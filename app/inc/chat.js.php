<?php
/**
Javascript plugin - Chat

Scope: Wall
Name: .chat
Description: Manage chat
*/

require_once(__DIR__.'/../prepend.php');

?>

/////////////////////////////////// PLUGIN ////////////////////////////////////

P.register('chat', class extends Wpt_toolbox {
  // METHOD constructor()
  constructor(settings) {
    super(settings);
    this.settings = settings;
    this.tag = settings.tag;

    const tag = this.tag;

   this.closeVKB = false;

    $(tag)
      // TODO Do not use jQuery here
      .draggable({
        distance: 10,
        cancel: '.textarea,input',
        cursor: 'move',
        drag: (e, ui) => this.fixDragPosition(ui),
        stop: () => S.set('dragging', true, 500),
      })
      // TODO Do not use jQuery here
      .resizable({
        handles: 'all',
        autoHide: H.haveMouse(),
        minHeight: 200,
        minWidth: 200,
        resize: (e, ui) =>
          tag.querySelector('.textarea')
            .style.height = `${ui.size.height - 100}px`,
      })
      // TODO Do not use jQuery here
      .append(`<button type="button" class="btn-close"></button><h2><i class="fas fa-fw fa-comments"></i> <?=_("Chat room")?> <div class="usersviewcounts"><i class="fas fa-user-friends"></i> <span class="wpt-badge inset"></span></div></h2><div><div class="textarea form-control"><span class="btn btn-sm btn-secondary btn-circle btn-clear" title="<?=_("Clear history")?>"><i class="fa fa-broom"></i></span><ul></ul></div></div><div class="console"><input type="text" name="msg" value="" class="form-control form-control-sm"></div>`);

    const input = tag.querySelector('input');

    // EVENT "keypress" in main input
    input.addEventListener('keypress', (e) => {
      if (e.which !== 13) return;

      const msg = H.noHTML(input.value);

      if (!msg) return;

      H.preventDefault(e);

      this.sendMsg(msg);
      this.setFocus();
      input.value = '';
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
    tag.querySelector('.btn-close').addEventListener('click',
      (e) => this.hide());

    // EVENT "click" on "clear" button
    tag.querySelector('.btn-clear').addEventListener('click', (e) => {
      if (H.disabledEvent()) return false;

      e.target.closest('.form-control').querySelectorAll('li').forEach(
        (el) => el.remove());

      input.focus();
    });
  }

  // METHOD hide()
  hide() {
    if (H.isVisible(this.tag)) {
      document.querySelector(`#main-menu li[data-action="chat"]`).click();
    }
  }

  // METHOD join()
  join() {
    H.request_ws('PUT', `wall/${this.settings.wallId}/chat`);
  }

  // METHOD leave()
  leave() {
    H.request_ws('DELETE', `wall/${this.settings.wallId}/chat`);
  }

  // METHOD setFocus()
  setFocus() {
    H.setAutofocus(this.tag);
  }

  // METHOD toggle()
  toggle() {
    const tag = this.tag;
    const wallId = this.settings.wallId;

    if (H.isVisible(tag)) {
      H.request_ws('DELETE', `wall/${wallId}/chat`);
      H.hide(tag);
    } else {
      const el =
        document.querySelector(`#wall-${wallId} .wall-menu .chat-alert`);

      el && el.remove();

      tag.style.bottom = '15px';
      tag.style.left = '5px';
      H.show(tag, 'table');

      this.setFocus();

      H.request_ws('PUT', `wall/${wallId}/chat`);
    }
  }

  // METHOD removeAlert()
  removeAlert() {
    const el = document.querySelector(
      `#wall-${this.settings.wallId} .wall-menu .chat-alert`);

    el && el.remove();
  }

  // METHOD refreshUserscount()
  refreshUserscount(args = {userscount: 0, userslist: []}) {
    const tag = this.tag;
    const userId = U.getId();

    tag.querySelector('.wpt-badge').innerHTML = args.userscount;

    let title = '';
     args.userslist.forEach(
       (el) => (el.id !== userId) ? title += `, ${el.name}` : '');
     tag.querySelector('.usersviewcounts').title = title.substring(1);
  }

  // METHOD addMsg()
  addMsg(args) {
    const tag = this.tag;
    const isHidden = !this.isVisible();
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

    if (node) {
      tag.querySelector('.textarea').querySelector('ul').appendChild(node);
    }

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
  }

  // METHOD setCursorToEnd()
  setCursorToEnd() {
    const el = this.tag.querySelector('.textarea');

    el.scrollTop = el.scrollHeight;
  }

  // METHOD sendMsg()
  sendMsg(msg) {
    H.request_ws("POST", `wall/${this.settings.wallId}/chat`, {msg});
  }

  // METHOD reset()
  reset() {
    const tag = this.tag;

    tag.querySelector('.textarea').innerText = '';
    tag.querySelector('input').value = '';
  }
});
