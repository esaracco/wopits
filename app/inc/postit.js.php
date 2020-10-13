<?php

  require_once (__DIR__.'/../prepend.php');

  use Wopits\DbCache;

  $Plugin = new Wopits\jQueryPlugin ('postit');
  echo $Plugin->getHeader ();

?>

  const _defaultClassColor =
          "color-<?=array_keys(WPT_MODULES['colorPicker']['items'])[0]?>";
  let $_attachmentsPopup,
      $_attachmentEditPopup,
      _originalObject,
      _plugRabbit = {
        line: null,
        // EVENT mousedown on destination postit for relationship creation
        mousedownEvent: function (e)
          {
            const from = S.get ("link-from"),
                  $start = from.obj,
                  $end = $(e.target).closest (".postit");

            e.stopImmediatePropagation ();
            e.preventDefault ();

            if (!$end.length)
              return $start.postit ("cancelPlugAction");

            const end0 = $end[0],
                  endPlugin = $end.postit ("getClass"),
                  endId = endPlugin.settings.id;

              if (from.id != endId &&
                  (end0.dataset.plugs||"").indexOf(from.id) == -1)
              {
                endPlugin.edit ({plugend: true}, ()=>
                  {
                    const $popup = $("#plugPopup"),
                          start0 = $start[0],
                          startPlugin = $start.postit ("getClass"),
                          line = {
                            startId: from.id,
                            endId: endId,
                            obj: endPlugin.getPlugTemplate (start0, end0)
                          };

                    line.obj.setOptions ({
                      dropShadow: null,
                      size: 3,
                      color: "#bbb",
                      dash: {animation: true}
                    });

                    startPlugin.addPlug (line);
                    startPlugin.cancelPlugAction (false);

                    from.cancelCallback = () =>
                      {
                        startPlugin.removePlug (line);
                        startPlugin.cancelPlugAction ();
                      };

                    from.confirmCallback = (label) =>
                      {
                        const $undo = $start.find (
                                ".postit-menu [data-action='undo-plug'] a");

                        if (!label)
                          label = "...";

                        line.label = label;
                        line.obj.setOptions ({
                          size: 4,
                          color: H.getBackgroundColor (
                             document.querySelector (".wall th:first-child")),
                          dash: null,
                          middleLabel: LeaderLine.captionLabel({
                            text: label,
                            fontSize:"13px"
                          })
                        });

                        startPlugin.applyTheme ();
                        startPlugin.addPlugLabel (line);

                        start0.dataset.undo = "add";
                        $undo.removeClass ("disabled");
                        $undo.find("span").text ("« <?=_("Add")?> »");

                        startPlugin.cancelPlugAction ();
                      };

                    H.cleanPopupDataAttr ($popup);

                    S.set ("link-from", from);

                    H.openModal ($popup);
                  });
              }
              else
              {
                if (from.id != endId)
                  H.displayMsg ({
                    type: "warning",
                    msg: "<?=_("This relationship already exists")?>"
                  });
                else
                  endPlugin.cancelPlugAction ();
              }
          },
        // EVENT mousemouve to track mouse pointer during relationship creation
        mousemoveEvent: (e) =>
          {
            const rabbit = document.getElementById ("plug-rabbit");

            rabbit.style.left = e.clientX+"px";
            rabbit.style.top = e.clientY+"px";

            _plugRabbit.line.position ();
          },
        escapeEvent: (e) =>
          {
            if (e.which == 27)
              S.get("link-from").obj.postit ("cancelPlugAction");
          }
      };

  /////////////////////////// PRIVATE METHODS ///////////////////////////

  // METHOD _getMaxEditModalWidth ()
  function _getMaxEditModalWidth (content)
  {
    let maxW = 0,
        tmp;

    (content.match(/<img\s[^>]+>/g)||[]).forEach ((img) =>
      {
        if ( (tmp = img.match (/width="(\d+)"/)) )
        {
          const w = Number (tmp[1]);

          if (w > maxW)
            maxW = w;
        }
      });

    return maxW;
  }

  // METHOD _resetZIndexData ()
  function _resetZIndexData ()
  {
    S.get("postit-oldzindex").obj.css (
      "z-index", S.get ("postit-oldzindex").zIndex);

    S.unset ("postit-oldzindex");
  }

  class _Menu
  {
    // METHOD constructor ()
    constructor (postitPlugin)
    {
      const $currentMenu = postitPlugin.settings.wall.find (".postit-menu");

      if ($currentMenu.length)
        $currentMenu.parent().postit ("closeMenu");

      this.postitPlugin = postitPlugin;
      this.$menu = $(`<?=Wopits\Helper::buildPostitMenu ()?>`);

      this.attachMenuEvents ();
      this.postitPlugin.element.prepend (this.$menu);

      this.checkPlugsMenu ();
    }

    // METHOD show ()
    show ()
    {
      H.enableTooltips (this.$menu);

      this.$menu.show ("fade");
    }

    // METHOD destroy ()
    destroy ()
    {
      this.$menu.remove ();
    }

    // METHOD checkPlugsMenu ()
    checkPlugsMenu (resetUndo)
    {
      const smenu = this.$menu[0].querySelector (
                     "li[data-action='delete-plugs'] .dropdown-item");

      if (this.postitPlugin.havePlugs ())
        smenu.classList.remove ("disabled");
      else
        smenu.classList.add ("disabled");

      if (resetUndo)
        this.postitPlugin.resetPlugsUndo ();

    if (this.postitPlugin.settings.wall.find(".postit").length == 1)
        this.$menu.find("li[data-action='add-plug'] .dropdown-item")
          .addClass ("disabled");
      else
        this.$menu.find("li[data-action='add-plug'] .dropdown-item")
          .removeClass ("disabled");
    }

    // METHOD setPosition ()
    setPosition (pos)
    {
      if (pos == "left")
        this.$menu.removeClass("right").addClass ("left");
      else
        this.$menu.removeClass("left").addClass ("right");
    }

    // METHOD getWidth ()
    getWidth ()
    {
      return this.$menu.width ();
    }

    // METHOD attachMenuEvents ()
    attachMenuEvents ()
    {
      const $menu = this.$menu,
            postitPlugin = this.postitPlugin,
            $postit = postitPlugin.element,
            postit0 = $postit[0],
            postitSettings = postitPlugin.settings,
            $wall = postitSettings.wall,
            writeAccess = H.checkAccess (
              "<?=WPT_WRIGHTS_RW?>", postitSettings.access);

      // Menu events
      $menu.find(">span")
        .off().on("click", function(e)
        {
          const $btn = $(this),
                action = $btn[0].dataset.action;

          e.stopImmediatePropagation ();

          postitPlugin.closePlugMenu ();

          // To prevent race condition with draggable & resizable plugins
          if (S.get ("still-dragging"))
            return;

          switch (action)
          {
            // OPEN post-it edit popup
            case "edit": return postitPlugin.openPostit ();
            // OPEN deadline date picker popup
            case "date-picker": return postitPlugin.openDatePicker ();
            // OPEN deadline date picker popup
            case "attachments": return postitPlugin.openAttachments ();
          }

          postitPlugin.edit (null, () =>
            {
              switch (action)
              {
                case "delete":
                  return H.openConfirmPopover ({
                    item: $postit.find(".btn-menu"),
                    placement: "right",
                    title: `<i class="fas fa-trash fa-fw"></i> <?=_("Delete")?>`,
                    content: "<?=_("Delete this sticky note?")?>",
                    cb_close: () => postitPlugin.unedit (),
                    cb_ok: () => postitPlugin.delete ()
                  });

                // OPEN tags picker
                case "tag-picker":
                  return S.getCurrent("tag-picker").tagPicker ("open", e);

                // OPEN color picker
                case "color-picker":
                  return $(".color-picker").colorPicker ("open", e);
              }
          });
        });

      // Menu submenus events
      $menu
        .find("ul.dropdown-menu")
        .off().on("mousedown", function (e)
        {
          e.stopImmediatePropagation ();
        })
        .find("li")
        .off().on("click", function(e, d)
        {
          const $item = $(this);

          e.stopImmediatePropagation ();

          // Nothing if item menu is disabled (can change dynamically)
          if ($item.find("a").hasClass ("disabled")) return;

          postitPlugin.closePlugMenu ();

          e = d||e;

          switch (this.dataset.action)
          {
            case "add-plug":

              postitPlugin.edit (null, () =>
                {
                  S.set ("link-from", {id: postitSettings.id, obj: $postit});

                  _plugRabbit.line = new LeaderLine (
                    postit0,
                    $(`<div id="plug-rabbit" style="left:${e.clientX}px;top:${e.clientY}px"></div>`).prependTo("body")[0],
                    {
                      size: 3,
                      color: "#9b9c9c",
                      dash: true
                    });

                  $(document)
                    .on("mousedown", _plugRabbit.mousedownEvent)
                    .on ("keydown", _plugRabbit.escapeEvent);

                  $("body").on ("mousemove", _plugRabbit.mousemoveEvent);
                });

              break;

            case "delete-plugs":

              postitPlugin.edit (null, () =>
                {
                  H.openConfirmPopover ({
                    item: $postit.find(".btn-menu"),
                    placement: "left",
                    title: `<i class="fas fa-trash fa-fw"></i> <?=_("Delete")?>`,
                    content: "<?=_("Delete all relationships from this sticky note?")?>",
                    cb_close: () => postitPlugin.unedit (),
                    cb_ok: () =>
                      {
                        postitPlugin.unedit ();

                        postit0.dataset.undo =
                          "delete|"+postitPlugin.removePlugs();
                        $menu.find("[data-action='undo-plug'] a")
                          .removeClass ("disabled")
                          .find("span").text ("« <?=_("Delete")?> »");
                      }
                    });
                  });

              break;

            case "undo-plug":

              const [action, ids] = postit0.dataset.undo.split ("|");

              postitPlugin.resetPlugsUndo ();

              if (action == "add")
              {
                postitPlugin.edit (null, () =>
                {
                  const plugs = postitSettings._plugs;

                  postitPlugin.removePlug (plugs[plugs.length - 1]);
                  postitPlugin.unedit ();
                });
              }
              else if (action == "delete")
              {
                postitPlugin.edit (null, () =>
                  {
                    const toSave = {};

                    ids.split(",").forEach ((item) =>
                    {
                      const startId = postitSettings.id,
                            [endId, label] = item.split (";"),
                            $end = $wall.find (
                                     ".postit[data-id='postit-"+endId+"']");

                      if ($end.length)
                      {
                        toSave[startId] = $postit;
                        toSave[endId] = $end;

                        postitPlugin.addPlug ({
                          startId: startId,
                          endId: endId,
                          label: label,
                          obj: postitPlugin.getPlugTemplate (
                                 postit0, $end[0], label)
                        });
                      }
                      else
                        H.displayMsg ({
                          type: "warning",
                          msg: "<?=_("This item has been deleted")?>"
                        });
                    });

                    S.set ("plugs-to-save", toSave);

                    postitPlugin.unedit ();
                  });
              }

              break;
          }
        });
    }
  }

  /////////////////////////// PUBLIC METHODS ////////////////////////////

  Plugin.prototype =
  {
    // METHOD init ()
    init ()
    {
      const plugin = this,
            $postit = plugin.element,
            postit0 = $postit[0],
            settings = plugin.settings,
            $wall = settings.wall,
            writeAccess = H.checkAccess (
              "<?=WPT_WRIGHTS_RW?>", settings.access);

      settings._plugs = [];
      postit0.dataset.id = "postit-"+settings.id;
      postit0.className = settings.classes || "postit";
      postit0.dataset.tags = settings.tags || "";

      if (settings.obsolete)
        postit0.classList.add ("obsolete");

      $postit
        .css({
          visibility: "hidden",
          top: settings.item_top,
          left: settings.item_left
        })
        // EVENTS mouseenter focusin click on postit
        .on("mouseenter focusin click", function(e)
          {
            const $oldPostit = S.get ("postit-oldzindex");

            if ($oldPostit && $oldPostit.obj.postit ("getId") != settings.id)
              _resetZIndexData ();

            if (!S.get ("postit-oldzindex"))
            {
              S.set ("postit-oldzindex", {
                zIndex: $postit.css ("z-index"),
                obj: $postit
              });

              $postit.css ("z-index", 5000);
            }
          })
        // EVENTS mouseleave focusout on postit
        .on("mouseleave focusout",function(e)
          {
            const $currentPostit = S.getCurrent ("postit");

            if (!$.support.touch &&
                (!$currentPostit || !$currentPostit.length) &&
                S.get ("postit-oldzindex") &&
                !$("#popup-layer").length &&
                !$(".modal:visible").length)
            {
              _resetZIndexData ();
            }
          })
        // Append header, dates, attachment count and tags
        .append (`<div class="postit-header"><span class="title">...</span></div><div class="postit-edit"></div><div class="dates"><div class="creation" title="<?=_("Creation date")?>"><i class="far fa-clock fa-xs"></i> <span>${moment.tz(wpt_userData.settings.timezone).format('Y-MM-DD')}</span></div><div class="end" title="<?=_("Deadline")?>"><i class="fas fa-times-circle"></i><i class="fas fa-hourglass-end fa-xs"></i> <span>...</span></div></div><div class="attachmentscount"${settings.attachmentscount?'':' style="display:none"'}><i data-action="attachments" class="fas fa-paperclip"></i><span class="wpt-badge">${settings.attachmentscount}</span></div><div class="postit-tags">${settings.tags?S.getCurrent("tag-picker").tagPicker("getHTMLFromString", settings.tags):""}</div>`)
        .find(".attachmentscount")
        // EVENT click on attachment count
        .on("click", function ()
          {
            if (writeAccess)
              plugin.openAttachments ();
            else
            {
              plugin.setCurrent ();
              plugin.displayAttachments ();
            }
          });

      if (writeAccess)
      {
        $postit  
        // DRAGGABLE postit
        .draggable ({
          //FIXME "distance" is deprecated -> is there any alternative?
          distance: 10,
          appendTo: "parent",
          revert: "invalid",
          cursor: "pointer",
          //cancel: "span,.title",
          containment: $wall.find ("tbody"),
          scrollSensitivity: 50,
          opacity: 0.35,
          scope: "dzone",
          stack: ".postit",
          drag: function(e, ui)
          {
            if (S.get("revertData").revert)
              return false;

// TODO - 1 - Hide plugs instead of moving them with postits (performance
//            issue with some touch devices)
            plugin.repositionPlugs ();
          },
          start: function(e, ui)
            {
              const p = $postit.position ();

// TODO - 1 - Hide plugs instead of moving them with postits (performance
//            issue with some touch devices)
//              plugin.hidePlugs ();
  
              S.set ("revertData", {
                revert: false,
                top: p.top,
                left: p.left
              });
  
              plugin.edit (
                {ignoreResize: true},
                null,
                ()=> S.get("revertData").revert = true
              );
            },
          stop: function(e, ui)
            {
              const $editable = $postit.find (".editable");

              // Cancel editable.
              if ($editable.length)
                $editable.editable ("cancel");

              S.set ("still-dragging", true, 500);

              if (S.get("revertData").revert)
              {
                const revertData = S.get ("revertData");

                plugin.setPosition ({
                  top: revertData.top,
                  left: revertData.left
                });

                plugin.cancelEdit ();
              }
              else
              {
                // If the postit has been dropped into another cell
                plugin.settings.cellId = $postit.parent().cell ("getId");

                plugin.unedit ();
              }

              // Update postits relationships arrows
              plugin.repositionPlugs ();
// TODO - 1 - Hide plugs instead of moving them with postits (performance
//            issue with some touch devices)
//              H.waitForDOMUpdate (() => plugin.showPlugs ());
            }
        })
        // RESIZABLE post-it
        .resizable ({
          handles: "all",
          autoHide: false,
          resize: function(e, ui)
          {
            // Update postits relationships arrows
            plugin.repositionPlugs ();

            if (S.get("revertData").revert)
              return false;
          },
          start: function(e, ui)
            {
              const $editable = $wall.find (".editable");

              // Cancel all editable (blur event is not triggered on resizing).
              if ($editable.length)
                $editable.editable ("cancelAll");
  
              S.set ("revertData", {
                revert: false,
                width: postit0.clientWidth,
                height: postit0.clientHeight
              });

              plugin.edit (
                {ignoreResize: true},
                null,
                ()=> S.get("revertData").revert = true);
            },
          stop: function(e, ui)
            {
              const revertData = S.get ("revertData");

              S.set ("still-dragging", true, 500);

              if (revertData.revert)
              {
                $postit.css ({
                  width: revertData.width,
                  height: revertData.height
                });

                plugin.cancelEdit ();

                // Update postits relationships arrows
                plugin.repositionPlugs ();
              }
              else
                H.waitForDOMUpdate (() =>
                  {
                    ui.element.parent().cell ("reorganize");

                    plugin.unedit ();
                  });
            }
          });

        $postit.find(".postit-edit")
          // EVENT doubletap on content
          .doubletap (() => plugin.openPostit ());
  
        // Append menu button
        $postit.prepend($(`<div class="btn-menu"><i class="far fa-caret-square-up"></i></div>`)
          // EVENT click on menu button
          .on("click", function(e)
            {
              const $btn = $(this).find("i"),
                    id = settings.id,
                    $header = $postit.find (".postit-header");

              // Create postit menu and show it
              if (!settings.Menu)
              {
                const coord = $header[0].getBoundingClientRect ();

                $wall.wall ("closeAllMenus");

                settings.Menu = new _Menu (plugin);

                if ((coord.x||coord.left)+settings.Menu.getWidth()+20 >
                      $(window).width())
                {
                  $btn
                    .switchClass ("fa-caret-square-up", "fa-caret-square-left");
                  settings.Menu.setPosition ("left");
                }
                else
                  settings.Menu.setPosition ("right");

                $header.addClass ("menu");
                $btn.switchClass ("far", "fas");

                settings.Menu.show ();
              }
              // Destroy postit menu
              else
              {
                $header.removeClass ("menu");
                $btn
                  .switchClass("fas", "far")
                  .switchClass ("fa-caret-square-left", "fa-caret-square-up");

                settings.Menu.destroy ();
                delete settings.Menu;
              }
            }));

        $postit.find(".postit-tags")
          // EVENT mousedown on tags
          .on("mousedown", function (e)
          {
            e.stopImmediatePropagation ();

            plugin.edit (null, () =>
              S.getCurrent("tag-picker").tagPicker ("open", e));
          });

        $postit.find(".dates .end")
          // EVENT click on dates
          .on("click", function(e)
            {
              const $item = $(e.target);
  
              if ($item.hasClass("fa-times-circle"))
              {
                plugin.edit (null, () =>
                {
                  H.openConfirmPopover ({
                    item: $item,
                    title: `<i class="fas fa-trash fa-fw"></i> <?=_("Reset")?>`,
                    content: "<?=_("Reset deadline?")?>",
                    cb_close: () => plugin.unedit (),
                    cb_ok: () => plugin.resetDeadline ()
                  });
                });
              }
              else
                plugin.openDatePicker ()
            });

        // Make postit title editable
        $postit.find(".title").editable ({
          wall: $wall,
          container: $postit.find (".postit-header"),
          maxLength: <?=DbCache::getFieldLength('postits', 'title')?>,
          triggerTags: ["span"],
          fontSize: "14px",
          callbacks: {
            before: (ed, v) => v == "..." && ed.setValue (""),
            edit: (cb) => !S.get ("still-dragging") && plugin.edit (null, cb),
            unedit: () => plugin.unedit (),
            update: (v) =>
              {
                plugin.setTitle (v);
                plugin.unedit ();
              }
          }
        });
      }
      else
        $postit.find(".postit-edit,.postit-header,.postit-tags,.dates")
          // EVENT click on postit
          .click (function ()
          {
            if (!S.get ("still-dragging"))
              plugin.openPostit ();
          });

      if (settings.creationdate)
        plugin.update (settings);
    },

    // METHOD openAttachments ()
    openAttachments ()
    {
      this.edit (null, () => this.displayAttachments ());
    },

    // METHOD openDatePicker ()
    openDatePicker ()
    {
      this.edit (null, () => $("#datePickerPopup").datePicker ("open"));
    },

    // METHOD openPostit ()
    openPostit ()
    {
      const writeAccess = H.checkAccess (
              "<?=WPT_WRIGHTS_RW?>", this.settings.access),
            $wall = this.settings.wall;

      // Open modal with read rights only
      if (!writeAccess)
      {
        if (!this.openAskForExternalRefPopup ({
               cb_ok: () =>
               {
                 $wall.wall ("displayExternalRef", 1);
                 this.open ()}}))
            {
              this.open ();
            }
       }
       else
         this.edit (null, () =>
           {
             if (!this.openAskForExternalRefPopup ({
                    cb_close: (btn) => (btn != "yes") && this.unedit (),
                    cb_ok: () =>
                    {
                      $wall.wall ("displayExternalRef", 1);
                      this.open ()}}))
              {
                this.open ();
              }
           });
    },

    // METHOD open ()
    open ()
    {
      const plugin = this,
            postit = plugin.element[0],
            title = postit.querySelector(".postit-header .title").innerText,
            content = postit.querySelector(".postit-edit").innerHTML||"",
            writeAccess =
              H.checkAccess ("<?=WPT_WRIGHTS_RW?>", this.settings.access);

      if (writeAccess)
      {
        const $popup = $("#postitUpdatePopup");

        S.set ("postit-data", {
          title: (title != "...")?title.replace(/&amp;/g, "&"):""
        });

        $("#postitUpdatePopupTitle").val (S.get("postit-data").title);

        //FIXME
        $(".tox-toolbar__overflow").show ();
        $(".tox-mbtn--active").removeClass ("tox-mbtn--active");

        // Check if post-it content has pictures
        if (content.match (/\/postit\/\d+\/picture\/\d+/))
          postit.dataset.hadpictures = true;
        else
          postit.removeAttribute ("data-hadpictures");

        tinymce.activeEditor.setContent (content);

        if ($.support.touch)
          H.fixVKBScrollStart ();

        H.openModal ($popup, _getMaxEditModalWidth (content));
      }
      else
      {
        const $popup = $("#postitViewPopup");

        plugin.setCurrent ();

        H.cleanPopupDataAttr ($popup);

        $popup.find(".modal-body").html ((content) ?
          content : "<i><?=_("No content.")?></i>");

        $popup.find(".modal-title").html (
          `<i class="fas fa-sticky-note"></i> ${title}`);

        H.openModal ($popup, _getMaxEditModalWidth (content));
      }
    },

    // METHOD displayDeadlineAlert ()
    displayDeadlineAlert ()
    {
      const data = this.element[0].dataset;
      let content;

      // Scroll to the to the post-it if needed.
      H.setViewToElement (this.element);

      H.waitForDOMUpdate (()=>
      {
        if (!data.deadlineepoch)
          content = "<?=_("The deadline for this sticky note has been removed!")?>";
        else if (this.element.hasClass ("obsolete"))
          content = "<?=_("This sticky note has expired.")?>";
        else
        {
          const a = moment.unix (data.deadlineepoch),
                b = moment (new Date ());
          let days = moment.duration(a.diff(b)).asDays ();

          if (days % 1 > 0)
            days = Math.trunc(days) + 1;

          content = (days > 1) ?
            "<?=_("This sticky note will expire in about %s day(s).")?>".replace("%s", days) :
            "<?=_("This sticky note will expire soon.")?>";
        }

        H.openConfirmPopover ({
          type: "info",
          item: this.element,
          title: `<i class="fa fa-exclamation-triangle fa-fw"></i> <?=_("Expiration")?>`,
          content: content
        });
      });
    },

    // METHOD cancelPlugAction ()
    cancelPlugAction (full = true, unedit = true)
    {
      if (_plugRabbit.line)
      {
        $(document)
          .off("mousedown", _plugRabbit.mousedownEvent)
          .off ("keydown", _plugRabbit.escapeEvent);

        $("body").off ("mousemove", _plugRabbit.mousemoveEvent);

        _plugRabbit.line.remove ();
        _plugRabbit.line = null;

        document.getElementById("plug-rabbit").remove ();
      }

      if (full)
      {
        if (unedit)
          S.get("link-from").obj.postit ("unedit");

        S.unset ("link-from");
      }
    },

    // METHOD havePlugs ()
    havePlugs ()
    {
      return (this.settings._plugs||[]).length;
    },

    // METHOD getPlugsIds ()
    getPlugsIds ()
    {
      return this.element[0].dataset.plugs.split (",");
    },

    // METHOD getPlugTemplate ()
    getPlugTemplate (start, end, label)
    {
      const line = new LeaderLine (
              start,
              end,
              {
                dropShadow: {
                  dx: 0.2,
                  dy: 0.2,
                  blur: 1,
                  color: H.getBackgroundColor (
                           document.querySelector (".bg-dark"))
                },
                startPlug: "arrow1",
                endPlug: "arrow1",
                color: H.getBackgroundColor (
                         document.querySelector (".wall th:first-child")),
                middleLabel: LeaderLine.captionLabel ({
                  text: label,
                  fontSize:"13px"
                })
              });

      line.dom = document.querySelector (".leader-line:last-child");

      return line;
    },

    // METHOD applyThemeToPlugs ()
    applyThemeToPlugs ()
    {
      this.settings._plugs.forEach (
        plug => plug.obj.setOptions ({
          dropShadow: {
            dx: 0.2,
            dy: 0.2,
            blur: 1,
            color: H.getBackgroundColor (document.querySelector (".bg-dark"))
          },
          color: H.getBackgroundColor (
                   document.querySelector (".wall th:first-child"))
        }));
    },

    // METHOD applyTheme ()
    applyTheme ()
    {
      document.querySelectorAll(".postit.with-plugs").forEach ((p)=>
        {
          $(p).postit ("applyThemeToPlugs");
        });
    },

    // METHOD resetPlugsUndo ()
    resetPlugsUndo ()
    {
      const postit = this.element[0],
            link = postit.querySelector (
                     ".postit-menu [data-action='undo-plug'] a");

      if (!link) return;

      postit.dataset.undo = "";

      link.classList.add ("disabled");
      link.querySelector("span").innerText = "";
    },

    // METHOD checkPlugsMenu ()
    checkPlugsMenu (resetUndo)
    {
      this.settings.Menu.checkPlugsMenu (resetUndo);
    },

    // METHOD updatePlugLabel ()
    updatePlugLabel (args)
    {
      const label = H.noHTML (args.label);

      for (const plug of this.settings._plugs)
      {
        if (plug.endId == args.endId && label != plug.label)
          {
            plug.label = label;

            plug.obj.setOptions({
              middleLabel: LeaderLine.captionLabel ({
                text: label,
                fontSize: "13px"
              })
            });

            plug.labelObj.find("a span").html (
              (label == ""  || label == "...") ?
                '<i class="fas fa-ellipsis-h"></i>' : label);

            // Update postits relationships arrows
            this.repositionPlugs ();

            break;
          }
      }
    },

    // METHOD addPlugLabel ()
    addPlugLabel (plug, $svg)
    {
      const $div = this.settings.plugsContainer;
      let svg;

      if ($svg)
      {
        $svg.appendTo ($div);
        svg = $svg[0];
      }
      else
        svg = $div[0].querySelector ("#_"+plug.startId+"-"+plug.endId);

      const text = svg.querySelector ("text"),
            pos = text ? text.getBoundingClientRect () : null;

      if (pos)
      {
        const writeAccess = H.checkAccess (
                "<?=WPT_WRIGHTS_RW?>", this.settings.access),
              menu = `<ul class="dropdown-menu"><li data-action="rename"><a class="dropdown-item" href="#"><i class="fa-fw fas fa-edit"></i> <?=_("Rename")?></a></li><li data-action="delete"><a class="dropdown-item" href="#"><i class="fa-fw fas fa-trash"></i> <?=_("Delete")?></a></li></ul>`;

        plug.labelObj = $(`<div class="plug-label dropdown submenu" style="top:${pos.top}px;left:${pos.left}px"><a href="#" ${writeAccess?'data-toggle="dropdown"':""} class="dropdown-toggle"><span>${plug.label != "..." ? H.noHTML (plug.label) : '<i class="fas fa-ellipsis-h"></i>'}</span></a>${writeAccess?menu:""}</div>`).appendTo ($div)
      }
    },

    // METHOD addPlug ()
    addPlug (plug)
    {
      const $start = this.element,
            $end = $(plug.obj.end),
            dataPlugsStart = $start[0].dataset.plugs||"",
            dataPlugsEnd = $end[0].dataset.plugs||"";

      this.resetPlugsUndo ();

      $start[0].dataset.plugs =
        (dataPlugsStart) ? dataPlugsStart+","+plug.endId : plug.endId;

      $end[0].dataset.plugs =
        (dataPlugsEnd) ? dataPlugsEnd+","+plug.startId : plug.startId;

      // Associate SVG line to plug and set its label
      const $svg = $(".leader-line:last-child");
      $svg[0].id = "_"+plug.startId+"-"+plug.endId;
      this.addPlugLabel (plug, $svg);

      // Register plug on start point postit (current plugin)
      this.settings._plugs.push (plug);
      $start[0].classList.add ("with-plugs");

      // Register plug on end point postit
      $end.postit("getSettings")._plugs.push (plug);
      $end[0].classList.add ("with-plugs");
    },

    // METHOD defragPlugsArray ()
    defragPlugsArray ()
    {
      const $postit = this.element,
            settings = this.settings;
      let activePlugs = "",
          i = settings._plugs.length;

      while (i--)
      {
        const plug = settings._plugs[i];

        if (!plug.obj)
          settings._plugs.splice (i, 1);
        else
          activePlugs +=
            ","+((plug.endId == settings.id) ? plug.startId : plug.endId);
      }

      $postit[0].dataset.plugs = activePlugs.substring (1);

      if (!this.havePlugs ())
        $postit.removeClass ("with-plugs");
    },

    // METHOD getPlugById ()
    getPlugById (plugId)
    {
      for (const plug of this.settings._plugs)
        if (plug.startId+"-"+plug.endId == plugId)
          return plug;
    },

    // METHOD removePlug ()
    removePlug (plug, noedit)
    {
      const toDefrag = {};

      this.resetPlugsUndo ();

      if (typeof plug !== "object")
        plug = this.getPlugById (plug);

      // Remove label
      if (plug.labelObj)
      {
        plug.labelObj.remove ();
        plug.labelObj = null;
      }

      // Remove line
      if (plug.obj)
      {
        toDefrag[plug.startId] = $(plug.obj.start);
        toDefrag[plug.endId] = $(plug.obj.end);

        document.body.appendChild (plug.obj.dom);
        plug.obj.remove ();
        plug.obj = null;

        for (const id in toDefrag)
          toDefrag[id].postit ("defragPlugsArray");

        if (!noedit)
          S.set ("plugs-to-save", toDefrag);
      }

      return ","+plug.startId+";"+plug.endId;
    },

    // METHOD removePlugs ()
    removePlugs (noedit)
    {
      const $postit = this.element,
            settings = this.settings,
            postitId = settings.id,
            tmp = {},
            toDefrag = {};
      let ret = "";

      this.resetPlugsUndo ();

      (settings._plugs||[]).forEach ((plug)=>
        {
          // Remove label
          if (plug.labelObj)
          {
            plug.labelObj.remove ();
            plug.labelObj = null;
          }

          toDefrag[plug.startId] = $(plug.obj.start);
          toDefrag[plug.endId] = $(plug.obj.end);

          // Remove line
          document.body.appendChild (plug.obj.dom);
          plug.obj.remove ();
          plug.obj = null;

          tmp[(plug.endId != settings.id) ? plug.endId : plug.startId] =
            plug.label;
        });

      for (const id in toDefrag)
        toDefrag[id].postit ("defragPlugsArray");

      if (!noedit)
        S.set ("plugs-to-save", toDefrag);

      $postit[0].dataset.plugs = "";
      settings._plugs = [];
      $postit.removeClass ("with-plugs");

      for (const id in tmp)
        ret += ","+id+";"+tmp[id];

      return ret.substring (1);
    },

    // METHOD hidePlugs ()
    hidePlugs ()
    {
      if (!this.settings.wall) return;

      this.element.find(".postit-menu [data-action='plug']").hide ();

      this.settings._plugs.forEach ((plug) =>
        {
          plug.labelObj.hide ();
          plug.obj.hide ("none");
        });
    },

    // METHOD showPlugs ()
    showPlugs ()
    {
      if (!this.settings.wall) return;

      this.element.find(".postit-menu [data-action='plug']").show ();

      this.settings._plugs.forEach ((plug) =>
        {
          plug.obj.show ();

          if (plug.labelObj)
            plug.labelObj.show ();
        });
    },

    // METHOD repositionPlugs ()
    repositionPlugs ()
    {
      const div = this.settings.plugsContainer[0];

      this.settings._plugs.forEach ((plug) =>
        {
          plug.obj.position ();

          const p = div.querySelector (
                      "#_"+plug.startId+"-"+plug.endId+" text")
                        .getBoundingClientRect ();

            plug.labelObj[0].style.top = p.top+"px";
            plug.labelObj[0].style.left = p.left+"px";
        });
    },

    // METHOD getId ()
    getId ()
    {
      return this.settings.id;
    },

    // METHOD getCellId ()
    getCellId ()
    {
      return this.settings.cellId;
    },

    // METHOD serializePlugs ()
    serializePlugs ()
    {
      const settings = this.settings;
      let ret = {};

      settings._plugs !== undefined &&
        settings._plugs.forEach ((plug) =>
        {
          // Take in account only plugs from this postit
          if (plug.startId == settings.id)
            ret[plug.endId] = (plug.label == "...") ?
              "" : plug.labelObj[0].querySelector("a span").innerText;
        });

      return ret;
    },

    // METHOD serialize ()
    serialize ()
    {
      const postits = [],
            displayExternalRef =
              (this.settings.wall.wall ("displayExternalRef") == 1);

      this.element.each (function ()
      {
        const $postit = $(this),
              postitId = this.dataset.id.substring (7);
        let data = {};

        if (this.dataset.todelete)
          data = {id: postitId, todelete: true};
        else
        {
          const plugin = $postit.postit ("getClass"),
                title =
                  this.querySelector(".postit-header span.title").innerText,
                content = this.querySelector(".postit-edit").innerHTML||"",
                classcolor = this.className.match (/(color\-[a-z]+)/),
                deadline = (this.dataset.deadlineepoch) ?
                  this.dataset.deadlineepoch :
                  this.querySelector(".dates .end span").innerText.trim (),
                bbox = this.getBoundingClientRect ();
          let tags = [];

          this.querySelectorAll(".postit-tags i").forEach (
            (item) => tags.push (item.dataset.tag));

          data = {
            id: postitId,
            width: Math.trunc (bbox.width),
            height: Math.trunc (bbox.height),
            item_top: (this.offsetTop < 0) ? 0 : Math.trunc (this.offsetTop),
            item_left: (this.offsetLeft < 0) ? 0 : Math.trunc (this.offsetLeft),
            classcolor: (classcolor) ? classcolor[0] : _defaultClassColor,
            title: (title == "...") ? "" : title,
            content: displayExternalRef ?
                       content : plugin.unblockExternalRef (content),
            tags: (tags.length) ? ","+tags.join(",")+"," : null,
            deadline: (deadline == "...") ? "" : deadline,
            alertshift: (this.dataset.deadlinealertshift !== undefined) ?
                          this.dataset.deadlinealertshift : null,
            updatetz: this.dataset.updatetz||null,
            obsolete: this.classList.contains ("obsolete"),
            attachmentscount:
              this.querySelector(".attachmentscount span").innerText,
            plugs: plugin.serializePlugs (),
            hadpictures: !!this.dataset.hadpictures,
            hasuploadedpictures: !!this.dataset.hasuploadedpictures
          };
        }

        postits.push (data);
      });

      return postits;
    },

    // METHOD setDeadline ()
    setDeadline (args)
    {
      const postit = this.element[0],
            date = postit.querySelector (".dates .end"),
            {deadline, alertshift, timezone} = args;
      let human;

      if (!deadline || isNaN (deadline))
        human = deadline||"...";
      else
        human = (deadline) ? H.getUserDate(deadline, timezone) : "...";

      date.querySelector("span").innerText = human;

      if (human == "...")
      {
        postit.classList.remove ("obsolete");

        postit.removeAttribute ("data-deadline");
        postit.removeAttribute ("data-deadlinealertshift");
        postit.removeAttribute ("data-deadlineepoch");
        postit.removeAttribute ("data-updatetz");

        date.classList.remove ("with-alert");
        date.classList.remove ("obsolete");
        date.querySelector("i.fa-times-circle").style.display = "none";
      }
      else
      {
        postit.dataset.deadline = human;
        postit.dataset.deadlineepoch = deadline;

        if (alertshift !== undefined)
        {
          if (alertshift !== null)
          {
            postit.dataset.deadlinealertshift = alertshift;
            date.classList.add ("with-alert");
          }
          else
          {
            postit.removeAttribute ("data-deadlinealertshift");
            date.classList.remove ("with-alert");
          }
        }

        if (H.checkAccess ("<?=WPT_WRIGHTS_RW?>"))
          date.querySelector("i.fa-times-circle").style.display = "block";
      }
    },

    // METHOD resetDeadline ()
    resetDeadline ()
    {
      this.setDeadline ({deadline: "..."});
    },

    // METHOD setCreationDate ()
    setCreationDate (date)
    {
      this.element.find(".dates .creation span").text (date.trim ());
    },

    // METHOD setTitle ()
    setTitle (title)
    {
      this.element.find(".postit-header span.title")
        .text (H.noHTML(title) || "...");
    },

    // METHOD setContent ()
    setContent (newContent)
    {
      const postit = this.element[0],
            edit = postit.querySelector (".postit-edit");

      if (newContent !== edit.innerHTML)
      {
        const externalRef = this.getExternalRef (newContent);

        if (externalRef)
        {
          postit.dataset.haveexternalref = 1;

          if (this.settings.wall.wall ("displayExternalRef") != 1)
            newContent = this.blockExternalRef (newContent, externalRef);
        }
        else
          postit.removeAttribute ("data-haveexternalref");

        edit.innerHTML = newContent;
      }
    },

    // METHOD openAskForExternalRefPopup ()
    openAskForExternalRefPopup (args)
    {
      let ask = (this.getExternalRef () &&
                 this.settings.wall.wall ("displayExternalRef") != 1);

      if (ask)
        H.openConfirmPopover ({
          item: this.element,
          title: `<i class="fas fa-link fa-fw"></i> <?=_("External content")?>`,
          content: "<?=_("This sticky note contains external images or videos.")?><br><?=_("Do you want to load all external content for this wall?")?>",
          cb_close: args.cb_close,
          cb_ok: args.cb_ok
        });

      return ask;
    },

    // METHOD getExternalRef ()
    getExternalRef (content)
    {
      return (content !== undefined) ?
               content.match (/(src\s*=\s*["']?http[^"'\s]+")/ig) :
               this.element[0].dataset.haveexternalref;
    },

    // METHOD blockExternalRef ()
    blockExternalRef (content, externalRef)
    {
      const el = this.element.find(".postit-edit")[0];
      let c = content||el.innerHTML;

      if (!externalRef)
        externalRef = this.getExternalRef (c);

      if (externalRef)
      {
        externalRef.forEach ((src) =>
          c = c.replace (new RegExp ("[^\-]"+H.escapeRegex(src), "g"),
                " external-"+src+" "));

        if (content === undefined)
          el.innerHTML = c;
        else
          return c;
      }
    },

    // METHOD unblockExternalRef ()
    unblockExternalRef (content)
    {
      if (content !== undefined)
        return content.replace (/external\-src/, "src");
      else
        this.element[0].querySelectorAll("[external-src]").forEach ((el)=>
          {
            el.setAttribute ("src", el.getAttribute ("external-src"));
            el.removeAttribute ("external-src");
          });
    },

    // METHOD setPosition ()
    setPosition (args)
    {
      const postit = this.element[0];

      if (args.cellId)
        this.settings.cellId = args.cellId;

      postit.style.top = args.top + "px";
      postit.style.left = args.left + "px";
    },

    // METHOD fixPosition ()
    fixPosition (cPos, cH, cW)
    {
       const postit = this.element[0],
             phTop = postit.querySelector(".postit-header")
                       .getBoundingClientRect().top,
             pW = postit.clientWidth,
             pH = postit.clientHeight;
       let pPos = postit.getBoundingClientRect ();

       // postit is too much high
       if (phTop < cPos.top)
         postit.style.top = "20px";
  
       // postit is too much left
       if (pPos.left < cPos.left)
         postit.style.left = "1px";
  
       // postit is too much right
       if (pPos.left + pW > cPos.left + cW + 1)
         postit.style.left = (cW - pW - 4) + "px";
 
       // postit is too large
       if (pW > cW)
       {
         postit.style.left = "0";
         postit.style.width = (cW - 2) + "px";
       }
  
       pPos = postit.getBoundingClientRect ();
  
       // postit is too much big
       if (pPos.top + pH > cPos.top + cH)
       {
         if (pH > cH)
           postit.style.height = (cH - 22) + "px";
  
         postit.style.top = (cH - postit.clientHeight - 4) + "px";
       }
    },

    // METHOD getClassColor ()
    getClassColor ()
    {
      const classe = this.element[0].className.match(/color\-[a-z]+/);

      return (classe && classe.length) ? classe[0] : _defaultClassColor;
    },

    // METHOD setClassColor ()
    setClassColor (newClass, $item)
    {
       const item = ($item) ? $item[0] : this.element[0],
             classes = item.className.replace(/color\-[a-z]+/, "");

       item.className = classes+" "+newClass;
    },

    // METHOD setPopupColor ()
    setPopupColor ($popup)
    {
      const classe = this.getClassColor ();

      this.setClassColor (classe, $popup.find(".modal-header"));
      this.setClassColor (classe, $popup.find(".modal-title"));
      this.setClassColor (classe, $popup.find(".modal-footer"));
    },

    // METHOD setAttachmentsCount ()
    setAttachmentsCount (count)
    {
      this.element.find(".attachmentscount")
        .css("display", (count) ? "inline-block": "none")
        .find("span").text (count);
    },

    //TODO Attachments plugin?
    // METHOD getAttachmentTemplate ()
    getAttachmentTemplate (item, noWriteAccess)
    {
      const tz = wpt_userData.settings.timezone,
            d = `<button type="button" data-action="delete"><i class="fas fa-trash fa-xs fa-fw"></i></button>`,
            c = (item.ownerid && item.ownerid != wpt_userData.id) ?
                  `<span class="ownername">${item.ownername}</span>` : '';

      return `<li data-target="#file${item.id}" data-toggle="collapse" data-id="${item.id}" data-url="${item.link}" data-icon="${item.icon}" data-fname="${H.htmlEscape(item.name)}" data-description="${H.htmlEscape(item.description||"")}" data-title="${H.htmlEscape(item.title||"")}" class="list-group-item list-group-item-action"><div><i class="fa fa-lg ${item.icon} fa-fw"></i> ${item.title||item.name} <div class="item-infos"><span class="creationdate">${H.getUserDate (item.creationdate)}</span><span class="file-size">${H.getHumanSize(item.size)}</span>${c}</div><div class="right-icons"><button type="button" data-action="download"><i class="fas fa-download fa-xs fa-fw"></i></button>${noWriteAccess?'':d}</div></li><div id="file${item.id}" class="collapse list-group-item" data-parent="#pa-accordion"></div>`;
    },

    // METHOD getAttachmentsCount ()
    getAttachmentsCount ()
    {
      return parseInt (this.element.find(".attachmentscount span").text ());
    },

    // METHOD displayAttachments ()
    displayAttachments ()
    {
      const writeAccess = H.checkAccess ("<?=WPT_WRIGHTS_RW?>");

      H.request_ajax (
        "GET",
        "wall/"+this.settings.wallId+
          "/cell/"+this.settings.cellId+
            "/postit/"+this.settings.id+"/attachment",
        null,
        // success cb
        (d) =>
        {
          let body = '';

          d = d.files;

          if (!d.length)
            body = "<?=_("This sticky note has no attachment")?>";
          else
            d.forEach (
              (a)=> body += this.getAttachmentTemplate (a, !writeAccess));

          if (writeAccess)
            $_attachmentsPopup.find(".btn-primary").show ();
          else
            $_attachmentsPopup.find(".btn-primary").hide ();

          $_attachmentsPopup.find(".modal-body ul").html (body);

          $_attachmentsPopup[0].dataset.noclosure = true; 

          H.openModal ($_attachmentsPopup);
        }
      );
    },

    // METHOD incAttachmentsCount ()
    incAttachmentsCount ()
    {
      this.setAttachmentsCount (this.getAttachmentsCount () + 1);
    },

    // METHOD decAttachmentsCount ()
    decAttachmentsCount ()
    {
      this.setAttachmentsCount (this.getAttachmentsCount () - 1);
    },

    // METHOD uploadAttachment ()
    uploadAttachment ()
    {
      $(".upload.postit-attachment").click ();
    },

    // METHOD setCurrent ()
    setCurrent ()
    {
      S.reset ("postit");
      this.element[0].classList.add ("current")
    },

    // METHOD unsetCurrent ()
    unsetCurrent ()
    {
      S.reset ("postit");
      this.element[0].classList.remove ("current");
    },

    // METHOD insert ()
    insert ()
    {
      const $postit = this.element,
            data = this.serialize()[0];

      H.request_ws (
        "PUT",
        "wall/"+this.settings.wallId+
        "/cell/"+this.settings.cellId+"/postit",
        data,
        // success cb
        (d) =>
        {
          if (d.error_msg)
            H.displayMsg ({
              type: "warning",
              msg: d.error_msg
            });

          $postit.remove ();
        },
        // error cb
        (d) =>
        {
          //FIXME factorisation (cf. H.request_ws ())
          H.displayMsg ({
            type: "danger",
            msg: (isNaN (d.error)) ?
              d.error : "<?=_("Unknown error.<br>Please try again later.")?>"
          });

          $postit.remove ();
        });
    },

    // METHOD update ()
    update (d, cell)
    {
      const $postit = this.element,
            postit0 = $postit[0],
            $tagPicker = S.getCurrent ("tag-picker"),
            tz = wpt_userData.settings.timezone;

      // Change postit cell
      if (cell && cell.id != this.settings.cellId)
      {
        this.settings.cell =
          cell.obj||this.settings.wall.find("[data-id='cell-"+cell.id+"']");
        this.settings.cellId = cell.id;

        $postit.appendTo (this.settings.cell);
      }

      if (!d.ignoreResize)
        $postit.css ({
          top: d.item_top,
          left: d.item_left,
          width: d.width,
          height: d.height
        });

      this.setClassColor (d.classcolor);

      this.setTitle (d.title);

      this.setContent (d.content);

      this.setAttachmentsCount (d.attachmentscount);

      this.setCreationDate (d.creationdate?H.getUserDate (d.creationdate):'');

      this.setDeadline (d);

      if (!d.obsolete)
        postit0.classList.remove ("obsolete");

      if (!d.tags)
        d.tags = "";
      postit0.dataset.tags = d.tags;

      postit0.querySelector(".postit-tags").innerHTML =
        $tagPicker.tagPicker ("getHTMLFromString", d.tags);

      $tagPicker.tagPicker ("refreshPostitDataTag", $postit);

      this.repositionPlugs ();
    },

    // METHOD delete ()
    delete ()
    {
      S.reset ();

      this.element[0].dataset.todelete = true;
    },

    // METHOD deleteAttachment ()
    deleteAttachment (id)
    {
      const $li = $_attachmentsPopup.find ("li[data-id='"+id+"']");

      H.request_ws (
        "DELETE",
        "wall/"+this.settings.wallId+
          "/cell/"+this.settings.cellId+"/postit/"+this.settings.id+
            "/attachment/"+id,
        null,
        // success cb
        (d) =>
        {
          if (d.error_msg)
            H.raiseError (null, d.error_msg);
          else
          {
            const $next = $li.next ();

            if ($next.length && $next.hasClass ("collapse"))
              $next.remove ();

            $li.remove ();

            this.decAttachmentsCount ();

            if (!$_attachmentsPopup[0].querySelector ("li"))
              $_attachmentsPopup.find("ul.list-group").html (
                "<?=_("This sticky note has no attachment")?>");
          }
        }
      );
    },

    // METHOD updateAttachment ()
    updateAttachment (args)
    {
      H.request_ajax (
        "POST",
        "wall/"+this.settings.wallId+
          "/cell/"+this.settings.cellId+"/postit/"+this.settings.id+
            "/attachment/"+args.id,
        {title: args.title, description: args.description},
        // success cb
        (d) =>
        {
          if (d.error_msg)
            H.raiseError (null, d.error_msg);
          else
            this.displayAttachments ();
        }
      );
    },

    // METHOD edit ()
    edit (args, success_cb, error_cb)
    {
      const data = {cellId: this.settings.cellId};

      if (!args)
        args = {};

      if (!args.plugend)
      {
        this.setCurrent ();

        _originalObject = this.serialize()[0];
      }

      if (!this.settings.wall[0].dataset.shared)
        return success_cb && success_cb ();

      H.request_ws (
        "PUT",
        "wall/"+this.settings.wallId+"/editQueue/postit/"+this.settings.id,
        data,
        // success cb
        (d) =>
        {
          if (args.ignoreResize)
            d.ignoreResize = true;

          if (d.error_msg)
          {
            H.raiseError (() =>
              {
                error_cb && error_cb ();
                this.cancelEdit (args);

              }, d.error_msg);
          }
          else if (success_cb)
            success_cb (d);
        },
        // error cb
        (d) => this.cancelEdit  (args)
      );
    },

    // METHOD unedit ()
    unedit (args = {})
    {
      const $postit = this.element,
            plugsToSave = S.get ("plugs-to-save");
      let data = null,
          todelete;

      if (!H.checkAccess ("<?=WPT_WRIGHTS_RW?>"))
        return this.cancelEdit (args);

      if (!args.plugend)
      {
        // Update postits plugs dependencies
        if (plugsToSave)
        {
          data = {updateplugs: true, plugs: []};

          for (const id in plugsToSave)
            data.plugs.push (plugsToSave[id].postit ("serialize")[0]);

          S.unset ("plugs-to-save");
        }
        // Postit update
        else
        {
          data = this.serialize()[0];
          todelete = !!data.todelete;

          // Update postit only if it has changed
          if (todelete || H.updatedObject (_originalObject,
                                             data, {hadpictures: 1}))
            data["cellId"] = this.settings.cellId;
          else if (!this.settings.wall[0].dataset.shared)
            return this.cancelEdit ();
          else
            data = null;
        }
      }

      H.request_ws (
        "DELETE",
        "wall/"+this.settings.wallId+"/editQueue/postit/"+this.settings.id,
        data,
        // success cb
        (d) =>
        {
          this.cancelEdit (args);

          if (d.error_msg)
            H.displayMsg ({
              type: "warning",
              msg: d.error_msg
            });
          else if (todelete)
          {
            $postit.remove ();
            $("#postitsSearchPopup").postitsSearch ("replay");
          }
          else if (data && data.updatetz)
            $postit[0].removeAttribute ("data-updatetz");
        },
        // error cb
        () => this.cancelEdit (args));
    },

    // METHOD cancelEdit ()
    cancelEdit (args = {})
    {
      $("body").css ("cursor", "auto");

      if (!args.plugend)
      {
        this.unsetCurrent ();

        this.element[0].removeAttribute ("data-hasuploadedpictures");
        this.element[0].removeAttribute ("data-hadpictures");

        if (!this.settings.wall)
          H.raiseError (null, "<?=_("The entire column was deleted while you were editing the sticky note!")?>");
      }
    },

    // METHOD closePlugMenu ()
    closePlugMenu ()
    {
      const menu = this.element[0].querySelector (".postit-menu");

      if (menu)
        $(menu.querySelector(".dropdown-menu")).dropdown ("hide");
    },

    // METHOD closeMenu ()
    closeMenu ()
    {
      if (this.element[0].querySelector (".postit-menu"))
        this.element.find(".btn-menu").click ();
    }
  };

  /////////////////////////// AT LOAD INIT //////////////////////////////

  if (!document.querySelector ("body.login-page"))
    $(function()
      {
        $_attachmentsPopup = $("#postitAttachmentsPopup");
        $_attachmentEditPopup = $_attachmentsPopup.find (".edit-popup");

        // EVENT focusin.
        // To fix tinymce bootstrap compatibility with popups
        $(document).on("focusin",
          function (e)
          {
            if ($(e.target).closest(
              ".tox-tinymce-aux,.moxman-window,.tam-assetmanager-root").length)
              e.stopImmediatePropagation();
          });

        // Init text editor
        let locale = $("html")[0].dataset.fulllocale;
        tinymce.init ({
          selector: "#postitUpdatePopupBody",
          content_style: "p {margin: 0}",
          language: (locale != "en_US")?locale:null,
          language_url: (locale != "en_US")?'/libs/tinymce-'+locale+'.js':null,
          branding: false,
          plugins: "autoresize link image media charmap hr searchreplace visualchars fullscreen insertdatetime lists",

          setup: function (editor)
          {
            // Hack to catch 404 not found error on just added images
            // -> Is there a TinyMCE callback for that?
            editor.on("change", function (e)
              {
                // Check content only if the TinyMCE dialog is open
                if ($(".tox-dialog").is(":visible"))
                {
                  let c = editor.getContent (),
                      tmp;

                  (c.match(/<img\s[^>]+>/g)||[]).forEach ((img) =>
                    {
                      if ( (tmp = img.match (/src="([^"]+)"/)) )
                      {
                        const src = tmp[1];

                        H.loader ("show");
                        H.testImage(src)
                          .then (
                          // Needed for some Safari on iOS that do not support
                          // Promise finally() callback.
                          ()=>
                          {
                            H.loader("hide");

                            editor.setContent (c);
                          },
                          ()=>
                          {
                            H.loader("hide");

                            c = c.replace(new RegExp (H.quoteRegex(img)), "");

                            editor.setContent (c);

                            // Return to the top of the modal if mobile device
                            if ($.support.touch)
                              $("#postitUpdatePopup").scrollTop (0);

                            H.displayMsg ({
                              type: "warning",
                              msg: "<?=_("The image %s was not available! It has been removed from the sticky note content.")?>".replace("%s", `«&nbsp;<i>${src}</i>&nbsp;»`)
                            });
                          });
                      }
                    });
                }
              });
          },

          // "media" plugin options.
          media_alt_source: false,
          media_poster: false,

          // "image" plugin options
          image_description: false,
          automatic_uploads: true,
          file_picker_types: "image",
          file_picker_callback: function (callback, value, meta)
          {
            S.set ("tinymce-callback", callback);
            $(".upload.postit-picture").click ();
          },

          // "link" plugin options
          default_link_target: "_blank",
          link_assume_external_targets: true,
          link_default_protocol: "https",
          link_title: false,
          target_list: false,

          visual: false,
          mobile: {menubar: "edit view format insert"},
          menubar: "edit view format insert",
          menu:{view:{title:"<?=_("View")?>", items:"fullscreen"}},
          toolbar: "undo redo | bold italic underline | numlist bullist | alignleft aligncenter alignright alignjustify | link image",
          statusbar: false
        });

        // EVENT click on plug label
        $(document).on("click", ".plug-label li", function ()
          {
            const $item = $(this),
                  $label = $item.closest("div"),
                  $popup = $("#plugPopup"),
                  $wall = S.getCurrent ("wall"),
                  [,startId, endId] =
                    $label[0].previousSibling.id.match (/^_(\d+)\-(\d+)$/),
                  startPlugin =
                    $wall.find(".postit[data-id='postit-"+startId+"']")
                      .postit("getClass"),
                  defaultLabel = H.htmlEscape ($label.find("span").text ());

            function __unedit ()
            {
              let toSave = {};

              toSave[startId] = startPlugin.element;
              toSave[endId] = $wall.find(".postit[data-id='postit-"+endId+"']");

              S.set ("plugs-to-save", toSave);
              startPlugin.unedit ();
            }

            switch ($item[0].dataset.action)
            {
              case "rename":

                startPlugin.edit (null, ()=>
                  {
                    H.openConfirmPopover ({
                      type: "update",
                      item: $label,
                      title: `<i class="fas fa-bezier-curve fa-fw"></i> <?=_("Relationship name")?>`,
                      content: `<input type="text" class="form-control form-control-sm" value="${defaultLabel}" maxlength="<?=DbCache::getFieldLength('postits_plugs', 'label')?>">`,
                      cb_close: __unedit,
                      cb_ok: ($popover) =>
                        {
                          const label = $popover.find("input").val().trim ();

                          if (label != defaultLabel)
                            startPlugin.updatePlugLabel ({
                              label: $popover.find("input").val().trim (),
                              endId: endId
                            });
                        }
                    });
                  });

                break;

              case "delete":

                startPlugin.edit (null, ()=>
                  {
                    H.openConfirmPopover ({
                      item: $label,
                      placement: "left",
                      title: `<i class="fas fa-trash fa-fw"></i> <?=_("Delete")?>`,
                      content: "<?=_("Delete this relationship?")?>",
                      cb_close: __unedit,
                      cb_ok: () =>
                        {
                          startPlugin.removePlug (startId+"-"+endId);
                          startPlugin.resetPlugsUndo ();
                        }
                    });
                  });

                break;
            }
          });

      // Attachment upload.
      $(`<input type="file" class="upload postit-attachment">`)
        .on("change", function (e)
        {
          const $upload = $(this),
                plugin = S.getCurrent("postit").postit ("getClass"),
                settings = plugin.settings;

          if (e.target.files && e.target.files.length)
          {
            H.getUploadedFiles (e.target.files, "all",
              (e, file) =>
              {
                $upload.val ("");

                if ($_attachmentsPopup.find(
                      ".list-group li[data-fname='"+
                        H.htmlEscape(file.name)+"']").length)
                  return H.displayMsg ({
                           type: "warning",
                           msg: "<?=_("The file is already linked to the sticky note.")?>"
                         });

                if (H.checkUploadFileSize ({size: e.total}) &&
                    e.target.result)
                {
                  H.request_ajax (
                    "PUT",
                    "wall/"+settings.wallId+
                      "/cell/"+settings.cellId+"/postit/"+
                        settings.id+"/attachment",
                    {
                      name: file.name,
                      size: file.size,
                      item_type: file.type,
                      content: e.target.result
                    },
                    // success cb
                    (d) =>
                    {
                      const $body = $_attachmentsPopup.find("ul.list-group");

                      $_attachmentsPopup.find(".modal-body").scrollTop (0);
                      $_attachmentsPopup.find("div.collapse.show")
                        .collapse ("hide");

                      if (d.error_msg)
                        return H.displayMsg ({
                                 type: "warning",
                                 msg: d.error_msg
                               });
    
                      if (!$body.find("li").length)
                        $body.html ("");
    
                      $body.prepend (plugin.getAttachmentTemplate (d));

                      plugin.incAttachmentsCount ();

                      H.waitForDOMUpdate (()=>$body.find("li:eq(0)").click ());
                    });
                }
              });
          }
        }).appendTo ("body");

        // Picture upload.
        $(`<input type="file" accept=".jpeg,.jpg,.gif,.png"
            class="upload postit-picture">`)
          .on("change", function ()
          {
            const $upload = $(this),
                  fname = this.files[0].name;

            function __error_cb (d)
            {
             if (d)
                H.displayMsg ({
                  type: "warning",
                  msg: d.error||d
                });
            }

            H.getUploadedFiles (this.files, "\.(jpe?g|gif|png)$",
              (e, file) =>
                {
                  $upload.val ("");

                  if (H.checkUploadFileSize ({
                        size: e.total,
                        cb_msg: __error_cb
                      }) && e.target.result)
                  {
                    const wallId = S.getCurrent("wall").wall ("getId"),
                          $postit = S.getCurrent ("postit"),
                          postitId = $postit.postit ("getId"),
                          cellId = $postit.postit ("getCellId");

                    H.request_ajax (
                      "PUT",
                      "wall/"+wallId+"/cell/"+cellId+"/postit/"+postitId+
                        "/picture",
                      {
                        name: file.name,
                        size: file.size,
                        item_type: file.type,
                        content: e.target.result
                      },
                      // success cb
                      (d) =>
                        {
                          const $f = $(".tox-dialog");

                          $postit[0].dataset.hasuploadedpictures = true;

                          //FIXME
                          // If uploaded img is too large TinyMCE plugin
                          // take too much time to gather informations
                          // about it. If user close popup before that,
                          // img is inserted without width/height
                          $f.find("input:eq(1)").val (d.width);
                          $f.find("input:eq(2)").val (d.height);

                          S.get("tinymce-callback")(d.link);

                          setTimeout(()=>
                          {
                            if (!$f.find("input:eq(0)").val ())
                              __error_cb ("<?=_("Sorry, there is a compatibility issue with your browser when it comes to uploading sticky notes images...")?>");
                          }, 0);
                        },
                        __error_cb
                    );
                  }
                },
                null,
                __error_cb);
          }).appendTo("body");

        // EVENT click on attachment line buttons.
        $(document).on("click", "#postitAttachmentsPopup .modal-body li button",
          function (e)
          {
            const action = this.dataset.action,
                  $item = $(this).closest ("li");

            e.stopImmediatePropagation ();

            if (action == "delete")
            {
              const id = $item[0].dataset.id;

              $item.addClass ("active");

              H.openConfirmPopover ({
                item: $(this),
                placement: "left",
                title: `<i class="fas fa-trash fa-fw"></i> <?=_("Delete")?>`,
                content: "<?=_("Delete this file?")?>",
                cb_close: ()=>
                  {
                    const el = document.querySelector (
                                 ".modal li.list-group-item.active");

                    if (el && el.getAttribute ("aria-expanded") != "true")
                      el.classList.remove ("active");
                  },
                cb_ok: () =>
                  S.getCurrent("postit").postit ("deleteAttachment", id)
              });
            }
            else
              H.download ($item[0].dataset);
          });

        // EVENT click on attachment thumbnail.
        $(document).on("click", "#postitAttachmentsPopup .edit-popup img",
          function (e)
          {
            $("body")
              .append(`<div id="img-viewer"><div class="close"><i class="fas fa-times-circle fa-2x"></i></div><img src="${this.getAttribute("src")}"></div>`)
              .find(".close")
              .on("click", function ()
              {
                $("#popup-layer").click ();
              });

            H.openPopupLayer (
              () => document.getElementById("img-viewer").remove());
          });

        // EVENT click on edit popup "Save" button.
        $(document).on("click",
                       "#postitAttachmentsPopup .edit-popup .btn-primary",
          function (e)
          {
            const popup = $_attachmentEditPopup[0];

            e.stopImmediatePropagation ();

            S.getCurrent("postit").postit ("updateAttachment", {
              id: popup.dataset.id,
              title: H.noHTML (popup.querySelector("input").value),
              description: H.noHTML (popup.querySelector("textarea").value)
            });
          });

        // EVENT hidden.bs.collapse attachment row.
        $(document).on("hidden.bs.collapse",
                       "#postitAttachmentsPopup .list-group-item.collapse",
          function (e)
          {
            const li = this.previousSibling;

            li.classList.remove ("no-bottom-radius");
            li.classList.remove ("active");
          });

        // EVENT show.bs.collapse attachment row.
        $(document).on("show.bs.collapse",
                       "#postitAttachmentsPopup .list-group-item.collapse",
          function (e)
          {
            const li = $(this).prev()[0],
                  popup = $_attachmentEditPopup[0],
                  liActive = $_attachmentsPopup[0].querySelector ("li.active"),
                  fileVal = li.dataset.fname,
                  titleVal = li.dataset.title,
                  descVal = li.dataset.description,
                  img = popup.querySelector (".img"),
                  isImg = fileVal.match (/\.(jpe?g|gif|png)$/);

            li.classList.add ("no-bottom-radius");

            liActive && liActive.classList.remove ("active");
            li.classList.add ("active");

            popup.dataset.id = li.dataset.id;

            popup.querySelector(".no-details").style.display = "none";
            popup.querySelector(".title").style.display = "block";
            popup.querySelector(".description").style.display = "block";
            img.querySelector("img").setAttribute ("src", "");
            img.style.display = "none";

            popup.querySelector(".file").innerText = fileVal;

            if (H.checkAccess ("<?=WPT_WRIGHTS_ADMIN?>"))
            {
              popup.querySelector(".btn-primary").style.display = "block";
              popup.querySelectorAll(".ro").forEach (
                (el) => el.style.display = "none");
              popup.querySelectorAll(".adm").forEach (
                (el) => el.style.display = "block");

              popup.querySelector(".title input").value = titleVal;
              popup.querySelector(".description textarea").value = descVal;

              H.setAutofocus ($(popup));
            }
            else
            {
              popup.querySelector(".btn-primary").style.display = "none";
              popup.querySelectorAll(".ro").forEach (
                (el) => el.style.display = "block");
              popup.querySelectorAll(".adm").forEach (
                (el) => el.style.display = "none");

              if (!isImg && !titleVal && !descVal)
                popup.querySelector(".no-details").style.display = "block";

              if (titleVal)
                popup.querySelector(".title .ro").innerText = titleVal;
              else
                popup.querySelector(".title").style.display = "none";

              if (descVal)
                popup.querySelector(".description .ro")
                  .innerHTML = H.nl2br (descVal);
              else
                popup.querySelector(".description").style.display = "none";
            }

            if (isImg)
            {
              img.querySelector("img").setAttribute ("src", li.dataset.url);
              img.style.display = "block";
            }

            $(this.appendChild (popup)).show ("fade");
          });

        // EVENT hide.bs.modal on postit popup
        $("#postitUpdatePopup").on("hide.bs.modal",
          function (e)
          {
            const data = S.get ("postit-data");

            // Return if we are closing the postit modal from the confirmation
            // popup
            if (data && data.closing) return;

            const $popup = $(this),
                  plugin = S.getCurrent("postit").postit ("getClass");

              const title = $("#postitUpdatePopupTitle").val (),
                    content = tinymce.activeEditor.getContent (),
                    cb_close = () =>
                      {
                        S.set ("postit-data", {closing: true});

                        //FIXME
                        $(".tox-toolbar__overflow").hide ();
                        $(".tox-menu").hide ();

                        $popup.find("input").val ("");
                        plugin.unedit ();

                        $popup.modal ("hide");
                        S.unset ("postit-data");

                        tinymce.activeEditor.resetContent ();

                        if ($.support.touch)
                          H.fixVKBScrollStop ();
                      };

              // If there is pending changes, ask confirmation to user
              if (data && (
                // Content change detection
                tinymce.activeEditor.isDirty () ||
                // Title change detection
                H.htmlEscape(data.title) != H.htmlEscape(title)))
              {
                e.preventDefault ();

                H.openConfirmPopup ({
                  type: "save-postits-changes",
                  icon: "save",
                  content: `<?=_("Save changes?")?>`,
                  cb_ok: () =>
                    {
                      plugin.setTitle (title);
                      plugin.setContent (content);

                      plugin.element[0]
                        .removeAttribute ("data-uploadedpictures");
                    },
                  cb_close: cb_close
                });

                S.set ("postit-data", data);
              }
              else
                cb_close ();
          });
      });

<?php echo $Plugin->getFooter ()?>
