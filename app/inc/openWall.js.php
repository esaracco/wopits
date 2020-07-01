<?php
  require_once (__DIR__.'/../class/Wpt_jQueryPlugins.php');
  $Plugin = new Wpt_jQueryPlugins ('openWall');
  echo $Plugin->getHeader ();
?>

/////////////////////////// PUBLIC METHODS ////////////////////////////

  Plugin.prototype =
  {
    // METHOD init ()
    init: function (args)
    {
      const plugin = this,
            $openWall = plugin.element;

      $openWall.find('input').on("keyup", function (e)
        {
          plugin.search ($(this).val().trim ())
        });

      // EVENT CLICK on open wall popup
      $(document).on("click", "#openWallPopup .modal-body li", function(e)
      {
        $("<div/>").wpt_wall ("open", $(this)[0].dataset.id);
  
        $openWall.modal ("hide");
      });
    },

    // METHOD reset ()
    reset: function ()
    {
      this.element.find("input").val ("");
    },

    // METHOD search ()
    search: function (str)
    {
      const openedWalls = wpt_userData.settings.openedWalls,
            userId = wpt_userData.id,
            walls = [];

      wpt_userData.walls.forEach ((wall) =>
      {
        const re = new RegExp (wpt_quoteRegex(str), 'ig');

        if (openedWalls.indexOf(String(wall.id)) == -1 && (
              wall.name.match (re) ||
              (userId != wall.ownerid && wall.ownername.match (re))))
          walls.push (wall);
      });

      this.displayWalls (walls);
    },

    displayWalls: function (walls)
    {
      const $openWall = this.element;
      let body = "";

      walls = walls || wpt_userData.walls;

      $openWall.find(".input-group").hide ();

      if (!wpt_userData.walls.length)
        body = "<?=_("No walls available")?>";
      else
      { 
        $openWall.find(".input-group").show ();

        if (walls.length)
        {
          let dt = '';

          walls.forEach ((item) =>
          { 
            if (!$('[data-id="wall-'+item.id+'"]').length)
            {
              const owner = (item.ownerid != wpt_userData.id) ?
                `<div class="item-infos"><span class="ownername"><em><?=_("created by")?></em> ${item.ownername}</span></div>`:'';
              let dt1 = wpt_getUserDate (item.creationdate);

              if (dt1 != dt)
              {
                dt = dt1;
                body += `<li href="" class="list-group-item title">${dt1}</li>`;
              }
  
              body += `<li href="#" data-id="${item.id}" class="list-group-item list-group-item-action"> ${wpt_getAccessIcon(item.access)} ${item.name}${owner}</li>`;  
            }
          });
  
          if (!body)
          {
            $openWall.find(".input-group").hide ();

            body = "<i><?=_("All available walls are opened.")?></i>";
          }
        }
        else
          body = "<span class='text-center'><?=_("No result")?></span>";
      }

      $openWall.find (".modal-body .list-group").html (body);
    }
  };

  /////////////////////////// AT LOAD INIT //////////////////////////////

  $(function ()
    {
      const $plugin = $("#openWallPopup");

      if ($plugin.length)
        $plugin.wpt_openWall ();
    });

<?php echo $Plugin->getFooter ()?>
