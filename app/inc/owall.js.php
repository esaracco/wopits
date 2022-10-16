<?php
/**
  Javascript plugin - Walls opener

  Scope: Global
  Elements: #owallPopup
  Description: Walls opening popup
*/

  require_once(__DIR__.'/../prepend.php');

  $Plugin = new Wopits\jQueryPlugin('owall');
  echo $Plugin->getHeader();

?>

/////////////////////////// PUBLIC METHODS ////////////////////////////

  Plugin.prototype = {
    btnClear: null,
    btnPrimary: null,
    // METHOD init()
    init(args) {
      const plugin = this;
      const $owall = plugin.element;
      const owall = $owall[0];
      const input = owall.querySelector(`input[type="text"]`);

      this.btnClear = owall.querySelector('.btn-clear');
      this.btnPrimary = owall.querySelector('.btn-primary');

      // EVENT "keyup" on input
     input.addEventListener('keyup',
         (e) => plugin.search(e.target.value.trim()));

      // EVENT "kepress" on input
      owall.addEventListener('keypress', (e) => {
        if (e.which === 13 &&
            $owall.find('.list-group-item[data-id]:visible').length === 1) {
          $owall.find('.list-group-item[data-id]:visible').click();
        }
      });

      // EVENT "click" on "clear input" button
      owall.querySelector('.clear-input').addEventListener('click', (e) => {
        input.value = '';
        owall.querySelectorAll('.ow-filters input:checked').forEach(
            (el) => el.dispatchEvent(new CustomEvent('click')));
      });

      // EVENT "click" on "clear history" button
      this.btnClear.addEventListener('click', (e) => {
        $('#settingsPopup').settings('set', {recentWalls: []});
        document.getElementById('ow-all').click();
        plugin.controlFiltersButtons();
      });

      // EVENT "click" on "open" button
      owall.querySelector('.btn-primary').addEventListener('click', (e) => {
        (async () => {
          const checked = plugin.getChecked();
          const len = checked.length;
          const $el = $('<div/>');
          let $wall;

          await Promise.all(checked.map(async (wallId) => {
            $wall = await $el.wall('open', {wallId, noPostProcess: true});
          }));

          if ($wall$) {
            wall.wall('postProcessLastWall');
          }
        })();
      });

      // EVENT "change" on filters
      owall.querySelector('.ow-filters').addEventListener('change', (e) => {
        const auto = e.detail ? e.detail.auto : false;
        let content = false;

        this.btnClear.classList.add('hidden');

        switch (e.target.id) {
          case 'ow-all':
            owall.querySelector(
                '.list-group li.first')?.classList.remove('first');
            owall.querySelector(
                '.list-group li.last')?.classList.remove('last');

            if (!auto) {
              plugin.displayWalls(null, false);
            }

            content = true;
            break;
          case 'ow-recent':
            const recentWalls = wpt_userData.settings.recentWalls || [];
            const walls = [];

            recentWalls.forEach((wallId) => {
              const id = Number(wallId);
              wpt_userData.walls.list.forEach(
                  (wall) => (wall.id === id) && walls.push(wall));
            });

            if (!auto) {
              plugin.displayWalls(walls, false);
            }

            owall.querySelectorAll('.list-group li.title').forEach(
                (el) => el.classList.add('hidden'));
            $owall.find('.list-group li:visible').first().addClass('first');

            content = walls.length;

            this.btnClear.classList.remove('hidden');
            break;
          case 'ow-shared':
            if (!auto) {
              plugin.displayWalls(null, false);
            }

            owall.querySelectorAll('.list-group li').forEach((el) => {
                if (el.dataset.shared !== undefined) {
                  content = true;
                  el.classList.remove('hidden');
                } else {
                  el.classList.add('hidden');
                }
              });

            $owall.find('.list-group li:visible').first().addClass('first');
            $owall.find('.list-group li:visible').last().addClass('last');
            break;
        }

        if (!content) {
          owall.querySelector('.modal-body .list-group').innerHTML =
              `<span class='text-center'><?=_("No result")?></span>`;
        }

        plugin.controlOpenButton();

        input.focus();
      });

      // EVENT "click"
      owall.addEventListener('click', (e) => {
        const el = e.target;

        // EVENT "click" on "open wall" popup
        if (el.matches('li,li *')) {
          const tag = el.tagName;

          if (tag === 'INPUT') {
            this.btnPrimary.classList[
                plugin.getChecked().length ? 'remove' : 'add']('hidden');
          } else if (tag !== 'LABEL') {
            $('<div/>').wall('open', {
              wallId: ((tag === 'LI') ? el : el.closest('li')).dataset.id,
            });
            bootstrap.Modal.getInstance(owall).hide();
          }
        }
      });
    },

    // METHOD controlFiltersButtons()
    controlFiltersButtons() {
      const $owall = this.element;
      let i = 0;
      let count = 0;
      let tmp;

      $owall.find('#ow-shared,#ow-recent').parent().hide();

      tmp = $owall[0].querySelectorAll('.list-group li[data-shared]');
      if (tmp.length) {
        let found = false;

        i = 0;
        while (
            i < tmp.length &&
            !(found = document.querySelector(
                  `[data-id="wall-${tmp[i].dataset.id}"]`))) {
          i++;
        }

        if (!found) {
          ++count;
          $owall.find('#ow-shared').parent().show();
        }
      }

      tmp = wpt_userData.settings.recentWalls || [];
      if (tmp.length) {
        i = 0;
        while (
            i < tmp.length &&
            document.querySelector(`[data-id="wall-${tmp[i]}"]`)) {
          i++;
        }

        if (i !== tmp.length) {
          ++count;
          $owall.find('#ow-recent').parent().show();
        }
      }

      if (!count) {
        $owall[0].querySelector('.ow-filters').classList.add('hidden');
      }
    },

    // METHOD controlOpenButton()
    controlOpenButton() {
       this.btnPrimary.classList[
           this.getChecked().length ? 'remove' : 'add']('hidden');
    },

    // METHOD getChecked()
    getChecked() {
      const checked = [];

      this.element[0].querySelectorAll('.list-group input:checked').forEach(
          (el) => checked.push(el.id.substring(1)));

      return checked;
    },

    // METHOD reset()
    reset() {
      const owall = this.element[0];

      owall.dataset.noclosure = 1;

      document.getElementById('ow-all').checked = true;
      owall.querySelector(`input[type="text"]`).value = '';
      owall.querySelectorAll(".list-group input:checked").forEach(
          (el) => el.checked = false);
    },

    // METHOD search()
    search(str) {
      const openedWalls = wpt_userData.settings.openedWalls;
      const userId = wpt_userData.id;
      const walls = [];
      const $wall = $('<div/>');

      wpt_userData.walls.list.forEach((el) => {
        const re = new RegExp(H.quoteRegex(str), 'ig');

        if (!$wall.wall('isOpened', el.id) && (
              el.name.match(re) ||
              (userId !== el.ownerid && el.ownername.match(re))))
          walls.push(el);
      });

      this.displayWalls(walls);
    },

    // METHOD displayWalls()
    displayWalls(walls, recurse = true) {
      const $owall = this.element;
      const owall = $owall[0];
      const checked = this.getChecked();
      const _walls = walls || wpt_userData.walls.list;
      let body = '';

      owall.querySelectorAll('.ow-filters,.input-group,.btn-primary,.btn-clear')
          .forEach((el) => el.classList.add('hidden'));

      if (!wpt_userData.walls.list.length) {
        body = `<?=_("No wall available.")?>`;
      } else { 
        owall.querySelectorAll('.ow-filters,.input-group')
            .forEach((el) => el.classList.remove('hidden'));

        if (_walls.length) {
          let dt = '';

          _walls.forEach((wall) => { 
            if (!document.querySelector(`[data-id="wall-${wall.id}"]`)) {
              const shared = (wall.ownerid !== wpt_userData.id);
              const owner = shared ? `<div class="item-infos"><span class="ownername"><em><?=_("shared by")?></em> ${wall.ownername}</span></div>` : '';
              let dt1 = H.getUserDate(wall.creationdate);

              if (dt1 !== dt) {
                dt = dt1;
                body += `<li class="list-group-item title">${dt1}</li>`;
              }
  
              body += `<li data-id="${wall.id}" ${shared ? 'data-shared' : ''} class="list-group-item"><div class="form-check form-check-inline wpt-checkbox"><input type="checkbox" class="form-check-input" id="_${wall.id}"><label class="form-check-label" for="_${wall.id}"></label></div> ${H.getAccessIcon(wall.access)} ${wall.name}${owner}</li>`;
            }
          });
  
          if (!body && document.getElementById('ow-all').checked) {
            owall.querySelectorAll('.ow-filters,.input-group,.btn-primary')
                .forEach((el) => el.classList.add('hidden'));

            body = `<i><?=_("All available walls are opened.")?></i>`;
          }
        }
        else {
          body = `<span class='text-center'><?=_("No result")?></span>`;
        }
      }

      owall.querySelector('.modal-body .list-group').innerHTML = body;

      checked.forEach((id) => {
        const el = document.getElementById(`_${id}`);
        if (el) {
          el.checked = true;
        }
      });

      if (recurse) {
        owall.querySelectorAll('.ow-filters input:checked').forEach((el) => {
          el.dispatchEvent(new CustomEvent('click', {
            bubbles: true,
            detail: {auto: true},
          }));
        });
      } else {
        owall.querySelector(`input[type="text"]`).value = '';
      }

      this.controlOpenButton();
    }
  };

<?php echo $Plugin->getFooter()?>
