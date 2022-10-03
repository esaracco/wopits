<?php
/**
  Javascript plugin - Settings

  Scope: Global
  Elements: #settings
  Description: Manage user's settings
*/

  require_once (__DIR__.'/../prepend.php');

  $Plugin = new Wopits\jQueryPlugin ('settings');
  echo $Plugin->getHeader ();

?>

  /////////////////////////// PUBLIC METHODS ////////////////////////////

  Plugin.prototype =
  {
    // METHOD init ()
    init (args)
    {
      const plugin = this,
            $settings = plugin.element,
            head = document.head;

      ["<?=implode('","', WPT_THEMES)?>"].forEach((color) => {
          const id = `theme-${color}`;

          if (!document.getElementById (id)) {
            const el = document.createElement('link');
            el.id = id;
            el.media = 'none';
            el.rel = 'stylesheet';
            el.href = `/css/themes/${color}.css?<?=WPT_VERSION?>`;
            head.appendChild(el);
          }
        });

      // TODO Factoring
      // EVENT "click" on theme color button
      const _eventCC = (e)=>
        {
          plugin.set ({theme: e.target.dataset.theme});
          plugin.applyTheme ();
        };
      document.querySelectorAll("a.dot-theme").forEach (
        el=> el.addEventListener ("click", _eventCC));

      // EVENT "change" on timezone select
      $settings[0].querySelector(".timezone").addEventListener("change", (e)=>
        {
          const el = e.target;

          H.openConfirmPopup ({
            type: "reload-app",
            icon: "sync-alt",
            content: `<?=_("Reload wopits to apply the new timezone?")?>`,
            cb_ok: () => plugin.applyTimezone (el.value),
            cb_close: () =>
              {
                const tz = el.dataset.timezone;

                if (tz !== undefined)
                  el.value = tz;
              }
          });
        });

      $settings.find(".locale-picker div").each (function ()
        {
          const locale = this.dataset.locale;

          if (plugin.settings.locale && plugin.settings.locale == locale)
            this.classList.add ("selected");

          this.innerHTML = `${locale} <img src='/img/locale/${locale}-24.png'>`;
        });

        // EVENT "click" on locale buttons
        const _eventCL = (e)=>
          {
            const el = e.target,
                  div = el.tagName=="DIV"?el:el.closest("div");

            if (!div.classList.contains ("selected"))
              {
                H.openConfirmPopup ({
                  type: "reload-app",
                  icon: "sync-alt",
                  content: `<?=_("Reload wopits to apply the new language?")?>`,
                  cb_ok: () => plugin.applyLocale (div.dataset.locale)
                });
              }
          };
        $settings[0].querySelectorAll(".locale-picker div").forEach (
          el=> el.addEventListener ("click", _eventCL));
    },

    // METHOD applyLocale ()
    applyLocale (locale)
    {
      this.set ({locale: locale}, ()=> location.href = `/r.php?l=${locale}`);
    },

    // METHOD applyTimezone ()
    applyTimezone (timezone)
    {
      this.set ({timezone: timezone}, ()=> location.href = "/r.php");
    },

    // METHOD applyTheme()
    applyTheme() {
      const theme = wpt_userData.settings.theme || 'theme-default';
      const current =
          document.querySelector(`link[id^="theme-"]:not([media="none"])`);

      if (current.id !== theme) {
        const postit = document.querySelector('.postit');

        document.getElementById(theme).media = '';
        current.media = 'none';

        // Apply theme to postits
        if (postit) {
          setTimeout(() => $(postit).postit('applyTheme'), 250);
        }
      }
    },

    // METHOD saveOpenedWalls ()
    saveOpenedWalls (activeWallId, updateRecent = true)
    {
      const openedWalls = [],
            recentWalls = wpt_userData.settings.recentWalls||[];
      let args = {};

      document.querySelectorAll(".nav-tabs.walls a.nav-link").forEach ((tab)=>
        {
          const wallId = tab.getAttribute("href").split('-')[1];

          openedWalls.push (wallId);

          if (!activeWallId && tab.classList.contains ("active"))
            activeWallId = wallId;
        });

      args.openedWalls = openedWalls;
      args.activeWall = (openedWalls.length) ? activeWallId : "";

      if (openedWalls.length && updateRecent)
      {
        const idx = recentWalls.indexOf (activeWallId);

        if (idx > -1)
          recentWalls.splice (idx, 1);

        // Display max 10 recent opened walls
        if (recentWalls.length >= 10)
          recentWalls.splice (0, 1);

        recentWalls.unshift (activeWallId);

        args.recentWalls = recentWalls;
      }

      this.set (args);
    },

    // METHOD removeRecentWall ()
    removeRecentWall (wallId)
    {
      const recentWalls = wpt_userData.settings.recentWalls||[],
            idx = recentWalls.indexOf (String(wallId));

      if (idx > -1)
      {
        recentWalls.splice (idx, 1);
        this.set ({recentWalls: recentWalls});
      }
    },

    // METHOD set ()
    set (keyVal, cb)
    {
      wpt_userData.settings = $.extend (wpt_userData.settings, keyVal);

      // if registered user (not login page)
      if (!H.isLoginPage ())
        H.request_ws (
          "POST",
          "user/settings",
          {settings: JSON.stringify (wpt_userData.settings)},
          // success cb
          cb
        );
      // if login page, save wopits version to set it in user settings when he
      // will be logged
      else if (keyVal.version)
        ST.set ("version", keyVal.version);
      // if login page, save selected theme to reapply it when user will be
      // logged
      else if (keyVal.theme)
        ST.set ("theme", keyVal.theme);
    },

    // METHOD getVersion ()
    get (key, wallId)
    {
      // version
      if (key == "version")
        return (wpt_userData.settings[key] !== undefined) ?
                 wpt_userData.settings[key] : "";
      // wall-background
      else if (key == "wall-background")
      {
        let ret = wpt_userData.settings.walls;

        if (ret)
          ret = (wallId && wpt_userData.settings.walls.specific[wallId]) ?
            wpt_userData.settings.walls.specific[wallId]["background-color"] :
            wpt_userData.settings.walls.global["background-color"];
        else
          ret = "ffffff";

        return ret;
      }
      else if (key == "openedWalls")
        return wpt_userData.settings.openedWalls;
    },

    // METHOD removeWallBackground ()
    removeWallBackground (wallId)
    {
      if (wpt_userData.settings.walls &&
          wpt_userData.settings.walls.specific[wallId])
      {
        delete wpt_userData.settings.walls.specific[wallId];
        this.set ({walls: wpt_userData.settings.walls});
      }
    },

    // METHOD setWallBackground ()
    setWallBackground (data, wallId)
    {
      if (!wpt_userData.settings.walls)
        wpt_userData.settings["walls"] = {
          "global": {"background-color":"ffffff"},
          "specific": {}
        };

      if (wallId)
        wpt_userData.settings.walls.specific[wallId] = data;
      else
        wpt_userData.settings.walls.global = data;

      this.set ({walls: wpt_userData.settings.walls});
    },

    // METHOD openThemeChooser ()
    openThemeChooser ()
    {
      const plugin = this;

      H.loadPopup ("themeChooser", {
        noeffect: true,
        init: ($p)=>
        {
          const p = $p[0];

          // TODO Factoring
          // EVENT "click" on theme color button chooser
          const _eventCC = (e)=>
            {
              plugin.set ({theme: e.target.dataset.theme});
              plugin.applyTheme ();
            };
          p.querySelectorAll("a.dot-theme").forEach (
            el=> el.addEventListener ("click", _eventCC));

          // EVENT "hide.bs.modal" on theme chooser popup
          p.addEventListener ("hide.bs.modal", (e)=>
            {
              if (!wpt_userData.settings.theme)
                plugin.set ({theme: "theme-default"});
            });

          // EVENT "click" on settings button in theme chooser popup
          p.querySelector(".settings").addEventListener ("click", (e)=>
            {
              bootstrap.Modal.getInstance($p).hide ();
              plugin.open ();
            });
        }
      });
    },

    // METHOD open ()
    open (args)
    {
      const plugin = this,
            $settings = plugin.element,
            $wall = S.getCurrent ("wall"),
            wallId = ($wall.length) ? $wall.wall ("getId") : null,
            $cp = $settings.find (".cp"),
            loaded = $settings[0].dataset.loaded,
            ww = $(window).width ();

      if (!loaded)
        $settings.find(".modal-body").hide ();

      if ($wall.length)
        $settings.find(".wall-color").html (
          `<?=_("Color of the wall «&nbsp;%s&nbsp;»:")?>`
            .replace("%s", $wall.wall ("getName")));
      else
        $settings.find(".wall-color").html (`<?=_("Default walls color:")?>`);

      if (loaded)
      {
        H.setColorpickerColor ($cp, plugin.get ("wall-background", wallId));

        //FIXME
        $cp.find(".ui-colorpicker-swatches")
          .css("width", ww < 435 ? ww - 90 : 435);
      }
      else
      {
        // Load timezones
        H.fetch (
          "GET",
          "common/timezones",
          null,
          (d) =>
            {
              const userTz = wpt_userData.settings.timezone;
              let html = "";

              d.forEach ((tz) => html += `<option name="${tz}"${(tz==userTz)?' selected="selected"':""}>${tz}</option>`);

              $settings.find(".timezone").html (html);
            }
        );

        // Load color picker
        $settings.find(".cp").colorpicker({
          parts:  ["swatches"],
          swatchesWidth: ww < 435 ? ww - 90 : 435,
          select: function (e, color)
            {
              const $wall = S.getCurrent ("wall"),
                    wallId = ($wall.length) ? $wall.wall ("getId") : null;

              if (color.css != plugin.get ("wall-background", wallId))
              {
                const style = {"background-color": color.css};

                if (wallId)
                  $wall.css (style);

                H.setColorpickerColor ($cp, color.css, false);
                plugin.setWallBackground (style, wallId);
              }
            }
        });

        H.setColorpickerColor ($cp, plugin.get ("wall-background", wallId));
      }

      H.openModal ({item: $settings[0]});

      if (!loaded)
      {
        $settings[0].dataset.loaded = 1;
        $settings.find(".modal-body").show ("fade");
      }
    }
  };

  /////////////////////////// AT LOAD INIT //////////////////////////////

  document.addEventListener ("DOMContentLoaded", ()=>
    {
      if (H.isLoginPage ())
      {
        const $plugin = $("#settingsPopup");

        if ($plugin.length)
          $plugin.settings ();
      }
    });

<?php echo $Plugin->getFooter ()?>
