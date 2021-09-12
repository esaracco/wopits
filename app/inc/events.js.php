/**
  Global javascript events
*/

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
        let tmp;
  
        H.fixMenuHeight ();
        H.fixMainHeight ();

        if ($wall.length)
        {
          // Refresh relations position
          $wall.wall ("repositionPostitsPlugs");

          tmp = document.querySelector (
                  ".modal.show.m-fullscreen[data-customwidth]");
          if (tmp)
            H.resizeModal ($(tmp));
   
          // Reposition chat popup if it is out of bounds
          tmp = S.getCurrent ("chat");
          if (tmp.is (":visible"))
            tmp.chat ("fixPosition");
   
          // Reposition filters popup if it is out of bounds
          tmp = S.getCurrent ("filters");
          if (tmp.is (":visible"))
            tmp.filters ("fixPosition");

          tmp = S.getCurrent ("arrows");
          if (tmp.is (":visible"))
            tmp.arrows ("reset");
   
          tmp = document.querySelector (".tab-content.walls");
          if (tmp.dataset.zoomlevelorigin)
          {
            if (tmp.dataset.zoomtype == "screen")
              $wall.wall ("zoom", {type: "screen"});
            else
            {
              $wall.wall ("zoom", {type: "="});
              $('.dropdown-menu li[data-action="zoom-screen"] a')
                .removeClass ("disabled");
            }
          }
     
          // Reposition wall menu if it is out of bounds
          S.getCurrent("wmenu").wmenu ("fixPosition");
        }
      });
  }

  // EVENT "scroll" on walls
  let _timeoutScroll,
      _scrollDiff = null;
  $_walls.on("scroll", function(e)
    {
      const $wall = S.getCurrent ("wall");

      if ($wall.length)
      {
        if (!S.get ("wall-dragging"))
        {
          const scroll = {
            top: this.scrollTop,
            left: this.scrollLeft
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

          // Refresh relations position
          if (!S.getCurrent("filters")[0].classList.contains ("plugs-hidden"))
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
        const $a = S.getCurrent ("arrows");
        if ($a.is (":visible"))
          $a.arrows ("update");
      }
    });

  // EVENT "click" on main menu
  $(document).on("click", ".nav-link:not(.dropdown-toggle),"+
                          ".dropdown-item", H.closeMainMenu);

  // EVENT "mousedown" on walls tabs
  $(document).on("mousedown", ".nav-tabs.walls a.nav-link",
    function (e)
    {
      const tab = e.target,
            isActive = this.classList.contains ("active"),
            close = tab.classList.contains ("close"),
            share = (isActive && tab.classList.contains ("fa-share")),
            rename = (isActive && !share && !close);

      if (share)
        return H.loadPopup ("swall", {
          open: false,
          cb: ($p)=> $p.swall ("open")
        });

      if (rename)
        return S.getCurrent("wall")
                 .wall ("openPropertiesPopup", {renaming: true});

      if (!close)
        $("#settingsPopup").settings (
          "saveOpenedWalls", this.getAttribute("href").split("-")[1]);
    });

  // EVENT "show" on relation's label menu
  $(document).on("show.bs.dropdown", ".plug-label", ()=> !H.disabledEvent ());

  // EVENT "hidden" on alert messages
  $(document).on("hidden.bs.toast", "#msg-container .toast",
    function (e)
    {
      bootstrap.Toast.getInstance (this).dispose ();
      this.remove ();
    });

  // EVENT "hide" on walls tabs
  $(document).on("hide.bs.tab", `.walls a[data-bs-toggle="tab"]`,
    function (e)
    {
      // Cancel zoom mode
      if (S.get ("zoom-level"))
        S.getCurrent("wall").wall ("zoom", {type: "normal", "noalert": true});

      S.getCurrent("wall").wall ("removePostitsPlugs", false);
    });

  // EVENT "shown" on walls tabs
  $(document).on("shown.bs.tab", `.walls a[data-bs-toggle="tab"]`,
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
      S.getCurrent("mmenu").mmenu ("checkAllowedActions");

      // Manage chat checkbox menu
      $menu.find(`li[data-action="chat"] input`)[0].checked = chatVisible;
      if (chatVisible)
      {
        $chat.chat ("removeAlert");
        $chat.chat ("setCursorToEnd");
      }

      // Manage filters checkbox menu
      $menu.find(`li[data-action="filters"] input`)[0].checked =
        $filters.is (":visible");

      // Manage arrows checkbox menu
      $menu.find(`li[data-action="arrows"] input`)[0].checked =
        $arrows.is (":visible");
      $arrows.arrows ("reset");

      // Refresh wall if it has not just been opened
      if (!S.get ("newWall"))
      {
        $wall.wall ("refresh");
        $wall.wall ("displayExternalRef");
        $wall.wall ("displayHeaders");
      }

      $wall.wall ("menu", {from: "wall", type: "have-wall"});

      $(window).trigger ("resize");
    });

  // EVENT "keypress" on popups and popovers to catch <enter> key
  $(document).on("keypress", ".modal,.popover",
    function (e)
    {
      if (e.which == 13 && e.target.tagName == "INPUT")
      {
        let btn = this.querySelector (".btn-primary.btn-sm");

        if (!btn)
          btn = this.querySelector (".btn-primary");

        if (!btn)
          btn = this.querySelector (".btn-success");

        if (btn)
        {
          e.preventDefault ();
          btn.click ();
        }
      }

    });

  // EVENT "show" on popups
  $(document).on("show.bs.modal", ".modal",
    function(e)
    {
      const dialog = this.querySelector (".modal-dialog"),
            modalsCount = $(".modal:visible").length;

      // If there is already opened modals
      if (modalsCount)
      {
        dialog.classList.remove ("modal-dialog-scrollable");

        dialog.dataset.toclean = 1;
        dialog.classList.add ("modal-sm", "shadow");
        dialog.querySelectorAll("button.btn").forEach (
          (b)=> b.classList.add ("btn-sm"));
      }
      else
      {
        let tmp;

        if (!H.haveMouse ())
          H.fixVKBScrollStart ();

        dialog.classList.add ("modal-dialog-scrollable");

        if (dialog.dataset.toclean)
        {
          dialog.querySelectorAll("button.btn").forEach (
            (b)=> b.classList.remove ("btn-sm"));
          dialog.classList.remove ("modal-sm", "shadow");
          dialog.removeAttribute ("data-toclean");
        }

        // Get postit color and set modal header color the same
        if ((tmp = S.getCurrent ("postit")).length)
          tmp.postit ("setPopupColor", $(this));
      }

      H.setAutofocus (this);
    });

  // Blur input/textarea to hide virtual keyboard
  if (!H.haveMouse ())
  {
    // EVENT "hide" on popups
    $(document).on("hide.bs.modal", ".modal",
     ()=> this.querySelectorAll("input,textarea").forEach (el=> el.blur ()));
  }

  // EVENT "hidden" on popups
  $(document).on("hidden.bs.modal", ".modal",
    function(e)
    {
      S.set ("still-closing", true, 500);

      // Prevent child popups from removing scroll to their parent
      if ($(".modal:visible").length)
        document.body.classList.add ("modal-open");
      else if (!H.haveMouse ())
        H.fixVKBScrollStop ();

      switch (e.target.id)
      {
        case "infoPopup":

          switch (this.dataset.popuptype)
          {
            // Reload app
            case "app-upgrade":
            case "app-reload":

              return location.href = '/r.php?u';

            case "app-logout":

              $("<div/>").login ("logout", {auto: true});
              break;
          }
          break;

        case "wpropPopup":

          if (H.checkAccess ("<?=WPT_WRIGHTS_ADMIN?>") &&
              !this.dataset.uneditdone)
            S.getCurrent("wall").wall ("unedit");
          break;

        case "postitViewPopup":

          S.getCurrent("postit").postit ("unsetCurrent");
          break;

        case "postitAttachmentsPopup":
        case "dpickPopup":

          S.getCurrent("postit").postit ("unedit");
          break;

        case "confirmPopup":

          S.get("confirmPopup").cb_close ();
          break;

        case "groupAccessPopup":
        case "groupPopup":

          $(".modal li.list-group-item.active").removeClass ("active");
          break;
      }
    });

  // EVENT "click" on popup buttons
  $(document).on("click", ".modal .modal-footer .btn",
    function (e)
    {
      const popup = this.closest (".modal"),
            $popup = $(popup),
            closePopup = !!!popup.dataset.noclosure,
            $postit = S.getCurrent ("postit");

      popup.removeAttribute ("data-noclosure");

      if (this.classList.contains ("btn-primary"))
      {
        switch (popup.id)
        {
          case "dpickPopup":

            $popup.dpick ("save");
            break;

          case "postitUpdatePopup":

            $postit.postit ("save", {
              content: tinymce.activeEditor.getContent (),
              progress: $(popup.querySelector(".slider")).slider ("value"),
              title: $("#postitUpdatePopupTitle").val ()
            });
            break;

          // Upload postit attachment
          case "postitAttachmentsPopup":

            popup.dataset.noclosure = true;
            $postit.find(".patt").patt ("upload");
            break;

          case "groupAccessPopup":

            $("#swallPopup").swall ("linkGroup");
            break;

          case "groupPopup":

            if (popup.dataset.action == "update")
              popup.dataset.noclosure = true;
            break;

          // Manage confirmations
          case "confirmPopup":

            S.get("confirmPopup").cb_ok ();
            break;

          // Create new wall
          case "createWallPopup":

            var Form = new Wpt_accountForms (),
                $inputs = $popup.find ("input");

            popup.dataset.noclosure = true;

            if (Form.checkRequired ($inputs) && Form.validForm ($inputs))
            {
              const data = {
                      name: popup.querySelector("input").value,
                      grid: popup.querySelector("#w-grid").checked
                    };

              if (data.grid)
                data.dim = {
                  colsCount: $popup.find(".cols-rows input:eq(0)").val (),
                  rowsCount: $popup.find(".cols-rows input:eq(1)").val ()
                };
              else
                data.dim = {
                  width: $popup.find(".width-height input:eq(0)").val (),
                  height: $popup.find(".width-height input:eq(1)").val ()
                };

              $("<div/>").wall ("addNew", data, $popup);
            }
            break;

          // Save wall properties
          case "wpropPopup":
            S.getCurrent("wall").wall ("saveProperties");
            return;
        }
      }

      if (closePopup)
        bootstrap.Modal.getInstance(popup).hide ();
    });

  // EVENT "click" on logout button
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
