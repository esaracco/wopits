<?php
  require_once (__DIR__.'/../class/Wpt_jQueryPlugins.php');
  $Plugin = new Wpt_jQueryPlugins ('usersSearch');
  echo $Plugin->getHeader ();
?>

/////////////////////////// PUBLIC METHODS ////////////////////////////

  Plugin.prototype =
  {
    // METHOD init ()
    init: function (args)
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
        .on("click", "#usersSearchPopup .list-group-item",function (e)
        {
          const $el = $(this),
                args = {
                  wallId: S.getCurrent("wall").wall ("getId"),
                  groupType: $ac[0].dataset.grouptype,
                  groupId: $ac[0].dataset.groupid,
                  userId: $el[0].dataset.id
                };

          e.stopImmediatePropagation ();

          if ($el[0].dataset.action == "add")
            plugin.addGroupUser (args);
          else
            plugin.removeGroupUser (args);
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
    reset: function (args)
    {
      const $ac = this.element;

      if (!args)
        args = {};

      if (!!args.full)
      {
        $ac[0].removeAttribute ("data-delegateadminid");

        args["field"] = true;
        args["users"] = true;
      }

      if (!!args.field)
        $ac.find("input").val ("");

      $ac.find(".result .content").empty ();
      $ac.find(".result button.closemenu").hide ();
      $ac.find(".search").removeClass ("shadow");
      $ac.find("input").removeClass ("autocomplete");
    },

    // METHOD removeGroupUser ()
    removeGroupUser: function (args)
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

          this.search (args);

          $("#shareWallPopup").shareWall ("displayGroups");
        });
    },

    // METHOD addGroupUser ()
    addGroupUser: function (args)
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
          this.displayUsers (args);

          args["str"] = this.element.find("input").val ();

          this.search (args);

          $("#shareWallPopup").shareWall ("displayGroups");
        });
    },

    // METHOD displayUsers ()
    displayUsers: function (args)
    {
      const $ac = this.element,
            delegateAdminId = $ac[0].dataset.delegateadminid||0;
      let service = "group/"+args.groupId+"/getUsers";

      if (args.groupType == <?=WPT_GTYPES_DED?>)
        service = "wall/"+args.wallId+"/"+service;

      H.request_ajax (
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
    search: function (args)
    {
      const $ac = this.element,
            wallId = S.getCurrent("wall").wall ("getId"),
            groupId = $ac[0].dataset.groupid;
      let service = "group/"+groupId+"/searchUsers/"+args.str,
          data = null;

      if (!args.str)
        return;

      if (args.groupType == <?=WPT_GTYPES_DED?>)
        data = {wallId: wallId};

      H.request_ajax (
        "GET",
        service,
        data,
        // success cb
        (d) =>
        {
          let html = "";

          d.users.forEach ((item) => html += `<li class="list-group-item list-group-item-action" data-action="add" data-id="${item.id}">${item.fullname}<button type="button" class="close"><i class="fas fa-plus-circle fa-fw fa-xs"></i></button></li>`);

          if (html)
          {
            $ac.find(".result button.closemenu").show ();
            $ac.find(".search").addClass ("shadow");
            $ac.find("input").addClass ("autocomplete");
          }
          else
            this.reset ();
          
          $ac.find(".result .content").html (html);
        }
      );
    },

  };

  /////////////////////////// AT LOAD INIT //////////////////////////////

  $(function ()
    {
      const $plugin = $("#usersSearchPopup");

      if ($plugin.length)
        $plugin.usersSearch ();
    });

<?php echo $Plugin->getFooter ()?>
