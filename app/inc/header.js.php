<?php

  require_once (__DIR__.'/../class/Wpt_jQueryPlugins.php');
  $Plugin = new Wpt_jQueryPlugins ('header');
  echo $Plugin->getHeader ();
?>

  let _realEdit = false,
      _originalObject,
       //FIXME
       // to bypass FF bug when file manager is triggered from a third callback
      _ffTriggerBug = {run: false, i: 0};

  /////////////////////////// PRIVATE METHODS ///////////////////////////

  // METHOD serializeOne ()
  function _serializeOne ($header)
  {
    const $img = $header.find("img");

    return {
      id: $header[0].dataset.id.substring (7),
      width: Math.trunc($header.outerWidth ()),
      height: Math.trunc($header.outerHeight ()),
      title: $header.find(".title").text (),
      picture: ($img.length) ? $img.attr("src") : null
    };
  }

  function _navigatorIsEdge ()
  {
    return navigator.userAgent.match (/edg/i);
  }

  //FIXME
  // METHOD _simulateClick ()
  function _simulateClick (x, y)
  {
    const el = document.elementFromPoint (x, y),
          $el = $(el);
    let evtName = (el.tagName.match (/^A|I|DIV|TH|IMG$/))?"click":"mousedown";

    //FIXME
    // do nothing if element is the previously clicked TH
    if ($el.hasClass ("_current") || $el.closest("th._current").length)
      return;

    // if cell click (TD) or cell resize, use mousedown
    if (el.getAttribute ("scope") ||
        el.className.indexOf ("ui-resizable-handle") != -1)
      evtName = "mousedown";

    el.dispatchEvent (
      new MouseEvent (evtName, {
        view: window,
        bubbles: true,
        cancelable: true,
        clientX: x,
        clientY: y
      }));
  }

  /////////////////////////// PUBLIC METHODS ////////////////////////////

  Plugin.prototype =
  {
    // METHOD init ()
    init: function (args)
    {
      const plugin = this,
            $header = plugin.element,
            settings = plugin.settings,
            $wall = settings.wall,
            type = settings.type,
            isCol = (type == "col"),
            adminAccess = wpt_checkAccess ("<?=WPT_RIGHTS['walls']['admin']?>",
                                           settings.access);

      $header[0].dataset.id = "header-"+settings.id;

      $header.append (
          "<div class='title'>"+
            ((settings.title != " ")?settings.title:"&nbsp;")+
          "</div>");

      if (adminAccess)
      {
        const $part = $(`
          <ul class="navbar-nav mr-auto submenu">
            <li class="nav-item dropdown"><a href="#" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" class="nav-link dropdown-toggle"><i class="far fa-caret-square-down btn-menu" data-placement="right"></i></a>
              <ul class="dropdown-menu border-0 shadow">
                <li data-action="rename"><a class="dropdown-item" href="#"><i class="fa-fw fas fa-edit"></i> <?=_("Rename")?></a></li>
                <li data-action="add-picture"><a class="dropdown-item" href="#"><i class="fa-fw fas fa-camera-retro"></i> <?=_("Associate a picture")?></a></li>
                <li class="dropdown-divider"></li>
                <li data-action="delete"><a class="dropdown-item" href="#"><i class="fa-fw fas fa-trash"></i> <?=_("Delete")?> <span></span></a></li>
              </ul>
            </li>
          </ul>`)
          .on("show.bs.dropdown", function ()
            {
              const $menu = $(this),
                    $deleteItem = $menu.find("li[data-action='delete'] a");
   
              $menu.find("a.dropdown-toggle i.far")
                .removeClass("far")
                .addClass ("fas");
   
              if (isCol && $wall.find("thead th").length > 2 ||
                  !isCol && $wall.find("tbody th").length > 1)
                $deleteItem.removeClass ("disabled");
              else
                $deleteItem.addClass ("disabled");
            })
          .on("hide.bs.dropdown", function ()
            {
              $(this).find("a.dropdown-toggle i.fas")
                .removeClass("fas")
                .addClass ("far");
            });

        $part.find(".dropdown-menu li a").on("click",function(e)
        {
          const $li = $(this).parent (),
                $cell = $li.closest ("th"),
                action = $li[0].dataset.action;

          e.stopImmediatePropagation ();
  
          $li.parent().dropdown ("hide");
  
          switch (action)
          {
            case "add-picture":
              if (settings.wall[0].dataset.shared)
              {
                //FIXME
                // we need this to cancel edit if no img is selected by user
                // (touch device version)
                plugin.addUploadLayer ();
  
                plugin.edit (() => _ffTriggerBug.run = true);

                plugin.uploadPicture ($cell);
              }
              else
                plugin.edit (() => plugin.uploadPicture ($cell));
                
              break;
  
            case "delete":
              plugin.edit (() =>
                {
                  wpt_openConfirmPopover ({
                       item: $cell.find("i.btn-menu"),
                       placement: (isCol) ? "left" : "right",
                       title: `<i class="fas fa-trash fa-fw"></i> <?=_("Delete")?>`,
                       content: (isCol) ?
                         "<?=_("Delete this column?")?>":
                         "<?=_("Delete this row?")?>",
                       cb_close: () => plugin.unedit (),
                       cb_ok: () =>
                         {
                           if (isCol)
                             $wall.wpt_wall ("deleteCol", $header.index ());
                           else
                            $wall.wpt_wall ("deleteRow",
                              $header.closest("tr").index ()); 
                         }
                     });
                });
              break;
  
            case "rename":
              plugin.edit (() =>
                {
                  const $popup = $("#updateOneInputPopup");
   
                  wpt_cleanPopupDataAttr ($popup);
    
                  $popup.find("input").val ($header.find(".title").text());
                  $popup.find("#w-grid").parent().remove ();
    
                  $popup.find(".modal-title").html (
                    (isCol)?"<?=_("Column title")?>":"<?=_("Row title")?>");
       
                  $popup.find(".btn-primary").html ("<?=_("Save")?>");
    
                  $popup[0].dataset.popuptype = "set-col-row-name";
                  $popup[0].dataset.itemid = $header[0].dataset.id;
                  $popup.modal ("show");
                });
              break;
          }
        });
  
        $part.prependTo ($header);
      }
      else
        $(`<ul class="navbar-nav mr-auto submenu"></ul>`).prependTo ($header);

      if (settings.picture)
        $header.append (plugin.getImgTemplate (settings.picture));
    },

    // METHOD useFocusTrick ()
    useFocusTrick: function ()
    {
      return (this.settings.wall[0].dataset.shared &&
              !$.support.touch && !_navigatorIsEdge ());
    },

    // METHOD addUploadLayer ()
    addUploadLayer: function ()
    {
      const plugin = this;

      if (!plugin.useFocusTrick ())
        $("#upload-layer")
          .off("mousedown")
          .on("mousedown", function (e)
          {
            plugin.unedit (
              {bubble_cb: () => _simulateClick (e.pageX, e.pageY)});
          })
          .show ();
    },

  //FIXME
  // to bypass FF bug when file manager is triggered from a third
  // callback
  // -> This trick does not fully work with edge!
  // METHOD uploadPicture ()
  uploadPicture: function ($item)
  {
    const plugin = this,
          $header = plugin.element,
          settings = plugin.settings;

    function __upload__ ()
    {
      $(`<input type="file" accept=".jpeg,.jpg,.gif,.png">`)
        .on("click", function (e)
          {
            //FIXME
            // we need this to cancel edit if no img is selected by user
            // (desktop version)
            if (plugin.useFocusTrick ())
              $(window).on("focus", function ()
                {
                  $(window).off ("focus");

                  if (!_realEdit)
                    plugin.unedit ();
                });
          })
        .on("change",function(e, data)
          {
            const $upload = $(this);

            if (e.target.files && e.target.files.length)
            {
              _realEdit = true;

              wpt_getUploadedFiles (e.target.files,
                (e, file) =>
                {
                  if (wpt_checkUploadFileSize ({size: e.total}) &&
                      e.target.result)
                  {
                    const oldW = $header.outerWidth (),
                          data = {
                            name: file.name,
                            size: file.size,
                            type: file.type,
                            content: e.target.result
                          };

                    $upload.remove ();
  
                    wpt_request_ajax (
                      "PUT",
                      "wall/"+settings.wallId+
                      "/header/"+settings.id+"/picture",
                      data,
                      // success cb
                      (d) =>
                      {
                        if (d.error_msg)
                          return plugin.unedit ({data: d});
  
                        plugin.setImg (d.img);
                        setTimeout(() =>
                          {
                            settings.wall.wpt_wall (
                              "fixSize", oldW, $header.outerWidth ());

                            plugin.unedit ();

                          }, 500);
                      },
                      // error cb
                      (d) => plugin.unedit ({data: d}));
                  }
                },
                // error cb
                () => plugin.unedit ());
            }
          }).appendTo("body").trigger ("click");
      }

      if (!settings.wall[0].dataset.shared || _navigatorIsEdge ())
        __upload__ ();
      else
      {
        clearInterval (_ffTriggerBug.i);
        _ffTriggerBug = {
          run: false,
          i: setInterval (() =>
            { 
              if (_ffTriggerBug.run)
              { 
                clearInterval (_ffTriggerBug.i);
                __upload__ ();
              }
            }, 150)
        };
      }
    },

    // METHOD removeUploadLayer ()
    removeUploadLayer: function ()
    {
      $("#upload-layer").hide ();
    },

    openMenu: function ()
    {
      if (!this.element.find(".btn-menu.fas").length)
        this.element.find(".btn-menu").trigger ("click");
    },

    // METHOD getImgTemplate ()
    getImgTemplate: function (src)
    {
      const plugin = this,
            $header = plugin.element,
            type =
              (($header.parent().parent()[0].tagName=="TBODY")?"row":"col"),
            adminAccess = wpt_checkAccess ("<?=WPT_RIGHTS['walls']['admin']?>",
                            plugin.settings.access),
            $img = $("<div class='img'><img src='"+src+"'></div>");

      if (!adminAccess)
        return $img;
      
      $img
        .on("click",function(e)
          {
            e.stopImmediatePropagation ();

            if (plugin.settings.wall[0].dataset.shared)
            {
              //FIXME
              // we need this to cancel edit if no img is selected by user
              // (touch device version)
              plugin.addUploadLayer ();

              plugin.edit (() => _ffTriggerBug.run = true);

              plugin.uploadPicture ($header);
            }
            else
              plugin.edit (() => plugin.uploadPicture ($header));
          })
        .find("img")
          .on("load", function (e)
          {
            plugin.settings.wall.wpt_wall ("repositionPostitsPlugs");
          });

      // Create img delete button
      const $deleteButton = $(`<button type="button" class="close img-delete"><i class="fas fa-times fa-sm"></i></button>`)
        .on("click",function(e)
          {
            e.stopImmediatePropagation ();

            plugin.edit (() =>
              {
                wpt_openConfirmPopover ({
                  item: $(this),
                  placement: "right",
                  title: `<i class="fas fa-trash fa-fw"></i> <?=_("Delete")?>`,
                  content: "<?=_("Delete this picture?")?>",
                  cb_close: () =>
                    {
                      if (!wpt_sharer.get ("unedit-done"))
                        plugin.unedit ();
                      else
                        wpt_sharer.unset ("unedit-done");
                    },
                  cb_ok: () =>
                    {
                      wpt_sharer.set ("unedit-done", true);
                      plugin.deleteImg ();
                    }
                });
            });

          });

      $img.prepend ($deleteButton);

      return $img;
    },

    // METHOD setImg ()
    setImg: function (src)
    {
      const $header = this.element,
            $img = $header.find(".img img");

      if (src)
      {
        if (!$img.length)
          $header.append (this.getImgTemplate (src));
        else if (src != $img.attr("src"))
          $img.attr ("src", src);
      }
      else if ($img.length)
        $header.find(".img").remove ();
    },

    // METHOD deleteImg ()
    deleteImg: function ()
    {
      const $header = this.element,
            $wall = this.settings.wall,
            oldW = $header.outerWidth ();

      wpt_request_ws (
        "DELETE",
        "wall/"+this.settings.wallId+"/header/"+this.settings.id+"/picture",
        null,
        // success cb
        (d) =>
        {
          if (d.error_msg)
            wpt_raiseError (null, d.error_msg);
          else
          {
            if (this.settings.type == "col")
              $header.find(".img").remove ();
            else
              wpt_headerRemoveContentKeepingWallSize ({
                oldW: oldW,
                cb: () => $header.find(".img").remove ()
              });

            this.unedit ();
          }
        }
      );
    },

    // METHOD update ()
    update: function (header)
    {
      if (header.hasOwnProperty("title"))
        this.setTitle (header.title);

      if (header.hasOwnProperty("picture"))
        this.setImg (header.picture);
    },

    // METHOD setTitle ()
    setTitle: function (title, resize)
    {
      const plugin = this,
            $header = plugin.element,
            thIdx = $header.index (),
            isRow = (plugin.settings.type == "row");

      title = wpt_noHTML (title);

      if (resize)
      {
        const $wall = plugin.settings.wall,
              oldW = $header.outerWidth ();
        let tdW = 0;

        // Get row TD total width
        $wall.find("tbody tr:eq(0) td").each (function ()
          {
            const $td = $(this);

            if (isRow || $td.index () != thIdx)
              tdW += $td.outerWidth ();
          });

        if (isRow)
          $header.css ("width", 1);

        $header.find(".title").html (title ? title : "&nbsp;");

        wpt_waitForDOMUpdate (()=>
          {
            const newW = $header.outerWidth ();

            if (isRow || newW > oldW)
            {
              if (newW != oldW)
              {
                $wall.wpt_wall ("fixSize", oldW, newW);
                $wall.css("width", tdW + newW);
              }

              if (!isRow)
              {
                $wall.find("tbody tr")
                  .find("td:eq("+(thIdx - 1)+")").each (function ()
                  {
                    const $cell = $(this);

                    $cell[0].style.width = newW+"px";
                    $cell.find(".ui-resizable-s")[0].style.width =
                      (newW + 2)+"px";
                  });
              }
            }
            else
              $wall.wpt_wall ("fixSize");

            plugin.unedit ();
          });
      }
      else
        $header.find(".title").html (title ? title : "&nbsp;");
    },

    // METHOD edit ()
    edit: function (success_cb, error_cb)
    {
      this.setCurrent ();

      _originalObject = _serializeOne (this.element);

      if (!this.settings.wall[0].dataset.shared)
        return success_cb && success_cb ();

      wpt_request_ws (
        "PUT",
        "wall/"+this.settings.wallId+"/editQueue/header/"+this.settings.id,
        null,
        // success cb
        (d) =>
        {
          // If header does not exists anymore (row/col has been deleted)
          if (d.error_msg)
          {
            wpt_raiseError (() =>
              {
                error_cb && error_cb ();
                this.cancelEdit ();

              }, d.error_msg);
          }
          else if (success_cb)
            success_cb (d);
        },
        // error cb
        (d) =>
        {
          wpt_raiseError (() =>
            {
              error_cb && error_cb ();
              this.cancelEdit ();

            }, (d && d.error) ? d.error : null);
        });
    },

    // METHOD setCurrent ()
    setCurrent: function ()
    {
      this.element.addClass ("current");
    },

    // METHOD unsetCurrent ()
    unsetCurrent: function ()
    {
      wpt_sharer.reset ("header");
      this.element.removeClass ("current");
    },

    // METHOD cancelEdit ()
    cancelEdit: function (bubble_event_cb)
    {
      const $header = this.element,
            $wall = this.settings.wall;

      clearInterval (_ffTriggerBug.i);

      _realEdit = false;

      this.unsetCurrent ();

      $wall.wpt_wall ("closeAllMenus");

      if (bubble_event_cb)
      {
        $header.addClass ("_current")
        bubble_event_cb ();
        $header.removeClass ("_current")
      }
    },

    // METHOD serialize ()
    serialize: function ()
    {
      const $wall = this.settings.wall,
            headers = {cols: [], rows: []};

      $wall.find("thead th:not(:eq(0))").each (function()
        {
          headers.cols.push (_serializeOne ($(this)));
        });

      $wall.find("tbody th").each (function()
        {
          headers.rows.push (_serializeOne ($(this)));
        });

      return headers;
    },

    // METHOD unedit ()
    unedit: function (args = {})
    {
      const $wall = this.settings.wall;
      let data = null;

      this.removeUploadLayer ();

      if (args.data)
      {
        const msg = (args.data.error) ?
          args.data.error : (args.data.error.error_msg) ?
            args.data.error_msg : null;

        if (msg)
          wpt_displayMsg ({
            type: (args.data.error) ? "danger" : "warning",
            title: "<?=_("Warning!")?>",
            msg: msg
          });
      }

      // Update header only if it has changed
      if (wpt_updatedObject(_originalObject, _serializeOne (this.element)))
      {
        data = {
          headers: this.serialize (),
          cells: $("<div/>").wpt_cell ("serialize"),
          wall: {width: Math.trunc($wall.outerWidth ())}
        };

        $wall.find("tbody td").each (function ()
          {
            $(this).wpt_cell ("reorganize");
          });
      }
      else if (!this.settings.wall[0].dataset.shared)
        return this.cancelEdit (args.bubble_cb);

      wpt_request_ws (
        "DELETE",
        "wall/"+this.settings.wallId+"/editQueue/header/"+this.settings.id,
        data,
        // success cb
        (d) => this.cancelEdit (args.bubble_cb),
        // error cb
        () => this.cancelEdit (args.bubble_cb)
      );
    },

    // METHOD getId ()
    getId: function ()
    {
      return this.settings.id;
    }

  };

<?php echo $Plugin->getFooter ()?>
