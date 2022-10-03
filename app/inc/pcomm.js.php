<?php
/**
  Javascript plugin - Notes comments

  Scope: Note
  Element: .pcomm
  Description: Manage notes comments
*/

  require_once (__DIR__.'/../prepend.php');

  $Plugin = new Wopits\jQueryPlugin ('pcomm', '', 'postitElement');
  echo $Plugin->getHeader ();

?>

  let $_popup,
      _textarea;

  /////////////////////////// PRIVATE METHODS ///////////////////////////

  // METHOD _getEventSelector ()
  const _getEventSelector = (s)=>
  {
    return H.haveMouse()?`.pcomm-popover ${s}`:`#postitCommentsPopup ${s}`;
  };

  /////////////////////////// PUBLIC METHODS ////////////////////////////

  // Inherit from Wpt_postitCountPlugin
  Plugin.prototype = Object.create(Wpt_postitCountPlugin.prototype);
  Object.assign (Plugin.prototype,
  {
    // METHOD init ()
    init ()
    {
      const settings = this.settings;

      if (settings.readonly && !settings.count)
        this.element[0].style.display = "none";

      $(`<i data-action="pcomm" class="fa-fw fas fa-comments"></i><span ${settings.count?"":`style="display:none"`} class="wpt-badge">${settings.count}</span></div>`).appendTo (this.element);

      this.settings._cache = settings.count ? [] : null;

      return this;
    },

    // METHOD refresh ()
    refresh (d)
    {
      this.settings._cache = d.comments;

      this.setCount (d.comments.length);

      if ($_popup)
        this.open (true);
    },

    // METHOD injectUserRef ()
    injectUserRef (s)
    {
      const val = _textarea.value,
            start = _textarea.selectionStart;
      let prev = val.substring(0, start).replace (/@([^\s]+)?$/, `@${s}`),
          next = val.substring(start).replace (/^[^\s]+/, "");

      _textarea.value = prev + next;

      _textarea.focus ();
      _textarea.selectionStart = _textarea.selectionEnd = prev.length;
    },

    // METHOD search ()
    search (args, force)
    {
      const pc = $_popup[0],
            el = pc.querySelector (".result-container"),
            {wallId} = this.getIds ();

      // INTERNAL FUNCTION __resize ()
      const __resize = ()=>
        {
          const wH = window.innerHeight - 30,
                elB = el.getBoundingClientRect().bottom;

          if (elB > wH)
            el.style.height = `${el.offsetHeight - (elB - wH)}px`;
        };

      args.str = args.str.replace (/&/, "");

      el.style.height = "auto";
      el.style.display = "block";

      H.fetch (
        "GET",
        `wall/${wallId}/searchUsers/${args.str}`,
        null,
        // success cb
        (d) =>
        {
          const users = d.users||[];
          let html = "";

          users.forEach ((item, i) => html += `<li class="${!i?"selected":""} list-group-item"><div class="label">${item.fullname}</div><div class="item-infos"><span>${item.username}</span></div></li></li>`);
          
          if (html)
          {
            const rc = pc.querySelector (".result-container");

            rc.style.width = `${_textarea.getBoundingClientRect().width}px`;
            rc.classList.add ("shadow");

            pc.querySelector (".search").classList.add ("shadow");

            _textarea.classList.add ("autocomplete");

            setTimeout (__resize, 50);
          }
          else
          {
            if (!args.str)
              H.displayMsg ({
                title: `<?=_("Comments")?>`,
                type: "warning",
                msg: `<?=_("The wall has not yet been shared with other users")?>`
              });

            this.reset ();
          }
         
          pc.querySelector(".result").innerHTML = html;
        }
      );
    },

    // METHOD reset ()
    reset (args)
    {
      const pc = $_popup[0];

      if (!args)
        args = {};

      if (Boolean(args.full))
      {
        args["field"] = true;
        args["users"] = true;
      }

      pc.querySelector(".result-container").style.display = "none";
      pc.querySelector(".result").innerHTML = "";

      if (Boolean(args.field))
        _textarea.value = "";

      _textarea.classList.remove ("autocomplete");

      pc.querySelectorAll(".shadow").forEach (el=>
        el.classList.remove ("shadow"));
    },

    // METHOD add ()
    add (content)
    {
      const {wallId, cellId, postitId} = this.getIds ();

      H.request_ws (
        "PUT",
        `wall/${wallId}/cell/${cellId}/postit/${postitId}/comment`,
        {
          content,
          postitTitle: this.postit().getTitle (),
          userFullname: $("#accountPopup").account ("getProp", "fullname")
        },
        // success cb
        () => H.waitForDOMUpdate (
                ()=> window.dispatchEvent (new Event ("resize"))));
    },

    // METHOD close ()
    close ()
    {
      if ($_popup)
      {
        if (!H.haveMouse ())
          $_popup.modal ("hide");
        else
          document.getElementById("popup-layer").click ();
      }
    },

    // METHOD open ()
    open (refresh)
    {
      const {wallId, cellId, postitId} = this.getIds ();

      if (this.settings._cache === null || this.settings._cache.length)
        this._open (this.settings._cache, refresh);
      else
        H.fetch (
          "GET",
          `wall/${wallId}/cell/${cellId}/postit/${postitId}/comment`,
          null,
          // success cb
          (d)=>
          {
            this.settings._cache = d;
            this._open (d, refresh);
          });
    },

    // METHOD open ()
    _open (d, refresh)
    {
      const plugin = this,
            userId = wpt_userData.id,
            {wallId, cellId, postitId} = plugin.getIds ();
      let content = "";

      // INTERNAL FUNCTION __resize ()
      const __resize = ()=>
        {
          const $body = $_popup.find (".popover-body");

          if ($body.height () - 145 > $body.find(".content").height ())
          {
            $body.css ("height", "auto");
            $_popup.removeClass ("have-scroll");
          }

          if ($_popup.hasClass ("have-scroll")) return;

          const wH = window.innerHeight - 30,
                bb = $_popup[0].getBoundingClientRect ();

          if (bb.bottom > wH)
          {
            const h = $_popup.height () -
                        (bb.bottom - window.innerHeight);

            $_popup.addClass ("have-scroll");
            $body.css ("height", `${h-53}px`);
          }
        };

      plugin.postit().setCurrent ();

      (d || []).forEach((c) => {
        content += `<div class="msg-item" data-id="${c.id}" data-userid="${c.ownerid}"><div class="msg-title"><i class="far fa-user"></i> ${c.ownername || `<s><?=_("deleted")?></s>`}${c.ownerid === userId ? `<button type="button" class="close" title="<?=_("Delete my comment")?>"><span><i class="fas fa-trash fa-xs"></i></span></button>` : ''}<div class="msg-date">${H.getUserDate(c.creationdate, null, 'Y-MM-DD H:mm')}</div></div><div class="msg-body">${c.content.replace(/\n/g, '<br>')}</div></div>`;
      });

      //FIXME
      content = content.replace(
          /(@[^\s\?\.:!,;"<@]+)/g, `<span class="msg-userref">$1</span>`);

      if (refresh)
      {
        $_popup.find (".content").html (content);

        // Resize only if popover
        if ($_popup[0].classList.contains ("popover"))
          H.waitForDOMUpdate (__resize);
      }
      else if (content || !plugin.settings.readonly)
      {
        // Device without mouse: open a POPUP
        if (!H.haveMouse ())
          H.loadPopup ("postitComments", {
            cb:($p)=>
            {
              const c = $p[0].querySelector (".content");

              $_popup = $p;
              _textarea = $p[0].querySelector ("textarea");

              c.dataset.wallid = wallId;
              c.dataset.cellid = cellId;
              c.dataset.postitid = postitId;

              c.innerHTML = content;

              // EVENT "hidden.bs.modal" on popup 
              $p[0].addEventListener ("hidden.bs.modal", (e)=>
                {
                  _textarea.value = "";

                  $_popup.pcomm ("reset");
                  $_popup = undefined;

                  plugin.postit().unsetCurrent ();

                }, {once: true});
            }
          });
        // Device with mouse: open a POPOVER
        else
          H.openConfirmPopover ({
            type: "custom",
            placement:"left",
            html_header: plugin.settings.readonly ? "" : `<button class="btn clear-textarea" type="button"><i class="fa fa-times"></i></button><div class="search mb-1"><textarea class="form-control" maxlength="<?=Wopits\DbCache::getFieldLength('postits_comments', 'content')?>"></textarea><div class="result-container"><ul class="result autocomplete list-group"></ul></div></div><div class="tip"><i class="far fa-lightbulb"></i> <?=_("Use @ to refer to another user.")?></div><button type="button" class="btn btn-primary btn-xs"><?=_("Send")?></button>`,
            customClass: "msg-popover pcomm-popover",
            noclosure: true,
            item: plugin.element,
            title: `<i class="fas fa-comments fa-fw"></i> <?=_("Comments")?>`,
            content: `<div class="content" data-wallid="${wallId}" data-cellid="${cellId}" data-postitid="${postitId}">${content}</div>`,
            cb_ok:($p)=>
            {
              const content = H.noHTML (_textarea.value);

              if (!content) return;

              this.add (content);
              _textarea.value = "";
              _textarea.focus ();
            },
            cb_close:()=>
            {
              $_popup = undefined;
              plugin.postit().unsetCurrent ();
            },
            cb_after:($p)=>
            {
              const p = $p[0];

              $_popup = $p;
              _textarea = p.querySelector ("textarea");

              __resize ();
            }
        });
      }
    }
  });

  /////////////////////////// AT LOAD INIT //////////////////////////////

  document.addEventListener ('DOMContentLoaded', () => {
    if (H.isLoginPage ()) return;

    // EVENT "click"
    document.body.addEventListener ('click', (e) => {
        const el = e.target;

        // EVENT "click" on postit comments button
        if (el.matches('.pcomm,.pcomm *')) {
          $((el.tagName == 'DIV') ? el : el.parentNode).pcomm('open');
        } else if (el.matches(_getEventSelector('*'))) {
          // EVENT "click" on comments "clear textarea" button
          if (el.matches('.clear-textarea,.clear-textarea *')) {
            _textarea.value = '';
            S.getCurrent('pcomm').pcomm('reset');
            _textarea.focus();

          // EVENT "click" on comments "submit" button
          } else if (el.matches('.btn-primary')) {
            const content = H.noHTML(_textarea.value);
            if (!content) return;
            S.getCurrent('pcomm').pcomm ('add', content);
            _textarea.value = '';
            _textarea.focus ();

          // EVENT "click" on comments users list
          } else if (el.matches('.result .list-group-item,'+
                                '.result .list-group-item *')) {
            const pcomm = S.getCurrent('pcomm').pcomm('getClass');

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

            e.preventDefault();
            e.stopImmediatePropagation();

            if (H.haveMouse()) {
              _textarea.focus();
            }

            H.request_ws (
              'DELETE',
              `wall/${data.wallid}/cell/${data.cellid}/postit/`+
                `${data.postitid}/comment/${item.dataset.id}`,
              null,
              // success cb
              () => H.waitForDOMUpdate(
                     () => window.dispatchEvent(new Event('resize'))));
          }
        }
      });

    // EVENTS "keyup & keydown"
    const _textareaEventK = (e) => {
      const el = e.target;

      // EVENTS "keyup & keydown" on comments textarea
      if (el.matches(_getEventSelector('textarea'))) {
        const list = el.closest(`[class*="-body"]`).querySelectorAll('li');

        if (!list.length) return;

        const k = e.which;
        const pcomm = S.getCurrent('pcomm').pcomm('getClass');

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
            e.preventDefault();

            if (e.type === 'keyup') {
              // INTERNAL FUNCTION __select ()
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
                    __select (i, k === 38 ? 'up': 'down');
                    return;
                  }
                }
              }
            }
          }
        }
      }
    };
    document.body.addEventListener('keyup', _textareaEventK);
    document.body.addEventListener('keydown', _textareaEventK);

    // EVENTS "keyup & click"
    const _textareaEventKC = (e) => {
      const el = e.target;

      // EVENTS "keyup & click" on comments textarea
      if (el.matches (_getEventSelector('textarea'))) {
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
            return e.preventDefault();
          }
        }

        // Nothing if we must ignore the key
        if (!ignore) {
          // Display users search list if needed
          if ( (m = prev.match(/(^|\s)@([^\s]+)?$/)) ) {
            S.getCurrent('pcomm').pcomm('search', {str: m[2] || ''});
          } else if (el.classList.contains('autocomplete')) {
            S.getCurrent('pcomm').pcomm('reset');
          }
        }
      }
    };
    document.body.addEventListener('keyup', _textareaEventKC);
    document.body.addEventListener('click', _textareaEventKC);

    // EVENT "keypress"
    document.body.addEventListener ('keypress', (e) => {
        const el = e.target;

        // EVENT "keypress" on comments textarea
        if (el.matches (_getEventSelector('textarea'))) {
          // If enter on selected users search item, select it
          if (e.which === 13 && el.classList.contains('autocomplete')) { 
            e.stopImmediatePropagation();
            e.preventDefault();

            el.closest(`[class*="-body"]`)
              .querySelector('.result .list-group-item.selected').click();
          }
        }
    });
  });

<?php echo $Plugin->getFooter ()?>
