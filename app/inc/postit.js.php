<?php
  require_once (__DIR__.'/../class/Wpt_jQueryPlugins.php');
  require_once (__DIR__.'/../class/Wpt_dbCache.php');
  $Plugin = new Wpt_jQueryPlugins ('postit');
  echo $Plugin->getHeader ();
?>

  const _defaultString = "...",
        _defaultClassColor =
          "color-<?=array_keys(WPT_MODULES['colorPicker']['items'])[0]?>";
  let $_attachmentsPopup,
      _originalObject,
      _plugRabbit = {
        line: null,
        mouseEvent: (e) =>
          {
            const rabbit = document.getElementById ("plug-rabbit");

            rabbit.style.left = e.clientX+"px";
            rabbit.style.top = e.clientY+"px";

            _plugRabbit.line.position ();
          },
        escapeEvent: (e) =>
          {
            if (e.key == "Escape")
              wpt_sharer.get("link-from").obj.wpt_postit ("cancelPlugAction");
          }
      };

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

  Plugin.prototype =
  {
    // METHOD init ()
    init: function ()
    {
      const plugin = this,
            $postit = plugin.element,
            settings = plugin.settings,
            $wall = settings.wall,
            writeAccess = wpt_checkAccess ("<?=WPT_RIGHTS['walls']['rw']?>",
                                           settings.access);

      $postit[0].className = settings.classes || "postit";
      $postit[0].dataset.tags = settings.tags || "";

      settings._plugs = [];
      settings._plugColor = "";

      const $body = $(`<div class="postit-header"><span class="title">${_defaultString}</span></div><div class="postit-edit"></div><div class="dates"><div class="creation" title="<?=_("Creation date")?>"><i class="far fa-clock fa-xs"></i> <span>${moment.tz(wpt_userData.settings.timezone).format('Y-MM-DD')}</span></div><div class="end" title="<?=_("Deadline")?>"><i class="fas fa-times-circle"></i><i class="fas fa-hourglass-end fa-xs"></i> <span>...</span></div></div>`);

      $postit.append ($body);

      $postit
        .css({
          visibility: "hidden",
          top: settings.top,
          left: settings.left
        })
        // EVENTS mouseenter focusin click
        .on("mouseenter focusin click", function(e)
          {
            const $oldPostit = wpt_sharer.get ("postit-oldzindex");

            if ($oldPostit &&
                $oldPostit.postit.wpt_postit ("getId") != settings.id)
              plugin.resetZIndexData ();

            if (!wpt_sharer.get ("postit-oldzindex"))
            {
              wpt_sharer.set ("postit-oldzindex", {
                zIndex: $postit.css ("z-index"),
                postit: $postit
              });

              $postit.css ("z-index", 5000);
            }
          })
        // EVENTS mouseleave focusout
        .on("mouseleave focusout",function(e)
          {
            const $currentPostit = wpt_sharer.getCurrent ("postit");

            if (!$.support.touch &&
                (!$currentPostit || !$currentPostit.length) &&
                wpt_sharer.get ("postit-oldzindex") &&
                !$("#popup-layer").length &&
                !$(".modal:visible").length)
            {
              plugin.resetZIndexData ();
            }
          });

      if (settings.obsolete)
        $postit.addClass ("obsolete");

      $postit.find(".postit-edit,.postit-header,.dates")
      .on("click", function (e)
      {
        const id = plugin.settings.id,
              from = wpt_sharer.get ("link-from");

        if (from)
        {
          e.stopImmediatePropagation ();
          e.preventDefault ();

          if (from.id != id &&
              ($postit[0].dataset.plugs||"").indexOf(from.id) == -1)
          {
            plugin.edit ({plugend: true}, ()=>
              {
                const $popup = $("#plugPopup"),
                  $start = from.obj,
                  line = {
                    startId: from.id,
                    endId: id,
                    obj: plugin.getPlugTemplate ($start[0], $postit[0])
                  };

                line.obj.setOptions ({
                  dropShadow: null,
                  size: 3,
                  color: "#bbb",
                  dash: {animation: true}
                });

                $start.wpt_postit ("addPlug", line);
                $start.wpt_postit ("cancelPlugAction", false);

                from.cancelCallback = () =>
                  {
                    $start.wpt_postit ("removePlug", line);
                    $start.wpt_postit ("cancelPlugAction");
                  };

                from.confirmCallback = (label) =>
                  {
                    const color = plugin.settings._plugColor||
                           $(".wall th:eq(0)").css("background-color"),
                          $undo = $start.find (
                            ".postit-menu [data-action='undo-plug'] a");

                    if (!label)
                      label = "...";

                    line.label = label;
                    line.obj.setOptions ({
                      size: 4,
                      color: color,
                      dash: null,
                      middleLabel: LeaderLine.captionLabel({
                        text: label,
                        fontSize:"13px"
                      })
                    });

                    $start.wpt_postit ("applyTheme");
                    $start.wpt_postit ("addPlugLabel", line);

                    $start[0].dataset.undo = "add";
                    $undo.removeClass ("disabled");
                    $undo.find("span").text ("« <?=_("Add")?> »");

                    $start.wpt_postit ("cancelPlugAction");
                  };

                wpt_cleanPopupDataAttr ($popup);

                wpt_sharer.set ("link-from", from);

                wpt_openModal ($popup);
              });
          }
          else
          {
            if (from.id != id)
              wpt_displayMsg ({
                type: "warning",
                msg: "<?=_("This relationship already exists")?>"
              });
            else
              plugin.cancelPlugAction ();
          }
        }
      })
      // EVENT doubletap
      .doubletap (() =>
        $postit.find(".postit-menu [data-action='edit']").trigger ("click"));

      if (writeAccess)
      {
        $postit  
        // DRAGGABLE post-it
        .draggable ({
          //FIXME "distance" is deprecated -> is there any alternative?
          distance: 10,
          appendTo: "parent",
          revert: "invalid",
          cursor: "pointer",
          containment: plugin.settings.wall.find("tbody"),
          scrollSensitivity: 50,
          opacity: 0.35,
          scope: "dzone",
          stack: ".postit",
          drag:function(e, ui)
          {
            if (wpt_sharer.get("revertData").revert)
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
  
              wpt_sharer.set ("revertData", {
                revert: false,
                top: p.top,
                left: p.left
              });
  
              plugin.edit (
                {ignoreResize: true},
                null,
                () => wpt_sharer.get("revertData").revert = true
              );
            },
          stop: function(e, ui)
            {
              if (wpt_sharer.get("revertData").revert)
              {
                const revertData = wpt_sharer.get ("revertData");

                plugin.setPosition ({
                  top: revertData.top,
                  left: revertData.left
                });

                plugin.cancelEdit ();
              }
              else
              {
                // If the postit has been dropped into another cell
                plugin.settings.cellId = $postit.parent().wpt_cell ("getId");

                plugin.unedit ();
              }

              // Update postits relationships arrows
              plugin.repositionPlugs ();
// TODO - 1 - Hide plugs instead of moving them with postits (performance
//            issue with some touch devices)
//              wpt_waitForDOMUpdate (() => plugin.showPlugs ());
            }
        })
        // RESIZABLE post-it
        .resizable ({
          handles: "all",
          autoHide: false,
          resize:function(e, ui)
          {
            // Update postits relationships arrows
            plugin.repositionPlugs ();

            if (wpt_sharer.get("revertData").revert)
              return false;
          },
          start: function(e, ui)
            {
              const postit = $postit[0];
  
              wpt_sharer.set ("revertData", {
                revert: false,
                width: postit.clientWidth,
                height: postit.clientHeight
              });

              plugin.edit (
                {ignoreResize: true},
                null,
                () => wpt_sharer.get("revertData").revert = true);
            },
          stop: function(e, ui)
            {
              const revertData = wpt_sharer.get ("revertData");

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
              {
                //FIXME
                setTimeout (() =>
                  {
                    ui.element.parent().wpt_cell ("reorganize");
                    plugin.unedit ();

                  }, 150);
              }
            }
          });
        }
  
      const $header = $(`
        <i class="far fa-caret-square-down" data-action="menu"></i>`)
        .on("click", function(e)
          {
            const $btn = $(this),
                  id = settings.id,
                  $menu = $postit.find (".postit-menu"),
                  $header = $btn.closest(".postit-header");

            if (!$menu.hasClass ("on"))
            {
              $wall.wpt_wall ("closeAllMenus");

              if ($wall.find(".postit").length == 1)
                $menu.find("li[data-action='add-plug'] .dropdown-item")
                  .addClass ("disabled");
              else
                $menu.find("li[data-action='add-plug'] .dropdown-item")
                  .removeClass ("disabled");

              plugin.checkPlugsMenu ();

              $header.addClass ("menu");
              $btn.switchClass ("far", "fas");
              $menu
                .addClass("on")
                .show ();
              $postit.find (".postit-delete").show ();
            }
            else
            {
              $header.removeClass ("menu");
              $btn.switchClass ("fas", "far");
              $menu
                .removeClass("on")
                .hide ();
              $postit.find (".postit-delete").hide ();
            }
          });
  
      if (!writeAccess)
        $header.css("visibility", "hidden");

      $header.prependTo ($postit.find(".postit-header"));

      // Post-it menu
      const $menu = $(`
        <div class="postit-menu">
          <div><i data-action="delete" class="fa-times fa-fw fas"></i></div>
          <div><i data-action="edit" class="fa-edit fa-fw fas fa-xs"></i></div>
          <div><i data-action="tag-picker" class="fa-tags fa-fw fas fa-xs"></i></div>
          <div><i data-action="color-picker" class="fa-palette fa-fw fas fa-xs"></i></div>
          <div><i data-action="date-picker" class="fa-hourglass-end fa-fw fas fa-xs"></i></div>
          <div><i data-action="attachments" class="fa-paperclip fa-fw fas fa-xs"></i></div>
          <ul data-action="plug" class="navbar-nav mr-auto submenu">
            <li class="nav-item dropdown">
              <a href="#" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" class="nav-link dropdown-toggle"><i data-action="plug" class="fa-bezier-curve fa-fw fas fa-xs"></i></a>
              <ul class="dropdown-menu border-0 shadow">
                <li data-action="add-plug"><a class="dropdown-item" href="#"><i class="fa-fw fas fa-plus"></i> <?=_("Add relationship")?></a></li>
                <li data-action="delete-plugs"><a class="dropdown-item" href="#"><i class="fa-fw fas fa-trash"></i> <?=_("Delete relationships")?></a></li>
                <li class="dropdown-divider"></li>
                <li data-action="undo-plug"><a class="dropdown-item disabled" href="#"><i class="fa-fw fas fa-undo-alt"></i> <?=_("Undo")?> <span></span></a></li>
              </ul>
            </li>
          </ul>
        </div>`);

        // Menu events
        $menu.find(">div").on("click", function(e)
          {
            let $btn = $(e.target),
                action = $btn[0].dataset.action;

            plugin.closePlugMenu ();

            e.stopImmediatePropagation ();

            if (!action)
            {
              $btn = $btn.find ("i");
              action = $btn[0].dataset.action;
            }

            // Open modal with read rights only
            if (!writeAccess)
            {
              const content = $postit.find(".postit-edit").html (),
                    title = $postit.find(".postit-header span.title").html (),
                    $popup = $("#postitViewPopup");

              plugin.setCurrent ();

              wpt_cleanPopupDataAttr ($popup);

              $popup.find(".modal-body").html ((content) ?
                content : "<i><?=_("No content.")?></i>");

              $popup.find(".modal-title").html (
                `<i class="fas fa-sticky-note"></i> ${title}`);

              wpt_openModal ($popup, _getMaxEditModalWidth (content));
            }
            // Open modal with write rights
            else
            {
              if (wpt_sharer.get ("link-from"))
                plugin.cancelPlugAction (true, false);

              plugin.edit (null, () =>
                {
                  switch ($btn[0].dataset.action)
                  {
                    case "delete":

                      wpt_openConfirmPopover ({
                        item: $header,
                        placement: "right",
                        title: `<i class="fas fa-trash fa-fw"></i> <?=_("Delete")?>`,
                        content: "<?=_("Delete this post-it?")?>",
                        cb_close: () => plugin.unedit (),
                        cb_ok: () => plugin.delete ()
                      });

                      break;
      
                    // OPEN Popup of attachments
                    case "attachments":
    
                      plugin.displayAttachments ();
    
                      break;                 
    
                    // OPEN post-it edit popup
                    case "edit":
    
                      const $popup = $("#postitUpdatePopup"),
                            title =
                              $postit.find(".postit-header span.title").html (),
                            content = $postit.find(".postit-edit").html()||"";

                      wpt_sharer.set ("postit-data", {
                        title: title != _defaultString ? title : ""
                      });
    
                      $("#postitUpdatePopupTitle")
                        .val (wpt_sharer.get("postit-data").title);

                      //FIXME
                      $(".tox-toolbar__overflow").show ();
                      $(".tox-mbtn--active").removeClass ("tox-mbtn--active");

                      // Check if post-it content has pictures
                      if (content.match (/\/postit\/\d+\/picture\/\d+/))
                        $postit[0].dataset.hadpictures = true;
                      else
                        $postit[0].removeAttribute ("data-hadpictures");

                      tinymce.activeEditor.setContent (content);

                      if ($.support.touch)
                        wpt_fixVKBScrollStart ();

                      wpt_openModal ($popup, _getMaxEditModalWidth (content));
    
                      break;
    
                    // OPEN tags picker
                    case "tag-picker":

                      $(".tag-picker").wpt_tagPicker ("open", e);

                      break;
    
                    // OPEN color picker
                    case "color-picker":

                      $(".color-picker").wpt_colorPicker ("open", e);

                      break;
    
                    // OPEN date picker
                    case "date-picker":

                      plugin.openDatePicker ();

                      break;
                  }
              });
            }
          });

        // Menu submenus events
        $menu.find(">ul .dropdown-menu a")
          .on("click", function(e, d)
          {
            const $item = $(this);

            e.stopImmediatePropagation ();

            plugin.closePlugMenu ();

            // Nothing if item menu is disabled (can change dynamically)
            if ($item.hasClass ("disabled")) return;

            e = d||e;

            switch ($(this).parent()[0].dataset.action)
            {
              case "add-plug":

                plugin.edit (null, () =>
                  {
                    $(document)
                      .off("keydown", _plugRabbit.escapeEvent)
                      .on ("keydown", _plugRabbit.escapeEvent);
      
                    $(`<div id="plug-rabbit" style="position:absolute;left:${e.clientX}px;top:${e.clientY}px"></div>`).prependTo ("body");
      
                    _plugRabbit.line = new LeaderLine (
                      $postit[0],
                      document.getElementById ("plug-rabbit"),
                      {
                        size: 3,
                        color: "#9b9c9c",
                        dash: true
                      });
       
                    $("body")
                      .off("mousemove", _plugRabbit.mouseEvent)
                      .on("mousemove", _plugRabbit.mouseEvent);
      
                    wpt_sharer.set ("link-from", {
                      id: settings.id,
                      obj: $postit
                    });

                  });

                break;

              case "delete-plugs":

                plugin.edit (null, () =>
                  {
                    wpt_openConfirmPopover ({
                      item: $postit.find("[data-action='menu']"),
                      placement: "left",
                      title: `<i class="fas fa-trash fa-fw"></i> <?=_("Delete")?>`,
                      content: "<?=_("Delete all relationships from this post-it?")?>",
                      cb_close: () => plugin.unedit (),
                      cb_ok: () =>
                        {
                          const removedIds = plugin.removePlugs (),
                                $undo =
                                  $menu.find("[data-action='undo-plug'] a");

                          plugin.unedit ();

                          $postit[0].dataset.undo = "delete|"+removedIds;
                          $undo.removeClass ("disabled");
                          $undo.find("span").text ("« <?=_("Delete")?> »");
                        }
                      });
                    });

                break;

              case "undo-plug":

                const [action, ids] = $postit[0].dataset.undo.split ("|");

                plugin.resetPlugsUndo ();

                if (action == "add")
                {
                  plugin.edit (null, () =>
                  {
                    const plugs = plugin.settings._plugs;

                    plugin.removePlug (plugs[plugs.length - 1]);
                    plugin.unedit ();
                  });
                }
                else if (action == "delete")
                {
                  plugin.edit (null, () =>
                    {
                      const toSave = {};

                      ids.split(",").forEach ((item) =>
                      {
                        const startId = plugin.settings.id,
                              [endId, label] = item.split (";"),
                              $end =
                                settings.wall
                                  .find(".postit[data-id='postit-"+endId+"']");
  
                        if ($end.length)
                        {
                          toSave[startId] = $postit;
                          toSave[endId] = $end;

                          plugin.addPlug ({
                            startId: startId,
                            endId: endId,
                            label: label,
                            obj: plugin.getPlugTemplate (
                                   $postit[0], $end[0], label)
                          });
                        }
                        else
                          wpt_displayMsg ({
                            type: "warning",
                            msg: "<?=_("This item has been deleted")?>"
                          });
                      });

                      wpt_sharer.set("plugs-to-save", toSave);

                      plugin.unedit ();

                    });
                }

                break;
            }
          });

      const $attachmentscount = $(`<div class="attachmentscount"${settings.attachmentscount?'':' style="display:none"'}><i data-action="attachments" class="fas fa-paperclip"></i><span class="wpt-badge">${settings.attachmentscount}</span></div>`)
        .on("click", function ()
          {
            if (writeAccess)
              $postit.find(".postit-menu [data-action='attachments']")
                .trigger ("click");
            else
              plugin.edit (null, () => plugin.displayAttachments ());
          });
  
      const $tags = $(`<div class="postit-tags">${settings.tags?$(".tag-picker").wpt_tagPicker ("getHTMLFromString", settings.tags):''}</div>`);

      if (writeAccess)
        $tags.on("mousedown",
          function (e)
          {
            const evt = e;
    
            e.stopImmediatePropagation ();
    
            plugin.edit (null,
              () => $(".tag-picker").wpt_tagPicker ("open", evt));
          });
      else
        $menu.css ("visibility", "hidden");
  
      $postit[0].dataset.id = "postit-"+settings.id;
    
      $postit
        .append($attachmentscount)
        .append($tags)
        .prepend($menu);

      if (writeAccess)
        $postit
        .find(".dates .end")
          .on("click", function(e)
            {
              const $item = $(e.target);
  
              if ($item.hasClass("fa-times-circle"))
              {
                plugin.edit (null, () =>
                {
                  wpt_openConfirmPopover ({
                    item: $item,
                    title: `<i class="fas fa-trash fa-fw"></i> <?=_("Reset")?>`,
                    content: "<?=_("Reset deadline?")?>",
                    cb_close: () => plugin.unedit (),
                    cb_ok: () => plugin.resetDeadline ()
                  });
                });
              }
              else
                $menu.find ("i.fa-hourglass-end").trigger ("click");
            });

      if (settings.creationdate)
        setTimeout (
          () => plugin.update (settings), (!!settings.isNewCell) ? 150 : 0);
    },

    // METHOD cancelPlugAction ()
    cancelPlugAction: function (full = true, unedit = true)
    {
      if (_plugRabbit.line)
      {
        $(document).off ("keydown", _plugRabbit.escapeEvent);

        $("body").off("mousemove", _plugRabbit.mouseEvent)
        _plugRabbit.line.remove ();
        _plugRabbit.line = null;
      }

      if (full)
      {
        if (unedit)
          wpt_sharer.get ("link-from").obj.wpt_postit ("unedit");

        wpt_sharer.unset ("link-from");
      }
    },

    // METHOD havePlugs ()
    havePlugs: function ()
    {
      return (this.settings._plugs||[]).length;
    },

    // METHOD getPlugsIds ()
    getPlugsIds: function ()
    {
      return this.element[0].dataset.plugs.split (",");
    },

    // METHOD getPlugTemplate ()
    getPlugTemplate: function (start, end, label)
    {
      return new LeaderLine (
              start,
              end,
              {
                dropShadow: {
                  dx: 0.2,
                  dy: 0.2,
                  blur: 1,
                  color: $(".bg-dark").css ("background-color")
                },
                startPlug: "arrow1",
                endPlug: "arrow1",
                color: this.settings._plugColor||
                      $(".wall th:eq(0)").css("background-color"),
                middleLabel: LeaderLine.captionLabel ({
                  text: label,
                  fontSize:"13px"
                })
              });
    },

    // METHOD applyThemeToPlugs ()
    applyThemeToPlugs: function ()
    {
      const settings = this.settings,
            shadowColor = $(".bg-dark").css ("background-color");

      settings._plugColor = $(".wall th:eq(0)").css ("background-color");

      settings._plugs.forEach (
        plug => plug.obj.setOptions ({
          dropShadow: {
            dx: 0.2,
            dy: 0.2,
            blur: 1,
            color: shadowColor
          },
          color: settings._plugColor
        }));
    },

    // METHOD applyTheme ()
    applyTheme: function ()
    {
      $(".postit.with-plugs").each (function ()
        {
          $(this).wpt_postit ("applyThemeToPlugs");
        });
    },

    // METHOD resetPlugsUndo ()
    resetPlugsUndo: function ()
    {
      this.settings.wall.find(".postit").each (function ()
        {
          const $postit = $(this);

          $postit[0].dataset.undo = "";

          $postit.find(".postit-menu [data-action='undo-plug'] a")
            .addClass("disabled")
            .find("span").text ("");
        });
    },

    // METHOD checkPlugsMenu ()
    checkPlugsMenu: function ()
    {
      const $menu = this.element.find (".postit-menu");

      if (this.havePlugs ())
        $menu.find("li[data-action='delete-plugs'] .dropdown-item")
          .removeClass ("disabled");
      else
        $menu.find("li[data-action='delete-plugs'] .dropdown-item")
          .addClass ("disabled");
    },

    // METHOD updatePlugLabel ()
    updatePlugLabel: function (args)
    {
      const label = wpt_noHTML (args.label);

      for (let i = 0, iLen = this.settings._plugs.length; i < iLen; i++)
      {
        const plug = this.settings._plugs[i];

        if (plug.endId == args.endId)
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
    addPlugLabel: function (plug, $svg)
    {
      const labelId = plug.startId+"-"+plug.endId;

      if (!$svg)
        $svg = $("svg.leader-line[data-id='"+labelId+"']");

      const $text = $svg.find ("text"),
            pos = $text.position ();

      if (pos)
      {
        const writeAccess = wpt_checkAccess (
                "<?=WPT_RIGHTS['walls']['rw']?>", this.settings.access),
              menu = `<ul class="dropdown-menu border-0 shadow"><li data-action="rename"><a class="dropdown-item" href="#"><i class="fa-fw fas fa-edit"></i> <?=_("Rename")?></a></li><li data-action="delete"><a class="dropdown-item" href="#"><i class="fa-fw fas fa-trash"></i> <?=_("Delete")?></a></li></ul>`;

        plug.labelObj = $(`<div class="plug-label nav-item dropdown submenu line-menu" data-id="${labelId}" style="top:${pos.top}px;left:${pos.left}px"><a href="#" ${writeAccess?'data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"':""} class="dropdown-toggle"><span>${plug.label != "..." ? wpt_noHTML (plug.label) : '<i class="fas fa-ellipsis-h"></i>'}</span></a>${writeAccess?menu:""}</div>`);

        document.body.appendChild (plug.labelObj[0]);
      }
    },

    // METHOD addPlug ()
    addPlug: function (plug)
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
      const $svg = $("svg.leader-line:last-child");
      $svg[0].dataset.id = plug.startId+"-"+plug.endId;
      this.addPlugLabel (plug, $svg);

      // Register plug on start point postit (current plugin)
      this.settings._plugs.push (plug);
      $start.addClass ("with-plugs");

      // Register plug on end point postit
      $end.wpt_postit("getSettings")._plugs.push (plug);
      $end.addClass ("with-plugs");
    },

    // METHOD defragPlugsArray ()
    defragPlugsArray: function ()
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
    getPlugById: function (plugId)
    {
      for (let i = 0, iLen = this.settings._plugs.length; i < iLen; i++)
      {
        const plug = this.settings._plugs[i];

        if (plug.startId+"-"+plug.endId == plugId)
          return plug;
      }
    },

    // METHOD removePlug ()
    removePlug: function (plug, noedit)
    {
      let toDefrag = {};

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

        plug.obj.remove ();
        plug.obj = null;

        for (const id in toDefrag)
          toDefrag[id].wpt_postit ("defragPlugsArray");

        if (!noedit)
          wpt_sharer.set("plugs-to-save", toDefrag);
      }

      return ","+plug.startId+";"+plug.endId;
    },

    // METHOD removePlugs ()
    removePlugs: function (noedit)
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
          plug.obj.remove ();
          plug.obj = null;

          tmp[(plug.endId != settings.id) ? plug.endId : plug.startId] =
            plug.label;
        });

      for (const id in toDefrag)
        toDefrag[id].wpt_postit ("defragPlugsArray");

      if (!noedit)
        wpt_sharer.set("plugs-to-save", toDefrag);

      $postit[0].dataset.plugs = "";
      settings._plugs = [];
      $postit.removeClass ("with-plugs");

      for (const id in tmp)
        ret += ","+id+";"+tmp[id];

      return ret.substring (1);
    },

    // METHOD hidePlugs ()
    hidePlugs: function ()
    {
      if (!this.settings.wall) return;

      this.element.find(".postit-menu [data-action='plug']").hide ();

      this.settings._plugs.forEach ((plug) =>
        {
           if (plug.labelObj)
            plug.labelObj.hide ();

          plug.obj.hide ("none");
        });
    },

    // METHOD showPlugs ()
    showPlugs: function ()
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
    repositionPlugs: function ()
    {
      const div = document.body;

      this.settings._plugs.forEach ((plug) =>
        {
          plug.obj.position ();

          if (plug.label)
          {
            const p = div.querySelector("svg.leader-line[data-id='"+
              plug.startId+"-"+plug.endId+"'] text").getBoundingClientRect ();

            plug.labelObj[0].style.top = p.top+"px";
            plug.labelObj[0].style.left = p.left+"px";
          }
        });
    },

    // METHOD resetZIndexData ()
    resetZIndexData: function ()
    {
      wpt_sharer.get("postit-oldzindex").postit.css ("z-index",
        wpt_sharer.get ("postit-oldzindex").zIndex);

      wpt_sharer.unset ("postit-oldzindex");
    },

    // METHOD getSettings ()
    getSettings: function ()
    {
      return this.settings;
    },

    // METHOD getId ()
    getId: function ()
    {
      return this.settings.id;
    },

    // METHOD getCellId ()
    getCellId: function ()
    {
      return this.settings.cellId;
    },

    // METHOD getWallId ()
    getWallId: function ()
    {
      return this.settings.wallId;
    },

    // METHOD serializePlugs ()
    serializePlugs: function ()
    {
      const settings = this.settings;
      let ret = {};

      settings._plugs !== undefined &&
        settings._plugs.forEach ((plug) =>
        {
          // Take in account only plugs from this postit
          if (plug.startId == settings.id)
            ret[plug.endId] = (plug.label == "...") ?
              "" : plug.labelObj.find("a span").text ();
        });

      return ret;
    },

    // METHOD serialize ()
    serialize: function ()
    {
      const postits = [];

      this.element.each (function ()
      {
        const $p = $(this),
              p = $p[0],
              postitId = p.dataset.id.substring (7);
        let data = {};

        if (p.dataset.todelete)
          data = {id: postitId, todelete: true};
        else
        {
          const title = $p.find(".postit-header span.title").html (),
                classcolor = p.className.match(/(color\-[a-z]+)/);
          let tags = [],
              deadline = (p.dataset.deadlineepoch) ?
                p.dataset.deadlineepoch :
                $p.find(".dates .end span").text().trim ();

          $p.find(".postit-tags i").each (function ()
            {
              tags.push (this.dataset.tag);
            });

          data = {
            id: postitId,
            width: Math.trunc($p.outerWidth ()),
            height: Math.trunc($p.outerHeight ()),
            top: Math.trunc((p.offsetTop < 0) ? 0 : p.offsetTop),
            left: Math.trunc((p.offsetLeft < 0) ? 0 : p.offsetLeft),
            classcolor: (classcolor) ? classcolor[0] : _defaultClassColor,
            title: (title == _defaultString) ? "" : title,
            content: $p.find(".postit-edit").html (),
            tags: (tags.length) ? ","+tags.join(",")+"," : null,
            deadline: (deadline == _defaultString) ? "" : deadline,
            updatetz: p.dataset.updatetz || null,
            obsolete: $p.hasClass ("obsolete"),
            attachmentscount: $p.find(".attachmentscount span").text (),
            plugs: $p.wpt_postit ("serializePlugs"),
            hadpictures: !!p.dataset.hadpictures,
            hasuploadedpictures: !!p.dataset.hasuploadedpictures
          };
        }

        postits.push (data);

      });

      return postits;
    },

    // METHOD setDeadline ()
    setDeadline: function (deadline, timezone)
    {
      const $postit = this.element,
            $date = $postit.find(".dates .end");
      let human;

      if (!deadline || isNaN (deadline))
        human = deadline||_defaultString;
      else
        human = (deadline) ?
          wpt_getUserDate (deadline, timezone) : _defaultString;

      $date.find("span").text (human);

      if (!wpt_checkAccess ("<?=WPT_RIGHTS['walls']['rw']?>") ||
          human == _defaultString)
      {
        $postit[0].removeAttribute ("data-deadline");
        $postit[0].removeAttribute ("data-deadlineepoch");
        $postit[0].removeAttribute ("data-updatetz");
        $date.find("i.fa-times-circle").hide ();
      }
      else
      {
        $postit[0].dataset.deadline = human;
        $postit[0].dataset.deadlineepoch = deadline;
        $date.find("i.fa-times-circle").show ();
      }
    },

    // METHOD resetDeadline ()
    resetDeadline: function ()
    {
      this.element.removeClass ("obsolete");
      this.setDeadline (_defaultString);
    },

    // METHOD setCreationDate ()
    setCreationDate: function (date)
    {
      this.element.find(".dates .creation span").text (date.trim ());
    },

    // METHOD setTitle ()
    setTitle: function (title)
    {
      this.element.find(".postit-header span.title")
        .html (wpt_noHTML(title) || _defaultString);
    },

    // METHOD setContent ()
    setContent: function (content)
    {
      //FIXME Optimize with postit versioning
      const $postit = this.element,
            c1 = $postit.find("div.postit-edit").html (),
//FIXME
//            c2 = content.trim().replace (/^<(br|p)\/>/ig, "");
            c2 = content;

      if (c1 !== c2)
        $postit.find("div.postit-edit").html (c2);
    },

    // METHOD setPosition ()
    setPosition: function (args)
    {
      const postit = this.element[0];

      if (args.cellId)
        this.settings.cellId = args.cellId;

      postit.style.top = args.top + "px";
      postit.style.left = args.left + "px";
    },

    // METHOD fixPosition ()
    fixPosition: function (cPos, cH, cW)
    {
       const $postit = this.element,
             postit = $postit[0],
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
    getClassColor: function ()
    {
      const classe = this.element[0].className.match(/color\-[a-z]+/);

      return (classe && classe.length) ? classe[0] : _defaultClassColor;
    },

    // METHOD setClassColor ()
    setClassColor: function (newClass, $item)
    {
       const item = ($item) ? $item[0] : this.element[0],
             classes = item.className.replace(/color\-[a-z]+/, "");

       item.className = classes+" "+newClass;
    },

    // METHOD setPopupColor ()
    setPopupColor: function ($popup)
    {
      const classe = this.getClassColor ();

      this.setClassColor (classe, $popup.find(".modal-header"));
      this.setClassColor (classe, $popup.find(".modal-title"));
      this.setClassColor (classe, $popup.find(".modal-footer"));
    },

    // METHOD setAttachmentsCount ()
    setAttachmentsCount: function (count)
    {
      this.element.find(".attachmentscount")
        .css("display", (count) ? "inline-block": "none")
        .find("span").text (count);
    },

    //TODO Attachments plugin?
    // METHOD getAttachmentTemplate ()
    getAttachmentTemplate: function (item, noWriteAccess)
    {
      const tz = wpt_userData.settings.timezone,
            w = `<button type="button" class="item-right-icon"><i class="fas fa-trash fa-xs fa-fw" data-action="delete"></i></button>`,
            c = (item.ownerid && item.ownerid != wpt_userData.id) ?
                  `<span>${item.ownername}</span>` : '';

      return `<li data-id="${item.id}" data-url="${item.link}" data-fname="${wpt_htmlQuotes(item.name)}" class="list-group-item list-group-item-action"><div><i class="fa fa-lg ${item.icon} fa-fw"></i> ${item.name} <div class="item-infos"><span class="creationdate">${wpt_getUserDate (item.creationdate)}</span><span class="file-size">${wpt_getHumanSize(item.size)}</span>${c}</div>${noWriteAccess?'':w}</li>`;
    },

    // METHOD getAttachmentsCount ()
    getAttachmentsCount: function ()
    {
      return parseInt (this.element.find(".attachmentscount span").text ());
    },

    // METHOD displayAttachments ()
    displayAttachments: function ()
    {
      const $postit = this.element,
            writeAccess = wpt_checkAccess ("<?=WPT_RIGHTS['walls']['rw']?>");

      wpt_request_ajax (
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
            body = "<?=_("This post-it has no attachment")?>";
          else
          {
            for (let i = 0, flen = d.length; i < flen; i++)
              body += this.getAttachmentTemplate (d[i], !writeAccess);
          }

          if (writeAccess)
            $_attachmentsPopup.find(".btn-primary").show ();
          else
            $_attachmentsPopup.find(".btn-primary").hide ();

          $_attachmentsPopup.find(".modal-body ul").html (body);

          $_attachmentsPopup[0].dataset.noclosure = true; 

          wpt_openModal ($_attachmentsPopup);
        }
      );
    },

    // METHOD incAttachmentsCount ()
    incAttachmentsCount: function ()
    {
      this.setAttachmentsCount (this.getAttachmentsCount () + 1);
    },

    // METHOD decAttachmentsCount ()
    decAttachmentsCount: function ()
    {
      this.setAttachmentsCount (this.getAttachmentsCount () - 1);
    },

    // METHOD uploadAttachment ()
    uploadAttachment: function ()
    {
      $(".upload.postit-attachment").click ();
    },

    // METHOD setCurrent ()
    setCurrent: function ()
    {
      wpt_sharer.reset ("postit");

      this.element.addClass("current")
    },

    // METHOD unsetCurrent ()
    unsetCurrent: function ()
    {
      wpt_sharer.reset ("postit");

      this.element.removeClass ("current");
    },

    // METHOD openDatePicker ()
    openDatePicker: function ()
    {
      const plugin = this,
            $postit = plugin.element;

      $postit.prepend (
        $("<input type='text' class='date-picker'>")
          .datepicker ({
            dateFormat: "yy-mm-dd",
            minDate: moment().tz(wpt_userData.settings.timezone).add (1, "days").format("Y-MM-DD")
          }));
      
      const $datePicker = $postit.find (".date-picker");
      $datePicker
        .on("change", function()
        {
          $postit[0].dataset.updatetz = true;
          $postit.removeClass ("obsolete");
          $("#popup-layer").trigger ("click");
        });
      
      if ($postit[0].dataset.deadline)
        $datePicker.datepicker ("setDate", $postit[0].dataset.deadline);
      
      wpt_openPopupLayer (() =>
        {
          plugin.removeDatePicker ();
          plugin.unedit ();
        });

      $datePicker.datepicker ("show");
      $datePicker.blur ();
    },

    // METHOD removeDatePicker ()
    removeDatePicker: function ()
    {
      const $postit = this.element,
            $datePicker = $postit.find(".date-picker");

      if ($datePicker.length)
      {
        const v = $datePicker.val ();

        $postit[0].dataset.deadline = v;
        this.setDeadline ((v) ? v : _defaultString);

        if (v)
          $postit[0].removeAttribute ("data-deadlineepoch");

        $datePicker.datepicker ("destroy");
        $datePicker.remove ();
        $(".ui-datepicker").remove ();
        $postit.trigger ("mouseleave");
      }
    },

    // METHOD insert ()
    insert: function ()
    {
      const $postit = this.element,
            data = this.serialize()[0];

      wpt_request_ws (
        "PUT",
        "wall/"+this.settings.wallId+
        "/cell/"+this.settings.cellId+"/postit",
        data,
        // success cb
        (d) =>
        {
          if (d.error_msg)
            wpt_displayMsg ({
              type: "warning",
              msg: d.error_msg
            });

          $postit.remove ();
        },
        // error cb
        (d) =>
        {
         //FIXME factorisation (cf. wpt_request_ws ())
          wpt_displayMsg ({
            type: "danger",
            msg: (isNaN (d.error)) ?
              d.error : "<?=_("Unknown error.<br>Please try again later.")?>"
          });

          $postit.remove ();
        });
    },

    // METHOD update ()
    update: function (d, cell)
    {
      const $postit = this.element,
            tz = wpt_userData.settings.timezone;

      // Change postit cell
      if (cell && cell.id != this.settings.cellId)
      {
        this.settings.cell =
          cell.obj || this.settings.wall.find("[data-id='cell-"+cell.id+"']");
        this.settings.cellId = cell.id;

        $postit.appendTo (this.settings.cell);
      }

      if (!d.ignoreResize)
        $postit.css ({
          top: d.top,
          left: d.left,
          width: d.width,
          height: d.height
        });

      this.setClassColor (d.classcolor);

      this.setTitle (d.title);

      this.setContent (d.content);

      this.setAttachmentsCount (d.attachmentscount);

      this.setCreationDate (d.creationdate?wpt_getUserDate (d.creationdate):'');

      this.setDeadline (d.deadline, d.timezone);

      if (!d.obsolete)
        $postit.removeClass ("obsolete");

      if (!d.tags)
        d.tags = "";
      $postit[0].dataset.tags = d.tags;

      $postit.find(".postit-tags").html (
        $(".tag-picker").wpt_tagPicker ("getHTMLFromString", d.tags));

      $(".tag-picker").wpt_tagPicker ("refreshPostitDataTag", $postit);

      this.repositionPlugs ();
    },

    // METHOD delete ()
    delete: function ()
    {
      wpt_sharer.reset ();

      this.element[0].dataset.todelete = true;
    },

    // METHOD deleteAttachment ()
    deleteAttachment: function ()
    {
      const $postit = this.element,
            $attachment = $_attachmentsPopup.find("li.todelete");

      wpt_request_ws (
        "DELETE",
        "wall/"+this.settings.wallId+
          "/cell/"+this.settings.cellId+"/postit/"+this.settings.id+
            "/attachment/"+$attachment[0].dataset.id,
        null,
        // success cb
        (d) =>
        {
          if (d.error_msg)
            wpt_raiseError (null, d.error_msg);
          else
          {
            $attachment.remove ();
            this.decAttachmentsCount ();
  
            if (!$_attachmentsPopup
                   .find("ul.list-group li:first-child").length)
            {
              $_attachmentsPopup.find("ul.list-group").html (
                "<?=_("This post-it has no attachment")?>");
            }
          }
        }
      );
    },

    // METHOD edit ()
    edit: function (args, success_cb, error_cb)
    {
      const data = {cellId: this.settings.cellId};

      if (!args)
        args = {};

      if (!args.plugend)
      {
        this.setCurrent ();

        _originalObject = this.serialize()[0];
      }

      if (!this.settings.wall[0].dataset.shared ||
          !wpt_checkAccess ("<?=WPT_RIGHTS['walls']['rw']?>"))
        return success_cb && success_cb ();

      wpt_request_ws (
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
            wpt_raiseError (() =>
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
    unedit: function (args = {})
    {
      const $postit = this.element,
            plugsToSave = wpt_sharer.get ("plugs-to-save");
      let data = null,
          todelete;

      if (!wpt_checkAccess ("<?=WPT_RIGHTS['walls']['rw']?>"))
        return this.cancelEdit (args);

      if (!args.plugend)
      {
        this.unsetCurrent ();

        // Update postits plugs dependencies
        if (plugsToSave)
        {
          data = {updateplugs: true, plugs: []};

          for (const id in plugsToSave)
            data.plugs.push (plugsToSave[id].wpt_postit ("serialize")[0]);

          wpt_sharer.unset ("plugs-to-save");
        }
        // Postit update
        else
        {
          data = this.serialize()[0];
          todelete = !!data.todelete;

          // Update postit only if it has changed
          if (todelete || wpt_updatedObject (_originalObject,
                                             data, {hadpictures: 1}))
            data["cellId"] = this.settings.cellId;
          else if (!this.settings.wall[0].dataset.shared)
            return this.cancelEdit ();
          else
            data = null;
        }
      }

      wpt_request_ws (
        "DELETE",
        "wall/"+this.settings.wallId+"/editQueue/postit/"+this.settings.id,
        data,
        // success cb
        (d) =>
        {
          this.cancelEdit (args);

          if (d.error_msg)
            wpt_displayMsg ({
              type: "warning",
              msg: d.error_msg
            });
          else if (todelete)
          {
            $postit.remove ();
            $("#postitsSearchPopup").wpt_postitsSearch ("replay");
          }
          else if (data && data.updatetz)
            $postit[0].removeAttribute ("data-updatetz");
        },
        // error cb
        () => this.cancelEdit (args));
    },

    // METHOD cancelEdit ()
    cancelEdit: function (args = {})
    {
      $("body").css ("cursor", "auto");

      if (!args.plugend)
      {
        this.unsetCurrent ();

        this.element[0].removeAttribute ("data-hasuploadedpictures");
        this.element[0].removeAttribute ("data-hadpictures");

        if (!this.settings.wall)
          wpt_raiseError (null, "<?=_("The entire column was deleted while you were editing the post-it!")?>");
      }
    },

    // METHOD openMenu ()
    openMenu: function ()
    {
      const $postit = this.element;

      if (!$postit.find(".postit-menu.on").length)
        $postit.find(".postit-header [data-action='menu']").trigger ("click");
    },

    // METHOD closePlugMenu ()
    closePlugMenu: function ()
    {
      const $postit = this.element;

      if ($postit.find(".postit-menu.on").length)
        $postit.find(".postit-menu .dropdown-menu").dropdown ("hide");
    },

    // METHOD closeMenu ()
    closeMenu: function ()
    {
      const $postit = this.element;

      if ($postit.find(".postit-menu.on").length)
        $postit.find(".postit-header [data-action='menu']").trigger ("click");
    }

  };

  /////////////////////////// AT LOAD INIT //////////////////////////////

  if (!document.querySelector ("body.login-page"))
    $(function()
      {
        $_attachmentsPopup = $("#postitAttachmentsPopup");

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
          language: (locale != "en_US") ? locale : null,
          branding: false,
          plugins: "autoresize link image charmap hr searchreplace visualchars fullscreen insertdatetime",

          // "image" plugin options
          image_description: false,
          automatic_uploads: true,
          file_picker_types: "image",
          file_picker_callback: function (callback, value, meta)
          {
            wpt_sharer.set ("tinymce-callback", callback);
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
          statusbar: false
        });

        // EVENT click on plug label
        $(document).on ("click", ".plug-label li", function ()
          {
            const $item = $(this),
                  $label = $item.closest("div"),
                  $popup = $("#plugPopup"),
                  $wall = wpt_sharer.getCurrent ("wall"),
                  [startId, endId] = $label[0].dataset.id.split ("-"),
                  $start = $wall.find(".postit[data-id='postit-"+startId+"']"),
                  $end = $wall.find(".postit[data-id='postit-"+endId+"']"),
                  defaultLabel = wpt_htmlQuotes ($label.find("span").text ());

            function __unedit ()
            {
              let toSave = {};

              toSave[startId] = $start;
              toSave[endId] = $end

              wpt_sharer.set ("plugs-to-save", toSave);
              $start.wpt_postit ("unedit");
            }

            switch ($item[0].dataset.action)
            {
              case "rename":

                $start.wpt_postit ("edit", null, ()=>
                  {
                    wpt_openConfirmPopover ({
                      type: "update",
                      item: $label,
                      title: `<i class="fas fa-bezier-curve fa-fw"></i> <?=_("Relationship name")?>`,
                      content: `<input type="text" class="form-control form-control-sm" value="${defaultLabel}" maxlength="<?=Wpt_dbCache::getFieldLength('postits_plugs', 'label')?>">`,
                      cb_close: __unedit,
                      cb_ok: ($popover) =>
                        {
                          const label = $popover.find("input").val().trim ();

                          if (label != defaultLabel)
                            $start.wpt_postit ("updatePlugLabel", {
                              label: $popover.find("input").val().trim(),
                              endId: endId
                            });
                        }
                    });
                  });

                break;

              case "delete":

                $start.wpt_postit ("edit", null, ()=>
                  {
                    wpt_openConfirmPopover ({
                      item: $label,
                      placement: "left",
                      title: `<i class="fas fa-trash fa-fw"></i> <?=_("Delete")?>`,
                      content: "<?=_("Delete this relationship?")?>",
                      cb_close: __unedit,
                      cb_ok: () =>
                        {
                          $start.wpt_postit ("removePlug", startId+"-"+endId);
                          $start.wpt_postit ("resetPlugsUndo");
                        }
                    });
                  });

                break;
            }
          });

      $(`<input type="file" class="upload postit-attachment">`)
        .on("change", function (e)
        {
          const $upload = $(this),
                $postit = wpt_sharer.getCurrent("postit"),
                settings = $postit.wpt_postit ("getSettings");

          if (e.target.files && e.target.files.length)
          {
            wpt_getUploadedFiles (e.target.files,
              (e, file) =>
              {
                $upload.val ("");

                if ($_attachmentsPopup.find(
                      ".list-group li[data-fname='"+
                        wpt_htmlQuotes(file.name)+"']").length)
                  return wpt_displayMsg ({
                           type: "warning",
                           msg: "<?=_("The file is already linked to the post-it")?>"
                         });

                if (wpt_checkUploadFileSize ({size: e.total}) &&
                    e.target.result)
                {
                  wpt_request_ajax (
                    "PUT",
                    "wall/"+settings.wallId+
                      "/cell/"+settings.cellId+"/postit/"+
                        settings.id+"/attachment",
                    {
                      name: file.name,
                      size: file.size,
                      type: file.type,
                      content: e.target.result
                    },
                    // success cb
                    (d) =>
                    {
                      const $body = $_attachmentsPopup.find("ul.list-group");

                      $_attachmentsPopup.find(".modal-body").scrollTop (0);

                      if (d.error_msg)
                        return wpt_displayMsg ({
                                 type: "warning",
                                 msg: d.error_msg
                               });
    
                      if (!$body.find("li").length)
                        $body.html ("");
    
                      $body.prepend (
                        $postit.wpt_postit ("getAttachmentTemplate", d));

                      $postit.wpt_postit("incAttachmentsCount");
                    });
                }
              });
          }
        }).appendTo ("body");

        $(`<input type="file" accept=".jpeg,.jpg,.gif,.png"
            class="upload postit-picture">`)
          .on("change", function ()
          {
            const $upload = $(this);

            function __error_cb (d)
            {
             if (d)
                wpt_displayMsg ({
                  type: "warning",
                  msg: d.error||d
                });
            }

            wpt_getUploadedFiles (
              this.files,
              (e, file) =>
                {
                  $upload.val ("");

                  if (wpt_checkUploadFileSize ({
                        size: e.total,
                        cb_msg: __error_cb
                      }) && e.target.result)
                  {
                    const wallId = wpt_sharer.getCurrent("wall")
                                     .wpt_wall("getId"),
                          $postit = wpt_sharer.getCurrent ("postit"),
                          postitId = $postit.wpt_postit ("getId"),
                          cellId = $postit.wpt_postit ("getCellId");

                    wpt_request_ajax (
                      "PUT",
                      "wall/"+wallId+"/cell/"+cellId+"/postit/"+postitId+
                        "/picture",
                      {
                        name: file.name,
                        size: file.size,
                        type: file.type,
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

                          wpt_sharer.get("tinymce-callback")(d.link);

                          setTimeout(()=>
                          {
                            if (!$f.find("input:eq(0)").val ())
                              __error_cb ("<?=_("Sorry, there is a compatibility issue with your browser (Safari?) when it comes to uploading post-its images...")?>");
                          }, 0);
                        },
                        __error_cb
                    );
                  }
                },
                null,
                __error_cb);

          }).appendTo("body");

        $(document).on("click", "#postitAttachmentsPopup .modal-body li button",
          function (e)
          {
            const $item = $(this).closest ("li");
      
            e.stopImmediatePropagation ();
      
            $item.addClass ("active todelete");

            wpt_openConfirmPopover ({
              item: $(this),
              placement: "left",
              title: `<i class="fas fa-trash fa-fw"></i> <?=_("Delete")?>`,
              content: "<?=_("Delete this file?")?>",
              cb_close: () =>
                $(".modal").find("li.list-group-item.active")
                  .removeClass ("active todelete"),
              cb_ok: () =>
                wpt_sharer.getCurrent ("postit").wpt_postit ("deleteAttachment")
            });
          });
    
        $(document).on("click", "#postitAttachmentsPopup .modal-body li",
          function (e)
          {
            wpt_download ($(this)[0].dataset);
          });    
      });

<?php echo $Plugin->getFooter ()?>
