<?php
/**
  Javascript plugin - Slider

  Scope: Note update popup
  Element: .slider
  Description: Custom slider
*/

  require_once (__DIR__.'/../prepend.php');

  $Plugin = new Wopits\jQueryPlugin ('slider');
  echo $Plugin->getHeader ();

?>

  /////////////////////////// PUBLIC METHODS ////////////////////////////

  Plugin.prototype = {
    // DOM element
    el: null,
    // Input field
    input: null,

    // METHOD init()
    init(args) {
      this.el = this.element[0];
      this.input = this.el.querySelector('input');

      this.input.addEventListener('input', (e) => this.value(e.target.value));
    },

    // METHOD value()
    value(v, setcomp) {
      if (v === undefined) {
        return this.input.value;
      } else {
        this.el.querySelector('label span').innerHTML = v + '%';
        if (setcomp) {
          this.input.value = v;
        }
      }
    }
  };

/////////////////////////// AT LOAD INIT //////////////////////////////

  document.addEventListener ('DOMContentLoaded', () => {
    if (H.isLoginPage()) return;

    const plugin = document.querySelector('#postitUpdatePopup .slider');

    if (plugin) {
      $(plugin).slider();
    }
  });

<?php echo $Plugin->getFooter ()?>
