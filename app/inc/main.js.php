<?php

  require_once (__DIR__.'/../prepend.php');

  $Plugin = new Wopits\jQueryPlugin ('wall');
  echo $Plugin->getHeader ();

?>

  let _refreshing = false,
      _originalObject;

  /////////////////////////// PRIVATE METHODS ///////////////////////////

  // METHOD _getCellTemplate ()
  function _getCellTemplate (data)
  {
    return `<td scope="dzone" class="size-init" style="width:${data.width}px;height:${data.height}px" data-id="cell-${data.id}"></td>`;
  }

  // METHOD _getDirectURLData ()
  function _getDirectURLData ()
  {
    const m = location.href.match (/\?\/(a|s)\/(\d+)(\/(\d+))?$/);

    return m ? {type: m[1], wallId: m[2], postitId: m[4]||null} : null;
  }

  /////////////////////////// PUBLIC METHODS ////////////////////////////

  // Inherit from Wpt_accountForms
  Plugin.prototype = Object.create(Wpt_accountForms.prototype);
  Object.assign (Plugin.prototype,
  {
    // METHOD init ()
    init ()
    {
      const plugin = this,
            $wall = plugin.element,
            wall0 = $wall[0],
            settings = plugin.settings,
            wallId = settings.id,
            access = settings.access,
            writeAccess = H.checkAccess ("<?=WPT_WRIGHTS_RW?>", access),
            rows = [];

      settings.tabLink =
        $(".nav-tabs.walls").find ('a[href="#wall-'+settings.id+'"]');

      // Create plugs container
      settings.plugsContainer =
        $(`<div id="plugs-${wallId}" data-access="${access}"></div>`)
          .appendTo ("body");

      if (settings.restoring)
        wall0.dataset.restoring = 1;

      wall0.dataset.displaymode = settings.displaymode;

      plugin.setName (settings.name, true);

      // Prepare rows array for display
      for (let i = 0, iLen = settings.cells.length; i < iLen; i++)
      {
        const cell = settings.cells[i],
              rowIdx = cell.item_row;

        if (!rows[rowIdx])
          rows[rowIdx] = [];

        rows[rowIdx][cell.item_col] = cell;
      }

      if (settings.shared)
        plugin.setShared (true);

      $wall
        .hide()
        .css({
          width: (settings.width) ? settings.width : "",
          "background-color": (settings["background-color"]) ?
                                 settings["background-color"] : "auto"
        })
        .html ("<thead><tr><th>&nbsp;</th></tr></thead><tbody></tbody>");

      if (!$.support.touch)
        $wall.draggable({
          //FIXME "distance" is deprecated -> is there any alternative?
          distance: 10,
          cursor: "grab",
//          cancel: (writeAccess) ? null : "span,.title,.postit-edit",
          start: function ()
            {
              S.set ("wall-dragging", true);
              plugin.hidePostitsPlugs ();
            },
          stop: function ()
            {
              const $arrows = S.getCurrent ("arrows"),
                    $filters = S.getCurrent ("filters");

              S.set ("still-dragging", true, 500);

              // Fix arrows tool appearence
              if ($arrows.is (":visible"))
                $arrows.arrows ("update");

              if (!$filters || !$filters.hasClass ("plugs-hidden"))
                plugin.showPostitsPlugs ();

              S.unset ("wall-dragging");
            }
        })

      H.waitForDOMUpdate (() =>
        {
          // Create wall columns headers
          const hcols = settings.headers.cols;
          for (let i = 0, iLen = hcols.length; i < iLen; i++)
          {
            const header = hcols[i],
                  $th = $("<th/>");

            $wall.find("thead tr").append ($th);
            $th.header ({
              access: access,
              item_type: "col",
              id: header.id,
              wall: $wall,
              wallId: wallId,
              title: header.title,
              picture: header.picture
            });
          }

          const hrows = settings.headers.rows;
          for (let i = 0, iLen = rows.length; i < iLen; i++)
          {
            const row = rows[i];

            plugin.addRow (hrows[i], row);

            for (let j = 0, jLen = row.length; j < jLen; j++)
            {
              const cell = row[j],
                    $cell = $wall.find("td[data-id='cell-"+cell.id+"']");

              for (let k = 0, kLen = cell.postits.length; k < kLen; k++)
              {
                cell.postits[k]["access"] = access;
                $cell.cell ("addPostit", cell.postits[k], true);
              }
            }
          }

          $("#welcome").hide ();

          $wall.show ("fade");

          wall0.dataset.cols = hcols.length;
          wall0.dataset.rows = hrows.length;

          plugin.setName (settings.name);
          plugin.setDescription (settings.description);

          $(window).trigger ("resize");

          if (settings.restoring)
          {
            delete settings.restoring;
            wall0.removeAttribute ("data-restoring");
          }

          // Set wall users view count if needed
          const viewcount =
            WS.popResponse ("viewcount-wall-"+wallId);
          if (viewcount !== undefined)
            plugin.refreshUsersview (viewcount); 

          // If last wall to load.
          if (S.get ("last-wall"))
          {
            S.unset ("last-wall");

            // If we must save opened walls (because user have no longer the
            // rights to load a previously opened wall for example).
            if (S.get ("save-opened-walls") ||
                !(wpt_userData.settings.recentWalls||[]).length)
            {
              S.unset ("save-opened-walls");

              plugin.setActive ();

              // Save only when all walls has been loaded.
              const t = setInterval (()=>
                {
                  if (!$(".walls i.fa-cog").length)
                  {
                    $("#settingsPopup").settings ("saveOpenedWalls");
                    clearInterval (t);
                  }
                });
            }
          }

          H.waitForDOMUpdate (()=>
          {
            const __postInit = ()=>
              {
                // Refresh postits relationships
                plugin.refreshPostitsPlugs (settings.postits_plugs);
                // Apply display mode
                plugin.refreshCellsToggleDisplayMode ();
              };

            plugin.displayExternalRef ();

            // Display postit dealine alert or specific wall if needed.
            if (settings.fromDirectURL)
            {
              const postitId = settings.postitId;

              plugin.setActive ();

              H.waitForDOMUpdate (() =>
                {
                  if (postitId)
                  {
                    const $postit = $wall.find("[data-id=postit-"+postitId+"]");

                    if ($postit.length)
                      $postit.postit ("displayDeadlineAlert");
                    else
                      H.displayMsg ({type: "warning", msg: "<?=_("The sticky note has been deleted.")?>"});
                  }
                  else
                  {
                    plugin.displayShareAlert ();
                  }

                  H.waitForDOMUpdate (() => __postInit ());
                });
            }
            else
              __postInit ();
          });

        });
    },

    // METHOD displayShareAlert ()
    displayShareAlert ()
    {
      const walls = wpt_userData.walls.list;
      let owner;

      for (const k in walls)
        if (walls[k].id == this.settings.id)
        {
          owner = walls[k].ownername
          break;
        }

      H.openConfirmPopover ({
        type: "info",
        item: $(".walls a.active"),
        title: `<i class="fas fa-share fa-fw"></i> <?=_("Sharing")?>`,
        content: "<?=_("%s shared this wall with you!")?>".replace("%s", owner)
      });
    },

    // METHOD setActive ()
    setActive ()
    {
      S.reset ();

      this.settings.tabLink.click ();
      $("#wall-"+this.settings.id).addClass ("active");

      this.menu ({from: "wall", type: "have-wall"});
    },

    // METHOD getId ()
    getId ()
    {
      return this.settings.id;
    },

    menu (args)
    {
      const $wall = S.getCurrent ("wall"),
            $menu = $("#main-menu"),
            $menuNormal =
              $menu.find('.dropdown-menu li[data-action="zoom-normal"] a'),
            adminAccess = H.checkAccess ("<?=WPT_WRIGHTS_ADMIN?>");

      switch (args.from)
        {
          // WALL menu
          case "wall":
    
            if (!adminAccess)
            {
              $menu.find(
                '[data-action="delete"] a,'+
                '[data-action="share"] a,'+
                '[data-action="add-col"] a,'+
                '[data-action="add-row"] a').addClass ("disabled");
            }
    
            switch (args.type)
            {
              case "no-wall":
    
                $menu.find(
                  '[data-action="delete"] a,'+
                  '[data-action="share"] a,'+
                  '[data-action="add-col"] a,'+
                  '[data-action="add-row"] a,'+
                  '[data-action="close-walls"] a,'+
                  '[data-action="view-properties"] a,'+
                  '[data-action="clone"] a,'+
                  '[data-action="export"] a,'+
                  '[data-action="search"] a').addClass ("disabled");
    
                break;
    
              case "have-wall":

                if ($wall.length)
                {
                  const view = $wall[0].dataset.displaymode;

                  this.menu ({from: "display", type: view});
                }

                if ($wall.length && $wall[0].dataset.shared)
                  $menu.find('[data-action="chatroom"] a')
                    .removeClass ("disabled");
                else
                {
                  const $chatroom = S.getCurrent ("chatroom");

                  if ($chatroom.length)
                    $chatroom.chatroom ("hide");

                  $menu.find('[data-action="chatroom"] a')
                    .addClass ("disabled");
                }

                $menu.find(
                  '[data-action="view-properties"] a,'+
                  '[data-action="clone"] a,'+
                  '[data-action="export"] a,'+
                  '[data-action="search"] a,'+
                  '[data-action="close-walls"] a').removeClass ("disabled");

                if (adminAccess)
                  $menu.find(
                    '[data-action="delete"] a,'+
                    '[data-action="share"] a,'+
                    '[data-action="add-col"] a,'+
                    '[data-action="add-row"] a').removeClass ("disabled");
                
                break;
            }
    
            break;
    
          // Display menu
          case "display":

            switch (args.type)
            {
              case "unblock-externalref":

                $menu.find("[data-action='block-externalref']").show ();
                $menu.find("[data-action='unblock-externalref']").hide ();
                break;

              case "list-mode":

                $menu.find("[data-action='list-mode'] a").addClass ("disabled");
                $menu.find("[data-action='postit-mode'] a")
                  .removeClass ("disabled");
                break;

              case "postit-mode":

                $menu.find("[data-action='postit-mode'] a")
                  .addClass ("disabled");
                $menu.find("[data-action='list-mode'] a")
                  .removeClass ("disabled");
                break;

              case "block-externalref":

                $menu.find("[data-action='block-externalref']").hide ();
                $menu.find("[data-action='unblock-externalref']").show ();
                break;

              // Activate normal view item
              case "zoom-normal-on":

                $menuNormal.removeClass ("disabled");

                if (adminAccess)
                  $menu.find('[data-action="chatroom"] a,'+
                             '[data-action="filters"] a,'+
                             '[data-action="arrows"] a,'+
                             '[data-action="add-col"] a,'+
                             '[data-action="add-row"] a')
                    .addClass("disabled");
                break;
    
              // Deactivate normal view item
              case "zoom-normal-off":

                $menuNormal.addClass("disabled");
    
                if (adminAccess)
                  $menu.find('[data-action="chatroom"] a,'+
                             '[data-action="filters"] a,'+
                             '[data-action="arrows"] a,'+
                             '[data-action="add-col"] a,'+
                             '[data-action="add-row"] a')
                    .removeClass("disabled");

                break;
            }
        } 

        if (!H.checkUserVisible ())
          $menu.find('[data-action="share"] a,'+
                     '[data-action="chatroom"] a').addClass ("disabled");
    },

    // METHOD closeAllMenus ()
    closeAllMenus ()
    {
      const menu = this.element[0].querySelector (".postit-menu");

      if (menu)
        $(menu.parentNode).postit ("closeMenu");
    },

    // METHOD refreshUsersview ()
    refreshUsersview (count)
    {
      this.element.find("thead th:eq(0)").html ((count) ?
        `<div class="usersviewcounts"><i class="fas fa-user-friends fa-lg"></i> <span class="wpt-badge">${count}</span></div>` : "&nbsp;");
    },

    // METHOD checkPostitPlugsMenu ()
    checkPostitPlugsMenu (resetUndo)
    {
      const menu = this.element[0].querySelector (".postit-menu");

      if (menu)
        $(menu.parentNode).postit ("checkPlugsMenu", resetUndo);
    },

    // METHOD repositionPostitsPlugs ()
    repositionPostitsPlugs ()
    {
      this.element[0].querySelectorAll(".postit.with-plugs").forEach (
        (p) => $(p).postit ("repositionPlugs"));
    },

    // METHOD removePostitsPlugs ()
    removePostitsPlugs ()
    {
      this.element[0].querySelectorAll(".postit.with-plugs").forEach (
        (p) => $(p).postit ("removePlugs", true));

      this.settings.plugsContainer.remove ();
    },

    // METHOD refreshPostitsPlugs ()
    //FIXME //TODO Optimize
    refreshPostitsPlugs (plugs, partial = false)
    {
      const wall = this.element[0],
            hidePlugs = S.getCurrent("filters").hasClass ("plugs-hidden");
      let idsNew = {};

      (plugs||[]).forEach ((plug) =>
        {
          const startId = plug.item_start,
                endId = plug.item_end,
                start0 = wall.querySelector (
                           ".postit[data-id='postit-"+startId+"']"),
                $start = $(start0),
                startPlugin = $start.postit ("getClass"),
                label = plug.label || "...";

          idsNew[startId+""+endId] = 1;

          if ((start0.dataset.plugs||"").indexOf (endId) == -1)
          {
            const end = wall.querySelector (
                          ".postit[data-id='postit-"+endId+"']"),
                  newPlug = {
                    startId: startId,
                    endId: endId,
                    label: label,
                    obj: startPlugin.getPlugTemplate (start0, end, label)
                  };

            startPlugin.addPlug (newPlug);

            if (hidePlugs ||
                end.parentNode.classList.contains("list-mode") ||
                start0.parentNode.classList.contains("list-mode"))
              startPlugin.hidePlugs ();
          }
          else
            startPlugin.updatePlugLabel ({endId: endId, label: label});
        });

      // Remove obsolete plugs
      if (!partial)
        wall.querySelectorAll(".postit.with-plugs").forEach ((postit)=>
        {
          $(postit).postit("getSettings")._plugs.forEach ((plug)=>
            {
              if (!idsNew[plug.startId+""+plug.endId])
                $(wall.querySelector(
                    ".postit[data-id='postit-"+plug.endId+"']"))
                  .postit ("removePlug", plug, true);
            });
        });
    },

    // METHOD hidePostitsPlugs ()
    hidePostitsPlugs ()
    {
      this.element[0].querySelectorAll(".postit").forEach (
        (p) => $(p).postit ("hidePlugs", true));
    },

    // METHOD showPostitsPlugs ()
    showPostitsPlugs ()
    {
      this.repositionPostitsPlugs ();

      H.waitForDOMUpdate (()=>
        this.element[0].querySelectorAll(".postit").forEach (
          (p) => $(p).postit ("showPlugs", true)));
    },

    // METHOD refresh ()
    refresh (d)
    {
      if (d)
        this._refresh (d);
      else if (this.settings.id)
        H.request_ajax (
          "GET",
          "wall/"+this.settings.id,
          null,
          // success cb
          (d) => this._refresh (d));
    },

    // METHOD _refresh ()
    _refresh (d)
    {
      const plugin = this,
            $wall = plugin.element,
            wall0 = $wall[0],
            $filters = S.getCurrent ("filters"),
            $arrows = S.getCurrent ("arrows"),
            __refreshWallBasicProperties = (d)=>
            {
              plugin.setShared (d.shared);
              plugin.setName (d.name);
              plugin.setDescription (d.description);
            };

      // Partial wall update
      if (d.partial)
      {
        switch (d.partial)
        {
          // Postits
          case "postit":
            const $postit = $wall.find("[data-id='postit-"+d.postit.id+"']");

            // Rare case, when user have multiple sessions opened
            if (d.action != "insert" && !$postit.length)
              return;

            switch (d.action)
            {
              // Insert postit
              case "insert":
                $("td[data-id='cell-"+d.postit.cells_id+"']")
                  .cell ("addPostit", d.postit, true);
                plugin.checkPostitPlugsMenu ();
                break;

              // Update postit
              case "update":
                $postit.postit ("update", d.postit, {id: d.postit.cells_id});
                break;

              // Remove postit
              case "delete":
                $postit.postit ("removePlugs", true);
                $postit.remove ();
                break;
            }

            break;

          // Wall
          case "wall":
            __refreshWallBasicProperties (d.wall);

            break;

          //FIXME
          // plugs
          case "plugs": break;
        }
      }
      // Full wall update
      else
      {
        const wallId = plugin.settings.id,
              access = plugin.settings.access,
              rowsHeadersIds = {},
              colsHeadersIds = {},
              rowsCount = d.headers.rows.length,
              colsCount = d.headers.cols.length,
              postitsIds = {},
              rows = [],
              plugsContainer = plugin.settings.plugsContainer;

        _refreshing = true;

        wall0.dataset.cols = colsCount;
        wall0.dataset.rows = rowsCount;
        wall0.dataset.oldwidth = d.width;

        __refreshWallBasicProperties (d);

        for (let i = 0; i < colsCount; i++)
        {
          const header = d.headers.cols[i],
                $header =
                  $wall.find('thead th[data-id="header-'+header.id+'"]');

          if (!$header.length)
          {
            const $th = $("<th/>");

            $wall.find("thead tr").append ($th);
            $th.header ({
              item_type: "col",
              id: header.id,
              wall: $wall,
              wallId: wallId,
              title: header.title,
              picture: header.picture
            });
          }
          else
            $header.header ("update", header);
        }

        // Remove deleted rows
        for (let i = 0; i < rowsCount; i++)
          rowsHeadersIds[d.headers.rows[i].id] = true;

        wall0.querySelectorAll("tbody th").forEach ((th)=>
          {
            const $header = $(th);

            if (!rowsHeadersIds[$header.header ("getId")])
            {
              const $cell =
                $wall.find("tbody tr:eq("+$header.parent().index()+")");

              $cell.cell ("removePostitsPlugs");
              $cell.remove ();
            }
          });

        // Remove deleted columns
        for (let i = 0; i < colsCount; i++)
          colsHeadersIds[d.headers.cols[i].id] = true;

        wall0.querySelectorAll("thead th").forEach ((th)=>
          {
            const $header = $(th),
                  idx = $header.index ();

            if (idx > 0)
            {
              if (!colsHeadersIds[$header.header ("getId")])
              {
                $wall.find("thead th:eq("+idx+")").remove ();
                wall0.querySelectorAll("tbody tr").forEach ((tr)=>
                  {
                    const $cell = $(tr).find("td:eq("+(idx-1)+")");

                    $cell.cell ("removePostitsPlugs");
                    $cell.remove();
                  });
              }
            }
          });

        for (let i = 0, iLen = d.cells.length; i < iLen; i++)
        {
          const cell = d.cells[i],
                irow = cell.item_row;

          // Get all postits ids for this cell
          for (let j = 0, jLen = cell.postits.length; j < jLen; j++)
            postitsIds[cell.postits[j].id] = true;

          if (rows[irow] == undefined)
            rows[irow] = [];

          rows[irow][cell.item_col] = cell;
        }

        for (let i = 0, iLen = rows.length; i < iLen; i++)
        {
          const row = rows[i],
                header = d.headers.rows[i];

          if (!$wall.find('td[data-id="cell-'+row[0].id+'"]').length)
            plugin.addRow (header, row);
          else
            $wall.find('tbody th[data-id="header-'+header.id+'"]')
              .header ("update", header);

          for (let j = 0, jLen = row.length; j < jLen; j++)
          {
            const cell = row[j];
            let $cell = $wall.find('td[data-id="cell-'+cell.id+'"]'),
                isNewCell = false;

            // If new cell, add it
            if (!$cell.length)
            {
              isNewCell = true;

              $cell = $(_getCellTemplate (cell));

              $wall.find("tbody tr:eq("+cell.item_row+")").append ($cell);

              // Init cell
              $cell.cell ({
                id: cell.id,
                access: access,
                wall: $wall,
                wallId: wallId,
                plugsContainer: plugsContainer
              });
            }
            else
            {
              $cell.cell ("update", cell);

              // Remove deleted post-its
              $cell[0].querySelectorAll(".postit").forEach ((p)=>
                {
                  const $postit = $(p);

                  if (!postitsIds[$postit.postit ("getId")])
                  {
                    $postit.postit ("removePlugs", true);
                    p.remove ();
                  }
                });
            }

            for (let k = 0, kLen = cell.postits.length; k < kLen; k++)
            {
              const postit = cell.postits[k],
                    $postit = $wall.find('.postit[data-id="postit-'+
                                cell.postits[k].id+'"]');

              // If new postit, add it
              if (!$postit.length)
              {
                postit.isNewCell = isNewCell;
                $cell.cell ("addPostit", postit, true);
              }
              // else update it
              else
              {
                if (d.ignoreResize)
                  postit.ignoreResize = true;

                $postit.postit ("update", postit, {id: cell.id, obj: $cell});
              }
            }
          }
        }

        _refreshing = false;
        plugin.fixSize ();
      }

      // If filters tool is visible
      if ($filters.is (":visible"))
        $filters.filters ("apply");

      // If arrows tool is visible
      if ($arrows.is (":visible"))
        $arrows.arrows ("reset");

      if (d.postits_plugs)
        setTimeout (() =>
          {
            // Refresh postits relationships
            plugin.refreshPostitsPlugs (
              d.postits_plugs, d.partial && d.partial != "plugs");

            plugin.checkPostitPlugsMenu (!d.isResponse);

          }, 0);
      else
        plugin.repositionPostitsPlugs ();

      // Apply display mode
      setTimeout (()=> plugin.refreshCellsToggleDisplayMode (), 0);

      // Replay postits search
      setTimeout (()=> $("#postitsSearchPopup").postitsSearch("replay"), 250);
    },

    // METHOD refreshCellsToggleDisplayMode ()
    refreshCellsToggleDisplayMode ()
    {
      this.element.find("td.list-mode").each (function ()
        {
          $(this).cell ("toggleDisplayMode", true);
        });
    },

    // METHOD openCloseAllWallsPopup ()
    openCloseAllWallsPopup ()
    {
      H.openConfirmPopup ({
        icon: "times",
        content: `<?=_("Close the walls?")?>`,
        cb_ok: () => this.closeAllWalls ()
      });
    },

    // METHOD closeAllWalls ()
    closeAllWalls (saveSession = true)
    {
      // Tell the other methods that we are massively closing the walls
      S.set ("closing-all", true);
      document.querySelectorAll("table.wall").forEach ((wall) =>
        $(wall).wall ("close"));
      S.unset ("closing-all");

      saveSession &&
        $("#settingsPopup").settings ("saveOpenedWalls", null, false);
    },

    // METHOD close ()
    close ()
    {
      const activeTabId = "wall-"+this.settings.id,
            activeTab = document.querySelector('a[href="#'+activeTabId+'"]'),
            newActiveTabId =
              (activeTab.previousElementSibling) ?
                activeTab.previousElementSibling.getAttribute ("href") :
                  (activeTab.nextElementSibling) ?
                    activeTab.nextElementSibling.getAttribute ("href") : null,
            $chatroom = S.getCurrent ("chatroom");

      if ($chatroom.is (":visible"))
        $chatroom.chatroom ("leave");

      // If account popup is opened, do not close it: we are dealing with the
      // "invisible mode" option.
      $(".modal.show:not(#accountPopup)").modal ("hide");

      this.removePostitsPlugs ();

      activeTab.remove ();
      document.getElementById(activeTabId).remove ();

      // No more wall to display
      if (!document.querySelector(".wall"))
      {
        document.querySelector(".nav.walls").style.display = "none";

        this.zoom ({type: "normal", "noalert": true});
        $("#dropdownView,#dropdownEdit").addClass ("disabled");
  
        this.menu ({from: "wall", type: "no-wall"});

        $("#welcome").show (S.get ("closing-all") ? null : "fade");
      }
      // Active another tabs after deletion
      else
        $(".nav-tabs.walls").find('a[href="'+newActiveTabId+'"]').tab ("show");

      // If we are not massively closing all walls
      if (!S.get ("closing-all"))
        $("#settingsPopup").settings ("saveOpenedWalls");

      //FIXME
      setTimeout (()=> S.reset (), 250);
    },

    // METHOD openDeletePopup ()
    openDeletePopup ()
    {
      this.edit (() =>
        {
          // H.openConfirmPopover() does not display the popover on some
          // devices when button menu is visible.
          if (H.isMainMenuCollapsed ())
            H.openConfirmPopup ({
              icon: "trash",
              content: `<?=_("Delete this wall?")?>`,
              cb_ok: () => this.delete (),
              cb_close: () => this.unedit ()
            });
          else
            H.openConfirmPopover ({
              item: this.settings.tabLink,
              placement: "left",
              title: `<i class="fas fa-trash fa-fw"></i> <?=_("Delete")?>`,
              content: "<?=_("Delete this wall?")?>",
              cb_close: () => this.unedit (),
              cb_ok: () => this.delete ()
            });
        }, null, true);
    },

    // METHOD delete ()
    delete ()
    {
      this.element[0].dataset.todelete = true;

      //FIXME 2x
      this.unedit (() =>
        {
          this.close ();
          $("#settingsPopup").settings (
            "removeWallBackground", this.settings.id);
        });
    },

    // METHOD createColRow ()
    createColRow (type)
    {
      const wall = this.element[0];

      if (Number (wall.dataset.rows) *
            Number (wall.dataset.cols) >= <?=WPT_MAX_CELLS?>)
        return H.infoPopup ("<?=_("For performance reasons, a wall cannot contain more than %s cells")?>.".replace("%s", <?=WPT_MAX_CELLS?>));

      H.request_ws (
        "PUT",
        "wall/"+this.settings.id+"/"+type,
        null,
        ()=>
          S.getCurrent("walls")[(type=="col")?"scrollLeft":"scrollTop"](30000));
    },

    // METHOD addRow ()
    addRow (header, row)
    {
      const plugin = this,
            $wall = plugin.element,
            wallId = plugin.settings.id;
      let tds = "";

      for (let i = 0; i < row.length; i++)
        tds += _getCellTemplate (row[i]);

      const $row = $(`<tr><th></th>${tds}</tr>`);

      // Add row
      $wall.find("tbody").append ($row);
      $row.find("th:eq(0)").header ({
        access: plugin.settings.access,
        item_type: "row",
        id: header.id,
        wall: $wall,
        wallId: wallId,
        title: header.title,
        picture: header.picture
      });

      // Init cells
      $row.find("td").each (function ()
        {
          const cellId = this.dataset.id.substring (5);

          $(this).cell ({
            id: cellId,
            access: plugin.settings.access,
            usersettings: plugin.settings.usersettings["cell-"+cellId]||{},
            wall: $wall,
            wallId: wallId,
            plugsContainer: plugin.settings.plugsContainer
          });
        });

      plugin.fixSize ();
    },

    // METHOD deleteRow ()
    deleteRow (rowIdx)
    {
      const $wall = this.element,
            $tr = $wall.find("tr:eq("+(rowIdx+1)+")");

      $tr[0].querySelectorAll("td").forEach (
        (td)=> $(td).cell ("removePostitsPlugs"));

      H.headerRemoveContentKeepingWallSize ({
        oldW: $tr.find("th").outerWidth (),
        cb: () => $tr.remove ()
      });

      H.request_ws (
        "DELETE",
        "wall/"+this.settings.id+"/row/"+rowIdx,
        {wall: {width: Math.trunc($wall.outerWidth ())}});
    },

    // METHOD deleteCol ()
    deleteCol (idx)
    {
      const $wall = this.element,
            $header = $wall.find("thead tr th:eq("+idx+")"),
            oldW = Math.trunc ($wall.outerWidth () - 1),
            newW = Math.trunc (oldW - $header.outerWidth ()),
            data = {
              wall: {width: oldW},
              width: Math.trunc ($header.outerWidth ())
            };

     $header.remove ();

     $wall[0].querySelectorAll("tbody tr").forEach ((tr)=>
        {
          const $cell = $(tr).find("td:eq("+(idx - 1)+")");

          $cell.cell ("removePostitsPlugs");
          $cell.remove ();
        });

      this.fixSize (oldW, newW);

      H.request_ws (
        "DELETE",
        "wall/"+this.settings.id+"/col/"+(idx - 1),
        data);
    },

    // METHOD addNew ()
    addNew (args, $popup)
    {
      const plugin = this,
            $tabs = $(".nav-tabs.walls"),
            method = (args.load) ? "GET" : "PUT",
            service = (args.load) ? "wall/"+args.wallId : "wall",
            data = (args.load) ? null : {name: args.name, grid: !!args.grid};

       if (data)
       {
         if (data.grid)
         {
           data["colsCount"] = args.dim.colsCount;
           data["rowsCount"] = args.dim.rowsCount;
         }
         else
         {
           const w = Number (args.dim.width),
                 h = Number (args.dim.height);

           if (w)
             data["width"] = w + 52;
           else
             data["width"] = $(window).width () - 50;

           if (data.height < 200)
             data.height = 200;

           if (h)
             data["height"] = h;
           else
             data["height"] = $(window).height ();

           if (data.width < 300)
             data.width = 300;
         }
       }

      S.reset ();

      if (args.restoring)
        $tabs.prepend (`<a class="nav-item nav-link" href="#wall-${args.wallId}" data-toggle="tab"><span class="icon"></span><span class="val"></span></a>`);

      H.request_ajax (
        method,
        service,
        data,
        // success cb
        (d) =>
        {
          // If we must raise a postit deadline alert.
          if (args.fromDirectURL)
          {
            d.postitId = args.postitId||null;
            d.fromDirectURL = true;
          }

          // If we are restoring a wall.
          if (args.restoring)
            d.restoring = 1;

          // The wall does not exists anymore.
          if (d.removed)
          {
            $tabs.find("a[href='#wall-"+args.wallId+"']").remove ();

            if ($tabs.find(".nav-item").length)
              $tabs.find(".nav-item:first-child").tab ("show");

            // Save opened walls when all walls will be loaded.
            if (d.restoring)
            {
              if (S.get ("last-wall") && S.get ("last-wall") == 1)
              {
                S.unset ("last-wall");
                $("#settingsPopup").settings ("saveOpenedWalls");
              }
              else
                S.set ("save-opened-walls", true);
            }

            return H.displayMsg ({type: "warning", msg: "<?=_("You no longer have access to some of the walls you had opened!")?>"});
          }

          if (d.error_msg)
            return H.displayMsg ({type: "warning", msg: d.error_msg});

          if ($popup)
            $popup.modal ("hide");

          $(".tab-content.walls").append (`<div class="tab-pane" id="wall-${d.id}"><div class="toolbox chatroom"></div><div class="toolbox filters"></div><div class="arrows"></div><table class="wall" data-id="wall-${d.id}" data-access="${d.access}"></table></div>`);

          if (!args.restoring)
            $tabs.prepend (`<a class="nav-item nav-link" href="#wall-${d.id}" data-toggle="tab"><span class="icon"></span><span class="val"></span></a>`);

          $tabs.find('a[href="#wall-'+d.id+'"]')
            .attr("data-access", d.access)
            .prepend($(`<button type="button" class="close"><span class="close">&times;</span></button>`)
            // EVENT click on close wall icon
            .on("click",function()
            {
              H.openConfirmPopover ({
                item: $(this).parent (),
                placement: "left",
                title: `<i class="fas fa-times fa-fw"></i> <?=_("Close")?>`,
                content: "<?=_("Close this wall?")?>",
                cb_ok: ()=> S.getCurrent("wall").wall ("close")
              });
            }));

          d["background-color"] =
            $("#settingsPopup").settings ("get", "wall-background", d.id);

          const $wallDiv = $("#wall-"+d.id);

          $wallDiv.find(".wall").wall (d);
          $wallDiv.find(".chatroom").chatroom ({wallId: d.id});
          $wallDiv.find(".filters").filters ();
          $wallDiv.find(".arrows").arrows ();

          if (!args.restoring || wpt_userData.settings.activeWall == d.id)
          {
            S.set ("newWall", true);
            $tabs.find('a[href="#wall-'+d.id+'"]').tab ("show");
            S.unset ("newWall");
          }

          document.querySelector(".nav.walls").style.display = "block";

          $("#dropdownView,#dropdownEdit").removeClass ("disabled");

          plugin.menu ({from: "wall", type: "have-wall"});

          if (!args.restoring)
            $("#settingsPopup").settings ("saveOpenedWalls");
        });
    },

    // METHOD open ()
    open (args)
    {
      args.load = true;

      this.addNew (args);
    },

    // METHOD clone ()
    clone ()
    {
      H.openConfirmPopup ({
        icon: "clone",
        content: `<?=_("Depending on its content, cloning a wall can take time.<br>Clone anyway?")?>`,
        cb_ok: () =>
          {
            H.request_ajax (
            "PUT",
            "wall/"+this.settings.id+"/clone",
            null,
            // success cb
            (d) =>
              {
                if (d.error_msg)
                  return H.displayMsg ({
                           type: "warning",
                           msg: d.error_msg
                         });

                $("<div/>").wall ("open", {wallId: d.wallId});

                H.displayMsg ({
                  type: "success",
                  msg: "<?=_("The wall has been successfully cloned.")?>"
                });
            });
          }
      });
    },

    // METHOD export ()
    export ()
    {
      H.openConfirmPopup ({
        icon: "file-export",
        content: `<?=_("Depending on its content, the export size can be substantial.<br>Export anyway?")?>`,
        cb_ok: () => H.download ({
          url: "/wall/"+this.settings.id+"/export",
          fname: "wopits-wall-export-"+this.settings.id+".zip",
          msg: "<?=_("An error occurred while exporting wall data.")?>"
        })
      });
    },

    // METHOD import ()
    import ()
    {
      $(".upload.import-wall").click ();
    },

    // METHOD restorePreviousSession ()
    restorePreviousSession (args)
    {
      const walls = wpt_userData.settings.openedWalls;

      if (walls)
      {
        const {type, wallId, postitId} = args||{},
              wallsLen = walls.length;

        for (let i = wallsLen - 1; i >= 0; i--)
        {
          const fromDirectURL = type && walls[i] == wallId;

          if (i == 0)
            S.set ("last-wall", wallsLen);

          this.open ({
            wallId: walls[i],
            restoring: true,
            fromDirectURL: fromDirectURL,
            postitId: fromDirectURL ? postitId : null
          });
        }
      }
    },

    // METHOD loadSpecific ()
    loadSpecific (args)
    {
      const {wallId, postitId} = args;

      if ((wpt_userData.settings.openedWalls||[]).indexOf (wallId) == -1)
        this.open ({
          wallId: wallId,
          restoring: false,
          fromDirectURL: true,
          postitId: postitId
        });

      // Remove special alert URL.
      history.pushState (null, null, "/");
    },

    // METHOD refreshUserWallsData ()
    refreshUserWallsData (success_cb)
    {
      H.request_ajax (
        "GET",
        "wall",
        null,
        // success cb
        (d) =>
        {
          wpt_userData.walls = d.list||[];

          if (success_cb)
            success_cb ();
        });
    },

    // METHOD openOpenWallPopup ()
    openOpenWallPopup ()
    {
      this.refreshUserWallsData (() =>
        {
          const $popup = $("#openWallPopup");

          $popup.openWall ("reset");
          $popup.openWall ("displayWalls");
          $popup.openWall ("controlFiltersButtons");

          H.openModal ($popup);
        });
    },

    // METHOD openNamePopup ()
    openNamePopup ()
    {
      const $popup = $("#createWallPopup");

      H.cleanPopupDataAttr ($popup);
      $popup[0].dataset.noclosure = true;
      H.openModal ($popup);
    },

    // METHOD displayWallUsersview()
    displayWallUsersview ()
    {
      //TODO We should use ajax instead of ws
      H.request_ws (
        "GET",
        "wall/"+this.settings.id+"/usersview",
        null,
        (d) =>
        {
          const $popup = $("#wallUsersviewPopup"),
                userId = wpt_userData.id;
          let html = "";

          d.list.forEach ((item) =>
            {
              if (item.id != userId)
                html += `<a href="#" data-id="${item.id}" class="list-group-item list-group-item-action" data-title="${H.htmlEscape(item.fullname)}" data-picture="${item.picture||""}" data-about="${H.htmlEscape(item.about||"")}">${H.getAccessIcon(item.access)} ${item.fullname} (${item.username})</a>`;
            });

          $popup.find(".list-group").html (html);
          H.openModal ($popup);
        }
      );
    },

    // METHOD openPropertiesPopup ()
    openPropertiesPopup (args = {})
    {
      args.wall = this.element;

      if (H.checkAccess ("<?=WPT_WRIGHTS_ADMIN?>"))
        this.edit (() =>
          $("#wallPropertiesPopup").wallProperties ("open", args));
      else
        $("#wallPropertiesPopup").wallProperties ("open", args);
    },

    // METHOD getName ()
    getName ()
    {
      return this.settings.tabLink.find("span.val").text ();
    },

    // METHOD setName ()
    setName (name, noIcon)
    {
      const $div = this.settings.tabLink,
            notOwner = (this.settings.ownerid != wpt_userData.id);

      let html = (noIcon) ?
        `<i class="fas fa-cog fa-spin fa-fw"></i>` :
         H.getAccessIcon (this.settings.access);

      if (!noIcon && notOwner)
        html = `<i class="fas fa-user-slash wallname-icon" data-toggle="tooltip" title="<?=_("You are not the creator of this wall")?>"></i>`+html;

      $div.find('span.icon').html (html);
      $div.find('span.val').text (H.noHTML (name));

      if (!noIcon)
      {
        if (notOwner)
          H.enableTooltips ($div.find('span.icon'));

        this.refreshSharedIcon ();
      }
    },

    // METHOD setShared ()
    setShared (isShared)
    {
      const wall = this.element[0];

      if (isShared)
        wall.dataset.shared = 1;
      else
        wall.removeAttribute ("data-shared");

      this.refreshSharedIcon ();
    },

    // METHOD refreshSharedIcon ()
    refreshSharedIcon ()
    {
      const $div = this.settings.tabLink,
            $span = $div.find ('span.icon');

      if (this.element[0].dataset.shared)
      {
        if (!$span.find(".wallname-icon").length)
        {
          $span.prepend (`<i class="fas fa-share wallname-icon" data-toggle="tooltip" title="<?=_("This wall is shared")?>"></i>`);
          H.enableTooltips ($span);
        }
      }
      else
        $span.find(".wallname-icon").remove ();
    },

    // METHOD getDescription ()
    getDescription ()
    {
      return this.settings.tabLink[0].dataset.description;
    },

    // METHOD setDescription ()
    setDescription (description)
    {
      this.settings.tabLink[0].dataset.description = H.noHTML (description);
    },

    // METHOD fixSize ()
    fixSize (oldW, newW)
    {
      if (_refreshing)
        return;

      const wall = this.element[0];
      let w = Number (wall.dataset.oldwidth);

      if (!w)
        w = this.element.outerWidth ();

      if (newW)
      {
        if (newW > oldW)
          w += (newW - oldW);
        else if (newW < oldW)
          w -= (oldW - newW);
      }

      wall.dataset.oldwidth = w;
      wall.style.width = w+"px";
      wall.style.maxWidth = w+"px";
    },

    // METHOD setPostitsDisplayMode ()
    setPostitsDisplayMode (type)
    {
      this.menu ({from: "display", type: type});

      this.element[0].dataset.displaymode = type;

      this.element.find("td").each (function ()
        {
          $(this).cell ("setPostitsDisplayMode", type);
        });

      // Re-apply filters
      if (S.getCurrent("filters").is (":visible"))
        S.getCurrent("filters").filters ("apply");

      H.request_ajax (
        "POST",
        "user/wall/"+this.settings.id+"/displayMode",
        {display: type});
    },

    // METHOD zoom ()
    //FIXME KO with some browsers and touch devices
    zoom (args)
    {
      const $zoom = $(".tab-content.walls"),
            zoom0 = $zoom[0],
            wall0 = this.element[0],
            $plugsLabels = this.settings.plugsContainer.find (".plug-label"),
            from = args.from,
            type = args.type,
            noalert = !!args.noalert,
            zoomStep = (!!args.step) ? args.step : 0.2,
            writeAccess = H.checkAccess ("<?=WPT_WRIGHTS_RW?>");
      let stylesOrigin;

      if (!args.step)
      {
        wall0.style.top = 0;
        wall0.style.left = "15px";
      }

      if (type == "screen")
        return this.screen ();

      $('.dropdown-menu li[data-action="zoom-screen"] a')
        .removeClass ("disabled");

      let level = $zoom.css ("transform") || $zoom.css ("-moz-transform");

      level = Number ((level == "none") ? 1 : level.match(/\(([^,]+),/)[1]);

      if (!zoom0.dataset.zoomlevelorigin)
      {
        stylesOrigin = zoom0.style;

        if (writeAccess && !noalert)
          H.displayMsg ({
            type: "warning",
            title: "<?=_("Zoom enabled")?>",
            msg: "<?=_("The wall is read only")?>"
          });

        zoom0.dataset.zoomlevelorigin = level;
        $zoom.css ({
          "pointer-events": "none",
          "opacity": (writeAccess) ? .6 : 1,
          "width": 30000
        });
        $plugsLabels.css ("pointer-events", "none");
      }

      if (from)
        zoom0.dataset.zoomtype = from;
      else
        zoom0.removeAttribute ("data-zoomtype");

      if (type != "normal")
        this.menu ({from: "display", type: "zoom-normal-on"});

      switch (type)
      {
        case "+": level += zoomStep; break;
        case "-": level -= zoomStep; break;
        case "normal": level = Number (zoom0.dataset.zoomlevelorigin); break;
      }

      if (from != "screen" && level == zoom0.dataset.zoomlevelorigin)
      {
        setTimeout(() => $("#normal-display-btn").hide().popover ("hide"),150);

        zoom0.removeAttribute ("data-zoomtype");
        zoom0.removeAttribute ("data-zoomlevelorigin");

        this.menu ({from: "display", type: "zoom-normal-off"});

        if (writeAccess && !noalert)
          H.displayMsg ({
            type: "info",
            title: "<?=_("Zoom disabled")?>",
            msg: "<?=_("The wall is again editable")?>"
          });

        zoom0.style = stylesOrigin;
        $plugsLabels.css ("pointer-events", "auto");

        //FIXME
        //wall0.scrollIntoView ({inline: "start"});
        $("#walls")
          .scrollLeft(0)
          .scrollTop (0);
      }
      else
      {
        $zoom.css ({
          "transform": "scale("+level+")",
          "transform-origin": "top left",
          "-moz-transform": "scale("+level+")",
          "-moz-transform-origin": "top left",
          "-webkit-transform": "scale("+level+")",
          "-webkit-transform-origin": "top left"
        });

        wall0.scrollIntoView ({inline: "center"});

        $("#normal-display-btn").show().popover ("show");
      }
    },

    // METHOD screen ()
    screen ()
    {
      const step = .005,
            wall = this.element[0],
            walls = S.getCurrent("walls")[0];
      let position = wall.getBoundingClientRect ();

      if (position.bottom - position.top < walls.clientHeight &&
          position.right < walls.clientWidth)
      {
        do
        { 
          this.zoom ({from: "screen", type: "+", step: step});
          position = wall.getBoundingClientRect ();
        }
        while (position.bottom - position.top < walls.clientHeight - 20 &&
               position.right < walls.clientWidth - 5);
      }
      else
      {
        do
        { 
          this.zoom ({from: "screen", type: "-", step: step});
          position = wall.getBoundingClientRect ();
        }
        while (!(position.bottom - position.top < walls.clientHeight - 20 &&
                 position.right < walls.clientWidth - 5));
      }

      $('.dropdown-menu li[data-action="zoom-screen"] a').addClass ("disabled");
    },

    // METHOD edit ()
    edit (success_cb, error_cb, todelete = false)
    {
      _originalObject = this.serialize ();

      if (!this.settings.shared)
        return success_cb && success_cb ();

      H.request_ws (
        "PUT",
        "wall/"+this.settings.id+"/editQueue/wall/"+this.settings.id,
        {todelete: todelete},
        // success cb
        (d) =>
        {
          if (d.error_msg)
            H.raiseError (() => error_cb && error_cb (), d.error_msg);
          else if (success_cb)
            success_cb (d);
        }
      );
    },

    // METHOD serialize ()
    serialize ()
    {
      return {
        name: this.getName (),
        description: this.getDescription ()
      };
    },

    // METHOD unedit ()
    unedit (success_cb, error_cb)
    {
      let data = null;

      if (this.element[0].dataset.todelete)
        data = {todelete: true};
      // Update wall only if it has changed
      else
      {
        data = this.serialize ();

        if (!H.updatedObject (_originalObject, data))
        {
          if (!this.settings.shared)
            return success_cb && success_cb ();
          else
            data = null;
        }
      }

      H.request_ws (
        "DELETE",
        "wall/"+this.settings.id+"/editQueue/wall/"+this.settings.id,
        data,
        // success cb
        (d) =>
        {
          if (!(data && data.todelete) && d.error_msg)
          {
            error_cb && error_cb ();

            H.displayMsg ({type: "warning", msg: d.error_msg});
          }
          else if (success_cb)
            success_cb ();
        },
        // error cb
        error_cb
      );
    },

    // METHOD displayExternalRef ()
    displayExternalRef (v)
    {
      const update = (v !== undefined),
            val = update ? v : this.settings.displayexternalref,
            type = (val == 1) ? "unblock" : "block";

      if (update)
      {
        this.settings.displayexternalref = val;

        this.element[0].querySelectorAll(".postit").forEach (
          (p) => $(p).postit (type+"ExternalRef"));

        H.request_ajax (
          "POST",
          "user/wall/"+this.settings.id+"/externalRef",
          {display: val});
      }

      if (this.element.is (":visible"))
        this.menu ({from: "display", type: type+"-externalref"});

      return val;
    },

    // METHOD haveExternalRef ()
    haveExternalRef ()
    {
      return this.element[0].querySelector (".postit[data-haveexternalref]");
    },

  });

  /////////////////////////// AT LOAD INIT //////////////////////////////

  $(function ()
    {
      if (!document.querySelector ("body.login-page"))
      {
        $("#settingsPopup").settings ("applyTheme");

        WS.connect (
          "wss://"+location.host+"/app/ws?token="+wpt_userData.token, ()=>
          {
            const $settings = $("#settingsPopup");

            $settings.settings ({locale: $("html").attr ("lang")});

            // if a theme exists from the login page, apply it once the user is
            // logged
            const loginTheme = ST.get ("theme");
            if (loginTheme)
            {
              ST.delete ("theme");
              $settings.settings ("set", {theme: loginTheme});
            }

            $settings.settings ("applyTheme");

            // Check if wopits has been upgraded
            H.checkForAppUpgrade ();

            const directURLData = _getDirectURLData ();

            // Load previously opened walls
            $("<div/>").wall ("restorePreviousSession", directURLData);

            // Check if we must display a postit alert or a specific wall
            // (from direct URL)
            if (directURLData)
              $("<div/>").wall ("loadSpecific", directURLData);

            // -> 15mn
            setInterval (()=> $.get ("/api/user/ping"), 15*60*1000);

            // Display theme chooser if needed.
            if (!wpt_userData.settings.theme)
              setTimeout(()=>
                $("#settingsPopup").settings ("openThemeChooser"), 1000);

            H.enableTooltips ($("body"));
          });

        H.fixMenuHeight ();
        H.fixMainHeight ();

        //FIXME KO with some browsers and touch devices
        if ($.support.touch || H.navigatorIsEdge ())
          $("#main-menu").addClass ("nofullview");

        // Arrows plugin is useless on desktop
        if (!$.support.touch)
          $("#main-menu").addClass ("noarrows");

        $("#normal-display-btn")
          // EVENT click on back to standard view button
          .on("click", function ()
          {
            S.getCurrent("wall").wall ("zoom", {type: "normal"});
          });

        $("#createWallPopup #w-grid")
          // EVENT change on wall dimension in wall creation popup
          .on("change", function ()
          {
            const btn = this,
                  $popup = $(this).closest (".modal");

            $popup.find("span.required").remove ();
            $popup.find(".cols-rows input").val (3);
            $popup.find(".width-height input").val ("");
            $popup.find(".cols-rows,.width-height").hide ();

            if (btn.checked)
              $(this).parent().removeClass ("disabled");
            else
              $(this).parent().addClass ("disabled");

            $popup.find(btn.checked?".cols-rows":".width-height").show ("fade");
          });

        $(`<input type="file" accept=".zip" class="upload import-wall">`)
          // EVENT change for wall importation upload
          .on("change", function (e)
            {
              if (e.target.files && e.target.files.length)
              {
                H.getUploadedFiles (e.target.files, "\.zip$",
                  (e, file) =>
                  {
                    $(this).val ("");
  
                    if (H.checkUploadFileSize ({
                          size: e.total,
                          maxSize:<?=WPT_IMPORT_UPLOAD_MAX_SIZE?>
                        }) &&
                        e.target.result)
                    {
                      H.request_ajax (
                        "PUT",
                        "wall/import",
                        {
                          name: file.name,
                          size: file.size,
                          item_type: file.type,
                          content: e.target.result
                        },
                        // success cb
                        (d) =>
                        {
                          if (d.error_msg)
                            return H.displayMsg ({
                                     type: "warning",
                                     msg: d.error_msg
                                   });
  
                          $("<div/>").wall ("open", {wallId: d.wallId});
  
                          H.displayMsg ({
                            type: "success",
                            msg: "<?=_("The wall has been successfully imported.")?>"
                          });
                        });
                    }
                  });
              }
            }).appendTo ("body");

        $(document)
          // EVENT click on wall users count
          .on("click","thead th:first-child .usersviewcounts", function (e)
          {
            S.getCurrent("wall").wall ("displayWallUsersview");
          });

        $("#wallUsersviewPopup")
          // EVENT click on username in wall users popup
          .on("click",".list-group a",function (e)
          {
            const a = this,
                  $popup = $("#userViewPopup"),
                  $userPicture = $popup.find (".user-picture"),
                  $about = $popup.find (".about");

            if (a.dataset.picture)
            {
              $userPicture
                .empty ()
                .append (
                  $(`<img src="${a.dataset.picture}">`)
                    .on("error",function (e){$userPicture.hide()}));

              $userPicture.show ();
            }
            else
              $userPicture.hide ();

            $popup.find(".modal-title span").text (a.dataset.title);
            $popup.find(".name dd").text (a.dataset.title);

            if (a.dataset.about)
            {
              $about.find("dd").html (H.nl2br (a.dataset.about));
              $about.show ();
            }
            else
              $about.hide ();

            H.openModal ($popup);
          });

        $(document)
          // EVENT CLICK on main menu items
          .on("click", ".navbar.wopits a,"+
                       "#main-menu a:not(.disabled),"+
                       ".nav-tabs.walls a[data-action='new'],"+
                       "#welcome",function(e)
          {
            const $wall = S.getCurrent ("wall"),
                  action = this.dataset.action||this.parentNode.dataset.action;

            switch (action)
            {
              case "zoom+":

                $wall.wall ("zoom", {type: "+"});

                break;

              case "zoom-":

                $wall.wall ("zoom", {type: "-"});

                break;

              case "zoom-screen":

                $wall.wall ("zoom", {type:"screen"});

                break;

              case "zoom-normal":

                $wall.wall ("zoom", {type: "normal"});

                break;

              case "list-mode":
              case "postit-mode":

                $wall.wall ("setPostitsDisplayMode", action);

                break;

              case "unblock-externalref":

                $wall.wall ("displayExternalRef", 1, true)

                break;

              case "block-externalref":

                $wall.wall ("displayExternalRef", 0, true)

                break;

              case "chatroom":

                var input = $(this).find("input")[0];

                // Manage checkbox
                if (e.target.tagName != "INPUT")
                  input.checked = !input.checked;

                S.getCurrent("chatroom").chatroom ("toggle");

                break;

              case "filters":

                var input = $(this).find("input")[0];

                // Manage checkbox
                if (e.target.tagName != "INPUT")
                  input.checked = !input.checked;

                S.getCurrent("filters").filters ("toggle");

                break;

              case "arrows":

                var input = $(this).find("input")[0];

                // Manage checkbox
                if (e.target.tagName != "INPUT")
                  input.checked = !input.checked;

                S.getCurrent("arrows").arrows ("toggle");

                break;

              case "settings":

                $("#settingsPopup").settings ("open");

                break;

              case "new":

                H.closeMainMenu ();

                $("<div/>").wall ("openNamePopup");

                break;

              case "about":

                H.openModal ($("#aboutPopup"));

                break;

              case "user-guide":

                H.openModal ($("#userGuidePopup"));

                break;

              case "open":

                $("<div/>").wall ("openOpenWallPopup");

                break;

              case "close-walls":

                $("<div/>").wall ("openCloseAllWallsPopup");

                break;

              case "delete":

                $wall.wall ("openDeletePopup");

                break;

              case "search":

                $("#postitsSearchPopup").postitsSearch ("open");

                break;

              case "clone":

                $wall.wall ("clone");

                break;

              case "export":

                $wall.wall ("export");

                break;

              case "import":

                $("<div/>").wall ("import");

                break;

              case "share":

                $("#shareWallPopup").shareWall ("open");

                break;

              case "add-col":

                $wall.wall ("createColRow", "col");

                break;

              case "add-row":

                $wall.wall ("createColRow", "row");

                break;

              case "view-properties":

                $wall.wall ("openPropertiesPopup");

                break;
            }
          });
      }
      else
        $('[data-action="about"]')
        // EVENT CLICK on about button in the login page
        .on("click", function ()
        {
          H.openModal ($("#aboutPopup"));
        });
  });

<?php echo $Plugin->getFooter ()?>
