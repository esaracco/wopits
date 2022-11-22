<?php
/**
Javascript plugin - Cell

Scope: Wall
Name: cell
Description: Wall's cell
*/

require_once(__DIR__.'/../prepend.php');

?>

(() => {
  'use strict';

/////////////////////////////////// PLUGIN ////////////////////////////////////

P.register('cell', class extends Wpt_pluginWallElement {
  // METHOD constructor()
  constructor(settings) {
    super(settings);
    const tag = this.tag;
    const $cell = $(tag);
    const cellId = settings.id;
    const usersettings = settings.usersettings;
    const wall = settings.wall;
    const wallTag = wall.tag;
    const writeAccess = this.canWrite();

    tag.classList.add(usersettings.displaymode || wallTag.dataset.displaymode);
    // Add cell menu
    const cellMenuCls = {className: 'cell-menu'};
    if (S.get('zoom-level')) {
      cellMenuCls.style = 'display: none';
    }
    tag.insertBefore(
      H.createElement('div',
        cellMenuCls,
        null,
        `<span class="btn btn-sm btn-secondary btn-circle"><i class="fas fa-sticky-note fa-fw"></i></span>`,
      ),
      tag.firstChild
    );

    if (writeAccess) {
      $cell
        // TODO Do not use jQuery here
        .droppable({
          accept: '.postit',
          tolerance: 'pointer',
          scope: 'dzone',
          classes: {'ui-droppable-hover': 'droppable-hover'},
          drop: (e, ui) => {
            if (S.get('revertData').revert) return;

            const cellPos = tag.getBoundingClientRect();
            const postit = P.get(ui.draggable[0], 'postit');
            const ptop = ui.offset.top - cellPos.top;
            const pleft = ui.offset.left - cellPos.left;

            postit.setPosition({
              cellId,
              top: (ptop < 0) ? 0 : ptop,
              left: (pleft < 0) ? 0 : pleft,
            });

            tag.append(postit.tag);

            this.reorganize().then(() => postit.dropStop());
          },
        });
    }

    // TODO Do not use jQuery here
    $cell.resizable({
      disabled: !writeAccess || Boolean(S.get('zoom-level')),
      autoHide: false,
      ghost: true,
      minWidth: settings.width || 300,
      minHeight: settings.height || 200,
      helper: 'resizable-helper',
      start: (e, ui) => {
        const editable = wallTag.querySelectorAll('.editable');
 
        // Cancel all editable (blur event is not triggered on resizing)
        if (editable.length) {
          editable.forEach((el) => P.get(el, 'editable').cancel());
        }
 
        this.edit(() => S.set('revertData', true));
      },
      stop: (e, ui) => {
        if (S.get('revertData')) {
          S.unset('revertData');

          tag.style.width = `${ui.originalSize.width}px`;
          tag.style.height = `${ui.originalSize.height}px`;
        } else {
          const absH = Math.abs(ui.size.height - ui.originalSize.height);
          const absW = Math.abs(ui.size.width - ui.originalSize.width);
 
          // Height
          if (absH < 2 || absH > 2) {
            if (wallTag.dataset.cols === '1' &&
                wallTag.dataset.rows === '1') {
              this.update({
                width: ui.size.width + 3,
                height: ui.size.height,
              });
            } else {
              tag.closest('tr.wpt').querySelector('th.wpt')
                .style.height = `${ui.size.height}px`;
 
              this.update({width: ui.size.width + 2});
 
              // Set height for all cells of the current row
              wallTag.querySelectorAll(`tbody.wpt tr.wpt`)[
                  tag.parentNode.rowIndex - 1].querySelectorAll(`td.wpt`)
                      .forEach((el) => {
                el.style.height = `${ui.size.height}px`;
                el.querySelector(`div.ui-resizable-e`)
                  .style.height = `${ui.size.height + 2}px`;
              });
            }
          }
 
          // Width
          if (absW < 2 || absW > 2) {
            const cellIndex = tag.cellIndex - 1;

            wallTag.querySelectorAll(`tbody.wpt tr.wpt`).forEach((tr) => {
              const td = tr.querySelectorAll(`td.wpt`)[cellIndex];

              td.style.width = `${ui.size.width}px`;
              td.querySelector(`div.ui-resizable-s`)
                .style.width = `${ui.size.width+2}px`;
            });
 
            wall.fixSize(ui.originalSize.width, ui.size.width);
          }
 
          this.reorganize(wallTag.querySelectorAll(`tbody.wpt td.wpt`));
        }

        this.unedit();
      }
    });
  }

  // METHOD showUserWriting()
  showUserWriting(user) {
    const tag = this.tag;

    setTimeout(() => {
      tag.classList.add('locked');
      tag.insertBefore(
        H.createElement('div',
          {className: 'user-writing main'},
          {userid: user.id},
          `<i class="fas fa-user-edit blink"></i> ${user.name}`,
        ),
        tag.firstChild,
      );
    }, 150);
  }

  // METHOD setPostitsUserWritingListMode()
  // See postit::showUserWriting()
  setPostitsUserWritingListMode() {
    this.tag.querySelectorAll('.user-writing').forEach((el) => {
      const p = el.parentNode;
      const min = p.parentNode.querySelector(
        `.postit-min[data-id="${p.dataset.id}"]`);

      if (min) {
        min.classList.add('locked');
        min.insertBefore(
          H.createElement('span',
            {className: `user-writing-min${el.classList.contains('main') ? ' main' : ''}`},
            {userid: el.dataset.userid},
            `<i class="${el.querySelector('i').className} fa-sm"></i>`,
          ),
          min.firstChild,
        );
      }
    });
  }

  // METHOD setPostitsDisplayMode()
  setPostitsDisplayMode(type) {
    const tag = this.tag;
    const $cell = $(tag);
    const displayMode = tag.querySelector('.cell-menu i');
    const writeAccess = this.canWrite();

    // If we must display list of minified notes
    // list-mode
    if (type === 'list-mode') {
      const cellWidth = tag.clientWidth;
      const cellHeight = tag.clientHeight;
      // FIXME
      //const postits = Array.from (tag.querySelectorAll('.postit'));
      const postits = Array.from(
        tag.querySelectorAll('.postit:not([data-order="undefined"])'));

      tag.classList.remove('postit-mode');
      tag.classList.add('list-mode');

      // Cell can not be resizable in minified moe
      $cell.resizable('disable');

      // Update cell's menu icon        
      displayMode.classList.replace('fa-sticky-note', 'fa-list-ul');

      // Add notes count to cell's menu
      tag.querySelector('.cell-menu').append(H.createElement('span',
        {className: 'wpt-badge inset'},
        null,
        String(postits.length)));

      // Build list of minified notes (html buffer)
      let html = '';
      postits
        // Sort by postit id DESC
        .sort((a, b) => {
          const aOrder = Number(a.dataset.order);
          const bOrder = Number(b.dataset.order);

          if (!aOrder && !bOrder) {
            return Number(b.dataset.id.split(/\-/)[1]) -
                     Number(a.dataset.id.split(/\-/)[1]);
          } else {
            return aOrder - bOrder;
          }
        })
        .forEach((p) => {
          const color = (p.className.match(/ color\-([a-z]+)/))[1];
          const postit = P.get(p, 'postit');
          const title = p.querySelector('.title').innerHTML;
          const progress = Number(p.dataset.progress || 0);

          postit.closeMenu();
          postit.hidePlugs();

          p.style.visibility = 'hidden';

          html += `<li class="color-${color} postit-min${p.classList.contains('selected') ? ' selected' : ''}" data-id="${p.dataset.id}" data-tags="${p.dataset.tags}">${progress?`<div class="postit-progress-container"><div class="postit-progress" style="width:${progress}%;background:${H.getProgressbarColor(progress)}"><span>${progress}%</span></div></div>`:""}${writeAccess?`<span>${(postits.length > 1)?`<i class="fas fa-arrows-alt-v fa-xs"></i>`:""}</span>`:""} ${title}</li>`;
        });

      // Create cell container for list of minified notes
      tag.insertBefore(H.createElement('div',
        {className: 'cell-list-mode'},
        null,
        `<ul style="max-width:${cellWidth}px;max-height:${cellHeight-1}px">${html}</ul>`), tag.firstChild);

      // Make notes list sortable
      if (writeAccess) {
        // TODO Do not use jQuery here
        $(tag.querySelector('.cell-list-mode ul')).sortable({
          //containment: $cell,
          handle: '>span',
          cursor: 'move',
          start: () => {
            this.edit(() => S.set('revertData', true), true);
          },
          stop: (e, ui) => {
            if (S.get('revertData')) {
              S.unset('revertData');
              $(e.target).sortable('cancel');
              this.unedit(true);
            } else {
              ui.item[0].parentNode.querySelectorAll('li').forEach((li, i) =>
                tag.querySelector(`.postit[data-id="${li.dataset.id}"]`)
                  .dataset.order = i + 1);
              this.unedit();
            }
          }
        });
      }

      this.setPostitsUserWritingListMode();

    // If we must display full postit
    // postit-mode
    } else {
      tag.classList.remove('list-mode');
      tag.classList.add('postit-mode');

      // Remove menu count and list of minified notes
      tag.querySelectorAll('.cell-list-mode,.cell-menu .wpt-badge')
        .forEach((el) => el.remove());

      // Display postits
      tag.querySelectorAll('.postit').forEach((p) => {
        p.style.visibility = 'visible';
        P.get(p, 'postit').showPlugs();
      });

      // Update cell's menu icon        
      displayMode.classList.replace('fa-list-ul', 'fa-sticky-note');

      if (writeAccess && !S.get('zoom-level')) {
        $cell.resizable('enable');
      }
    }
  }

  // METHOD toggleDisplayMode()
  toggleDisplayMode(refresh = false) {
    const tag = this.tag;
    const settings = this.settings;
    let type;

    if (tag.classList.contains('postit-mode') || refresh) {
      type = 'list-mode';
      if (refresh) {
        tag.querySelectorAll('.cell-list-mode,.cell-menu .wpt-badge')
        .forEach((el) => el.remove());
      }
    } else {
      type = 'postit-mode';
    }

    this.setPostitsDisplayMode(type);

    // Re-apply filters
    const f = S.getCurrent('filters');
    if (f.isVisible()) {
      f.apply({norefresh: true});
    }

    if (!refresh) {
      settings.usersettings.displaymode = type;
      H.fetch (
        'POST',
        `user/wall/${settings.wallId}/settings`,
        {key: `cell-${settings.id}`, value: settings.usersettings});
    }
  }

  // METHOD decCount()
  decCount() {
    const el = this.tag.querySelector('.cell-menu .wpt-badge');

    if (el) {
      el.innerText = parseInt(el.innerText) - 1; 
    }
  }

  // METHOD remove()
  remove() {
    const tag = this.tag;

    // Delete nots from cell
    tag.querySelectorAll('.postit').forEach((p) => P.get(p, 'postit').remove());

    // Delete cell
    P.remove(tag, 'cell');
    tag.remove();
  }

  // METHOD reorganize()
  async reorganize(cells) {
    (cells || [this.tag]).forEach((el) => {
      el.querySelectorAll('.postit').forEach(
        (p) => P.get(p, 'postit').fixPosition(el.getBoundingClientRect()));
    });
  }

  // METHOD serialize()
  serialize(args = {}) {
    const cells = [];
    const postitDiv = document.createElement('div');
    const wall = S.getCurrent('wall');
    const postit = P.getOrCreate(postitDiv, 'postit', {
      wall: wall,
    });
    let postits;

   wall.tag.querySelectorAll('tbody.wpt td.wpt')
        .forEach((cell) => {
      cells.push({
        id: Number(cell.dataset.id.substring(5)),
        width: parseInt(cell.style.width),
        height: parseInt(cell.style.height),
        item_row: cell.parentNode.rowIndex - 1,
        item_col: cell.cellIndex - 1,
        postits: (!args.noPostits &&
                  (postits = cell.querySelectorAll('.postit')).length) ?
                     postit.serialize({...args, postits}) : null,
      });
    });

    // Clean plugins cache
    P.remove(postitDiv, 'postit');

    return cells;
  }

  // METHOD addPostit()
  addPostit(args, noinsert) {
    const tag = this.tag;
    const {wall, wallId, id: cellId} = this.settings;
    const wallTag = wall.tag;
    const postitDiv = document.createElement('div');

    // CREATE postit
    // No perf killer spread operator here!
    args.wall = wall;
    args.wallId = wallId;
    args.cell = this;
    args.cellId = cellId;
    const postit = P.getOrCreate(postitDiv, 'postit', args);

    // Add postit on cell
    tag.appendChild(postitDiv);

    // If we are refreshing wall and postit has been already created by
    // another user, do not add it again in DB
    if (!noinsert) {
      this.reorganize();
      postit.insert();
    } else {
      const toDelete = wallTag.querySelector('[data-id=postit-undefined]');

      if (toDelete) {
        // Clean plugins cache
        P.remove(toDelete, 'postit');
      }

      if (tag.classList.contains('postit-mode')) {
        postitDiv.style.visibility = 'visible';
      }
    }

    return postit;
  }

  // METHOD update()
  update(d) {
    const tag = this.tag;
    const bbox = tag.getBoundingClientRect();
    const idx = tag.cellIndex - 1;
    const W = parseInt(d.width);
    const H = parseInt(d.height);

    // If width has changed
    if (parseInt(bbox.width) !== W) {
      this.settings.wall.tag.querySelectorAll('tbody.wpt tr.wpt')
          .forEach((tr) => {
        const td = tr.querySelectorAll('td.wpt')[idx];

        td.style.width = `${W}px`;
        td.querySelector('div.ui-resizable-s').style.width = `${W}px`;
      });
    }

    // If height has changed
    if (parseInt(bbox.height) !== H) {
      const tr = tag.parentNode;

      tr.querySelectorAll('th.wpt').forEach((el) =>
        el.style.height = `${H}px`);

      let div;
      tr.querySelectorAll('td.wpt').forEach((td) => {
        td.style.height = `${H}px`;
        if ( (div = td.querySelector('div.ui-resizable-e')) ) {
          div.style.height = `${H}px`;
        }
      });
    }
  }

  // METHOD edit()
  edit(onError, nopush) {
    if (nopush || !this.settings.wall.isShared()) return;

    H.request_ws(
      'PUT',
      `wall/${this.settings.wallId}/editQueue/cell/${this.settings.id}`,
      null,
      // success cb
      (d) => {
        if (d.error_msg) {
          H.raiseError(() => onError && onError(), d.error_msg);
        }
      },
      // error cb
      onError);
  }

  // METHOD unedit()
  unedit(noupdate = false, move) {
    const wall = this.settings.wall;
    const wallTag = wall.tag;
    const data = noupdate ?
      null :
      {
        cells: this.serialize({noPostitContent: true}),
        wall: {
          width: Math.trunc(wallTag.dataset.displayheaders === '0' ?
                   wall.getTDsWidth() +
                     wallTag.querySelector('tbody.wpt th.wpt').clientWidth :
                    wallTag.clientWidth),
        },
      };

    // If we are moving col/row
    if (data && move) {
      move.headers = P.get(this.tag.closest('tr.wpt')
        .querySelector('th.wpt'), 'header').serialize();
      data.move = move;
    }

    H.request_ws (
      "DELETE",
      `wall/${this.settings.wallId}/editQueue/cell/${this.settings.id}`,
      data
    );
  }
});

//////////////////////////////////// INIT ////////////////////////////////////

document.addEventListener('DOMContentLoaded', () => {
  if (H.isLoginPage()) return;

  // LOCAL FUNCTION __dblclick()
  const __dblclick = (e) => {
    const tag = e.target;
    const wall = S.getCurrent('wall');

    if (S.get('action-mmenu') ||
        S.get('zoom-level') ||
        S.get('postit-creating') ||
        (
          (
            tag.tagName !== 'TD' ||
            !tag.classList.contains('wpt')
          ) &&
          !tag.classList.contains('cell-list-mode')
        ) ||
        !wall.canWrite()) {
      return e.stopImmediatePropagation();
    }

    S.set('postit-creating', true, 500);

    const cell = P.get(tag, 'cell');
    const tCoords = e && e.changedTouches && e.changedTouches[0];
    const cellPos = tag.getBoundingClientRect();
    const pTop = (tCoords ? tCoords.clientY : e.pageY) - cellPos.top;
    const pLeft = (tCoords ? tCoords.clientX : e.pageX) - cellPos.left;

    wall.closeAllMenus();

    const f = S.getCurrent('filters');
    f.isVisible() && f.reset();

    cell.addPostit({
      access: cell.settings.access,
      item_top: pTop,
      item_left: pLeft - 15,
    });
  };

  if (H.haveMouse()) {
    // EVENT dblclick on cell
    document.addEventListener('dblclick', __dblclick);
  }

  if (H.haveTouch()) {
    const walls = S.getCurrent('walls');
    // EVENT dbltap on cell
    walls.addEventListener('dbltap', ({detail: e}) => __dblclick(e));
    // Fixes issue with some touch devices
    walls.addEventListener('touchstart', (e) => {
      document.querySelectorAll('#main-menu.show').forEach(
        (el) => bootstrap.Collapse.getInstance(el).hide());
    });
  }

  // EVENT "click"
  document.querySelector('.tab-content.walls')
      .addEventListener('click', (e) => {
    const el = e.target;
    const mm = S.getCurrent('mmenu');

    if (el.matches('td.wpt *')) {
      const tag = el.closest('td.wpt');

      // EVENT "click" on cell's menu
      if (el.matches('.cell-menu,.cell-menu *')) {
        const menu = (el.tagName === 'DIV') ? el : el.closest('div');

        e.stopImmediatePropagation();

        if (!H.disabledEvent()) {
          P.get(tag, 'cell').toggleDisplayMode();
        } else {
          H.preventDefault(e);
        }
      // EVENT "click" on note in stack mode
      } else if (el.classList.contains('postit-min')) {
        const postit = P.get(tag.querySelector(
          `.postit[data-id="${el.dataset.id}"]`), 'postit');

        e.stopImmediatePropagation();

        if (e.ctrlKey) {
          H.preventDefault(e);

          if (el.classList.contains('selected')) {
            mm.remove(postit.getId());
          } else {
            mm.add(postit);
          }
        } else {
          H.preventDefault(e);

          if (!H.disabledEvent()) {
            postit.openPostit(el);
          }
        }
      }
    // EVENT "click" ctrl+click on cell to paste/cut into
    } else if (el.matches('td.wpt')) {
      if (!S.get('zoom-level') &&
          (e.ctrlKey || S.get('action-mmenu')) &&
          !mm.isEmpty()) {
        e.stopImmediatePropagation();

        mm.apply({event: e, cell: P.get(el, 'cell')});
      }
    }
  });
});

})();
