<?php
/**
Javascript plugin - Note tags

Name: tpick
Description: Manage notes tags
*/

require_once(__DIR__.'/../prepend.php');

?>

(() => {
'use strict';

/////////////////////////////////// PLUGIN ////////////////////////////////////

P.register('tpick', class extends Wpt_pluginBase {
  // METHOD constructor()
  constructor(settings) {
    super(settings);
    const tag = this.tag;

    this.width = 0;
    this.height = 0;

    let html = '';
    this.getTagsList().forEach(
      (el) => html += `<div>${this.getTagTemplate(el)}</div>`);
    tag.innerHTML = html;

    // EVENT "click" on tags
    tag.addEventListener('click', (e) => {
      const el = e.target;
      let div;
      let tag;

      e.stopImmediatePropagation();

      // Return if click on popup, out I and I div parent
      if (el.id) return;

      // I tag
      if (el.tagName === 'I') {
        div = el.parentNode;
        tag = el.dataset.tag;
      // I parent DIV tag
      } else {
        div = el;
        tag = div.querySelector('i').dataset.tag;
      }

      const postitTag = S.getCurrent('postit').tag;

      if (div.classList.contains('selected')) {
        postitTag.querySelector(`.postit-tags i.fa-${tag}`).remove();
      } else {
        $(postitTag).find('.postit-tags').prepend(this.getTagTemplate(tag));
      }

      div.classList.toggle('selected');

      this.refreshPostitDataTag();

      const f = S.getCurrent('filters');
      if (f.isVisible()) {
        f.apply({norefresh: true});
      }
    });

    H.waitForDOMUpdate(() => {
      this.width = tag.offsetWidth;
      this.height = tag.offsetHeight;
    });
  }

  // METHOD getTagTemplate()
  getTagTemplate(tag) {
    return `<i class="fa-${tag} fa-fw fas" data-tag="${tag}"></i>`;
  }

  // METHOD getTagsList()
  getTagsList() {
    return [`<?=join('`,`', WPT_MODULES['tpick']['items'])?>`];
  }

  // METHOD open()
  open({pageX, pageY}) {
    const tag = this.tag;
    const postit = S.getCurrent('postit');
    const wW = window.outerWidth;
    const wH = window.innerHeight;
    let x = pageX + 5;
    let y = pageY - 20;

    tag.querySelectorAll('.selected').forEach(
      (el) => el.classList.remove('selected'));

    postit.tag.querySelectorAll('.postit-tags i').forEach((el) =>
      tag.querySelector(`i.fa-${el.dataset.tag}`)
          .parentNode.classList.add('selected'));

    if (x + this.width > wW) {
      x = wW - this.width - 5;
    }

    if (y + this.height > wH) {
      y = wH - this.height - 5;
    }

    H.openPopupLayer(() => {
      this.close();
      S.getCurrent('postit').unedit();
    });

    tag.style.top = `${y}px`;
    tag.style.left = `${x}px`;
    tag.style.visibility = 'visible';
  }

  // METHOD close()
  close() {
    this.tag.style.visibility = 'hidden';
    S.getCurrent('postit').tag.dispatchEvent(new MouseEvent('mouseleave'));
  }

  // METHOD refreshPostitDataTag()
  refreshPostitDataTag(postit) {
    const postitTag = (postit && postit.tag) || S.getCurrent('postit').tag;
    let dataTag = '';

    postitTag.querySelectorAll('.postit-tags i').forEach(
      (el) => dataTag += `,${el.dataset.tag}`);

    if (dataTag) {
      dataTag += ',';
    }

    postitTag.dataset.tags = dataTag;

    postitTag.querySelector('.postit-tags').style.display =
      dataTag ? 'block' : 'none';
  }

  // METHOD getHTMLFromString()
  getHTMLFromString(str) {
    if (!( str = str.replace(/(^,|,$)/g, ''))) return '';

    let ret = '';

    (str.split(',')).forEach((el) => ret += this.getTagTemplate(el));

    return ret;
  }
});

//////////////////////////////////// INIT ////////////////////////////////////

document.addEventListener('DOMContentLoaded', () => {
  if (H.isLoginPage()) return;

  P.create(document.getElementById('tpick'), 'tpick');
});

})();
