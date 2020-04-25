<?php
  require_once (__DIR__.'/../class/Wpt_jQueryPlugins.php');
  $Plugin = new Wpt_jQueryPlugins ('chatroom');
  echo $Plugin->getHeader ();
?>

/////////////////////////// PUBLIC METHODS ////////////////////////////

  // Inherit from Wpt_toolbox
  Plugin.prototype = Object.create(Wpt_toolbox.prototype);
  Object.assign (Plugin.prototype,
  {
    // METHOD init ()
    init: function ()
    {
      const plugin = this,
            $chatroom = plugin.element;

      $chatroom
        //FIXME "distance" is deprecated -> is there any alternative?
        .draggable({
          distance:10,
          start: function ()
            {
              plugin.closeUsersTooltip ();
            },
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
              $chatroom.find(".textarea").css("height", ui.size.height - 100);
            }
        })
        .append (`
          <button type="button" class="close" data-dismiss="modal">
            <span>&times;</span>
          </button>
          <h2><i class="fas fa-fw fa-comments"></i> <?=_("Chat room")?> <div class="usersviewcounts"><i class="fas fa-user-friends"></i> <span class="wpt-badge"></span></div></h2>
          <div>
            <div class="textarea form-control">
              <button type="button" class="erase"><span><i class="fas fa-times-circle fa-sm"></i></span></button>
              <ul>

              </ul>
            </div>
          </div>
          <div class="console">
            <input type="text" name="msg" autofocus value="" class="form-control form-control-sm"><button type="button" class="btn btn-xs btn-primary">Envoyer</button>
          </div>
          `)
        .on("mouseleave focusout", function ()
        {
          plugin.closeUsersTooltip ();
        })
        .on("keypress", function (e)
        {
          if (e.which == 13 && e.target.tagName == "INPUT")
          {
            e.preventDefault ();

            $(this).find(".btn-primary").trigger ("click");
          }
        });

      // Needed for touch devices
      $chatroom.find("input").on("click",
        function ()
        {
          $(this).focus ();
        });

      $chatroom.find(".close").on("click",
        function ()
        {
          plugin.hide ();
        });

      $chatroom.find(".erase").on("click",
        function ()
        {
          $(this).parent().find("li").remove ();
        });


      $chatroom.find("button.btn-primary").on("click",
        function ()
        {
          const msg = wpt_noHTML ($chatroom.find("input").val());

          if (!msg)
            return;

          plugin.sendMsg (wpt_noHTML ($chatroom.find("input").val()));
          $chatroom.find("input").val("");
        });
    },

    // METHOD hide ()
    hide: function ()
    {
      if (this.element.is (":visible"))
        $("#main-menu").find("li[data-action='chatroom'] a").trigger ("click");
    },

    // METHOD join ()
    join: function ()
    {
      wpt_request_ws (
        "PUT",
         "wall/"+this.settings.wallId+"/chat");
    },

    // METHOD leave ()
    leave: function ()
    {
      wpt_request_ws (
        "DELETE",
         "wall/"+this.settings.wallId+"/chat");
    },

    // METHOD toggle ()
    toggle: function ()
    {
      const plugin = this,
            $chatroom = plugin.element;

      if ($chatroom.is (":visible"))
      {
        plugin.closeUsersTooltip (true);

        wpt_request_ws (
          "DELETE",
          "wall/"+plugin.settings.wallId+"/chat");

        $chatroom.hide ();
      }
      else
      {
        const $alert = $("#wall-"+plugin.settings.wallId+" .chatroom-alert");

        if ($alert.length)
          $alert.remove ();

        $chatroom.css({bottom: "15px", left: "5px", display: "table"});

        if (!$.support.touch)
          setTimeout (() => $chatroom.find("[autofocus]").focus (), 150);

        wpt_request_ws (
          "PUT",
          "wall/"+plugin.settings.wallId+"/chat");
      }
    },

    // METHOD removeAlert ()
    removeAlert: function ()
    {
      const $el = $("#wall-"+this.settings.wallId+" .chatroom-alert");

      if ($el.length)
        $el.remove ();
    },

    // METHOD closeUsersTooltip ()
    closeUsersTooltip: function (full = false)
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
    refreshUserscount: function (args)
    {
      const plugin = this,
            $chatroom = plugin.element,
            $tooltip = $chatroom.find(".usersviewcounts");

      if ($chatroom.is(":visible"))
      {
        const userId = wpt_userData.id;

        plugin.closeUsersTooltip (true);

        $chatroom.find("h2 .wpt-badge").html (args.userscount);
  
         let title = "";
         args.userslist.forEach (
           (user) => (user.id != userId) ? title += `${user.name}<br>`:'');
         $tooltip.attr("title", title);
         $tooltip.tooltip ({
           html: true,
           trigger: ($.support.touch) ? "click" : "hover focus"
         });
      }
    },

    // METHOD addMsg ()
    addMsg: function (args)
    {
      const plugin = this,
            $chatroom = plugin.element,
            $area = $chatroom.find(".textarea"),
            isHidden = $chatroom.is (":hidden");
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
      {
        html = (`<li class="${args._msgId?"current":""}"><span>${args._msgId?'<i class="fas fa-user fa-sm"></i>':args.username}</span> ${args.msg}</li>`);
      }

      $area.find("ul").append (html);

      if (isHidden && args.method != "DELETE")
      {
        let el =
          $("#wall-"+this.settings.wallId+" .chatroom-alert .wpt-badge")[0];

        if (el)
          el.textContent = Number (el.textContent) + 1;
        else
        $(`<div class="chatroom-alert"><i class="fas fa-comments fa-2x"></i><span class="wpt-badge">1</span></div>`)
          .on("click", function ()
          {
            $("#main-menu").find("li[data-action='chatroom'] input")
              .trigger ("click");
          })
          .insertBefore ($chatroom);
      }

      plugin.setCursorToEnd ();
    },

    // METHOD setCursorToEnd ()
    setCursorToEnd: function ()
    {
      const area = this.element.find(".textarea")[0];

      area.scrollTop = area.scrollHeight;
    },

    // METHOD sendMsg ()
    sendMsg: function (msg)
    {
      wpt_request_ws (
        "POST",
        "wall/"+this.settings.wallId+"/chat",
        {msg: msg});
    },

    // METHOD reset ()
    reset: function ()
    {
      const $chatroom = this.element;

      $chatroom.find(".textarea").html ("");
      $chatroom.find("input").val ("");
    }

  });

<?php echo $Plugin->getFooter ()?>
