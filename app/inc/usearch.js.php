<?php
/**
Javascript plugin - Users search

Scope: Wall & note
Name: usearch (used by #usearchPopup, #pworkPopup)
Description: Manage users search for sharing wall (swall) and
             postit workers (pwork) plugins
*/

require_once(__DIR__.'/../prepend.php');

?>

/////////////////////////////////// PLUGIN ////////////////////////////////////

P.register('usearch', class extends Wpt_pluginBase {
  // METHOD constructor()
  constructor(settings) {
    super(settings);
    const tag = this.tag;
    const search = tag.querySelector('.search');

    this.noResultStr = undefined;
    this.oldIds = undefined;
    this.readonly = false;

    search.append(
      H.createElement('div',
        {className: 'input-group'}, null,
        `<span class="input-group-text"><i class="fas fa-search fa-xs fa-fw"></i></span><input type="text" class="form-control" value="" placeholder="<?=_("username")?>" autocorrect="off" autocapitalize="off"><button class="btn clear-input" type="button"><i class="fa fa-times"></i></button>`),
      H.createElement('ul',
        {className: 'result autocomplete list-group'}, null,
        `<button type="button" class="close closemenu">×</button><div class="content"></div>`),
    );

    const input = search.querySelector('input');

    // EVENT "hidden.bs.modal"
    tag.addEventListener('hidden.bs.modal', (e) => {
      this.settings.onClose && this.settings.onClose();
      e.target.querySelector('.list-group.attr').innerHTML = '';
      this.oldIds = undefined;
    });
 
    // EVENT "mouseover"
    document.addEventListener('mouseover', (e) => {
      const el = e.target;

      // EVENT "mouseover" on users search result list item
      if (el.matches(`#${tag.id} .result .list-group-item *,`+
                     `#${tag.id} .result .list-group-item`)) {
        const li = (el.tagName === 'LI') ? el : el.closest('li');

        e.stopImmediatePropagation();

        search.querySelector('li.selected').classList.remove('selected');
        li.classList.add('selected');
      }
    });

    // EVENT "click"
    tag.addEventListener('click', (e) => {
      const el = e.target;

      // EVENT "click" on users search list item
      if (el.matches(`.list-group-item, .list-group-item *`)) {
        const li = (el.tagName === 'LI') ? el : el.closest('li');

        e.stopImmediatePropagation();

        if (this.readonly) {
          H.openUserview(li.dataset);
        } else {
          const isDed =
            (Number(tag.dataset.grouptype) === <?=WPT_GTYPES_DED?>);
          const actionAdd = (li.dataset.action === 'add');
          const args = {
            ...this.getIds(),
            groupType: Number(tag.dataset.grouptype),
            groupId: Number(tag.dataset.groupid),
            userId: Number(li.dataset.id),
          };

          input.focus();

          if (this.isWorkers()) {
            if (actionAdd) {
              this.addUser(args);
            } else {
              this.removeUser(args);
            }
          } else {
            if (actionAdd) {
              this.addUser(args);
            } else if (isDed && tag.dataset.noattr) {
              this.removeUser(args);
            } else {
              H.openConfirmPopover({
                item: li.querySelector('span'),
                title: `<i class="fas fa-minus-circle fa-fw"></i> <?=_("Remove")?>`,
                content: isDed ? `<?=_("This user will lose their access to the wall.<br>Remove anyway?")?>` : `<?=_("This user will lose their access for all walls shared with this group.<br>Remove anyway?")?>`,
                onConfirm: () => {
                  this.removeUser(args);
                  input.focus();
                }
              });
            }
          }
        }
      }
    });

    // EVENT "click" on textarea clear button
    search.querySelector('.clear-input').addEventListener('click', (e) => {
      input.value = '';
      this.reset();
      
      input.focus();
    });

    // EVENT "keyup" on input
    input.addEventListener('keyup', (e) => {
      const val = e.target.value.trim();
      const k = e.which;

      if (!val || (!this.isWorkers() && val.length < 3)) {
        this.reset();
        return;
      }

      // Do not search if arrow keys
      if (k < 37 || k > 40) {
        this.search({
          str: val,
          groupType: Number(tag.dataset.grouptype),
        });
      }
    });

    // EVENT "keypress" on input
    input.addEventListener('keypress', (e) => {
      const el = e.target;

      // If enter on selected users search item, select it
      if (e.which === 13 && el.classList.contains('autocomplete')) {
        e.stopImmediatePropagation();
        H.preventDefault(e);

        search.querySelector('.list-group-item.selected').click();
      }
    });

    // EVENTS "keyup" & "keydown" on input
    const _eventKK = (e) => {
      const el = e.target;
      const k = e.which;
      const list = search.querySelectorAll('li');

      if (!list.length) return;

      // If ESC, close the users search
      if (k === 27) {
        if (!document.querySelector('.popover.show')) {
          this.reset();
        }
      // Arrow up or arrow down.
      } else if (k === 38 || k === 40) {
          e.stopImmediatePropagation();
          H.preventDefault(e);

          if (e.type === 'keyup') {
            // LOCAL FUNCTION __select()
            const __select = (i, type) => {
              // Key up
              if (i && type === 'up') {
                const el = list[i - 1];

                list[i].classList.remove('selected');
                el.classList.add('selected');
                el.scrollIntoView(false);
              // Key down
              } else if (i < list.length - 1 && type === 'down') {
                const el = list[i + 1];

                list[i].classList.remove('selected');
                el.classList.add('selected');
                el.scrollIntoView(false);
              }
            };

            for (let i = 0, iLen = list.length; i < iLen; i++) {
              if (list[i].classList.contains('selected')) {
                __select(i, k === 38 ? 'up': 'down');
                return;
              }
            }
          }
      }
    };
    input.addEventListener('keyup', _eventKK);
    input.addEventListener('keydown', _eventKK);

    // EVENT "click" on users search list close button
    search.querySelector('button.closemenu').addEventListener('click',
      (e) => this.reset({field: true}));
  }

  // METHOD getNewUsers()
  getNewUsers() {
    const userId = U.getId();
    let ids = [];

    this.tag.querySelectorAll(
        '.list-group.attr .list-group-item').forEach((el) => {
      const id = Number(el.dataset.id);

      if (id !== userId && !this.oldIds.includes(id)) {
        ids.push(id);
      }
    });

    return ids;
  }

  // METHOD setSettings()
  setSettings(args) {
    this.settings = Object.assign(this.settings, args);
  }

  // METHOD getIds()
  getIds() {
    const postit = S.getCurrent('postit');
    const postitSettings = (postit && postit.settings) || {};

    return {
      wallId: S.getCurrent('wall').getId(),
      cellId: postitSettings.cellId,
      postitId: postitSettings.id,
    };
  }

  // METHOD reset()
  reset(args = {}) {
    const tag = this.tag;
    const search = tag.querySelector('.search');
    const input = tag.querySelector('input');

    if (args.full) {
      tag.removeAttribute('data-delegateadminid');
      args.field = true;
      args.users = true;
    }

    if (args.field) {
      input.value = '';
    }

    input.classList.remove('autocomplete');
    tag.querySelector('.result .content').innerHTML = '';
    H.hide(tag.querySelector('.result button.closemenu'));
    search.classList.remove('shadow');

    if (args.readonly !== undefined && args.readonly) {
      this.readonly = true;
      H.hide(search);
      H.hide(tag.querySelector('.desc'));
    } else {
      this.readonly = false;
      H.show(search);
      H.show(tag.querySelector('.desc'));
    }
  }

  // METHOD isWorkers()
  isWorkers() {
    return (this.settings.caller === 'pwork');
  }

  // METHOD removeUser()
  removeUser(args) {
    const isWorkers = this.isWorkers();
    let service = isWorkers ?
      `cell/${args.cellId}/postit/${args.postitId}/worker/${args.userId}` :
      `group/${args.groupId}/removeUser/${args.userId}`;

    if (isWorkers || args.groupType === <?=WPT_GTYPES_DED?>) {
      service = `wall/${args.wallId}/${service}`;
    }

    H.request_ws(
      'DELETE',
      service,
      null,
      // success cb
      (d) => {
        this.displayUsers(args);
        this.search({
          ...args,
          str: this.tag.querySelector('input').value,
        }, true);
        this.settings.onRemove && this.settings.onRemove();
      });
  }

  // METHOD addUser()
  addUser(args) {
    const isWorkers = this.isWorkers();
    let service = isWorkers ?
      `cell/${args.cellId}/postit/${args.postitId}/worker/${args.userId}` :
      `group/${args.groupId}/addUser/${args.userId}`;

    if (isWorkers || args.groupType === <?=WPT_GTYPES_DED?>) {
      service = `wall/${args.wallId}/${service}`;
    }

    H.request_ws(
      'PUT',
      service,
      null,
      // success cb
      (d) => {
        if (d.error_msg) {
          H.raiseError(null, d.error_msg);
          return;
        } else if (d.notfound) {
          H.displayMsg({
            type: 'warning',
            msg: `<?=_("The user is no longer available")?>`,
          });
        }

        this.displayUsers(args);
        this.search({
          ...args,
          str: this.tag.querySelector('input').value,
        }, true);
        this.settings.onAdd && this.settings.onAdd();
      });
  }

  // METHOD displayUsers()
  displayUsers(args) {
    const tag = this.tag;
    const delegateAdminId = Number(tag.dataset.delegateadminid || 0);
    const isWorkers = this.isWorkers();
    let service = isWorkers ?
                    `cell/${args.cellId}/postit/${args.postitId}/worker` :
                    `group/${args.groupId}/getUsers`;

    if (isWorkers || args.groupType === <?=WPT_GTYPES_DED?>) {
      service = `wall/${args.wallId}/${service}`;
    }

    H.fetch(
      'GET',
      service,
      null,
      // success cb
      (d) => {
        const div = tag.querySelector('.modal-body .list-group.attr');
        const divParent = div.parentNode;
        let html = '';

        d = d.users;

        // Keep users state before action
        if (this.oldIds === undefined) {
          this.oldIds = [];
          d.forEach((u) => this.oldIds.push(u.id));
          this.oldIds.sort();
        }

        if (d.length) {
          H.show(tag.querySelector('.users-title'));
          H.hide(tag.querySelector('.nousers-title'));

          d.forEach((u) => this.readonly ?
            html += `<li class="list-group-item" data-id="${u.id}" data-title="${H.htmlEscape(u.fullname)}" data-picture="${u.picture || ''}" data-about="${H.htmlEscape(u.about || '')}"><div class="label">${u.fullname}</div><div class="item-infos"><span>${u.username}</span></div></li>` :
            html += `<li class="list-group-item${(delegateAdminId === u.id) ? ' readonly' : ''}" data-action="remove" data-id="${u.id}"><div class="label">${u.fullname}</div><button type="button" class="close"><i class="fas fa-minus-circle fa-fw fa-xs"></i></button><div class="item-infos"><span>${u.username}</span></div></li>`);

          if (d.length > 1) {
            divParent.classList.add('scroll');
          } else {
            divParent.classList.remove('scroll');
          }

          div.innerHTML = html;
        }
        else
        {
          H.hide(tag.querySelector('.users-title'));
          H.show(tag.querySelector('.nousers-title'));
          divParent.classList.remove('scroll');
          div.innerHTML = '';
        }

        args.then && args.then(d.length);
      });
  }

  // METHOD search()
  search(args, force) {
    const tag = this.tag;
    const wallId = S.getCurrent('wall').getId();
    let service;

    if (this.isWorkers()) {
      const {cellId, postitId, wallId} = this.getIds();

      service = `wall/${wallId}/cell/${cellId}/`+
                   `postit/${postitId}/searchWorkers/${args.str}`;
    } else {
      service = (args.groupType === <?=WPT_GTYPES_DED?>) ?
            `group/${tag.dataset.groupid}/wall/${wallId}`+
              `/searchUsers/${args.str}` :
            `group/${tag.dataset.groupid}/searchUsers/${args.str}`;
    }

    args.str = args.str.replace(/&/, '');

    if (!force &&
        (
          !args.str ||
          (
            this.noResultStr &&
             args.str !== this.noResultStr &&
             args.str.includes(this.noResultStr)
          )
        )
      ) {
      return;
    }

    H.fetch(
      'GET',
      service,
      null,
      // success cb
      (d) => {
        const users = d.users || [];
        let html = '';

        if (!users.length) {
          this.noResultStr = args.str;
        }

        users.forEach((item, i) => html += `<li class="${i === 0 ? 'selected' : ''} list-group-item" data-action="add" data-id="${item.id}"><div class="label">${item.fullname}</div><button type="button" class="close"><i class="fas fa-plus-circle fa-fw fa-xs"></i></button><div class="item-infos"><span>${item.username}</span></div></li></li>`);

        if (html) {
          tag.querySelector('.result button.closemenu').style.display= 'block';
          tag.querySelector('.search').classList.add('shadow');
          tag.querySelector('input').classList.add('autocomplete');
        } else {
          this.reset();
        }
        
        tag.querySelector('.result .content').innerHTML = html;
      });
  }
});
