<?php
/**
  Javascript plugin - Account

  Scope: Global
  Element: #accountPopup
  Description: User account management
*/

  require_once (__DIR__.'/../prepend.php');

  use Wopits\DbCache;

  $Plugin = new Wopits\jQueryPlugin ('account');
  echo $Plugin->getHeader ();

?>

  /////////////////////////// PRIVATE METHODS ///////////////////////////

  const _getUserPictureTemplate = (src)=>
    {
      return (src) ? `<button type="button" class="btn-close img-delete"></button><img src="${src}">` : "<i class='fas fa-camera-retro fa-3x'></i>";
    };

  /////////////////////////// PUBLIC METHODS ////////////////////////////

  // Inherit from Wpt_accountForms
  Plugin.prototype = Object.create(Wpt_accountForms.prototype);
  Object.assign (Plugin.prototype,
  {
    // METHOD init ()
    init (args)
    {
      const plugin = this,
            $account = plugin.element,
            account = $account[0];

      if ($.support.touch)
        // EVENT "click" on modal "user" icon
        account.querySelector(".modal-title i.fa-user-circle")
          .addEventListener ("click",
            (e)=> bootstrap.Modal.getInstance($account[0]).hide ())

      // EVENT "click" on "delete" button
      account.querySelector(`[data-action="delete-account"]`)
        .addEventListener ("click", (e)=>
        {
          H.openConfirmPopup ({
            icon: "sad-tear",
            content: `<?=_("Do you really want to permanently delete your wopits account?")?>`,
            cb_ok: () => plugin.delete ()
          });
        });

      // EVENT "change" on profil picture
      $(`<input type="file" accept=".jpeg,.jpg,.gif,.png"
          class="upload" id="account-picture">`)
        .on("change",function (e)
          {
            const $upload = $(this);

            if (e.target.files && e.target.files.length)
            {
              H.getUploadedFiles (e.target.files, "\.(jpe?g|gif|png)$",
                (e, file) =>
                {
                  $upload.val ("");

                  if (H.checkUploadFileSize ({size: e.total}) &&
                      e.target.result)
                  {
                    H.fetchUpload (
                      "user/picture",
                      {
                        name: file.name,
                        size: file.size,
                        type: file.type,
                        content: e.target.result
                      },
                      // success cb
                      (d) => account.querySelector(".user-picture").innerHTML =
                               _getUserPictureTemplate (d.src));
                  }
                });
            }
          }).appendTo ("body");

      // EVENT "click" on user profil picture
      account.querySelector(".user-picture")
        .addEventListener ("click", (e)=>
        {
          e.stopImmediatePropagation ();

          // If delete img
          if (e.target.classList.contains ("img-delete"))
            H.openConfirmPopover ({
              item: $(e.target),
              placement: "left",
              title: `<i class="fas fa-trash fa-fw"></i> <?=_("Delete")?>`,
              content: `<?=_("Delete your profile picture?")?>`,
              cb_ok: () => plugin.deletePicture ()
            });
          else
            document.getElementById("account-picture").click ();
        });

      // EVENT "hide.bs.modal" on account popup
      account.addEventListener ("hide.bs.modal", (e)=>
        {
          const about = e.target.querySelector (`[name="about"]`),
                val = H.noHTML (about.value);

          if (val != about.dataset.oldvalue)
            plugin.updateField ({about: val}, $account.find(".modal-body"));
        });

      // EVENT "click" on account popup
      account.addEventListener ("click", (e)=>
        {
          const el = e.target;


          // EVENT "click" on account popup user data
          if (el.matches ("input,button.btn-change-input,"+
                          "button.btn-change-input *"))
          {
            const btn = el.tagName=="I"?el.parentNode:el,
                  field = (btn.tagName=="INPUT") ?
                             btn : btn.parentNode.querySelector("input"),
                  name = field.getAttribute ("name"),
                  value = field.value;
            let title,
                $popup;
  
            if (account.querySelector(".ldap-msg") &&
                btn.tagName == "INPUT" &&
                !name.match (/^fullname|visible|allow_emails$/))
              return;
  
            switch (name)
            {
              case "visible":
  
                if (field.checked)
                  H.openConfirmPopover ({
                    item: $(field),
                    placement: "top",
                    title: `<i class="fas fa-eye-slash fa-fw"></i> <?=_("Invisible mode")?>`,
                    content: `<?=_("By checking this option, sharing will be impossible, and you will be removed from all groups.<br>Become invisible anyway?")?>`,
                    cb_close: (btn) => (btn != "yes")&&(field.checked=false),
                    cb_ok: () => plugin.updateField ({visible: 0}, true)
                  });
                else
                  plugin.updateField ({visible: 1}, true);
                break;
  
              case "allow_emails":
  
                if (!field.checked)
                  H.openConfirmPopover ({
                    item: $(field),
                    placement: "top",
                    title: `<i class="fas fa-envelope fa-fw"></i> <?=_("Notify me by email")?>`,
                    content: `<?=_("By unchecking this option, you will no longer receive email notifications.<br>Disable notifications anyway?")?>`,
                    cb_close: (btn) => (btn != "yes")&&(field.checked=true),
                    cb_ok: () => plugin.updateField ({allow_emails: 0}, true)
                  });
                else
                  plugin.updateField ({allow_emails: 1}, true);
                break;
  
              case "password":
  
                H.loadPopup ("changePassword", {
                  open: false,
                  init: ($p)=>
                    $p[0].querySelector(".btn-primary").addEventListener (
                      "click", (e)=> plugin.onSubmit ($p, e)),
                  cb: ($p)=>
                  {
                    $p.find("input").val ("");
  
                    $p[0].dataset.field = "password";
                    $p[0].dataset.noclosure = true;
  
                    H.openModal ({item: $p});
                  }
                });
  
                break;
  
              case "username":
              case "fullname":
              case "email":
  
                H.loadPopup ("updateOneInput", {
                  open: false,
                  init: ($p)=>
                    $p[0].querySelector(".btn-primary").addEventListener (
                      "click", (e)=> plugin.onSubmit ($p, e)),
                  cb: ($p)=>
                  {
                    const $input = $p.find ("input");
  
                    switch (name)
                    {
                      case "username":
                        title = `<i class="fas fa-user"></i> <?=_("Login")?>`;
                        $input
                          .attr("maxlength", "<?=DbCache::getFieldLength('users', 'username')?>")
                          .attr("placeholder", `<?=_("username")?>`)
                          .attr("autocorrect", "off")
                          .attr ("autocapitalize", "off");
                        break;
                      case "fullname":
                        title = `<i class="fas fa-signature"></i> <?=_("Full name")?>`;
                        $input
                          .attr("maxlength", "<?=DbCache::getFieldLength('users', 'fullname')?>")
                          .attr("placeholder", `<?=_("full name")?>`);
                        break;
                      case "email":
                        title = `<i class="fas fa-envelope"></i> <?=_("Email")?>`;
                        $input
                          .attr("maxlength", "<?=DbCache::getFieldLength('users', 'email')?>")
                          .attr("placeholder", `<?=_("email")?>`)
                          .attr("autocorrect", "off")
                          .attr ("autocapitalize", "off");
                        break;
                    }
  
                    $p.find(".modal-dialog").addClass ("modal-sm");
                    $p.find(".modal-title").html (title);
  
                    $input.attr ("name", name);
                    $input.val (value);
  
                    $p[0].dataset.field = name;
                    $p[0].dataset.oldvalue = value;
                    $p[0].dataset.noclosure = true;
                    H.openModal ({item: $p});
                  }
                });
  
                break;
            }
          }
        });
    },

    // METHOD onSubmit ()
    onSubmit ($popup, e)
    {
      const plugin = this,
            field = $popup[0].dataset.field;

      e.stopImmediatePropagation ();

      $popup[0].dataset.noclosure = true;

      switch (field)
      {
        case "username":
        case "fullname":
        case "email":

          const $input = $popup.find ("input"),
                value = $input.val().trim ();

          if (value == $popup[0].dataset.oldvalue)
            return bootstrap.Modal.getInstance($popup).hide ();

          if (plugin.checkRequired ($input) && plugin.validForm ($input))
          {
            let data = {};

            data[field] = value;
            plugin.updateField (data);
          }

          break;

        case "password":

          const $inputs = $popup.find ("input");

          if (plugin.checkRequired ($inputs) && plugin.validForm ($inputs))
            plugin.updateField ({
              password: {
                current: $inputs[0].value,
                new: $inputs[1].value,
                confirm: $inputs[2].value
              }
            });

          break;
      }
    },

    // METHOD updateMainMenu ()
    updateMainMenu ()
    {
      // Update "invisible mode" icon in main menu.
      document.querySelector(".invisible-mode").style.display =
        (wpt_userData.settings.visible == 1)?"none":"inline-block";
    },

    // METHOD getProp ()
    getProp (prop)
    {
      return this.element.find(`input[name="${prop}"]`).val ();
    },

    // METHOD deletePicture ()
    deletePicture ()
    {
      const $account = this.element;

      H.request_ws (
        "DELETE",
        "user/picture",
        null,
        // success cb
        (d) =>
        {
          if (d.error_msg)
            H.raiseError (null, d.error_msg);
          else
            $account.find(".user-picture").html (_getUserPictureTemplate ());
        }
      );
    },

    // METHOD delete ()
    delete ()
    {
      H.fetch (
        "DELETE",
        "user",
        null,
        // success cb
        (d) =>
        {
          if (d.error_msg)
            H.displayMsg ({
              title: `<?=_("Account")?>`,
              type: "danger",
              msg: d.error_msg
            });
          else
            return location.href = "/r.php";
        });
    },

    // METHOD updateField ()
    updateField (args, noclosure)
    {
      const $account = this.element;

      H.request_ws (
        "POST",
        "user/update",
        args,
        // success cb
        (d) =>
        {
          if (d.error_msg)
            H.displayMsg ({
              title: `<?=_("Account")?>`,
              type: "warning",
              msg: d.error_msg
            });
          else
          {
            for (const k in d)
            {
              const field = $account.find(`[name="${k}"]`)[0];
      
              if (field)
              {
                if (k == "visible" && wpt_userData.settings.visible != d[k])
                {
                  const $wall = S.getCurrent ("wall");

                  wpt_userData.settings.visible = d[k];

                  if (d[k] != 1)
                  {
                    // Close all current opened walls and reload session.
                    if ($wall.length)
                    {
                      S.getCurrent("chat").chat ("hide");
                      $wall.wall ("closeAllWalls", false);
                      $wall.wall ("restorePreviousSession");
                    }
                  }
                  else if ($wall.length)
                    $wall.wall ("menu", {from: "wall", type: "have-wall"});

                  // Update "invisible mode" icon in main menu.
                  this.updateMainMenu ();
                }
                else
                {
                  field.dataset.oldvalue = d[k]||"";
                  field.value = d[k]||"";
                }
              }
            }

            if (!args.about)
              H.displayMsg ({
                title: `<?=_("Account")?>`,
                type: "success",
                msg: `<?=_("Your profile has been updated")?>`
              });

            // Close field update popup
            if (!noclosure)
              bootstrap.Modal.getInstance(S.get("mstack")[0]).hide ();
          }
        });
    }

  });

/////////////////////////// AT LOAD INIT //////////////////////////////

  document.addEventListener ("DOMContentLoaded", ()=>
    {
      if (H.isLoginPage ())
        return;

      setTimeout (()=>
        {
          // EVENT "click" on main menu account button
          document.getElementById("account").addEventListener("click", (e)=>
          {
            H.closeMainMenu ();
            H.loadPopup ("account");
          });

          $("#accountPopup").account ();
          $("#accountPopup").account ("updateMainMenu");
        }, 0);
    });

<?php echo $Plugin->getFooter ()?>
