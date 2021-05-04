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

  Plugin.prototype =
  {
    // METHOD init ()
    init (args)
    {
      this.element[0].querySelector("input").addEventListener ("input",
        e => this.value (e.target.value));
    },

    // METHOD value ()
    value (v, setcomp)
    {
      const el = this.element[0];

      if (v === undefined)
        return el.querySelector("input").value;
      else
      {
        el.querySelector("label span").innerHTML = `${v}%`;
        if (setcomp)
          el.querySelector("input").value = v;
      }
    }
  };

/////////////////////////// AT LOAD INIT //////////////////////////////

  $(function ()
    {
      if (!H.isLoginPage ())
      {
        const $plugin = $(".slider");

        if ($plugin.length)
          $plugin.slider ();
      }
    });

<?php echo $Plugin->getFooter ()?>
