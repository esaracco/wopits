<?php
/**
  Javascript plugin - Notes deadline picker

  Scope: Note
  Name: dpick
  Description: Manage notes deadline
*/

  require_once(__DIR__.'/../prepend.php');

?>

/////////////////////////////////// PLUGIN ////////////////////////////////////

P.register('dpick', class extends Wpt_pluginBase {
  // METHOD constructor()
  constructor(settings) {
    super(settings);
    const tag = this.tag;

    this.$picker = $(tag.querySelector('.dpick'));
    this.alert = tag.querySelector('.dpick-notify');
    this.notify = document.getElementById('dp-notify');

    // TODO Do not use jQuery here
    this.$picker.datepicker({
      showWeek: true,
      changeMonth: true,
      changeYear: true,
      dateFormat: 'yy-mm-dd',
      minDate: moment().tz(U.get('timezone')).add(1, 'days').format('Y-MM-DD'),
      onSelect: (dt, $dp) => H.show(this.alert),
    });

    // EVENT "change" on "alert" checkbox
    this.notify.addEventListener('change', (e) => {
      const el = e.target;
      const div = this.alert.querySelectorAll('div')[1];

      if (el.checked) {
        H.show(div);
        document.getElementById('_dp-shift1').checked = true;
        el.parentNode.classList.remove('disabled');
      } else {
        H.hide(div);
        el.parentNode.classList.add('disabled');
      }
    });

    // EVENT "click" on
    this.alert.querySelector(`input[type="number"]`)
      .addEventListener('change',
        (e) => document.getElementById('_dp-shift2').checked = true);
  }

  // METHOD open()
  open() {
    const $picker = this.$picker;
    const elAlert = this.alert;
    const postitTag = S.getCurrent('postit').tag;
    const shift  = postitTag.dataset.deadlinealertshift;
    const days = Number(shift);
    const dpNotify = this.notify;

    if (postitTag.dataset.deadline) {
      $picker.datepicker('setDate', postitTag.dataset.deadline);
    } else {
      $picker.datepicker('setDate', null);
      $picker[0].querySelector('.ui-state-active')
        .classList.remove('ui-state-active');
      H.hide(elAlert);
    }

    if (shift !== undefined) {
      dpNotify.checked = true;
      dpNotify.parentNode.classList.remove('disabled');
    } else {
      dpNotify.checked = false;
      dpNotify.parentNode.classList.add('disabled');
    }
    document.getElementById('_dp-shift1').checked = (days === 0);
    document.getElementById('_dp-shift2').checked = (days > 0);
    elAlert.querySelector(`input[type="number"]`).value =
      (shift === undefined || days === 0) ? 1 : days;

    if (shift === undefined) {
      H.hide(elAlert.querySelectorAll('div')[1]);
    } else {
      H.show(elAlert.querySelectorAll('div')[1]);
    }

    H.openModal({item: this.tag, noautofocus: true});
  }

  // METHOD save()
  save() {
    const $picker = this.$picker;
    const postit = S.getCurrent('postit');
    const postitTag = postit.tag;
    const v = $picker[0].value;

    if (v !== postitTag.dataset.deadline) {
      postitTag.dataset.updatetz = true;
      postitTag.classList.remove('obsolete');
    }

    postitTag.dataset.deadline = v;
    postit.setDeadline({deadline: v || '...'});

    if (v) {
      const elAlert = this.alert;

      postitTag.removeAttribute('data-deadlineepoch');

      if (this.notify.checked) {
        const shift = document.getElementById('_dp-shift1').checked ?
         0 : elAlert.querySelector(`input[type="number"]`).value;

        postitTag.dataset.deadlinealertshift = shift;
        postitTag.querySelector('.dates .end').classList.add('with-alert');
      } else {
        postitTag.removeAttribute('data-deadlinealertshift');
        postitTag.querySelector('.dates .end').classList.remove('with-alert');
      }
    }
  }
});
