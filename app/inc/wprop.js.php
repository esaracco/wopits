<?php
/**
Javascript plugin - Wall properties

Scope: Wall
Elements: #wpropPopup
Description: Manage wall's properties
*/

require_once(__DIR__.'/../prepend.php');

$Plugin = new Wopits\jQueryPlugin('wprop');
echo $Plugin->getHeader();

?>

/////////////////////////////////// PRIVATE //////////////////////////////////

  const _wall = {plugin: null, data: null};
  let _forceHide = false;
  let _saving = false;
  let _submitted = false;

/////////////////////////////////// PUBLIC ///////////////////////////////////

<?=$Plugin->getPublicSection()?>

Plugin.prototype = {
  sharedGroups: null,
  // METHOD init()
  init() {
    const popup = this.element[0];

    // EVENT "click" on primary button
    popup.querySelector('.btn-primary').addEventListener('click', (e) => {
      _submitted = true;
      _saving = _wall.plugin.saveProperties();
      if (_saving) {
        bootstrap.Modal.getInstance(popup).hide();
      }
    });

    // EVENT "hidden.bs.modal" plug's settings popup
    popup.addEventListener('hidden.bs.modal', (e) => {
      if (!H.checkAccess(<?=WPT_WRIGHTS_ADMIN?>)) return;

      if (_forceHide || _saving) {
        this.unedit();
      }
    });

    // EVENT "hide.bs.modal" plug's settings popup
    popup.addEventListener('hide.bs.modal', (e) => {
      if (!H.checkAccess(<?=WPT_WRIGHTS_ADMIN?>)) return;

      if (!_forceHide && !_saving) {
        const {
          name,
          description,
          width = null,
          height = null,
        } = _wall.data;
        const newName = H.noHTML( popup.querySelector('.name input').value);
        const newDescription = H.noHTML(
                popup.querySelector('.description textarea').value);
        let save = (name !== newName ||
                    (description || '') !== newDescription);

        if (width) {
          const newWidth = popup.querySelector(`[name="wall-width"]`).value;
          const newHeight = popup.querySelector(`[name="wall-height"]`).value;

          if (width !== parseInt(newWidth) ||
              height !== parseInt(newHeight)) {
            save = true;
          }
        }

        _submitted = false;

        if (save) {
           H.preventDefault(e);
           H.openConfirmPopup({
             type: 'save-wprops-changes',
             icon: 'save',
             content: `<?=_("Save changes?")?>`,
             onConfirm: () => popup.querySelector('.btn-primary').click(),
             onClose: () => {
               if (!_submitted || (_submitted && _saving)) {
                 _forceHide = true;
                 bootstrap.Modal.getInstance(popup).hide();
               }
             },
           });
         } else {
           this.unedit();
         }
      }
    });

    // EVENT "click" on reject sharing button
    popup.querySelector('.reject-sharing button')
      .addEventListener('click', (e) => {
      H.openConfirmPopover({
        item: e.target,
        title: `<i class="fas fa-heart-broken fa-fw"></i> <?=_("Reject sharing")?>`,
        content: `<?=_("You will lose your access to the wall.<br>Reject anyway?")?>`,
        onConfirm: () => this.removeGroupUser(),
      });
    });
  },

  // METHOD unedit()
  unedit() {
    if (!this.element[0].dataset.uneditdone) {
      _wall.plugin.unedit();
    }
  },

  // METHOD removeGroupUser()
  removeGroupUser(args) {
    _wall.plugin.close();

    H.request_ws(
      'DELETE',
      `wall/${_wall.plugin.getId()}/`+
      `group/${this.sharedGroups.join(',')}/removeMe`,
    );
  },

  // METHOD getWallSize()
  getWallSize() {
    const cell = _wall.plugin.element[0].querySelector('td.wpt');

    return {
      width: Math.floor(cell.offsetWidth),
      height: Math.floor(cell.offsetHeight),
    };
  },

  // METHOD open()
  open(args) {
    _wall.plugin = args.wall.wall('getClass');

    H.fetch(
      'GET',
      `wall/${_wall.plugin.getId()}/infos`,
      null,
      // success cb
      (d) => {
        const $popup = this.element;
        const popup = $popup[0];
        const isCreator = (d.user_id === wpt_userData.id);

        _wall.data = d;
        _forceHide = false;
        _saving = false;
        _submitted = false;

        H.cleanPopupDataAttr(popup);

        H.show(popup.querySelector('.description'));
        popup.querySelector('.creator').innerText = d.user_fullname;
        popup.querySelector('.creationdate').innerText =
          H.getUserDate(d.creationdate, null, 'Y-MM-DD HH:mm');
        H.hide(popup.querySelector('.size'));

        if (H.checkAccess(<?=WPT_WRIGHTS_ADMIN?>)) {
          const wall = _wall.plugin.element[0];
          const input = popup.querySelector('.name input');

          H.show(popup.querySelector('.btn-primary'));
          popup.querySelectorAll('.ro').forEach((el) => H.hide(el));
          popup.querySelectorAll('.adm').forEach((el) => H.show(el));

          input.value = d.name;
          popup.querySelector('.description textarea').value = d.description;

          if (wall.dataset.rows === '1' && wall.dataset.cols === '1') {
            const {width, height} = this.getWallSize();

            _wall.data.width = width;
            _wall.data.height = height;

            popup.querySelector(`[name="wall-width"]`).value = width;
            popup.querySelector(`[name="wall-height"]`).value = height;
            H.show(popup.querySelector('.size'));
          }
        } else {
          H.hide(popup.querySelector('.btn-primary'));
          popup.querySelectorAll('.adm').forEach((el) => H.hide(el));
          popup.querySelectorAll('.ro').forEach((el) => H.show(el));

          popup.querySelector('.name .ro').innerHTML = H.nl2br(d.name);
          if (d.description) {
            popup.querySelector('.description .ro').innerHTML =
              H.nl2br(d.description);
          } else {
            H.hide(popup.querySelector('.description'));
          }
        }

        H.hide(popup.querySelector('.reject-sharing'));

        if (!isCreator) {
          // Rejecting shared is only possible with dedicated groups
          this.sharedGroups = d.groups
            .filter((g) => g.item_type === <?=WPT_GTYPES_DED?>)
            .map((g) => g.groups_id);

          if (this.sharedGroups.length) {
            H.show(popup.querySelector('.reject-sharing'));
          }
        }

        popup.dataset.noclosure = true;
        H.openModal({item: popup});
      });
  }
};

<?=$Plugin->getFooter()?>
