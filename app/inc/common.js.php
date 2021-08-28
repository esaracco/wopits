/**
  Global javascript classes:

  Wpt_forms -> parent for forms plugins (swall...)
  Wpt_accountForms -> parent for account forms plugins
  Wpt_toolbox -> parent for toolbox utilities (filters, arrows, mmenu,  chat...)

  WHelper -> alias H (helper methods)
  WSharer -> alias S (tool for sharing values between javascript elements)
  WStorage -> alias ST (localStorage facilities)
  WSocket -> alias WS (WebSocket management)
*/

// CLASS Wpt_forms
class Wpt_forms
{
  // METHOD checkRequired ()
  checkRequired (fields, displayMsg = true)
  {
    const form = fields[0].closest ("form");

    form.querySelectorAll("span.required").forEach (
      (f)=> f.remove ());
    form.querySelectorAll(".required").forEach (
      (f)=> f.classList.remove ("required"));

    for (const f of fields)
    {
      if (f.hasAttribute("required") && !f.value.trim())
      {
        this.focusBadField (f, displayMsg?`<?=_("Required field")?>`:null);
        f.focus ();

        return false;
      }
    }

    return true;
  }

  // METHOD focusBadField ()
  focusBadField (f, msg)
  {
    const group = f.closest (".input-group");

    group.classList.add ("required");

    f.focus ();

    if (msg)
      $(`<span class="required">${msg}:</span>`).insertBefore (group);
        
    setTimeout (()=> group.classList.remove ("required"), 2000);
  }
}

// CLASS Wpt_accountForms
class Wpt_accountForms extends Wpt_forms
{
  // METHOD _checkPassword ()
  _checkPassword (password)
  {
    let ret = true;

    if (password.length < 6 ||
        !password.match (/[a-z]/) ||
        !password.match (/[A-Z]/) ||
        !password.match (/[0-9]/))
    {
      ret = false;
      H.displayMsg ({
        title: `<?=_("Account")?>`,
        type: "warning",
        msg: `<?=_("Your password must contain at least:<ul><li><b>6</b> characters</li><li>One <b>lower case</b> letter and one <b>upper case</b> letter</li><li>One <b>number</b></li></ul>")?>`
      });
    }

    return ret;
  }

  // METHOD _checkEmail ()
  _checkEmail (email)
  {
    return email.match (/^\w+([\.-]?\w+)*@\w+([\.-]?\w+)*(\.\w{2,6})+$/);
  }

  // METHOD validForm ()
  validForm (fields)
  {
    for (const f of fields)
    {
      let val = f.value;

      switch (f.getAttribute ("name"))
      {
        case "wall-width":
        case "wall-height":

          val = Number (val);

          if (val > 0)
          {
            if (f.getAttribute("name") == "wall-width" && val < 300 ||
                f.getAttribute("name") == "wall-height" && val < 200)
              return this.focusBadField (f, `<?=_("The size of a wall cannot be less than %s")?>`.replace("%s", "300x200"));
            else if (val > 20000)
              return this.focusBadField (f, `<?=_("The size of a wall cannot be greater than %s")?>`.replace("%s", "20000x20000"));
          }
          break;

        case "wall-cols":
        case "wall-rows":

          const form = f.closest ("form"),
                cols = Number (form.querySelector(
                                 "input[name='wall-cols']").value)||3,
                rows = Number (form.querySelector(
                                 "input[name='wall-rows']").value)||3;

          if (cols * rows > <?=WPT_MAX_CELLS?>)
            return this.focusBadField (f, `<?=_("For performance reasons, a wall cannot contain more than %s cells")?>`.replace("%s", <?=WPT_MAX_CELLS?>));
          break;

        case "email":

          if (!this._checkEmail (val))
            return this.focusBadField (f, `<?=_("Bad email address")?>`);

          break;

        case "email2":

          if (val != f.closest("form")
                       .querySelector("input[name='email']").value)
            return this.focusBadField (f, `<?=_("Emails must match")?>`);

          break;

        case "username":

          if (val.trim().length < 3 || val.match (/@|&|\\/))
            return this.focusBadField (f, `<?=_("Login is not valid")?>`);

          break;

        case "password":

          if (!this._checkPassword (val))
            return this.focusBadField (f, `<?=_("Unsecured password")?>`);

          break;

        case "password2":

          if (!f.closest("form").querySelector("input[name='password3']"))
          {
            if (val != f.closest("form")
                         .querySelector("input[name='password']").value)
              return this.focusBadField (f, `<?=_("Passwords must match")?>`);
          }
          else if (!this._checkPassword (val))
            return this.focusBadField (f, `<?=_("Unsecured password")?>`);

          break;

        case "password3":

          if (val != f.closest("form")
                       .querySelector("input[name='password2']").value)
            return this.focusBadField (f, `<?=_("New passwords must match")?>`);

          break;
      }
    }
    
    return true;
  }
}

class Wpt_postitCountPlugin
{
  // METHOD getIds ()
  getIds ()
  {
    const ps = this.postit().settings,
          {wallId, cellId} = ps;

    return {wallId, cellId, postitId: ps.id}
  }

  // METHOD postit ()
  postit ()
  {
    return this.settings.postitPlugin;
  }

  // METHOD setCount ()
  setCount (count)
  {
    const elC = this.element[0].querySelector ("span");

    elC.style.display = count ? "inline-block": "none";
    elC.innerText = count;

    if (this.settings.readonly)
      this.element[0].style.display = count ? "inline-block" : "none";
  }

  // METHOD getCount ()
  getCount ()
  {
    return parseInt (this.element[0].querySelector("span").innerText);
  }

  // METHOD incCount ()
  incCount ()
  {
    this.setCount (this.getCount() + 1);
  }

  // METHOD decCount ()
  decCount ()
  {
    this.setCount (this.getCount() - 1);
  }
}

// CLASS Wpt_toolbox
class Wpt_toolbox
{
  // METHOD fixPosition ()
  fixPosition ()
  {
    const el = this.element[0],
          //document.getElementsByClassName("fixed-top")[0].clientHeight
          mH = 56,
          pos = el.getBoundingClientRect ();

    if (pos.top <= 56 + 4)
      el.style.top = `${56+4}px`;
    else
    {
      const wH = window.innerHeight - 15;

      if (pos.top + el.clientHeight > wH)
        el.style.top = `${wH-el.clientHeight-1}px`;
    }

    if (pos.left <= 0)
      el.style.left = "5px";
    else
    {
      const wW = window.innerWidth - 20;

      if (pos.left + el.clientWidth > wW)
        el.style.left = `${wW-el.clientWidth-1}px`;
    }
  }

  // METHOD fixDragPosition ()
  fixDragPosition (ui)
  {
    const el = this.element[0],
          //document.getElementsByClassName("fixed-top")[0].clientHeight
          mH = 56,
          pos = ui.position;

    if (pos.top <= 56 + 4)
      pos.top = 56 + 4;
    else
    {
      const wH = window.innerHeight - 15;

      if (pos.top + el.clientHeight > wH)
        pos.top = wH - el.clientHeight - 1;
    }

    if (pos.left <= 0)
      pos.left = 5;
    else
    {
      const wW = window.innerWidth - 20;

      if (pos.left + el.clientWidth > wW)
        pos.left = wW - el.clientWidth - 1;
    }
  }
}

// CLASS WStorage
class WStorage
{
  // METHOD delete ()
  delete (name)
  {
    localStorage.removeItem (name);
  }

  // METHOD setNoDisplay ()
  noDisplay (name, value)
  {
    const noDisplay = this.get ("no-display")||{};

    if (value)
    {
      noDisplay[name] = value;
      this.set ("no-display", noDisplay);
    }
    else
      return (noDisplay[name] === true)||false;
  }

  // METHOD set ()
  set (name, value)
  {
    localStorage.setItem (name, JSON.stringify (value));
  }

  // METHOD get ()
  get (name)
  {
    return JSON.parse (localStorage.getItem (name));
  }
}

// CLASS WSharer
class WSharer
{
  // METHOD constructor ()
  constructor ()
  {
    this.vars = {};
    this.walls = [];
    this._fullReset ();
  }

  // METHOD reset ()
  reset (type)
  {
    if (!type)
      this._fullReset ();
    else
      this[type] = typeof this[type] === "string" ? "" : [];
  }

  // METHOD _fullReset ()
  _fullReset ()
  {
    this.wall = [];
    this.chat = [];
    this.filters = [];
    this.wmenu = [];
    this.arrows = [];
    this.postit = [];
    this.pcomm = [];
    this.header = [];
    this.plugColor = "";
  }

  // METHOD set ()
  set (k, v, t)
  {
    this.vars[k] = v;

    if (t)
      setTimeout (()=> this.unset (k), t);
  }

  // METHOD get ()
  get (k)
  {
    return this.vars[k];
  }

  // METHOD unset ()
  unset (k)
  {
    delete this.vars[k];
  }

  // METHOD getCurrent ()
  getCurrent (item)
  {
    if (!this.walls.length)
    {
      this.walls = $(document.getElementById("walls"));
      if (!this.walls.length)
        return $([]);
    }

    switch (item)
    {
      case "wall":

        if (!this.wall.length)
          this.wall = $(this.walls[0].querySelector(
                          ".tab-pane.active .wall")||[]);

        return this.wall;

      case "postit":

        if (!this.postit.length)
          this.postit = $(this.walls[0].querySelector(
                            ".tab-pane.active .postit.current"));

        return this.postit;

      case "plugColor":

        if (!this.plugColor)
        {
          //const el = document.querySelector (".wall th.wpt:first-child");
          const el = document.querySelector (".cell-menu .btn-secondary");

          this.plugColor = H.rgb2hex (window.getComputedStyle ?
            window.getComputedStyle(el, null)
              .getPropertyValue ("background-color") :
            el.style.backgroundColor);
        }

        return this.plugColor;

      case "header":

        if (!this.header.length)
          this.header = $(this.walls[0].querySelector(
                            ".tab-pane.active th.wpt.current"));

        return this.header;

      case "tpick":

        if (!this.tpick)
          this.tpick = $(document.getElementById("tpick"));

        return this.tpick;

      case "walls":

        return this.walls;

      case "sandbox":

        if (!this.sandbox)
          this.sandbox = $(document.getElementById("sandbox"));

        return this.sandbox;

      case "chat":

        if (!this.chat.length)
          this.chat = $(this.walls[0].querySelector(".tab-pane.active .chat"));

        return this.chat;

      case "filters":

        if (!this.filters.length)
          this.filters = $(this.walls[0]
                             .querySelector(".tab-pane.active .filters"));

        return this.filters;

      case "wmenu":

        if (!this.wmenu.length)
          this.wmenu = $(this.walls[0]
                           .querySelector(".tab-pane.active .wall-menu"));

        return this.wmenu;

      case "arrows":

        if (!this.arrows.length)
          this.arrows = $(this.walls[0]
                            .querySelector(".tab-pane.active .arrows"));

        return this.arrows;

      case "mmenu":

        if (!this.mmenu)
          this.mmenu = $(document.getElementById("mmenu"));

        return this.mmenu;

      case "pcomm":

        if (!this.pcomm.length)
          this.pcomm = $(this.getCurrent("postit")[0].querySelector(".pcomm"));

        return this.pcomm;
    }
  }
}

// CLASS WSocket
class WSocket
{
  // METHOD constructor ()
  constructor ()
  {
    this.responseQueue = {};
    this._sendQueue = [];
    this._retries = 0;
    this._msgId = 0;
    this._send_cb = {};
    this._connected = false;
  }

  // METHOD connect ()
  connect (url, opencb_init)
  {
    this._connect (url, null, opencb_init);
  }

  // METHOD _connect ()
  _connect (url, onopen_cb, opencb_init)
  {
    H.loader ("show", true);

    this.cnx = new WebSocket (url);

    // EVENT open
    this.cnx.onopen = (e) =>
      {
        this._connected = true
        this._retries = 0;

        if (opencb_init)
          opencb_init ();

        if (onopen_cb)
          onopen_cb ();

        H.loader ("hide", true);
      };

    // EVENT message
    this.cnx.onmessage = (e) =>
      {
        const data = JSON.parse (e.data||"{}"),
              $wall = (data.wall && data.wall.id) ?
                $(`[data-id="wall-${data.wall.id}"]`) : [],
              isResponse = (this._send_cb[data.msgId] !== undefined);

        //console.log (`RECEIVED ${data.msgId}\n`);
        //console.log (data);

        if (data.action)
        {
          switch (data.action)
          {
            // userwriting
            case "userwriting":

              var $el = $(`${data.item=="postit"?".postit":""}`+
                            `[data-id="${data.item}-${data.itemId}"]`);

              if ($el.length)
                $el[data.item]("showUserWriting", data.user);
              break;

            // userstoppedwriting
            case "userstoppedwriting":
                H.hideUserWriting (data.userstoppedwriting.user);
              break;

            // exitsession
            case "exitsession":
              //TODO use H.infoPopup()
              var $popup = $("#infoPopup");

              bootstrap.Modal.getOrCreateInstance(
                document.querySelector(".modal")).hide ();

              H.cleanPopupDataAttr ($popup);

              $popup.find(".modal-body").html (`<?=_("One of your sessions has just been closed. All of your sessions will end. Please log in again.")?>`);
              $popup.find(".modal-title").html (`<i class="fas fa-fw fa-exclamation-triangle"></i> <?=_("Warning")?>`);
              $popup[0].dataset.popuptype = "app-logout";
              H.openModal ({item: $popup, customClass: "zindexmax"});

              setTimeout (
                ()=> bootstrap.Modal.getInstance($popup).hide (), 3000);
              break;

            // refreshpcomm
            case "refreshpcomm":

              var $el = $(`[data-id="postit-${data.postitId}"] .pcomm`);

              if ($el.length)
                $el.pcomm ("refresh", data);
              break;

            // refreshwall
            case "refreshwall":
              if ($wall.length && data.wall)
              {
                data.wall.isResponse = isResponse;
                $wall.wall ("refresh", data.wall);

                if (data.userstoppedwriting)
                  H.hideUserWriting (data.userstoppedwriting.user);
              }
              break;

            // viewcount
            case "viewcount":
              if ($wall.length)
                $wall.wall ("refreshUsersview", data.count);
              else
                WS.pushResponse (`viewcount-wall-${data.wall.id}`, data.count);
              break;

            // chat
            case "chat":
              if ($wall.length)
                $(`#wall-${data.wall.id} .chat`).chat ("addMsg", data);
              break;

            // chatcount
            case "chatcount":
              if ($wall.length)
                $(`#wall-${data.wall.id} .chat`)
                  .chat ("refreshUserscount", data.count);
              break;

            // have-msg
            case "have-msg":
              $("#umsg").umsg ("addMsg", data);
              break;

            // unlinked
            // Either the wall has been deleted
            // or the user no longer have necessary right to access the wall.
            case "unlinked":
              if (!isResponse)
              {
                H.displayMsg ({
                  title: `<?=_("Walls")?>`,
                  type: "warning",
                  msg: `<?=_("Some walls are no longer available")?>`
                });

                $wall.wall ("close");
                $("#settingsPopup").settings ("removeRecentWall", data.wall.id);
              }
              break;

            // mainupgrade
            case "mainupgrade":
              // Check only when all modals are closed
              var iid = setInterval (()=>
                {
                  if (!$(".modal:visible").length)
                  {
                    clearInterval (iid);
                    H.checkForAppUpgrade (data.version);
                  }
                }, 5000);
              break;

            // Reload to refresh user working space.
            case "reloadsession":
              return location.href = '/r.php?l='+data.locale;

            // Maintenance reload.
            case "reload":
              //TODO use H.infoPopup()
              var $popup = $("#infoPopup");

              // No need to deactivate it afterwards: page will be reloaded.
              S.set ("block-msg", true);

              // Close current popup if any
              bootstrap.Modal.getOrCreateInstance(
                document.querySelector(".modal")).hide ();

              H.cleanPopupDataAttr ($popup);

              $popup.find(".modal-body").html (`<?=_("We are sorry for the inconvenience, but due to a maintenance operation, the application must be reloaded.")?>`);
              $popup.find(".modal-title").html (`<i class="fas fa-fw fa-tools"></i> <?=_("Reload needed")?>`);

              $popup[0].dataset.popuptype = "app-reload";
              H.openModal ({item: $popup, customClass: "zindexmax"});

              break;
          }
        }

        let nextMsg = null;

        if (data.msgId)
        {
          const msgId = data.msgId;
          let i = this._sendQueue.length;

          // Remove request from sending queue
          while (i--)
          {
            if (this._sendQueue[i].msg.msgId == msgId)
            {
              if (this._sendQueue[i+1])
                nextMsg = this._sendQueue[i+1];

              this._sendQueue.splice (i, 1);
              break;
            }
          }

          delete (data.msgId);

          if (isResponse)
          {
            this._send_cb[msgId](data);

            delete this._send_cb[msgId];
          }
        }

        H.loader ("hide");

        // Send next message pending in sending queue
        if (nextMsg)
          this.send (nextMsg.msg, nextMsg.success_cb, nextMsg.error_cb);
      };

    // EVENT error
    this.cnx.onerror = (e) =>
      {
        H.loader ("hide");

        if (this._retries < 30)
          this.tryToReconnect ({
            success_cb: () =>
              {
                const $wall = S.getCurrent ("wall");

                if ($wall.length)
                  $wall.wall ("refresh");
              }
          });
        else
          this.displayNetworkErrorMsg ();
      };
  }

  // METHOD tryToReconnect ()
  tryToReconnect (args)
  {
    let ret = true;

    this._connected = false;
    ++this._retries;

    this._connect (this.cnx.url, () =>
      {
        if (args)
        {
          if (args.msg)
            this.send (args.msg, args.success_cb, args.error_cb);
          else if (args.success_cb)
            args.success_cb ();
        }
      });
  }

  // METHOD displayNetworkErrorMsg ()
  displayNetworkErrorMsg ()
  {
    $("body").html (`<div class="global-error"><?=_("Either the network is not available or a maintenance operation is in progress. Please reload the page or try again later.")?></div>`);
  }

  // METHOD ready ()
  ready ()
  {
    return (this.cnx && this.cnx.readyState == this.cnx.OPEN);
  }

  // METHOD send ()
  send (msg, success_cb, error_cb)
  {
    if (!this.ready ())
    {
      if (this._connected && this._retries < 30)
        this.tryToReconnect ({
          msg: msg,
          success_cb: success_cb,
          error_cb: error_cb
        });

      return;
    }

    const send = !!msg.msgId;

    // Put message in message queue if not already in
    if (!msg.msgId)
    {
      msg["msgId"] = ++this._msgId;

      // If some messages have already been sent without response, queued the
      // new message to send it after the others.
      this._sendQueue.push ({
        msg: msg,
        success_cb: success_cb,
        error_cb: error_cb
      });
    }

    // If first message or request for sending a message in queue
    if (send || this._sendQueue.length == 1)
    {
      //console.log (`SEND ${msg.msgId}\n`);

      this._send_cb[msg.msgId] = success_cb;
 
      this.cnx.send (JSON.stringify (msg));
    }
  }

  // METHOD pushResponse ()
  pushResponse (type, response)
  {
    if (this.responseQueue[type] === undefined)
      this.responseQueue[type] = [];
    
    this.responseQueue[type].push (response);
  }

  // METHOD popResponse ()
  popResponse (type)
  { 
    return (this.responseQueue[type] !== undefined &&
            this.responseQueue[type].length) ?
              this.responseQueue[type].pop () : undefined;
  }
}

// CLASS WHelper
class WHelper
{
  constructor ()
  {
    this.entitiesMap = {
      "&": "&amp;",
      "<": "&lt;",
      ">": "&gt;",
      '"': "&quot;",
      "'": "&#39;"
    };
  }

  // METHOD hideUserWriting ()
  hideUserWriting (user)
  {
    document.querySelectorAll(
      `[class^="user-writing"][data-userid="${user.id}"]`).forEach (el =>
      {
        el.parentNode.classList.remove ("locked", "main");
        el.remove ();
      });
  }

  // METHOD isLoginPage ()
  isLoginPage ()
  {
    return (document.body && document.body.id == "login-page");
  }

  // METHOD disabledEvent ()
  disabledEvent (cond)
  {
    return !!((cond === true) || S.get ("link-from") || S.get ("dragging"));
  }

  // METHOD lightenDarkenColor ()
  lightenDarkenColor (col, amt)
  {
    let usePound = false;

    if (col[0] == "#")
    {
      col = col.slice(1);
      usePound = true;
    }

    let num = parseInt (col, 16),
        r = (num >> 16) + amt;

    if (r > 255)
      r = 255;
    else if  (r < 0)
      r = 50;

    let b = ((num >> 8) & 0x00ff) + amt;

    if (b > 255)
      b = 255;
    else if (b < 0)
      b = 0;

    let g = (num & 0x0000ff) + amt;

    if (g > 255)
      g = 255;
    else if (g < 0)
      g = 0;

    return (usePound?"#":"") + (g | (b << 8) | (r << 16)).toString(16);
  }

  // METHOD rgb2hex ()
  rgb2hex (rgb)
  {
    // If already in hex
    if (rgb.charAt(0) == "#")
      return rgb;

    const hex = (x) => ("0" + parseInt(x).toString(16)).slice (-2);

    rgb = rgb.match (/^rgba?\((\d+),?\s*(\d+),?\s*(\d+)/);

    return `#${hex(rgb[1])}${hex(rgb[2])}${hex(rgb[3])}`;
  }

  // METHOD setAutofocus ()
  setAutofocus (el)
  {
    // Set focus on first autofocus field if not touch device
    if (this.haveMouse ())
    {
      let tmp;

      setTimeout (() =>
        (tmp = el.querySelector("[autofocus]")) && tmp.focus (), 350);
    }
  }

  // METHOD testImage ()
  testImage (url, timeout = 10000)
  {
    return new Promise ((resolve, reject) =>
      {
        const img = new Image ();
        let timer;
  
        img.onerror = img.onabort = ()=>
          {
            clearTimeout (timer);
            reject ("error");
          };
  
        img.onload = ()=>
          {
            clearTimeout (timer);
            resolve ("success");
          };
  
        timer = setTimeout(()=>
          {
            // reset .src to invalid URL so it stops previous
            // loading, but doesn't trigger new load
            img.src = "//!!!!/test.jpg";
            reject ("timeout");
          }, timeout);
  
        img.src = url;
      });
  }

  // METHOD haveMouse ()
  haveMouse ()
  {
    return (window.matchMedia("(hover: hover)").matches);
  }

  // METHOD isMainMenuCollapsed ()
  isMainMenuCollapsed ()
  {
    return $("button[data-target='#main-menu']").is (":visible");
  }

  // METHOD escapeRegex ()
  escapeRegex (str)
  {
    return (`${str}`).replace(/[.*+?^${}()|[\]\\]/g, "\\$&");
  }

  // METHOD quoteRegex ()
  quoteRegex (str)
  {
    return (`${str}`).replace (/(\W)/g, '\\$1');
  }

  // METHOD HTMLEscape ()
  htmlEscape (str)
  {
    return (`${str}`).replace (/[&<>"']/g, (c) => this.entitiesMap[c]);
  }
  
  // METHOD noHTML ()
  noHTML (str)
  {
    return (`${str}`).trim().replace (/<[^>]+>|&[^;]+;/g, "");
  }

  // METHOD nl2br ()
  nl2br (str)
  {
    return (`${str}`).replace(/([^>\r\n]?)(\r\n|\n\r|\r|\n)/g, "$1<br>$2");
  }
  
  // METHOD closeMainMenu ()
  closeMainMenu ()
  {
    if ($("#main-menu.show").length)
      $("button.navbar-toggler").click ();
  }
  
  // METHOD trimObject ()
  trimObject (obj, exclude = [])
  {
    for (const key in obj)
    {
      const v = obj[key];

      if (typeof v === 'string' && exclude.indexOf(key) == -1)
        obj[key] = v.trim ();
    }

    return obj;
  }

  // METHOD updatedObject ()
  updatedObject (obj1, obj2, ignore = {})
  {
    for (const key in obj2)
    {
      if (obj1[key] !== null && typeof obj1[key] === "object")
      {
        if (this.updatedObject (obj1[key], obj2[key], ignore))
          return true;
      }
      else if (obj1[key] != obj2[key] && !ignore[key])
        return true;
    }
  
    return false;
  }
  
  // METHOD cleanPopupDataAttr ()
  cleanPopupDataAttr ($popup)
  {
    // Remove all data attributes
    const attrs = [];
    $.each ($popup[0].attributes, function (i, a)
      {
        if (a.name.indexOf ("data-") == 0)
          attrs.push (a.name);
      });
    attrs.forEach ((item) => $popup.removeAttr (item));
  
    $popup.find("span.required").remove ();
    $popup.find(".input-group.required").removeClass ("required");
  
    switch ($popup.attr ("id"))
    {
      case "createWallPopup":
  
        $popup.find("input").val ("");
        $popup.find(".cols-rows input").val (3);
        $popup.find(".cols-rows,.width-height").hide ();
        $popup.find(".cols-rows").show ();
        $popup.find("#w-grid")[0].checked = true;
        $popup.find("#w-grid").parent().removeClass ("disabled");
        break;
  
      case "groupPopup":
  
        $popup.find("input").val (""); 
        $popup.find(".desc").html ("");
        $popup.find("button.btn-primary").removeAttr ("data-type data-groupid");
        break;
  
      case "updateOneInputPopup":
  
        $popup.find(".modal-dialog").removeClass ("modal-sm");
        $popup.find("#w-grid").parent().remove ();
        $popup.find(".btn-primary").html (`<i class="fas fa-save"></i> <?=_("Save")?>`);
  
        $popup.find("input")
          .removeAttr ("placeholder autocorrect autocapitalize maxlength")
          .val ("");
        break;
  
      case "confirmPopup":
  
        $popup.removeClass ("no-theme");
        break;

      case "groupAccessPopup":

        const $cb = $popup.find(".send-msg input[type='checkbox']");

        if ($cb[0].checked)
          $cb.click ();
        break;
    }
  }
  
  // METHOD getHumanSize ()
  getHumanSize (bytes)
  {
    const i = Math.floor(Math.log(bytes) / Math.log(1024)),
          sizes = ['B', 'KB', 'MB'];
  
    return (bytes / Math.pow(1024, i)).toFixed(2) * 1 + ' ' + sizes[i];
  }
  
  // METHOD getAccess ()
  getAccess ()
  {
    const $wall = S.getCurrent ("wall");
  
    return ($wall.length) ? $wall[0].dataset.access : "";
  }
  
  // METHOD checkAccess ()
  checkAccess (requiredRights, currentAccess)
  {
    if (currentAccess === undefined)
    {
      const $wall = S.getCurrent ("wall");
  
      if ($wall.length)
        currentAccess = $wall[0].dataset.access;
    }
  
    return (currentAccess === undefined) ?
      false : (currentAccess == "<?=WPT_WRIGHTS_ADMIN?>" ||
               currentAccess == String (requiredRights))
  }
  
  // METHOD getAccessIcon ()
  getAccessIcon (access)
  {
    let icon;
  
    if (!access)
      access = this.getAccess ();
  
    switch (String (access))
    {
      case "<?=WPT_WRIGHTS_ADMIN?>": icon = 'fa-shield-alt'; break;
      case "<?=WPT_WRIGHTS_RW?>": icon = 'fa-edit'; break;
      case "<?=WPT_WRIGHTS_RO?>": icon = 'fa-eye'; break;
    }
  
    return `<i class="fas ${icon} fa-fw"></i>`;
  }
  
  // METHOD getUserDate ()
  getUserDate (dt, tz, fmt)
  {
    return moment.unix(dt).tz(
      tz||wpt_userData.settings.timezone||moment.tz.guess())
        .format(fmt||"Y-MM-DD");
  }

  checkUserVisible ()
  {
    return (wpt_userData.settings && wpt_userData.settings.visible == 1);
  }
  
  // METHOD loader ()
  loader (action, force = false, xhr = null)
  {
    const $layer = $("#popup-loader"),
          layer = $layer[0];
  
    if (layer && (WS.ready() || force))
    {
      clearTimeout (layer.dataset.timeoutid);
  
      if (action == "show")
      {
        layer.dataset.timeoutid = setTimeout (() =>
          {
            if (xhr)
            {
              // Abort upload on user request
              $layer.find("button").off("click").on("click", ()=> xhr.abort ());
              $layer.find (".progress,button").show ();
            }
  
            $layer.show ();
  
          }, 500);
      }
      else
      {
        $layer.hide ();
  
        $layer.find("button").hide ();
        $layer.find(".progress").css ({
          display: "none",
          background: "#ea6966"
        });

        layer.removeAttribute ("data-timeoutid");
      }
    }
  }

  // METHOD openUserview ()
  openUserview (args)
  {
    const {about, picture, title} = args;

    H.loadPopup ("userView", {
      open: false,
      cb: ($p)=>
      {
        const p = $p[0],
              $picture = $p.find (".user-picture"),
              aboutEl = p.querySelector (".about");

        p.querySelector(".modal-title span").innerText = title;
        p.querySelector(".name dd").innerText = title;

        $picture.empty().hide ();

        if (picture)
        {
          $picture
            .append (
              $(`<img src="${picture}">`)
                .on("error",function (e){$picture.hide()}));

          $picture.show ();
        }

        if (about)
        {
          aboutEl.querySelector("dd").innerHTML = H.nl2br (about);
          aboutEl.style.display = "block";
        }
        else
          aboutEl.style.display = "none";

        H.openModal ({item: $p});
      }
    });
  }
  
  // METHOD openPopupLayer ()
  openPopupLayer (cb, closeMenus = true)
  {
    const $layer = $(`<div id="popup-layer" class="layer"></div>`);
  
    $(document)
      .off("keydown.popuplayer")
      .on("keydown.popuplayer", function (e)
      {
        if (e.which == 27)
          $layer.click ();
      });
  
    $layer
      .on("click",
      function (e)
      {
        $(document).off ("keydown.popuplayer");
  
        // Remove the layer
        this.remove ();
  
        if (cb)
          cb (e);
      });
  
    $layer.prependTo("body").show ();
  }
  
  // METHOD openConfirmPopup ()
  openConfirmPopup (args)
  {
    const $popup = $("#confirmPopup"),
          popup0 = $popup[0];
  
    S.set ("confirmPopup", {
      cb_ok: args.cb_ok,
      cb_close: () =>
        {
          args.cb_close && args.cb_close ();

          S.unset ("confirmPopup");
        }
    });

    this.cleanPopupDataAttr ($popup);
  
    popup0.querySelector(".modal-title").innerHTML = `<i class="fas fa-${args.icon} fa-fw"></i> ${args.title||`<?=_("Confirmation")?>`}`;
    popup0.querySelector(".modal-body").innerHTML = args.content;

    popup0.dataset.popuptype = args.type;

    this.openModal ({item: $popup});
  }
  
  // METHOD openConfirmPopover ()
  openConfirmPopover (args)
  {
    const scroll = !!args.scrollIntoView;
    let tmp, btn, buttons;

    if (!this.haveMouse ())
      this.fixVKBScrollStart ();
  
    this.openPopupLayer ((e)=>
      {
        const bp = bootstrap.Popover.getInstance(args.item[0])

        if (bp)
        {
          if (args.cb_close)
            args.cb_close (bp.tip.dataset.btnclicked);

          if (document.querySelector(".popover.show"))
            bp.dispose ();
        }
  
        if (!this.haveMouse ())
          this.fixVKBScrollStop ();

      }, false);
    
    switch (args.type)
    {
      case "info":
        btn = {primary:"save"};
        buttons = "";
        break;

      case "update":
        btn = {primary:"save", secondary: "close"};
        buttons = `<button type="button" class="btn btn-xs btn-primary"><?=_("Save")?></button> <button type="button" class="btn btn-xs btn-secondary"><?=_("Close")?></button>`;
        break;

      case "custom":
        btn = {primary:"save"};
        buttons = `<button type="button" style="display:none" class="btn btn-xs btn-primary"></button>`;

        if (args.html_header)
          args.content = args.html_header+args.content;
        else if (args.html_footer)
          args.content += args.html_footer;
        break;

      default:
        btn = {primary:"yes", secondary: "no"};
        buttons = `<button type="button" class="btn btn-xs btn-primary"><?=_("Yes")?></button> <button type="button" class="btn btn-xs btn-secondary"><?=_("No")?></button>`;
    }
  
    const bp = bootstrap.Popover.getOrCreateInstance (args.item[0], {
      html: true,
      sanitize: false,
      title: args.title,
      customClass:args.customClass||"",
      placement: args.placement||"left",
      boundary: "window",
      content: `<p>${args.content}</p>${buttons}`
    });

    bp.show ();

    const body = bp.tip.querySelector (".popover-body");

    if (this.haveMouse () && (tmp = body.querySelector("input")) )
      tmp.focus ();

    $(body).find("button:not(.close)").on("click", function (e)
      {
        if (this.classList.contains ("btn-primary"))
        {
          bp.tip.dataset.btnclicked = btn.primary;
          if (args.cb_ok)
            args.cb_ok ($(bp.tip));
        }
        else
          bp.tip.dataset.btnclicked = btn.secondary;
  
        if (!args.noclosure)
          document.getElementById("popup-layer").click ();
      });

    // Scroll to the popover point
    if (scroll)
      args.item[0].scrollIntoView (false);

    if (args.cb_after)
      args.cb_after ($(bp.tip));

    if (scroll)
      window.dispatchEvent (new Event("resize"));
  }

  // METHOD setViewToElement ()
  setViewToElement ($el)
  {
    const $view = S.getCurrent ("walls"),
          posE = $el[0].getBoundingClientRect (),
          posV = $view[0].getBoundingClientRect ();

    if (posE.left > posV.width)
      $view.scrollLeft (posE.left - posE.width);

    if (posE.top > posV.height)
      $view.scrollTop (posE.top - posE.height - 110);
  }
  
  // METHOD resizeModal ()
  resizeModal ($modal, w)
  {
    const modal = $modal[0],
          md = modal.querySelector(".modal-dialog"),
          wW = $(window).width (),
          cW = Number (modal.dataset.customwidth)||0,
          oW = w;
  
    if (cW)
      w = cW;
  
    if (w < 500)
      w = 500;
  
    if (wW <= 800)
      w = "100%";
    else
    {
      if (w != 500)
        w += this.checkAccess ("<?=WPT_WRIGHTS_RW?>",
               S.getCurrent("wall")[0].dataset.access) ? 70 : 30;
  
      if (w > wW)
        w = "100%";
    }
  
    if (!cW)
      modal.dataset.customwidth = oW;
  
    md.style.width = `${w}px`;
    md.style.minWidth = `${w}px`;
    md.style.maxWidth = `${w}px`;
  }
  
  // METHOD openModal ()
  openModal (args)
  {
    const m = args.item[0],
          md = m.querySelector(".modal-dialog"),
          bm = bootstrap.Modal.getOrCreateInstance (m, {backdrop: true});

    // Modals with transition effect
    if (!args.noeffect)
      m.classList.add ("fade");

    m.removeAttribute ("data-customwidth");
    m.style.top = 0;
    m.style.left = 0;

    m.querySelector(".modal-content").classList.add ("shadow-lg");

    if (args.customClass)
      m.classList.add (args.customClass);

    bm.show ();

    if (args.width)
      this.resizeModal (args.item, args.width);
    else
    {
      md.style.width = "";
      md.style.minWidth = "";
      md.style.maxWidth = "";
    }
  }

  // METHOD loadPopup ()
  async loadPopup (type, args = {open: true})
  {
    const id = `${args.template||type}Popup`,
          popup = document.getElementById (id);

    // INTERNAL FUNCTION __exec ()
    const __exec = ($p)=>
      {
        H.cleanPopupDataAttr ($p);

        if (args.cb)
          args.cb ($p);

        if (args.settings)
          $p[type]("setSettings", args.settings);

        if (args.open)
          H.openModal ({
            item: $p,
            noeffect: !!args.noeffect
          });
      };

    if (args.open === undefined)
      args.open = true;

    if (popup)
      __exec ($(popup));
    else
    {
      const r = await fetch (`/ui/${args.template||type}.php`);
      if (r.ok)
      {
        $("body").prepend (await r.text ());

        const $p = $(`#${id}`);

        if ($p[type] !== undefined)
          $p[type](args.settings||{});

        if (args.init)
          args.init ($p);

        __exec ($p);
      }
      else
        this.manageUnknownError ();
    }
  }
  
  // METHOD infoPopup ()
  infoPopup (msg, notheme)
  {
    const p = document.getElementById ("infoPopup"),
          $p = $(p);
  
    if (notheme)
      p.classList.add ("no-theme");
    else
      this.cleanPopupDataAttr ($p);
      
    p.querySelector(".modal-dialog").classList.add ("modal-sm");
  
    p.querySelector(".modal-body").innerHTML = msg;
  
    p.querySelector(".modal-title").innerHTML = `<i class="fas fa-bullhorn"></i> <?=_("Information")?>`;
  
    this.openModal ({item: $p});
  }
  
  // METHOD raiseError ()
  raiseError (error_cb, msg)
  {
    error_cb && error_cb ();
  
    this.displayMsg ({
      title: `<?=_("System")?>`,
      type: msg?"warning" : "danger",
      msg: msg?msg:`<?=_("System error. Please report it to the administrator")?>`
    });
  }
  
  // METHOD displayMsg ()
  displayMsg (args)
  {
    if (S.get ("block-msg"))
      return;

    // If a TinyMCE plugin is running, display message using the editor window
    // manager.
    if ($(".tox-dialog").is(":visible"))
      return tinymce.activeEditor.windowManager.alert (args.msg);

    const t = args.type;

    // Blur TinyMCE editor to hide virtual keyboard
    if (!this.haveMouse ())
    {
      const ef = document.getElementById ("postitUpdatePopupBody_ifr");

      if (ef)
        ef.blur ();
    }

    // Close opened alert with the same content
    document.querySelectorAll("#msg-container .toast").forEach (
      (el)=> (el.querySelector(".toast-body").innerHTML == args.msg) &&
                bootstrap.Toast.getInstance(el).hide ());

    (new bootstrap.Toast ($(`<div class="toast align-items-center border-0 ${(t=="danger"||t=="success")?"text-white":""} bg-${t} bg-gradient" role="alert" aria-live="assertive" aria-atomic="true"><div class="d-flex"><button type="button" class="btn-close m-auto" data-bs-dismiss="toast"></button><div class="toast-body">${args.msg}</div></div></div>`).prependTo ("#msg-container"), {delay: (t=="danger"||t=="warning")?5000:3000})).show ();
  }

  // METHOD fixMenuHeight ()
  fixMenuHeight ()
  {
    const menu = document.querySelector (".navbar-collapse"),
          mbBtn = document.querySelector (".navbar-toggler-icon");
  
    // If menu is in min mode, limit menus height
    if (mbBtn.offsetWidth > 0 && mbBtn.offsetHeight > 0)
    {
      menu.style.overflowY = "auto";
      menu.style.maxHeight = `${window.innerHeight-56}px`;
    }
    else
    {
      menu.style.overflowY = "";
      menu.style.maxHeight = "";
    }
  }
  
  // METHOD fixMainHeight ()
  fixMainHeight ()
  {
    document.querySelector("html").style.overflow = "hidden";

    S.getCurrent("walls")[0].style.height =
      `${window.innerHeight - document.querySelector(".nav-tabs.walls").offsetHeight}px`;
  }

  // METHOD setColorpickerColor ()
  setColorpickerColor ($cp, c, apply = true)
  {
    const s = $cp[0].querySelectorAll (".ui-colorpicker-swatch"),
          ss = $cp[0].querySelector (".ui-colorpicker-swatch.cp-selected");

    if (ss)
      ss.classList.remove ("cp-selected");

    if (apply)
      $cp.colorpicker ("setColor", c);

    for (let i = 0, iLen = s.length; i < iLen; i++)
    {
      if (H.rgb2hex ($(s[i]).css("background-color")) == c)
      {
        s[i].classList.add ("cp-selected");
        break;
      }
    }
  }

  // METHOD getProgressbarColor ()
  getProgressbarColor (v)
  {
    return (v < 30) ? "#f60104" :
           (v < 50) ? "#f57f00" :
           (v < 75) ? "#f5c900" :
           (v < 85) ? "#f0f700" :
           (v < 95) ? "#84f600" :
           "#26f700";
  }

  // METHOD download ()
  download (args)
  {
    const req = new XMLHttpRequest ();
  
    this.loader ("show");
  
    req.onreadystatechange = (e) =>
      {
        if (req.readyState == 4)
        {
          this.loader ("hide");
  
          if (req.status != 200)
            this.displayMsg ({
              title: `<?=_("Download")?>`,
              type: "warning",
              msg: args.msg||`<?=_("An error occurred while downloading")?>`
            });
        }
      };
  
    req.open ("GET", args.url);
    req.responseType = "blob";
  
    req.onload = (e) =>
    {
      const blob = req.response,
            fname = args.fname,
            contentType = req.getResponseHeader ("Content-Type");

      if (contentType == 404)
      {
        this.displayMsg ({
          title: `<?=_("Download")?>`,
          type: "warning",
          msg: `<?=_("The file is no longer available for download")?>`
        });
      }
      else
      {
        if (window.navigator.msSaveOrOpenBlob)
          window.navigator.msSaveOrOpenBlob (
            new Blob([blob], {type: contentType}), fname);
        else
        {
          const el = document.createElement ("a");

          el.href = window.URL.createObjectURL (blob);
          el.download = fname;
          el.click ();
        }
      }
    };

    req.send ();
  }
  
  // METHOD manageUnknownError ()
  manageUnknownError (d = {}, error_cb)
  {
    let msg;

    if (d.error && isNaN (d.error))
      msg = d.error;
    else if (error_cb)
      msg = `<?=_("Unknown error.<br>Please try again later.")?>`;
    else
    {
      msg = `<?=_("Unknown error.<br>You are about to be disconnected...<br>Sorry for the inconvenience.")?>`;
      setTimeout (()=> $("<div/>").login ("logout", {auto: true}), 3000);
    }

    this.displayMsg ({
      title: `<?=_("System")?>`,
      type: d.msgtype||"danger",
      msg: msg
    });

    if (error_cb)
      error_cb (d);
  }
  
  // METHOD request_ws ()
  request_ws (method, service, args, success_cb, error_cb)
  {
    this.loader ("show");
  
    //console.log (`WS: ${method} ${service}`);
  
    WS.send ({
      method: method,
      route: service,
      data: args ? encodeURI (JSON.stringify (args)) : null  
    },
    (d)=>
      {
        this.loader ("hide");
  
        if (d.error)
          this.manageUnknownError (d, error_cb);
        else if (success_cb)
          success_cb (d);
        else if (d.error_msg)
          H.displayMsg ({
            title: `<?=_("Warning")?>`,
            type: "warning",
            msg: d.error_msg
          });
      },
    ()=>
      {
        this.loader ("hide");
  
        error_cb && error_cb ();
      });
  }
  
  // METHOD fetch ()
  async fetch (method, service, args, success_cb, error_cb)
  {
    this.loader ("show");

    //console.log (`FETCH: ${method} ${service}`);

    const r = await fetch (`/api/${service}`, {
                method: method,
                cache: "no-cache",
                headers: {"Content-Type": "application/json;charset=utf-8"},
                body: args ? encodeURI (JSON.stringify (args)) : null
              }),
          d = await r.json ();

    this.loader ("hide");

    //console.log (d);

    if (r.ok)
    {
      if (!d || d.error)
        error_cb ? error_cb (d) : this.manageUnknownError (d);
      else if (success_cb)
        success_cb (d);
    }
    else
      this.manageUnknownError ();
  }

  // METHOD fetchUpload ()
  // Only used for file upload
  //TODO Use fetch() when upload progress will be available!
  fetchUpload (service, args, success_cb, error_cb)
  {
    //console.log (`AJAX: PUT ${service}`);
  
    const pbar = document.querySelector ("#loader .progress"),
          xhr = $.ajax (
            {
              // Progressbar for file upload
              xhr: ()=>
              {
                const xhr = new window.XMLHttpRequest ();
      
                // INTERNAL FUNCTION __progress ()
                const __progress = (e)=>
                  {
                    if (e.lengthComputable)
                    {
                      const display = Math.trunc(e.loaded/e.total*100);
      
                      pbar.innerText = display+"%";
                      pbar.style.width = display+"%";
      
                      //FIXME Use classes
                      if (display < 50)
                        pbar.style.backgroundColor = "#ea6966";
                      else if (display < 90)
                        pbar.style.backgroundColor = "#f5b240";
                      else if (display < 100)
                        pbar.style.backgroundColor = "#6ece4b";
                      else
                        pbar.innerText = `<?=_("Upload completed")?>`;
                    }
                  };
      
                  xhr.upload.addEventListener ("progress", __progress, false);
//FIXME useful?
//                  xhr.addEventListener ("progress", __progress, false);
      
                return xhr;
              },
              type: "PUT",
              // No timeout for file uploading
              timeout: 0,
              async: true,
              cache: false,
              url: `/api/${service}`,
              data: args ? encodeURI (JSON.stringify (args)) : null,
              dataType: "json",
              contentType: "application/json;charset=utf-8"
            })
            .done ((d)=>
             {
               if (!d||d.error)
               {
                 if (error_cb)
                   error_cb (d);
                 else
                   this.manageUnknownError (d);
               }
               else if (success_cb)
                 success_cb (d);
             })
            .fail ((jqXHR, textStatus, errorThrown)=>
             {
               switch (textStatus)
               {
                 case "abort":
                   this.manageUnknownError ({
                     msgtype: "warning",
                     error: `<?=_("Upload has been canceled")?>`});
                   break;
        
                 default:
                  this.manageUnknownError ();
               }
             })
            .always (()=> this.loader ("hide", true));

    this.loader ("show", true, xhr);
  }
  
  // METHOD checkUploadFileSize ()
  checkUploadFileSize (args)
  {
    const msg = `<?=_("File size is too large (%sM max)")?>`.replace("%s", <?=WPT_UPLOAD_MAX_SIZE?>),
          maxSize = args.maxSize || <?=WPT_UPLOAD_MAX_SIZE?>;
    let ret = true;
  
    if (args.size / 1024000 > maxSize)
    {
      ret = false;
  
      if (args.cb_msg)
        args.cb_msg (msg);
      else
        this.displayMsg ({
          title: `<?=_("File upload")?>`,
          type: "warning",
          msg: msg
        });
    }
  
    return ret;
  }
  
  // METHOD getUploadedFiles ()
  getUploadedFiles (files, type, success_cb, error_cb, cb_msg)
  {
    const _H = this,
          reader = new FileReader (),
          file = files[0];

    if (type != "all" && !file.name.match (new RegExp (type, 'i')))
      return _H.displayMsg ({
        title: `<?=_("File upload")?>`,
        type: "warning",
        msg: `<?=_("Wrong file type for %s")?>`.replace("%s", file.name)
      });
  
    reader.readAsDataURL (file);
  
    reader.onprogress = (e) =>
      {
        if (!_H.checkUploadFileSize (e.total, cb_msg))
          reader.abort ();
      }
  
    reader.onerror = (e) =>
      {
        const msg = `<?=_("Can not read file")?> (${e.target.error.code})`;
  
        if (cb_msg)
          cb_msg (msg);
        else
          _H.displayMsg ({
            title: `<?=_("File upload")?>`,
            type: "danger",
            msg: msg
          });
      }
  
    reader.onloadend = ((f) => (evt) => success_cb (evt, f))(file);
  }
  
  // METHOD waitForDOMUpdate ()
  waitForDOMUpdate (cb)
  {
    window.requestAnimationFrame (() => window.requestAnimationFrame (cb));
  }
  
  // METHOD checkForAppUpgrade ()
  async checkForAppUpgrade (version)
  {
    const html = $("html")[0],
          $popup = $("#infoPopup"),
          officialVersion = (version) ? version : html.dataset.version;
  
    if (!String(officialVersion).match(/^\d+$/))
    {
      const $userSettings = $("#settingsPopup"),
            userVersion = $userSettings.settings ("get", "version");

      if (userVersion != officialVersion)
      {
        $userSettings.settings ("set", {version: officialVersion});
  
        if (userVersion)
        {
          // Close current popup if any
          bootstrap.Modal.getOrCreateInstance(
            document.querySelector(".modal")).hide ();
  
          this.cleanPopupDataAttr ($popup);
  
          $popup.find(".modal-body").html (`<?=_("A new release of wopits is available.")?><br><?=_("The application will be upgraded from v%s1 to v%s2.")?>`.replace("%s1", `<b>${userVersion}</b>`).replace("%s2", `<b>${officialVersion}</b>`));
          $popup.find(".modal-title").html (`<i class="fas fa-fw fa-glass-cheers"></i> <?=_("New release")?>`);
  
          $popup[0].dataset.popuptype = "app-upgrade";
          this.openModal ({item: $popup});
  
          return true;
        }
      }
      else if (html.dataset.upgradedone)
      {
        html.removeAttribute ("data-upgradedone");
  
        $popup.find(".modal-dialog").removeClass ("modal-sm");
        $popup.find(".modal-title").html (`<i class="fas fa-glass-cheers"></i> <?=_("Upgrade done")?>`);

        const r = await fetch ("/whats_new/latest.php");
        if (r.ok)
        {
          let d = await r.text ();

          <?php if (WPT_DISPLAY_LATEST_NEWS):?>
          if (d)
            d = `<h5 class="mb-3 text-center"><i class="fas fa-bullhorn fa-fw"></i> <?=_("What's new in v%s?")?></h5>`.replace("%s", "<?=WPT_VERSION?>")+d;
          else
            d = `<?=_("Upgrade done. Thank you for using wopits!")?>`;
          <?php else:?>
            d = `<?=_("Upgrade done. Thank you for using wopits!")?>`;
          <?php endif?>

          $popup.find(".modal-body").html (`${d}<div class="mt-2"><button type="button" class="btn btn-secondary btn-xs"><i class="fas fa-scroll"></i> <?=_("See more...")?></button></div>`);
          $popup.find(".modal-body").find("button")
            .on("click", function ()
            {
              bootstrap.Modal.getInstance($popup).hide ();
              H.loadPopup ("userGuide");
            });

          this.openModal ({item: $popup, noeffect: true});
        }
      }
    }
  }
  
  // METHOD navigatorIsEdge ()
  navigatorIsEdge ()
  {
    return navigator.userAgent.match (/edg/i);
  }

  // METHOD fixVKBScrollStart ()
  //FIXME
  fixVKBScrollStart ()
  {
    const walls = document.getElementById ("walls");

    if (!walls)
      return;

    const body = document.body,
          bodyScrollTop = body.scrollTop,
          wallsScrollLeft = walls.scrollLeft;
  
    S.set ("wallsScrollLeft", wallsScrollLeft);
    S.set ("bodyComputedStyles", window.getComputedStyle (body));
  
    body.style.position = "fixed";
    body.style.overflow = "hidden";
    body.style.top = `${bodyScrollTop*-1}px`;
  
    if (this.navigatorIsEdge ())
      S.getCurrent("wall")[0].style.left = `${wallsScrollLeft*-1}px`;
  
    walls.style.width = `${window.innerWidth}px`;
    walls.style.overflow = "hidden";
  
    $(window).trigger ("resize");
  }
  
  // METHOD fixVKBScrollStop ()
  //FIXME
  fixVKBScrollStop ()
  {
    const walls = document.getElementById ("walls");

    if (!walls)
      return;
  
    document.body.style = S.get ("bodyComputedStyles");
    S.unset ("bodyComputedStyles");
  
    walls.style.overflow = "auto";
    walls.style.width = "auto";
    if (this.navigatorIsEdge ())
      S.getCurrent("wall")[0].style.left = "";
    walls.scrollLeft = S.get ("wallsScrollLeft");
  
    this.waitForDOMUpdate (()=>
      {
        this.fixMainHeight ();
        S.getCurrent("wall").wall ("repositionPostitsPlugs");
      });
  }

}

// GLOBAL VARS
const H = new WHelper (),
      S = new WSharer (),
      ST = new WStorage (),
      WS = new WSocket ();
