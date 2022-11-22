<?php
/**
Javascript plugin - Wall's header

Scope: Wall
Name: header
Description: Wall's headers
*/

require_once(__DIR__.'/../prepend.php');

use Wopits\DbCache;

?>

(() => {
  'use strict';

/////////////////////////////////// PLUGIN ////////////////////////////////////

P.register('header', class extends Wpt_pluginWallElement {
  // METHOD constructor()
  constructor(settings) {
    super(settings);
    const tag = this.tag;

    this.realEdit = false;
    this.originalObject = null;

    tag.dataset.id = `header-${settings.id}`;

    this.appendTitle();
    this.appendMenu();

    if (settings.picture) {
      tag.appendChild(this.getImgTemplate(settings.picture));
    }

    // Disable header if zoom is enabled
    if (S.get('zoom-level')) {
      tag.style.pointerEvents = 'none';
      if (this.canWrite()) {
        tag.style.opacity = .6;
      }
    }
  }

  // METHOD appendTitle()
  appendTitle() {
    const title = this.settings.title;

    this.tag.appendChild(
      H.createElement('div', 
        {className: 'title'},
        null,
        (title !== ' ') ? title : '&nbsp;',
    ));
  }

  // METHOD appendMenu()
  appendMenu() {
    const tag = this.tag;
    const settings = this.settings;
    const wall = settings.wall;
    const wallTag = wall.tag;
    const isCol = (settings.item_type === 'col');
    const adminAccess =
        H.checkAccess(<?=WPT_WRIGHTS_ADMIN?>, settings.access);
    
    const menu = H.createElement('ul',
      {className: 'navbar-nav mr-auto submenu'},
      null,
      adminAccess ? `<li class="nav-item dropdown"><div data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" class="nav-link dropdown-toggle"><i class="far fa-caret-square-right btn-menu" data-placement="right"></i></div><ul class="dropdown-menu shadow"><li data-action="rename"><a class="dropdown-item" href="#"><i class="fa-fw fas fa-edit"></i> <?=_("Rename")?></a></li><li data-action="add-picture"><a class="dropdown-item" href="#"><i class="fa-fw fas fa-camera-retro"></i> <?=_("Associate a picture")?></a></li>${isCol?`<li data-action="move-left"><a class="dropdown-item" href="#"><i class="fa-fw fas fa-chevron-left"></i> <?=_("Move left")?></a></li><li data-action="move-right"><a class="dropdown-item" href="#"><i class="fa-fw fas fa-chevron-right"></i> <?=_("Move right")?></a></li>`:`<li data-action="move-up"><a class="dropdown-item" href="#"><i class="fa-fw fas fa-chevron-up"></i> <?=_("Move up")?></a></li><li data-action="move-down"><a class="dropdown-item" href="#"><i class="fa-fw fas fa-chevron-down"></i> <?=_("Move down")?></a></li>`}</li><li data-action="delete"><a class="dropdown-item" href="#"><i class="fa-fw fas fa-trash"></i> <?=_("Delete")?> <span></span></a></li></ul></li>` : null,
    );

    if (adminAccess) {
      // EVENT "show.bs.dropdown" on header menu
      menu.addEventListener('show.bs.dropdown', (e) => {
        const el = e.target;
        const menu = el.closest('ul');
        const tr = tag.closest('tr.wpt');
        const deleteItem =
            menu.querySelector(`[data-action="delete"] a`);
        const moveUpItem =
            menu.querySelector(`[data-action="move-up"] a`);
        const moveDownItem =
            menu.querySelector(`[data-action="move-down"] a`);
        const moveLeftItem =
            menu.querySelector(`[data-action="move-left"] a`);
        const moveRightItem =
            menu.querySelector(`[data-action="move-right"] a`);

        el.querySelector('.nav-link i.far').classList.replace('far', 'fas');

        // Display all items by default
        menu.querySelectorAll('a').forEach((el) => H.show(el));

        // If column menu
        if (isCol) {
          const thIdx = tag.cellIndex;

          if (wallTag.querySelectorAll('thead.wpt th.wpt').length <= 2) {
            H.hide(deleteItem);
          }

          if (thIdx === 1) {
            H.hide(moveLeftItem);
          }

          if (thIdx === tr.querySelectorAll('th.wpt').length - 1) {
            H.hide(moveRightItem);
          }
        // If row menu
        } else {
          const trIdx = tr.rowIndex - 1;

          if (wallTag.querySelectorAll('tbody.wpt th.wpt').length === 1) {
            H.hide(deleteItem);
          }

          if (trIdx === 0) {
            H.hide(moveUpItem);
          }

          if (trIdx === wallTag.querySelectorAll('tr.wpt').length - 2) {
            H.hide(moveDownItem);
          }
        }

        if (isCol && wallTag.dataset.cols === '1') {
          H.hide(moveLeftItem);
          H.hide(moveRightItem);
        }

        if (!isCol && wallTag.dataset.rows === '1') {
          H.hide(moveUpItem);
          H.hide(moveDownItem);
        }
      });

      // EVENT "hide.bs.dropdown" on header menu
      menu.addEventListener('hide.bs.dropdown',
        (e) => e.target.querySelector('.nav-link i.fas')
                 .classList.replace('fas', 'far'));

      // EVENT "click" on header menu items
      menu.querySelector('.dropdown-menu').addEventListener('click', (e) => {
        const el = e.target;
        const li = el.closest('li');
        const cell = li.closest('th.wpt');
        const action = li.dataset.action;

        switch (action) {
          case 'add-picture':
            const upload = document.querySelector('.upload.header-picture');
            if (wall.isShared()) {
              // We need this to cancel edit if no img is selected by user
              // (touch device version)
              this.addUploadLayer();
              this.edit();
              upload.click();
            } else {
              this.edit(() => upload.click());
            }
            break;
          case 'delete':
            this.edit(() => {
              H.openConfirmPopover({
                item: cell.querySelector('i.btn-menu'),
                placement: isCol ? 'left' : 'right',
                title: `<i class="fas fa-trash fa-fw"></i> <?=_("Delete")?>`,
                content: isCol ?
                    `<?=_("Delete this column?")?>` :
                    `<?=_("Delete this row?")?>`,
                onClose: () => this.unedit(),
                onConfirm: () => {
                  // Wait for the popover to be closed
                  setTimeout(() => {
                    if (isCol) {
                      wall.deleteCol(tag.cellIndex);
                    } else {
                      wall.deleteRow(tag.closest('tr.wpt').rowIndex - 1);
                    }
                  }, 250);
                },
              });
            });
            break;
          case 'rename':
            this.edit(() => {
              this.saveCurrentWidth();
              H.openConfirmPopover({
                type: 'update',
                scrollIntoView: isCol,
                item: li.parentNode.parentNode.querySelector('.btn-menu'),
                title: `<i class="fas fa-grip-lines${isCol ? '-vertical' : ''} fa-fw"></i> ${(isCol)?`<?=_("Column name")?>`:`<?=_("Row name")?>`}`,
                content: `<input type="text" class="form-control form-control-sm" value="${tag.querySelector('.title').innerText}" maxlength="<?=DbCache::getFieldLength('headers', 'title')?>">`,
                onClose: () => {
                  if (!S.get('no-unedit')) {
                    this.unedit();
                  }
                  S.unset('no-unedit');
                },
                onConfirm: (p) => {
                  S.set('no-unedit', true);
                  this.setTitle(p.querySelector('input').value, true);
                }
              });
            });
            break;
          case 'move-up':
          case 'move-down':
          case 'move-left':
          case 'move-right':
            this.moveColRow(action);
            break;
        }
      });

      tag.querySelectorAll('.title').forEach((el) => {
        P.create(el, 'editable', {
          wall: wall,
          container: tag,
          maxLength: <?=DbCache::getFieldLength('headers', 'title')?>,
          triggerTags: ['th', 'div'],
          fontSize: '14px',
          callbacks: {
            before: () => this.saveCurrentWidth(),
            edit: (cb) => {
              if (H.disabledEvent()) return false;
              this.edit(cb);
            },
            unedit: () => this.unedit(),
            update: (v) => this.setTitle(v, true),
          },
        })
      });
    }

    tag.prepend(menu);
  }

  // METHOD moveRow()
  moveColRow(move, noSynchro) {
    const tag = this.tag;
    const tr = tag.closest('tr.wpt');
    const wallTag = this.settings.wall.tag;
    const idx = tag.cellIndex - 1;

    switch (move) {
      case 'move-up':
        H.insertBefore(tr, tr.previousSibling);
        break;
      case 'move-down':
        H.insertAfter(tr, tr.nextSibling);
        break;
      case 'move-left':
        H.insertBefore(tag, tag.previousSibling);
        wallTag.querySelectorAll('tr.wpt').forEach((el) => {
          const td = el.querySelectorAll(`td.wpt`)[idx];
          if (td && td.previousSibling) {
            H.insertBefore(td, td.previousSibling);
          }
        });
        break;
      case 'move-right':
        H.insertAfter(tag, tag.nextSibling);
        wallTag.querySelectorAll('tr.wpt').forEach((el) => {
          const td = el.querySelectorAll(`td.wpt`)[idx];
          if (td && td.nextSibling) {
            H.insertAfter(td, td.nextSibling);
          }
        });
        break;
    }

    if (!noSynchro) {
      P.get(wallTag.querySelector('td.wpt'), 'cell').unedit(false, {
        move,
        headerId: this.settings.id,
      });
    }
  }

  // METHOD showUserWriting()
  showUserWriting(user) {
    const tag = this.tag;

    tag.prepend(H.createElement('div',
      {className: 'user-writing main'},
      {userid: user.id},
      `<i class="fas fa-user-edit blink"></i> ${user.name}`,
    ));
    tag.classList.add('locked');
  }

  // METHOD useFocusTrick()
  useFocusTrick() {
    return (
      this.settings.wall.isShared() &&
      H.haveMouse() &&
      !H.navigatorIsEdge()
    );
  }

  // METHOD saveCurrentWidth()
  saveCurrentWidth() {
    // Save current TH width
    this.settings.thwidth = this.tag.offsetWidth;
  }

  // METHOD addUploadLayer()
  addUploadLayer() {
    if (!this.useFocusTrick()) {
      const layer = document.getElementById('upload-layer');

      if (H.haveMouse()) {
        layer.addEventListener('mousedown',
          (e) => this.unedit(), {once: true});
      }

      if (H.haveTouch()) {
        layer.addEventListener('touchstart',
          (e) => this.unedit(), {once: true});
      }

      H.show(layer);
    }
  }

  // METHOD remove()
  remove() {
    P.remove(this.tag, 'header');
    this.tag.remove();
  }

  // METHOD removeUploadLayer()
  removeUploadLayer() {
    H.hide(document.getElementById('upload-layer'));
  }

  // METHOD getImgTemplate()
  getImgTemplate(src) {
    const adminAccess =
        H.checkAccess(<?=WPT_WRIGHTS_ADMIN?>, this.settings.access);
    const img = H.createElement('div',
      {className: 'img'},
      null,
      `<img src="${src}">`,
    );

    // EVENT "load" on header picture
    // Refresh postits plugs once picture has been fully loaded
    img.querySelector('img').addEventListener('load',
      (e) => this.settings.wall.repositionPostitsPlugs());

    if (!adminAccess) {
      return img;
    }
    
    // EVENT "click" on header picture
    img.addEventListener('click', (e) => {
      const upload = document.querySelector('.upload.header-picture');

      e.stopImmediatePropagation();

      if (this.settings.wall.isShared()) {
        //FIXME
        // we need this to cancel edit if no img is selected by user
        // (touch device version)
        this.addUploadLayer();
        this.edit();
        upload.click();
      } else {
        this.edit(() => upload.click());
      }
    });

    // Create img "delete" button
    const deleteBtn = H.createElement('button', {
      type: 'button',
      className: 'btn-close img-delete',
    });

    // EVENT "click" on header picture
    deleteBtn.addEventListener('click', (e) => {
      e.stopImmediatePropagation();

      this.edit(() => {
        H.openConfirmPopover({
          item: e.target,
          placement: 'left',
          title: `<i class="fas fa-trash fa-fw"></i> <?=_("Delete")?>`,
          content: `<?=_("Delete this picture?")?>`,
          onClose: () => {
            if (!S.get('unedit-done')) {
              this.unedit();
            } else {
              S.unset('unedit-done');
            }
          },
          onConfirm: () => {
            S.set('unedit-done', true);
            this.deleteImg();
          },
        });
      });
    });

    img.prepend(deleteBtn);

    return img;
  }

  // METHOD setImg()
  setImg(src) {
    const tag = this.tag;
    const img = tag.querySelector('.img img');

    this.settings.picture = src;

    if (src) {
      if (!img) {
        tag.appendChild(this.getImgTemplate(src));
      } else if (src !== img.getAttribute('src')) {
        img.setAttribute('src', src);
      }
    }  else if (img) {
      tag.querySelector('.img').remove();
    }
  }

  // METHOD deleteImg()
  deleteImg() {
    const tag = this.tag;

    H.request_ws(
      'DELETE',
      `wall/${this.settings.wallId}/header/${this.settings.id}/picture`,
      null,
      // success cb
      (d) => {
        if (d.error_msg) {
          H.raiseError(null, d.error_msg);
        } else {
          const oldW = tag.getBoundingClientRect().width;
          const img = tag.querySelector('.img');

          if (this.settings.item_type === 'col') {
            img.remove();
          } else {
            this.removeContentKeepingWallSize({
              oldW: oldW,
              cb: () => img.remove(),
            });
          }

          this.unedit();
        }
      });
  }

  // METHOD removeContentKeepingWallSize()
  removeContentKeepingWallSize(args) {
    const wall = this.settings.wall;
    const wallTag = wall.tag;
    const th1 = wallTag.querySelector('thead.wpt th.wpt');

    args.cb();

    wallTag.style.width = 'auto';
    th1.style.width = 0;

    wall.fixSize(args.oldW, th1.offsetWidth);
  }

  // METHOD update()
  update(header) {
    if (header.title !== this.settings.title) {
      this.setTitle(header.title);
    }

    if (header.picture !== this.settings.picture) {
      this.setImg(header.picture);
    }
  }

  // METHOD setTitle()
  setTitle(title, resize) {
    const tag = this.tag;

    title = H.noHTML(title);

    this.settings.title = title || '&nbsp;';

    tag.querySelector('.title').innerHTML = this.settings.title;

    if (resize) {
      const wall = this.settings.wall;
      const wallTag = wall.tag;
      const oldW = this.settings.thwidth;
      const isRow = (this.settings.item_type === 'row');

      if (isRow) {
        wallTag.style.width = 'auto';
        tag.style.width = 0;
      }

      H.waitForDOMUpdate(() => {
        const newW = tag.getBoundingClientRect().width;

        if (isRow || newW > oldW) {
          wall.fixSize(oldW, newW);

          if (!isRow) {
            wallTag.querySelectorAll('tbody.wpt tr.wpt').forEach((tr) => {
              const td = tr.querySelectorAll('td.wpt')[tag.cellIndex - 1];
              td.style.width = `${newW}px`;
              td.querySelector('.ui-resizable-s').style.width = `${newW + 2}px`;
            });
          }
        }
        else {
          wall.fixSize();
        }

        this.unedit();
      });
    }
  }

  // METHOD edit()
  edit(then, onError) {
    this.setCurrent();

    this.originalObject = this.serializeOne(this.tag);

    if (!this.settings.wall.isShared()) {
      then && then();
      return;
    }

    H.request_ws(
      'PUT',
      `wall/${this.settings.wallId}/editQueue/header/${this.settings.id}`,
      null,
      // success cb
      (d) => {
        // If header does not exists anymore (row/col has been deleted)
        if (d.error_msg) {
          H.raiseError(() => {
            onError && onError();
            this.cancelEdit();
          }, d.error_msg);
        } else if (then) {
          then(d);
        }
      },
      // error cb
      (d) => this.cancelEdit(),
    );
  }

  // METHOD setCurrent()
  setCurrent() {
    this.tag.classList.add('current');
  }

  // METHOD unsetCurrent()
  unsetCurrent() {
    S.reset('header');
    this.tag.classList.remove('current');
  }

  // METHOD cancelEdit()
  cancelEdit() {
    this.realEdit = false;
    this.unsetCurrent();
  }

  // METHOD serializeOne()
  serializeOne(th) {
    const img = th.querySelector('img');

    return {
      id: Number(th.dataset.id.substring(7)),
      width: Math.trunc(th.offsetWidth),
      height: Math.trunc(th.offsetHeight),
      title: th.querySelector('.title').innerText,
      picture: img ? img.getAttribute('src') : null,
    };
  }

  // METHOD serialize()
  serialize() {
    const wallTag = this.settings.wall.tag;
    const headers = {cols: [], rows: []};

    wallTag.querySelectorAll('thead.wpt th.wpt').forEach((th) => {
      if (th.cellIndex > 0) {
        headers.cols.push(this.serializeOne(th));
      };
    });

    wallTag.querySelectorAll('tbody.wpt th.wpt').forEach(
      (th) => headers.rows.push(this.serializeOne(th)));

    return headers;
  }

  // METHOD unedit()
  unedit(args = {}) {
    const wall = this.settings.wall;
    const wallTag = wall.tag;
    let data = null;

    this.removeUploadLayer();

    if (args.data) {
      const msg = args.data.error ?
        args.data.error : args.data.error.error_msg ?
          args.data.error_msg : null;

      if (msg) {
        H.displayMsg({msg, type: args.data.error ? 'danger' : 'warning'});
      }
    }

    // Update header only if it has changed
    if (H.objectHasChanged(this.originalObject, this.serializeOne(this.tag))) {
      const cells = wallTag.querySelectorAll('tbody.wpt td.wpt');
      const cell = P.get(cells[0], 'cell');
      data = {
        headers: this.serialize(),
        cells: cell.serialize({noPostits: true}),
        wall: {width: Math.trunc(wallTag.clientWidth)},
      };
      cell.reorganize(cells);
    } else if (!wall.isShared()) {
      return this.cancelEdit();
    }

    H.request_ws(
      'DELETE',
      `wall/${this.settings.wallId}/editQueue/header/${this.settings.id}`,
      data,
      // success cb
      (d) => this.cancelEdit(),
      // error cb
      () => this.cancelEdit(),
    );
  }
});

//////////////////////////////////// INIT ////////////////////////////////////

document.addEventListener('DOMContentLoaded', () => {
  if (H.isLoginPage()) return;

  // Create input to upload header image
  H.createUploadElement({
    attrs: {className: 'header-picture', accept: '.jpeg,.jpg,.gif,.png'},
    onChange: (e) => {
      const el = e.target;

      if (!el.files || !el.files.length) return;

      const header = S.getCurrent('header');
      const tag = header.tag;
      const settings = header.settings;

      this.realEdit = true;

      H.getUploadedFiles(e.target.files, '\.(jpe?g|gif|png)$', (e, file) => {
        el.value = '';

        if (H.checkUploadFileSize({size: e.total}) && e.target.result) {
          const oldW = tag.getBoundingClientRect().width;

          H.fetchUpload(
            `wall/${settings.wallId}/header/${settings.id}/picture`,
            {
              name: file.name,
              size: file.size,
              item_type: file.type,
              content: e.target.result,
            },
            // success cb
            (d) => {
              if (d.error_msg) {
                return header.unedit({data: d});
              }

              header.setImg(d.img);
              setTimeout(() => {
                settings.wall.fixSize(oldW, tag.getBoundingClientRect().width);
                header.unedit();
              }, 500);
            },
            // error cb
            (d) => header.unedit({data: d}));
          }
        },
        // error cb
        () => header.unedit());
    },
    onClick: (e) => {
      const header = S.getCurrent('header');

      //FIXME
      // we need this to cancel edit if no img is selected by user
      // (desktop version)
      if (header.useFocusTrick()) {
        window.addEventListener('focus',
          () => !header.realEdit && header.unedit(), {once: true});
      }
    },
  });
});

})();
