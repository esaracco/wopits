<?php
/**
Javascript plugin - Wall properties

Scope: Wall
Name: wprop
Description: Manage wall's properties
*/

require_once(__DIR__.'/../prepend.php');

?>

/////////////////////////////////// PLUGIN ////////////////////////////////////

P.register('wprop', class extends Wpt_pluginBase {
  // METHOD constructor()
  constructor(settings) {
    super(settings);
    const tag = this.tag;

    this.wall = {plugin: null, data: null};
    this.forceHide = false;
    this.saving = false;
    this.submitted = false;
    this.sharedGroups = null;

    // EVENT "click" on primary button
    tag.querySelector('.btn-primary').addEventListener('click', (e) => {
      this.submitted = true;
      this.saving = this.wall.plugin.saveProperties();
      if (this.saving) {
        bootstrap.Modal.getInstance(tag).hide();
      }
    });

    // EVENT "hidden.bs.modal" plug's settings popup
    tag.addEventListener('hidden.bs.modal', (e) => {
      if (!H.checkAccess(<?=WPT_WRIGHTS_ADMIN?>)) return;

      if (this.forceHide || this.saving) {
        this.unedit();
      }
    });

    // EVENT "hide.bs.modal" plug's settings popup
    tag.addEventListener('hide.bs.modal', (e) => {
      if (!H.checkAccess(<?=WPT_WRIGHTS_ADMIN?>)) return;

      if (!this.forceHide && !this.saving) {
        const {name, description, width = null, height = null} = this.wall.data;
        const newName = H.noHTML(tag.querySelector('.name input').value);
        const newDescription = H.noHTML(
          tag.querySelector('.description textarea').value);
        let save = (name !== newName || (description || '') !== newDescription);

        if (width) {
          const newWidth = tag.querySelector(`[name="wall-width"]`).value;
          const newHeight = tag.querySelector(`[name="wall-height"]`).value;

          if (width !== parseInt(newWidth) ||
              height !== parseInt(newHeight)) {
            save = true;
          }
        }

        this.submitted = false;

        if (save) {
           H.preventDefault(e);
           H.openConfirmPopup({
             type: 'save-wprops-changes',
             icon: 'save',
             content: `<?=_("Save changes?")?>`,
             onConfirm: () => tag.querySelector('.btn-primary').click(),
             onClose: () => {
               if (!this.submitted || (this.submitted && this.saving)) {
                 this.forceHide = true;
                 bootstrap.Modal.getInstance(tag).hide();
               }
             },
           });
         } else {
           this.unedit();
         }
      }
    });

    // EVENT "click" on reject sharing button
    tag.querySelector('.reject-sharing button')
      .addEventListener('click', (e) => {
      H.openConfirmPopover({
        item: e.target,
        title: `<i class="fas fa-heart-broken fa-fw"></i> <?=_("Reject sharing")?>`,
        content: `<?=_("You will lose your access to the wall.<br>Reject anyway?")?>`,
        onConfirm: () => this.removeGroupUser(),
      });
    });
  }

  // METHOD unedit()
  unedit() {
    if (!this.tag.dataset.uneditdone) {
      this.wall.plugin.unedit();
    }
  }

  // METHOD removeGroupUser()
  removeGroupUser(args) {
    this.wall.plugin.close();

    H.request_ws(
      'DELETE',
      `wall/${this.wall.plugin.getId()}/`+
      `group/${this.sharedGroups.join(',')}/removeMe`,
    );
  }

  // METHOD getWallSize()
  getWallSize() {
    const cell = this.wall.plugin.tag.querySelector('td.wpt');

    return {
      width: Math.floor(cell.offsetWidth),
      height: Math.floor(cell.offsetHeight),
    };
  }

  // METHOD open()
  open(args) {
    const tag = this.tag;

    this.wall.plugin = args.wall;

    H.fetch(
      'GET',
      `wall/${this.wall.plugin.getId()}/infos`,
      null,
      // success cb
      (d) => {
        const isCreator = (d.user_id === U.getId());

        this.wall.data = d;
        this.forceHide = false;
        this.saving = false;
        this.submitted = false;

        H.cleanPopupDataAttr(tag);

        H.show(tag.querySelector('.description'));
        tag.querySelector('.creator').innerText = d.user_fullname;
        tag.querySelector('.creationdate').innerText =
          U.formatDate(d.creationdate, null, 'Y-MM-DD HH:mm');
        H.hide(tag.querySelector('.size'));

        if (H.checkAccess(<?=WPT_WRIGHTS_ADMIN?>)) {
          const wallTag = this.wall.plugin.tag;
          const input = tag.querySelector('.name input');

          H.show(tag.querySelector('.btn-primary'));
          tag.querySelectorAll('.ro').forEach((el) => H.hide(el));
          tag.querySelectorAll('.adm').forEach((el) => H.show(el));

          input.value = d.name;
          tag.querySelector('.description textarea').value = d.description;

          if (wallTag.dataset.rows === '1' && wallTag.dataset.cols === '1') {
            const {width, height} = this.getWallSize();

            this.wall.data.width = width;
            this.wall.data.height = height;

            tag.querySelector(`[name="wall-width"]`).value = width;
            tag.querySelector(`[name="wall-height"]`).value = height;
            H.show(tag.querySelector('.size'));
          }
        } else {
          H.hide(tag.querySelector('.btn-primary'));
          tag.querySelectorAll('.adm').forEach((el) => H.hide(el));
          tag.querySelectorAll('.ro').forEach((el) => H.show(el));

          tag.querySelector('.name .ro').innerHTML = H.nl2br(d.name);
          if (d.description) {
            tag.querySelector('.description .ro').innerHTML =
              H.nl2br(d.description);
          } else {
            H.hide(tag.querySelector('.description'));
          }
        }

        H.hide(tag.querySelector('.reject-sharing'));

        if (!isCreator) {
          // Rejecting shared is only possible with dedicated groups
          this.sharedGroups = d.groups
            .filter((g) => g.item_type === <?=WPT_GTYPES_DED?>)
            .map((g) => g.groups_id);

          if (this.sharedGroups.length) {
            H.show(tag.querySelector('.reject-sharing'));
          }
        }

        tag.dataset.noclosure = true;
        H.openModal({item: tag});
      });
  }
});
