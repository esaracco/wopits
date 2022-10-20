<?php
/**
  Javascript plugin - Cell

  Scope: Wall
  Element: td
  Description: Wall's cell
*/

  require_once(__DIR__.'/../prepend.php');

  $Plugin = new Wopits\jQueryPlugin('cell', 'width: 300, height: 200',
                                    'wallElement');
  echo $Plugin->getHeader();

?>

  /////////////////////////// PUBLIC METHODS ////////////////////////////

  Plugin.prototype = {
    // METHOD init()
    init() {
      const plugin = this;
      const $cell = plugin.element;
      const cell = $cell[0];
      const settings = plugin.settings;
      const cellId = settings.id;
      const usersettings = settings.usersettings;
      const $wall = settings.wall;
      const writeAccess = plugin.canWrite();
      // Coords of touchstart on touch devices
      let _coords = null;

      cell.classList.add(
          usersettings.displaymode || $wall[0].dataset.displaymode);

      // Add cell menu
      cell.insertBefore($(`<div class="cell-menu"><span class="btn btn-sm btn-secondary btn-circle"><i class="fas fa-sticky-note fa-fw"></i></span></div>`)[0], cell.firstChild);

      if (writeAccess) {
        $cell
          // Make cell DROPPABLE
          .droppable({
            accept: '.postit',
            tolerance: 'pointer',
            scope: 'dzone',
            classes: {'ui-droppable-hover': 'droppable-hover'},
            drop: function(e, ui) {
              if (S.get('revertData').revert) return;

              const $cell = $(this);
              const cellPos = this.getBoundingClientRect();
              const $postit = ui.draggable;
              const ptop = ui.offset.top - cellPos.top;
              const pleft = ui.offset.left - cellPos.left;

              $postit.postit('setPosition', {
                cellId,
                top: (ptop < 0) ? 0 : ptop,
                left: (pleft < 0) ? 0 : pleft,
              });
  
              this.append($postit[0]);
  
              $cell.cell('reorganize').then(()=> $postit.postit('dropStop'));
            },
          });
      }

      // Make cell resizable
      $cell.resizable({
        disabled: !writeAccess,
        autoHide: false,
        ghost: true,
        minWidth: settings.width,
        minHeight: settings.height,
        helper: 'resizable-helper',
        resize: function(e, ui) {
          if (S.get('revertData').revert) {
            $('body')[0].removeAttribute('style');
 
            return false;
          }
        },
        start: function(e, ui) {
          const editable = $wall[0].querySelectorAll('.editable');
 
          // Cancel all editable (blur event is not triggered on resizing).
          if (editable.length) {
            editable.forEach((el) => $(el).editable('cancel'));
          }
 
          S.set('revertData', {
            revert: false,
            size: {
              width: $cell.outerWidth(),
              height: $cell.outerHeight(),
            }
          });
 
          plugin.edit(() => S.get('revertData').revert = true);
        },
        stop:function(e, ui) {
          const revertData = S.get('revertData');
 
          S.unset('revertData');
 
          if (revertData.revert) {
            $cell.css({
              width: revertData.size.width,
              height: revertData.size.height,
            });
          } else {
            const absH = Math.abs(ui.size.height - ui.originalSize.height);
            const absW = Math.abs(ui.size.width - ui.originalSize.width);
 
            // Height
            if (absH < 2 || absH > 2) {
              if ($wall[0].dataset.cols === '1' &&
                  $wall[0].dataset.rows === '1') {
                plugin.update({
                  width: ui.size.width + 3,
                  height: ui.size.height,
                });
              } else {
                $cell.closest('tr.wpt').find('th.wpt:first-child')
                    .css('height', ui.size.height);
 
                plugin.update({width: ui.size.width + 2});
 
                // Set height for all cells of the current row
                $wall.find(
                  `tbody.wpt tr.wpt:eq(${$cell.parent().index()}) td.wpt`)
                  .each(function() {
                    this.style.height = `${ui.size.height}px`;
                    this.querySelector("div.ui-resizable-e")
                        .style.height = `${ui.size.height + 2}px`;
                  });
              }
            }
 
            // Width
            if (absW < 2 || absW > 2) {
              $wall
                .find('tbody.wpt tr.wpt')
                .find(`td.wpt:eq(${$cell.index() - 1})`)
                .each (function() {
                  this.style.width = `${ui.size.width}px`;
                  this.querySelector('div.ui-resizable-s')
                      .style.width = `${ui.size.width+2}px`;
                });
 
                $wall.wall('fixSize', ui.originalSize.width, ui.size.width);
            }
 
            $wall.find('tbody.wpt td.wpt').cell('reorganize');
 
            plugin.unedit();
          }
        }
      });

      if (writeAccess) {
        // LOCAL FUNCTION __dblclick()
        const __dblclick = (e) => {
          if (S.get('zoom-level') || S.get('postit-creating') ||
              (
                (
                  e.target.tagName !== 'TD' ||
                  !e.target.classList.contains('wpt')
                ) &&
                !e.target.classList.contains('cell-list-mode')
              )) {
            return e.stopImmediatePropagation();
          }

          S.set('postit-creating', true, 500);

          const cellOffset = $cell.offset();
          const pTop =
              ((_coords && _coords.changedTouches) ?
                _coords.changedTouches[0].clientY : e.pageY) - cellOffset.top;
          const pLeft =
              ((_coords && _coords.changedTouches) ?
                _coords.changedTouches[0].clientX : e.pageX) - cellOffset.left;

           _coords = null;

           $wall.wall('closeAllMenus');

           const $f = S.getCurrent('filters');
           if ($f.is(':visible')) {
             $f.filters('reset');
           }

           plugin.addPostit({
             access: settings.access,
             item_top: pTop,
             item_left: pLeft - 15,
           });
        };

        // Touch devices
        if ($.support.touch) {
          $cell
            // EVENT touchstart on cell to retrieve touch coords
            .on('touchstart', (e) => {
              _coords = e;
              // Fix issue with some touch devices
              $(".navbar-nav,.dropdown-menu").collapse("hide");
            })
            // EVENT MOUSEDOWN on cell
            .doubletap(__dblclick);
          // No touch device
        } else {
          $cell.dblclick(__dblclick);
        }
      }

      let h;
      let w;

      if ($cell[0].classList.contains('size-init')) {
        w = $cell.outerWidth();
        h = $cell.outerHeight();
      } else {
        const $trPrev = $cell.parent().prev();
        const $tdPrev = $trPrev.length ?
                $trPrev.find(`td.wpt:eq(${$cell.index() - 1})`) : undefined;

        w = $tdPrev ? $tdPrev.css('width') : settings.width;
        h = $tdPrev ? $tdPrev.css('height') : settings.height;
      }

      plugin.update({width: w, height: h});
    },

    // METHOD showUserWriting()
    showUserWriting(user) {
      const cell = this.element[0];

      setTimeout(() => {
        cell.classList.add('locked');
        cell.insertBefore($(`<div class="user-writing main" data-userid="${user.id}"><i class="fas fa-user-edit blink"></i> ${user.name}</div>`)[0], cell.firstChild);
      }, 150);
    },

    // METHOD setPostitsUserWritingListMode()
    // See postit::showUserWriting()
    setPostitsUserWritingListMode() {
      this.element[0].querySelectorAll('.user-writing').forEach((el) => {
        const p = el.parentNode;
        const min = p.parentNode.querySelector(
                        `.postit-min[data-id="${p.dataset.id}"]`);

        if (min) {
          min.classList.add('locked');
          min.insertBefore($(`<span class="user-writing-min${el.classList.contains("main")?" main":""}" data-userid="${el.dataset.userid}"><i class="${el.querySelector("i").className} fa-sm"></i></span>`)[0], min.firstChild);
        }
      });
    },

    // METHOD setPostitsDisplayMode()
    setPostitsDisplayMode(type) {
      const plugin = this;
      const $cell = plugin.element;
      const cell = $cell[0];
      const $displayMode = $cell.find('.cell-menu i');
      const writeAccess = plugin.canWrite();

      // If we must display list of minified notes
      // list-mode
      if (type === 'list-mode') {
        const cellWidth = cell.clientWidth;
        const cellHeight = cell.clientHeight;
        // FIXME
        //const postits = Array.from (cell.querySelectorAll('.postit'));
        const postits = Array.from(
          cell.querySelectorAll('.postit:not([data-order="undefined"])'));

        cell.classList.remove('postit-mode');
        cell.classList.add('list-mode');

        // Cell can not be resizable in minified moe
        $cell.resizable('disable');

        // Update cell's menu icon        
        $displayMode[0].classList.replace('fa-sticky-note', 'fa-list-ul');

        // Add notes count to cell's menu
        cell.querySelector('.cell-menu').append(H.createElement('span',
          {className: 'wpt-badge'},
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
              return parseInt(b.dataset.id.split(/\-/)[1]) -
                       parseInt(a.dataset.id.split(/\-/)[1]);
            } else {
              return aOrder - bOrder;
            }
          })
          .forEach((p) => {
            const color = (p.className.match(/ color\-([a-z]+)/))[1];
            const postitPlugin = $(p).postit('getClass');
            const title = p.querySelector('.title').innerHTML;
            const progress = Number(p.dataset.progress || 0);

            postitPlugin.closeMenu();
            postitPlugin.hidePlugs();

            p.style.visibility = 'hidden';

            html += `<li class="color-${color} postit-min${p.classList.contains('selected') ? ' selected' : ''}" data-id="${p.dataset.id}" data-tags="${p.dataset.tags}">${progress?`<div class="postit-progress-container"><div class="postit-progress" style="width:${progress}%;background:${H.getProgressbarColor(progress)}"><span>${progress}%</span></div></div>`:""}${writeAccess?`<span>${(postits.length > 1)?`<i class="fas fa-arrows-alt-v fa-xs"></i>`:""}</span>`:""} ${title}</li>`;
          });

        // Create cell container for list of minified notes
        cell.insertBefore(H.createElement('div',
          {className: 'cell-list-mode'},
          null,
          `<ul style="max-width:${cellWidth}px;max-height:${cellHeight-1}px">${html}</ul>`), cell.firstChild);

        // Make notes list sortable
        if (writeAccess) {
          $cell.find('.cell-list-mode ul').sortable({
            //containment: $cell,
            handle: '>span',
            cursor: 'move',
            sort: function() {
              if (S.get('revertData').revert) {
                $('body')[0].removeAttribute('style');
                $(this).sortable('cancel');
                return false;
              }
            },
            start: function() {
              S.set('revertData', {revert: false});
              plugin.edit(()=> S.get('revertData').revert = true, true);
            },
            stop: function(e, ui) {
              const revertData = S.get('revertData');

              if (revertData.revert) {
                S.unset('revertData');
                plugin.unedit(true);
              } else {
                ui.item[0].parentNode.querySelectorAll('li').forEach((li, i) =>
                  cell.querySelector(`.postit[data-id="${li.dataset.id}"]`)
                    .dataset.order = i + 1);
                plugin.unedit();
              }
            }
          });
        }

        plugin.setPostitsUserWritingListMode();

      // If we must display full postit
      // postit-mode
      } else {
        cell.classList.remove('list-mode');
        cell.classList.add('postit-mode');

        // Remove menu count and list of minified notes
        cell.querySelectorAll('.cell-list-mode,.cell-menu .wpt-badge')
          .forEach((el) => el.remove());

        // Display postits
        cell.querySelectorAll('.postit').forEach((p) => {
          p.style.visibility = 'visible';
          $(p).postit('showPlugs');
        });

        // Update cell's menu icon        
        $displayMode[0].classList.replace('fa-list-ul', 'fa-sticky-note');

        if (writeAccess && !S.get('zoom-level')) {
          $cell.resizable('enable');
        }
      }
    },

    // METHOD toggleDisplayMode()
    toggleDisplayMode(refresh = false) {
      const cell = this.element[0];
      const settings = this.settings;
      let type;

      if (cell.classList.contains('postit-mode') || refresh) {
        type = 'list-mode';
        if (refresh) {
          cell.querySelectorAll('.cell-list-mode,.cell-menu .wpt-badge')
          .forEach((el) => el.remove());
        }
      } else {
        type = 'postit-mode';
      }

      this.setPostitsDisplayMode(type);

      // Re-apply filters
      const $f = S.getCurrent('filters');
      if ($f.is(':visible')) {
        $f.filters('apply', {norefresh: true});
      }

      if (!refresh) {
        settings.usersettings.displaymode = type;
        H.fetch (
          'POST',
          `user/wall/${settings.wallId}/settings`,
          {key: `cell-${settings.id}`, value: settings.usersettings});
      }
    },

    // METHOD decCount()
    decCount() {
      const el = this.element[0].querySelector('.cell-menu .wpt-badge');

      if (el) {
        el.innerText = parseInt(el.innerText) - 1; 
      }
    },

    // METHOD remove()
    remove() {
      this.element[0].querySelectorAll('.postit').forEach((p) =>
          $(p).postit('remove', true));

      this.element.remove();
    },

    // METHOD reorganize()
    async reorganize() {
      this.element.each(function() {
        this.querySelectorAll('.postit').forEach((p) =>
            $(p).postit('fixPosition', this.getBoundingClientRect()));
      });
    },

    // METHOD serialize()
    serialize(args = {}) {
      const cells = [];
      let postits;

      S.getCurrent('wall')[0].querySelectorAll('tbody.wpt td.wpt')
          .forEach((cell) => {
        cells.push({
          id: cell.dataset.id.substring(5),
          width: parseInt(cell.style.width),
          height: parseInt(cell.style.height),
          item_row: cell.parentNode.rowIndex - 1,
          item_col: cell.cellIndex - 1,
          postits: (!args.noPostits &&
                    (postits = cell.querySelectorAll('.postit')).length) ?
                       $(postits).postit('serialize', args) : null,
        });
      });

      return cells;
    },

    // METHOD addPostit()
    addPostit(args, noinsert) {
      const cell = this.element[0];
      const {wall, wallId, id: cellId} = this.settings;
      const $postit = $('<div/>');

      // CREATE postit
      // No perf killer spread operator here!
      args.wall = wall;
      args.wallId = wallId;
      args.cell = $(cell);
      args.cellId = cellId;
      $postit.postit(args);

      // Add postit on cell
      cell.appendChild($postit[0]);

      this.reorganize();

      // If we are refreshing wall and postit has been already created by
      // another user, do not add it again in DB
      if (!noinsert) {
        $postit.postit('insert');
      } else if (cell.classList.contains('postit-mode')) {
        if (args.init || H.isVisible(S.getCurrent('filters')[0])) {
          $postit[0].style.visibility = 'visible';
        } else {
          $postit.hide();
          $postit[0].style.visibility = 'visible';
          $postit.show('fade');
        }
      }

      return $postit;
    },

    // METHOD update ()
    update (d)
    {
      const $cell = this.element,
            cell = $cell[0],
            bbox = cell.getBoundingClientRect (),
            idx = $cell.index () - 1,
            W = parseInt (d.width),
            H = parseInt (d.height);

      // If width has changed
      if (parseInt (bbox.width) != W)
        this.settings.wall[0].querySelectorAll("tbody.wpt tr.wpt").forEach (tr =>
          {
            const td = tr.querySelectorAll("td.wpt")[idx];

            td.style.width = `${W}px`;
            $(td).find(">div.ui-resizable-s").css ("width", W);
          });

      // If height has changed
      if (parseInt (bbox.height) != H)
      {
         const tr = cell.parentNode;

        tr.querySelectorAll("td.wpt").forEach (td =>
          {
            tr.querySelector("th.wpt").style.height = `${H}px`;

            td.style.height = `${H}px`;
            $(td).find(">div.ui-resizable-e").css ("height", H);
         });
       }
    },

    // METHOD edit ()
    edit (error_cb, nopush)
    {
      if (nopush || !this.settings.wall.wall ("isShared"))
        return;

      H.request_ws (
        "PUT",
        `wall/${this.settings.wallId}/editQueue/cell/${this.settings.id}`,
        null,
        // success cb
        (d) => d.error_msg &&
                 H.raiseError (() => error_cb && error_cb (), d.error_msg)
      );
    },

    // METHOD unedit ()
    unedit (noupdate = false, move)
    {
      const wall = this.settings.wall[0],
            data = noupdate ?
              null :
              {
                cells: this.serialize ({noPostitContent: true}),
                wall: {
                  width: Math.trunc (wall.dataset.displayheaders == "0" ?
                    this.settings.wall.wall ("getTDsWidth") +
                      wall.querySelector("tbody.wpt th.wpt").clientWidth
                    :
                    wall.clientWidth
                  )
                }
              };

      // If we are moving col/row
      if (data && move)
      {
        move.headers =
          this.element.closest("tr.wpt").find("th.wpt").header ("serialize");
        data.move = move;
      }

      H.request_ws (
        "DELETE",
        `wall/${this.settings.wallId}/editQueue/cell/${this.settings.id}`,
        data
      );
    }
  };

  /////////////////////////// AT LOAD INIT //////////////////////////////

  document.addEventListener('DOMContentLoaded', () => {
    if (H.isLoginPage()) return;

    // EVENT "click"
    document.querySelector('.tab-content.walls')
        .addEventListener('click', (e) => {
      const el = e.target;
      const $mm = S.getCurrent('mmenu');

      if (el.matches('td.wpt *')) {
        const $cell = $(el.closest('td.wpt'));

        // EVENT "click" on cell's menu
        if (el.matches('.cell-menu *')) {
          e.stopImmediatePropagation();

          if (!H.disabledEvent()) {
            $cell.cell('toggleDisplayMode');
          } else {
            H.preventDefault(e);
          }
        // EVENT "click" on note in stack mode
        } else if (el.classList.contains('postit-min')) {
          const $p = $cell.find(`.postit[data-id="${el.dataset.id}"]`);

          e.stopImmediatePropagation();
  
          if (e.ctrlKey) {
            H.preventDefault(e);

            if (el.classList.contains('selected')) {
              $mm.mmenu('remove', $p.postit('getId'));
            } else {
              $mm.mmenu('add', $p.postit('getClass'));
            }
          } else {
            H.preventDefault(e);
  
            if (!H.disabledEvent()) {
              $p.postit('openPostit', $(el));
            }
          }
        }
      // EVENT "click" ctrl+click on cell to paste/cut into
      } else if (el.matches('td.wpt')) {
        if ((e.ctrlKey || S.get('action-mmenu')) && !$mm.mmenu('isEmpty')) {
          e.stopImmediatePropagation();

          $mm.mmenu('apply', {
            event: e,
            cellPlugin: $(el).cell('getClass'),
          });
        }
      }
    });
  });

<?php echo $Plugin->getFooter()?>
