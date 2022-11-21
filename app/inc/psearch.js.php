<?php
/**
Javascript plugin - Notes search

Scope: Wall
Name: psearch
Description: Search in wall's notes
*/

require_once(__DIR__.'/../prepend.php');

?>

/////////////////////////////////// PLUGIN ////////////////////////////////////

P.register('psearch', class extends Wpt_pluginBase {
  // METHOD constructor()
  constructor(settings) {
    super(settings);
    const tag = this.tag;
    const input = tag.querySelector('input');

    this.mmenu = S.getCurrent('mmenu');

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
    tag.querySelector('.clear-input').addEventListener('click', (e) => {
      this.reset(true)
      input.focus();
    });

    // EVENT "hidden.bs.modal" on popup
    tag.addEventListener('hidden.bs.modal', (e) => {
      if (this.mmenu.isVisible()) {
        this.mmenu.showHelp();
      }
    });
  }

  // METHOD isVisible()
  isVisible() {
    return H.isVisible(this.tag);
  }

  // METHOD open()
  open() {
    const f = S.getCurrent('filters');
    if (f.isVisible()) {
      f.reset();
    }

    H.openModal({item: this.tag});
    this.replay();
  }

  // METHOD close()
  close() {
    bootstrap.Modal.getInstance(this.tag).hide();
  }

  // METHOD restore()
  restore(str) {
    const input = this.tag.querySelector('input');

    input.value = str;
    input.dispatchEvent (new Event('keyup'));
  }

  // METHOD replay ()
  replay() {
    const input = this.tag.querySelector('input');

    input.value = S.getCurrent('wall').tag.dataset.searchstring || '';

    if (input.value) {
      input.dispatchEvent(new Event('keyup'));
    } else {
      this.reset();
    }
  }

  // METHOD reset()
  reset(full) {
    const wall = S.getCurrent('wall');
    const tag = this.tag;

    if (full) {
      tag.querySelector('input').value = '';
    }

    this.mmenu.reset();
    if (wall) {
      wall.tag.removeAttribute('data-searchstring');
    }
    tag.querySelector('.result').innerHTML = '';
  }

  // METHOD search()
  search(str) {
    const wallTag = S.getCurrent('wall').tag;

    this.reset();

    wallTag.dataset.searchstring = str;

    // Search in note title and body
    wallTag.querySelectorAll('.postit-edit,.postit-header .title')
      .forEach((el) => {
      if (el.innerText.match(new RegExp(H.quoteRegex(str), 'ig'))) {
        this.mmenu.add(P.get(el.closest('.postit'), 'postit'));
      }
    });

    const count = wallTag.querySelectorAll('.postit.selected').length;
    let html;

    if (count) {
      html = (count === 1) ?
        `<?=_("1 note match your search.")?>` :
        `<?=_("%s notes match your search.")?>`.replace('%s', count);
    } else {
      html = `<?=_("No result")?>`;
    }

    this.tag.querySelector('.result').innerHTML = html;
  }
});
