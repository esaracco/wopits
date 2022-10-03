<?php
/**
  Javascript plugin - Users search

  Scope: Wall & note
  Elements: #usearchPopup, #pworkPopup
  Description: Manage users search for sharing wall (swall) and
               postit workers (pwork) plugins
*/

  require_once (__DIR__.'/../prepend.php');

  $Plugin = new Wopits\jQueryPlugin ('usearch');
  echo $Plugin->getHeader ();

?>

  let _noResultStr,
      _oldIds,
      _readonly = false;

  /////////////////////////// PUBLIC METHODS ////////////////////////////

  Plugin.prototype =
  {
    // METHOD init ()
    init (args)
    {
      const plugin = this,
            $ac = plugin.element,
            ac = $ac[0],
            id = ac.id,
            search = ac.querySelector (".search");

      $(search).append (`<div class="input-group"><span class="input-group-text"><i class="fas fa-search fa-xs fa-fw"></i></span><input type="text" class="form-control" value="" placeholder="<?=_("username")?>" autocorrect="off" autocapitalize="off"><button class="btn clear-input" type="button"><i class="fa fa-times"></i></button></div><ul class="result autocomplete list-group"><button type="button" class="close closemenu">×</button><div class="content"></div></ul>`);

      // EVENT "hidden.bs.modal"
      ac.addEventListener ("hidden.bs.modal", (e)=>
        {
          if (args.cb_close)
            args.cb_close ();

          e.target.querySelector(".list-group.attr").innerHTML = "";
          _oldIds = undefined;
        });
   
      // EVENT "mouseover"
      document.body.addEventListener ("mouseover", (e)=>
        {
          const el = e.target;

          // EVENT "mouseover" on users search result list item
          if (el.matches (`#${id} .result .list-group-item *,`+
                          `#${id} .result .list-group-item`))
          {
            const li = el.tagName=="LI"?el:el.closest("li");

            e.stopImmediatePropagation ();

            li.closest(".modal-body .search").querySelector("li.selected")
              .classList.remove ("selected");
            li.classList.add ("selected");
          }
        });

      // EVENT "click"
      document.body.addEventListener ("click", (e)=>
        {
          const el = e.target;

          // EVENT "click" on users search list item
          if (el.matches (`#${id} .list-group-item *,`+
                          `#${id} .list-group-item`))
          {
            const li = el.tagName=="LI"?el:el.closest("li");

            e.stopImmediatePropagation ();

            if (_readonly)
              H.openUserview ({
                about: li.dataset.about,
                picture: li.dataset.picture,
                title: li.dataset.title
              });
            else
            {
              const isDed = (ac.dataset.grouptype == <?=WPT_GTYPES_DED?>),
                    actionAdd = (li.dataset.action == "add"),
                    args = Object.assign (
                      {
                        groupType: ac.dataset.grouptype,
                        groupId: ac.dataset.groupid,
                        userId: li.dataset.id
                      }, plugin.getIds());
  
              search.querySelector("input").focus ();
  
              if (plugin.isWorkers ())
              {
                if (actionAdd)
                  plugin.addUser (args);
                else
                  plugin.removeUser (args);
              }
              else
              {
                if (actionAdd)
                  plugin.addUser (args);
                else if (isDed && ac.dataset.noattr)
                  plugin.removeUser (args);
                else
                {
                  H.openConfirmPopover ({
                    item: $(li.querySelector("span")),
                    title: `<i class="fas fa-minus-circle fa-fw"></i> <?=_("Remove")?>`,
                    content: isDed ? `<?=_("This user will lose their access to the wall.<br>Remove anyway?")?>` : `<?=_("This user will lose their access for all walls shared with this group.<br>Remove anyway?")?>`,
                    cb_ok: () =>
                    {
                      plugin.removeUser (args);
                      search.querySelector("input").focus ();
                    }
                  });
                }
              }
            }
          }
        });

      // EVENT "click" on textarea clear button
      search.querySelector(".clear-input").addEventListener ("click", (e)=>
        {
          const input = search.querySelector ("input");

          input.value = "";
          plugin.reset ();
          
          input.focus ();
        });

      // EVENT "keyup" on input
      search.querySelector("input").addEventListener ("keyup", (e)=>
        {
          const val = e.target.value.trim (),
                k = e.which;

          if (!val || (!plugin.isWorkers () && val.length < 3))
            return plugin.reset ();

          // Do not search if arrow keys
          if (k < 37 || k > 40)
            plugin.search ({
              str: val,
              groupType: ac.dataset.grouptype
            });
        });

      // EVENT "keypress" on input
      search.querySelector("input").addEventListener ("keypress", (e)=>
        {
          const el = e.target;

          // If enter on selected users search item, select it
          if (e.which == 13 && el.classList.contains ("autocomplete"))
          {
            e.stopImmediatePropagation ();
            e.preventDefault ();

            el.closest(".modal-body")
              .querySelector(".search .list-group-item.selected").click ();
          }
        });

      // EVENTS "keyup" & "keydown" on input
      const _eventKK = (e)=>
        {
          const el = e.target,
                k = e.which,
                list = el.closest(".modal-body .search")
                         .querySelectorAll ("li");

           if (!list.length)
             return;

          // If ESC, close the users search
          if (k == 27)
          {
            e.stopImmediatePropagation ();
            plugin.reset ();
          }
          // Arrow up or arrow down.
          else if (k == 38 || k == 40)
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
                    __select (i, k == 38 ? "up": "down");
                    return;
                  }
              }
          }
        };
      search.querySelector("input").addEventListener ("keyup", _eventKK);
      search.querySelector("input").addEventListener ("keydown", _eventKK);

      // EVENT "click" on users search list close button
      search.querySelector("button.closemenu").addEventListener ("click",
        (e)=> plugin.reset ({field: true}));
    },

    // METHOD getNewUsers ()
    getNewUsers ()
    {
      const userId = wpt_userData.id;
      let ids = [];

      this.element[0].querySelectorAll(".list-group.attr .list-group-item")
        .forEach (item =>
          {
            const id = parseInt (item.getAttribute ("data-id"));

            if (id != userId && !_oldIds.includes (id))
              ids.push (id);
          });

      return ids;
    },

    // METHOD setSettings ()
    setSettings (args)
    {
      this.settings = Object.assign (this.settings, args);
    },

    // METHOD getIds ()
    getIds ()
    {
      const $postit = S.getCurrent ("postit"),
            postitSettings = $postit.length?$postit.postit("getSettings"):{};

      return {
        wallId: S.getCurrent("wall").wall ("getId"),
        cellId: postitSettings.cellId,
        postitId: postitSettings.id,
      };
    },

    // METHOD reset ()
    reset (args)
    {
      const ac = this.element[0],
            input = ac.querySelector ("input");

      if (!args)
        args = {};

      if (Boolean(args.full))
      {
        ac.removeAttribute ("data-delegateadminid");

        args["field"] = true;
        args["users"] = true;
      }

      if (Boolean(args.field))
        input.value = "";

      input.classList.remove ("autocomplete");
      ac.querySelector(".result .content").innerHTML = "";
      ac.querySelector(".result button.closemenu").style.display = "none";
      ac.querySelector(".search").classList.remove ("shadow");

      if (args.readonly !== undefined && args.readonly)
      {
        _readonly = true;

        ac.querySelector(".search").style.display = "none";
        ac.querySelector(".desc").style.display = "none";
      }
      else
      {
        _readonly = false;

        ac.querySelector(".search").style.display = "block";
        ac.querySelector(".desc").style.display = "block";
      }
    },

    // METHOD removeUser ()
    removeUser (args)
    {
      const isWorkers = this.isWorkers ();
      let service = isWorkers ?
                      `cell/${args.cellId}/postit/${args.postitId}/`+
                         `worker/${args.userId}` :
                      `group/${args.groupId}/removeUser/${args.userId}`;

      if (isWorkers || args.groupType == <?=WPT_GTYPES_DED?>)
        service = `wall/${args.wallId}/${service}`;

      H.request_ws (
        "DELETE",
        service,
        null,
        // success cb
        (d) =>
        {
          this.displayUsers (args);

          args["str"] = this.element.find("input").val ();

          this.search (args, true);

          if (this.settings.cb_remove)
            this.settings.cb_remove ();
        });
    },

    // METHOD isWorkers ()
    isWorkers ()
    {
      return (this.settings.caller == "pwork");
    },

    // METHOD addUser ()
    addUser (args)
    {
      const isWorkers = this.isWorkers ();
      let service = isWorkers ?
                      `cell/${args.cellId}/postit/${args.postitId}/`+
                         `worker/${args.userId}` :
                      `group/${args.groupId}/addUser/${args.userId}`;

      if (isWorkers || args.groupType == <?=WPT_GTYPES_DED?>)
        service = `wall/${args.wallId}/${service}`;

      H.request_ws (
        "PUT",
        service,
        null,
        // success cb
        (d) =>
        {
          if (d.error_msg)
            return H.raiseError (null, d.error_msg);
          else if (d.notfound)
            H.displayMsg ({
              title: `<?=_("Users involved")?>`,
              type: "warning",
              msg: `<?=_("The user is no longer available")?>`
            });

          this.displayUsers (args);

          args["str"] = this.element.find("input").val ();

          this.search (args, true);

          if (this.settings.cb_add)
            this.settings.cb_add ();
        });
    },

    // METHOD displayUsers ()
    displayUsers (args)
    {
      const $ac = this.element,
            delegateAdminId = $ac[0].dataset.delegateadminid||0,
            isWorkers = this.isWorkers ();
      let service = isWorkers ?
                      `cell/${args.cellId}/postit/${args.postitId}/worker` :
                      `group/${args.groupId}/getUsers`;

      if (isWorkers || args.groupType == <?=WPT_GTYPES_DED?>)
        service = `wall/${args.wallId}/${service}`;

      H.fetch (
        "GET",
        service,
        null,
        // success cb
        (d) =>
        {
          const $div = $ac.find (".modal-body .list-group.attr");
          let html = "";

          d = d.users;

          // Keep users state before action
          if (_oldIds === undefined)
          {
            _oldIds = [];
            d.forEach (u => _oldIds.push (u.id));
            _oldIds.sort ();
          }

          if (d.length)
          {
            $ac.find(".users-title").show ();
            $ac.find(".nousers-title").hide ();

            d.forEach (u => _readonly ?
              html += `<li class="list-group-item" data-id="${u.id}" data-title="${H.htmlEscape(u.fullname)}" data-picture="${u.picture||""}" data-about="${H.htmlEscape(u.about||"")}"><div class="label">${u.fullname}</div><div class="item-infos"><span>${u.username}</span></div></li>` :
              html += `<li class="list-group-item${(delegateAdminId == u.id)?" readonly":""}" data-action="remove" data-id="${u.id}"><div class="label">${u.fullname}</div><button type="button" class="close"><i class="fas fa-minus-circle fa-fw fa-xs"></i></button><div class="item-infos"><span>${u.username}</span></div></li>`);

            if (d.length > 1)
              $div.parent().addClass ("scroll");
            else
              $div.parent().removeClass ("scroll");

            $div.html (html);
          }
          else
          {
            $ac.find(".users-title").hide ();
            $ac.find(".nousers-title").show ();
            $div.parent().removeClass ("scroll");
            $div.empty ();
          }

          args.cb_after && args.cb_after (d.length);
        }
      );
    },

    // METHOD search ()
    search (args, force)
    {
      const ac = this.element[0],
            wallId = S.getCurrent("wall").wall ("getId");
      let service;

      if (this.isWorkers ())
      {
        const ids = this.getIds ();

        service = `wall/${ids.wallId}/cell/${ids.cellId}/`+
                     `postit/${ids.postitId}/searchWorkers/${args.str}`;
      }
      else
        service = (args.groupType == <?=WPT_GTYPES_DED?>) ?
              `group/${ac.dataset.groupid}/wall/${wallId}`+
                `/searchUsers/${args.str}` :
              `group/${ac.dataset.groupid}/searchUsers/${args.str}`;

      args.str = args.str.replace (/&/, "");

      if (!force &&
          (
            !args.str ||
            (
              _noResultStr &&
               args.str != _noResultStr &&
               args.str.indexOf (_noResultStr) != -1
            )
          )
        )
        return;

      H.fetch (
        "GET",
        service,
        null,
        // success cb
        (d) =>
        {
          const users = d.users||[];
          let html = "";

          if (!users.length)
            _noResultStr = args.str;

          users.forEach ((item,i) => html += `<li class="${!i?"selected":""} list-group-item" data-action="add" data-id="${item.id}"><div class="label">${item.fullname}</div><button type="button" class="close"><i class="fas fa-plus-circle fa-fw fa-xs"></i></button><div class="item-infos"><span>${item.username}</span></div></li></li>`);

          if (html)
          {
            ac.querySelector(".result button.closemenu").style.display= "block";
            ac.querySelector(".search").classList.add ("shadow");
            ac.querySelector("input").classList.add ("autocomplete");
          }
          else
            this.reset ();
          
          ac.querySelector(".result .content").innerHTML = html;
        }
      );
    },

  };

<?php echo $Plugin->getFooter ()?>
