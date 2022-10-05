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
      _originalObject;

  /////////////////////////// PRIVATE METHODS ///////////////////////////

  // METHOD serializeOne ()
  const _serializeOne = (th)=>
    {
      const img = th.querySelector ("img");

      return {
        id: th.dataset.id.substring (7),
        width: Math.trunc (th.offsetWidth),
        height: Math.trunc (th.offsetHeight),
        title: th.querySelector(".title").innerText,
        picture: img ? img.getAttribute ("src") : null
      };
    };

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

      $header[0].dataset.id = `header-${settings.id}`;

      $header.append (`<div class='title'>${(settings.title!=" ")?settings.title:"&nbsp;"}</div>`);

      if (adminAccess)
      {
        const $part = $(`<ul class="navbar-nav mr-auto submenu"><li class="nav-item dropdown"><div data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" class="nav-link dropdown-toggle"><i class="far fa-caret-square-right btn-menu" data-placement="right"></i></div><ul class="dropdown-menu border-0 shadow"><li data-action="rename"><a class="dropdown-item" href="#"><i class="fa-fw fas fa-edit"></i> <?=_("Rename")?></a></li><li data-action="add-picture"><a class="dropdown-item" href="#"><i class="fa-fw fas fa-camera-retro"></i> <?=_("Associate a picture")?></a></li>${isCol?`<li data-action="move-left"><a class="dropdown-item" href="#"><i class="fa-fw fas fa-chevron-left"></i> <?=_("Move left")?></a></li><li data-action="move-right"><a class="dropdown-item" href="#"><i class="fa-fw fas fa-chevron-right"></i> <?=_("Move right")?></a></li>`:`<li data-action="move-up"><a class="dropdown-item" href="#"><i class="fa-fw fas fa-chevron-up"></i> <?=_("Move up")?></a></li><li data-action="move-down"><a class="dropdown-item" href="#"><i class="fa-fw fas fa-chevron-down"></i> <?=_("Move down")?></a></li>`}</li><li data-action="delete"><a class="dropdown-item" href="#"><i class="fa-fw fas fa-trash"></i> <?=_("Delete")?> <span></span></a></li></ul></li></ul>`);

        // EVENT "show.bs.dropdown" on header menu
        $part[0].addEventListener ("show.bs.dropdown", (e)=>
          {
            const el = e.target,
                  $menu = $(el.closest("ul")),
                  menu = $menu[0],
                  wall = $wall[0],
                  tr = $header.closest("tr.wpt")[0],
                  deleteItem =
                    menu.querySelector(`[data-action="delete"] a`),
                  moveUpItem =
                    menu.querySelector(`[data-action="move-up"] a`),
                  moveDownItem =
                    menu.querySelector(`[data-action="move-down"] a`),
                  moveLeftItem =
                    menu.querySelector(`[data-action="move-left"] a`),
                  moveRightItem =
                    menu.querySelector(`[data-action="move-right"] a`);

            $menu.find("a").show ();
            el.querySelector (".nav-link i.far")
              .classList.replace ("far", "fas");

            if (isCol)
            {
              const thIdx = $header.index ();

              if (wall.querySelectorAll("thead.wpt th.wpt").length <= 2)
                deleteItem.style.display = "none";

              if (thIdx == 1)
                moveLeftItem.style.display = "none";

              if (thIdx == tr.querySelectorAll("th.wpt").length-1)
                moveRightItem.style.display = "none";
            }
            else
            {
              const trIdx = $(tr).index ();

              if (wall.querySelectorAll("tbody.wpt th.wpt").length == 1)
                deleteItem.style.display = "none";

              if (trIdx == 0)
                moveUpItem.style.display = "none";

              if (trIdx == wall.querySelectorAll("tr.wpt").length - 2)
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
          });

        // EVENT "hide.bs.dropdown" on header menu
        $part[0].addEventListener ("hide.bs.dropdown", (e)=> 
          e.target.querySelector (".nav-link i.fas")
            .classList.replace ("fas", "far"));

        // EVENT "click" on header menu items
        $part[0].querySelector(".dropdown-menu")
          .addEventListener ("click", (e)=>
          {
            const el = e.target,
                  li = el.closest ("li"),
                  $cell = $(li.closest("th.wpt")),
                  action = li.dataset.action;

            switch (action)
            {
              case "add-picture":
                if (settings.wall.wall ("isShared"))
                {
                  //FIXME
                  // we need this to cancel edit if no img is selected by user
                  // (touch device version)
                  plugin.addUploadLayer ();
    
                  plugin.edit ();
  
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
                           `<?=_("Delete this column?")?>` :
                           `<?=_("Delete this row?")?>`,
                         cb_close: () => plugin.unedit (),
                         cb_ok: () =>
                           {
                             if (isCol)
                               $wall.wall ("deleteCol", $header.index ());
                             else
                              $wall.wall (
                                "deleteRow", $header.closest("tr.wpt").index ());
                           }
                       });
                  });
                break;
    
              case 'rename':
                plugin.edit(() => {
                  plugin.saveCurrentWidth();

                  H.openConfirmPopover({
                    type: 'update',
                    scrollIntoView: isCol,
                    item: $(li.parentNode.parentNode
                              .querySelector(".btn-menu")),
                    title: `<i class="fas fa-grip-lines${isCol?"-vertical":""} fa-fw"></i> ${(isCol)?`<?=_("Column name")?>`:`<?=_("Row name")?>`}`,
                    content: `<input type="text" class="form-control form-control-sm" value="${$header.find(".title").text()}" maxlength="<?=DbCache::getFieldLength('headers', 'title')?>">`,
                    cb_close: () => {
                      if (!S.get('no-unedit')) {
                        plugin.unedit();
                      }
                      S.unset('no-unedit');
                    },
                    cb_ok: ($p) => {
                      S.set('no-unedit', true);
                      plugin.setTitle ($p.find('input').val(), true);
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
            edit: (cb) =>
            {
              if (H.disabledEvent ())
                return false;

              plugin.edit (cb);
            },
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
    moveColRow (move, noSynchro)
    {
      const $th = this.element,
            $tr = $th.closest ("tr.wpt"),
            $wall = this.settings.wall,
            $cell = $wall.find ("td.wpt:eq(0)");

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

          $th.insertBefore ($th.prev ());

          $wall.find("tr.wpt").each (function ()
            {
              const $td = $(this).find (`td.wpt:eq(${idx})`),
                    $tdprev = $td.prev ();

              if ($tdprev.length)
                $td.insertBefore ($tdprev);
            });
          break;

        case "move-right":

          var idx = $th.index() - 1;

          $th.insertAfter ($th.next ());

          $wall.find("tr.wpt").each (function ()
            {
              const $td = $(this).find (`td.wpt:eq(${idx})`),
                    $tdnext = $td.next ();

              if ($tdnext.length)
                $td.insertAfter ($tdnext);
            });
          break;
      }

      if (!noSynchro)
        $cell.cell ("unedit", false, {
          headerId: this.settings.id,
          move: move
        });
    },

    // METHOD showUserWriting ()
    showUserWriting (user)
    {
      setTimeout (()=>
        {
          const header = this.element[0];

          header.classList.add ("locked");
          header.insertBefore ($(`<div class="user-writing main" data-userid="${user.id}"><i class="fas fa-user-edit blink"></i> ${user.name}</div>`)[0], header.firstChild);
        }, 150);
    },

    // METHOD useFocusTrick()
    useFocusTrick () {
      return (this.settings.wall.wall('isShared') &&
              H.haveMouse() &&
              !H.navigatorIsEdge());
    },

    // METHOD saveCurrentWidth ()
    saveCurrentWidth ()
    {
      // Save current TH width
      this.settings.thwidth = this.element.outerWidth ();
    },

    // METHOD addUploadLayer()
    addUploadLayer() {
      const plugin = this;

      if (!plugin.useFocusTrick()) {
        const layer = document.getElementById('upload-layer');

        ['mousedown', 'touchstart'].forEach((type) =>
          layer.addEventListener(type, (e) => plugin.unedit(), {once: true}));

        layer.style.display = 'block';
      }
    },

    //FIXME still useful?
    // METHOD uploadPicture ()
    uploadPicture ($item)
    {
      document.querySelector(".upload.header-picture").click ();
    },

    // METHOD removeUploadLayer ()
    removeUploadLayer ()
    {
      document.getElementById ("upload-layer").style.display = "none";
    },

    // METHOD getImgTemplate ()
    getImgTemplate (src)
    {
      const plugin = this,
            $header = plugin.element,
            adminAccess = H.checkAccess ("<?=WPT_WRIGHTS_ADMIN?>",
                            plugin.settings.access),
            $img = $(`<div class='img'><img src="${src}"></div>`);

      // EVENT "load" on header picture
      // Refresh postits plugs once picture has been fully loaded
      $img[0].querySelector("img").addEventListener("load",
        (e)=> plugin.settings.wall.wall ("repositionPostitsPlugs"));

      if (!adminAccess)
        return $img;
      
      // EVENT "click" on header picture
      $img[0].addEventListener ("click", (e)=>
        {
          e.stopImmediatePropagation ();

          if (plugin.settings.wall.wall ("isShared"))
          {
            //FIXME
            // we need this to cancel edit if no img is selected by user
            // (touch device version)
            plugin.addUploadLayer ();

            plugin.edit ();

            plugin.uploadPicture ($header);
          }
          else
            plugin.edit (() => plugin.uploadPicture ($header));
        });

      // Create img "delete" button
      const $deleteButton = $(`<button type="button" class="btn-close img-delete"></button>`);

      // EVENT "click" on header picture
      $deleteButton[0].addEventListener ("click", (e)=>
        {
          e.stopImmediatePropagation ();

          plugin.edit (() =>
            {
              H.openConfirmPopover ({
                item: $(e.target),
                placement: "left",
                title: `<i class="fas fa-trash fa-fw"></i> <?=_("Delete")?>`,
                content: `<?=_("Delete this picture?")?>`,
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
        `wall/${this.settings.wallId}/header/${this.settings.id}/picture`,
        null,
        // success cb
        (d)=>
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
              this.removeContentKeepingWallSize ({
                oldW: oldW,
                cb: () => img.remove ()
              });

            this.unedit ();
          }
        }
      );
    },

    // METHOD removeContentKeepingWallSize ()
    removeContentKeepingWallSize (args)
    {
      const $wall = this.settings.wall,
            th1 = $wall[0].querySelector ("thead.wpt th.wpt");

      args.cb ();

      $wall[0].style.width = "auto";
      th1.style.width = 0;

      $wall.wall ("fixSize", args.oldW, th1.offsetWidth);
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
                $wall.find("tbody.wpt tr.wpt")
                  .find(`td.wpt:eq(${(header.cellIndex-1)})`).each (function ()
                  {
                    this.style.width = `${newW}px`;
                    this.querySelector(".ui-resizable-s")
                      .style.width = `${newW+2}px`;
                  });
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
        `wall/${this.settings.wallId}/editQueue/header/${this.settings.id}`,
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
      const header = this.element[0];

      _realEdit = false;

      this.unsetCurrent ();

      this.settings.wall.wall ("closeAllMenus");

      if (bubble_event_cb)
      {
        header.classList.add ("_current")
        bubble_event_cb ();
        header.classList.remove ("_current")
      }
    },

    // METHOD serialize ()
    serialize ()
    {
      const wall = this.settings.wall[0],
            headers = {cols: [], rows: []};

      wall.querySelectorAll("thead.wpt th.wpt").forEach (th =>
        (th.cellIndex > 0) && headers.cols.push (_serializeOne (th)));

      wall.querySelectorAll("tbody.wpt th.wpt").forEach (th =>
        headers.rows.push (_serializeOne (th)));

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
            title: `<?=_("Wall")?>`,
            type: (args.data.error) ? "danger" : "warning",
            msg: msg
          });
      }

      // Update header only if it has changed
      if (H.updatedObject(_originalObject, _serializeOne (this.element[0])))
      {
        data = {
          headers: this.serialize (),
          cells: $("<div/>").cell ("serialize", {noPostits: true}),
          wall: {width: Math.trunc($wall.outerWidth ())}
        };

        $wall.find("tbody.wpt td.wpt").cell ("reorganize");
      }
      else if (!this.settings.wall.wall ("isShared"))
        return this.cancelEdit (args.bubble_cb);

      H.request_ws (
        "DELETE",
        `wall/${this.settings.wallId}/editQueue/header/${this.settings.id}`,
        data,
        // success cb
        (d)=> this.cancelEdit (args.bubble_cb),
        // error cb
        ()=> this.cancelEdit (args.bubble_cb)
      );
    }
  };

  /////////////////////////// AT LOAD INIT //////////////////////////////

  document.addEventListener('DOMContentLoaded', () => {
    if (H.isLoginPage()) return;

    // Create input to upload header image
    H.createUploadElement({
      attrs: {className: 'header-picture', accept: '.jpeg,.jpg,.gif,.png'},
      onChange: (e) => {
        const el = e.target;

        if (!el.files || !el.files.length) return;

        const $header = S.getCurrent('header');
        const settings = $header.header('getSettings');

        _realEdit = true;

        H.getUploadedFiles(e.target.files, '\.(jpe?g|gif|png)$', (e, file) => {
          el.value = '';

          if (H.checkUploadFileSize({size: e.total}) && e.target.result) {
            const oldW = $header.outerWidth();

            H.fetchUpload(
              `wall/${settings.wallId}/header/${settings.id}/picture`,
              {
                name: file.name,
                size: file.size,
                item_type: file.type,
                content: e.target.result,
              },
              // success cb
              (d) => {
                if (d.error_msg) {
                  return $header.header('unedit', {data: d});
                }

                $header.header('setImg', d.img);
                setTimeout(() => {
                  settings.wall.wall('fixSize', oldW, $header.outerWidth());
                  $header.header('unedit');
                }, 500);
              },
              // error cb
              (d) => $header.header('unedit', {data: d}));
            }
          },
          // error cb
          () => $header.header('unedit'));
      },
      onClick: (e) => {
        const $header = S.getCurrent('header');

        //FIXME
        // we need this to cancel edit if no img is selected by user
        // (desktop version)
        if ($header.header('useFocusTrick')) {
          window.addEventListener('focus',
              (e) => !_realEdit && $header.header('unedit'), {once: true});
        }
      },
    });
  });

<?php echo $Plugin->getFooter()?>
