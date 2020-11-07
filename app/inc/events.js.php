$(function()
{
  "use strict";

  const $_walls = S.getCurrent ("walls");

  if (!H.isLoginPage ())
  {
    $(window)
      // EVENTS resize & orientationchange on window
      .on("resize orientationchange", function(e)
      {
        if (e.target.tagName)
          return;

        const $wall = S.getCurrent ("wall");
  
        H.fixMenuHeight ();
        H.fixMainHeight ();
  
        if ($wall.length)
        {
          // Reposition relationships
          $wall.wall ("repositionPostitsPlugs");

          const $modal = $(".modal.m-fullscreen[data-customwidth]");
          if ($modal.length)
            H.resizeModal ($modal);
   
          // Reposition chat popup if it is out of bounds
          const $c = S.getCurrent ("chat");
          if ($c.is (":visible"))
            $c.chat ("fixPosition");
   
          // Reposition filters popup if it is out of bounds
          const $f = S.getCurrent ("filters");
          if ($f.is (":visible"))
            $f.filters ("fixPosition");

          const $a = S.getCurrent ("arrows");
          if ($a.is (":visible"))
            $a.arrows ("reset");
   
          const $zoom = $(".tab-content.walls");
          if ($zoom[0].dataset.zoomlevelorigin)
          {
            if ($zoom[0].dataset.zoomtype == "screen")
              $wall.wall ("zoom", {type:"screen"});
            else
            {
              $wall.wall ("zoom", {type: "="});
              $('.dropdown-menu li[data-action="zoom-screen"] a')
                .removeClass ("disabled");
            }
          }
     
          // Fix TinyMCE menu placement with virtual keyboard on touch devices
          if (!H.haveMouse () && $("#postitUpdatePopup").is(":visible"))
            $(".tox-selected-menu").css ("top",
              ($(".tox-menubar")[0].getBoundingClientRect().bottom -
                document.body.getBoundingClientRect().bottom - 2)+"px");

          // Reposition wall menu if it is out of bounds
          S.getCurrent("wmenu").wmenu ("fixPosition");
        }
      });
  }

  // EVENT walls scroll
  let _timeoutScroll,
      _scrollDiff = null;
  $_walls.on("scroll", function(e)
    {
      const $wall = S.getCurrent ("wall");

      if ($wall.length)
      {
        const $arrows = S.getCurrent ("arrows");

        if (!S.get ("wall-dragging"))
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

          if (!S.get ("plugs-hidden") &&
              (Math.abs(scroll.top - _scrollDiff.top) > 1 ||
               Math.abs(scroll.left - _scrollDiff.left) > 1))
          {
            $wall.wall ("hidePostitsPlugs");
            S.set ("plugs-hidden", true);
          }

          // Reposition relationships
          if (!S.getCurrent("filters").hasClass ("plugs-hidden"))
          {
            clearTimeout (_timeoutScroll);
            _timeoutScroll = setTimeout (() =>
            {
              _scrollDiff = null;

              $wall.wall ("showPostitsPlugs");
              S.unset ("plugs-hidden");

            }, 150);
          }
        }

        // Update arrows
        if ($arrows.is (":visible"))
          $arrows.arrows ("update");
      }
    });

  // EVENT click on main menu
  $(document).on("click", ".nav-link:not(.dropdown-toggle),"+
                          ".dropdown-item", H.closeMainMenu);

  // EVENT mousedown on walls tabs
  $(document).on("mousedown", ".nav-tabs.walls a.nav-link",
    function (e)
    {
      const tab = e.target,
            isActive = this.classList.contains ("active"),
            close = tab.classList.contains ("close"),
            share = (isActive && tab.classList.contains ("fa-share")),
            rename = (isActive && !share && !close);

      $(this).parent().find("[data-toggle='tooltip']").tooltip ("hide");

      if (share)
        return H.loadPopup ("swall", {
                 open: false,
                 cb: ($p)=> $p.swall ("open")
               });

      if (rename)
        return S.getCurrent("wall")
                 .wall ("openPropertiesPopup", {renaming: true});

      if (!close)
      {
        const $c = S.getCurrent ("chat");
        if ($c.is(":visible"))
          $c.chat ("closeUsersTooltip");

        $("#settingsPopup").settings (
          "saveOpenedWalls", $(this).attr("href").split("-")[1]);
      }
    });

/*FIXME Useful?
  // Useful in rare case, when user have multiple sessions opened
  $(window).focus (
    function (e)
    {
      setTimeout (()=>
        {
          const $wall = S.getCurrent ("wall");

          if ($wall.length)
            $wall.wall ("refresh");

        }, 150);
    });
*/

  // EVENT hidden.bs.tab on walls tabs
  $(document).on("hide.bs.tab", ".walls a[data-toggle='tab']",
    function (e)
    {
      // Cancel zoom mode
      if (S.get ("zoom-level"))
        S.getCurrent("wall").wall ("zoom", {type: "normal", "noalert": true});

      document.querySelectorAll(".walls table.wall").forEach (
        (w)=> $(w).wall ("hidePostitsPlugs"));
    });

  // EVENT shown.bs.tab on walls tabs
  $(document).on("shown.bs.tab", ".walls a[data-toggle='tab']",
    function (e)
    {
          // If we are massively restoring all walls, do nothing here
      if ($_walls.find(".wall[data-restoring]").length ||
          // If we are massively closing all walls, do nothing here
          S.get ("closing-all"))
        return;

      S.reset ();

      // New wall
      const $wall = S.getCurrent ("wall");

      // Need wall to continue
      if (!$wall.length) return;

      $("#walls")
          .scrollLeft(0)
          .scrollTop (0);

      const $menu = $("#main-menu"),
            $chat = S.getCurrent ("chat"),
            chatVisible = $chat.is (":visible"),
            $filters = S.getCurrent ("filters"),
            $arrows = S.getCurrent ("arrows");

      // Show/hide super menu actions menu depending on user wall access rights
      S.getCurrent("smenu").smenu ("checkAllowedActions");

      // Manage chat checkbox menu
      $menu
        .find("li[data-action='chat'] input")[0].checked = chatVisible;
      if (chatVisible)
      {
        $chat.chat ("removeAlert");
        $chat.chat ("setCursorToEnd");
      }

      // Manage filters checkbox menu
      $menu.find("li[data-action='filters'] input")[0].checked =
        $filters.is (":visible");

      // Manage arrows checkbox menu
      $menu.find("li[data-action='arrows'] input")[0].checked =
        $arrows.is (":visible");
      $arrows.arrows ("reset");

      // Refresh wall if it has not just been opened
      if (!S.get ("newWall"))
      {
        $wall.wall ("refresh");
        $wall.wall ("displayExternalRef");
        $wall.wall ("displayHeaders");

        if (!($filters.is (":visible") && $filters.hasClass ("plugs-hidden")))
          $wall.wall ("showPostitsPlugs");
      }

      $wall.wall ("menu", {from: "wall", type: "have-wall"});

      $(window).trigger ("resize");
    });

  // EVENT keypress on popups and popovers to catch <enter> key
  $(document).on("keypress", ".modal,.popover",
    function (e)
    {
      if (e.which == 13 && e.target.tagName == "INPUT")
      {
        const $popup = $(this);
        let $btn = $popup.find (".btn-primary.btn-sm");

        if (!$btn.length)
          $btn = $popup.find (".btn-primary");

        if (!$btn.length)
          $btn = $popup.find (".btn-success");

        if ($btn.length)
        {
          e.preventDefault ();
          $btn.click ();
        }
      }

    });

  // EVENT show.bs.modal on popups
  $(document).on("show.bs.modal", ".modal",
    function(e)
    {
      const $popup = $(this),
            $dialog = $popup.find (".modal-dialog"),
            $postit = S.getCurrent ("postit"),
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
        $postit.postit ("setPopupColor", $(this));
      else
        $popup.find(".modal-header,.modal-title,.modal-footer").each (
          function ()
          {
            this.className = this.className.replace (/color\-[a-z]+/, "");
          });

      H.setAutofocus ($popup);
    });  

  // EVENT hidden.bs.modal on popups
  $(document).on("hidden.bs.modal", ".modal",
    function(e)
    {
      const $popup = $(this),
            $wall = S.getCurrent ("wall"),
            type = $popup[0].dataset.popuptype,
            $postit = S.getCurrent ("postit");

      // Prevent child popups from removing scroll to their parent
      if ($(".modal:visible").length)
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
              $("<div/>").login ("logout", {auto: true});
              break;
          }
          break;

        case "plugPopup":

          const from = S.get ("link-from");

          if (from)
            from.cancelCallback ();
          break;

        case "wpropPopup":

          if (H.checkAccess ("<?=WPT_WRIGHTS_ADMIN?>") &&
              !$popup[0].dataset.uneditdone)
            $wall.wall ("unedit");
          break;

        case "postitViewPopup":

          $postit.postit ("unsetCurrent");
          break;

        case "postitAttachmentsPopup":
        case "dpickPopup":

          $postit.postit ("unedit");
          break;

        case "confirmPopup":

          S.get("confirmPopup").cb_close ();
          break;

        case "usearchPopup":
        case "groupAccessPopup":
        case "groupPopup":

          $(".modal").find("li.list-group-item.active")
            .removeClass ("active todelete");

          break;
      }
    });

  // EVENT click on popup buttons
  $(document).on("click", ".modal .modal-footer .btn",
    function (e)
    {
      const $popup = $(this).closest (".modal"),
            $wall = S.getCurrent ("wall"),
            closePopup = !!!$popup[0].dataset.noclosure,
            $postit = S.getCurrent ("postit");

      $popup[0].removeAttribute ("data-noclosure");

      if ($(this).hasClass ("btn-primary"))
      {
        switch ($popup.attr ("id"))
        {
          case "dpickPopup":

            $popup.dpick ("save");
            break;

          case "plugPopup":

            S.get ("link-from")
              .confirmCallback ($popup.find("input").val());
            break;

          case "postitUpdatePopup":

            $postit.postit ("setTitle", $("#postitUpdatePopupTitle").val ());
            $postit.postit ("setContent", tinymce.activeEditor.getContent());

            $postit[0].removeAttribute ("data-uploadedpictures");
            S.unset ("postit-data");
            break;

          // Upload postit attachment
          case "postitAttachmentsPopup":

            $popup[0].dataset.noclosure = true;
            $postit.postit ("uploadAttachment");
            break;

          case "groupAccessPopup":

            $("#swallPopup").swall ("linkGroup");
            break;

          case "groupPopup":

            if ($popup[0].dataset.action == "update")
              $popup[0].dataset.noclosure = true;
            break;

          // Manage confirmations
          case "confirmPopup":

            S.get("confirmPopup").cb_ok ();
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

              $("<div/>").wall ("addNew", data, $popup);
            }
            break;

          // UPDATE wall name and description
          //TODO Should be a wall() method
          case "wpropPopup":

            var Form = new Wpt_accountForms (),
                $inputs = $popup.find("input:visible"),
                name = H.noHTML ($popup.find(".name input").val ()),
                description =
                  H.noHTML ($popup.find(".description textarea").val ());

            $popup[0].dataset.noclosure = true;

            if (Form.checkRequired ($inputs) && Form.validForm ($inputs))
            {
              const wallPlugin = $wall.wall ("getClass"),
                    oldName = wallPlugin.getName (),
                    $cell = $wall.find ("td"),
                    oldW = $cell.outerWidth ();

              wallPlugin.setName (name);
              wallPlugin.setDescription (description);

              wallPlugin.unedit (
                () =>
                {
                  $popup[0].dataset.uneditdone = 1;
                  $popup.modal ("hide");
                },
                () =>
                {
                  wallPlugin.setName (oldName);
                  //FIXME
                  wallPlugin.edit ();
                });

              if ($inputs[1] && $inputs[1].value != oldW ||
                  $inputs[2] && $inputs[2].value != $cell.outerHeight ())
              {
                const w = Number ($inputs[1].value) + 1,
                      h = Number ($inputs[2].value),
                      cellPlugin = $cell.cell ("getClass"),
                      __resize = (args)=>
                      {
                        $wall.find("thead th:eq(1),td")
                          .css ("width", args.newW);
                        $wall.find(".ui-resizable-s")
                          .css ("width", args.newW + 2);

                        if (args.newH)
                        {
                          $wall.find("tbody th,td")
                            .css ("height", args.newH);
                          $wall.find(".ui-resizable-e")
                            .css ("height", args.newH+2);
                        }

                        wallPlugin.fixSize (args.oldW, args.newW);
                      };

                __resize ({newW: w, oldW: oldW, newH: h});
                if ($wall.find("td").outerWidth () != w)
                  __resize ({newW: $wall.find("td").outerWidth (), oldW: w});

                cellPlugin.edit ();
                cellPlugin.reorganize ();
                cellPlugin.unedit ();
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
      H.closeMainMenu ();

      H.openConfirmPopup ({
        type: "logout",
        icon: "power-off",
        content: `<?=_("Do you really want to logout from wopits?")?>`,
        cb_ok: () => $("<div/>").login ("logout")
      });
    });

});
