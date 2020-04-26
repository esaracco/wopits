<?php
  require_once (__DIR__.'/../class/Wpt_jQueryPlugins.php');
  $Plugin = new Wpt_jQueryPlugins ('settings');
  echo $Plugin->getHeader ();
?>

  /////////////////////////// PUBLIC METHODS ////////////////////////////

  Plugin.prototype =
  {
    // METHOD init ()
    init: function (args)
    {
      const plugin = this,
            $settings = plugin.element;

      $("a.dot-theme")
        .on("click", function ()
        {
          plugin.set ({theme: this.dataset.theme});

          plugin.applyTheme ();
        });

      $settings.find(".timezone")
        .on ("change", function (e)
        {
          const timezone = $(this).val ();

          wpt_openConfirmPopup ({
            type: "reload-app",
            icon: "sync-alt",
            content: `<?=_("Reload wopits to apply the new timezone?")?>`,
            cb_ok: () =>
              $("#settingsPopup").wpt_settings ("applyTimezone", timezone),
            cb_cancel: () =>
              {
                const tz = $(this)[0].dataset.timezone;

                if (tz !== undefined)
                  $("#settingsPopup select.timezone").val (tz);
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
            .on ("click", function ()
            {
              if (!$(this).hasClass ("selected"))
              {
                wpt_openConfirmPopup ({
                  type: "reload-app",
                  icon: "sync-alt",
                  content: `<?=_("Reload wopits to apply the new language?")?>`,
                  cb_ok: () => $("#settingsPopup").wpt_settings ("applyLocale", locale)
                  });
              }
            })
            .append ("<img src='/img/locale/"+locale+"-24.png'>");
        });
    },

    // METHOD applyLocale ()
    applyLocale: function (locale)
    {
      this.set ({locale: locale}, () =>
        {
          this.element.modal ("hide");

          location.href = '/r.php?l='+locale;
        });
    },

    // METHOD applyTimezone ()
    applyTimezone: function (timezone)
    {
      this.set ({timezone: timezone}, () =>
        {
          this.element.modal ("hide");

          location.href = '/r.php';
        });
    },

    // METHOD applyTheme ()
    applyTheme: function ()
    {
      const theme = wpt_userData.settings.theme||"theme-default";

      $("link[id^='theme-']").each (function ()
      {
        this.media = (this.id == theme) ? '' : 'none';
      });

      setTimeout (() => $("<div/>").wpt_postit ("applyTheme"), 250);
    },

    saveOpenedWalls: function (activeWall)
    {
      const plugin = this;
      let openedWalls = [];

      $(".nav-tabs.walls a.nav-link").each (function ()
        {
          const $tab = $(this),
                wallId = $tab.attr("href").split('-')[1];

          openedWalls.push (wallId);

          if (!activeWall && $tab.hasClass ("active"))
            activeWall = wallId;
        });

      plugin.set ({
        openedWalls: openedWalls,
        activeWall: (openedWalls.length) ? activeWall : ""
      });
    },

    // METHOD set ()
    set: function (keyVal, cb)
    {
      wpt_userData.settings = $.extend (wpt_userData.settings, keyVal);

      // if registered user (not login page)
      if (!document.querySelector ("body.login-page"))
        wpt_request_ws (
          "POST",
          "user/settings",
          {settings: JSON.stringify (wpt_userData.settings)},
          // success cb
          cb
        );
      // if login page, save wopits version to set it in user settings when he
      // will be logged
      else if (keyVal.version)
        wpt_storage.set ("version", keyVal.version);
      // if login page, save selected theme to reapply it when user will be
      // logged
      else if (keyVal.theme)
        wpt_storage.set ("theme", keyVal.theme);
    },

    // METHOD getVersion ()
    get: function (key, wallId)
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
      {
        return wpt_userData.settings.openedWalls;
      }
    },

    // METHOD removeWallBackground ()
    removeWallBackground: function (wallId)
    {
      if (wpt_userData.settings.walls &&
          wpt_userData.settings.walls.specific[wallId])
      {
        delete wpt_userData.settings.walls.specific[wallId];
        this.set ({walls: wpt_userData.settings.walls});
      }
    },

    // METHOD setWallBackground ()
    setWallBackground: function (data, wallId)
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

    // METHOD open ()
    open: function ()
    {
      const plugin = this,
            $settings = plugin.element,
            $wall = wpt_sharer.getCurrent ("wall"),
            wallId = ($wall.length) ? $wall.wpt_wall ("getId") : null,
            $colorPicker = $settings.find(".cp"),
            loaded = $settings[0].dataset.loaded,
            ww = $(window).width ();
      let setColorTimeout;

      if (!loaded)
        $settings.find(".modal-body").hide ();

      if ($wall.length)
        $settings.find(".wall-color").html (
          "<?=_("Color of the wall «&nbsp;%s&nbsp;»:")?>"
            .replace("%s", $wall.wpt_wall ("getName")));
      else
        $settings.find(".wall-color").html ("<?=_("Default walls color:")?>");

      if (loaded)
      {
        $colorPicker.colorpicker ("setColor",
          plugin.get ("wall-background", wallId));

        //FIXME
        $colorPicker.find(".ui-colorpicker-swatches")
          .css("width", ww < 435 ? ww - 90 : 435);
      }
      else
      {
        // Load timezones
        wpt_request_ajax (
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
          parts:  ["swatches", "bar", "map"],
          layout: {
            swatches: [0, 0, 2, 1],
            bar: [0, 1, 1, 1],
            map: [1, 1, 1, 1]
          },
          part: {
            bar: { size: 128 },
            map: { size: 128 }
          },
          swatchesWidth: ww < 435 ? ww - 90 : 435,
          color: plugin.get ("wall-background", wallId),
          select: function (e, color)
            {
              const $wall = wpt_sharer.getCurrent ("wall"),
                    wallId = ($wall.length) ? $wall.wpt_wall ("getId") : null;

              if (color.css != plugin.get ("wall-background", wallId))
              {
                const style = {"background-color": color.css};

                if (wallId)
                  $wall.css (style);

                clearTimeout (setColorTimeout);
                setColorTimeout =
                  setTimeout (
                    () => plugin.setWallBackground (style, wallId), 500);
              }
            }
        });
      }

      wpt_openModal ($settings);

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
      if (document.querySelector ("body.login-page"))
      {
        const $plugin = $("#settingsPopup");

        if ($plugin.length)
          $plugin.wpt_settings ();
      }
    });

<?php echo $Plugin->getFooter ()?>
