<?php
/**
  Javascript plugin - Sharing walls

  Scope: Wall
  Elements: #swallPopup
  Description: Manage wall sharing
*/

  require_once (__DIR__.'/../prepend.php');

  $Plugin = new Wopits\jQueryPlugin ('swall');
  echo $Plugin->getHeader ();

?>

  let $_groupPopup,
      $_groupAccessPopup;

  /////////////////////////// PRIVATE METHODS ///////////////////////////

  // METHOD _displaySection ()
  function _displaySection ($div, type, items)
  {
    const pClass = $div[0].parentNode.classList,
          active = document.querySelector (".modal li.list-group-item.active");

    let html = '';

    pClass.remove ("scroll");

    items.forEach ((item) =>
      {
        if (item.item_type == type)
          html += `<li data-id="${item.id}" data-type="${item.item_type}" data-name="${H.htmlEscape(item.name)}" class="list-group-item list-group-item-action is-wall-creator${active&&active.dataset.id==item.id?" active todelete":""}"><div class="userscount" data-action="users-search" data-toggle="tooltip" title="${item.userscount} <?=_("users in this group")?>"><i class="fas fa-layer-group fa-fw"></i> <span class="wpt-badge">${item.userscount}</span></div> <span class="name">${item.name}</span> <span class="desc">${item.description||''}</span><button data-action="delete-group" type="button" class="close" data-toggle="tooltip" title="<?=_("Delete this group")?>"><i class="fas fa-trash fa-fw fa-xs"></i></button><button data-action="users-search" type="button" class="close" data-toggle="tooltip" title="<?=_("Manage users")?>"><i class="fas fa-user-friends fa-fw fa-xs"></i></button><button data-action="link-group" type="button" class="btn btn-secondary btn-xs btn-share" data-toggle="tooltip" title="<?=_("Share with this group")?>"><i class="fas fa-plus-circle"></i><?=_("Share")?></button></li>`;
      });

    $div.html (html);

    if (html)
    {
      pClass.add ("scroll");

      if ($div.find("li").length == 1)
        pClass.add ("one");
      else
        pClass.remove ("one");
    }
  }

  /////////////////////////// PUBLIC METHODS ////////////////////////////

  // Inherit from Wpt_forms
  Plugin.prototype = Object.create(Wpt_forms.prototype);
  Object.assign (Plugin.prototype,
  {
    // METHOD init ()
    init (args)
    {
      const plugin = this,
            $share = plugin.element;

      H.loadPopup ("account", {open: false});

      $_groupPopup = $("#groupPopup");

      $share
        .on("hidden.bs.modal", function (e)
        {
          S.getCurrent("wall").wall ("menu", {from: "wall", type:"have-wall"});
        });

      $(document)
        .on("click", "#swallPopup .list-group-item button,"+
                     "#swallPopup .list-group-item div.userscount",
        function (e)
        {
          const $btn = $(this),
                $row = $btn.parent(),
                groupType = $row[0].dataset.type,
                action = $btn[0].dataset.action,
                id = $row[0].dataset.id;

          e.stopImmediatePropagation ();

          $btn.tooltip ("hide");

          $row.addClass ("active todelete");

          switch (action)
          {
            case "users-search":

              H.loadPopup ("usearch", {
                open: false,
                cb: ($p)=>
                {
                  const groupId = $row[0].dataset.id,
                        delegateAdminId = $row[0].dataset.delegateadminid||0;

                  $p.usearch ("reset", {full: true});

                  if ($row[0].parentNode.classList.contains("noattr"))
                    $p[0].dataset.noattr = 1;
                  $p[0].dataset.delegateadminid = delegateAdminId;
                  $p[0].dataset.groupid = groupId;
                  $p[0].dataset.grouptype = groupType;

                  $p
                    .find(".desc").html ("<?=_("Add or remove users in the group « %s ».")?>".replace("%s", "<b>"+$row[0].dataset.name+"</b>"));

                  $p.usearch (
                    "displayUsers",
                    {
                      wallId: S.getCurrent("wall").wall ("getId"),
                      groupId: groupId,
                      groupType: groupType
                    });

                  H.openModal ($p);
                }
              });
              break;

            case "delete-group":

              var content =
               ($row.parent().hasClass ("gtype-<?=WPT_GTYPES_DED?>"))?
                 `<?=_("Delete this group?")?>`:
                 `<?=_("This group will no longer be available for the current wall or for your other walls.<br>Delete it anyway?")?>`;

              H.openConfirmPopover ({
                 item: $btn.parent().find(".name"),
                 title: `<i class="fas fa-trash fa-fw"></i> <?=_("Delete")?>`,
                 content: content,
                 cb_close: () =>
                   $share.find("li.list-group-item.active")
                     .removeClass ("active todelete"),
                 cb_ok: () => plugin.deleteGroup ()
               });
              break;

            case "unlink-group":

              // Ask confirmation only if they are some users in the group to
              // unlink.
              if ($row.find(".userscount .wpt-badge").text() == 0)
                plugin.unlinkGroup ({id: id}, groupType);
              else
                H.openConfirmPopover ({
                   item: $btn.parent().find(".name"),
                   title: `<i class="fas fa-minus-circle fa-fw"></i> <?=_("Unshare")?>`,
                   content: "<?=_("Users will lose their access to the wall.<br>Unshare anyway?")?>",
                   cb_close: () =>
                     $share.find("li.list-group-item.active")
                       .removeClass ("active todelete"),
                   cb_ok: () => plugin.unlinkGroup ({id: id}, groupType)
                 });
              break;

            case "link-group":

              H.loadPopup ("groupAccess", {
                init: ($p)=>
                {
                  $p.find(".send-msg input[type='checkbox']")
                    .on("change", function ()
                    {
                      const $div = $(this).closest (".send-msg");

                      if (this.checked)
                        $div.removeClass ("disabled");
                      else
                        $div.addClass ("disabled");
                    });

                  $_groupAccessPopup = $p;
                }
              });
              break;
          }
        });

      $(document)
        .on("click", "#swallPopup .list-group-item",
        function (e)
        {
          if (this.classList.contains ("is-wall-creator"))
          {
            const $row = $(this);

            $row.addClass ("active todelete");

            plugin.openUpdateGroup ({
              groupId: $row[0].dataset.id,
              name: $row.find(".name").text(),
              description: $row.find(".desc").text()
            });
          }
          else
            $share.find("button[data-action='users-search'] i").click ();
        });

      $share.find("button")
        .on("click", function (e)
        {
          const action = this.dataset.action;

          if (action == "add-gtype-<?=WPT_GTYPES_GEN?>" ||
              action == "add-gtype-<?=WPT_GTYPES_DED?>")
            plugin.openAddGroup (action.match(/add\-gtype\-([^\-]+)/)[1]);
        });
    },

    // METHOD onSubmitGroup ()
    onSubmitGroup ($p, btn, e)
    {
      const plugin = this,
            type = btn.dataset.type,
            groupId = btn.dataset.groupid,
            $inputs = $p.find ("input");

      e.stopImmediatePropagation ();

      if (!groupId)
      {
        $p[0].dataset.noclosure = true;

        if (plugin.checkRequired ($inputs))
          plugin.createGroup (type, {
            name: H.noHTML ($inputs[0].value),
            description: H.noHTML ($inputs[1].value)
          });
      }
      else if (plugin.checkRequired ($inputs))
      {
        plugin.updateGroup ({
          groupId: groupId,
          name: H.noHTML ($inputs[0].value),
          description: H.noHTML ($inputs[1].value)
        });
      }
    },

    // METHOD openAddGroup ()
    openAddGroup (type)
    {
      const plugin = this;
      let title,
          desc;

      if (type == <?=WPT_GTYPES_GEN?>)
      {
        title = "<?=_("Create a <b>generic</b> group")?>";
        desc =  "<?=_("The group will also be available for all your other walls.")?>";
      }
      else
      {
        title = "<?=_("Create a <b>dedicated</b> group")?>";
        desc = "<?=_("The group will be available only for the current wall.")?>";
      }

      H.loadPopup ("group", {
        open: false,
        init: ($p)=>
        {
          $p.find(".btn-primary")
            .on("click", function (e)
            {
              plugin.onSubmitGroup ($p, this, e);
            });

          $_groupPopup = $p;
        },
        cb: ($p)=>
        {
          $p[0].dataset.action = "create";
          $p[0].dataset.noclosure = true;

          $p.find(".modal-title span").html (title);
          $p.find(".desc").html (desc);

          $p.find("button.btn-primary")[0].dataset.type = type;
          $p.find("button.btn-primary").html (`<i class="fas fa-bolt"></i> <?=_("Create")?>`);
          $p.find("button.btn-secondary").html (`<i class="fas fa-undo-alt"></i> <?=_("Cancel")?>`);

          H.openModal ($p);
        }
      });
    },

    // METHOD openUpdateGroup ()
    openUpdateGroup (args)
    {
      const plugin = this;

      H.loadPopup ("group", {
        open: false,
        init: ($p)=>
        {
          $p.find(".btn-primary")
            .on("click", function (e)
            {
              plugin.onSubmitGroup ($p, this, e);
            });

          $_groupPopup = $p;
        },
        cb: ($p)=>
        {
          $p[0].dataset.action = "update";
          $p[0].dataset.noclosure = true;

          $p.find(".modal-title span").html ("<?=_("Update this group")?>");

          $p.find("input")[0].value = args.name;
          $p.find("input")[1].value = args.description||"";

          $p.find("button.btn-primary")[0].dataset.groupid = args.groupId;
          $p.find("button.btn-primary").html (`<i class="fas fa-save"></i> <?=_("Save")?>`);
          $p.find("button.btn-secondary").html (`<i class="fas fa-times"></i> <?=_("Close")?>`);

          H.openModal ($p);
        }
      });
    },

    // METHOD open ()
    open ()
    {
      this.displayGroups ();
    },

    // METHOD linkGroup ()
    linkGroup (args)
    {
      const $wall = S.getCurrent ("wall"),
            wallId = $wall.wall ("getId"),
            $group = this.element.find ("li.todelete"),
            $sendMsg = $_groupAccessPopup.find (".send-msg"),
            data = {
              type:
                $group.parent().hasClass("gtype-<?=WPT_GTYPES_DED?>") ?
                   <?=WPT_GTYPES_DED?> : <?=WPT_GTYPES_GEN?>,
              access:
                $_groupAccessPopup.find("input[name='access']:checked").val (),
              sendmail: $sendMsg.find("input[type='checkbox']")[0].checked ?
                {
                  userFullname:
                    $("#accountPopup").account ("getProp", "fullname"),
                  wallTitle: $wall.wall ("getName")
                } : null
            };

      H.request_ws (
        "POST",
        "wall/"+wallId+"/group/"+$group[0].dataset.id+"/link",
        data,
        // success cb
        (d) =>
        {
          if (d.error_msg)
            H.raiseError (null, d.error_msg);
          else
            this.displayGroups ();
        });
    },

    // METHOD unlinkGroup ()
    unlinkGroup (args, groupType)
    {
      const $share = this.element,
            wallId = S.getCurrent("wall").wall ("getId");

      $share.find(".nav-link.active").removeClass ("active");
      $share.find(".tab-content .tab-pane.active").removeClass ("show active");
      $share.find(".nav-tabs .gtype-"+groupType).addClass ("active");
      $share.find(".tab-content #gtype-"+groupType).tab ("show");

      H.request_ws (
        "POST",
        "wall/"+wallId+"/group/"+args.id+"/unlink",
        null,
        // success cb
        (d) =>
        {
          if (d.error_msg)
            H.raiseError (null, d.error_msg);
          else
            this.displayGroups ();
        });
    },

    // METHOD deleteGroup ()
    deleteGroup ()
    {
      const $group = this.element.find("li.todelete"),
            service = ($group[0].dataset.type == <?=WPT_GTYPES_DED?>) ?
              "wall/"+S.getCurrent("wall").wall("getId")+
                "/group/"+$group[0].dataset.id :
              "group/"+$group[0].dataset.id

      H.request_ws (
        "DELETE",
        service,
        null,
        // success cb
        (d) =>
        {
          if (d.error_msg)
            H.raiseError (null, d.error_msg);
          else
            this.displayGroups ();
        });
    },

    // METHOD createGroup ()
    createGroup (type, args)
    {
      const service = (type == <?=WPT_GTYPES_DED?>) ?
              "wall/"+S.getCurrent("wall").wall("getId")+"/group" :
              "group";

      H.request_ws (
        "PUT",
        service,
        args,
        // success cb
        (d) =>
        {
          if (d.error_msg)
            H.displayMsg ({type: "warning", msg: d.error_msg});
          else
          {
            this.displayGroups ();
            $_groupPopup.modal ("hide");
          }
        });
    },

    // METHOD updateGroup ()
    updateGroup (args)
    {
      H.request_ws (
        "POST",
        "group/"+args.groupId,
        args,
        // success cb
        (d) =>
        {
          if (d.error_msg)
            H.displayMsg ({type: "warning", msg: d.error_msg});
          else
          {
            this.displayGroups ();
            $_groupPopup.modal ("hide");
          }
        });
    },

    // METHOD displayGroups ()
    displayGroups ()
    {
      const $share = this.element,
            wallPlugin = S.getCurrent("wall").wall ("getClass"),
            isOwner = (wallPlugin.settings.ownerid == wpt_userData.id),
            $body = $share.find (".modal-body");

      H.fetch (
        "GET",
        "wall/"+wallPlugin.settings.id+"/group",
        null,
        // success cb
        (d) =>
        {
          if (d.error_msg)
            return H.raiseError (null, d.error_msg);
        
          const div = $body.find(".list-group.attr")[0],
                pClass = div.parentNode.classList;
          let html = '';

          if (d.in.length)
          {
            const active =
                    document.querySelector (".modal li.list-group-item.active");

            if (isOwner)
              wallPlugin.setShared (true);

            pClass.add ("scroll");
            $share.find(".grp-lb").text ("<?=_("Other available groups:")?>");

            html = `<label><?=_("The wall is shared with the following groups:")?></label>`;

            d.in.forEach ((item) =>
              {
                const isDed = (item.item_type == <?=WPT_GTYPES_DED?>),
                      typeIcon = (d.delegateAdminId) ? '' : `<i class="${isDed ? "fas fa-asterisk":"far fa-circle"} fa-xs"></i>`,
                      unlinkBtn = (d.delegateAdminId) ? '' : `<button data-action="unlink-group" type="button" class="btn btn-secondary btn-xs btn-share" data-toggle="tooltip" title="<?=_("Cancel sharing for this group")?>"><i class="fas fa-minus-circle"></i><?=_("Unshare")?></button>`;

                html += `<li data-id="${item.id}" data-type="${item.item_type}" data-name="${H.htmlEscape(item.name)}" data-delegateadminid=${d.delegateAdminId||0} class="list-group-item list-group-item-action${d.delegateAdminId?'':' is-wall-creator'}${active&&active.dataset.id==item.id?" active todelete":""}"><div class="userscount" data-action="users-search" data-toggle="tooltip" title="${item.userscount} <?=_("users in this group")?>">${H.getAccessIcon(item.access)}<span class="wpt-badge">${item.userscount}</span></div> <span class="name">${typeIcon}${item.name}</span> <span class="desc">${item.description||""}</span><button data-action="users-search" type="button" class="close" data-toggle="tooltip" title="<?=_("Manage users")?>"><i class="fas fa-user-friends fa-fw fa-xs"></i></button>${unlinkBtn}</li>`;
              });

            if (d.in.length == 1)
              pClass.add ("one");
            else
              pClass.remove ("one");
          }
          else
          {
            if (isOwner)
              wallPlugin.setShared (false);

            $share.find(".grp-lb").text ("<?=_("Available groups:")?>");
            wallPlugin.element.find("thead th:eq(0)").html ("&nbsp;");

            html = (d.delegateAdminId) ?
              "<span class='nogroup'><?=_("You cannot manage any of the existing groups.")?></span>" :
              "<span class='nogroup'><?=_("This wall is not shared with any group!")?></span>";
            pClass.remove ("scroll");
          }

          div.innerHTML = html;

          if (!d.delegateAdminId)
          {
            $body.find(".delegate-admin-only").hide ();

            _displaySection ($body.find (".list-group.gtype-<?=WPT_GTYPES_DED?>.noattr"), <?=WPT_GTYPES_DED?>, d.notin);

            _displaySection ($body.find (".list-group.gtype-<?=WPT_GTYPES_GEN?>.noattr"), <?=WPT_GTYPES_GEN?>, d.notin);
  
            $body.find(".creator-only").show ();
          }
          else
          {
            $body.find(".creator-only").hide ();
            $body.find(".delegate-admin-only").show ();
          }

          H.enableTooltips ($share.find(".modal-body [data-toggle='tooltip']"));

          H.openModal ($share);
        });
    }

  });

<?php echo $Plugin->getFooter ()?>
