<?php

  require_once (__DIR__.'/../prepend.php');

  $Plugin = new Wopits\jQueryPlugin ('smenu');
  echo $Plugin->getHeader ();

?>

  const _noDisplayBtn = `<div class="mt-2"><button type="button" class="btn btn-xs btn-primary nodisplay"><?=_("I get it !")?></button></div>`;
  let _data = {postits: {}, dest: null};

  /////////////////////////// PUBLIC METHODS ////////////////////////////

  // Inherit from Wpt_toolbox
  Plugin.prototype = Object.create(Wpt_toolbox.prototype);
  Object.assign (Plugin.prototype,
  {
    // METHOD init ()
    init (args)
    {
      const plugin = this,
            $sm = plugin.element;

      plugin.reset ();

      $sm
        .draggable({
          //FIXME "distance" is deprecated -> is there any alternative?
          distance: 10,
          cursor: "move",
          drag: (e, ui)=> plugin.fixDragPosition (ui),
          stop: ()=> S.set ("still-dragging", true, 500)
        });

      $sm.find("button.close").on("click", ()=> plugin.close ());

      // EVENT click on menu
      $sm.find("li").on("click", function (e)
        {
          if (!H.checkAccess ("<?=WPT_WRIGHTS_RW?>") ||
              S.get ("still-dragging") ||
              $sm.find("li:visible").length == 1)
            return;

          const icon = this.querySelector ("i"),
                set = icon.classList.contains ("set");
          let title, content, cbOK;

          $sm.find("i").removeClass ("set");

          if (set)
            return;

          icon.classList.add ("set");

          switch (this.dataset.action)
          {
            case "delete":
            case "cpick":
              return plugin.apply ({event: e});

            case "copy":
              if (!ST.noDisplay ("smenu-copy-help"))
              {
                title = `<i class="fas fa-paste fa-fw"></i> <?=_("Copy")?>`;
                content = "<?=_("<kbd>ctrl+click</kbd> on the destination cell to copy the selected notes.")?>"+_noDisplayBtn;
                cbOK = ()=> ST.noDisplay ("smenu-copy-help", true);
              }
              break;

            case "move":
              if (!ST.noDisplay ("smenu-move-help"))
              {
                title = `<i class="fas fa-cut fa-fw"></i> <?=_("Move")?>`;
                content = "<?=_("<kbd>ctrl+click</kbd> on the destination cell to move the selected notes.")?>"+_noDisplayBtn;
                cbOK = ()=> ST.noDisplay ("smenu-move-help", true);
              }
              break
          }

          if (title)
            H.openConfirmPopover ({
              item: $(this),
              type: "info",
              title: title,
              placement: "right",
              content: content,
              cb_ok: cbOK
            });
        });
    },

    // METHOD getAction ()
    getAction ()
    {
      const el = this.element[0].querySelector (".set");

      return el ? el.parentNode.dataset.action : null;
    },

    // METHOD apply ()
    apply (args = {})
    {
      let item = args.cellPlugin ? args.cellPlugin.element : null,
          type, title, content, cbClose;

      if (!H.checkAccess ("<?=WPT_WRIGHTS_RW?>"))
        return H.displayMsg ({
          type: "warning",
          msg: "<?=_("You need write access to perform this action!")?>"
        });

      _data.dest = args.cellPlugin;

      switch (this.getAction ())
      {
        case "copy":
          title =  `<i class="fas fa-paste fa-fw"></i> <?=_("Copy")?>`;
          content = "<?=_("Do you want to copy the selected notes in this cell?")?>";
          break;

        case "move":
          title = `<i class="fas fa-cut fa-fw"></i> <?=_("Move")?>`;
          content = "<?=_("Do you want to move the selected notes in this cell?")?>";
          break;

        case "delete":
          item = this.element.find ("[data-action='delete']");
          title = `<i class="fas fa-trash fa-fw"></i> <?=_("Delete")?>`;
          content = "<?=_("Delete selected notes?")?>";
          break;

        case "cpick":
          return this.send (args);

        default:
          type = "info";
          item = this.element.find ("li:eq(0)");
          title = "<?=_("Copy/Move")?>";
          content = "<?=_("Please, select the type of action first.")?>";
      }

      if (title)
      {
        if (_data.dest)
          _data.dest.element.addClass ("selected");

        H.openConfirmPopover ({
          item: item,
          type: type,
          title: title,
          content: content,
          cb_close: ()=>
          {
            args.event.target.classList.remove ("set");

            cbClose && cbClose ();

            if (_data.dest)
            {
              _data.dest.element.removeClass ("selected");
              _data.dest = null;
            }
          },
          cb_ok: ()=> this.send (args)
        });
      }
    },

    // METHOD send ()
    send (args = {})
    {
      const action = this.getAction ();

      // Color picker
      switch (action)
      {
        case "cpick":
          $("#cpick").cpick ("open", {
            event: args.event,
            cb_close: ()=> args.event.target.classList.remove ("set"),
            cb_click: (c)=>
            {
              H.request_ws (
                "POST",
                "postits/color",
                {
                  color: c.className,
                  postits: Object.keys (_data.postits)
                }
              );
            }
          });
        break;

        // Delete
        case "delete":
          H.request_ws (
            "DELETE",
            "postits",
            {postits: Object.keys (_data.postits)},
            // success cb
            ()=> this.close ());
          break;

        // Copy
        case "copy":
        // Move
        case "move":
          const cellSettings = _data.dest.settings;

          H.request_ws (
            "PUT",
            "wall/"+cellSettings.wallId+
              "/cell/"+cellSettings.id+"/postits/"+action,
            {postits: Object.keys (_data.postits)},
            // success cb
            ()=> (action == "move") && this.close ());
          break;
      }
    },

    // METHOD isEmpty ()
    isEmpty ()
    {
      return !this.itemsCount ();
    },

    // METHOD reset ()
    reset ()
    {
      this.removeAll ();
      this.element.find(".set").removeClass ("set");

      _data = {postits: {}, dest: null};
    },

    // METHOD refresh ()
    refresh ()
    {
      for (const id in _data.postits)
        if (!document.querySelector (
              ".postit.selected[data-id='postit-"+id+"']"))
          this.remove (id);
    },

    // METHOD add ()
    add (p)
    {
      if (this.isEmpty ())
        this.open ();

      p.settings.cell[0].querySelectorAll(
        "[data-id='"+p.element[0].dataset.id+"']")
           .forEach ((_p)=> _p.classList.add ("selected"));

      _data.postits[p.settings.id] = p;

      this.refreshItemsCount ();
      this.checkAllowedActions ();
    },

    // METHOD update ()
    update (id, p)
    {
      _data.postits[id] = p;
    },

    // METHOD remove ()
    remove (id)
    {
      const p = _data.postits[id];

      p.settings.cell[0].querySelectorAll(
        ".selected[data-id='"+p.element[0].dataset.id+"']")
           .forEach ((_p)=> _p.classList.remove ("selected"));

      delete _data.postits[id];

      this.refreshItemsCount ();

      if (this.isEmpty ())
        this.close ();
      else
        this.checkAllowedActions ();
    },

    // METHOD removeAll ()
    removeAll ()
    {
      for (const id in _data.postits)
        this.remove (id);
    },

    // METHOD itemsCount ()
    itemsCount ()
    {
      return Object.keys(_data.postits).length;
    },

    // METHOD refreshItemsCount ()
    refreshItemsCount ()
    {
      this.element.find(".wpt-badge").text (this.itemsCount ());
    },

    // METHOD open ()
    open ()
    {
      if (!H.haveMouse ())
        return;

      const plugin = this;

      if (plugin.element.is (":visible"))
        return;

      this.element.show ();

      if (!$(".modal:visible").length)
        this.showHelp ();
    },

    // METHOD checkAllowedActions ()
    checkAllowedActions ()
    {
      this.element[0].style.opacity = 1;
      this.element.find("li").show ();

      for (const id in _data.postits)
        if (!H.checkAccess ("<?=WPT_WRIGHTS_RW?>",
               _data.postits[id].settings.wall[0].dataset.access))
        {
          //FIXME //TODO trigger on btn copy menu item
          this.element.find("[data-action='copy'] i").addClass ("set");
          this.element.find("li:not([data-action='copy'])").hide ();

          return;
        }

      if (!H.checkAccess ("<?=WPT_WRIGHTS_RW?>"))
      {
        this.element.find("[data-action='copy'] i.set").removeClass ("set");
        this.element[0].style.opacity = 0.3;
      }
    },

    // METHOD showHelp ()
    showHelp ()
    {
      const writeAccess = H.checkAccess ("<?=WPT_WRIGHTS_RW?>");

      if (ST.noDisplay ("smenu-help-"+writeAccess))
        return;

      let content;

      if (writeAccess)
        content = "<?=_("Use this menu to execute actions on multiple notes")?>:<ul><li><?=_("To select / unselect, <kbd>ctrl+click</kbd> on the note.")?></li><li><?=_("To <b>copy</b> %s1 or <b>move</b> %s2, choose the appropriate icon and <kbd>ctrl+click</kbd> on the destination cell.")?></li><li><?=_("To <b>change color</b>, click on %s3")?></li><li><?=_("To <b>delete</b>, click on %s4")?></li></ul>".replace("%s1", `<i class="fas fa-paste fa-sm"></i>`).replace("%s2", `<i class="fas fa-cut fa-sm"></i>`).replace("%s3", `<i class="fas fa-palette fa-sm"></i>`).replace("%s4", `<i class="fas fa-trash fa-sm"></i>`);
      else
        content = "<?=_("Use this menu to execute actions on multiple notes")?>:<ul><li><?=_("To select / unselect, <kbd>ctrl+click</kbd> on the note.")?></li><li><?=_("<kbd>ctrl+click</kbd> on the destination cell to copy the selected notes.")?></li></ul>";

      H.openConfirmPopover ({
        item: this.element,
        type: "info",
        title: "<i class='fas fa-cogs fa-fw'></i> <?=_("Batch actions")?>",
        placement: "right",
        content: content+_noDisplayBtn,
        cb_ok: ()=> ST.noDisplay ("smenu-help-"+writeAccess, true)
      });
    },

    // METHOD close ()
    close ()
    {
      const $ps = $("#psearchPopup");

      document.querySelectorAll(".postit.selected").forEach (
        (p)=> p.classList.remove ("selected"));

      if ($ps.is (":hidden"))
        $ps.psearch ("reset", true);

      this.reset ();
      this.element.hide ();
    }
  });

  /////////////////////////// AT LOAD INIT //////////////////////////////

  $(function ()
    {
      if (!H.isLoginPage ())
      {
        $("body").prepend (`<ul class="toolbox" id="smenu"><button type="button" class="close"><span>&times;</span></button><span class="wpt-badge">0</span><li data-toggle="tooltip" title="<?=_("Copy selected notes")?>" data-action="copy"><i class="fas fa-paste fa-fw fa-lg"></i></li><li data-toggle="tooltip" title="<?=_("Move selected notes")?>" data-action="move"><i class="fas fa-cut fa-fw fa-lg"></i></li><li class="divider"></li><li data-toggle="tooltip" title="<?=_("Change selected notes color")?>" data-action="cpick"><i class="fas fa-palette fa-fw fa-lg"></i></li><li data-toggle="tooltip" title="<?=_("Delete selected notes")?>" data-action="delete"><i class="fas fa-trash fa-fw fa-lg"></i></li></ul`);

        S.getCurrent("smenu").smenu ();
      }
    });

<?php echo $Plugin->getFooter ()?>
