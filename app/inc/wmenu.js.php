<?php
/**
  Javascript plugin - Wall menu

  Scope: Wall
  Elements: .wall-menu
  Description: Manage wall's floating menu
*/

  require_once (__DIR__.'/../prepend.php');

  use Wopits\jQueryPlugin;

  $Plugin = new jQueryPlugin ('wmenu');
  echo $Plugin->getHeader ();

?>

/////////////////////////// PUBLIC METHODS ////////////////////////////

  // Inherit from Wpt_toolbox
  Plugin.prototype = Object.create(Wpt_toolbox.prototype);
  Object.assign (Plugin.prototype,
  {
    // METHOD init ()
    init ()
    {
      const plugin = this,
            $menu = plugin.element,
            wallPlugin = this.settings.wallPlugin,
            adminAccess =
              H.checkAccess ("<?=WPT_WRIGHTS_ADMIN?>", this.settings.access);

      $menu[0].classList.add ("toolbox");

      $menu.append (`
<li data-action="list-mode" title="<?=_("Switch to stack mode")?>"><i class="fa-fw fas fa-sticky-note fa-lg set"></i></li>
<li data-action="postit-mode" title="<?=_("Switch to sticky notes mode")?>"><i class="fa-fw fas fa-tasks fa-lg set"></i></li><li class="divider"></li><li data-action="show-headers" title="<?=_("Show wall headers")?>"><i class="fa-fw fas fa-h-square fa-lg notset"></i></li><li data-action="hide-headers" title="<?=_("Hide wall headers")?>"><i class="fa-fw fas fa-h-square fa-lg set"></i></li><li data-action="unblock-externalref" title="<?=_("Show external contents")?>"><i class="fa-fw fas fa-link fa-lg notset"></i></li><li data-action="block-externalref" title="<?=_("Block external contents")?>"><i class="fa-fw fas fa-link fa-lg set"></i></li><li class="divider"></li>${adminAccess?`<li data-action="add-col" title="<?=_("Add column")?>"><i class="fa-fw fas fa-grip-lines-vertical"></i></li><li data-action="add-row" title="<?=_("Add row")?>"><i class="fa-fw fas fa-grip-lines"></i></li>`:""}<li data-action="search" title="<?=_("Search...")?>"><i class="fa-fw fas fa-search"></i></li><li data-action="share" title="<?=_("Share...")?>"><i class="fa-fw fas fa-share"></i></li><li class="divider hidden"></li><li data-action="show-users" class="usersviewcounts" title="<?=_("Users viewing this wall")?>"><i class="fas fa-user-friends fa-fw fa-lg"></i> <span class="wpt-badge"></span></li>`);

      $menu.draggable ({
        distance: 10,
        cursor: "move",
        drag: (e, ui)=> plugin.fixDragPosition (ui),
        stop: ()=> S.set ("dragging", true, 500)
      });

      // EVENT "click" on wall menu
      $menu[0].addEventListener ("click", (e)=>
      {
        const el = e.target;

        if (el.tagName == "I" || el.tagName == "SPAN")
        {
          if (H.disabledEvent ())
            return false;
  
          const li = el.closest("li"),
                action = li.dataset.action;
  
          switch (action)
          {
            case "share":
              H.loadPopup ("swall", {
                open: false,
                cb: ($p)=> $p.swall ("open")
              });
              break;
  
            case "add-col":
              wallPlugin.createColRow ("col");
              break;
  
            case "add-row":
              wallPlugin.createColRow ("row");
              break;
  
            case "search":
              H.loadPopup ("psearch", {
                open: false,
                cb: ($p)=> $p.psearch ("open")
              });
              break
  
            case "postit-mode":
              wallPlugin.setPostitsDisplayMode (action);
              break;
  
            case "list-mode":
              S.getCurrent("mmenu").mmenu ("close");
              wallPlugin.setPostitsDisplayMode (action);
              break;
  
            case "unblock-externalref":
              wallPlugin.displayExternalRef (1, true);
  
              H.displayMsg ({
                title: `<?=_("Wall")?>`,
                type: "info",
                msg: `<?=_("External contents are no longer filtered")?>`
              });
              break;
  
            case "block-externalref":
              wallPlugin.displayExternalRef (0, true);
  
              H.displayMsg ({
                title: `<?=_("Wall")?>`,
                type: "info",
                msg: `<?=_("External contents are now filtered")?>`
              });
              break;
  
            case "show-headers":
              wallPlugin.displayHeaders (1, true);
              break;
  
            case "hide-headers":
              wallPlugin.displayHeaders (0, true);
              break;
  
            case "show-users":
              wallPlugin.displayWallUsersview ();
              break;
          }
        }
      });
    },
  });

<?php echo $Plugin->getFooter ()?>
