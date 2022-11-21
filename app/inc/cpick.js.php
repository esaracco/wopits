<?php

/**
  Javascript plugin - Notes color picker

  Name: cpick
  Description: Manage notes color
*/

require_once(__DIR__.'/../prepend.php');

?>

(() => {
  'use strict';

/////////////////////////////////// PLUGIN ////////////////////////////////////

P.register('cpick', class extends Wpt_pluginBase {
  // METHOD constructor()
  constructor(settings) {
    super(settings);
    this.onClose = null;
    this.onSelect = null;

    const tag = this.tag;

    let html = '';
    this.getColorsList().forEach(
      (cls) => html += `<div class="${cls}">&nbsp;</div>`);
    tag.innerHTML = html;

    H.waitForDOMUpdate(() => {
      this.width = tag.offsetWidth;
      this.height = tag.offsetHeight;
    }); 
  }

  // METHOD getColorsList()
  getColorsList() {
    return [`color-<?=join('`,`color-', array_keys(WPT_MODULES['cpick']['items']))?>`];
  }

  // METHOD open()
  open(args) {
    const tag = this.tag;
    const wW = window.outerWidth;
    const wH = window.innerHeight;
    let x = args.event.pageX + 5;
    let y = args.event.pageY - 20;

    if (x + this.width > wW) {
      x = wW - this.width - 20;
    }

    if (y + this.height > wH) {
      y = wH - this.height - 20;
    }

    this.onClose = args.onClose;
    this.onSelect = args.onSelect;

    // EVENT "click" on colors
    const _eventC = (e) => {
      const el = e.target;

      e.stopImmediatePropagation();

      // Do not process if not color div
      if (el.className.indexOf('color') !== 0) return;

      // Update background color
      this.onSelect(el);

      // Remove color picker
      // document.getElementById('popup-layer').click();

      const f = S.getCurrent('filters');
      if (f.isVisible()) {
        f.apply({norefresh: true});
      }
    };
    tag.removeEventListener('click', _eventC);
    tag.addEventListener('click', _eventC);

    H.openPopupLayer(() => {
      const postit = S.getCurrent('postit');

      this.close();

      if (postit) {
        postit.unedit();
      }
    });

    tag.style.top = `${y}px`;
    tag.style.left = `${x}px`;
    tag.style.visibility = 'visible';
  }

  // METHOD close()
  close() {
    const tag = this.tag;

    if (tag) {
      tag.style.visibility = 'hidden';
      this.onClose && this.onClose();
    }
  }
});

//////////////////////////////////// INIT ////////////////////////////////////

document.addEventListener('DOMContentLoaded', () => {
  if (H.isLoginPage()) return;

  P.create(document.getElementById('cpick'), 'cpick');
});

})();
