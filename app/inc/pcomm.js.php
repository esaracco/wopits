<?php
/**
  Javascript plugin - Notes comments

  Scope: Note
  Element: .pcomm
  Description: Manage notes comments
*/

  require_once (__DIR__.'/../prepend.php');

  $Plugin = new Wopits\jQueryPlugin ('pcomm');
  echo $Plugin->getHeader ();

?>

  let $_popup,
      _textarea;

  /////////////////////////// PRIVATE METHODS ///////////////////////////

  // METHOD _getEventSelector ()
  const _getEventSelector = (s)=>
  {
    return H.haveMouse() ? `.pcomm-popover ${s}` : `#postitCommentsPopup ${s}`;
  };

  /////////////////////////// PUBLIC METHODS ////////////////////////////

  Plugin.prototype =
  {
    // METHOD init ()
    init ()
    {
      const settings = this.settings;

      $(`<i data-action="comments" class="fas fa-comments" ${(settings.readonly && !settings.count)?`style="display:none"`:""}></i><span ${settings.count?"":`style="display:none"`} class="wpt-badge">${settings.count}</span></div>`).appendTo (this.element);

      this.settings._cache = settings.count ? [] : null;

      return this;
    },

    // METHOD refresh ()
    refresh (d)
    {
      const $pc = this.element,
            badge = $pc[0].querySelector (".wpt-badge"),
            count = d.comments.length;

      this.settings._cache = d.comments;

      if (this.settings.readonly)
      {
        if (count)
          $pc.find(`[data-action="comments"]`).show ();
        else
          $pc.find(`[data-action="comments"]`).hide ();
      }

      badge.innerText = count;
      badge.style.display = count ? "inline-block": "none";

      if ($_popup)
        this.open (true);
    },

    // METHOD getIds ()
    getIds ()
    {
      const ps = this.postit().settings,
            {wallId, cellId} = ps;

      return {wallId, cellId, postitId: ps.id}
    },

    // METHOD injectUserRef ()
    injectUserRef (s)
    {
      const val = _textarea.value,
            start = _textarea.selectionStart;
      let prev = val.substring(0, start).replace (/@([^\s]+)?$/, "@"+s),
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
            el.style.height = (el.offsetHeight - (elB - wH)) +"px";
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

          users.forEach ((item, i) => html += `<li class="${!i?"selected":""} list-group-item">${item.fullname}<div class="item-infos"><span>${item.username}</span></div></li></li>`);
          
          if (html)
          {
            const rc = pc.querySelector (".result-container");

            rc.style.width = _textarea.getBoundingClientRect().width+"px";

            rc.classList.add ("shadow");
            pc.querySelector (".search").classList.add ("shadow");
            _textarea.classList.add ("autocomplete");

            setTimeout (__resize, 50);
          }
          else
          {
            if (!args.str)
              H.displayMsg ({
                type: "warning",
                msg: `<?=_("This wall has not been shared with any user yet.")?>`
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

      if (!!args.full)
      {
        args["field"] = true;
        args["users"] = true;
      }

      pc.querySelector(".result-container").style.display = "none";
      pc.querySelector(".result").innerHTML = "";

      if (!!args.field)
        _textarea.value = "";

      _textarea.classList.remove ("autocomplete");

      pc.querySelectorAll(".shadow").forEach (el=>
        el.classList.remove("shadow"));
    },

    // METHOD postit ()
    postit ()
    {
      return this.settings.postitPlugin;
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
          postitTitle: this.postit().getTitle ()
        },
        // success cb
        () => H.waitForDOMUpdate (
                ()=>window.dispatchEvent (new Event("resize"))));
    },

    // METHOD close ()
    close ()
    {
      if ($_popup)
        $("#popup-layer").click ();
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
            $body.css ("height", (h-53)+"px");
          }
        };

      plugin.postit().setCurrent ();

      (d||[]).forEach (c=>
      {
        content += `<div class="msg-item" data-id="${c.id}" data-userid="${c.ownerid}"><div class="msg-header"><div class="msg-date"><i class="far fa-clock"></i> ${H.getUserDate(c.creationdate, null, "Y-MM-DD H:mm")}</div><div class="msg-username"><i class="far fa-user"></i> ${c.ownername||`<s><?=_("deleted")?></s>`}</div>${c.ownerid==userId?`<button type="button" class="close" data-toggle="tooltip" title="<?=_("Delete my comment")?>"><span>&times;</span></button>`:""}</div><div class="msg-body">${c.content.replace(/\n/g, "<br>")}</div></div>`;
      });

      //FIXME
      content = content.replace (/(@[^\s\?\.:!,;"<@]+)/g,
                  `<span class="msg-userref">$1</span>`);

      if (refresh)
      {
        const $el = $_popup.find (".content");

        $el.find("[data-toggle='tooltip']").tooltip ("dispose");
        $el.html (content);

        // Resize only if popover
        if ($_popup[0].classList.contains ("popover"))
          H.waitForDOMUpdate (__resize);

        H.enableTooltips ($el);
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
            }
          });
        // Device with mouse: open a POPOVER
        else
          H.openConfirmPopover ({
            type: "custom",
            placement:"left",
            html_header: plugin.settings.readonly ? "" : `<div class="search mb-1"><textarea class="form-control" autofocus maxlength="<?=Wopits\DbCache::getFieldLength('postits_comments', 'content')?>"></textarea><span class="btn btn-sm btn-secondary btn-circle btn-clear"><i class="fa fa-broom"></i></span><div class="result-container"><ul class="result autocomplete list-group"></ul></div></div><div class="tip"><i class="far fa-lightbulb"></i> <?=_("Use @ to refer to another user.")?></div><button type="button" class="btn btn-primary btn-xs"><?=_("Send")?></button>`,
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
              $_popup = $p;
              _textarea = $p[0].querySelector ("textarea");

              __resize ($p);

              H.setAutofocus ($p);
            }
        });
      }
    }
  };

  /////////////////////////// AT LOAD INIT //////////////////////////////

  if (!H.isLoginPage ())
    $(function()
      {
        setTimeout (()=>{

        // EVENT click on postit comments button.
        $(document).on("click", ".pcomm", function ()
          {
            $(this).pcomm ("open");
          }); 
 
        // EVENT hidden on popup (only for devices without mouse)
        if (!H.haveMouse ())
          $(document).on("hidden.bs.modal", "#postitCommentsPopup",
            function (e)
            {
              _textarea.value = "";

              S.getCurrent("pcomm").pcomm ("reset");
              S.getCurrent("postit").postit ("unsetCurrent");

              $_popup = undefined;
            });

        // EVENT click on clear input button.
        $(document).on("click", _getEventSelector(".btn-clear"),
          function (e)
          {
            _textarea.value = "";
            S.getCurrent("pcomm").pcomm ("reset");
            _textarea.focus ();
          });

        // EVENT keyup on textarea.
        $(document).on("keyup keydown", _getEventSelector(" textarea"),
          function (e)
          {
            const list = this.closest(`[class*="-body"]`)
                           .querySelectorAll ("li");

             if (!list.length)
               return;

            const k = e.which,
                  pcomm = S.getCurrent("pcomm").pcomm ("getClass");

            // If ESC, close the users search
            if (k == 27)
            {
              e.stopImmediatePropagation ();
              pcomm.reset ();
            }
            // Arrow up or arrow down.
            else if (k == 38 || k == 40)
            {
              if (list.length == 1)
                pcomm.reset ();
              else
              {
                e.stopImmediatePropagation ();
                e.preventDefault ();

                if (e.type == "keyup")
                {
                  // INTERNAL FUNCTION __select ()
                  const __select = (i, type)=>
                    {
                      // Arrow up.
                      if (i && type == "up")
                      {
                        const el = list[i-1];

                        list[i].classList.remove ("selected");
                        el.classList.add ("selected");
                        el.scrollIntoView (false);
                      }
                      // Arrow down.
                      else if (i < list.length - 1 && type == "down")
                      {
                        const el = list[i+1];

                        list[i].classList.remove ("selected");
                        el.classList.add ("selected");
                        el.scrollIntoView (false);
                      }
                    };

                  for (let i = 0, iLen = list.length; i < iLen; i++)
                    if (list[i].classList.contains ("selected"))
                    {
                      if (i == 0 && k == 38 ||
                          i == iLen - 1 && k == 40)
                      {
                        pcomm.reset ();
                        return;
                      }
                      else
                      {
                        __select (i, k == 38 ? "up": "down");
                        return;
                      }
                    }
                }
              }
            }
          });

         // EVENT click on submit button
         $(document).on("click", _getEventSelector(".btn-primary"),
          function (e)
          {
            const content = H.noHTML (_textarea.value);

            if (!content) return;

            S.getCurrent("pcomm").pcomm ("add", content);
            _textarea.value = "";
            _textarea.focus ();
         });

        // EVENTS keyup/click on textarea
        $(document).on("keyup click", _getEventSelector("textarea"),
          function (e)
          {
            const k = e.which,
                  prev = this.value.substring (0, this.selectionStart),
                  // Keys to ignore
                  ignore = (e.ctrlKey || k == 27 || k == 16 || k == 225);
            let m;

            // Submit if needed
            if (k == 13)
            {
              if (e.ctrlKey)
                this.closest(`[class*="-body"]`)
                  .querySelector(".btn-primary").click ();
              else
                return e.preventDefault ();
            }

            // Nothing if we must ignore the key
            if (!ignore)
            {
              // Display users search list if needed
              if (m = prev.match (/(^|\s)@([^\s]+)?$/))
                S.getCurrent("pcomm").pcomm ("search", {str: m[2]||""});
              if (this.classList.contains ("autocomplete"))
                S.getCurrent("pcomm").pcomm ("reset");
            }
          });

        // EVENT keypress on textarea
        $(document).on("keypress", _getEventSelector("textarea"),
          function (e)
          {
            // If enter on selected users search item, select it
            if (e.which == 13 && this.classList.contains ("autocomplete"))
            { 
              e.stopImmediatePropagation ();
              e.preventDefault ();

              this.closest(`[class*="-body"]`)
                .querySelector(".result .list-group-item.selected").click ();
            }
          });

        // EVENT click on users list
        $(document).on("click", _getEventSelector(".result .list-group-item"),
          function (e)
          {
            const pcomm = S.getCurrent("pcomm").pcomm("getClass");

            // Selected the current item in users search list and close the
            // list
            pcomm.injectUserRef (this.querySelector("span").innerText);
            pcomm.reset ();
          });

        // EVENT click on comment delete button
        $(document).on("click", _getEventSelector(".msg-item .close"),
          function (e)
          {
            const data = this.closest(".content").dataset,
                  item = this.closest (".msg-item");

            e.preventDefault ();
            e.stopImmediatePropagation ();

            _textarea.focus ();

            H.request_ws (
              "DELETE",
              `wall/${data.wallid}/cell/${data.cellid}/postit/`+
                `${data.postitid}/comment/${item.dataset.id}`,
              null,
              // success cb
              ()=> H.waitForDOMUpdate (
                     ()=>window.dispatchEvent (new Event("resize"))));
          });

        }, 0);

      });

<?php echo $Plugin->getFooter ()?>
