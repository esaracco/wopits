<?php
/**
Javascript plugin - Notes comments

Scope: Note
Name: pcomm
Description: Manage notes comments
*/

require_once(__DIR__.'/../prepend.php');

?>

(() => {
'use strict';

let _popup;
let _textarea;

// METHOD _getEventSelector()
const _getEventSelector = (s) =>
  H.haveMouse() ? `.pcomm-popover ${s}` : `#postitCommentsPopup ${s}`;

/////////////////////////////////// PLUGIN ////////////////////////////////////

P.register('pcomm', class extends Wpt_postitCountPlugin {
  // METHOD constructor()
  constructor(settings) {
    super(settings);

    this.readonly = this.settings.readonly;

    if (this.readonly && !this.settings.count) {
      this.tag.classList.add('hidden');
    }

    // Create postit top comments icon
    this.addTopIcon('fa-comments', 'pcomm');

    this.settings._cache = this.settings.count ? [] : null;

    return this;
  }

  // METHOD refresh()
  refresh(d) {
    this.settings._cache = d.comments;
    this.setCount(d.comments.length);

    if (_popup) {
      this.open(true);
    }
  }

  // METHOD injectUserRef()
  injectUserRef(s) {
    const val = _textarea.value;
    const start = _textarea.selectionStart;
    let prev = val.substring(0, start).replace(/@([^\s]+)?$/, `@${s}`);
    let next = val.substring(start).replace(/^[^\s]+/, '');

    _textarea.value = prev + next;

    _textarea.focus();
    _textarea.selectionStart = _textarea.selectionEnd = prev.length;
  }

  // METHOD search()
  search(args, force) {
    const pc = _popup;
    const el = pc.querySelector('.result-container');
    const {wallId} = this.getIds();

    // LOCAL FUNCTION __fixHeight()
    const __fixHeight = () => {
      const wH = window.innerHeight - 30;
      const elB = el.getBoundingClientRect().bottom;
      if (elB > wH) {
        el.style.height = `${el.offsetHeight - (elB - wH)}px`;
      }
    };

    args.str = args.str.replace(/&/g, '');

    el.style.height = 'auto';
    H.show(el);

    H.fetch(
      'GET',
      `wall/${wallId}/searchUsers/${args.str}`,
      null,
      // success cb
      (d) => {
        const users = d.users || [];
        let html = '';

        users.forEach((item, i) => html += `<li class="${!i ? 'selected' : ''} list-group-item"><div class="label">${item.fullname}</div><div class="item-infos"><span>${item.username}</span></div></li></li>`);
        
        if (html) {
          const rc = pc.querySelector('.result-container');

          rc.style.width = `${_textarea.getBoundingClientRect().width}px`;
          rc.classList.add('shadow');
          pc.querySelector('.search').classList.add('shadow');
          _textarea.classList.add('autocomplete');

          setTimeout(__fixHeight, 50);
        } else {
          if (!args.str) {
            _textarea.value = _textarea.value.replace(/@/, '');
            H.displayMsg({
              type: 'warning',
              msg: `<?=_("The wall has not yet been shared with other users")?>`,
            });
          }
          this.reset();
        }
       
        pc.querySelector('.result').innerHTML = html;
      }
    );
  }

  // METHOD reset()
  reset(args = {}) {
    if (args.full) {
      args.field = true;
      args.users = true;
    }

    if (args.field) {
      _textarea.value = '';
    }

    H.hide(_popup.querySelector('.result-container'));
    _popup.querySelector('.result').innerHTML = '';

    _textarea.classList.remove('autocomplete');

    _popup.querySelectorAll('.shadow').forEach(
      (el) => el.classList.remove('shadow'));
  }

  // METHOD add()
  add(content) {
    const {wallId, cellId, postitId} = this.getIds();

    H.request_ws(
      'PUT',
      `wall/${wallId}/cell/${cellId}/postit/${postitId}/comment`,
      {
        content,
        postitTitle: this.postit().getTitle(),
        userFullname:
          P.get(document.getElementById('accountPopup'), 'account')
            .getProp('fullname'),
      },
      // success cb
      () => H.waitForDOMUpdate(
        () => window.dispatchEvent(new Event('resize'))));
  }

  // METHOD close()
  close() {
    if (_popup) {
      if (!H.haveMouse()) {
        bootstrap.Modal.getInstance(_popup).hide();
      } else {
        document.getElementById('popup-layer').click();
      }
    }
  }

  // METHOD open()
  open(refresh) {
    const {wallId, cellId, postitId} = this.getIds();

    if (this.settings._cache === null || this.settings._cache.length) {
      this._open(this.settings._cache, refresh);
    } else {
      H.fetch(
        'GET',
        `wall/${wallId}/cell/${cellId}/postit/${postitId}/comment`,
        null,
        // success cb
        (d) => {
          if (this.readonly && !d.length) {
            this.close();
          } else {
            this.settings._cache = d;
            this._open(d, refresh);
          }
        });
    }
  }

  // METHOD open()
  _open(d, refresh) {
    const userId = U.getId();
    const {wallId, cellId, postitId} = this.getIds();
    let content = '';

    // LOCAL FUNCTION __fixHeight()
    const __fixHeight = () => {
      const body = _popup.querySelector('.popover-body');

      if (body.clientHeight > body.querySelector('.content').clientHeight) {
        body.style.height = 'auto';
        _popup.classList.remove('have-scroll');
      }

      if (_popup.classList.contains('have-scroll')) return;

      const wH = window.innerHeight - 30;
      const bb = _popup.getBoundingClientRect();

      if (bb.bottom > wH) {
        const h = _popup.clientHeight - (bb.bottom - window.innerHeight);
        _popup.classList.add('have-scroll');
        body.style.height = `${h-60}px`;
      }
    };

    this.postit().setCurrent();

    (d || []).forEach((c) => {
      content += `<div class="msg-item" data-id="${c.id}" data-userid="${c.ownerid}"><div class="msg-title"><i class="far fa-user"></i> ${c.ownername || `<s><?=_("deleted")?></s>`}${(!this.readonly && c.ownerid === userId) ? `<button type="button" class="close" title="<?=_("Delete my comment")?>"><span><i class="fas fa-trash fa-xs"></i></span></button>` : ''}<div class="msg-date">${U.formatDate(c.creationdate, null, 'Y-MM-DD H:mm')}</div></div><div class="msg-body">${c.content.replace(/\n/g, '<br>')}</div></div>`;
    });

    content = content.replace(
        /(@[^\s\?\.:!,;"<@]+)/g, `<span class="msg-userref">$1</span>`);

    if (refresh) {
      _popup.querySelector('.content').innerHTML = content;

      // Resize only if popover
      if (_popup.classList.contains('popover')) {
        H.waitForDOMUpdate(__fixHeight);
      }
    } else if (content || !this.readonly) {
      const editing = this.readonly ? '' : `<div class="search mb-1"><button class="btn clear-textarea" type="button"><i class="fa fa-times"></i></button><textarea class="form-control" maxlength="<?=Wopits\DbCache::getFieldLength('postits_comments', 'content')?>"></textarea><div class="result-container"><ul class="result autocomplete list-group"></ul></div></div><div class="tip">${S.getCurrent('wall').tag.dataset.shared ? `<i class="far fa-lightbulb"></i> <?=_("Use @ to refer to another user.")?>` : `<?=_("Since the wall is not shared, you are the only one to see these comments.")?>`}</div><button type="button" class="btn btn-primary btn-xs"><?=_("Send")?></button>`;

      // Device without mouse: open a POPUP
      if (!H.haveMouse()) {
        H.loadPopup('postitComments', {
          cb: (p) => {
            const c = p.querySelector('.content');

            _popup = p;

            if (editing) {
              p.querySelector('.editing').innerHTML = editing;
              _textarea = p.querySelector('textarea');
            }

            c.dataset.wallid = wallId;
            c.dataset.cellid = cellId;
            c.dataset.postitid = postitId;

            c.innerHTML = content;

            // EVENT "hidden.bs.modal" on popup 
            p.addEventListener('hidden.bs.modal', (e) => {
              if (!this.readonly) {
                _textarea.value = '';
                this.reset();
              }
               _popup = undefined;
               this.postit().unsetCurrent();
            }, {once: true});
          }
        });
      // Device with mouse: open a POPOVER
      } else {
        H.openConfirmPopover({
          type: 'custom',
          placement: 'left',
          html_header: editing,
          customClass: 'msg-popover pcomm-popover',
          noclosure: true,
          item: this.tag.querySelector('i'),
          title: `<i class="fas fa-comments fa-fw"></i> <?=_("Comments")?>`,
          content: `<div class="content" data-wallid="${wallId}" data-cellid="${cellId}" data-postitid="${postitId}">${content}</div>`,
          onConfirm: () => {
            const content = H.noHTML(_textarea.value);
            if (content) {
              this.add(content);
              _textarea.value = '';
              _textarea.focus();
            }
          },
          onClose: () => {
            _popup = undefined;
            this.postit().unsetCurrent();
          },
          then: (p) => {
            _popup = p;
            _textarea = p.querySelector('textarea');
            __fixHeight();
          }
      });
    }
    }
  }
});

//////////////////////////////////// INIT ////////////////////////////////////

document.addEventListener('DOMContentLoaded', () => {
  if (H.isLoginPage()) return;

  // EVENT "click"
  document.addEventListener('click', (e) => {
    const el = e.target;

    if (S.get('postit-creating')) return;

    // EVENT "click" on postit comments button
    if (el.matches('.pcomm,.pcomm *')) {
      P.get((el.tagName === 'DIV') ? el : el.parentNode, 'pcomm').open();
    } else if (el.matches(_getEventSelector('*'))) {
      // EVENT "click" on comments "clear textarea" button
      if (el.matches('.clear-textarea,.clear-textarea *')) {
        _textarea.value = '';
        S.getCurrent('pcomm').reset();
        _textarea.focus();

      // EVENT "click" on comments "submit" button
      } else if (el.matches('.btn-primary')) {
        const content = H.noHTML(_textarea.value);
        if (content) {
          S.getCurrent('pcomm').add(content);
          _textarea.value = '';
          _textarea.focus();
        }

      // EVENT "click" on comments users list
      } else if (el.matches('.result .list-group-item,'+
                            '.result .list-group-item *')) {
        const pcomm = S.getCurrent('pcomm');

        // Selected the current item in users search list and close the
        // list
        pcomm.injectUserRef(
          (el.tagName === 'LI' ? el : el.closest('li'))
             .querySelector('span').innerText);
        pcomm.reset();

      // EVENT "click" on "delete comment" button
      } else if (el.matches('.msg-item .close,.msg-item .close *')) {
        const data = el.closest('.content').dataset;
        const item = el.closest('.msg-item');

        H.preventDefault(e);
        e.stopImmediatePropagation();

        if (H.haveMouse()) {
          _textarea.focus();
        }

        H.request_ws(
          'DELETE',
          `wall/${data.wallid}/cell/${data.cellid}/postit/`+
            `${data.postitid}/comment/${item.dataset.id}`,
          null,
          // success cb
          () => H.waitForDOMUpdate(() =>
                  window.dispatchEvent(new Event('resize'))));
      }
    }
  });

  // EVENTS "keyup & keydown"
  const _textareaEventK = (e) => {
    const el = e.target;

    if (!_popup) return;

    // EVENTS "keyup & keydown" on comments textarea
    if (el.matches(_getEventSelector('textarea'))) {
      const list = el.closest(`[class*="-body"]`).querySelectorAll('li');

      if (!list.length) return;

      const k = e.which;
      const pcomm = S.getCurrent('pcomm');

      // If ESC, close the users search
      if (k === 27) {
        e.stopImmediatePropagation();
        pcomm.reset();

      // Arrow up or arrow down
      } else if (k === 38 || k === 40) {
        if (list.length === 1) {
          pcomm.reset();
        } else {
          e.stopImmediatePropagation();
          H.preventDefault(e);

          if (e.type === 'keyup') {
            // LOCAL FUNCTION __select()
            const __select = (i, type) => {
              // Arrow up.
              if (i && type === 'up') {
                const el = list[i-1];

                list[i].classList.remove('selected');
                el.classList.add('selected');
                el.scrollIntoView(false);

              // Arrow down
              } else if (i < list.length - 1 && type === 'down') {
                const el = list[i+1];

                list[i].classList.remove('selected');
                el.classList.add('selected');
                el.scrollIntoView(false);
              }
            };

            for (let i = 0, iLen = list.length; i < iLen; i++) {
              if (list[i].classList.contains('selected')) {
                if (i === 0 && k === 38 || i === iLen - 1 && k === 40) {
                  pcomm.reset();
                  return;
                } else {
                  __select(i, (k === 38) ? 'up': 'down');
                  return;
                }
              }
            }
          }
        }
      }
    }
  };
  document.addEventListener('keyup', _textareaEventK);
  document.addEventListener('keydown', _textareaEventK);

  // EVENTS "keyup & click"
  const _textareaEventKC = (e) => {
    const el = e.target;

    // EVENTS "keyup & click" on comments textarea
    if (el.matches(_getEventSelector('textarea'))) {
      const k = e.which;
      const prev = el.value.substring(0, el.selectionStart);
      // Keys to ignore
      const ignore = (e.ctrlKey || k === 27 || k === 16 || k === 225);
      let m;

      // Submit if needed
      if (k === 13) {
        if (e.ctrlKey) {
          el.closest(`[class*="-body"]`)
              .querySelector('.btn-primary').click();
        } else {
          H.preventDefault(e);
          return;
        }
      }

      // Nothing if we must ignore the key
      if (S.getCurrent('wall').tag.dataset.shared && !ignore) {
        // Display users search list if needed
        if ( (m = prev.match(/(^|\s)@([^\s]+)?$/)) ) {
          S.getCurrent('pcomm').search({str: m[2] || ''});
        } else if (el.classList.contains('autocomplete')) {
          S.getCurrent('pcomm').reset();
        }
      }
    }
  };
  document.addEventListener('keyup', _textareaEventKC);
  document.addEventListener('click', _textareaEventKC);

  // EVENT "keypress"
  document.addEventListener('keypress', (e) => {
    const el = e.target;

    // EVENT "keypress" on comments textarea
    if (el.matches(_getEventSelector('textarea'))) {
      // If enter on selected users search item, select it
      if (e.which === 13 && el.classList.contains('autocomplete')) { 
        e.stopImmediatePropagation();
        H.preventDefault(e);

        el.closest(`[class*="-body"]`)
          .querySelector('.result .list-group-item.selected').click();
      }
    }
  });
});

})();
