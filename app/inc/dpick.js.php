<?php
/**
  Javascript plugin - Notes deadline picker

  Scope: Note
  Element: .dpick
  Description: Manage notes deadline
*/

  require_once(__DIR__.'/../prepend.php');

  $Plugin = new Wopits\jQueryPlugin('dpick');
  echo $Plugin->getHeader();

?>

  /////////////////////////// PUBLIC METHODS ////////////////////////////

  Plugin.prototype = {
    // METHOD init()
    init() {
      const popup = this.element[0];
      const $picker = $(popup.querySelector('.dpick'));
      const elAlert = popup.querySelector('.dpick-notify');

      this.settings.$picker = $picker;
      this.settings.alert = elAlert;

      $picker.datepicker({
        showWeek: true,
        changeMonth: true,
        changeYear: true,
        dateFormat: 'yy-mm-dd',
        minDate:
          moment().tz(wpt_userData.settings.timezone)
            .add(1, 'days').format('Y-MM-DD'),
        onSelect: (dt, $dp) => H.show(elAlert),
      });

      // EVENT "change" on "alert" checkbox
      document.getElementById('dp-notify').addEventListener('change', (e) => {
        const el = e.target;
        const div = elAlert.querySelectorAll('div')[1];

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
      elAlert.querySelector(`input[type="number"]`)
        .addEventListener('change',
          (e) => document.getElementById('_dp-shift2').checked = true);
    },

    // METHOD open()
    open() {
      const $picker = this.settings.$picker;
      const elAlert = this.settings.alert;
      const $postit = S.getCurrent('postit');
      const shift  = $postit[0].dataset.deadlinealertshift;
      const days = Number(shift);
      const dpNotify = document.getElementById('dp-notify');

      if ($postit[0].dataset.deadline) {
        $picker.datepicker('setDate', $postit[0].dataset.deadline);
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

      H.openModal({item: this.element[0], noautofocus: true});
    },

    // METHOD save()
    save() {
      const $picker = this.settings.$picker;
      const postit = S.getCurrent('postit')[0];
      const v = $picker[0].value;

      if (v !== postit.dataset.deadline) {
        postit.dataset.updatetz = true;
        postit.classList.remove('obsolete');
      }

      postit.dataset.deadline = v;
      $(postit).postit('setDeadline', {deadline: v || '...'});

      if (v) {
        const elAlert = this.settings.alert;

        postit.removeAttribute('data-deadlineepoch');

        if (document.getElementById('dp-notify').checked) {
          const shift = document.getElementById('_dp-shift1').checked ?
           0 : elAlert.querySelector(`input[type="number"]`).value;

          postit.dataset.deadlinealertshift = shift;
          postit.querySelector('.dates .end').classList.add('with-alert');
        } else {
          postit.removeAttribute('data-deadlinealertshift');
          postit.querySelector('.dates .end').classList.remove('with-alert');
        }
      }
    }
  };

<?php echo $Plugin->getFooter()?>
