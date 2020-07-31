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
            type = settings.item_type,
            isCol = (type == "col"),
            adminAccess = H.checkAccess ("<?=WPT_WRIGHTS_ADMIN?>",
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
                  H.openConfirmPopover ({
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
                             $wall.wall ("deleteCol", $header.index ());
                           else
                            $wall.wall (
                              "deleteRow", $header.closest("tr").index ()); 
                         }
                     });
                });
              break;
  
            case "rename":

              plugin.edit (() =>
                {
                  H.openConfirmPopover ({
                    type: "update",
                    item: $li.parent().parent().find(".btn-menu"),
                    title: `<i class="fas fa-grip-lines${isCol?"-vertical":""} fa-fw"></i> ${(isCol)?"<?=_("Column name")?>":"<?=_("Row name")?>"}`,
                      content: `<input type="text" class="form-control form-control-sm" value="${$header.find(".title").text()}" maxlength="<?=Wpt_dbCache::getFieldLength('headers', 'title')?>">`,
                      cb_close: () =>
                        {
                          if (!S.get ("no-unedit"))
                            plugin.unedit ();

                          S.unset ("no-unedit");
                        },
                      cb_ok: ($popover) =>
                        {
                          S.set ("no-unedit", true);
                          plugin.setTitle ($popover.find("input").val(), true);
                        }
                    });
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
              !$.support.touch && !H.navigatorIsEdge ());
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
        $(".upload.header-picture").click ();
      }

      if (!settings.wall[0].dataset.shared || H.navigatorIsEdge ())
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
        this.element.find(".btn-menu").click ();
    },

    // METHOD getImgTemplate ()
    getImgTemplate: function (src)
    {
      const plugin = this,
            $header = plugin.element,
            type =
              (($header.parent().parent()[0].tagName=="TBODY")?"row":"col"),
            adminAccess = H.checkAccess ("<?=WPT_WRIGHTS_ADMIN?>",
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
            plugin.settings.wall.wall ("repositionPostitsPlugs");
          });

      // Create img delete button
      const $deleteButton = $(`<button type="button" class="close img-delete"><i class="fas fa-times fa-sm"></i></button>`)
        .on("click",function(e)
          {
            e.stopImmediatePropagation ();

            plugin.edit (() =>
              {
                H.openConfirmPopover ({
                  item: $(this),
                  placement: "right",
                  title: `<i class="fas fa-trash fa-fw"></i> <?=_("Delete")?>`,
                  content: "<?=_("Delete this picture?")?>",
                  cb_close: () =>
                    {
                      if (!S.get ("unedit-done"))
                        plugin.unedit ();
                      else
                        S.unset ("unedit-done");
                    },
                  cb_ok: () =>
                    {
                      S.set ("unedit-done", true);
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

      H.request_ws (
        "DELETE",
        "wall/"+this.settings.wallId+"/header/"+this.settings.id+"/picture",
        null,
        // success cb
        (d) =>
        {
          if (d.error_msg)
            H.raiseError (null, d.error_msg);
          else
          {
            if (this.settings.item_type == "col")
              $header.find(".img").remove ();
            else
              H.headerRemoveContentKeepingWallSize ({
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
            isRow = (plugin.settings.item_type == "row");

      title = H.noHTML (title);

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

        H.waitForDOMUpdate (()=>
          {
            const newW = $header.outerWidth ();

            if (isRow || newW > oldW)
            {
              if (newW != oldW)
              {
                $wall.wall ("fixSize", oldW, newW);
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
              $wall.wall ("fixSize");

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

      H.request_ws (
        "PUT",
        "wall/"+this.settings.wallId+"/editQueue/header/"+this.settings.id,
        null,
        // success cb
        (d) =>
        {
          // If header does not exists anymore (row/col has been deleted)
          if (d.error_msg)
          {
            H.raiseError (() =>
              {
                error_cb && error_cb ();
                this.cancelEdit ();

              }, d.error_msg);
          }
          else if (success_cb)
            success_cb (d);
        },
        // error cb
        (d) => this.cancelEdit ()
      );
    },

    // METHOD setCurrent ()
    setCurrent: function ()
    {
      this.element.addClass ("current");
    },

    // METHOD unsetCurrent ()
    unsetCurrent: function ()
    {
      S.reset ("header");
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

      $wall.wall ("closeAllMenus");

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
          H.displayMsg ({
            type: (args.data.error) ? "danger" : "warning",
            msg: msg
          });
      }

      // Update header only if it has changed
      if (H.updatedObject(_originalObject, _serializeOne (this.element)))
      {
        data = {
          headers: this.serialize (),
          cells: $("<div/>").cell ("serialize"),
          wall: {width: Math.trunc($wall.outerWidth ())}
        };

        $wall.find("tbody td").each (function ()
          {
            $(this).cell ("reorganize");
          });
      }
      else if (!this.settings.wall[0].dataset.shared)
        return this.cancelEdit (args.bubble_cb);

      H.request_ws (
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
    },

    // METHOD getSettings ()
    getSettings: function ()
    {
      return this.settings;
    }

  };

  $(function()
    {
      $(`<input type="file" accept=".jpeg,.jpg,.gif,.png"
          class="upload header-picture">`)
        .on("click", function ()
          {
            const $header = S.getCurrent ("header");

            //FIXME
            // we need this to cancel edit if no img is selected by user
            // (desktop version)
            if ($header.header ("useFocusTrick"))
              $(window).on("focus", function ()
                {
                  $(window).off ("focus");

                  if (!_realEdit)
                    $header.header ("unedit");
                });
          })
        .on("change",function (e)
          {
            const $upload = $(this),
                  $header = S.getCurrent ("header"),
                  settings = $header.header ("getSettings");

            if (e.target.files && e.target.files.length)
            {
              _realEdit = true;

              H.getUploadedFiles (e.target.files,
                (e, file) =>
                {
                  $upload.val ("");

                  if (H.checkUploadFileSize ({size: e.total}) &&
                      e.target.result)
                  {
                    const oldW = $header.outerWidth ();
  
                    H.request_ajax (
                      "PUT",
                      "wall/"+settings.wallId+
                      "/header/"+settings.id+"/picture",
                      {
                        name: file.name,
                        size: file.size,
                        item_type: file.type,
                        content: e.target.result
                      },
                      // success cb
                      (d) =>
                      {
                        if (d.error_msg)
                          return $header.header ("unedit", {data: d});
  
                        $header.header ("setImg", d.img);
                        setTimeout(() =>
                          {
                            settings.wall.wall (
                              "fixSize", oldW, $header.outerWidth ());

                            $header.header ("unedit");

                          }, 500);
                      },
                      // error cb
                      (d) => $header.header ("unedit", {data: d}));
                  }
                },
                // error cb
                () => $header.header ("unedit"));
            }
          }).appendTo ("body");

    });

<?php echo $Plugin->getFooter ()?>
