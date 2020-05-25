<?php
  require_once (__DIR__.'/../class/Wpt_jQueryPlugins.php');
  $Plugin = new Wpt_jQueryPlugins ('colorPicker');
  echo $Plugin->getHeader ();
?>

  const _COLOR_PICKER_COLORS = [<?='"color-'.join ('","color-', array_keys (WPT_MODULES['colorPicker']['items'])).'"'?>];
  let _width = 0,
      _height = 0;

  /////////////////////////// PUBLIC METHODS ////////////////////////////

  Plugin.prototype =
  {
    // METHOD init ()
    init: function (args)
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
         wpt_sharer.getCurrent ("postit")
            .removeClass(_COLOR_PICKER_COLORS.join(" "))
            .addClass($(this).attr ("class"));

          // Remove color picker
          $("#popup-layer").trigger ("click");

          wpt_sharer.getCurrent("filters").wpt_filters ("apply");
        });

      wpt_waitForDOMUpdate (() =>
        {
          _width = $picker.outerWidth ();
          _height = $picker.outerHeight ();
        }); 
    },

    // METHOD getColorsList ()
    getColorsList: function ()
    {
      return _COLOR_PICKER_COLORS;
    },

    // METHOD open ()
    open: function (args)
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

      wpt_openPopupLayer (() =>
        {
          this.close ();
          wpt_sharer.getCurrent ("postit").wpt_postit ("unedit");
        });

      $picker
        .css({top: y, left: x})
        .show ();
    },

    // METHOD close ()
    close: function ()
    { 
      const $picker = this.element;

      if ($picker.length)
      {
        $picker.hide ();
        wpt_sharer.getCurrent("postit").trigger ("mouseleave");
      }
    }

  };

  /////////////////////////// AT LOAD INIT //////////////////////////////

  $(function ()
  {
    const $plugin = $(".color-picker");

    if ($plugin.length)
      $plugin.wpt_colorPicker ();
  });

<?php echo $Plugin->getFooter ()?>
