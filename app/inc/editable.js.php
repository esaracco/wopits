<?php
/**
  Javascript plugin - Smart input field

  Scope: Wall & Note
  Element: .editable
  Description: Manage inputs editing
*/

  require_once (__DIR__.'/../prepend.php');

  $Plugin = new Wopits\jQueryPlugin ('editable');
  echo $Plugin->getHeader ();

?>

  let _editing = false;

  /////////////////////////// PRIVATE METHODS ///////////////////////////

  // METHOD _clearSelection ()
  const _clearSelection  = ()=>
    {
      window.getSelection && window.getSelection().removeAllRanges () ||
        document.selection && document.selection.empty ();
    };

  // METHOD _getTextWidth ()
  const _getTextWidth = (str, fontSize)=>
    {
      let ret = 0;

      if (str != "")
      {
        const sb = S.getCurrent("sandbox")[0];

        if (fontSize)
          sb.style.fontSize = fontSize;
        else
          sb.removeAttribute ("style");

        sb.innerText = str;

        ret = `${sb.clientWidth+30}px`;
      }

      return ret;
    };

  /////////////////////////// PUBLIC METHODS ////////////////////////////

  Plugin.prototype =
  {
    // METHOD init ()
    init (args)
    {
      const plugin = this,
            editable = plugin.element[0],
            settings = plugin.settings,
            cb = settings.callbacks;

      editable.classList.add ("editable");
      settings._timeoutEditing = 0;
      settings._intervalBlockEditing = 0;

      // EVENT "click" on editable element
      settings.container[0].addEventListener ("click", (e)=>
        {
          // Cancel if:
          // creation of a relation in progress
          if (S.get ("link-from") ||
              // editing has just been cancelled
              S.get ("block-editing"))
            return false;

          if (!settings._intervalBlockEditing &&
              !editable.classList.contains ("editing") &&
              settings.triggerTags
                .indexOf (e.target.tagName.toLowerCase ()) != -1)
          {
            settings._intervalBlockEditing = setInterval (() =>
            {
              if (!_editing)
              {
                clearInterval (settings._intervalBlockEditing);
                settings._intervalBlockEditing = 0;

                cb.edit (()=>
                {
                  _editing = true;
                  plugin.disablePlugins (true);

                  if (!H.haveMouse()) {
                    H.fixVKBScrollStart();
                  }

                  settings._valueOrig = editable.innerText;

                  settings._overflowOrig = settings.container.css ("overflow");
                  settings.container[0].style.overflow = "visible";

                  editable.classList.add ("editing");
                  editable.style.height = `${editable.clientHeight}px`;

                  const html = H.htmlEscape (settings._valueOrig);
                  editable.innerHTML = `<div style="visibility:hidden;height:0">${html}</div><input type="text" value="${html}" maxlength="${settings.maxLength}">`;

                  settings._input = editable.querySelector ("input");

                  if (cb.before)
                    cb.before (plugin, settings._input.value);

                  plugin.resize ();

                  // EVENT "blur" on editable element
                  settings._input.addEventListener ("blur", (e)=>
                    {
                      const el = e.target,
                            title = el.value;

                      e.stopImmediatePropagation ();

                      el.parentNode.parentNode.style.overflow =
                        settings._overflowOrig;

                      editable.classList.remove ("editing");
                      editable.removeAttribute ("style");
                      el.remove ();

                      if (title != settings._valueOrig)
                        cb.update (title);
                      else
                      {
                        editable.innerText = title;
                        cb.unedit ();
                      }

                      _clearSelection ();

                      clearTimeout (settings._timeoutEditing);
                      _editing = false;
                      plugin.disablePlugins (false);

                      if (S.get('vkbData')) {
                        H.fixVKBScrollStop();
                      }

                      S.set ("block-editing", true, 500)
                    });

                  // EVENT "keyup" on editable element
                  settings._input.addEventListener ("keyup", (e)=>
                    {
                      const el = e.target,
                            k = e.which;

                      // ENTER Validate changes
                      if (k == 13)
                        el.blur ();
                      // ESC Cancel edition
                      else if (e.which == 27)
                      {
                        el.value = settings._valueOrig;
                        el.blur ();
                      }
                      // Exclude some control keys
                      else if (
                        k != 9 &&
                        (k < 16 || k > 20) &&
                        k != 27 &&
                        (k < 35 || k > 40) &&
                        k != 45 &&
                        // CTRL+A
                        !(e.ctrlKey && k == 65) &&
                        // CTRL+C
                        !(e.ctrlKey && k == 67) &&
                        // CTRL+V (managed by "paste" event)
                        !(e.ctrlKey && k == 86) &&
                        (k < 144 || k > 145))
                      {
                        plugin.resize ();
                      }
                    });

                  // EVENT "paste" on editable element
                  settings._input.addEventListener ("paste", (e)=>
                    plugin.resize (
                      e.originalEvent.clipboardData.getData ('text')));

                  settings._input.focus();
                });
              }
            }, 250);
          }
        });
    },

    // METHOD setValue ()
    setValue (v)
    {
      this.settings._input.value = v;
    },

    // METHOD cancelAll ()
    cancelAll ()
    {
      this.element.each (function ()
      {
        $(this).editable ("cancel");
      });
    },

    // METHOD cancel ()
    cancel ()
    {
      if (this.element.hasClass ("editing"))
        $(this.settings._input).trigger ("blur");
    },

    // METHOD disablePlugins ()
    disablePlugins (type)
    {
      if (S.get ("zoom-level"))
        return;

      const settings = this.settings;
      let $plug;

      if (settings.wall[0].classList.contains ("ui-draggable"))
        settings.wall.draggable ("option", "disabled", type);

      $plug = settings.container.closest (".ui-draggable");
      if ($plug.length)
        $plug.draggable ("option", "disabled", type);

      $plug = settings.container.closest ("ui-resizable");
      if ($plug.length)
        $plug.resizable ("option", "disabled", type);
    },

    // METHOD resize ()
    // We also pass the value to enable text pasting.
    resize (v)
    {
      const settings = this.settings,
            input = settings._input;

      input.style.width = _getTextWidth (v||input.value, settings.fontSize);

      // Commit change automatically if no activity since
      // 15s.
      clearTimeout (settings._timeoutEditing);
      settings._timeoutEditing = setTimeout (
        ()=> input.blur (), <?=WPT_TIMEOUTS['edit']*1000?>);
    }

  };

/////////////////////////// AT LOAD INIT //////////////////////////////

  $(function ()
  {
    if (H.isLoginPage ())
        return;

    setTimeout (()=>
    {
      document.body.append ($(`<div id="sandbox"></div>`)[0]);
    }, 0);
  });

<?php echo $Plugin->getFooter ()?>
