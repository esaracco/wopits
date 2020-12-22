<?php
/**
  Javascript plugin - Note

  Scope: Wall
  Elements: .postit
  Description: Note management

  TODO: relations plugin
  TODO: attachment plugin
*/

  require_once (__DIR__.'/../prepend.php');

  use Wopits\DbCache;

  $Plugin = new Wopits\jQueryPlugin ('postit', '', 'wallElement');
  echo $Plugin->getHeader ();

?>

  const _defaultClassColor =
          "color-<?=array_keys(WPT_MODULES['cpick']['items'])[0]?>";
  let $_attachmentsPopup,
      $_attachmentEditPopup,
      _originalObject,
      _plugRabbit = {
        line: null,
        // EVENT mousedown on destination postit for relation creation
        mousedownEvent: function (e)
          {
            const from = S.get ("link-from"),
                  $start = from.obj,
                  $end = $(e.target).closest (".postit");

            e.stopImmediatePropagation ();
            e.preventDefault ();

            if (!$end.length)
              return _cancelPlugAction ();

            const end0 = $end[0],
                  endPlugin = $end.postit ("getClass"),
                  endId = endPlugin.settings.id;

              if (from.id != endId && !endPlugin.plugExists (from.id))
              {
                endPlugin.edit ({plugend: true}, ()=>
                  {
                    const start0 = $start[0];

                    $start.postit ("addPlug", {
                      label: {name: "..."},
                      startId: from.id,
                      endId: endId,
                      obj: endPlugin.getPlugTemplate ({
                        hide: true,
                        label: "...",
                        start: start0,
                        end: end0
                      })
                    }, !!S.get("zoom-level"));

                    _cancelPlugAction ();
                  });
              }
              else if (from.id != endId)
              {
                _cancelPlugAction ();
                H.displayMsg ({
                  type: "warning",
                  msg: "<?=_("This relation already exists!")?>"
                });
              }
              else
                _cancelPlugAction ();
          },
        // EVENT mousemouve to track mouse pointer during relation creation
        mousemoveEvent: (e) =>
          {
            const rabbit = document.getElementById ("plug-rabbit");

            rabbit.style.left = (e.clientX+5)+"px";
            rabbit.style.top = (e.clientY-10)+"px";

            _plugRabbit.line.position ();
          },
        escapeEvent: (e) =>
          {
            if (e.which == 27)
              _cancelPlugAction ();
          }
      };

  /////////////////////////// PRIVATE METHODS ///////////////////////////

  // METHOD _getMaxEditModalWidth ()
  function _getMaxEditModalWidth (content)
  {
    let maxW = 0,
        tmp;

    (content.match(/<img\s[^>]+>/g)||[]).forEach (img =>
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

  // METHOD _removePlug ()
  function _removePlug (plug, toDefrag)
  {
    toDefrag[plug.startId] = plug.obj.start;
    toDefrag[plug.endId] = plug.obj.end;

    // Remove template line
    document.body.appendChild (plug.obj.dom);
    plug.obj.remove ();
    plug.obj = null;

    // Remove related lines
    plug.related.forEach (r => r.remove ());
    plug.related = [];
    plug.customPos = false;

    // Remove label
    plug.labelObj.remove ();
    plug.labelObj = null;
  }

  // METHOD _cancelPlugAction ()
  function _cancelPlugAction ()
  {
    const $postit = S.get("link-from").obj;

    if (_plugRabbit.line)
    {
      $(document)
        .off("mousedown.rabbit")
        .off ("keydown.rabbit");

      $("body").off ("mousemove.rabbit");

      _plugRabbit.line.remove ();
      _plugRabbit.line = null;

      document.getElementById("plug-rabbit").remove ();
    }

    $postit.postit ("unedit");

    S.set ("link-from", true, 500);
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
      this.$menu.find("[data-toggle='tooltip']").tooltip ("hide");
      this.$menu.remove ();
    }

    // METHOD setPosition ()
    setPosition (pos)
    {
      const m = this.$menu[0];

      if (pos == "left")
        m.classList.replace ("right", "left");
      else
        m.classList.replace ("left", "right");
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
            postit = $postit[0],
            postitSettings = postitPlugin.settings,
            $wall = postitSettings.wall,
            writeAccess = postitPlugin.canWrite ();

      // Menu events
      $menu.find(">span")
        .off().on("click", function(e)
        {
          const $btn = $(this),
                action = $btn[0].dataset.action;

          e.stopImmediatePropagation ();

          postitPlugin.closePlugMenu ();

          // To prevent race condition with draggable & resizable plugins
          if (H.disabledEvent ())
            return false;

          switch (action)
          {
            // OPEN post-it edit popup
            case "edit": return postitPlugin.openPostit ();
            // OPEN deadline date picker popup
            case "dpick": return postitPlugin.openDatePicker ();
            // OPEN deadline date picker popup
            case "attachments": return postitPlugin.openAttachments ();
            case "add-plug":

              postitPlugin.edit ({}, () =>
                {
                  postitPlugin.closeMenu ();

                  S.set ("link-from", {id: postitSettings.id, obj: $postit});

                  _plugRabbit.line = new LeaderLine (
                    postit,
                    $(`<div id="plug-rabbit" style="left:${e.clientX+5}px;top:${e.clientY-10}px"> <i class="fas fa-anchor fa-lg set"></i></div>`).prependTo("body")[0],
                    {
                      size: 3,
                      color: "#9b9c9c",
                      dash: true,
                      endPlug: "behind"
                    });

                  $(document)
                    .on("mousedown.rabbit", _plugRabbit.mousedownEvent)
                    .on ("keydown.rabbit", _plugRabbit.escapeEvent);

                  $("body").on ("mousemove.rabbit", _plugRabbit.mousemoveEvent);
                });

              break;

          }

          postitPlugin.edit ({}, ()=>
            {
              switch (action)
              {
                case "delete":
                  return H.openConfirmPopover ({
                    item: $postit.find(".btn-menu"),
                    placement: "right",
                    title: `<i class="fas fa-trash fa-fw"></i> <?=_("Delete")?>`,
                    content: "<?=_("Delete this note?")?>",
                    cb_close: () => postitPlugin.unedit (),
                    cb_ok: () => postitPlugin.delete ()
                  });

                // OPEN tags picker
                case "tpick":
                  return S.getCurrent("tpick").tpick ("open", e);

                // OPEN color picker
                case "cpick":
                  var cp = $("#cpick").cpick ("getClass");

                  return cp.open ({
                    event: e,
                    cb_close: ()=> postitPlugin.element.trigger ("mouseleave"),
                    cb_click: (div) =>
                      postitPlugin.element
                        .removeClass(cp.getColorsList().join(" "))
                        .addClass($(div).attr ("class"))
                  });
              }
          });
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
            postit = $postit[0],
            settings = plugin.settings,
            $wall = settings.wall,
            writeAccess = plugin.canWrite ();

      settings.plugs = [];
      postit.dataset.id = "postit-"+settings.id;
      postit.dataset.order = settings.item_order;
      postit.className = settings.classes||"postit";
      postit.dataset.tags = settings.tags||"";

      if (settings.obsolete)
        postit.classList.add ("obsolete");

      postit.style.visibility = "hidden";
      postit.style.top = settings.item_top+"px";
      postit.style.left = settings.item_left+"px";

      $postit
        // Append menu, header, dates, attachment count and tags
        .append ((writeAccess?`<div class="btn-menu"><i class="far fa-caret-square-up"></i></div>`:'')+`<div class="postit-header"><span class="title">...</span></div><div class="postit-progress-container"><div><span></span></div><div class="postit-progress"></div></div><div class="postit-edit"></div><div class="dates"><div class="creation" title="<?=_("Creation date")?>"><span>${moment.tz(wpt_userData.settings.timezone).format('Y-MM-DD')}</span></div><div class="end" title="<?=_("Deadline")?>"><i class="fas fa-times-circle fa-lg"></i> <span>...</span></div></div><div class="attachmentscount"${settings.attachmentscount?'':' style="display:none"'}><i data-action="attachments" class="fas fa-paperclip"></i><span class="wpt-badge">${settings.attachmentscount}</span></div><div class="postit-tags">${settings.tags?S.getCurrent("tpick").tpick("getHTMLFromString", settings.tags):""}</div>`);

      if (writeAccess)
      {
        $postit  
        // DRAGGABLE postit
        .draggable ({
          distance: 10,
          appendTo: "parent",
          revert: "invalid",
          cursor: "pointer",
          cancel: ".postit-tags",
          containment: $wall.find ("tbody"),
          scrollSensitivity: 50,
          opacity: 0.35,
          scope: "dzone",
          stack: ".postit",
          drag: function(e, ui)
          {
            if (S.get("revertData").revert)
            {
              $(this).draggable ("cancel");
              return false;
            }

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
  
              plugin.edit ({ignoreResize: true}, null,
                ()=> S.get("revertData").revert = true);
            },
          stop: function(e, ui)
            {
              const $editable = $postit.find (".editable");

              // Cancel editable.
              if ($editable.length)
                $editable.editable ("cancel");

              S.set ("dragging", true, 500);

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
                plugin.settings.cell = $postit.parent ();
                plugin.settings.cellId = plugin.settings.cell.cell ("getId");

                S.getCurrent("mmenu").mmenu ("update", settings.id, plugin);

                plugin.unedit ();
              }

              // Refresh relations position
              plugin.repositionPlugs ();
// TODO - 1 - Hide plugs instead of moving them with postits (performance
//            issue with some touch devices)
//              H.waitForDOMUpdate (() => plugin.showPlugs ());
            }
        })
        // RESIZABLE post-it
        .resizable ({
          handles: (H.haveMouse()) ? "all":"n, e, w, ne, se, sw, nw",
          autoHide: false,
          resize: function(e, ui)
          {
            // Refresh relations position
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
                width: postit.clientWidth,
                height: postit.clientHeight
              });

              plugin.edit ({ignoreResize: true}, null,
                ()=> S.get("revertData").revert = true);
            },
          stop: function(e, ui)
            {
              const revertData = S.get ("revertData");

              S.set ("dragging", true, 500);

              if (revertData.revert)
              {
                $postit.css ({
                  width: revertData.width,
                  height: revertData.height
                });

                plugin.cancelEdit ();

                // Refresh relations position
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
          .doubletap ((e)=>
          {
            if (e.target.tagName == "A" || H.disabledEvent (e.ctrlKey))
              return false;

            plugin.openPostit ();
          });
  
        // Make postit title editable
        $postit.find(".title").editable ({
          wall: $wall,
          container: $postit.find (".postit-header"),
          maxLength: <?=DbCache::getFieldLength('postits', 'title')?>,
          triggerTags: ["span", "div"],
          fontSize: "14px",
          callbacks: {
            before: (ed, v) => v == "..." && ed.setValue (""),
            edit: (cb) =>
            {
              if (H.disabledEvent ())
                return false;

              plugin.edit ({}, cb);
            },
            unedit: () => plugin.unedit (),
            update: (v) =>
              {
                plugin.setTitle (v);
                plugin.unedit ();
              }
          }
        });
      }

      if (settings.creationdate)
        plugin.update (settings);
    },

    // METHOD openAttachments ()
    openAttachments ()
    {
      this.edit ({}, ()=> this.displayAttachments ());
    },

    // METHOD openPlugProperties ()
    openPlugProperties (plug)
    {
      this.edit ({}, ()=> H.loadPopup ("plugprop", {
                            open: false,
                            cb: ($p)=> $p.plugprop ("open", this, plug)
                          }));
    },

    // METHOD openDatePicker ()
    openDatePicker ()
    {
      this.edit ({}, ()=> H.loadPopup ("dpick", {
                            open: false,
                            cb: ($p)=> $p.dpick ("open")
                          }));
    },

    // METHOD openPostit ()
    openPostit (item)
    {
      // Open modal with read rights only
      if (!this.canWrite ())
      {
        if (!this.openAskForExternalRefPopup ({item: item}))
          this.open ();
       }
       else
         this.edit ({}, () =>
           {
             if (!this.openAskForExternalRefPopup ({
                    item: item,
                    cb_close: (btn) => (btn != "yes") && this.unedit ()}))
               this.open ();
           });
    },

    // METHOD open ()
    open ()
    {
      const plugin = this,
            postit = plugin.element[0],
            progress = parseInt (postit.dataset.progress||0),
            title = this.element.find(".postit-header .title").text (),
            content = postit.querySelector(".postit-edit").innerHTML||"";

      if (plugin.canWrite ())
      {
        const $popup = $("#postitUpdatePopup");

        S.set ("postit-data", {
          title: (title != "...")?title.replace(/&amp;/g, "&"):"",
          progress: progress
        });

        $popup.find(".slider").slider ("value", progress, true);

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

        if (!H.haveMouse ())
          H.fixVKBScrollStart ();

        H.openModal ($("#postitUpdatePopup"), _getMaxEditModalWidth (content));
      }
      else
      {
        plugin.setCurrent ();

        H.loadPopup ("postitView", {
          open: false,
          cb: ($p)=>
          {
            $p.find(".modal-body").html ((content) ?
              content : "<i><?=_("No content.")?></i>");

            $p.find(".modal-title").html (
              `<i class="fas fa-sticky-note"></i> ${title}`);

            H.openModal ($p, _getMaxEditModalWidth (content));
          }
        });
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
          content = "<?=_("The deadline for this note has been removed!")?>";
        else if (this.element.hasClass ("obsolete"))
          content = "<?=_("This note has expired.")?>";
        else
        {
          const a = moment.unix (data.deadlineepoch),
                b = moment (new Date ());
          let days = moment.duration(a.diff(b)).asDays ();

          if (days % 1 > 0)
            days = Math.trunc(days) + 1;

          content = (days > 1) ?
            "<?=_("This note will expire in about %s day(s).")?>".replace("%s", days) :
            "<?=_("This note will expire soon.")?>";
        }

        H.openConfirmPopover ({
          type: "info",
          item: this.element,
          title: `<i class="fa fa-exclamation-triangle fa-fw"></i> <?=_("Expiration")?>`,
          content: content
        });
      });
    },

    // METHOD remove ()
    remove ()
    {
      const __remove = ()=>
              {
                this.removePlugs (true);
                this.element.remove ();
              };

      if (H.haveMouse ())
      {
        // Empty postit content to prevent effect to reload deleted embedded
        // images
        this.element[0].querySelector(".postit-edit").innerHTML = "";
        this.element.hide ("explode", ()=> __remove ());
       }
       // The explode effect works poorly on mobile devices
       else
         __remove ();

      S.getCurrent("mmenu").mmenu ("remove", this.settings.id);
    },

    // METHOD havePlugs ()
    havePlugs ()
    {
      return this.settings.plugs.length;
    },

    // METHOD applyPlugLineType ()
    applyPlugLineType (ll)
    {
      switch (ll.line_type)
      {
        case "solid":
          ll.dash = false;
          break;
        case "dashed":
          ll.dash = true;
          break;
        case "a-dashed":
          ll.dash = {animation: true};
          break;
      }
    },

    // METHOD getPlugDropShadowTemplate ()
    getPlugDropShadowTemplate (color)
    {
      return {
        dy: 10,
/*
        dx: 0.2,
        blur: 1,
*/
        color: H.lightenDarkenColor (color, -20)
      };
    },

    // METHOD getPlugTemplate ()
    getPlugTemplate (args, ignoreZoom)
    {
      const color = args.line_color||S.getCurrent ("plugColor"),
            size = args.line_size||<?=WS_PLUG_DEFAULTS['lineSize']?>,
            ll = new LeaderLine (
             args.start,
             args.end,
             {
               hide: !!args.hide,
               dropShadow: this.getPlugDropShadowTemplate (color),
               size: ignoreZoom ? size : size * (S.get("zoom-level")||1),
               path: args.line_path||"<?=WS_PLUG_DEFAULTS['linePath']?>",
               color: color,
//               startPlug: args.startPlug||"arrow1",
               endPlug: args.endPlug||"arrow1",
               middleLabel: LeaderLine.captionLabel ({
                 text: args.label,
                 fontSize:"13px"
               })
             });

      ll.dom = document.querySelector (".leader-line:last-child");
      ll.line_size = size;
      ll.line_type = args.line_type||"<?=WS_PLUG_DEFAULTS['lineType']?>";
      ll.customCol = args.line_color;

      this.applyPlugLineType (ll);

      return ll;
    },

    // METHOD applyZoomToPlugs ()
    applyZoomToPlugs (z)
    {
      const reset = (z == 1);

      this.settings.plugs.forEach (p =>
        {
          const size = Math.trunc(p.obj.line_size * z)||1,
                gr = Math.trunc ((100*(size*100/p.obj.line_size))/100);

          p.labelObj[0].style.transformOrigin = (reset) ? null : "top left";
          p.labelObj[0].style.transform = (reset) ? null : "scale("+z+")";

          p.obj.size = size;

          if (p.customPos)
            p.related.forEach (_p => _p.setOptions ({
              startSocketGravity: (reset) ? "auto" : gr,
              endSocketGravity: (reset) ? "auto" : gr,
              size: size
            }));
        });
    },

    // METHOD applyZoom ()
    applyZoom ()
    {
      const z = S.get("zoom-level")||1;

      document.querySelectorAll(".postit.with-plugs").forEach (p =>
        $(p).postit ("applyZoomToPlugs", z));
    },

    // METHOD applyThemeToPlugs ()
    applyThemeToPlugs (color)
    {
      const __apply = (p) => p.setOptions ({
              dropShadow: this.getPlugDropShadowTemplate (color),
              color: color
            });

      this.settings.plugs.forEach (p =>
        {
          if (!p.obj.customCol)
          {
            __apply (p.obj);

            if (p.customPos)
              p.related.forEach (_p => __apply (_p));
          }
        });
    },

    // METHOD applyTheme ()
    applyTheme ()
    {
      S.reset ("plugColor");
      const color = S.getCurrent ("plugColor");

      document.querySelectorAll(".postit.with-plugs").forEach (p =>
        $(p).postit ("applyThemeToPlugs", color));
    },

    // METHOD repositionPlugLabel ()
    repositionPlugLabel (label, top, left, wPos)
    {
      const z = S.get("zoom-level")||1;

      label.style.top = ((parseInt(top)*z)+wPos.top)+"px";
      label.style.left = ((parseInt(left)*z)+wPos.left)+"px";
    },

    // METHOD resetPlugLabelPosition ()
    resetPlugLabelPosition (label)
    {
      label.removeAttribute ("data-pos");
      label.removeAttribute ("data-origtop");
      label.removeAttribute ("data-origleft");

      if (this.canWrite ())
      {
        label.querySelector("i.fa-thumbtack").style.display = "none";
        label.querySelector("li[data-action='position-auto']")
         .style.display = "none";
      }
    },

    // METHOD updatePlugProperties ()
    updatePlugProperties (ll)
    {
      const id = ll.endId || this.settings.id,
            defaultLineColor = S.getCurrent ("plugColor");

      for (const plug of this.settings.plugs)
      {
        //FIXME
        if ((ll.endId && plug.endId == ll.endId) ||
            (!ll.endId && plug.startId == this.settings.id))
        {
          const customCol = (ll.color && ll.color != defaultLineColor),
                lineColor = customCol ? ll.color : defaultLineColor,
                lineType =
                  (ll.line_type &&
                   ll.line_type != "<?=WS_PLUG_DEFAULTS['lineType']?>") ?
                     ll.line_type : "<?=WS_PLUG_DEFAULTS['lineType']?>",
                lineSize =
                  (ll.size &&
                   ll.size != <?=WS_PLUG_DEFAULTS['lineSize']?>) ?
                     ll.size : <?=WS_PLUG_DEFAULTS['lineSize']?>,
                props = {
                  size: lineSize * (S.get("zoom-level")||1),
                  path:
                    (ll.path &&
                     ll.path != "<?=WS_PLUG_DEFAULTS['linePath']?>") ?
                       ll.path : "<?=WS_PLUG_DEFAULTS['linePath']?>",
                  color: lineColor,
                  dropShadow: this.getPlugDropShadowTemplate (lineColor)
                };

          plug.obj.setOptions (props);
          plug.obj.line_type = lineType;
          plug.obj.line_size = lineSize;
          plug.obj.customCol = customCol;

          this.applyPlugLineType (plug.obj);

          if (plug.customPos)
            plug.related.forEach (p =>
              {
                p.setOptions (props);
                p.line_type = lineType;
                this.applyPlugLineType (p);
              });

          break;
        }
      }
    },

    // METHOD updatePlugLabel ()
    updatePlugLabel (args)
    {
      const label = H.noHTML (args.label),
            wPos = this.settings.wall[0].getBoundingClientRect (),
            canWrite = this.canWrite (),
            p = this.getPlugById (args.endId),
            pl = p.labelObj[0];

      p.label.name = label;
      p.obj.middleLabel = LeaderLine.captionLabel ({
                            text: label,
                            fontSize: "13px"
                          });

      pl.querySelector("a span").innerHTML = (label == ""  || label == "...") ?
        `<i class="fas fa-ellipsis-h"></i>` : label;

      if (args.top !== undefined)
      {
        if (args.top)
        {
          pl.dataset.pos = 1;
          pl.dataset.origtop = args.top;
          pl.dataset.origleft = args.left;

          if (canWrite)
          {
            pl.querySelector("i.fa-thumbtack").style.display = "block";
            pl.querySelector("li[data-action='position-auto']")
              .style.display = "block";
          }

          if (!p.customPos)
          {
            this.repositionPlugLabel (pl, args.top, args.left, wPos);
            p.related = this.createRelatedPlugs (p);
            p.obj.hide ();
          }
        }
        else if (p.customPos)
        {
          this.resetPlugLabelPosition (pl);
          p.related.forEach (r => r.remove ());
          p.related = [];
          p.customPos = false;
          p.obj.show ();
        }
      }

      this.repositionPlugs ();
    },

    // METHOD createRelatedPlugs ()
    createRelatedPlugs (plug)
    {
      const ll = plug.obj,
            //FIXME Factorization
            related = [
              this.getPlugTemplate ({
                hide: true,
                line_size: ll.line_size,
                line_path: ll.path,
                line_color: ll.color,
                line_type: ll.line_type,
                start: ll.start,
                end: plug.labelObj[0],
                endPlug: "behind"
              }),
              this.getPlugTemplate ({
                hide: true,
                line_size: ll.line_size,
                line_path: ll.path,
                line_color: ll.color,
                line_type: ll.line_type,
                start: plug.labelObj[0],
                end: ll.end
              })
            ];

      plug.customPos = true;
      related.forEach (p => p.show ());

      return related;
    },

    // METHOD addPlugLabel ()
    addPlugLabel (plug, svg, applyZoom)
    {
      const plugin = this,
            $div = plugin.settings.plugsContainer,
            wPos = this.settings.wall[0].getBoundingClientRect (),
            canWrite = this.canWrite ();

      if (svg)
        $div[0].appendChild (svg);
      else
        svg = $div[0].querySelector ("#_"+plug.startId+"-"+plug.endId);

      const text = svg.querySelector ("text"),
            pos = plug.label.top ?
              {top: plug.label.top+wPos.top, left: plug.label.left+wPos.left} :
              text.getBoundingClientRect (),
            $start = $(plug.obj.start),
            menu = `<ul class="dropdown-menu"><li data-action="rename"><a class="dropdown-item" href="#"><i class="fa-fw fas fa-edit"></i> <?=_("Rename")?></a></li><li data-action="delete"><a class="dropdown-item" href="#"><i class="fa-fw fas fa-trash"></i> <?=_("Delete")?></a></li><li data-action="properties"><a class="dropdown-item" href="#"><i class="fa-fw fas fa-cogs"></i> <?=_("Properties")?></a></li><li data-action="position-auto"><a class="dropdown-item" href="#"><i class="fa-fw fas fa-magic"></i> <?=_("Auto position")?></a></li></ul>`,
            $label = $(`<div ${plug.label.top?"data-pos=1":""} class="plug-label dropdown submenu" style="top:${pos.top}px;left:${pos.left}px">${canWrite?`<i class="fas fa-thumbtack fa-xs"></i>`:""}<a href="#" ${canWrite?'data-toggle="dropdown"':""} class="dropdown-toggle"><span>${plug.label.name != "..." ? H.noHTML (plug.label.name) : '<i class="fas fa-ellipsis-h"></i>'}</span></a>${canWrite?menu:""}</div>`);

        plug.labelObj = $label.appendTo ($div);

        const pl = plug.labelObj[0];

        if (plug.label.top)
        {
          pl.dataset.origtop = plug.label.top;
          pl.dataset.origleft = plug.label.left;

          if (canWrite)
            pl.querySelector("i.fa-thumbtack").style.display = "block";

          plug.related = plugin.createRelatedPlugs (plug);
        }
        else
        {
          if (canWrite)
            pl.querySelector("li[data-action='position-auto']")
              .style.display = "none";

          plug.related = [];
          plug.customPos = false;
          plug.obj.show ("none");
        }

        if (applyZoom)
        {
          pl.style.transformOrigin = "top left";
          pl.style.transform = "scale("+(S.get("zoom-level")||1)+")";
        }

        if (canWrite)
          $label.draggable ({
            distance: 10,
//            containment: S.getCurrent("wall").find("tbody"),
            start: function (e, ui)
            {
              const p = plug.labelObj.position ();

              S.set ("revertData", {
                revert: false,
                top: p.top,
                left: p.left
              });

              $start.postit ("edit", {},
                // success cb
                ()=>
                {
                  if (!plug.customPos)
                  {
                    plug.related = plugin.createRelatedPlugs (plug);
                    plug.obj.hide ();
                  }
                },
                // error cb
                ()=> S.get("revertData").revert = true);
            },
            drag: function ()
            {
              if (S.get("revertData").revert)
              {
                $(this).draggable ("cancel");
                return false;
              }

              plug.related.forEach (p => p.position ());
            },
            stop: function (e, ui)
            {
              S.set ("dragging", true, 500);

              if (S.get("revertData").revert)
              {
                const revertData = S.get ("revertData");

                plug.labelObj.css ({
                  top: revertData.top,
                  left: revertData.left
                });

                $start.postit ("cancelEdit");
              }
              else
              {
                const wPos = S.getCurrent("wall")[0].getBoundingClientRect (),
                      lbPos = $label[0].getBoundingClientRect (),
                      z = S.get("zoom-level")||1,
                      toSave = {};

                $label[0].dataset.pos = 1;
                pl.querySelector("i.fa-thumbtack").style.display = "block";
                pl.querySelector("li[data-action='position-auto']")
                  .style.display = "none";

                pl.dataset.origtop = Math.trunc ((lbPos.top-wPos.top)/z);
                pl.dataset.origleft = Math.trunc ((lbPos.left-wPos.left)/z);

                toSave[plug.startId] = $(plug.obj.start);
                toSave[plug.endId] = $(plug.obj.end);

                S.set ("plugs-to-save", toSave);
                $start.postit ("unedit");
              }
            }
          });
    },

    // METHOD addPlug ()
    addPlug (plug, applyZoom)
    {
      const $start = this.element,
            $end = $(plug.obj.end);

      // Associate SVG line to plug and set its label
      const svg = document.querySelector (".leader-line:last-child");
      svg.id = "_"+plug.startId+"-"+plug.endId;
      this.addPlugLabel (plug, svg, applyZoom);

      // Register plug on start point postit (current plugin)
      this.settings.plugs.push (plug);
      $start[0].classList.add ("with-plugs");

      // Register plug on end point postit
      $end.postit("getSettings").plugs.push (plug);
    },

    // METHOD defragPlugsArray ()
    defragPlugsArray ()
    {
      const settings = this.settings;
      let i = settings.plugs.length;

      while (i--)
        if (!settings.plugs[i].obj)
          settings.plugs.splice (i, 1);

      if (!this.havePlugs ())
        this.element[0].classList.remove ("with-plugs");
    },

    // METHOD plugExists ()
    plugExists (plugId)
    {
      for (const plug of this.settings.plugs)
        if (plug.startId == plugId || plug.endId == plugId)
          return true;
    },

    // METHOD getPlugById ()
    getPlugById (plugId)
    {
      for (const plug of this.settings.plugs)
        if (plug.endId == plugId)
          return plug;
    },

    // METHOD removePlug ()
    removePlug (plug, noedit)
    {
      const toDefrag = {};

      if (typeof plug !== "object")
        plug = this.getPlugById (plug);

      _removePlug (plug, toDefrag);

      for (const id in toDefrag)
        $(toDefrag[id]).postit ("defragPlugsArray");

      if (!noedit)
        S.set ("plugs-to-save", toDefrag);
    },

    // METHOD removePlugs ()
    removePlugs (noedit)
    {
      const settings = this.settings,
            toDefrag = {};

      settings.plugs.forEach (p => _removePlug (p, toDefrag));

      for (const id in toDefrag)
        $(toDefrag[id]).postit ("defragPlugsArray");

      if (!noedit)
        S.set ("plugs-to-save", toDefrag);

      settings.plugs = [];
      this.element[0].classList.remove ("with-plugs");
    },

    // METHOD hidePlugs ()
    hidePlugs (ignoreDisplayMode = false)
    {
      if (!this.settings.wall) return;

      const postitId = this.settings.id;

      this.element.find(".postit-menu [data-action='plug']").hide ();

      this.settings.plugs.forEach (p =>
        {
          if (!ignoreDisplayMode)
          {
            if (p.startId == postitId)
              p.startHidden = true;
            else
              p.endHidden = true;
          }

          p.labelObj.hide ();
          if (!p.customPos)
            p.obj.hide ("none");
          else
            p.related.forEach (_p => _p.hide ("none"));
        });
    },

    // METHOD showPlugs ()
    showPlugs (ignoreDisplayMode = false)
    {
      if (!this.settings.wall) return;

      const postitId = this.settings.id,
            wPos = this.settings.wall[0].getBoundingClientRect ();

      this.element.find(".postit-menu [data-action='plug']").show ();

      this.settings.plugs.forEach (p =>
        {
          if (!ignoreDisplayMode)
          {
            if (p.startId == postitId)
              delete p.startHidden;
            else
              delete p.endHidden;
          }

          if (!p.startHidden && !p.endHidden)
          {
            p.labelObj.show ();
            if (!p.customPos)
              p.obj.show ("none");
            else
            {
              const pl = p.labelObj[0];

              this.repositionPlugLabel (
                pl, pl.dataset.origtop, pl.dataset.origleft, wPos);

              p.related.forEach (_p => _p.show().position());
            }
          }
        });
    },

    // METHOD repositionPlugs ()
    repositionPlugs ()
    {
      const div = this.settings.plugsContainer[0],
            wPos = this.settings.wall[0].getBoundingClientRect ();

      this.settings.plugs.forEach (p =>
        {
          const pl = p.labelObj[0];

          if (pl.dataset.pos)
          {
            this.repositionPlugLabel (
              pl, pl.dataset.origtop, pl.dataset.origleft,  wPos);

            p.related.forEach (_p => _p.position ());
          }
          else
          {
            p.obj.position ();

            const pos = div.querySelector (`#_${p.startId}-${p.endId} text`)
                          .getBoundingClientRect ();

            pl.style.top = pos.top+"px";
            pl.style.left = pos.left+"px";
          }
        });
    },

    // METHOD getCellId ()
    getCellId ()
    {
      return this.settings.cellId;
    },

    // METHOD serializePlugs ()
    serializePlugs ()
    {
      const settings = this.settings,
            defaultLineColor = S.getCurrent ("plugColor");
      let ret = {};

      settings.plugs.forEach (p =>
        {
          // Take in account only plugs from this postit
          if (p.startId == settings.id)
          {
            const pl = p.labelObj[0];

            ret[p.endId] = {
              label: (p.label == "...") ?
                       "" : pl.querySelector("a span").innerText,
              line_type:
                (p.obj.line_type != "<?=WS_PLUG_DEFAULTS['lineType']?>") ?
                   p.obj.line_type : undefined,
              line_size:
                (p.obj.line_size != <?=WS_PLUG_DEFAULTS['lineSize']?>) ?
                   parseInt (p.obj.line_size) : undefined,
              line_path:
                (p.obj.path != "<?=WS_PLUG_DEFAULTS['linePath']?>") ?
                   p.obj.path : undefined,
              line_color:
                (p.obj.color != defaultLineColor) ?
                   p.obj.color : undefined
            };

            if (pl.dataset.pos)
            {
              ret[p.endId].top = parseInt (pl.dataset.origtop);
              ret[p.endId].left = parseInt (pl.dataset.origleft);
            }
          }
        });

      return ret;
    },

    // METHOD serialize ()
    serialize ()
    {
      const postits = [],
            displayExternalRef = this.settings.wall.wall ("displayExternalRef"),
            z = S.get("zoom-level")||1;

      this.element.each (function ()
      {
        const plugin = $(this).postit ("getClass");
        let data = {};

        if (this.dataset.todelete)
          data = {id: plugin.settings.id, todelete: true};
        else
        {
          const title = this.querySelector(".postit-header span.title")
                          .innerText,
                content = this.querySelector(".postit-edit").innerHTML,
                classcolor = this.className.match (/(color\-[a-z]+)/),
                deadline = (this.dataset.deadlineepoch) ?
                  this.dataset.deadlineepoch :
                  this.querySelector(".dates .end span").innerText.trim (),
                bbox = this.getBoundingClientRect ();
          let tags = [];

          this.querySelectorAll(".postit-tags i").forEach (item =>
            tags.push (item.dataset.tag));

          data = {
            id: plugin.settings.id,
            width: Math.trunc (bbox.width/z),
            height: Math.trunc (bbox.height/z),
            item_top: (this.offsetTop < 0) ? 0 : Math.trunc (this.offsetTop),
            item_left: (this.offsetLeft < 0) ? 0 : Math.trunc (this.offsetLeft),
            item_order: parseInt (this.dataset.order),
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
            hasuploadedpictures: !!this.dataset.hasuploadedpictures,
            progress: parseInt (this.dataset.progress||0)
          };
        }

        postits.push (data);
      });

      return postits;
    },

    // METHOD showUserWriting ()
    showUserWriting (user, isRelated)
    {
      const id = this.settings.id,
            $cell = this.settings.cell,
            canWrite = this.canWrite ();
      const __lock = el =>
              el.classList.add("locked", isRelated?"related":"main"),
            __addMain = ()=>
              this.element.prepend (`<div class="user-writing main" data-userid="${user.id}"><i class="fas fa-user-edit blink"></i> ${user.name}</div>`);

      this.closeMenu ();

      // See cell::setPostitsUserWritingListMode()
      if ($cell[0].classList.contains ("list-mode"))
      {
        const min = $cell[0].querySelector (
                ".postit-min[data-id='postit-"+this.settings.id+"']");

        if (canWrite)
          __lock (min);

        $(min).prepend (`<span class="user-writing-min${!isRelated?" main":""}" data-userid="${user.id}"><i class="fas fa-sm fa-${isRelated?"user-lock":"user-edit blink"}"></i></span>`);
      }

      if (canWrite)
      {
        __lock (this.element[0]);

        if (isRelated)
          this.element.prepend (`<div class="user-writing" data-userid="${user.id}"><i class="fas fa-user-lock"></i></div>`);
        else
          __addMain ();

        // Show a lock bubble on related items
        if (!isRelated)
          this.settings.plugs.forEach (p =>
            $(p.obj[(p.startId!=id)?"start":"end"])
              .postit ("showUserWriting", user, true));
      }
      else if (!isRelated)
        __addMain ();
    },

    // METHOD setDeadline ()
    setDeadline (args)
    {
      const postit = this.element[0],
            date = postit.querySelector (".dates .end"),
            {deadline, alertshift, timezone} = args,
            reset = date.querySelector("i.fa-times-circle");
      let human;

      if (!deadline || isNaN (deadline))
        human = deadline||"...";
      else
        human = (deadline) ? H.getUserDate(deadline, timezone) : "...";

      date.querySelector("span").innerText = human;

      reset.style.display = "none";

      if (human == "...")
      {
        postit.classList.remove ("obsolete");

        postit.removeAttribute ("data-deadline");
        postit.removeAttribute ("data-deadlinealertshift");
        postit.removeAttribute ("data-deadlineepoch");
        postit.removeAttribute ("data-updatetz");

        date.classList.remove ("with-alert");
        date.classList.remove ("obsolete");
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

        if (this.canWrite ())
          reset.style.display = "inline-block";
      }
    },

    // METHOD resetDeadline ()
    resetDeadline ()
    {
      this.setDeadline ({deadline: "..."});
    },

    // METHOD setCreationDate ()
    setCreationDate (v)
    {
      this.element.find(".dates .creation span").text (v.trim ());
    },

    // METHOD setProgress ()
    setProgress (v)
    {
      const ppc = this.element[0].querySelector (".postit-progress-container");

      if (v == undefined || v == 0)
      {
        this.element[0].removeAttribute ("data-progress");
        ppc.style.display = "none";
      }
      else
      {
        const p = ppc.querySelector (".postit-progress");

        this.element[0].dataset.progress = v;
        ppc.querySelector("span").innerText = v+"%";
        ppc.style.display = "block";

        p.style.height = v+"%";
        p.style.backgroundColor = H.getProgressbarColor (v);
      }
    },

    // METHOD setTitle ()
    setTitle (v)
    {
      this.element.find(".postit-header span.title")
        .text (H.noHTML(v) || "...");
    },

    // METHOD addExternalRefIcon ()
    addExternalRefIcon (c)
    {
      c.querySelectorAll("[external-src]").forEach (img =>
        {
          const next = img.nextSibling;

          if (!next || !next.classList.contains ("externalref"))
            $(`<i class="fas fa-umbrella fa-lg externalref"></i>`)
              .insertAfter ($(img));
        });
    },

    // METHOD removeExternalRefIcon ()
    removeExternalRefIcon (c)
    {
      c.querySelectorAll("i.externalref").forEach (el => el.remove ());
    },

    // METHOD setContent ()
    setContent (newContent)
    {
      const postit = this.element[0],
            edit = postit.querySelector (".postit-edit");
      let setIcon = false;

      if (newContent !== edit.innerHTML)
      {
        const externalRef = this.getExternalRef (newContent);

        if (externalRef)
        {
          postit.dataset.haveexternalref = 1;

          if (!this.settings.wall.wall("displayExternalRef"))
          {
            setIcon = true;
            newContent = this.blockExternalRef (newContent, externalRef);
          }
        }
        else
          postit.removeAttribute ("data-haveexternalref");

        edit.innerHTML = newContent;

        if (setIcon)
          this.addExternalRefIcon (edit);
        else
          this.removeExternalRefIcon (edit);
      }
    },

    // METHOD openAskForExternalRefPopup ()
    openAskForExternalRefPopup (args = {})
    {
      let ask = (this.getExternalRef() &&
                 this.settings.wall.wall("displayExternalRef") != 1);

      if (ask)
        H.openConfirmPopover ({
          item: args.item||this.element,
          title: `<i class="fas fa-link fa-fw"></i> <?=_("External content")?>`,
          content: "<?=_("This note contains external images or videos.")?><br><?=_("Do you want to load all external content for this wall?")?>",
          cb_close: args.cb_close,
          cb_ok: () =>
          {
            this.settings.wall.wall ("displayExternalRef", 1);
            this.open ()
          }
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
        externalRef.forEach (src =>
          c = c.replace (new RegExp ("[^\-]"+H.escapeRegex(src), "g"),
                " external-"+src+" "));

        if (content === undefined)
        {
          el.innerHTML = c;
          this.addExternalRefIcon (el);
        }
        else
          return c;
      }
    },

    // METHOD unblockExternalRef ()
    unblockExternalRef (content)
    {
      if (content !== undefined)
        return content.replace (/external\-src/g, "src");
      else
      {
        this.element[0].querySelectorAll("[external-src]").forEach (el =>
          {
            el.setAttribute ("src", el.getAttribute ("external-src"));
            el.removeAttribute ("external-src");
          });

        this.removeExternalRefIcon (this.element[0]);
      }
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
      H.fetch (
        "GET",
        "wall/"+this.settings.wallId+
          "/cell/"+this.settings.cellId+
            "/postit/"+this.settings.id+"/attachment",
        null,
        // success cb
        (d) =>
        {
          H.loadPopup ("postitAttachments", {
            open: false,
            init: ($p)=>
            {
              $_attachmentsPopup = $p;
              $_attachmentEditPopup = $p.find (".edit-popup");
            },
            cb: ($p)=>
            {
              const writeAccess = this.canWrite ();
              let body = '';

              d = d.files;

              if (!d.length)
                body = "<?=_("This note has no attachment")?>";
              else
                d.forEach (a =>
                  body += this.getAttachmentTemplate (a, !writeAccess));

              if (writeAccess)
                $p.find(".btn-primary").show ();
              else
                $p.find(".btn-primary").hide ();

              $p.find(".modal-body ul").html (body);

              $p[0].dataset.noclosure = true;

              H.openModal ($p);
            }
          });
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
              d.error : "<?=_("Unknown error.<br>Please try again later!")?>"
          });

          $postit.remove ();
        });
    },

    // METHOD update ()
    update (d, cell)
    {
      const $postit = this.element,
            postit = $postit[0],
            $tpick = S.getCurrent ("tpick");

      // Change postit cell
      if (cell && cell.id != this.settings.cellId)
      {
        if (this.settings.cell[0].classList.contains ("list-mode"))
          this.settings.cell.find(
            ".postit-min[data-id='postit-"+this.settings.id+"']").remove ();

        this.settings.cell =
          cell.obj||this.settings.wall.find("td[data-id='cell-"+cell.id+"']");
        this.settings.cellId = cell.id;

        $postit.appendTo (this.settings.cell);

        if (this.settings.cell[0].classList.contains ("postit-mode"))
          postit.style.visibility = "visible";
      }

      if (!d.ignoreResize)
      {
        postit.style.top = d.item_top+"px";
        postit.style.left = d.item_left+"px";
        postit.style.width = d.width+"px";
        postit.style.height = d.height+"px";

        H.waitForDOMUpdate (()=> this.repositionPlugs ());
      }

      this.setClassColor (d.classcolor);

      this.setProgress (d.progress);

      this.setTitle (d.title);

      this.setContent (d.content);

      this.setAttachmentsCount (d.attachmentscount);

      this.setCreationDate (d.creationdate?H.getUserDate (d.creationdate):"");

      this.setDeadline (d);

      postit.dataset.order = d.item_order||0;

      if (!d.obsolete)
        postit.classList.remove ("obsolete");

      if (!d.tags)
        d.tags = "";

      postit.dataset.tags = d.tags;

      postit.querySelector(".postit-tags").innerHTML =
        $tpick.tpick ("getHTMLFromString", d.tags);

      $tpick.tpick ("refreshPostitDataTag", $postit);
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
                "<?=_("This note has no attachment")?>");
          }
        }
      );
    },

    // METHOD updateAttachment ()
    updateAttachment (args)
    {
      H.fetch (
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
    edit (args = {}, success_cb, error_cb)
    {
      const data = {cellId: this.settings.cellId};

      if (!args.plugend)
      {
        this.setCurrent ();

        _originalObject = this.serialize()[0];
      }

      if (!this.settings.wall.wall ("isShared"))
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

      if (!this.settings.id || !this.canWrite ())
        return this.cancelEdit (args);

      if (!args.plugend)
      {
        // Update postits plugs dependencies
        if (plugsToSave)
        {
          data = {updateplugs: true, plugs: []};

          for (const id in plugsToSave)
            data.plugs.push ($(plugsToSave[id]).postit ("serialize")[0]);

          S.unset ("plugs-to-save");
        }
        // Postit update
        else
        {
          data = this.serialize()[0];
          todelete = !!data.todelete;

          // Delete/update postit only if it has changed
          if (todelete || H.updatedObject (_originalObject,
                                             data, {hadpictures: 1}))
            data["cellId"] = this.settings.cellId;
          else if (!this.settings.wall.wall ("isShared"))
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
          else if (todelete && $postit[0].classList.contains ("selected"))
            S.getCurrent("mmenu").mmenu ("remove", this.settings.id);
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
      }

      if (!this.settings.id)
        setTimeout(()=>H.raiseError (null, "<?=_("The entire column/row was deleted while you were editing the note!")?>"), 150);
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

  if (!H.isLoginPage ())
    $(function()
      {
        setTimeout (()=>{
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
                let c = editor.getContent ();
                const noTab = H.removeHTMLTable (c);

                // Clean up HTML tables
                if (noTab !== null)
                  editor.setContent (noTab);

                // Check for img only if the TinyMCE dialog is open
                if ($(".tox-dialog").is(":visible"))
                {
                  let tmp;

                  (c.match(/<img\s[^>]+>/g)||[]).forEach (img =>
                    {
                      if ( (tmp = img.match (/src="([^"]+)"/)) )
                      {
                        const src = tmp[1];

                        H.loader ("show");
                        H.testImage(src)
                          .then (
                          // Needed for some Safari on iOS that do not support
                          // Promise finally() callback.
                          ()=> H.loader("hide"),
                          ()=>
                          {
                            H.loader("hide");

                            editor.setContent (
                              c.replace(new RegExp (H.quoteRegex(img)), ""));

                            // Return to the top of the modal if mobile device
                            if (!H.haveMouse ())
                              $("#postitUpdatePopup").scrollTop (0);

                            H.displayMsg ({
                              type: "warning",
                              msg: "<?=_("The image %s was not available! It has been removed from the note content")?>".replace("%s", `«&nbsp;<i>${src}</i>&nbsp;»`)
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

      // EVENTS mouseenter touchstart on postit
      // Sort of ":hover" simulation with z-index persistence
      $(document).on("mouseenter touchstart", ".postit", function ()
        {
          const el = S.getCurrent("wall")[0].querySelector (".postit.hover");

          if (el)
            el.classList.remove ("hover");

          this.classList.add ("hover");
        });

      // EVENTS click on postit
      $(document).on("click", ".postit", function (e)
        {
          if (!e.ctrlKey)
            return;

          const id = this.dataset.id.substring (7),
                menu = S.getCurrent("mmenu").mmenu ("getClass");

          e.stopImmediatePropagation ();
          e.preventDefault ();

          if (this.classList.contains ("selected"))
            menu.remove (id);
          else
            menu.add ($(this).postit ("getClass"));
        });

        // EVENT click on postit content links
        $(document).on("click", ".postit-edit a[href]", function (e)
          {
            const canWrite = H.checkAccess ("<?=WPT_WRIGHTS_RW?>"),
                  $link = $(this),
                  $menu = $(`<div class="dropdown submenu submenu-link"><ul class="dropdown-menu show"><li data-action="open-link"><a class="dropdown-item" href="#"><i class="fa-fw fas fa-link"></i> <?=_("Open link")?></a></li><li data-action="edit"><a class="dropdown-item" href="#"><i class="fa-fw fas fa-${canWrite?"edit":"eye"}"></i> ${canWrite?"<?=_("Edit note")?>":"<?=_("Open note")?>"}</a></li></ul></div>`);

           if (e.ctrlKey || H.disabledEvent ())
            return;

            e.stopImmediatePropagation ();
            e.preventDefault ();

            $menu.find("li").on("click", function ()
              {
                $("#popup-layer").click ();

                if (this.dataset.action == "open-link")
                  window.open ($link.attr ("href"), "_blank");
                else
                  $link.closest(".postit").postit ("openPostit");
              });

            H.openPopupLayer (()=> $menu.remove ());
            $menu.prependTo ("body");
            $menu.css ({top: e.clientY, left: e.clientX});
          });

        // EVENT mousedown on tags
        $(document).on("mousedown", ".postit-tags", function (e)
          {
            if (H.disabledEvent (!H.checkAccess ("<?=WPT_WRIGHTS_RW?>")))
              return false;

            $(this.parentNode).postit ("edit", {},
              () => S.getCurrent("tpick").tpick ("open", e));
          });

        // EVENT click on dates
        $(document).on("click", ".postit .dates .end", function (e)
          {
            if (H.disabledEvent (!H.checkAccess ("<?=WPT_WRIGHTS_RW?>")))
              return false;

            const $item = $(e.target),
                  plugin = $(this.parentNode.parentNode).postit ("getClass");

            if ($item.hasClass("fa-times-circle"))
            {
              plugin.edit ({}, () =>
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
              plugin.openDatePicker ();
          });

        // EVENT click on attachment count
        $(document).on("click", ".attachmentscount", function (e)
          {
            const plugin = $(this.parentNode).postit ("getClass");

            if (H.checkAccess ("<?=WPT_WRIGHTS_RW?>"))
              plugin.openAttachments ();
            else
            {
              plugin.setCurrent ();
              plugin.displayAttachments ();
            }
          });

          // EVENT click on menu button
        $(document).on("click", ".postit .btn-menu", function (e)
          {
            if (!H.checkAccess ("<?=WPT_WRIGHTS_RW?>"))
              return;

            const $btn = $(this),
                  btn = this.querySelector ("i"),
                  $postit = $(this.parentNode),
                  id = $postit.postit ("getId"),
                  settings = $postit.postit ("getSettings"),
                  $wall = settings.wall,
                  $header = $postit.find (".postit-header");

            // Create postit menu and show it
            if (!settings.Menu)
            {
              const coord = $header[0].getBoundingClientRect ();

              $wall.wall ("closeAllMenus");

              settings.Menu = new _Menu ($postit.postit ("getClass"));

              if ((coord.x||coord.left)+settings.Menu.getWidth()+20 >
                    $(window).width())
              {
                btn.classList
                  .replace ("fa-caret-square-up", "fa-caret-square-left");
                settings.Menu.setPosition ("left");
              }
              else
                settings.Menu.setPosition ("right");

              $header.addClass ("menu");
              btn.classList.replace ("far", "fas");

              settings.Menu.show ();

              $(document)
                // EVENT keydown
                .on("keydown.pmenu", function (e)
                {
                  if (e.which == 27)
                    $btn.click ();
                });
            }
            // Destroy postit menu
            else
            {
              $(document).off ("keydown.pmenu");

              $header.removeClass ("menu");
              btn.classList.replace("fas", "far");
              btn.classList
                .replace ("fa-caret-square-left", "fa-caret-square-up");

              settings.Menu.destroy ();
              delete settings.Menu;
            }
          });

        // EVENT click on postit
        // -> readonly mode
        $(document).on(
          "click",
          ".postit-edit,.postit-header,.dates", function (e)
          {
            if (H.disabledEvent ())
              return false;

            if (!e.ctrlKey && !H.checkAccess ("<?=WPT_WRIGHTS_RW?>"))
              $(this.parentNode).postit ("openPostit");
          });

        // EVENT click on plug label
        $(document).on("click", ".plug-label li", function (e)
          {
            const $item = $(this),
                  $label = $item.closest("div"),
                  $wall = S.getCurrent ("wall"),
                  [,startId, endId] =
                    $label[0].previousSibling.id.match (/^_(\d+)\-(\d+)$/),
                  startPlugin =
                    $wall.find(".postit[data-id='postit-"+startId+"']")
                      .postit("getClass"),
                  defaultLabel = H.htmlEscape ($label.find("span").text ()),
                  __unedit = ()=>
                  {
                    const toSave = {};

                    toSave[startId] = startPlugin.element;
                    toSave[endId] =
                      $wall.find(".postit[data-id='postit-"+endId+"']");

                    S.set ("plugs-to-save", toSave);
                    startPlugin.unedit ();
                  };

            switch ($item[0].dataset.action)
            {
              case "rename":

                startPlugin.edit ({}, ()=>
                  {
                    H.openConfirmPopover ({
                      type: "update",
                      item: $label,
                      title: `<i class="fas fa-bezier-curve fa-fw"></i> <?=_("Relation name")?>`,
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

                startPlugin.edit ({}, ()=>
                  {
                    H.openConfirmPopover ({
                      item: $label,
                      placement: "left",
                      title: `<i class="fas fa-trash fa-fw"></i> <?=_("Delete")?>`,
                      content: "<?=_("Delete this relation?")?>",
                      cb_close: __unedit,
                      cb_ok: () => startPlugin.removePlug (endId)
                    });
                  });

                break;

              case "position-auto":

                startPlugin.edit ({}, ()=>
                  {
                    const p = startPlugin.getPlugById (endId);

                    p.related.forEach (_p => _p.remove ());
                    p.related = [];
                    p.customPos = false;
                    p.obj.show ();

                    startPlugin.resetPlugLabelPosition ($label[0]);
                    startPlugin.repositionPlugs ();
                    __unedit ();
                  });
                break;

              case "properties":

                startPlugin.openPlugProperties (startPlugin.getPlugById(endId));
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
                           msg: "<?=_("The file is already linked to the note!")?>"
                         });

                if (H.checkUploadFileSize ({size: e.total}) &&
                    e.target.result)
                {
                  H.fetchUpload (
                    "wall/"+settings.wallId+"/cell/"+settings.cellId+"/postit/"+
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
                  fname = this.files[0].name,
                  __error_cb = (d)=>
                  {
                    if (d)
                      H.displayMsg ({
                        type: "warning",
                        msg: d.error||d
                      });
                  };

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

                    H.fetch (
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
                              __error_cb ("<?=_("Sorry, there is a compatibility issue with your browser when it comes to uploading notes images...")?>");
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
              popup.querySelectorAll(".ro").forEach (el =>
                el.style.display = "none");
              popup.querySelectorAll(".adm").forEach (el =>
                el.style.display = "block");

              popup.querySelector(".title input").value = titleVal;
              popup.querySelector(".description textarea").value = descVal;

              H.setAutofocus ($(popup));
            }
            else
            {
              popup.querySelector(".btn-primary").style.display = "none";
              popup.querySelectorAll(".ro").forEach (el =>
                el.style.display = "block");
              popup.querySelectorAll(".adm").forEach (el =>
                el.style.display = "none");

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
                  plugin = S.getCurrent("postit").postit ("getClass"),
                  progress = $popup.find(".slider").slider ("value"),
                  title = $("#postitUpdatePopupTitle").val (),
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

                      if (!H.haveMouse ())
                        H.fixVKBScrollStop ();
                    };

              // If there is pending changes, ask confirmation to user
              if (data && (
                // Content change detection
                tinymce.activeEditor.isDirty () ||
                // Title change detection
                H.htmlEscape(data.title) != H.htmlEscape(title) ||
                // Progress change detection
                data.progress != progress))
              {
                e.preventDefault ();

                H.openConfirmPopup ({
                  type: "save-postits-changes",
                  icon: "save",
                  content: `<?=_("Save changes?")?>`,
                  cb_ok: () =>
                    {
                      plugin.setProgress (progress);
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
          })}, 0);
      });

<?php echo $Plugin->getFooter ()?>
