<?php
/**
Javascript plugin - Notes search

Scope: Wall
Elements: #psearchPopup
Description: Search in wall's notes
*/

require_once(__DIR__.'/../prepend.php');

$Plugin = new Wopits\jQueryPlugin('psearch');
echo $Plugin->getHeader();

?>

/////////////////////////////////// PRIVATE //////////////////////////////////

let _smPlugin;

/////////////////////////////////// PUBLIC ///////////////////////////////////

<?=$Plugin->getPublicSection()?>

Plugin.prototype = {
  // METHOD init()
  init(args) {
    const search = this.element[0];
    const input = search.querySelector('input');

    _smPlugin = S.getCurrent('mmenu').mmenu('getClass');

    // EVENT "keyup" on input
    input.addEventListener('keyup', (e) => {
      if (e.which === 13) return;

      const val = e.target.value.trim();

      if (val.length < 3) {
        this.reset();
      } else {
        this.search(val)
      }
    });

    // EVENT "keypress" on input
    input.addEventListener('keypress', (e) => {
       if (e.which === 13) {
         this.close();
       }
    });

      // EVENT "click" on input clear button
    search.querySelector('.clear-input').addEventListener('click', (e) => {
      this.reset(true)
      input.focus();
    });

    // EVENT "hidden.bs.modal" on popup
    search.addEventListener('hidden.bs.modal', (e) => {
      if (H.isVisible(_smPlugin.element[0])) {
        _smPlugin.showHelp();
      }
    });
  },

  // METHOD open()
  open() {
    const f = S.getCurrent('filters')[0];
    if (H.isVisible(f)) {
      $(f).filters('reset');
    }

    H.openModal({item: this.element[0]});
    this.replay();
  },

  // METHOD close()
  close() {
    bootstrap.Modal.getInstance(this.element[0]).hide();
  },

  // METHOD restore()
  restore(str) {
    const input = this.element[0].querySelector('input');

    input.value = str;
    input.dispatchEvent (new Event('keyup'));
  },

  // METHOD replay ()
  replay() {
    const input = this.element[0].querySelector('input');

    input.value = S.getCurrent('wall')[0].dataset.searchstring || '';

    if (input.value) {
      input.dispatchEvent(new Event('keyup'));
    } else {
      this.reset();
    }
  },

  // METHOD reset()
  reset(full) {
    const $wall = S.getCurrent('wall');
    const search = this.element[0];

    if (full) {
      search.querySelector('input').value = '';
    }

    _smPlugin.reset();
    if ($wall.length) {
      $wall[0].removeAttribute('data-searchstring');
    }
    search.querySelector('.result').innerHTML = '';
  },

  // METHOD search()
  search(str) {
    const wall = S.getCurrent('wall')[0];

    this.reset();

    wall.dataset.searchstring = str;

    // Search in note title and body
    wall.querySelectorAll('.postit-edit,.postit-header .title')
      .forEach((el) => {
      if (el.innerText.match(new RegExp(H.quoteRegex(str), 'ig'))) {
        _smPlugin.add($(el.closest('.postit')).postit('getClass'));
      }
    });

    const count = wall.querySelectorAll('.postit.selected').length;
    let html;

    if (count) {
      html = (count === 1) ?
        `<?=_("1 note match your search.")?>` :
        `<?=_("%s notes match your search.")?>`.replace('%s', count);
    } else {
      html = `<?=_("No result")?>`;
    }

    this.element[0].querySelector('.result').innerHTML = html;
  }
};

<?=$Plugin->getFooter()?>
