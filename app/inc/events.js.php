/**
  Global javascript events
*/

document.addEventListener('DOMContentLoaded', () => {
  'use strict';

  const _walls = document.getElementById('walls');
  let _closeVKB = false;

  if (!H.isLoginPage()) {
    // CREATE CUSTOM EVENT dbltap
    if (H.haveTouch()) {
      let _timeoutTouch = null;
      document.addEventListener('touchstart', (e) => {
        if (!_timeoutTouch) {
          _timeoutTouch = setTimeout(() => _timeoutTouch = null, 300);
        } else {
          clearTimeout(_timeoutTouch), _timeoutTouch = null;
  
          // Dispatch dbltap event with touchstart properties in "detail"
          e.target.dispatchEvent(new CustomEvent('dbltap', {
            detail: e,
            bubbles: true,
          }));
        }
      });
    }

    // EVENTS resize & orientationchange on window
    let _timeoutResize = null;
    window.addEventListener('resize', (e) => {
      clearTimeout(_timeoutResize);
      _timeoutResize = setTimeout(() =>  {
        const wall = S.getCurrent('wall');
        let tmp;

        H.fixHeight();

        // Fix user msg popover scroll
        tmp = S.getCurrent('umsg');
        tmp.isPopoverVisible() && tmp.fixHeight();

        if (wall) {
          if ( (tmp = document.querySelector(
                    '.modal.show.m-fullscreen[data-customwidth]')) ) {
            H.resizeModal(tmp);
          }

          if (S.get('zoom-level')) {
            wall.zoom(true, {noalert: true});
          } else {
            // Reposition chat popup if it is out of bounds
            tmp = S.getCurrent('chat');
            tmp.isVisible() && tmp.fixPosition();

            // Reposition filters popup if it is out of bounds
            tmp = S.getCurrent('filters');
            tmp.isVisible() && tmp.fixPosition();

            // Reposition wall menu if it is out of bounds
            const wmenu = S.getCurrent('wmenu');
            wmenu.isVisible() && wmenu.fixPosition();
          }

          // Refresh relations position
          wall.repositionPostitsPlugs();
        }
      }, 150);
    });

    // EVENT "scroll" on walls
    let _timeoutScroll = null;
    let _plugsHidden = false;
    _walls.addEventListener('scroll', () => {
      const wall = S.getCurrent('wall');
      const mstack = S.get('mstack') || [];

      if (!wall) return;
  
      if (!S.get('wall-dragging') && !S.get('still-closing') &&
          !mstack.length) {
        if (!_plugsHidden) {
          wall.hidePostitsPlugs();
          _plugsHidden = true;
        }

        // Refresh relations position
        if (!S.getCurrent('filters').tag.classList.contains('plugs-hidden')) {
          clearTimeout(_timeoutScroll);
          _timeoutScroll = setTimeout(() => {
            wall.showPostitsPlugs();
            _plugsHidden = false;
          }, 150);
        }
      }
    });

    // EVENT "mousedown"
    document.addEventListener('mousedown', (e) => {
      const el = e.target;

      // EVENT "mousedown" on walls tabs
      if (el.matches('.nav-tabs.walls a.nav-link,'+
                     '.nav-tabs.walls a.nav-link *')) {
        const a = (el.tagName === 'A') ? el : el.closest('a.nav-link');
        const isActive = a.classList.contains('active');
        const isLocked = a.classList.contains('locked');
        const close = el.classList.contains('close');
        const share = (isActive && el.classList.contains('fa-share'));
        const rename = (isActive && !share && !close);

        // Open the wall's sharing popup
        if (share) {
          return H.loadPopup('swall', {
            open: false,
            cb: (p) => P.getOrCreate(p, 'swall').open(),
          });
        }

        // Open the wall popup properties
        if (rename) {
          if (!isLocked) {
            S.getCurrent('wall').openPropertiesPopup({renaming: true});
          }
          return;
        }

        // Save new current wall ID
        if (!close) {
          S.getCurrent('settings')
            .saveOpenedWalls(Number(a.getAttribute('href').split('-')[1]));
        }
      }
    });

    // EVENT "hide.bs.tab"
    document.addEventListener('hide.bs.tab', (e) => {
      const el = e.target;
  
      // EVENT "hide.bs.tab" on walls tabs
      if (el.matches(`.walls a[data-bs-toggle="tab"]`)) {
        const wall = S.getCurrent('wall');

        // Cancel zoom mode
        if (S.get('zoom-level')) {
          wall.zoom(false);
        }
  
        wall.removePostitsPlugs(false);
      }
    });
  
    // EVENT "shown.bs.tab"
    document.addEventListener('shown.bs.tab', (e) => {
      // If we are massively closing or restoring all walls, do nothing here
      if (S.get('closing-all') ||
          _walls.querySelector('.wall[data-restoring]')) {
        return;
      }
  
      const el = e.target;
  
      // EVENT "shown.bs.tab" on walls tabs
      if (el.matches(`.walls a[data-bs-toggle="tab"]`)) {
        S.reset();
    
        // The new wall
        const wall = S.getCurrent('wall');
    
        // Need a wall to continue
        if (!wall) return;
    
        _walls.scrollTop = 0;
        _walls.scrollLeft = 0;
    
        const menu = document.getElementById('main-menu');
        const mmenu = S.getCurrent('mmenu');
        const chat = S.getCurrent('chat');
        const filters = S.getCurrent('filters');
    
        // Show/hide super menu actions menu depending on user wall rights
        if (mmenu) {
          S.getCurrent('mmenu').checkAllowedActions();
        }
    
        // Manage chat checkbox menu
        if ( (menu.querySelector(`li[data-action="chat"] input`)
                 .checked = chat && chat.isVisible()) ) {
          chat.removeAlert();
          chat.setCursorToEnd();
        }
    
        // Manage filters checkbox menu
        menu.querySelector(`li[data-action="filters"] input`)
            .checked = filters && filters.isVisible();
    
        // Refresh wall if it has not just been opened
        if (!S.get('newWall')) {
          (async () => {
            await wall.refresh();
            wall.displayExternalRef();
            wall.displayHeaders();
          })();
        }

        wall.menu({from: 'wall', type: 'have-wall'});
      }
    });

    // EVENT "click" on logout button
    document.getElementById('logout').addEventListener('click', () => {
      H.closeMainMenu();
  
      H.openConfirmPopup({
        type: 'logout',
        icon: 'power-off',
        content: `<?=_("Do you really want to logout from wopits?")?>`,
        onConfirm: () => H.logout(),
      });
    });
  }

  // EVENT "online"
  window.addEventListener('online', (e) => {
    const wall = S.getCurrent('wall');

    if (wall) {
      H.loader('hide');
      wall.refresh();
    } else {
      location.reload();
    }
  });

  // EVENT "offline"
  window.addEventListener('offline', (e) => H.displayNetworkErrorMsg());

  // EVENT "keydown"
  document.addEventListener('keydown', (e) => {
    // If "ESC" while popup layer is opened, close it
    if (e.which === 27 && !S.get('noDefaultEscape')) {
      const mstack = S.get('mstack') || [];
      let tmp;

      if ( (tmp = document.querySelector('.dropdown-toggle.show')) ) {
        e.stopImmediatePropagation();
        bootstrap.Dropdown.getInstance(tmp).hide();
      // If popup layer, click on it to close popup
      } else if ( tmp = document.getElementById('popup-layer') ) {
        e.stopImmediatePropagation();
        H.preventDefault(e);
        tmp.click();
      // If postit menu, click on menu button to close it
      } else if ( tmp = document.querySelector('.postit-menu') ) {
        e.stopImmediatePropagation();
        tmp.nextElementSibling.click();
        // Close current opened modal
      } else if (mstack.length &&
                 !mstack[0].querySelector('.tox-mbtn--active')) {
        e.stopImmediatePropagation();
        H.preventDefault(e);
        bootstrap.Modal.getInstance(mstack[0]).hide();
      } else if (S.get('zoom-level')) {
        S.getCurrent('wall').zoom(false);
      }
    }
  });

  // EVENT "show.bs.dropdown" on relation's label menu, to prevent menu from
  //       opening right after dragging
  document.addEventListener('show.bs.dropdown', (e) => {
    if (H.disabledEvent()) {
      H.preventDefault(e);
    }
  });

  // EVENT "hidden.bs.toast" on alert messages
  document.addEventListener('hidden.bs.toast', (e) => e.target.remove());

  // EVENT "keypress" on popups and popovers to catch <enter> key
  document.addEventListener('keypress', (e) => {
    const el = e.target;

    if (e.which !== 13 || el.tagName === 'TEXTAREA') return;

    const popup = document.querySelector('.popover') ||
                  el.classList.contains('.modal') ||
                  el.closest('.popover,.modal');

    if (!popup) return;

    const r = Array.from(popup.querySelectorAll(
      '.btn-primary.btn-sm,.btn-primary,.btn-success')).filter(
        (item) => H.isVisible(item));

    if (r.length) {
      H.preventDefault(e);
      r[0].click();
    }
  });

  // EVENT "show" on popups
  document.addEventListener('show.bs.modal', (e) => {
    const el = e.target;
    const dialog = el.querySelector('.modal-dialog');
    const mstack = S.get('mstack') || [];
    const modalsCount = mstack.length;

    mstack.unshift(el);
    S.set('mstack', mstack);

    // If there is already opened modals
    if (modalsCount) {
      dialog.dataset.toclean = 1;
      el.classList.add('modal-sm');
      dialog.querySelectorAll('button.btn')
          .forEach((b) => b.classList.add('btn-sm'));
    } else {
      const ps = S.getCurrent('postit');

      if (dialog.dataset.toclean) {
        el.classList.remove('modal-sm');
        dialog.querySelectorAll('button.btn')
            .forEach((b) => b.classList.remove('btn-sm'));
        dialog.removeAttribute('data-toclean');
      }

      // Get postit color and set modal header color the same
      if (ps) {
        ps.setPopupColor(el);
      }
    }
  });

  // EVENT "shown" on popups
  document.addEventListener('shown.bs.modal', (e) => {
    const target = e.target;

    if (!H.haveMouse()) {
      if (!S.get('vkbData') && S.get('mstack').length === 1 &&
          !S.get('zoom-level') &&
          (
            // Exception for sharing wall and postit attachments popups
            target.classList.contains('contains-inputs') ||
            H.getFirstInputFields(e.target.querySelector('.modal-dialog'))
          )) {
        _closeVKB = H.fixVKBScrollStart();
      }
    } else if (!target.dataset.noautofocus) {
      H.setAutofocus(e.target);
    }
  });

  // EVENT "hide.bs.modal" on popups
  //       Blur input/textarea to hide virtual keyboard
  document.addEventListener('hide.bs.modal', (e) => {
    const el = e.target;

    if (!H.haveMouse()) {
      el.querySelectorAll('input,textarea').forEach((el) => el.blur());
    }

    if (_closeVKB && S.get('vkbData') && S.get('mstack').length === 1) {
      H.fixVKBScrollStop();
      _closeVKB = false;
    }
  });

  // EVENT "hidden" on popups
  document.addEventListener('hidden.bs.modal', (e) => {
    const el = e.target;
    const mstack = S.get('mstack');

    S.set('still-closing', true, 500);

    mstack.shift();
    S.set('mstack', mstack);

    // Prevent child popups from removing scroll to their parent
    if (mstack.length) {
      document.body.classList.add('modal-open');
    }

    switch (el.id) {
      case 'infoPopup':
        switch (el.dataset.popuptype) {
          // Reload app
          case 'app-upgrade':
          case 'app-reload':
            return location.href = '/r.php?u';
          case 'app-logout':
            H.logout({auto: true});
            break;
        }
        break;
      case 'postitViewPopup':
        S.getCurrent('postit').unsetCurrent();
        break;
      case 'postitAttachmentsPopup':
      case 'dpickPopup':
        S.getCurrent('postit').unedit();
        break;
      case 'confirmPopup':
        S.get('confirmPopup').onClose();
        break;
      case 'groupAccessPopup':
      case 'groupPopup':
        const li = document.querySelector('.modal li.list-group-item.active');
        if (li) {
          li.classList.remove('active');
        }
        break;
    }
  });

  // EVENT "click"
  document.addEventListener('click', (e) => {
    const el = e.target;

    // EVENT "click" on popup buttons
    if (el.matches('.modal .modal-footer .btn,.modal .modal-footer .btn *')) {
      const btn = (el.tagName === 'BUTTON') ? el : el.closest('button');
      const popup = btn.closest('.modal');
      const closePopup = !Boolean(popup.dataset.noclosure);
      const postit = S.getCurrent('postit');

      e.stopImmediatePropagation();

      popup.removeAttribute('data-noclosure');

      if (btn.classList.contains('btn-primary')) {
        switch (popup.id) {
          case 'dpickPopup':
            P.get(popup, 'dpick').save();
            break;
          case 'postitUpdatePopup':
            postit.save({
              content: tinymce.activeEditor.getContent(),
              progress: P.get(popup.querySelector('.slider'), 'slider').value(),
              title: $('#postitUpdatePopupTitle').val(),
            });
            break;
          // Upload postit attachment
          case 'postitAttachmentsPopup':
            popup.dataset.noclosure = true;
            P.get(postit.tag.querySelector('.patt'), 'patt').upload();
            break;
          case 'groupAccessPopup':
            P.get(document.getElementById('swallPopup'), 'swall').linkGroup();
            break;
          case'groupPopup':
            if (popup.dataset.action === 'update') {
              popup.dataset.noclosure = true;
            }
            break;
          // Manage confirmations
          case 'confirmPopup':
            S.get('confirmPopup').onConfirm();
            break;
          // Create new wall
          case 'createWallPopup':
            const Form = new Wpt_accountForms();
            const inputs = popup.querySelectorAll('input');

            popup.dataset.noclosure = true;

            if (Form.checkRequired(inputs) && Form.validForm(inputs)) {
              const data = {
                name: popup.querySelector('input').value,
                grid: popup.querySelector('#w-grid').checked,
              };

              if (data.grid) {
                const cr = popup.querySelectorAll('.cols-rows input');
                data.dim = {colsCount: cr[0].value, rowsCount: cr[1].value};
              }
              else {
                const wh = popup.querySelectorAll('.width-height input');
                data.dim = {
                  width: Number(wh[0].value),
                  height: Number(wh[1].value,
                )};
              }
              (async () => {
                const wallDiv = document.createElement('div');
                const wall = await P.getOrCreate(wallDiv, 'wall').create(data);

                if (wall) {
                  wall.postProcessLastWall();
                  bootstrap.Modal.getInstance(popup).hide();
                  P.remove(wallDiv, 'wall');
                }
              })();
            }
            break;
        }
      }

      if (closePopup) {
        bootstrap.Modal.getInstance(popup).hide();
      }
    // EVENT "click" on main menu and list items
    } else if (el.matches('.nav-link:not(.dropdown-toggle),.dropdown-item')) {
      H.closeMainMenu();
    }
  });
});
