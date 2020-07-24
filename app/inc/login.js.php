<?php
  require_once (__DIR__.'/../class/Wpt_jQueryPlugins.php');
  $Plugin = new Wpt_jQueryPlugins ('login');
  echo $Plugin->getHeader ();
?>

/////////////////////////// METHODS ////////////////////////////

  // Inherit from Wpt_accountForms
  Plugin.prototype = Object.create(Wpt_accountForms.prototype);
  Object.assign (Plugin.prototype,
  {
    // METHOD init ()
    init: function (args)
    {
      const plugin = this,
            $login = plugin.element;
      let $popup;

      $(".login-page .modal")
        .on('hidden.bs.modal', function(e)
        {
          H.displayMsg ({target:$(this), reset: true});
        });

      $login
        .on("click", "button,a", function (e)
        {
         switch (e.target.dataset.type) 
          {
            case "login":

              if (plugin.checkRequired ($login.find("input")))
              {
                let dl = $login.find("input[name='_directURL']").val ();

                if (dl && !dl.match (/^\/(a|s)\/w\/\d+(\/p\/\d+)?$/))
                  dl = "";

                plugin.login ({
                  _directURL: dl,
                  remember: $login.find("#remember")[0].checked,
                  username: $login.find("input[type='text']").val().trim (), 
                  password: $login.find("input[type='password']").val().trim ()
                });
              }

              break;

            case "create":

              $popup = $("#createAccountPopup");

              H.displayMsg ({reset: true});
              H.cleanPopupDataAttr ($popup);

              plugin.resetCreateUserForm ();
              $popup[0].dataset.noclosure = true;
              H.openModal ($popup);
              break;

            case "forgot":

              $popup = $("#resetPasswordPopup");

              H.cleanPopupDataAttr ($popup);
              H.displayMsg ({reset: true});

              $popup[0].dataset.noclosure = true;
              H.openModal ($popup);
              break;

          }
        })
        .on("keypress", function (e)
        {
          if (e.which == 13 && e.target.tagName == "INPUT")
          {
            e.preventDefault ();

            $(this).find(".btn-success").click ();
          }
        });

      $("#resetPasswordPopup .btn-primary")
        .on("click", function (e)
        {
          const $popup = $(this).closest(".modal"),
                $input = $popup.find("input");

          e.stopImmediatePropagation ();

          $popup[0].dataset.noclosure = true;

          if (plugin.checkRequired ($input) && plugin.validForm ($input))
          {
            plugin.resetPassword ({email: $input.val().trim ()});
            $input.val ("");
          }
        });

      $("#createAccountPopup .btn-primary")
        .on("click", function (e)
        {
          const $popup = $(this).closest(".modal"),
                $form = $popup.find("form");

          e.stopImmediatePropagation ();

          H.displayMsg ({target:$popup, reset: true});

          $popup[0].dataset.noclosure = true;

          if ($popup.find(".confirm").length)
          {
            if (plugin.checkRequired ($popup.find("input").slice(0, 2)) &&
                plugin.validForm ($popup.find("input")))
            {
              plugin.createUser ({
                _check: $(".main-login form")
                          .find("input[name='_check']").val (),
                username: $form.find("input[name='username']").val (),
                fullname: $form.find("input[name='fullname']").val (),
                password: $form.find("input[name='password']").val (),
                email: $form.find("input[name='email']").val ()
              });
            }
          }
          else if (plugin.checkRequired ($popup.find("input")) &&
                   plugin.validForm ($popup.find("input")))
          {
            H.displayMsg ({reset: true});

            $popup.find(".main")
              .addClass("readonly")
              .find("input").attr("readonly", "readonly");

            $popup.find("form").prepend (`
              <div class="confirm">
                <div><?=_("Please, confirm your password:")?></div>
                <div class="input-group mb-1">
                  <div class="input-group-append"><span class="input-group-text"><i class="fas fa-shield-alt fa-fw fa-xs"></i> <i class="fas fa-key fa-fw fa-xs"></i></span></div>
                  <input class="form-control" type="password" name="password2" placeholder="<?=_("password confirmation")?>" required value="">
                </div>

                <div><?=_("Please, confirm your email:")?></div>
                <div class="input-group mb-4">
                  <div class="input-group-append"><span class="input-group-text"><i class="fas fa-shield-alt fa-fw fa-xs"></i> <i class="fas fa-envelope fa-fw fa-xs"></i></span></div>
                  <input class="form-control" type="email" name="email2" required value="" placeholder="<?=_("email confirmation")?>">

              </div>
            `);

            $('<button type="button" class="btn btn-info"><i class="fas fa-caret-left"></i> <?=_("Previous")?></button>')
            .on("click", function ()
            {
              plugin.resetCreateUserForm ();
            })
            .insertBefore ($popup.find(".btn-primary"));

            $popup.find(".btn-primary").html(
              "<i class='fas fa-bolt fa-fw fa-xs'></i> <?=_("Create")?></i>");

            setTimeout (() => $popup.find("input").eq(0).focus (), 150);
          }

        });
    },

    // METHOD resetCreateUserForm ()
    resetCreateUserForm: function ()
    {
      const $popup = $("#createAccountPopup");

      $popup.find(".main")
        .removeClass("readonly")
        .find("input").removeAttr ("readonly");
      $popup.find(".confirm,.btn-info").remove ();
      $popup.find(".btn-primary")
        .html ('<?=_("Next")?> <i class="fas fa-caret-right"></i>');
    },

    // METHOD login ()
    login: function (args)
    {
      H.request_ajax (
        "POST",
        "user/login",
        args,
        // success cb
        (d) =>
        {
          if (d.error_msg)
            H.displayMsg ({type: "warning", msg: d.error_msg});
          else
            return location.href = (args._directURL) ?
              args._directURL : "/";
        });
    },

    // METHOD logout ()
    logout: function (args = {})
    {
      location.href = "/login.php";

      // Clean all data only if the logout order come from the main user
      // session.
      if (!args.auto)
        H.request_ajax (
          "POST",
          "user/logout");
    },

    // METHOD createUser ()
    createUser: function (args)
    {
      H.request_ajax (
        "PUT",
        "user",
        args,
        // success cb
        (d) =>
        {
          if (d.error_msg)
          {
            H.displayMsg ({type: "warning", msg: d.error_msg});

            this.resetCreateUserForm ();
          }
          else
            return location.href = "/";
        });
    },

    // METHOD resetPassword ()
    resetPassword: function (args)
    {
      H.request_ajax (
        "POST",
        "user/resetPassword",
        args,
        // success cb
        (d) =>
        {
          if (d.error_msg)
            H.displayMsg ({type: "warning", msg: d.error_msg});
          else
          {
            $("#resetPasswordPopup").modal ("hide");
            H.displayMsg ({
                noclosure: true,
                type: "info",
                msg: "<?=_("Your new password has been sent. Please, check your spam box if you don't receive it.")?>"
            });
          }
        }
      );
    }

  });

  /////////////////////////// AT LOAD INIT //////////////////////////////

  $(function ()
  {
    if (location.href.match (/login\.php/))
      $("#login").login ();
  });

<?php echo $Plugin->getFooter ()?>
