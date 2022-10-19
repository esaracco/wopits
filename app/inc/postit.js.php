<?php
/**
  Javascript plugin - Note

  Scope: Wall
  Elements: .postit
  Description: Note management

  TODO: relations plugin
*/

  require_once(__DIR__.'/../prepend.php');

  use Wopits\DbCache;

  $Plugin = new Wopits\jQueryPlugin('postit', '', 'wallElement');
  echo $Plugin->getHeader();

?>

  let _originalObject;
  const _defaultClassColor = `color-<?=WPT_POSTIT_COLOR_DEFAULT?>`;
  const _plugRabbit = {
    line: null,
    // EVENT mousedown on destination postit for relation creation
    mousedownEvent: (e) => {
      const $end = $(e.target.closest('.postit'));

      e.stopImmediatePropagation();
      H.preventDefault(e);

      if (!$end.length) return _cancelPlugAction();

      const from = S.get('link-from');
      const $start = from.obj;
      const end = $end[0];
      const endPlugin = $end.postit('getClass');
      const endId = endPlugin.settings.id;

      if (from.id !== endId && !endPlugin.plugExists(from.id)) {
        endPlugin.edit({plugend: true}, () => {
          const start = $start[0];

          $start.postit('addPlug', {
            endId,
            startId: from.id,
            label: {name: '...'},
            obj: endPlugin.getPlugTemplate({
              start,
              end,
              hide: true,
              label: '...',
            }),
          }, Boolean(S.get('zoom-level')));

          _cancelPlugAction();
        });
      } else if (from.id !== endId) {
        _cancelPlugAction();

        H.displayMsg({
          title: `<?=_("Note")?>`,
          type: 'warning',
          msg: `<?=_("The relation already exists")?>`,
        });
      } else {
        _cancelPlugAction();
      }
    },
    // EVENT mousemouve to track mouse pointer during relation creation
    mousemoveEvent: (e) => {
      const rabbit = document.getElementById('plug-rabbit');

      rabbit.style.left = `${e.clientX + 5}px`;
      rabbit.style.top = `${e.clientY - 10}px`;

      _plugRabbit.line.position();
    },
    escapeEvent: (e) => {
      if (e.which === 27) {
        _cancelPlugAction();
      }
    },
  };

  /////////////////////////// PRIVATE METHODS ///////////////////////////

  // EVENT focusin
  // To fix tinymce dialogs compatibility with bootstrap popups
  const _focusinInFilter = (e) =>
      e.target.closest('.tox-dialog,.tox-tiered-menu') &&
        e.stopImmediatePropagation();

  // METHOD _getMaxEditModalWidth ()
  const _getMaxEditModalWidth = (content) => {
    let maxW = 0;
    let tmp;

    (content.match(/<[a-z]+\s[^>]+>/g) || []).forEach((tag) => {
      if ( (tmp = tag.match (/width\s*[=:]\s*"?(\d+)"?/)) ) {
        const w = Number(tmp[1]);

        if (w > maxW) {
          maxW = w;
        }
      }
    });

    return maxW ? maxW + 5 : 0;
  };

  // METHOD _deleteRelatedPlugs ()
  const _deleteRelatedPlugs = (plug) => {
    plug.related.forEach ((r) => r.remove());
    plug.related = [];
    plug.customPos = false;
  };

  // METHOD _removePlug()
  const _removePlug = (plug, toDefrag) => {
    toDefrag[plug.startId] = plug.obj.start;
    toDefrag[plug.endId] = plug.obj.end;

    // Remove label
    plug.labelObj.remove();
    plug.labelObj = null;

    // Remove related lines
    if (plug.customPos) {
      _deleteRelatedPlugs(plug);
    }

    plug.obj.remove();
    plug.obj = null;
  };

  // METHOD _cancelPlugAction()
  const _cancelPlugAction = () => {
    if (_plugRabbit.line) {
      document.removeEventListener('keydown', _plugRabbit.escapeEvent);
      document.removeEventListener('mousedown', _plugRabbit.mousedownEvent);
      document.removeEventListener('mousemove', _plugRabbit.mousemoveEvent);

      _plugRabbit.line.remove();
      _plugRabbit.line = null;

      document.getElementById('plug-rabbit').remove();
    }

    // Unedit postit
    S.get('link-from').obj.postit ('unedit');

    // Prevents post-it editing events from being triggered during 500ms
    // Sort of preventDefault() cross-type events
    S.set ('link-from', true, 500);
  };

  // METHOD _displayOpenLinkMenu()
  const _displayOpenLinkMenu = (e, args) => {
    const el = e.target;
    const link = (el.tagName === 'A') ? el : el.closest ('a');
    const canWrite = H.checkAccess(`<?=WPT_WRIGHTS_RW?>`);
    const menu = H.createElement('div',
      {className: 'dropdown submenu submenu-link'}, null,
      `<ul class="dropdown-menu shadow show"><li data-action="open-link"><a class="dropdown-item" href="#"><i class="fa-fw fas fa-link"></i> <?=_("Open link")?></a></li>${args?.noEditItem ? '' : `<li data-action="edit"><a class="dropdown-item" href="#"><i class="fa-fw fas fa-${canWrite ? 'edit' : 'eye'}"></i> ${canWrite ? `<?=_("Edit note")?>` : `<?=_("Open note")?>`}</a></li>`}</ul>`);
  
    H.preventDefault(e);
  
    // EVENT "click" on content links menu
    menu.addEventListener('click', (e) => {
      const li = (e.target.tagName === 'LI') ?
        e.target : e.target.closest('li');
  
      document.getElementById('popup-layer').click();
  
      if (li.dataset.action === 'open-link') {
        window.open(link.href, '_blank', 'noopener');
      } else {
        $(el.closest('.postit')).postit('openPostit');
      }
    });
  
    H.openPopupLayer(() => menu.remove());

    document.body.appendChild(menu);

    menu.style.top = `${e.clientY}px`;
    menu.style.left = `${e.clientX}px`;
  };

  // CLASS _Menu
  const _$menuTemplate = $(`<?=Wopits\Helper::buildPostitMenu ()?>`);
  class _Menu {
    // METHOD constructor()
    constructor(postitPlugin) {
      const $currentMenu = postitPlugin.settings.wall.find('.postit-menu');
      const postit = postitPlugin.element[0];

      if ($currentMenu.length) {
        $currentMenu.parent().postit('closeMenu');
      }

      this.header = postit.querySelector('.postit-header');
      this.btn = postit.querySelector('.btn-menu i');
      this.postitPlugin = postitPlugin;
      this.$menu = _$menuTemplate;

      this.init();
      postit.insertBefore(this.$menu[0], postit.firstChild);
    }

    // METHOD init()
    init() {
      const pSettings = this.postitPlugin.settings;

      if (!pSettings.wall.wall('isShared') || 
          (!this.postitPlugin.canWrite() && !pSettings.attachmentscount)) {
        this.$menu[0].querySelector(`[data-action="pwork"]`)
            .style.display = 'none';
      }
    }

    // METHOD show()
    show() {
      const postit = this.postitPlugin.element[0];
      const coord = this.header.getBoundingClientRect();

      if ((coord.x || coord.left) + this.getWidth() > window.outerWidth) {
        this.btn.classList.replace(
            'fa-caret-square-down', 'fa-caret-square-left');
        this.setPosition('left');
      } else {
        this.setPosition('right');
      }
  
      this.header.classList.add('menu');
      this.btn.classList.replace('far', 'fas');

      // FIXME z-index issue
      // H.openPopupLayer(()=> this.btn.click());
      this.$menu.show();
    }

    // METHOD destroy()
    destroy() {
      this.header.classList.remove('menu');
      this.btn.classList.replace(
          'fa-caret-square-left', 'fa-caret-square-down');
      this.btn.classList.replace('fas', 'far');
      this.$menu.remove();
    }

    // METHOD setPosition()
    setPosition(pos) {
      const menuCls = this.$menu[0].classList;
      if (pos === 'left') {
        menuCls.replace('right', 'left');
      } else {
        menuCls.replace('left', 'right');
      }
    }

    // METHOD getWidth()
    getWidth() {
      return this.$menu.width();
    }
  }

  /////////////////////////// PUBLIC METHODS ////////////////////////////

  Plugin.prototype = {
    // METHOD init()
    init(args) {
      const plugin = this;
      const $postit = plugin.element;
      const postit = $postit[0];
      const settings = plugin.settings;
      const $wall = settings.wall;
      const writeAccess = plugin.canWrite();

      settings.plugs = [];
      settings.plugins = [];
      postit.dataset.id = `postit-${settings.id}`;
      postit.dataset.order = settings.item_order;
      postit.className = settings.classes || 'postit';
      postit.dataset.tags = settings.tags || '';

      // if the deadline has passed
      if (settings.obsolete) {
        postit.classList.add('obsolete');
      }

      postit.style.visibility = 'hidden';
      postit.style.top = `${settings.item_top}px`;
      postit.style.left = `${settings.item_left}px`;

      // Append if the user have write access
      if (writeAccess) {
        postit.appendChild(H.createElement('div',
          {className: 'btn-menu'}, null,
          `<i class="far fa-caret-square-down"></i>`));
      }
      // Append header, dates, attachment count and tags
      postit.append(
        // Header
        H.createElement('div', {className: 'postit-header'}, null,
          `<span class="title">...</span>`),
        // Progress bar
        H.createElement('div', {className: 'postit-progress-container'}, null,
          `<div><span></span></div><div class="postit-progress"></div>`),
        // Edit
        H.createElement('div', {className: 'postit-edit'}),
        // Dates (creation and deadline)
        H.createElement('div', {className: 'dates'}, null,
          `<div class="creation" title="<?=_("Creation date")?>"><span>${moment.tz(wpt_userData.settings.timezone).format("Y-MM-DD")}</span></div><div class="end" title="<?=_("Deadline")?>"><i class="fas fa-times-circle fa-lg"></i> <span>...</span></div>`),
        // Top icons
        H.createElement('div', {className: 'topicon'}, null,
          `<div class="pwork" title="<?=_("Users involved")?>"></div><div class="pcomm" title="<?=_("Comments")?>"></div><div class="patt" title="<?=_("Attached files")?>"></div>`),
        // Tags
        H.createElement('div', {className: 'postit-tags'}, null,
          `${settings.tags ? S.getCurrent('tpick').tpick("getHTMLFromString", settings.tags) : ''}`)
      );

      if (writeAccess)
      {
        const postitEdit = postit.querySelector('.postit-edit');

        $postit  
          // DRAGGABLE postit
          .draggable ({
            distance: 10,
            appendTo: 'parent',
            revert: 'invalid',
            cursor: 'pointer',
            cancel: '.postit-tags',
            containment: $wall.find('tbody.wpt'),
            scrollSensitivity: 50,
            opacity: .35,
            scope: 'dzone',
            stack: '.postit',
            drag: function(e, ui) {
              if (S.get('revertData').revert) {
                $(this).draggable('cancel');
                return false;
              }
  
              // Refresh relations position
              // plugin.repositionPlugs ();
            },
            start: function(e, ui) {
              S.set('revertData', {
                revert: false,
                top: postit.offsetTop,
                left: postit.offsetLeft,
              });
  
              this.hideSHowPlugs =
                !S.getCurrent('filters')[0].classList.contains('plugs-hidden');
  
              if (this.hideSHowPlugs) {
                plugin.hidePlugs();
              }
  
              plugin.edit ({ignoreResize: true}, null,
                () => S.get('revertData').revert = true);
              },
            stop: function(e, ui) {
              if (this.hideSHowPlugs) {
                plugin.showPlugs();
              }
              delete this.hideSHowPlugs;
              plugin.dropStop();
            }
          })
          // RESIZABLE post-it
          .resizable({
            handles: H.haveMouse() ? 'all' : 'n, e, w, ne, se, sw, nw',
            autoHide: false,
            resize: function(e, ui) {
              // Refresh relations position
              plugin.repositionPlugs();
  
              plugin.fixEditHeight();
  
              if (S.get('revertData').revert) {
                return false;
              }
            },
            start: function(e, ui) {
              const editable = $wall[0].querySelectorAll('.editable');
  
              // Cancel all editable
              // (because blur event is not triggered on resizing)
              if (editable.length) {
                editable.forEach((el) => $(el).editable('cancel'));
              }
    
              S.set('revertData', {
                revert: false,
                width: postit.clientWidth,
                height: postit.clientHeight,
              });
  
              plugin.hidePlugs();
  
              plugin.edit ({ignoreResize: true}, null,
                () => S.get('revertData').revert = true);
            },
            stop: function(e, ui) {
              const revertData = S.get('revertData');

              S.set('dragging', true, 500);

              plugin.showPlugs();

              if (revertData.revert) {
                postit.style.width = `${revertData.width}px`;
                postit.style.height = `${revertData.height}px`;

                plugin.cancelEdit();

                // Refresh relations position
                plugin.repositionPlugs();
              }
              else {
                H.waitForDOMUpdate (() => {
                  ui.element.parent().cell('reorganize');
                  plugin.unedit();
                });
              }
            }
          });

        $(postitEdit)
          // EVENT doubletap on content
          .doubletap((e)=> {
            if (e.target.tagName === 'A' || H.disabledEvent(e.ctrlKey)) {
              return false;
            }

            plugin.openPostit();
          });
  
        // Make postit title editable
        $postit.find('.title').editable({
          wall: $wall,
          container: $postit.find('.postit-header'),
          maxLength: <?=DbCache::getFieldLength('postits', 'title')?>,
          triggerTags: ['span', 'div'],
          fontSize: '14px',
          callbacks: {
            before: (ed, v) => v === '...' && ed.setValue(''),
            edit: (cb) => {
              if (H.disabledEvent()) return false;

              plugin.edit({}, cb);
            },
            unedit: () => plugin.unedit(),
            update: (v) => {
              plugin.setTitle(v);
              plugin.unedit();
            },
          }
        });
      }

      // Initialize topicons plugins
      const _args = {
        postitPlugin: this,
        readonly: !writeAccess,
        shared: $wall.wall('isShared'),
      };
      // Attachments
      _args.count = settings.attachmentscount;
      settings.plugins.patt = $postit.find('.patt').patt(_args);
      // Workers
      _args.count = settings.workerscount;
      settings.plugins.pwork = $postit.find('.pwork').pwork(_args);
      // Comments
      _args.count = settings.commentscount;
      settings.plugins.pcomm = $postit.find('.pcomm').pcomm(_args);

      // If we are updating a note
      if (settings.creationdate) {
        plugin.update(settings);
      }
    },

    // METHOD getPlugin()
    getPlugin(type) {
      return this.settings.plugins[type];
    },

    // METHOD dropStop()
    dropStop() {
      if (S.get('dragging')) return;

      const plugin = this;
      const $postit = plugin.element;
      const postitEdit = $postit[0].querySelector('.postit-edit');
      const $editable = $postit.find('.editable');

      // Cancel editable.
      if ($editable.length) {
        $editable.editable('cancel');
      }

      S.set('dragging', true, 500);

      if (S.get('revertData').revert) {
        plugin.setPosition(S.get('revertData'));
        plugin.cancelEdit ();
      } else {
        const content = postitEdit.innerHTML;

        // If the postit has been dropped into another cell
        plugin.settings.cell = $postit.parent();

        // Update content cells references if any
        if (content.indexOf('/cell/') !== -1) {
          postitEdit.innerHTML =
              content.replace(/\/cell\/\d+\//g,
                              `/cell/${plugin.settings.cellId}/`);
        }

        S.getCurrent('mmenu').mmenu('update', plugin.settings.id, plugin);

        plugin.unedit();
      }

      // Refresh relations position
      plugin.repositionPlugs();
    },

    // METHOD openPlugProperties()
    openPlugProperties(plug) {
      this.edit({}, () => H.loadPopup('plugprop', {
        open: false,
        cb: ($p) => $p.plugprop('open', this, plug),
      }));
    },

    // METHOD openDatePicker()
    openDatePicker() {
      this.edit({}, () => H.loadPopup('dpick', {
        open: false,
        cb: ($p) => $p.dpick('open'),
      }));
    },

    // METHOD openPostit()
    openPostit(item) {
      // Open modal with read rights only
      if (!this.canWrite()) {
        if (!this.openAskForExternalRefPopup({item})) {
          this.open ();
        }
      } else {
        this.edit({}, () => {
          if (!this.openAskForExternalRefPopup({
                 item, cb_close: (btn) => (btn !== 'yes') && this.unedit()})) {
            this.open();
          }
        });
      }
    },

    // METHOD open()
    open() {
      const postit = this.element[0];
      const progress = Number(postit.dataset.progress || 0);
      const title = this.getTitle();
      const content = postit.querySelector('.postit-edit').innerHTML || '';

      if (this.canWrite()) {
        const $popup = $('#postitUpdatePopup');

        S.set('postit-data', {
          progress,
          title: (title !== '...') ? title.replace(/&amp;/g, '&') : '',
        });

        // Set progress slider
        $popup.find('.slider').slider('value', progress, true);

        // Set title
        document.getElementById('postitUpdatePopupTitle').value =
          S.get('postit-data').title;

        //FIXME
        $('.tox-toolbar__overflow').show();
        $('.tox-mbtn--active').removeClass('tox-mbtn--active');

        // Check if post-it content has pictures
        if (content.match(/\/postit\/\d+\/picture\/\d+/)) {
          postit.dataset.hadpictures = true;
        } else {
          postit.removeAttribute('data-hadpictures');
        }

        // Filter the focusin event
        document.addEventListener('focusin', _focusinInFilter);

        tinymce.activeEditor.setContent(content);

        H.openModal({
          item: document.getElementById('postitUpdatePopup'),
          width: _getMaxEditModalWidth(content),
        });
      } else {
        this.setCurrent();

        H.loadPopup('postitView', {
          open: false,
          cb: ($p) => {
            const p = $p[0];

            p.querySelector('.modal-body').innerHTML =
              content ? content : `<i><?=_("No content")?></i>`;

            p.querySelector('.modal-title').innerHTML =
              `<i class="fas fa-sticky-note"></i> ${title}`;

            H.openModal({item: p, width: _getMaxEditModalWidth(content)});
          }
        });
      }
    },

    // METHOD getMin()
    getMin() {
      return this.settings.cell[0].querySelector(
                 `.postit-min[data-id="postit-${this.settings.id}"]`);
    },

    // METHOD getNormal()
    getNormal() {
      return this.settings.cell[0].querySelector(
                 `.postit[data-id="postit-${this.settings.id}"]`);
    },

    // METHOD displayAlert()
    displayAlert(type) {
      const data = this.element[0].dataset;
      let content;

      // Scroll to the to the post-it if needed.
      H.setViewToElement(this.element);

      H.waitForDOMUpdate(()=> {
        let title;
        let content;

        switch (type) {
          // Worker
          case 'worker':
            title = `<i class="fa fa-user-cog fa-fw"></i> <?=_("Note assignation")?>`;
            content = `<?=_("This note has been assigned to you")?>`;
            break;
          // Comment
          case 'comment':
            title = `<i class="fa fa-comment fa-fw"></i> <?=_("Comment")?>`;
            content = `<?=_("You were mentioned in a comment to this note")?>`;
            break;
          // Deadline
          case 'deadline':
          case 'postit':
            title = `<i class="fa fa-exclamation-triangle fa-fw"></i> <?=_("Expiration")?>`;

            if (!data.deadlineepoch) {
              content =`<?=_("The deadline for this note has been removed")?>`;
            } else if (this.element[0].classList.contains('obsolete')) {
              content = `<?=_("This note has expired")?>`;
            } else {
              const a = moment.unix(data.deadlineepoch);
              const b = moment (new Date());
              let days = moment.duration(a.diff(b)).asDays();

              if (days % 1 > 0) {
                days = Math.trunc(days) + 1;
              }

              content = (days > 1) ? `<?=_("This note will expire in about %s day(s)")?>`.replace("%s", days) : `<?=_("This note will expire soon")?>`;
            }
            break;
        }

        const min = this.getMin();

        H.openConfirmPopover({
          type: 'info',
          scrollIntoView: true,
          item: min ? $(min) : this.element,
          title, 
          content,
        });
      });
    },

    // METHOD remove()
    remove(noEffect) {
      const postit = this.element[0];

      // LOCAL FUNCTION __remove()
      const __remove = () => {
        const min = this.getMin();

        // Remove min postit (stack mode display) if needed
        if (min) {
          min.remove();
          this.settings.cell.cell('decCount');
        }

        this.removePlugs(true);
        postit.remove();
      };

      if (!noEffect) {
        // Empty postit content to prevent effect to reload deleted embedded
        // images
        postit.querySelector('.postit-edit').innerHTML = '';
        $(postit).hide('explode', __remove);

      // The explode effect works poorly on mobile devices
      } else {
        __remove();
      }

      S.getCurrent('mmenu').mmenu('remove', this.settings.id);

      this.getPlugin('pcomm').close ();
    },

    // METHOD havePlugs()
    havePlugs() {
      return this.settings.plugs.length;
    },

    // METHOD applyPlugLineType()
    applyPlugLineType(ll) {
      switch (ll.line_type) {
        case 'solid':
          ll.dash = false;
          break;
        case 'dashed':
          ll.dash = true;
          break;
        case 'a-dashed':
          ll.dash = {animation: true};
          break;
      }
    },

    // METHOD getPlugDropShadowTemplate()
    getPlugDropShadowTemplate(color) {
      return {dy: 10, color: H.lightenDarkenColor(color, -20)};
    },

    // METHOD getPlugTemplate()
    getPlugTemplate(args, ignoreZoom) {
      const color = args.line_color || S.getCurrent('plugColor');
      const size = args.line_size || <?=WPT_PLUG_DEFAULTS['lineSize']?>;
      const ll = new LeaderLine(
        args.start,
        args.end,
        {
          hide: Boolean(args.hide),
          dropShadow: this.getPlugDropShadowTemplate(color),
          size: ignoreZoom ? size : size * (S.get('zoom-level') || 1),
          path: args.line_path || `<?=WPT_PLUG_DEFAULTS['linePath']?>`,
          color: color,
          endPlug: args.endPlug || 'arrow1',
          middleLabel: LeaderLine.captionLabel({
            text: args.label,
            fontSize: '13px',
          }),
        });

      ll.line_size = size;
      ll.line_type = args.line_type || `<?=WPT_PLUG_DEFAULTS['lineType']?>`;
      ll.customCol = args.line_color;

      this.applyPlugLineType(ll);

      return ll;
    },

    // METHOD applyZoomToPlugs()
    applyZoomToPlugs(z) {
      const reset = (z === 1);

      this.settings.plugs.forEach((p) => {
        const size = Math.trunc(p.obj.line_size * z) || 1;
        const gr = Math.trunc((100 * (size * 100 / p.obj.line_size)) / 100);

        p.labelObj[0].style.transformOrigin = reset ? null : 'top left';
        p.labelObj[0].style.transform = reset ? null : `scale(${z})`;
        p.obj.size = size;

        if (p.customPos) {
          const g = reset ? 'auto' : gr;
          const opt = {
            size,
            startSocketGravity: g,
            endSocketGravity: g,
          };
          p.related.forEach((r) => r.setOptions(opt));
        }
      });
    },

    // METHOD applyZoom()
    applyZoom() {
      const z = S.get('zoom-level') || 1;

      S.getCurrent('wall')[0].querySelectorAll('.postit.with-plugs')
        .forEach((p) => $(p).postit('applyZoomToPlugs', z));
    },

    // METHOD applyThemeToPlugs()
    applyThemeToPlugs(color) {
      // LOCAL FUNCTION __apply ()
      const __apply = (r) => r.setOptions({
              color,
              dropShadow: this.getPlugDropShadowTemplate(color),
            });

      this.settings.plugs.forEach((p) => {
        if (p.obj.customCol) return;

        __apply(p.obj);

        if (p.customPos) {
          p.related.forEach((r) => __apply(r));
        }
      });
    },

    // METHOD applyTheme()
    applyTheme() {
      S.reset('plugColor');

      const color = S.getCurrent('plugColor');

      S.getCurrent('wall')[0].querySelectorAll('.postit.with-plugs')
        .forEach((p) => $(p).postit('applyThemeToPlugs', color));
    },

    // METHOD getWallHeadersShift()
    getWallHeadersShift() {
      const hs = this.settings.wall[0].dataset.headersshift;

      return hs ? JSON.parse(hs) : null;
    },

    // METHOD repositionPlugLabel()
    repositionPlugLabel(label, top, left, wPos) {
      const z = S.get('zoom-level') || 1;
       // Shift for plugs if headers are hidden
      const hs = this.getWallHeadersShift();
      let ptop = (parseInt(top) * z) + wPos.top;
      let pleft = (parseInt(left) * z) + wPos.left;

      if (hs) {
        ptop -= hs.height * z;
        pleft -= hs.width * z;
      }

      label.style.top = `${ptop}px`;
      label.style.left = `${pleft}px`;
    },

    // METHOD resetPlugLabelPosition()
    resetPlugLabelPosition(label) {
      label.removeAttribute('data-pos');
      label.removeAttribute('data-origtop');
      label.removeAttribute('data-origleft');

      if (this.canWrite()) {
        label.querySelector('i.fa-thumbtack').style.display = 'none';
        label.querySelector(`li[data-action="position-auto"]`)
          .style.display = 'none';
      }
    },

    // METHOD updatePlugProperties()
    updatePlugProperties(ll) {
      const id = ll.endId || this.settings.id;
      const defaultLineColor = S.getCurrent('plugColor');

      for (const plug of this.settings.plugs) {
        //FIXME
        if ((ll.endId && plug.endId == ll.endId) ||
            (!ll.endId && plug.startId == this.settings.id)) {
          const customCol = (ll.color && ll.color !== defaultLineColor);
          const lineColor = customCol ? ll.color : defaultLineColor;
          const lineType = (
            ll.line_type &&
            ll.line_type !== `<?=WPT_PLUG_DEFAULTS['lineType']?>`) ?
                ll.line_type : `<?=WPT_PLUG_DEFAULTS['lineType']?>`;
          const lineSize = (
            ll.size &&
            ll.size !== <?=WPT_PLUG_DEFAULTS['lineSize']?>) ?
                ll.size : <?=WPT_PLUG_DEFAULTS['lineSize']?>;
          const props = {
            size: lineSize * (S.get('zoom-level') || 1),
            path: (
              ll.path &&
              ll.path !== `<?=WPT_PLUG_DEFAULTS['linePath']?>`) ?
                  ll.path : `<?=WPT_PLUG_DEFAULTS['linePath']?>`,
              color: lineColor,
              dropShadow: this.getPlugDropShadowTemplate(lineColor),
            };

          plug.obj.setOptions(props);
          plug.obj.line_type = lineType;
          plug.obj.line_size = lineSize;
          plug.obj.customCol = customCol;

          this.applyPlugLineType(plug.obj);

          if (plug.customPos) {
            plug.related.forEach((r) => {
              r.setOptions(props);
              r.line_type = lineType;
              this.applyPlugLineType(r);
            });
          }

          // Update label
          if (ll.label !== undefined && plug.label.name !== ll.label) {
            this.updatePlugLabel({label: ll.label, endId: ll.endId});
          }
          break;
        }
      }
    },

    // METHOD updatePlugLabel ()
    updatePlugLabel(args) {
      const label = H.noHTML(args.label);
      const wPos = this.settings.wall[0].getBoundingClientRect();
      const canWrite = this.canWrite();
      const p = this.getPlugById(args.endId);
      const pl = p.labelObj[0];

      p.label.name = label;
      p.obj.middleLabel = LeaderLine.captionLabel({
        text: label,
        fontSize: '13px',
      });

      pl.querySelector('div span').innerHTML =
          (label === '' || label === '...') ?
            `<i class="fas fa-ellipsis-h"></i>` : label;

      if (args.top !== undefined) {
        if (args.top) {
          pl.dataset.pos = 1;
          pl.dataset.origtop = args.top;
          pl.dataset.origleft = args.left;

          if (canWrite) {
            pl.querySelector('i.fa-thumbtack').style.display = 'block';
            pl.querySelector(`li[data-action="position-auto"]`)
                .style.display = 'block';
          }

          if (!p.customPos) {
            this.repositionPlugLabel(pl, args.top, args.left, wPos);
            p.related = this.createRelatedPlugs(p);
            p.obj.hide();
          }
        } else if (p.customPos) {
          this.resetPlugLabelPosition(pl);
          _deleteRelatedPlugs(p);
          p.obj.show();
        }
      }

      this.repositionPlugs();
    },

    // METHOD createRelatedPlugs()
    createRelatedPlugs(plug) {
      const {line_size, path, color, line_type, start, end} = plug.obj;
      const pl = plug.labelObj[0];

      plug.customPos = true;

      return [
        this.getPlugTemplate({
          start,
          line_size,
          line_type,
          line_path: path,
          line_color: color,
          endPlug: 'behind',
          end: pl,
        }),
        this.getPlugTemplate({
          end,
          line_size,
          line_type,
          line_path: path,
          line_color: color,
          start: pl,
        })
      ];
    },

    // METHOD addPlugLabel()
    addPlugLabel (plug, svg, applyZoom) {
      const plugin = this;
      const wPos = this.settings.wall[0].getBoundingClientRect();
      const canWrite = this.canWrite();

      svg = document.querySelector(`#_${plug.startId}-${plug.endId}`);

      const pos = plug.label.top ?
        {
          top: plug.label.top + wPos.top,
          left: plug.label.left + wPos.left,
        } : svg.querySelector('text').getBoundingClientRect();
      const $start = $(plug.obj.start);
      const renameItem = H.haveMouse() ? `<li data-action="rename"><a class="dropdown-item" href="#"><i class="fa-fw fas fa-edit"></i> <?=_("Rename")?></a></li>` : '';
      const menu = `<ul class="dropdown-menu shadow">${renameItem}<li data-action="delete"><a class="dropdown-item" href="#"><i class="fa-fw fas fa-trash"></i> <?=_("Delete")?></a></li><li data-action="properties"><a class="dropdown-item" href="#"><i class="fa-fw fas fa-cogs"></i> <?=_("Properties")?></a></li><li data-action="position-auto"><a class="dropdown-item" href="#"><i class="fa-fw fas fa-magic"></i> <?=_("Auto position")?></a></li></ul>`;
      const label = H.createElement('div',
        {className: 'plug-label dropdown submenu', style: `top:${pos.top}px;left:${pos.left}px`},
        plug.label.top ? {pos: 1} : null,
        `${canWrite?`<i class="fas fa-thumbtack fa-xs"></i>`:""}<div ${canWrite?'data-bs-toggle="dropdown"':""} class="dropdown-toggle"><span>${plug.label.name != "..." ? H.noHTML (plug.label.name) : '<i class="fas fa-ellipsis-h"></i>'}</span></div>${canWrite?menu:""}`);

      plug.labelObj = $(document.body.appendChild(label));

      if (plug.label.top) {
        label.dataset.origtop = plug.label.top;
        label.dataset.origleft = plug.label.left;

        if (canWrite) {
          label.querySelector('i.fa-thumbtack').style.display = 'block';
        }

        plug.related = plugin.createRelatedPlugs(plug);
      } else {
        if (canWrite) {
          label.querySelector(`li[data-action="position-auto"]`)
            .style.display = 'none';
        }

        plug.related = [];
        plug.customPos = false;
        plug.obj.show('none');
      }

      if (applyZoom) {
        label.style.transformOrigin = 'top left';
        label.style.transform = `scale(${S.get('zoom-level') || 1})`;
      }

      if (canWrite)
        plug.labelObj.draggable ({
          distance: 10,
          containment: S.getCurrent('wall').find('tbody.wpt'),
          scroll: false,
          start: function(e, ui) {
            S.set ('revertData', {
              revert: false,
              top: plug.labelObj[0].offsetTop,
              left: plug.labelObj[0].offsetLeft
            });

            $start.postit('edit', {},
              // success cb
              () => {
                if (!plug.customPos) {
                  plug.related = plugin.createRelatedPlugs(plug);
                  plug.obj.hide();
                }
                plug.related.forEach((r) => r.hide('none'));
              },
              // error cb
              () => S.get('revertData').revert = true);
          },
          drag: function() {
            if (S.get('revertData').revert) {
              $(this).draggable('cancel');
              return false;
            }
            // plug.related.forEach(r => r.position ());
          },
          stop: function(e, ui) {
            S.set('dragging', true, 500);

            if (S.get('revertData').revert) {
              const revertData = S.get('revertData');

              plug.labelObj.style.top = `${revertData.top}px`;
              plug.labelObj.style.left = `${revertData.left}px`;

              $start.postit('cancelEdit');
            } else {
              const wPos = S.getCurrent('wall')[0].getBoundingClientRect();
              const lbPos = label.getBoundingClientRect();
              const z = S.get('zoom-level') || 1;
              const toSave = {};

              label.dataset.changed = 1;
              label.dataset.pos = 1;

              label.querySelector('i.fa-thumbtack').style.display = 'block';
              label.querySelector(`li[data-action="position-auto"]`)
                .style.display = 'none';

              label.dataset.origtop = Number((lbPos.top - wPos.top) / z);
              label.dataset.origleft = Number((lbPos.left - wPos.left) / z);

              toSave[plug.startId] = $(plug.obj.start);
              toSave[plug.endId] = $(plug.obj.end);

              S.set('plugs-to-save', toSave);
              $start.postit('unedit');
            }

            setTimeout(() =>
              plug.related.forEach((r) => r.position().show()), 150);
          }
        });
    },

    // METHOD addPlug()
    addPlug(plug, applyZoom) {
      const $start = this.element;
      const $end = $(plug.obj.end);

      // Associate SVG line to plug and set its label
      const svg = document.querySelector('.leader-line:last-child');
      svg.id = `_${plug.startId}-${plug.endId}`;
      this.addPlugLabel(plug, svg, applyZoom);

      // Register plug on start point postit (current plugin)
      this.settings.plugs.push(plug);
      $start[0].classList.add('with-plugs');

      // Register plug on end point postit
      $end.postit('getSettings').plugs.push(plug);
    },

    // METHOD defragPlugsArray()
    defragPlugsArray() {
      const settings = this.settings;
      let i = settings.plugs.length;

      while (i--) {
        if (!settings.plugs[i].obj) {
          settings.plugs.splice(i, 1);
        }
      }

      if (!this.havePlugs()) {
        this.element[0].classList.remove('with-plugs');
      }
    },

    // METHOD plugExists()
    plugExists(plugId) {
      for (const plug of this.settings.plugs) {
        // FIXME == ===
        if (plug.startId == plugId || plug.endId == plugId) {
          return true;
        }
      }
    },

    // METHOD getPlugById()
    getPlugById(plugId) {
      for (const plug of this.settings.plugs) {
        // FIXME == ===
        if (plug.endId == plugId) {
          return plug;
        }
      }
    },

    // METHOD removePlug()
    removePlug(plug, noedit) {
      const toDefrag = {};

      if (typeof plug !== 'object') {
        plug = this.getPlugById(plug);
      }

      _removePlug(plug, toDefrag);

      for (const id in toDefrag) {
        $(toDefrag[id]).postit('defragPlugsArray');
      }

      if (!noedit) {
        S.set('plugs-to-save', toDefrag);
      }
    },

    // METHOD removePlugs()
    removePlugs(noedit) {
      const settings = this.settings;
      const toDefrag = {};

      settings.plugs.forEach((p) => _removePlug(p, toDefrag));

      for (const id in toDefrag) {
        if ($(toDefrag[id]).length) {
          $(toDefrag[id]).postit('defragPlugsArray');
        }
      }

      if (!noedit) {
        S.set('plugs-to-save', toDefrag);
      }

      settings.plugs = [];
      this.element[0].classList.remove('with-plugs');
    },

    // METHOD hidePlugs()
    hidePlugs(ignoreDisplayMode = false) {
      if (!this.settings.wall) return;

      const postitId = this.settings.id;

      this.settings.plugs.forEach((p) => {
        if (!ignoreDisplayMode) {
          if (p.startId == postitId) {
            p.startHidden = true;
          } else {
            p.endHidden = true;
          }
        }

        p.labelObj.hide();
        if (!p.customPos) {
          p.obj.hide('none');
        } else {
          p.related.forEach((r) => r.hide('none'));
        }
      });
    },

    // METHOD showPlugs()
    showPlugs(ignoreDisplayMode = false) {
      if (!this.settings.wall) return;

      const postitId = this.settings.id;
      const wPos = this.settings.wall[0].getBoundingClientRect();

      this.settings.plugs.forEach((p) => {
        if (!ignoreDisplayMode) {
          if (p.startId == postitId) {
            delete p.startHidden;
          } else {
            delete p.endHidden;
          }
        }

        if (!p.startHidden && !p.endHidden) {
          p.labelObj.show();
          if (!p.customPos) {
            p.obj.show('none');
          } else {
            const pl = p.labelObj[0];

            this.repositionPlugLabel(
                pl, pl.dataset.origtop, pl.dataset.origleft, wPos);

            p.related.forEach ((r) => r.show('none').position());
          }
        }
      });
    },

    // METHOD repositionPlugs()
    repositionPlugs() {
      const wPos = this.settings.wall[0].getBoundingClientRect();

      this.settings.plugs.forEach((p) => {
        const pl = p.labelObj[0];

        if (pl.dataset.pos) {
          this.repositionPlugLabel(
              pl, pl.dataset.origtop, pl.dataset.origleft, wPos);

          p.related.forEach((r) => r.position());
        } else {
          p.obj.position ();

          const pos = document.querySelector(`#_${p.startId}-${p.endId} text`)
                          .getBoundingClientRect();

          pl.style.top = `${pos.top}px`;
          pl.style.left = `${pos.left}px`;
        }
      });
    },

    // METHOD getCellId()
    getCellId() {
      return this.settings.cellId;
    },

    // METHOD serializePlugs()
    serializePlugs() {
      const settings = this.settings;
      const defaultLineColor = S.getCurrent('plugColor');
      // Shift for plugs if headers are hidden
      const hs = this.getWallHeadersShift();
      let ret = {};

      settings.plugs.forEach((p) => {
        // Take in account only plugs from this postit
        if (p.startId == settings.id) {
          const pl = p.labelObj[0];

          ret[p.endId] = {
            label:
                (p.label === '...') ?
                   '' : pl.querySelector('div span').innerText,
            line_type:
                (p.obj.line_type !== `<?=WPT_PLUG_DEFAULTS['lineType']?>`) ?
                   p.obj.line_type : undefined,
            line_size:
                (p.obj.line_size !== <?=WPT_PLUG_DEFAULTS['lineSize']?>) ?
                   p.obj.line_size : undefined,
            line_path:
                (p.obj.path !== `<?=WPT_PLUG_DEFAULTS['linePath']?>`) ?
                   p.obj.path : undefined,
            line_color:
                (p.obj.color !== defaultLineColor) ?
                   p.obj.color : undefined
          };

          if (pl.dataset.pos) {
            ret[p.endId].top = Number(pl.dataset.origtop);
            ret[p.endId].left = Number(pl.dataset.origleft);

            // We apply shift only if headers are hidden, plug has a custom
            // position and has just been modified
            if (hs && p.customPos && pl.dataset.changed) {
              pl.removeAttribute('data-changed');

              ret[p.endId].top += hs.height;
              ret[p.endId].left += hs.width;
            }
          }
        }
      });

      return ret;
    },

    // METHOD serialize()
    serialize(args = {}) {
      const postits = [];
      const displayExternalRef = this.settings.wall.wall('displayExternalRef');
      const z = S.get('zoom-level') || 1;

      this.element.each(function() {
        const plugin = $(this).postit('getClass');
        let data = {};

        if (this.dataset.todelete) {
          data = {id: plugin.settings.id, todelete: true};
        } else {
          const title = plugin.getTitle();
          const content = this.querySelector('.postit-edit').innerHTML;
          const classcolor = this.className.match(/(color\-[a-z]+)/);
          const patt = this.querySelector('.patt span');
          const pwork = this.querySelector('.pwork span');
          const deadline = this.dataset.deadlineepoch ?
                  this.dataset.deadlineepoch :
                  this.querySelector('.dates .end span').innerText.trim();
          const bbox = this.getBoundingClientRect();
          let tags = [];
          let top = Math.trunc(this.offsetTop);
          let left = Math.trunc(this.offsetLeft);

          this.querySelectorAll('.postit-tags i').forEach((item) =>
              tags.push(item.dataset.tag));

          data = {
            id: plugin.settings.id,
            width: Math.trunc(bbox.width / z),
            height: Math.trunc(bbox.height / z),
            item_top: (this.offsetTop < 0) ? 0 : top,
            item_left: (this.offsetLeft < 0) ? 0 : left,
            item_order: parseInt(this.dataset.order),
            classcolor: classcolor ? classcolor[0] : _defaultClassColor,
            title: (title === '...') ? '' : title,
            content: args.noPostitContent ? null :
                       displayExternalRef ?
                         content : plugin.unblockExternalRef(content),
            tags: tags.length ? `,${tags.join(',')},` : null,
            deadline: (deadline === '...') ? '' : deadline,
            alertshift: (this.dataset.deadlinealertshift !== undefined) ?
                          this.dataset.deadlinealertshift : null,
            updatetz: this.dataset.updatetz || null,
            obsolete: this.classList.contains('obsolete'),
            attachmentscount: patt ? patt.innerText : 0,
            workerscount: pwork ? pwork.innerText : 0,
            plugs: plugin.serializePlugs(),
            hadpictures: Boolean(this.dataset.hadpictures),
            hasuploadedpictures: Boolean(this.dataset.hasuploadedpictures),
            progress: parseInt(this.dataset.progress || 0),
          };
        }

        postits.push(data);
      });

      return postits;
    },

    // METHOD showUserWriting()
    showUserWriting(user, isRelated) {
      const postit = this.element[0];
      const id = this.settings.id;
      const $cell = this.settings.cell;
      const canWrite = this.canWrite();

      // LOCAL FUNCTION __lock()
      const __lock = (el) =>
        el.classList.add('locked', isRelated ? 'related' : 'main');

      // LOCAL FUNCTION __addMain()
      const __addMain = () =>
        postit.insertBefore(
          H.createElement('div',
            {className: 'user-writing main'},
            {userid: user.id},
            `<i class="fas fa-user-edit blink"></i> ${user.name}`),
          postit.firstChild);

      this.closeMenu();

      // See cell::setPostitsUserWritingListMode()
      if ($cell[0].classList.contains('list-mode')) {
        const min = this.getMin();

        if (canWrite) {
          __lock(min);
        }

        min.insertBefore(
          H.createElement('span',
            {className: `user-writing-min${isRelated ? '' : ' main'}`},
            {userid: user.id},
            `<i class="fas fa-sm fa-${isRelated ? 'user-lock' : 'user-edit blink'}"></i>`),
          min.firstChild);
      }

      if (canWrite) {
        __lock(postit);

        if (isRelated) {
          postit.insertBefore(
            H.createElement('div',
              {className: 'user-writing'},
              {userid: user.id},
              `<i class="fas fa-user-lock"></i>`),
            postit.firstChild);
        } else {
          __addMain();
        }

        // Show a lock bubble on related items
        this.settings.plugs.forEach((p) => {
          p.labelObj[0].classList.add('locked');
          if (!isRelated) {
            $(p.obj[(p.startId !== id) ? 'start' : 'end'])
              .postit('showUserWriting', user, true);
          }
        });
      }
      else if (!isRelated) {
        __addMain ();
      }
    },

    // METHOD setDeadline()
    setDeadline (args) {
      const postit = this.element[0];
      const date = postit.querySelector('.dates .end');
      const {deadline, alertshift, timezone} = args;
      const reset = date.querySelector('i.fa-times-circle');
      let human;

      if (!deadline || isNaN(deadline)) {
        human = deadline || '...';
      } else {
        human = deadline ? H.getUserDate(deadline, timezone) : '...';
      }

      date.querySelector('span').innerText = human;

      reset.style.display = 'none';

      if (human === '...') {
        postit.classList.remove('obsolete');

        ['deadline', 'deadlinealertshift', 'deadlineepoch', 'updatetz']
          .forEach((k) => postit.removeAttribute(`data-${k}`));

        date.classList.remove('with-alert');
        date.classList.remove('obsolete');
      } else {
        postit.dataset.deadline = human;
        postit.dataset.deadlineepoch = deadline;

        if (alertshift !== undefined) {
          if (alertshift !== null) {
            postit.dataset.deadlinealertshift = alertshift;
            date.classList.add('with-alert');
          } else {
            postit.removeAttribute('data-deadlinealertshift');
            date.classList.remove('with-alert');
          }
        }

        if (this.canWrite()) {
          reset.style.display = 'inline-block';
        }
      }
    },

    // METHOD resetDeadline()
    resetDeadline() {
      this.setDeadline({deadline: '...'});
    },

    // METHOD setCreationDate()
    setCreationDate(v) {
      this.element[0].querySelector('.dates .creation span')
        .innerText = v.trim();
    },

    // METHOD setProgress()
    setProgress (v) {
      const postit = this.element[0];
      const container = postit.querySelector('.postit-progress-container');

      v = Number(v);

      if (!v) {
        postit.removeAttribute('data-progress');
        container.style.display = 'none';
      } else {
        const progress = container.querySelector('.postit-progress');

        postit.dataset.progress = v;

        container.querySelector('span').innerText = `${v}%`;
        container.style.display = 'block';

        progress.style.height = `${v}%`;
        progress.style.backgroundColor = H.getProgressbarColor(v);
      }
    },

    // METHOD getTitle()
    getTitle() {
      return this.element[0]
        .querySelector('.postit-header span.title').innerHTML;
    },

    // METHOD setTitle()
    setTitle(v) {
      this.element[0].querySelector('.postit-header span.title')
        .innerText = H.noHTML(v) || '...';
    },

    // METHOD addExternalRefIcon()
    addExternalRefIcon(c) {
      c.querySelectorAll("[external-src]").forEach((img) => {
        const next = img.nextSibling;

        if (!next ||
            !next.classList || !next.classList.contains('externalref')) {
          img.parentNode.title = `<?=_("This external content is filtered")?>`;
          H.insertAfter(
            H.createElement('i',
              {className: 'fas fa-umbrella fa-lg externalref'}),
            img);
        }
      });
    },

    // METHOD removeExternalRefIcon()
    removeExternalRefIcon(c) {
      c.querySelectorAll('i.externalref').forEach((el) => {
        el.parentNode.removeAttribute('title');
        el.remove ();
      });
    },

    // METHOD setContent()
    setContent(newContent) {
      const postit = this.element[0];
      const edit = postit.querySelector('.postit-edit');
      let setIcon = false;

      if (newContent !== edit.innerHTML) {
        const externalRef = this.getExternalRef(newContent);

        if (externalRef) {
          postit.dataset.haveexternalref = 1;

          if (!this.settings.wall.wall('displayExternalRef')) {
            setIcon = true;
            newContent = this.blockExternalRef(newContent, externalRef);
          }
        } else {
          postit.removeAttribute ("data-haveexternalref");
        }

        edit.innerHTML = newContent;

        if (setIcon) {
          this.addExternalRefIcon(edit);
        } else {
          this.removeExternalRefIcon(edit);
        }
      }
    },

    // METHOD openAskForExternalRefPopup()
    openAskForExternalRefPopup(args = {}) {
      let ask = (this.getExternalRef() &&
                 !this.settings.wall.wall('displayExternalRef'));

      if (ask) {
        H.openConfirmPopover({
          item: args.item || this.element,
          title: `<i class="fas fa-link fa-fw"></i> <?=_("External content")?>`,
          content: `<?=_("This note contains external images or videos.")?><br><?=_("Would you like to load all external content for the current wall?")?>`,
          cb_close: args.cb_close,
          cb_ok: () => {
            this.settings.wall.wall('displayExternalRef', 1);
            this.open();
          }
        });
      }

      return ask;
    },

    // METHOD getExternalRef()
    getExternalRef(content) {
      return (content !== undefined) ?
               content.match(/(src\s*=\s*["']?http[^"'\s]+")/ig) :
               this.element[0].dataset.haveexternalref;
    },

    // METHOD blockExternalRef()
    blockExternalRef(content, externalRef) {
      const el = this.element[0].querySelector('.postit-edit');
      let c = content || el.innerHTML;

      if (!externalRef) {
        externalRef = this.getExternalRef(c);
      }

      if (externalRef) {
        externalRef.forEach((src) =>
          c = c.replace (new RegExp ('[^\-]'+H.escapeRegex(src), 'g'),
                ` external-${src} `));

        if (content === undefined) {
          el.innerHTML = c;
          this.addExternalRefIcon(el);
        } else {
          return c;
        }
      }
    },

    // METHOD unblockExternalRef()
    unblockExternalRef(content) {
      if (content !== undefined) {
        return content.replace(/external\-src/g, 'src');
      } else {
        const postit = this.element[0];

        postit.querySelectorAll('[external-src]').forEach((el) => {
          el.setAttribute('src', el.getAttribute('external-src'));
          el.removeAttribute('external-src');
        });

        this.removeExternalRefIcon(postit);
      }
    },

    // METHOD setPosition()
    setPosition({cellId, top, left}) {
      const postit = this.element[0];

      if (cellId) {
        this.settings.cellId = cellId;
      }

      postit.style.top = `${top}px`;
      postit.style.left = `${left}px`;
    },

    // METHOD fixEditHeight()
    fixEditHeight() {
      const postit = this.element[0];

      postit.querySelector('.postit-edit')
        .style.maxHeight = `${postit.offsetHeight - 40}px`;
    },

    // METHOD fixPosition()
    fixPosition(cPos) {
      const postit = this.element[0];
      const phTop =
        postit.querySelector('.postit-header').getBoundingClientRect().top;
      const pW = postit.clientWidth;
      const pH = postit.clientHeight;
      const cH = cPos.height;
      const cW = cPos.width;
      let pPos = postit.getBoundingClientRect();

      // Postit is too high
      if (phTop < cPos.top) {
        postit.style.top = '20px';
      }
 
      // Postit is too much on left
      if (pPos.left < cPos.left) {
        postit.style.left = '1px';
      }
 
      // Postit is too much on right
      if (pPos.left + pW > cPos.left + cW + 1) {
        postit.style.left = `${cW - pW - 4}px`;
      }

      // Postit is too large
      if (pW > cW) {
        postit.style.left = '0';
        postit.style.width = `${cW - 2}px`;
      }
 
      pPos = postit.getBoundingClientRect();
 
      // Postit is too big
      if (pPos.top + pH > cPos.top + cH) {
        if (pH > cH) {
          postit.style.height = `${cH - 22}px`;
        }
 
        postit.style.top = `${cH - postit.clientHeight - 4}px`;
      }
    },

    // METHOD setClassColor()
    setClassColor (newClass, item) {
      if (item !== undefined && item === null) return;

      const el = item ? item : this.element[0];
      const cls = el.className.replace(/color\-[a-z]+/, '');

      el.className = `${cls} ${newClass}`;
    },

    // METHOD setPopupColor()
    setPopupColor($popup) {
      const popup = $popup[0];
      const cls = this.element[0].className.match(/color\-[a-z]+/)[0];

      this.setClassColor (cls, popup.querySelector('.modal-header'));
      this.setClassColor (cls, popup.querySelector('.modal-title'));
      this.setClassColor (cls, popup.querySelector('.modal-footer'));
    },

    // METHOD setCurrent()
    setCurrent() {
      S.reset('postit');
      this.element[0].classList.add('current');
    },

    // METHOD unsetCurrent()
    unsetCurrent() {
      S.reset('postit');
      S.reset('pcomm');

      this.element[0].classList.remove('current');
    },

    // METHOD insert()
    insert() {
      const postit = this.element[0];
      const data = this.serialize()[0];

      H.request_ws(
        'PUT',
        `wall/${this.settings.wallId}/cell/${this.settings.cellId}/postit`,
        data,
        // success cb
        (d) => {
          if (d.error_msg) {
            H.displayMsg({
              title: `<?=_("Note")?>`,
              type: 'warning',
              msg: d.error_msg,
            });
          }
          postit.remove();
        },
        // error cb
        (d) => {
          //FIXME factorisation (cf. H.request_ws ())
          H.displayMsg({
            title: `<?=_("Note")?>`,
            type: 'danger',
            msg: isNaN(d.error) ?
              d.error : `<?=_("Unknown error.<br>Please try again later.")?>`,
          });
          postit.remove();
        });
    },

    // METHOD save()
    save({content, progress, title}) {
      this.setContent(content);
      this.setProgress(progress);
      this.setTitle(title);

      this.element[0].removeAttribute('data-uploadedpictures');

      S.unset('postit-data');
    },

    // METHOD update()
    update(d, cell) {
      const $postit = this.element;
      const postit = $postit[0];
      const $tpick = S.getCurrent('tpick');
      const postitEdit = d.init ? postit.querySelector('.postit-edit') : null;

      if (postitEdit) {
        postitEdit.style.display = 'none';
      }

      // Change postit cell
      if (cell && cell.id !== Number(this.settings.cellId)) {
        if (this.settings.cell[0].classList.contains('list-mode')) {
          this.settings.cell.cell('decCount');
          this.getMin().remove();
        }

        this.settings.cell =
          cell.obj ||  $(this.settings.wall[0]
                         .querySelector(`td[data-id="cell-${cell.id}"]`));
        this.settings.cellId = cell.id;

        this.settings.cell[0].append(postit);

        if (this.settings.cell[0].classList.contains('postit-mode')) {
          postit.style.visibility = 'visible';
        }
      }

      if (!d.ignoreResize) {
        postit.style.top = `${d.item_top}px`;
        postit.style.left = `${d.item_left}px`;
        postit.style.width = `${d.width}px`;
        postit.style.height = `${d.height}px`;

        this.fixEditHeight();
      }

      this.setClassColor(d.classcolor);
      this.setProgress(d.progress);
      this.setTitle(d.title);
      this.setContent(d.content);

      //FIXME
      let p;
      if (p = this.getPlugin('patt')) {
        p.setCount(d.attachmentscount);
      }
      if (p = this.getPlugin('pwork')) {
        p.setCount(d.workerscount);
      }

      this.setCreationDate(d.creationdate ? H.getUserDate(d.creationdate) : '');
      this.setDeadline(d);

      postit.dataset.order = d.item_order || 0;

      if (d.obsolete) {
        postit.classList.add('obsolete');
      } else {
        postit.classList.remove('obsolete');
      }

      if (!d.tags) {
        d.tags = '';
      }
      postit.dataset.tags = d.tags;

      postit.querySelector('.postit-tags').innerHTML =
        $tpick.tpick('getHTMLFromString', d.tags);

      $tpick.tpick('refreshPostitDataTag', $postit);

      // FIXME
      if (postitEdit) {
        setTimeout(() => postitEdit.style.display = 'block', 150);
      }
    },

    // METHOD delete()
    delete() {
      S.reset();
      this.element[0].dataset.todelete = true;
    },

    // METHOD edit()
    edit(args = {}, success_cb, error_cb) {
      const data = {cellId: this.settings.cellId};

      if (!args.plugend) {
        this.setCurrent();
        _originalObject = this.serialize()[0];
      }

      if (!this.settings.wall.wall('isShared')) {
        return success_cb && success_cb();
      }

      H.request_ws(
        'PUT',
        `wall/${this.settings.wallId}/editQueue/postit/${this.settings.id}`,
        data,
        // success cb
        (d) => {
          if (d.error_msg) {
            H.raiseError(() => {
              error_cb && error_cb();
              this.cancelEdit(args);
            }, d.error_msg);
          } else if (success_cb) {
            success_cb({...d, ...args});
          }
        },
        // error cb
        (d) => this.cancelEdit(args),
      );
    },

    // METHOD unedit()
    unedit(args = {}) {
      const $postit = this.element;
      const plugsToSave = S.get('plugs-to-save');
      let data = null;

      if (!this.settings.id || !this.canWrite()) {
        return this.cancelEdit(args);
      }

      if (!args.plugend) {
        // Update postits plugs dependencies
        if (plugsToSave) {
          data = {updateplugs: true, plugs: []};

          for (const id in plugsToSave) {
            data.plugs.push($(plugsToSave[id])
              .postit('serialize', {noPostitContent: true})[0]);
          }

          S.unset('plugs-to-save');

        // Postit update
        } else {
          data = this.serialize()[0];

          // Delete/update postit only if it has changed
          if (data?.todelete ||
              H.updatedObject(_originalObject, data, {hadpictures: 1})) {
            data.cellId = this.settings.cellId;
          } else if (!this.settings.wall.wall('isShared')) {
            return this.cancelEdit();
          } else {
            data = null;
          }
        }
      }

      H.request_ws(
        'DELETE',
        `wall/${this.settings.wallId}/editQueue/postit/${this.settings.id}`,
        data,
        // success cb
        (d) => {
          const postit = $postit[0];

          this.cancelEdit(args);

          if (d.error_msg) {
            H.displayMsg({
              title: `<?=_("Note")?>`,
              type: 'warning',
              msg: d.error_msg,
            });
          } else if (data?.todelete && postit.classList.contains('selected')) {
            S.getCurrent('mmenu').mmenu('remove', this.settings.id);
          } else if (data && data.updatetz) {
            postit.removeAttribute('data-updatetz');
          }
        },
        // error cb
        () => this.cancelEdit(args));
    },

    // METHOD cancelEdit()
    cancelEdit(args = {}) {
      document.body.style.cursor = 'auto';

      if (!args.plugend) {
        const postit = this.element[0];

        this.unsetCurrent();

        postit.removeAttribute('data-hasuploadedpictures');
        postit.removeAttribute('data-hadpictures');
      }

      if (!this.settings.id) {
        setTimeout(() => H.raiseError (null, `<?=_("The entire column/row was deleted while you were editing the note")?>`), 150);
      }
    },

    // METHOD closeMenu()
    closeMenu () {
      const postit = this.element[0];

      if (postit.querySelector('.postit-menu')) {
        postit.querySelector('.btn-menu').click ();
      }
    }
  };

  /////////////////////////// AT LOAD INIT //////////////////////////////

  document.addEventListener('DOMContentLoaded', () => {
    if (H.isLoginPage ()) return;

    const walls = document.querySelector('.tab-content.walls');

    // FIXME polyfill for TinyMCE (old Safari iOS)
    Promise.allSettled = Promise.allSettled || ((promises) => Promise.all(
      promises.map((p) => p
        .then(value => ({status: 'fulfilled', value}))
        .catch(reason => ({status: 'rejected', reason})))
    ));

    // Init text editor
    let locale = $("html")[0].dataset.fulllocale;
    tinymce.init ({
      selector: "#postitUpdatePopupBody",
      promotion: false,
      content_style: "p {margin: 0}",
      language: (locale != "en_US")?locale:null,
      language_url: (locale != "en_US")?`/libs/tinymce-${locale}.js`:null,
      branding: false,
      plugins: [
        "autoresize",
        "link",
        "image",
        "media",
        "charmap",
        "searchreplace",
        "visualchars",
        "fullscreen",
        "insertdatetime",
        "lists",
        "table",
      ],
      setup: function (editor)
      {
        // "change" event can be triggered twice, we use this var to
        // avoir that
        let _current = false;

        // Trick to catch 404 not found error on just added images
        // -> Is there a TinyMCE callback for that?
        editor.on("change", function (e)
          {
            if (_current)
              return;

            _current = true;

            let c = editor.getContent ();

            // Remove unwanted images attributes
            if (c.match (/\s(srcset|alt)\s*=/i))
            {
              //FIXME
              c = c.replace (/\s(srcset|alt)\s*=/ig, "none=");
              editor.setContent (c);
            }

            // Check for img only if the TinyMCE dialog is open
            if ($(".tox-dialog").is(":visible"))
            {
              (c.match(/<img\s[^>]+>/g)||[]).forEach (img =>
                {
                  var tmp = img.match (/src="([^\"]+)"/);
                  if (tmp)
                  {
                    const src = tmp[1];

                    H.loader ("show");
                    H.testImage(src)
                      .then (
                      // Needed for some Safari on iOS that do not support
                      // Promise finally() callback.
                      ()=> H.loader("hide"),
                      ()=>
                      {
                        H.loader("hide");

                        editor.setContent (
                          c.replace(new RegExp (H.quoteRegex(img)), ""));

                        // Return to the top of the modal if mobile device
                        if (!H.haveMouse ())
                          $("#postitUpdatePopup").scrollTop (0);

                        H.displayMsg ({
                          title: `<?=_("Note")?>`,
                          type: "warning",
                          msg: `<?=_("The image %s was not available! It has been removed from the note content.")?>`.replace("%s", `«&nbsp;<i>${src}</i>&nbsp;»`)
                        });
                      })
                      .finally (()=> _current = false);
                  }
                });
            }
            else
              _current = false;
          });
      },

      // "media" plugin options.
      media_alt_source: false,
      media_poster: false,

      // "image" plugin options
      image_description: false,
      automatic_uploads: true,
      file_picker_types: "image",
      file_picker_callback: function (callback, value, meta)
      {
        S.set ("tinymce-callback", callback);
        document.getElementById("postit-picture").click ();
      },

      // "link" plugin options
      default_link_target: "_blank",
      link_assume_external_targets: true,
      link_default_protocol: "https",
      link_title: false,
      target_list: false,

      visual: false,
      mobile: {menubar: "edit view format insert"},
      menubar: "edit view format insert",
      menu:{view:{title:`<?=_("View")?>`, items:"fullscreen"}},
      toolbar: "undo redo | bold italic underline | numlist bullist | alignleft aligncenter alignright alignjustify | link image | table",
      statusbar: false
    });

    // EVENTS mouseenter touchstart on postit
    // Sort of ":hover" simulation with z-index persistence
    $(document).on("mouseenter touchstart", ".postit", function ()
      {
        const el = S.getCurrent("wall")[0].querySelector (".postit.hover");

        if (el)
          el.classList.remove ("hover");

        this.classList.add ("hover");
      });

    // EVENT "click" on postit
    document.addEventListener ("click", (e)=>
    {
      const el = e.target;
      
      if (el.matches('.postit *')) {
        const postit = el.closest (".postit");
  
        // EVENT "click" ctrl+click on postit
        if (e.ctrlKey)
        {
          const id = postit.dataset.id.substring (7),
                menu = S.getCurrent("mmenu").mmenu ("getClass");
  
          e.stopImmediatePropagation ();
          H.preventDefault (e);
  
          if (postit.classList.contains ("selected"))
            menu.remove (id);
          else
            menu.add ($(postit).postit ("getClass"));
        }
        // EVENT "click" on postit content links
        else if (el.matches('.postit-edit a[href],.postit-edit a[href] *')) {
          e.stopImmediatePropagation();
          if (e.ctrlKey || H.disabledEvent()) return;
          _displayOpenLinkMenu(e);
         }
        // EVENT "click" on postit for READ-ONLY mode
        else if (!H.checkAccess ("<?=WPT_WRIGHTS_RW?>"))
        {
          if (H.disabledEvent ())
          {
            H.preventDefault (e);
            return;
          }
  
          if (!el.closest(".topicon"))
            $(postit).postit ("openPostit");
        }
        // EVENT "click" on postit menu button
        else if (el.matches (".btn-menu,.btn-menu *"))
        {
          e.stopImmediatePropagation();
  
          if (!H.checkAccess(`<?=WPT_WRIGHTS_RW?>`)) return;
  
          const btn = (el.tagName == "DIV")?el:el.closest("div"),
                ibtn = btn.querySelector ("i"),
                $postit = $(postit),
                id = $postit.postit ("getId"),
                settings = $postit.postit ("getSettings"),
                $wall = settings.wall,
                $header = $postit.find (".postit-header");
  
          // Create postit menu and show it
          if (!settings.Menu)
          {
            $wall.wall ("closeAllMenus");
            settings.Menu = new _Menu ($postit.postit ("getClass"));
            settings.Menu.show ();
          }
          // Destroy postit menu
          else
          {
            settings.Menu.destroy ();
            delete settings.Menu;
          }
        // EVENT "click" on postit menu buttons
        } else if (el.matches('.postit-menu,.postit-menu *')) {
          e.stopImmediatePropagation();

          if (el.tagName === 'DIV') return;

          const postitPlugin = $(postit).postit('getClass');
          const action =
              (el.tagName === 'SPAN' ? el : el.closest('span')).dataset.action;
  
          // To prevent race condition with draggable & resizable plugins
          if (H.disabledEvent()) return;
  
          switch (action) {
            // OPEN postit edit popup
            case 'edit': return postitPlugin.openPostit();
            // OPEN deadline date picker popup
            case 'dpick': return postitPlugin.openDatePicker();
            // OPEN popup for attachments, workers or comments
            case 'patt':
            case 'pwork':
            case 'pcomm':
              return $(postit.querySelector(
                  `.topicon .${action}`))[action]('open');
          }
  
          postitPlugin.edit({}, () => {
            switch (action) {
              // DELETE postit
              case 'delete':
                H.openConfirmPopover({
                  item: $(postit.querySelector('.btn-menu')),
                  placement: 'right',
                  title: `<i class="fas fa-trash fa-fw"></i> <?=_("Delete")?>`,
                  content: `<?=_("Delete this note?")?>`,
                  cb_close: () => postitPlugin.unedit(),
                  cb_ok: () => postitPlugin.delete(),
                });
                break;
              // OPEN tags picker
              case 'tpick':
                return S.getCurrent('tpick').tpick('open', e);
              // OPEN color picker
              case 'cpick':
                const cp = $('#cpick').cpick('getClass');
  
                cp.open({
                  event: e,
                  cb_close: () => postitPlugin.element.trigger('mouseleave'),
                  cb_click: (div) => {
                    const el = postitPlugin.element[0];
                    cp.getColorsList().forEach((c) => el.classList.remove(c));
                    el.classList.add(div.className);
                  },
                });
                break;
              // ADD plug
              case 'add-plug':
                postitPlugin.closeMenu();
  
                S.set('link-from', {
                  id: postitPlugin.settings.id,
                  obj: $(postit),
                });
  
                _plugRabbit.line = new LeaderLine(
                  postit,
                  $(`<div id="plug-rabbit" style="left:${e.clientX+5}px;top:${e.clientY-10}px"> <i class="fas fa-anchor fa-lg set"></i></div>`).prependTo('body')[0],
                  {
                    path: `<?=WPT_PLUG_DEFAULTS['linePath']?>`,
                    size: 3,
                    color: '#9b9c9c',
                    dash: true,
                    endPlug: 'behind',
                  }
                );
  
                document.addEventListener('keydown',
                    _plugRabbit.escapeEvent);
                document.addEventListener ('mousedown',
                    _plugRabbit.mousedownEvent);
                document.addEventListener ('mousemove',
                    _plugRabbit.mousemoveEvent);
                break;
            }
          });

        // EVENT "click" on postit dates
        } else if (el.matches('.dates .end,.dates .end *')) {
          e.stopImmediatePropagation();
  
          if (H.disabledEvent (!H.checkAccess(`<?=WPT_WRIGHTS_RW?>`))) {
            H.preventDefault(e);
            return;
          }
  
          const $item = $(el.tagName === 'DIV' ? el : el.closest('div'));
          const plugin = $(postit).postit('getClass');
  
          if (el.classList.contains('fa-times-circle')) {
            plugin.edit({}, () => {
              H.openConfirmPopover({
                item: $item,
                title: `<i class="fas fa-trash fa-fw"></i> <?=_("Reset")?>`,
                content: `<?=_("Reset deadline?")?>`,
                cb_close: () => plugin.unedit(),
                cb_ok: () => plugin.resetDeadline(),
              });
            });
          } else {
            plugin.openDatePicker();
          }
        }
      } else if (el.matches('#postitViewPopup .modal-body *')) {
        if (el.tagName === 'A' || el.closest('A')) {
          e.stopImmediatePropagation();
          _displayOpenLinkMenu(e, {noEditItem: true});
        }
      }
    });

  // EVENT "click"
  document.body.addEventListener ("click", (e)=>
    {
      const el = e.target;

      // EVENT "click" on plugs menu
      if (el.matches (".plug-label li,.plug-label li *"))
      {
        const item = el.tagName=="li"?el:el.closest("li");
        const label = item.closest ("div");
        const $wall = S.getCurrent ("wall");
        const ids = label.previousSibling.id.match(/^_(\d+)\-(\d+)$/);
        const startId = parseInt(ids[1]);
        const endId = parseInt(ids[2]);
        const startPlugin = $wall.find(`.postit[data-id="postit-${startId}"]`)
            .postit("getClass");
        const defaultLabel =
            H.htmlEscape(label.querySelector("span").innerText);

        // LOCAL FUNCTION __unedit ()
        const __unedit = ()=>
          {
            const toSave = {};

            toSave[startId] = startPlugin.element;
            toSave[endId] =
              $wall.find (`.postit[data-id="postit-${endId}"]`);

            S.set ("plugs-to-save", toSave);
            startPlugin.unedit ();
          };

        switch (item.dataset.action)
        {
          case "rename":
            startPlugin.edit ({}, () => {
              H.openConfirmPopover ({
                type: 'update',
                item: $(label),
                title: `<i class="fas fa-bezier-curve fa-fw"></i> <?=_("Relation name")?>`,
                content: `<input type="text" class="form-control form-control-sm" value="${defaultLabel}" maxlength="<?=DbCache::getFieldLength('postits_plugs', 'label')?>">`,
                cb_close: __unedit,
                cb_ok: ($p) => {
                  const label = $p.find('input').val().trim();

                  if (label !== defaultLabel) {
                    startPlugin.updatePlugLabel ({label, endId});
                  }
                }
              });
            });
            break;

          case "delete":

            startPlugin.edit ({}, ()=>
              {
                H.openConfirmPopover ({
                  item: $(label),
                  placement: "left",
                  title: `<i class="fas fa-trash fa-fw"></i> <?=_("Delete")?>`,
                  content: `<?=_("Delete this relation?")?>`,
                  cb_close: __unedit,
                  cb_ok: () => startPlugin.removePlug (endId)
                });
              });

            break;

          case "position-auto":

            startPlugin.edit ({}, ()=>
              {
                const p = startPlugin.getPlugById (endId);

                _deleteRelatedPlugs (p);
                p.obj.show ();

                startPlugin.resetPlugLabelPosition (label);
                startPlugin.repositionPlugs ();
                __unedit ();
              });
            break;

          case "properties":

            startPlugin.openPlugProperties (startPlugin.getPlugById(endId));
            break;
        }
      }
    });

    // EVENT "mousedown"
    walls.addEventListener ("mousedown", (e)=>
      {
        const el = e.target;

        // EVENT "mousedown" on postit tags
        if (el.matches (".postit-tags,.postit-tags *"))
        {
          e.stopImmediatePropagation ();

          if (H.disabledEvent (!H.checkAccess ("<?=WPT_WRIGHTS_RW?>")))
          {
            H.preventDefault (e);
            return;
          }

          $(el.closest(".postit")).postit ("edit", {},
            () => S.getCurrent("tpick").tpick ("open", e));
        }
      });

    // Create input to upload postit images
    H.createUploadElement({
      attrs: {id: 'postit-picture', accept: '.jpeg,.jpg,.gif,.png'},
      onChange: (e) => {
        const el = e.target;
        const fname = el.files[0].name;
    
        // LOCAL FUNCTION __error_cb ()
        const __error_cb = (r) => {
          if (r) {
            H.displayMsg({
              title: `<?=_("Note")?>`,
              type: 'warning',
              msg: r.error || r
            });
          }
        };
    
        H.getUploadedFiles(el.files, '\.(jpe?g|gif|png)$', (e, file) => {
          el.value = '';

          if (H.checkUploadFileSize({size: e.total, cb_msg: __error_cb}) &&
              e.target.result) {
            const wallId = S.getCurrent('wall').wall('getId');
            const $postit = S.getCurrent('postit');
            const postitId = $postit.postit('getId');
            const cellId = $postit.postit('getCellId');

            (async () => {
              const r = await H.fetch (
                'PUT',
                `wall/${wallId}/cell/${cellId}/postit/${postitId}/picture`,
                {
                  name: file.name,
                  size: file.size,
                  item_type: file.type,
                  content: e.target.result,
                });

              if (!r || r.error) {
                __error_cb(r);
              } else {
                const $f = $('.tox-dialog');

                $postit[0].dataset.hasuploadedpictures = true;

                //FIXME
                // If uploaded img is too large TinyMCE plugin
                // take too much time to gather informations
                // about it. If user close popup before that,
                // img is inserted without width/height
                $f.find('input:eq(1)').val(r.width);
                $f.find('input:eq(2)').val(r.height);

                S.get('tinymce-callback')(r.link);

                setTimeout(() => {
                  if (!$f.find("input:eq(0)").val ()) {
                    __error_cb (`<?=_("Sorry, there is a compatibility issue with your browser when it comes to uploading notes images...")?>`);
                  }
                }, 0);
              }
            })();
          }
        },
        null,
        __error_cb);
      },
    });

    // EVENT hide.bs.modal on postit popup
    document.getElementById("postitUpdatePopup")
      .addEventListener("hide.bs.modal", (e)=>
      {
        const el = e.target,
              data = S.get ("postit-data");

        // Return if we are closing the postit modal from the confirmation
        // popup
        if (data && data.closing) return;

        const $popup = $(el),
              plugin = S.getCurrent("postit").postit ("getClass"),
              progress = $popup.find(".slider").slider ("value"),
              title = $("#postitUpdatePopupTitle").val (),
              content = tinymce.activeEditor.getContent ();

        // LOCAL FUNCTION cb_close ()
        const __close = (forceHide = false) =>
          {
            S.set ("postit-data", {closing: true});

            //FIXME
            $(".tox-toolbar__overflow").hide ();
            $(".tox-menu").hide ();

            $popup.find("input").val ("");
            plugin.unedit ();

            if (forceHide)
              $popup.modal ("hide");

            S.unset ("postit-data");

            tinymce.activeEditor.resetContent ();

            // Stop focusin event filtering
            document.removeEventListener ("focusin", _focusinInFilter);
          };

          // If there is pending changes, ask confirmation to user
          if (data && (
            // Content change detection
            tinymce.activeEditor.isDirty () ||
            // Title change detection
            H.htmlEscape(data.title) != H.htmlEscape(title) ||
            // Progress change detection
            data.progress != progress))
          {
            H.preventDefault (e);
            H.openConfirmPopup ({
              type: "save-postits-changes",
              icon: "save",
              content: `<?=_("Save changes?")?>`,
              cb_ok: () => el.querySelector('.btn-primary').click(),
              cb_close: () => __close(true)
            });

            S.set ("postit-data", data);
          }
          else
            __close ();
      });
});

<?php echo $Plugin->getFooter()?>
