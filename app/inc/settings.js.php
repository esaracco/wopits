<?php
/**
Javascript plugin - Settings

Scope: Global
Name: settings
Description: Manage user's settings
*/

require_once(__DIR__.'/../prepend.php');

?>

(() => {
'use strict';

/////////////////////////////////// PLUGIN ////////////////////////////////////

P.register('settings', class extends Wpt_pluginBase {
  // METHOD constructor()
  constructor(settings) {
    super(settings);
    const tag = this.tag;
    const head = document.head;

    [`<?=implode('`,`', WPT_THEMES)?>`].forEach((color) => {
      const id = `theme-${color}`;

      if (!document.getElementById(id)) {
        head.appendChild(H.createElement('link', {
          id,
          media: 'none',
          rel: 'stylesheet',
          href: `/css/themes/${color}.css?<?=WPT_VERSION?>`,
        }));
      }
    });

    // TODO Factorization
    // EVENT "click" on theme color button
    const _eventCC = (e) => {
      this.set({theme: e.target.dataset.theme});
      this.applyTheme();
    };
    document.querySelectorAll('a.dot-theme').forEach(
      (el) => el.addEventListener('click', _eventCC));

    // EVENT "change" on timezone select
    tag.querySelector('.timezone').addEventListener('change',
      (e) => {
        const el = e.target;

        H.openConfirmPopup({
          type: 'reload-app',
          icon: 'sync-alt',
          content: `<?=_("Reload wopits to apply the new timezone?")?>`,
          onConfirm: () => this.applyTimezone(el.value),
          onClose: () => {
            const tz = el.dataset.timezone;
            if (tz !== undefined) {
              el.value = tz;
            }
          },
        });
      });

    // EVENT "click" on locale buttons
    const _eventCL = (e) => {
      const el = e.target;
      const div = (el.tagName === 'DIV') ? el : el.closest('div');

      if (!div.classList.contains('selected')) {
        H.openConfirmPopup({
          type: 'reload-app',
          icon: 'sync-alt',
          content: `<?=_("Reload wopits to apply the new language?")?>`,
          onConfirm: () => this.applyLocale(div.dataset.locale),
        });
      }
    };
    tag.querySelectorAll('.locale-picker div').forEach((el) => {
      const locale = el.dataset.locale;

      if (this.settings.locale && this.settings.locale === locale) {
        el.classList.add('selected');
      }

      el.innerHTML = `${locale} <img src='/img/locale/${locale}-24.png'>`;

      el.addEventListener('click', _eventCL);
    });
  }

  // METHOD applyLocale()
  applyLocale(locale) {
    this.set({locale}, () => location.href = `/r.php?l=${locale}`);
  }

  // METHOD applyTimezone()
  applyTimezone(timezone) {
    this.set({timezone}, () => location.href = '/r.php');
  }

  // METHOD applyTheme()
  applyTheme() {
    const theme = U.getTheme();
    const current =
        document.querySelector(`link[id^="theme-"]:not([media="none"])`);

    if (current.id !== theme) {
      const postit = document.querySelector('.postit');

      document.getElementById(theme).media = '';
      current.media = 'none';

      // Apply theme to postits
      if (postit) {
        setTimeout(() => P.get(postit, 'postit').applyTheme(), 250);
      }
    }
  }

  // METHOD saveOpenedWalls()
  saveOpenedWalls(activeWallId, updateRecent = true) {
    const openedWalls = [];
    const recentWalls = U.getRecentWalls();
    let args = {};

    document.querySelectorAll('.nav-tabs.walls a.nav-link').forEach((tab) => {
      const wallId = Number(tab.getAttribute('href').split('-')[1]);

      openedWalls.push(wallId);

      if (!activeWallId && tab.classList.contains('active')) {
        activeWallId = wallId;
      }
    });

    args.openedWalls = openedWalls;
    args.activeWall = openedWalls.length ? activeWallId : null;

    if (openedWalls.length && updateRecent) {
      const idx = recentWalls.indexOf(activeWallId);

      if (idx > -1) {
        recentWalls.splice(idx, 1);
      }

      // Display max 10 recent opened walls
      if (recentWalls.length >= 10) {
        recentWalls.splice(0, 1);
      }

      recentWalls.unshift(activeWallId);
      args.recentWalls = recentWalls;
    }

    this.set(args);
  }

  // METHOD removeWall()
  removeWall(id) {
    this.removeOpenedWall(id);
    this.removeRecentWall(id);
    this.removeWallBackground(id);
    if (U.get('activeWall') === id) {
      U.set('activeWall', null);
    }
  }

  // METHOD removeRecentWall()
  removeRecentWall(wallId) {
    const recentWalls = U.getRecentWalls();
    const idx = recentWalls.indexOf(wallId);

    if (idx > -1) {
      recentWalls.splice(idx, 1);
      this.set({recentWalls});
    }
  }

  // METHOD removeOpenedWall()
  removeOpenedWall(wallId) {
    const openedWalls = U.getOpenedWalls();
    const idx = openedWalls.indexOf(wallId);

    if (idx > -1) {
      openedWalls.splice(idx, 1);
      this.set({openedWalls});
    }
  }

  // METHOD set()
  set(keyVal, cb) {
    U.setSettings({...U.getSettings(), ...keyVal});

    // If registered user (not login page)
    if (!H.isLoginPage()) {
      H.request_ws(
        'POST',
        'user/settings',
        {settings: JSON.stringify(U.getSettings())},
        // success cb
        cb
      );
    // If login page, save wopits version to set it in user settings when he
    // will be logged
    } else if (keyVal.version) {
      ST.set('version', keyVal.version);
    // If login page, save selected theme to reapply it when user will be
    // logged
    } else if (keyVal.theme) {
      ST.set('theme', keyVal.theme);
    }
  }

  // METHOD getVersion()
  get(key, wallId) {
    const settings = U.getSettings();

    // version
    if (key === 'version') {
      return (settings[key] !== undefined) ? settings[key] : '';
    // wall-background
    } else if (key === 'wall-background') {
      let ret = settings.walls;
      if (ret) {
        ret = (wallId && settings.walls.specific[wallId]) ?
          settings.walls.specific[wallId]['background-color'] :
          settings.walls.global['background-color'];
      } else {
        ret = 'ffffff';
      }
      return ret;
    } else if (key === 'openedWalls') {
      return settings.openedWalls;
    }
  }

  // METHOD removeWallBackground()
  removeWallBackground(wallId) {
    const walls = U.get('walls');

    if (walls && walls.specific[wallId]) {
      delete walls.specific[wallId];
      this.set({walls});
    }
  }

  // METHOD setWallBackground ()
  setWallBackground(data, wallId) {
    const settings = U.getSettings();

    if (!settings.walls) {
      settings.walls = {
        'global': {'background-color': 'ffffff'},
        'specific': {},
      };
    }

    if (wallId) {
      settings.walls.specific[wallId] = data;
    } else {
      settings.walls.global = data;
    }

    this.set({walls: settings.walls});
  }

  // METHOD openThemeChooser()
  openThemeChooser() {
    H.loadPopup('themeChooser', {
      noeffect: true,
      init: (p) => {
        // TODO Factorization
        // EVENT "click" on theme color button chooser
        const _eventCC = (e) => {
            this.set({theme: e.target.dataset.theme});
            this.applyTheme();
        };
        p.querySelectorAll('a.dot-theme').forEach(
          (el) => el.addEventListener('click', _eventCC));

        // EVENT "hide.bs.modal" on theme chooser popup
        p.addEventListener('hide.bs.modal', (e) => {
          if (!wpt_userData.settings.theme) {
            this.set({theme: 'theme-default'});
          }
        });

        // EVENT "click" on settings button in theme chooser popup
        p.querySelector('.settings').addEventListener('click', (e) => {
          bootstrap.Modal.getInstance(p).hide();
          this.open();
        });
      }
    });
  }

  // METHOD open()
  async open(args) {
    const tag = this.tag;
    const $settings = $(tag);
    const wall = S.getCurrent('wall');
    const wallId = wall ? wall.getId() : null;
    const $cp = $(tag.querySelector('.cp'));
    const wcContent = tag.querySelector('.wall-color');
    const loaded = tag.dataset.loaded;
    const ww = window.outerWidth;
    //FIXME
    const swatchesWidth = ww < 435 ? ww - 90 : 435;

    if (!loaded) {
      H.hide(tag.querySelector('.modal-body'));
    }

    if (wall) {
      wcContent.innerHTML = `<?=_("Color of the wall «&nbsp;%s&nbsp;»:")?>`
        .replace('%s', wall.getName());
    } else {
      wcContent.innerHTML = `<?=_("Default walls color:")?>`;
    }

    if (loaded) {
      H.setColorpickerColor($cp, this.get('wall-background', wallId));
      $cp[0].querySelector('.ui-colorpicker-swatches').style.width = 
        `${swatchesWidth}px`;
    } else {
      const userTz = U.get('timezone');
      // Load timezones
      const r = await H.fetch('GET', 'common/timezones');
      let html = '';

      r.forEach((tz) => html += `<option name="${tz}"${(tz === userTz) ? ' selected="selected"' : ''}>${tz}</option>`);
      tag.querySelector('.timezone').innerHTML = html;

      // Load color picker
      // TODO Do not use jQuery here
      $settings.find('.cp').colorpicker({
        swatchesWidth,
        parts: ['swatches'],
        select: (e, color) => {
          const wall = S.getCurrent('wall');
          const wallId = wall ? wall.getId() : null;
          const wallTag = wall && wall.tag;

          if (color.css !== this.get('wall-background', wallId)) {
            if (wallId) {
              wallTag.style.backgroundColor = color.css;
            }
            H.setColorpickerColor($cp, color.css, false);
            this.setWallBackground({'background-color': color.css}, wallId);
          }
        }
      });

      H.setColorpickerColor($cp, this.get('wall-background', wallId));
    }

    H.openModal({item: tag});

    if (!loaded) {
      tag.dataset.loaded = 1;
      $settings.find('.modal-body').show('fade');
    }
  }
});

//////////////////////////////////// INIT ////////////////////////////////////

document.addEventListener('DOMContentLoaded', () => {
  if (!H.isLoginPage()) return;

  P.create(document.getElementById('settingsPopup'), 'settings');
});

})();
