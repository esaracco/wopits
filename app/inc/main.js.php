<?php
/**
Javascript plugin - Main menu & walls

Scope: Global
Name: wall
Description: Manage main menu & walls
*/

require_once(__DIR__.'/../prepend.php');

?>

(() => {
  'use strict';

/////////////////////////////////// PLUGIN ////////////////////////////////////

P.register('wall', class extends Wpt_pluginWallElement {
  // METHOD constructor()
  constructor(settings) {
    super(settings);

    this.originalObject = null;
    this.width = 0;
    this.height = 0;
  }

  // METHOD init()
  async init() {
    const settings= this.settings;
    const tag = this.tag;
    const $wall = $(tag);
    const wallId = settings.id;
    const access = settings.access;
    const writeAccess = this.canWrite();
    const displayHeaders = settings.displayheaders;

    H.hide(document.getElementById('welcome'));

    // Array of wall postits plugs
    settings.plugs = [];

    // Display mode (list-mode, postit-mode)
    tag.dataset.displaymode = settings.displaymode;

    // If headers are displayed (1) or hidden (0)
    tag.dataset.displayheaders = displayHeaders;

    // Wall title tab link
    settings.tabLink = document.querySelector(
      `.nav-tabs.walls a[href="#wall-${wallId}"]`);

    // If wa are restoring a previous session
    if (settings.restoring) {
      tag.dataset.restoring = 1;
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
    P.create(document.querySelector(
      `#wall-${wallId} .wall-menu`), 'wmenu', {access, wall: this});

    tag.style.width = settings.width ? `${settings.width}px` : 'auto';
    tag.style.backgroundColor = settings['background-color'] || 'auto';
    tag.innerHTML = `<thead class="wpt"><tr class="wpt"><th class="wpt ${displayHeaders ? 'display' : 'hide'}">&nbsp;</th></tr></thead><tbody class="wpt"></tbody>`

    // TODO Do not use jQuery here
    $wall.draggable({
      distance: 10,
      cursor: 'move',
      cancel: writeAccess ? '.postit-tags' : null,
      start: () => {
        S.set('wall-dragging', true);
        this.hidePostitsPlugs();
      },
      stop: () => {
        S.set('dragging', true, 500);
        const filtersTag =
          S.getCurrent('filters') && S.getCurrent('filters').tag;
        if (!filtersTag || !filtersTag.classList.contains('plugs-hidden')) {
          this.showPostitsPlugs();
        }
        S.unset('wall-dragging');
      },
    });

    // Create columns headers
    const hcols = settings.headers.cols;
    const tr = tag.querySelector('thead.wpt tr.wpt');
    tag.dataset.cols = hcols.length;
    hcols.forEach((header) => {
      const th = H.createElement('th', {
        className: `wpt ${displayHeaders ? 'display' : 'hide'}`,
      });
      tr.appendChild(th);
      P.create(th, 'header', {
        access,
        wallId,
        wall: this,
        item_type: 'col',
        id: header.id,
        title: header.title,
        picture: header.picture,
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
    tag.dataset.rows = hrows.length;
    rows.forEach((row, rowIdx) => {
      this.addRow(hrows[rowIdx], row);
      row.forEach((cellData) => {
        const cell =
          P.get(tag.querySelector(`[data-id="cell-${cellData.id}"]`), 'cell');
        cellData.postits.forEach((postit) => {
          // No perf killer spread operator here!
          postit.init = true;
          postit.access = access;
          cell.addPostit(postit, true);
        });
      })
    });

    // Set wall name (remove loading spinner)
    this.setName(settings.name);

    if (settings.restoring) {
      delete settings.restoring;
      tag.removeAttribute('data-restoring');
    }

    return this;
  }

  // METHOD displayPostitAlert()
  displayPostitAlert({postitId, type}) {
    const postitTag = S.getCurrent('wall').tag.querySelector(
      `.postit[data-id="postit-${postitId}"]`);

    if (postitTag) {
      P.get(postitTag, 'postit').displayAlert(type);
    } else {
      H.displayMsg({
        type: 'warning',
        msg: `<?=_("The note has been deleted")?>`,
      });
    }
  }

  // METHOD displayShareAlert()
  displayShareAlert(wallId) {
    const walls = U.getWalls();
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
  }

  // METHOD setActive()
  setActive() {
    S.reset();
    bootstrap.Tab.getOrCreateInstance(this.settings.tabLink).show();
    document.getElementById(`wall-${this.settings.id}`)
      .classList.add('active');
    this.menu({from: 'wall', type: 'have-wall'});
  }

  // METHOD ctrlMenu()
  ctrlMenu(action, type) {
    document.querySelectorAll(
      `.dropdown-menu li[data-action="${action}"] a`).forEach((el) => {
      switch (type) {
        case 'enable':
        case 'disable':
          el.classList[type === 'enable' ? 'remove' : 'add']('disabled');
          break;
        case 'show':
        case 'hide':
          H[type](el);
          break;
      }
    });
  }

  // METHOD menu()
  menu(args) {
    const wall = S.getCurrent('wall');
    const tag = wall && wall.tag;
    const wmenu = tag ? tag.parentNode.querySelector('.wall-menu') : null;
    const menu = document.getElementById('main-menu');
    const adminAccess = H.checkAccess(<?=WPT_WRIGHTS_ADMIN?>);

    switch (args.from) {
      // WALL menu
      case 'wall':
        if (!adminAccess) {
          this.ctrlMenu('delete', 'disable');
          H.hide(wmenu.querySelector('[data-action="share"]'));
        }

        switch (args.type) {
          case 'no-wall':
            H.hide(document.querySelector('.nav.walls'));
            document.getElementById('dropdownView').classList.add('disabled');
            H.show(document.getElementById('welcome'));

            this.ctrlMenu('delete', 'disable');
            this.ctrlMenu('close-walls', 'disable');
            this.ctrlMenu('clone', 'disable');
            this.ctrlMenu('export', 'disable');
           
            H.hide(wmenu.querySelector('[data-action="share"]'));
            break;
          case 'have-wall':
            if (tag) {
              H.show(document.querySelector('.nav.walls'));
              document.getElementById('dropdownView')
                .classList.remove('disabled');
              this.menu({
                from: 'display',
                type: tag.dataset.displaymode,
              });
            }

            this.ctrlMenu('clone', 'enable');
            this.ctrlMenu('export', 'enable');
            this.ctrlMenu('close-walls', 'enable');
            this.ctrlMenu('chat',
              (tag && tag.dataset.shared) ? 'enable' : 'disable');
            if (adminAccess) {
              this.ctrlMenu('delete', 'enable');
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
          case 'zoom-on':
            H.show(document.getElementById('normal-display-btn'));
            H.show(menu.querySelector(`[data-action="zoom-off"] a`));
            H.hide(menu.querySelector(`[data-action="zoom-on"] a`));
            this.ctrlMenu('chat', 'disable');
            this.ctrlMenu('filters', 'disable');
            break;
          case 'zoom-off':
            H.hide(document.getElementById('normal-display-btn'));
            H.hide(menu.querySelector(`[data-action="zoom-off"] a`));
            H.show(menu.querySelector(`[data-action="zoom-on"] a`));
            this.ctrlMenu('chat', tag.dataset.shared ? 'enable' : 'disable');
            this.ctrlMenu('filters', 'enable');
            break;
        }
      } 

      // If the user has decided to be invisible
      if (!U.isVisible()) {
        this.ctrlMenu('chat', 'disable');
        H.hide(wmenu.querySelector(`[data-action="share"]`));
      }
  }

  // METHOD closeAllMenus()
  closeAllMenus() {
    const menu = S.getCurrent('walls').querySelector('.postit-menu');

    if (menu) {
      P.get(menu.parentNode, 'postit').closeMenu();
    }
  }

  // METHOD refreshUsersview()
  refreshUsersview(count) {
    const el = this.tag.parentNode.querySelectorAll('.usersviewcounts')[0];
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
  }

  // METHOD UIPluginCtrl()
  UIPluginCtrl(selector, plugin, option, value, forceHandle = false) {
    const isDisabled = (option === 'disabled');
    const items = (typeof selector === 'string') ?
      this.tag.querySelectorAll(selector) :
        (selector.length > 1) ? selector : [selector];

    items.forEach((el) => {
      // FIXME
      const cls = el && el.classList || el[0] && el[0].classList;

      if (!cls || !cls.contains(`ui-${plugin}`)) return;
      if (forceHandle && isDisabled) {
        el.querySelectorAll(`.ui-${plugin}-handle`).forEach((el1) =>
          el1.style.visibility = value ? 'hidden' : 'visible');
      }
      $(el)[plugin]('option', option, value);
    });
  }

  // METHOD repositionPostitsPlugs()
  repositionPostitsPlugs() {
    this.tag.querySelectorAll('.postit.with-plugs').forEach(
      (el) => P.get(el, 'postit').repositionPlugs());
  }

  // METHOD removePostitsPlugs()
  removePostitsPlugs() {
    this.tag.querySelectorAll('.postit.with-plugs').forEach(
      (el) => P.get(el, 'postit').removePlugs(true));
  }

  // METHOD refreshPostitsPlugs()
  refreshPostitsPlugs(partial) {
    const tag = this.tag;
    const filtersTag = S.getCurrent('filters') && S.getCurrent('filters').tag;

    if (filtersTag && filtersTag.classList.contains('plugs-hidden')) return;

    const applyZoom = Boolean(S.get('zoom-level'));
    const idsNew = {};
    const postits = {};

    // Retrieve all postits to optimize search
    tag.querySelectorAll('.postit').forEach((postit) =>
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
      const startTag = postits[startId];

      if (startTag) {
        const start = P.get(startTag, 'postit');
        const label = originalLabel || '...';
        const endId = item_end;

        idsNew[`${startId}${endId}`] = 1;

        if (!start.plugExists(endId)) {
          const endTag = postits[endId];

          if (endTag) {
            start.addPlug({
              startId,
              endId,
              label: {
                name: label,
                top: item_top,
                left: item_left,
              },
              obj: start.getPlugTemplate({
                hide: true,
                start: startTag,
                end: endTag,
                label,
                line_size,
                line_path,
                line_color,
                line_type,
              }),
            }, applyZoom);
          }
        } else {
          start.updatePlugLabel({
            endId,
            label,
            top: item_top,
            left: item_left,
          });
          start.updatePlugProperties({
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
      tag.querySelectorAll('.postit.with-plugs').forEach((el) => {
        P.get(el, 'postit').settings.plugs.forEach((plug) => {
          if (!idsNew[`${plug.startId}${plug.endId}`]) {
            P.get(tag.querySelector(
              `.postit[data-id="postit-${plug.endId}"]`), 'postit')
                .removePlug(plug, true);
          }
        });
      });
    }
  }

  // METHOD hidePostitsPlugs()
  hidePostitsPlugs() {
    this.tag.querySelectorAll('.postit').forEach(
      (el) => P.get(el, 'postit').hidePlugs(true));
  }

  // METHOD showPostitsPlugs()
  showPostitsPlugs() {
    this.repositionPostitsPlugs();
    this.tag.querySelectorAll('.postit').forEach(
      (el) => P.get(el, 'postit').showPlugs(true));
  }

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
  }

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
  }

  // METHOD _refresh()
  async _refresh(d) {
    const tag = this.tag;
    const wallIsVisible = H.isVisible(tag);

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
          const postitTag = tag.querySelector(
            `.postit[data-id="postit-${d.postit.id}"]`);

          // Case when user have multiple sessions opened
          if (!postitTag && d.action !== 'insert')
            return;

          const cellTag = tag.querySelector(
                  `td[data-id="cell-${d.postit.cells_id}"]`);

          switch (d.action) {
            // Insert postit
            case 'insert':
              P.get(cellTag, 'cell').addPostit(d.postit, true);
              break;
            // Update postit
            case 'update':
              P.get(postitTag, 'postit')
                .update(d.postit, {id: d.postit.cells_id});
              break;
            // Remove postit
            case 'delete':
              P.get(postitTag, 'postit').remove();
              break;
          }
          break;
        // Wall
        case 'wall':
          // Col/row has been moved
          if (d.action === 'movecolrow') {
            if (!d.isResponse) {
              P.get(tag.querySelector(
                `th[data-id="header-${d.header.id}"]`), 'header')
                  .moveColRow(d.move, true);
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

      tag.dataset.cols = d.headers.cols.length;
      tag.dataset.rows = d.headers.rows.length;
      tag.dataset.oldwidth = d.width;

      __refreshWallBasicProperties(d);

      // Refresh headers
      d.headers.cols.forEach((header) => {
        let th = tag.querySelector(
          `thead.wpt th.wpt[data-id="header-${header.id}"]`);

        if (!th) {
          th = H.createElement('th',
            {className: `wpt ${displayHeaders ? 'display' : 'hide'}`});

          tag.querySelectorAll('thead.wpt tr.wpt').forEach(
            (el) => el.appendChild(th));

          P.create(th, 'header', {
            wall: this,
            item_type: 'col',
            id: header.id,
            wallId: wallId,
            title: header.title,
            picture: header.picture
          });
        } else {
          P.get(th, 'header').update(header);
        }
      });

      // Remove deleted rows
      const rowsHeadersIds = d.headers.rows.map((el) => el.id);
      tag.querySelectorAll('tbody.wpt th.wpt').forEach((th) => {
        if (!rowsHeadersIds.includes(P.get(th, 'header').getId())) {
          const tr = tag.querySelectorAll(
            `tbody.wpt tr.wpt`)[th.parentNode.rowIndex - 1];

          // Remove TH
          P.get(tr.querySelector('th.wpt'), 'header').remove();

          // Remove TDs
          tr.querySelectorAll('td.wpt').forEach(
            (el) => P.get(el, 'cell').remove());

          // Remove TR
          tr.remove();
        }
      });

      // Remove deleted columns
      const colsHeadersIds = d.headers.cols.map((el) => el.id);
      tag.querySelectorAll('thead.wpt th.wpt').forEach((th) => {
        const idx = th.cellIndex;

        if (idx > 0 && !colsHeadersIds.includes(P.get(th, 'header').getId())) {
          P.get(tag.querySelectorAll(
            'thead.wpt th.wpt')[idx], 'header').remove();

          tag.querySelectorAll('tbody.wpt tr.wpt').forEach((tr) =>
            P.get(tr.querySelectorAll('td.wpt')[idx - 1], 'cell').remove());
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

        if (!tag.querySelector(`td.wpt[data-id="cell-${row[0].id}"]`)) {
          this.addRow(header, row);
        } else {
          P.get(tag.querySelector(
            `tbody.wpt th.wpt[data-id="header-${header.id}"]`), 'header')
              .update(header);
        }

        row.forEach((cellData) => {
          let cellTag = tag.querySelector(
            `td.wpt[data-id="cell-${cellData.id}"]`);
          let cell;

          // If new cell, add it
          if (!cellTag) {
            const cellId = cellData.id;

            cellTag = this.getCellTemplate(cellData, true);
            tag.querySelectorAll(`tbody.wpt tr.wpt`)[cellData.item_row]
              .appendChild(cellTag);

            // Create cell
            cell = P.getOrCreate(cellTag, 'cell', {
              wall: this,
              wallId,
              id: cellId,
              access: access,
              usersettings: userSettings[`cell-${cellId}`] || {},
            });

          // else update cell
          } else {
            cell = P.get(cellTag, 'cell');

            cell.update(cellData);

            // Remove deleted notes
            cellTag.querySelectorAll('.postit').forEach((postitTag) => {
              const postit = P.get(postitTag, 'postit');

              if (!postitsIds.includes(postit.getId())) {
                postit.remove();
              }
            });
          }

          cellData.postits.forEach((postitData) => {
            const postitTag = tag.querySelector(
              `.postit[data-id="postit-${postitData.id}"]`);

            if (!postitTag) {
              cell.addPostit(postitData, true);
            } else {
              P.get(postitTag, 'postit').update(postitData, {
                id: cellData.id,
                obj: cell,
              });
            }
          });
        });
      });
    }

    // Set wall menu visible
    S.getCurrent('wmenu').tag.style.visibility = 'visible';

    this.fixSize();

    // Apply display mode
    this.refreshCellsToggleDisplayMode();

    if (!d.isResponse && !d.partial && S.get('zoom-level')) {
      if (S.get('zoom-level')) {
        this.zoom(true, {noalert: true});
      }
    }

    // Show locks
    if (d.lock && d.locks.length) {
      d.locks.forEach(({item, item_id, user_id, user_name}) => {
        const el = document.querySelector(
          `${item === 'postit' ? '.postit' : ''}`+
          `[data-id="${item}-${item_id}"]`);
        if (el) {
          P.get(el, item).showUserWriting({id: user_id, name: user_name});
        }
      });
    }

    H.waitForDOMUpdate(() => {
      if (wallIsVisible && d.postits_plugs) {
        // Refresh postits relations
        this.refreshPostitsPlugs(d.partial && d.partial !== 'plugs');

        if (!this.settings.displayheaders) {
          this.repositionPostitsPlugs();
        }
      }

      this.refreshCellsToggleDisplayMode();

      // Re-apply filters
      const f = S.getCurrent('filters');
      if (f.isVisible()) {
        f.apply({norefresh: true});
      }
    });
  }

  // METHOD refreshCellsToggleDisplayMode()
  refreshCellsToggleDisplayMode() {
    this.tag.querySelectorAll('td.wpt.list-mode').forEach(
      (el) => P.get(el, 'cell').toggleDisplayMode(true));

    if (S.get('zoom-level')) {
      this.UIPluginCtrl(
        '.cell-list-mode ul', 'sortable', 'disabled', true, true);
    }
  }

  // METHOD openCloseAllWallsPopup()
  openCloseAllWallsPopup() {
    H.openConfirmPopup({
      icon: 'times',
      content: `<?=_("Close the walls?")?>`,
      onConfirm: () => this.closeAllWalls(),
    });
  }

  // METHOD closeAllWalls()
  closeAllWalls(saveSession = true) {
    // Tell the other methods that we are massively closing the walls
    S.set('closing-all', true);
    document.querySelectorAll('table.wall').forEach(
      (el) => P.get(el, 'wall').close());
    S.unset('closing-all');

    if (saveSession) {
      S.getCurrent('settings').saveOpenedWalls(null, false);
    }

    S.reset();
  }

  // METHOD getNextActiveTab()
  getNextActiveTab(current) {
    return current.previousElementSibling ?
      current.previousElementSibling : current.nextElementSibling ?
        current.nextElementSibling : null;
  }

  // METHOD close()
  close() {
    const activeTabId = `wall-${this.settings.id}`;
    const activeTab = document.querySelector(`a[href="#${activeTabId}"]`);
    const wallDiv = document.getElementById(activeTabId);
    const newActiveTab = this.getNextActiveTab(activeTab);
    const chat = S.getCurrent('chat');
    const haveZoom = Boolean(S.get('zoom-level'));

    if (haveZoom) {
      this.zoom(false);
    }

    S.getCurrent('mmenu').close();

    if (chat.isVisible()) {
      chat.leave();
    }

    // If account popup is opened, do not close it: we are dealing with the
    // "invisible mode" option.
    document.querySelectorAll('.modal.show:not(#accountPopup)').forEach(
      (el) => bootstrap.Modal.getInstance(el).hide());

    this.removePostitsPlugs();

    // Clean plugins cache
    H.waitForDOMUpdate(() =>
      wallDiv.querySelectorAll('[data-cached]').forEach(
        (el) => P.remove(el, el.dataset.cached)));

    activeTab.remove();
    wallDiv.remove();
    P.remove(this.tag, 'wall');

    // No more wall to display
    if (!document.querySelector('.wall')) {
      if (haveZoom) {
        this.zoom(false);
      }
      this.menu({from: 'wall', type: 'no-wall'});
    // Active another tabs after deletion
    } else {
      bootstrap.Tab.getOrCreateInstance(newActiveTab).show();
    }

    // If we are not massively closing all walls
    if (!S.get('closing-all')) {
      H.fixHeight();
      S.getCurrent('settings').saveOpenedWalls();
      S.reset();
    }
  }

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
  }

  // METHOD delete()
  delete() {
    this.tag.dataset.todelete = true;

    this.unedit(() => {
      const settings = S.getCurrent('settings');
      const wallId = this.settings.id;

      this.close();

      settings.removeWallBackground(wallId);
      settings.removeRecentWall(wallId);
    });
  }

  // METHOD createColRow()
  createColRow(type) {
    const tag = this.tag;

    if (Number(tag.dataset.rows) *
        Number(tag.dataset.cols) >= <?=WPT_MAX_CELLS?>) {
      H.displayMsg({
        type: 'warning',
        msg: `<?=_("For performance reasons, a wall cannot contain more than %s cells")?>`.replace('%s', <?=WPT_MAX_CELLS?>),
      });
      return;
    }

    H.request_ws(
      'PUT',
      `wall/${this.settings.id}/${type}`,
      null,
      // TODO Scroll to the exact ending of the new added col or row
      () => S.getCurrent('walls')[(type === 'col') ?
              'scrollLeft' : 'scrollTop'] = 30000,
    );
  }

  // METHOD getCellTemplate()
  getCellTemplate({id, width, height}, createEl) {
    return createEl ?
      H.createElement('td',
        {scope: 'dzone', className: 'wpt', style: `width:${width}px;height:${height}px`},
        {id: `cell-${id}`}) :
     `<td scope="dzone" class="wpt" style="width:${width}px;height:${height}px" data-id="cell-${id}"></td>`;
  }

  // METHOD addRow()
  addRow(header, cols) {
    const tag = this.tag;
    const settings = this.settings;
    const wallId = settings.id;
    const displayHeaders = settings.displayheaders;
    let tds = '';

    cols.forEach((col) => tds += this.getCellTemplate(col));

    const row = H.createElement('tr',
      {className: `wpt`},
      null,
      `<th class="wpt ${displayHeaders ? 'display' : 'hide'}"></th>${tds}`);

    // Add row
    tag.querySelector('tbody.wpt').appendChild(row);
    P.create(row.querySelector('th.wpt'), 'header', {
      wallId,
      wall: this,
      access: settings.access,
      item_type: 'row',
      id: header.id,
      title: header.title,
      picture: header.picture
    });

    // Create cells
    const userSettings = settings.usersettings || {};
    row.querySelectorAll('td.wpt').forEach((cell) => {
      const cellId = Number(cell.dataset.id.substring(5));

      P.create(cell, 'cell', {
        wallId,
        wall: this,
        id: cellId,
        access: settings.access,
        usersettings: userSettings[`cell-${cellId}`] || {},
      });
    });
  }

  // METHOD deleteRow()
  deleteRow(rowIdx) {
    const headerTag = this.tag
      .querySelectorAll(`tbody.wpt tr.wpt`)[rowIdx]
      .querySelector(`th.wpt`);

    P.get(headerTag, 'header').removeContentKeepingWallSize({
      oldW: headerTag.offsetWidth,
      cb: () => {
        headerTag.querySelectorAll('.img').forEach((el) => el.remove());
        headerTag.querySelector('.title').innerHTML = '&nbsp;';
      },
    });

    H.request_ws(
      'DELETE',
      `wall/${this.settings.id}/row/${rowIdx}`,
      {wall: {width: Math.trunc(this.tag.offsetWidth)}});

  }

  // METHOD deleteCol()
  deleteCol(idx) {
    const tag = this.tag;

    H.request_ws(
      'DELETE',
      `wall/${this.settings.id}/col/${idx-1}`,
      {
        wall: {width: Math.trunc(tag.clientWidth - 1)},
        width: Math.trunc(
          tag.querySelectorAll(`thead.wpt tr.wpt th.wpt`)[idx]
            .getBoundingClientRect().width),
      });
  }

  // METHOD addNew()
  async addNew(args) {
    const tabs = document.querySelector('.nav-tabs.walls');
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
        H.displayMsg({type: 'warning', msg: r.error_msg});
      }
      return;
    }

    // If the wall does not exists anymore remove it and return
    if (r.removed) {
      const tmp = tabs.querySelector(`a[href="#wall-${args.wallId}"]`);

      tmp && tmp.remove();

      if (args.restoring && U.get('activeWall') === args.wallId) {
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
        type: 'warning',
        msg: `<?=_("Some walls are no longer available")?>`,
      });
      S.getCurrent('settings').removeWall(args.wallId);
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
      S.getCurrent('settings').get('wall-background', r.id);

    P.create(wallDiv.querySelector('.chat'), 'chat', {wallId: r.id});
    P.create(wallDiv.querySelector('.filters'), 'filters');

    return await P.getOrCreate(
      wallDiv.querySelector('.wall'), 'wall', {...args, ...r}).init();
  }

  // METHOD create()
  async create(args) {
    return await this.open({...args, type: 'create', noPostProcess: true});
  }

  // METHOD open()
  async open(args) {
    const options = {
      ...args,
      // "load" or "create". "load" by default
      type: (args.type !== undefined) ? args.type : 'load',
    };
    const wall = await this.addNew(options);

    if (wall && !args.noPostProcess) {
      wall.postProcessLastWall(options);
    }

    return wall;
  }

  // METHOD postProcessLastWall()
  async postProcessLastWall(args = {}) {
    const wallTag = this.tag;
    const awSettingsId = U.get('activeWall');
    let activeWallTag;

    if (!args.restoring || args.then || S.get('save-opened-walls')) {
      activeWallTag = wallTag;
    } else {
      activeWallTag = document.querySelector(
        `[data-id="wall-${U.get('activeWall')}"]`) || wallTag;
    }

    const activeWall = P.get(activeWallTag, 'wall');
    const awId = activeWall.getId();
    activeWall.setActive();
    await activeWall.refresh();
    activeWall.displayExternalRef();
    activeWall.displayHeaders();

    if (S.get('save-opened-walls') || awSettingsId !== awId) {
      S.unset('save-opened-walls');
      S.getCurrent('settings').saveOpenedWalls();
    }

    H.fixHeight();

    // Display postit dealine alert or specific wall if needed.
    args.then && args.then();

    // Set wall users view count if needed
    const viewcount = WS.popResponse(`viewcount-wall-${awId}`);
    if (viewcount) {
      activeWall.refreshUsersview(viewcount);
    }
  }

  // METHOD clone()
  async clone() {
    const r = await H.fetch(
    'PUT',
    `wall/${this.settings.id}/clone`);

    if (r.error) {
      if (r.error_msg) {
        H.displayMsg ({type: 'warning', msg: r.error_msg});
      }
      return;
    }

    // try / catch
    const wallDiv = document.createElement('div');
    const wall = await P.getOrCreate(wallDiv, 'wall').open({wallId: r.wallId});

    if (wall) {
      P.remove(wallDiv, 'wall');
      H.displayMsg({
        type: 'success',
        msg: `<?=_("The wall has been successfully cloned")?>`,
      });
    }
  }

  // METHOD export()
  // Format: wopits_export-{wall_name}-{ymd}_{hms}.zip
  export() {
    const now = U.formatDate(moment().unix(), null, 'YMMDD_HHmmss');
    const wallName = this.getName().replace(/\s+/g, '_');

    H.download({
      url: `/wall/${this.settings.id}/export`,
      fname: `wopits_export-${wallName}-${now}.zip`,
      msg: `<?=_("An error occurred during the export")?>`,
    });
  }

  // METHOD import()
  import () {
    document.querySelector('.upload.import-wall').click();
  }

  // METHOD restorePreviousSession()
  async restorePreviousSession() {
    const walls = U.getOpenedWalls();

    if (walls.length) {
      let wall;

      await Promise.all(walls.reverse().map(async (wallId) => {
        wall = await this.open({
          wallId,
          restoring: true,
          noPostProcess: true,
        });
      }));

      if (wall) {
        wall.postProcessLastWall({restoring: true});
      } else if (S.get('save-opened-walls')) {
        S.unset('save-opened-walls');
        S.getCurrent('settings').saveOpenedWalls();
      }
    }
  }

  // METHOD isOpened()
  isOpened(wallId) {
    return U.getOpenedWalls().includes(wallId);
  }

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
      const wall = await this.open({wallId, noPostProcess: true});

      if (wall) {
        wall.postProcessLastWall({then: __displayAlert});
      }
    } else {
      // Set wall current if needed
      if (wallId !== S.getCurrent('wall').getId()) {
        const el = document.querySelector(`a[href="#wall-${wallId}"]`);
        el.dispatchEvent(new Event('mousedown', {bubbles: true}));
        el.dispatchEvent(new Event('click', {bubbles: true}));
      }

      __displayAlert();
    }

    // Remove special alert URL
    history.pushState(null, null, '/');
  }

  // METHOD refreshUserWallsData()
  async refreshUserWallsData(then) {
    const r = await H.fetch('GET', 'wall');
    if (r && !r.error) {
      U.setWalls(r.list);
      then && then();
    }
  }

  // METHOD openOpenWallPopup()
  openOpenWallPopup() {
    this.refreshUserWallsData(() => {
      H.loadPopup ('owall', {
        cb: (p) => {
          const owall = P.getOrCreate(p, 'owall');

          owall.reset();
          owall.displayWalls();
          owall.controlFiltersButtons();
        }
      });
    });
  }

  // METHOD openCreatePopup()
  openCreatePopup() {
    H.closeMainMenu();
    H.loadPopup('createWall', {
      init: (p) => {
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
      cb: (p) => p.dataset.noclosure = true,
    });
  }

  // METHOD displayWallUsersview()
  displayWallUsersview() {
    H.request_ws(
      'GET',
      `wall/${this.settings.id}/usersview`,
      null,
      (d) => {
        H.loadPopup('wallUsersview', {
          open: false,
          cb: (p) => {
            const userId = U.getId();

            let html = '';
            d.list.forEach((item) => {
              if (item.id !== userId) {
                html += `<li class="list-group-item" data-id="${item.id}" data-title="${H.htmlEscape(item.fullname)}" data-picture="${item.picture || ''}" data-about="${H.htmlEscape(item.about || '')}"><div class="label">${H.getAccessIcon(item.access)} ${item.fullname}</div><div class="item-infos"><span>${item.username}</span></div>`;
              }
            });
            p.querySelector('.list-group').innerHTML = html;

            H.openModal ({item: p});
          }
        });
      }
    );
  }

  // METHOD openPropertiesPopup()
  openPropertiesPopup(args = {}) {
    const __open = () => {
      H.loadPopup('wprop', {
        open: false,
        cb: (p) => P.getOrCreate(p, 'wprop').open({...args, wall: this}),
      });
    };

    if (H.checkAccess(<?=WPT_WRIGHTS_ADMIN?>)) {
      this.edit(__open);
    } else {
      __open();
    }
  }

  // METHOD saveProperties()
  // FIXME Issues with walls without grid
  saveProperties() {
    const popup = document.getElementById('wpropPopup');
    const inputs = Array.from(popup.querySelectorAll('input'))
      .filter((el) => H.isVisible(el));
    const Form = new Wpt_accountForms();
    let ret = true;

    popup.dataset.noclosure = true;

    if (Form.checkRequired(inputs) && Form.validForm(inputs)) {
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

      // If wall without grid
      if (inputs.length > 1) {
        const tag = this.tag;
        const cellTag = tag.querySelector('td.wpt');
        const oldW = cellTag.offsetWidth;
        const w = parseInt(inputs[1].value);
        const h = parseInt(inputs[2].value);

        if (w !== oldW || h !== cellTag.offsetHeight) {
          const cell = P.get(cellTag, 'cell');

          // LOCAL FUNCTION __resize()
          const __resize = (args) => {
            tag.querySelectorAll(`thead.wpt th.wpt`)[1]
              .style.width = `${args.newW}px`;
            cellTag.style.width = `${args.newW}px`;
            cellTag.querySelector('.ui-resizable-s')
              .style.width = `${args.newW + 2}px`;

            if (args.newH) {
             tag.querySelectorAll(`tbody.wpt th.wpt,td.wpt`).forEach(
               (el) => el.style.height = `${args.newH}px`);
             cellTag.querySelector('.ui-resizable-e')
               .style.height = `${args.newH + 2}px`;
            }

            this.fixSize(args.oldW, args.newW);
          };

          __resize({newW: w, oldW: oldW, newH: h});

          const tmp = tag.querySelector('td.wpt').offsetWidth;
          if (tmp !== w) {
            __resize({newW: tmp, oldW: w});
          }

          cell.edit();
          cell.reorganize();
          cell.unedit();
        }
      }
    } else {
      ret = false;
    }

    return ret;
  }

  // METHOD getName()
  getName() {
    return this.settings.tabLink.querySelector('span.val').innerText;
  }

  // METHOD setName()
  setName(name, noIcon) {
    const div = this.settings.tabLink;
    const notOwner = (this.settings.ownerid !== U.getId());

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
  }

  // METHOD isShared()
  isShared() {
    return Boolean(this.tag.dataset.shared);
  }

  // METHOD setShared()
  setShared(isShared) {
    const tag = this.tag;

    if (isShared) {
      tag.dataset.shared = 1;
    } else {
      tag.removeAttribute('data-shared');
    }

    this.refreshPostitsWorkersIcon();
    this.refreshSharedIcon();
  }

  // METHOD refreshPostitsWorkersIcon()
  refreshPostitsWorkersIcon() {
    const display = this.isShared();

    this.tag.querySelectorAll('.postit').forEach((p) => {
      const pMenu = P.get(p, 'postit').settings.Menu;

      p.querySelector('.pwork').style.display =
        display ? 'inline-block' : 'none';

      if (pMenu) {
        pMenu.tag.querySelector(`[data-action="pwork"]`)
          .style.display = display ? 'inline-block' : 'none';
      }
    });
  }

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
  }

  // METHOD getDescription()
  getDescription() {
    return this.settings.tabLink.getAttribute('title');
  }

  // METHOD setDescription()
  setDescription(description) {
    this.settings.tabLink.setAttribute('title', H.noHTML(description || ''));
  }

  // METHOD fixSize()
  fixSize(oldW, newW) {
    const tag = this.tag;
    let w;

    // If no header, substract header width from wall width
    if (!this.settings.displayheaders) {
      w = this.getTDsWidth();
    } else if (!(w = Number(tag.dataset.oldwidth))) {
      w = tag.offsetWidth;
    }

    if (newW) {
      if (newW > oldW) {
        w += (newW - oldW);
      } else if (newW < oldW) {
        w -= (oldW - newW);
      }
    }

    tag.dataset.oldwidth = w;
    tag.style.width = `${w}px`;
    tag.style.maxWidth = `${w}px`;

    const {width, height} = tag.getBoundingClientRect();
    const z = S.get('zoom-level') || 1;

    this.width = Math.ceil(width / z);
    this.height = Math.ceil(height / z);
  }

  // METHOD setPostitsDisplayMode()
  setPostitsDisplayMode(type) {
    const tag = this.tag;

    this.menu({from: 'display', type: type});

    tag.dataset.displaymode = type;

    tag.querySelectorAll('td.wpt').forEach(
      (el) => P.get(el, 'cell').setPostitsDisplayMode(type));

    // Re-apply filters
    const f = S.getCurrent('filters');
    if (f.isVisible()) {
      f.apply({norefresh: true});
    }

    H.fetch(
      'POST',
      `user/wall/${this.settings.id}/displaymode`,
      {value: type});
  }

  // METHOD zoom()
  zoom(enable, args = {}) {
    const zoom = document.querySelector('.tab-content.walls');
    const tag = this.tag;
    const $wall = $(tag);
    const walls = S.getCurrent('walls');
    const writeAccess = this.canWrite();
    const postitDiv = document.createElement('div');

    tag.style.top = 0;
    tag.style.left = 0;

    walls.scrollLeft = 0;
    walls.scrollTop = 0;

    // Normal view
    if (!enable) {
      const level = Number(zoom.dataset.zoomlevelorigin);

      zoom.removeAttribute('data-zoomlevelorigin');
      S.unset('zoom-level');

      walls.style.overflow = 'auto';
      this.menu({from: 'display', type: 'zoom-off'});

      if (S.get('old-styles')) {
        zoom.style.width = S.get('old-styles').width;
        zoom.style.transform = S.get('old-styles').transform;
        S.unset('old-styles');
      }

      walls.style.overflow = 'auto';

      // Reactivate headers
      zoom.querySelectorAll('th.wpt').forEach((th) => {
        th.style.pointerEvents = 'auto';
        if (writeAccess) {
          th.style.opacity = 1;
        }
      });
      // Reactivate wall dragging
      if (tag.classList.contains('ui-draggable')) {
        $wall.draggable('enable');
      }
      // Show cell menu
      tag.querySelectorAll('.cell-menu').forEach((el) => H.show(el));
      // Reactivate note sorting in stack mode
      this.UIPluginCtrl('.cell-list-mode ul',
        'sortable', 'disabled', false, true);
      // Reactivate note & cell resizing
      this.UIPluginCtrl('td,.postit',
        'resizable', 'disabled', false);
      // Reactivate note dragging
      this.UIPluginCtrl('.postit',
        'draggable', 'disabled', false);
      // Disable plug label dragging
      this.UIPluginCtrl(document.body.querySelectorAll('.plug-label'),
        'draggable', 'disabled', false);

    // Screen view
    } else {
      const wallsW = walls.clientWidth - 20;
      const wallsH = walls.clientHeight - 20;
      let ratioW;
      let ratioH;
      let level = zoom.style.transform;
      level = (!level || level === 'none') ?
        1 : Number(level.match(/[0-9\.]+/)[0]);

      if (!S.get('zoom-level')) {
        zoom.dataset.zoomlevelorigin = level;

        this.menu({from: 'display', type: 'zoom-on'});

        S.set('old-styles', {
          width: zoom.style.width,
          transform: zoom.style.transform,
        });

        if (writeAccess && !args.noalert) {
          H.displayMsg({
            type: 'info',
            msg: `<?=_("Some features are not available when zoom is enabled")?>`,
          });
        }

        // Disable headers
        zoom.querySelectorAll('th.wpt').forEach ((th) => {
          th.style.pointerEvents = 'none';
          if (writeAccess) {
            th.style.opacity = .6;
          }
        });
        // Disable wall dragging
        if (tag.classList.contains('ui-draggable')) {
          $wall.draggable('disable');
        }
        // Hide cell menu
        tag.querySelectorAll('.cell-menu').forEach((el) => H.hide(el));
        // Disable note sorting in stack mode
        this.UIPluginCtrl('.cell-list-mode ul',
          'sortable', 'disabled', true, true);
        // Disable note & cell resizing
        this.UIPluginCtrl('td,.postit',
          'resizable', 'disabled', true);
        // Disable note dragging
        this.UIPluginCtrl('.postit',
          'draggable', 'disabled', true);
        // Disable plug label dragging
        this.UIPluginCtrl(document.body.querySelectorAll('.plug-label'),
          'draggable', 'disabled', true);

        S.getCurrent('mmenu').checkAllowedActions();
      }

      if (wallsW < this.width) {
        ratioW = (wallsW < this.width) ?
          wallsW / this.width : this.width / wallsW;
      } else if (wallsW > this.width) {
        ratioW = (wallsW > this.width) ?
          wallsW / this.width : this.width / wallsW;
      }

      if (wallsH < this.height) {
        ratioH = (wallsH < this.height) ?
          wallsH / this.height : this.height / wallsH;
      } else if (wallsH > this.height) {
        ratioH = (wallsH > this.height) ?
          wallsH / this.height : this.height / wallsH;
      }

      level = Math.abs(ratioH < ratioW ? ratioH : ratioW);

      zoom.style.transformOrigin = 'top left';
      zoom.style.transform = `scale(${level})`;
      zoom.style.width = '30000px';

      walls.scrollLeft = ((30000 * level) / 2 - window.innerWidth / 2) + 20;
      walls.style.overflow = 'hidden';

      S.set('zoom-level', level);

      S.getCurrent('mmenu').checkAllowedActions();
    }

    P.getOrCreate(postitDiv, 'postit', {wall: this}).applyZoom();
    P.remove(postitDiv, 'postit');
  }

  // METHOD edit()
  edit(then, onError, todelete = false) {
    this.originalObject = this.serialize();

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
  }

  // METHOD serialize()
  serialize() {
    return {
      name: this.getName(),
      description: this.getDescription(),
    };
  }

  // METHOD unedit()
  unedit(then, onError) {
    let data = null;

    if (this.tag.dataset.todelete) {
      data = {todelete: true};
    // Update wall only if it has changed
    } else {
      data = this.serialize();

      if (!H.objectHasChanged(this.originalObject, data)) {
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
          H.displayMsg({type: 'warning', msg: d.error_msg});
        }
        else if (then) {
          then();
        }
      },
      // error cb
      onError,
    );
  }

  // METHOD displayExternalRef()
  displayExternalRef(v) {
    const update = (v !== undefined);
    const val = update ? v : this.settings.displayexternalref;
    const type = (val == 1) ? 'unblock' : 'block';

    if (update) {
      this.settings.displayexternalref = val;

      this.tag.querySelectorAll('.postit').forEach(
        (el) => P.get(el, 'postit')[`${type}ExternalRef`]());

      H.fetch(
        'POST',
        `user/wall/${this.settings.id}/displayexternalref`,
        {value: val});
    }

    if (H.isVisible(this.tag)) {
      this.menu({from: 'display', type: `${type}-externalref`});
    }

    return val;
  }

  // METHOD haveExternalRef()
  haveExternalRef() {
    return this.tag.querySelector('.postit[data-haveexternalref]');
  }

  // METHOD getTDsWidth()
  getTDsWidth() {
    let w = 0;

    this.tag.querySelector('tbody.wpt tr.wpt')
      .querySelectorAll('td.wpt').forEach(
        (td) => w += parseFloat(td.style.width));

    return w;
  }

  // METHOD showHeaders()
  showHeaders() {
    this.tag.querySelectorAll('th.wpt').forEach(
        (el) => el.classList.replace('hide', 'display'));
  }

  // METHOD hideHeaders()
  hideHeaders() {
    this.tag.querySelectorAll('th.wpt').forEach(
        (el) => el.classList.replace('display', 'hide'));
  }

  // METHOD displayHeaders()
  displayHeaders(v) {
    const tag = this.tag;
    const update = (v !== undefined);
    const val = update ? v : this.settings.displayheaders;
    const type = (val === 1) ? 'show' : 'hide';

    if (type === 'show') {
      if(tag.dataset.headersshift) {
        const hshift = JSON.parse(tag.dataset.headersshift);
        tag.removeAttribute('data-headersshift');
        this.showHeaders();
        this.fixSize (0, hshift.width);
      }
    } else {
      // Save plugs shift width & height for absolutely positioned plugs
      //FIXME
      // Required to obtain the headers dimensions?
      this.showHeaders ();
      const bbox =
        tag.querySelector('thead.wpt th.wpt').getBoundingClientRect();
      if (bbox.width) {
        tag.dataset.headersshift = JSON.stringify({
          width: parseInt(bbox.width),
          height: parseInt(bbox.height),
        });
      }
      this.hideHeaders();
      tag.style.width = `${this.getTDsWidth()}px`;
    }

    if (H.isVisible(tag)) {
      this.menu({from: 'display', type: `${type}-headers`});
    }

    if (update) {
      this.settings.displayheaders = val;
      tag.dataset.displayheaders = val;
      this.repositionPostitsPlugs();
      H.fetch(
        'POST',
        `user/wall/${this.settings.id}/displayheaders`,
        {value: val});
    }
  }
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

    WS.connect(`${location.protocol === 'https:' ?
      'wss' : 'ws'}://${location.host}/app/ws?token=${U.getToken()}`, () => {
      // Heartbeat (every 15mn)
      setInterval(() => fetch('/api/user/ping'), 60 * 1000 * 15);

      const settings = S.getCurrent('settings');

      // If a theme exists from the login page, apply it once the user is
      // logged
      const loginTheme = ST.get('theme');
      if (loginTheme) {
        ST.delete('theme');
        settings.set({theme: loginTheme});
      }

      settings.applyTheme();

      // Check if wopits has been upgraded
      H.checkForAppUpgrade();

      (async () => {
        const directURLData = __getDirectURLData();
        const loadSpecific = (directURLData && directURLData.type !== 'u');
        const displayAccount = (directURLData && directURLData.type === 'u');
        const wallDiv = document.createElement('div');
        const wall = P.getOrCreate(wallDiv, 'wall');

        // Load previously opened walls
        await wall.restorePreviousSession();

        // Check if we must display a postit alert or a specific wall
        // (from direct URL).
        if (loadSpecific) {
          wall.loadSpecific(directURLData);
        // Display account popup and highlight emails settings field
        // (from direct URL).
        } else if (displayAccount) {
          // Remove special alert URL.
          history.pushState(null, null, '/');

          H.loadPopup('account', {
            cb: (p) => {
              const el = p.querySelector(`[name="allow_emails"]`).parentNode;

              // TODO Helper for transitions
              el.style.transition = '2s';
              el.style.backgroundColor = 'var(--modal-1-theme-bg-color)';
              setTimeout(() => el.style.backgroundColor = '', 2000);
            },
          });
        }

        P.remove(wallDiv, 'wall');

        // Display theme chooser if needed
        if (!U.get('theme')) {
          settings.openThemeChooser();
        }
      })();
    });

    H.fixHeight();

    // Create "back to standard view" button
    const displayBtn = H.createElement('div',
        {id: 'normal-display-btn'}, null, `<i class="fas fa-crosshairs fa-2x"></i> <span><?=_("Back to standard view")?></span>`);
    // EVENT "click" on back to standard view button
    displayBtn.addEventListener('click',
      (e) => S.getCurrent('wall').zoom(false));
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
                H.displayMsg({type: 'warning', msg: d.error_msg});
                return;
              }

              const wallDiv = document.createElement('div');
              P.getOrCreate(wallDiv, 'wall').open({wallId: d.wallId});
              P.remove(wallDiv, 'wall');

              H.displayMsg({
                type: 'success',
                msg: `<?=_("The wall has been successfully imported")?>`,
              });
            });
          }
        });
      },
    });

    // EVENT "click" on main content wopits icon
    document.getElementById('welcome').addEventListener('click', (e) => {
      const wallDiv = document.createElement('div');

      P.getOrCreate(wallDiv, 'wall').openCreatePopup();
      P.remove(wallDiv, 'wall');
    });

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
          onConfirm: () => S.getCurrent('wall').close(),
        });
      // EVENT "click" on "new wall" tab button
      } else if (el.parentNode.dataset.action === 'new') {
        const wallDiv = document.createElement('div');

        P.getOrCreate(wallDiv, 'wall').openCreatePopup();
        P.remove(wallDiv, 'wall');
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
        S.getCurrent(type).toggle();
      };

      let wall = S.getCurrent('wall');
      let wallDiv;

      if (!wall) {
        wallDiv = document.createElement('div');
        wall = P.getOrCreate(wallDiv, 'wall');
      }

      switch (action) {
        case 'zoom-on':
          wall.zoom(true);
          break;
        case 'zoom-off':
          wall.zoom(false);
          break;
        case 'chat':
        case 'filters':
          __manageCheckbox(el, li, action);
          break;
        case 'settings':
          S.getCurrent('settings').open();
          break;
        case 'new':
          wall.openCreatePopup();
          break;
        case 'about':
          H.loadPopup('about');
          break;
        case "user-guide":
          H.loadPopup('userGuide');
          break;
        case 'open':
          wall.openOpenWallPopup();
          break;
        case 'close-walls':
          wall.openCloseAllWallsPopup();
          break;
        case 'delete':
          wall.openDeletePopup();
          break;
        case 'clone':
          wall.clone();
          break;
        case 'export':
          wall.export();
          break;
        case 'import':
          wall.import();
          break;
      }

      if (wallDiv) {
        P.remove(wallDiv, 'wall');
      }
    });
  }
});

})();
