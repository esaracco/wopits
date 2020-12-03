<?php
/**
  Javascript plugin - Wall's header

  Scope: Wall
  Element: th
  Description: Wall's headers
*/

  require_once (__DIR__.'/../prepend.php');

  use Wopits\DbCache;

  $Plugin = new Wopits\jQueryPlugin ('header', '', 'wallElement');
  echo $Plugin->getHeader ();

?>

  let _realEdit = false,
      _originalObject,
       //FIXME
       // to bypass FF bug when file manager is triggered from a third callback
      _ffTriggerBug = {run: false, i: 0};

  /////////////////////////// PRIVATE METHODS ///////////////////////////

  // METHOD serializeOne ()
  function _serializeOne (header)
  {
    const img = header.querySelector ("img"),
          bbox = header.getBoundingClientRect ();

    return {
      id: header.dataset.id.substring (7),
      width: Math.trunc (bbox.width),
      height: Math.trunc (bbox.height),
      title: header.querySelector(".title").innerText,
      picture: img ? img.getAttribute ("src") : null
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
    init (args)
    {
      const plugin = this,
            $header = plugin.element,
            settings = plugin.settings,
            $wall = settings.wall,
            isCol = (settings.item_type == "col"),
            adminAccess = H.checkAccess ("<?=WPT_WRIGHTS_ADMIN?>",
                                           settings.access);

      settings._timeoutEditing = 0;

      $header[0].dataset.id = "header-"+settings.id;

      $header.append (
          "<div class='title'>"+
            ((settings.title != " ")?settings.title:"&nbsp;")+
          "</div>");

      if (adminAccess)
      {
        const $part = $(`<ul class="navbar-nav mr-auto submenu"><li class="nav-item dropdown"><a href="#" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" class="nav-link dropdown-toggle"><i class="far fa-caret-square-right btn-menu" data-placement="right"></i></a><ul class="dropdown-menu border-0 shadow"><li data-action="rename"><a class="dropdown-item" href="#"><i class="fa-fw fas fa-edit"></i> <?=_("Rename")?></a></li><li data-action="add-picture"><a class="dropdown-item" href="#"><i class="fa-fw fas fa-camera-retro"></i> <?=_("Associate a picture")?></a></li>${isCol?`<li data-action="move-left"><a class="dropdown-item" href="#"><i class="fa-fw fas fa-chevron-left"></i> <?=_("Move left")?></a></li><li data-action="move-right"><a class="dropdown-item" href="#"><i class="fa-fw fas fa-chevron-right"></i> <?=_("Move right")?></a></li>`:`<li data-action="move-up"><a class="dropdown-item" href="#"><i class="fa-fw fas fa-chevron-up"></i> <?=_("Move up")?></a></li><li data-action="move-down"><a class="dropdown-item" href="#"><i class="fa-fw fas fa-chevron-down"></i> <?=_("Move down")?></a></li>`}</li><li data-action="delete"><a class="dropdown-item" href="#"><i class="fa-fw fas fa-trash"></i> <?=_("Delete")?> <span></span></a></li></ul></li></ul>`)
          .on("show.bs.dropdown", function ()
            {
              const $menu = $(this),
                    menu = $menu[0],
                    wall = $wall[0],
                    tr = $header.closest("tr")[0],
                    deleteItem =
                      menu.querySelector("[data-action='delete'] a"),
                    moveUpItem =
                      menu.querySelector("[data-action='move-up'] a"),
                    moveDownItem =
                      menu.querySelector("[data-action='move-down'] a"),
                    moveLeftItem =
                      menu.querySelector("[data-action='move-left'] a"),
                    moveRightItem =
                      menu.querySelector("[data-action='move-right'] a");

              $menu.find("a.disabled").removeClass ("disabled");
              $menu.find("a").show ();
              $menu.find("a.dropdown-toggle i.far")
                .removeClass("far")
                .addClass ("fas");

              if (isCol)
              {
                const thIdx = $header.index ();

                if (wall.querySelectorAll("thead th").length <= 2)
                  deleteItem.style.display = "none";

                if (thIdx == 1)
                  moveLeftItem.style.display = "none";

                if (thIdx == tr.querySelectorAll("th").length-1)
                  moveRightItem.style.display = "none";
              }
              else
              {
                const trIdx = $(tr).index ();

                if (wall.querySelectorAll("tbody th").length == 1)
                  deleteItem.style.display = "none";

                if (trIdx == 0)
                  moveUpItem.style.display = "none";

                if (trIdx == wall.querySelectorAll("tr").length - 2)
                  moveDownItem.style.display = "none";
              }

              if (isCol && wall.dataset.cols == "1")
              {
                moveLeftItem.style.display = "none";
                moveRightItem.style.display = "none";
              }

              if (!isCol && wall.dataset.rows == "1")
              {
                moveUpItem.style.display = "none";
                moveDownItem.style.display = "none";
              }
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
              if (settings.wall.wall ("isShared"))
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
                  plugin.saveCurrentWidth ();

                  H.openConfirmPopover ({
                    type: "update",
                    item: $li.parent().parent().find(".btn-menu"),
                    title: `<i class="fas fa-grip-lines${isCol?"-vertical":""} fa-fw"></i> ${(isCol)?"<?=_("Column name")?>":"<?=_("Row name")?>"}`,
                      content: `<input type="text" class="form-control form-control-sm" value="${$header.find(".title").text()}" maxlength="<?=DbCache::getFieldLength('headers', 'title')?>">`,
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

            case "move-up":
            case "move-down":
            case "move-left":
            case "move-right":

              plugin.moveColRow (action);
              break;
          }
        });

        $header.find(".title").editable ({
          wall: $wall,
          container: $header,
          maxLength: <?=DbCache::getFieldLength('headers', 'title')?>,
          triggerTags: ["th", "div"],
          fontSize: "14px",
          callbacks: {
            before: () => plugin.saveCurrentWidth (),
            edit: (cb) => !S.get ("dragging") && plugin.edit (cb),
            unedit: () => plugin.unedit (),
            update: (v) => plugin.setTitle (v, true)
          }
        });

        $part.prependTo ($header);
      }
      else
        $(`<ul class="navbar-nav mr-auto submenu"></ul>`).prependTo ($header);

      if (settings.picture)
        $header.append (plugin.getImgTemplate (settings.picture));
    },

    // METHOD moveRow ()
    moveColRow (move, nosynchro)
    {
      const $th = this.element,
            $tr = $th.closest ("tr"),
            $wall = this.settings.wall,
            $cell = $wall.find ("td:eq(0)");

      switch (move)
      {
        case "move-up":

          $tr.insertBefore ($tr.prev ());
          break;

        case "move-down":
          $tr.insertAfter ($tr.next ());
          break;

        case "move-left":

          var idx = $th.index() - 1;

          $th.insertBefore ($tr.find("th:eq("+idx+")"));
          $wall.find("tr").each (function ()
            {
              $(this).find("td:eq("+idx+")")
                .insertBefore ($(this).find("td:eq("+(idx-1)+")"));
            });
          break;

        case "move-right":

          var idx = $th.index() - 1;

          $th.insertAfter ($tr.find("th:eq("+(idx-1)+")"));
          $wall.find("tr").each (function ()
            {
              $(this).find("td:eq("+idx+")")
                .insertAfter ($(this).find("td:eq("+(idx+1)+")"));
            });
          break;
      }

      if (!nosynchro)
        $cell.cell ("unedit", false, {
          headerId: this.settings.id,
          move: move
        });
    },

    // METHOD showUserWriting ()
    showUserWriting (user)
    {
      setTimeout (()=>this.element.prepend (`<div class="user-writing main" data-userid="${user.id}"><i class="fas fa-user-edit blink"></i> ${user.name}</div>`), 150);
    },

    // METHOD useFocusTrick ()
    useFocusTrick ()
    {
      return (this.settings.wall.wall("isShared") &&
              H.haveMouse() && !H.navigatorIsEdge());
    },

    // METHOD saveCurrentWidth ()
    saveCurrentWidth ()
    {
      // Save current TH width
      this.settings.thwidth = this.element.outerWidth ();
    },

    // METHOD addUploadLayer ()
    addUploadLayer ()
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
    uploadPicture ($item)
    {
      const plugin = this,
            $header = plugin.element,
            settings = plugin.settings;

      if (!settings.wall.wall("isShared") || H.navigatorIsEdge ())
        $(".upload.header-picture").click ();
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
                $(".upload.header-picture").click ();
              }
            }, 150)
        };
      }
    },

    // METHOD removeUploadLayer ()
    removeUploadLayer ()
    {
      $("#upload-layer").hide ();
    },

    // METHOD getImgTemplate ()
    getImgTemplate (src)
    {
      const plugin = this,
            $header = plugin.element,
            adminAccess = H.checkAccess ("<?=WPT_WRIGHTS_ADMIN?>",
                            plugin.settings.access),
            $img = $("<div class='img'><img src='"+src+"'></div>");

      // Refresh postits plugs once picture has been fully loaded
      $img.find("img")
        .on("load", function (e)
        {
          plugin.settings.wall.wall ("repositionPostitsPlugs");
        });

      if (!adminAccess)
        return $img;
      
      $img
        .on("click",function(e)
          {
            e.stopImmediatePropagation ();

            if (plugin.settings.wall.wall ("isShared"))
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
    setImg (src)
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
    deleteImg ()
    {
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
            const header = this.element[0],
                  oldW = header.getBoundingClientRect().width,
                  img = header.querySelector (".img");

            if (this.settings.item_type == "col")
              img.remove ();
            else
              H.headerRemoveContentKeepingWallSize ({
                oldW: oldW,
                cb: () => img.remove ()
              });

            this.unedit ();
          }
        }
      );
    },

    // METHOD update ()
    update (header)
    {
      if (header.hasOwnProperty ("title"))
        this.setTitle (header.title);

      if (header.hasOwnProperty ("picture"))
        this.setImg (header.picture);
    },

    // METHOD setTitle ()
    setTitle (title, resize)
    {
      const header = this.element[0];

      title = H.noHTML (title);

      header.querySelector(".title").innerHTML = title||"&nbsp;";

      if (resize)
      {
        const $wall = this.settings.wall,
              oldW = this.settings.thwidth,
              isRow = (this.settings.item_type == "row");

        if (isRow)
        {
          $wall[0].style.width = "auto";
          header.style.width = 0;
        }

        H.waitForDOMUpdate (()=>
          {
            const newW = header.getBoundingClientRect().width;

            if (isRow || newW > oldW)
            {
              $wall.wall ("fixSize", oldW, newW);

              if (!isRow)
              {
                $wall.find("tbody tr")
                  .find("td:eq("+(header.cellIndex-1)+")").each (function ()
                  {
                    this.style.width = newW+"px";
                    this.querySelector(".ui-resizable-s").style.width =
                      (newW+2)+"px";
                  });
              }
            }
            else
              $wall.wall ("fixSize");

            this.unedit ();
          });
      }
    },

    // METHOD edit ()
    edit (success_cb, error_cb)
    {
      this.setCurrent ();

      _originalObject = _serializeOne (this.element[0]);

      if (!this.settings.wall.wall ("isShared"))
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
    setCurrent ()
    {
      this.element[0].classList.add ("current");
    },

    // METHOD unsetCurrent ()
    unsetCurrent ()
    {
      S.reset ("header");
      this.element[0].classList.remove ("current");
    },

    // METHOD cancelEdit ()
    cancelEdit (bubble_event_cb)
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
    serialize ()
    {
      const wall = this.settings.wall[0],
            headers = {cols: [], rows: []};

      wall.querySelectorAll("thead th").forEach ((header)=>
        (header.cellIndex > 0) && headers.cols.push (_serializeOne (header)));

      wall.querySelectorAll("tbody th").forEach (
        (header)=> headers.rows.push (_serializeOne (header)));

      return headers;
    },

    // METHOD unedit ()
    unedit (args = {})
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
      if (H.updatedObject(_originalObject, _serializeOne (this.element[0])))
      {
        data = {
          headers: this.serialize (),
          cells: $("<div/>").cell ("serialize"),
          wall: {width: Math.trunc($wall.outerWidth ())}
        };

        $wall.find("tbody td").cell ("reorganize");
      }
      else if (!this.settings.wall.wall ("isShared"))
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
              $(window).on("focus.header", function ()
                {
                  $(window).off ("focus.header");

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

              H.getUploadedFiles (e.target.files, "\.(jpe?g|gif|png)$",
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
