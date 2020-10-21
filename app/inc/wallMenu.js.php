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
            wallPlugin = this.settings.wallPlugin;

      //$menu.append (`<li data-action="list-mode" data-toggle="tooltip" title="<?=_("Switch to list mode")?>"><i class="fa-fw fas fa-sticky-note fa-lg"></i></li><li data-action="postit-mode" data-toggle="tooltip" title="<?=_("Switch to sticky notes mode")?>"><i class="fa-fw fas fa-tasks fa-lg set"></i></li><li data-action="show-headers" data-toggle="tooltip" title="<?=_("Show headers")?>"><i class="fa-fw far fa-square fa-lg set"></i></li><li data-action="hide-headers" data-toggle="tooltip" title="<?=_("Hide headers")?>"><i class="fa-fw fas fa-h-square fa-lg"></i></li><li data-action="unblock-externalref" data-toggle="tooltip" title="<?=_("Show external contents")?>"><i class="fa-fw fas fa-umbrella fa-lg"></i></li><li data-action="block-externalref" data-toggle="tooltip" title="<?=_("Block external contents")?>"><i class="fa-fw fas fa-link fa-lg set"></i></li><li data-action="show-users" class="usersviewcounts dyn" data-toggle="tooltip" title="<?=_("Users viewing this wall")?>"><i class="fas fa-user-friends fa-fw fa-lg"></i> <span class="wpt-badge"></span></li><li class="toolbox chatroom"></li>`);
      $menu.append (`<li data-action="list-mode" data-toggle="tooltip" title="<?=_("Switch to list mode")?>"><i class="fa-fw fas fa-sticky-note fa-lg"></i></li><li data-action="postit-mode" data-toggle="tooltip" title="<?=_("Switch to sticky notes mode")?>"><i class="fa-fw fas fa-tasks fa-lg set"></i></li><li data-action="unblock-externalref" data-toggle="tooltip" title="<?=_("Show external contents")?>"><i class="fa-fw fas fa-umbrella fa-lg"></i></li><li data-action="block-externalref" data-toggle="tooltip" title="<?=_("Block external contents")?>"><i class="fa-fw fas fa-link fa-lg set"></i></li><li data-action="show-users" class="usersviewcounts dyn" data-toggle="tooltip" title="<?=_("Users viewing this wall")?>"><i class="fas fa-user-friends fa-fw fa-lg"></i> <span class="wpt-badge"></span></li><li class="toolbox chatroom"></li>`);

      $menu.draggable ({
        //FIXME "distance" is deprecated -> is there any alternative?
        distance: 10,
        cursor: "move",
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
          case "postit-mode":
            wallPlugin.setPostitsDisplayMode (action);

            H.displayMsg ({
              type: "info",
              msg: "<?=_("Your notes now appears as sticky notes")?>"
            });
            break;

          case "list-mode":
            wallPlugin.setPostitsDisplayMode (action);

            H.displayMsg ({
              type: "info",
              msg: "<?=_("Your notes now appears as lists")?>"
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
