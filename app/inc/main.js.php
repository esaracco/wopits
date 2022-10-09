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
  const _getCellTemplate = (data)=>
    {
      return `<td scope="dzone" class="wpt size-init" style="width:${data.width}px;height:${data.height}px" data-id="cell-${data.id}"></td>`;
    };

  // METHOD _getDirectURLData ()
  const _getDirectURLData = ()=> {
    const m = location.search.match(<?=WPT_DIRECTURL_REGEXP?>);
    if (m) {
      return m[0] === 'unsubscribe' ?
        {type: 'u'} :
        {
          type: m[1],
          wallId: m[2],
          postitId: m[4],
          commentId: m[6],
        };
    }
  };

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

      settings.tabLink = $(document.querySelector (
                             `.nav-tabs.walls a[href="#wall-${settings.id}"]`));

      // Add wall menu
      $(`#wall-${settings.id}`).find(".wall-menu").wmenu ({
        wallPlugin:plugin,
        access: access
      });

      settings.plugs = [];

      if (settings.restoring)
        wall.dataset.restoring = 1;

      wall.dataset.displaymode = settings.displaymode;
      wall.dataset.displayheaders = settings.displayheaders;

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
          "background-color":(settings["background-color"]) ?
                                 settings["background-color"] : "auto"
        })
        .html (`<thead class="wpt"><tr class="wpt"><th class="wpt">&nbsp;</th></tr></thead><tbody class="wpt"></tbody>`);

      if (H.haveMouse ())
        $wall.draggable({
          distance: 10,
          cursor: "grab",
//          cancel: (writeAccess) ? "span,.title,.postit-edit" : null,
          cancel: (writeAccess) ? ".postit-tags" : null,
          start: function ()
            {
              S.set ("wall-dragging", true);
              plugin.hidePostitsPlugs ();
            },
          stop: function ()
            {
              S.set ("dragging", true, 500);

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
                  $th = $(`<th class="wpt"/>`);

            $wall.find("thead.wpt tr.wpt").append ($th);
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
                    $cell = $wall.find (`[data-id="cell-${cell.id}"]`);

              for (let k = 0, kLen = cell.postits.length; k < kLen; k++)
              {
                cell.postits[k]["access"] = access;
                cell.postits[k]["init"] = true;
                $cell.cell ("addPostit", cell.postits[k], true);
              }
            }
          }

          $("#welcome").hide ();

          $wall.show (settings.displayheaders?"fade":null);

          wall.dataset.cols = hcols.length;
          wall.dataset.rows = hrows.length;

          plugin.setName (settings.name);
          plugin.setDescription (settings.description);

          window.dispatchEvent (new Event("resize"));

          if (settings.restoring)
          {
            delete settings.restoring;
            wall.removeAttribute ("data-restoring");
          }

          // Set wall users view count if needed
          const viewcount = WS.popResponse (`viewcount-wall-${wallId}`);
          if (viewcount !== undefined)
            plugin.refreshUsersview (viewcount); 

          // If last wall to load.
          if (settings.lastWall)
          {
            setTimeout(()=>
              $(`[data-id="wall-${wpt_userData.settings.activeWall}"]`)
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
            // LOCAL FUNCTION ()
            const __postInit = ()=>
              {
                H.waitForDOMUpdate (() =>
                {
                  // Apply display header mode
                  plugin.displayHeaders ();
                  // Apply display mode
                  plugin.refreshCellsToggleDisplayMode ();

                  $wall.parent().find(".wall-menu")
                    .css ("visibility", "visible");
                });
              };

            plugin.displayExternalRef ();

            // Display postit dealine alert or specific wall if needed.
            if (settings.cb_after)
            {
              plugin.setActive ();
              //FIXME To much refresh
              plugin.refresh ();

              H.waitForDOMUpdate (() =>
                {
                  settings.cb_after ();
                  __postInit ();
                });
            }
            else
              __postInit ();
          });
        });
    },

    // METHOD displayPostitAlert ()
    displayPostitAlert (args)
    {
      const $wall = $(`.wall[data-id="wall-${args.wallId}"]`),
            $postit = $wall.find (`.postit[data-id="postit-${args.postitId}"]`);

      if ($postit.length)
        $postit.postit ("displayAlert", args.type);
      else
        H.displayMsg ({
          title: `<?=_("Note")?>`,
          type: "warning",
          msg: `<?=_("The note has been deleted")?>`
        });
    },

    // METHOD displayShareAlert ()
    displayShareAlert (wallId)
    {
      const walls = wpt_userData.walls.list;
      let owner;

      for (const k in walls)
        if (walls[k].id == wallId)
        {
          owner = walls[k].ownername
          break;
        }

      H.openConfirmPopover ({
        type: "info",
        item: $(".walls a.active span.val"),
        title: `<i class="fas fa-share fa-fw"></i> <?=_("Sharing")?>`,
        content: `<?=_("%s shared this wall with you")?>`.replace("%s", owner)
      });
    },

    // METHOD setActive ()
    setActive ()
    {
      S.reset ();

      this.settings.tabLink.click ();
      $(`#wall-${this.settings.id}`).addClass ("active");

      this.menu ({from: "wall", type: "have-wall"});
    },

    // METHOD ctrlMenu ()
    ctrlMenu (action, type)
    {
      const m = document.querySelector (
                  `.dropdown-menu li[data-action="${action}"] a`).classList

      if (type == "off")
        m.add ("disabled");
      else
        m.remove ("disabled");
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
                             '[data-action="filters"] a')
                    .addClass("disabled");
                break;
    
              // Deactivate normal view item
              case "zoom-normal-off":

                $menuNormal.addClass("disabled");

                this.ctrlMenu ("zoom-screen", "on");

                if (adminAccess)
                  $menu.find('[data-action="chat"] a,'+
                             '[data-action="filters"] a')
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

    // METHOD closeAllMenus()
    closeAllMenus() {
      const menu = this.element[0].querySelector('.postit-menu');

      if (menu) {
        $(menu.parentNode).postit('closeMenu');
      }
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
          if (this.classList.contains (`ui-${plugin}`))
          {
            if (forceHandle && isDisabled)
              $(this).find(`.ui-${plugin}-handle`)
                .css ("visibility", value?"hidden":"visible");

            $(this)[plugin] ("option", option, value);
          }
        });
    },

    // METHOD repositionPostitsPlugs()
    repositionPostitsPlugs() {
      this.element[0].querySelectorAll('.postit.with-plugs').forEach((p) =>
          $(p).postit('repositionPlugs'));
    },

    // METHOD removePostitsPlugs()
    removePostitsPlugs() {
      this.element[0].querySelectorAll('.postit.with-plugs').forEach((p) =>
          $(p).postit('removePlugs', true));
    },

    // METHOD refreshPostitsPlugs ()
    refreshPostitsPlugs (partial)
    {
      const $f = S.getCurrent ("filters");
      if ($f.length && $f[0].classList.contains ("plugs-hidden"))
        return;

      const wall = this.element[0],
            applyZoom = Boolean(S.get("zoom-level"));
      let idsNew = {};

      (this.settings.plugs||[]).forEach (plug =>
        {
          const startId = plug.item_start,
                start0 = wall.querySelector (
                           `.postit[data-id="postit-${startId}"]`);

          if (start0)
          {
            const endId = plug.item_end,
                  $start = $(start0),
                  startPlugin = $start.postit ("getClass"),
                  labelName = plug.label||"...";

            idsNew[`${startId}${endId}`] = 1;

            if (!startPlugin.plugExists (endId))
            {
              const end = wall.querySelector (
                            `.postit[data-id="postit-${endId}"]`);

              if (end)
              {
                const newPlug = {
                        startId: startId,
                        endId: endId,
                        label: {
                           name: labelName,
                           top: plug.item_top,
                           left: plug.item_left
                      },
                      obj: startPlugin.getPlugTemplate ({
                             hide: true,
                             start: start0,
                             end: end,
                             label: labelName,
                             line_size: plug.line_size,
                             line_path: plug.line_path,
                             line_color: plug.line_color,
                             line_type: plug.line_type
                           })
                      };

                startPlugin.addPlug (newPlug, applyZoom);
              }
            }
            else
            {
              startPlugin.updatePlugLabel ({
                endId: endId,
                label: labelName,
                top: plug.item_top,
                left: plug.item_left
              });

              startPlugin.updatePlugProperties ({
                endId: endId,
                size: plug.line_size,
                path: plug.line_path,
                color: plug.line_color,
                line_type: plug.line_type
              });
            }
          }
        });

      // Remove obsolete plugs
      if (!partial)
        wall.querySelectorAll(".postit.with-plugs").forEach (postit =>
          {
            $(postit).postit("getSettings").plugs.forEach (plug =>
              {
                if (!idsNew[`${plug.startId}${plug.endId}`])
                  $(wall.querySelector(
                      `.postit[data-id="postit-${plug.endId}"]`))
                    .postit ("removePlug", plug, true);
              });
          });
    },

    // METHOD hidePostitsPlugs()
    hidePostitsPlugs() {
      this.element[0].querySelectorAll('.postit').forEach(
          (p) => $(p).postit('hidePlugs', true));
    },

    // METHOD showPostitsPlugs()
    showPostitsPlugs() {
      this.repositionPostitsPlugs();

      H.waitForDOMUpdate(() =>
          this.element[0].querySelectorAll('.postit').forEach(
              (p) => $(p).postit('showPlugs', true)));
    },

    // METHOD showUserWriting ()
    showUserWriting (user)
    {
      setTimeout (()=>
        {
          const tab = document.querySelector (
                        `.walls a[href="#wall-${this.settings.id}"]`);

          tab.classList.add ("locked");
          tab.insertBefore ($(`<div class="user-writing main" data-userid="${user.id}"><i class="fas fa-user-edit blink"></i> ${user.name}</div>`)[0], tab.firstChild);
        }, 150);
    },

    // METHOD refresh ()
    refresh (d)
    {
      if (d)
        this._refresh (d);
      else if (this.settings.id)
        H.fetch (
          "GET",
          `wall/${this.settings.id}`,
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
            wallIsVisible = $wall.is (":visible");

      // LOCAL FUNCTION __refreshWallBasicProperties ()
      const __refreshWallBasicProperties = (d)=>
        {
          plugin.setShared (d.shared);
          plugin.setName (d.name);
          plugin.setDescription (d.description);
        };

      this.settings.plugs = d.postits_plugs;

      // Partial wall update
      if (d.partial)
      {
        switch (d.partial)
        {
          // Postits
          case "postit":
            const $postit = $(wall.querySelector (
                     `.postit[data-id="postit-${d.postit.id}"]`)),
                  cell = wall.querySelector (
                    `td[data-id="cell-${d.postit.cells_id}"]`);

            // Rare case, when user have multiple sessions opened
            if (d.action != "insert" && !$postit.length)
              return;

            switch (d.action)
            {
              // Insert postit
              case "insert":

                $(cell).cell ("addPostit", d.postit, true);
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

                $postit.postit ("remove");
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
                  $wall.find (`th[data-id="header-${d.header.id}"]`)
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
      else if (!d.removed)
      {
        const wallId = plugin.settings.id,
              access = plugin.settings.access,
              rowsHeadersIds = {},
              colsHeadersIds = {},
              rowsCount = d.headers.rows.length,
              colsCount = d.headers.cols.length,
              postitsIds = {},
              rows = [],
              showHeaders = plugin.settings.displayheaders;

        _refreshing = true;

        wall.dataset.cols = colsCount;
        wall.dataset.rows = rowsCount;
        wall.dataset.oldwidth = d.width;

        __refreshWallBasicProperties (d);

        // Refresh headers
        for (let i = 0; i < colsCount; i++)
        {
          const header = d.headers.cols[i],
                $header = $wall.find(`thead.wpt th.wpt[data-id="header-${header.id}"]`);

          if (!$header.length)
          {
            const $th = $(`<th class="wpt"/>`);

            $th[0].classList.add (showHeaders?"display":"hide");

            $wall.find("thead.wpt tr.wpt").append ($th);
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

        wall.querySelectorAll("tbody.wpt th.wpt").forEach (th =>
          {
            const $header = $(th);

            if (!rowsHeadersIds[$header.header ("getId")])
              $wall.find(`tbody.wpt tr.wpt:eq(${$header.parent().index()})`)
                .cell ("remove");
          });

        // Remove deleted columns
        for (let i = 0; i < colsCount; i++)
          colsHeadersIds[d.headers.cols[i].id] = true;

        wall.querySelectorAll("thead.wpt th.wpt").forEach (th =>
          {
            const $header = $(th),
                  idx = $header.index ();

            if (idx > 0 && !colsHeadersIds[$header.header ("getId")])
            {
              wall.querySelectorAll("thead.wpt th.wpt")[idx].remove ();
              wall.querySelectorAll("tbody.wpt tr.wpt").forEach (tr =>
                $(tr).find (`td.wpt:eq(${idx-1})`).cell ("remove"));
            }
          });

        for (let i = 0, iLen = d.cells.length; i < iLen; i++)
        {
          const cell = d.cells[i],
                irow = cell.item_row;

          // Get all postits ids for this cell
          for (let j = 0, jLen = cell.postits.length; j < jLen; j++)
            postitsIds[cell.postits[j].id] = true;

          if (rows[irow] === undefined)
            rows[irow] = [];

          rows[irow][cell.item_col] = cell;
        }

        for (let i = 0, iLen = rows.length; i < iLen; i++)
        {
          const row = rows[i],
                header = d.headers.rows[i];

          if (!$wall.find(`td.wpt[data-id="cell-${row[0].id}"]`).length)
            plugin.addRow (header, row);
          else
            $wall.find(`tbody.wpt th.wpt[data-id="header-${header.id}"]`)
              .header ("update", header);

          for (let j = 0, jLen = row.length; j < jLen; j++)
          {
            const cell = row[j];
            let $cell = $wall.find(`td.wpt[data-id="cell-${cell.id}"]`),
                isNewCell = false;

            // If new cell, add it
            if (!$cell.length)
            {
              const cellId = cell.id;

              isNewCell = true;

              $cell = $(_getCellTemplate (cell));

              $wall.find(`tbody.wpt tr.wpt:eq(${cell.item_row})`).append ($cell);

              // Init cell
              $cell.cell ({
                id: cellId,
                access: access,
                usersettings:
                  plugin.settings.usersettings[`cell-${cellId}`]||{},
                wall: $wall,
                wallId: wallId
              });
            }
            else
            {
              $cell.cell ("update", cell);

              // Remove deleted post-its
              $cell[0].querySelectorAll(".postit").forEach (p =>
                {
                  if (!postitsIds[$(p).postit("getId")])
                    $(p).postit ("remove");
                });
            }

            for (let k = 0, kLen = cell.postits.length; k < kLen; k++)
            {
              const postit = cell.postits[k],
                    $postit = $wall.find (
                      `.postit[data-id="postit-${cell.postits[k].id}"]`);

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

      // Refresh super menu tool
      S.getCurrent("mmenu").mmenu ("refresh");

      if (wallIsVisible && d.postits_plugs)
        setTimeout (() =>
          {
            // Refresh postits relations
            plugin.refreshPostitsPlugs (d.partial && d.partial != "plugs");
          }, 0);
      else
        plugin.repositionPostitsPlugs ();

      // Apply display mode
      setTimeout (()=>
        {
          plugin.fixSize ();

          if (d.reorganize)
            $wall.find("tbody.wpt td.wpt").cell ("reorganize");

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

          // Show locks
          if (d.locks) {
            d.locks.forEach(({item, item_id, user_id, user_name}) => {
              const el = document.querySelector(
                `${item === 'postit' ? '.postit' : ''}`+
                `[data-id="${item}-${item_id}"]`);
              if (el) {
                $(el)[item]('showUserWriting', {id: user_id, name: user_name});
              }
            });
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
      const activeTabId = `wall-${this.settings.id}`,
            activeTab = document.querySelector(`a[href="#${activeTabId}"]`),
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
        bootstrap.Tab.getOrCreateInstance(document.querySelector(
          `.nav-tabs.walls a[href="${newActiveTabId}"]`)).show ();

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
              item: $(this.settings.tabLink[0].querySelector("span.val")),
              placement: "left",
              title: `<i class="fas fa-trash fa-fw"></i> <?=_("Delete")?>`,
              content: `<?=_("Delete this wall?")?>`,
              cb_close: () => this.unedit (),
              cb_ok: () => this.delete ()
            });
        }, null, true);
    },

    // METHOD delete ()
    delete ()
    {
      this.element[0].dataset.todelete = true;

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
        return H.displayMsg ({
                 title: `<?=_("Wall")?>`,
                 type: "warning",
                 msg: `<?=_("For performance reasons, a wall cannot contain more than %s cells")?>`.replace("%s", <?=WPT_MAX_CELLS?>)
               });

      H.request_ws (
        "PUT",
        `wall/${this.settings.id}/${type}`,
        null,
        ()=>
          S.getCurrent("walls")[(type=="col")?"scrollLeft":"scrollTop"](30000));
    },

    // METHOD addRow ()
    addRow (header, row)
    {
      const plugin = this,
            $wall = plugin.element,
            wallId = plugin.settings.id,
            showHeaders = plugin.settings.displayheaders;
      let tds = "";

      for (let i = 0; i < row.length; i++)
        tds += _getCellTemplate (row[i]);

      const $row = $(`<tr class="wpt"><th class="wpt ${showHeaders?"display":"hide"}"></th>${tds}</tr>`);

      // Add row
      $wall.find("tbody.wpt").append ($row);
      $row.find("th.wpt:eq(0)").header ({
        access: plugin.settings.access,
        item_type: "row",
        id: header.id,
        wall: $wall,
        wallId: wallId,
        title: header.title,
        picture: header.picture
      });

      // Init cells
      $row.find("td.wpt").each (function ()
        {
          const cellId = this.dataset.id.substring (5);

          $(this).cell ({
            id: cellId,
            access: plugin.settings.access,
            usersettings: plugin.settings.usersettings[`cell-${cellId}`]||{},
            wall: $wall,
            wallId: wallId
          });
        });
    },

    // METHOD deleteRow ()
    deleteRow (rowIdx)
    {
      const th = this.element.find(`tbody.wpt tr.wpt:eq(${rowIdx}) th.wpt:eq(0)`)[0];

      $(th).header ("removeContentKeepingWallSize", {
        oldW: th.offsetWidth,
        cb: () =>
          {
            const img = th.querySelector (".img");

            if (img)
              img.remove ();

            th.querySelector(".title").innerHTML = "&nbsp;";
          }
        });

      H.request_ws (
        "DELETE",
        `wall/${this.settings.id}/row/${rowIdx}`,
        {wall: {width: Math.trunc (this.element.outerWidth ())}});
    },

    // METHOD deleteCol ()
    deleteCol (idx)
    {
      const $wall = this.element;

      H.request_ws (
        "DELETE",
        `wall/${this.settings.id}/col/${idx-1}`,
        {
          wall: {width: Math.trunc ($wall.outerWidth()-1)},
          width: Math.trunc ($wall.find(`thead.wpt tr.wpt th.wpt:eq(${idx})`).outerWidth())
        });
    },

    // METHOD addNew ()
    addNew (args, $popup)
    {
      const plugin = this,
            tabs = document.querySelector (".nav-tabs.walls"),
            $tabs = $(tabs),
            method = (args.load) ? "GET" : "PUT",
            service = (args.load) ? `wall/${args.wallId}` : "wall",
            data = (args.load) ?
                null : {
                  name: args.name,
                  grid: Boolean(args.grid),
                };

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
        tabs.insertBefore ($(`<a class="nav-item nav-link" href="#wall-${args.wallId}" data-bs-toggle="tab"><span class="icon"></span><span class="val"></span></a>`)[0], tabs.firstChild);

      H.fetch (
        method,
        service,
        data,
        // success cb
        (d) =>
        {
          // If we must execute a callback after loading the wall.
          if (args.cb_after)
            d.cb_after = args.cb_after;

          // If we are restoring a wall.
          if (args.restoring)
            d.restoring = 1;

          // The wall does not exists anymore.
          if (d.removed)
          {
            var tmp = tabs.querySelector(`a[href="#wall-${args.wallId}"]`);
            if (tmp)
              tmp.remove ();

            return H.waitForDOMUpdate (()=>
              {
                if (args.restoring && tabs.querySelector(".nav-item"))
                {
                  const el = tabs.querySelector (".nav-item:first-child");

                  $(el).trigger ("mousedown");
                  bootstrap.Tab.getOrCreateInstance(el).show ();
                }

                // Save opened walls when all walls will be loaded.
                if (d.restoring)
                {
                  if (args.lastWall)
                    $("#settingsPopup").settings ("saveOpenedWalls");
                  else
                    S.set ("save-opened-walls", true);
                }

                H.displayMsg ({
                  title: `<?=_("Wall")?>`,
                  type: "warning",
                  msg: `<?=_("Some walls are no longer available")?>`
                });
            });
          }

          if (d.error_msg)
            return H.displayMsg ({
              title: `<?=_("Wall")?>`,
              type: "warning",
              msg: d.error_msg
            });

          if ($popup)
            $popup.modal ("hide");

          document.querySelector(".tab-content.walls").appendChild ($(`<div class="tab-pane" id="wall-${d.id}"><ul class="wall-menu shadow"></ul><div class="toolbox chat shadow"></div><div class="toolbox filters shadow"></div><table class="wall" data-id="wall-${d.id}" data-access="${d.access}"></table></div>`)[0]);

          if (!args.restoring)
            tabs.insertBefore ($(`<a class="nav-item nav-link" href="#wall-${d.id}" data-bs-toggle="tab"><span class="icon"></span><span class="val"></span></a>`)[0], tabs.firstChild);

          if (args.lastWall)
            d.lastWall = args.lastWall;

          const wallTab = tabs.querySelector (`a[href="#wall-${d.id}"]`);

          wallTab.setAttribute ("data-access", d.access);
          wallTab.insertBefore ($(`<button type="button" class="close" title="<?=_("Close this wall")?>"><span class="close">&times;</span></button>`)[0], wallTab.firstChild);

          d["background-color"] =
            $("#settingsPopup").settings ("get", "wall-background", d.id);

          const $wallDiv = $(document.getElementById(`wall-${d.id}`));

          $wallDiv.find(".wall").wall (d);
          $wallDiv.find(".chat").chat ({wallId: d.id});
          $wallDiv.find(".filters").filters ();

          if (!args.restoring || wpt_userData.settings.activeWall == d.id)
          {
            S.set ("newWall", true);
            bootstrap.Tab.getOrCreateInstance (wallTab).show ();
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
      H.fetch (
      "PUT",
      `wall/${this.settings.id}/clone`,
      null,
      // success cb
      (d) =>
        {
          if (d.error_msg)
            return H.displayMsg ({
              title: `<?=_("Wall")?>`,
              type: "warning",
              msg: d.error_msg
            });

          $("<div/>").wall ("open", {
            lastWall: 1,
            wallId: d.wallId
          });

          H.displayMsg ({
            title: `<?=_("Wall")?>`,
            type: "success",
            msg: `<?=_("The wall has been successfully cloned")?>`
          });
      });
    },

    // METHOD export ()
    export ()
    {
      H.download ({
        url: `/wall/${this.settings.id}/export`,
        fname: `wopits-wall-export-${this.settings.id}.zip`,
        msg: `<?=_("An error occurred during the export")?>`
      });
    },

    // METHOD import ()
    import ()
    {
      document.querySelector(".upload.import-wall").click ();
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
          this.open ({
            lastWall: (i == 0) ? wallsLen : null,
            wallId: walls[i],
            restoring: true
          });
        }
      }
    },

    // METHOD isOpened ()
    isOpened (wallId)
    {
      return ((wpt_userData.settings.openedWalls||[])
                 .indexOf (String(wallId)) != -1);
    },

    // METHOD loadSpecific ()
    loadSpecific (args, noDelay)
    {
      const {wallId, postitId, commentId} = args;
      let type;

      // LOCAL FUNCTION __displayAlert ()
      const __displayAlert = ()=>
        {
          if (postitId)
          {
            switch (args.type)
            {
              case "a": type = "deadline"; break;
              case "c": type = "comment"; break;
              case "w": type = "worker"; break;
              default:
                type = args.type;
            }

            setTimeout (()=>
              this.displayPostitAlert ({
                wallId,
                postitId,
                type: type
              }), noDelay ? 0 : 250);
          }
          else
            this.displayShareAlert (wallId);
        };

      if (!this.isOpened (wallId))
        this.open ({
          lastWall: 1,
          wallId: wallId,
          restoring: false,
          cb_after: __displayAlert
        });
      else
        setTimeout (()=>
        {
          // Set wall current if needed
          if (wallId != S.getCurrent("wall").wall ("getId"))
          {
            const el = document.querySelector(`a[href="#wall-${wallId}"]`);

            $(el).trigger ("mousedown");
            bootstrap.Tab.getOrCreateInstance(el).show ();
          }

          __displayAlert ();

        }, noDelay ? 0 : 500);

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
          wpt_userData.walls = {list: d.list||[]};

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

    // METHOD openNamePopup()
    openNamePopup() {
      H.loadPopup('createWall', {
        init: ($p) => {
          const p = $p[0];

          // EVENT change on wall dimension in wall creation popup
          p.querySelector('#w-grid').addEventListener('change', (e) => {
            const btn = e.target;

            p.querySelectorAll('.cols-rows input').forEach(
                (el) => el.value = 3);
            p.querySelectorAll('.width-height input').forEach(
                (el) => el.value = '');
            p.querySelectorAll('.cols-rows,.width-height').forEach(
                (el) => el.style.display = 'none');

            if (btn.checked)
              btn.parentNode.classList.remove('disabled');
            else
              btn.parentNode.classList.add('disabled');

            p.querySelector(btn.checked ? '.cols-rows' : '.width-height')
                .style.display = 'flex';
          });
        },
        cb: ($p) => $p[0].dataset.noclosure = true
      });
    },

    // METHOD displayWallUsersview()
    displayWallUsersview ()
    {
      H.request_ws (
        "GET",
        `wall/${this.settings.id}/usersview`,
        null,
        (d) =>
        {
          H.loadPopup ("wallUsersview", {
            open: false,
            cb: ($p)=>
            {
              const userId = wpt_userData.id;

              let html = "";
              d.list.forEach (item =>
              {
                if (item.id != userId)
                  html += `<li class="list-group-item" data-id="${item.id}" data-title="${H.htmlEscape(item.fullname)}" data-picture="${item.picture||""}" data-about="${H.htmlEscape(item.about||"")}"><div class="label">${H.getAccessIcon(item.access)} ${item.fullname}</div><div class="item-infos"><span>${item.username}</span></div>`;
              });
              $p.find(".list-group").html (html);

              H.openModal ({item: $p[0]});
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
              $cell = $wall.find ("td.wpt"),
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
                cellPlugin = $cell.cell ("getClass");

          // LOCAL FUNCTION __resize ()
          const __resize = (args)=>
            {
              $wall.find("thead.wpt th.wpt:eq(1),td.wpt").css ("width", args.newW);
              $wall[0].querySelector("td.wpt .ui-resizable-s")
                .style.width = `${args.newW+2}px`;

              if (args.newH)
              {
                $wall.find("tbody.wpt th.wpt,td.wpt").css ("height", args.newH);
                $wall[0].querySelector("td.wpt .ui-resizable-e")
                  .style.height = `${args.newH+2}px`;
              }

              plugin.fixSize (args.oldW, args.newW);
            };

          __resize ({newW: w, oldW: oldW, newH: h});
          if ($wall.find("td.wpt").outerWidth () != w)
            __resize ({newW: $wall.find("td.wpt").outerWidth (), oldW: w});

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
        html = `<i class="fas fa-user-slash wallname-icon" title="<?=_("You are not the creator of this wall")?>"></i>${html}`;

      $div.find('span.icon').html (html);
      $div.find('span.val').text (H.noHTML (name));

      if (!noIcon)
        this.refreshSharedIcon ();
    },

    // METHOD isShared ()
    isShared ()
    {
      return Boolean(this.element[0].dataset.shared);
    },

    // METHOD setShared ()
    setShared (isShared)
    {
      const wall = this.element[0];

      if (isShared)
        wall.dataset.shared = 1;
      else
        wall.removeAttribute ("data-shared");

      this.refreshPostitsWorkersIcon ();
      this.refreshSharedIcon ();
    },

    // METHOD refreshPostitsWorkersIcon ()
    refreshPostitsWorkersIcon ()
    {
      const display = this.isShared ();

      this.element[0].querySelectorAll(".postit").forEach (p =>
        {
          const pMenu = $(p).postit ("getSettings").Menu;

          p.querySelector(".pwork")
            .style.display = display?"inline-block":"none";

          if (pMenu)
            pMenu.$menu[0].querySelector(`[data-action="pwork"]`)
              .style.display = display?"block":"none";
        });
    },

    // METHOD refreshSharedIcon ()
    refreshSharedIcon ()
    {
      const $div = this.settings.tabLink,
            $span = $div.find ('span.icon');

      if (this.isShared ())
      {
        if (!$span.find(".wallname-icon").length)
          $span.prepend (`<i class="fas fa-share wallname-icon" title="<?=_("The wall is shared")?>"></i>`);
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

      const $wall = this.element,
            wall = $wall[0];
      let w;

      // If no header, substract header width from wall width
      if (!this.settings.displayheaders)
        w = this.getTDsWidth ();
      else if (!(w = Number (wall.dataset.oldwidth)))
        w = $wall.outerWidth ();

      if (newW)
      {
        if (newW > oldW)
          w += (newW - oldW);
        else if (newW < oldW)
          w -= (oldW - newW);
      }

      wall.dataset.oldwidth = w;
      wall.style.width = `${w}px`;
      wall.style.maxWidth = `${w}px`;
    },

    // METHOD setPostitsDisplayMode ()
    setPostitsDisplayMode (type)
    {
      this.menu ({from: "display", type: type});

      this.element[0].dataset.displaymode = type;

      this.element.find("td.wpt").each (function ()
        {
          $(this).cell ("setPostitsDisplayMode", type);
        });

      // Re-apply filters
      const $f = S.getCurrent("filters");
      if ($f.is (":visible"))
        $f.filters ("apply", {norefresh: true});

      H.fetch (
        "POST",
        `user/wall/${this.settings.id}/displaymode`,
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
            noalert = Boolean(args.noalert),
            zoomStep = (Boolean(args.step)) ? args.step : 0.2,
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
            title: `<?=_("Zoom")?>`,
            type: "info",
            msg: `<?=_("Some features are not available when zoom is enabled")?>`
          });

        zoom0.dataset.zoomlevelorigin = level;

        zoom0.style.width = "30000px";

        zoom0.querySelectorAll("th.wpt").forEach (th =>
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
          title: `<?=_("Zoom")?>`,
          type: "warning",
          msg: `<?=_("The minimum zoom has been reached")?>`
        });

      S.set ("zoom-level", level);

      if (from != "screen" && level == zoom0.dataset.zoomlevelorigin)
      {
        const $walls = S.getCurrent('walls');

        S.unset ("zoom-level");

        $walls[0].style.overflow = 'auto';

        this.hidePostitsPlugs ();

        setTimeout (() => $("#normal-display-btn").hide(), 150);

        zoom0.removeAttribute ("data-zoomtype");
        zoom0.removeAttribute ("data-zoomlevelorigin");

        this.menu ({from: "display", type: "zoom-normal-off"});

        if (writeAccess && !noalert)
          H.displayMsg ({
            title: `<?=_("Zoom")?>`,
            type: "info",
            msg: `<?=_("All features are available again")?>`
          });

        zoom0.style.width = S.get ("old-styles").width;
        zoom0.style.transform = S.get ("old-styles").transform;
        S.unset ("old-styles");

        zoom0.querySelectorAll("th.wpt").forEach (th =>
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

        $walls
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
        zoom0.style.transform = `scale(${level})`;

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

      $(walls).scrollLeft(0).scrollTop(0);

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
      this.ctrlMenu ("zoom-screen", "off");

      $("#walls").scrollLeft (
        ((30000*S.get("zoom-level"))/2-window.innerWidth/2)+20);

      $("<div/>").postit ("applyZoom");

      walls.style.overflow = 'hidden';
    },

    // METHOD edit ()
    edit (success_cb, error_cb, todelete = false)
    {
      _originalObject = this.serialize ();

      if (!this.isShared ())
        return success_cb && success_cb ();

      H.request_ws (
        "PUT",
        `wall/${this.settings.id}/editQueue/wall/${this.settings.id}`,
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
        `wall/${this.settings.id}/editQueue/wall/${this.settings.id}`,
        data,
        // success cb
        (d) =>
        {
          if (!(data && data.todelete) && d.error_msg)
          {
            error_cb && error_cb ();

            H.displayMsg ({
              title: `<?=_("Wall")?>`,
              type: "warning",
              msg: d.error_msg
            });
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
          $(p).postit (`${type}ExternalRef`));

        H.fetch (
          "POST",
          `user/wall/${this.settings.id}/displayexternalref`,
          {value: val});
      }

      if (this.element.is (":visible"))
        this.menu ({from: "display", type: `${type}-externalref`});

      return val;
    },

    // METHOD haveExternalRef ()
    haveExternalRef ()
    {
      return this.element[0].querySelector (".postit[data-haveexternalref]");
    },

    // METHOD getTDsWidth ()
    getTDsWidth ()
    {
      let w = 0;

      this.element[0].querySelector("tbody.wpt tr.wpt").querySelectorAll("td.wpt")
        .forEach ((td)=> w += parseFloat (td.style.width));

      return w;
    },

    // METHOD showHeaders ()
    showHeaders ()
    {
      this.element[0].querySelectorAll("th.wpt").forEach (th =>
        {
          th.classList.remove ("hide");
          th.classList.add ("display");
        });
    },

    // METHOD hideHeaders ()
    hideHeaders ()
    {
      this.element[0].querySelectorAll("th.wpt").forEach (th =>
        {
          th.classList.remove ("display");
          th.classList.add ("hide");
        });
    },

    // METHOD displayHeaders ()
    displayHeaders (v)
    {
      const wall = this.element[0],
            update = (v !== undefined),
            val = update ? v : this.settings.displayheaders,
            type = (val == 1) ? "show" : "hide";

      if (val == 1)
      {
        this.showHeaders ();

        wall.removeAttribute ("data-headersshift");

        if (update)
          H.waitForDOMUpdate (()=>
            {
              let w = this.getTDsWidth ();

              w += wall.querySelector("tbody.wpt th.wpt").clientWidth;

              wall.style.width = `${w}px`;
              wall.dataset.oldwidth = w;

              if (val == 1)
                this.fixSize ();
            });
      }
      else
      {
        // Save plugs shift width & height for absolutely positioned plugs
        if (!wall.dataset.headersshift)
        {
          //FIXME
          // Required to obtain the headers dimensions
          this.showHeaders ();

          const bbox = wall.querySelector("thead.wpt th.wpt").getBoundingClientRect ();

          if (bbox.width)
            wall.dataset.headersshift =
              JSON.stringify ({width: parseInt(bbox.width),
                               height: parseInt(bbox.height)});
        }

        this.hideHeaders ();

        wall.style.width = `${this.getTDsWidth()}px`;
      }

      if (val == 1)
        this.fixSize ();

      if (this.element.is (":visible"))
        this.menu ({from: "display", type: `${type}-headers`});

      if (update)
      {
        this.settings.displayheaders = val;
        wall.dataset.displayheaders = val;

        H.waitForDOMUpdate (()=>this.repositionPostitsPlugs ());

        H.fetch (
          "POST",
          `user/wall/${this.settings.id}/displayheaders`,
          {value: val});
      }
    },

  });

  /////////////////////////// AT LOAD INIT //////////////////////////////

  document.addEventListener ("DOMContentLoaded", ()=>
    {
      if (!H.isLoginPage ())
        setTimeout (()=>{

        WS.connect (
          `${location.protocol === 'https:' ? 'wss' : 'ws'}://${location.host}/app/ws?token=${wpt_userData.token}`, ()=>
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

            const directURLData = _getDirectURLData (),
                  loadSpecific = (directURLData && directURLData.type != "u"),
                  displayAccount = (directURLData && directURLData.type == "u");

            // Load previously opened walls
            $("<div/>").wall ("restorePreviousSession", loadSpecific);

            // Check if we must display a postit alert or a specific wall
            // (from direct URL).
            if (loadSpecific)
              $("<div/>").wall ("loadSpecific", directURLData);
            // Display account popup and highlight emails settings field
            // (from direct URL).
            else if (displayAccount)
            {
              // Remove special alert URL.
              history.pushState (null, null, "/");

              H.loadPopup ("account", {
                cb: ($p)=>
                      $p.find("[name='allow_emails']")
                        .parent().effect ("highlight", {duration: 5000})
              });
            }

            // Websocket heartbeat (every 15mn)
            setInterval (()=> fetch ("/api/user/ping"), 60*1000*15);

            // Display theme chooser if needed.
            if (!wpt_userData.settings.theme)
              setTimeout (()=> $settings.settings ("openThemeChooser"), 1000);

          });

        H.fixHeight ();

        const displayBtn = $(`<div id="normal-display-btn"><i class="fas fa-crosshairs fa-2x"></i> <span><?=_("Back to standard view")?></span></div>`)[0];

        // EVENT "click" on back to standard view button
        displayBtn.addEventListener ("click",
          (e)=> S.getCurrent("wall").wall("zoom",{type:"normal"}));

        document.body.insertBefore (displayBtn, document.body.firstChild);

        // Create input to upload wall file import
        H.createUploadElement({
          attrs: {accept: '.zip', className: 'import-wall'},
          onChange: (e) => {
            const el = e.target;

            if (!el.files || !el.files.length) return;

            H.getUploadedFiles(
              el.files,
              '\.zip$',
              (e, file) => {
                el.value = '';

                if (H.checkUploadFileSize({
                      size: e.total,
                      maxSize:<?=WPT_IMPORT_UPLOAD_MAX_SIZE?>
                    }) && e.target.result) {
                  H.fetchUpload(
                    'wall/import',
                    {
                      name: file.name,
                      size: file.size,
                      item_type: file.type,
                      content: e.target.result
                    },
                    // success cb
                    (d) => {
                      if (d.error_msg) {
                        return H.displayMsg({
                          title: `<?=_("Wall")?>`,
                          type: 'warning',
                          msg: d.error_msg,
                        });
                      }

                      $('<div/>').wall('open', {
                        lastWall: 1,
                        wallId: d.wallId,
                      });

                      H.displayMsg({
                        title: `<?=_("Wall")?>`,
                        type: 'success',
                        msg: `<?=_("The wall has been successfully imported")?>`
                      });
                    });
                }
              });
          },
        });

        // EVENT "click" on main content wopits icon
        document.getElementById("welcome").addEventListener ("click", (e)=>
          {
            H.closeMainMenu ();
            $("<div/>").wall ("openNamePopup");
          });

        // EVENT "click" on walls tab
        document.querySelector(".nav-tabs.walls")
          .addEventListener ("click", (e)=>
          {
            const el = e.target;

            // EVENT "click" on "close wall" tab button
            if (el.classList.contains ("close"))
            {
              e.stopImmediatePropagation ();

              H.openConfirmPopover ({
                item: $(el.closest(".nav-item").querySelector ("span.val")),
                placement: "left",
                title: `<i class="fas fa-times fa-fw"></i> <?=_("Close")?>`,
                content: `<?=_("Close this wall?")?>`,
                cb_ok: ()=> S.getCurrent("wall").wall ("close")
              });
            }
            // EVENT "click" on "new wall" tab button
            else if (el.parentNode.dataset.action=="new")
            {
              H.closeMainMenu ();
              $("<div/>").wall ("openNamePopup");
            }
          });

        // EVENT "click"
        document.body.addEventListener ("click", (e)=>
          {
            const el = e.target;

            // "click" on wall users view popup
            if (el.matches ("#wallUsersviewPopup *"))
            {
              e.stopImmediatePropagation ();

              // EVENT "click" on users list
              if (el.matches (".list-group-item,.list-group-item *"))
              {
                const li = el.tagName=="LI"?el:el.closest("li");

                H.openUserview ({
                  about: li.dataset.about,
                  picture: li.dataset.picture,
                  title: li.dataset.title
                });
              }
            }
          });

        // EVENT "click" on main menu items
        document.getElementById("main-menu").addEventListener ("click", (e)=>
          {
            const el = e.target,
                  $wall = S.getCurrent ("wall"),
                  li = el.tagName=="LI"?el:el.closest("li"),
                  action = li?li.dataset.action:null;

            // Nothing if menu item is disabled
            if (!li || li.querySelector ("a.disabled"))
              return;

            // LOCAL FUNCTION __manageCheckbox()
            const __manageCheckbox = (el, li, type)=>
              {
                if (el.tagName != "INPUT")
                {
                  const input = li.querySelector ("input");
                  input.checked = !input.checked;
                }

                S.getCurrent(type)[type] ("toggle");
              };

            switch (action)
            {
              case "zoom+":
                $wall.wall ("zoom", {type: "+"});
                $wall.wall ("ctrlMenu", "zoom-screen", "on");
                break;

              case "zoom-":
                $wall.wall ("zoom", {type: "-"});
                $wall.wall ("ctrlMenu", "zoom-screen", "on");
                break;

              case "zoom-screen":
                $wall.wall ("zoom", {type:"screen"});
                break;

              case "zoom-normal":
                $wall.wall ("zoom", {type: "normal"});
                break;

              case "chat":

                __manageCheckbox (el, li, "chat");

                break;

              case "filters":

                __manageCheckbox (el, li, "filters");

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
            }
          });
      }, 0);
      else
        // EVENT CLICK on about button in the login page
        document.querySelectorAll(`[data-action="about"]`).forEach (el=>
          el.addEventListener ("click",
            (e) => H.openModal({item: document.getElementById('aboutPopup')})));
  });

<?php echo $Plugin->getFooter ()?>
