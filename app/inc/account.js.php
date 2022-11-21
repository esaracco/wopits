<?php
/**
Javascript plugin - Account

Scope: Global
Name: account
Description: User account management
*/

require_once(__DIR__.'/../prepend.php');

use Wopits\DbCache;

?>

(() => {
  'use strict';

/////////////////////////////////// PLUGIN ////////////////////////////////////

P.register('account', class extends Wpt_accountForms {
  // METHOD constructor()
  constructor(settings) {
    super(settings);
    this.settings = settings;
    this.tag = settings.tag;

    const tag = this.tag;
    const deleteBtn = tag.querySelector(`[data-action="delete-account"]`);

    // EVENT "click" on "delete" button (only if no LDAP mode)
    if (deleteBtn) {
      deleteBtn.addEventListener('click', (e) => {
        H.openConfirmPopup({
          icon: 'sad-tear',
          content: `<?=_("Do you really want to permanently delete your wopits account?")?>`,
          onConfirm: () => this.delete(),
        });
      });
    }

    // Create input to upload user's profil picture
    H.createUploadElement({
      attrs: {id: 'account-picture', accept: '.jpeg,.jpg,.gif,.png'},
      onChange: (e) => {
        const el = e.target;

        if (!el.files || !el.files.length) return;

        H.getUploadedFiles(
          e.target.files,
          '\.(jpe?g|gif|png)$',
          (e, file) => {
            el.value = '';

            if (H.checkUploadFileSize({size: e.total}) &&
                e.target.result) {
              H.fetchUpload(
                'user/picture',
                {
                  name: file.name,
                  size: file.size,
                  type: file.type,
                  content: e.target.result,
                },
                // success cb
                (d) => {
                   tag.querySelector('.user-picture').innerHTML =
                     this.getUserPictureTemplate(d.src)
                });
            }
          });
      }
    });

    // EVENT "click" on user profil picture
    tag.querySelector('.user-picture').addEventListener('click', (e) => {
      e.stopImmediatePropagation();

      // If delete img
      if (e.target.classList.contains('img-delete')) {
        H.openConfirmPopover({
          item: e.target,
          placement: 'left',
          title: `<i class="fas fa-trash fa-fw"></i> <?=_("Delete")?>`,
          content: `<?=_("Delete your profile picture?")?>`,
          onConfirm: () => this.deletePicture(),
        });
      } else {
        document.getElementById('account-picture').click();
      }
    });

    // EVENT "hide.bs.modal" on account popup
    tag.addEventListener('hide.bs.modal', (e) => {
      const about = e.target.querySelector(`[name="about"]`);
      const val = H.noHTML(about.value);

      if (val !== about.dataset.oldvalue) {
        this.updateField({about: val}, tag.querySelector('.modal-body'));
      }
    });

    // EVENT "click" on account popup
    tag.addEventListener('click', (e) => {
      const el = e.target;

      // EVENT "click" on account popup user data
      if (el.matches('input,button.btn-change-input,'+
                     'button.btn-change-input *')) {
        const btn = el.tagName === 'I' ? el.parentNode : el;
        const field = (btn.tagName === 'INPUT') ?
                           btn : btn.parentNode.querySelector('input');
        const name = field.getAttribute('name');
        const value = field.value;
        let title;

        if (tag.querySelector('.ldap-msg') &&
            btn.tagName === 'INPUT' &&
            !name.match(/^fullname|visible|allow_emails$/)) {
          return;
        }

        switch (name) {
          case 'visible':
            if (field.checked) {
              H.openConfirmPopover({
                item: field,
                placement: 'top',
                title: `<i class="fas fa-eye-slash fa-fw"></i> <?=_("Invisible mode")?>`,
                content: `<?=_("By checking this option, sharing will be impossible, and you will be removed from all groups.<br>Become invisible anyway?")?>`,
                onClose: (btn) => {
                  if (btn !== 'yes') {
                    field.checked = false;
                  }
                },
                onConfirm: () => this.updateField({visible: 0}, true),
              });
            } else {
              this.updateField({visible: 1}, true);
            }
            break;
          case 'allow_emails':
            if (!field.checked) {
              H.openConfirmPopover({
                item: field,
                placement: 'top',
                title: `<i class="fas fa-envelope fa-fw"></i> <?=_("Notify me by email")?>`,
                content: `<?=_("By unchecking this option, you will no longer receive email notifications.<br>Disable notifications anyway?")?>`,
                onClose: (btn) => {
                  if (btn !== 'yes') {
                    field.checked = true;
                  }
                },
                onConfirm: () => this.updateField({allow_emails: 0}, true),
              });
            } else {
              this.updateField({allow_emails: 1}, true);
            }
            break;
            case 'password':
              H.loadPopup('changePassword', {
                open: false,
                init: (p) =>
                  p.querySelector('.btn-primary').addEventListener('click',
                    (e) => this.onSubmit(p, e)),
                cb: (p) => {
                  p.querySelectorAll('input').forEach((el) => el.value = '');
                  p.dataset.field = 'password';
                  p.dataset.noclosure = true;

                  H.openModal({item: p});
                },
              });
              break;
            case 'username':
            case 'fullname':
            case 'email':
              H.loadPopup('updateOneInput', {
                open: false,
                init: (p) =>
                  p.querySelector('.btn-primary').addEventListener('click',
                    (e) => this.onSubmit(p, e)),
                cb: (p) => {
                  const input = H.createElement('input', {
                    className: 'form-control',
                    type: 'text',
                    required: true,
                  });

                  switch (name) {
                    case 'username':
                      title = `<i class="fas fa-user"></i> <?=_("Login")?>`;
                      H.setAttributes(input, {
                        autocapitalize: 'off',
                        autocomplete: 'on',
                        autocorrect: 'off',
                        maxlength: <?=DbCache::getFieldLength('users', 'username')?>,
                        placeholder: `<?=_("username")?>`,
                      });
                      break;
                    case 'fullname':
                      title = `<i class="fas fa-signature"></i> <?=_("Full name")?>`;
                      H.setAttributes(input, {
                        autocomplete: 'off',
                        maxlength: <?=DbCache::getFieldLength('users', 'fullname')?>,
                        placeholder: `<?=_("full name")?>`,
                      });
                      break;
                    case 'email':
                      title = `<i class="fas fa-envelope"></i> <?=_("Email")?>`;
                      H.setAttributes(input, {
                        autocapitalize: 'off',
                        autocomplete: 'off',
                        autocorrect: 'off',
                        maxlength: <?=DbCache::getFieldLength('users', 'email')?>,
                        placeholder: `<?=_("email")?>`,
                        type: 'email',
                      });
                      break;
                  }

                  p.querySelector('.modal-dialog').classList.add('modal-sm');
                  p.querySelector('.modal-title').innerHTML = title;

                  input.setAttribute('name', name);
                  input.value = value;

                  p.dataset.field = name;
                  p.dataset.oldvalue = value;
                  p.dataset.noclosure = true;

                  const group = p.querySelector('.input-group');
                  if (group.lastChild) {
                    group.removeChild(group.lastChild);
                  }
                  p.querySelector('.input-group').appendChild(input);

                  H.openModal({item: p});
                },
              });
              break;
          }
        }
      });

    this.updateMainMenu();
  }

  getUserPictureTemplate(src) {
    return src ? `<button type="button" class="btn-close img-delete"></button><img src="${src}">` : `<i class="fas fa-camera-retro fa-3x"></i>`;
  }

  // METHOD onSubmit()
  onSubmit(popup, e) {
    const field = popup.dataset.field;

    e.stopImmediatePropagation();

    popup.dataset.noclosure = true;

    switch (field) {
      case 'username':
      case 'fullname':
      case 'email':
        const input = popup.querySelector('input');
        const value = input.value.trim();

        if (value === popup.dataset.oldvalue) {
          return bootstrap.Modal.getInstance(popup).hide();
        }

        if (this.checkRequired(input) && this.validForm(input)) {
          this.updateField({[field]: value});
        }
        break;
      case 'password':
        const inputs = popup.querySelectorAll('input');

        if (this.checkRequired(inputs) && this.validForm(inputs)) {
          this.updateField({
            password: {
              current: inputs[0].value,
              new: inputs[1].value,
              confirm: inputs[2].value,
            }
          });
        }
        break;
    }
  }

  // METHOD updateMainMenu()
  updateMainMenu() {
    // Update "invisible mode" icon in main menu.
    document.querySelector('.invisible-mode').style.display =
      U.isVisible() ? 'none' : 'inline-block';
  }

  // METHOD getProp()
  getProp(prop) {
    return this.tag.querySelector(`input[name="${prop}"]`).value;
  }

  // METHOD deletePicture()
  deletePicture() {
    H.request_ws(
      'DELETE',
      'user/picture',
      null,
      // success cb
      (d) => {
        if (d.error_msg) {
          H.raiseError(null, d.error_msg);
        } else {
          this.tag.querySelector('.user-picture').innerHTML =
            this.getUserPictureTemplate();
        }
      }
    );
  }

  // METHOD delete()
  async delete() {
    const r = await H.fetch('DELETE', 'user');
    if (!r || r.error_msg) {
      if (r) {
        H.displayMsg({type: 'danger', msg: r.error_msg});
      }
    } else {
      return location.href = '/r.php';
    }
  }

  // METHOD updateField()
  updateField(args, noclosure) {
    H.request_ws(
      'POST',
      'user/update',
      args,
      // success cb
      (d) => {
        if (d.error_msg) {
          H.displayMsg({type: 'warning', msg: d.error_msg});
        } else {
          for (const k in d) {
            const field = this.tag.querySelector(`[name="${k}"]`);

            if (field) {
              if (k === 'visible' && U.get('visible') !== d[k]) {
                const wall = S.getCurrent('wall');

                U.set('visible', d[k]);

                if (d[k] !== 1) {
                  // Close all current opened walls and reload session.
                  if (wall) {
                    S.getCurrent('chat').hide();
                    wall.closeAllWalls(false);
                    (async () => {
                      await wall.restorePreviousSession();
                      S.getCurrent('settings').saveOpenedWalls(null, false);
                    })();
                  }
                } else if (wall) {
                  wall.menu({from: 'wall', type: 'have-wall'});
                }

                // Update "invisible mode" icon in main menu.
                this.updateMainMenu();
              } else {
                field.dataset.oldvalue = d[k] || '';
                field.value = d[k] || '';
              }
            }
          }

          if (!args.about) {
            H.displayMsg({
              type: 'success',
              msg: `<?=_("Your profile has been updated")?>`,
            });
          }

          // Close field update popup
          if (!noclosure) {
            bootstrap.Modal.getInstance(S.get('mstack')[0]).hide();
          }
        }
      });
  }
});

//////////////////////////////////// INIT ////////////////////////////////////

document.addEventListener('DOMContentLoaded', () => {
  if (H.isLoginPage()) return;

  // EVENT "click" on main menu account button
  document.getElementById('account').addEventListener('click', (e) => {
    H.closeMainMenu();
    H.loadPopup('account');
  });

  P.create(document.getElementById('accountPopup'), 'account');
});

})();
