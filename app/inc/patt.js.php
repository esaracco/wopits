<?php
/**
Javascript plugin - Notes attachments

Scope: Note
Name: patt
Description: Manage notes attachments
*/

require_once(__DIR__.'/../prepend.php');

?>

(() => {
'use strict';

let _mainPopup;
let _editPopup;

/////////////////////////////////// PLUGIN ////////////////////////////////////

P.register('patt', class extends Wpt_postitCountPlugin {
  // METHOD constructor()
  constructor(settings) {
    super(settings);

    if (this.settings.readonly && !this.settings.count) {
      this.tag.classList.add('hidden');
    }

    // Create postit top attachments icon
    this.addTopIcon('fa-paperclip', 'patt');

    return this;
  }

  // METHOD update()
  async update ({description, id, title}) {
    const {wallId, cellId, postitId} = this.getIds();

    const r = await H.fetch(
      'POST',
      `wall/${wallId}/cell/${cellId}/postit/${postitId}/attachment/${id}`,
      {description, title});

    if (r.error) {
      if (r.error_msg) {
        H.raiseError(null, r.error_msg);
      }
    }
    else {
      this.display();
    }
  }

  // METHOD delete()
  delete(id) {
    const {wallId, cellId, postitId} = this.getIds ();

    H.request_ws(
      'DELETE',
      `wall/${wallId}/cell/${cellId}/postit/${postitId}/attachment/${id}`,
      null,
      // success cb
      (d) => {
        if (d.error_msg) {
          H.raiseError(null, d.error_msg);
        } else {
          _mainPopup.querySelector(`.accordion-item[data-id="${id}"]`).remove();

          this.decCount();

          if (!_mainPopup.querySelector('.accordion-item')) {
            _mainPopup.querySelector('.list-group').innerHTML =
              `<?=_("The note has no attached file.")?>`;
          }
        }
      }
    );
  }

  // METHOD getTemplate()
  getTemplate(item, noWriteAccess) {
    const tz = U.get('timezone');
    const d = `<button type="button" data-action="delete"><i class="fas fa-trash fa-xs fa-fw"></i></button>`;
    const owner =
      (item.ownerid !== undefined && item.ownerid !== U.getId()) ?
        item.ownername || `<s><?=_("Former user")?></s>` : '';

    return H.createElement('div', {className: 'accordion-item'}, {id: item.id, url: item.link, icon: item.icon, fname: H.htmlEscape(item.name), description: H.htmlEscape(item.description || ''), title: H.htmlEscape(item.title || ''), creationdate: U.formatDate(item.creationdate), size: H.getHumanSize(item.size), owner: H.htmlEscape(owner)}, `<div class="accordion-header" id="hfile${item.id}"><div class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#cfile${item.id}" aria-expanded="false" aria-controls="cfile${item.id}"><i class="fa fa-lg ${item.icon} fa-fw"></i> <span>${item.title || item.name}</div></div><div id="cfile${item.id}" class="accordion-collapse collapse" aria-labelledby="hfile${item.id}" data-bs-parent="#pa-accordion"><div class="accordion-body"></div></div></div>`);
  }

  // METHOD upload()
  upload() {
    document.getElementById('postit-attachment').click();
  }

  // METHOD display()
  display() {
    const {wallId, cellId, postitId} = this.getIds();

    if (this.getCount()) {
      H.fetch (
        'GET',
        `wall/${wallId}/cell/${cellId}/postit/${postitId}/attachment`,
        null,
        // success cb
        (d) => this._display(d));
    } else {
      this._display();
    }
  }

  // METHOD _display()
  _display(d) {
    H.loadPopup('postitAttachments', {
      open: false,
      init: (p) => {
        _mainPopup = p;
        _editPopup = p.querySelector('.edit-popup');
      },
      cb: (p) => {
        const writeAccess = !this.settings.readonly;
        const body = p.querySelector('.modal-body .list-group');

        if (d) {
          body.innerHTML = '';
          d.files.forEach(
            (a) => body.append(this.getTemplate(a, !writeAccess)));
        } else {
          body.innerHTML = `<?=_("The note has no attached file.")?>`;
        }

        p.querySelectorAll('.btn-primary').forEach((el) =>
          el.style.display = writeAccess ? 'block' : 'none');
        p.dataset.noclosure = true;
        H.openModal({item: p});

        // EVENT "click" on popup
        const __click = (e) => this._eventClick(e);
        p.addEventListener('click', __click);

        // EVENT "hidden.bs.collapse" on attachment row
        const __hiddenCollapse = (e) => this._eventHiddenCollapse(e);
        p.addEventListener('hidden.bs.collapse', __hiddenCollapse);

        // EVENT "show.bs.collapse" on attachment row
        const __showCollapse = (e) => this._eventShowCollapse(e);
        p.addEventListener('show.bs.collapse', __showCollapse);

        // EVENT "shown.bs.collapse" on attachment row
        const __shownCollapse = (e) => this._eventShownCollapse(e);
        p.addEventListener('shown.bs.collapse', __shownCollapse);

        // Remove event listeners on closing
        p.addEventListener('hide.bs.modal', (e) => {
          p.removeEventListener('shown.bs.collapse', __shownCollapse);
          p.removeEventListener('show.bs.collapse', __showCollapse);
          p.removeEventListener('hidden.bs.collapse', __hiddenCollapse);
          p.removeEventListener('click', __click);
        }, {once: true});
      }
    });
  }

  // METHOD _eventShownCollapse()
  _eventShownCollapse(e) {
    H.setAutofocus(e.target);
  }

  // METHOD _eventShowCollapse()
  _eventShowCollapse(e) {
    const el = e.target;
    const li = el.closest('.accordion-item');
    const popup = _editPopup;
    const body = li.querySelector('.accordion-body');
    const liActive = _mainPopup.querySelector('div.active');
    const fileVal = li.dataset.fname;
    const fileInfosVal = (li.dataset.owner ? li.dataset.owner + ', ' : '') +
                          li.dataset.creationdate + ', ' +
                          li.dataset.size;
    const titleVal = li.dataset.title;
    const descVal = li.dataset.description;
    const img = popup.querySelector('.img');
    const isImg = fileVal.match(/\.(jpe?g|gif|png)$/);

    li.classList.add('no-bottom-radius');

    liActive && liActive.classList.remove('active');
    li.classList.add('active');

    popup.dataset.id = li.dataset.id;

    H.show(popup.querySelector('.title'));
    H.show(popup.querySelector('.description'));
    img.querySelector('img').setAttribute('src', '');
    H.hide(img);

    popup.querySelector('.file').innerText = fileVal;
    popup.querySelector('.file-infos').innerHTML = fileInfosVal;

    if (H.checkAccess(<?=WPT_WRIGHTS_RW?>)) {
      // Display "Save" button
      H.show(popup.querySelector('.btn-primary'), 'inline-block');
      // Display "Delete" button
      H.show(popup.querySelector('.btn-secondary'), 'inline-block');
      popup.querySelectorAll('.ro').forEach((el) => H.hide(el));
      popup.querySelectorAll('.adm').forEach((el) => H.show(el));

      popup.querySelector('.title input').value = titleVal;
      popup.querySelector('.description textarea').value = descVal;

      H.setAutofocus(popup);
    } else {
      // Hide "Save" button
      H.hide(popup.querySelector('.btn-primary'));
      // Hide "Delete" button
      H.hide(popup.querySelector('.btn-secondary'));
      popup.querySelectorAll('.ro').forEach ((el) => H.show(el));
      popup.querySelectorAll('.adm').forEach((el) => H.hide(el));

      if (titleVal) {
        popup.querySelector('.title .ro').innerText = titleVal;
      } else {
        H.hide(popup.querySelector('.title'));
      }

      if (descVal) {
        popup.querySelector('.description .ro').innerHTML = H.nl2br(descVal);
      } else {
        H.hide(popup.querySelector('.description'));
      }
    }

    if (isImg) {
      img.querySelector('img').setAttribute('src', li.dataset.url);
      H.show(img);
    }

    $(body.appendChild(popup)).show('fade');
  }

  // METHOD _eventHiddenCollapse()
  _eventHiddenCollapse(e) {
    const el = e.target;

    el.classList.remove('no-bottom-radius');
    el.classList.remove('active');
  }

  // METHOD _eventClick()
  _eventClick (e) {
    const el = e.target;
    const postitTag = this.postit().tag;

    // EVENT "click" on attachment thumbnail to preview
    if (el.matches('.edit-popup img')) {
      const viewer = H.createElement('div',
        {id: 'img-viewer'}, null, `<img src="${el.getAttribute('src')}">`);

      e.stopImmediatePropagation();

      document.body.appendChild(viewer);

      H.openPopupLayer(() => viewer.remove());

    // EVENT "click" on edit attachment buttons
    } else if (el.matches('.edit-popup button,.edit-popup button *')) {
      const btn = (el.tagName === 'BUTTON') ? el : el.closest('button');
      const action = btn.dataset.action;
      const item = btn.closest('.accordion-item');

      e.stopImmediatePropagation();

      switch (action) {
        // "Delete" button
        case 'delete':
          const id = Number(item.dataset.id);

          item.classList.add('active');
          H.openConfirmPopover({
            item: btn,
            title: `<i class="fas fa-trash fa-fw"></i> <?=_("Delete")?>`,
            content: `<?=_("Delete the file?")?>`,
            onClose: () => {
              const active =
                document.querySelector('.modal .accordion-item.active');

              if (active && active.getAttribute('aria-expanded') !== 'true') {
                active.classList.remove('active');
              }
            },
            onConfirm: () => P.get(
              postitTag.querySelector('.patt'), 'patt').delete(id),
          });
          break;
        // "Download" button
        case 'download':
          H.download(item.dataset);
          break;
        // "Save" button
        case 'save':
          P.get(postitTag.querySelector('.patt'), 'patt').update({
            id: Number(_editPopup.dataset.id),
            title: H.noHTML(_editPopup.querySelector('input').value),
            description: H.noHTML(_editPopup.querySelector('textarea').value),
          });
          break;
      }
    }
  }

  // METHOD open()
  open(refresh) {
    this.postit().edit({}, () => this.display());
  }
});

//////////////////////////////////// INIT ////////////////////////////////////

document.addEventListener('DOMContentLoaded', () => {
  if (H.isLoginPage()) return;

  // EVENT "click"
  document.addEventListener('click', (e) => {
    const el = e.target;

    if (S.get('postit-creating')) return;

    // EVENT "click" on attachment count
    if (el.matches('.patt,.patt *')) {
      const tag = (el.tagName === 'DIV') ? el : el.parentNode;
      const patt = P.get(tag, 'patt');

      e.stopImmediatePropagation();

      if (H.checkAccess(<?=WPT_WRIGHTS_RW?>)) {
        patt.open();
      } else {
        P.get(tag.closest('.postit'), 'postit').setCurrent();
        patt.display();
      }
    }
  });

  // // Create input to upload postit attachments
  H.createUploadElement({
    attrs: {id: 'postit-attachment'},
    onChange: (e) => {
      const el = e.target;

      if (!el.files || !el.files.length) return;

      const postit = S.getCurrent('postit');
      const settings = postit.settings;

      H.getUploadedFiles(e.target.files, 'all', (e, file) => {
        el.value = '';

        if (_mainPopup.querySelector(
            `.list-group .accordion-item`+
            `[data-fname="${H.htmlEscape(file.name)}"]`)) {
          H.displayMsg({
            type: 'warning',
            msg: `<?=_("The file is already linked to the note")?>`,
          });
          return;
        }

        if (H.checkUploadFileSize({size: e.total}) && e.target.result) {
          H.fetchUpload(
            `wall/${settings.wallId}/cell/${settings.cellId}/`+
              `postit/${settings.id}/attachment`,
            {
              name: file.name,
              size: file.size,
              item_type: file.type,
              content: e.target.result,
            },
            // success cb
            (d) => {
              const patt = postit.getPlugin('patt');
              const body = _mainPopup.querySelector('.list-group');
              const accordion = body.querySelector('.accordion-item');

              _mainPopup.querySelector('.modal-body').scrollTop = 0;

              if (d.error_msg) {
                H.displayMsg({type: 'warning', msg: d.error_msg});
                return;
              }

              if (accordion) {
                const current = _mainPopup.querySelector('div.collapse.show');

                if (current) {
                  bootstrap.Collapse.getInstance(current).hide();
                }
              } else {
                body.innerHTML = '';
              }

              body.prepend(patt.getTemplate(d));
              patt.incCount();

              if (accordion) {
                H.waitForDOMUpdate(() => accordion.click());
              }
            });
        }
      });
    },
  });
});

})();
