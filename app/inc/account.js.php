<?php
  require_once (__DIR__.'/../class/Wpt_jQueryPlugins.php');
  $Plugin = new Wpt_jQueryPlugins ('account');
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
    init: function (args)
    {
      const plugin = this,
            $account = plugin.element;

      $account.find("[data-action='delete-account']")
        .on("click", function (e)
        {
          H.openConfirmPopup ({
            type: "delete-account",
            icon: "sad-tear",
            content: `<?=_("The deletion of your account will result in the deletion of all your walls and associated items.<p/>Do you really want to permanently delete your wopits account?")?>`,
            cb_ok: () => $("#accountPopup").account ("delete")
          });
        });

      $(`<input type="file" accept=".jpg,.gif,.png"
          class="upload account-picture">`)
        .on("change",function (e)
          {
            const $upload = $(this);

            if (e.target.files && e.target.files.length)
            {
              H.getUploadedFiles (e.target.files,
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
            H.openConfirmPopup ({
              type: "delete-account-picture",
              icon: "trash",
              content: `<?=_("Delete your profile photo?")?>`,
              cb_ok: () => $("#accountPopup").account ("deletePicture")
            });
          else
            $(".upload.account-picture").click ();
        });

      $("#account").on("click", function (e)
        {
          H.closeMainMenu ();

          H.cleanPopupDataAttr ($account);
          H.openModal ($account);
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
        .on("click", "button", function (e)
        {
          const $btn = $(this),
                $field = $btn.parent().prev (),
                name = $field.attr ("name"),
                value = $field.val ();
          let title,
              $popup;

          switch (name)
          {
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
                    .attr("maxlength", "<?=Wpt_dbCache::getFieldLength('users', 'username')?>")
                    .attr("placeholder", "<?=_("username")?>")
                    .attr("autocorrect", "off")
                    .attr ("autocapitalize", "none");
                  break;
                case "fullname":
                  title =`<i class="fas fa-signature"></i> <?=_("Full name")?>`;
                  $input
                    .attr("maxlength", "<?=Wpt_dbCache::getFieldLength('users', 'fullname')?>")
                    .attr("placeholder", "<?=_("full name")?>");
                  break;
                case "email":
                  title = `<i class="fas fa-envelope"></i> <?=_("Email")?>`;
                  $input
                    .attr("maxlength", "<?=Wpt_dbCache::getFieldLength('users', 'email')?>")
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

    // METHOD getProp ()
    getProp: function (prop)
    {
      return this.element.find("input[name='"+prop+"']").val ();
    },

    // METHOD deletePicture ()
    deletePicture: function ()
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
    delete: function ()
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
    updateField: function (args)
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
                field.dataset.oldvalue = d[k] || '';
                field.value = d[k] || '';
              }
            }

            if (!args.about)
              H.displayMsg (
                {
                  target: $account.find (".modal-body"),
                  type: "success",
                  msg: "<?=_("Your account has been updated")?>"
                });

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
