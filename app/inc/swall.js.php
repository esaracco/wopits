<?php
/**
  Javascript plugin - Sharing walls

  Scope: Wall
  Elements: #swallPopup
  Description: Manage wall sharing
*/

  require_once(__DIR__.'/../prepend.php');

  $Plugin = new Wopits\jQueryPlugin('swall');
  echo $Plugin->getHeader();

?>

  let $_groupPopup
  let $_groupAccessPopup;

  /////////////////////////// PRIVATE METHODS ///////////////////////////

  // METHOD _displaySection()
  const _displaySection = (div, type, items) => {
    const pClass = div.parentNode.classList;
    const active = document.querySelector('.modal li.list-group-item.active');

    pClass.remove('scroll');

    let html = '';
    items.forEach((item) => {
      if (item.item_type === type) {
        html += `<li data-id="${item.id}" data-type="${item.item_type}" data-name="${H.htmlEscape(item.name)}" class="list-group-item is-wall-creator${active && Number(active.dataset.id) === item.id ? ' active' : ''}"><div class="userscount" data-action="users-search" title="${item.userscount} <?=_("user(s) in this group")?>"><i class="fas fa-layer-group fa-fw"></i> <span class="wpt-badge inset">${item.userscount}</span></div> <span class="name">${item.name}</span> <span class="desc">${item.description || ''}</span><div class="float-end"><button data-action="delete-group" type="button" class="close" title="<?=_("Delete this group")?>"><i class="fas fa-trash fa-fw fa-xs"></i></button><button data-action="users-search" type="button" class="close" title="<?=_("Manage users")?>"><i class="fas fa-user-friends fa-fw fa-xs"></i></button><button data-action="link-group" type="button" class="btn btn-secondary btn-xs btn-share" title="<?=_("Share with this group")?>"><i class="fas fa-plus-circle"></i><?=_("Share")?></button></div></li>`;
      }
    });

    div.innerHTML = html;

    if (html) {
      pClass.add('scroll');

      if (div.querySelector('li').length === 1) {
        pClass.add('one');
      } else {
        pClass.remove('one');
      }
    }
  };

  /////////////////////////// PUBLIC METHODS ////////////////////////////

  // Inherit from Wpt_forms
  Plugin.prototype = Object.create(Wpt_forms.prototype);
  Object.assign(Plugin.prototype,
  {
    // METHOD init()
    init(args) {
      const plugin = this;
      const $share = plugin.element;
      const share = $share[0];

      $_groupPopup = $('#groupPopup');

      share.addEventListener('hidden.bs.modal', (e) =>
        S.getCurrent('wall').wall('menu', {from: 'wall', type: 'have-wall'}));

      // LOCAl FUNCTION __close()
      const __close = () =>
        share.querySelector(
          'li.list-group-item.active').classList.remove('active');


      // EVENT "click"
      plugin.element[0].addEventListener('click', (e) => {
        const el = e.target;

        // EVENT "click" on group list item buttons
        if (el.matches('.list-group-item [data-action],'+
                       '.list-group-item [data-action] *')) {
          const btn = el.getAttribute('data-action') ?
            el : el.closest('[data-action]');
          const row = btn.closest('li');
          const groupType = row.dataset.type;
          const action = btn.dataset.action;
          const id = row.dataset.id;

          e.stopImmediatePropagation();

          row.classList.add('active');

          switch (action) {
            // Users search
            case 'users-search':
              H.loadPopup('usearch', {
                open: false,
                settings: {
                  caller: 'swall',
                  cb_add: () => plugin.displayGroups(),
                  cb_remove: () => plugin.displayGroups(),
                  cb_close: __close,
                },
                cb: ($p) => {
                  const p = $p[0];
                  const groupId = row.dataset.id;
                  const delegateAdminId = row.dataset.delegateadminid || 0;

                  $p.usearch('reset', {full: true});

                  p.dataset.delegateadminid = delegateAdminId;
                  p.dataset.groupid = groupId;
                  p.dataset.grouptype = groupType;

                  if (row.parentNode.classList.contains('noattr')) {
                    p.dataset.noattr = 1;
                  }

                  p.querySelector('.desc').innerHTML = `<?=_("Add or remove users in the group « %s ».")?>`.replace('%s', `<b>${row.dataset.name}</b>`);

                  $p.usearch('displayUsers', {
                    groupId,
                    groupType,
                    wallId: S.getCurrent('wall').wall('getId'),
                   });

                  H.openModal({item: p});
                }
              });
              break;
            // Delete group
            case 'delete-group':
              H.openConfirmPopover({
                 item: $(btn.closest('li').querySelector('.name')),
                 title: `<i class="fas fa-trash fa-fw"></i> <?=_("Delete")?>`,
                 content:
                   (row.parentNode.classList.contains(
                       'gtype-<?=WPT_GTYPES_DED?>')) ?
                     `<?=_("Delete this group?")?>`:
                     `<?=_("This group will no longer be available for the current wall or for your other walls.<br>Delete it anyway?")?>`,
                 cb_close: __close,
                 cb_ok: () => plugin.deleteGroup(),
               });
              break;
            // unlink group
            case 'unlink-group':
              // Ask confirmation only if they are some users in the group to
              // unlink.
              if (row.querySelector(
                    '.userscount .wpt-badge').innerText === '0') {
                plugin.unlinkGroup({id}, groupType);
              } else {
                H.openConfirmPopover({
                   item: $(btn.closest('li').querySelector('.name')),
                   title: `<i class="fas fa-minus-circle fa-fw"></i> <?=_("Unshare")?>`,
                   content: `<?=_("Users will lose their access to the wall.<br>Unshare anyway?")?>`,
                   cb_close: __close,
                   cb_ok: () => plugin.unlinkGroup({id}, groupType)
                 });
              }
              break;
            // Link group
            case 'link-group':
              H.loadPopup('groupAccess', {
                init: ($p) => {
                  $p[0].querySelector(`.send-msg input[type="checkbox"]`)
                      .addEventListener('change', (e) => {
                    const el = e.target;
                    const cls = el.closest('.send-msg').classList;

                    if (el.checked) {
                      cls.remove('disabled');
                    } else {
                      cls.add('disabled');
                    }
                  });

                  $_groupAccessPopup = $p;
                }
              });
              break;
          }
        }
        // EVENT "click" on group list item
        else if (el.matches('.list-group-item, .list-group-item span')) {
          const li = (el.tagName === 'SPAN') ? el.closest('li') : el;

          e.stopImmediatePropagation();

          if (li.classList.contains('is-wall-creator')) {
            li.classList.add('active');

            plugin.openUpdateGroup({
              groupId: li.dataset.id,
              name: li.querySelector('.name').innerText,
              description: li.querySelector('.desc').innerText,
            });
          } else {
            share.querySelector(`button[data-action="users-search"] i`).click();
          }
        }
      });

      // EVENT "click" on buttons
      const _eventCB = (e) => {
        const action = e.target.dataset.action;
        if (action === `add-gtype-<?=WPT_GTYPES_GEN?>` ||
            action === `add-gtype-<?=WPT_GTYPES_DED?>`) {
          plugin.openAddGroup(action.match(/add\-gtype\-([^\-]+)/)[1]);
        }
      };
      share.querySelectorAll('button').forEach((el) =>
        el.addEventListener('click', _eventCB));
    },

    // METHOD onSubmitGroup()
    onSubmitGroup(p, btn, e) {
      const type = btn.dataset.type;
      const groupId = btn.dataset.groupid;
      const inputs = p.querySelectorAll('input');

      e.stopImmediatePropagation();

      if (!groupId) {
        p.dataset.noclosure = true;

        if (this.checkRequired($(inputs)))
          this.createGroup(type, {
            name: H.noHTML(inputs[0].value),
            description: H.noHTML(inputs[1].value),
          });
      } else if (this.checkRequired($(inputs))) {
        this.updateGroup({
          groupId,
          name: H.noHTML(inputs[0].value),
          description: H.noHTML(inputs[1].value),
        });
      }
    },

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
        init: ($p) => {
          const p = $p[0];

          p.querySelector('.btn-primary').addEventListener('click', (e) =>
            this.onSubmitGroup(p, e.target, e));

          $_groupPopup = $p;
        },
        cb: ($p) => {
          const p = $p[0];
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
    },

    // METHOD openUpdateGroup()
    openUpdateGroup(args) {
      H.loadPopup('group', {
        open: false,
        init: ($p) => {
          const p = $p[0];
          
          p.querySelector('.btn-primary').addEventListener('click', (e) =>
            this.onSubmitGroup(p, e.target, e));

          $_groupPopup = $p;
        },
        cb: ($p) => {
          const p = $p[0];
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
    },

    // METHOD open()
    open() {
      this.displayGroups();
    },

    // METHOD linkGroup()
    linkGroup(args) {
      const $wall = S.getCurrent('wall');
      const group = this.element[0].querySelector('li.active');

      H.request_ws(
        'POST',
        `wall/${$wall.wall('getId')}/group/${group.dataset.id}/link`,
        {
          type: group.parentNode
                  .classList.contains(`gtype-<?=WPT_GTYPES_DED?>`) ?
                    <?=WPT_GTYPES_DED?> : <?=WPT_GTYPES_GEN?>,
          access: $_groupAccessPopup[0].querySelector(
            `input[name="access"]:checked`).value,
          sendmail: $_groupAccessPopup[0]
            .querySelector(`.send-msg input[type="checkbox"]`).checked ?
              {
                userFullname: $("#accountPopup").account('getProp', 'fullname'),
                wallTitle: $wall.wall('getName'),
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
    },

    // METHOD unlinkGroup()
    unlinkGroup(args, groupType) {
      bootstrap.Tab.getOrCreateInstance(this.element[0].querySelector(
        `a[href="#gtype-${groupType}"]`)).show();

      H.request_ws(
        'POST',
        `wall/${S.getCurrent('wall').wall('getId')}/group/${args.id}/unlink`,
        null,
        // success cb
        (d) => {
          if (d.error_msg) {
            H.raiseError(null, d.error_msg);
          } else {
            this.displayGroups();
          }
        });
    },

    // METHOD deleteGroup()
    deleteGroup() {
      const group = this.element[0].querySelector('li.active');

      H.request_ws(
        'DELETE',
        (Number(group.dataset.type) === <?=WPT_GTYPES_DED?>) ?
          `wall/${S.getCurrent('wall').wall('getId')}/`+
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
    },

    // METHOD createGroup()
    createGroup(type, args) {
      H.request_ws(
        'PUT',
        (Number(type) === <?=WPT_GTYPES_DED?>) ?
          `wall/${S.getCurrent('wall').wall('getId')}/group` :
          `group`,
        args,
        // success cb
        (d) => {
          if (d.error_msg) {
            H.displayMsg({
              title: `<?=_("Sharing")?>`,
              type: 'warning',
              msg: d.error_msg,
            });
          } else {
            this.displayGroups();
            bootstrap.Modal.getInstance($_groupPopup[0]).hide();
          }
        });
    },

    // METHOD updateGroup()
    updateGroup(args) {
      H.request_ws(
        'POST',
        `group/${args.groupId}`,
        args,
        // success cb
        (d) => {
          if (d.error_msg) {
            H.displayMsg({
              title: `<?=_("Sharing")?>`,
              type: 'warning',
              msg: d.error_msg,
            });
          } else {
            this.displayGroups();
            bootstrap.Modal.getInstance($_groupPopup[0]).hide();
          }
        });
    },

    // METHOD displayGroups()
    async displayGroups() {
      const $share = this.element;
      const wallPlugin = S.getCurrent('wall').wall('getClass');
      const isOwner = (wallPlugin.settings.ownerid === wpt_userData.id);
      const body = $share[0].querySelector('.modal-body');

      const r = await H.fetch(
        'GET',
        `wall/${wallPlugin.settings.id}/group`);

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
          wallPlugin.setShared(true);
        }

        $share[0].querySelector('.grp-lb').innerText = `<?=_("Other available groups:")?>`;

        pClass.add('scroll');

        label.classList.remove('nogroup');
        label.innerHTML = `<label class="mb-2"><?=_("The wall is shared with the following groups:")?></label>`;

        r.in.forEach((item) => {
          const isDed = (Number(item.item_type) === <?=WPT_GTYPES_DED?>);
          const typeIcon = r.delegateAdminId ? '' : `<i class="${isDed ? "fas fa-asterisk":"far fa-circle"} fa-xs"></i>`;
          const unlinkBtn = r.delegateAdminId ? '' : `<button data-action="unlink-group" type="button" class="btn btn-secondary btn-xs btn-share" title="<?=_("Cancel sharing for this group")?>"><i class="fas fa-minus-circle"></i><?=_("Unshare")?></button>`;

            html += `<li data-id="${item.id}" data-type="${item.item_type}" data-name="${H.htmlEscape(item.name)}" data-delegateadminid=${r.delegateAdminId || 0} class="list-group-item${r.delegateAdminId?"":" is-wall-creator"}${active && Number(active.dataset.id) === item.id ? ' active' : ''}"><div class="userscount" data-action="users-search" title="${item.userscount} <?=_("user(s) in this group")?>">${H.getAccessIcon(item.access)}<span class="wpt-badge inset">${item.userscount}</span></div> <span class="name">${typeIcon}${item.name}</span> <span class="desc">${item.description || ''}</span><div class="float-end"><button data-action="users-search" type="button" class="close" title="<?=_("Manage users")?>"><i class="fas fa-user-friends fa-fw fa-xs"></i></button>${unlinkBtn}</div></li>`;
        });

        if (r.in.length === 1) {
          pClass.add('one');
        } else {
          pClass.remove('one');
        }
      } else {
        if (isOwner) {
          wallPlugin.setShared(false);
        }

        $share[0].querySelector('.grp-lb').innerText = `<?=_("Available groups:")?>`;
/*
        wallPlugin.element[0].querySelector('thead.wpt th.wpt')
          .innerHTML = '&nbsp;';
*/

        pClass.remove('scroll');

        label.classList.add('nogroup');
        label.innerHTML = r.delegateAdminId ?
          `<?=_("You cannot manage any of the existing groups.")?>` :
          `<?=_("The wall is not shared with any group.")?>`;
      }

      div.innerHTML = html;

      if (!r.delegateAdminId) {
        body.querySelectorAll('.delegate-admin-only').forEach((el) =>
          el.style.display = 'none');

        _displaySection(body.querySelector('.list-group.gtype-<?=WPT_GTYPES_DED?>.noattr'), <?=WPT_GTYPES_DED?>, r.notin);

        _displaySection(body.querySelector('.list-group.gtype-<?=WPT_GTYPES_GEN?>.noattr'), <?=WPT_GTYPES_GEN?>, r.notin);
  
        body.querySelectorAll('.creator-only').forEach((el) =>
          el.style.display = 'block');
      }
      else
      {
        body.querySelectorAll('.creator-only').forEach((el) =>
          el.style.display = 'none');
        body.querySelectorAll('.delegate-admin-only').forEach((el) =>
          el.style.display = 'block');
      }

      H.openModal({item: $share[0]});
    }
  });

<?php echo $Plugin->getFooter()?>
