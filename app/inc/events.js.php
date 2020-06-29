$(function()
{
  "use strict";

  const $_walls = wpt_sharer.getCurrent ("walls");

  // EVENT resize on window
  if (!document.querySelector ("body.login-page"))
  {
    // EVENT onbeforeunload
    window.onbeforeunload = function ()
      {
        $(".chatroom").each (function ()
          {
            const $chatroom = $(this);

            if ($chatroom.css("display") == "table")
              $chatroom.wpt_chatroom ("leave");
          });
      };

    // EVENTS resize & orientationchange
    $(window)
      .on("resize orientationchange", function()
      {
        const $wall = wpt_sharer.getCurrent ("wall");
  
        wpt_fixMenuHeight ();
        wpt_fixMainHeight ();
  
        if ($wall.length)
        {
          const $zoom = $(".tab-content.walls"),
                $modal = $(".modal.m-fullscreen[data-customwidth]"),
                $chatroom = wpt_sharer.getCurrent ("chatroom"),
                $filters = wpt_sharer.getCurrent ("filters"),
                $arrows = wpt_sharer.getCurrent ("arrows");

          // Reposition relationships
          $wall.wpt_wall ("repositionPostitsPlugs");

          if ($modal.length)
            wpt_resizeModal ($modal);
   
          // Reposition chatroom popup if it is out of bounds
          if ($chatroom && $chatroom.is (":visible"))
            $chatroom.wpt_chatroom ("fixPosition");
   
          // Reposition filters popup if it is out of bounds
          if ($filters.is (":visible"))
            $filters.wpt_filters ("fixPosition");

          if ($arrows.is(":visible"))
            $arrows.wpt_arrows ("reset");
   
          if ($zoom[0].dataset.zoomlevelorigin)
          {
            if ($zoom[0].dataset.zoomtype == "screen")
              $wall.wpt_wall ("zoom", {type:"screen"});
            else
            {
              $wall.wpt_wall ("zoom", {type: "="});
              $('.dropdown-menu li[data-action="zoom-screen"] a')
                .removeClass ("disabled");
            }
          }
     
          // Fix TinyMCE menu placement with virtual keyboard on touch devices
          if ($.support.touch && $("#postitUpdatePopup").is(":visible"))
            $(".tox-selected-menu").css ("top",
              ($(".tox-menubar")[0].getBoundingClientRect().bottom -
                document.body.getBoundingClientRect().bottom - 2)+"px");
        }
      });
  }

  // EVENT walls scroll
  let _timeoutScroll,
      _scrollDiff = null;
  $_walls.on("scroll", function(e)
    {
      const $wall = wpt_sharer.getCurrent ("wall");

      if ($wall.length)
      {
        const $arrows = wpt_sharer.getCurrent ("arrows"),
              $filters = wpt_sharer.getCurrent ("filters");

        if (!wpt_sharer.get ("wall-dragging"))
        {
          const scroll = {
            top: $(this).scrollTop (),
            left: $(this).scrollLeft ()
          };

          if (_scrollDiff === null)
            _scrollDiff = {
              top: scroll.top,
              left: scroll.left
            };

          if (!wpt_sharer.get ("plugs-hidden") &&
              (Math.abs(scroll.top - _scrollDiff.top) > 1 ||
               Math.abs(scroll.left - _scrollDiff.left) > 1))
          {
            $wall.wpt_wall ("hidePostitsPlugs");
            wpt_sharer.set ("plugs-hidden", true);
          }

          // Reposition relationships
          if (!$filters || !$filters.hasClass ("plugs-hidden"))
          {
            clearTimeout (_timeoutScroll);
            _timeoutScroll = setTimeout (() =>
            {
              _scrollDiff = null;

              $wall.wpt_wall ("showPostitsPlugs");
              wpt_sharer.unset ("plugs-hidden");

            }, 150);
          }
        }

        // Update arrows
        if ($arrows.is (":visible"))
          $arrows.wpt_arrows ("update");
      }
    });

  // EVENT click on main menu
  $(document).on("click", ".nav-link:not(.dropdown-toggle),"+
                          ".dropdown-item", wpt_closeMainMenu);

  // EVENT mousedown on walls tabs
  $(document).on("mousedown", ".nav-tabs.walls a.nav-link",
    function (e)
    {
      const close = $(e.target).hasClass ("close"),
            rename = (!close && $(this).hasClass ("active"));

      if (rename && wpt_checkAccess ("<?=WPT_RIGHTS['walls']['admin']?>"))
        wpt_sharer.getCurrent("wall").wpt_wall (
          "openPropertiesPopup", {forRename: true});

      if (!rename && !close)
      {
        const $chatroom = wpt_sharer.getCurrent ("chatroom");

        if ($chatroom)
          $chatroom.wpt_chatroom ("closeUsersTooltip");

        $("#settingsPopup").wpt_settings ("saveOpenedWalls",
          $(this).attr("href").split("-")[1]);
      }

    });

  // EVENT hidden.bs.tab on walls tabs
  $(document).on("hidden.bs.tab", ".walls a[data-toggle='tab']",
    function (e)
    {
      $_walls.wpt_wall ("hidePostitsPlugs");
    });

  // EVENT shown.bs.tab on walls tabs
  $(document).on("shown.bs.tab", ".walls a[data-toggle='tab']",
    function (e)
    {
          // If we are massively restoring all walls, do nothing here
      if ($_walls.find(".wall[data-restoring]").length ||
          // If we are massively closing all walls, do nothing here
          wpt_sharer.get ("closingAll"))
        return;

      wpt_sharer.reset ();

      // New wall
      const $wall = wpt_sharer.getCurrent ("wall");

      // Need wall to continue
      if (!$wall.length) return;

      // Reinit search plugin for the current wall
      $("#postitsSearchPopup").wpt_postitsSearch (
        "restore", $wall[0].dataset.searchstring||"");

      $wall.wpt_wall ("zoom", {type: "normal", "noalert": true});

      $("#walls")
          .scrollLeft(0)
          .scrollTop (0);

      const $menu = $("#main-menu"),
            $chatroom = wpt_sharer.getCurrent ("chatroom"),
            chatRoomVisible = $chatroom.is (":visible"),
            $arrows = wpt_sharer.getCurrent ("arrows");

      // Manage chatroom checkbox menu
      $menu
        .find("li[data-action='chatroom'] input")[0].checked = chatRoomVisible;
      if (chatRoomVisible)
      {
        $chatroom.wpt_chatroom ("removeAlert");
        $chatroom.wpt_chatroom ("setCursorToEnd");
      }

      // Manage filters checkbox menu
      $menu.find("li[data-action='filters'] input")[0].checked =
        wpt_sharer.getCurrent("filters").is (":visible");

      // Manage arrows checkbox menu
      $menu.find("li[data-action='arrows'] input")[0].checked =
        $arrows.is (":visible");
      $arrows.wpt_arrows ("reset");

      // Refresh wall if it has not just been opened
      if (!wpt_sharer.get ("newWall"))
        $wall.wpt_wall ("refresh");

      $wall.wpt_wall ("menu", {from: "wall", type: "have-wall"});

      $(window).trigger ("resize");
    });

  // CATCH <Enter> key on popups
  $(document).on("keypress", ".modal, .popover",
    function (e)
    {
      if (e.which == 13 && e.target.tagName == "INPUT")
      {
        const $popup = $(this);
        let $btn = $popup.find (".btn-primary");

        if (!$btn.length)
          $btn = $popup.find (".btn-success");

        if ($btn.length)
        {
          e.preventDefault ();
          $btn.trigger ("click");
        }
      }

    });

  // EVENT show.bs.modal on popups
  $(".modal").on("show.bs.modal",
    function(e)
    {
      const $popup = $(this),
            $dialog = $popup.find (".modal-dialog"),
            $postit = wpt_sharer.getCurrent ("postit"),
            modalsCount = $(".modal:visible").length;

      // If there is already opened modals
      if (modalsCount)
      {
        $dialog[0].dataset.toclean = 1;
        $dialog.addClass ("modal-sm shadow");
        $dialog.find("button.btn").addClass ("btn-sm");
      }
      else if ($dialog[0].dataset.toclean)
      {
        $dialog.find("button.btn").removeClass ("btn-sm");
        $dialog.removeClass ("modal-sm shadow");
        $dialog[0].removeAttribute ("data-toclean");
      }

      // Get postit color and set modal header color the same
      if (!modalsCount && $postit.length)
        $postit.wpt_postit ("setPopupColor", $(this));
      else
        $popup.find(".modal-header,.modal-title,.modal-footer").each (
          function ()
          {
            this.className = this.className.replace (/color\-[a-z]+/, "");
          });

      // Set focus on first autofocus field if not touch device
      if (!$.support.touch)
        setTimeout (() => $popup.find("[autofocus]:eq(0)").focus (), 150);
    });  


  // EVENT hide.bs.modal on popups
  $(".modal").on("hide.bs.modal",
    function (e)
    {
      const $popup = $(this),
            $postit = wpt_sharer.getCurrent ("postit");

      switch (e.target.id)
      {
        case "postitUpdatePopup":

          const data = wpt_sharer.get ("postit-data");

          // Return if we are closing the postit modal from the confirmation
          // popup
          if (data && data.closing) return;

          const title = $("#postitUpdatePopupTitle").val (),
                content = tinymce.activeEditor.getContent (),
                cb_cancel = () =>
                  {
                    wpt_sharer.set ("postit-data", {closing: true});

                    //FIXME
                    $(".tox-toolbar__overflow").hide ();
                    $(".tox-menu").hide ();
    
                    $popup.find("input").val ("");
                    $postit.wpt_postit ("unedit");

                    $popup.modal ("hide");
                    wpt_sharer.unset ("postit-data");

                    tinymce.activeEditor.resetContent ();

                    if ($.support.touch)
                      wpt_fixVKBScrollStop ();
                  };

          // If there is pending changes, ask confirmation to user
          if (data && (
            // Content change detection
            tinymce.activeEditor.isDirty () ||
            // Title change detection
            wpt_convertEntities(data.title) != wpt_convertEntities(title)))
          {
            e.preventDefault ();

            wpt_openConfirmPopup ({
              type: "save-postits-changes",
              icon: "save",
              content: `<?=_("Save changes?")?>`,
              cb_ok: () =>
                {
                  $postit.wpt_postit ("setTitle", title);
                  $postit.wpt_postit ("setContent", content);

                  $postit[0].removeAttribute ("data-uploadedpictures");
                },
              cb_cancel: cb_cancel
            });

            wpt_sharer.set ("postit-data", data);
          }
          else
            cb_cancel ();
          break;
      }

    });

  // EVENT hidden.bs.modal on popups
  $(".modal").on("hidden.bs.modal",
    function(e)
    {
      const $popup = $(this),
            $wall = wpt_sharer.getCurrent ("wall"),
            type = $popup[0].dataset.popuptype,
            openedModals = $(".modal:visible").length,
            $postit = wpt_sharer.getCurrent ("postit"),
            $header = wpt_sharer.getCurrent ("header");

      // Prevent child popups from removing scroll to their parent
      if (openedModals)
        $("body").addClass ("modal-open");

      switch (e.target.id)
      {
        case "infoPopup":

          switch (type)
          {
            // Reload app
            case "app-upgrade":
            case "app-reload":

              return location.href = '/r.php?u';
              break;

            case "app-logout":
              $("<div/>").wpt_login ("logout");
              break;
          }
          break;

        case "plugPopup":

          const from = wpt_sharer.get ("link-from");

          if (from)
            from.cancelCallback ();
          break;

        case "wallPropertiesPopup":

          if (wpt_checkAccess ("<?=WPT_RIGHTS['walls']['admin']?>") &&
              !$popup[0].dataset.uneditdone)
            $wall.wpt_wall ("unedit");
          break;

        case "postitViewPopup":

          $postit.wpt_postit ("unsetCurrent");
          break;

        case "postitAttachmentsPopup":

          $postit.wpt_postit ("unedit");
          break;

        case "confirmPopup":
          wpt_sharer.get("confirmPopup").cb_cancel ();
          // No break
        case "usersSearchPopup":
        case "groupAccessPopup":
        case "groupPopup":

          $(".modal").find("li.list-group-item.active")
            .removeClass ("active todelete");

          break;
      }

    });

  // EVENT click on popup buttons
  $(".modal .modal-footer .btn").on("click",
    function (e)
    {
      const $popup = $(this).closest (".modal"),
            $wall = wpt_sharer.getCurrent ("wall"),
            type = $popup[0].dataset.popuptype,
            closePopup = !!!$popup[0].dataset.noclosure,
            $postit = wpt_sharer.getCurrent ("postit"),
            $header = wpt_sharer.getCurrent ("header");

      $popup[0].removeAttribute ("data-noclosure");

      if ($(this).hasClass ("btn-primary"))
      {
        switch ($popup.attr ("id"))
        {
          case "plugPopup":
            wpt_sharer.get ("link-from")
              .confirmCallback ($popup.find("input").val());
            break;

          case "postitUpdatePopup":

            $postit.wpt_postit("setTitle",$("#postitUpdatePopupTitle").val ());
            $postit.wpt_postit("setContent",tinymce.activeEditor.getContent());

            $postit[0].removeAttribute ("data-uploadedpictures");
            wpt_sharer.unset ("postit-data");
            break;

          case "groupAccessPopup":

            $("#shareWallPopup").wpt_shareWall ("linkGroup");
            break;

          case "groupPopup":

            if ($popup[0].dataset.action == "update")
              $popup[0].dataset.noclosure = true;
            break;

          // Upload postit attachment
          case "postitAttachmentsPopup":

            $popup[0].dataset.noclosure = true;
            $postit.wpt_postit ("uploadAttachment");
            break;

          // Manage confirmations
          case "confirmPopup":

            wpt_sharer.get("confirmPopup").cb_ok ();
            break;

          // Create new wall
          case "createWallPopup":

            var Form = new Wpt_accountForms (),
                $inputs = $popup.find("input");

            $popup[0].dataset.noclosure = true;

            if (Form.checkRequired ($inputs) && Form.validForm ($inputs))
            {
              const data = {
                      name: $popup.find("input").val(),
                      grid: $popup.find("#w-grid")[0].checked
                    };

              if (data.grid)
                data.dim = {
                  colsCount: $popup.find(".cols-rows input:eq(0)").val(),
                  rowsCount: $popup.find(".cols-rows input:eq(1)").val()
                };
              else
                data.dim = {
                  width: $popup.find(".width-height input:eq(0)").val(),
                  height: $popup.find(".width-height input:eq(1)").val()
                };

              $("<div/>").wpt_wall ("addNew", data, $popup);
            }
            break;

          // UPDATE wall name and description
          //TODO Should be a wpt_wall() method
          case "wallPropertiesPopup":

            var Form = new Wpt_accountForms (),
                $inputs = $popup.find("input:visible"),
                name =
                  wpt_noHTML ($popup.find(".name input").val ()),
                description =
                  wpt_noHTML ($popup.find(".description textarea").val ());

            $popup[0].dataset.noclosure = true;

            if (Form.checkRequired ($inputs) && Form.validForm ($inputs))
            {
              const oldName = $wall.wpt_wall ("getName"),
                    $cell = $wall.find("td"),
                    oldW = $cell.outerWidth ();

              $wall.wpt_wall ("setName", name);
              $wall.wpt_wall ("setDescription", description);

              $wall.wpt_wall ("unedit",
                () =>
                {
                  $popup[0].dataset.uneditdone = 1;
                  $popup.modal ("hide");
                },
                () =>
                {
                  $wall.wpt_wall ("setName", oldName);
                  //FIXME
                  $wall.wpt_wall ("edit");
                });

              if ($inputs[1] && $inputs[1].value != oldW ||
                  $inputs[2] && $inputs[2].value != $cell.outerHeight ())
              {
                const w = Number ($inputs[1].value) + 1,
                      h = Number ($inputs[2].value);

                function __resize (args)
                {
                  $wall.find("thead th:eq(1),td").css ("width", args.newW);
                  $wall.find(".ui-resizable-s").css ("width", args.newW + 2);

                  if (args.newH)
                  {
                    $wall.find("tbody th,td").css ("height", args.newH);
                    $wall.find(".ui-resizable-e").css ("height", args.newH+2);
                  }

                  $wall.wpt_wall ("fixSize", args.oldW, args.newW);
                }

                __resize ({newW: w, oldW: oldW, newH: h});
                if ($wall.find("td").outerWidth () != w)
                  __resize ({newW: $wall.find("td").outerWidth (), oldW: w});

                $cell.wpt_cell ("edit");
                $cell.wpt_cell ("reorganize");
                $cell.wpt_cell ("unedit");
              }
            }
            return;
        }
      }

      if (closePopup)
        $popup.modal ("hide");
    });

  // EVENT click on logout button
  $("#logout").on("click",
    function (e)
    {
      wpt_closeMainMenu ();

      wpt_openConfirmPopup ({
        type: "logout",
        icon: "power-off",
        content: `<?=_("Do you really want to logout from wopits?")?>`,
        cb_ok: () => $("<div/>").wpt_login ("logout")
      });
    });

});
