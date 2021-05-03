<?php
/**
  Javascript plugin - Users search

  Scope: Wall
  Elements: #usearchPopup
  Description: Manage users search for sharing wall (swall) plugin
*/

  require_once (__DIR__.'/../prepend.php');

  $Plugin = new Wopits\jQueryPlugin ('usearch');
  echo $Plugin->getHeader ();

?>

  let _lastStr = "",
      _noResultStr;

  /////////////////////////// PUBLIC METHODS ////////////////////////////

  Plugin.prototype =
  {
    // METHOD init ()
    init (args)
    {
      const plugin = this,
            $ac = plugin.element,
            $search = $ac.find (".search");

      $search.append (`<div class="input-group"><div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-search fa-xs fa-fw"></i></span></div><input type="input" class="form-control" value="" placeholder="<?=_("username")?>" autocorrect="off" autocapitalize="off" autofocus></div><ul class="result autocomplete list-group"><button type="button" class="close closemenu"><span>&times;</span></button><div class="content"></div></ul>`);

      // EVENT hidden.bs.modal
      $ac.on("hidden.bs.modal", function (e)
        {
          $(this).find(".list-group.attr").empty ();
        });
   
      // EVENT click on users search list item
      $(document).on("click", "#usearchPopup .list-group-item", function (e)
        {
          const $el = $(this),
                isDed = ($ac[0].dataset.grouptype == <?=WPT_GTYPES_DED?>),
                args = {
                  wallId: S.getCurrent("wall").wall ("getId"),
                  groupType: $ac[0].dataset.grouptype,
                  groupId: $ac[0].dataset.groupid,
                  userId: $el[0].dataset.id
                };

          e.stopImmediatePropagation ();

          $search.find("input").focus ();

          if ($el[0].dataset.action == "add")
            plugin.addGroupUser (args);
          else if (isDed && $ac[0].dataset.noattr)
            plugin.removeGroupUser (args);
          else
          {
            H.openConfirmPopover ({
              item: $el.find("span:eq(0)"),
              title: `<i class="fas fa-minus-circle fa-fw"></i> <?=_("Remove")?>`,
              content: isDed ? `<?=_("This user will lose their access to the wall.<br>Remove anyway?")?>` : `<?=_("This user will lose their access for all walls shared with this group.<br>Remove anyway?")?>`,
              cb_ok: () =>
              {
                plugin.removeGroupUser (args);
                $search.find("input").focus ();
              }
            }); 
          }
        });

      // EVENT keyup on input
      $search.find("input").on("keyup", function (e)
        {
          const val = $(this).val().trim ();

          if (val.length < 3)
            return plugin.reset ();

          plugin.search ({
            str: val,
            groupType: $ac[0].dataset.grouptype
          })
        });

      // EVENT keypress on input
      $search.find("input").on("keypress", function (e)
        {
          // If enter on selected users search item, select it
          if (e.which == 13 && this.classList.contains ("autocomplete"))
          {
            e.stopImmediatePropagation ();
            e.preventDefault ();

            this.closest(".modal-body")
              .querySelector(".search .list-group-item.selected").click ();
          }
        });

      // EVENT keyup on input
      $search.find("input").on("keyup keydown", function (e)
        {
          const k = e.which,
                list = this.closest(".modal-body .search")
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
        });

      // EVENT click on users search list close button
      $search.find("button.closemenu").on("click", function (e)
        {
          plugin.reset ({field: true});
        });
    },

    // METHOD reset ()
    reset (args)
    {
      const ac = this.element[0],
            input = ac.querySelector ("input");

      if (!args)
        args = {};

      if (!!args.full)
      {
        ac.removeAttribute ("data-delegateadminid");

        args["field"] = true;
        args["users"] = true;

        _lastStr = "";
      }

      if (!!args.field)
        input.value = "";

      if (!input.value)
        _lastStr = "";

      input.classList.remove ("autocomplete");
      ac.querySelector(".result .content").innerHTML = "";
      ac.querySelector(".result button.closemenu").style.display = "none";
      ac.querySelector(".search").classList.remove ("shadow");
    },

    // METHOD removeGroupUser ()
    removeGroupUser (args)
    {
      let service = "group/"+args.groupId+"/removeUser/"+args.userId;

      if (args.groupType == <?=WPT_GTYPES_DED?>)
        service = "wall/"+args.wallId+"/"+service;

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

          $("#swallPopup").swall ("displayGroups");
        });
    },

    // METHOD addGroupUser ()
    addGroupUser (args)
    {
      let service = `group/${args.groupId}/addUser/${args.userId}`;

      if (args.groupType == <?=WPT_GTYPES_DED?>)
        service = "wall/"+args.wallId+"/"+service;

      H.request_ws (
        "PUT",
        service,
        null,
        // success cb
        (d) =>
        {
          if (d.notfound)
            H.displayMsg ({
              type: "warning",
              msg: `<?=_("This user is no longer available.")?>`
            });

          this.displayUsers (args);

          args["str"] = this.element.find("input").val ();

          this.search (args, true);

          $("#swallPopup").swall ("displayGroups");
        });
    },

    // METHOD displayUsers ()
    displayUsers (args)
    {
      const $ac = this.element,
            delegateAdminId = $ac[0].dataset.delegateadminid||0;
      let service = `group/${args.groupId}/getUsers`;

      if (args.groupType == <?=WPT_GTYPES_DED?>)
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

          if (d.length)
          {
            $ac.find(".users-title").show ();
            $ac.find(".nousers-title").hide ();

            d.forEach ((item) => html += `<li class="list-group-item${(delegateAdminId == item.id)?" readonly":""}" data-action="remove" data-id="${item.id}"><span>${item.fullname}</span><button type="button" class="close"><i class="fas fa-minus-circle fa-fw fa-xs"></i></button><div class="item-infos"><span>${item.username}</span></div></li>`);

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
        }
      );
    },

    // METHOD search ()
    search (args, force)
    {
      const ac = this.element[0],
            wallId = S.getCurrent("wall").wall ("getId"),
            service = (args.groupType == <?=WPT_GTYPES_DED?>) ?
              `group/${ac.dataset.groupid}/wall/${wallId}`+
                `/searchUsers/${args.str}` :
              `group/${ac.dataset.groupid}/searchUsers/${args.str}`;

      args.str = args.str.replace (/&/, "");

      if (!force &&
          (
            !args.str ||
            args.str == _lastStr ||
            (
              args.str.length > _lastStr.length &&
              _noResultStr &&
               args.str != _noResultStr &&
               args.str.indexOf (_noResultStr) != -1
            )
          )
        )
        return;

      _lastStr = args.str;

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

          users.forEach ((item,i) => html += `<li class="${!i?"selected":""} list-group-item" data-action="add" data-id="${item.id}">${item.fullname}<button type="button" class="close"><i class="fas fa-plus-circle fa-fw fa-xs"></i></button><div class="item-infos"><span>${item.username}</span></div></li></li>`);

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
