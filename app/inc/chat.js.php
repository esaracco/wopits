<?php
/**
  Javascript plugin - Chat

  Scope: Wall
  Element: .chat
  Description: Manage chat
*/

  require_once (__DIR__.'/../prepend.php');

  $Plugin = new Wopits\jQueryPlugin ('chat');
  echo $Plugin->getHeader ();

?>

/////////////////////////// PUBLIC METHODS ////////////////////////////

  // Inherit from Wpt_toolbox
  Plugin.prototype = Object.create(Wpt_toolbox.prototype);
  Object.assign (Plugin.prototype,
  {
    // METHOD init ()
    init ()
    {
      const plugin = this,
            $chat = plugin.element;

      $chat
        .draggable({
          distance: 10,
          cursor: "move",
          drag: (e, ui)=> plugin.fixDragPosition (ui),
          stop: ()=> S.set ("dragging", true, 500),
          start: ()=> plugin.closeUsersTooltip (),
        })
        .resizable({
          handles: "all",
          autoHide: !$.support.touch,
          minHeight: 200,
          minWidth: 200,
          start: function ()
            {
              plugin.closeUsersTooltip ();
            },
          resize: function (e, ui)
            {
              $chat.find(".textarea").css ("height", ui.size.height - 100);
            }
        })
        .append (`<button type="button" class="close" data-dismiss="modal"><span>&times;</span></button><h2><i class="fas fa-fw fa-comments"></i> <?=_("Chat room")?> <div class="usersviewcounts"><i class="fas fa-user-friends"></i> <span class="wpt-badge"></span></div></h2><div><div class="textarea form-control"><span class="btn btn-sm btn-secondary btn-circle btn-clear" data-toggle="tooltip" title="<?=_("Clear history")?>"><i class="fa fa-broom"></i></span><ul></ul></div></div><div class="console"><input type="text" name="msg" autofocus value="" class="form-control form-control-sm"><button type="button" class="btn btn-xs btn-primary">Envoyer</button></div>`)
        .on("mouseleave focusout", function ()
        {
          plugin.closeUsersTooltip ();
        })
        .on("keypress", function (e)
        {
          if (e.which == 13 && e.target.tagName == "INPUT")
          {
            e.preventDefault ();

            $(this).find(".btn-primary").click ();
          }
        });

      // Needed for touch devices
      $chat.find("input").on("click",
        function ()
        {
          $(this).focus ();
        });

      $chat.find(".close").on("click",
        function ()
        {
          plugin.hide ();
        });

      $chat.find(".btn-clear").on("click",
        function ()
        {
          if (H.disabledEvent ())
            return;

          $(this).parent().find("li").remove ();
        });

      $chat.find("button.btn-primary").on("click",
        function ()
        {
          const msg = H.noHTML ($chat.find("input").val());

          if (!msg)
            return;

          plugin.sendMsg (H.noHTML($chat.find("input").val()));
          plugin.setFocus ();
          $chat.find("input").val ("");
        });

      H.enableTooltips ($chat);
    },

    // METHOD hide ()
    hide ()
    {
      if (this.element.is (":visible"))
        $("#main-menu").find("li[data-action='chat'] a").click ();
    },

    // METHOD join ()
    join ()
    {
      H.request_ws (
        "PUT",
         "wall/"+this.settings.wallId+"/chat");
    },

    // METHOD leave ()
    leave ()
    {
      H.request_ws (
        "DELETE",
         "wall/"+this.settings.wallId+"/chat");
    },

    // METHOD setFocus ()
    setFocus (delay = 0)
    {
      if (H.haveMouse ())
        setTimeout (() => this.element.find("[autofocus]").focus (), delay);
    },

    // METHOD toggle ()
    toggle ()
    {
      const $chat = this.element,
            wallId = this.settings.wallId;

      if ($chat.is (":visible"))
      {
        this.closeUsersTooltip (true);

        H.request_ws (
          "DELETE",
          "wall/"+wallId+"/chat");

        $chat.hide ();
      }
      else
      {
        const el = document.querySelector (
                     "#wall-"+wallId+" .wall-menu .chat-alert");

        if (el)
          el.remove ();

        $chat.css ({bottom: "15px", left: "5px", display: "table"});

        this.setFocus (150);

        H.request_ws (
          "PUT",
          "wall/"+wallId+"/chat");
      }
    },

    // METHOD removeAlert ()
    removeAlert ()
    {
      const el = document.querySelector (
                   "#wall-"+this.settings.wallId+" .wall-menu .chat-alert");

      if (el)
        el.remove ();
    },

    // METHOD closeUsersTooltip ()
    closeUsersTooltip (full = false)
    {
      const $tooltip = this.element.find (".usersviewcounts");

      if (full)
      {
        $tooltip.tooltip ("dispose");
        $tooltip.removeAttr ("data-title");
        $tooltip.removeAttr ("data-original-title");
      }
      else
        $tooltip.tooltip ("hide");
    },

    // METHOD refreshUserscount ()
    refreshUserscount (args)
    {
      const $chat = this.element,
            $tooltip = $chat.find (".usersviewcounts"),
            userId = wpt_userData.id;

      if (!args)
        args = {userscount: 0, userslist: []};

      this.closeUsersTooltip (true);

      $chat.find("h2 .wpt-badge").html (args.userscount);
  
       let title = "";
       args.userslist.forEach (
         (user) => (user.id != userId) ? title += `${user.name}<br>`:'');
       $tooltip.attr ("title", title);
       $tooltip.tooltip ({
         html: true,
         trigger: (!H.haveMouse ()) ? "click" : "hover focus"
       });
    },

    // METHOD addMsg ()
    addMsg (args)
    {
      const plugin = this,
            $chat = plugin.element,
            $area = $chat.find (".textarea"),
            isHidden = $chat.is (":hidden");
      let html;

      if (args.internal !== undefined)
      {
        plugin.refreshUserscount (args);

        switch (args.msg)
        {
          case "_JOIN_":
            html = `<li class="internal join"><i class="fas fa-arrow-right"></i> ${args.username}</li>`;
            break;

           case "_LEAVE_":
            html = `<li class="internal leave"><i class="fas fa-arrow-left"></i> ${args.username}</li>`;
            break;
        }
      }
      else
        html = (`<li class="${args.msgId?"current":""}"><span>${args.msgId?'<i class="fas fa-user fa-sm"></i>':args.username}</span> ${args.msg}</li>`);

      $area.find("ul").append (html);

      if (isHidden && args.method != "DELETE")
      {
        let el = $("#wall-"+this.settings.wallId+
                   " .wall-menu .chat-alert .wpt-badge")[0];

        if (el)
          el.textContent = Number (el.textContent) + 1;
        else
        $(`<li class="chat-alert"><i class="fas fa-comments fa-fw fa-lg set"></i><span class="wpt-badge">1</span></li>`)
          .on("click", function ()
          {
            $("#main-menu").find("li[data-action='chat'] input").click ();
          })
          .appendTo ($("#wall-"+this.settings.wallId+" .wall-menu"));
      }

      plugin.setCursorToEnd ();
    },

    // METHOD setCursorToEnd ()
    setCursorToEnd ()
    {
      const area = this.element.find(".textarea")[0];

      area.scrollTop = area.scrollHeight;
    },

    // METHOD sendMsg ()
    sendMsg (msg)
    {
      H.request_ws (
        "POST",
        "wall/"+this.settings.wallId+"/chat",
        {msg: msg});
    },

    // METHOD reset ()
    reset ()
    {
      const $chat = this.element;

      $chat.find(".textarea").html ("");
      $chat.find("input").val ("");
    }

  });

<?php echo $Plugin->getFooter ()?>
