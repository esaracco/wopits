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

/////////////////////////// PUBLIC METHODS ////////////////////////////

  Plugin.prototype = {
    wall: {plugin: null, data: null},
    forceHide: false,
    saving: false,
    submitted: false,
    // METHOD init()
    init(args) {
      const popup = this.element[0];

      // EVENT "click" on primary button
      popup.querySelector('.btn-primary').addEventListener('click', (e) => {
        this.submitted = true;
        this.saving = this.wall.plugin.saveProperties();
        if (this.saving) {
          bootstrap.Modal.getInstance(popup).hide();
        }
      });

      // EVENT "hidden.bs.modal" plug's settings popup
      popup.addEventListener('hidden.bs.modal', (e) => {
        if (!H.checkAccess(`<?=WPT_WRIGHTS_ADMIN?>`)) return;

        if (this.forceHide || this.saving) {
          this.unedit();
        }
      });

      // EVENT "hide.bs.modal" plug's settings popup
      popup.addEventListener('hide.bs.modal', (e) => {
        if (!H.checkAccess(`<?=WPT_WRIGHTS_ADMIN?>`)) return;

        if (!this.forceHide && !this.saving) {
          const {
            name,
            description,
            width = null,
            height = null,
          } = this.wall.data;
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

          this.submitted = false;

          if (save) {
             H.preventDefault(e);
             H.openConfirmPopup ({
               type: 'save-wprops-changes',
               icon: 'save',
               content: `<?=_("Save changes?")?>`,
               cb_ok: () => popup.querySelector('.btn-primary').click(),
               cb_close: () => {
                 if (this.submitted && this.saving || !this.submitted) {
                   this.forceHide = true;
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
        H.openConfirmPopover ({
          item: $(e.target),
          title: `<i class="fas fa-heart-broken fa-fw"></i> <?=_("Reject sharing")?>`,
          content: `<?=_("You will lose your access to the wall.<br>Reject anyway?")?>`,
          cb_ok: () => this.removeGroupUser(),
        });
      });
    },

    // METHOD unedit()
    unedit() {
      if (!this.element[0].dataset.uneditdone) {
        this.wall.plugin.unedit();
      }
    },

    // METHOD removeGroupUser()
    removeGroupUser(args) {
      this.wall.plugin.close();

      H.request_ws(
        'DELETE',
        `wall/${this.wall.plugin.getId()}/group/`+
            `${this.element[0].dataset.groups}/removeMe`,
      );
    },

    // METHOD getWallSize()
    getWallSize() {
      const cell = this.wall.plugin.element[0].querySelector('td.wpt');

      return {
        width: Math.floor(cell.offsetWidth),
        height: Math.floor(cell.offsetHeight),
      };
    },

    // METHOD open()
    open(args) {
      this.wall.plugin = args.wall.wall('getClass');

      H.fetch(
        'GET',
        `wall/${this.wall.plugin.getId()}/infos`,
        null,
        // success cb
        (d) => {
          const $popup = this.element;
          const popup = $popup[0];
          const isCreator = (d.user_id == wpt_userData.id);

          this.wall.data = d;
          this.forceHide = false;
          this.saving = false;
          this.submitted = false;

          H.cleanPopupDataAttr(popup);

          popup.querySelector('.description').style.display = 'block';
          popup.querySelector('.creator').innerText = d.user_fullname;
          popup.querySelector('.creationdate').innerText =
            H.getUserDate(d.creationdate, null, 'Y-MM-DD HH:mm');
          popup.querySelector('.size').style.display = 'none';

          if (H.checkAccess(`<?=WPT_WRIGHTS_ADMIN?>`)) {
            const wall = this.wall.plugin.element[0];
            const input = popup.querySelector('.name input');

            $popup.find('.btn-primary').show();
            $popup.find('.ro').hide();
            $popup.find('.adm').show();

            input.value = d.name;
            popup.querySelector('.description textarea').value = d.description;

            if (wall.dataset.rows === '1' && wall.dataset.cols === '1') {
              const {width, height} = this.getWallSize();

              this.wall.data.width = width;
              this.wall.data.height = height;

              popup.querySelector(`[name="wall-width"]`).value = width;
              popup.querySelector(`[name="wall-height"]`).value = height;
              popup.querySelector('.size').style.display = 'block';
            }
          } else {
            $popup.find('.btn-primary').hide();
            $popup.find('.adm').hide ();
            $popup.find('.ro').show ();

            $popup.find('.name .ro').html(H.nl2br(d.name));
            if (d.description) {
              $popup.find('.description .ro').html(H.nl2br (d.description));
            } else {
              $popup.find('.description').hide ();
            }
          }

          if (isCreator) {
            $popup.find('.reject-sharing').hide();
          } else {
            $popup.find('.reject-sharing').show();
            popup.dataset.groups = d.groups.join(',');
          }

          popup.dataset.noclosure = true;
          H.openModal({item: popup});
        });
    }
  };

<?php echo $Plugin->getFooter()?>
