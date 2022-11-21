<?php
/**
Javascript plugin - Notes relations properties

Scope: Note
Name: plugprop
Description: Set notes relations properties
*/

require_once(__DIR__.'/../prepend.php');

?>

/////////////////////////////////// PLUGIN ////////////////////////////////////

P.register('plugprop', class extends Wpt_pluginBase {
  // METHOD constructor()
  constructor(settings) {
    super(settings);
    const tag = this.tag;
    const ww = window.outerWidth;

    this.forceHide = false;
    this.ll = null;
    this.postit = null;
    this.plug = null;

    // EVENT "hide.bs.modal" plug's settings popup
    tag.addEventListener('hide.bs.modal', (e) => {
      if (this.forceHide || S.get('plugs-to-save')) return;

       const newLabel =
          tag.querySelector(`input[name="label"]`).value || '...';

       if (newLabel !== this.plug.label.name ||
           this.ll.size !== this.plug.obj.line_size ||
           this.ll.path !== this.plug.obj.path ||
           this.ll.line_type !== this.plug.obj.line_type ||
           this.ll.color !== this.plug.obj.color) {
         H.preventDefault (e);

         H.openConfirmPopup ({
           type: 'save-plugs-changes',
           icon: 'save',
           content: `<?=_("Save changes?")?>`,
           onConfirm: () => tag.querySelector('.btn-primary').click(),
           onClose: () => {
             this.forceHide = true;
             bootstrap.Modal.getInstance(tag).hide();
           },
         });
       }
    });

    // LOCAL FUNCTION _eventSP()
    // FIXME
    const _eventSP = (e) => this.ll && this.ll.position();

    // EVENT "hidden.bs.modal" plug's settings popup
    tag.addEventListener('hidden.bs.modal', (e) => {
      // FIXME
      tag.removeEventListener('scroll', _eventSP);

      // Remove leader line sample
      this.removeSample();
      this.postit.unedit();
    });

    // EVENT "show.bs.modal" plug's settings popup
    tag.addEventListener('show.bs.modal', (e) => {
      // EVENT "scroll" on popup body
      // FIXME
      tag.addEventListener('scroll', _eventSP);

      document.getElementById('plugprop-sample').style.backgroundColor =
          S.getCurrent('wall').tag.style.backgroundColor || '#fff';
    });

    // EVENT "click" on plug's settings reset button
    tag.querySelector('button.reset').addEventListener('click', (e) => {
      this.removeSample();
      this.createSample(true);

      tag.querySelector(`input[name="size"]`).value = this.ll.size;
      tag.querySelector(`input[value="${this.ll.path}"]`).checked = true;
      tag.querySelector(`input[value="${this.ll.line_type}"]`).checked = true;
    });

    // EVENT "click" on plug's settings "line type" options
    const _eventLineType = (e) => {
      this.ll.line_type = e.target.value;
      this.postit.applyPlugLineType(this.ll);
    };
    tag.querySelectorAll(`input[name="type"]`)
        .forEach((el) => el.addEventListener('click', _eventLineType));

    // EVENT "click" on plug's settings "line path" options
    const _eventLinePath = (e) => this.ll.path = e.target.value;
    tag.querySelectorAll(`input[name="path"]`)
        .forEach((el) => el.addEventListener('click', _eventLinePath));

    // Load color picker
    $(tag.querySelector('.cp')).colorpicker({
      parts: ['swatches'],
      swatchesWidth: ww < 435 ? ww - 90 : 435,
      color: '#fff',
      select: (e, color) => {
        this.ll.color = color.css;
        this.ll.setOptions ({
          dropShadow: this.postit.getPlugDropShadowTemplate(color.css),
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
    tag.querySelectorAll('.cp .ui-colorpicker-swatch')
        .forEach (el=> el.addEventListener('click', _eventColor));

    // EVENT "click" on plug's settings "save" button
    tag.querySelector('.btn-primary').addEventListener('click', (e) => {
      let toSave = {};

      toSave[this.plug.startId] = P.get(this.plug.obj.start, 'postit');
      toSave[this.plug.endId] = P.get(this.plug.obj.end, 'postit');

      S.set('plugs-to-save', toSave);

      this.postit.updatePlugProperties ({
        label: tag.querySelector(`input[name="label"]`).value,
        startId: this.plug.startId,
        endId: this.plug.endId,
        size: this.ll.size,
        path: this.ll.path,
        color: this.ll.color,
        line_type: this.ll.line_type,
      });

      this.removeSample();
    });

    /// EVENTS "keyup & change" on plug's settings "line size" option
    const _eventKC = (e) => {
      if (this.ll) {
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

        this.ll.size = parseInt(el.value);
      }
    };
    tag.querySelector(`input[name="size"]`)
        .addEventListener('keyup', _eventKC);
    tag.querySelector(`input[name="size"]`)
        .addEventListener('change', _eventKC);
  }

  // METHOD createSample()
  createSample(reset) {
    const els = document.querySelectorAll('#plugprop-sample div');

    this.ll = this.postit.getPlugTemplate({
      start: els[0],
      end: els[1],
      line_size: reset ? undefined : this.plug.obj.line_size,
      line_path: reset ? undefined : this.plug.obj.path,
      line_type: reset ? undefined : this.plug.obj.line_type,
      line_color: reset ? undefined : this.plug.obj.color
    }, true);

    this.tag.appendChild(document.querySelector('.leader-line:last-child'));
  }

  // METHOD removeSample()
  removeSample() {
    const s = this.tag.querySelector('.leader-line');

    if (s) {
      document.body.appendChild(s);
      this.ll.remove();
      this.ll = null;
    }
  }

  // METHOD open()
  open(postit, plug) {
    const tag = this.tag;

    H.openModal({item: tag});

    this.forceHide = false;
    this.plug = plug;
    this.postit = postit;

    // FIXME setTimeout()
    setTimeout (() => {
      const label = this.plug.label.name;

      this.createSample();

      H.setColorpickerColor($(tag.querySelector('.cp')), this.plug.obj.color);
      tag.querySelector(`input[name="label"]`).value =
        (label === '...') ? '' : label;
      tag.querySelector(`input[name="size"]`).value = this.plug.obj.line_size;
      tag.querySelector(`input[value="${this.plug.obj.path}"]`)
        .checked = true;
      tag.querySelector(`input[value="${this.plug.obj.line_type}"]`)
        .checked = true;
    }, 350);
  }
});
