<?php

  require_once (__DIR__.'/../prepend.php');

  use Wopits\DbCache;

  $Plugin = new Wopits\jQueryPlugin ('account');
  echo $Plugin->getHeader ();

?>

/////////////////////////// METHODS ////////////////////////////

  function _getUserPictureTemplate (src)
  {
    return (src) ? `<button type="button" class="close img-delete"><span>×</span></button><img src="${src}">` : "<i class='fas fa-camera-retro fa-3x'></i>";
  }

  // Inherit from Wpt_accountForms
  Plugin.prototype = Object.create(Wpt_accountForms.prototype);
  Object.assign (Plugin.prototype,
  {
    // METHOD init ()
    init (args)
    {
      const plugin = this,
            $account = plugin.element;

      if ($.support.touch)
        $account.find(".modal-title i.fa-user-circle")
          .on("click", function ()
            {
              $account.modal ("hide");
            });

      // Update "invisible mode" icon in main menu.
      plugin.updateMainMenu ();

      // Main menu account link.
      $("#account").on("click", function (e)
        {
          H.closeMainMenu ();

          H.cleanPopupDataAttr ($account);
          H.openModal ($account);
        });

      $account.find("[data-action='delete-account']")
        .on("click", function (e)
        {
          H.openConfirmPopup ({
            icon: "sad-tear",
            content: `<?=_("The deletion of your account will result in the deletion of all your walls and associated items.<p/>Do you really want to permanently delete your wopits account?")?>`,
            cb_ok: () => plugin.delete ()
          });
        });

      $(`<input type="file" accept=".jpeg,.jpg,.gif,.png"
          class="upload account-picture">`)
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
                    H.request_ajax (
                      "PUT",
                      "user/picture",
                      {
                        name: file.name,
                        size: file.size,
                        type: file.type,
                        content: e.target.result
                      },
                      // success cb
                      (d) => $account.find(".user-picture").html (
                               _getUserPictureTemplate (d.src)));
                  }
                });
            }
          }).appendTo ("body");

      $account.find(".user-picture")
        .on("click", function (e)
        {
          e.stopImmediatePropagation ();

          // If delete img
          if (e.target.tagName == "SPAN")
            H.openConfirmPopover ({
              item: $account.find (".img-delete"),
              placement: "right",
              title: `<i class="fas fa-trash fa-fw"></i> <?=_("Delete")?>`,
              content: "<?=_("Delete your profile photo?")?>",
              cb_ok: () => plugin.deletePicture ()
            });
          else
            $(".upload.account-picture").click ();
        });

      $account
        .on("hide.bs.modal", function ()
        {
          const about = $(this).find("[name='about']")[0],
                val = H.noHTML (about.value);

          if (val != about.dataset.oldvalue)
            plugin.updateField ({about: val}, $account.find(".modal-body"));
        });

      $("#updateOneInputPopup .btn-primary,"+
        "#changePasswordPopup .btn-primary")
        .on("click", function (e)
        {
          const $popup = $(this).closest (".modal"),
                field = $popup[0].dataset.field;

          e.stopImmediatePropagation ();

          $popup[0].dataset.noclosure = true;

          H.displayMsg ({target:$popup, reset: true});

          switch (field)
          {
            case "username":
            case "fullname":
            case "email":

              const $input = $popup.find("input"),
                    value = $input.val().trim ();

              if (value == $popup[0].dataset.oldvalue)
                return $popup.modal ("hide");

              if (plugin.checkRequired ($input) && plugin.validForm ($input))
              {
                let data = {};
                data[field] = value;
                plugin.updateField (data);
              }

              break;

            case "password":

              const $inputs = $popup.find("input");

              if (plugin.checkRequired ($inputs) && plugin.validForm ($inputs))
                plugin.updateField ({
                  password: {
                    current: $inputs[0].value.trim (),
                    new: $inputs[1].value,
                    confirm: $inputs[2].value
                  }
                });

              break;
          }
        });

      $account
        .on("click", "button,input", function (e)
        {
          const $btn = $(this),
                $field = (e.target.tagName == 'INPUT') ?
                           $btn : $btn.parent().prev (),
                name = $field.attr("name"),
                value = $field.val ();
          let title,
              $popup;

          if ($account.find(".ldap-msg").length &&
              e.target.tagName == 'INPUT' && name != "fullname")
            return;

          switch (name)
          {
            case "visible":

              if ($field[0].checked)
                H.openConfirmPopover ({
                  item: $field,
                  placement: "top",
                  title: `<i class="fas fa-eye-slash fa-fw"></i> <?=_("Invisible mode")?>`,
                  content: "<?=_("Sharing will be impossible, and you will be removed from all groups.<br>Become invisible anyway?")?>",
                  cb_close: (btn) => (btn != "yes")&&($field[0].checked= false),
                  cb_ok: () => plugin.updateField ({visible: 0}, true)
                });
              else
                plugin.updateField ({visible: 1}, true);
              break;

            case "password":

              $popup = $("#changePasswordPopup");

              H.displayMsg ({target: $popup, reset: true});
              H.cleanPopupDataAttr ($popup);

              $popup.find("input").val ("");

              $popup[0].dataset.field = "password";
              $popup[0].dataset.noclosure = true;
              H.openModal ($popup);

              break;

            case "username":
            case "fullname":
            case "email":

              $popup = $("#updateOneInputPopup");

              const $input = $popup.find("input");

              H.displayMsg ({reset: true});
              H.cleanPopupDataAttr ($popup);

              switch (name)
              {
                case "username":
                  title = `<i class="fas fa-user"></i> <?=_("Login")?>`;
                  $input
                    .attr("maxlength", "<?=DbCache::getFieldLength('users', 'username')?>")
                    .attr("placeholder", "<?=_("username")?>")
                    .attr("autocorrect", "off")
                    .attr ("autocapitalize", "none");
                  break;
                case "fullname":
                  title =`<i class="fas fa-signature"></i> <?=_("Full name")?>`;
                  $input
                    .attr("maxlength", "<?=DbCache::getFieldLength('users', 'fullname')?>")
                    .attr("placeholder", "<?=_("full name")?>");
                  break;
                case "email":
                  title = `<i class="fas fa-envelope"></i> <?=_("Email")?>`;
                  $input
                    .attr("maxlength", "<?=DbCache::getFieldLength('users', 'email')?>")
                    .attr("placeholder", "<?=_("email")?>")
                    .attr("autocorrect", "off")
                    .attr ("autocapitalize", "none");

                  break;
              }

              $popup.find(".modal-dialog").addClass ("modal-sm");
              $popup.find(".modal-title").html (title);

              $input.attr("name", name);
              $input.val (value);

              $popup[0].dataset.field = name;
              $popup[0].dataset.oldvalue = value;
              $popup[0].dataset.noclosure = true;
              H.openModal ($popup);

              break;
          }
        })
    },

    // METHOD updateMainMenu ()
    updateMainMenu ()
    {
      document.querySelector(".invisible-mode").style.display =
        (wpt_userData.settings.visible == 1) ? "none" : "inline-block";
    },

    // METHOD getProp ()
    getProp (prop)
    {
      return this.element.find("input[name='"+prop+"']").val ();
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
      H.request_ajax (
        "DELETE",
        "user",
        null,
        // success cb
        (d) =>
        {
          if (d.error_msg)
            H.displayMsg ({type: "warning", msg: d.error_msg});
          else
            return location.href = '/r.php';
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
            H.displayMsg ({type: "warning", msg: d.error_msg});
          else
          {
            for (const k in d)
            {
              const field = $account.find("[name='"+k+"']")[0];
      
              if (field)
              {
                if (k == "visible")
                {
                  const $wall = S.getCurrent ("wall");

                  wpt_userData.settings.visible = d[k];

                  if (d[k] != 1)
                  {
                    // Close all current opened walls and reload session.
                    if ($wall.length)
                    {
                      S.getCurrent ("chatroom").chatroom ("hide");
                      $wall.wall("closeAllWalls", false);
                      $wall.wall("restorePreviousSession");
                    }
                  }
                  else if ($wall.length)
                    $wall.wall ("menu", {from: "wall", type: "have-wall"});

                  // Update "invisible mode" icon in main menu.
                  this.updateMainMenu ();
                }
                else
                {
                  field.dataset.oldvalue = d[k]||'';
                  field.value = d[k]||'';
                }
              }
            }

            if (!args.about)
              H.displayMsg ({
                target: $account.find (".modal-body"),
                type: "success",
                msg: "<?=_("Your profile has been updated")?>"
              });

            if (!noclosure)
              $(".modal:visible").last().modal ("hide");
          }
        });
    }

  });

  /////////////////////////// AT LOAD INIT //////////////////////////////

  $(function ()
    {
      const $plugin = $("#accountPopup");
  
      if ($plugin.length)
        $plugin.account ();
    });

<?php echo $Plugin->getFooter ()?>
