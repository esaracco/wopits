<?php
/**
  Javascript plugin - Notes deadline picker

  Scope: Note
  Element: .dpick
  Description: Manage notes deadline
*/

  require_once (__DIR__.'/../prepend.php');

  $Plugin = new Wopits\jQueryPlugin ('dpick');
  echo $Plugin->getHeader ();

?>

  /////////////////////////// PUBLIC METHODS ////////////////////////////

  Plugin.prototype =
  {
    // METHOD init ()
    init ()
    {
      const $popup = this.element,
            $picker = $popup.find (".dpick"),
            $alert = $popup.find (".dpick-notify");

      this.settings.$picker = $picker;
      this.settings.$alert = $alert;

      $picker.datepicker ({
        showWeek: true,
        changeMonth: true,
        changeYear: true,
        dateFormat: "yy-mm-dd",
        minDate: moment().tz(wpt_userData.settings.timezone)
                   .add (1, "days").format ("Y-MM-DD"),
        onSelect: function (dt, $dp)
          {
            $alert.show ();
          }
      });

      $alert.find("#dp-notify")
        .on("change", function ()
          {
            const $div = $alert.find(">div:eq(1)");

            if (this.checked)
            {
              $div.show ("fade");
              $alert.find("#_dp-shift1")[0].checked = true;
              $(this).parent().removeClass ("disabled");
            }
            else
            {
              $div.hide ();
              $(this).parent().addClass ("disabled");
            }
          });

      $alert.find("input[type='number']")
        .on("click", function ()
        {
          $alert.find("#_dp-shift2")[0].checked = true;
        });
    },

    // METHOD open ()
    open ()
    {
      const $popup = this.element,
            $picker = this.settings.$picker,
            $alert = this.settings.$alert,
            $postit = S.getCurrent ("postit"),
            days = $postit[0].dataset.deadlinealertshift;

      if ($postit[0].dataset.deadline)
      {
        $picker.datepicker ("setDate", $postit[0].dataset.deadline);
      }
      else
      {
        $picker.datepicker ("setDate", null);
        $picker.find(".ui-state-active").removeClass("ui-state-active");
        $alert.hide ();
      }

      if (days !== undefined)
      {
        $alert.find("#dp-notify")[0].checked = true;
        $alert.find("#dp-notify").parent().removeClass ("disabled");
      }
      else
      {
        $alert.find("#dp-notify")[0].checked = false;
        $alert.find("#dp-notify").parent().addClass ("disabled");
      }
      $alert.find("#_dp-shift1")[0].checked = (days == 0);
      $alert.find("#_dp-shift2")[0].checked = (days > 0);
      $alert.find("input[type='number']").val (
        (days === undefined || days == 0) ? 1 : days);

      if (days === undefined)
        $alert.find(">div:eq(1)").hide ();
      else
        $alert.find(">div:eq(1)").show ();

      H.openModal ({item: $popup});
    },

    // METHOD save ()
    save ()
    {
      const $popup = this.element,
            $picker = this.settings.$picker,
            $alert = this.settings.$alert,
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

<?php echo $Plugin->getFooter ()?>
