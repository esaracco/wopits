<?php
/**
  Javascript plugin - Notes relations properties

  Scope: Note
  Element: .plugprop
  Description: Set notes relations properties
*/

  require_once (__DIR__.'/../prepend.php');

  $Plugin = new Wopits\jQueryPlugin ('plugprop');
  echo $Plugin->getHeader ();

?>

  let _ll;
  let _postitPlugin;
  let _plug;

  /////////////////////////// PUBLIC METHODS ////////////////////////////

  Plugin.prototype = {
    // METHOD init()
    init() {
      const plugin = this;
      const ra = plugin.element[0];
      const ww = $(window).width();

      // FIXME
      const _eventSP = (e) => _ll && _ll.position();

      // EVENT "hidden.bs.modal" plug's settings popup
      ra.addEventListener('hidden.bs.modal', (e) => {
        // FIXME
        ra.removeEventListener('scroll', _eventSP);

        // Remove leader line sample
        plugin.removeSample();
        _postitPlugin.unedit();
      });

      // EVENT "show.bs.modal" plug's settings popup
      ra.addEventListener('show.bs.modal', (e) => {
        // EVENT "scroll" on popup body
        // FIXME
        ra.addEventListener('scroll', _eventSP);

        document.getElementById('plugprop-sample').style.backgroundColor =
            S.getCurrent('wall')[0].style.backgroundColor || '#fff';
      });

      // EVENT "click" on plug's settings reset button
      ra.querySelector('button.reset').addEventListener('click', (e) => {
        plugin.removeSample();
        plugin.createSample(true);

        ra.querySelector(`input[name="size"]`).value = _ll.size;
        ra.querySelector(`input[value="${_ll.path}"]`).checked = true;
        ra.querySelector(`input[value="${_ll.line_type}"]`).checked = true;
      });

      // EVENT "click" on plug's settings "line type" options
      const _eventLineType = (e) => {
        _ll.line_type = e.target.value;
        _postitPlugin.applyPlugLineType(_ll);
      };
      ra.querySelectorAll(`input[name="type"]`)
          .forEach((el) => el.addEventListener('click', _eventLineType));

      // EVENT "click" on plug's settings "line path" options
      const _eventLinePath = (e) => _ll.path = e.target.value;
      ra.querySelectorAll(`input[name="path"]`)
          .forEach((el) => el.addEventListener('click', _eventLinePath));

      // Load color picker
      $(ra.querySelector('.cp')).colorpicker({
        parts: ['swatches'],
        swatchesWidth: ww < 435 ? ww - 90 : 435,
        color: '#fff',
        select: (e, color) => {
          _ll.color = color.css;
          _ll.setOptions ({
            dropShadow: _postitPlugin.getPlugDropShadowTemplate(color.css),
          });
        },
      });

      // EVENT "click" on plug's settings "color" options
      const _eventColor = (e) => {
        const el = e.target;
        const s = el.parentNode.querySelector('.cp-selected');

        if (s) {
          s.classList.remove('cp-selected');
        }

        el.classList.add ('cp-selected');
      };
      ra.querySelectorAll('.cp .ui-colorpicker-swatch')
          .forEach (el=> el.addEventListener('click', _eventColor));

      // EVENT "click" on plug's settings "save" button
      ra.querySelector('.btn-primary').addEventListener('click', (e) => {
        let toSave = {};

        toSave[_plug.startId] = $(_plug.obj.start);
        toSave[_plug.endId] = $(_plug.obj.end);

        S.set('plugs-to-save', toSave);

        _postitPlugin.updatePlugProperties ({
          label: ra.querySelector(`input[name="label"]`).value,
          startId: _plug.startId,
          endId: _plug.endId,
          size: _ll.size,
          path: _ll.path,
          color: _ll.color,
          line_type: _ll.line_type,
        });

        plugin.removeSample();
      });

      /// EVENTS "keyup & change" on plug's settings "line size" option
      const _eventKC = (e) => {
        if (_ll) {
          const el = e.target;
          const max = parseInt(el.getAttribute('max'));

          if (el.value > max) {
            el.value = max;
          } else {
            const min = parseInt(el.getAttribute('min'));

            if (el.value < min) {
              el.value = min;
            }
          }

          _ll.size = parseInt(el.value);
        }
      };
      ra.querySelector(`input[name="size"]`)
          .addEventListener('keyup', _eventKC);
      ra.querySelector(`input[name="size"]`)
          .addEventListener('change', _eventKC);
    },

    // METHOD createSample()
    createSample(reset) {
      const els = document.querySelectorAll('#plugprop-sample div');

      _ll = _postitPlugin.getPlugTemplate({
        start: els[0],
        end: els[1],
        line_size: reset ? undefined : _plug.obj.line_size,
        line_path: reset ? undefined : _plug.obj.path,
        line_type: reset ? undefined : _plug.obj.line_type,
        line_color: reset ? undefined : _plug.obj.color
      }, true);

      this.element[0].appendChild(
          document.querySelector('.leader-line:last-child'));
    },

    // METHOD removeSample()
    removeSample() {
      const s = this.element[0].querySelector('.leader-line');

      if (s) {
        document.body.appendChild(s);
        _ll.remove();
        _ll = null;
      }
    },

    // METHOD open()
    open (postitPlugin, plug) {
      const ra = this.element[0];

      H.openModal({item: ra});

      _plug = plug;
      _postitPlugin = postitPlugin;

      setTimeout (() => {
          const label = _plug.label.name;

          this.createSample();

          H.setColorpickerColor($(ra.querySelector('.cp')), _plug.obj.color);
          ra.querySelector(`input[name="label"]`).value =
              (label === '...') ? '' : label;
          ra.querySelector(`input[name="size"]`).value = _plug.obj.line_size;
          ra.querySelector(`input[value="${_plug.obj.path}"]`)
              .checked = true;
          ra.querySelector(`input[value="${_plug.obj.line_type}"]`)
              .checked = true;

        }, 350);
    },
  };

<?php echo $Plugin->getFooter()?>
