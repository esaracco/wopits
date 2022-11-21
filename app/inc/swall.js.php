<?php
/**
Javascript plugin - Sharing walls

Scope: Wall
Name: swall
Description: Manage wall sharing
*/

require_once(__DIR__.'/../prepend.php');

?>

/////////////////////////////////// PLUGIN ////////////////////////////////////

P.register('swall', class extends Wpt_forms {
  // METHOD constructor()
  constructor(settings) {
    super(settings);
    this.Settings = settings;
    this.tag = settings.tag;

    this.groupPopup = null;
    this.groupAccessPopup = null;

    const tag = this.tag;

    this.groupPopup = document.getElementById('groupPopup');

    tag.addEventListener('hidden.bs.modal', (e) =>
      S.getCurrent('wall').menu({from: 'wall', type: 'have-wall'}));

    // LOCAl FUNCTION __close()
    const __close = () =>
      tag.querySelector('li.list-group-item.active').classList.remove('active');


    // EVENT "click"
    tag.addEventListener('click', (e) => {
      const el = e.target;

      // EVENT "click" on group list item buttons
      if (el.matches('.list-group-item [data-action],'+
                     '.list-group-item [data-action] *')) {
        const btn = el.getAttribute('data-action') ?
          el : el.closest('[data-action]');
        const action = btn.dataset.action;
        const row = btn.closest('li');
        const groupType = Number(row.dataset.type);
        const id = Number(row.dataset.id);

        e.stopImmediatePropagation();

        row.classList.add('active');

        switch (action) {
          // Users search
          case 'users-search':
            H.loadPopup('usearch', {
              open: false,
              cb: (p) => {
                const groupId = Number(row.dataset.id);
                const delegateAdminId =
                  Number(row.dataset.delegateadminid || 0);
                const usearch = P.getOrCreate(p, 'usearch');

                usearch.setSettings({
                  caller: 'swall',
                  onAdd: () => this.displayGroups(),
                  onRemove: () => this.displayGroups(),
                  onClose: __close,
                });

                usearch.reset({full: true});

                p.dataset.delegateadminid = delegateAdminId;
                p.dataset.groupid = groupId;
                p.dataset.grouptype = groupType;

                if (row.parentNode.classList.contains('noattr')) {
                  p.dataset.noattr = 1;
                }

                p.querySelector('.desc').innerHTML = `<?=_("Add or remove users in the group « %s ».")?>`.replace('%s', `<b>${row.dataset.name}</b>`);

                usearch.displayUsers({
                  groupId,
                  groupType,
                  wallId: S.getCurrent('wall').getId(),
                 });

                H.openModal({item: p});
              }
            });
            break;
          // Delete group
          case 'delete-group':
            H.openConfirmPopover({
               item: btn.closest('li').querySelector('.name'),
               title: `<i class="fas fa-trash fa-fw"></i> <?=_("Delete")?>`,
               content:
                 (row.parentNode.classList.contains(
                     'gtype-<?=WPT_GTYPES_DED?>')) ?
                   `<?=_("Delete this group?")?>`:
                   `<?=_("This group will no longer be available for the current wall or for your other walls.<br>Delete it anyway?")?>`,
               onClose: __close,
               onConfirm: () => this.deleteGroup(),
             });
            break;
          // unlink group
          case 'unlink-group':
            // Ask confirmation only if they are some users in the group to
            // unlink.
            if (row.querySelector(
                  '.userscount .wpt-badge').innerText === '0') {
              this.unlinkGroup({id}, groupType);
            } else {
              H.openConfirmPopover({
                 item: btn.closest('li').querySelector('.name'),
                 title: `<i class="fas fa-minus-circle fa-fw"></i> <?=_("Unshare")?>`,
                 content: `<?=_("Users will lose their access to the wall.<br>Unshare anyway?")?>`,
                 onClose: __close,
                 onConfirm: () => this.unlinkGroup({id}, groupType)
               });
            }
            break;
          // Link group
          case 'link-group':
            H.loadPopup('groupAccess', {
              init: (p) => {
                p.querySelector(`.send-msg input[type="checkbox"]`)
                    .addEventListener('change', (e) => {
                  const el = e.target;
                  const cls = el.closest('.send-msg').classList;

                  if (el.checked) {
                    cls.remove('disabled');
                  } else {
                    cls.add('disabled');
                  }
                });

                this.groupAccessPopup = p;
              }
            });
            break;
        }
      }
      // EVENT "click" on group list item
      else if (el.matches('.list-group-item, .list-group-item span')) {
        const li = (el.tagName === 'SPAN') ? el.closest('li') : el;

        e.stopImmediatePropagation();

        if (li.dataset.creator) {
          li.classList.add('active');

          this.openUpdateGroup({
            groupId: Number(li.dataset.id),
            name: li.querySelector('.name').innerText,
            description: li.querySelector('.desc').innerText,
          });
        } else {
          tag.querySelector(`button[data-action="users-search"] i`).click();
        }
      }
    });

    // EVENT "click" on buttons
    const _eventCB = (e) => {
      const action = e.currentTarget.dataset.action;
      if (action === `add-gtype-<?=WPT_GTYPES_GEN?>` ||
          action === `add-gtype-<?=WPT_GTYPES_DED?>`) {
        this.openAddGroup(action.match(/add\-gtype\-([^\-]+)/)[1]);
      }
    };
    tag.querySelectorAll('button').forEach((el) =>
      el.addEventListener('click', _eventCB));
  }

  // METHOD onSubmitGroup()
  onSubmitGroup(p, btn, e) {
    const type = btn.dataset.type;
    const groupId = Number(btn.dataset.groupid);
    const inputs = p.querySelectorAll('input');

    e.stopImmediatePropagation();

    if (!groupId) {
      p.dataset.noclosure = true;

      if (this.checkRequired(inputs))
        this.createGroup(type, {
          name: H.noHTML(inputs[0].value),
          description: H.noHTML(inputs[1].value),
        });
    } else if (this.checkRequired(inputs)) {
      this.updateGroup({
        groupId,
        name: H.noHTML(inputs[0].value),
        description: H.noHTML(inputs[1].value),
      });
    }
  }

  // METHOD openAddGroup()
  openAddGroup(type) {
    let title;
    let desc;

    if (Number(type) === <?=WPT_GTYPES_GEN?>) {
      title = `<?=_("Create a <b>generic</b> group")?>`;
      desc =  `<?=_("The group will also be available for all your other walls.")?>`;
    } else {
      title = `<?=_("Create a <b>dedicated</b> group")?>`;
      desc = `<?=_("The group will be available only for the current wall.")?>`;
    }

    H.loadPopup('group', {
      open: false,
      init: (p) => {
        p.querySelector('.btn-primary').addEventListener('click', (e) =>
          this.onSubmitGroup(p, e.currentTarget, e));

        this.groupPopup = p;
      },
      cb: (p) => {
        const btnPrimary = p.querySelector('.btn-primary');

        p.dataset.action = 'create';
        p.dataset.noclosure = true;

        p.querySelector('.modal-title span').innerHTML = title;
        p.querySelector('.desc').innerHTML = desc;

        btnPrimary.dataset.type = type;
        btnPrimary.innerHTML = `<i class="fas fa-bolt"></i> <?=_("Create")?>`;
        p.querySelector('button.btn-secondary').innerHTML = `<i class="fas fa-undo-alt"></i> <?=_("Cancel")?>`;

        H.openModal({item: p});
      }
    });
  }

  // METHOD openUpdateGroup()
  openUpdateGroup(args) {
    H.loadPopup('group', {
      open: false,
      init: (p) => {
        p.querySelector('.btn-primary').addEventListener('click', (e) =>
          this.onSubmitGroup(p, e.currentTarget, e));

        this.groupPopup = p;
      },
      cb: (p) => {
        const btnPrimary = p.querySelector('.btn-primary');
        const inputs = p.querySelectorAll('input');

        p.dataset.action = 'update';
        p.dataset.noclosure = true;

        p.querySelector('.modal-title span').innerHTML = `<?=_("Update this group")?>`;

        inputs[0].value = args.name;
        inputs[1].value = args.description || '';

        btnPrimary.dataset.groupid = args.groupId;
        btnPrimary.innerHTML = `<i class="fas fa-save"></i> <?=_("Save")?>`;
        p.querySelector('button.btn-secondary').innerHTML = `<i class="fas fa-times"></i> <?=_("Close")?>`;

        H.openModal({item: p});
      }
    });
  }

  // METHOD open()
  open() {
    this.displayGroups();
  }

  // METHOD linkGroup()
  linkGroup(args) {
    const wall = S.getCurrent('wall');
    const group = this.tag.querySelector('li.active');

    H.request_ws(
      'POST',
      `wall/${wall.getId()}/group/${group.dataset.id}/link`,
      {
        type: group.parentNode
                .classList.contains(`gtype-<?=WPT_GTYPES_DED?>`) ?
                  <?=WPT_GTYPES_DED?> : <?=WPT_GTYPES_GEN?>,
        access: this.groupAccessPopup.querySelector(
          `input[name="access"]:checked`).value,
        sendmail: this.groupAccessPopup
          .querySelector(`.send-msg input[type="checkbox"]`).checked ?
            {
              userFullname:
                P.get(document.getElementById('accountPopup'), 'account')
                  .getProp('fullname'),
              wallTitle: wall.getName(),
            } : null,
      },
      // success cb
      (d) => {
        if (d.error_msg) {
          H.raiseError(null, d.error_msg);
        } else {
          this.displayGroups();
        }
      });
  }

  // METHOD unlinkGroup()
  unlinkGroup(args, groupType) {
    bootstrap.Tab.getOrCreateInstance(this.tag.querySelector(
      `a[href="#gtype-${groupType}"]`)).show();

    H.request_ws(
      'POST',
      `wall/${S.getCurrent('wall').getId()}/group/${args.id}/unlink`,
      null,
      // success cb
      (d) => {
        if (d.error_msg) {
          H.raiseError(null, d.error_msg);
        } else {
          this.displayGroups();
        }
      });
  }

  // METHOD deleteGroup()
  deleteGroup() {
    const group = this.tag.querySelector('li.active');

    H.request_ws(
      'DELETE',
      (Number(group.dataset.type) === <?=WPT_GTYPES_DED?>) ?
        `wall/${S.getCurrent('wall').getId()}/`+
          `group/${group.dataset.id}` :
        `group/${group.dataset.id}`,
      null,
      // success cb
      (d) => {
        if (d.error_msg) {
          H.raiseError(null, d.error_msg);
        } else {
          this.displayGroups();
        }
      });
  }

  // METHOD createGroup()
  createGroup(type, args) {
    H.request_ws(
      'PUT',
      (Number(type) === <?=WPT_GTYPES_DED?>) ?
        `wall/${S.getCurrent('wall').getId()}/group` :
        `group`,
      args,
      // success cb
      (d) => {
        if (d.error_msg) {
          H.displayMsg({type: 'warning', msg: d.error_msg});
        } else {
          this.displayGroups();
          bootstrap.Modal.getInstance(this.groupPopup).hide();
        }
      });
  }

  // METHOD updateGroup()
  updateGroup(args) {
    H.request_ws(
      'POST',
      `group/${args.groupId}`,
      args,
      // success cb
      (d) => {
        if (d.error_msg) {
          H.displayMsg({type: 'warning', msg: d.error_msg});
        } else {
          this.displayGroups();
          bootstrap.Modal.getInstance(this.groupPopup).hide();
        }
      });
  }

  // METHOD displayGroups()
  async displayGroups() {
    const tag = this.tag;
    const wall = S.getCurrent('wall');
    const isOwner = (wall.settings.ownerid === U.getId());
    const body = tag.querySelector('.modal-body');

    const r = await H.fetch(
      'GET',
      `wall/${wall.getId()}/group`);

    if (r.error) {
      if (r.error_msg) {
        H.raiseError(null, r.error_msg);
      }
      return;
    }
      
    const div = body.querySelector('.list-group.attr');
    const pClass = div.parentNode.classList;
    const label = div.parentNode.querySelector('span');
    let html = '';

    if (r.in.length) {
      const active = document.querySelector(
        '.modal li.list-group-item.active');

      if (isOwner) {
        wall.setShared(true);
      }

      tag.querySelector('.grp-lb').innerText = `<?=_("Other available groups:")?>`;

      pClass.add('scroll');

      label.classList.remove('nogroup');
      label.innerHTML = `<label class="mb-2"><?=_("The wall is shared with the following groups:")?></label>`;

      r.in.forEach((item) => {
        const isDed = (Number(item.item_type) === <?=WPT_GTYPES_DED?>);
        const typeIcon = r.delegateAdminId ? '' : `<i class="${isDed ? "fas fa-asterisk":"far fa-circle"} fa-xs"></i>`;
        const unlinkBtn = r.delegateAdminId ? '' : `<button data-action="unlink-group" type="button" class="btn btn-secondary btn-xs btn-share" title="<?=_("Cancel sharing for this group")?>"><i class="fas fa-minus-circle"></i><?=_("Unshare")?></button>`;

          html += `<li data-id="${item.id}" data-type="${item.item_type}" data-name="${H.htmlEscape(item.name)}" ${r.delegateAdminId ? '' : ` data-creator="1"`} data-delegateadminid=${r.delegateAdminId || 0} class="list-group-item${active && Number(active.dataset.id) === item.id ? ' active' : ''}"><div class="userscount" data-action="users-search" title="${item.userscount} <?=_("user(s) in this group")?>">${H.getAccessIcon(item.access)}<span class="wpt-badge inset">${item.userscount}</span></div> <span class="name">${typeIcon}${item.name}</span> <span class="desc">${item.description || ''}</span><div class="float-end"><button data-action="users-search" type="button" class="close" title="<?=_("Manage users")?>"><i class="fas fa-user-friends fa-fw fa-xs"></i></button>${unlinkBtn}</div></li>`;
      });

      pClass[r.in.length === 1 ? 'add' : 'remove']('one');
    } else {
      if (isOwner) {
        wall.setShared(false);
      }

      tag.querySelector('.grp-lb').innerText = `<?=_("Available groups:")?>`;

      pClass.remove('scroll');

      label.classList.add('nogroup');
      label.innerHTML = r.delegateAdminId ?
        `<?=_("You cannot manage any of the existing groups.")?>` :
        `<?=_("The wall is not shared with any group.")?>`;
    }

    div.innerHTML = html;

    if (!r.delegateAdminId) {
      body.querySelectorAll('.delegate-admin-only').forEach((el) => H.hide(el));

      // Display all groups (dedicated and generic)
      [<?=WPT_GTYPES_DED?>, <?=WPT_GTYPES_GEN?>].forEach((type) => {
        const div = body.querySelector(`.list-group.gtype-${type}.noattr`);
        const items = r.notin;
        const pClass = div.parentNode.classList;
        const active = document.querySelector(
          '.modal li.list-group-item.active');
      
        pClass.remove('scroll');
      
        let html = '';
        items.forEach((item) => {
          if (item.item_type === type) {
            html += `<li data-id="${item.id}" data-type="${item.item_type}" data-name="${H.htmlEscape(item.name)}" data-creator="1" class="list-group-item ${active && Number(active.dataset.id) === item.id ? ' active' : ''}"><div class="userscount" data-action="users-search" title="${item.userscount} <?=_("user(s) in this group")?>"><i class="fas fa-layer-group fa-fw"></i> <span class="wpt-badge inset">${item.userscount}</span></div> <span class="name">${item.name}</span> <span class="desc">${item.description || ''}</span><div class="float-end"><button data-action="delete-group" type="button" class="close" title="<?=_("Delete this group")?>"><i class="fas fa-trash fa-fw fa-xs"></i></button><button data-action="users-search" type="button" class="close" title="<?=_("Manage users")?>"><i class="fas fa-user-friends fa-fw fa-xs"></i></button><button data-action="link-group" type="button" class="btn btn-secondary btn-xs btn-share" title="<?=_("Share with this group")?>"><i class="fas fa-plus-circle"></i><?=_("Share")?></button></div></li>`;
          }
        });
      
        div.innerHTML = html;
      
        if (html) {
          pClass.add('scroll');
          pClass[
            div.querySelectorAll('li').length === 1 ? 'add' : 'remove']('one');
        }
      });

      body.querySelectorAll('.creator-only').forEach((el) => H.show(el));
    }
    else
    {
      body.querySelectorAll('.creator-only').forEach((el) => H.hide(el));
      body.querySelectorAll('.delegate-admin-only').forEach((el) => H.show(el));
    }

    H.openModal({item: tag});
  }
});
