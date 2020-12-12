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

      $search.append (`<div class="input-group"><div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-search fa-xs fa-fw"></i></span></div><input type="input" class="form-control" value="" placeholder="<?=_("username")?>" autocorrect="off" autocapitalize="none" autofocus></div><ul class="result autocomplete list-group"><button type="button" class="close closemenu"><span>&times;</span></button><div class="content"></div></ul>`);

      $ac
        .on("hidden.bs.modal", function (e)
        {
          $(this).find(".list-group.attr").empty ();
        })
        .on("keypress", function (e)
        {
          if (e.which == 13 && e.target.tagName == "INPUT")
          {
            const $el = $ac.find (".search .list-group-item-action");

            if ($el.length == 1)
            {
              e.stopImmediatePropagation ();
              e.preventDefault ();

              $el.click ();
            }
          }
        });
   
      $(document)
        .on("click", "#usearchPopup .list-group-item", function (e)
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

          if ($el[0].dataset.action == "add")
            plugin.addGroupUser (args);
          else if (isDed && $ac[0].dataset.noattr)
            plugin.removeGroupUser (args);
          else
          {
            H.openConfirmPopover ({
              item: $el.find("span"),
              title: `<i class="fas fa-minus-circle fa-fw"></i> <?=_("Remove")?>`,
              content: isDed ? "<?=_("This user will lose their access to the wall.<br>Remove anyway?")?>" : "<?=_("This user will lose their access for all walls shared with this generic group.<br>Remove anyway?")?>",
              cb_ok: () => plugin.removeGroupUser (args)
            }); 
          }
        });

      $search.find('input')
        .on("keyup", function (e)
        {
          const val = $(this).val().trim ();

          if (val.length < 3)
            return plugin.reset ();

          plugin.search ({
            str: val,
            groupType: $ac[0].dataset.grouptype
          })
        });

      $search.find("button.closemenu")
        .on("click", function (e)
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
      let service = "group/"+args.groupId+"/addUser/"+args.userId;

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
              msg: "<?=_("This user is no longer available!")?>"
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
      let service = "group/"+args.groupId+"/getUsers";

      if (args.groupType == <?=WPT_GTYPES_DED?>)
        service = "wall/"+args.wallId+"/"+service;

      H.fetch (
        "GET",
        service,
        null,
        // success cb
        (d) =>
        {
          const $div = $ac.find (".modal-body .list-group.attr");
          let html = '';

          d = d.users;

          if (d.length)
          {
            $ac.find(".users-title").show ();
            $ac.find(".nousers-title").hide ();

            d.forEach ((item) => html += `<li class="list-group-item list-group-item-action${(delegateAdminId == item.id)?' readonly':''}" data-action="remove" data-id="${item.id}"><span>${item.fullname}</span><button type="button" class="close"><i class="fas fa-minus-circle fa-fw fa-xs"></i></button></li>`);

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
              "group/"+ac.dataset.groupid+"/wall/"+wallId+
                "/searchUsers/"+args.str :
              "group/"+ac.dataset.groupid+"/searchUsers/"+args.str;

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

          users.forEach ((item) => html += `<li class="list-group-item list-group-item-action" data-action="add" data-id="${item.id}">${item.fullname}<button type="button" class="close"><i class="fas fa-plus-circle fa-fw fa-xs"></i></button></li>`);

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
