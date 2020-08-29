<?php

  require_once (__DIR__.'/../prepend.php');

  $Plugin = new Wopits\jQueryPlugin ('wallProperties');
  echo $Plugin->getHeader ();

?>

/////////////////////////// PUBLIC METHODS ////////////////////////////

  Plugin.prototype =
  {
    // METHOD init ()
    init (args)
    {
      const plugin = this;

      plugin.element.find(".cancel-sharing")
        .on ("click", function ()
        {
          H.openConfirmPopover ({
            item: $(this),
            title: `<i class="fas fa-minus-circle fa-fw"></i> <?=_("Cancel sharing")?>`,
            content: "<?=_("You will lose your access to the wall.<br>Cancel anyway?")?>",
            cb_close: () =>
              setTimeout(()=> S.unset ("cancel-sharing-data"), 500),
            cb_ok: () => plugin.removeGroupUser (S.get ("cancel-sharing-data"))
          });
        });
    },

    // METHOD removeGroupUser ()
    removeGroupUser (args)
    {
      const wallId = args.wall.wall ("getId"),
            groupIds = args.groups.join (",");

      args.wall.wall ("close");

      H.request_ws (
        "DELETE",
        "wall/"+wallId+"/group/"+groupIds+"/removeMe");
    },

    // METHOD open ()
    open (args)
    {
      const $wall = args.wall;

      H.request_ajax (
        "GET",
        "wall/"+$wall.wall("getId")+"/infos",
        null,
        // success cb
        (d) =>
        {
          const $popup = this.element,
                isCreator = (d.user_id == wpt_userData.id);

          H.cleanPopupDataAttr ($popup);

          $popup.find(".description").show ();

          $popup.find(".creator").text (d.user_fullname);
          $popup.find(".creationdate").text (
            H.getUserDate (d.creationdate, null, "Y-MM-DD HH:mm"));

          $popup.find(".size").hide ();

          if (H.checkAccess ("<?=WPT_WRIGHTS_ADMIN?>"))
          {
            const $input = $popup.find(".name input");

            $popup.find(".btn-primary").show ();
            $popup.find(".ro").hide ();
            $popup.find(".adm").show ();

            $input.val(d.name);
            $popup.find(".description textarea").val(d.description);

            if (args && args.renaming)
              $input.attr ("autofocus", "autofocus");
            else
              $input.removeAttr ("autofocus");

            if ($wall[0].dataset.rows == 1 && $wall[0].dataset.cols == 1)
            {
              const $div = $popup.find(".wall-size"),
                    $cell = $wall.find ("td");

              $popup.find("[name='wall-width']")
                .val (Math.floor ($cell.outerWidth ()));
              $popup.find("[name='wall-height']")
                .val (Math.floor ($cell.outerHeight ()));
              $popup.find(".size").show ();
            }
          }
          else
          {
            $popup.find(".btn-primary").hide ();
            $popup.find(".adm").hide ();
            $popup.find(".ro").show ();

            $popup.find(".name .ro").html(H.nl2br (d.name));
            if (d.description)
              $popup.find(".description .ro").html(H.nl2br (d.description));
            else
              $popup.find(".description").hide ();
          }

          if (isCreator)
            $popup.find(".cancel-sharing").hide ();
          else
          {
            $popup.find(".cancel-sharing").show ();

            S.set ("cancel-sharing-data", {
              wall: args.wall,
              groups: d.groups
            });
          }

          $popup[0].dataset.noclosure = true;
          H.openModal ($popup);
        }
      );
    }

  };

  /////////////////////////// AT LOAD INIT //////////////////////////////

  $(function ()
    {
      const $plugin = $("#wallPropertiesPopup");

      if ($plugin.length)
        $plugin.wallProperties ();
    });

<?php echo $Plugin->getFooter ()?>
