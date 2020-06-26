<?php
  require_once (__DIR__.'/../class/Wpt_jQueryPlugins.php');
  $Plugin = new Wpt_jQueryPlugins ('wall');
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

  /////////////////////////// PUBLIC METHODS ////////////////////////////

  // Inherit from Wpt_accountForms
  Plugin.prototype = Object.create(Wpt_accountForms.prototype);
  Object.assign (Plugin.prototype,
  {
    // METHOD init ()
    init: function ()
    {
      const plugin = this,
            $wall = plugin.element,
            settings = plugin.settings,
            wallId = settings.id,
            access = settings.access,
            rows = [];

      if (settings.restoring)
        $wall[0].dataset.restoring = 1;

      plugin.setName (settings.name, true);

      // Prepare rows array for display
      for (let i = 0, iLen = settings.cells.length; i < iLen; i++)
      {
        const cell = settings.cells[i],
              rowIdx = cell.row;

        if (!rows[rowIdx])
          rows[rowIdx] = [];

        rows[rowIdx][cell.col] = cell;
      }

      if (settings.shared)
        $wall[0].dataset.shared = 1;

      $wall
        .hide()
        .css({
          width: (settings.width) ? settings.width : "",
          "background-color": (settings["background-color"]) ?
                                settings["background-color"] : "auto"
        })
        .draggable({
          //FIXME "distance" is deprecated -> is there any alternative?
          distance: 10,
          cursor: "grab",
          start: function ()
            {
              wpt_sharer.set ("wall-dragging", true);
              plugin.hidePostitsPlugs ();
            },
          stop: function ()
            {
              const $arrows = wpt_sharer.getCurrent ("arrows");

              // Fix arrows tool appearence
              if ($arrows.is (":visible"))
                $arrows.wpt_arrows ("update");

              plugin.showPostitsPlugs ();
              wpt_sharer.unset ("wall-dragging", true);
            }
        })
        .html ("<thead><tr><th>&nbsp;</th></tr></thead><tbody></tbody>");

      wpt_waitForDOMUpdate (() =>
        {
          // Create wall columns headers
          const hcols = settings.headers.cols;
          for (let i = 0, iLen = hcols.length; i < iLen; i++)
          {
            const header = hcols[i],
                  $th = $("<th/>");

            $wall.find("thead tr").append ($th);
            $th.wpt_header ({
              access: access,
              type: "col",
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
                $cell.wpt_cell ("addPostit", cell.postits[k], true);
              }
            }
          }

          $("#welcome").hide ();

          $wall.show ("fade");

          $wall[0].dataset.cols = hcols.length;
          $wall[0].dataset.rows = hrows.length;

          plugin.setName (settings.name);
          plugin.setDescription (settings.description);

          $(window).trigger ("resize");

          if (settings.restoring)
          {
            delete settings.restoring;
            $wall[0].removeAttribute ("data-restoring");
          }

          // Set wall users view count if needed
          const viewcount =
            wpt_WebSocket.popResponse ("viewcount-wall-"+wallId);
          if (viewcount !== undefined)
            plugin.refreshUsersview (viewcount); 

          setTimeout (()=>
          {
            // Refresh postits relations
            plugin.refreshPostitsPlugs (settings.postits_plugs);

          }, 0);

        });
    },

    // METHOD getId ()
    getId: function ()
    {
      return this.settings.id;
    },

    menu: function (args)
    {
      const $menu = $("#main-menu"),
            $wall = wpt_sharer.getCurrent ("wall"),
            $menuNormal =
              $menu.find('.dropdown-menu li[data-action="zoom-normal"] a'),
            adminAccess = wpt_checkAccess ("<?=WPT_RIGHTS['walls']['admin']?>");

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
    },

    // METHOD closeAllMenus ()
    closeAllMenus: function ()
    {
      const $wall = this.element;

      // Postits menu
      $wall.find("td div.postit-menu.on").each (function ()
        {
          $(this).parent().wpt_postit ("closeMenu");
        });
    
      // Col/row headers menu
      $wall.find("th div[class*='-menu'].on").each (function ()
        {
          $(this).parent().find(".btn-menu").trigger ("click");
        });
    },

    // METHOD refreshUsersview ()
    refreshUsersview: function (count)
    {
      this.element.find("thead th:eq(0)").html ((count) ?
        `<div class="usersviewcounts"><i class="fas fa-user-friends"></i> <span class="wpt-badge">${count}</span></div>` : "&nbsp;");
    },

    // METHOD checkPostitsPlugsMenu ()
    checkPostitsPlugsMenu: function ()
    {
      this.element.find(".postit").each (function ()
        {
          $(this).wpt_postit ("checkPlugsMenu");
        });
    },

    // METHOD repositionPostitsPlugs ()
    repositionPostitsPlugs: function ()
    {
      this.element.find(".postit.with-plugs").each (function ()
        {
          $(this).wpt_postit ("repositionPlugs");
        });
    },

    // METHOD refreshPostitsPlugs ()
    //FIXME //TODO Optimize
    refreshPostitsPlugs: function (plugs, partial = false)
    {
      const $wall = this.element,
            hidePlugs =
              wpt_sharer.getCurrent("filters").hasClass ("plugs-hidden");
      let idsNew = {};

      (plugs||[]).forEach ((plug) =>
        {
          const startId = plug.start,
                endId = plug.end,
                $start = $wall.find(".postit[data-id='postit-"+startId+"']"),
                $end = $wall.find(".postit[data-id='postit-"+endId+"']"),
                label = plug.label || "...";

          idsNew[startId+""+endId] = 1;

          if (($start[0].dataset.plugs||"").indexOf (endId) == -1)
          {
            const newPlug = {
              startId: startId,
              endId: endId,
              label: label,
              obj: $start.wpt_postit ("getPlugTemplate",
                     $start[0], $end[0], label),
            };

            $start.wpt_postit ("addPlug", newPlug);

            if (hidePlugs)
              $start.wpt_postit ("hidePlugs");
          }
          else
            $start.wpt_postit ("updatePlugLabel", {
              endId: endId,
              label: label
            });
        });

        // Remove obsolete plugs
        if (!partial)
          $wall.find(".postit.with-plugs").each (function ()
          {
            $(this).wpt_postit("getSettings")._plugs.forEach ((plug)=>
              {
                if (!idsNew[plug.startId+""+plug.endId])
                  $wall.find(".postit[data-id='postit-"+plug.endId+"']")
                    .wpt_postit("removePlug", plug, true);
              });
          });
    },

    // METHOD removePostitsPlugs ()
    removePostitsPlugs: function ()
    {
      this.element.find(".postit.with-plugs").each (function ()
        {
          $(this).wpt_postit ("removePlugs", true);
        });
    },

    // METHOD hidePostitsPlugs ()
    hidePostitsPlugs: function ()
    {
      this.element.find(".postit").each (function ()
        {
          $(this).wpt_postit ("hidePlugs");
        });
    },

    // METHOD showPostitsPlugs ()
    showPostitsPlugs: function ()
    {
      this.repositionPostitsPlugs ();

      wpt_waitForDOMUpdate (()=>
        this.element.find(".postit").each (function ()
          {
            $(this).wpt_postit ("showPlugs");
          }));
    },

    // METHOD refresh ()
    refresh: function (data)
    {
      const plugin = this;

      function __refresh (d)
      {
        if (d.removed)
        { 
          wpt_displayMsg ({type: "warning", msg: d.removed});
          
          plugin.close ();
        }
        else
          plugin._refresh (d);
      }

      if (data)
        __refresh (data);
      else
        wpt_request_ajax (
          "GET",
          "wall/"+plugin.settings.id,
          null,
          // success cb
          __refresh);
    },

    // METHOD _refresh ()
    _refresh: function (d)
    {
      const plugin = this,
            $wall = plugin.element,
            $filters = wpt_sharer.getCurrent ("filters"),
            $arrows = wpt_sharer.getCurrent ("arrows");

      function __refreshWallBasicProperties (d)
      {
        if (d.shared)
          $wall[0].dataset.shared = 1;
        else
          $wall[0].removeAttribute ("data-shared");

        plugin.setName (d.name);
        plugin.setDescription (d.description);
      }

      // Partial wall update
      if (d.partial)
      {
        switch (d.partial)
        {
          // Postits
          case "postit":

            const $postit = $wall.find("[data-id='postit-"+d.postit.id+"']");
            switch (d.action)
            {
              // Insert postit
              case "insert":
                $("td[data-id='cell-"+d.postit.cells_id+"']")
                  .wpt_cell ("addPostit", d.postit, true);
                break;

              // Update postit
              case "update":
                $postit.wpt_postit (
                  "update", d.postit, {id: d.postit.cells_id});
                break;

              // Remove postit
              case "delete":
                $postit.wpt_postit ("removePlugs", true);
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
              rows = [];

        _refreshing = true;

        $wall[0].dataset.cols = colsCount;
        $wall[0].dataset.rows = rowsCount;
        $wall[0].dataset.oldwidth = d.width;

        __refreshWallBasicProperties (d);

        //FIXME
        $wall.css ("width", d.width + 1);

        for (let i = 0; i < colsCount; i++)
        {
          const header = d.headers.cols[i],
                $header =
                  $wall.find('thead th[data-id="header-'+header.id+'"]');

          if (!$header.length)
          {
            $wall.find("thead tr").append ("<th></th>");
            $wall.find("thead tr th:last-child").wpt_header ({
              type: "col",
              id: header.id,
              wall: $wall,
              wallId: wallId,
              title: header.title,
              picture: header.picture
            });
          }
          else
            $header.wpt_header ("update", header);
        }

        // Remove deleted rows
        for (let i = 0; i < rowsCount; i++)
          rowsHeadersIds[d.headers.rows[i].id] = true;

        $wall.find("tbody th").each (function ()
          {
            const $header = $(this);

            if (!rowsHeadersIds[$header.wpt_header ("getId")])
            {
              const $cell =
                $wall.find("tbody tr:eq("+$header.parent().index()+")");

              $cell.wpt_cell ("removePostitsPlugs");
              $cell.remove ();
            }
          });

        // Remove deleted columns
        for (let i = 0; i < colsCount; i++)
          colsHeadersIds[d.headers.cols[i].id] = true;

        $wall.find("thead th").each (function ()
          {
            const $header = $(this),
                  idx = $header.index ();

            if (idx > 0)
            {
              if (!colsHeadersIds[$header.wpt_header ("getId")])
              {
                $wall.find("thead th:eq("+idx+")").remove ();
                $wall.find("tbody tr").each(function()
                  {
                    const $cell = $(this).find("td:eq("+(idx-1)+")");

                    $cell.wpt_cell ("removePostitsPlugs");
                    $cell.remove();
                  });
              }
            }
          });

        for (let i = 0, iLen = d.cells.length; i < iLen; i++)
        {
          const cell = d.cells[i],
                irow = cell.row;

          // Get all postits ids for this cell
          for (let j = 0, jLen = cell.postits.length; j < jLen; j++)
            postitsIds[cell.postits[j].id] = true;

          if (rows[irow] == undefined)
            rows[irow] = [];

          rows[irow][cell.col] = cell;
        }

        for (let i = 0, iLen = rows.length; i < iLen; i++)
        {
          const row = rows[i],
                header = d.headers.rows[i];

          if (!$wall.find('td[data-id="cell-'+row[0].id+'"]').length)
            plugin.addRow (header, row);
          else
          {
            $wall.find('tbody th[data-id="header-'+header.id+'"]')
              .wpt_header ("update", header);
          }

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

              $wall.find("tbody tr:eq("+cell.row+")").append ($cell);

              // Init cell
              $cell.wpt_cell ({
                id: cell.id,
                access: access,
                wall: $wall,
                wallId: wallId
              });
            }
            else
            {
              $cell.wpt_cell ("update", cell);

              // Remove deleted post-its
              $cell.find(".postit").each (function ()
                {
                  const $postit = $(this);

                  if (!postitsIds[$postit.wpt_postit ("getId")])
                  {
                    $postit.wpt_postit ("removePlugs", true);
                    $postit.remove ();
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
                $cell.wpt_cell ("addPostit", postit, true);
              }
              // else update it
              else
              {
                if (d.ignoreResize)
                  postit.ignoreResize = true;

                $postit.wpt_postit (
                  "update", postit, {id: cell.id, obj: $cell});
              }
            }
          }
        }

        _refreshing = false;
        plugin.fixSize ();
      }

      // If filters tool is visible
      if ($filters.is (":visible"))
        $filters.wpt_filters ("apply");

      // If arrows tool is visible
      if ($arrows.is (":visible"))
        $arrows.wpt_arrows ("reset");

      if (d.postits_plugs)
        setTimeout (() =>
          {
            // Refresh postits relations
            plugin.refreshPostitsPlugs (
              d.postits_plugs, d.partial && d.partial != "plugs");
            plugin.checkPostitsPlugsMenu ();

          }, 0);
      else
        plugin.repositionPostitsPlugs ();

      // Replay postits search
      setTimeout (() =>
        $("#postitsSearchPopup").wpt_postitsSearch("replay"), 250);
    },

    // METHOD openCloseAllWallsPopup ()
    openCloseAllWallsPopup: function ()
    {
      wpt_openConfirmPopup ({
        type: "close-walls",
        icon: "times",
        content: `<?=_("Close the walls?")?>`,
        cb_ok: () => this.closeAllWalls ()
      });
    },

    // METHOD closeAllWalls ()
    closeAllWalls: function ()
    {
      // Tell the other methods that we are massively closing the walls
      wpt_sharer.set ("closingAll", 1);
      wpt_sharer.getCurrent("walls").find("table.wall").each (function ()
        {
          $(this).wpt_wall ("close");
        });
      wpt_sharer.unset ("closingAll");

      $("#settingsPopup").wpt_settings ("saveOpenedWalls");
    },

    // METHOD close ()
    close: function ()
    {
      const activeTabId = "wall-"+this.settings.id,
            $activeTab = $('a[href="#'+activeTabId+'"]'),
            newActiveTabId = ($activeTab.prev().length) ?
              $activeTab.prev().attr("href") :
                ($activeTab.next().length) ?
                  $activeTab.next().attr("href") : null,
            $chatroom = wpt_sharer.getCurrent ("chatroom");

      if ($chatroom.is (":visible"))
        $chatroom.wpt_chatroom ("leave");

      $(".modal.show").modal ("hide");

      this.removePostitsPlugs ();

      $activeTab.remove ();
      $("#"+activeTabId).remove ();

      // No more wall to display
      if (!$(".wall").length)
      {
        $(".nav.walls").hide ();

        this.zoom ({type: "normal", "noalert": true});
        $("#dropdownView,#dropdownEdit").addClass ("disabled");
  
        this.menu ({from: "wall", type: "no-wall"});

        $("#welcome").show ("fade");
      }
      // Active another tabs after deletion
      else
        $(".nav-tabs.walls").find('a[href="'+newActiveTabId+'"]').tab ("show");

      // If we are not massively closing all walls
      if (!wpt_sharer.get("closingAll"))
        $("#settingsPopup").wpt_settings ("saveOpenedWalls");

      //FIXME
      setTimeout (() => wpt_sharer.reset (), 250);
    },

    // METHOD openDeletePopup ()
    openDeletePopup: function ()
    {
      this.edit (() =>
        {
          wpt_openConfirmPopup ({
            type: "delete-wall",
            icon: "trash",
            content: `<?=_("Delete this wall?")?>`,
            cb_ok: () => this.delete (),
            cb_cancel: () => this.unedit ()
          });
        }, null, true);
    },

    // METHOD delete ()
    delete: function ()
    {
      this.element[0].dataset.todelete = true;

      //FIXME 2x
      this.unedit (() =>
        {
          this.close ();
          $("#settingsPopup")
            .wpt_settings ("removeWallBackground", this.settings.id);
        });
    },

    // METHOD createColRow ()
    createColRow: function (type)
    {
      const wall = this.element[0];

      if (Number (wall.dataset.rows) *
            Number (wall.dataset.cols) >= <?=WPT_MAX_CELLS?>)
        return wpt_infoPopup ("<?=_("For performance reasons, a wall cannot contain more than %s cells")?>.".replace("%s", <?=WPT_MAX_CELLS?>));

      wpt_request_ws (
        "PUT",
        "wall/"+this.settings.id+"/"+type,
        null,
        () =>
          {
            wpt_sharer.getCurrent("walls")
              [(type == "col")?"scrollLeft":"scrollTop"](30000);
            wall.dataset[type+"s"] = Number (wall.dataset[type+"s"]) + 1;
          });
    },

    // METHOD addRow ()
    addRow: function (header, row)
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
      $row.find("th:eq(0)").wpt_header ({
        access: plugin.settings.access,
        type: "row",
        id: header.id,
        wall: $wall,
        wallId: wallId,
        title: header.title,
        picture: header.picture
      });

      // Init cells
      $row.find("td").each (function ()
        {
          $(this).wpt_cell ({
            id: this.dataset.id.substring (5),
            access: plugin.settings.access,
            wall: $wall,
            wallId: wallId
          });
        });

      plugin.fixSize ();
    },

    // METHOD deleteRow ()
    deleteRow: function (rowIdx)
    {
      const $wall = this.element,
            $tr = $wall.find("tr:eq("+(rowIdx+1)+")");

      this.closeAllMenus ();

      $tr.find("td").wpt_cell ("removePostitsPlugs");

      wpt_headerRemoveContentKeepingWallSize ({
        oldW: $tr.find("th").outerWidth (),
        cb: () => $tr.remove ()
      });

      wpt_request_ws (
        "DELETE",
        "wall/"+this.settings.id+"/row/"+rowIdx,
        {wall: {width: Math.trunc($wall.outerWidth ())}},
        () => $wall[0].dataset.rows = Number ($wall[0].dataset.rows) - 1);
    },

    // METHOD deleteCol ()
    deleteCol: function (idx)
    {
      const plugin = this,
            $wall = plugin.element,
            $header = $wall.find("thead tr th:eq("+idx+")"),
            oldW = Math.trunc($wall.outerWidth () - 1),
            newW = Math.trunc(oldW - $header.outerWidth ()),
            data = {
              wall: {width: oldW},
              width: Math.trunc($header.outerWidth ())
            };

      plugin.closeAllMenus ();

     $header.remove ();
     $wall.find("tbody tr").each(function()
        {
          const $cell = $(this).find("td:eq("+(idx - 1)+")");

          $cell.wpt_cell ("removePostitsPlugs");
          $cell.remove ();
        });

      $wall.find("tbody th").each(function()
        {
          $(this).css ("width", 1);
        });

      plugin.fixSize (oldW, newW); 

      wpt_request_ws (
        "DELETE",
        "wall/"+plugin.settings.id+"/col/"+(idx - 1),
        data,
        () => $wall[0].dataset.cols = Number ($wall[0].dataset.cols) - 1);
    },

    // METHOD addNew ()
    addNew: function (args, $popup)
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

      wpt_sharer.reset ();

      if (args.restoring)
        $tabs.prepend (`<a class="nav-item nav-link" href="#wall-${args.wallId}" data-toggle="tab"><span class="icon"></span><span class="val"></span></a>`);

      wpt_request_ajax (
        method,
        service,
        data,
        // success cb
        (d) =>
        {
          if (args.restoring)
          {
            d.restoring = 1;

            // A wall in user session has been removed
            if (d.removed)
            {
              $tabs.find("a[href='#wall-"+args.wallId+"']").remove ();

              if ($tabs.find(".nav-item").length)
                $tabs.find(".nav-item:first-child").tab ("show")

              //FIXME Wait for ws server connection...
              setTimeout (
                ()=>$("#settingsPopup").wpt_settings ("saveOpenedWalls"), 500);

              return wpt_displayMsg ({type: "warning", msg: d.removed});
            }
          }

          if (d.error_msg)
            return wpt_displayMsg ({type: "warning", msg: d.error_msg});

          if ($popup)
            $popup.modal ("hide");

          $(".tab-content.walls").append (`<div class="tab-pane" id="wall-${d.id}"><div class="toolbox chatroom"></div><div class="toolbox filters"></div><div class="arrows"></div><table class="wall" data-id="wall-${d.id}" data-access="${d.access}"></table></div>`);

          if (!args.restoring)
            $tabs.prepend (`<a class="nav-item nav-link" href="#wall-${d.id}" data-toggle="tab"><span class="icon"></span><span class="val"></span></a>`);

          $tabs.find('a[href="#wall-'+d.id+'"]').prepend (
            $(`<button type="button" class="close"><span class="close">&times;</span></button>`)
             .on("click",function()
             {
               wpt_openConfirmPopover ({
                 item: $(this),
                 placement: "left",
                 title: `<i class="fas fa-times fa-fw"></i> <?=_("Close")?>`,
                 content: "<?=_("Close this wall?")?>",
                 cb_ok: () => wpt_sharer.getCurrent("wall").wpt_wall ("close")
               });
             }));

          d["background-color"] =
            $("#settingsPopup").wpt_settings ("get", "wall-background", d.id);

          const $wallDiv = $("#wall-"+d.id);

          $wallDiv.find(".wall").wpt_wall (d);
          $wallDiv.find(".chatroom").wpt_chatroom ({wallId: d.id});
          $wallDiv.find(".filters").wpt_filters ();
          $wallDiv.find(".arrows").wpt_arrows ();

          if (!args.restoring || wpt_userData.settings.activeWall == d.id)
          {
            wpt_sharer.set ("newWall", true);
            $tabs.find('a[href="#wall-'+d.id+'"]').tab ("show");
            wpt_sharer.unset ("newWall");
          }

          $(".nav.walls").show ();

          $("#dropdownView,#dropdownEdit").removeClass ("disabled");

          plugin.menu ({from: "wall", type: "have-wall"});

          if (!args.restoring)
            $("#settingsPopup").wpt_settings ("saveOpenedWalls");

        });
    },

    // METHOD open ()
    open: function (wallId, restoring)
    {
      this.addNew ({
        load: true,
        restoring: restoring,
        wallId: wallId
      });
    },

    // METHOD clone ()
    clone: function ()
    {
      wpt_openConfirmPopup ({
        type: "clone-wall",
        icon: "clone",
        content: `<?=_("Depending on its content, cloning a wall can take time.<br>Do you confirm this request?")?>`,
        cb_ok: () =>
          {
            wpt_request_ajax (
            "PUT",
            "wall/"+this.settings.id+"/clone",
            null,
            // success cb
            (d) =>
              {
                if (d.error_msg)
                  return wpt_displayMsg ({
                           type: "warning",
                           msg: d.error_msg
                         });

                $("<div/>").wpt_wall ("open", d.wallId);

                wpt_displayMsg ({
                  type: "success",
                  msg: "<?=_("The wall has been successfully cloned.")?>"
                });
            });
          }
      });
    },

    // METHOD export ()
    export: function ()
    {
      wpt_openConfirmPopup ({
        type: "export-wall",
        icon: "file-export",
        content: `<?=_("Depending on its content, the export size can be substantial.<br>Do you confirm this request?")?>`,
        cb_ok: () => wpt_download ({
          url: "/wall/"+this.settings.id+"/export",
          fname: "wopits-wall-export-"+this.settings.id+".zip",
          msg: "<?=_("An error occurred while exporting wall data.")?>"
        })
      });
    },

    // METHOD import ()
    import: function ()
    {
      $(".upload.import-wall").click ();
    },

    // METHOD restorePreviousSession ()
    restorePreviousSession: function ()
    {
      const walls = wpt_userData.settings.openedWalls;
   
      if (walls)
      {
        for (let i = walls.length - 1; i >= 0; i--)
          this.open (walls[i], true);
      }
    },

    // METHOD refreshUserWallsData ()
    refreshUserWallsData: function (success_cb)
    {
      wpt_request_ajax (
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
    openOpenWallPopup: function ()
    {
      this.refreshUserWallsData (() =>
        {
          const $popup = $("#openWallPopup");

          $popup.wpt_openWall ("reset");
          $popup.wpt_openWall ("displayWalls");

          wpt_openModal ($popup);
        });
    },

    // METHOD openNamePopup ()
    openNamePopup: function ()
    {
      const $popup = $("#createWallPopup");

      wpt_cleanPopupDataAttr ($popup);
      $popup[0].dataset.noclosure = true;
      wpt_openModal ($popup);
    },

    // METHOD displayWallUsersview()
    displayWallUsersview: function ()
    {
      //TODO We should use ajax instead of ws
      wpt_request_ws (
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
                html += `<a href="#" data-id="${item.id}" class="list-group-item list-group-item-action" data-title="${wpt_htmlQuotes(item.fullname)}" data-picture="${item.picture||""}" data-about="${wpt_htmlQuotes(item.about||"")}">${wpt_getAccessIcon(item.access)} ${item.fullname} (${item.username})</a>`;
            });

          $popup.find(".list-group").html (html);
          wpt_openModal ($popup);
        }
      );
    },

    // METHOD displayWallProperties ()
    displayWallProperties: function (args)
    {
      const $wall = this.element;

      wpt_request_ajax (
        "GET",
        "wall/"+this.settings.id+"/infos",
        null,
        // success cb
        (d) =>
        {
          const $popup = $("#wallPropertiesPopup");

          wpt_cleanPopupDataAttr ($popup);

          $popup.find(".description").show ();

          $popup.find(".creator").text (d.user_fullname);
          $popup.find(".creationdate").text (
            wpt_getUserDate (d.creationdate, null, "Y-MM-DD HH:mm"));

          $popup.find(".size").hide ();

          if (wpt_checkAccess("<?=WPT_RIGHTS['walls']['admin']?>"))
          {
            const $input = $popup.find(".name input");

            $popup.find(".btn-primary").show ();
            $popup.find(".ro").hide ();
            $popup.find(".adm").show ();

            $input.val(d.name);
            $popup.find(".description textarea").val(d.description);

            if (args && args.forRename)
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

            $popup.find(".name .ro").html(wpt_nl2br (d.name));
            if (d.description)
              $popup.find(".description .ro").html(wpt_nl2br (d.description));
            else
              $popup.find(".description").hide ();
          }
      
          $popup[0].dataset.noclosure = true;
          wpt_openModal ($popup);
        }
      );
    },

    // METHOD openPropertiesPopup ()
    openPropertiesPopup: function (args)
    {
      if (wpt_checkAccess ("<?=WPT_RIGHTS['walls']['admin']?>"))
        this.edit (() => this.displayWallProperties (args));
      else
        this.displayWallProperties ();
    },

    // METHOD getName ()
    getName: function ()
    {
      return $('a[href="#wall-'+this.settings.id+'"] span.val').text ();
    },

    // METHOD setName ()
    setName: function (name, noicon)
    {
      const $div = $('a[href="#wall-'+this.settings.id+'"]');

      let html = (noicon) ?
        `<i class="fas fa-cog fa-spin fa-fw"></i>` :
         wpt_getAccessIcon (this.settings.access);

      if (!noicon && this.settings.ownerid != wpt_userData.id)
        html = `<i class="fas fa-user-slash notowner" title="<?=_("You are not the creator of this wall")?>"></i>`+html;

      $div.find('span.icon').html (html);
      $div.find('span.val').text (wpt_noHTML (name));
    },

    // METHOD getDescription ()
    getDescription: function ()
    {
      return $('a[href="#wall-'+this.settings.id+'"]')[0].dataset.description;
    },

    // METHOD setDescription ()
    setDescription: function (description)
    {
      $('a[href="#wall-'+this.settings.id+'"]')[0]
        .dataset.description = wpt_noHTML (description);
    },

    // METHOD fixSize ()
    fixSize: function (oldW, newW)
    {
      if (_refreshing)
        return;

      const $wall = this.element;
      let w = Number ($wall[0].dataset.oldwidth);

      if (!w)
        w = $wall.outerWidth ();

      if (newW)
      {
        if (newW > oldW)
          w += (newW - oldW);
        else if (newW < oldW)
          w -= (oldW - newW);
      }

      $wall[0].dataset.oldwidth = w;
      $wall[0].style.width = w+"px";
      $wall[0].style.maxWidth = w+"px";
    },

    // METHOD zoom ()
    //FIXME KO with some browsers and touch devices
    zoom: function (args)
    {
      const $zoom = $(".tab-content.walls"),
            zoom0 = $zoom[0],
            wall0 = this.element[0],
            from = args.from,
            type = args.type,
            noalert = !!args.noalert,
            zoomStep = (!!args.step) ? args.step : 0.2,
            writeAccess = wpt_checkAccess ("<?=WPT_RIGHTS['walls']['rw']?>");
      let stylesOrigin;

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
          wpt_displayMsg ({
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
        $(".plug-label").css ("pointer-events", "none");
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
          wpt_displayMsg ({
            type: "info",
            title: "<?=_("Zoom disabled")?>",
            msg: "<?=_("The wall is again editable")?>"
          });

        zoom0.style = stylesOrigin;
        $(".plug-label").css ("pointer-events", "auto");

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
    screen: function ()
    {
      const step = .005,
            wall = this.element[0],
            walls = wpt_sharer.getCurrent("walls")[0];
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
    edit: function (success_cb, error_cb, todelete = false)
    {
      _originalObject = this.serialize ();

      if (!this.settings.shared)
        return success_cb && success_cb ();

      wpt_request_ws (
        "PUT",
        "wall/"+this.settings.id+"/editQueue/wall/"+this.settings.id,
        {todelete: todelete},
        // success cb
        (d) =>
        {
          if (d.removed)
          {
            wpt_displayMsg ({type: "warning", msg: d.removed});

            this.close ();
          }
          else if (d.error_msg)
            wpt_raiseError (() => error_cb && error_cb (), d.error_msg);
          else if (success_cb)
            success_cb (d);
        }
      );
    },

    serialize: function ()
    {
      return {
        name: this.getName (),
        description: this.getDescription ()
      };
    },

    // METHOD unedit ()
    unedit: function (success_cb, error_cb)
    {
      let data = null;

      if (this.element[0].dataset.todelete)
        data = {todelete: true};
      // Update wall only if it has changed
      else
      {
        data = this.serialize ();

        if (!wpt_updatedObject (_originalObject, data))
        {
          if (!this.settings.shared)
            return success_cb && success_cb ();
          else
            data = null;
        }
      }

      wpt_request_ws (
        "DELETE",
        "wall/"+this.settings.id+"/editQueue/wall/"+this.settings.id,
        data,
        // success cb
        (d) =>
        {
          if (!(data && data.todelete) && d.error_msg)
          {
            error_cb && error_cb ();

            wpt_displayMsg ({type: "warning", msg: d.error_msg});
          }
          else if (success_cb)
            success_cb ();
        },
        // error cb
        error_cb
      );
    }

  });

  /////////////////////////// AT LOAD INIT //////////////////////////////

  $(function ()
    {
      if (!document.querySelector ("body.login-page"))
      {
        $("#settingsPopup").wpt_settings ("applyTheme");

        wpt_WebSocket.connect (
          "wss://"+location.host+"/app/ws?token="+wpt_userData.token, ()=>
          {
            const $settings = $("#settingsPopup");

            $settings.wpt_settings ({locale: $("html").attr ("lang")});

            // if a theme exists from the login page, apply it once the user is
            // logged
            const loginTheme = wpt_storage.get ("theme");
            if (loginTheme)
            {
              wpt_storage.delete ("theme");
              $settings.wpt_settings ("set", {theme: loginTheme});
            }

            $settings.wpt_settings ("applyTheme");

            // Check if wopits has been upgraded
            wpt_checkForAppUpgrade ();

            // Load previously opened walls
            $("<div/>").wpt_wall ("restorePreviousSession");

            // Keep WS connection and database persistent connection alive and
            // prevent PHP timeout
            // -> 15mn
            setInterval (()=>
              {
                // WebSocket ping
                wpt_WebSocket.ping();
                // AJAX ping
                $.get ("/api/user/ping");

              }, 15*60*1000);

          });

        wpt_fixMenuHeight ();
        wpt_fixMainHeight ();

        //FIXME KO with some browsers and touch devices
        if ($.support.touch || navigator.userAgent.match (/edg/i))
          $("#main-menu").addClass ("nofullview");

        $("#normal-display-btn")
          .on("click", function ()
          {
            wpt_sharer.getCurrent("wall").wpt_wall ("zoom", {type: "normal"});
          });

        $("#createWallPopup #w-grid").on("change", function ()
          {
            const el = $(this)[0],
                  $popup = $(this).closest (".modal");

            $popup.find("span.required").remove ();
            $popup.find(".cols-rows input,.width-height input").val ("");
            $popup.find(".cols-rows,.width-height").hide ();

            $popup.find(el.checked?".cols-rows":".width-height").show ("fade");
          });

        $(`<input type="file" accept=".zip" class="upload import-wall">`)
          .on("change", function (e)
            {
              if (e.target.files && e.target.files.length)
              {
                wpt_getUploadedFiles (e.target.files,
                  (e, file) =>
                  {
                    $(this).val ("");
  
                    if (wpt_checkUploadFileSize ({
                          size: e.total,
                          maxSize:<?=WPT_IMPORT_UPLOAD_MAX_SIZE?>
                        }) &&
                        e.target.result)
                    {
                      wpt_request_ajax (
                        "PUT",
                        "wall/import",
                        {
                          name: file.name,
                          size: file.size,
                          type: file.type,
                          content: e.target.result
                        },
                        // success cb
                        (d) =>
                        {
                          if (d.error_msg)
                            return wpt_displayMsg ({
                                     type: "warning",
                                     msg: d.error_msg
                                   });
  
                          $("<div/>").wpt_wall ("open", d.wallId);
  
                          wpt_displayMsg ({
                            type: "success",
                            msg: "<?=_("The wall has been successfully imported.")?>"
                          });
                        });
                    }
                  });
              }
            }).appendTo ("body");

        $(document)
          .on("click","thead th:first-child",function (e)
          {
            if ($(this).find(".wpt-badge").length)
              wpt_sharer.getCurrent("wall").wpt_wall ("displayWallUsersview");
          });

        $("#wallUsersviewPopup")
          .on("click",".list-group a",function (e)
          {
            const a = $(this)[0],
                  $popup = $("#userViewPopup"),
                  $userPicture = $popup.find (".user-picture");

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
            $popup.find(".about dd").html ((a.dataset.about) ?
              wpt_nl2br(a.dataset.about) : "<i><?=_("No description.")?></i>");

            wpt_openModal ($popup);
          });

        // EVENT CLICK on menu items
        $(document)
          .on("click", ".navbar.wopits a,"+
                       "#main-menu a:not(.disabled),"+
                       ".nav-tabs.walls a[data-action='new'],"+
                       "#welcome",function(e)
          {
            const $wall = wpt_sharer.getCurrent ("wall");
            const action = $(this)[0].dataset.action ||
                             $(this).parent()[0].dataset.action;

            switch (action)
            {
              case "zoom+":

                $wall.wpt_wall ("zoom", {type: "+"});

                break;

              case "zoom-":

                $wall.wpt_wall ("zoom", {type: "-"});

                break;

              case "zoom-screen":

                $wall.wpt_wall ("zoom", {type:"screen"});

                break;

              case "zoom-normal":

                $wall.wpt_wall ("zoom", {type: "normal"});

                break;

              case "chatroom":

                var input = $(this).find("input")[0];

                // Manage checkbox
                if (e.target.tagName != "INPUT")
                  input.checked = !input.checked;

                wpt_sharer.getCurrent("chatroom").wpt_chatroom ("toggle");

                break;

              case "filters":

                var input = $(this).find("input")[0];

                // Manage checkbox
                if (e.target.tagName != "INPUT")
                  input.checked = !input.checked;

                wpt_sharer.getCurrent("filters").wpt_filters ("toggle");

                break;

              case "arrows":

                var input = $(this).find("input")[0];

                // Manage checkbox
                if (e.target.tagName != "INPUT")
                  input.checked = !input.checked;

                wpt_sharer.getCurrent("arrows").wpt_arrows ("toggle");

                break;

              case "settings":

                $("#settingsPopup").wpt_settings ("open");

                break;

              case "new":

                wpt_closeMainMenu ();

                $("<div/>").wpt_wall ("openNamePopup");

                break;

              case "about":

                wpt_openModal ($("#aboutPopup"));

                break;

              case "user-guide":

                wpt_openModal ($("#userGuidePopup"));

                break;

              case "open":

                $("<div/>").wpt_wall ("openOpenWallPopup");

                break;

              case "close-walls":

                $("<div/>").wpt_wall ("openCloseAllWallsPopup");

                break;

              case "delete":

                $wall.wpt_wall ("openDeletePopup");

                break;

              case "search":

                $("#postitsSearchPopup").wpt_postitsSearch ("open");

                break;

              case "clone":

                $wall.wpt_wall ("clone");

                break;

              case "export":

                $wall.wpt_wall ("export");

                break;

              case "import":

                $("<div/>").wpt_wall ("import");

                break;

              case "share":

                $("#shareWallPopup").wpt_shareWall ("open");

                break;

              case "add-col":

                $wall.wpt_wall ("createColRow", "col");

                break;

              case "add-row":

                $wall.wpt_wall ("createColRow", "row");

                break;

              case "view-properties":

                $wall.wpt_wall ("openPropertiesPopup");

                break;
            }
          });
      }
      // EVENT CLICK on about at login page
      else
        $('[data-action="about"]').on("click", function ()
          {wpt_openModal ($("#aboutPopup"))});

  });

<?php echo $Plugin->getFooter ()?>
