<?php
/**
Javascript plugin - User login & account creation

Scope: Login page
Name: login
Description: Login & User account creation
*/

require_once(__DIR__.'/../prepend.php');

?>

(() => {
  'use strict';

/////////////////////////////////// PLUGIN ////////////////////////////////////

P.register('login', class extends Wpt_accountForms {
  // METHOD constructor()
  constructor(settings) {
    super(settings);
    this.settings = settings;
    this.tag = settings.tag;

    const tag = this.tag;

    if (!ST.noDisplay('welcome-msg')) {
      const welcome = document.getElementById('desc-container');

      welcome.innerHTML = `<div class="welcome alert alert-primary alert-dismissible justify fade show" role="alert"><button type="button" class="btn-close" data-bs-dismiss="alert"></button><h4 class="alert-heading"><?=_("Welcome to wopits!")?></h4><p><?=sprintf(_("A free, open-source sticky notes manager that respects your privacy, that's good, right? %s will let you do a lot of things, without ever having to worrying about where your data is going to or how it is monetized."), '<a href="https://wopits.esaracco.fr" target="_blank">wopits</a>')?></p><hr><p class="mb-0"><?=sprintf(_("Besides, you don't have to use it online: you can %sget the code%s and install it yourself!"), '<a href="https://github.com/esaracco/wopits" target="_blank">', '</a>')?></p><div class="mt-3"><button type="button" class="btn btn-sm btn-primary nowelcome"><?=_("I get it !")?></button></div></div>`;

      // EVENT "closed.bs.alert" on alert
      welcome.querySelector('.alert').addEventListener('closed.bs.alert',
          (e) => H.setAutofocus(tag));

      // EVENT "click" on "I get it!" button"
      welcome.querySelector('button.nowelcome').addEventListener('click',
          (e) => {
        ST.noDisplay('welcome-msg', true);
        e.target.closest('.alert').remove();
        H.setAutofocus(tag);
      });

      welcome.focus();
    } else {
      H.setAutofocus(tag);
    }

    // EVENT "click" on buttons and a links
    tag.addEventListener('click', (e) => {
      const el = e.target;
      let popup;

      if (el.tagName === 'BUTTON' || el.tagName === 'A') {
        switch (el.dataset.type) {
          case 'login':
            if (this.checkRequired(tag.querySelectorAll('input'), false)) {
              const du = tag.querySelector(`input[name="_directURL"]`).value;

              this.login(H.trimObject({
                directURL:
                  (du && du.match(<?=WPT_DIRECTURL_REGEXP?>)) ?
                    `/?${du}` : null,
                remember: document.getElementById('remember').checked,
                username: tag.querySelector(`input[type="text"]`).value,
                password: tag.querySelector(`input[type="password"]`).value,
              }, ['password']));
            }
            break;
          case 'create':
            popup = document.getElementById('createAccountPopup');

            H.cleanPopupDataAttr(popup);

            this.resetCreateUserForm();
            popup.dataset.noclosure = true;
            H.openModal({item: popup});
            break;
          case 'forgot':
            popup = document.getElementById('resetPasswordPopup');

            H.cleanPopupDataAttr(popup);

            popup.dataset.noclosure = true;
            H.openModal({item: popup});
            break;
        }          
      }
    });

    // EVENT "keypress" on inputs
    tag.addEventListener('keypress', (e) => {
      if (e.which === 13 && e.target.tagName === 'INPUT') {
        H.preventDefault(e);
        tag.querySelector('.btn-success').click();
      }
    });

    // EVENT "click" on reset password popup "send" button
    document.querySelector('#resetPasswordPopup .btn-primary')
        .addEventListener('click', (e) => {
      const popup = e.target.closest('.modal');
      const input = popup.querySelector('input');

      e.stopImmediatePropagation();

      popup.dataset.noclosure = true;

      if (this.checkRequired(input) && this.validForm(input)) {
        this.resetPassword({email: input.value.trim()});
        input.value = '';
      }
    });

    // EVENT "click" on create account popup "create" button
    document.querySelector('#createAccountPopup .btn-primary')
        .addEventListener('click', (e) => {
      const popup = e.target.closest('.modal');
      const form = popup.querySelector('form');
      const inputs = popup.querySelectorAll('input');

      e.stopImmediatePropagation();

      popup.dataset.noclosure = true;

      if (popup.querySelector('.confirm')) {
        if (this.checkRequired(Array.from(inputs).slice(0, 2)) &&
            this.validForm(inputs)) {
          this.createUser(H.trimObject({
            _check: document.querySelector(
                        `.main-login form input[name="_check"]`).value,
            username: form.querySelector(`input[name="username"]`).value,
            fullname: form.querySelector(`input[name="fullname"]`).value,
            password: form.querySelector(`input[name="password"]`).value,
            email: form.querySelector(`input[name="email"]`).value,
          }, ['password']));
        }
      } else if (this.checkRequired(inputs) && this.validForm(inputs)) {
        const main = popup.querySelector('.main');
        const footer = popup.querySelector('.modal-footer');

        main.classList.add('readonly');
        main.querySelectorAll('input').forEach((el) => el.readOnly = true);

        // Display confirmation section
        form.insertBefore($(`<div class="confirm"><div><?=_("Please, confirm your password:")?></div><div class="input-group mb-1"><span class="input-group-text"><i class="fas fa-shield-alt fa-fw fa-xs"></i> <i class="fas fa-key fa-fw fa-xs"></i></span><input class="form-control" type="password" autocomplete="new-password" name="password2" placeholder="<?=_("password confirmation")?>" required value=""></div><div><?=_("Please, confirm your email:")?></div><div class="input-group mb-4"><span class="input-group-text"><i class="fas fa-shield-alt fa-fw fa-xs"></i> <i class="fas fa-envelope fa-fw fa-xs"></i></span><input class="form-control" type="email" name="email2" required value="" placeholder="<?=_("email confirmation")?>"></div>`)[0], form.firstChild);

        // Display "previous" button
        footer.insertBefore ($(`<button type="button" class="btn btn-secondary btn-info"><i class="fas fa-caret-left"></i> <?=_("Previous")?></button>`)[0], footer.firstChild);

        // EVENT "click" on "previous" button
        popup.querySelector('.btn-info').addEventListener('click', (e) => {
          e.stopImmediatePropagation();
          this.resetCreateUserForm();
        });

        popup.querySelector('.btn-primary').innerHTML = 
          `<i class="fas fa-bolt fa-fw fa-xs"></i> <?=_("Create")?></i>`;

        setTimeout(() => popup.querySelector('input').focus(), 150);
      }
    });

    if (tag.querySelector(`input[name="_directURL"]`)
          .value.includes('unsubscribe')) {
      H.infoPopup(`<?=_("Please log in to update your preferences")?>`);
    }
  }

  // METHOD resetCreateUserForm()
  resetCreateUserForm() {
    const popup = document.getElementById('createAccountPopup');
    const main = popup.querySelector('.main');

    main.classList.remove('readonly');
    main.querySelectorAll('input').forEach((el) => el.readOnly = false);

    popup.querySelectorAll('.confirm,.btn-info').forEach((el) => el.remove());
    popup.querySelector('.btn-primary').innerHTML =
        `<?=_("Next")?> <i class="fas fa-caret-right"></i>`;
  }

  // METHOD login()
  async login(args) {
    const r = await H.fetch('POST', 'user/login', args);
    if (r.error) {
      if (r.error_msg) {
        H.displayMsg ({type: 'warning', msg: r.error_msg});
      }
    } else {
      return location.href = args.directURL || '/';
    }
  }

  // METHOD createUser()
  async createUser(args) {
    const r = await H.fetch('PUT', 'user', args);
    if (r.error) {
      this.resetCreateUserForm ();
      if (r.error_msg) {
        H.displayMsg({type: 'warning', msg: r.error_msg});
      }
    } else {
      return location.href = '/';
    }
  }

  // METHOD resetPassword()
  async resetPassword(args) {
    const r = await H.fetch('POST', 'user/resetPassword', args);
    if (r.error) {
      if (r.error_msg) {
        H.displayMsg ({type: 'warning', msg: r.error_msg});
      }
    } else {
      bootstrap.Modal.getInstance('#resetPasswordPopup').hide();
      H.displayMsg({
        type: 'info',
        msg: `<?=_("Your new password has been sent. Please, check your spam box if you don't receive it.")?>`,
      });
    }
  }
});

//////////////////////////////////// INIT ////////////////////////////////////

document.addEventListener('DOMContentLoaded', () => {
  if (!H.isLoginPage()) return;

  P.create(document.getElementById('login'), 'login');
});

})();
