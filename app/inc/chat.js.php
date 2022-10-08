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
    closeVKB: false,
    // METHOD init ()
    init ()
    {
      const plugin = this,
            $chat = plugin.element,
            chat = $chat[0];

      $chat
        .draggable({
          distance: 10,
          cursor: "move",
          drag: (e, ui)=> plugin.fixDragPosition (ui),
          stop: ()=> S.set ("dragging", true, 500)
        })
        .resizable({
          handles: "all",
          autoHide: !$.support.touch,
          minHeight: 200,
          minWidth: 200,
          resize: function (e, ui)
            {
              $chat.find(".textarea").css ("height", ui.size.height - 100);
            }
        })
        .append (`<button type="button" class="btn-close"></button><h2><i class="fas fa-fw fa-comments"></i> <?=_("Chat room")?> <div class="usersviewcounts"><i class="fas fa-user-friends"></i> <span class="wpt-badge"></span></div></h2><div><div class="textarea form-control"><span class="btn btn-sm btn-secondary btn-circle btn-clear" title="<?=_("Clear history")?>"><i class="fa fa-broom"></i></span><ul></ul></div></div><div class="console"><input type="text" name="msg" value="" class="form-control form-control-sm"><button type="button" class="btn btn-xs btn-primary">Envoyer</button></div>`);

      const input = chat.querySelector('input');

      // EVENT "keypress" on main input
      input.addEventListener ("keypress", (e)=>
        {
          if (e.which == 13)
          {
            H.preventDefault (e);

            chat.querySelector(".btn-primary").click ();
          }
        });

      input.addEventListener('focus', (e) => {
        if (!H.haveMouse() && !S.get('vkbData')) {
          this.closeVKB = H.fixVKBScrollStart();
        }
      });

      input.addEventListener('blur', (e) => {
        if (this.closeVKB && S.get('vkbData')) {
          H.fixVKBScrollStop();
          this.closeVKB = false;
        }
      });

      // EVENT "click" on main input
      // Needed for touch devices
      input.addEventListener ("click",
        (e)=> e.target.focus());

      // EVENT "click" on close button
      chat.querySelector(".btn-close").addEventListener ("click",
        (e)=> plugin.hide ());

      // EVENT "click" on "clear" button
      chat.querySelector(".btn-clear").addEventListener ("click", (e)=>
        {
          if (H.disabledEvent ())
            return false;

          e.target.closest(".form-control").querySelectorAll("li").forEach (
            (el)=> el.remove ());

          input.focus ();
        });

      // EVENT "click" on "send" button
      chat.querySelector("button.btn-primary").addEventListener ("click", (e)=>
        {
          const msg = H.noHTML (input.value);

          if (!msg)
            return;

          plugin.sendMsg (msg);
          plugin.setFocus ();
          input.value = "";
        });
    },

    // METHOD hide ()
    hide ()
    {
      if (this.element.is (":visible"))
        document.querySelector(`#main-menu li[data-action="chat"]`).click ();
    },

    // METHOD join ()
    join ()
    {
      H.request_ws (
        "PUT",
        `wall/${this.settings.wallId}/chat`);
    },

    // METHOD leave ()
    leave ()
    {
      H.request_ws (
        "DELETE",
        `wall/${this.settings.wallId}/chat`);
    },

    // METHOD setFocus ()
    setFocus ()
    {
      H.setAutofocus (this.element[0]);
    },

    // METHOD toggle ()
    toggle ()
    {
      const $chat = this.element,
            wallId = this.settings.wallId;

      if ($chat.is (":visible"))
      {
        H.request_ws (
          "DELETE",
          `wall/${wallId}/chat`);

        $chat.hide ();
      }
      else
      {
        const el = document.querySelector (
                     `#wall-${wallId} .wall-menu .chat-alert`);

        if (el)
          el.remove ();

        $chat.css ({bottom: "15px", left: "5px", display: "table"});

        this.setFocus ();

        H.request_ws (
          "PUT",
          `wall/${wallId}/chat`);
      }
    },

    // METHOD removeAlert ()
    removeAlert ()
    {
      const el = document.querySelector (
                   `#wall-${this.settings.wallId} .wall-menu .chat-alert`);

      if (el)
        el.remove ();
    },

    // METHOD refreshUserscount ()
    refreshUserscount (args)
    {
      const chat = this.element[0],
            userId = wpt_userData.id

      if (!args)
        args = {userscount: 0, userslist: []};

      chat.querySelector(".wpt-badge").innerHTML = args.userscount;

      let title = "";
       args.userslist.forEach (
         (el)=> (el.id != userId) ? title += `, ${el.name}`:"");
       chat.querySelector(".usersviewcounts").title = title.substring(1);
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
        const el = $(`#wall-${this.settings.wallId} .wall-menu .chat-alert `+
                     `.wpt-badge`)[0];

        if (el)
          el.textContent = Number (el.textContent) + 1;
        else
        {
          const wmenu = document.querySelector(
                          `#wall-${this.settings.wallId} .wall-menu`);

          wmenu.appendChild ($(`<li class="chat-alert"><i class="fas fa-comments fa-fw fa-lg set"></i><span class="wpt-badge">1</span></li>`)[0]);

          wmenu.querySelector(".chat-alert").addEventListener ("click",
            (e)=> document.querySelector(`#main-menu li[data-action="chat"]`)
                    .click());
        }
      }

      plugin.setCursorToEnd ();
    },

    // METHOD setCursorToEnd ()
    setCursorToEnd ()
    {
      const el = this.element[0].querySelector(".textarea");

      el.scrollTop = el.scrollHeight;
    },

    // METHOD sendMsg ()
    sendMsg (msg)
    {
      H.request_ws (
        "POST",
        `wall/${this.settings.wallId}/chat`,
        {msg: msg});
    },

    // METHOD reset ()
    reset ()
    {
      const $chat = this.element[0];

      chat.querySelector(".textarea").innerText = "";
      chat.querySelector("input").value = "";
    }

  });

<?php echo $Plugin->getFooter ()?>
