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
            writeAccess = wpt_checkAccess (
                            "<?=WPT_RIGHTS['walls']['rw']?>", settings.access);

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
                if (wpt_sharer.get("revertData").revert)
                  return;

                const $target = $(this),
                      $postit = ui.draggable,
                      ptop = ui.offset.top - $target.offset().top,
                      pleft = ui.offset.left - $target.offset().left;
  
                $postit.wpt_postit ("setPosition", {
                  cellId: settings.id,
                  top: ptop<0?0:ptop,
                  left: pleft<0?0:pleft
                });
  
                $postit.appendTo ($target);
  
                $target.wpt_cell ("reorganize");
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
              if (wpt_sharer.get("revertData").revert)
              {
                $("body")[0].removeAttribute ("style");

                return false;
              }
            },
          start: function(e, ui)
            {
              wpt_sharer.set ("revertData", {
                revert: false,
                size: {
                  width: $cell.outerWidth (),
                  height: $cell.outerHeight ()
                }
              });

              $cell.wpt_cell ("edit",
                () =>  wpt_sharer.get("revertData").revert = true);
            },
          stop:function(e, ui)
            {
              const revertData = wpt_sharer.get ("revertData");

              if (revertData.revert)
              {
                $cell.css ({
                  width: revertData.size.width,
                  height: revertData.size.height
                });

                wpt_sharer.unset ("revertData");
              }
              else
              {
                $wall.wpt_wall ("fixSize",
                  ui.originalSize.width, ui.size.width + 3);

                $cell.wpt_cell ("update", {
                  width: ui.size.width + 3,
                  height: ui.size.height
                });

                // Set height/width for all cells of the current row
                $wall.find("tbody tr:eq("+$cell.parent().index ()+") td")
                  .each (function ()
                  {
                    const $c = $(this),
                          c = $c[0];

                    c.style.height = (ui.size.height + 2)+"px";
                    c.style.width = c.clientWidth+"px";

                    $c.find(">div.ui-resizable-e")[0]
                      .style.height = (ui.size.height + 2)+"px";
                    $c.find(">div.ui-resizable-s")[0]
                      .style.width = c.clientWidth+"px";

                  });

                $wall.find("tbody td").each (function ()
                  {
                    $(this).wpt_cell ("reorganize");
                  });

                $cell.wpt_cell ("unedit");
              }
            }
        });

       if (writeAccess)
         $cell
          // EVENT MOUSEDOWN on cell
          .on("dblclick",function(e)
            {
              // Only left click on cell
              if (e.which != 1 || e.target.tagName != 'TD')
                return e.stopImmediatePropagation ();
  
              const cellOffset = $cell.offset (),
                    $filters = wpt_sharer.getCurrent ("filters"),
                    pTop = e.pageY - cellOffset.top,
                    pLeft = e.pageX - cellOffset.left,
                    $mark = $(`<div class="postit-mark"><i class="fas fa-sticky-note"></i></div>`).css ({
                      top: (pTop + 16 > cellOffset.top) ?
                             pTop - 16 : pTop - 8,
                      left: (pLeft < 14) ?
                        pLeft : pLeft - 12})
                      .appendTo ($cell);

              wpt_openConfirmPopover ({
                item: $mark,
                title: `<i class="fas fa-sticky-note fa-fw"></i> <?=_("Create")?>`,
                content: "<?=_("Create a new postit-it here?")?>",
                cb_close: () => $mark.remove (),
                cb_ok: () =>
                  {
                    const $filters = wpt_sharer.getCurrent ("filters");

                    if ($filters)
                      $filters.wpt_filters ("reset");

                    $wall.wpt_wall ("closeAllMenus");                    
                    plugin.addPostit ({
                      access: settings.access,
                      top: pTop,
                      left: pLeft - 15
                    });
                  }
              });

            });

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

        $cell.wpt_cell ("update", {width: w, height: h});
    },

    // METHOD removePostitsPlugs ()
    removePostitsPlugs: function ()
    {
      this.element.find(".postit.with-plugs").each (function ()
        {
          $(this).wpt_postit ("removePlugs", true);
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
        const $cell = $(this),
              cell = $cell[0],
              bbox = cell.getBoundingClientRect ();

        $cell.find("div.postit").each (function ()
        {
          $(this).wpt_postit ("fixPosition",
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

      wpt_sharer.getCurrent("wall").find("tbody td").each (function ()
      {
        const $cell = $(this),
              cellId = $cell.wpt_cell ("getId");

        cells.push ({
          id: cellId,
          width: $cell.outerWidth (),
          height: $cell.outerHeight (),
          row: $cell.parent().index (),
          col: $cell.index () - 1,
          postits: $("<div/>").wpt_postit ("serialize", cellId)
        });
      });

      return cells;
    },

    // METHOD addPostit ()
    addPostit: function (args, noinsert)
    {
      const plugin = this,
            $cell = plugin.element,
            $postit = $("<div></div>");

      args["wall"] = plugin.settings.wall;
      args["wallId"] = plugin.settings.wallId;
      args["cell"]  = $cell;
      args["cellId"] = plugin.settings.id;

      //FIXME Use a auto increment for post-its
      if (!noinsert)
        args["id"] = wpt_buildId ();

      // CREATE post-it
      $postit.wpt_postit (args);

      // Add postit on cell
      $cell.append ($postit);

      plugin.reorganize ();

      // If we are refreshing wall and postit has been already created by
      // another user, do not add it again in DB
      if (!noinsert)
        $postit.wpt_postit ("insert");
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
      const plugin = this,
            $wall = plugin.settings.wall,
            cellId = plugin.settings.id;

      wpt_request_ws (
        "PUT",
        "wall/"+plugin.settings.wallId+"/editQueue/cell/"+cellId,
        null,
        // success cb
        (d) =>
        {
          if (d.error_msg)
          {
            wpt_raiseError (() =>
              {
                if (error_cb)
                  error_cb ();

                if (d.deletewall)
                  $wall.wpt_wall ("close");
                else
                  $wall.wpt_wall ("refresh"); 

              }, d.error_msg);
          }
        },
        // error cb
        (d) =>
        {
          wpt_raiseError (() =>
            {
              if (error_cb)
                error_cb ();

            }, (d && d.error) ? d.error : null);
        });
    },

    // METHOD unedit ()
    unedit: function ()
    {
      const plugin = this,
            $cell = plugin.element,
            $wall = plugin.settings.wall,
            data = {
              cells: plugin.serialize (),
              wall: {width: $wall.outerWidth () - 1}
            };

      wpt_request_ws (
        "DELETE",
        "wall/"+plugin.settings.wallId+"/editQueue/cell/"+plugin.settings.id,
        data);
    }
  };
  
<?php echo $Plugin->getFooter ()?>
