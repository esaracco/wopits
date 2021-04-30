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
            $head = $("head");

      ["<?=implode('","', WPT_THEMES)?>"].forEach ((color) =>
        {
          const id = "theme-"+color;

          if (!document.getElementById (id))
            $head.append (`<link rel="stylesheet" href="/css/themes/${color}.css<?=(php_sapi_name()!='cli')?'.php?'.date('U'):'?'.WPT_VERSION?>" id="${id}" media="none">`);
        });

      $("a.dot-theme")
        // EVENT click on theme color button
        .on("click", function ()
        {
          plugin.set ({theme: this.dataset.theme});
          plugin.applyTheme ();
        });

      $settings.find(".timezone")
        // EVENT change on timezone select
        .on("change", function (e)
        {
          const timezone = this.value;

          H.openConfirmPopup ({
            type: "reload-app",
            icon: "sync-alt",
            content: `<?=_("Reload wopits to apply the new timezone?")?>`,
            cb_ok: () => plugin.applyTimezone (timezone),
            cb_close: () =>
              {
                const tz = this.dataset.timezone;

                if (tz !== undefined)
                  $settings.find("select.timezone").val (tz);
              }
          });
        });

      $settings.find(".locale-picker > div").each (function ()
        {
          const $item = $(this),
                locale = $item[0].dataset.locale;

          if (plugin.settings.locale && plugin.settings.locale == locale)
            $item.addClass ("selected");

          $item
            // EVENT click on locale buttons
            .on("click", function ()
            {
              if (!$(this).hasClass ("selected"))
              {
                H.openConfirmPopup ({
                  type: "reload-app",
                  icon: "sync-alt",
                  content: `<?=_("Reload wopits to apply the new language?")?>`,
                  cb_ok: () => plugin.applyLocale (locale)
                });
              }
            })
            .append (`<img src='/img/locale/${locale}-24.png'>`);
        });
    },

    // METHOD applyLocale ()
    applyLocale (locale)
    {
      this.set ({locale: locale}, () =>
        {
          this.element.modal ("hide");

          location.href = '/r.php?l='+locale;
        });
    },

    // METHOD applyTimezone ()
    applyTimezone (timezone)
    {
      this.set ({timezone: timezone}, () =>
        {
          this.element.modal ("hide");

          location.href = '/r.php';
        });
    },

    // METHOD applyTheme ()
    applyTheme ()
    {
      const theme = wpt_userData.settings.theme||"theme-default",
            current = document.querySelector (
                        "link[id^='theme-']:not([media='none'])");

      if (!current || current.id != theme)
      {
        document.querySelectorAll("link[id^='theme-']").forEach ((link)=>
          link.media = (link.id == theme) ? "" : "none");

        // Apply theme to postits
        if (document.querySelector(".postit"))
          setTimeout (()=> $("<div/>").postit ("applyTheme"), 250);
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
        init: ($p)=>
        {
          $p.find("a.dot-theme")
            // EVENT click on theme color button chooser
            .on("click", function ()
            {
              plugin.set ({theme: this.dataset.theme});
              plugin.applyTheme ();
            });

          $p
            // EVENT theme chooser popup closing
            .on("hide.bs.modal", function ()
            {
              if (!wpt_userData.settings.theme)
                plugin.set ({theme: "theme-default"});
            });

          $p.find(".settings")
            // EVENT click on settings button in theme chooser popup
            .on("click", function ()
            {
              $p.modal ("hide");
              plugin.open ();
            });
        }
      });
    },

    // METHOD open ()
    open ()
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
        $settings.find(".wall-color").html ("<?=_("Default walls color:")?>");

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

      H.openModal ($settings);

      if (!loaded)
      {
        $settings[0].dataset.loaded = 1;
        $settings.find(".modal-body").show ("fade");
      }
    }
  };

  /////////////////////////// AT LOAD INIT //////////////////////////////

  $(function ()
    {
      if (H.isLoginPage ())
      {
        const $plugin = $("#settingsPopup");

        if ($plugin.length)
          $plugin.settings ();
      }
    });

<?php echo $Plugin->getFooter ()?>
