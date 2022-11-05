<?php
/**
Javascript plugin - Main menu & walls

Scope: Global
Elements: .wall
Description: Manage main menu & walls
*/

require_once(__DIR__.'/../prepend.php');

$Plugin = new Wopits\jQueryPlugin('wall', '', 'wallElement');
echo $Plugin->getHeader();

?>

/////////////////////////////////// PRIVATE //////////////////////////////////

let _originalObject;

// METHOD _getNextActiveTab()
const _getNextActiveTab = (current) =>
  current.previousElementSibling ?
    current.previousElementSibling : current.nextElementSibling ?
      current.nextElementSibling : null;

// METHOD _getCellTemplate()
const _getCellTemplate = ({id, width, height}) => {
  return `<td scope="dzone" class="wpt size-init" style="width:${width}px;height:${height}px" data-id="cell-${id}"></td>`;
};

/////////////////////////////////// PUBLIC ///////////////////////////////////

<?=$Plugin->getPublicSection()?>

// Inherit from Wpt_accountForms
Plugin.prototype = Object.create(Wpt_accountForms.prototype);
Object.assign(Plugin.prototype, {
  // METHOD init()
  async init(args) {
    const $wall = this.element;
    const wall = $wall[0];
    const settings = this.settings;
    const wallId = settings.id;
    const access = settings.access;
    const writeAccess = this.canWrite();
    const displayHeaders = settings.displayheaders;

    H.hide(document.getElementById('welcome'));

    // Array of wall postits plugs
    settings.plugs = [];

    // Display mode (list-mode, postit-mode)
    wall.dataset.displaymode = settings.displaymode;

    // If headers are displayed (1) or hidden (0)
    wall.dataset.displayheaders = displayHeaders;

    // Wall title tab link
    settings.tabLink = document.querySelector(
      `.nav-tabs.walls a[href="#wall-${wallId}"]`);

    // If wa are restoring a previous session
    if (settings.restoring) {
      wall.dataset.restoring = 1;
    }

    // If wall is shared
    if (settings.shared) {
      this.setShared(true);
    }

    // Set wall name (with loading spinner)
    this.setName(settings.name, true);

    // Set wall description
    this.setDescription(settings.description);

    // Add wall floating menu
    $(`#wall-${wallId}`).find('.wall-menu').wmenu({access, wallPlugin: this});

    wall.style.width = settings.width ? `${settings.width}px` : 'auto';
    wall.style.backgroundColor = settings['background-color'] || 'auto';
    wall.innerHTML = `<thead class="wpt"><tr class="wpt"><th class="wpt ${displayHeaders ? 'display' : 'hide'}">&nbsp;</th></tr></thead><tbody class="wpt"></tbody>`

    if (H.haveMouse()) {
      $wall.draggable({
        distance: 10,
        cursor: 'grab',
//          cancel: writeAccess ? 'span,.title,.postit-edit' : null,
        cancel: writeAccess ? '.postit-tags' : null,
        start: () => {
          S.set('wall-dragging', true);
          this.hidePostitsPlugs();
        },
        stop: () => {
          S.set('dragging', true, 500);
          const f = S.getCurrent('filters')[0];
          if (!f || !f.classList.contains('plugs-hidden')) {
            this.showPostitsPlugs();
          }
          S.unset('wall-dragging');
        },
      });
    }

    // Create columns headers
    const hcols = settings.headers.cols;
    const tr = wall.querySelector('thead.wpt tr.wpt');
    wall.dataset.cols = hcols.length;
    hcols.forEach((header) => {
      const th = H.createElement('th', {
        className: `wpt ${displayHeaders ? 'display' : 'hide'}`,
      });
      tr.appendChild(th);
      $(th).header({
        access,
        wallId,
        item_type: 'col',
        id: header.id,
        title: header.title,
        picture: header.picture,
        wall: $wall,
      });
    });

    // Prepare rows array
    const rows = [];
    settings.cells.forEach((cell) => {
      const {item_row, item_col} = cell;
      if (!rows[item_row]) {
        rows[item_row] = [];
      }
      rows[item_row][item_col] = cell;
    });

    // Create rows with cells and postits
    const hrows = settings.headers.rows;
    wall.dataset.rows = hrows.length;
    rows.forEach((row, rowIdx) => {
      this.addRow(hrows[rowIdx], row);
      row.forEach((cell) => {
        const $cell = $(wall.querySelector(`[data-id="cell-${cell.id}"]`));
        cell.postits.forEach((postit) => {
          // No perf killer spread operator here!
          postit.init = true;
          postit.access = access;
          $cell.cell('addPostit', postit, true);
        });
      })
    });

    // Set wall name (remove loading spinner)
    this.setName(settings.name);

    if (settings.restoring) {
      delete settings.restoring;
      wall.removeAttribute('data-restoring');
    }

    return $wall;
  },

  // METHOD displayPostitAlert()
  displayPostitAlert({postitId, type}) {
    const postit = S.getCurrent('wall')[0].querySelector(
      `.postit[data-id="postit-${postitId}"]`);

    if (postit) {
      $(postit).postit('displayAlert', type);
    } else {
      H.displayMsg({
        title: `<?=_("Note")?>`,
        type: 'warning',
        msg: `<?=_("The note has been deleted")?>`,
      });
    }
  },

  // METHOD displayShareAlert()
  displayShareAlert(wallId) {
    const walls = wpt_userData.walls.list;
    let owner;

    for (const k in walls) {
      if (walls[k].id === wallId) {
        owner = walls[k].ownername;
        break;
      }
    }

    H.openConfirmPopover({
      type: 'info',
      item: document.querySelector('.walls a.active span.val'),
      title: `<i class="fas fa-share fa-fw"></i> <?=_("Sharing")?>`,
      content: owner ? `<?=_("%s shared this wall with you")?>`.replace("%s", owner) : `<?=_("This wall has been shared with you")?>`,
    });
  },

  // METHOD setActive()
  setActive() {
    S.reset();
    bootstrap.Tab.getOrCreateInstance(this.settings.tabLink).show();
    document.getElementById(`wall-${this.settings.id}`)
      .classList.add('active');
    this.menu({from: 'wall', type: 'have-wall'});
  },

  // METHOD ctrlMenu()
  ctrlMenu(action, type) {
    const m = document.querySelector(
      `.dropdown-menu li[data-action="${action}"] a`).classList;

    if (type === 'off') {
      m.add('disabled');
    } else {
      m.remove('disabled');
    }
  },

  // METHOD menu()
  menu(args) {
    const $wall = S.getCurrent('wall');
    const wmenu = $wall[0].parentNode.querySelector('.wall-menu');
    const menu = document.getElementById('main-menu');
    const menuNormal =
      menu.querySelector(`.dropdown-menu li[data-action="zoom-normal"] a`);
    const adminAccess = H.checkAccess(<?=WPT_WRIGHTS_ADMIN?>);

    switch (args.from) {
      // WALL menu
      case 'wall':
        if (!adminAccess) {
          menu.querySelector('[data-action="delete"] a')
            .classList.add('disabled');
          H.hide(wmenu.querySelector('[data-action="share"]'));
        }

        switch (args.type) {
          case 'no-wall':
            H.hide(document.querySelector('.nav.walls'));
            document.getElementById('dropdownView')
              .classList.add('disabled');
            $('#welcome').show(S.get('closing-all') ? null : 'fade');
            menu.querySelectorAll(
                '[data-action="delete"] a,'+
                '[data-action="close-walls"] a,'+
                '[data-action="clone"] a,'+
                '[data-action="export"] a').forEach(
              (el) => el.classList.add('disabled'));

              H.hide(wmenu.querySelector('[data-action="share"]'));
            break;
          case 'have-wall':
            if ($wall.length) {
              H.show(document.querySelector('.nav.walls'));
              document.getElementById('dropdownView')
                .classList.remove('disabled');
              this.menu({
                from: 'display',
                type: $wall[0].dataset.displaymode,
              });
            }

            if ($wall.length && $wall[0].dataset.shared) {
              menu.querySelector('[data-action="chat"] a')
                .classList.remove('disabled');
            } else {
              const $chat = S.getCurrent('chat');

              if ($chat.length) {
                $chat.chat('hide');
              }

              menu.querySelector('[data-action="chat"] a')
                .classList.add('disabled');
            }

            menu.querySelectorAll(
                '[data-action="clone"] a,'+
                '[data-action="export"] a,'+
                '[data-action="close-walls"] a').forEach(
              (el) => el.classList.remove('disabled'));

            if (adminAccess) {
              menu.querySelector('[data-action="delete"] a')
                .classList.remove('disabled');
              H.show(wmenu.querySelector('[data-action="share"]'),
                'inline-block');
            }
            break;
        }
        break;
      // Display menu
      case 'display':
        switch (args.type) {
          case 'unblock-externalref':
            H.show(wmenu.querySelector(`[data-action="block-externalref"]`),
              'inline-block');
            H.hide(wmenu.querySelector(
              `[data-action="unblock-externalref"]`));
            break;
          case 'block-externalref':
            H.hide(wmenu.querySelector(`[data-action="block-externalref"]`));
            H.show(wmenu.querySelector(`[data-action="unblock-externalref"]`),
              'inline-block');
            break;
          case 'show-headers':
            H.hide(wmenu.querySelector(`[data-action="show-headers"]`));
            H.show(wmenu.querySelector(`[data-action="hide-headers"]`),
              'inline-block');
            break;
          case 'hide-headers':
            H.hide(wmenu.querySelector(`[data-action="hide-headers"]`));
            H.show(wmenu.querySelector(`[data-action="show-headers"]`),
              'inline-block');
            break;
          case 'list-mode':
            H.hide(wmenu.querySelector(`li[data-action="list-mode"]`));
            H.show(wmenu.querySelector(`li[data-action="postit-mode"]`),
              'inline-block');
            break;
          case 'postit-mode':
            H.hide(wmenu.querySelector(`li[data-action="postit-mode"]`));
            H.show(wmenu.querySelector(`li[data-action="list-mode"]`),
              'inline-block');
            break;
          // Activate normal view item
          case 'zoom-normal-on':
            menuNormal.classList.remove('disabled');
            if (adminAccess) {
              menu.querySelectorAll(
                  '[data-action="chat"] a,'+
                  '[data-action="filters"] a').forEach(
                (el) => el.classList.add('disabled'));
            }
            break;
          // Deactivate normal view item
          case 'zoom-normal-off':
            menuNormal.classList.add('disabled');
            this.ctrlMenu('zoom-screen', 'on');
            if (adminAccess) {
              if ($wall[0].dataset.shared) {
                menu.querySelector(`[data-action="chat"] a`)
                  .classList.remove('disabled');
              }
              menu.querySelector(`[data-action="filters"] a`)
                .classList.remove('disabled');
            }
            break;
        }
      } 

      // If the user has decided to be invisible
      if (!wpt_userData.settings?.visible) {
        menu.querySelector('[data-action="chat"] a')
          .classList.add('disabled');
        H.hide(wmenu.querySelector(`[data-action="share"]`));
      }
  },

  // METHOD closeAllMenus()
  closeAllMenus() {
    const menu = S.getCurrent('walls')[0].querySelector('.postit-menu');

    if (menu) {
      $(menu.parentNode).postit('closeMenu');
    }
  },

  // METHOD refreshUsersview()
  refreshUsersview(count) {
    const el =
      this.element[0].parentNode.querySelectorAll('.usersviewcounts')[0];
    const divider = el.previousSibling;

    if (count) {
      H.show(divider);
      H.show(el);
      el.querySelector('span').innerText = count;

      document.title = `⚡${count} - wopits`;
    } else {
      H.hide(divider);
      H.hide(el);

      document.title = 'wopits';
    }
  },

  // METHOD UIPluginCtrl()
  UIPluginCtrl(selector, plugin, option, value, forceHandle = false) {
    const isDisabled = (option === 'disabled');

    this.element[0].querySelectorAll(selector).forEach((el) => {
      if (!el.classList.contains(`ui-${plugin}`)) return;

      if (forceHandle && isDisabled) {
        el.querySelectorAll(`.ui-${plugin}-handle`).forEach((el1) =>
          el1.style.visibility = value ? 'hidden' : 'visible');
      }
      $(el)[plugin]('option', option, value);
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

  // METHOD refreshPostitsPlugs()
  refreshPostitsPlugs(partial) {
    const wall = this.element[0];
    const f = S.getCurrent('filters')[0];

    if (f && f.classList.contains('plugs-hidden')) return;

    const applyZoom = Boolean(S.get('zoom-level'));
    const idsNew = {};
    const postits = {};

    // Retrieve all postits to optimize search
    wall.querySelectorAll('.postit').forEach((postit) =>
      postits[postit.dataset.id.split('-')[1]] = postit);

    (this.settings.plugs || []).forEach((plug) => {
      const {
        item_start,
        item_end,
        label: originalLabel,
        item_top,
        item_left,
        line_size,
        line_path,
        line_color,
        line_type,
      } = plug;
      const startId = item_start;
      const start = postits[startId];

      if (start) {
        const endId = item_end;
        const startPlugin = $(start).postit('getClass');
        const label = originalLabel || '...';

        idsNew[`${startId}${endId}`] = 1;

        if (!startPlugin.plugExists(endId)) {
          const end = postits[endId];

          if (end) {
            startPlugin.addPlug({
              startId,
              endId,
              label: {
                name: label,
                top: item_top,
                left: item_left,
              },
              obj: startPlugin.getPlugTemplate({
                hide: true,
                start,
                end,
                label,
                line_size,
                line_path,
                line_color,
                line_type,
              }),
            }, applyZoom);
          }
        } else {
          startPlugin.updatePlugLabel({
            endId,
            label,
            top: item_top,
            left: item_left,
          });
          startPlugin.updatePlugProperties({
            endId,
            line_type,
            size: line_size,
            path: line_path,
            color: line_color,
          });
        }
      }
    });

    // Remove obsolete plugs
    if (partial === false) {
      wall.querySelectorAll('.postit.with-plugs').forEach((postit) => {
        $(postit).postit('getSettings').plugs.forEach((plug) => {
          if (!idsNew[`${plug.startId}${plug.endId}`]) {
            $(wall.querySelector(
              `.postit[data-id="postit-${plug.endId}"]`))
                .postit('removePlug', plug, true);
          }
        });
      });
    }
  },

  // METHOD hidePostitsPlugs()
  hidePostitsPlugs() {
    this.element[0].querySelectorAll('.postit').forEach(
      (el) => $(el).postit('hidePlugs', true));
  },

  // METHOD showPostitsPlugs()
  showPostitsPlugs() {
    this.repositionPostitsPlugs();
    this.element[0].querySelectorAll('.postit').forEach(
      (el) => $(el).postit('showPlugs', true));
  },

  // METHOD showUserWriting()
  // FIXME
  showUserWriting(user) {
    const tab = document.querySelector(
      `.walls a[href="#wall-${this.settings.id}"]`);

    tab.classList.add('locked');
    tab.insertBefore(H.createElement('div',
      {className: 'user-writing main'},
      {userid: user.id},
      `<i class="fas fa-user-edit blink"></i> ${user.name}`),
      tab.firstChild);
  },

  // METHOD refresh()
  async refresh(d) {
    if (!d && this.settings.id) {
      d = await H.fetch('GET', `wall/${this.settings.id}`);
    }

    if (d && !d.error) {
      this._refresh(d);
    } else {
      H.displayNetworkErrorMsg();
    }
  },

  // METHOD _refresh()
  async _refresh(d) {
    const $wall = this.element;
    const wall = $wall[0];
    const wallIsVisible = $wall.is(':visible');

    // LOCAL FUNCTION __refreshWallBasicProperties()
    const __refreshWallBasicProperties = (d) => {
      this.setShared(d.shared);
      this.setName(d.name);
      this.setDescription(d.description);
    };

    this.settings.plugs = d.postits_plugs;

    // Partial wall update
    if (d.partial) {
      switch (d.partial) {
        // Postits
        case 'postit':
          const $postit = $(wall.querySelector(
                  `.postit[data-id="postit-${d.postit.id}"]`));
          const cell = wall.querySelector(
                  `td[data-id="cell-${d.postit.cells_id}"]`);

          // Rare case, when user have multiple sessions opened
          if (d.action !== 'insert' && !$postit.length)
            return;

          switch (d.action) {
            // Insert postit
            case 'insert':
              $(cell).cell('addPostit', d.postit, true);
              break;
            // Update postit
            case 'update':
              if (d.isResponse ||
                  cell.classList.contains('list-mode') ||
                  S.getCurrent('filters').is(':visible')) {
                $postit.postit('update', d.postit, {id: d.postit.cells_id});
              } else {
                $postit.hide('fade', 250, () => {
                  $postit.postit('update', d.postit, {id: d.postit.cells_id});
                  $postit.show('fade', 250,
                    ()=> $postit.postit('repositionPlugs'));
                });
              }
              break;
            // Remove postit
            case 'delete':
              $postit.postit('remove');
              break;
          }
          break;
        // Wall
        case 'wall':
          // Col/row has been moved
          if (d.action === 'movecolrow') {
            if (!d.isResponse) {
              $wall.find(`th[data-id="header-${d.header.id}"]`)
                .header('moveColRow', d.move, true);
            }
          } else {
            __refreshWallBasicProperties(d.wall);
          }
          break;
        //FIXME
        // plugs
        case 'plugs': break;
      }

    // Full wall update
    } else if (!d.removed) {
      const wallId = this.settings.id;
      const access = this.settings.access;
      const postitsIds = [];
      const rows = [];
      const displayHeaders = this.settings.displayheaders;

      wall.dataset.cols = d.headers.cols.length;
      wall.dataset.rows = d.headers.rows.length;
      wall.dataset.oldwidth = d.width;

      __refreshWallBasicProperties(d);

      // Refresh headers
      d.headers.cols.forEach((header) => {
        let th = wall.querySelector(
          `thead.wpt th.wpt[data-id="header-${header.id}"]`);

        if (!th) {
          th = H.createElement('th',
            {className: `wpt ${displayHeaders ? 'display' : 'hide'}`});

          wall.querySelectorAll('thead.wpt tr.wpt').forEach(
            (el) => el.appendChild(th));

          $(th).header({
            item_type: 'col',
            id: header.id,
            wall: $wall,
            wallId: wallId,
            title: header.title,
            picture: header.picture
          });
        } else {
          $(th).header('update', header);
        }
      });

      // Remove deleted rows
      const rowsHeadersIds = d.headers.rows.map((el) => el.id);
      wall.querySelectorAll('tbody.wpt th.wpt').forEach((th) => {
        if (!rowsHeadersIds.includes($(th).header('getId'))) {
          $(wall.querySelectorAll(
            `tbody.wpt tr.wpt`)[th.parentNode.rowIndex - 1]).cell('remove');
        }
      });

      // Remove deleted columns
      const colsHeadersIds = d.headers.cols.map((el) => el.id);
      wall.querySelectorAll('thead.wpt th.wpt').forEach((th) => {
        const idx = th.cellIndex;

        if (idx > 0 && !colsHeadersIds.includes($(th).header('getId'))) {
          wall.querySelectorAll('thead.wpt th.wpt')[idx].remove();
          wall.querySelectorAll('tbody.wpt tr.wpt').forEach((tr) =>
            $(tr).find(`td.wpt:eq(${idx - 1})`).cell('remove'));
        }
      });

      d.cells.forEach((cell) => {
        const irow = cell.item_row;

        // Get all postits ids for this cell
        cell.postits.forEach(({id}) => postitsIds.push(id));

        if (rows[irow] === undefined) {
          rows[irow] = [];
        }

        rows[irow][cell.item_col] = cell;
      });

      const userSettings = this.settings.usersettings || {};
      rows.forEach((row, i) => {
        const header = d.headers.rows[i];

        if (!wall.querySelector(`td.wpt[data-id="cell-${row[0].id}"]`)) {
          this.addRow(header, row);
        } else {
          $wall.find(`tbody.wpt th.wpt[data-id="header-${header.id}"]`)
            .header('update', header);
        }

        row.forEach((cell) => {
          let $cell = $wall.find(`td.wpt[data-id="cell-${cell.id}"]`);

          // If new cell, add it
          if (!$cell.length) {
            const cellId = cell.id;

            $cell = $(_getCellTemplate(cell));
            wall.querySelectorAll(
              `tbody.wpt tr.wpt`)[cell.item_row].appendChild($cell[0]);

            // Init cell
            $cell.cell({
              wallId,
              id: cellId,
              access: access,
              usersettings: userSettings[`cell-${cellId}`] || {},
              wall: $wall,
            });

          // else update cell
          } else {
            $cell.cell('update', cell);

            // Remove deleted post-its
            $cell[0].querySelectorAll('.postit').forEach((p) => {
              if (!postitsIds.includes($(p).postit('getId'))) {
                $(p).postit('remove');
              }
            });
          }

          cell.postits.forEach((postit) => {
            const p = wall.querySelector(
              `.postit[data-id="postit-${postit.id}"]`);

            if (!p) {
              $cell.cell('addPostit', postit, true);
            } else {
              $(p).postit('update', postit, {id: cell.id, obj: $cell});
            }
          });
        });
      });
    }

    // Refresh super menu tool
    // FIXME Useful?
    // S.getCurrent('mmenu').mmenu('refresh');

    // Set wall menu visible
    S.getCurrent('wmenu')[0].style.visibility = 'visible';

    this.fixSize();

    // Apply display mode
    this.refreshCellsToggleDisplayMode();

    if (!d.isResponse && !d.partial && S.get('zoom-level')) {
      const zoom = document.querySelector('.tab-content.walls');

      if (zoom &&
          zoom.dataset.zoomlevelorigin &&
          zoom.dataset.zoomtype === 'screen') {
        this.zoom({type: 'screen'});
      }
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

    if (wallIsVisible && d.postits_plugs) {
      // Refresh postits relations
      this.refreshPostitsPlugs(d.partial && d.partial !== 'plugs');
    } else {
      this.repositionPostitsPlugs();
    }

    this.refreshCellsToggleDisplayMode();

    // Re-apply filters
    const $f = S.getCurrent('filters');
    if (H.isVisible($f[0])) {
      $f.filters('apply', {norefresh: true});
    }
  },

  // METHOD refreshCellsToggleDisplayMode()
  refreshCellsToggleDisplayMode() {
    this.element[0].querySelectorAll('td.wpt.list-mode').forEach((cell) => {
      $(cell).cell('toggleDisplayMode', true);
    });

    if (S.get('zoom-level')) {
      this.UIPluginCtrl(
        '.cell-list-mode ul', 'sortable', 'disabled', true, true);
    }
  },

  // METHOD openCloseAllWallsPopup()
  openCloseAllWallsPopup() {
    H.openConfirmPopup({
      icon: 'times',
      content: `<?=_("Close the walls?")?>`,
      onConfirm: () => this.closeAllWalls(),
    });
  },

  // METHOD closeAllWalls()
  closeAllWalls(saveSession = true) {
    // Tell the other methods that we are massively closing the walls
    S.set('closing-all', true);
    document.querySelectorAll('table.wall').forEach((wall) =>
      $(wall).wall('close'));
    S.unset('closing-all');

    if (saveSession) {
      $('#settingsPopup').settings('saveOpenedWalls', null, false);
    }

    S.reset();
  },

  // METHOD close()
  close() {
    const activeTabId = `wall-${this.settings.id}`;
    const activeTab = document.querySelector(`a[href="#${activeTabId}"]`);
    const newActiveTab = _getNextActiveTab(activeTab);
    const $chat = S.getCurrent('chat');

    if (S.get('zoom-level')) {
      this.zoom({type: 'normal'});
    }

    S.getCurrent('mmenu').mmenu('close');

    if ($chat.is(':visible')) {
      $chat.chat('leave');
    }

    // If account popup is opened, do not close it: we are dealing with the
    // "invisible mode" option.
    document.querySelectorAll('.modal.show:not(#accountPopup)')
      .forEach((m) => bootstrap.Modal.getInstance(m).hide());

    this.removePostitsPlugs();

    activeTab.remove();
    document.getElementById(activeTabId).remove();

    // No more wall to display
    if (!document.querySelector('.wall')) {
      this.zoom({type: 'normal', noalert: true});
      this.menu({from: 'wall', type: 'no-wall'});
    // Active another tabs after deletion
    } else {
      bootstrap.Tab.getOrCreateInstance(newActiveTab).show();
    }

    // If we are not massively closing all walls
    if (!S.get('closing-all')) {
      H.fixHeight();
      $('#settingsPopup').settings('saveOpenedWalls');
      S.reset();
    }
  },

  // METHOD openDeletePopup()
  openDeletePopup() {
    this.edit(() => {
      const args = {
        onClose: () => this.unedit(),
        onConfirm: () => this.delete(),
      };

      // H.openConfirmPopover() does not display the popover on some
      // devices when button menu is visible.
      if (H.isMainMenuCollapsed()) {
        H.openConfirmPopup({...args,
          icon: 'trash',
          content: `<?=_("Delete the wall?")?>`,
        });
      } else {
        H.openConfirmPopover({...args,
          item: this.settings.tabLink.querySelector('span.val'),
          placement: 'left',
          title: `<i class="fas fa-trash fa-fw"></i> <?=_("Delete")?>`,
          content: `<?=_("Delete this wall?")?>`,
        });
      }
    }, null, true);
  },

  // METHOD delete()
  delete() {
    this.element[0].dataset.todelete = true;

    this.unedit(() => {
      const sPlugin = $('#settingsPopup').settings('getClass');
      const wallId = this.settings.id;

      this.close();

      sPlugin.removeWallBackground(wallId);
      sPlugin.removeRecentWall(wallId);
    });
  },

  // METHOD createColRow()
  createColRow(type) {
    const wall = this.element[0];

    if (Number(wall.dataset.rows) *
        Number(wall.dataset.cols) >= <?=WPT_MAX_CELLS?>) {
      return H.displayMsg({
               title: `<?=_("Wall")?>`,
               type: 'warning',
               msg: `<?=_("For performance reasons, a wall cannot contain more than %s cells")?>`.replace('%s', <?=WPT_MAX_CELLS?>)
             });
    }

    H.request_ws(
      'PUT',
      `wall/${this.settings.id}/${type}`,
      null,
      // TODO Scroll to the exact ending of the new added col or row
      () => S.getCurrent('walls')[(type=== 'col') ?
              'scrollLeft' : 'scrollTop'](30000),
    );
  },

  // METHOD addRow()
  addRow(header, cols) {
    const settings = this.settings;
    const $wall = this.element;
    const wallId = settings.id;
    const displayHeaders = settings.displayheaders;
    let tds = '';

    cols.forEach((col) => tds += _getCellTemplate(col));

    const row = H.createElement('tr',
      {className: `wpt`},
      null,
      `<th class="wpt ${displayHeaders ? 'display' : 'hide'}"></th>${tds}`);

    // Add row
    $wall[0].querySelector('tbody.wpt').appendChild(row);
    $(row).find('th.wpt:eq(0)').header({
      wallId,
      access: settings.access,
      item_type: 'row',
      id: header.id,
      wall: $wall,
      title: header.title,
      picture: header.picture
    });

    // Init cells
    const userSettings = settings.usersettings || {};
    row.querySelectorAll('td.wpt').forEach((cell) => {
      const cellId = cell.dataset.id.substring(5);
      $(cell).cell({
          wallId,
          id: cellId,
          access: settings.access,
          usersettings: userSettings[`cell-${cellId}`] || {},
          wall: $wall,
        });
    });
  },

  // METHOD deleteRow()
  deleteRow(rowIdx) {
    const th = this.element[0]
      .querySelectorAll(`tbody.wpt tr.wpt`)[rowIdx]
      .querySelector(`th.wpt`);

    $(th).header('removeContentKeepingWallSize', {
      oldW: th.offsetWidth,
      cb: () => {
        th.querySelectorAll('.img').forEach((el) => el.remove());
        th.querySelector('.title').innerHTML = '&nbsp;';
      },
    });

    H.request_ws(
      'DELETE',
      `wall/${this.settings.id}/row/${rowIdx}`,
      {wall: {width: Math.trunc(this.element.outerWidth())}});
  },

  // METHOD deleteCol()
  deleteCol(idx) {
    const wall = this.element[0];

    H.request_ws(
      'DELETE',
      `wall/${this.settings.id}/col/${idx-1}`,
      {
        wall: {width: Math.trunc(wall.clientWidth - 1)},
        width: Math.trunc(
          wall.querySelectorAll(`thead.wpt tr.wpt th.wpt`)[idx]
            .getBoundingClientRect().width),
      });
  },

  // METHOD addNew()
  async addNew(args) {
    const tabs = document.querySelector('.nav-tabs.walls');
    const $tabs = $(tabs);
    const load = (args.type === 'load');
    const service = load ? `wall/${args.wallId}` : 'wall';
    const data = load ? null : {name: args.name, grid: Boolean(args.grid)};

    S.reset();

    if (data) {
      if (data.grid) {
        data.colsCount = args.dim.colsCount;
        data.rowsCount = args.dim.rowsCount;
      } else {
        const w = args.dim.width;
        const h = args.dim.height;

        data.width = w ? w + 52 : window.outerWidth - 50
        if (data.width < 300) {
          data.width = 300;
        }

        data.height = h ? h : window.innerHeight;
        if (data.height < 200) {
          data.height = 200;
        }
      }
    }

    if (args.restoring) {
      tabs.insertBefore(H.createElement('a',
        {href: `#wall-${args.wallId}`, className: 'nav-item nav-link'},
        {'bsToggle': 'tab'},
        `<span class="icon"></span><span class="val"></span>`,
      ), tabs.firstChild);
    }

    const r = await H.fetch(load ? 'GET' : 'PUT', service, data);

    // Return on error
    if (r.error) {
      if (r.error_msg) {
        H.displayMsg({
          title: `<?=_("Wall")?>`,
          type: 'warning',
          msg: r.error_msg,
        });
      }
      return;
    }

    // If the wall does not exists anymore remove it and return
    if (r.removed) {
      const tmp = tabs.querySelector(`a[href="#wall-${args.wallId}"]`);

      tmp && tmp.remove();

      if (args.restoring &&
          wpt_userData.settings.activeWall === args.wallId) {
        const el = tabs.querySelector('.nav-item');

        if (el) {
          el.dispatchEvent(new Event('mousedown'));
          bootstrap.Tab.getOrCreateInstance(el).show();
        }
      }

      // Save opened walls when all walls will be loaded
      if (args.restoring) {
        S.set('save-opened-walls', true);
      }

      H.displayMsg({
        title: `<?=_("Wall")?>`,
        type: 'warning',
        msg: `<?=_("Some walls are no longer available")?>`,
      });

      return;
    }

    const wallDiv = H.createElement('div',
      {id: `wall-${r.id}`, className: 'tab-pane'},
      null,
      `<ul class="wall-menu shadow"></ul><div class="toolbox chat shadow"></div><div class="toolbox filters shadow"></div><table class="wall" data-id="wall-${r.id}" data-access="${r.access}"></table>`,
    );

    // Add main wall container (tab pane)
    document.querySelector('.tab-content.walls').appendChild(wallDiv);

    if (!args.restoring) {
      tabs.insertBefore (H.createElement('a',
        {href: `#wall-${r.id}`, className: 'nav-item nav-link'},
        {'bsToggle': 'tab'}, `<span class="icon"></span><span class="val"></span>`,
      ), tabs.firstChild);
    }

    const wallTab = tabs.querySelector(`a[href="#wall-${r.id}"]`);

    wallTab.setAttribute('data-access', r.access);

    // Add close wall button
    wallTab.insertBefore(H.createElement('button',
      {
        type: 'button',
        className: 'close',
        title: `<?=_("Close this wall")?>`,
      },
      null,
      `<span class="close">&times;</span>`,
    ), wallTab.firstChild);

    r['background-color'] =
      $("#settingsPopup").settings('get', 'wall-background', r.id);

    $(wallDiv.querySelector('.chat')).chat({wallId: r.id});
    $(wallDiv.querySelector('.filters')).filters();

    return await $(wallDiv.querySelector('.wall')).wall({...args, ...r});
  },

  // METHOD create()
  async create(args) {
    return await this.open({...args, type: 'create', noPostProcess: true});
  },

  // METHOD open()
  async open(args) {
    const options = {
      ...args,
      // "load" or "create". "load" by default
      type: (args.type !== undefined) ? args.type : 'load',
    };
    const $wall = await this.addNew(options);

    if ($wall && !args.noPostProcess) {
      $wall.wall('postProcessLastWall', options);
    }

    return $wall;
  },

  // METHOD postProcessLastWall()
  async postProcessLastWall(args = {}) {
    const wall = this.element[0];
    const awSettingsId = Number(wpt_userData.settings.activeWall);
    let activeWall;

    if (!args.restoring || args.then || S.get('save-opened-walls')) {
      activeWall = wall;
    } else {
      activeWall = document.querySelector(
        `[data-id="wall-${wpt_userData.settings.activeWall}"]`) || wall;
    }

    const awPlugin = $(activeWall).wall('getClass');
    const awId = awPlugin.getId();
    awPlugin.setActive();
    await awPlugin.refresh();
    awPlugin.displayExternalRef();
    awPlugin.displayHeaders();

    if (S.get('save-opened-walls') || awSettingsId !== awId) {
      S.unset('save-opened-walls');
      $('#settingsPopup').settings('saveOpenedWalls');
    }

    H.fixHeight();

    // Display postit dealine alert or specific wall if needed.
    args.then && args.then();

    // Set wall users view count if needed
    const viewcount = WS.popResponse(`viewcount-wall-${awId}`);
    if (viewcount) {
      awPlugin.refreshUsersview(viewcount);
    }
  },

  // METHOD clone()
  async clone() {
    const r = await H.fetch(
    'PUT',
    `wall/${this.settings.id}/clone`);

    if (r.error) {
      if (r.error_msg) {
        H.displayMsg ({
          title: `<?=_("Wall")?>`,
          type: 'warning',
          msg: r.error_msg,
        });
      }
      return;
    }

    // try / catch
    const $wall = await $('<div/>').wall('open', {wallId: r.wallId});

    if ($wall) {
      H.displayMsg({
        title: `<?=_("Wall")?>`,
        type: 'success',
        msg: `<?=_("The wall has been successfully cloned")?>`,
      });
    }
  },

  // METHOD export()
  // Format: wopits_export-{wall_name}-{ymd}_{hms}.zip
  export() {
    const now = H.getUserDate(moment().unix(), null, 'YMMDD_HHmmss');
    const wallName = this.getName().replace(/\s+/g, '_');

    H.download({
      url: `/wall/${this.settings.id}/export`,
      fname: `wopits_export-${wallName}-${now}.zip`,
      msg: `<?=_("An error occurred during the export")?>`,
    });
  },

  // METHOD import()
  import () {
    document.querySelector('.upload.import-wall').click();
  },

  // METHOD restorePreviousSession()
  async restorePreviousSession() {
    const walls = wpt_userData.settings.openedWalls || [];

    if (walls.length) {
      let $wall;

      await Promise.all(walls.reverse().map(async (wallId) => {
        $wall = await this.open({
          wallId,
          restoring: true,
          noPostProcess: true,
        });
      }));

      if ($wall) {
        $wall.wall('postProcessLastWall', {restoring: true});
      }
    }
  },

  // METHOD isOpened()
  isOpened(wallId) {
    return (wpt_userData.settings.openedWalls || []).includes(String(wallId));
  },

  // METHOD loadSpecific()
  async loadSpecific(args, noDelay) {
    const {wallId, postitId, commentId} = args;
    let type;

    // LOCAL FUNCTION __displayAlert()
    const __displayAlert = () => {
      if (postitId) {
        switch (args.type) {
          case 'a': type = 'deadline'; break;
          case 'c': type = 'comment'; break;
          case 'w': type = 'worker'; break;
          default: type = args.type;
        }

        this.displayPostitAlert({postitId, type});
      } else {
        this.displayShareAlert(wallId);
      }
    };

    if (!this.isOpened(wallId)) {
      const $wall = await this.open({wallId, noPostProcess: true});
      if ($wall) {
        $wall.wall('postProcessLastWall', {then: __displayAlert});
      }
    } else {
      // Set wall current if needed
      if (wallId !== S.getCurrent('wall').wall('getId')) {
        const el = document.querySelector(`a[href="#wall-${wallId}"]`);
        el.dispatchEvent(new Event('mousedown', {bubbles: true}));
        el.dispatchEvent(new Event('click', {bubbles: true}));
      }

      __displayAlert();
    }

    // Remove special alert URL
    history.pushState(null, null, '/');
  },

  // METHOD refreshUserWallsData()
  async refreshUserWallsData(then) {
    const r = await H.fetch('GET', 'wall');
    if (r && !r.error) {
      wpt_userData.walls = {list: r.list || []};
      then && then();
    }
  },

  // METHOD openOpenWallPopup()
  openOpenWallPopup() {
    this.refreshUserWallsData(() => {
      H.loadPopup ('owall', {
        cb: ($p) => {
          $p.owall('reset');
          $p.owall('displayWalls');
          $p.owall('controlFiltersButtons');
        }
      });
    });
  },

  // METHOD openCreatePopup()
  openCreatePopup() {
    H.closeMainMenu();
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
            (el) => H.hide(el));

          if (btn.checked) {
            btn.parentNode.classList.remove('disabled');
          } else {
            btn.parentNode.classList.add('disabled');
          }

          H.show(
            p.querySelector(btn.checked ? '.cols-rows' : '.width-height'),
            'flex');
        });
      },
      cb: ($p) => $p[0].dataset.noclosure = true,
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

  // METHOD openPropertiesPopup()
  openPropertiesPopup (args = {}) {
    const __open = () => {
      H.loadPopup('wprop', {
        open: false,
        cb: ($p) => $p.wprop('open', {...args, wall: this.element}),
      });
    };

    if (H.checkAccess(<?=WPT_WRIGHTS_ADMIN?>)) {
      this.edit(__open);
    } else {
      __open();
    }
  },

  // METHOD saveProperties()
  // FIXME Issues with walls having 1 row and 1 col
  saveProperties() {
    const popup = document.getElementById('wpropPopup');
    const $popup = $(popup);
    const $inputs = $popup.find('input:visible');
    const Form = new Wpt_accountForms();
    let ret = true;

    popup.dataset.noclosure = true;

    if (Form.checkRequired($inputs) && Form.validForm($inputs)) {
      const oldName = this.getName();
      const oldDescription = this.getDescription();
      const name = H.noHTML(popup.querySelector('.name input').value);
      const description = H.noHTML(
                popup.querySelector('.description textarea').value);

      this.setName(name);
      this.setDescription(description);

      this.unedit(
        () => popup.dataset.uneditdone = 1,
        () => {
          this.setName(oldName);
          this.setDescription(oldDescription);
          //FIXME
          this.edit();
        });

      // If wall width & height
      if ($inputs.length > 1) {
        const $wall = this.element;
        const wall = $wall[0];
        const cell = wall.querySelector('td.wpt');
        const oldW = cell.offsetWidth;
        const w = parseInt($inputs[1].value);
        const h = parseInt($inputs[2].value);

        if (w !== oldW || h !== cell.offsetHeight) {
          const cellPlugin = $(cell).cell('getClass');

          // LOCAL FUNCTION __resize()
          const __resize = (args) => {
            $wall.find('thead.wpt th.wpt:eq(1),td.wpt')
                .css('width', args.newW);
            wall.querySelector('td.wpt .ui-resizable-s')
                .style.width = `${args.newW + 2}px`;

            if (args.newH) {
             $wall.find('tbody.wpt th.wpt,td.wpt').css('height', args.newH);
             wall.querySelector('td.wpt .ui-resizable-e')
                 .style.height = `${args.newH + 2}px`;
            }

            this.fixSize(args.oldW, args.newW);
          };

          __resize({newW: w, oldW: oldW, newH: h});

          const tmp = wall.querySelector('td.wpt').offsetWidth;
          if (tmp !== w) {
            __resize({newW: tmp, oldW: w});
          }

          cellPlugin.edit();
          cellPlugin.reorganize();
          cellPlugin.unedit();
        }
      }
    } else {
      ret = false;
    }

    return ret;
  },

  // METHOD getName()
  getName() {
    return this.settings.tabLink.querySelector('span.val').innerText;
  },

  // METHOD setName()
  setName(name, noIcon) {
    const div = this.settings.tabLink;
    const notOwner = (this.settings.ownerid !== wpt_userData.id);

    let html = noIcon ?
        `<i class="fas fa-cog fa-spin fa-fw"></i>` :
        H.getAccessIcon(this.settings.access);

    if (!noIcon && notOwner)
      html = `<i class="fas fa-user-slash wallname-icon" title="<?=_("You are not the creator of this wall")?>"></i>${html}`;

    div.querySelector('span.icon').innerHTML = html;
    div.querySelector('span.val').innerText = H.noHTML(name);

    if (!noIcon) {
      this.refreshSharedIcon();
    }
  },

  // METHOD isShared()
  isShared() {
    return Boolean(this.element[0].dataset.shared);
  },

  // METHOD setShared()
  setShared(isShared) {
    const wall = this.element[0];

    if (isShared) {
      wall.dataset.shared = 1;
    } else {
      wall.removeAttribute('data-shared');
    }

    this.refreshPostitsWorkersIcon();
    this.refreshSharedIcon();
  },

  // METHOD refreshPostitsWorkersIcon()
  refreshPostitsWorkersIcon() {
    const display = this.isShared();

    this.element[0].querySelectorAll('.postit').forEach((p) => {
      const pMenu = $(p).postit('getSettings').Menu;

      p.querySelector('.pwork').style.display =
        display ? 'inline-block' : 'none';

      if (pMenu) {
        pMenu.$menu[0].querySelector(`[data-action="pwork"]`)
          .style.display = display ? 'inline-block' : 'none';
      }
    });
  },

  // METHOD refreshSharedIcon()
  refreshSharedIcon() {
    const span = this.settings.tabLink.querySelector('span.icon');
    const wIcon = span.querySelector('.wallname-icon');

    if (this.isShared ()) {
      if (!wIcon) {
        span.prepend(H.createElement('i', {
          className: 'fas fa-share wallname-icon',
          title: `<?=_("The wall is shared")?>`,
        }));
      }
    } else if (wIcon) {
      wIcon.remove();
    }
  },

  // METHOD getDescription()
  getDescription() {
    return this.settings.tabLink.dataset.description;
  },

  // METHOD setDescription()
  setDescription(description) {
    this.settings.tabLink.dataset.description = H.noHTML(description);
  },

  // METHOD fixSize()
  fixSize(oldW, newW) {
    const wall = this.element[0];
    let w;

    // If no header, substract header width from wall width
    if (!this.settings.displayheaders) {
      w = this.getTDsWidth();
    } else if (!(w = Number(wall.dataset.oldwidth))) {
      w = wall.offsetWidth;
    }

    if (newW) {
      if (newW > oldW) {
        w += (newW - oldW);
      } else if (newW < oldW) {
        w -= (oldW - newW);
      }
    }

    wall.dataset.oldwidth = w;
    wall.style.width = `${w}px`;
    wall.style.maxWidth = `${w}px`;
  },

  // METHOD setPostitsDisplayMode()
  setPostitsDisplayMode(type) {
    this.menu({from: 'display', type: type});

    this.element[0].dataset.displaymode = type;

    this.element.find('td.wpt').each(function() {
      $(this).cell('setPostitsDisplayMode', type);
    });

    // Re-apply filters
    const $f = S.getCurrent('filters');
    if (H.isVisible($f[0])) {
      $f.filters('apply', {norefresh: true});
    }

    H.fetch(
      'POST',
      `user/wall/${this.settings.id}/displaymode`,
      {value: type});
  },

//FIXME TODO
  // METHOD zoom()
  zoom(args) {
    const $zoom = $('.tab-content.walls');
    const zoom0 = $zoom[0];
    const $wall = this.element;
    const wall = $wall[0];
    const from = args.from;
    const type = args.type;
    const noalert = Boolean(args.noalert);
    const zoomStep = (Boolean(args.step)) ? args.step : 0.2;
    const writeAccess = this.canWrite();

    if (!args.step) {
      wall.style.top = 0;
      wall.style.left = '15px';
    }

    if (type === 'screen') {
      return this.screen();
    }

    let level = zoom0.style.transform;
    level = (!level || level === 'none') ?
      1 : Number(level.match(/[0-9\.]+/)[0]);

    if (!zoom0.dataset.zoomlevelorigin) {
      if (!S.get('old-width')) {
        S.set('old-styles', {
          width: zoom0.style.width,
          transform: zoom0.style.transform,
        });
      }

      if (writeAccess && !noalert) {
        H.displayMsg({
          title: `<?=_("Zoom")?>`,
          type: 'info',
          msg: `<?=_("Some features are not available when zoom is enabled")?>`,
        });
      }

      zoom0.dataset.zoomlevelorigin = level;

      zoom0.style.width = '30000px';

      zoom0.querySelectorAll('th.wpt').forEach ((th) => {
        th.style.pointerEvents = 'none';

        if (writeAccess) {
          th.style.opacity = .6;
        }
      });

      // Deactivate some features
      if (wall.classList.contains('ui-draggable')) {
        $wall.draggable('disable');
      }
      wall.querySelectorAll('.cell-menu').forEach((el) => H.hide(el));
      this.UIPluginCtrl('.cell-list-mode ul',
                        'sortable', 'disabled', true, true);
      this.UIPluginCtrl('td,.postit',
                        'resizable', 'disabled', true);
      this.UIPluginCtrl('.wall,.postit',
                        'draggable', 'disabled', true);
    }

    if (from) {
      zoom0.dataset.zoomtype = from;
    } else {
      zoom0.removeAttribute('data-zoomtype');
    }

    if (type !== 'normal') {
      this.menu({from: 'display', type: 'zoom-normal-on'});
    }

    switch (type) {
      case '+': level += zoomStep; break;
      case '-': level -= zoomStep; break;
      case 'normal': level = Number(zoom0.dataset.zoomlevelorigin); break;
    }

    if (level <= 0) {
      return H.displayMsg({
        title: `<?=_("Zoom")?>`,
        type: 'warning',
        msg: `<?=_("The minimum zoom has been reached")?>`,
      });
    }

    S.set('zoom-level', level);

    // FIXME level == zoom0.dataset.zoomlevelorigin
    if (from !== 'screen' && level == zoom0.dataset.zoomlevelorigin) {
      const $walls = S.getCurrent('walls');

      S.unset('zoom-level');

      $walls[0].style.overflow = 'auto';

      this.hidePostitsPlugs();

      setTimeout(() => {
        $('#normal-display-btn').hide();
        S.getCurrent('mmenu').mmenu('checkAllowedActions');
      }, 150);

      zoom0.removeAttribute('data-zoomtype');
      zoom0.removeAttribute('data-zoomlevelorigin');

      this.menu({from: 'display', type: 'zoom-normal-off'});

      if (writeAccess && !noalert) {
        H.displayMsg({
          title: `<?=_("Zoom")?>`,
          type: 'info',
          msg: `<?=_("All features are available again")?>`,
        });
      }

      zoom0.style.width = S.get('old-styles').width;
      zoom0.style.transform = S.get('old-styles').transform;
      S.unset('old-styles');

      zoom0.querySelectorAll('th.wpt').forEach ((th) => {
        th.style.pointerEvents = 'auto';

        if (writeAccess) {
          th.style.opacity = 1;
        }
      });

      // Reavtivate some previously deactivated features
      if (wall.classList.contains('ui-draggable')) {
        $wall.draggable('enable');
      }
      wall.querySelectorAll('.cell-menu').forEach((el) => H.show(el));
      this.UIPluginCtrl('.cell-list-mode ul',
                        'sortable', 'disabled', false, true);
      this.UIPluginCtrl('td,.postit',
                        'resizable', 'disabled', false);
      this.UIPluginCtrl('.wall,.postit',
                        'draggable', 'disabled', false);

      $walls.scrollLeft(0).scrollTop(0);

      $('<div/>').postit('applyZoom');
    } else {
      if (from !== 'screen') {
        this.hidePostitsPlugs();

        $('<div/>').postit('applyZoom');
        $('#normal-display-btn').show();
        S.getCurrent('mmenu').mmenu('checkAllowedActions');
      }

      zoom0.style.transformOrigin = 'top left';
      zoom0.style.transform = `scale(${level})`;

      $('#walls').scrollLeft(
        ((30000 * level) / 2 - window.innerWidth / 2) + 20);
    }
  },

  // METHOD screen()
  // TODO optimization
  screen() {
    const step = .005;
    const wall = this.element[0];
    const walls = S.getCurrent('walls')[0];
    let position = wall.getBoundingClientRect();

    this.hidePostitsPlugs();

    $(walls).scrollLeft(0).scrollTop(0);

    if (position.bottom - position.top < walls.clientHeight &&
        position.right < walls.clientWidth) {
      do { 
        this.zoom({from: 'screen', type: '+', step: step});
        position = wall.getBoundingClientRect();
      } while (position.bottom - position.top < walls.clientHeight - 20 &&
               position.right < walls.clientWidth - 5);
    } else {
      do { 
        this.zoom ({from: 'screen', type: '-', step: step});
        position = wall.getBoundingClientRect();
      } while (!(position.bottom - position.top < walls.clientHeight - 20 &&
                 position.right < walls.clientWidth - 5));
    }

    $('#normal-display-btn').show();
    this.ctrlMenu('zoom-screen', 'off');
    S.getCurrent('mmenu').mmenu('checkAllowedActions');

    $('#walls').scrollLeft (
      ((30000 * S.get('zoom-level')) / 2 - window.innerWidth / 2) + 20);

    $('<div/>').postit('applyZoom');

    walls.style.overflow = 'hidden';
  },

  // METHOD edit()
  edit(then, onError, todelete = false) {
    _originalObject = this.serialize();

    if (!this.isShared()) {
      then && then();
      return;
    }

    H.request_ws(
      'PUT',
      `wall/${this.settings.id}/editQueue/wall/${this.settings.id}`,
      {todelete},
      // success cb
      (d) => {
        if (d.error_msg) {
          H.raiseError(() => onError && onError(), d.error_msg);
        } else if (then) {
          then(d);
        }
      }
    );
  },

  // METHOD serialize()
  serialize() {
    return {
      name: this.getName(),
      description: this.getDescription(),
    };
  },

  // METHOD unedit()
  unedit(then, onError) {
    let data = null;

    if (this.element[0].dataset.todelete) {
      data = {todelete: true};
    // Update wall only if it has changed
    } else {
      data = this.serialize();

      if (!H.objectHasChanged(_originalObject, data)) {
        if (!this.isShared()) {
          then && then();
          return;
        } else {
          data = null;
        }
      }
    }

    H.request_ws(
      'DELETE',
      `wall/${this.settings.id}/editQueue/wall/${this.settings.id}`,
      data,
      // success cb
      (d) => {
        if (!(data && data.todelete) && d.error_msg) {
          onError && onError();
          H.displayMsg({
            title: `<?=_("Wall")?>`,
            type: 'warning',
            msg: d.error_msg,
          });
        }
        else if (then) {
          then();
        }
      },
      // error cb
      onError,
    );
  },

  // METHOD displayExternalRef()
  displayExternalRef(v) {
    const update = (v !== undefined);
    const val = update ? v : this.settings.displayexternalref;
    const type = (val == 1) ? 'unblock' : 'block';

    if (update) {
      this.settings.displayexternalref = val;

      this.element[0].querySelectorAll('.postit').forEach((p) =>
        $(p).postit(`${type}ExternalRef`));

      H.fetch(
        'POST',
        `user/wall/${this.settings.id}/displayexternalref`,
        {value: val});
    }

    if (this.element.is(':visible')) {
      this.menu({from: 'display', type: `${type}-externalref`});
    }

    return val;
  },

  // METHOD haveExternalRef()
  haveExternalRef() {
    return this.element[0].querySelector('.postit[data-haveexternalref]');
  },

  // METHOD getTDsWidth()
  getTDsWidth() {
    let w = 0;

    this.element[0].querySelector('tbody.wpt tr.wpt')
        .querySelectorAll('td.wpt').forEach(
            (td) => w += parseFloat(td.style.width));

    return w;
  },

  // METHOD showHeaders()
  showHeaders() {
    this.element[0].querySelectorAll('th.wpt').forEach(
        (el) => el.classList.replace('hide', 'display'));
  },

  // METHOD hideHeaders()
  hideHeaders() {
    this.element[0].querySelectorAll('th.wpt').forEach(
        (el) => el.classList.replace('display', 'hide'));
  },

  // METHOD displayHeaders()
  displayHeaders(v) {
    const wall = this.element[0];
    const update = (v !== undefined);
    const val = update ? v : this.settings.displayheaders;
    const type = (val === 1) ? 'show' : 'hide';

    if (type === 'show') {
      this.showHeaders();
      wall.removeAttribute('data-headersshift');
      if (update) {
        let w = this.getTDsWidth();
        w += wall.querySelector('tbody.wpt th.wpt').clientWidth;
        wall.style.width = `${w}px`;
        wall.dataset.oldwidth = w;
      }
      this.fixSize ();
    } else {
      // Save plugs shift width & height for absolutely positioned plugs
      if (!wall.dataset.headersshift) {
        //FIXME
        // Required to obtain the headers dimensions?
        this.showHeaders ();
        const bbox =
          wall.querySelector('thead.wpt th.wpt').getBoundingClientRect();
        if (bbox.width) {
          wall.dataset.headersshift = JSON.stringify({
            width: parseInt(bbox.width),
            height: parseInt(bbox.height),
          });
        }
      }
      this.hideHeaders();
      wall.style.width = `${this.getTDsWidth()}px`;
    }

    if (this.element.is(':visible')) {
      this.menu ({from: 'display', type: `${type}-headers`});
    }

    if (update) {
      this.settings.displayheaders = val;
      wall.dataset.displayheaders = val;
      this.repositionPostitsPlugs();
      H.fetch(
        'POST',
        `user/wall/${this.settings.id}/displayheaders`,
        {value: val});
    }
  },
});

//////////////////////////////////// INIT ////////////////////////////////////

document.addEventListener('DOMContentLoaded', () => {
  if (H.isLoginPage()) {
    const aboutPopup = document.getElementById('aboutPopup');

    // EVENT CLICK on about button in the login page
    document.querySelectorAll(`[data-action="about"]`).forEach((el) => {
      el.addEventListener('click', (e) => H.openModal({item: aboutPopup}));
    });
  } else {
    // LOCAL FUNCTION __getDirectURLData()
    const __getDirectURLData = () => {
      const m = location.search.match(<?=WPT_DIRECTURL_REGEXP?>);
      if (m) {
        return m[0] === 'unsubscribe' ?
          {type: 'u'} :
          {
            type: m[1],
            wallId: Number(m[2]),
            postitId: Number(m[4]),
            commentId: Number(m[6]),
          };
      }
    };

    WS.connect(`${location.protocol === 'https:' ? 'wss' : 'ws'}://${location.host}/app/ws?token=${wpt_userData.token}`,
      () => {
        const $settings = $('#settingsPopup');

        // Heartbeat (every 15mn)
        setInterval(() => fetch('/api/user/ping'), 60 * 1000 * 15);

        $settings.settings({
          locale: document.documentElement.getAttribute('lang'),
        });

        // If a theme exists from the login page, apply it once the user is
        // logged
        const loginTheme = ST.get('theme');
        if (loginTheme) {
          ST.delete('theme');
          $settings.settings('set', {theme: loginTheme});
        }

        $settings.settings('applyTheme');

        // Check if wopits has been upgraded
        H.checkForAppUpgrade();

        const directURLData = __getDirectURLData();
        const loadSpecific = (directURLData && directURLData.type !== 'u');
        const displayAccount = (directURLData && directURLData.type === 'u');
        const $wall = $('<div/>');

        (async () => {
          // Load previously opened walls
          await $wall.wall('restorePreviousSession');

          // Check if we must display a postit alert or a specific wall
          // (from direct URL).
          if (loadSpecific) {
            $wall.wall('loadSpecific', directURLData);
          // Display account popup and highlight emails settings field
          // (from direct URL).
          } else if (displayAccount) {
            // Remove special alert URL.
            history.pushState(null, null, '/');

            H.loadPopup('account', {
              cb: ($p) => {
                $p.find(`[name="allow_emails"]`)
                  .parent().effect("highlight", {duration: 5000});
              },
            });
          }

          // Display theme chooser if needed.
          if (!wpt_userData.settings.theme) {
            $settings.settings('openThemeChooser');
          }
        })();
      });

    H.fixHeight();

    // Create "back to standard view" button
    const displayBtn = H.createElement('div',
        {id: 'normal-display-btn'}, null, `<i class="fas fa-crosshairs fa-2x"></i> <span><?=_("Back to standard view")?></span>`);
    // EVENT "click" on back to standard view button
    displayBtn.addEventListener('click',
      (e) => S.getCurrent('wall').wall('zoom', {type: 'normal'}));
    document.body.insertBefore(displayBtn, document.body.firstChild);

    // Create input to upload wall file import
    H.createUploadElement({
      attrs: {accept: '.zip', className: 'import-wall'},
      onChange: (e) => {
        const el = e.target;

        if (!el.files || !el.files.length) return;

        H.getUploadedFiles( el.files, '\.zip$', (e, file) => {
          el.value = '';

          if (H.checkUploadFileSize({
                size: e.total,
                maxSize: <?=WPT_IMPORT_UPLOAD_MAX_SIZE?>,
              }) && e.target.result) {
            H.fetchUpload('wall/import', {
              name: file.name,
              size: file.size,
              item_type: file.type,
              content: e.target.result,
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

              $('<div/>').wall('open', {wallId: d.wallId});

              H.displayMsg({
                title: `<?=_("Wall")?>`,
                type: 'success',
                msg: `<?=_("The wall has been successfully imported")?>`,
              });
            });
          }
        });
      },
    });

    // EVENT "click" on main content wopits icon
    document.getElementById('welcome').addEventListener('click',
      (e) => $('<div/>').wall('openCreatePopup'));

    // EVENT "click" on walls tab
    document.querySelector('.nav-tabs.walls').addEventListener('click',
        (e) => {
      const el = e.target;

      // EVENT "click" on "close wall" tab button
      if (el.classList.contains('close')) {
        e.stopImmediatePropagation();

        H.openConfirmPopover({
          item: el.closest('.nav-item').querySelector('span.val'),
          placement: 'left',
          title: `<i class="fas fa-times fa-fw"></i> <?=_("Close")?>`,
          content: `<?=_("Close this wall?")?>`,
          onConfirm: () => S.getCurrent('wall').wall ('close'),
        });
      // EVENT "click" on "new wall" tab button
      } else if (el.parentNode.dataset.action === 'new') {
        $('<div/>').wall('openCreatePopup');
      }
    });

    // EVENT "click"
    document.addEventListener('click', (e) => {
      const el = e.target;

      // "click" on wall users view popup
      if (el.matches('#wallUsersviewPopup *')) {
        e.stopImmediatePropagation();

        // EVENT "click" on users list
        if (el.matches('.list-group-item,.list-group-item *')) {
          const li = (el.tagName === 'LI') ? el : el.closest('li');
          H.openUserview({
            about: li.dataset.about,
            picture: li.dataset.picture,
            title: li.dataset.title,
          });
        }
      }
    });

    // EVENT "click" on main menu items
    document.getElementById('main-menu').addEventListener('click', (e) => {
      const el = e.target;
      const $wall = S.getCurrent('wall');
      const wallPlugin = $wall.length ?
        $wall.wall('getClass') : $('<div/>').wall('getClass');
      const li = (el.tagName === 'LI') ? el : el.closest('li');
      const action = li ? li.dataset.action : null;

      // Nothing if menu item is disabled
      if (!li || li.querySelector('a.disabled')) return;

      // LOCAL FUNCTION __manageCheckbox()
      const __manageCheckbox = (el, li, type) => {
        if (el.tagName !== 'INPUT') {
          const input = li.querySelector('input');
          input.checked = !input.checked;
        }
        S.getCurrent(type)[type]('toggle');
      };

      switch (action) {
        case 'zoom+':
          wallPlugin.zoom({type: '+'});
          wallPlugin.ctrlMenu('zoom-screen', 'on');
          break;
        case 'zoom-':
          wallPlugin.zoom({type: '-'});
          wallPlugin.ctrlMenu('zoom-screen', 'on');
          break;
        case 'zoom-screen':
          wallPlugin.zoom({type: 'screen'});
          break;
        case 'zoom-normal':
          wallPlugin.zoom({type: 'normal'});
          break;
        case 'chat':
        case 'filters':
          __manageCheckbox(el, li, action);
          break;
        case 'settings':
          $('#settingsPopup').settings('open');
          break;
        case 'new':
          wallPlugin.openCreatePopup();
          break;
        case 'about':
          H.loadPopup('about');
          break;
        case "user-guide":
          H.loadPopup('userGuide');
          break;
        case 'open':
          wallPlugin.openOpenWallPopup();
          break;
        case 'close-walls':
          wallPlugin.openCloseAllWallsPopup();
          break;
        case 'delete':
          wallPlugin.openDeletePopup();
          break;
        case 'clone':
          wallPlugin.clone();
          break;
        case 'export':
          wallPlugin.export();
          break;
        case 'import':
          wallPlugin.import();
          break;
      }
    });
  }
});

<?=$Plugin->getFooter()?>
