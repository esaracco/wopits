<?php
/**
  Javascript plugin - Arrows

  Scope: Wall
  Element: .arrows
  Description: Manage repositionning arrows panel
*/

  require_once (__DIR__.'/../prepend.php');

  $Plugin = new Wopits\jQueryPlugin ('arrows');
  echo $Plugin->getHeader ();

?>

/////////////////////////// PUBLIC METHODS ////////////////////////////

  Plugin.prototype =
  {
    // METHOD init ()
    init ()
    {
      this.settings["walls"] = S.getCurrent ("walls");

      this.element.html (`<div class="goto-box goto-box-y"><i class="fas fa-arrow-up fa-2x full-up" title="A"></i><i class="fas fa-chevron-up fa-2x up"></i><i class="fas fa-chevron-down fa-2x down"></i><i class="fas fa-arrow-down fa-2x full-down"></i></div><div class="goto-box goto-box-x"><i class="fas fa-arrow-left fa-2x full-left"></i><i class="fas fa-chevron-left fa-2x left"></i><i class="fas fa-chevron-right fa-2x right"></i><i class="fas fa-arrow-right fa-2x full-right"></i></div>`);
    },

    // METHOD reset ()
    reset ()
    {
      if (this.element.is (":hidden"))
        return;

      const $walls = this.settings.walls;

      if ($walls)
        $walls.trigger ("scroll");
    },

    // METHOD hide ()
    hide ()
    {
      if (this.element.is (":visible"))
        $("#main-menu").find("li[data-action='arrows'] a").click ();
    },

    // METHOD toggle ()
    toggle ()
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
    update ()
    {
      const $arrows = this.element,
            bounding = S.getCurrent("wall")[0].getBoundingClientRect ();

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
      if (!H.isLoginPage ())
        setTimeout(()=>{
        const $walls = S.getCurrent ("walls");

        // EVENT click on arrows tool
        $(document).on("click", ".arrows .goto-box-x i,"+
                                ".arrows .goto-box-y i", function (e)
          {
            const $btn = $(this),
                  $wall = S.getCurrent ("wall");

            e.stopImmediatePropagation ();

            $wall.wall ("hidePostitsPlugs");

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

            $wall.wall ("showPostitsPlugs");

          })}, 0);
    });

<?php echo $Plugin->getFooter ()?>
