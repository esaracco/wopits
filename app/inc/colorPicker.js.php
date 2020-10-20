<?php

  require_once (__DIR__.'/../prepend.php');

  $Plugin = new Wopits\jQueryPlugin ('colorPicker');
  echo $Plugin->getHeader ();

?>

  const _COLOR_PICKER_COLORS = [<?='"color-'.join ('","color-', array_keys (WPT_MODULES['colorPicker']['items'])).'"'?>];
  let _width = 0,
      _height = 0;

  /////////////////////////// PUBLIC METHODS ////////////////////////////

  Plugin.prototype =
  {
    // METHOD init ()
    init (args)
    {
      const $picker = this.element;
      let html = "";

      _COLOR_PICKER_COLORS.forEach (
        (cls, i) => html += `<div class="${cls}">&nbsp;</div>`);

      $picker.append(html).find("> div")
        .on("click", function(e)
        {
          e.stopImmediatePropagation ();

          // Update popup background color
          S.getCurrent("postit")
            .removeClass(_COLOR_PICKER_COLORS.join(" "))
            .addClass($(this).attr ("class"));

          // Remove color picker
          $("#popup-layer").click ();

          S.getCurrent("filters").filters ("apply");
        });

      H.waitForDOMUpdate (() =>
        {
          _width = $picker.outerWidth ();
          _height = $picker.outerHeight ();
        }); 
    },

    // METHOD getColorsList ()
    getColorsList ()
    {
      return _COLOR_PICKER_COLORS;
    },

    // METHOD open ()
    open (args)
    {
      const $picker = this.element,
            wW = $(window).outerWidth (),
            wH = $(window).outerHeight ();
      let x = args.pageX + 5,
          y = args.pageY - 20;
     
      if (x + _width > wW)
        x = wW - _width - 20;

      if (y + _height > wH)
        y = wH - _height - 20;

      H.openPopupLayer (() =>
        {
          this.close ();
          S.getCurrent("postit").postit ("unedit");
        });

      $picker
        .css({top: y, left: x})
        .show ();
    },

    // METHOD close ()
    close ()
    { 
      const $picker = this.element;

      if ($picker.length)
      {
        $picker.hide ();
        S.getCurrent("postit").trigger ("mouseleave");
      }
    }

  };

  /////////////////////////// AT LOAD INIT //////////////////////////////

  $(function ()
  {
    setTimeout (()=>
    {
      $("body").prepend (`<div id="color-picker"></div>`);
      $("#color-picker").colorPicker ();
    }, 0);
  });

<?php echo $Plugin->getFooter ()?>
