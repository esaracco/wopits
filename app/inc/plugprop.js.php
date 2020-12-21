<?php
/**
  Javascript plugin - Relationship's arrows properties

  Scope: Note
  Element: .plprop
  Description: Set relationships arrows properties
*/

  require_once (__DIR__.'/../prepend.php');

  $Plugin = new Wopits\jQueryPlugin ('plugprop');
  echo $Plugin->getHeader ();

?>

  let _ll, _postitPlugin, _plug;

  /////////////////////////// PUBLIC METHODS ////////////////////////////

  Plugin.prototype =
  {
    // METHOD init ()
    init ()
    {
      const plugin = this,
            $ra = plugin.element,
            ww = $(window).width ();

      // EVENT hidden.bs.modal on popup
      $ra.on("hidden.bs.modal", function ()
        {
          $(this).find(".modal-body").off ("scroll.plugprop");

          // Remove relationship sample
          plugin.removeSample ();
          _postitPlugin.unedit ();
        });

      // EVENT show.bs.modal on popup
      $ra.on("show.bs.modal", function ()
        {
          // EVENT scroll on popup body
          $(this).find(".modal-body")
            .on("scroll.plugprop", ()=> _ll.position ());

          document.getElementById("plugprop-sample").style.backgroundColor =
            S.getCurrent("wall")[0].style.backgroundColor||"#fff";
        });

      // EVENT click on reset button
      $ra.find("button.reset").on("click", function ()
        {
          plugin.removeSample ();
          plugin.createSample (true);

          $ra.find("input[name='size']").val (_ll.size);
          $ra.find("input[value='"+_ll.path+"']")[0].checked = true;
          $ra.find("input[value='"+_ll.line_type+"']")[0].checked = true;

          return false;
        });

      /// EVENT keyup & change on line size input
      $ra.find("input[name='size']").on("keyup change", function ()
        {
          if (_ll)
          {
            const max = parseInt (this.getAttribute ("max"));

            if (this.value > max)
              this.value = max;
            else
            {
              const min = parseInt (this.getAttribute ("min"));

              if (this.value < min)
                this.value = min;
            }

            _ll.size = parseInt (this.value);
          }
        });

      // EVENT click on line type options
      $ra.find("input[name='type']").on("click", function ()
        {
          _ll.line_type = this.value;

          _postitPlugin.applyPlugLineType (_ll);
        });

      // EVENT click on line path options
      $ra.find("input[name='path']").on("click", function ()
        {
          _ll.path = this.value;
        });

      // Load color picker
      $ra.find(".cp").colorpicker({
        parts:  ["swatches"],
        swatchesWidth: ww < 435 ? ww - 90 : 435,
        color: "#fff",
        select: function (e, color)
          {
            _ll.color = color.css;
            _ll.setOptions ({
              dropShadow: _postitPlugin.getPlugDropShadowTemplate (color.css)
            });
          }
      });

      // EVENT click on colorpicker color
      $ra.find(".cp .ui-colorpicker-swatch").on("click", function ()
        {
          const s = this.parentNode.querySelector (".cp-selected");

          if (s)
            s.classList.remove ("cp-selected");

          this.classList.add ("cp-selected");
        });

      // EVENT click on submit button
      $ra.find(".btn-primary").on("click", function ()
        {
          let toSave = {};

          toSave[_plug.startId] = $(_plug.obj.start);
          toSave[_plug.endId] = $(_plug.obj.end);

          S.set ("plugs-to-save", toSave);

          _postitPlugin.updatePlugProperties ({
            startId: _plug.startId,
            endId: _plug.endId,
            size: _ll.size,
            path: _ll.path,
            color: _ll.color,
            line_type: _ll.line_type
          });

          plugin.removeSample ();
        });
    },

    // METHOD createSample ()
    createSample (reset)
    {
      const els = document.querySelectorAll ("#plugprop-sample div");

      _ll = _postitPlugin.getPlugTemplate ({
        start: els[0],
        end: els[1],
        line_size: (reset) ? undefined : _plug.obj.line_size,
        line_path: (reset) ? undefined : _plug.obj.path,
        line_type: (reset) ? undefined : _plug.obj.line_type,
        line_color: (reset) ? undefined : _plug.obj.color
      }, true);

      this.element[0].appendChild (
        document.querySelector(".leader-line:last-child"));
    },

    // METHOD removeSample ()
    removeSample ()
    {
      const s = this.element[0].querySelector (".leader-line");

      if (s)
      {
        document.body.appendChild (s);
        _ll.remove ();
        _ll = null;
      }
    },

    // METHOD open ()
    open (postitPlugin, plug)
    {
      const $ra = this.element;

      H.openModal ($ra);

      _plug = plug;
      _postitPlugin = postitPlugin;

      this.createSample ();

      H.setColorpickerColor ($ra.find(".cp"), _plug.obj.color);
      $ra.find("input[name='size']").val (_plug.obj.line_size);
      $ra.find("input[value='"+_plug.obj.path+"']")[0].checked = true;
      $ra.find("input[value='"+_plug.obj.line_type+"']")[0].checked = true;
    },
  };

<?php echo $Plugin->getFooter ()?>
