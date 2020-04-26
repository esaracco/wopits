// CLASS Wpt_forms
class Wpt_forms
{
  // METHOD checkRequired ()
  checkRequired (fields)
  {
    const $form = $(fields[0]).closest ("form");

    $form.find("span.required").remove ();
    $form.find(".required").removeClass ("required");
    
    for (let i = 0, iLen = fields.length; i < iLen; i++)
    {
      const $f = $(fields[i]);

      if ($f.attr("required") && !$f.val().trim().length)
      {
        this.focusBadField ($f, "<?=_("Required field")?>");
        $f.focus ();

        return false;
      }
    }

    return true;
  }

  // METHOD focusBadField ()
  focusBadField ($f, msg)
  {
    const $group = $f.closest (".input-group");

    $group.addClass ("required");

    $f.focus ();

    $(`<span class="required">${msg}:</span>`).insertBefore ($group);
        
    setTimeout (() => $group.removeClass ("required"), 2000);
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
      wpt_displayMsg ({
        noclosure: true,
        type: "warning", msg: "<?=_("Your password must contain at least:<ul><li><b>6</b> characters</li><li>One <b>lower case</b> letter and one <b>upper case</b> letter</li><li>One <b>number</b></li></ul>")?>"
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
    for (let i = 0, iLen = fields.length; i < iLen; i++)
    {
      const $f = $(fields[i]),
            val = $f.val();

      switch ($f.attr("name"))
      {
        case "email":

          if (!this._checkEmail (val))
            return this.focusBadField ($f, "<?=_("Bad email address")?>");

          break;

        case "email2":

          if (val != $f.closest("form").find("input[name='email']").val())
            return this.focusBadField ($f, "<?=_("Emails must match")?>");

          break;

        case "username":

          if (val.trim().length < 3)
            return this.focusBadField ($f, "<?=_("Login must contain at least 3 characters")?>");

          break;

        case "password":

          if (!this._checkPassword (val))
            return this.focusBadField ($f, "<?=_("Unsecured password")?>");

          break;

        case "password2":

          if (!$f.closest("form").find("input[name='password3']").length)
          {
            if (val != $f.closest("form").find("input[name='password']").val())
              return this.focusBadField ($f, "<?=_("Passwords must match")?>");
          }
          else if (!this._checkPassword (val))
            return this.focusBadField ($f, "<?=_("Unsecured password")?>");

          break;

        case "password3":

          if (val != $f.closest("form").find("input[name='password2']").val())
            return this.focusBadField (
                     $f, "<?=_("New passwords must match")?>");

          break;
      }
    }
    
    return true;
  }
}

// CLASS Wpt_toolbox
class Wpt_toolbox
{
  // METHOD fixPosition ()
  fixPosition ()
  {
    const el = this.element[0],
          pos = el.getBoundingClientRect (),
          wW = window.innerWidth - 20,
          wH = window.innerHeight - 15;

    if (pos.top + pos.height > wH)
      el.style.top = (wH - pos.height)+"px";
    if (pos.left + pos.width > wW)
      el.style.left = (wW - pos.width)+"px";
  }
}

// CLASS Wpt_storage
class Wpt_storage
{
  // METHOD delete ()
  delete (name)
  {
    localStorage.removeItem (name);
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

// CLASS Wpt_sharer
class Wpt_sharer
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
      this[type] = [];
  }

  // METHOD _fullReset ()
  _fullReset ()
  {
    this.wall = [];
    this.chatroom = [];
    this.filters = [];
    this.arrows = [];
    this.postit = [];
    this.header = [];
  }

  // METHOD set ()
  set (k, v)
  {
    this.vars[k] = v;
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
      this.walls = $("#walls");

    switch (item)
    {
      case "wall":

        if (!this.wall.length)
         this.wall = this.walls.find(".tab-pane.active .wall");

        return this.wall;

      case "postit":

        if (!this.postit.length)
         this.postit=this.walls.find(".tab-pane.active .wall .postit.current");

        return this.postit;

      case "header":

        if (!this.header.length)
         this.header = this.walls.find(".tab-pane.active .wall th.current");

        return this.header;

      case "chatroom":

        if (!this.chatroom.length)
         this.chatroom = this.walls.find(".tab-pane.active .chatroom");

        return this.chatroom;

      case "filters":

        if (!this.filters.length)
         this.filters = this.walls.find(".tab-pane.active .filters");

        return this.filters;

      case "arrows":

        if (!this.arrows.length)
         this.arrows = this.walls.find(".tab-pane.active .arrows");

        return this.arrows;

      case "walls":

        return this.walls;
    }
  }
}

// CLASS Wpt_WebSocket
class Wpt_WebSocket
{
  // METHOD constructor ()
  constructor ()
  {
    this.timeoutId = 0;
    this.responseQueue = {};
    this._retries = 0;
    this._msgId = 0;
    this._send_cb = [];
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
    wpt_loader ("show", true);

    clearTimeout (this.timeoutId);
    this.timeoutId = setTimeout (() =>
      {
        if (!this.ready ())
          this.displayNetworkErrorMsg ();
      }, 5000);

    this.cnx = new WebSocket (url);

    // EVENT close
    this.cnx.onclose = (e) =>
    {
      //FIXME Hack to fix Firefox WebSocket closing on file download with
      //      location.href
      if (location.href.indexOf ("#downloading") != -1)
        this.tryToReconnect ();
    };

    // EVENT open
    this.cnx.onopen = (e) =>
      {
        this._connected = true
        this._retries = 0;

        if (opencb_init)
          opencb_init ();

        if (onopen_cb)
          onopen_cb ();

        wpt_loader ("hide", true);
      };

    // EVENT message
    this.cnx.onmessage = (e) =>
      {
        const data = JSON.parse (e.data||"{}"),
              $wall = (data.wall && data.wall.id) ?
                $(".wall[data-id='wall-"+data.wall.id+"']") : [],
              isResponse = (this._send_cb[data._msgId] !== undefined);

        //console.log ("RECEIVED "+data._msgId+"\n");
        //console.log (data);

        if (data.action)
        {
          switch (data.action)
          {
            // sessionexists
            case "sessionexists":
              var $popup = $("#infoPopup");

              $(".modal").modal ("hide");

              wpt_cleanPopupDataAttr ($popup);

              $popup.find(".modal-body").html ("<?=_("Another session was detected. Both sessions will be closed. Please log in again.")?>");
              $popup.find(".modal-title").html (
                '<i class="fas fa-fw fa-exclamation-triangle"></i> <?=_("Warning")?>');
              $popup[0].dataset.popuptype = "app-logout";
              wpt_openModal ($popup);

              setTimeout (() => $("<div/>").wpt_login ("logout"), 3000);
              break;

            // refreshwall
            case "refreshwall":
              if ($wall.length && data.wall)
                $wall.wpt_wall ("refresh", data.wall);
              break;

            // viewcount
            case "viewcount":
              if ($wall.length)
                $wall.wpt_wall ("refreshUsersview", data.count);
              else
                wpt_WebSocket.pushResponse (
                  "viewcount-wall-"+data.wall.id, data.count);
              break;

            // chat
            case "chat":
                $("#wall-"+data.wall.id+" .chatroom")
                  .wpt_chatroom ("addMsg", data);
              break;

            // chatcount
            case "chatcount":
                $("#wall-"+data.wall.id+" .chatroom")
                  .wpt_chatroom ("refreshUserscount", data.count);
              break;

            // deletedwall
            case "deletedwall":
              if (!isResponse)
              {
                wpt_displayMsg ({type: "warning", msg: data.wall.removed}); 
                $wall.wpt_wall ("close");
              }
              break;

            // mainupgrade
            case "mainupgrade":
              wpt_checkForAppUpgrade (data.version);
              break;

            // reload
            case "reload":
              var $popup = $("#infoPopup");

              $(".modal").modal ("hide");

              wpt_cleanPopupDataAttr ($popup);

              $popup.find(".modal-body").html ("<?=_("We are sorry for the inconvenience, but due to a maintenance operation, the application must be reloaded")?>");
              $popup.find(".modal-title").html (
                '<i class="fas fa-fw fa-tools"></i> <?=_("Reload needed")?>');

              $popup[0].dataset.popuptype = "app-reload";
              wpt_openModal ($popup);
              break;
          }
        }

        if (isResponse)
        {
          const msgId = data._msgId;

          delete (data._msgId);

          this._send_cb[msgId].success (data);

          delete this._send_cb[msgId];
        }

        wpt_loader ("hide");

      };

    // EVENT error
    this.cnx.onerror = (e) =>
      {
        wpt_loader ("hide");

        console.error (e);

        this.tryToReconnect ({
          success_cb: () =>
            {
              const $wall = wpt_sharer.getCurrent ("wall");

              if ($wall.length)
                $wall.wpt_wall ("refresh");
            }
        });
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
    $("body").html (`<div class="global-error"><?=_("Maintenance operation in progress. Please reload this page or try again later.")?></div>`);
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
      if (this._connected && this._retries < 5)
        this.tryToReconnect ({
          msg: msg,
          success_cb: success_cb,
          error_cb: error_cb
        });

      return;
    }
    
    msg['_msgId'] = wpt_buildId ();

    //console.log ("SEND "+msg['_msgId']+"\n");

    this._send_cb[msg['_msgId']] = {
      success: success_cb,
      error: error_cb
    };
 
    this.cnx.send (JSON.stringify (msg));
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

// FUNCTION wpt_quoteRegex ()
function wpt_quoteRegex (str)
{
  return (str+"").replace (/(\W)/g, '\\$1');
}

// FUNCTION wpt_htmlQuotes ()
function wpt_htmlQuotes (str)
{
  return (str+"").replace(/["']/g, (c) => ({"\"":"&quot;", "'": "&#x27;"})[c]);
}

// FUNCTION wpt_noHTML ()
function wpt_noHTML (str)
{
  return (str+"").trim().replace (/<[^>]+>/g, "");
}

// FUNCTION wpt_nl2br ()
function wpt_nl2br (str)
{
  return (str+"").replace(/([^>\r\n]?)(\r\n|\n\r|\r|\n)/g, "$1<br>$2");
}

// FUNCTION wpt_closeMainMenu ()
function wpt_closeMainMenu ()
{
  if ($("#main-menu.show").length)
    $("button.navbar-toggler").trigger ("click");
}

// FUNCTION wpt_updatedObject ()
function wpt_updatedObject (obj1, obj2)
{
  for (const key in obj2)
  {
    if (obj1[key] !== null && typeof obj1[key] === "object")
    {
      if (wpt_updatedObject (obj1[key], obj2[key]))
        return true;
    }
    else if (obj1[key] != obj2[key])
      return true;
  }

  return false;
}

// FUNCTION wpt_cleanPopupDataAttr ()
function wpt_cleanPopupDataAttr ($popup)
{
  const id = $popup.attr ("id"),
        attrs = [];

  // Remove all data attributes
  $.each ($popup[0].attributes, function (i, a)
    {
      if (a.name.indexOf ("data-") == 0)
        attrs.push (a.name);
    });
  attrs.forEach ((item) => $popup.removeAttr (item));

  $popup.find("span.required").remove ();
  $popup.find(".input-group.required").removeClass ("required");

  switch (id)
  {
    case "plugPopup":

      $popup.find("input").val ("");
      break;
 
    case "groupPopup":

      $popup.find("input").val (""); 
      $popup.find(".desc").html ("");
      $popup.find("button.btn-primary")[0].removeAttribute ("data-type");
      $popup.find("button.btn-primary")[0].removeAttribute ("data-groupid");

      break;

    case "updateOneInputPopup":

      const $input = $popup.find("input");

      $popup.find(".modal-dialog").removeClass ("modal-sm");
      $popup.find("#w-grid").parent().remove ();
      $popup.find(".btn-primary").html ("<?=_("Save")?>");

      $input.removeAttr ("placeholder");
      $input.removeAttr ("autocorrect autocapitalize");
      $input.val ("");

      break;

    case "confirmPopup":

      $popup.removeClass ("no-theme");

      break;
  }
}

// FUNCTION wpt_getHumanSize ()
function wpt_getHumanSize (bytes)
{
  const i = Math.floor(Math.log(bytes) / Math.log(1024)),
        sizes = ['B', 'KB', 'MB'];

  return (bytes / Math.pow(1024, i)).toFixed(2) * 1 + ' ' + sizes[i];
}

// FUNCTION wpt_getAccess ()
function wpt_getAccess ()
{
  const $wall = wpt_sharer.getCurrent ("wall");

  return ($wall.length) ? $wall[0].dataset.access : "";
}

// FUNCTION wpt_checkAccess ()
function wpt_checkAccess (requiredRights, currentAccess)
{
  if (currentAccess === undefined)
  {
    const $wall = wpt_sharer.getCurrent ("wall");

    if ($wall.length)
      currentAccess = $wall[0].dataset.access;
  }

  return (currentAccess === undefined) ?
    false : (currentAccess == "<?=WPT_RIGHTS['walls']['admin']?>" ||
             currentAccess == String (requiredRights))
}

// FUNCTION wpt_getAccessIcon ()
function wpt_getAccessIcon (access)
{
  let icon;

  if (!access)
    access = wpt_getAccess ();

  switch (String (access))
  {
    case "<?=WPT_RIGHTS['walls']['admin']?>": icon = 'fa-shield-alt'; break;
    case "<?=WPT_RIGHTS['walls']['rw']?>": icon = 'fa-edit'; break;
    case "<?=WPT_RIGHTS['walls']['ro']?>": icon = 'fa-eye'; break;
  }

  return `<i class="fas ${icon} fa-fw"></i>`;
}

// FUNCTION wpt_getUserDate ()
function wpt_getUserDate (dt, tz, fmt)
{
  return moment.unix(dt).tz(tz||wpt_userData.settings.timezone)
           .format(fmt||"Y-MM-DD");
}

//FIXME
// FUNCTION wpt_buildId ()
function wpt_buildId ()
{
  const min = 0,
        max = 1000;

  return moment.now()+""+(Math.floor (Math.random()*(max - min + 1)) + min);
}

// FUNCTION wpt_loader ()
function wpt_loader (action, force = false)
{
  const $layer = $("#popup-loader");

  if ($layer.length && (wpt_WebSocket.ready () || force))
  {
    clearTimeout ($layer[0].dataset.timeoutid);

    if (action == "show")
      $layer[0].dataset.timeoutid = setTimeout (() => $layer.show (), 500);
    else
      $layer.hide ();
  }
}

// FUNCTION wpt_openPopupLayer ()
function wpt_openPopupLayer (cb, closeMenus = true)
{
  const $layer = $(`<div id="popup-layer" class="layer"></div>`);

  $(document)
    .on ("keydown", function (e)
    {
      if (e.key == "Escape")
        $layer.trigger ("click");
    });

    $layer
      .on ("click",
      function (e)
      {
        $(document).off ("keydown");

        // Remove the layer
        $(this).remove ();

        if (cb)
          cb (e);
      });

  $layer.prependTo("body").show ();
}

// FUNCTION wpt_openInputPopup ()
function wpt_openInputPopup (args)
{
  const $popup = $("#updateOneInputPopup");

  wpt_cleanPopupDataAttr ($popup)

  if (args.before)
    args.before ($popup);

  $popup.find(".modal-title").html (args.title);
  $popup.find("input").val (args.defaultValue||'');

  $popup.find(".btn-primary").off("click").on("click", function (e)
    {
      args.cb_save ($popup.find("input").val(), $popup);
      if (args.closure === undefined || args.closure == true)
      $popup.modal ("hide");
    });

  $popup.find(".btn-secondary").off("click").on("click", function (e)
    {
      args.cb_close ();
    });

  $popup.modal ("show");
}

function wpt_openConfirmPopup (args)
{
  const $popup = $("#confirmPopup");

  wpt_sharer.set ("confirmPopup", {
    cb_ok: args.cb_ok,
    cb_cancel: () =>
      {
        if (args.cb_cancel)
          args.cb_cancel ();
        wpt_sharer.unset ("confirmPopup");
      }
  });

  wpt_cleanPopupDataAttr ($popup);

  $popup.find(".modal-title").html (
    `<i class="fas fa-${args.icon} fa-fw"></i> <?=_("Confirmation")?>`);
  $popup.find(".modal-body").html (args.content);

  $popup[0].dataset.popuptype = args.type;

  wpt_openModal ($popup);
}

// FUNCTION wpt_openConfirmPopover ()
function wpt_openConfirmPopover (args)
{
  wpt_openPopupLayer (() =>
    { 
      $(".popover").popover ("dispose");

      if (args.cb_close)
        args.cb_close ();
    }, false);
  
  args.item.popover({
    html: true,
    title: args.title,
    placement: args.placement || "auto",
    boundary: "window"
  }).popover ("show");
  
  //FIXME If not, "title" element property is used by default
  $(".popover-header").html (args.title);

  const buttons = (args.type && args.type == "update") ?
    `<button type="button" class="btn btn-xs btn-primary"><?=_("Save")?></button> <button type="button" class="btn btn-xs btn-secondary"><?=_("Close")?></button>` : `<button type="button" class="btn btn-xs btn-primary"><?=_("Yes")?></button> <button type="button" class="btn btn-xs btn-secondary"><?=_("No")?></button>`;

  const $body = $(".popover-body");

  $body.html (`<p>${args.content}</p>${buttons}`);

  if (!$.support.touch)
    $body.find("input:eq(0)").focus ();

  $body.find("button").on("click", function (e)
    {
      const $btn = $(this);

      if ($btn.hasClass ("btn-primary"))
        args.cb_ok ($btn.closest(".popover"));

      $("#popup-layer").trigger ("click");
    });
}

// FUNCTION wpt_openModal ()
function wpt_openModal ($modal)
{
  $modal.modal({
    backdrop: true,
    show: true
  })
  .draggable({
    //FIXME "distance" is deprecated -> is there any alternative?
    distance: 10,
    handle: ".modal-header",
    cursor: "pointer"
  })
  .css({
    top: 0,
    left: 0
  });
}

// FUNCTION wpt_infoPopup ()
function wpt_infoPopup (msg, notheme)
{
  const $popup = $("#infoPopup");

  if (notheme)
    $popup.addClass ("no-theme");
  else
    wpt_cleanPopupDataAttr ($popup);
    
  $popup.find(".modal-dialog").addClass ("modal-sm");

  $popup.find(".modal-body").html (msg);

  $popup.find(".modal-title").html (
    '<i class="fas fa-bullhorn"></i> <?=_("Information")?>');

  wpt_openModal ($popup);
}

// FUNCTION wpt_raiseError ()
function wpt_raiseError (error_cb, msg)
{
  if (error_cb)
    error_cb ();

  wpt_displayMsg ({
    type: (msg)?"warning" : "danger",
    msg: (msg)?msg : "<?=_("System error. Please report it to the administrator.")?>"
  });
}

// FUNCTION wpt_displayMsg ()
function wpt_displayMsg (args)
{
  const $previous = $(".alert").eq(0),
        id = (args.noclosure) ? "noclosure": "timeout-"+Math.random();
  let $target;

  if (args.target)
    $target = args.target;
  else
  {
    const $modals = $(".modal:visible");

    $target = $modals.length ?
                $modals.last().find(".modal-body") : $("#msg-container");
  }

  $target.find(".alert[data-timeoutid='noclosure']").remove ();

  if (args.reset)
    return;

  if (!($previous.length && $previous.find("span").text () == args.msg))
  {
    $target.prepend (`<div class="alert alert-dismissible alert-${args.type}" data-timeoutid="${id}"><a href="#" class="close" data-dismiss="alert">&times;</a>${args.title ? "<strong>"+args.title+"</strong> ":""}<span>${args.msg}</span></div>`);

    if (!args.noclosure)
      setTimeout (() =>
        {
          const $div = $target.find(".alert[data-timeoutid='"+id+"']");

          $div.hide("fade", function(){$div.remove ()})
  
        }, 2000);
  }
}

// FUNCTION wpt_fixMenuHeight ()
function wpt_fixMenuHeight ()
{
  const menu = document.querySelector (".navbar-collapse.collapse"),
        mbBtn = document.querySelector (".navbar-toggler-icon");

  // If menu is in min mode, limit menus height
  if (mbBtn.offsetWidth > 0 && mbBtn.offsetHeight > 0)
  {
    menu.style.overflowY = "auto";
    menu.style.maxHeight = (window.innerHeight - 56)+"px";
  }
  else
  {
    menu.style.overflowY = "";
    menu.style.maxHeight = "";
  }
}

// FUNCTION wpt_fixMainHeight ()
function wpt_fixMainHeight ()
{
  document.querySelector("html").style.overflow = "hidden";

  wpt_sharer.getCurrent("walls")[0].style.height = (
    window.innerHeight -
      (document.querySelector(".nav-tabs.walls").offsetHeight + 70))+"px";
}

//FIXME Hack to fix Firefox WebSocket closing on file download with
//      location.href
// FUNCTION wpt_fixDownloadingHack ()
function wpt_fixDownloadingHack ()
{
  const loc = location.href;
    
  if (loc.indexOf ("#downloading") != -1)
    location.href = loc.replace (/#downloading/, "#");
}

// FUNCTION wpt_enableTooltips ()
function wpt_enableTooltips ($item)
{
  if (!$.support.touch)
    $item.tooltip ();
}

// FUNCTION wpt_request_ws ()
function wpt_request_ws (method, service, args, success_cb, error_cb)
{
  const msgArgs = {type: "danger", title: "<?=_("Warning!")?>"};

  wpt_loader ("show");

  //console.log ("WS: "+method+" "+service);

  wpt_WebSocket.send ({
    method: method,
    route: service,
    data: args ? encodeURI (JSON.stringify (args)) : null  
  },
  (d) =>
    {
      wpt_loader ("hide");

      // If PHP session has expired, redirect on login page
      if (d.expired_session)
        return location.href = '/index.php';

      if (d.error)
      {
        if (error_cb)
          error_cb (d);
        else
        {
          msgArgs["msg"] = (isNaN (d.error)) ?
            d.error :
            "<?=_("Network error.<br>Please try again later.")?>";

          wpt_displayMsg (msgArgs);
        }
      }
      else if (success_cb)
        success_cb (d);
    },
  () =>
    {
      wpt_loader ("hide");

      if (error_cb)
        error_cb ();
    });
}

// FUNCTION wpt_request_ajax ()
function wpt_request_ajax (method, service, args, success_cb, error_cb)
{
  const msgArgs = {type: "danger", title: "<?=_("Warning!")?>"},
        timeout = <?=WPT_TIMEOUTS['default'] * 1000?>;

  wpt_loader ("show");

  //console.log ("AJAX: "+method+" "+service);

  $.ajax (
    {
      type: method,
      timeout: timeout,
      async: true,
      cache: false,
      url: "/api/"+service,
      data: args ? encodeURI (JSON.stringify (args)) : null,
      dataType: "json",
      contentType: "application/json;charset=utf-8"
    })
    .done (function (d)
     {
       // If PHP session has expired, redirect on login page
       if (d.expired_session)
         return location.href = '/index.php';

       if (d.error)
       {
         if (error_cb)
           error_cb (d);
         else
         {
           msgArgs["msg"] = (isNaN (d.error)) ?
             d.error :
             "<?=_("Network error.<br>Please try again later.")?>";

           wpt_displayMsg (msgArgs);
         }
       }
       else if (success_cb)
         success_cb (d);
     })
    .fail (function (jqXHR, textStatus, errorThrown)
     {
       // No user msg for internal works
       msgArgs["msg"] = (textStatus == "timeout") ?
         "<?=_("The server is taking too long to respond.<br>Please, try again later.")?>" :
         "<?=_("Network error.<br>Please try again later.")?>";

       wpt_displayMsg (msgArgs);

       if (error_cb)
         error_cb ();
     })
    .always (function (){wpt_loader ("hide")});
}

// FUNCTION wpt_checkUploadFileSize ()
function wpt_checkUploadFileSize (size)
{
  let ret = true;

  if (size / 1024000 > <?=WPT_UPLOAD_MAX_SIZE?>)
  {
    ret = false;

    wpt_displayMsg ({
        type: "danger",
        msg: "<?=_("File size is too large (%sM max)!")?>".replace("%s", <?=WPT_UPLOAD_MAX_SIZE?>)
      });
  }

  return ret;
}

// FUNCTION wpt_getUploadedFiles ()
function wpt_getUploadedFiles (files, success_cb, error_cb)
{
  const reader = new FileReader ();

  reader.onprogress = (e) =>
    {
      if (!wpt_checkUploadFileSize (e.total))
        reader.abort ();
    }

  reader.onerror = (e) =>
    {
      wpt_displayMsg ({
        type: "danger",
        msg: "<?=_("Can not read file")?> ("+e.target.error.code+")"
      });
    }

  reader.onloadend = ((f) => (evt) => success_cb (evt, f))(files[0]);
  reader.readAsDataURL (files[0]);
}

// FUNCTION wpt_headerRemoveContentKeepingWallSize ()
function wpt_headerRemoveContentKeepingWallSize (args)
{
  const $wall = wpt_sharer.getCurrent ("wall");
  let tdW = 0;

  // Get row TD total width
  $wall.find("tbody tr:eq(0) td").each (function ()
    {
      tdW += $(this).outerWidth ();
    });

  args.cb ();

  $wall.find("tbody tr th").css ("width", 1);

  const newW = $wall.find("thead th:eq(0)").outerWidth ();

  if (newW != args.oldW)
  {
    $wall.wpt_wall ("fixSize", args.oldW, newW);
    $wall.css ("width", tdW + newW);
  }
}

// FUNCTION wpt_waitForDOMUpdate ()
function wpt_waitForDOMUpdate (cb)
{
  //FIXME
  setTimeout (
    () => window.requestAnimationFrame (
            () => window.requestAnimationFrame (cb)), 150);
}

// FUNCTION wpt_checkForAppUpgrade ()
function wpt_checkForAppUpgrade (version)
{
  const html = $("html")[0],
        $popup = $("#infoPopup"),
        officialVersion = (version) ? version : html.dataset.version;

  if (String(officialVersion).length != 10)
  {
    const $userSettings = $("#settingsPopup"),
          userVersion = $userSettings.wpt_settings ("get", "version");

    if (userVersion != officialVersion)
    {
      $userSettings.wpt_settings ("set", {version: officialVersion});

      if (userVersion)
      {
        // Needed to unedit current items
        $(".modal").modal ("hide");

        wpt_cleanPopupDataAttr ($popup);

        $popup.find(".modal-body").html ("<?=_("A new release of wopits is available!")?><br><?=_("The application will be upgraded from v%s1 to v%s2.")?>".replace("%s1", "<b>"+userVersion+"</b>").replace("%s2", "<b>"+officialVersion+"</b>"));
        $popup.find(".modal-title").html (
          '<i class="fas fa-fw fa-glass-cheers"></i> <?=_("New release")?>');

        $popup[0].dataset.popuptype = "app-upgrade";
        wpt_openModal ($popup);

        return true;
      }
    }
    else if (html.dataset.upgradedone)
    {
      html.removeAttribute ("data-upgradedone");

      $popup.find(".modal-dialog").removeClass ("modal-sm");
      $popup.find(".modal-body").html ("<?=_("Upgrade done.")?><br><?=_("Thank you for using wopits!")?>");
      $popup.find(".modal-title").html (
        '<i class="fas fa-glass-cheers"></i> <?=_("Upgrade done")?>');

      wpt_openModal ($popup);
    }
  }
}

// GLOBAL VARS
const wpt_sharer = new Wpt_sharer (),
      wpt_storage = new Wpt_storage (),
      wpt_WebSocket = new Wpt_WebSocket ();
