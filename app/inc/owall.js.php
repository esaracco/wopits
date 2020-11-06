<?php

  require_once (__DIR__.'/../prepend.php');

  $Plugin = new Wopits\jQueryPlugin ('owall');
  echo $Plugin->getHeader ();

?>

/////////////////////////// PUBLIC METHODS ////////////////////////////

  Plugin.prototype =
  {
    // METHOD init ()
    init (args)
    {
      const plugin = this,
            $owall = plugin.element;

      $owall.find('input:eq(0)')
        // EVENT keyup on input
        .on("keyup", function (e)
        {
          plugin.search (this.value.trim ())
        });

      $owall
        // EVENT kepress on input
        .on("keypress", function (e)
        {
          if (e.which == 13 &&
              $owall.find(".list-group-item-action:visible").length == 1)
            $owall.find(".list-group-item-action:visible").click ();
        });

      $owall.find(".clear-input")
        // EVENT click on input clear button
        .on("click", function ()
        {
          $owall.find("input[type='text']").val ("");
          $owall.find(".ow-filters input:checked").trigger ("click", false);
        });

      $owall.find(".btn-clear")
        // EVENT CLICK on Clear history button
        .on("click", function ()
        {
          $owall.find(".btn-clear").tooltip ("hide");
          $("#settingsPopup").settings ("set", {recentWalls: []});
          $owall.find("#ow-all").click ();
          plugin.controlFiltersButtons ();
        });

      $owall.find(".btn-primary")
        // EVENT CLICK on Open button.
        .on("click", function ()
        {
          plugin.getChecked().forEach (
            (id) => $("<div/>").wall ("open", {wallId: id}));
        });

      $owall.find(".ow-filters input")
        // EVENT CLICK on filters
        .on("click", function (e, auto)
        {
          let content = false;

          $owall.find(".btn-clear").hide ();

          switch (e.target.id)
          {
            case "ow-all":
              $owall.find(".list-group li.first,.list-group li.last")
                .removeClass ("first last");

              if (!auto)
                plugin.displayWalls (null, false);

              content = true;
              break;

            case "ow-recent":
              const recentWalls = wpt_userData.settings.recentWalls||[],
                    walls = [];

              $owall.find(".btn-clear").show ();

              recentWalls.forEach ((wallId) =>
                  wpt_userData.walls.forEach ((wall) =>
                    (wall.id == wallId) && walls.push (wall)));

              if (!auto)
                plugin.displayWalls (walls, false);

              $owall.find(".list-group li.title").hide ();
              $owall.find(".list-group li:visible").first().addClass ("first");

              content = walls.length;
              break;

            case "ow-shared":
              if (!auto)
                plugin.displayWalls (null, false);

              $owall[0].querySelectorAll(".list-group li").forEach ((li)=>
                {
                  if (li.dataset.shared !== undefined)
                  {
                    content = true;
                    li.style.display = "block";
                  }
                  else
                    li.style.display = "none";
                });

              $owall.find(".list-group li:visible").first().addClass ("first");
              $owall.find(".list-group li:visible").last().addClass ("last");
              break;
          }

          if (!content)
            $owall.find (".modal-body .list-group").html (
              "<span class='text-center'><?=_("No result")?></span>");

          plugin.controlOpenButton ();

          $owall.find("input[type='text']").focus ();
        });

      // EVENT CLICK on open wall popup
      $(document).on("click", "#owallPopup .modal-body li", function(e)
      {
        const tag = e.target.tagName;

        if (e.target.tagName == "INPUT")
        {
          if (plugin.getChecked().length)
            $owall.find(".btn-primary").show ();
          else
            $owall.find(".btn-primary").hide ();
        }
        else if (tag != "LABEL")
        {
          $("<div/>").wall ("open", {wallId: this.dataset.id});
          $owall.modal ("hide");
        }
      });
    },

    // METHOD controlFiltersButtons ()
    controlFiltersButtons ()
    {
      const $owall = this.element;
      let i, tmp, count = 0;

      $owall.find("#ow-shared,#ow-recent").parent().hide ();

      tmp = $owall[0].querySelectorAll (".list-group li[data-shared]");
      if (tmp.length)
      {
        let found;

        i = 0;
        while (
          i < tmp.length &&
          !(found =
              document.querySelector('[data-id="wall-'+tmp[i].dataset.id+'"]')))
            i++;

        if (!found)
        {
          ++count;
          $owall.find("#ow-shared").parent().show ();
        }
      }

      tmp = wpt_userData.settings.recentWalls||[];
      if (tmp.length)
      {
        i = 0;
        while (
          i < tmp.length &&
          document.querySelector('[data-id="wall-'+tmp[i]+'"]'))
            i++;

        if (i != tmp.length)
        {
          ++count;
          $owall.find("#ow-recent").parent().show ();
        }
      }

      if (!count)
        $owall.find(".ow-filters").hide ();
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
      const $owall = this.element;

      $owall[0].dataset.noclosure = 1;

      $owall.find("#ow-all").prop ("checked", true);
      $owall.find("input[type='text']").val ("");
      $owall.find(".list-group input:checked").prop ("checked", false);
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
      const $owall = this.element,
            checked = this.getChecked ();
      let body = "";

      walls = walls||wpt_userData.walls;

      $owall.find(".ow-filters,.input-group,.btn-primary").hide ();

      if (!wpt_userData.walls.length)
        body = "<?=_("No walls available")?>";
      else
      { 
        $owall.find(".ow-filters,.input-group").show ();

        if (walls.length)
        {
          let dt = '';

          walls.forEach ((item) =>
          { 
            if (!document.querySelector ('[data-id="wall-'+item.id+'"]'))
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
  
          if (!body && $owall.find("#ow-all")[0].checked)
          {
            $owall.find(".ow-filters,.input-group,.btn-primary").hide ();

            body = "<i><?=_("All available walls are opened.")?></i>";
          }
        }
        else
          body = "<span class='text-center'><?=_("No result")?></span>";
      }

      $owall.find (".modal-body .list-group").html (body);

      checked.forEach ((id) =>
        {
          const el = document.getElementById ("_"+id);

          if (el)
            el.checked = true;
        });

      if (recurse)
        $owall.find(".ow-filters input:checked").trigger ("click", true);
      else
        $owall.find("input[type='text']").val ("");

      this.controlOpenButton ();
    }
  };

<?php echo $Plugin->getFooter ()?>
