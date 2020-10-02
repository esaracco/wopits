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

      $openWall.find('input:eq(0)').on("keyup", function (e)
        {
          plugin.search (this.value.trim ())
        });

      $openWall
        .on("keypress", function (e)
        {
          if (e.which == 13 &&
              $openWall.find(".list-group-item-action:visible").length == 1)
            $openWall.find(".list-group-item-action:visible").click ();
        });

      // EVENT CLICK on Open button.
      $openWall.find(".btn-primary")
        .on("click", function ()
        {
          plugin.getChecked().forEach (
            (id) => $("<div/>").wall ("open", {wallId: id}));
        });

      // EVENT CLICK on filters
      $openWall.find(".ow-filters input")
        .on("click", function (e, auto)
        {
          switch (e.target.id)
          {
            case "ow-all":
              $openWall.find(".list-group li.first,.list-group li.last")
                .removeClass("first last");

              if (!auto)
                plugin.displayWalls (null, false);
              break;

            case "ow-recent":
              const recentWalls = wpt_userData.settings.recentWalls||[],
                    walls = [];

              recentWalls.forEach ((wallId) =>
                  wpt_userData.walls.forEach ((wall) =>
                    (wall.id == wallId) &&  walls.push (wall)));

              if (!auto)
                plugin.displayWalls (walls, false);

              $openWall.find(".list-group li.title").hide ();
              $openWall.find(".list-group li:visible")
                .first().addClass("first");
              break;

            case "ow-shared":
              if (!auto)
                plugin.displayWalls (null, false);

              $openWall[0].querySelectorAll(".list-group li").forEach ((li)=>
                {
                  if (li.dataset.shared !== undefined)
                  {
                    li.style.display = "block";
                  }
                  else
                    li.style.display = "none";
                });

              $openWall.find(".list-group li:visible")
                .first().addClass("first");
              $openWall.find(".list-group li:visible")
                .last().addClass("last");
              break;
          }

          plugin.controlOpenButton ();
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

    // METHOD controlOpenButton ()
    controlOpenButton ()
    {
      this.element[0].querySelector (".btn-primary").style.display =
        this.getChecked().length ? "block" : "none";
    },

    // METHOD getChecked ()
    getChecked ()
    {
      let checked = [];

      this.element.find(".list-group input:checked").each (function ()
        {
          if ($(this).is(":visible"))
            checked.push (this.id.substring(1));
        });

      return checked;
    },

    // METHOD reset ()
    reset ()
    {
      const $openWall = this.element;

      $openWall[0].dataset.noclosure = 1;

      $openWall.find("#ow-all").prop ("checked", true);
      $openWall.find("input[type='text']").val ("");
      $openWall.find(".list-group input:checked").prop ("checked", false);
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
    displayWalls (walls, recurse = true)
    {
      const $openWall = this.element,
            checked = this.getChecked ();
      let body = "";

      walls = walls||wpt_userData.walls;

      $openWall.find(".ow-filters,.input-group,.btn-primary").hide ();

      if (!wpt_userData.walls.length)
        body = "<?=_("No walls available")?>";
      else
      { 
        $openWall.find(".ow-filters,.input-group").show ();

        if (walls.length)
        {
          let dt = '';

          walls.forEach ((item) =>
          { 
            if (!$('[data-id="wall-'+item.id+'"]').length)
            {
              const shared = (item.ownerid != wpt_userData.id),
                    owner = shared ? `<div class="item-infos"><span class="ownername"><em><?=_("created by")?></em> ${item.ownername}</span></div>`:'';
              let dt1 = H.getUserDate (item.creationdate);

              if (dt1 != dt)
              {
                dt = dt1;
                body += `<li class="list-group-item title">${dt1}</li>`;
              }
  
              body += `<li data-id="${item.id}" ${shared?"data-shared":""} class="list-group-item list-group-item-action"><div class="custom-control custom-checkbox"><input type="checkbox" class="custom-control-input" id="_${item.id}"><label class="custom-control-label" for="_${item.id}"></label></div> ${H.getAccessIcon(item.access)} ${item.name}${owner}</li>`;
            }
          });
  
          if (!body && $openWall.find("#ow-all")[0].checked)
          {
            $openWall.find(".ow-filters,.input-group,.btn-primary").hide ();

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

      if (recurse)
        $openWall.find(".ow-filters input:checked").trigger ("click", true);
      else
        $openWall.find("input[type='text']").val ("");

      this.controlOpenButton ();
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
