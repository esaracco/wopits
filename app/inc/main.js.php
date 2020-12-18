<?php
/**
  Javascript plugin - Main menu & walls

  Scope: Global
  Elements: .wall
  Description: Manage main menu & walls
*/

  require_once (__DIR__.'/../prepend.php');

  $Plugin = new Wopits\jQueryPlugin ('wall', '', 'wallElement');
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
    init (args)
    {
      const plugin = this,
            $wall = plugin.element,
            wall = $wall[0],
            settings = plugin.settings,
            wallId = settings.id,
            access = settings.access,
            writeAccess = this.canWrite (),
            rows = [];

      settings.tabLink =
        $(".nav-tabs.walls").find ('a[href="#wall-'+settings.id+'"]');

      // Add wall menu
      $("#wall-"+settings.id).find(".wall-menu").wmenu ({
        wallPlugin:plugin,
        access: access
      });

      // Create plugs container
      settings.plugsContainer =
        $(`<div id="plugs-${wallId}" data-access="${access}"></div>`)
          .appendTo ("body");

      if (args.restoring)
        wall.dataset.restoring = 1;

      wall.dataset.displaymode = settings.displaymode;

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

      if (H.haveMouse ())
        $wall.draggable({
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
              S.set ("dragging", true, 500);

              // Fix arrows tool appearence
              const $a = S.getCurrent ("arrows");
              if ($a.is (":visible"))
                $a.arrows ("update");

              const $f = S.getCurrent ("filters");
              if (!$f.length || !$f.hasClass ("plugs-hidden"))
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
                    $cell = $wall.find("[data-id='cell-"+cell.id+"']");

              for (let k = 0, kLen = cell.postits.length; k < kLen; k++)
              {
                cell.postits[k]["access"] = access;
                cell.postits[k]["init"] = true;
                $cell.cell ("addPostit", cell.postits[k], true);
              }
            }
          }

          $("#welcome").hide ();

          $wall.show ("fade");

          wall.dataset.cols = hcols.length;
          wall.dataset.rows = hrows.length;

          plugin.setName (settings.name);
          plugin.setDescription (settings.description);

          $(window).trigger ("resize");

          if (args.restoring)
          {
            delete settings.restoring;
            wall.removeAttribute ("data-restoring");
          }

          // Set wall users view count if needed
          const viewcount =
            WS.popResponse ("viewcount-wall-"+wallId);
          if (viewcount !== undefined)
            plugin.refreshUsersview (viewcount); 

          // If last wall to load.
          if (args.lastWall)
          {
            if (!S.get ("fromDirectURL"))
              setTimeout(()=>
              $("[data-id='wall-"+wpt_userData.settings.activeWall+"']")
                .wall ("refresh"), 0);

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
                  if (!document.querySelector (".walls i.fa-cog"))
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
                plugin.displayHeaders ();
                // Apply display mode
                plugin.refreshCellsToggleDisplayMode ();

                $wall.parent().find(".wall-menu").css ("visibility", "visible");
              };

            plugin.displayExternalRef ();

            // Display postit dealine alert or specific wall if needed.
            if (settings.fromDirectURL)
            {
              const postitId = settings.postitId;

              plugin.setActive ();
              //FIXME
              plugin.refresh ();

              H.waitForDOMUpdate (() =>
                {
                  if (postitId)
                  {
                    const $postit =
                            $wall.find(".postit[data-id=postit-"+postitId+"]");

                    if ($postit.length)
                      $postit.postit ("displayDeadlineAlert");
                    else
                      H.displayMsg ({type: "warning", msg: "<?=_("The note has been deleted")?>"});
                  }
                  else
                    plugin.displayShareAlert ();

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

    // METHOD menu ()
    menu (args)
    {
      const $wall = S.getCurrent ("wall"),
            $wmenu = $wall.parent().find (".wall-menu"),
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
              $menu.find('[data-action="delete"] a').addClass ("disabled");
              $wmenu.find('[data-action="share"]').hide ();
            }
    
            switch (args.type)
            {
              case "no-wall":
    
                $menu.find(
                  '[data-action="delete"] a,'+
                  '[data-action="close-walls"] a,'+
                  '[data-action="clone"] a,'+
                  '[data-action="export"] a').addClass ("disabled");

                  $wmenu.find('[data-action="share"]').hide ();
    
                break;
    
              case "have-wall":

                if ($wall.length)
                  this.menu ({
                    from: "display",
                    type: $wall[0].dataset.displaymode
                  });

                if ($wall.length && $wall[0].dataset.shared)
                  $menu.find('[data-action="chat"] a')
                    .removeClass ("disabled");
                else
                {
                  const $chat = S.getCurrent ("chat");

                  if ($chat.length)
                    $chat.chat ("hide");

                  $menu.find('[data-action="chat"] a')
                    .addClass ("disabled");
                }

                $menu.find(
                  '[data-action="clone"] a,'+
                  '[data-action="export"] a,'+
                  '[data-action="close-walls"] a').removeClass ("disabled");

                if (adminAccess)
                {
                  $menu.find(
                    '[data-action="delete"] a').removeClass ("disabled");

                  $wmenu.find('[data-action="share"]').show ();
                }
                
                break;
            }
    
            break;
    
          // Display menu
          case "display":

            switch (args.type)
            {
              case "unblock-externalref":

                $wmenu.find("[data-action='block-externalref']").show ();
                $wmenu.find("[data-action='unblock-externalref']").hide ();

                break;

              case "block-externalref":

                $wmenu.find("[data-action='block-externalref']").hide ();
                $wmenu.find("[data-action='unblock-externalref']").show ();

                break;

              case "show-headers":

                $wmenu.find("[data-action='show-headers']").hide ();
                $wmenu.find("[data-action='hide-headers']").show ();

                break;

              case "hide-headers":

                $wmenu.find("[data-action='hide-headers']").hide ();
                $wmenu.find("[data-action='show-headers']").show ();

                break;

              case "list-mode":

                $wmenu.find("li[data-action='list-mode']").hide ();
                $wmenu.find("li[data-action='postit-mode']").show ();

                break;

              case "postit-mode":

                $wmenu.find("li[data-action='postit-mode']").hide ();
                $wmenu.find("li[data-action='list-mode']").show ();

                break;

              // Activate normal view item
              case "zoom-normal-on":

                $menuNormal.removeClass ("disabled");

                if (adminAccess)
                  $menu.find('[data-action="chat"] a,'+
                             '[data-action="filters"] a,'+
                             '[data-action="arrows"] a')
                    .addClass("disabled");
                break;
    
              // Deactivate normal view item
              case "zoom-normal-off":

                $menuNormal.addClass("disabled");

                $('.dropdown-menu li[data-action="zoom-screen"] a')
                  .removeClass ("disabled");
    
                if (adminAccess)
                  $menu.find('[data-action="chat"] a,'+
                             '[data-action="filters"] a,'+
                             '[data-action="arrows"] a')
                    .removeClass("disabled");

                break;
            }
        } 

        if (!H.checkUserVisible ())
        {
          $menu.find('[data-action="chat"] a').addClass ("disabled");
          $wmenu.find('[data-action="share"]').hide ();
        }
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
      const $el = this.element.parent().find (".usersviewcounts:eq(0)"),
            $divider = $el.prev ();

      if (count)
      {
        $divider.show ();
        $el.show().find("span").text (count);

        document.title = `⚡${count} - wopits`;
      }
      else
      {
        $divider.hide ();
        $el.hide ();

        document.title = "wopits";
      }
    },

    // METHOD UIPluginCtrl()
    UIPluginCtrl (selector, plugin, option, value, forceHandle = false)
    {
      const isDisabled = (option == "disabled");

      this.element.find(selector).each (function ()
        {
          if (this.classList.contains ("ui-"+plugin))
          {
            if (forceHandle && isDisabled)
              $(this).find(".ui-"+plugin+"-handle")
                .css ("visibility", value?"hidden":"visible");

            $(this)[plugin] ("option", option, value);
          }
        });
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
      this.element[0].querySelectorAll(".postit.with-plugs").forEach (p =>
        $(p).postit ("repositionPlugs"));
    },

    // METHOD removePostitsPlugs ()
    removePostitsPlugs ()
    {
      this.element[0].querySelectorAll(".postit.with-plugs").forEach (p =>
        $(p).postit ("removePlugs", true));

      this.settings.plugsContainer.remove ();
    },

    // METHOD refreshPostitsPlugs ()
    refreshPostitsPlugs (plugs, partial, applyZoom)
    {
      const $f = S.getCurrent ("filters");
      if ($f.length && $f[0].classList.contains ("plugs-hidden"))
        return;

      const wall = this.element[0];
      let idsNew = {};

      (plugs||[]).forEach (plug =>
        {
          const startId = plug.item_start,
                endId = plug.item_end,
                start0 = wall.querySelector (
                           ".postit[data-id='postit-"+startId+"']"),
                $start = $(start0),
                startPlugin = $start.postit ("getClass"),
                labelName = plug.label || "...";

          idsNew[startId+""+endId] = 1;

          if ((start0.dataset.plugs||"").indexOf (endId) == -1)
          {
            const end = wall.querySelector (
                          ".postit[data-id='postit-"+endId+"']"),
                  newPlug = {
                    startId: startId,
                    endId: endId,
                    label: {
                      name: labelName,
                      top: plug.item_top,
                      left: plug.item_left,
                    },
                    obj: startPlugin.getPlugTemplate ({
                           hide: true,
                           start: start0,
                           end: end,
                           label: labelName
                         })
                  };

            startPlugin.addPlug (newPlug, applyZoom);
          }
          else
            startPlugin.updatePlugLabel ({
              endId: endId,
              label: labelName,
              top: plug.item_top,
              left: plug.item_left
            });
        });

      // Remove obsolete plugs
      if (!partial)
        wall.querySelectorAll(".postit.with-plugs").forEach (postit =>
          {
            $(postit).postit("getSettings")._plugs.forEach (plug =>
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
      this.element[0].querySelectorAll(".postit").forEach (p =>
        $(p).postit ("hidePlugs", true));
    },

    // METHOD showPostitsPlugs ()
    showPostitsPlugs ()
    {
      this.repositionPostitsPlugs ();

      H.waitForDOMUpdate (()=>
        this.element[0].querySelectorAll(".postit").forEach (p =>
          $(p).postit ("showPlugs", true)));
    },

    // METHOD showUserWriting ()
    showUserWriting (user)
    {
      setTimeout (()=>$(".walls a[href='#wall-"+this.settings.id+"']")
        .prepend (`<div class="user-writing main" data-userid="${user.id}"><i class="fas fa-user-edit blink"></i> ${user.name}</div>`), 150);
    },

    // METHOD refresh ()
    refresh (d)
    {
      if (d)
        this._refresh (d);
      else if (this.settings.id)
        H.fetch (
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
            wall = $wall[0],
            wallIsVisible = $wall.is (":visible"),
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
            const $postit = $wall.find (
                    ".postit[data-id='postit-"+d.postit.id+"']"),
                  cell = wall.querySelector (
                    "td[data-id='cell-"+d.postit.cells_id+"']");

            // Rare case, when user have multiple sessions opened
            if (d.action != "insert" && !$postit.length)
              return;

            switch (d.action)
            {
              // Insert postit
              case "insert":

                $(cell).cell ("addPostit", d.postit, true);
                plugin.checkPostitPlugsMenu ();
                break;

              // Update postit
              case "update":

                if (d.isResponse ||
                    cell.classList.contains ("list-mode") ||
                    S.getCurrent("filters").is (":visible"))
                  $postit.postit ("update", d.postit, {id: d.postit.cells_id});
                else
                  $postit.hide ("fade", 250, ()=>
                  {
                    $postit.postit ("update", d.postit, {id:d.postit.cells_id});
                    $postit.show ("fade", 250,
                      ()=> $postit.postit ("repositionPlugs"));
                  });
                break;

              // Remove postit
              case "delete":

                $wall.find("[data-id='postit-"+d.postit.id+"']")
                  .postit ("remove");
                break;
            }

            break;

          // Wall
          case "wall":

            switch (d.action)
            {
              // Col/row has been moved
              case "movecolrow":
                if (!d.isResponse)
                  $wall.find ("th[data-id='header-"+d.header.id+"']")
                    .header ("moveColRow", d.move, true);
                break;

              default:
                __refreshWallBasicProperties (d.wall);
            }

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

        wall.dataset.cols = colsCount;
        wall.dataset.rows = rowsCount;
        wall.dataset.oldwidth = d.width;

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

        wall.querySelectorAll("tbody th").forEach (th =>
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

        wall.querySelectorAll("thead th").forEach (th =>
          {
            const $header = $(th),
                  idx = $header.index ();

            if (idx > 0 && !colsHeadersIds[$header.header ("getId")])
            {
              $wall.find("thead th:eq("+idx+")").remove ();
              wall.querySelectorAll("tbody tr").forEach (tr =>
                {
                  const $cell = $(tr).find ("td:eq("+(idx-1)+")");

                  $cell.cell ("removePostitsPlugs");
                  $cell.remove();
                });
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
              const cellId = cell.id;

              isNewCell = true;

              $cell = $(_getCellTemplate (cell));

              $wall.find("tbody tr:eq("+cell.item_row+")").append ($cell);

              // Init cell
              $cell.cell ({
                id: cellId,
                access: access,
                usersettings: plugin.settings.usersettings["cell-"+cellId]||{},
                wall: $wall,
                wallId: wallId,
                plugsContainer: plugsContainer
              });
            }
            else
            {
              $cell.cell ("update", cell);

              // Remove deleted post-its
              $cell[0].querySelectorAll(".postit").forEach (p =>
                {
                  const $postit = $(p);

                  if (!postitsIds[$postit.postit("getId")])
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

      // Reset arrows tool
      S.getCurrent("arrows").arrows ("reset");

      // Refresh super menu tool
      S.getCurrent("mmenu").mmenu ("refresh");

      if (wallIsVisible && d.postits_plugs)
        setTimeout (() =>
          {
            // Refresh postits relationships
            plugin.refreshPostitsPlugs (
              d.postits_plugs, d.partial && d.partial != "plugs",
              !!S.get("zoom-level"));

            plugin.checkPostitPlugsMenu (!d.isResponse);

          }, 0);
      else
        plugin.repositionPostitsPlugs ();

      // Apply display mode
      setTimeout (()=>
        {
          if (d.reorganize)
            $wall.find("tbody td").cell ("reorganize");

          plugin.refreshCellsToggleDisplayMode ();

          // Re-apply filters
          setTimeout (()=>
            {
              const $f = S.getCurrent("filters");
              if ($f.is (":visible"))
                $f.filters ("apply", {norefresh: true});
            }, 0)

          if (!d.isResponse && !d.partial &&
              S.get ("zoom-level"))
          {
            const zoom = document.querySelector (".tab-content.walls");

            if (zoom &&
                zoom.dataset.zoomlevelorigin &&
                zoom.dataset.zoomtype == "screen")
              plugin.zoom ({type: "screen"});
          }
        }, 0);
    },

    // METHOD refreshCellsToggleDisplayMode ()
    refreshCellsToggleDisplayMode ()
    {
      this.element.find("td.list-mode").each (function ()
        {
          $(this).cell ("toggleDisplayMode", true);
        });

      if (S.get ("zoom-level"))
        this.UIPluginCtrl (".cell-list-mode ul",
                           "sortable", "disabled", true, true);
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
      document.querySelectorAll("table.wall").forEach (wall =>
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
            $chat = S.getCurrent ("chat");

      if ($chat.is (":visible"))
        $chat.chat ("leave");

      // If account popup is opened, do not close it: we are dealing with the
      // "invisible mode" option.
      $(".modal.show:not(#accountPopup)").modal ("hide");

      this.removePostitsPlugs ();

      activeTab.remove ();
      document.getElementById(activeTabId).remove ();

      // No more wall to display
      if (!document.querySelector (".wall"))
      {
        document.querySelector(".nav.walls").style.display = "none";

        this.zoom ({type: "normal", "noalert": true});
        document.getElementById("dropdownView").classList.add ("disabled");
  
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
          const sPlugin = $("#settingsPopup").settings ("getClass");

          this.close ();

          sPlugin.removeWallBackground (this.settings.id);
          sPlugin.removeRecentWall (this.settings.id);
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
      H.request_ws (
        "DELETE",
        "wall/"+this.settings.id+"/row/"+rowIdx,
        {wall: {width: Math.trunc (this.element.outerWidth ())}});
    },

    // METHOD deleteCol ()
    deleteCol (idx)
    {
      const $wall = this.element;

      H.request_ws (
        "DELETE",
        "wall/"+this.settings.id+"/col/"+(idx-1),
        {
          wall: {width: Math.trunc ($wall.outerWidth()-1)},
          width: Math.trunc ($wall.find("thead tr th:eq("+idx+")").outerWidth())
        });
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

      H.fetch (
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
              if (args.lastWall && args.lastWall == 1)
                $("#settingsPopup").settings ("saveOpenedWalls");
              else
                S.set ("save-opened-walls", true);
            }

            return H.displayMsg ({
                     type: "warning",
                     msg: "<?=_("Some walls are no longer available!")?>"
                   });
          }

          if (d.error_msg)
            return H.displayMsg ({type: "warning", msg: d.error_msg});

          if ($popup)
            $popup.modal ("hide");

          $(".tab-content.walls").append (`<div class="tab-pane" id="wall-${d.id}"><ul class="wall-menu"></ul><div class="toolbox chat"></div><div class="toolbox filters"></div><div class="arrows"></div><table class="wall" data-id="wall-${d.id}" data-access="${d.access}"></table></div>`);

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

          if (args.lastWall)
            d.lastWall = args.lastWall;

          $wallDiv.find(".wall").wall (d);
          $wallDiv.find(".chat").chat ({wallId: d.id});
          $wallDiv.find(".filters").filters ();
          $wallDiv.find(".arrows").arrows ();

          if (!args.restoring || wpt_userData.settings.activeWall == d.id)
          {
            S.set ("newWall", true);
            $tabs.find('a[href="#wall-'+d.id+'"]').tab ("show");
            S.unset ("newWall");
          }

          document.querySelector(".nav.walls").style.display = "block";
          document.getElementById("dropdownView").classList.remove ("disabled");

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
            H.fetch (
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

                $("<div/>").wall ("open", {
                  lastWall: 1,
                  wallId: d.wallId
                });

                H.displayMsg ({
                  type: "success",
                  msg: "<?=_("The wall has been successfully cloned")?>"
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
          msg: "<?=_("An error occurred while exporting wall data!")?>"
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

        S.set ("fromDirectURL", !!args);

        for (let i = wallsLen - 1; i >= 0; i--)
        {
          const fromDirectURL = type && walls[i] == wallId;

          this.open ({
            lastWall: (i == 0) ? wallsLen : null,
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
          lastWall: 1,
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
      H.fetch (
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
          H.loadPopup ("owall", {
            cb: ($p)=>
            {
              $p.owall ("reset");
              $p.owall ("displayWalls");
              $p.owall ("controlFiltersButtons");
            }
          });
        });
    },

    // METHOD openNamePopup ()
    openNamePopup ()
    {
      H.loadPopup ("createWall", {
        init: ($p)=>
          $p.find("#w-grid")
            // EVENT change on wall dimension in wall creation popup
            .on("change", function ()
            {
              const btn = this;

              $p.find("span.required").remove ();
              $p.find(".cols-rows input").val (3);
              $p.find(".width-height input").val ("");
              $p.find(".cols-rows,.width-height").hide ();

              if (btn.checked)
                $(this).parent().removeClass ("disabled");
              else
                $(this).parent().addClass ("disabled");

              $p.find(btn.checked?".cols-rows":".width-height").show ("fade");
            }),
        cb: ($p)=> $p[0].dataset.noclosure = true
      });
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
          H.loadPopup ("wallUsersview", {
            open: false,
            init: ($p)=>
            {
              $p
                // EVENT click on username in wall users popup
                .on("click",".list-group a",function (e)
                {
                  const a = this;

                  H.loadPopup ("userView", {
                    open: false,
                    cb: ($p)=>
                    {
                      const $userPicture = $p.find (".user-picture"),
                            $about = $p.find (".about");

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

                      $p.find(".modal-title span").text (a.dataset.title);
                      $p.find(".name dd").text (a.dataset.title);

                      if (a.dataset.about)
                      {
                        $about.find("dd").html (H.nl2br (a.dataset.about));
                        $about.show ();
                      }
                      else
                        $about.hide ();

                      H.openModal ($p);
                    }
                  });
                });
            },
            cb: ($p)=>
            {
              const userId = wpt_userData.id;

              let html = "";
              d.list.forEach (item =>
              {
                if (item.id != userId)
                  html += `<a href="#" data-id="${item.id}" class="list-group-item list-group-item-action" data-title="${H.htmlEscape(item.fullname)}" data-picture="${item.picture||""}" data-about="${H.htmlEscape(item.about||"")}">${H.getAccessIcon(item.access)} ${item.fullname} (${item.username})</a>`;
              });
              $p.find(".list-group").html (html);

              H.openModal ($p);
            }
          });
        }
      );
    },

    // METHOD openPropertiesPopup ()
    openPropertiesPopup (args = {})
    {
      args.wall = this.element;

      if (H.checkAccess ("<?=WPT_WRIGHTS_ADMIN?>"))
        this.edit (() =>
          {
            H.loadPopup ("wprop",
              {
                open: false,
                cb: ($p)=> $p.wprop ("open", args)
              });
          });
      else
        H.loadPopup ("wprop",
          {
            open: false,
            cb: ($p)=> $p.wprop ("open", args)
          });
    },

    // METHOD saveProperties ()
    saveProperties ()
    {
      const plugin = this,
            $wall = plugin.element,
            $popup = $("#wpropPopup"),
            Form = new Wpt_accountForms (),
            $inputs = $popup.find("input:visible"),
            name = H.noHTML ($popup.find(".name input").val ()),
            description =
              H.noHTML ($popup.find(".description textarea").val ());

      $popup[0].dataset.noclosure = true;

      if (Form.checkRequired ($inputs) && Form.validForm ($inputs))
      {
        const oldName = plugin.getName (),
              $cell = $wall.find ("td"),
              oldW = $cell.outerWidth ();

        plugin.setName (name);
        plugin.setDescription (description);

        plugin.unedit (
          () =>
          {
            $popup[0].dataset.uneditdone = 1;
            $popup.modal ("hide");
          },
          () =>
          {
            plugin.setName (oldName);
            //FIXME
            plugin.edit ();
          });

        if ($inputs[1] && $inputs[1].value != oldW ||
            $inputs[2] && $inputs[2].value != $cell.outerHeight ())
        {
          const w = Number ($inputs[1].value) + 1,
                h = Number ($inputs[2].value),
                cellPlugin = $cell.cell ("getClass"),
                __resize = (args)=>
                {
                  $wall.find("thead th:eq(1),td")
                    .css ("width", args.newW);
                  $wall.find(".ui-resizable-s")
                    .css ("width", args.newW + 2);

                  if (args.newH)
                  {
                    $wall.find("tbody th,td")
                      .css ("height", args.newH);
                    $wall.find(".ui-resizable-e")
                      .css ("height", args.newH+2);
                  }

                  plugin.fixSize (args.oldW, args.newW);
                };

          __resize ({newW: w, oldW: oldW, newH: h});
          if ($wall.find("td").outerWidth () != w)
            __resize ({newW: $wall.find("td").outerWidth (), oldW: w});

          cellPlugin.edit ();
          cellPlugin.reorganize ();
          cellPlugin.unedit ();
        }
      }
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

    // METHOD isShared ()
    isShared ()
    {
      return this.element[0].dataset.shared;
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

      if (this.isShared ())
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
      const $f = S.getCurrent("filters");
      if ($f.is (":visible"))
        $f.filters ("apply", {norefresh: true});

      H.fetch (
        "POST",
        "user/wall/"+this.settings.id+"/displaymode",
        {value: type});
    },

    // METHOD zoom ()
    zoom (args)
    {
      const $zoom = $(".tab-content.walls"),
            zoom0 = $zoom[0],
            $wall = this.element,
            wall = $wall[0],
            from = args.from,
            type = args.type,
            noalert = !!args.noalert,
            zoomStep = (!!args.step) ? args.step : 0.2,
            writeAccess = this.canWrite ();

      if (!args.step)
      {
        wall.style.top = 0;
        wall.style.left = "15px";
      }

      if (type == "screen")
        return this.screen ();

      let level = zoom0.style.transform;
      level = (!level||level=="none")?1:Number(level.match(/[0-9\.]+/)[0]);

      if (!zoom0.dataset.zoomlevelorigin)
      {
        if (!S.get ("old-width"))
          S.set ("old-styles", {
            width: zoom0.style.width,
            transform: zoom0.style.transform
          });

        if (writeAccess && !noalert)
          H.displayMsg ({
            type: "warning",
            title: "<?=_("Zoom enabled")?>",
            msg: "<?=_("Some features are not available in this mode")?>"
          });

        zoom0.dataset.zoomlevelorigin = level;

        zoom0.style.width = "30000px";

        zoom0.querySelectorAll("th").forEach (th =>
          {
            th.style.pointerEvents = "none";

            if (writeAccess)
              th.style.opacity = .6;
          });

        // Deactivate some features
        if (wall.classList.contains ("ui-draggable"))
          $wall.draggable ("disable");
        $wall.find(".cell-menu").hide ();
        this.UIPluginCtrl (".cell-list-mode ul",
                           "sortable", "disabled", true, true);
        this.UIPluginCtrl ("td,.postit",
                           "resizable", "disabled", true);
        this.UIPluginCtrl (".wall,.postit",
                           "draggable", "disabled", true);
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

      if (level <= 0)
        return H.displayMsg ({
          type: "warning",
          title: "<?=_("Zoom")?>",
          msg: "<?=_("The minimum zoom has been reached!")?>"
        });

      S.set ("zoom-level", level);

      if (from != "screen" && level == zoom0.dataset.zoomlevelorigin)
      {
        S.unset ("zoom-level");

        this.hidePostitsPlugs ();

        setTimeout (() => $("#normal-display-btn").hide(), 150);

        zoom0.removeAttribute ("data-zoomtype");
        zoom0.removeAttribute ("data-zoomlevelorigin");

        this.menu ({from: "display", type: "zoom-normal-off"});

        if (writeAccess && !noalert)
          H.displayMsg ({
            type: "info",
            title: "<?=_("Zoom disabled")?>",
            msg: "<?=_("All features are available again")?>"
          });

        zoom0.style.width = S.get ("old-styles").width;
        zoom0.style.transform = S.get ("old-styles").transform;
        S.unset ("old-styles");

        zoom0.querySelectorAll("th").forEach (th =>
          {
            th.style.pointerEvents = "auto";

            if (writeAccess)
              th.style.opacity = 1;
          });

        // Reavtivate some previously deactivated features
        if (wall.classList.contains ("ui-draggable"))
          $wall.draggable ("enable");
        $wall.find(".cell-menu").show ();
        this.UIPluginCtrl (".cell-list-mode ul",
                           "sortable", "disabled", false, true);
        this.UIPluginCtrl ("td,.postit",
                           "resizable", "disabled", false);
        this.UIPluginCtrl (".wall,.postit",
                           "draggable", "disabled", false);

        $("#walls")
          .scrollLeft(0)
          .scrollTop (0);

        $("<div/>").postit ("applyZoom");
      }
      else
      {
        if (from != "screen")
        {
          this.hidePostitsPlugs ();

          $("<div/>").postit ("applyZoom");
          $("#normal-display-btn").show ();
        }

        zoom0.style.transformOrigin = "top left";
        zoom0.style.transform = "scale("+level+")";

        $("#walls").scrollLeft (((30000*level)/2-window.innerWidth/2)+20);
      }
    },

    // METHOD screen ()
    screen ()
    {
      const step = .005,
            wall = this.element[0],
            walls = S.getCurrent("walls")[0];
      let position = wall.getBoundingClientRect ();

      this.hidePostitsPlugs ();

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

      $("#normal-display-btn").show ();
      $('.dropdown-menu li[data-action="zoom-screen"] a').addClass ("disabled");

      $("#walls").scrollLeft (
        ((30000*S.get("zoom-level"))/2-window.innerWidth/2)+20);

      $("<div/>").postit ("applyZoom");
    },

    // METHOD edit ()
    edit (success_cb, error_cb, todelete = false)
    {
      _originalObject = this.serialize ();

      if (!this.isShared ())
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
          if (!this.isShared ())
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

        this.element[0].querySelectorAll(".postit").forEach (p =>
          $(p).postit (type+"ExternalRef"));

        H.fetch (
          "POST",
          "user/wall/"+this.settings.id+"/displayexternalref",
          {value: val});
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

    // METHOD displayHeaders ()
    displayHeaders (v)
    {
      const update = (v !== undefined),
            val = update ? v : this.settings.displayheaders,
            type = (val == 1) ? "show" : "hide";

      if (val == 1)
        this.element.find("th").show ();
      else
        this.element.find("th").hide ();

      if (update)
      {
        this.settings.displayheaders = val;
        this.repositionPostitsPlugs ();

        H.fetch (
          "POST",
          "user/wall/"+this.settings.id+"/displayheaders",
          {value: val});
      }

      if (this.element.is (":visible"))
        this.menu ({from: "display", type: type+"-headers"});

      return val;
    },

  });

  /////////////////////////// AT LOAD INIT //////////////////////////////

  $(function ()
    {
      if (!H.isLoginPage ())
        setTimeout (()=>{

        $("body").prepend (`<div id="popup-loader" class="layer"><div id="loader"><div class="progress"></div><i class="fas fa-cog fa-spin fa-lg"></i> <span><?=_("Please wait")?>...</span> <button type="button" class="btn btn-xs btn-secondary"><?=_("Stop")?></button></div></div>`);

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
                $settings.settings ("openThemeChooser"), 1000);

            H.enableTooltips ($("body"));
          });

        H.fixMenuHeight ();
        H.fixMainHeight ();

        // Arrows plugin is useless on desktop
        if (H.haveMouse ())
          $("#main-menu").addClass ("noarrows");

        $("body").prepend ($(`<div id="normal-display-btn"><i class="fas fa-crosshairs fa-2x"></i> <span><?=_("Back to standard view")?></span></div>`)
          // EVENT click on back to standard view button
          .on("click", ()=>S.getCurrent("wall").wall("zoom",{type:"normal"}))
          .on("shown.bs.popover", function (e)
          {
            // Reduce the popover z-index
            document.getElementById(this.getAttribute ("aria-describedby"))
              .style.zIndex = 1031;
          }));

        // EVENT click on main menu account button
        $("#account").on("click", function (e)
        {
          H.closeMainMenu ();
          H.loadPopup ("account");
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
                      H.fetchUpload (
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
  
                          $("<div/>").wall ("open", {
                            lastWall: 1,
                            wallId: d.wallId
                          });
  
                          H.displayMsg ({
                            type: "success",
                            msg: "<?=_("The wall has been successfully imported")?>"
                          });
                        });
                    }
                  });
              }
            }).appendTo ("body");

        $(document)
          // EVENT CLICK on main menu items
          .on("click", ".navbar.wopits a,"+
                       ".wall-menu li,"+
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

              case "show-users":

                $wall.wall ("displayWallUsersview");

                break;

              case "chat":

                var input = $(this).find("input")[0];

                // Manage checkbox
                if (e.target.tagName != "INPUT")
                  input.checked = !input.checked;

                S.getCurrent("chat").chat ("toggle");

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

                H.loadPopup ("about");

                break;

              case "user-guide":

                H.loadPopup ("userGuide");

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

              case "clone":

                $wall.wall ("clone");

                break;

              case "export":

                $wall.wall ("export");

                break;

              case "import":

                $("<div/>").wall ("import");

                break;

/*
              case "view-properties":

                $wall.wall ("openPropertiesPopup");
*/

                break;
            }
          })}, 0);
      else
        $('[data-action="about"]')
        // EVENT CLICK on about button in the login page
        .on("click", function ()
        {
          H.openModal ($("#aboutPopup"));
        });
  });

<?php echo $Plugin->getFooter ()?>
