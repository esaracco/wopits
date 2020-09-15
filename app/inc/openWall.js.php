<?php

  require_once (__DIR__.'/../prepend.php');

  $Plugin = new Wopits\jQueryPlugin ('openWall');
  echo $Plugin->getHeader ();

?>

/////////////////////////// PUBLIC METHODS ////////////////////////////

  Plugin.prototype =
  {
    // METHOD init ()
    init (args)
    {
      const plugin = this,
            $openWall = plugin.element;

      $openWall.find('input').on("keyup", function (e)
        {
          plugin.search (this.value.trim ())
        });

      $openWall
        .on("keypress", function (e)
        {
          if (e.which == 13 &&
              $openWall.find(".list-group-item-action").length == 1)
            $openWall.find(".list-group-item-action").click ();
        });

      // EVENT CLICK on Open button.
      $openWall.find(".btn-primary")
        .on("click", function ()
        {
          plugin.getChecked().forEach (
            (id) => $("<div/>").wall ("open", {wallId: id}));

          $openWall.modal ("hide");
        });

      // EVENT CLICK on open wall popup
      $(document).on("click", "#openWallPopup .modal-body li", function(e)
      {
        const tag = e.target.tagName;

        if (e.target.tagName == "INPUT")
        {
          if (plugin.getChecked().length)
            $openWall.find(".btn-primary").show ();
          else
            $openWall.find(".btn-primary").hide ();
        }
        else if (tag != "LABEL")
        {
          $("<div/>").wall ("open", {wallId: this.dataset.id});
          $openWall.modal ("hide");
        }
      });
    },

    // METHOD getChecked ()
    getChecked ()
    {
      let checked = [];

      this.element[0].querySelectorAll("input:checked").forEach (
        (el) => checked.push (el.id.substring(1)));

      return checked;
    },

    // METHOD reset ()
    reset ()
    {
      const $openWall = this.element;

      $openWall.find("input").val ("");
      $openWall.find("input:checked").prop ("checked", false);
    },

    // METHOD search ()
    search (str)
    {
      const openedWalls = wpt_userData.settings.openedWalls,
            userId = wpt_userData.id,
            walls = [];

      wpt_userData.walls.forEach ((wall) =>
      {
        const re = new RegExp (H.quoteRegex(str), 'ig');

        if (openedWalls.indexOf(String(wall.id)) == -1 && (
              wall.name.match (re) ||
              (userId != wall.ownerid && wall.ownername.match (re))))
          walls.push (wall);
      });

      this.displayWalls (walls);
    },

    // METHOD displayWalls ()
    displayWalls (walls)
    {
      const $openWall = this.element,
            checked = this.getChecked ();
      let body = "";

      walls = walls||wpt_userData.walls;

      $openWall.find(".input-group").hide ();
      $openWall.find(".btn-primary").hide ();

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
              let dt1 = H.getUserDate (item.creationdate);

              if (dt1 != dt)
              {
                dt = dt1;
                body += `<li class="list-group-item title">${dt1}</li>`;
              }
  
              body += `<li data-id="${item.id}" class="list-group-item list-group-item-action"><div class="custom-control custom-checkbox"><input type="checkbox" class="custom-control-input" id="_${item.id}"><label class="custom-control-label" for="_${item.id}"></label></div> ${H.getAccessIcon(item.access)} ${item.name}${owner}</li>`;
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

      checked.forEach ((id) =>
        {
          const el = document.getElementById ("_"+id);

          if (el)
            el.checked = true;
        });

      if (this.getChecked().length)
        $openWall.find(".btn-primary").show ();
    }
  };

  /////////////////////////// AT LOAD INIT //////////////////////////////

  $(function ()
    {
      const $plugin = $("#openWallPopup");

      if ($plugin.length)
        $plugin.openWall ();
    });

<?php echo $Plugin->getFooter ()?>
