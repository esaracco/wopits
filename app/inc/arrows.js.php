<?php
  require_once (__DIR__.'/../class/Wpt_jQueryPlugins.php');
  $Plugin = new Wpt_jQueryPlugins ('arrows');
  echo $Plugin->getHeader ();
?>

/////////////////////////// PUBLIC METHODS ////////////////////////////

  Plugin.prototype =
  {
    // METHOD init ()
    init: function ()
    {
      this.settings["walls"] = wpt_sharer.getCurrent("walls");

      this.element.html (`<div class="goto-box goto-box-y"><i class="fas fa-arrow-up fa-2x full-up" title="A"></i><i class="fas fa-chevron-up fa-2x up"></i><i class="fas fa-chevron-down fa-2x down"></i><i class="fas fa-arrow-down fa-2x full-down"></i></div><div class="goto-box goto-box-x"><i class="fas fa-arrow-left fa-2x full-left"></i><i class="fas fa-chevron-left fa-2x left"></i><i class="fas fa-chevron-right fa-2x right"></i><i class="fas fa-arrow-right fa-2x full-right"></i></div>`);
    },

    // METHOD reset ()
    reset: function ()
    {
      const $walls = this.settings.walls;

      if ($walls)
        $walls.trigger ("scroll");
    },

    // METHOD hide ()
    hide: function ()
    {
      if (this.element.is (":visible"))
        $("#main-menu").find("li[data-action='arrows'] a").trigger ("click");
    },

    // METHOD toggle ()
    toggle: function ()
    {
      const plugin = this,
            $arrows = plugin.element;

      if ($arrows.is (":visible"))
        $arrows.hide ();
      else
      {
        $arrows.show ();
        plugin.reset ();
      }
    },
  };

<?php echo $Plugin->getFooter ()?>
