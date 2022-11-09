<?php
/**
Javascript plugin - Note tags

Scope: Note
Elements: .tpick
Description: Manage notes tags
*/

require_once(__DIR__.'/../prepend.php');

$Plugin = new Wopits\jQueryPlugin('tpick');
echo $Plugin->getHeader();

?>

/////////////////////////////////// PRIVATE //////////////////////////////////

let _width = 0;
let _height = 0;

// METHOD _getTagTemplate()
const _getTagTemplate =
    (tag) => `<i class="fa-${tag} fa-fw fas" data-tag="${tag}"></i>`;

/////////////////////////////////// PUBLIC ///////////////////////////////////

<?=$Plugin->getPublicSection()?>

Plugin.prototype = {
  // METHOD init()
  init() {
    const picker = this.element[0];
    let html = '';

    this.getTagsList().forEach(
      (el) => html += `<div>${_getTagTemplate(el)}</div>`);

    picker.innerHTML = html;

    // EVENT "click" on tags
    picker.addEventListener('click', (e) => {
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

      const select = !div.classList.contains('selected');
      const $postit = S.getCurrent('postit');

      if (!select) {
        $postit[0].querySelector(`.postit-tags i.fa-${tag}`).remove();
      } else {
        $postit.find('.postit-tags').prepend(_getTagTemplate(tag));
      }

      div.classList.toggle('selected');

      this.refreshPostitDataTag();

      const $f = S.getCurrent('filters');
      if (H.isVisible($f[0])) {
        $f.filters('apply', {norefresh: true});
      }
    });

    H.waitForDOMUpdate(() => {
      _width = picker.offsetWidth;
      _height = picker.offsetHeight;
    });
  },

  // METHOD getTagsList()
  getTagsList() {
    return [`<?=join('`,`', WPT_MODULES['tpick']['items'])?>`];
  },

  // METHOD open()
  open({pageX, pageY}) {
    const picker = this.element[0];
    const $postit = S.getCurrent('postit');
    const wW = window.outerWidth;
    const wH = window.innerHeight;
    let x = pageX + 5;
    let y = pageY - 20;

    picker.querySelectorAll('.selected').forEach(
        (el) => el.classList.remove('selected'));

    $postit[0].querySelectorAll('.postit-tags i').forEach((el) =>
        picker.querySelector(`i.fa-${el.dataset.tag}`)
            .parentNode.classList.add('selected'));

    if (x + _width > wW) {
      x = wW - _width - 20;
    }

    if (y + _height > wH) {
      y = wH - _height - 20;
    }

    H.openPopupLayer(() => {
      this.close();
      S.getCurrent('postit').postit('unedit');
    });

    picker.style.top = y + 'px';
    picker.style.left = x + 'px';
    picker.style.visibility = 'visible';
  },

  // METHOD close()
  close() {
    const picker = this.element[0];

    if (picker) {
      picker.style.visibility = 'hidden';
      S.getCurrent('postit').trigger('mouseleave');
    }
  },

  // METHOD refreshPostitDataTag()
  refreshPostitDataTag($postit) {
    const postit = $postit ? $postit[0] : S.getCurrent('postit')[0];
    let dataTag = '';

    postit.querySelectorAll('.postit-tags i').forEach(
        (el) => dataTag += ','+el.dataset.tag);

    if (dataTag) {
      dataTag += ',';
    }

    postit.dataset.tags = dataTag;

    postit.querySelector('.postit-tags')
        .style.display = dataTag ? 'block' : 'none';
  },

  // METHOD getHTMLFromString()
  getHTMLFromString(str) {
    if (!( str = str.replace(/(^,|,$)/g, ''))) return '';

    let ret = '';

    (str.split(',')).forEach((el) => ret += _getTagTemplate(el));

    return ret;
  }
};

//////////////////////////////////// INIT ////////////////////////////////////

document.addEventListener('DOMContentLoaded',
    () => !H.isLoginPage() && $('#tpick').tpick());

<?=$Plugin->getFooter()?>
