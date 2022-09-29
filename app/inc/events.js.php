/**
  Global javascript events
*/

document.addEventListener ("DOMContentLoaded", ()=>
{
  "use strict";

  const _walls = document.getElementById ("walls");

  if (!H.isLoginPage ())
  {
    // EVENTS resize & orientationchange on window
    const _windowResizeEvent = (e)=>
      {
        if (e.target.tagName)
          return;

        const $wall = S.getCurrent ("wall");
  
        H.fixMenuHeight ();
        H.fixMainHeight ();

        if ($wall.length)
        {
          // Refresh relations position
          $wall.wall ("repositionPostitsPlugs");

          var tmp = document.querySelector (
                      ".modal.show.m-fullscreen[data-customwidth]");
          if (tmp)
            H.resizeModal ($(tmp));
   
          // Reposition chat popup if it is out of bounds
          var tmp = S.getCurrent ("chat");
          if (tmp.is (":visible"))
            tmp.chat ("fixPosition");
   
          // Reposition filters popup if it is out of bounds
          var tmp = S.getCurrent ("filters");
          if (tmp.is (":visible"))
            tmp.filters ("fixPosition");

          var tmp = document.querySelector (".tab-content.walls");
          if (tmp.dataset.zoomlevelorigin)
            $wall.wall ("zoom",
              {type: (tmp.dataset.zoomtype == "screen")?"screen":"="});
     
          // Reposition wall menu if it is out of bounds
          S.getCurrent("wmenu").wmenu ("fixPosition");
        }
      };
    window.addEventListener ("resize", _windowResizeEvent);
    window.addEventListener ("orientationchange", _windowResizeEvent);

    // EVENT "scroll" on walls
    let _timeoutScroll;
    let _plugsHidden = false;
    _walls.addEventListener ('scroll', () => {
      const $wall = S.getCurrent('wall');
  
      if (!$wall.length) return;

      if (!S.get('wall-dragging')) {
        if (!_plugsHidden) {
          $wall.wall('hidePostitsPlugs');
          _plugsHidden = true;
        }

        // Refresh relations position
        if (!S.getCurrent('filters')[0].classList.contains('plugs-hidden')) {
          clearTimeout(_timeoutScroll);
          _timeoutScroll = setTimeout (() => {
            $wall.wall('showPostitsPlugs');
            _plugsHidden = false;
          }, 150);
        }
      }
    });

    // EVENT "mousedown"
    document.body.addEventListener ("mousedown", function (e)
      {
        const el = e.target;

        // EVENT "mousedown" on walls tabs
        if (el.matches (".nav-tabs.walls a.nav-link,"+
                        ".nav-tabs.walls a.nav-link *"))
        {
          const a = (el.tagName == "A")?el:el.closest ("a.nav-link"),
                isActive = a.classList.contains ("active"),
                close = el.classList.contains ("close"),
                share = (isActive && el.classList.contains ("fa-share")),
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
              "saveOpenedWalls", a.getAttribute("href").split("-")[1]);
        }
      });

    // EVENT "hide.bs.tab"
    document.body.addEventListener ("hide.bs.tab", (e)=>
      {
        const el = e.target;
  
        // EVENT "hide.bs.tab" on walls tabs
        if (el.matches (`.walls a[data-bs-toggle="tab"]`))
        {
          // Cancel zoom mode
          if (S.get ("zoom-level"))
            S.getCurrent("wall").wall("zoom", {type: "normal","noalert": true});
  
          S.getCurrent("wall").wall ("removePostitsPlugs", false);
        }
      });
  
    // EVENT "shown.bs.tab"
    document.body.addEventListener ("shown.bs.tab", (e)=>
      {
            // If we are massively restoring all walls, do nothing here
        if (_walls.querySelector(".wall[data-restoring]") ||
            // If we are massively closing all walls, do nothing here
            S.get ("closing-all"))
          return;
  
        const el = e.target;
  
        // EVENT "shown.bs.tab" on walls tabs
        if (el.matches (`.walls a[data-bs-toggle="tab"]`))
        {
          S.reset ();
    
          // The new wall
          const $wall = S.getCurrent ("wall");
    
          // Need a wall to continue
          if (!$wall.length) return;
    
          $("#walls")
              .scrollLeft(0)
              .scrollTop (0);
    
          const menu = document.getElementById ("main-menu"),
                $chat = S.getCurrent ("chat"),
                $filters = S.getCurrent ("filters");
    
          // Show/hide super menu actions menu depending on user wall rights
          S.getCurrent("mmenu").mmenu ("checkAllowedActions");
    
          // Manage chat checkbox menu
          if ( (menu.querySelector(`li[data-action="chat"] input`)
                  .checked = $chat.is (":visible")) )
          {
            $chat.chat ("removeAlert");
            $chat.chat ("setCursorToEnd");
          }
    
          // Manage filters checkbox menu
          menu.querySelector(`li[data-action="filters"] input`)
            .checked = $filters.is (":visible");
    
          // Refresh wall if it has not just been opened
          if (!S.get ("newWall"))
          {
            $wall.wall ("refresh");
            $wall.wall ("displayExternalRef");
            $wall.wall ("displayHeaders");
          }
    
          $wall.wall ("menu", {from: "wall", type: "have-wall"});
    
          window.dispatchEvent (new Event("resize"));
        }
      });

    // EVENT "click" on logout button
    document.getElementById("logout").addEventListener ("click", ()=>
      {
        H.closeMainMenu ();
  
        H.openConfirmPopup ({
          type: "logout",
          icon: "power-off",
          content: `<?=_("Do you really want to logout from wopits?")?>`,
          cb_ok: () => $("<div/>").login ("logout")
        });
      });
  }

  // EVENT "keydown"
  document.body.addEventListener ("keydown", (e)=>
    {
      // If "ESC" while popup layer is opened, close it
      if (e.which == 27)
      {
        let tmp;

        // If popup layer, click on it to close popup
        if ( tmp = document.getElementById ("popup-layer") )
          tmp.click ();
        // If postit menu, click on menu button to close it
        else if ( tmp = document.querySelector (".postit-menu") )
          tmp.nextElementSibling.click ();
      }
    });

  // EVENT "show.bs.dropdown" on relation's label menu, to prevent menu from
  //       opening right after dragging
  document.body.addEventListener ("show.bs.dropdown", (e)=>
    H.disabledEvent () && e.preventDefault ());

  // EVENT "hidden.bs.toast" on alert messages
  document.body.addEventListener ("hidden.bs.toast", (e)=>
    {
      const el = e.target;

      bootstrap.Toast.getInstance (el).dispose ();
      el.remove ();
    });

  // EVENT "keypress" on popups and popovers to catch <enter> key
  document.body.addEventListener ("keypress", (e)=>
    {
      if (e.which != 13 || e.target.tagName != "INPUT")
        return;

      const popup = e.target.closest (".popover,.modal");
      if (!popup)
        return;

      const btn = popup.querySelector (
                    ".btn-primary.btn-sm,.btn-primary,.btn-success");
      if (btn)
      {
        e.preventDefault ();
        btn.click ();
      }
    });

  // EVENT "show" on popups
  document.body.addEventListener('show.bs.modal', (e) => {
    const el = e.target;
    const dialog = el.querySelector('.modal-dialog');
    const mstack = S.get('mstack') || [];
    const modalsCount = mstack.length;

    mstack.unshift(el);
    S.set('mstack', mstack);

    // If there is already opened modals
    if (modalsCount) {
      dialog.classList.remove('modal-dialog-scrollable');
      dialog.dataset.toclean = 1;
      dialog.classList.add('modal-sm', 'shadow');
      dialog.querySelectorAll('button.btn')
          .forEach ((b) => b.classList.add('btn-sm'));
    } else {
      const $ps = S.getCurrent('postit');

      if (!H.haveMouse() && H.getFirstInputFields(dialog)) {
        H.fixVKBScrollStart();
      }

      dialog.classList.add('modal-dialog-scrollable');

      if (dialog.dataset.toclean) {
        dialog.querySelectorAll('button.btn')
            .forEach ((b) => b.classList.remove('btn-sm'));
        dialog.classList.remove('modal-sm', 'shadow');
        dialog.removeAttribute('data-toclean');
      }

      // Get postit color and set modal header color the same
      if ($ps.length) {
        $ps.postit('setPopupColor', $(el));
      }
    }
  });

  // EVENT "shown" on popups
  document.body.addEventListener('shown.bs.modal', (e) => {
    if (H.haveMouse()) {
      H.setAutofocus(e.target);
    }
  });

  // EVENT "hide.bs.modal" on popups
  //       Blur input/textarea to hide virtual keyboard
  document.body.addEventListener ('hide.bs.modal', (e) => {
    const el = e.target;

    if (!H.haveMouse()) {
      el.querySelectorAll('input,textarea').forEach((el) => el.blur());
    }

    if (el.id === 'wpropPopup' &&
        H.checkAccess(`<?=WPT_WRIGHTS_ADMIN?>`) &&
        !el.dataset.uneditdone) {
      S.getCurrent('wall').wall('unedit');
    }
  });

  // EVENT "hidden" on popups
  document.body.addEventListener ("hidden.bs.modal", (e)=>
    {
      const el = e.target,
            mstack = S.get ("mstack");

      S.set ("still-closing", true, 500);

      mstack.shift ();
      S.set ("mstack", mstack);

      if (S.get('vkbData')) {
        H.fixVKBScrollStop();
      }

      // Prevent child popups from removing scroll to their parent
      if (mstack.length)
        document.body.classList.add ("modal-open");

      switch (el.id)
      {
        case "infoPopup":

          switch (el.dataset.popuptype)
          {
            // Reload app
            case "app-upgrade":
            case "app-reload":

              return location.href = "/r.php?u";

            case "app-logout":

              $("<div/>").login ("logout", {auto: true});
              break;
          }
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

          var tmp = document.querySelector (".modal li.list-group-item.active");

          if (tmp)
            tmp.classList.remove ("active");

          break;
      }
    });

  // EVENT "click"
  document.body.addEventListener ("click", (e)=>
    {
      const el = e.target;

      // EVENT "click" on popup buttons
      if (el.matches (".modal .modal-footer .btn,.modal .modal-footer .btn *"))
      {
        const btn = (el.tagName=="BUTTON")?el:el.closest("button"),
              popup = btn.closest (".modal"),
              $popup = $(popup),
              closePopup = !!!popup.dataset.noclosure,
              $postit = S.getCurrent ("postit");

        e.stopImmediatePropagation ();
  
        popup.removeAttribute ("data-noclosure");
  
        if (btn.classList.contains ("btn-primary"))
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
      }
      // EVENT "click" on main menu and list items
      else if (el.matches (".nav-link:not(.dropdown-toggle),"+
                           ".dropdown-item"))
      {
        H.closeMainMenu ();
      }
    });

});
