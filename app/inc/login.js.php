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
            login = $login[0];
      let $popup;

      if (!ST.noDisplay ("welcome-msg"))
      {
        const welcome = document.getElementById ("desc-container");

        welcome.innerHTML = `<div class="welcome alert alert-primary alert-dismissible fade show" role="alert"><button type="button" class="btn-close" data-bs-dismiss="alert"></button><h4 class="alert-heading"><?=_("Welcome to wopits!")?></h4><p><?=sprintf(_("A free, open-source sticky notes manager that respects your privacy, that's good, right? %s will let you do a lot of things, without ever having to worrying about where your data is going to or how it is monetized."), '<a href="https://wopits.esaracco.fr" target="_blank">wopits</a>')?></p><hr><p class="mb-0"><?=sprintf(_("Besides, you don't have to use it online: you can %sget the code%s and install it yourself!"), '<a href="https://github.com/esaracco/wopits" target="_blank">', '</a>')?></p><div class="mt-3"><button type="button" class="btn btn-sm btn-primary nowelcome"><?=_("I get it !")?></button></div></div>`;

        // EVENT "closed.bs.alert" on alert
        welcome.querySelector(".alert").addEventListener("closed.bs.alert",
          (e)=> H.setAutofocus (login));

        // EVENT "click" on "I get it!" button"
        welcome.querySelector("button.nowelcome").addEventListener ("click",
          (e)=>
          {
            ST.noDisplay ("welcome-msg", true);
            e.target.closest(".alert").remove ();
            H.setAutofocus (login);
          });

        welcome.focus ();
      }
      else
        H.setAutofocus (login);

      // EVENT "click" on buttons and a links
      login.addEventListener ("click", (e)=>
        {
          const el = e.target;

          if (el.tagName == "BUTTON" || el.tagName == "A")
          {
            switch (el.dataset.type) 
            {
              case "login":

                if (plugin.checkRequired ($login.find("input"), false))
                {
                  const du =
                          login.querySelector(`input[name="_directURL"]`).value;

                  plugin.login (H.trimObject ({
                    directURL: (du&&du.match(<?=WPT_DIRECTURL_REGEXP?>)) ?
                                  `/?${du}` : null,
                    remember: document.getElementById("remember").checked,
                    username: login.querySelector(`input[type="text"]`).value,
                    password: login.querySelector(`input[type="password"]`)
                                .value
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
          }
        });

      // EVENT "keypress" on inputs
      login.addEventListener ("keypress", (e)=>
        {
          if (e.which == 13 && e.target.tagName == "INPUT")
          {
            e.preventDefault ();

            login.querySelector(".btn-success").click ();
          }
        });

      // EVENT "click" on reset password popup "send" button
      document.querySelector("#resetPasswordPopup .btn-primary")
        .addEventListener ("click", (e)=>
        {
          const popup = e.target.closest (".modal"),
                input = popup.querySelector ("input"),
                $input = $(input);

          e.stopImmediatePropagation ();

          popup.dataset.noclosure = true;

          if (plugin.checkRequired ($input) && plugin.validForm ($input))
          {
            plugin.resetPassword ({email: input.value.trim ()});
            input.value = "";
          }
        });

      // EVENT "click" on create account popup "create" button
      document.querySelector("#createAccountPopup .btn-primary")
        .addEventListener ("click", (e)=>
        {
          const popup = e.target.closest (".modal"),
                $popup = $(popup),
                form = popup.querySelector ("form"),
                $form = $(form);

          e.stopImmediatePropagation ();

          popup.dataset.noclosure = true;

          if (popup.querySelector (".confirm"))
          {
            if (plugin.checkRequired ($popup.find("input").slice(0, 2)) &&
                plugin.validForm ($popup.find("input")))
            {
              plugin.createUser (H.trimObject ({
                _check: document.querySelector(
                          `.main-login form input[name="_check"]`).value,
                username: form.querySelector(`input[name="username"]`).value,
                fullname: form.querySelector(`input[name="fullname"]`).value,
                password: form.querySelector(`input[name="password"]`).value,
                email: form.querySelector(`input[name="email"]`).value
              }, ["password"]));
            }
          }
          else if (plugin.checkRequired ($popup.find("input")) &&
                   plugin.validForm ($popup.find("input")))
          {
            const main = popup.querySelector (".main"),
                  footer = popup.querySelector (".modal-footer");

            main.classList.add ("readonly");
            main.querySelectorAll("input").forEach (el=> el.readOnly = true);

            // Display confirmation section
            form.insertBefore ($(`<div class="confirm"><div><?=_("Please, confirm your password:")?></div><div class="input-group mb-1"><span class="input-group-text"><i class="fas fa-shield-alt fa-fw fa-xs"></i> <i class="fas fa-key fa-fw fa-xs"></i></span><input class="form-control" type="password" name="password2" placeholder="<?=_("password confirmation")?>" required value=""></div><div><?=_("Please, confirm your email:")?></div><div class="input-group mb-4"><span class="input-group-text"><i class="fas fa-shield-alt fa-fw fa-xs"></i> <i class="fas fa-envelope fa-fw fa-xs"></i></span><input class="form-control" type="email" name="email2" required value="" placeholder="<?=_("email confirmation")?>"></div>`)[0], form.firstChild);

            // Display "previous" button
            footer.insertBefore ($(`<button type="button" class="btn btn-info"><i class="fas fa-caret-left"></i> <?=_("Previous")?></button>`)[0], footer.firstChild);

            // EVENT "click" on "previous" button
            popup.querySelector(".btn-info").addEventListener ("click", (e)=>
              plugin.resetCreateUserForm ());

            popup.querySelector(".btn-primary").innerHTML = 
              `<i class="fas fa-bolt fa-fw fa-xs"></i> <?=_("Create")?></i>`;

            setTimeout (() => popup.querySelector("input").focus (), 150);
          }

        });

      if (login.querySelector(`input[name="_directURL"]`).value
            .indexOf("unsubscribe") != -1)
        H.infoPopup (`<?=_("Please log in to update your preferences")?>`);
    },

    // METHOD resetCreateUserForm ()
    resetCreateUserForm ()
    {
      const popup = document.getElementById ("createAccountPopup"),
            main = popup.querySelector (".main");

      main.classList.remove ("readonly");
      main.querySelectorAll("input").forEach (el=> el.readOnly = false);

      popup.querySelectorAll(".confirm,.btn-info").forEach (el=> el.remove ());
      popup.querySelector(".btn-primary").innerHTML =
        `<?=_("Next")?> <i class="fas fa-caret-right"></i>`;
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
          // success cb
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
