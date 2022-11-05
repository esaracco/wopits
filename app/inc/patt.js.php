<?php
/**
Javascript plugin - Notes attachments

Scope: Note
Element: .patt
Description: Manage notes attachments
*/

require_once(__DIR__.'/../prepend.php');

$Plugin = new Wopits\jQueryPlugin('patt');
echo $Plugin->getHeader();

?>

/////////////////////////////////// PRIVATE //////////////////////////////////

let _$mainPopup;
let _$editPopup;

/////////////////////////////////// PUBLIC ///////////////////////////////////

<?=$Plugin->getPublicSection()?>

// Inherit from Wpt_postitCountPlugin
Plugin.prototype = Object.create(Wpt_postitCountPlugin.prototype);
Object.assign(Plugin.prototype, {
  // METHOD init()
  init() {
    const settings = this.settings;

    if (settings.readonly && !settings.count) {
      this.element[0].classList.add('hidden');
    }

    // Create postit top attachments icon
    this.addTopIcon('fa-paperclip', 'patt');

    return this;
  },

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
  },

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
          const popup = _$mainPopup[0];

          popup.querySelector(`.accordion-item[data-id="${id}"]`).remove();

          this.decCount();

          if (!popup.querySelector('.accordion-item')) {
            popup.querySelector('.list-group').innerHTML =
              `<?=_("The note has no attached file.")?>`;
          }
        }
      }
    );
  },

  // METHOD getTemplate()
  getTemplate(item, noWriteAccess) {
    const tz = wpt_userData.settings.timezone;
    const d = `<button type="button" data-action="delete"><i class="fas fa-trash fa-xs fa-fw"></i></button>`;
    const owner =
      (item.ownerid !== undefined && item.ownerid != wpt_userData.id) ?
        item.ownername || `<s><?=_("Former user")?></s>` : '';

    return H.createElement('div', {className: 'accordion-item'}, {id: item.id, url: item.link, icon: item.icon, fname: H.htmlEscape(item.name), description: H.htmlEscape(item.description || ''), title: H.htmlEscape(item.title || ''), creationdate: H.getUserDate(item.creationdate), size: H.getHumanSize(item.size), owner: H.htmlEscape(owner)}, `<div class="accordion-header" id="hfile${item.id}"><div class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#cfile${item.id}" aria-expanded="false" aria-controls="cfile${item.id}"><i class="fa fa-lg ${item.icon} fa-fw"></i> <span>${item.title || item.name}</div></div><div id="cfile${item.id}" class="accordion-collapse collapse" aria-labelledby="hfile${item.id}" data-bs-parent="#pa-accordion"><div class="accordion-body"></div></div></div>`);
  },

  // METHOD upload()
  upload() {
    document.getElementById('postit-attachment').click();
  },

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
  },

  // METHOD _display()
  _display(d) {
    H.loadPopup('postitAttachments', {
      open: false,
      init: ($p) => {
        _$mainPopup = $p;
        _$editPopup = $p.find('.edit-popup');
      },
      cb: ($p) => {
        const p = $p[0];
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
  },

  // METHOD _eventShownCollapse()
  _eventShownCollapse(e) {
    H.setAutofocus(e.target);
  },

  // METHOD _eventShowCollapse()
  _eventShowCollapse(e) {
    const el = e.target;
    const li = el.closest('.accordion-item');
    const popup = _$editPopup[0];
    const body = li.querySelector('.accordion-body');
    const liActive = _$mainPopup[0].querySelector('div.active');
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

    if (H.checkAccess(<?=WPT_WRIGHTS_ADMIN?>)) {
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
  },

  // METHOD _eventHiddenCollapse()
  _eventHiddenCollapse(e) {
    const el = e.target;

    el.classList.remove('no-bottom-radius');
    el.classList.remove('active');
  },

  // METHOD _eventClick()
  _eventClick (e) {
    const el = e.target;
    const $postit = this.postit().element;

    // EVENT "click" on attachment thumbnail to preview
    if (el.matches('.edit-popup img')) {
      const viewer = H.createElement('div',
        {id: 'img-viewer'}, null, `<img src="${el.getAttribute('src')}">`);

      e.stopImmediatePropagation();

      document.body.appendChild(viewer);

      H.openPopupLayer(() => viewer.remove());

    // EVENT "click" on edit attachment buttons
    } else if (el.matches('.edit-popup button,.edit-popup button *')) {
      const btn = (el.tagName=== 'BUTTON') ? el : el.closest('button');
      const action = btn.dataset.action;
      const item = btn.closest('.accordion-item');

      e.stopImmediatePropagation();

      switch (action) {
        // "Delete" button
        case 'delete':
          const id = item.dataset.id;

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
            onConfirm: () => $postit.find('.patt').patt('delete', id),
          });
          break;
        // "Download" button
        case 'download':
          H.download(item.dataset);
          break;
        // "Save" button
        case 'save':
          const popup = _$editPopup[0];

          $postit.find('.patt').patt('update', {
            id: popup.dataset.id,
            title: H.noHTML(popup.querySelector('input').value),
            description: H.noHTML(popup.querySelector('textarea').value),
          });
          break;
      }
    }
  },

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
      const $patt = $((el.tagName === 'DIV') ? el : el.parentNode);

      e.stopImmediatePropagation();

      if (H.checkAccess(<?=WPT_WRIGHTS_RW?>)) {
        $patt.patt('open');
      } else {
        $patt.closest('.postit').postit('setCurrent');
        $patt.patt('display');
      }
    }
  });

  // // Create input to upload postit attachments
  H.createUploadElement({
    attrs: {id: 'postit-attachment'},
    onChange: (e) => {
      const el = e.target;

      if (!el.files || !el.files.length) return;

      const plugin = S.getCurrent('postit').postit('getClass');
      const settings = plugin.settings;

      H.getUploadedFiles(e.target.files, 'all', (e, file) => {
        el.value = '';

        if (_$mainPopup.find(
              `.list-group .accordion-item`+
                 `[data-fname="${H.htmlEscape(file.name)}"]`).length) {
          return H.displayMsg({
            title: `<?=_("Attached files")?>`,
            type: 'warning',
              msg: `<?=_("The file is already linked to the note")?>`,
            });
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
              const pa = plugin.getPlugin('patt');
              const body = _$mainPopup[0].querySelector('.list-group');

              _$mainPopup.find('.modal-body').scrollTop(0);
              _$mainPopup.find('div.collapse.show').collapse('hide');

              if (d.error_msg) {
                return H.displayMsg({
                  title: `<?=_("Attached files")?>`,
                  type: 'warning',
                  msg: d.error_msg,
                });
              }

              if (!body.querySelector('.accordion-item')) {
                body.innerHTML = '';
              }

              body.prepend(pa.getTemplate(d));
              pa.incCount();

              H.waitForDOMUpdate(
                () => body.querySelector('.accordion-item').click());
            });
        }
      });
    },
  });
});

<?=$Plugin->getFooter()?>
