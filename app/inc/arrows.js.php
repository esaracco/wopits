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
      const $arrows = this.element;

      if ($arrows.is (":visible"))
        $arrows.hide ();
      else
      {
        $arrows.show ();
        this.reset ();
      }
    },

    // METHOD update ()
    update: function ()
    {
      const $arrows = this.element,
            bounding = wpt_sharer.getCurrent("wall")[0].getBoundingClientRect();

      if (this.settings.walls.scrollLeft() <= 0)
        $arrows.find(".goto-box-x i.left,.goto-box-x i.full-left")
          .addClass ("readonly");
      else
        $arrows.find(".goto-box-x i.left,.goto-box-x i.full-left")
          .removeClass ("readonly");

      if (bounding.right > (window.innerWidth ||
                            document.documentElement.clientWidth))
        $arrows.find(".goto-box-x i.right,.goto-box-x i.full-right")
          .removeClass ("readonly");
      else
        $arrows.find(".goto-box-x i.right,.goto-box-x i.full-right")
          .addClass ("readonly");

      if (this.settings.walls.scrollTop() <= 0)
        $arrows.find(".goto-box-y i.up,.goto-box-y i.full-up")
          .addClass ("readonly");
      else
        $arrows.find(".goto-box-y i.up,.goto-box-y i.full-up")
          .removeClass ("readonly");

      if (bounding.bottom > (window.innerHeight ||
                            document.documentElement.clientHeight))
        $arrows.find(".goto-box-y i.down,.goto-box-y i.full-down")
          .removeClass ("readonly");
      else
        $arrows.find(".goto-box-y i.down,.goto-box-y i.full-down")
          .addClass ("readonly");
    }
  };

/////////////////////////// AT LOAD INIT //////////////////////////////

  $(function ()
    {
      if (!document.querySelector ("body.login-page"))
      {
        const $walls = wpt_sharer.getCurrent ("walls");

        // EVENT click on arrows tool
        $(document).on("click", ".arrows .goto-box-x i,"+
                                ".arrows .goto-box-y i", function (e)
          {
            const $btn = $(this),
                  $wall = wpt_sharer.getCurrent ("wall");

            e.stopImmediatePropagation ();

            $wall.wpt_wall ("hidePostitsPlugs");

            if ($btn.closest("div").hasClass ("goto-box-y"))
            {
              const sTop = $walls.scrollTop ();

              if($btn[0].className.indexOf("up") != -1)
                $walls.scrollTop (sTop -
                  ($btn.hasClass("full-up") ? 100000 : 100));
              else
                $walls.scrollTop (sTop +
                  ($btn.hasClass("full-down") ? 100000 : 100));
            }
            else
            {
              const sLeft = $walls.scrollLeft ();

              if($btn[0].className.indexOf("right") != -1)
                $walls.scrollLeft (sLeft +
                  ($btn.hasClass("full-right") ? 100000 : 100));
              else
                $walls.scrollLeft (sLeft -
                  ($btn.hasClass("full-left") ? 100000 : 100));
            }

            $wall.wpt_wall ("showPostitsPlugs");

          });
      }
    });

<?php echo $Plugin->getFooter ()?>
