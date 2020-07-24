<?php
  require_once (__DIR__.'/../class/Wpt_jQueryPlugins.php');
  $Plugin = new Wpt_jQueryPlugins ('datePicker');
  echo $Plugin->getHeader ();
?>

  /////////////////////////// PUBLIC METHODS ////////////////////////////

  Plugin.prototype =
  {
    // METHOD init ()
    init: function (args)
    {
      const $popup = this.element,
            $picker = $popup.find(".date-picker"),
            $alert = $popup.find(".date-picker-notify");

      $picker.datepicker ({
        showWeek: true,
        changeMonth: true,
        changeYear: true,
        dateFormat: "yy-mm-dd",
        minDate: moment().tz(wpt_userData.settings.timezone)
                   .add (1, "days").format ("Y-MM-DD")
      });

      $alert.find("#dp-notify")
        .on("change", function ()
          {
            const $div = $(this).closest(".date-picker-notify")
                           .find(">div:eq(1)");

            if (this.checked)
            {
              $div.show ("fade");
              $alert.find("#_dp-shift1")[0].checked = true;
            }
            else
              $div.hide ();
          });

      $alert.find("input[type='number']")
        .on("click", function ()
        {
          $alert.find("#_dp-shift2")[0].checked = true;
        });
    },

    // METHOD open ()
    open: function (args)
    {
      const $popup = this.element,
            $picker = $popup.find(".date-picker"),
            $alert = $popup.find(".date-picker-notify"),
            $postit = S.getCurrent ("postit"),
            days = $postit[0].dataset.deadlinealertshift;

      if ($postit[0].dataset.deadline)
        $picker.datepicker ("setDate", $postit[0].dataset.deadline);
      else
      {
        $picker.datepicker ("setDate", null);
        $picker.find(".ui-state-active").removeClass("ui-state-active");
      }

      $alert.find("#dp-notify")[0].checked = (days !== undefined);
      $alert.find("#_dp-shift1")[0].checked = (days == 0);
      $alert.find("#_dp-shift2")[0].checked = (days > 0);
      $alert.find("input[type='number']").val (
        (days === undefined || days == 0) ? 1 : days);

      if (days === undefined)
        $alert.find(">div:eq(1)").hide ();
      else
        $alert.find(">div:eq(1)").show ();

      H.openModal ($popup);
    },

    // METHOD close ()
    save: function ()
    {
      const $popup = this.element,
            $picker = $popup.find(".date-picker"),
            $alert = $popup.find(".date-picker-notify"),
            $postit = S.getCurrent ("postit"),
            v = $picker.val ();

        if (v != $postit[0].dataset.deadline)
        {
          $postit[0].dataset.updatetz = true;
          $postit.removeClass ("obsolete");
        }

        $postit[0].dataset.deadline = v;
        $postit.postit ("setDeadline", {deadline: v||"..."});

        if (v)
        {
          $postit[0].removeAttribute ("data-deadlineepoch");

          if ($alert.find("#dp-notify")[0].checked)
          {
            const shift = ($alert.find("#_dp-shift1")[0].checked) ?
             0 : $alert.find("input[type='number']").val ();

            if (shift == 0)
              $postit[0].dataset.deadlinealertshift = 0;
            else
              $postit[0].dataset.deadlinealertshift = shift;

            $postit.find(".dates .end").addClass ("with-alert");
          }
          else
          {
            $postit[0].removeAttribute ("data-deadlinealertshift");
            $postit.find(".dates .end").removeClass ("with-alert");
          }
        }
    }
  };

  /////////////////////////// AT LOAD INIT //////////////////////////////

  $(function ()
    {
      const $plugin = $("#datePickerPopup");

      if ($plugin.length)
        $plugin.datePicker ();
    });

<?php echo $Plugin->getFooter ()?>
