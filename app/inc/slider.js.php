<?php
/**
  Javascript plugin - Slider

  Scope: Note update popup
  Element: .slider
  Description: Custom slider
*/

  require_once(__DIR__.'/../prepend.php');

  $Plugin = new Wopits\jQueryPlugin('slider');
  echo $Plugin->getHeader();

?>

/////////////////////////////////// PRIVATE //////////////////////////////////

// DOM element
let _el = null;
// Input field
let _input =  null;

/////////////////////////////////// PUBLIC ///////////////////////////////////

<?=$Plugin->getPublicSection()?>

  Plugin.prototype = {
    // METHOD init()
    init(args) {
      _el = this.element[0];
      _input = _el.querySelector('input');

      _input.addEventListener('input', (e) => this.value(e.target.value));
    },

    // METHOD value()
    value(v, setcomp) {
      if (v === undefined) {
        return _input.value;
      } else {
        _el.querySelector('label span').innerHTML = v + '%';
        if (setcomp) {
          _input.value = v;
        }
      }
    }
  };

//////////////////////////////////// INIT ////////////////////////////////////

  document.addEventListener('DOMContentLoaded', () => {
    if (H.isLoginPage()) return;

    const plugin = document.querySelector('#postitUpdatePopup .slider');

    if (plugin) {
      $(plugin).slider();
    }
  });

<?=$Plugin->getFooter()?>
