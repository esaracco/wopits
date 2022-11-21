<?php
/**
Javascript plugin - Walls opener

Scope: Global
Name: owall
Description: Walls opening popup
*/

require_once(__DIR__.'/../prepend.php');

?>

/////////////////////////////////// PLUGIN ////////////////////////////////////

P.register('owall', class extends Wpt_pluginBase {
  // METHOD constructor()
  constructor(settings) {
    super(settings);
    const tag = this.tag;
    const input = tag.querySelector(`input[type="text"]`);

    this.btnClear = tag.querySelector('.btn-clear');
    this.btnPrimary = tag.querySelector('.btn-primary');

    // EVENT "keyup" on input
   input.addEventListener('keyup', (e) => this.search(e.target.value.trim()));

    // EVENT "click" on "clear input" button
    tag.querySelector('.clear-input').addEventListener('click', (e) => {
      input.value = '';
      tag.querySelectorAll('.ow-filters input:checked').forEach(
        (el) => el.click());
    });

    // EVENT "click" on "clear history" button
    this.btnClear.addEventListener('click', (e) => {
      S.getCurrent('settings').set({recentWalls: []});
      document.getElementById('ow-all').click();
      this.controlFiltersButtons();
    });

    // EVENT "click" on "open" button
    tag.querySelector('.btn-primary').addEventListener('click', (e) => {
      (async () => {
        const checked = this.getChecked();
        const len = checked.length;
        const wallDiv = document.createElement('div');
        let wall;

        await Promise.all(checked.map(async (wallId) => {
          wall = await P.getOrCreate(wallDiv, 'wall')
            .open({wallId, noPostProcess: true});
        }));

        if (wall) {
          wall.postProcessLastWall();
        }

        P.remove(wallDiv, 'wall');
      })();
    });

    // EVENT "click" on filters
    tag.querySelector('.ow-filters').addEventListener('click', (e) => {
      if (e.target.classList.contains('ow-filters')) return;

      const auto = e.detail && e.detail.auto;
      const el = e.target.classList.contains('form-check') ?
        e.target.querySelector('input') : e.target;
      let content = false;

      this.btnClear.classList.add('hidden');

      el.checked = true;

      switch (el.id) {
        case 'ow-all':
          let li = tag.querySelector('.list-group li.first');
          li && li.classList.remove('first');
          li = tag.querySelector('.list-group li.last');
          li && li.classList.remove('last');

          if (!auto) {
            this.displayWalls(null, false);
          }

          content = true;
          break;
        case 'ow-recent':
          const recentWalls = U.getRecentWalls();
          const walls = [];

          recentWalls.forEach((wallId) => {
            U.getWalls().forEach(
              (wall) => (wall.id === wallId) && walls.push(wall));
          });

          if (!auto) {
            this.displayWalls(walls, false);
          }

          tag.querySelectorAll('.list-group li.title').forEach(
            (el) => el.classList.add('hidden'));
          Array.from(tag.querySelectorAll('.list-group li'))
            .filter((el) => H.isVisible(el))[0].classList.add('first');

          content = walls.length;

          this.btnClear.classList.remove('hidden');
          break;
        case 'ow-shared':
          if (!auto) {
            this.displayWalls(null, false);
          }

          tag.querySelectorAll('.list-group li').forEach((el) => {
            if (el.dataset.shared !== undefined) {
              content = true;
              el.classList.remove('hidden');
            } else {
              el.classList.add('hidden');
            }
          });

          const liVisible = Array.from(tag.querySelectorAll(
            '.list-group li')).filter((el) => H.isVisible(el));

          if (liVisible.length) {
            liVisible[0].classList.add('first');
            liVisible[liVisible.length - 1].classList.add('last');
          }
          break;
      }

      if (!content) {
        tag.querySelector('.modal-body .list-group').innerHTML =
          `<span class='text-center'><?=_("No result")?></span>`;
      }

      this.controlOpenButton();

      input.focus();
    });

    // EVENT "click"
    tag.addEventListener('click', (e) => {
      const el = e.target;

      // EVENT "click" on "open wall" popup
      if (el.matches('li,li *')) {
        const tagName = el.tagName;

        if (tagName === 'INPUT') {
          this.btnPrimary.classList[
            this.getChecked().length ? 'remove' : 'add']('hidden');
        } else if (tagName !== 'LABEL') {
          const wallDiv = document.createElement('div');
          P.getOrCreate(wallDiv, 'wall').open({
            wallId: ((tagName === 'LI') ? el : el.closest('li')).dataset.id,
          });
          bootstrap.Modal.getInstance(tag).hide();
          P.remove(wallDiv, 'wall');
        }
      }
    });
  }

  // METHOD controlFiltersButtons()
  controlFiltersButtons() {
    const tag = this.tag;
    let i = 0;
    let count = 0;
    let tmp;

    tag.querySelectorAll('#ow-shared,#ow-recent').forEach(
      (el) => H.hide(el.parentNode));

    tmp = tag.querySelectorAll('.list-group li[data-shared]');
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
        H.show(document.getElementById('ow-shared').parentNode,
          'inline-block');
      }
    }

    tmp = U.getRecentWalls();
    if (tmp.length) {
      i = 0;
      while (
          i < tmp.length &&
          document.querySelector(`[data-id="wall-${tmp[i]}"]`)) {
        i++;
      }

      if (i !== tmp.length) {
        ++count;
        H.show(document.getElementById('ow-recent').parentNode,
          'inline-block');
      }
    }

    if (!count) {
      tag.querySelector('.ow-filters').classList.add('hidden');
    }
  }

  // METHOD controlOpenButton()
  controlOpenButton() {
     this.btnPrimary.classList[
         this.getChecked().length ? 'remove' : 'add']('hidden');
  }

  // METHOD getChecked()
  getChecked() {
    const checked = [];

    this.tag.querySelectorAll('.list-group input:checked').forEach(
      (el) => checked.push(el.id.substring(1)));

    return checked;
  }

  // METHOD reset()
  reset() {
    const tag = this.tag;

    tag.dataset.noclosure = 1;

    document.getElementById('ow-all').checked = true;
    tag.querySelector(`input[type="text"]`).value = '';
    tag.querySelectorAll('.list-group input:checked').forEach(
      (el) => el.checked = false);
  }

  // METHOD search()
  search(str) {
    const userId = U.getId();
    const walls = [];
    const wallDiv = document.createElement('div');
    const wall = P.getOrCreate(wallDiv, 'wall');

    U.getWalls().forEach((el) => {
      const re = new RegExp(H.quoteRegex(str), 'ig');

      if (!wall.isOpened(el.id) && (
            el.name.match(re) ||
            (userId !== el.ownerid && el.ownername.match(re)))) {
        walls.push(el);
      }
    });

    P.remove(wallDiv, 'wall');

    this.displayWalls(walls);
  }

  // METHOD displayWalls()
  displayWalls(walls, recurse = true) {
    const tag = this.tag;
    const checked = this.getChecked();
    const _walls = walls || U.getWalls();
    const userId = U.getId();
    let body = '';

    tag.querySelectorAll('.ow-filters,.input-group,.btn-primary,.btn-clear')
        .forEach((el) => el.classList.add('hidden'));

    if (!U.getWalls().length) {
      body = `<?=_("No wall available.")?>`;
    } else { 
      tag.querySelectorAll('.ow-filters,.input-group')
          .forEach((el) => el.classList.remove('hidden'));

      if (_walls.length) {
        let dt = '';

        _walls.forEach((wall) => { 
          if (!document.querySelector(`[data-id="wall-${wall.id}"]`)) {
            const shared = (wall.ownerid !== userId);
            const owner = shared ? `<div class="item-infos"><span class="ownername"><em><?=_("shared by")?></em> ${wall.ownername}</span></div>` : '';
            let dt1 = U.formatDate(wall.creationdate);

            if (dt1 !== dt) {
              dt = dt1;
              body += `<li class="list-group-item title">${dt1}</li>`;
            }

            body += `<li data-id="${wall.id}" ${shared ? 'data-shared' : ''} class="list-group-item"><div class="form-check form-check-inline wpt-checkbox"><input type="checkbox" class="form-check-input" id="_${wall.id}"><label class="form-check-label" for="_${wall.id}"></label></div> ${H.getAccessIcon(wall.access)} ${wall.name}${owner}</li>`;
          }
        });

        if (!body && document.getElementById('ow-all').checked) {
          tag.querySelectorAll('.ow-filters,.input-group,.btn-primary')
              .forEach((el) => el.classList.add('hidden'));

          body = `<i><?=_("All available walls are opened.")?></i>`;
        }
      }
      else {
        body = `<span class='text-center'><?=_("No result")?></span>`;
      }
    }

    tag.querySelector('.modal-body .list-group').innerHTML = body;

    checked.forEach((id) => {
      const el = document.getElementById(`_${id}`);
      if (el) {
        el.checked = true;
      }
    });

    if (recurse) {
      tag.querySelectorAll('.ow-filters input:checked').forEach((el) => {
        el.dispatchEvent(new CustomEvent('click', {
          bubbles: true,
          detail: {auto: true},
        }));
      });
    } else {
      tag.querySelector(`input[type="text"]`).value = '';
    }

    this.controlOpenButton();
  }
});
