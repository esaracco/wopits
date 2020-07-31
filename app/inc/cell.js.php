<?php
  require_once (__DIR__.'/../class/Wpt_jQueryPlugins.php');
  $Plugin = new Wpt_jQueryPlugins ('cell', 'width: 300, height: 200');
  echo $Plugin->getHeader ();
?>

  /////////////////////////// PUBLIC METHODS ////////////////////////////

  Plugin.prototype =
  {
    // METHOD init ()
    init: function ()
    {
      const plugin = this,
            $cell = plugin.element,
            settings = plugin.settings,
            $wall = settings.wall,
            writeAccess =
              H.checkAccess ("<?=WPT_WRIGHTS_RW?>", settings.access);
      // Coords of touchstart on touch devices
      let _coords = null;

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

              if (revertData.revert)
              {
                $cell.css ({
                  width: revertData.size.width,
                  height: revertData.size.height
                });

                S.unset ("revertData");
              }
              else
              {
                $wall.wall ("fixSize",
                  ui.originalSize.width, ui.size.width + 3);

                plugin.update ({
                  width: ui.size.width + 3,
                  height: ui.size.height
                });

                // Set height/width for all cells of the current row
                $wall.find("tbody tr:eq("+$cell.parent().index ()+") td")
                  .each (function ()
                  {
                    const $c = $(this);

                    this.style.height = (ui.size.height + 2)+"px";
                    this.style.width = this.clientWidth+"px";

                    $c.find(">div.ui-resizable-e")[0]
                      .style.height = (ui.size.height + 2)+"px";
                    $c.find(">div.ui-resizable-s")[0]
                      .style.width = this.clientWidth+"px";

                  });

                $wall.find("tbody td").each (function ()
                  {
                    $(this).cell ("reorganize");
                  });

                plugin.unedit ();
              }
            }
        });

       if (writeAccess)
       {
         $cell
          // Get touch coords on touch devices
          .on("touchstart", function (e)
            {
              _coords = e;

              // Fix issue with some touch devices
              $(".navbar-nav,.dropdown-menu").collapse ("hide");
            })
          .on("click", function (e)
            {
              const currentPlug = S.get ("link-from");

              if (currentPlug)
                currentPlug.obj.postit ("cancelPlugAction");
            })
          // EVENT MOUSEDOWN on cell
          .doubletap(function(e)
            {
              if (e.target.tagName != 'TD')
                return e.stopImmediatePropagation ();
  
              const $filters = S.getCurrent ("filters"),
                    cellOffset = $cell.offset (),
                    pTop = ((_coords && _coords.changedTouches) ?
                      _coords.changedTouches[0].clientY :
                      e.pageY) - cellOffset.top,
                    pLeft = ((_coords && _coords.changedTouches) ?
                      _coords.changedTouches[0].clientX :
                      e.pageX) - cellOffset.left;

              _coords = null;

              $wall.wall ("closeAllMenus");

              if ($filters)
                $filters.filters ("reset");

              plugin.addPostit ({
                access: settings.access,
                item_top: pTop,
                item_left: pLeft - 15
              });
            });
        }

        let w, h;

        if ($cell.hasClass("size-init"))
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

    // METHOD removePostitsPlugs ()
    removePostitsPlugs: function ()
    {
      this.element.find(".postit.with-plugs").each (function ()
        {
          $(this).postit ("removePlugs", true);
        });
    },

    // METHOD getId ()
    getId: function ()
    {
      return this.settings.id;
    },

    // METHOD reorganize ()
    reorganize: function ()
    {
      this.element.each (function ()
      {
        const cell = this,
              bbox = cell.getBoundingClientRect ();

        $(this).find(".postit").each (function ()
        {
          $(this).postit ("fixPosition",
            bbox,
            cell.clientHeight,
            cell.clientWidth
          );
        });
      });
    },

    // METHOD serialize ()
    serialize: function ()
    {
      const cells = [];

      S.getCurrent("wall").find("tbody td").each (function ()
      {
        const $cell = $(this),
              $postits = $cell.find(".postit");

        cells.push ({
          id: $cell[0].dataset.id.substring (5),
          width: Math.trunc($cell.outerWidth ()),
          height: Math.trunc($cell.outerHeight ()),
          item_row: $cell.parent().index (),
          item_col: $cell.index () - 1,
          postits: $postits.length ? $postits.postit ("serialize") : null
        });
      });

      return cells;
    },

    // METHOD addPostit ()
    addPostit: function (args, noinsert)
    {
      const $cell = this.element,
            settings = this.settings,
            $postit = $("<div/>");

      args.wall = settings.wall;
      args.wallId = settings.wallId;
      args.plugsContainer = settings.plugsContainer;
      args.cell = $cell;
      args.cellId = settings.id;

      // CREATE post-it
      $postit.postit (args);

      // Add postit on cell
      $cell.append ($postit);

      this.reorganize ();

      // If we are refreshing wall and postit has been already created by
      // another user, do not add it again in DB
      if (!noinsert)
        $postit.postit ("insert");
      else
        $postit.css ("visibility", "visible");
    },

    // METHOD update ()
    update: function (d)
    {
      const $cell = this.element,
            cell = $cell[0],
            chgH = ($cell[0].clientHeight + 1 != d.height),
            chgW = ($cell[0].clientWidth + 1 != d.width);
            
      if (chgH || chgW)
      {
        $cell.css ({
          width: d.width,
          height: d.height
        }); 
 
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
    edit: function (error_cb)
    {
      if (!this.settings.wall[0].dataset.shared)
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
    unedit: function ()
    {
      H.request_ws (
        "DELETE",
        "wall/"+this.settings.wallId+"/editQueue/cell/"+this.settings.id,
        {
          cells: this.serialize (),
          wall: {width: Math.trunc(this.settings.wall.outerWidth () - 1)}
        }
      );
    }
  };
  
<?php echo $Plugin->getFooter ()?>
