<?php
/**
Javascript plugin - Wall menu

Scope: Wall
Name: wmenu
Description: Manage wall's floating menu
*/

require_once(__DIR__.'/../prepend.php');

?>

/////////////////////////////////// PLUGIN ////////////////////////////////////

P.register('wmenu', class extends Wpt_toolbox {
  // METHOD constructor()
  constructor(settings) {
    super(settings);
    this.settings = settings;
    this.tag = settings.tag;

    const tag = this.tag;
    const wall = this.settings.wall;
    const adminAccess =
      H.checkAccess(<?=WPT_WRIGHTS_ADMIN?>, this.settings.access);
    const menuItems = [
      // Notes display mode (list or sticky)
      {
        action: 'list-mode',
        title: `<?=_("Display in stack mode")?>`,
        icon: 'tasks',
        cls: 'notset',
      },
      {
        action: 'postit-mode',
        title: `<?=_("Display in sticky notes mode")?>`,
        icon: 'tasks',
        cls: 'set',
      },
      // Headers control (shown or hidden)
      {
        action: 'show-headers',
        title: `<?=_("Show wall headers")?>`,
        icon: 'h-square',
        cls: 'notset',
      },
      {
        action: 'hide-headers',
        title: `<?=_("Hide wall headers")?>`,
        icon: 'h-square',
        cls: 'set',
      },
      // External refs (loaded or not loaded)
      {
        action: 'unblock-externalref',
        title: `<?=_("Show external contents")?>`,
        icon: 'link',
        cls: 'notset',
       },
      {
        action: 'block-externalref',
        title: `<?=_("Block external contents")?>`,
        icon: 'link',
        cls: 'set',
      },
      {}, // Divider
      // Add columns or rows (only if user if wall admin)
      adminAccess ?
        {
          action: 'add-col',
          title: `<?=_("Add column")?>`,
          icon: 'grip-lines-vertical',
        } : null,
      adminAccess ?
        {
          action: 'add-row',
          title: `<?=_("Add row")?>`,
          icon: 'grip-lines',
        } : null,
      adminAccess ? {} : null, // Divider
      // Notes search
      {
        action: 'search',
        title: `<?=_("Search...")?>`,
        icon: 'search',
      },
      // Wall sharing
      {
        action: 'share',
        title: `<?=_("Share...")?>`,
        icon: 'share',
      },
      {cls: 'hidden'}, // Divider
      {
        action: 'show-users',
        title: `<?=_("Users viewing this wall")?>`,
        icon: 'user-friends',
        className: 'usersviewcounts',
        content: `<span class="wpt-badge inset"></span>`,
      },
    ];

    tag.classList.add('toolbox');

    menuItems.forEach((args) => {
      if (!args) return;
      const {action, title, icon, className, cls, content} = args;
      const options = {title};
      if (className) {
        options.className = className;
      }
      tag.appendChild(action ?
        // Menu item
        H.createElement('li',
          options,
          {action},
          `<i class="fa-fw fas fa-${icon} fa-lg ${cls || ''}"></i> ${content ? content : ''}`) :
        // Divider
        H.createElement('li', {className: `divider ${cls || ''}`})); 
    });

    // TODO Do not use jQuery here
    $(tag).draggable({
      distance: 10,
      cursor: 'move',
      drag: (e, ui) => this.fixDragPosition(ui),
      stop: () => S.set('dragging', true, 500),
    });

    // EVENT "click" on wall menu
    tag.addEventListener('click', (e) => {
      const el = e.target;

      if (el.matches('li,li *')) {
        if (H.disabledEvent()) return false;

        const li = (el.tagName === 'LI') ? el : el.closest('li');
        const action = li.dataset.action;

        switch (action) {
          case 'share':
            H.loadPopup('swall', {
              open: false,
              cb: (p) => P.getOrCreate(p, 'swall').open(),
            });
            break;
          case 'add-col':
          case 'add-row':
            wall.createColRow(action.split('-')[1]);
            break;
          case 'search':
            H.loadPopup('psearch', {
              open: false,
              cb: (p) => P.getOrCreate(p, 'psearch').open(),
            });
            break
          case 'postit-mode':
            wall.setPostitsDisplayMode(action);
            H.waitForDOMUpdate(() => wall.refreshPostitsPlugs(true));
            break;
          case 'list-mode':
            S.getCurrent('mmenu').close();
            wall.setPostitsDisplayMode(action);
            break;
          case 'unblock-externalref':
            wall.displayExternalRef(1, true);
            H.displayMsg({
              type: 'info',
              msg: `<?=_("External contents are no longer filtered")?>`,
            });
            break;
          case 'block-externalref':
            wall.displayExternalRef(0, true);
            H.displayMsg({
              type: 'info',
              msg: `<?=_("External contents are now filtered")?>`,
            });
            break;
          case 'show-headers':
            wall.displayHeaders(1);
            H.waitForDOMUpdate(() => wall.refreshPostitsPlugs(true));
            break;
          case 'hide-headers':
            wall.displayHeaders(0);
            break;
          case 'show-users':
            wall.displayWallUsersview();
            break;
        }
      }
    });
  }
});
