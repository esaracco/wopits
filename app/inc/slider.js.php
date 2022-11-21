<?php
/**
  Javascript plugin - Slider

  Scope: Note update popup
  Name: slider
  Description: Custom slider
*/

  require_once(__DIR__.'/../prepend.php');

?>

(() => {
'use strict';

/////////////////////////////////// PLUGIN ////////////////////////////////////

P.register('slider', class extends Wpt_pluginBase {
  // METHOD constructor()
  constructor(settings) {
    super(settings);

    this.input = this.tag.querySelector('input');

    this.input.addEventListener('input', (e) => this.value(e.target.value));
  }

  // METHOD value()
  value(v, setcomp) {
    if (v === undefined) {
      return this.input.value;
    } else {
      this.tag.querySelector('label span').innerHTML = v + '%';
      if (setcomp) {
        this.input.value = v;
      }
    }
  }
});

//////////////////////////////////// INIT ////////////////////////////////////

document.addEventListener('DOMContentLoaded', () => {
  if (H.isLoginPage()) return;

  P.create(document.querySelector('#postitUpdatePopup .slider'), 'slider');
});

})();
