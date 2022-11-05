<?php
/**
Javascript plugin - Chat

Scope: Wall
Element: .chat
Description: Manage chat
*/

require_once(__DIR__.'/../prepend.php');

$Plugin = new Wopits\jQueryPlugin('chat');
echo $Plugin->getHeader();

?>

/////////////////////////////////// PUBLIC ///////////////////////////////////

<?=$Plugin->getPublicSection()?>

// Inherit from Wpt_toolbox
Plugin.prototype = Object.create(Wpt_toolbox.prototype);
Object.assign(Plugin.prototype, {
  closeVKB: false,
  // METHOD init()
  init() {
    const $chat = this.element;
    const chat = $chat[0];

    $chat
      .draggable({
        distance: 10,
        cursor: 'move',
        drag: (e, ui) => this.fixDragPosition(ui),
        stop: ()=> S.set('dragging', true, 500)
      })
      .resizable({
        handles: 'all',
        autoHide: !$.support.touch,
        minHeight: 200,
        minWidth: 200,
        resize: (e, ui) =>
          chat.querySelector('.textarea')
            .style.height = `${ui.size.height - 100}px`,
      })
      .append(`<button type="button" class="btn-close"></button><h2><i class="fas fa-fw fa-comments"></i> <?=_("Chat room")?> <div class="usersviewcounts"><i class="fas fa-user-friends"></i> <span class="wpt-badge inset"></span></div></h2><div><div class="textarea form-control"><span class="btn btn-sm btn-secondary btn-circle btn-clear" title="<?=_("Clear history")?>"><i class="fa fa-broom"></i></span><ul></ul></div></div><div class="console"><input type="text" name="msg" value="" class="form-control form-control-sm"><button type="button" class="btn btn-xs btn-primary">Envoyer</button></div>`);

    const input = chat.querySelector('input');

    // EVENT "keypress" in main input
    input.addEventListener('keypress', (e) => {
      if (e.which !== 13) return;

      H.preventDefault (e);
      chat.querySelector('.btn-primary').click();
    });

    // EVENT "focus" in main input
    input.addEventListener('focus', (e) => {
      if (H.haveMouse() || S.get('vkbData')) return;

      this.closeVKB = H.fixVKBScrollStart();
    });

    // EVENT "blur" in main input
    input.addEventListener('blur', (e) => {
      if (!this.closeVKB || !S.get('vkbData')) return;

      H.fixVKBScrollStop();
      this.closeVKB = false;
    });

    // EVENT "click" on main input
    // Needed for touch devices
    input.addEventListener('click', (e) => e.target.focus());

    // EVENT "click" on close button
    chat.querySelector('.btn-close').addEventListener('click',
      (e) => this.hide());

    // EVENT "click" on "clear" button
    chat.querySelector('.btn-clear').addEventListener('click', (e) => {
      if (H.disabledEvent()) return false;

      e.target.closest('.form-control').querySelectorAll('li').forEach(
        (el) => el.remove());

        input.focus();
    });

    // EVENT "click" on "send" button
    chat.querySelector('button.btn-primary').addEventListener('click',
      (e) => {
      const msg = H.noHTML(input.value);

      if (!msg) return;

      this.sendMsg (msg);
      this.setFocus();
      input.value = '';
    });
  },

  // METHOD hide()
  hide() {
    if (this.element.is(":visible")) {
      document.querySelector(`#main-menu li[data-action="chat"]`).click();
    }
  },

  // METHOD join()
  join() {
    H.request_ws('PUT', `wall/${this.settings.wallId}/chat`);
  },

  // METHOD leave()
  leave() {
    H.request_ws('DELETE', `wall/${this.settings.wallId}/chat`);
  },

  // METHOD setFocus()
  setFocus() {
    H.setAutofocus(this.element[0]);
  },

  // METHOD toggle()
  toggle() {
    const $chat = this.element;
    const chat = $chat[0];
    const wallId = this.settings.wallId;

    if ($chat.is(':visible')) {
      H.request_ws('DELETE', `wall/${wallId}/chat`);
      H.hide(chat);
    } else {
      const el =
        document.querySelector(`#wall-${wallId} .wall-menu .chat-alert`);

      el && el.remove ();

      chat.style.bottom = '15px';
      chat.style.left = '5px';
      H.show(chat, 'table');

      this.setFocus();

      H.request_ws('PUT', `wall/${wallId}/chat`);
    }
  },

  // METHOD removeAlert()
  removeAlert() {
    const el = document.querySelector(
      `#wall-${this.settings.wallId} .wall-menu .chat-alert`);

    el && el.remove ();
  },

  // METHOD refreshUserscount()
  refreshUserscount(args = {userscount: 0, userslist: []}) {
    const chat = this.element[0];
    const userId = wpt_userData.id;

    chat.querySelector('.wpt-badge').innerHTML = args.userscount;

    let title = '';
     args.userslist.forEach(
       (el) => (el.id !== userId) ? title += `, ${el.name}` : '');
     chat.querySelector('.usersviewcounts').title = title.substring(1);
  },

  // METHOD addMsg()
  addMsg(args) {
    const $chat = this.element;
    const isHidden = $chat.is(':hidden');
    let node;

    if (args.internal !== undefined) {
      this.refreshUserscount(args);

      switch (args.msg) {
        case '_JOIN_':
          node = H.createElement('li', {className: 'internal join'}, null, `<i class="fas fa-arrow-right"></i> ${args.username}`);
          break;
         case '_LEAVE_':
          node = H.createElement('li', {className: 'internal leave'}, null, `<i class="fas fa-arrow-left"></i> ${args.username}`);
          break;
      }
    } else {
      node = H.createElement('li', {className: `${ args.msgId ? 'current' : ''}`}, null, `<span>${args.msgId ? '<i class="fas fa-user fa-sm"></i>' : args.username}</span> ${args.msg}`);
    }

    $chat[0].querySelector('.textarea').querySelector('ul').appendChild(node);

    if (isHidden && args.method !== 'DELETE') {
      const el = document.querySelector(`#wall-${this.settings.wallId} .wall-menu .chat-alert .wpt-badge`);

      if (el) {
        el.textContent = Number(el.textContent) + 1;
      } else {
        const wmenu =
          document.querySelector(`#wall-${this.settings.wallId} .wall-menu`);

        wmenu.appendChild(H.createElement('li', {className: 'chat-alert'}, null, `<i class="fas fa-comments fa-fw fa-lg set"></i><span class="wpt-badge inset">1</span>`));

        wmenu.querySelector('.chat-alert').addEventListener('click', (e) =>
          document.querySelector(`#main-menu li[data-action="chat"]`)
            .click());
      }
    }

    this.setCursorToEnd();
  },

  // METHOD setCursorToEnd()
  setCursorToEnd() {
    const el = this.element[0].querySelector('.textarea');

    el.scrollTop = el.scrollHeight;
  },

  // METHOD sendMsg()
  sendMsg(msg) {
    H.request_ws("POST", `wall/${this.settings.wallId}/chat`, {msg});
  },

  // METHOD reset()
  reset() {
    const $chat = this.element[0];

    chat.querySelector('.textarea').innerText = '';
    chat.querySelector('input').value = '';
  }

});

<?=$Plugin->getFooter()?>
