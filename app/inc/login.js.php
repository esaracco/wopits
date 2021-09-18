<?php
/**
  Javascript plugin - User login & account creation

  Scope: Login page
  Elements: #login & #createAccountPopup
  Description: Login & User account creation
*/

  require_once (__DIR__.'/../prepend.php');

  $Plugin = new Wopits\jQueryPlugin ('login');
  echo $Plugin->getHeader ();

?>

/////////////////////////// METHODS ////////////////////////////

  // Inherit from Wpt_accountForms
  Plugin.prototype = Object.create(Wpt_accountForms.prototype);
  Object.assign (Plugin.prototype,
  {
    // METHOD init ()
    init (args)
    {
      const plugin = this,
            $login = plugin.element,
            $welcome = $("#desc-container");
      let $popup;

      if ($welcome.length && !ST.noDisplay ("welcome-msg"))
      {
        $welcome.html (`<div class="welcome alert alert-primary alert-dismissible fade show" role="alert"><button type="button" class="btn-close" data-bs-dismiss="alert"></button><h4 class="alert-heading"><?=_("Welcome to wopits!")?></h4><p><?=sprintf(_("A free, open-source sticky notes manager that respects your privacy, that's good, right? %s will let you do a lot of things, without ever having to worrying about where your data is going to or how it is monetized."), '<a href="https://wopits.esaracco.fr" target="_blank">wopits</a>')?></p><hr><p class="mb-0"><?=sprintf(_("Besides, you don't have to use it online: you can %sget the code%s and install it yourself!"), '<a href="https://github.com/esaracco/wopits" target="_blank">', '</a>')?></p><div class="mt-3"><button type="button" class="btn btn-sm btn-primary nowelcome"><?=_("I get it !")?></button></div></div>`);

        $welcome.find("button.nowelcome").on("click", function ()
          {
            ST.noDisplay ("welcome-msg", true);

            $(this).closest(".alert").remove ();
          });
      }

      $login
        .on("click", "button,a", function (e)
        {
         switch (e.target.dataset.type) 
          {
            case "login":

              if (plugin.checkRequired ($login.find("input"), false))
              {
                const du = $login.find("input[name='_directURL']").val ();

                plugin.login (H.trimObject ({
                  directURL: (du&&du.match(<?=WPT_DIRECTURL_REGEXP?>)) ?
                                `/?${du}` : null,
                  remember: $login.find("#remember")[0].checked,
                  username: $login.find("input[type='text']").val (),
                  password: $login.find("input[type='password']").val ()
                }, ['password']));
              }

              break;

            case "create":

              $popup = $("#createAccountPopup");

              H.cleanPopupDataAttr ($popup);

              plugin.resetCreateUserForm ();
              $popup[0].dataset.noclosure = true;
              H.openModal ({item: $popup});
              break;

            case "forgot":

              $popup = $("#resetPasswordPopup");

              H.cleanPopupDataAttr ($popup);

              $popup[0].dataset.noclosure = true;
              H.openModal ({item: $popup});
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

          $popup[0].dataset.noclosure = true;

          if ($popup.find(".confirm").length)
          {
            if (plugin.checkRequired ($popup.find("input").slice(0, 2)) &&
                plugin.validForm ($popup.find("input")))
            {
              plugin.createUser (H.trimObject ({
                _check:
                  $(".main-login form").find("input[name='_check']").val (),
                username: $form.find("input[name='username']").val (),
                fullname: $form.find("input[name='fullname']").val (),
                password: $form.find("input[name='password']").val (),
                email: $form.find("input[name='email']").val ()
              }, ['password']));
            }
          }
          else if (plugin.checkRequired ($popup.find("input")) &&
                   plugin.validForm ($popup.find("input")))
          {
            $popup.find(".main")
              .addClass("readonly")
              .find("input").attr("readonly", "readonly");

            $popup.find("form").prepend (`<div class="confirm"><div><?=_("Please, confirm your password:")?></div><div class="input-group mb-1"><span class="input-group-text"><i class="fas fa-shield-alt fa-fw fa-xs"></i> <i class="fas fa-key fa-fw fa-xs"></i></span><input class="form-control" type="password" name="password2" placeholder="<?=_("password confirmation")?>" required value=""></div><div><?=_("Please, confirm your email:")?></div><div class="input-group mb-4"><span class="input-group-text"><i class="fas fa-shield-alt fa-fw fa-xs"></i> <i class="fas fa-envelope fa-fw fa-xs"></i></span><input class="form-control" type="email" name="email2" required value="" placeholder="<?=_("email confirmation")?>"></div>`);

            $(`<button type="button" class="btn btn-info"><i class="fas fa-caret-left"></i> <?=_("Previous")?></button>`)
            .on("click", function ()
            {
              plugin.resetCreateUserForm ();
            })
            .insertBefore ($popup.find(".btn-primary"));

            $popup.find(".btn-primary").html(
              `<i class='fas fa-bolt fa-fw fa-xs'></i> <?=_("Create")?></i>`);

            setTimeout (() => $popup.find("input").eq(0).focus (), 150);
          }

        });

      if ($login.find("input[name='_directURL']").val()
            .indexOf("unsubscribe") != -1)
        H.infoPopup (`<?=_("Please log in to update your preferences")?>`);
    },

    // METHOD resetCreateUserForm ()
    resetCreateUserForm ()
    {
      const $popup = $("#createAccountPopup");

      $popup.find(".main")
        .removeClass("readonly")
        .find("input").removeAttr ("readonly");
      $popup.find(".confirm,.btn-info").remove ();
      $popup.find(".btn-primary")
        .html (`<?=_("Next")?> <i class="fas fa-caret-right"></i>`);
    },

    // METHOD login ()
    login (args)
    {
      H.fetch (
        "POST",
        "user/login",
        args,
        // success cb
        (d) =>
        {
          if (d.error_msg)
            H.displayMsg ({
              title: `<?=_("Log in")?>`,
              type: "warning",
              msg: d.error_msg
            });
          else
            return location.href = args.directURL||"/";
        });
    },

    // METHOD logout ()
    logout (args = {})
    {
      // Clean all data only if the logout order come from the main user
      // session.
      if (args.auto)
        location.href = "/login.php";
      else
        H.fetch (
          "POST",
          "user/logout",
          null,
          ()=> location.href = "/login.php");
    },

    // METHOD createUser ()
    createUser (args)
    {
      H.fetch (
        "PUT",
        "user",
        args,
        // success cb
        (d) =>
        {
          if (d.error_msg)
          {
            H.displayMsg ({
              title: `<?=_("Account creation")?>`,
              type: "warning",
              msg: d.error_msg
            });

            this.resetCreateUserForm ();
          }
          else
            return location.href = "/";
        });
    },

    // METHOD resetPassword ()
    resetPassword (args)
    {
      H.fetch (
        "POST",
        "user/resetPassword",
        args,
        // success cb
        (d) =>
        {
          if (d.error_msg)
            H.displayMsg ({
              title: `<?=_("Password reset")?>`,
              type: "warning",
              msg: d.error_msg
            });
          else
          {
            $("#resetPasswordPopup").modal ("hide");
            H.displayMsg ({
              title: `<?=_("Password reset")?>`,
              type: "info",
              msg: `<?=_("Your new password has been sent. Please, check your spam box if you don't receive it.")?>`
            });
          }
        }
      );
    }

  });

  /////////////////////////// AT LOAD INIT //////////////////////////////

  document.addEventListener ("DOMContentLoaded", ()=>
  {
    if (H.isLoginPage ())
      setTimeout (()=> $("#login").login (), 0);
  });

<?php echo $Plugin->getFooter ()?>
