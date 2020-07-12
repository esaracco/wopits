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

  /////////////////////////// PUBLIC METHODS ////////////////////////////

  Plugin.prototype =
  {
    // METHOD init ()
    init: function ()
    {
      const plugin = this,
            $postit = plugin.element,
            settings = plugin.settings,
            $wall = settings.wall,
            writeAccess = H.checkAccess (
              "<?=WPT_RIGHTS['walls']['rw']?>", settings.access);

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
            const $oldPostit = S.get ("postit-oldzindex");

            if ($oldPostit &&
                $oldPostit.obj.postit ("getId") != settings.id)
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
        // EVENTS mouseleave focusout
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
          });

      if (settings.obsolete)
        $postit.addClass ("obsolete");

      $postit.find(".postit-edit,.postit-header,.dates")
      .on("click", function (e)
      {
        const id = plugin.settings.id,
              from = S.get ("link-from");

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

                $start.postit ("addPlug", line);
                $start.postit ("cancelPlugAction", false);

                from.cancelCallback = () =>
                  {
                    $start.postit ("removePlug", line);
                    $start.postit ("cancelPlugAction");
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

                    $start.postit ("applyTheme");
                    $start.postit ("addPlugLabel", line);

                    $start[0].dataset.undo = "add";
                    $undo.removeClass ("disabled");
                    $undo.find("span").text ("« <?=_("Add")?> »");

                    $start.postit ("cancelPlugAction");
                  };

                H.cleanPopupDataAttr ($popup);

                S.set ("link-from", from);

                H.openModal ($popup);
              });
          }
          else
          {
            if (from.id != id)
              H.displayMsg ({
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
        $postit.find(".postit-menu [data-action='edit']").click ());

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
              const postit = $postit[0];
  
              S.set ("revertData", {
                revert: false,
                width: postit.clientWidth,
                height: postit.clientHeight
              });

              plugin.edit (
                {ignoreResize: true},
                null,
                ()=> S.get("revertData").revert = true);
            },
          stop: function(e, ui)
            {
              const revertData = S.get ("revertData");

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
        }
  
      const $header = $(`
        <i class="far fa-caret-square-down" data-action="menu"></i>`)
        .on("click", function(e)
          {
            const $btn = $(this),
                  id = settings.id,
                  $menu = $postit.find (".postit-menu"),
                  $header = $btn.closest (".postit-header");

            if (!$menu.hasClass ("on"))
            {
              $wall.wall ("closeAllMenus");

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
              $postit.find(".postit-delete").show ();
            }
            else
            {
              $header.removeClass ("menu");
              $btn.switchClass ("fas", "far");
              $menu
                .removeClass("on")
                .hide ();
              $postit.find(".postit-delete").hide ();
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

            // To prevent race condition with draggable & resizable plugins.
            if (S.get ("block-edit"))
              return;

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

              H.cleanPopupDataAttr ($popup);

              $popup.find(".modal-body").html ((content) ?
                content : "<i><?=_("No content.")?></i>");

              $popup.find(".modal-title").html (
                `<i class="fas fa-sticky-note"></i> ${title}`);

              H.openModal ($popup, _getMaxEditModalWidth (content));
            }
            // Open modal with write rights
            else
            {
              //FIXME quand drag speed et click speed apres
              if (S.get ("link-from"))
                plugin.cancelPlugAction (true, false);

              plugin.edit (null, () =>
                {
                  switch ($btn[0].dataset.action)
                  {
                    case "delete":

                      H.openConfirmPopover ({
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

                      S.set ("postit-data", {
                        title: title != _defaultString ? title : ""
                      });
    
                      $("#postitUpdatePopupTitle")
                        .val (S.get("postit-data").title);

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
                        H.fixVKBScrollStart ();

                      H.openModal ($popup, _getMaxEditModalWidth (content));
    
                      break;
    
                    // OPEN tags picker
                    case "tag-picker":

                      $(".tag-picker").tagPicker ("open", e);

                      break;
    
                    // OPEN color picker
                    case "color-picker":

                      $(".color-picker").colorPicker ("open", e);

                      break;
    
                    // OPEN date picker
                    case "date-picker":

                      $("#datePickerPopup").datePicker ("open");

                      break;
                  }
              });
            }
          });

        // Menu submenus events
        $menu.find("ul.dropdown-menu li")
          .on("click", function(e, d)
          {
            const $item = $(this);

            e.stopImmediatePropagation ();

            plugin.closePlugMenu ();

            // Nothing if item menu is disabled (can change dynamically)
            if ($item.find("a").hasClass ("disabled")) return;

            e = d||e;

            switch (this.dataset.action)
            {
              case "add-plug":

                plugin.edit (null, () =>
                  {
                    $(document)
                      .off("keydown", _plugRabbit.escapeEvent)
                      .on ("keydown", _plugRabbit.escapeEvent);
      
                    _plugRabbit.line = new LeaderLine (
                      $postit[0],
                      $(`<div id="plug-rabbit" style="position:absolute;left:${e.clientX}px;top:${e.clientY}px"></div>`).prependTo("body")[0],
                      {
                        size: 3,
                        color: "#9b9c9c",
                        dash: true
                      });
       
                    $("body")
                      .off("mousemove", _plugRabbit.mouseEvent)
                      .on("mousemove", _plugRabbit.mouseEvent);
      
                    S.set ("link-from", {id: settings.id, obj: $postit});
                  });

                break;

              case "delete-plugs":

                plugin.edit (null, () =>
                  {
                    H.openConfirmPopover ({
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
                          H.displayMsg ({
                            type: "warning",
                            msg: "<?=_("This item has been deleted")?>"
                          });
                      });

                      S.set ("plugs-to-save", toSave);

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
              $postit.find(".postit-menu [data-action='attachments']").click ();
            else
              plugin.edit (null, () => plugin.displayAttachments ());
          });
  
      const $tags = $(`<div class="postit-tags">${settings.tags?$(".tag-picker").tagPicker ("getHTMLFromString", settings.tags):''}</div>`);

      if (writeAccess)
        $tags.on("mousedown",
          function (e)
          {
            e.stopImmediatePropagation ();

            plugin.edit (null, () => $(".tag-picker").tagPicker ("open", e));
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
                $menu.find ("i.fa-hourglass-end").click ();
            });

      if (settings.creationdate)
        setTimeout (
          () => plugin.update (settings), (!!settings.isNewCell) ? 150 : 0);
    },

    // METHOD displayDeadlineAlert ()
    displayDeadlineAlert: function ()
    {
      const data = this.element[0].dataset;
      let content;

      // Scroll to the to the post-it if needed.
      H.setViewToElement (this.element);

      H.waitForDOMUpdate (()=>
      {
        if (!data.deadlineepoch)
          content = "<?=_("The deadline for this post-it has been removed!")?>";
        else if (this.element.hasClass ("obsolete"))
          content = "<?=_("This post-it has expired.")?>";
        else
        {
          const a = moment.unix (data.deadlineepoch),
                b = moment (new Date ());
          let days = moment.duration(a.diff(b)).asDays ();

          if (days % 1 > 0)
            days = Math.trunc(days) + 1;

          content = (days > 1) ?
            "<?=_("The deadline for this post-it will expire in about %s day(s).")?>".replace("%s", days) :
            "<?=_("The deadline for this post-it will expire soon.")?>";
        }

        H.openConfirmPopover ({
          type: "info",
          item: this.element,
          title: `<i class="fa fa-exclamation-triangle fa-fw"></i> <?=_("Post-it deadline alert")?>`,
          content: content
        });
      });
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
        $("#plug-rabbit").remove ();
      }

      if (full)
      {
        if (unedit)
          S.get("link-from").obj.postit ("unedit");

        S.unset ("link-from");
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
      const line = new LeaderLine (
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
                      $(".wall th:eq(0)").css ("background-color"),
                middleLabel: LeaderLine.captionLabel ({
                  text: label,
                  fontSize:"13px"
                })
              });

      line.dom = document.querySelector ("svg.leader-line:last-child");

      return line;
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
          $(this).postit ("applyThemeToPlugs");
        });
    },

    // METHOD resetPlugsUndo ()
    resetPlugsUndo: function ()
    {
      this.settings.wall.find(".postit").each (function ()
        {
          this.dataset.undo = "";

          $(this).find(".postit-menu [data-action='undo-plug'] a")
            .addClass("disabled")
            .find("span").text ("");
        });
    },

    // METHOD checkPlugsMenu ()
    checkPlugsMenu: function ()
    {
      const $menu = this.element.find (
        ".postit-menu li[data-action='delete-plugs'] .dropdown-item");

      if (this.havePlugs ())
        $menu.removeClass ("disabled");
      else
        $menu.addClass ("disabled");
    },

    // METHOD updatePlugLabel ()
    updatePlugLabel: function (args)
    {
      const label = H.noHTML (args.label);

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
      const labelId = plug.startId+"-"+plug.endId,
            $div = this.settings.plugsContainer;

      if ($svg)
        $svg.appendTo ($div);
      else
        $svg = $div.find ("svg.leader-line[data-id='"+labelId+"']");

      const $text = $svg.find ("text"),
            pos = $text.position ();

      if (pos)
      {
        const writeAccess = H.checkAccess (
                "<?=WPT_RIGHTS['walls']['rw']?>", this.settings.access),
              menu = `<ul class="dropdown-menu border-0 shadow"><li data-action="rename"><a class="dropdown-item" href="#"><i class="fa-fw fas fa-edit"></i> <?=_("Rename")?></a></li><li data-action="delete"><a class="dropdown-item" href="#"><i class="fa-fw fas fa-trash"></i> <?=_("Delete")?></a></li></ul>`;

        plug.labelObj = $(`<div class="plug-label nav-item dropdown submenu line-menu" data-id="${labelId}" style="top:${pos.top}px;left:${pos.left}px"><a href="#" ${writeAccess?'data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"':""} class="dropdown-toggle"><span>${plug.label != "..." ? H.noHTML (plug.label) : '<i class="fas fa-ellipsis-h"></i>'}</span></a>${writeAccess?menu:""}</div>`).appendTo ($div)
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
      $end.postit("getSettings")._plugs.push (plug);
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
    hidePlugs: function ()
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
    showPlugs: function ()
    {
      if (!this.settings.wall) return;

      this.element.find(".postit-menu [data-action='plug']").show ();

      this.settings._plugs.forEach ((plug) =>
        {
          plug.obj.show ();
          plug.labelObj.show ();
        });
    },

    // METHOD repositionPlugs ()
    repositionPlugs: function ()
    {
      const div = this.settings.plugsContainer[0];

      this.settings._plugs.forEach ((plug) =>
        {
          plug.obj.position ();

          const p = div.querySelector("svg.leader-line[data-id='"+
            plug.startId+"-"+plug.endId+"'] text").getBoundingClientRect ();

          plug.labelObj[0].style.top = p.top+"px";
          plug.labelObj[0].style.left = p.left+"px";
        });
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
            ret[plug.endId] = (plug.label == _defaultString) ?
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
              p = this,
              postitId = p.dataset.id.substring (7);
        let data = {};

        if (p.dataset.todelete)
          data = {id: postitId, todelete: true};
        else
        {
          const title = $p.find(".postit-header span.title").html (),
                classcolor = p.className.match(/(color\-[a-z]+)/),
                deadline = (p.dataset.deadlineepoch) ?
                  p.dataset.deadlineepoch :
                  $p.find(".dates .end span").text().trim ();
          let tags = [];

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
            alertshift: (p.dataset.deadlinealertshift !== undefined) ?
                          p.dataset.deadlinealertshift : null,
            updatetz: p.dataset.updatetz || null,
            obsolete: $p.hasClass ("obsolete"),
            attachmentscount: $p.find(".attachmentscount span").text (),
            plugs: $p.postit ("serializePlugs"),
            hadpictures: !!p.dataset.hadpictures,
            hasuploadedpictures: !!p.dataset.hasuploadedpictures
          };
        }

        postits.push (data);
      });

      return postits;
    },

    // METHOD setDeadline ()
    setDeadline: function (args)
    {
      const postit = this.element[0],
            $date = this.element.find(".dates .end"),
            {deadline, alertshift, timezone} = args;
      let human;

      if (!deadline || isNaN (deadline))
        human = deadline||_defaultString;
      else
        human = (deadline) ? H.getUserDate(deadline, timezone) : _defaultString;

      $date.find("span").text (human);

      if (!H.checkAccess ("<?=WPT_RIGHTS['walls']['rw']?>") ||
          human == _defaultString)
      {
        postit.removeAttribute ("data-deadline");
        postit.removeAttribute ("data-deadlinealertshift");
        postit.removeAttribute ("data-deadlineepoch");
        postit.removeAttribute ("data-updatetz");

        $date.find("i.fa-times-circle").hide ();
      }
      else
      {
        postit.dataset.deadline = human;
        postit.dataset.deadlineepoch = deadline;
        if (alertshift !== undefined && alertshift !== null)
          postit.dataset.deadlinealertshift = alertshift;

        $date.find("i.fa-times-circle").show ();
      }
    },

    // METHOD resetDeadline ()
    resetDeadline: function ()
    {
      this.element.removeClass ("obsolete");
      this.setDeadline ({deadline: _defaultString});
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
        .html (H.noHTML(title) || _defaultString);
    },

    // METHOD setContent ()
    setContent: function (newContent)
    {
      if (newContent !== this.element.find("div.postit-edit").html ())
        this.element.find("div.postit-edit").html (newContent);
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

      return `<li data-id="${item.id}" data-url="${item.link}" data-fname="${H.htmlQuotes(item.name)}" class="list-group-item list-group-item-action"><div><i class="fa fa-lg ${item.icon} fa-fw"></i> ${item.name} <div class="item-infos"><span class="creationdate">${H.getUserDate (item.creationdate)}</span><span class="file-size">${H.getHumanSize(item.size)}</span>${c}</div>${noWriteAccess?'':w}</li>`;
    },

    // METHOD getAttachmentsCount ()
    getAttachmentsCount: function ()
    {
      return parseInt (this.element.find(".attachmentscount span").text ());
    },

    // METHOD displayAttachments ()
    displayAttachments: function ()
    {
      const writeAccess = H.checkAccess ("<?=WPT_RIGHTS['walls']['rw']?>");

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

          H.openModal ($_attachmentsPopup);
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
      S.reset ("postit");

      this.element.addClass ("current")
    },

    // METHOD unsetCurrent ()
    unsetCurrent: function ()
    {
      S.set ("block-edit", true);
      setTimeout (()=>
        {
          S.reset ("postit");
          this.element.removeClass ("current");
          S.unset ("block-edit");

        }, 500);
    },

    // METHOD insert ()
    insert: function ()
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
    update: function (d, cell)
    {
      const $postit = this.element,
            $tagPicker = $(".tag-picker"),
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

      this.setCreationDate (d.creationdate?H.getUserDate (d.creationdate):'');

      this.setDeadline (d);

      if (!d.obsolete)
        $postit.removeClass ("obsolete");

      if (!d.tags)
        d.tags = "";
      $postit[0].dataset.tags = d.tags;

      $postit.find(".postit-tags").html (
        $tagPicker.tagPicker ("getHTMLFromString", d.tags));

      $tagPicker.tagPicker ("refreshPostitDataTag", $postit);

      this.repositionPlugs ();
    },

    // METHOD delete ()
    delete: function ()
    {
      S.reset ();

      this.element[0].dataset.todelete = true;
    },

    // METHOD deleteAttachment ()
    deleteAttachment: function ()
    {
      const $attachment = $_attachmentsPopup.find ("li.todelete");

      H.request_ws (
        "DELETE",
        "wall/"+this.settings.wallId+
          "/cell/"+this.settings.cellId+"/postit/"+this.settings.id+
            "/attachment/"+$attachment[0].dataset.id,
        null,
        // success cb
        (d) =>
        {
          if (d.error_msg)
            H.raiseError (null, d.error_msg);
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
          !H.checkAccess ("<?=WPT_RIGHTS['walls']['rw']?>"))
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
    unedit: function (args = {})
    {
      const $postit = this.element,
            plugsToSave = S.get ("plugs-to-save");
      let data = null,
          todelete;

      if (!H.checkAccess ("<?=WPT_RIGHTS['walls']['rw']?>"))
        return this.cancelEdit (args);

      if (!args.plugend)
      {
        this.unsetCurrent ();

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
    cancelEdit: function (args = {})
    {
      $("body").css ("cursor", "auto");

      if (!args.plugend)
      {
        this.unsetCurrent ();

        this.element[0].removeAttribute ("data-hasuploadedpictures");
        this.element[0].removeAttribute ("data-hadpictures");

        if (!this.settings.wall)
          H.raiseError (null, "<?=_("The entire column was deleted while you were editing the post-it!")?>");
      }
    },

    // METHOD openMenu ()
    openMenu: function ()
    {
      if (!this.element.find(".postit-menu.on").length)
        this.element.find(".postit-header [data-action='menu']").click ();
    },

    // METHOD closePlugMenu ()
    closePlugMenu: function ()
    {
      if (this.element.find(".postit-menu.on").length)
        this.element.find(".postit-menu .dropdown-menu").dropdown ("hide");
    },

    // METHOD closeMenu ()
    closeMenu: function ()
    {
      if (this.element.find(".postit-menu.on").length)
        this.element.find(".postit-header [data-action='menu']").click ();
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
                          .then (null, ()=>
                          {
                            c = c.replace(new RegExp (H.quoteRegex(img)), "");

                            editor.setContent (c);

                            // Return to the top of the modal if mobile device
                            if ($.support.touch)
                              $("#postitUpdatePopup").scrollTop (0);

                            H.displayMsg ({
                              type: "warning",
                              msg: "<?=_("The image %s was not available! It has been removed from post-it content.")?>".replace("%s", `«&nbsp;<i>${src}</i>&nbsp;»`)
                            });
                          })
                          .finally (()=> H.loader("hide"));
                      }
                    });
                }
              });
          },

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
          statusbar: false
        });

        // EVENT click on plug label
        $(document).on ("click", ".plug-label li", function ()
          {
            const $item = $(this),
                  $label = $item.closest("div"),
                  $popup = $("#plugPopup"),
                  $wall = S.getCurrent ("wall"),
                  [startId, endId] = $label[0].dataset.id.split ("-"),
                  $start = $wall.find(".postit[data-id='postit-"+startId+"']"),
                  $end = $wall.find(".postit[data-id='postit-"+endId+"']"),
                  defaultLabel = H.htmlQuotes ($label.find("span").text ());

            function __unedit ()
            {
              let toSave = {};

              toSave[startId] = $start;
              toSave[endId] = $end

              S.set ("plugs-to-save", toSave);
              $start.postit ("unedit");
            }

            switch ($item[0].dataset.action)
            {
              case "rename":

                $start.postit ("edit", null, ()=>
                  {
                    H.openConfirmPopover ({
                      type: "update",
                      item: $label,
                      title: `<i class="fas fa-bezier-curve fa-fw"></i> <?=_("Relationship name")?>`,
                      content: `<input type="text" class="form-control form-control-sm" value="${defaultLabel}" maxlength="<?=Wpt_dbCache::getFieldLength('postits_plugs', 'label')?>">`,
                      cb_close: __unedit,
                      cb_ok: ($popover) =>
                        {
                          const label = $popover.find("input").val().trim ();

                          if (label != defaultLabel)
                            $start.postit ("updatePlugLabel", {
                              label: $popover.find("input").val().trim(),
                              endId: endId
                            });
                        }
                    });
                  });

                break;

              case "delete":

                $start.postit ("edit", null, ()=>
                  {
                    H.openConfirmPopover ({
                      item: $label,
                      placement: "left",
                      title: `<i class="fas fa-trash fa-fw"></i> <?=_("Delete")?>`,
                      content: "<?=_("Delete this relationship?")?>",
                      cb_close: __unedit,
                      cb_ok: () =>
                        {
                          $start.postit ("removePlug", startId+"-"+endId);
                          $start.postit ("resetPlugsUndo");
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
                $postit = S.getCurrent ("postit"),
                settings = $postit.postit ("getSettings");

          if (e.target.files && e.target.files.length)
          {
            H.getUploadedFiles (e.target.files,
              (e, file) =>
              {
                $upload.val ("");

                if ($_attachmentsPopup.find(
                      ".list-group li[data-fname='"+
                        H.htmlQuotes(file.name)+"']").length)
                  return H.displayMsg ({
                           type: "warning",
                           msg: "<?=_("The file is already linked to the post-it")?>"
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
                      type: file.type,
                      content: e.target.result
                    },
                    // success cb
                    (d) =>
                    {
                      const $body = $_attachmentsPopup.find("ul.list-group");

                      $_attachmentsPopup.find(".modal-body").scrollTop (0);

                      if (d.error_msg)
                        return H.displayMsg ({
                                 type: "warning",
                                 msg: d.error_msg
                               });
    
                      if (!$body.find("li").length)
                        $body.html ("");
    
                      $body.prepend (
                        $postit.postit ("getAttachmentTemplate", d));

                      $postit.postit("incAttachmentsCount");
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
                H.displayMsg ({
                  type: "warning",
                  msg: d.error||d
                });
            }

            H.getUploadedFiles (
              this.files,
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

                          S.get("tinymce-callback")(d.link);

                          setTimeout(()=>
                          {
                            if (!$f.find("input:eq(0)").val ())
                              __error_cb ("<?=_("Sorry, there is a compatibility issue with your browser when it comes to uploading post-its images...")?>");
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

            H.openConfirmPopover ({
              item: $(this),
              placement: "left",
              title: `<i class="fas fa-trash fa-fw"></i> <?=_("Delete")?>`,
              content: "<?=_("Delete this file?")?>",
              cb_close: ()=> $(".modal").find("li.list-group-item.active")
                               .removeClass ("active todelete"),
              cb_ok: ()=> S.getCurrent("postit").postit ("deleteAttachment")
            });
          });
    
        $(document).on("click", "#postitAttachmentsPopup .modal-body li",
          function (e)
          {
            H.download ($(this)[0].dataset);
          });    
      });

<?php echo $Plugin->getFooter ()?>
