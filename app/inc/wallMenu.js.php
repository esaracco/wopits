<?php

  require_once (__DIR__.'/../prepend.php');

  use Wopits\jQueryPlugin;

  $Plugin = new jQueryPlugin ('wallMenu');
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

      //$menu.append (`<li data-action="list-mode" data-toggle="tooltip" title="<?=_("Switch to list mode")?>"><i class="fa-fw fas fa-sticky-note fa-lg"></i></li><li data-action="postit-mode" data-toggle="tooltip" title="<?=_("Switch to sticky notes mode")?>"><i class="fa-fw fas fa-tasks fa-lg set"></i></li><li data-action="show-headers" data-toggle="tooltip" title="<?=_("Show headers")?>"><i class="fa-fw far fa-square fa-lg set"></i></li><li data-action="hide-headers" data-toggle="tooltip" title="<?=_("Hide headers")?>"><i class="fa-fw fas fa-h-square fa-lg"></i></li><li data-action="unblock-externalref" data-toggle="tooltip" title="<?=_("Show external contents")?>"><i class="fa-fw fas fa-umbrella fa-lg"></i></li><li data-action="block-externalref" data-toggle="tooltip" title="<?=_("Block external contents")?>"><i class="fa-fw fas fa-link fa-lg set"></i></li><li data-action="show-users" class="usersviewcounts" data-toggle="tooltip" title="<?=_("Users viewing this wall")?>"><i class="fas fa-user-friends fa-fw fa-lg"></i> <span class="wpt-badge"></span></li><li class="toolbox chatroom"></li>`);
      $menu.append (`<li data-action="list-mode" data-toggle="tooltip" title="<?=_("Switch to list mode")?>"><i class="fa-fw fas fa-sticky-note fa-lg set"></i></li><li data-action="postit-mode" data-toggle="tooltip" title="<?=_("Switch to sticky notes mode")?>"><i class="fa-fw fas fa-tasks fa-lg set"></i></li><li data-action="unblock-externalref" data-toggle="tooltip" title="<?=_("Show external contents")?>"><i class="fa-fw fas fa-umbrella fa-lg set"></i></li><li data-action="block-externalref" data-toggle="tooltip" title="<?=_("Block external contents")?>"><i class="fa-fw fas fa-link fa-lg set"></i></li><li class="divider"></li>${adminAccess?`<li data-action="add-col" data-toggle="tooltip" title="<?=_("Add column")?>"><i class="fa-fw fas fa-grip-lines-vertical"></i></li><li data-action="add-row" data-toggle="tooltip" title="<?=_("Add row")?>"><i class="fa-fw fas fa-grip-lines"></i></li>`:''}<li data-action="search" data-toggle="tooltip" title="<?=_("Search...")?>"><i class="fa-fw fas fa-search"></i></li><li data-action="share" data-toggle="tooltip" title="<?=_("Share...")?>"><i class="fa-fw fas fa-share"></i></li><li class="divider hidden"></li><li data-action="show-users" class="usersviewcounts" data-toggle="tooltip" title="<?=_("Users viewing this wall")?>"><i class="fas fa-user-friends fa-fw fa-lg"></i> <span class="wpt-badge"></span></li>`);

      $menu.draggable ({
        //FIXME "distance" is deprecated -> is there any alternative?
        distance: 10,
        cursor: "move",
        drag: (e, ui)=> plugin.fixDragPosition (ui),
        stop: ()=> S.set ("still-dragging", true, 500)
      });

      if (!$.support.touch)
        H.enableTooltips ($menu, {boundary:"viewport"});

      $menu.find("li").on("click", function ()
      {
        const action = this.dataset.action;

        if (S.get ("still-dragging"))
          return;

        switch (action)
        {
          case "share":
            H.loadPopup ("shareWall", {
              open: false,
              cb: ($p)=> $p.shareWall ("open")
            });
            break;

          case "add-col":
            wallPlugin.createColRow ("col");
            break;

          case "add-row":
            wallPlugin.createColRow ("row");
            break;

          case "search":
            H.loadPopup ("postitsSearch");
            break

          case "postit-mode":
            wallPlugin.setPostitsDisplayMode (action);

            H.displayMsg ({
              type: "info",
              msg: "<?=_("Notes now appears as sticky notes")?>"
            });
            break;

          case "list-mode":
            wallPlugin.setPostitsDisplayMode (action);

            H.displayMsg ({
              type: "info",
              msg: "<?=_("Notes now appears as lists")?>"
            });
            break;

          case "unblock-externalref":
            wallPlugin.displayExternalRef (1, true);

            H.displayMsg ({
              type: "info",
              msg: "<?=_("External contents are no longer filtered")?>"
            });
            break;

          case "block-externalref":
            wallPlugin.displayExternalRef (0, true);

            H.displayMsg ({
              type: "info",
              msg: "<?=_("External contents are now filtered")?>"
            });
            break;

          case "show-headers":
            wallPlugin.displayHeaders (1, true);

            H.displayMsg ({
              type: "info",
              msg: "<?=_("Wall headers are now visible")?>"
            });
            break;

          case "hide-headers":
            wallPlugin.displayHeaders (0, true);

            H.displayMsg ({
              type: "info",
              msg: "<?=_("Wall headers are now hidden")?>"
            });
            break;
        }
      });
    },
  });

<?php echo $Plugin->getFooter ()?>
