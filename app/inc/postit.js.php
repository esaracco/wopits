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

?>

(() => {
'use strict';

// TODO Class
const _plugRabbit = {
  line: null,
  // FUNCTION cancelPlugAction()
  cancelPlugAction: () => {
    if (_plugRabbit.line) {
      document.removeEventListener('keydown', _plugRabbit.escapeEvent);
      document.removeEventListener('mousedown', _plugRabbit.mousedownEvent);
      document.removeEventListener('mousemove', _plugRabbit.mousemoveEvent);

      _plugRabbit.line.remove();
      _plugRabbit.line = null;

      document.getElementById('plug-rabbit').remove();
    }

    // Unedit postit
    S.get('link-from').obj.unedit();

    // Prevents post-it editing events from being triggered during 500ms
    // Sort of preventDefault() cross-type events
    S.set('link-from', true, 500);
  },
  // EVENT mousedown on destination postit for relation creation
  mousedownEvent: (e) => {
    const endTag = e.target.closest('.postit');

    e.stopImmediatePropagation();
    H.preventDefault(e);

    if (!endTag) return _plugRabbit.cancelPlugAction();

    const from = S.get('link-from');
    const start = from.obj;
    const end = P.get(endTag, 'postit');
    const endId = end.getId();

    if (from.id !== endId && !end.plugExists(from.id)) {
      end.edit({plugend: true}, () => {
        start.addPlug({
          endId,
          startId: from.id,
          label: {name: '...'},
          obj: end.getPlugTemplate({
            start: start.tag,
            end: endTag,
            hide: true,
            label: '...',
          }),
        }, Boolean(S.get('zoom-level')));

        _plugRabbit.cancelPlugAction();
      });
    } else if (from.id !== endId) {
      _plugRabbit.cancelPlugAction();

      H.displayMsg({
        type: 'warning',
        msg: `<?=_("The relation already exists")?>`,
      });
    } else {
      _plugRabbit.cancelPlugAction();
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
      _plugRabbit.cancelPlugAction();
    }
  },
};

// EVENT focusin
// To fix tinymce dialogs compatibility with bootstrap popups
const _focusinInFilter = (e) =>
  e.target.closest('.tox-dialog,.tox-tiered-menu') &&
    e.stopImmediatePropagation();

// CLASS _Menu
class _Menu {
  // METHOD constructor()
  constructor(postit) {
    const postitTag = postit.tag;

    this.header = postitTag.querySelector('.postit-header');
    this.btn = postitTag.querySelector('.btn-menu i');
    this.postit = postit;
    this.tag = H.createElement('div',
      {className: 'postit-menu right'},
      null,
      `<?=Wopits\Helper::getPostitMenuItems()?>`);

    this.init();
    postitTag.insertBefore(this.tag, postitTag.firstChild);
  }

  // METHOD init()
  init() {
    const postitSettings = this.postit.settings;

    if (!postitSettings.wall.isShared() || 
        (!this.postit.canWrite() && !postitSettings.attachmentscount)) {
      H.hide(this.tag.querySelector(`[data-action="pwork"]`));
    }
  }

  // METHOD show()
  show() {
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
    // H.openPopupLayer(() => this.btn.click());
    H.show(this.tag);
  }

  // METHOD destroy()
  destroy() {
    this.header.classList.remove('menu');
    this.btn.classList.replace(
        'fa-caret-square-left', 'fa-caret-square-down');
    this.btn.classList.replace('fas', 'far');
    this.tag.remove();
  }

  // METHOD setPosition()
  setPosition(pos) {
    const menuCls = this.tag.classList;
    if (pos === 'left') {
      menuCls.replace('right', 'left');
    } else {
      menuCls.replace('left', 'right');
    }
  }

  // METHOD getWidth()
  getWidth() {
    return this.tag.offsetWidth;
  }
}

/////////////////////////////////// PLUGIN ////////////////////////////////////

P.register('postit', class extends Wpt_pluginWallElement {
  // METHOD constructor()
  constructor(settings) {
    super(settings);
    const tag = this.tag;
    const wall = settings.wall;
    const wallTag = wall.tag;
    const writeAccess = this.canWrite();

    this.originalObject = null;

    settings.plugs = [];
    tag.dataset.id = `postit-${settings.id}`;
    tag.dataset.order = settings.item_order;
    tag.dataset.tags = settings.tags || '';

    tag.className = settings.classes || 'postit';

    // If the deadline has passed
    if (settings.obsolete) {
      tag.classList.add('obsolete');
    }

    tag.style.visibility = 'hidden';
    tag.style.top = `${settings.item_top}px`;
    tag.style.left = `${settings.item_left}px`;
    tag.style.width = `${settings.width}px`;
    tag.style.height = `${settings.height}px`;

    // Append if the user have write access
    if (writeAccess) {
      tag.appendChild(H.createElement('div',
        {className: 'btn-menu'}, null,
        `<i class="far fa-caret-square-down"></i>`));
    }

    // Append header, dates, attachment count and tags
    tag.append(
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
        `<div class="creation" title="<?=_("Creation date")?>"><span>${moment.tz(U.get('timezone')).format("Y-MM-DD")}</span></div><div class="end" title="<?=_("Deadline")?>"><i class="fas fa-times-circle fa-lg"></i> <span>...</span></div>`),
      // Top icons
      H.createElement('div', {className: 'topicon'}, null,
        `<div class="pwork" title="<?=_("Users involved")?>"></div><div class="pcomm" title="<?=_("Comments")?>"></div><div class="patt" title="<?=_("Attached files")?>"></div>`),
      // Tags
      H.createElement('div', {className: 'postit-tags'}, null,
        `${settings.tags ? S.getCurrent('tpick').getHTMLFromString(settings.tags) : ''}`)
    );

    tag.querySelector('.postit-edit')
      .style.maxHeight = `${settings.height - 40}px`;

    if (writeAccess) {
      const postitEdit = tag.querySelector('.postit-edit');

      $(tag)
        // TODO Do not use jQuery here
        .draggable({
          // If zoom is active, disable note dragging
          disabled: Boolean(S.get('zoom-level')),
          distance: 10,
          appendTo: 'parent',
          revert: 'invalid',
          cursor: 'pointer',
          cancel: '.postit-tags',
          containment: $(wallTag.querySelector('tbody.wpt')),
          scrollSensitivity: 50,
          scope: 'dzone',
          start: (e, ui) => {
            S.set('revertData', {
              revert: false,
              top: tag.offsetTop,
              left: tag.offsetLeft,
            });

            this.hideSHowPlugs = !S.getCurrent('filters').tag
              .classList.contains('plugs-hidden');

            if (this.hideSHowPlugs) {
              this.hidePlugs();
            }

            this.edit({}, null, () => S.get('revertData').revert = true);
            },
          stop: (e, ui) => {
            if (S.get('revertData').revert) {
              $(e.target).draggable('cancel');
            }

            if (this.hideSHowPlugs) {
              this.showPlugs();
            }
            delete this.hideSHowPlugs;
            this.dropStop();
          }
        })
        // TODO Do not use jQuery here
        .resizable({
          // If zoom is active, disable note resizing
          disabled: Boolean(S.get('zoom-level')),
          handles: H.haveMouse() ? 'all' : 'n, e, w, ne, se, sw, nw',
          autoHide: false,
          resize: (e, ui) => {
            // Refresh relations position
            this.repositionPlugs();

            this.fixEditHeight();

            if (S.get('revertData').revert) {
              return false;
            }
          },
          start: (e, ui) => {
            const editable = wallTag.querySelectorAll('.editable');

            // Cancel all editable
            // (because blur event is not triggered on resizing)
            if (editable.length) {
              editable.forEach((el) => P.get(el, 'editable').cancel());
            }
  
            S.set('revertData', {
              revert: false,
              width: tag.clientWidth,
              height: tag.clientHeight,
            });

            this.hidePlugs();
            this.edit({}, null, () => S.get('revertData').revert = true);
          },
          stop: (e, ui) => {
            const revertData = S.get('revertData');

            S.set('dragging', true, 500);

            this.showPlugs();

            if (revertData.revert) {
              tag.style.width = `${revertData.width}px`;
              tag.style.height = `${revertData.height}px`;

              this.cancelEdit();
              this.repositionPlugs();
            }
            else {
              H.waitForDOMUpdate(() => {
                P.get(ui.element[0].parentNode, 'cell').reorganize();
                this.unedit();
              });
            }
          }
        });

      // LOCAL FUNCTION __dblclick()
      const __dblclick = (e) => {
        if (e.target.tagName === 'A' || H.disabledEvent(e.ctrlKey)) {
          return false;
        }

        this.openPostit();
      }; 

      if ($.support.touch) {
        // EVENT dbltap on note content
        postitEdit.addEventListener('dbltap', ({detail: e}) => __dblclick(e));
      } else {
        // EVENT dblclick on note content
        postitEdit.addEventListener('dblclick', __dblclick);
      }

      // Make note title editable
      tag.querySelectorAll('.title').forEach((el) => {
        P.create(el, 'editable', {
          wall,
          container: tag.querySelector('.postit-header'),
          maxLength: <?=DbCache::getFieldLength('postits', 'title')?>,
          triggerTags: ['span', 'div'],
          fontSize: '14px',
          callbacks: {
            before: (ed, v) => v === '...' && ed.setValue(''),
            edit: (cb) => {
              if (H.disabledEvent()) return false;

              this.edit({}, cb);
            },
            unedit: () => this.unedit(),
            update: (v) => {
              this.setTitle(v);
              this.unedit();
            },
          }
        });
      });
    }

    // Initialize topicons plugins
    const pluginArgs = {
      postit: this,
      readonly: !writeAccess,
      shared: wall.isShared(),
    };

    settings.plugins = {
      // Attachments
      patt: P.getOrCreate(tag.querySelector('.patt'), 'patt', {
        ...pluginArgs,
        count: settings.attachmentscount,
      }),
      // Workers
      pwork: P.getOrCreate(tag.querySelector('.pwork'), 'pwork', {
        ...pluginArgs,
        count: settings.workerscount,
      }),
      // Comments
      pcomm: P.getOrCreate(tag.querySelector('.pcomm'), 'pcomm', {
        ...pluginArgs,
        count: settings.commentscount,
      }),
    }

    // If we are updating a note
    if (settings.creationdate) {
      this.update(settings);
    }
  }

  // METHOD getPlugin()
  getPlugin(type) {
    return this.settings.plugins[type];
  }

  // METHOD dropStop()
  dropStop() {
    if (S.get('dragging')) return;

    S.set('dragging', true, 500);

    if (S.get('revertData').revert) {
      this.setPosition(S.get('revertData'));
      this.cancelEdit();
    } else {
      const settings = this.settings;
      const tag = this.tag;

      if (settings.cell.tag.dataset.id !== tag.parentNode.dataset.id) {
        const postitEdit = tag.querySelector('.postit-edit');
        const content = postitEdit.innerHTML;

        // If the postit has been dropped into another cell
        settings.cell = P.get(tag.parentNode, 'cell');

        // Update content cells references if any (i.e. pictures)
        if (content.includes('/cell/')) {
          postitEdit.innerHTML =
              content.replace(/\/cell\/\d+\//g, `/cell/${settings.cellId}/`);
        }
      }

      S.getCurrent('mmenu').update(settings.id, this);

      this.unedit();
    }

    // Refresh relations position
    this.repositionPlugs();
  }

  // METHOD openPlugProperties()
  openPlugProperties(plug) {
    this.edit({}, () => H.loadPopup('plugprop', {
      open: false,
      cb: (p) => P.getOrCreate(p, 'plugprop').open(this, plug),
    }));
  }

  // METHOD openDatePicker()
  openDatePicker() {
    this.edit({}, () => H.loadPopup('dpick', {
      open: false,
      cb: (p) => P.getOrCreate(p, 'dpick').open(),
    }));
  }

  // METHOD openPostit()
  openPostit(item) {
    // Open modal with read rights only
    if (!this.canWrite()) {
      if (!this.openAskForExternalRefPopup({item})) {
        this.open();
      }
    } else {
      this.edit({}, () => {
        if (!this.openAskForExternalRefPopup({
               item,
               onClose: (btn) => (btn !== 'yes') && this.unedit(),
             })) {
          this.open();
        }
      });
    }
  }

  // METHOD open()
  open() {
    const tag = this.tag;
    const progress = Number(tag.dataset.progress || 0);
    const title = this.getTitle();
    const content = tag.querySelector('.postit-edit').innerHTML || '';

    // LOCAL FUNCTION __getMaxEditModalWidth()
    const __getMaxEditModalWidth = (content) => {
      let maxW = 0;
      let tmp;
    
      (content.match(/<[a-z]+\s[^>]+>/g) || []).forEach((tag) => {
        if ( (tmp = tag.match(/width\s*[=:]\s*"?(\d+)"?/)) ) {
          const w = Number(tmp[1]);
    
          if (w > maxW) {
            maxW = w;
          }
        }
      });
    
      return maxW ? maxW + 5 : 0;
    };

    if (this.canWrite()) {
      const popup = document.getElementById('postitUpdatePopup');

      S.set('postit-data', {
        progress,
        title: (title !== '...') ? title.replace(/&amp;/g, '&') : '',
      });

      // Set progress slider
      P.get(popup.querySelector('.slider'), 'slider').value(progress, true);

      // Set title
      document.getElementById('postitUpdatePopupTitle').value =
        S.get('postit-data').title;

      //FIXME
      $('.tox-toolbar__overflow').show();
      $('.tox-mbtn--active').removeClass('tox-mbtn--active');

      // Check if post-it content has pictures
      if (content.match(/\/postit\/\d+\/picture\/\d+/)) {
        tag.dataset.hadpictures = true;
      } else {
        tag.removeAttribute('data-hadpictures');
      }

      // Filter the focusin event
      document.addEventListener('focusin', _focusinInFilter);

      tinymce.activeEditor.setContent(content);

      H.openModal({
        item: document.getElementById('postitUpdatePopup'),
        width: __getMaxEditModalWidth(content),
      });
    } else {
      this.setCurrent();

      H.loadPopup('postitView', {
        open: false,
        cb: (p) => {
          p.querySelector('.modal-body').innerHTML =
            content ? content : `<i><?=_("No content")?></i>`;

          p.querySelector('.modal-title').innerHTML =
            `<i class="fas fa-sticky-note"></i> ${title}`;

          H.openModal({item: p, width: __getMaxEditModalWidth(content)});
        }
      });
    }
  }

  // METHOD getMin()
  getMin() {
    return this.settings.cell.tag.querySelector(
      `.postit-min[data-id="postit-${this.settings.id}"]`);
  }

  // METHOD getNormal()
  getNormal() {
    return this.settings.cell.tag.querySelector(
      `.postit[data-id="postit-${this.settings.id}"]`);
  }

  // METHOD displayAlert()
  displayAlert(type) {
    const data = this.tag.dataset;
    let content;

    // Scroll to the note if needed
    H.setViewToElement(this.tag);

    H.waitForDOMUpdate(() => {
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
          } else if (this.tag.classList.contains('obsolete')) {
            content = `<?=_("This note has expired")?>`;
          } else {
            const a = moment.unix(data.deadlineepoch);
            const b = moment(new Date());
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
        item: min || this.tag,
        title, 
        content,
      });
    });
  }

  // METHOD remove()
  remove() {
    const tag = this.tag;
    const min = this.getMin();

    // Remove min postit (stack mode display) if needed
    if (min) {
      min.remove();
      this.settings.cell.decCount();
    }

    this.removePlugs(true);
    P.remove(tag, 'postit');
    tag.remove();
    S.getCurrent('mmenu').remove(this.settings.id);

    this.getPlugin('pcomm').close();
  }

  // METHOD havePlugs()
  havePlugs() {
    return this.settings.plugs.length;
  }

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
  }

  // METHOD getPlugDropShadowTemplate()
  getPlugDropShadowTemplate(color) {
    return {dy: 10, color: H.lightenDarkenColor(color, -20)};
  }

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
  }

  // METHOD applyZoomToPlugs()
  applyZoomToPlugs(z) {
    const reset = (z === 1);

    this.settings.plugs.forEach((p) => {
      const labelStyle = p.labelObj[0].style;
      const size = Math.trunc(p.obj.line_size * z) || 1;
      const gr = Math.trunc((100 * (size * 100 / p.obj.line_size)) / 100);

      labelStyle.transformOrigin = reset ? null : 'top left';
      labelStyle.transform = reset ? null : `scale(${z})`;
      p.obj.size = size;

      if (p.customPos) {
        const g = reset ? 'auto' : gr;

        p.related.forEach((r) => r.setOptions({
          size,
          startSocketGravity: g,
          endSocketGravity: g,
        }));
      }
    });
  }

  // METHOD applyZoom()
  applyZoom() {
    const z = S.get('zoom-level') || 1;

    S.getCurrent('wall').tag.querySelectorAll('.postit.with-plugs').forEach(
      (el) => P.get(el, 'postit').applyZoomToPlugs(z));
  }

  // METHOD applyThemeToPlugs()
  applyThemeToPlugs(color) {
    // LOCAL FUNCTION __apply()
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
  }

  // METHOD applyTheme()
  applyTheme() {
    S.reset('plugColor');

    const color = S.getCurrent('plugColor');

    S.getCurrent('wall').tag.querySelectorAll('.postit.with-plugs')
      .forEach((el) => P.get(el, 'postit').applyThemeToPlugs(color));
  }

  // METHOD getWallHeadersShift()
  getWallHeadersShift() {
    const hs = this.settings.wall.tag.dataset.headersshift;

    return hs ? JSON.parse(hs) : null;
  }

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
  }

  // METHOD resetPlugLabelPosition()
  resetPlugLabelPosition(label) {
    label.removeAttribute('data-pos');
    label.removeAttribute('data-origtop');
    label.removeAttribute('data-origleft');

    if (this.canWrite()) {
      H.hide(label.querySelector('i.fa-thumbtack'));
      H.hide(label.querySelector(`li[data-action="position-auto"]`));
    }
  }

  // METHOD updatePlugProperties()
  updatePlugProperties(ll) {
    const id = ll.endId || this.settings.id;
    const defaultLineColor = S.getCurrent('plugColor');

    for (const plug of this.settings.plugs) {
      //FIXME == ===
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
  }

  // METHOD deleteRelatedPlugs()
  deleteRelatedPlugs(plug) {
    plug.related.forEach((r) => r.remove());
    plug.related = [];
    plug.customPos = false;
  }

  // METHOD updatePlugLabel()
  updatePlugLabel(args) {
    const label = H.noHTML(args.label);
    const wPos = this.settings.wall.tag.getBoundingClientRect();
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
          H.show(pl.querySelector('i.fa-thumbtack'));
          H.show(pl.querySelector(`li[data-action="position-auto"]`));
        }

        if (!p.customPos) {
          this.repositionPlugLabel(pl, args.top, args.left, wPos);
          p.related = this.createRelatedPlugs(p);
          p.obj.hide();
        }
      } else if (p.customPos) {
        this.resetPlugLabelPosition(pl);
        this.deleteRelatedPlugs(p);
        p.obj.show();
      }
    }

    this.repositionPlugs();
  }

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
  }

  // METHOD addPlugLabel()
  addPlugLabel(plug, svg, applyZoom) {
    const wPos = this.settings.wall.tag.getBoundingClientRect();
    const canWrite = this.canWrite();

    svg = document.querySelector(`#_${plug.startId}-${plug.endId}`);

    const pos = plug.label.top ?
      {
        top: plug.label.top + wPos.top,
        left: plug.label.left + wPos.left,
      } : svg.querySelector('text').getBoundingClientRect();
    const start = P.get(plug.obj.start, 'postit');
    const renameItem = H.haveMouse() ? `<li data-action="rename"><a class="dropdown-item" href="#"><i class="fa-fw fas fa-edit"></i> <?=_("Rename")?></a></li>` : '';
    const menu = `<ul class="dropdown-menu shadow">${renameItem}<li data-action="delete"><a class="dropdown-item" href="#"><i class="fa-fw fas fa-trash"></i> <?=_("Delete")?></a></li><li data-action="properties"><a class="dropdown-item" href="#"><i class="fa-fw fas fa-cogs"></i> <?=_("Properties")?></a></li><li data-action="position-auto"><a class="dropdown-item" href="#"><i class="fa-fw fas fa-magic"></i> <?=_("Auto position")?></a></li></ul>`;
    const label = H.createElement('div',
      {className: 'plug-label dropdown submenu inset', style: `top:${pos.top}px;left:${pos.left}px`},
      plug.label.top ? {pos: 1} : null,
      `${canWrite?`<i class="fas fa-thumbtack fa-xs"></i>`:""}<div ${canWrite ? 'data-bs-toggle="dropdown"' : ''} class="dropdown-toggle"><span>${plug.label.name !== '...' ? H.noHTML(plug.label.name) : '<i class="fas fa-ellipsis-h"></i>'}</span></div>${canWrite ? menu : ''}`);

    plug.labelObj = $(document.body.appendChild(label));

    if (plug.label.top) {
      label.dataset.origtop = plug.label.top;
      label.dataset.origleft = plug.label.left;

      if (canWrite) {
        H.show(label.querySelector('i.fa-thumbtack'));
      }

      plug.related = this.createRelatedPlugs(plug);
    } else {
      if (canWrite) {
        H.hide(label.querySelector(`li[data-action="position-auto"]`));
      }

      plug.related = [];
      plug.customPos = false;
      plug.obj.show('none');
    }

    if (applyZoom) {
      label.style.transformOrigin = 'top left';
      label.style.transform = `scale(${S.get('zoom-level') || 1})`;
    }

    if (canWrite) {
      // TODO Do not use jQuery here
      plug.labelObj.draggable({
        disabled: applyZoom,
        distance: 10,
        containment: $(S.getCurrent('wall').tag.querySelector('tbody.wpt')),
        scroll: false,
        start: (e, ui) => {
          S.set('revertData', {
            revert: false,
            top: plug.labelObj[0].offsetTop,
            left: plug.labelObj[0].offsetLeft
          });

          start.edit({},
            // success cb
            () => {
              if (!plug.customPos) {
                plug.related = this.createRelatedPlugs(plug);
                plug.obj.hide();
              }
              plug.related.forEach((r) => r.hide('none'));
            },
            // error cb
            () => S.get('revertData').revert = true);
        },
        stop: (e, ui) => {
          const revertData = S.get('revertData');

          S.set('dragging', true, 500);

          if (revertData.revert) {
            $(label).draggable('cancel');

            plug.labelObj[0].style.top = `${revertData.top}px`;
            plug.labelObj[0].style.left = `${revertData.left}px`;

            start.cancelEdit();
          } else {
            const wPos = S.getCurrent('wall').tag.getBoundingClientRect();
            const lbPos = label.getBoundingClientRect();
            const z = S.get('zoom-level') || 1;
            const toSave = {};

            label.dataset.changed = 1;
            label.dataset.pos = 1;

            H.show(label.querySelector('i.fa-thumbtack'));
            H.hide(label.querySelector(`li[data-action="position-auto"]`));

            label.dataset.origtop = Number((lbPos.top - wPos.top) / z);
            label.dataset.origleft = Number((lbPos.left - wPos.left) / z);

            toSave[plug.startId] = P.get(plug.obj.start, 'postit');
            toSave[plug.endId] = P.get(plug.obj.end, 'postit');

            S.set('plugs-to-save', toSave);
            start.unedit();
          }

          setTimeout(() =>
            plug.related.forEach((el) => el.position().show()), 150);
        }
      });
    }
  }

  // METHOD addPlug()
  addPlug(plug, applyZoom) {
    const startTag = this.tag;
    const end = P.get(plug.obj.end, 'postit');

    // Associate SVG line to plug and set its label
    const svg = document.querySelector('.leader-line:last-child');
    svg.id = `_${plug.startId}-${plug.endId}`;
    this.addPlugLabel(plug, svg, applyZoom);

    // Register plug on start point postit (current plugin)
    this.settings.plugs.push(plug);
    startTag.classList.add('with-plugs');

    // Register plug on end point postit
    end.settings.plugs.push(plug);
  }

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
      this.tag.classList.remove('with-plugs');
    }
  }

  // METHOD plugExists()
  plugExists(plugId) {
    for (const plug of this.settings.plugs) {
      // FIXME == ===
      if (plug.startId == plugId || plug.endId == plugId) {
        return true;
      }
    }
  }

  // METHOD getPlugById()
  getPlugById(plugId) {
    for (const plug of this.settings.plugs) {
      // FIXME == ===
      if (plug.endId == plugId) {
        return plug;
      }
    }
  }

  // METHOD _removePlug()
  _removePlug(plug, toDefrag) {
    toDefrag[plug.startId] = plug.obj.start;
    toDefrag[plug.endId] = plug.obj.end;
  
    // Remove label
    plug.labelObj.remove();
    plug.labelObj = null;
  
    // Remove related lines
    if (plug.customPos) {
      this.deleteRelatedPlugs(plug);
    }
  
    plug.obj.remove();
    plug.obj = null;
  }

  // METHOD removePlug()
  removePlug(plug, noedit) {
    const toDefrag = {};

    if (typeof plug !== 'object') {
      plug = this.getPlugById(plug);
    }

    this._removePlug(plug, toDefrag);

    for (const id in toDefrag) {
      P.get(toDefrag[id], 'postit').defragPlugsArray();
    }

    if (!noedit) {
      S.set('plugs-to-save', toDefrag);
    }
  }

  // METHOD removePlugs()
  removePlugs(noedit) {
    const settings = this.settings;
    const toDefrag = {};

    settings.plugs.forEach((p) => this._removePlug(p, toDefrag));

    for (const id in toDefrag) {
      if (toDefrag[id]) {
        P.get(toDefrag[id], 'postit').defragPlugsArray();
      }
    }

    if (!noedit) {
      S.set('plugs-to-save', toDefrag);
    }

    settings.plugs = [];
    this.tag.classList.remove('with-plugs');
  }

  // METHOD hidePlugs()
  hidePlugs(ignoreDisplayMode = false) {
    if (!this.settings.wall) return;

    const postitId = this.settings.id;

    /// FIXME == ===
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
  }

  // METHOD showPlugs()
  showPlugs(ignoreDisplayMode = false) {
    if (!this.settings.wall) return;

    const postitId = this.settings.id;
    const wPos = this.settings.wall.tag.getBoundingClientRect();

    // FIXME == ===
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

          p.related.forEach((r) => r.show('none').position());
        }
      }
    });
  }

  // METHOD repositionPlugs()
  repositionPlugs() {
    const wPos = this.settings.wall.tag.getBoundingClientRect();

    this.settings.plugs.forEach((p) => {
      const pl = p.labelObj[0];

      if (pl.dataset.pos) {
        this.repositionPlugLabel(
            pl, pl.dataset.origtop, pl.dataset.origleft, wPos);

        p.related.forEach((r) => r.position());
      } else {
        p.obj.position();

        const pos = document.querySelector(`#_${p.startId}-${p.endId} text`)
                        .getBoundingClientRect();

        pl.style.top = `${pos.top}px`;
        pl.style.left = `${pos.left}px`;
      }
    });
  }

  // METHOD getCellId()
  getCellId() {
    return this.settings.cellId;
  }

  // METHOD serializePlugs()
  serializePlugs() {
    const settings = this.settings;
    const defaultLineColor = S.getCurrent('plugColor');
    // Shift for plugs if headers are hidden
    const hs = this.getWallHeadersShift();
    let ret = {};

    // FIXME == ===
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
          ret[p.endId].top = parseInt(pl.dataset.origtop);
          ret[p.endId].left = parseInt(pl.dataset.origleft);

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
  }

  // METHOD serialize()
  serialize(args = {}) {
    const postits = [];
    const displayExternalRef = this.settings.wall.displayExternalRef();
    const z = S.get('zoom-level') || 1;

    (args.postits || [this.tag]).forEach((el) => {
      const postit = P.get(el, 'postit');
      const tag = postit.tag;
      const id = postit.getId();
      let data = {};

      if (tag.dataset.todelete) {
        data = {id, todelete: true};
      } else {
        const title = postit.getTitle();
        const content = tag.querySelector('.postit-edit').innerHTML;
        const classcolor = tag.className.match(/(color\-[a-z]+)/);
        const pattSpan = tag.querySelector('.patt span');
        const pworkSpan = tag.querySelector('.pwork span');
        const deadline = tag.dataset.deadlineepoch ?
                tag.dataset.deadlineepoch :
                tag.querySelector('.dates .end span').innerText.trim();
        const bbox = tag.getBoundingClientRect();
        const tags = [];

        tag.querySelectorAll('.postit-tags i').forEach(
          (el) => tags.push(el.dataset.tag));

        data = {
          id,
          width: Math.trunc(bbox.width / z),
          height: Math.trunc(bbox.height / z),
          item_top: tag.offsetTop < 0 ? 0 : Math.trunc(tag.offsetTop),
          item_left: tag.offsetLeft < 0 ? 0 : Math.trunc(tag.offsetLeft),
          item_order: parseInt(tag.dataset.order),
          classcolor: classcolor ?
            classcolor[0] : `color-<?=WPT_POSTIT_COLOR_DEFAULT?>`,
          title: (title === '...') ? '' : title,
          content: args.noPostitContent ? null :
                     displayExternalRef ?
                       content : postit.unblockExternalRef(content),
          tags: tags.length ? `,${tags.join(',')},` : null,
          deadline: (deadline === '...') ? '' : deadline,
          alertshift: (tag.dataset.deadlinealertshift !== undefined) ?
                        tag.dataset.deadlinealertshift : null,
          updatetz: tag.dataset.updatetz || null,
          obsolete: tag.classList.contains('obsolete'),
          attachmentscount: pattSpan ? pattSpan.innerText : 0,
          workerscount: pworkSpan ? pworkSpan.innerText : 0,
          plugs: postit.serializePlugs(),
          hadpictures: Boolean(tag.dataset.hadpictures),
          hasuploadedpictures: Boolean(tag.dataset.hasuploadedpictures),
          progress: parseInt(tag.dataset.progress || 0),
        };
      }

      postits.push(data);
    });

    return postits;
  }

  // METHOD showUserWriting()
  showUserWriting(user, isRelated) {
    const tag = this.tag;
    const id = this.settings.id;
    const canWrite = this.canWrite();

    // LOCAL FUNCTION __lock()
    const __lock = (el) =>
      el.classList.add('locked', isRelated ? 'related' : 'main');

    // LOCAL FUNCTION __addMain()
    const __addMain = () =>
      tag.insertBefore(
        H.createElement('div',
          {className: 'user-writing main'},
          {userid: user.id},
          `<i class="fas fa-user-edit blink"></i> ${user.name}`),
        tag.firstChild);

    this.closeMenu();

    // See cell::setPostitsUserWritingListMode()
    if (this.settings.cell.tag.classList.contains('list-mode')) {
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
      __lock(tag);

      if (isRelated) {
        tag.insertBefore(
          H.createElement('div',
            {className: 'user-writing'},
            {userid: user.id},
            `<i class="fas fa-user-lock"></i>`),
          tag.firstChild);
      } else {
        __addMain();
      }

      // Show a lock bubble on related items
      this.settings.plugs.forEach((p) => {
        p.labelObj[0].classList.add('locked');
        if (!isRelated) {
          P.get(p.obj[(p.startId !== id) ? 'start' : 'end'], 'postit')
            .showUserWriting(user, true);
        }
      });
    }
    else if (!isRelated) {
      __addMain();
    }
  }

  // METHOD setDeadline()
  setDeadline(args) {
    const tag = this.tag;
    const date = tag.querySelector('.dates .end');
    const {deadline, alertshift, timezone} = args;
    const reset = date.querySelector('i.fa-times-circle');
    let human;

    if (!deadline || isNaN(deadline)) {
      human = deadline || '...';
    } else {
      human = deadline ? U.formatDate(deadline, timezone) : '...';
    }

    date.querySelector('span').innerText = human;

    H.hide(reset);

    if (human === '...') {
      tag.classList.remove('obsolete');

      ['deadline', 'deadlinealertshift', 'deadlineepoch', 'updatetz']
        .forEach((k) => tag.removeAttribute(`data-${k}`));

      date.classList.remove('with-alert');
      date.classList.remove('obsolete');
    } else {
      tag.dataset.deadline = human;
      tag.dataset.deadlineepoch = deadline;

      if (alertshift !== undefined) {
        if (alertshift !== null) {
          tag.dataset.deadlinealertshift = alertshift;
          date.classList.add('with-alert');
        } else {
          tag.removeAttribute('data-deadlinealertshift');
          date.classList.remove('with-alert');
        }
      }

      if (this.canWrite()) {
        H.show(reset, 'inline-block');
      }
    }
  }

  // METHOD resetDeadline()
  resetDeadline() {
    this.setDeadline({deadline: '...'});
  }

  // METHOD setCreationDate()
  setCreationDate(v) {
    this.tag.querySelector('.dates .creation span')
      .innerText = v.trim();
  }

  // METHOD setProgress()
  setProgress(v) {
    const tag = this.tag;
    const container = tag.querySelector('.postit-progress-container');

    v = Number(v);

    if (!v) {
      tag.removeAttribute('data-progress');
      H.hide(container);
    } else {
      const progress = container.querySelector('.postit-progress');

      tag.dataset.progress = v;

      container.querySelector('span').innerText = `${v}%`;
      H.show(container);

      progress.style.height = `${v}%`;
      progress.style.backgroundColor = H.getProgressbarColor(v);
    }
  }

  // METHOD getTitle()
  getTitle() {
    return this.tag.querySelector('.postit-header span.title').innerHTML;
  }

  // METHOD setTitle()
  setTitle(v) {
    this.tag.querySelector('.postit-header span.title')
      .innerText = H.noHTML(v) || '...';
  }

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
  }

  // METHOD removeExternalRefIcon()
  removeExternalRefIcon(c) {
    c.querySelectorAll('i.externalref').forEach((el) => {
      el.parentNode.removeAttribute('title');
      el.remove();
    });
  }

  // METHOD setContent()
  setContent(newContent) {
    const tag = this.tag;
    const edit = tag.querySelector('.postit-edit');
    let setIcon = false;

    if (newContent !== edit.innerHTML) {
      const externalRef = this.getExternalRef(newContent);

      if (externalRef) {
        tag.dataset.haveexternalref = 1;

        if (!this.settings.wall.displayExternalRef()) {
          setIcon = true;
          newContent = this.blockExternalRef(newContent, externalRef);
        }
      } else {
        tag.removeAttribute('data-haveexternalref');
      }

      edit.innerHTML = newContent;

      if (setIcon) {
        this.addExternalRefIcon(edit);
      } else {
        this.removeExternalRefIcon(edit);
      }
    }
  }

  // METHOD openAskForExternalRefPopup()
  openAskForExternalRefPopup(args = {}) {
    let ask = (this.getExternalRef() &&
               !this.settings.wall.displayExternalRef());

    if (ask) {
      H.openConfirmPopover({
        item: args.item ? args.item : this.tag,
        title: `<i class="fas fa-link fa-fw"></i> <?=_("External content")?>`,
        content: `<?=_("This note contains external images or videos.")?><br><?=_("Would you like to load all external content for the current wall?")?>`,
        onClose: args.onClose,
        onConfirm: () => {
          this.settings.wall.displayExternalRef(1);
          this.open();
        }
      });
    }

    return ask;
  }

  // METHOD getExternalRef()
  getExternalRef(content) {
    return (content !== undefined) ?
             content.match(/(src\s*=\s*["']?http[^"'\s]+")/ig) :
             this.tag.dataset.haveexternalref;
  }

  // METHOD blockExternalRef()
  blockExternalRef(content, externalRef) {
    const el = this.tag.querySelector('.postit-edit');
    let c = content || el.innerHTML;

    if (!externalRef) {
      externalRef = this.getExternalRef(c);
    }

    if (externalRef) {
      externalRef.forEach((src) =>
        c = c.replace(new RegExp('[^\-]'+H.escapeRegex(src), 'g'),
              ` external-${src} `));

      if (content === undefined) {
        el.innerHTML = c;
        this.addExternalRefIcon(el);
      } else {
        return c;
      }
    }
  }

  // METHOD unblockExternalRef()
  unblockExternalRef(content) {
    if (content !== undefined) {
      return content.replace(/external\-src/g, 'src');
    } else {
      const tag = this.tag;

      tag.querySelectorAll('[external-src]').forEach((el) => {
        el.setAttribute('src', el.getAttribute('external-src'));
        el.removeAttribute('external-src');
      });

      this.removeExternalRefIcon(tag);
    }
  }

  // METHOD setPosition()
  setPosition({cellId, top, left}) {
    const tag = this.tag;

    if (cellId) {
      this.settings.cellId = cellId;
    }

    tag.style.top = `${top}px`;
    tag.style.left = `${left}px`;
  }

  // METHOD fixEditHeight()
  fixEditHeight() {
    const tag = this.tag;

    tag.querySelector('.postit-edit')
      .style.maxHeight = `${tag.offsetHeight - 40}px`;
  }

  // METHOD fixPosition()
  fixPosition(cPos) {
    const tag = this.tag;
    const phTop =
      tag.querySelector('.postit-header').getBoundingClientRect().top;
    const pW = tag.clientWidth;
    const pH = tag.clientHeight;
    const cH = cPos.height;
    const cW = cPos.width;
    let pPos = tag.getBoundingClientRect();

    // Postit is too high
    if (phTop < cPos.top) {
      tag.style.top = '20px';
    }
 
    // Postit is too much on left
    if (pPos.left < cPos.left) {
      tag.style.left = '1px';
    }
 
    // Postit is too much on right
    if (pPos.left + pW > cPos.left + cW + 1) {
      tag.style.left = `${cW - pW - 4}px`;
    }

    // Postit is too large
    if (pW > cW) {
      tag.style.left = 0;
      tag.style.width = `${cW - 2}px`;
    }
 
    pPos = tag.getBoundingClientRect();
 
    // Postit is too big
    if (pPos.top + pH > cPos.top + cH) {
      if (pH > cH) {
        tag.style.height = `${cH - 2}px`;
      }
 
      tag.style.top = `${cH - tag.clientHeight - 4}px`;
    }
  }

  // METHOD setClassColor()
  setClassColor(newClass, item) {
    if (item !== undefined && item === null) return;

    const el = item ? item : this.tag;
    const cls = el.className.replace(/color\-[a-z]+/, '');

    el.className = `${cls} ${newClass}`;
  }

  // METHOD setPopupColor()
  setPopupColor(popup) {
    const cls = this.tag.className.match(/color\-[a-z]+/)[0];

    this.setClassColor(cls, popup.querySelector('.modal-header'));
    this.setClassColor(cls, popup.querySelector('.modal-title'));
    this.setClassColor(cls, popup.querySelector('.modal-footer'));
  }

  // METHOD setCurrent()
  setCurrent() {
    S.reset('postit');
    this.tag.classList.add('current');
  }

  // METHOD unsetCurrent()
  unsetCurrent() {
    S.reset('postit');
    S.reset('pcomm');

    this.tag.classList.remove('current');
  }

  // METHOD insert()
  insert() {
    const tag = this.tag;
    const data = this.serialize()[0];

    H.request_ws(
      'PUT',
      `wall/${this.settings.wallId}/cell/${this.settings.cellId}/postit`,
      data,
      // success cb
      (d) => {
        if (d.error_msg) {
          H.displayMsg({type: 'warning', msg: d.error_msg});
        }
        tag.remove();
      },
      // error cb
      (d) => {
        //FIXME factorisation (cf. H.request_ws ())
        H.displayMsg({
          type: 'danger',
          msg: isNaN(d.error) ?
            d.error : `<?=_("Unknown error.<br>Please try again later.")?>`,
        });
        tag.remove();
      });
  }

  // METHOD save()
  save({content, progress, title}) {
    this.setContent(content);
    this.setProgress(progress);
    this.setTitle(title);

    this.tag.removeAttribute('data-uploadedpictures');

    S.unset('postit-data');
  }

  // METHOD update()
  update(d, cell) {
    const tag = this.tag;
    const tpick = S.getCurrent('tpick');

    // Change postit cell
    if (cell && cell.id !== this.settings.cellId) {
      if (this.settings.cell.tag.classList.contains('list-mode')) {
        this.settings.cell.decCount();
        this.getMin().remove();
      }

      this.settings.cell =
        cell.obj || P.get(this.settings.wall.tag
                       .querySelector(`td[data-id="cell-${cell.id}"]`), 'cell');
      this.settings.cellId = cell.id;

      this.settings.cell.tag.append(tag);

      if (this.settings.cell.tag.classList.contains('postit-mode')) {
        tag.style.visibility = 'visible';
      }
    }

    tag.style.top = `${d.item_top}px`;
    tag.style.left = `${d.item_left}px`;
    tag.style.width = `${d.width}px`;
    tag.style.height = `${d.height}px`;

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

    this.setCreationDate(d.creationdate ? U.formatDate(d.creationdate) : '');
    this.setDeadline(d);

    tag.dataset.order = d.item_order || 0;

    if (d.obsolete) {
      tag.classList.add('obsolete');
    } else {
      tag.classList.remove('obsolete');
    }

    if (!d.tags) {
      d.tags = '';
    }
    tag.dataset.tags = d.tags;

    tag.querySelector('.postit-tags').innerHTML =
      tpick.getHTMLFromString(d.tags);

    tpick.refreshPostitDataTag(this);

   H.waitForDOMUpdate(() => this.fixEditHeight());
  }

  // METHOD delete()
  delete() {
    S.reset();
    this.tag.dataset.todelete = true;
  }

  // METHOD edit()
  edit(args = {}, then, onError) {
    const data = {cellId: this.settings.cellId};

    if (!args.plugend) {
      this.setCurrent();
      this.originalObject = this.serialize()[0];
    }

    if (!this.settings.wall.isShared()) {
      then && then();
      return;
    }

    H.request_ws(
      'PUT',
      `wall/${this.settings.wallId}/editQueue/postit/${this.settings.id}`,
      data,
      // success cb
      (d) => {
        if (d.error_msg) {
          H.raiseError(() => {
            onError && onError();
            this.cancelEdit(args);
          }, d.error_msg);
        } else if (then) {
          then({...d, ...args});
        }
      },
      // error cb
      (d) => {
        onError && onError();
        this.cancelEdit(args);
      },
    );
  }

  // METHOD unedit()
  unedit(args = {}) {
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
          data.plugs.push(plugsToSave[id]
            .serialize({noPostitContent: true})[0]);
        }

        S.unset('plugs-to-save');

      // Postit update
      } else {
        data = this.serialize()[0];

        // Delete/update postit only if it has changed
        if ((data && data.todelete) ||
            H.objectHasChanged(this.originalObject, data, {hadpictures: 1})) {
          data.cellId = this.settings.cellId;
        } else if (!this.settings.wall.isShared()) {
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
        const tag = this.tag;

        this.cancelEdit(args);

        if (d.error_msg) {
          H.displayMsg({type: 'warning', msg: d.error_msg});
        } else if (data && data.todelete &&
                   tag.classList.contains('selected')) {
          S.getCurrent('mmenu').remove(this.settings.id);
        } else if (data && data.updatetz) {
          tag.removeAttribute('data-updatetz');
        }
      },
      // error cb
      () => this.cancelEdit(args));
  }

  // METHOD cancelEdit()
  cancelEdit(args = {}) {
    document.body.style.cursor = 'auto';

    if (!args.plugend) {
      const tag = this.tag;

      this.unsetCurrent();

      tag.removeAttribute('data-hasuploadedpictures');
      tag.removeAttribute('data-hadpictures');
    }

    if (!this.settings.id) {
      setTimeout(() => H.raiseError(null, `<?=_("The entire column/row was deleted while you were editing the note")?>`), 150);
    }
  }

  // METHOD closeMenu()
  closeMenu() {
    const tag = this.tag;

    if (tag.querySelector('.postit-menu')) {
      this.settings.Menu.destroy();
      delete this.settings.Menu;
    }
  }
});

//////////////////////////////////// INIT ////////////////////////////////////

document.addEventListener('DOMContentLoaded', () => {
  if (H.isLoginPage()) return;

  const walls = document.querySelector('.tab-content.walls');
  const wallsId = document.getElementById('walls');

  // LOCAL FUNCTION __displayOpenLinkMenu()
  const __displayOpenLinkMenu = (e, args = {}) => {
    const el = e.target;
    const link = (el.tagName === 'A') ? el : el.closest('a');
    const canWrite = H.checkAccess(<?=WPT_WRIGHTS_RW?>);
    const menu = H.createElement('div',
      {className: 'dropdown submenu submenu-link'}, null,
      `<ul class="dropdown-menu shadow show"><li data-action="open-link"><a class="dropdown-item" href="#"><i class="fa-fw fas fa-link"></i> <?=_("Open link")?></a></li>${args.noEditItem ? '' : `<li data-action="edit"><a class="dropdown-item" href="#"><i class="fa-fw fas fa-${canWrite ? 'edit' : 'eye'}"></i> ${canWrite ? `<?=_("Edit note")?>` : `<?=_("Open note")?>`}</a></li>`}</ul>`);
  
    H.preventDefault(e);
  
    // EVENT "click" on content links menu
    menu.addEventListener('click', (e) => {
      const li = (e.target.tagName === 'LI') ?
        e.target : e.target.closest('li');
  
      document.getElementById('popup-layer').click();
  
      if (li.dataset.action === 'open-link') {
        window.open(link.href, '_blank', 'noopener');
      } else {
        P.get(el.closest('.postit'), 'postit').openPostit();
      }
    });
  
    H.openPopupLayer(() => menu.remove());
  
    document.body.appendChild(menu);
  
    menu.style.top = `${e.clientY}px`;
    menu.style.left = `${e.clientX}px`;
  };

  // FIXME polyfill for TinyMCE (old Safari iOS)
  Promise.allSettled = Promise.allSettled || ((promises) => Promise.all(
    promises.map((p) => p
      .then(value => ({status: 'fulfilled', value}))
      .catch(reason => ({status: 'rejected', reason})))
  ));

  // Init text editor
  let locale = document.querySelector('html').dataset.fulllocale;
  tinymce.init({
    selector: '#postitUpdatePopupBody',
    promotion: false,
    content_style: 'p {margin: 0}',
    language: (locale !== 'en_US') ? locale : null,
    language_url: (locale !== 'en_US') ? `/libs/tinymce-${locale}.js` : null,
    branding: false,
    plugins: [
      'autoresize',
      'link',
      'image',
      'media',
      'charmap',
      'searchreplace',
      'visualchars',
      'fullscreen',
      'insertdatetime',
      'lists',
      'table',
    ],
    setup: (editor) => {
      // "change" event can be triggered twice, we use this var to
      // avoir that
      let _current = false;

      // Trick to catch 404 not found error on just added images
      // -> Is there a TinyMCE callback for that?
      editor.on('change', (e) => {
        if (_current) return;

        // LOCAL FUNCTION __testImage()
        const __testImage = (url, timeout = 10000) => {
          return new Promise((resolve, reject) => {
            const img = new Image();
            let timer;
        
            img.onerror = img.onabort = () => {
              clearTimeout(timer);
              reject('error');
            };
        
            img.onload = () => {
              clearTimeout(timer);
              resolve('success');
            };
        
            timer = setTimeout(() => {
              // reset .src to invalid URL so it stops previous
              // loading, but doesn't trigger new load
              img.src = '//!!!!/test.jpg';
              reject('timeout');
            }, timeout);
        
            img.src = url;
          });
        };

        _current = true;

        const tox = document.querySelector('.tox-dialog');
        let c = editor.getContent();

        // Remove unwanted images attributes
        if (c.match(/\s(srcset|alt)\s*=/i)) {
          //FIXME
          c = c.replace(/\s(srcset|alt)\s*=/ig, 'none=');
          editor.setContent(c);
        }

        // Check for img only if the TinyMCE dialog is open
        if (tox && H.isVisible(tox)) {
          (c.match(/<img\s[^>]+>/g)||[]).forEach((img) => {
            const tmp = img.match(/src="([^\"]+)"/);
            if (tmp) {
              const src = tmp[1];

              H.loader('show');
              __testImage(src)
                .then(
                  // Needed for some Safari on iOS that do not support
                  // Promise finally() callback.
                  () => H.loader('hide'),
                  () => {
                    H.loader('hide');
                    editor.setContent(
                      c.replace(new RegExp(H.quoteRegex(img)), ''));

                    // Return to the top of the modal if mobile device
                    if (!H.haveMouse()) {
                      $('#postitUpdatePopup').scrollTop(0);
                    }

                    H.displayMsg({
                      type: 'warning',
                      msg: `<?=_("The image %s was not available! It has been removed from the note content.")?>`.replace("%s", `«&nbsp;<i>${src}</i>&nbsp;»`),
                    });
                  })
                  .finally(() => _current = false);
              }
            });
        } else {
          _current = false;
        }
      });
    },

    // "media" plugin options.
    media_alt_source: false,
    media_poster: false,

    // "image" plugin options
    image_description: false,
    automatic_uploads: true,
    file_picker_types: 'image',
    file_picker_callback: (cb) => {
      S.set('tinymce-callback', cb);
      document.getElementById('postit-picture').click();
    },

    // "link" plugin options
    default_link_target: '_blank',
    link_assume_external_targets: true,
    link_default_protocol: 'https',
    link_title: false,
    target_list: false,

    visual: false,
    mobile: {menubar: 'edit view format insert'},
    menubar: 'edit view format insert',
    menu: {view: {title: `<?=_("View")?>`, items: 'fullscreen'}},
    toolbar: 'undo redo | bold italic underline | numlist bullist | alignleft aligncenter alignright alignjustify | link image | table',
    statusbar: false,
  });

  // EVENTS "mouseover" &  "touchstart" on postit
  // Sort of CSS ":hover", but with z-index persistence
  const __eventMOTS = (e) => {
    const el = e.target;

    if (!el.matches('.postit *')) return;

    const newP = el.closest('.postit');

    if (newP.classList.contains('hover')) return;

    const oldP = S.getCurrent('wall').tag.querySelector('.postit.hover');

    oldP && oldP.classList.remove('hover');
    newP.classList.add('hover');
  };

  if ($.support.touch) {
    wallsId.addEventListener('touchstart', __eventMOTS);
  } else {
    wallsId.addEventListener('mouseover', __eventMOTS);
  }

  // EVENT "click" on postit
  document.addEventListener('click', (e) => {
    const el = e.target;

    if (el.matches('.postit *')) {
      const tag = el.closest('.postit');
      const postit = P.get(tag, 'postit');

      // EVENT "click" ctrl+click on postit
      if (e.ctrlKey) {
        const mm = S.getCurrent('mmenu');

        e.stopImmediatePropagation();
        H.preventDefault(e);

        if (tag.classList.contains('selected')) {
          mm.remove(postit.getId());
        } else {
          mm.add(postit);
        }

      // EVENT "click" on postit content links
      } else if (el.matches('.postit-edit a[href],.postit-edit a[href] *')) {
        e.stopImmediatePropagation();
        if (e.ctrlKey || H.disabledEvent()) return;
        __displayOpenLinkMenu(e);

      // EVENT "click" on postit for READ-ONLY mode
      } else if (!H.checkAccess(<?=WPT_WRIGHTS_RW?>)) {
        if (H.disabledEvent()) {
          H.preventDefault(e);
          return;
        }

        if (!el.closest('.topicon')) {
          postit.openPostit();
        }

      // EVENT "click" on postit menu button
      } else if (el.matches('.btn-menu,.btn-menu *')) {
        e.stopImmediatePropagation();

        if (!H.checkAccess(<?=WPT_WRIGHTS_RW?>)) return;

        const btn = (el.tagName === 'DIV') ? el : el.closest('div');
        const ibtn = btn.querySelector('i');
        const settings = postit.settings;

        // Create postit menu and show it
        if (!settings.Menu) {
          settings.wall.closeAllMenus();
          settings.Menu = new _Menu(postit);
          settings.Menu.show ();
        // Destroy postit menu
        } else {
          settings.Menu.destroy();
          delete settings.Menu;
        }
      // EVENT "click" on postit menu buttons
      } else if (el.matches('.postit-menu,.postit-menu *')) {
        e.stopImmediatePropagation();

        if (el.tagName === 'DIV') return;

        const action =
            (el.tagName === 'SPAN' ? el : el.closest('span')).dataset.action;

        // To prevent race condition with draggable & resizable postits
        if (H.disabledEvent()) return;

        switch (action) {
          // OPEN postit edit popup
          case 'edit': return postit.openPostit();
          // OPEN deadline date picker popup
          case 'dpick': return postit.openDatePicker();
          // OPEN popup for attachments, comments or workers
          case 'patt':
          case 'pcomm':
          case 'pwork':
            return P.get(
              tag.querySelector(`.topicon .${action}`), action).open();
        }

        postit.edit({}, () => {
          switch (action) {
            // DELETE postit
            case 'delete':
              H.openConfirmPopover({
                item: tag.querySelector('.btn-menu'),
                placement: 'right',
                title: `<i class="fas fa-trash fa-fw"></i> <?=_("Delete")?>`,
                content: `<?=_("Delete this note?")?>`,
                onClose: () => postit.unedit(),
                onConfirm: () => postit.delete(),
              });
              break;
            // OPEN tags picker
            case 'tpick':
              return S.getCurrent('tpick').open(e);
            // OPEN color picker
            case 'cpick':
              const cpick = S.getCurrent('cpick');

              cpick.open({
                event: e,
                onClose: () =>
                  tag.dispatchEvent(new MouseEvent('mouseleave')),
                onSelect: (div) => {
                  cpick.getColorsList().forEach((c) => tag.classList.remove(c));
                  tag.classList.add(div.className);
                },
              });
              break;
            // ADD plug
            case 'add-plug':
              postit.closeMenu();

              S.set('link-from', {
                id: postit.getId(),
                obj: postit,
              });

              const rabbit = H.createElement('div',
                {
                  id: 'plug-rabbit',
                  style: `top:${e.clientY - 10}px;left:${e.clientX + 5}px`,
                },
                null,
                `<i class="fas fa-anchor fa-lg set"></i>`);

              document.body.prepend(rabbit);

              _plugRabbit.line = new LeaderLine(
                tag,
                rabbit,
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
              document.addEventListener('mousedown',
                  _plugRabbit.mousedownEvent);
              document.addEventListener('mousemove',
                  _plugRabbit.mousemoveEvent);
              break;
          }
        });

      // EVENT "click" on postit dates
      } else if (el.matches('.dates .end,.dates .end *')) {
        e.stopImmediatePropagation();

        if (H.disabledEvent (!H.checkAccess(<?=WPT_WRIGHTS_RW?>))) {
          H.preventDefault(e);
          return;
        }

        if (el.classList.contains('fa-times-circle')) {
          postit.edit({}, () => {
            H.openConfirmPopover({
              item: (el.tagName === 'DIV') ? el : el.closest('div'),
              title: `<i class="fas fa-trash fa-fw"></i> <?=_("Reset")?>`,
              content: `<?=_("Reset deadline?")?>`,
              onClose: () => postit.unedit(),
              onConfirm: () => postit.resetDeadline(),
            });
          });
        } else {
          postit.openDatePicker();
        }
      }
    } else if (el.matches('#postitViewPopup .modal-body *')) {
      if (el.tagName === 'A' || el.closest('A')) {
        e.stopImmediatePropagation();
        __displayOpenLinkMenu(e, {noEditItem: true});
      }
    }
  });

  // EVENT "click"
  document.addEventListener('click', (e) => {
    const el = e.target;

    // EVENT "click" on plugs menu
    if (el.matches('.plug-label li,.plug-label li *')) {
      const item = (el.tagName === 'li') ? el : el.closest('li');
      const label = item.closest('div');
      const wallTag = S.getCurrent('wall').tag;
      const ids = label.previousSibling.id.match(/^_(\d+)\-(\d+)$/);
      const startId = Number(ids[1]);
      const endId = Number(ids[2]);
      const start = P.get(wallTag.querySelector(
        `.postit[data-id="postit-${startId}"]`), 'postit');
      const end = P.get(wallTag.querySelector(
        `.postit[data-id="postit-${endId}"]`), 'postit');
      const defaultLabel =
        H.htmlEscape(label.querySelector('span').innerText);

      // LOCAL FUNCTION __unedit()
      const __unedit = () => {
        const toSave = {};

        toSave[startId] = start;
        toSave[endId] = end;

        S.set('plugs-to-save', toSave);
        start.unedit();
      };

      switch (item.dataset.action) {
        case 'rename':
          start.edit({}, () => {
            H.openConfirmPopover({
              type: 'update',
              item: label,
              title: `<i class="fas fa-bezier-curve fa-fw"></i> <?=_("Relation name")?>`,
              content: `<input type="text" class="form-control form-control-sm" value="${defaultLabel}" maxlength="<?=DbCache::getFieldLength('postits_plugs', 'label')?>">`,
              onClose: __unedit,
              onConfirm: (p) => {
                const label = p.querySelector('input').value.trim();

                if (label !== defaultLabel) {
                  start.updatePlugLabel({label, endId});
                }
              },
            });
          });
          break;
        case 'delete':
          start.edit({}, () => {
            H.openConfirmPopover ({
              item: label,
              placement: 'left',
              title: `<i class="fas fa-trash fa-fw"></i> <?=_("Delete")?>`,
              content: `<?=_("Delete this relation?")?>`,
              onClose: __unedit,
              onConfirm: () => start.removePlug(endId),
            });
          });
          break;
        case 'position-auto':
          start.edit({}, () => {
              const p = start.getPlugById(endId);

              start.deleteRelatedPlugs(p);
              p.obj.show();

              start.resetPlugLabelPosition(label);
              start.repositionPlugs();
              __unedit ();
            });
          break;
        case 'properties':
          start.openPlugProperties(start.getPlugById(endId));
          break;
      }
    }
  });

  // EVENT "mousedown"
  walls.addEventListener('mousedown', (e) => {
    const el = e.target;

    // EVENT "mousedown" on postit tags
    if (el.matches('.postit-tags,.postit-tags *')) {
      e.stopImmediatePropagation();

      if (H.disabledEvent(!H.checkAccess(<?=WPT_WRIGHTS_RW?>))) {
        H.preventDefault (e);
        return;
      }

      P.get(el.closest('.postit'), 'postit')
        .edit({}, () => S.getCurrent('tpick').open(e));
    }
  });

  // Create input to upload postit images
  H.createUploadElement({
    attrs: {id: 'postit-picture', accept: '.jpeg,.jpg,.gif,.png'},
    onChange: (e) => {
      const el = e.target;
      const fname = el.files[0].name;
  
      // LOCAL FUNCTION __displayError()
      const __displayError = (r) => {
        if (r) {
          H.displayMsg({type: 'warning', msg: r.error || r});
        }
      };
  
      H.getUploadedFiles(el.files, '\.(jpe?g|gif|png)$', (e, file) => {
        el.value = '';

        if (H.checkUploadFileSize({
              size: e.total,
              onErrorMsg: __displayError,
            }) && e.target.result) {
          const wallId = S.getCurrent('wall').getId();
          const postit = S.getCurrent('postit');
          const postitId = postit.getId();
          const cellId = postit.getCellId();

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

            if (r.error) {
              __displayError(r);
            } else {
              const inputs = document.querySelector('.tox-dialog')
                .querySelectorAll('input');

              postit.tag.dataset.hasuploadedpictures = true;

              //FIXME
              // If uploaded img is too large TinyMCE plugin
              // take too much time to gather informations
              // about it. If user close popup before that,
              // img is inserted without width/height
              inputs[1].value = r.width;
              inputs[2].value = r.height;

              S.get('tinymce-callback')(r.link);

              setTimeout(() => {
                if (!inputs[0].value) {
                  __displayError(`<?=_("Sorry, there is a compatibility issue with your browser when it comes to uploading notes images...")?>`);
                }
              }, 0);
            }
          })();
        }
      },
      null,
      __displayError);
    },
  });

  // EVENT "hide.bs.modal" on postit popup
  document.getElementById('postitUpdatePopup')
      .addEventListener('hide.bs.modal', (e) => {
    const el = e.target;
    const data = S.get('postit-data');

    // Return if we are closing the postit modal from the confirmation
    // popup
    if (data && data.closing) return;

    const popup = el;
    const plugin = S.getCurrent('postit');
    const progress =
      Number(P.get(popup.querySelector('.slider'), 'slider').value());
    const title = document.getElementById('postitUpdatePopupTitle').value;
    const content = tinymce.activeEditor.getContent();

    // LOCAL FUNCTION __close()
    const __close = (forceHide = false) => {
      S.set('postit-data', {closing: true});

      //FIXME
      document.querySelectorAll('.tox-toolbar__overflow').forEach(
        (el) => H.hide(el));
      //$('.tox-menu').hide();

      popup.querySelectorAll('input').forEach((el) => el.value = '');
      plugin.unedit();

      if (forceHide) {
        bootstrap.Modal.getInstance(popup).hide();
      }

      S.unset('postit-data');

      tinymce.activeEditor.resetContent();

      // Stop focusin event filtering
      document.removeEventListener('focusin', _focusinInFilter);
    };

    // If there is pending changes, ask confirmation to user
    if (data && (
        // Content change detection
        tinymce.activeEditor.isDirty() ||
        // Title change detection
        H.htmlEscape(data.title) !== H.htmlEscape(title) ||
        // Progress change detection
        data.progress !== progress)) {

      H.preventDefault(e);
      H.openConfirmPopup({
        type: 'save-postits-changes',
        icon: 'save',
        content: `<?=_("Save changes?")?>`,
        onConfirm: () => el.querySelector('.btn-primary').click(),
        onClose: () => __close(true),
      });

      S.set('postit-data', data);
    } else {
      __close();
    }
  });
});

})();
