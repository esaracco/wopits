<?php
/**
Javascript plugin - Display filters

Scope: Wall
Name: filters
Description: Manage filtering of notes display
*/

require_once(__DIR__.'/../prepend.php');

?>

(() => {
  'use strict';

let _body;

/////////////////////////////////// PLUGIN ////////////////////////////////////

P.register('filters', class extends Wpt_toolbox {
  // METHOD constructor()
  constructor(settings) {
    super(settings);
    this.settings = settings;
    this.tag = settings.tag;

    const tag = this.tag;

    if (!_body) {
      let tags = '';
      let colors = '';

      S.getCurrent('tpick').getTagsList().forEach((el) => tags +=
        `<div><i class="fa-${el} fa-fw fas" data-tag="${el}"></i></div>`);

      S.getCurrent('cpick').getColorsList().forEach((el) => colors +=
        `<div class="${el}">&nbsp;</div>`);

      _body = `<button type="button" class="btn-close"></button><h2><?=_("Filters")?></h2><div class="filters-items"><div class="tags"><h3><?=_("Tags:")?></h3>${tags}</div><div class="colors"><h3><?=_("Colors:")?></h3>${colors}</div></div>`;
    }

    $(tag)
      // TODO Do not use jQuery here
      .draggable({
        distance: 10,
        cursor: 'move',
        drag: (e, ui) => this.fixDragPosition(ui),
        stop: () => S.set('dragging', true, 500),
      })
      // TODO Do not use jQuery here
      .resizable({
        handles: 'all',
        autoHide: !$.support.touch,
      })
      .append(_body);

    // EVENT "click" on close button
    tag.querySelector('.btn-close').addEventListener('click',
      (e) => this.hide());

    // EVENT "click" on tags
    tag.querySelector('.tags').addEventListener('click', (e) => {
      const el = e.target;

      if (el.tagName === 'I') {
        if (H.disabledEvent()) return false;

        el.parentNode.classList.toggle('selected');

        this.apply();
      }
    });

    // EVENT "click" on colors
    tag.querySelector('.colors').addEventListener('click', (e) => {
      const el = e.target;

      if (el.tagName === 'DIV') {
        if (H.disabledEvent()) return false;

        el.classList.toggle('selected');

        this.apply();
      }
    });
  }

  // METHOD hide()
  hide() {
    if (this.isVisible()) {
      document.querySelector(`#main-menu li[data-action="filters"]`).click();
    }
  }

  // METHOD hidePlugs()
  hidePlugs() {
    this.tag.classList.add('plugs-hidden');
    S.getCurrent('wall').hidePostitsPlugs(); 
  }

  // METHOD showPlugs()
  showPlugs() {
    const wall = S.getCurrent('wall');

    this.tag.classList.remove('plugs-hidden');

    H.waitForDOMUpdate(() => {
      wall.showPostitsPlugs();
      if (wall.isShared()) {
        wall.refresh();
      } else {
        wall.refreshPostitsPlugs(true);
      }
    });
  }

  // METHOD toggle()
  toggle() {
    const tag = this.tag;

    if (this.isVisible()) {
      H.hide(tag);
      tag.querySelectorAll(
          '.tags div.selected,.colors div.selected').forEach(
              (el) => el.classList.remove('selected'));
      this.apply();
    } else {
      tag.style.top = '60px';
      tag.style.left = '5px';
      H.show(tag, 'table');
      this.apply({norefresh: true});
    }
  }

  // METHOD reset()
  reset() {
    this.tag.querySelectorAll('.selected').forEach(
        (el) => el.classList.remove('selected'));

    this.apply();
  }

  // METHOD apply()
  apply(args = {}) {
    const tag = this.tag;
    const wallTag = S.getCurrent('wall').tag;
    const tags = tag.querySelectorAll('.tags .selected');
    const colors = tag.querySelectorAll('.colors .selected');

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
      S.getCurrent('mmenu').reset();

      wallTag.querySelectorAll('td.wpt').forEach((cell) => {
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
      wallTag.querySelectorAll('td.wpt').forEach(__setVisible);

      if (!args.norefresh) {
        this.showPlugs();
      }
    }
  }
});

})();
