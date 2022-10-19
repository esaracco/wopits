<?php
/**
  Javascript plugin - Wall's header

  Scope: Wall
  Element: th
  Description: Wall's headers
*/

  require_once(__DIR__.'/../prepend.php');

  use Wopits\DbCache;

  $Plugin = new Wopits\jQueryPlugin('header', '', 'wallElement');
  echo $Plugin->getHeader();

?>

  let _realEdit = false,
      _originalObject;

  /////////////////////////// PRIVATE METHODS ///////////////////////////

  // METHOD serializeOne()
  const _serializeOne = (th) => {
      const img = th.querySelector('img');

      return {
        id: th.dataset.id.substring(7),
        width: Math.trunc(th.offsetWidth),
        height: Math.trunc(th.offsetHeight),
        title: th.querySelector('.title').innerText,
        picture: img ? img.getAttribute('src') : null,
      };
    };

  /////////////////////////// PUBLIC METHODS ////////////////////////////

  Plugin.prototype =
  {
    // METHOD init()
    init(args) {
      const header = this.element[0];
      const settings = this.settings;

      settings._timeoutEditing = 0;
      header.dataset.id = `header-${settings.id}`;

      this.appendTitle();
      this.appendMenu();

      if (settings.picture) {
        header.appendChild(this.getImgTemplate(settings.picture));
      }
    },

    // METHOD appendTitle()
    appendTitle() {
      const title = this.settings.title;

      this.element[0].appendChild(
        H.createElement('div', 
          {className: 'title'},
          null,
          (title !== ' ') ? title : '&nbsp;',
      ));
    },

    // METHOD appendMenu()
    appendMenu() {
      const $header = this.element;
      const header = $header[0];
      const settings = this.settings;
      const $wall = settings.wall;
      const isCol = (settings.item_type === 'col');
      const adminAccess =
          H.checkAccess(`<?=WPT_WRIGHTS_ADMIN?>`, settings.access);
      
      const menu = H.createElement('ul',
        {className: 'navbar-nav mr-auto submenu'},
        null,
        adminAccess ? `<li class="nav-item dropdown"><div data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" class="nav-link dropdown-toggle"><i class="far fa-caret-square-right btn-menu" data-placement="right"></i></div><ul class="dropdown-menu shadow"><li data-action="rename"><a class="dropdown-item" href="#"><i class="fa-fw fas fa-edit"></i> <?=_("Rename")?></a></li><li data-action="add-picture"><a class="dropdown-item" href="#"><i class="fa-fw fas fa-camera-retro"></i> <?=_("Associate a picture")?></a></li>${isCol?`<li data-action="move-left"><a class="dropdown-item" href="#"><i class="fa-fw fas fa-chevron-left"></i> <?=_("Move left")?></a></li><li data-action="move-right"><a class="dropdown-item" href="#"><i class="fa-fw fas fa-chevron-right"></i> <?=_("Move right")?></a></li>`:`<li data-action="move-up"><a class="dropdown-item" href="#"><i class="fa-fw fas fa-chevron-up"></i> <?=_("Move up")?></a></li><li data-action="move-down"><a class="dropdown-item" href="#"><i class="fa-fw fas fa-chevron-down"></i> <?=_("Move down")?></a></li>`}</li><li data-action="delete"><a class="dropdown-item" href="#"><i class="fa-fw fas fa-trash"></i> <?=_("Delete")?> <span></span></a></li></ul></li>` : null,
      );

      if (adminAccess) {
        // EVENT "click" on header menu button
        // To prevent header title editing
/*FIXME
        menu.querySelector('.dropdown-toggle')
            .addEventListener('click', (e) => e.stopPropagation());
*/

        // EVENT "show.bs.dropdown" on header menu
        menu.addEventListener('show.bs.dropdown', (e) => {
          const el = e.target;
          const $menu = $(el.closest("ul"));
          const menu = $menu[0];
          const wall = $wall[0];
          const tr = $header.closest("tr.wpt")[0];
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
          menu.querySelectorAll('a').forEach((el) =>
              el.style.display = 'block');

          // If column menu
          if (isCol) {
            const thIdx = header.cellIndex;

            if (wall.querySelectorAll('thead.wpt th.wpt').length <= 2) {
              deleteItem.style.display = 'none';
            }

            if (thIdx === 1) {
              moveLeftItem.style.display = 'none';
            }

            if (thIdx == tr.querySelectorAll('th.wpt').length - 1) {
              moveRightItem.style.display = "none";
            }
          // If row menu
          } else {
            const trIdx = tr.rowIndex - 1;

            if (wall.querySelectorAll('tbody.wpt th.wpt').length === 1) {
              deleteItem.style.display = 'none';
            }

            if (trIdx === 0) {
              moveUpItem.style.display = 'none';
            }

            if (trIdx === wall.querySelectorAll('tr.wpt').length - 2) {
              moveDownItem.style.display = 'none';
            }
          }

          if (isCol && wall.dataset.cols == '1') {
            moveLeftItem.style.display = 'none';
            moveRightItem.style.display = 'none';
          }

          if (!isCol && wall.dataset.rows == '1') {
            moveUpItem.style.display = 'none';
            moveDownItem.style.display = 'none';
          }
        });

        // EVENT "hide.bs.dropdown" on header menu
        menu.addEventListener('hide.bs.dropdown', (e) => 
            e.target.querySelector('.nav-link i.fas')
              .classList.replace('fas', 'far'));

        // EVENT "click" on header menu items
        menu.querySelector('.dropdown-menu').addEventListener('click', (e) => {
          const el = e.target;
          const li = el.closest('li');
          const $cell = $(li.closest('th.wpt'));
          const action = li.dataset.action;

          switch (action) {
            case 'add-picture':
              const upload = document.querySelector('.upload.header-picture');
              if (settings.wall.wall('isShared')) {
                // we need this to cancel edit if no img is selected by user
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
                  item: $cell.find('i.btn-menu'),
                  placement: isCol ? 'left' : 'right',
                  title: `<i class="fas fa-trash fa-fw"></i> <?=_("Delete")?>`,
                  content: isCol ?
                      `<?=_("Delete this column?")?>` :
                      `<?=_("Delete this row?")?>`,
                  cb_close: () => this.unedit(),
                  cb_ok: () => {
                    if (isCol) {
                      $wall.wall('deleteCol', header.cellIndex);
                    } else {
                      $wall.wall('deleteRow',
                          header.closest('tr.wpt').rowIndex - 1);
                    }
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
                  item: $(li.parentNode.parentNode
                            .querySelector(".btn-menu")),
                  title: `<i class="fas fa-grip-lines${isCol?"-vertical":""} fa-fw"></i> ${(isCol)?`<?=_("Column name")?>`:`<?=_("Row name")?>`}`,
                  content: `<input type="text" class="form-control form-control-sm" value="${$header.find(".title").text()}" maxlength="<?=DbCache::getFieldLength('headers', 'title')?>">`,
                  cb_close: () => {
                    if (!S.get('no-unedit')) {
                      this.unedit();
                    }
                    S.unset('no-unedit');
                  },
                  cb_ok: ($p) => {
                    S.set('no-unedit', true);
                    this.setTitle ($p.find('input').val(), true);
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

        $header.find('.title').editable({
          wall: $wall,
          container: $header,
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
        });
      }

      header.prepend(menu);
    },

    // METHOD moveRow()
    moveColRow (move, noSynchro) {
      const th = this.element[0];
      const tr = th.closest('tr.wpt');
      const wall = this.settings.wall[0];
      const idx = th.cellIndex - 1;

      switch (move) {
        case 'move-up':
          H.insertBefore(tr, tr.previousSibling);
          break;
        case 'move-down':
          H.insertAfter(tr, tr.nextSibling);
          break;
        case 'move-left':
          H.insertBefore(th, th.previousSibling);
          wall.querySelectorAll('tr.wpt').forEach((el) => {
            const td = el.querySelectorAll(`td.wpt`)[idx];
            if (td && td.previousSibling) {
              H.insertBefore(td, td.previousSibling);
            }
          });
          break;
        case 'move-right':
          H.insertAfter(th, th.nextSibling);
          wall.querySelectorAll('tr.wpt').forEach((el) => {
            const td = el.querySelectorAll(`td.wpt`)[idx];
            if (td && td.nextSibling) {
              H.insertAfter(td, td.nextSibling);
            }
          });
          break;
      }

      if (!noSynchro) {
        $(wall.querySelector('td.wpt')).cell('unedit', false, {
          move,
          headerId: this.settings.id,
        });
      }
    },

    // METHOD showUserWriting()
    showUserWriting(user) {
      const header = this.element[0];

      header.prepend(H.createElement('div',
        {className: 'user-writing main'},
        {userid: user.id},
        `<i class="fas fa-user-edit blink"></i> ${user.name}`,
      ));
      header.classList.add('locked');
    },

    // METHOD useFocusTrick()
    useFocusTrick () {
      return (
        this.settings.wall.wall('isShared') &&
        H.haveMouse() &&
        !H.navigatorIsEdge()
      );
    },

    // METHOD saveCurrentWidth()
    saveCurrentWidth() {
      // Save current TH width
      this.settings.thwidth = this.element[0].offsetWidth;
    },

    // METHOD addUploadLayer()
    addUploadLayer() {
      if (!this.useFocusTrick()) {
        const layer = document.getElementById('upload-layer');

        ['mousedown', 'touchstart'].forEach((type) =>
          layer.addEventListener(type, (e) => this.unedit(), {once: true}));

        layer.style.display = 'block';
      }
    },

    // METHOD removeUploadLayer()
    removeUploadLayer() {
      document.getElementById('upload-layer').style.display = 'none';
    },

    // METHOD getImgTemplate()
    getImgTemplate(src) {
      const $header = this.element;
      const adminAccess =
          H.checkAccess(`<?=WPT_WRIGHTS_ADMIN?>`, this.settings.access);
      const img = H.createElement('div',
        {className: 'img'},
        null,
        `<img src="${src}">`,
      );

      // EVENT "load" on header picture
      // Refresh postits plugs once picture has been fully loaded
      img.querySelector("img").addEventListener("load",
        (e)=> this.settings.wall.wall ("repositionPostitsPlugs"));

      if (!adminAccess) {
        return img;
      }
      
      // EVENT "click" on header picture
      img.addEventListener('click', (e) => {
        const upload = document.querySelector('.upload.header-picture');

        e.stopImmediatePropagation ();

        if (this.settings.wall.wall('isShared')) {
          //FIXME
          // we need this to cancel edit if no img is selected by user
          // (touch device version)
          this.addUploadLayer();
          this.edit();
          upload.click ();
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
          H.openConfirmPopover ({
            item: $(e.target),
            placement: 'left',
            title: `<i class="fas fa-trash fa-fw"></i> <?=_("Delete")?>`,
            content: `<?=_("Delete this picture?")?>`,
            cb_close: () => {
              if (!S.get('unedit-done')) {
                this.unedit();
              } else {
                S.unset('unedit-done');
              }
            },
            cb_ok: () => {
              S.set('unedit-done', true);
              this.deleteImg();
            },
          });
        });
      });

      img.prepend (deleteBtn);

      return img;
    },

    // METHOD setImg()
    setImg(src) {
      const header = this.element[0];
      const img = header.querySelector('.img img');

      this.settings.picture = src;

      if (src) {
        if (!img) {
          header.appendChild(this.getImgTemplate(src));
        } else if (src !== img.getAttribute('src')) {
          img.setAttribute('src', src);
        }
      }  else if (img) {
        header.querySelector('.img').remove();
      }
    },

    // METHOD deleteImg()
    deleteImg() {
      H.request_ws(
        'DELETE',
        `wall/${this.settings.wallId}/header/${this.settings.id}/picture`,
        null,
        // success cb
        (d) => {
          if (d.error_msg) {
            H.raiseError (null, d.error_msg);
          } else {
            const header = this.element[0];
            const oldW = header.getBoundingClientRect().width;
            const img = header.querySelector('.img');

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
    },

    // METHOD removeContentKeepingWallSize()
    removeContentKeepingWallSize(args) {
      const $wall = this.settings.wall;
      const th1 = $wall[0].querySelector('thead.wpt th.wpt');

      args.cb();

      $wall[0].style.width = 'auto';
      th1.style.width = 0;

      $wall.wall('fixSize', args.oldW, th1.offsetWidth);
    },

    // METHOD update()
    update(header) {
      if (header.title !== this.settings.title) {
        this.setTitle(header.title);
      }

      if (header.picture !== this.settings.picture) {
        this.setImg(header.picture);
      }
    },

    // METHOD setTitle()
    setTitle(title, resize) {
      const header = this.element[0];

      title = H.noHTML(title);

      this.settings.title = title || '&nbsp;'

      header.querySelector('.title').innerHTML = this.settings.title;

      if (resize) {
        const $wall = this.settings.wall;
        const oldW = this.settings.thwidth;
        const isRow = (this.settings.item_type == 'row');

        if (isRow) {
          $wall[0].style.width = 'auto';
          header.style.width = 0;
        }

        H.waitForDOMUpdate(() => {
          const newW = header.getBoundingClientRect().width;

          if (isRow || newW > oldW) {
            $wall.wall('fixSize', oldW, newW);

            if (!isRow) {
              $wall.find('tbody.wpt tr.wpt')
                  .find(`td.wpt:eq(${(header.cellIndex-1)})`).each(function() {
                this.style.width = `${newW}px`;
                this.querySelector('.ui-resizable-s')
                    .style.width = `${newW+2}px`;
              });
            }
          }
          else {
            $wall.wall('fixSize');
          }

          this.unedit();
        });
      }
    },

    // METHOD edit()
    edit(success_cb, error_cb) {
      this.setCurrent();

      _originalObject = _serializeOne(this.element[0]);

      if (!this.settings.wall.wall('isShared')) {
        if (success_cb) {
          success_cb();
        }
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
              if (error_cb) {
                error_cb();
              }
              this.cancelEdit();
            }, d.error_msg);
          } else if (success_cb) {
            success_cb(d);
          }
        },
        // error cb
        (d) => this.cancelEdit(),
      );
    },

    // METHOD setCurrent()
    setCurrent() {
      this.element[0].classList.add('current');
    },

    // METHOD unsetCurrent()
    unsetCurrent() {
      S.reset('header');
      this.element[0].classList.remove('current');
    },

    // METHOD cancelEdit()
    cancelEdit(bubble_event_cb) {
      const header = this.element[0];

      _realEdit = false;

      this.unsetCurrent();

      if (bubble_event_cb) {
        header.classList.add('_current')
        bubble_event_cb();
        header.classList.remove('_current')
      }
    },

    // METHOD serialize()
    serialize() {
      const wall = this.settings.wall[0];
      const headers = {cols: [], rows: []};

      wall.querySelectorAll('thead.wpt th.wpt').forEach((th) => {
        if (th.cellIndex > 0) {
          headers.cols.push(_serializeOne(th));
        };
      });

      wall.querySelectorAll('tbody.wpt th.wpt').forEach((th) =>
          headers.rows.push(_serializeOne(th)));

      return headers;
    },

    // METHOD unedit()
    unedit(args = {}) {
      const $wall = this.settings.wall;
      let data = null;

      this.removeUploadLayer();

      if (args.data) {
        const msg = args.data.error ?
          args.data.error : args.data.error.error_msg ?
            args.data.error_msg : null;

        if (msg) {
          H.displayMsg ({
            title: `<?=_("Wall")?>`,
            type: args.data.error ? 'danger' : 'warning',
            msg: msg,
          });
        }
      }

      // Update header only if it has changed
      if (H.updatedObject(_originalObject, _serializeOne(this.element[0]))) {
        data = {
          headers: this.serialize(),
          cells: $('<div/>').cell('serialize', {noPostits: true}),
          wall: {width: Math.trunc($wall[0].clientWidth)},
        };
        $wall.find('tbody.wpt td.wpt').cell('reorganize');
      } else if (!$wall.wall('isShared')) {
        return this.cancelEdit(args.bubble_cb);
      }

      H.request_ws(
        'DELETE',
        `wall/${this.settings.wallId}/editQueue/header/${this.settings.id}`,
        data,
        // success cb
        (d) => this.cancelEdit(args.bubble_cb),
        // error cb
        () => this.cancelEdit(args.bubble_cb),
      );
    }
  };

  /////////////////////////// AT LOAD INIT //////////////////////////////

  document.addEventListener('DOMContentLoaded', () => {
    if (H.isLoginPage()) return;

    // Create input to upload header image
    H.createUploadElement({
      attrs: {className: 'header-picture', accept: '.jpeg,.jpg,.gif,.png'},
      onChange: (e) => {
        const el = e.target;

        if (!el.files || !el.files.length) return;

        const $header = S.getCurrent('header');
        const settings = $header.header('getSettings');

        _realEdit = true;

        H.getUploadedFiles(e.target.files, '\.(jpe?g|gif|png)$', (e, file) => {
          el.value = '';

          if (H.checkUploadFileSize({size: e.total}) && e.target.result) {
            const oldW = $header.outerWidth();

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
                  return $header.header('unedit', {data: d});
                }

                $header.header('setImg', d.img);
                setTimeout(() => {
                  settings.wall.wall('fixSize', oldW, $header.outerWidth());
                  $header.header('unedit');
                }, 500);
              },
              // error cb
              (d) => $header.header('unedit', {data: d}));
            }
          },
          // error cb
          () => $header.header('unedit'));
      },
      onClick: (e) => {
        const $header = S.getCurrent('header');

        //FIXME
        // we need this to cancel edit if no img is selected by user
        // (desktop version)
        if ($header.header('useFocusTrick')) {
          window.addEventListener('focus',
              (e) => !_realEdit && $header.header('unedit'), {once: true});
        }
      },
    });
  });

<?php echo $Plugin->getFooter()?>
