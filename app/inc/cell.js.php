<?php
/**
  Javascript plugin - Cell

  Scope: Wall
  Element: td
  Description: Wall's cell
*/

  require_once (__DIR__.'/../prepend.php');

  $Plugin = new Wopits\jQueryPlugin ('cell', 'width: 300, height: 200',
                                     'wallElement');
  echo $Plugin->getHeader ();

?>

  /////////////////////////// PUBLIC METHODS ////////////////////////////

  Plugin.prototype =
  {
    // METHOD init ()
    init ()
    {
      const plugin = this,
            $cell = plugin.element,
            settings = plugin.settings,
            cellId = settings.id,
            usersettings = settings.usersettings,
            $wall = settings.wall,
            writeAccess = plugin.canWrite ();
      // Coords of touchstart on touch devices
      let _coords = null;

      $cell.addClass (usersettings.displaymode||$wall[0].dataset.displaymode);

      // Add cell menu
      $cell.prepend (`<div class="cell-menu"><span class="btn btn-sm btn-secondary btn-circle"><i class="fas fa-sticky-note fa-fw"></i></span></div>`);

      if (writeAccess)
        $cell
          // Make cell DROPPABLE
          .droppable ({
            accept: ".postit",
            tolerance: "pointer",
            scope:"dzone",
            classes: {"ui-droppable-hover" : "droppable-hover"},
            drop: function (e, ui)
              {
                if (S.get("revertData").revert) return;

                const $target = $(this),
                      $postit = ui.draggable,
                      ptop = ui.offset.top - $target.offset().top,
                      pleft = ui.offset.left - $target.offset().left;
  
                $postit.postit ("setPosition", {
                  cellId: settings.id,
                  top: (ptop < 0) ? 0 : ptop,
                  left: (pleft < 0) ? 0 : pleft
                });
  
                $postit.appendTo ($target);
  
                $target.cell ("reorganize");
              }
          });

       $cell
        // Make cell resizable
        .resizable ({
          disabled: !writeAccess,
          autoHide: false,
          ghost: true,
          minWidth: settings.width,
          minHeight: settings.height,
          helper: "resizable-helper",
          resize:function(e, ui)
            {
              if (S.get("revertData").revert)
              {
                $("body")[0].removeAttribute ("style");

                return false;
              }
            },
          start: function(e, ui)
            {
              const $editable = $wall.find (".editable");

              // Cancel all editable (blur event is not triggered on resizing).
              if ($editable.length)
                $editable.editable ("cancelAll");

              S.set ("revertData", {
                revert: false,
                size: {
                  width: $cell.outerWidth (),
                  height: $cell.outerHeight ()
                }
              });

              plugin.edit (()=> S.get("revertData").revert = true);
            },
          stop:function(e, ui)
            {
              const revertData = S.get ("revertData");

              S.unset ("revertData");

              if (revertData.revert)
                $cell.css ({
                  width: revertData.size.width,
                  height: revertData.size.height
                });
              else
              {
                const absH = Math.abs (ui.size.height - ui.originalSize.height),
                      absW = Math.abs (ui.size.width - ui.originalSize.width);

                // Height
                if (absH < 2 || absH > 2)
                {
                  if ($wall[0].dataset.cols == 1 && $wall[0].dataset.rows == 1)
                    plugin.update ({
                      width: ui.size.width + 3,
                      height: ui.size.height
                    });
                  else
                  {
                    $cell.closest("tr").find("th:first-child")
                      .css("height", ui.size.height);

                    plugin.update ({
                      width: ui.size.width + 2,
                    });

                    // Set height for all cells of the current row
                    $wall.find("tbody tr:eq("+$cell.parent().index ()+") td")
                      .each (function ()
                      {
                        this.style.height = ui.size.height+"px";

                        this.querySelector("div.ui-resizable-e")
                          .style.height = (ui.size.height+2)+"px";
                      });
                  }
                }

                // Width
                if (absW < 2 || absW > 2)
                {
                  $wall.find("tbody tr").find("td:eq("+($cell.index()-1)+")")
                    .each (function ()
                    {
                      this.style.width = ui.size.width+"px";

                      this.querySelector("div.ui-resizable-s")
                        .style.width = (ui.size.width+2)+"px";
                    });

                  $wall.wall("fixSize", ui.originalSize.width, ui.size.width);
                }

                $wall.find("tbody td").cell ("reorganize");

                plugin.unedit ();
              }
            }
        });

       if (writeAccess)
       {
         const __dblclick = (e)=>
         {
           if (S.get ("zoom-level") ||
               (e.target.tagName != 'TD' &&
                 !e.target.classList.contains ("cell-list-mode")))
                return e.stopImmediatePropagation ();

           const cellOffset = $cell.offset (),
                 pTop = ((_coords && _coords.changedTouches) ?
                   _coords.changedTouches[0].clientY :
                   e.pageY) - cellOffset.top,
                 pLeft = ((_coords && _coords.changedTouches) ?
                   _coords.changedTouches[0].clientX :
                   e.pageX) - cellOffset.left;

           _coords = null;

           $wall.wall ("closeAllMenus");

           const $f = S.getCurrent ("filters");
           if ($f.is (":visible"))
             $f.filters ("reset");

           plugin.addPostit ({
             access: settings.access,
             item_top: pTop,
             item_left: pLeft - 15
           });
         };

         // Touch devices
         if ($.support.touch)
           $cell
            // EVENT touchstart on cell to retrieve touch coords
            .on("touchstart", function (e)
            {
              _coords = e;

              // Fix issue with some touch devices
              $(".navbar-nav,.dropdown-menu").collapse ("hide");
            })
            // EVENT MOUSEDOWN on cell
            .doubletap (__dblclick);
          // No touch device
          else
            $cell.dblclick (__dblclick);
        }

        let w, h;

        if ($cell[0].classList.contains ("size-init"))
        {
          w = $cell.outerWidth();
          h = $cell.outerHeight ();
        }
        else
        {
          const $trPrev = $cell.parent().prev (),
                $tdPrev = ($trPrev.length) ?
                  $trPrev.find("td:eq("+($cell.index() - 1)+")") : undefined;

          w = $tdPrev ? $tdPrev.css ("width") : settings.width;
          h = $tdPrev ? $tdPrev.css ("height") : settings.height;
        }

        plugin.update ({width: w, height: h});
    },

    // METHOD showUserWriting ()
    showUserWriting (user)
    {
      this.element.prepend (`<div class="user-writing main" data-userid="${user.id}"><i class="fas fa-user-edit blink"></i> ${user.name}</div>`);
    },

    // METHOD setPostitsUserWritingListMode ()
    // See postit::showUserWriting()
    setPostitsUserWritingListMode ()
    {
      this.element[0].querySelectorAll(".user-writing").forEach ((el) =>
        {
          const p = el.parentNode,
                min = p.parentNode.querySelector (
                  ".postit-min[data-id='"+p.dataset.id+"']");

          min.classList.add ("locked");

          $(min).prepend (`<span class="user-writing-min${el.classList.contains("main")?" main":""}" data-userid="${el.dataset.userid}"><i class="${el.querySelector("i").className} fa-sm"></i></span>`);
        });
    },

    // METHOD setPostitsDisplayMode ()
    setPostitsDisplayMode (type)
    {
      const plugin = this,
            $cell = plugin.element,
            $displayMode = $cell.find (".cell-menu i"),
            writeAccess = plugin.canWrite ();

      // If we must display list
      // list-mode
      if (type == "list-mode")
      {
        const cell = $cell[0],
              cellWidth = cell.clientWidth,
              cellHeight = cell.clientHeight,
              postits = Array.from (cell.querySelectorAll (".postit"));

        $cell.removeClass("postit-mode").addClass ("list-mode");

        $cell.resizable ("disable");

        $displayMode[0].classList.replace ("fa-sticky-note", "fa-list-ul");

        let html = "";
        postits
          // Sort by postit id DESC
          .sort((a, b)=>
          {
            const aOrder = parseInt (a.dataset.order),
                  bOrder = parseInt (b.dataset.order);

            if (!aOrder && !bOrder)
              return parseInt(b.dataset.id.split(/\-/)[1]) -
                       parseInt(a.dataset.id.split(/\-/)[1]);
            else
              return aOrder - bOrder;
          })
          .forEach (p =>
          {
            const color = (p.className.match (/ color\-([a-z]+)/))[1],
                  postitPlugin = $(p).postit ("getClass"),
                  title = postitPlugin.element.find(".title").text (),
                  progress = parseInt (p.dataset.progress||0);

            postitPlugin.closeMenu ();
            postitPlugin.hidePlugs ();

            p.style.visibility = "hidden";

            html += `<li class="color-${color} postit-min${p.classList.contains("selected")?" selected":""}" data-id="${p.dataset.id}" data-tags="${p.dataset.tags}">${progress?`<div class="postit-progress-container"><div class="postit-progress" style="width:${progress}%;background:${H.getProgressbarColor(progress)}"><span>${progress}%</span></div></div>`:""}${writeAccess?`<span>${(postits.length > 1)?`<i class="fas fa-arrows-alt-v fa-xs"></i>`:""}</span>`:""} ${title}</li>`;
          });

        $cell.find(".cell-menu").append (
          `<span class="wpt-badge">${postits.length}</span>`);
        $cell.prepend (
          `<div class="cell-list-mode"><ul style="max-width:${cellWidth}px;max-height:${cellHeight-1}px">${html}</ul></div>`);

        if (writeAccess)
          $cell.find(".cell-list-mode ul").sortable ({
            //containment: $cell,
            handle: ">span",
            cursor: "move",
            sort: function ()
            {
              if (S.get("revertData").revert)
              {
                $("body")[0].removeAttribute ("style");
                $(this).sortable ("cancel");

                return false;
              }
            },
            start: function ()
            {
              S.set ("revertData", {revert: false});
              plugin.edit (()=> S.get("revertData").revert = true, true);
            },
            stop: function (e, ui)
            {
              const revertData = S.get ("revertData");

              if (revertData.revert)
              {
                S.unset ("revertData");
                plugin.unedit (true);
              }
              else
              {
                ui.item[0].parentNode.querySelectorAll("li").forEach ((li, i) =>
                  cell.querySelector(".postit[data-id='"+li.dataset.id+"']")
                    .dataset.order = i+1);

                plugin.unedit ();
              }
            }
          });

        plugin.setPostitsUserWritingListMode ();
      }
      // If we must display full postit
      // postit-mode
      else
      {
        $cell.removeClass("list-mode").addClass ("postit-mode");

        $cell.find(".cell-list-mode").remove ();
        $cell.find(".cell-menu .wpt-badge").remove ();

        $cell[0].querySelectorAll(".postit").forEach (p =>
          {
            p.style.visibility = "visible";

            $(p).postit ("showPlugs");
          });

        $displayMode[0].classList.replace ("fa-list-ul", "fa-sticky-note");

        if (writeAccess && !S.get ("zoom-level"))
          $cell.resizable ("enable");
      }
    },

    // METHOD toggleDisplayMode ()
    toggleDisplayMode (refresh = false)
    {
      const $cell = this.element,
            settings = this.settings;
      let type;

      if ($cell[0].classList.contains ("postit-mode") || refresh)
      {
        type = "list-mode";

        if (refresh)
        {
          $cell.find(".cell-list-mode").remove ();
          $cell.find(".cell-menu .wpt-badge").remove ();
        }
      }
      else
        type = "postit-mode";

      this.setPostitsDisplayMode (type);

      // Re-apply filters
      const $f = S.getCurrent ("filters");
      if ($f.is (":visible"))
        $f.filters ("apply", {norefresh: true});

      if (!refresh)
      {
        settings.usersettings.displaymode = type;

        H.fetch (
          "POST",
          "user/wall/"+settings.wallId+"/settings",
          {
            key: "cell-"+settings.id,
            value: settings.usersettings
          });
      }
    },

    // METHOD removePostitsPlugs ()
    removePostitsPlugs ()
    {
      this.element[0].querySelectorAll(".postit.with-plugs").forEach (p =>
        $(p).postit ("removePlugs", true));
    },

    // METHOD reorganize ()
    reorganize ()
    {
      this.element.each (function ()
      {
        const cell = this,
              bbox = cell.getBoundingClientRect ();

        this.querySelectorAll(".postit").forEach (postit =>
          $(postit).postit ("fixPosition", bbox, cell.clientHeight,
                                           cell.clientWidth));
      });
    },

    // METHOD serialize ()
    serialize ()
    {
      const cells = [];

      S.getCurrent("wall")[0].querySelectorAll("tbody td").forEach (cell =>
        {
          const $postits = $(cell).find (".postit"),
                bbox = cell.getBoundingClientRect ();

          cells.push ({
            id: cell.dataset.id.substring (5),
            width: Math.trunc (bbox.width),
            height: Math.trunc (bbox.height),
            item_row: cell.parentNode.rowIndex - 1,
            item_col: cell.cellIndex - 1,
            postits: $postits.length ? $postits.postit ("serialize") : null
          });
        });

      return cells;
    },

    // METHOD addPostit ()
    addPostit (args, noinsert)
    {
      const $cell = this.element,
            settings = this.settings,
            $postit = $("<div/>");

      // CREATE postit
      $postit.postit ($.extend (args, {
        wall: settings.wall,
        wallId: settings.wallId,
        cell: $cell,
        cellId: settings.id,
        plugsContainer: settings.plugsContainer
      }));

      // Add postit on cell
      $cell.append ($postit);

      this.reorganize ();

      // If we are refreshing wall and postit has been already created by
      // another user, do not add it again in DB
      if (!noinsert)
        $postit.postit ("insert");
      else if ($cell[0].classList.contains ("postit-mode"))
      {
        if (args.init || S.getCurrent("filters").is (":visible"))
          $postit[0].style.visibility = "visible";
        else
        {
          $postit.hide ();
          $postit[0].style.visibility = "visible";
          $postit.show ("fade");
        }
      }

      return $postit;
    },

    // METHOD update ()
    update (d)
    {
      const $cell = this.element,
            cell = $cell[0],
            chgH = (cell.clientHeight + 1 != d.height),
            chgW = (cell.clientWidth + 1 != d.width);

      if (chgH || chgW)
      {
        cell.style.width = d.width+"px";
        cell.style.height = d.height+"px";
 
        if (chgW)
          $cell.find(">div.ui-resizable-s").css ("width", d.width + 2);

        if (chgH)
        {
          $cell.closest("tr").find("th:first-child").css("height", d.height);
          $cell.find(">div.ui-resizable-e").css ("height", d.height + 2);
        }
      }
    },

    // METHOD edit ()
    edit (error_cb, nopush)
    {
      if (nopush || !this.settings.wall.wall ("isShared"))
        return;

      H.request_ws (
        "PUT",
        "wall/"+this.settings.wallId+"/editQueue/cell/"+this.settings.id,
        null,
        // success cb
        (d) => d.error_msg &&
                 H.raiseError (() => error_cb && error_cb (), d.error_msg)
      );
    },

    // METHOD unedit ()
    unedit (noupdate = false, move)
    {
      const data = noupdate ?
              null :
              {
                cells: this.serialize (),
                wall: {width: Math.trunc (this.settings.wall.outerWidth ())}
              };

      // If we are moving col/row
      if (data && move)
      {
        move.headers =
          this.element.closest("tr").find("th").header ("serialize");
        data.move = move;
      }

      H.request_ws (
        "DELETE",
        "wall/"+this.settings.wallId+"/editQueue/cell/"+this.settings.id,
        data
      );
    }
  };

  /////////////////////////// AT LOAD INIT //////////////////////////////

  $(function()
    {
      // EVENT click on cell menu
      $(document).on("click", ".cell-menu", function ()
        {
          if (H.disabledEvent ())
            return false;

          $(this).parent().cell ("toggleDisplayMode");
        });

      // EVENT click on cells
      $(document)
        .on("click", "td", function (e)
        {
          const $cell = $(this),
                $mm = S.getCurrent ("mmenu");

          // Click on notes in list mode
          if (e.target.classList.contains ("postit-min"))
          {
            const li = e.target,
                  $p = $cell.find(".postit[data-id='"+li.dataset.id+"']");

            if (e.ctrlKey)
            {
              if (li.classList.contains ("selected"))
                $mm.mmenu ("remove", $p.postit ("getId"));
              else
                $mm.mmenu ("add", $p.postit ("getClass"));

              e.stopImmediatePropagation ();
              e.preventDefault ();
            }
            else
            {
              if (e.cancelable)
                e.preventDefault ();

              if (H.disabledEvent ())
                return false;

              $p.postit ("openPostit", $(li).find ("span"));
            }
          }
          // ctrl+click on a cell to paste/cut on it
          else
          {
            if ((e.ctrlKey || S.get ("action-mmenu")) &&
                !$mm.mmenu ("isEmpty"))
              $mm.mmenu ("apply", {
                event: e,
                cellPlugin: $cell.cell ("getClass")
              });
          }
        });
    });

<?php echo $Plugin->getFooter ()?>
