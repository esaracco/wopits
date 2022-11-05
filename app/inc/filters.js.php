<?php
/**
Javascript plugin - Display filters

Scope: Wall
Element: .filters
Description: Manage filtering of notes display
*/

require_once(__DIR__.'/../prepend.php');

use Wopits\jQueryPlugin;

$Plugin = new jQueryPlugin('filters');
echo $Plugin->getHeader();

?>

/////////////////////////////////// PRIVATE //////////////////////////////////

let _body;

/////////////////////////////////// PUBLIC ///////////////////////////////////

<?=$Plugin->getPublicSection()?>

// Inherit from Wpt_toolbox
Plugin.prototype = Object.create(Wpt_toolbox.prototype);
Object.assign(Plugin.prototype, {
  // METHOD init()
  init() {
    const filters = this.element[0];

    if (!_body) {
      let tags = '';
      let colors = '';

      S.getCurrent('tpick').tpick('getTagsList').forEach((el) =>
          tags +=
            `<div><i class="fa-${el} fa-fw fas" data-tag="${el}"></i></div>`);

      $('#cpick').cpick('getColorsList').forEach((el) =>
          colors += `<div class="${el}">&nbsp;</div>`);

      _body = `<button type="button" class="btn-close"></button><h2><?=_("Filters")?></h2><div class="filters-items"><div class="tags"><h3><?=_("Tags:")?></h3>${tags}</div><div class="colors"><h3><?=_("Colors:")?></h3>${colors}</div></div>`;
    }

    $(filters)
      .draggable({
        distance: 10,
        cursor: 'move',
        drag: (e, ui) => this.fixDragPosition(ui),
        stop: () => S.set('dragging', true, 500),
      })
      .resizable({
        handles: 'all',
        autoHide: !$.support.touch,
      })
      .append(_body);

    // EVENT "click" on close button
    filters.querySelector('.btn-close').addEventListener('click',
      (e) => this.hide());

    // EVENT "click" on tags
    filters.querySelector('.tags').addEventListener('click', (e) => {
      const el = e.target;

      if (el.tagName === 'I') {
        if (H.disabledEvent()) return false;

        el.parentNode.classList.toggle('selected');

        this.apply();
      }
    });

    // EVENT "click" on colors
    filters.querySelector('.colors').addEventListener('click', (e) => {
      const el = e.target;

      if (el.tagName === 'DIV') {
        if (H.disabledEvent()) return false;

        el.classList.toggle('selected');

        this.apply();
      }
    });
  },

  // METHOD hide()
  hide() {
    if (H.isVisible(this.element[0])) {
      document.querySelector(`#main-menu li[data-action="filters"]`).click();
    }
  },

  // METHOD hidePlugs()
  hidePlugs() {
    this.element[0].classList.add('plugs-hidden');
    S.getCurrent('wall').wall('hidePostitsPlugs'); 
  },

  // METHOD showPlugs()
  showPlugs() {
    const wall = S.getCurrent('wall').wall('getClass');

    this.element[0].classList.remove('plugs-hidden');

    H.waitForDOMUpdate(()=> {
      wall.showPostitsPlugs();
      if (wall.isShared()) {
        wall.refresh();
      } else {
        wall.refreshPostitsPlugs(true);
      }
    });
  },

  // METHOD toggle()
  toggle() {
    const filters = this.element[0];

    if (H.isVisible(filters)) {
      H.hide(filters);
      filters.querySelectorAll(
          '.tags div.selected,.colors div.selected').forEach(
              (el) => el.classList.remove('selected'));
      this.apply();
    } else {
      filters.style.top = '60px';
      filters.style.left = '5px';
      H.show(filters, 'table');
      this.apply({norefresh: true});
    }
  },

  // METHOD reset()
  reset() {
    this.element[0].querySelectorAll('.selected').forEach(
        (el) => el.classList.remove('selected'));

    this.apply();
  },

  // METHOD apply()
  apply(args = {}) {
    const filters = this.element[0];
    const wall = S.getCurrent('wall')[0];
    const tags = filters.querySelectorAll('.tags .selected');
    const colors = filters.querySelectorAll('.colors .selected');

    // LOCAL FUNCTION __setVisible()
    const __setVisible = (cell) => {
      const pclass =
          cell.classList.contains('list-mode') ? '.postit-min' : '.postit';

      cell.querySelectorAll(pclass).forEach((el) => {
        el.classList.remove('filter-display');
        el.style.visibility = 'visible';
      });

      return pclass;
    };

    if (tags.length || colors.length) {
      this.hidePlugs();
      S.getCurrent('mmenu').mmenu('reset');

      wall.querySelectorAll('td.wpt').forEach((cell) => {
        const pclass = __setVisible(cell);

        tags.forEach((el) => {
          const tag =
              el.querySelector('i').className.match(/fa\-([^ ]+) /)[1];

          cell.querySelectorAll(`${pclass}[data-tags*=",${tag},"]`)
              .forEach((el) => el.classList.add('filter-display'));
          });

        colors.forEach((el) => {
          cell.querySelectorAll(`${pclass}.${el.className.split(' ')[0]}`)
              .forEach((el) => el.classList.add('filter-display'));
        });

        cell.querySelectorAll(`${pclass}:not(.filter-display)`)
            .forEach((el) => el.style.visibility = 'hidden');
      });
    } else {
      wall.querySelectorAll('td.wpt').forEach(__setVisible);

      if (!args.norefresh) {
        this.showPlugs();
      }
    }
  }
});

<?=$Plugin->getFooter()?>
