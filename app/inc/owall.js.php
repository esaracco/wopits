<?php
/**
  Javascript plugin - Walls opener

  Scope: Global
  Elements: #owallPopup
  Description: Walls opening popup
*/

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
            $owall = plugin.element,
            owall = $owall[0],
            input = owall.querySelector (`input[type="text"]`);

      // EVENT "keyup" on input
     input.addEventListener ("keyup", (e)=>
        plugin.search (e.target.value.trim ()));

      // EVENT "kepress" on input
      owall.addEventListener ("keypress", (e)=>
        {
          if (e.which == 13 &&
              $owall.find(".list-group-item[data-id]:visible").length == 1)
            $owall.find(".list-group-item[data-id]:visible").click ();
        });

      // EVENT "click" on "clear input" button
      owall.querySelector(".clear-input").addEventListener ("click", (e)=>
        {
          input.value = "";
          owall.querySelectorAll(".ow-filters input:checked").forEach (el=>
            el.dispatchEvent(new CustomEvent("click")));
        });

      // EVENT "click" on "clear history" button
      owall.querySelector(".btn-clear").addEventListener ("click", (e)=>
        {
          $("#settingsPopup").settings ("set", {recentWalls: []});
          document.getElementById("ow-all").click ();
          plugin.controlFiltersButtons ();
        });

      // EVENT "click" on "open" button
      owall.querySelector(".btn-primary").addEventListener ("click", (e)=>
        {
          const checked = plugin.getChecked (),
                len = checked.length,
                $el = $("<div/>");

          checked.forEach ((id, i)=>
            $el.wall ("open", {
              lastWall: (i == len - 1) ? len : null,
              wallId: id
            }));
        });

      // EVENT "click" on filters
      const _inputClickEvent = function (e)
        {
          const auto = e.detail?e.detail.auto:false;
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
                  wpt_userData.walls.list.forEach ((wall) =>
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
              `<span class='text-center'><?=_("No result")?></span>`);

          plugin.controlOpenButton ();

          input.focus ();
        };
      owall.querySelectorAll(".ow-filters input").forEach (el=>
        el.addEventListener ("click", _inputClickEvent));

      // EVENT "click"
      owall.addEventListener ("click", (e)=>
        {
          const el = e.target;

          // EVENT "click" on "open wall" popup
          if (el.matches ("li,li *"))
          {
            const tag = el.tagName;

            if (tag == "INPUT")
            {
              owall.querySelector(".btn-primary").style.display =
                (plugin.getChecked().length) ? "block" : "none";
            }
            else if (tag != "LABEL")
            {
              $("<div/>").wall ("open", {
                lastWall: 1,
                wallId: (tag=="LI"?el:el.closest("li")).dataset.id
              });

              $owall.modal ("hide");
            }
          }
        });
    },

    // METHOD controlFiltersButtons ()
    controlFiltersButtons ()
    {
      const $owall = this.element;
      let i, count = 0;

      $owall.find("#ow-shared,#ow-recent").parent().hide ();

      var tmp = $owall[0].querySelectorAll (".list-group li[data-shared]");
      if (tmp.length)
      {
        let found;

        i = 0;
        while (
          i < tmp.length &&
          !(found =
              document.querySelector(`[data-id="wall-${tmp[i].dataset.id}"]`)))
            i++;

        if (!found)
        {
          ++count;
          $owall.find("#ow-shared").parent().show ();
        }
      }

      var tmp = wpt_userData.settings.recentWalls||[];
      if (tmp.length)
      {
        i = 0;
        while (
          i < tmp.length &&
          document.querySelector(`[data-id="wall-${tmp[i]}"]`))
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
      const checked = [];

      this.element[0].querySelectorAll(".list-group input:checked").forEach (
        el=> checked.push (el.id.substring(1)));

      return checked;
    },

    // METHOD reset ()
    reset ()
    {
      const owall = this.element[0];

      owall.dataset.noclosure = 1;

      document.getElementById("ow-all").checked = true;
      owall.querySelector(`input[type="text"]`).value = "";
      owall.querySelectorAll(".list-group input:checked").forEach (
        el=> el.checked = false);
    },

    // METHOD search ()
    search (str)
    {
      const openedWalls = wpt_userData.settings.openedWalls,
            userId = wpt_userData.id,
            walls = [];

      wpt_userData.walls.list.forEach (el=>
      {
        const re = new RegExp (H.quoteRegex(str), 'ig');

        if (!$("<div/>").wall("isOpened", el.id) && (
              el.name.match (re) ||
              (userId != el.ownerid && el.ownername.match (re))))
          walls.push (el);
      });

      this.displayWalls (walls);
    },

    // METHOD displayWalls ()
    displayWalls (walls, recurse = true)
    {
      const $owall = this.element,
            owall = $owall[0],
            checked = this.getChecked ();
      let body = "";

      walls = walls||wpt_userData.walls.list;

      owall.querySelectorAll(".ow-filters,.input-group,.btn-primary")
        .forEach (el=> el.style.display = "none");

      if (!wpt_userData.walls.list.length)
        body = `<?=_("No wall available.")?>`;
      else
      { 
        $owall.find(".ow-filters,.input-group").show ();

        if (walls.length)
        {
          let dt = "";

          walls.forEach ((item) =>
          { 
            if (!document.querySelector (`[data-id="wall-${item.id}"]`))
            {
              const shared = (item.ownerid != wpt_userData.id),
                    owner = shared ? `<div class="item-infos"><span class="ownername"><em><?=_("created by")?></em> ${item.ownername}</span></div>`:"";
              let dt1 = H.getUserDate (item.creationdate);

              if (dt1 != dt)
              {
                dt = dt1;
                body += `<li class="list-group-item title">${dt1}</li>`;
              }
  
              body += `<li data-id="${item.id}" ${shared?"data-shared":""} class="list-group-item"><div class="form-check form-check-inline wpt-checkbox"><input type="checkbox" class="form-check-input" id="_${item.id}"><label class="form-check-label" for="_${item.id}"></label></div> ${H.getAccessIcon(item.access)} ${item.name}${owner}</li>`;
            }
          });
  
          if (!body && document.getElementById("ow-all").checked)
          {
            owall.querySelectorAll(".ow-filters,.input-group,.btn-primary")
              .forEach (el=> el.style.display = "none");

            body = `<i><?=_("All available walls are opened.")?></i>`;
          }
        }
        else
          body = `<span class='text-center'><?=_("No result")?></span>`;
      }

      owall.querySelector(".modal-body .list-group").innerHTML = body;

      checked.forEach ((id) =>
        {
          const el = document.getElementById (`_${id}`);

          if (el)
            el.checked = true;
        });

      if (recurse)
        owall.querySelectorAll(".ow-filters input:checked").forEach (el=>
          el.dispatchEvent(new CustomEvent("click", {
            bubbles: true,
            detail: {auto: true}
          })));
      else
        owall.querySelector(`input[type="text"]`).value = "";

      this.controlOpenButton ();
    }
  };

<?php echo $Plugin->getFooter ()?>
