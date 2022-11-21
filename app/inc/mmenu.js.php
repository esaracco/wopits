<?php
/**
Javascript plugin - Meta menu

Scope: Global
Name: mmenu
Description: Manage notes Meta menu
*/

require_once(__DIR__.'/../prepend.php');

?>

(() => {
  'use strict';

/////////////////////////////////// PLUGIN ////////////////////////////////////

P.register('mmenu', class extends Wpt_toolbox {
  // METHOD constructor()
  constructor(settings) {
    super(settings);

    this.settings = settings;
    this.tag = settings.tag;

    this.NO_DISPLAY_BTN = `<div class="mt-2"><button type="button" class="btn btn-xs btn-primary nodisplay"><?=_("I get it!")?></button></div>`;
    this.data = {postits: {}, dest: null};

    const tag = this.tag;

    this.boundMousemoveEventHandler = this.mousemoveEventHandler.bind(this);
    this.boundKeydownEventHandler = this.keydownEventHandler.bind(this);

    this.reset();

    // TODO Do not use jQuery here
    $(tag).draggable({
      distance: 10,
      cursor: 'move',
      drag: (e, ui) => this.fixDragPosition(ui),
      stop: () => S.set('dragging', true, 500),
    });

    // EVENT "click" on "close" button
    tag.querySelector('button.btn-close').addEventListener('click',
      (e) => this.close());

    // EVENT "click" on menu
    tag.addEventListener('click', (e) => {
      const el = e.target;
      const li = (el.tagName === 'LI') ? el : el.closest('li');

      if (!li || H.disabledEvent(
            !H.checkAccess(<?=WPT_WRIGHTS_RW?>) ||
            Array.from(tag.querySelectorAll('li'))
              .filter((el) => H.isVisible(el)).length === 1)) {
        return false;
      }

      const icon = li.querySelector('i');
      const set = icon.classList.contains('set');
      const args = {};

      tag.querySelectorAll('i').forEach((el) => el.classList.remove('set'));

      if (set) return;

      icon.classList.add('set');

      switch (li.dataset.action) {
        case 'delete':
        case 'cpick':
          return this.apply({event: e});
        case 'copy':
          if (!ST.noDisplay('mmenu-copy-help')) {
            args.title = `<i class="fas fa-paste fa-fw"></i> <?=_("Copy")?>`;
            args.content = `<?=_("<kbd>ctrl+click</kbd> on the destination cell to copy the selected notes")?>${this.NO_DISPLAY_BTN}`;
            args.onConfirm = () => ST.noDisplay('mmenu-copy-help', true);
          }
          break;
        case 'move':
          if (!ST.noDisplay('mmenu-move-help')) {
            args.title = `<i class="fas fa-cut fa-fw"></i> <?=_("Move")?>`;
            args.content = `<?=_("<kbd>ctrl+click</kbd> on the destination cell to move the selected notes")?>${this.NO_DISPLAY_BTN}`;
            args.onConfirm = () => ST.noDisplay('mmenu-move-help', true);
          }
          break
      }

      if (args.title) {
        H.openConfirmPopover({
          ...args,
          item: li,
          type: 'info',
          placement: 'right',
        });
      }
    });
  }

  // METHOD getAction()
  getAction() {
    const el = this.tag.querySelector('.set');

    return el ? el.parentNode.dataset.action : null;
  }

  // METHOD apply()
  apply(args = {}) {
    if (!H.checkAccess(<?=WPT_WRIGHTS_RW?>)) {
      return H.displayMsg({
        type: 'warning',
        msg: `<?=_("You need write access to perform this action")?>`,
      });
    }

    let ok = true;
    let item;
    let type;
    let title;
    let content;
    let placement = 'left';

    this.data.dest = args.cell;

    switch (this.getAction()) {
      case 'copy':
        title =  `<i class="fas fa-paste fa-fw"></i> <?=_("Copy")?>`;
        content = `<?=_("Do you want to copy the selected notes here (comments and workers will be reset)?")?>`;
        break;
      case 'move':
        title = `<i class="fas fa-cut fa-fw"></i> <?=_("Move")?>`;
        content = `<?=_("Do you want to move the selected notes here (comments and workers will be reset)?")?>`;
        break;
      case 'delete':
        item = this.tag.querySelector(`[data-action='delete']`);
        title = `<i class="fas fa-trash fa-fw"></i> <?=_("Delete")?>`;
        content = `<?=_("Delete selected notes?")?>`;
        break;
      case 'cpick':
        return this.send(args);
      default:
        ok = false;
        type = 'info';
        item = this.tag.querySelector('li');
        title = `<?=_("Copy/Move")?>`;
        content = `<?=_("Please, select the type of action first")?>`;
    }

    if (title) {
      const e = args.event;
      const cellTag = this.data.dest && this.data.dest.tag;
      let tmpDiv;

      S.set('noDefaultEscape', true);

      if (ok && cellTag) {
        cellTag.classList.add('selected');

        tmpDiv = H.createElement('div', {
          id: `copy-paste-target`,
          style: `top: ${e.clientY}px; left: ${e.clientX}px;`,
        });
        document.body.prepend(tmpDiv);
      }

      H.openConfirmPopover({
        content,
        item: item || tmpDiv,
        placement,
        type,
        title,
        onClose: () => {
          S.unset('noDefaultEscape');

          // Remove tmp div if any
          tmpDiv && tmpDiv.remove();

          (e.target.querySelector('i') || e.target).classList.remove('set');

          if (cellTag) {
            cellTag.classList.remove('selected');
            this.data.dest = null;
          }
        },
        onConfirm: () => {
          let position;

          if (cellTag) {
            const cellPos = cellTag.getBoundingClientRect();

            if (cellPos.top && cellPos.left) {
              position = {
                top: e.clientY - cellPos.top,
                left: e.clientX - cellPos.left,
              }
            } else {
              this.reset();
              H.displayMsg({
                type: 'warning',
                msg: `<?=_("The destination cell has been deleted")?>`,
              }); 
              return;
            }
          }

          this.send({...args, position});
        },
      });
    }
  }

  // METHOD send()
  send(args = {}) {
    const action = this.getAction();

    // Color picker
    switch (action) {
      case 'cpick':
        S.getCurrent('cpick').open({
          event: args.event,
          onClose: () => args.event.target.classList.remove('set'),
          onSelect: (c) => {
            H.request_ws(
              'POST',
              'postits/color',
              {
                color: c.className,
                postits: Object.keys(this.data.postits),
              }
            );
          }
        });
      break;
      // Delete
      case 'delete':
        // FIXME race condition with edit/unedit
        Object.keys(this.data.postits).forEach((id) => {
          const p = this.data.postits[id];
          p.edit({}, () => {
            p.delete();
            p.unedit();
          });
        });
        break;
      // Copy
      case 'copy':
      // Move
      case 'move':
        const cellSettings = this.data.dest.settings;
        const cellPos = this.data.dest.tag.getBoundingClientRect();
        const postits = Object.keys(this.data.postits);
        const dims = {};
        const tmp = [];

        // First, create each note (dum) to fix its position and dimensions
        // before inserting it in database
        let lastPos;
        postits.forEach((id) => {
          const {tag} = this.data.postits[id];

          if (!lastPos) {
            lastPos = args.position;
          } else {
            lastPos.top += 20;
            lastPos.left += 10;
          }

          const newP = this.data.dest.addPostit({
            width: parseInt(tag.style.width),
            height: parseInt(tag.style.height),
            item_top: lastPos.top,
            item_left: lastPos.left,
          }, true);

          tmp.push(newP.tag);

          H.waitForDOMUpdate(() => {
            newP.fixPosition(cellPos);

            const newPos = newP.tag.getBoundingClientRect();
            const item_top = newPos.top - cellPos.top;
            const item_left = newPos.left - cellPos.left;

            lastPos = dims[id] = {
              item_top: item_top < 0 ? 0 : Math.trunc(item_top),
              item_left: item_left < 0 ? 0 : Math.trunc(item_left),
              width: newPos.width,
              height: newPos.height,
            };
          });
        });

        H.waitForDOMUpdate(() => {
          // Remove temporary notes
          tmp.forEach((el) => {
            P.remove(el, 'postit');
            el.remove();
          });

          // Create real note in database with fixed dimensions
          H.request_ws(
            'PUT',
            `wall/${cellSettings.wallId}/cell/${cellSettings.id}`+
              `/postits/${action}`,
            {postits, dims},
            // success cb
            () => this.close());
        });
        break;
    }
  }

  // METHOD isEmpty()
  isEmpty() {
    return !this.itemsCount();
  }

  // METHOD reset()
  reset() {
    this.removeAll();
    this.tag.querySelectorAll('.set').forEach(
      (el) => el.classList.remove('set'));

    this.data = {postits: {}, dest: null};
  }

  // METHOD add()
  add(p) {
    if (this.isEmpty()) {
      this.open();
    }

    p.settings.cell.tag.querySelectorAll(`[data-id="${p.getId(true)}"]`)
      .forEach((el) => el.classList.add('selected'));

    this.data.postits[p.settings.id] = p;

    this.refreshItemsCount();
    this.checkAllowedActions();
  }

  // METHOD update()
  update(id, p) {
    if (this.data.postits[id]) {
      this.data.postits[id] = p;
    }
  }

  // METHOD remove()
  remove(id) {
    const p = this.data.postits[id];

    if (p) {
      p.settings.cell.tag.querySelectorAll(
        `.selected[data-id="${p.getId(true)}"]`).forEach(
          (el) => el.classList.remove('selected'));
    }

    delete this.data.postits[id];

    this.refreshItemsCount();

    if (this.isEmpty()) {
      this.close();
    } else {
      this.checkAllowedActions();
    }
  }

  // METHOD removeAll()
  removeAll() {
    Object.keys(this.data.postits).forEach((id) => this.remove(id));
  }

  // METHOD itemsCount()
  itemsCount() {
    return Object.keys(this.data.postits).length;
  }

  // METHOD refreshItemsCount()
  refreshItemsCount() {
    this.tag.querySelector('.wpt-badge').innerText = this.itemsCount();
  }

  // METHOD mousemoveEventHandler()
  mousemoveEventHandler(e) {
    S.set('mousepos', {x: e.pageX, y: e.pageY});
  }

  // METHOD keydownEventHandler()
  keydownEventHandler(e) {
    const tag = this.tag;

    // Nothing if modal was opened and is closing
    if (S.get('still-closing') || S.get('zoom-level')) return;

    // Nothing if modal/popover is opened or editable field is active
    if (!S.get('noDefaultEscape') && document.querySelector([
         '#popup-layer',
         '.modal.show',
         '.popover.show',
         '.editable.editing',
        ])) {
      return;
    }

    switch (e.which) {
      // ESC
      case 27:
        return S.get('noDefaultEscape') ?
          document.getElementById('popup-layer').click() : this.close();
      // DEL
      case 46:
        const del = tag.querySelector(`li[data-action="delete"]`);
        if (!del.querySelector('i.set')) {
          del.click();
        }
        break;
      // CTRL+C
      case 67:
        if (e.ctrlKey) {
          return tag.querySelector(`li[data-action="copy"]`).click();
        }
        break;
      // CTRL+V
      case 86:
        if (e.ctrlKey) {
          const mpos = S.get('mousepos');
          let el = document.elementFromPoint(mpos.x, mpos.y);

          if (el.tagName !== 'TD') {
            el = el.closest('td.wpt');
          }

          // Simulate click on cell
          if (el) {
            S.set('action-mmenu', true, 500);

            el.dispatchEvent(new MouseEvent('click', {
              view: window,
              bubbles: true,
              cancelable: true,
              clientX: mpos.x,
              clientY: mpos.y,
            }));
          }
        }
        break;
      // CTRL+X
      case 88:
        if (e.ctrlKey) {
          return tag.querySelector(`li[data-action="move"]`).click();
        }
        break;
    }
  }

  // METHOD open()
  open() {
    if (this.isVisible()) return;

    H.show(this.tag);

    // EVENT mousemove
    S.getCurrent('walls').addEventListener('mousemove',
      this.boundMousemoveEventHandler);
    // EVENT keydown
    document.addEventListener('keydown', this.boundKeydownEventHandler);

    if (!S.get('mstack')) {
      this.showHelp();
    }
  }

  // METHOD checkAllowedActions()
  checkAllowedActions() {
    const tag = this.tag;

    // No meta menu if full view and readonly wall
    if (S.get('zoom-level') && !H.checkAccess(<?=WPT_WRIGHTS_RW?>)) {
      this.close();
    }

    tag.style.opacity = 1;
    tag.querySelectorAll('li').forEach((el) => H.show(el));

    //FIXME No copy or cut with full view
    if (S.get('zoom-level') || !H.haveMouse()) {
      tag.querySelectorAll(`[data-action="copy"] i,[data-action="move"] i`)
        .forEach((el) => el.classList.remove('set'));
      tag.querySelectorAll(
          `[data-action="copy"],[data-action="move"], .divider`).forEach(
        (el) => H.hide(el));
    }

    for (const id in this.data.postits) {
      if (!H.checkAccess(<?=WPT_WRIGHTS_RW?>,
             this.data.postits[id].settings.wall.tag.dataset.access)) {

        //TODO Trigger on btn copy menu item
        tag.querySelector(`[data-action="copy"] i`).classList.add('set');
        tag.querySelectorAll(`li:not([data-action="copy"])`).forEach(
          (el) => H.hide(el));

        return;
      }
    };

    if (!H.checkAccess(<?=WPT_WRIGHTS_RW?>))  {
      tag.querySelectorAll(`[data-action="copy"] i.set`).forEach(
        (el) => el.classList.remove('set'));
      tag.style.opacity = .3;
    }
  }

  // METHOD showHelp()
  showHelp() {
    const writeAccess = H.checkAccess(<?=WPT_WRIGHTS_RW?>);

    if (ST.noDisplay(`mmenu-help-${writeAccess}`)) return;

    let content;

    if (writeAccess) {
      content = `<?=_("Use this menu to execute actions on multiple notes")?>:<ul><li><?=_("To select / unselect, <kbd>ctrl+click</kbd> on the note.")?></li><li><?=_("To <b>copy</b> %s1 or <b>move</b> %s2, choose the appropriate icon and <kbd>ctrl+click</kbd> on the destination cell.")?></li><li><?=_("To <b>change color</b>, click on %s3")?></li><li><?=_("To <b>delete</b>, click on %s4")?></li></ul>`.replace('%s1', `<i class="fas fa-paste fa-sm"></i>`).replace('%s2', `<i class="fas fa-cut fa-sm"></i>`).replace('%s3', `<i class="fas fa-palette fa-sm"></i>`).replace('%s4', `<i class="fas fa-trash fa-sm"></i>`);
    } else {
      content = `<?=_("Use this menu to execute actions on multiple notes")?>:<ul><li><?=_("To select / unselect, <kbd>ctrl+click</kbd> on the note.")?></li><li><?=_("<kbd>ctrl+click</kbd> on the destination cell to copy the selected notes.")?></li></ul>`;
    }

    H.openConfirmPopover({
      item: this.tag,
      type: 'info',
      title: `<i class="fas fa-bolt fa-fw"></i> <?=_("Meta menu")?>`,
      placement: 'right',
      content: content + this.NO_DISPLAY_BTN,
      onConfirm: () => ST.noDisplay(`mmenu-help-${writeAccess}`, true),
    });
  }

  // METHOD close()
  close() {
    const psearch = P.get(document.getElementById('psearchPopup'), 'psearch');

    document.removeEventListener('keydown', this.boundKeydownEventHandler);
    S.getCurrent('walls')
      .removeEventListener('mousemove', this.boundMousemoveEventHandler);
    S.unset('mousepos');

    document.querySelectorAll('.postit.selected').forEach(
      (el) => el.classList.remove('selected'));

    setTimeout(() =>
      psearch && !psearch.isVisible() && psearch.reset(true), 250);

    this.reset();
    H.hide(this.tag);
  }
});

//////////////////////////////////// INIT ////////////////////////////////////

document.addEventListener('DOMContentLoaded', () => {
  if (H.isLoginPage()) return;

  document.body.prepend(H.createElement('ul',
    {className: `toolbox shadow`, id: 'mmenu'},
    null,
    `<button type="button" class="btn-close"></button><span class="wpt-badge inset">0</span><li title="<?=_("Copy notes")?>" data-action="copy"><i class="fas fa-paste fa-fw fa-lg"></i></li><li title="<?=_("Move notes")?>" data-action="move"><i class="fas fa-cut fa-fw fa-lg"></i></li><li class="divider"></li><li title="<?=_("Change notes color")?>" data-action="cpick"><i class="fas fa-palette fa-fw fa-lg"></i></li><li title="<?=_("Delete notes")?>" data-action="delete"><i class="fas fa-trash fa-fw fa-lg"></i></li>`));

  P.create(document.getElementById('mmenu'), 'mmenu');
});

})();
