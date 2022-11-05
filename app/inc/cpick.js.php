<?php

/**
  Javascript plugin - Notes color picker

  Scope: Note
  Element: .cpick
  Description: Manage notes color
*/

require_once(__DIR__.'/../prepend.php');

$Plugin = new Wopits\jQueryPlugin('cpick');
echo $Plugin->getHeader();

?>

/////////////////////////////////// PRIVATE //////////////////////////////////

const _COLOR_PICKER_COLORS = [`color-<?=join('`,`color-', array_keys(WPT_MODULES['cpick']['items']))?>`];

/////////////////////////////////// PUBLIC ///////////////////////////////////

<?=$Plugin->getPublicSection()?>

Plugin.prototype = {
  width: null,
  height: null,
  onClose: null,
  onSelect: null,
  // METHOD init()
  init() {
    const picker = this.element[0];
    let html = '';

    _COLOR_PICKER_COLORS.forEach(
      (cls) => html += `<div class="${cls}">&nbsp;</div>`);

    picker.innerHTML = html;

    H.waitForDOMUpdate(() => {
      this.width = picker.offsetWidth;
      this.height = picker.offsetHeight;
    }); 
  },

  // METHOD getColorsList()
  getColorsList() {
    return _COLOR_PICKER_COLORS;
  },

  // METHOD open()
  open(args) {
    const picker = this.element[0];
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

      const $f = S.getCurrent('filters');
      if ($f.is(':visible')) {
        $f.filters('apply', {norefresh: true});
      }
    };
    picker.removeEventListener('click', _eventC);
    picker.addEventListener('click', _eventC);

    H.openPopupLayer(() => {
      const $postit = S.getCurrent('postit');

      this.close();

      if ($postit.length) {
        $postit.postit('unedit');
      }
    });

    picker.style.top = `${y}px`;
    picker.style.left = `${x}px`;
    picker.style.visibility = 'visible';
  },

  // METHOD close()
  close() { 
    const picker = this.element[0];

    if (picker) {
      picker.style.visibility = 'hidden';
      this.onClose && this.onClose();
    }
  }
};

//////////////////////////////////// INIT ////////////////////////////////////

document.addEventListener('DOMContentLoaded',
  () => !H.isLoginPage() && $('#cpick').cpick());

<?=$Plugin->getFooter()?>
