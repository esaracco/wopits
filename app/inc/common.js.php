/**
  Global javascript classes:

  Wpt_forms -> parent for forms plugins (swall...)
  Wpt_accountForms -> parent for account forms plugins
  Wpt_toolbox -> parent for toolbox utilities (filters, mmenu, chat...)

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
        msg: `<?=_("Your password must contain at least:<ul><li><b>6</b> characters</li><li>A <b>lower case</b> and a <b>upper case</b> letter</li><li>A <b>digit</b></li></ul>")?>`
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

// CLASS Wpt_postitCountPlugin
class Wpt_postitCountPlugin {
  // METHOD getIds()
  getIds () {
    const {wallId, cellId, id: postitId} = this.postit().settings;

    return {wallId, cellId, postitId}
  }

  // METHOD addTopIcon()
  addTopIcon(className, action) {
    const count = this.settings.count;

    const el_I = H.createElement('i',
        {className: `fa-fw fas ${className}`}, {action});
    const el_SPAN = H.createElement('span',
      {className: `wpt-badge ${count ? '' : 'hidden'}`});
    el_SPAN.innerText = count;

    this.element[0].append(el_I, el_SPAN); 
  }

  // METHOD postit()
  postit() {
    return this.settings.postitPlugin;
  }

  // METHOD setCount()
  setCount(count) {
    const elC = this.element[0].querySelector('span');

    elC.style.display = count ? 'inline-block' : 'none';
    elC.innerText = count;

    if (this.settings.readonly) {
      this.element[0].style.display = count ? 'inline-block' : 'none';
    }
  }

  // METHOD getCount()
  getCount() {
    return Number(this.element[0].querySelector('span').innerText);
  }

  // METHOD incCount()
  incCount() {
    this.setCount(this.getCount() + 1);
  }

  // METHOD decCount()
  decCount() {
    this.setCount(this.getCount() - 1);
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

    if (pos.top <= mH + 4)
      el.style.top = `${mH+4}px`;
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

    if (pos.top <= mH + 4)
      pos.top = mH + 4;
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
class WSharer {
  // METHOD constructor()
  constructor() {
    this.vars = {};
    this.walls = [];
    this._fullReset();
  }

  // METHOD reset()
  reset (type) {
    if (!type) {
      this._fullReset ();
    } else {
      this[type] = typeof this[type] === 'string' ? '' : [];
    }
  }

  // METHOD _fullReset()
  _fullReset() {
    this.wall = [];
    this.chat = [];
    this.filters = [];
    this.wmenu = [];
    this.postit = [];
    this.pcomm = [];
    this.header = [];
    this.plugColor = '';
  }

  // METHOD set()
  set(k, v, t) {
    this.vars[k] = v;

    if (t) {
      setTimeout(() => this.unset(k), t);
    }
  }

  // METHOD get()
  get(k) {
    return this.vars[k];
  }

  // METHOD unset()
  unset(k) {
    delete this.vars[k];
  }

  // METHOD getCurrent()
  getCurrent(item) {
    if (!this.walls.length) {
      this.walls = $(document.getElementById('walls'));
      if (!this.walls.length) {
        return $([]);
      }
    }

    switch (item) {
      case 'wall':
        if (!this.wall.length) {
          this.wall =
            $(this.walls[0].querySelector('.tab-pane.active .wall') || []);
        }
        return this.wall;
      case 'postit':
        if (!this.postit.length) {
          this.postit =
            $(this.walls[0].querySelector('.tab-pane.active .postit.current'));
        }
        return this.postit;
      case 'plugColor':
        if (!this.plugColor) {
          const el = document.querySelector('.cell-menu .btn-secondary');
          this.plugColor = H.rgb2hex(
            window.getComputedStyle ?
              window.getComputedStyle(el, null)
                .getPropertyValue('background-color') :
              el.style.backgroundColor
            );
        }
        return this.plugColor;
      case 'header':
        if (!this.header.length) {
          this.header =
            $(this.walls[0].querySelector('.tab-pane.active th.wpt.current'));
        }
        return this.header;
      case 'tpick':
        if (!this.tpick) {
          this.tpick = $(document.getElementById('tpick'));
        }
        return this.tpick;
      case 'walls':
        return this.walls;
      case 'sandbox':
        if (!this.sandbox) {
          this.sandbox = $(document.getElementById('sandbox'));
        }
        return this.sandbox;
      case 'chat':
        if (!this.chat.length) {
          this.chat = $(this.walls[0].querySelector('.tab-pane.active .chat'));
        }
        return this.chat;
      case 'filters':
        if (!this.filters.length) {
          this.filters =
            $(this.walls[0].querySelector('.tab-pane.active .filters'));
        }
        return this.filters;
      case 'wmenu':
        if (!this.wmenu.length) {
          this.wmenu =
            $(this.walls[0].querySelector('.tab-pane.active .wall-menu'));
        }
        return this.wmenu;
      case 'mmenu':
        if (!this.mmenu) {
          this.mmenu = $(document.getElementById('mmenu'));
        }
        return this.mmenu;
      case 'pcomm':
        if (!this.pcomm.length) {
          this.pcomm = $(this.getCurrent('postit')[0].querySelector('.pcomm'));
        }
        return this.pcomm;
    }
  }
}

// CLASS WSocket
class WSocket
{
  // METHOD constructor()
  constructor() {
    this.cnx = null;
    this.cnxTimeoutId = 0;
    this.waitForMsgId = {};
    this.responseQueue = {};
    this.retries = 0;
    this.msgId = 0;
    this.send_cb = {};
    this.connected = false;
  }

  // METHOD connect()
  connect(url, init) {
    this._connect(url, {init});
  }

  // METHOD _connect()
  _connect(url, {onSuccess, init}) {
    H.loader('show', {force: true});

    this.cnx = new WebSocket(url);

    this.cnxTimeoutId = setTimeout(
      () => this.cnx.close(), <?=WPT_TIMEOUTS['network_connection'] * 1000?>);

    // EVENT "open"
    this.cnx.onopen = (e) => {
      clearTimeout(this.cnxTimeoutId);
      this.cnxTimeoutId = 0;

      this.connected = true
      this.retries = 0;

      init && init();
      onSuccess && onSuccess();

      H.loader('hide', {force: true});
    };

    // EVENT "message"
    this.cnx.onmessage = (e) => {
      const data = JSON.parse(e.data || '{}');
      const $wall = data.wall?.id ? $(`[data-id="wall-${data.wall.id}"]`) : [];
      const isResponse = (this.send_cb[data.msgId] !== undefined);
      let el;
      let popup;
      let nextMsg = null;

      //console.log(`RECEIVED ${data.msgId}\n`);
      //console.log(data);

      if (isResponse) {
        clearTimeout(this.waitForMsgId[data.msgId]);
        delete this.waitForMsgId[data.msgId];
      }

      if (data.action) {
        switch (data.action) {
          // userwriting
          case 'userwriting':
            el = document.querySelector(
                `${data.item === 'postit' ? '.postit' : ''}`+
                `[data-id="${data.item}-${data.itemId}"]`);
            if (el) {
              $(el)[data.item]('showUserWriting', data.user);
            }
            break;
          // userstoppedwriting
          case 'userstoppedwriting':
              H.hideUserWriting(data.userstoppedwriting.user);
            break;
          // exitsession
          case 'exitsession':
            //TODO use H.infoPopup()
            popup = document.getElementById('infoPopup');

            popup.querySelector('.modal-body').innerHTML = `<?=_("One of your sessions has just been closed. All of your sessions will end. Please log in again.")?>`;
            popup.querySelector('.modal-title').innerHTML = `<i class="fas fa-fw fa-exclamation-triangle"></i> <?=_("Warning")?>`;
            popup.dataset.popuptype = 'app-logout';
            H.openModal({item: popup, customClass: 'zindexmax'});

            // Close current popups if any
            setTimeout(()=> (S.get('mstack') || []).forEach(
                (el) => bootstrap.Modal.getInstance(el).hide()), 3000);
            break;
          // refreshpcomm
          case 'refreshpcomm':
            el = document.querySelector(
                     `[data-id="postit-${data.postitId}"] .pcomm`);

            if (el) {
              $(el).pcomm('refresh', data);
            }
            break;
          // refreshwall
          case 'refreshwall':
            if ($wall.length && data.wall) {
              data.wall.isResponse = isResponse;
              $wall.wall('refresh', data.wall);

              if (data.userstoppedwriting) {
                H.hideUserWriting(data.userstoppedwriting.user);
              }
            }
            break;
          // viewcount
          case 'viewcount':
            if ($wall.length) {
              $wall.wall('refreshUsersview', data.count);
            } else {
              WS.pushResponse(`viewcount-wall-${data.wall.id}`, data.count);
            }
            break;
          // chat
          case 'chat':
            if ($wall.length) {
              $(`#wall-${data.wall.id} .chat`).chat('addMsg', data);
            }
            break;
          // chatcount
          case 'chatcount':
            if ($wall.length) {
              $(`#wall-${data.wall.id} .chat`)
                  .chat('refreshUserscount', data.count);
            }
            break;
          // have-msg
          case 'have-msg':
            $('#umsg').umsg('addMsg', data);
            break;
          // unlinked
          // Either the wall has been deleted
          // or the user no longer have necessary right to access the wall.
          case 'unlinked':
            if (!isResponse) {
              H.displayMsg({
                title: `<?=_("Walls")?>`,
                type: 'warning',
                msg: `<?=_("Some walls are no longer available")?>`,
              });
              $wall.wall('close');
              $('#settingsPopup').settings('removeRecentWall', data.wall.id);
            }
            break;
          // mainupgrade
          case 'mainupgrade':
            // Check only when all modals are closed
            const iid = setInterval(() => {
                if (!(S.get('mstack') || []).length) {
                  clearInterval(iid);
                  H.checkForAppUpgrade(data.version);
                }
              }, 5000);
            break;
          // Reload to refresh user working space.
          case 'reloadsession':
            return location.href = `/r.php?l=${data.locale}`;
          // Maintenance reload
          case 'reload':
            //TODO use H.infoPopup()
            popup = document.getElementById('infoPopup');

            // No need to deactivate it afterwards: page will be reloaded.
            S.set('block-msg', true);

            // Close current popups if any
            (S.get('mstack') || []).forEach((el) =>
                bootstrap.Modal.getInstance(el).hide());

            popup.querySelector('.modal-body').innerHTML = `<?=_("We are sorry for the inconvenience, but due to a maintenance operation, the application must be reloaded.")?>`;
            popup.querySelector('.modal-title').innerHTML = `<i class="fas fa-fw fa-tools"></i> <?=_("Reload needed")?>`;

            popup.dataset.popuptype = 'app-reload';
            H.openModal({item: popup, customClass: 'zindexmax'});
            break;
        }
      }

      if (data.msgId) {
        const {msgId} = data;

        delete data.msgId;

        if (isResponse) {
          this.send_cb[msgId](data);
          delete this.send_cb[msgId];
        }
      }

      H.loader('hide');
    };

    // EVENT "error"
    this.cnx.onerror = (e) => {
      if (this.retries < 15) {
        this.tryToReconnect({
          success_cb: () => {
            const $wall = S.getCurrent('wall');

            if ($wall.length) {
              $wall.wall('refresh');
            }
          },
        });
      } else {
        this.displayNetworkErrorMsg();
      }
    };
  }

  // METHOD tryToReconnect()
  tryToReconnect(args) {
    this.connected = false;
    ++this.retries;

    this._connect(this.cnx.url, {
      onSuccess: !args ? undefined : () => {
        if (args.msg) {
          this.send(args.msg, args.success_cb, args.error_cb);
        } else if (args.success_cb) {
          args.success_cb();
        } else {
          this.displayNetworkErrorMsg();
        }
      },
    });
  }

  // METHOD ready()
  ready() {
    return (this.cnx?.readyState === WebSocket.OPEN);
  }

  // METHOD send()
  send(msg, success_cb, error_cb) {
    if (!this.ready()) {
      if (this.connected && this.retries < 15) {
        this.tryToReconnect({msg, success_cb, error_cb});
      } else if (error_cb) {
        error_cb();
      }
      return;
    }

    // Put message in message queue if not already in
    if (!msg.msgId) {
      msg.msgId = ++this.msgId;
    }

    this.send_cb[msg.msgId] = success_cb;
 
    try {
      this.waitForMsgId[msg.msgId] = setTimeout(
          () => this.displayNetworkErrorMsg(),
          <?=WPT_TIMEOUTS['network_connection'] * 1000?>);
      this.cnx.send(JSON.stringify(msg));
    } catch(e) {
      this.tryToReconnect({msg, success_cb, error_cb});
    }
  }

  // METHOD pushResponse()
  pushResponse(type, response) {
    if (!this.responseQueue[type]) {
      this.responseQueue[type] = [];
    }
    
    this.responseQueue[type].push(response);
  }

  // METHOD popResponse()
  popResponse(type) {
    const rq = this.responseQueue[type];

    return rq?.length ? rq.pop() : undefined;
  }

  displayNetworkErrorMsg() {
    H.loader('hide', {force: true});
    H.displayNetworkErrorMsg();
  }
}

const entitiesMap = {
  "&": "&amp;",
  "<": "&lt;",
  ">": "&gt;",
  '"': "&quot;",
  "'": "&#39;"
};

// CLASS WHelper
class WHelper
{
  // METHOD displayNetworkErrorMsg()
  static displayNetworkErrorMsg() {
    const el = document.getElementById('popup-loader');
    const text = `<?=_("Network error!<br>Please, check your connection.")?>`;

    if (!el) {
      document.body.innerHTML = `<div class="global-error">${text}</div>`;
    } else if (!el.classList.contains('show')) {
      this.loader('show', {
        text,
        force: true,
        delay: 0,
        icon: 'fas fa-exclamation-circle fa-lg',
      });
    }
  }

  // METHOD preventDefault()
  static preventDefault(e) {
    if (typeof e.cancelable !== 'boolean' || e.cancelable) {
      e.preventDefault();
    }
  }

  // METHOD createElement()
  static createElement(tag, props, dataSet, content) {
    const el = Object.assign(document.createElement(tag), props);

    if (dataSet) {
      Object.keys(dataSet).forEach(
          (attr) => el.dataset[attr] = dataSet[attr]);
    }

    if (content) {
      el.innerHTML = content;
    }

    return el;  
  }

  // METHOD createUploadElement()
  static createUploadElement({attrs, onChange, onClick}) {
    const el = this.createElement('input', {...attrs, type: 'file'});

    el.classList.add('upload');

    if (onChange) {
      el.addEventListener('change', onChange);
    }

    if (onClick) {
      el.addEventListener('click', onClick);
    }

    document.body.appendChild(el);
  }

  // METHOD hideUserWriting()
  static hideUserWriting({id}) {
    // Unlock elements
    document.querySelectorAll(
      `[class^="user-writing"][data-userid="${id}"]`).forEach((el) => {
      const parentNode = el.parentNode;
      // Unlock postit plugs
      if (parentNode.classList.contains('postit')) {
        ($(parentNode).postit('getSettings').plugs || []).forEach((plug) => {
          plug.labelObj[0].classList.remove('locked');
        });
      }
      parentNode.classList.remove('locked', 'main');
      el.remove();
    });
  }

  // METHOD isLoginPage()
  static isLoginPage() {
    // Only the login page has an "id" attr
    return Boolean(document.body.id);
  }

  // METHOD disabledEvent()
  static disabledEvent(cond) {
    return Boolean((cond === true) || S.get('link-from') || S.get('dragging'));
  }

  // METHOD lightenDarkenColor ()
  static lightenDarkenColor (col, amt)
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

  // METHOD rgb2hex()
  static rgb2hex(rgb) {
    // If already in hex
    if (rgb.charAt(0) === '#') {
      return rgb;
    }

    const hex = (x) => ('0' + parseInt(x).toString(16)).slice(-2);

    rgb = rgb.match(/^rgba?\((\d+),?\s*(\d+),?\s*(\d+)/);

    return `#${hex(rgb[1])}${hex(rgb[2])}${hex(rgb[3])}`;
  }

  // METHOD getFirstInputFields()
  static getFirstInputFields(el) {
    return el.querySelector(
        `input[type="text"]:not(:read-only),`+
        `input[type="email"]:not(:read-only),`+
        `input[type="password"]:not(:read-only),`+
        `input[type="number"]:not(:read-only),`+
        `textarea:not(:read-only)`);
  }

  // METHOD setAutofocus ()
  static setAutofocus(el) {
    const input = this.getFirstInputFields(el);

    if (input) {
      input.focus();
    }
  }

  // METHOD testImage ()
  static testImage (url, timeout = 10000)
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
  static haveMouse ()
  {
    return (window.matchMedia("(hover: hover)").matches);
  }

  // METHOD isMainMenuCollapsed ()
  static isMainMenuCollapsed ()
  {
    return $(`button[data-target="#main-menu"]`).is (":visible");
  }

  // METHOD escapeRegex ()
  static escapeRegex (str)
  {
    return (`${str}`).replace(/[.*+?^${}()|[\]\\]/g, "\\$&");
  }

  // METHOD quoteRegex ()
  static quoteRegex (str)
  {
    return (`${str}`).replace (/(\W)/g, '\\$1');
  }

  // METHOD HTMLEscape ()
  static htmlEscape (str)
  {
    return (`${str}`).replace (/[&<>"']/g, (c) => entitiesMap[c]);
  }
  
  // METHOD noHTML ()
  static noHTML (str)
  {
    return (`${str}`).trim().replace (/<[^>]+>|&[^;]+;/g, "");
  }

  // METHOD nl2br ()
  static nl2br (str)
  {
    return (`${str}`).replace(/([^>\r\n]?)(\r\n|\n\r|\r|\n)/g, "$1<br>$2");
  }
  
  // METHOD closeMainMenu ()
  static closeMainMenu ()
  {
    if (document.querySelector ("#main-menu.show"))
      document.querySelector("button.navbar-toggler").click ();
  }
  
  // METHOD trimObject ()
  static trimObject (obj, exclude = [])
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
  static updatedObject (obj1, obj2, ignore = {})
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
  
  // METHOD cleanPopupDataAttr()
  static cleanPopupDataAttr(popup) {
    // Remove all popup data attributes
    Array.from(popup.attributes).forEach(({name}) => {
      if (name.indexOf('data-') === 0) {
        popup.removeAttribute(name);
      }
    });
  
    // Remove required warnings
    popup.querySelectorAll('span.required').forEach((el) => el.remove());
    popup.querySelectorAll('.input-group.required').forEach(
        (el) => el.classList.remove('required'));
  
    switch (popup.id) {
      case 'createWallPopup':
        const wGrid = popup.querySelector('#w-grid');
  
        popup.querySelector('input').value = '';
        popup.querySelectorAll('.cols-rows input').forEach(
            (el) => el.value = 3);
        popup.querySelectorAll('.cols-rows,.width-height')
            .forEach((el) => el.style.display = 'none');
        popup.querySelector('.cols-rows').style.display = 'flex';
        wGrid.checked = true;
        wGrid.parentNode.classList.remove('disabled');
        break;
      case 'groupPopup':
        const btn = popup.querySelector('button.btn-primary');

        ['data-type', 'data-groupid'].forEach((c) => btn.removeAttribute(c));
        popup.querySelectorAll('input').forEach((el) => el.value = ''); 
        break;
      case 'groupAccessPopup':
        popup.querySelector('.send-msg input[type="checkbox"]').checked = false;
        break;
    }
  }
  
  // METHOD getHumanSize ()
  static getHumanSize (bytes)
  {
    const i = Math.floor (Math.log(bytes) / Math.log(1024)),
          sizes = ['B', 'KB', 'MB'];
  
    return (bytes / Math.pow(1024, i)).toFixed(2) * 1 + ' ' + sizes[i];
  }
  
  // METHOD getAccess ()
  static getAccess ()
  {
    const $wall = S.getCurrent ("wall");
  
    return ($wall.length) ? $wall[0].dataset.access : "";
  }
  
  // METHOD checkAccess ()
  static checkAccess (requiredRights, currentAccess)
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
  static getAccessIcon (access)
  {
    let icon;
  
    switch (String (access||this.getAccess ()))
    {
      case "<?=WPT_WRIGHTS_ADMIN?>": icon = "shield-alt"; break;
      case "<?=WPT_WRIGHTS_RW?>": icon = "edit"; break;
      case "<?=WPT_WRIGHTS_RO?>": icon = "eye"; break;
    }
  
    return `<i class="fas fa-${icon} fa-fw"></i>`;
  }
  
  // METHOD getUserDate ()
  static getUserDate (dt, tz, fmt)
  {
    return moment.unix(dt).tz(
      tz||wpt_userData.settings.timezone||moment.tz.guess())
        .format(fmt||"Y-MM-DD");
  }

  static checkUserVisible ()
  {
    return (wpt_userData.settings && wpt_userData.settings.visible == 1);
  }
  
  // METHOD loader()
  // static loader(action, force = false, xhr = null) {
  static loader(action, args) {
    const layer = document.getElementById('popup-loader');
  
    if (layer && (WS.ready() || args?.force)) {
      const progress = layer.querySelector('.progress');
      const button = layer.querySelector('button');

      clearTimeout(layer.dataset.timeoutid);
  
      if (action === 'show') {
        layer.dataset.timeoutid = setTimeout(() => {
          if (args?.xhr) {
            // Abort upload on user request
            button.addEventListener('click',
                (e) => args?.xhr.abort(), {once: true});
            button.style.display = 'block';
            progress.style.display = 'block';
          }
  
          layer.querySelector('i').className =
              args?.icon || 'fas fa-cog fa-spin fa-lg';
          layer.querySelector('span').innerHTML =
              args?.text || `<?=_("Please wait")?>...`;

          layer.classList.add('show');
        }, args?.delay !== undefined ? args.delay : 500);
      } else {
        layer.classList.remove('show');
        button.style.display = 'none';
        progress.style.display = 'none';
        progress.style.backgroundColor = '#ea6966';

        layer.removeAttribute('data-timeoutid');
      }
    }
  }

  // METHOD openUserview()
  static openUserview({about, picture, title}) {
    H.loadPopup('userView', {
      open: false,
      cb: ($p) => {
        const p = $p[0];
        const div = p.querySelector('.user-picture');
        const aboutEl = p.querySelector('.about');

        p.querySelector('.modal-title span').innerText = title;
        p.querySelector('.name dd').innerText = title;

        div.innerHTML = '';
        div.style.display = 'none';

        if (picture) {
          const img = this.createElement('img', {src: picture});

          img.addEventListener('error', (e) => div.style.display = 'none');
          div.appendChild(img);

          div.style.display = 'block';
        }

        if (about) {
          aboutEl.querySelector('dd').innerHTML = H.nl2br(about);
          aboutEl.style.display = 'block';
        } else {
          aboutEl.style.display = "none";
        }

        H.openModal({item: p});
      }
    });
  }
  
  // METHOD insertBefore()
  static insertBefore(n1, n2) {
    return n1.parentNode.insertBefore(n2, n1.nextSibling);
  }

  // METHOD insertAfter()
  static insertAfter(n1, n2) {
    return n2.parentNode.insertBefore(n1, n2.nextSibling);
  }

  // METHOD openPopupLayer()
  static openPopupLayer(cb) {
    const layer = this.createElement('div', {
        id: 'popup-layer',
        className: 'layer',
    });
  
    // EVENT "click" on layer
    layer.addEventListener('click', (e) => {
      // Remove the layer
      e.target.remove();
  
      if (cb) {
        cb(e);
      }
    });

    document.body.appendChild(layer);

    layer.style.display = 'block';
  }
  
  // METHOD openConfirmPopup()
  static openConfirmPopup(args) {
    const popup = document.getElementById('confirmPopup');
  
    S.set('confirmPopup', {
      cb_ok: args.cb_ok,
      cb_close: () => {
        if (args.cb_close) {
          args.cb_close();
        }
        S.unset('confirmPopup');
      },
    });

    popup.querySelector('.modal-title').innerHTML = `<i class="fas fa-${args.icon} fa-fw"></i> ${args.title || `<?=_("Confirmation")?>`}`;
    popup.querySelector('.modal-body').innerHTML = args.content;

    popup.dataset.popuptype = args.type;

    this.openModal ({item: popup});
  }
  
  // METHOD openConfirmPopover()
  static openConfirmPopover(args) {
    const scroll = Boolean(args.scrollIntoView);
    let closeVKB = false;
    let btn;
    let buttons;

    this.openPopupLayer((e) => {
      const bp = bootstrap.Popover.getInstance(args.item[0]);

      if (bp) {
        if (args.cb_close) {
          args.cb_close(bp.tip.dataset.btnclicked);
        }

        if (document.querySelector('.popover.show')) {
          bp.hide();
          setTimeout(() => {
            if (closeVKB && S.get('vkbData')) {
              H.fixVKBScrollStop();
            }
            bp.dispose();
          }, 250);
        }
      }
    }, false);
    
    switch (args.type) {
      case 'info':
        btn = {primary: 'save'};
        buttons = '';
        break;
      case 'update':
        btn = {primary: 'save', secondary: 'close'};
        buttons = `<button type="button" class="btn btn-xs btn-primary"><?=_("Save")?></button> <button type="button" class="btn btn-xs btn-secondary"><?=_("Close")?></button>`;
        break;
      case 'custom':
        btn = {primary: 'save'};
        buttons = `<button type="button" style="display:none" class="btn btn-xs btn-primary"></button>`;

        if (args.html_header) {
          args.content = args.html_header + args.content;
        } else if (args.html_footer) {
          args.content += args.html_footer;
        }
        break;
      default:
        btn = {primary: 'yes', secondary: 'no'};
        buttons = `<button type="button" class="btn btn-xs btn-primary"><?=_("Yes")?></button> <button type="button" class="btn btn-xs btn-secondary"><?=_("No")?></button>`;
    }
  
    const bp = new bootstrap.Popover(args.item[0], {
      html: true,
      sanitize: false,
      title: args.title,
      customClass: args.customClass || '',
      placement: args.placement || 'left',
      boundary: 'window',
      content: `<p>${args.content}</p>${buttons}`,
    });

    bp.show();

    const input = this.getFirstInputFields(bp.tip);
    // If popover contains input fields
    if (input) {
      // Trick for virtual keyboard
      if (!H.haveMouse() && !S.get('vkbData')) {
        closeVKB = H.fixVKBScrollStart();
      // If no virtual keyboard, focus on the first input
      } else {
        input.focus();
      }
    }

    // EVENT "click" on popover buttons
    const _eventC = (e) => {
      if (e.target.classList.contains('btn-primary')) {
        bp.tip.dataset.btnclicked = btn.primary;
        if (args.cb_ok) {
          args.cb_ok($(bp.tip));
        }
      } else {
        bp.tip.dataset.btnclicked = btn.secondary;
      }

      if (!args.noclosure) {
        document.getElementById("popup-layer").click ();
      }
    };

    const body = bp.tip.querySelector('.popover-body');

    body.classList.add('justify');

    body.querySelectorAll('button:not(.close)')
        .forEach((el) => el.addEventListener('click', _eventC));

    // Scroll to the popover point
    if (scroll) {
      args.item[0].scrollIntoView(false);
    }

    if (args.cb_after) {
      args.cb_after($(bp.tip));
    }

    if (scroll) {
      window.dispatchEvent(new Event('resize'));
    }
  }

  // METHOD setViewToElement ()
  static setViewToElement ($el)
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
  static resizeModal (modal, w)
  {
    const md = modal.querySelector (".modal-dialog"),
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
  
  // METHOD openModal()
  static openModal(args) {
    const m = args.item;

    // Modals with transition effect
    if (!args.noeffect) {
      m.classList.add('fade');
    }

    m.removeAttribute('data-customwidth');
    m.style.top = 0;
    m.style.left = 0;

    m.querySelector('.modal-content').classList.add('shadow-lg');

    if (args.customClass) {
      m.classList.add(args.customClass);
    }

    bootstrap.Modal.getOrCreateInstance(m, {backdrop: true}).show();

    if (args.width) {
      this.resizeModal (m, args.width);
    } else {
      const md = m.querySelector('.modal-dialog');

      md.style.width = '';
      md.style.minWidth = '';
      md.style.maxWidth = '';
    }
  }

  // METHOD loadPopup ()
  static async loadPopup (type, args = {open: true})
  {
    const id = `${args.template||type}Popup`,
          popup = document.getElementById (id);

    // LOCAL FUNCTION __exec ()
    const __exec = ($p)=>
      {
        const p = $p[0];

        H.cleanPopupDataAttr (p);

        if (args.cb)
          args.cb ($p);

        if (args.settings)
          $p[type]("setSettings", args.settings);

        if (args.open)
          H.openModal ({
            item: p,
            noeffect: Boolean(args.noeffect),
          });
      };

    if (args.open === undefined)
      args.open = true;

    if (popup)
      __exec ($(popup));
    else
    {
      const r = await fetch (`/ui/${args.template||type}.php`);
      if (r && r.ok)
      {
        $("body").prepend (await r.text ());

        const $p = $(`#${id}`);

        if ($p[type] !== undefined)
          $p[type](args.settings||{});

        if (args.init)
          args.init ($p);

        __exec ($p);
      }
      else {
        this.manageUnknownError ();
      }
    }
  }
  
  // METHOD infoPopup()
  static infoPopup(msg, notheme) {
    const p = document.getElementById('infoPopup');
  
    if (notheme) {
      p.classList.add('no-theme');
    } else {
      this.cleanPopupDataAttr(p);
    }
      
    p.querySelector('.modal-dialog').classList.add('modal-sm');
  
    p.querySelector('.modal-body').innerHTML = msg;
  
    p.querySelector('.modal-title').innerHTML = `<i class="fas fa-bullhorn"></i> <?=_("Information")?>`;
  
    this.openModal({item: p});
  }
  
  // METHOD raiseError ()
  static raiseError (error_cb, msg)
  {
    if (error_cb)
      error_cb ();
  
    this.displayMsg ({
      title: `<?=_("System")?>`,
      type: msg?"warning" : "danger",
      msg: msg?msg:`<?=_("System error. Please report it to the administrator")?>`
    });
  }
  
  // METHOD displayMsg ()
  static displayMsg (args)
  {
    if (S.get ("block-msg"))
      return;

    // If a TinyMCE plugin is running, display message using the editor window
    // manager.
    if ($(".tox-dialog").is(":visible"))
      return tinymce.activeEditor.windowManager.alert (args.msg);

    const ctn = document.getElementById ("msg-container"),
          t = args.type;

    // Blurs
    setTimeout (()=>
    {
      const $ef = $("#postitUpdatePopupBody_ifr");

      // TinyEditor
      if ($ef.is (":visible"))
        $ef.blur ();

      // Forms
      document.querySelectorAll("input:focus,textarea:focus")
        .forEach (el=> el.blur ());

    }, 150);

    // Close opened alert with the same content
    ctn.querySelectorAll(".toast").forEach (el=>
      el.querySelector(".toast-body").innerHTML == args.msg &&
        bootstrap.Toast.getInstance(el).hide ());

    const msg = $(`<div class="toast align-items-center border-0 ${(t=="danger"||t=="success")?"text-white":""} bg-${t} bg-gradient" role="alert" aria-live="assertive" aria-atomic="true"><div class="d-flex"><button type="button" class="btn-close m-auto" data-bs-dismiss="toast"></button><div class="toast-body">${args.msg}</div></div></div>`)[0];

    ctn.insertBefore (msg, ctn.firstChild);

    new bootstrap.Toast (
          msg, {delay: (t=="danger"||t=="warning")?5000:3000}).show ();
  }

  // METHOD fixHeight()
  static fixHeight() {
    const menuStyle = document.querySelector('.navbar-collapse').style;
    const mbBtn = document.querySelector('.navbar-toggler i');
  
    // If menu is in min mode, limit menus height
    if (mbBtn.offsetWidth > 0 && mbBtn.offsetHeight > 0) {
      menuStyle.overflowY = 'auto';
      menuStyle.maxHeight = `${window.innerHeight - 56}px`;
    } else {
      menuStyle.overflowY = '';
      menuStyle.maxHeight = '';
    }

    document.querySelector('html').style.overflow = 'hidden';

    S.getCurrent('walls')[0].style.height =
      `${window.innerHeight -
         document.querySelector(".nav-tabs.walls").offsetHeight}px`;
  }

  // METHOD setColorpickerColor ()
  static setColorpickerColor ($cp, c, apply = true)
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
  static getProgressbarColor (v)
  {
    return (v < 30) ? "#f60104" :
           (v < 50) ? "#f57f00" :
           (v < 75) ? "#f5c900" :
           (v < 85) ? "#f0f700" :
           (v < 95) ? "#84f600" :
           "#26f700";
  }

  // METHOD download()
  static download({
    url, fname, msg = `<?=_("An error occurred while downloading")?>`}) {
    const req = new XMLHttpRequest();
  
    this.loader('show');
  
    req.onreadystatechange = (e) => {
      if (req.readyState === 4) {
        this.loader ('hide');
  
        if (req.status !== 200) {
          this.displayMsg ({
            msg,
            title: `<?=_("Download")?>`,
            type: 'warning',
          });
        }
      }
    };
  
    req.onload = (e) => {
      const blob = req.response;
      const type = req.getResponseHeader('Content-Type');

      if (type === 404) {
        this.displayMsg ({
          title: `<?=_("Download")?>`,
          type: 'warning',
          msg: `<?=_("The file is no longer available for download")?>`
        });
      } else {
        if (window.navigator.msSaveOrOpenBlob) {
          window.navigator.msSaveOrOpenBlob (new Blob([blob], {type}), fname);
        } else {
          this.createElement('a', {
            href: window.URL.createObjectURL(blob),
            download: fname,
          }).click();
        }
      }
    };

    req.open('GET', url);
    req.responseType = 'blob';
    req.send();
  }
  
  // METHOD manageUnknownError ()
  static manageUnknownError (d = {}, error_cb)
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
  
  // METHOD request_ws()
  static request_ws (method, route, args, success_cb, error_cb) {
    this.loader('show');
  
    //console.log (`WS: ${method} ${route}`);
  
    WS.send ({
      method,
      route,
      data: args ? encodeURI(JSON.stringify(args)) : null,
    },
    (d) => {
      this.loader('hide');
      if (d.error) {
        this.manageUnknownError(d, error_cb);
      } else if (success_cb) {
        success_cb(d);
      } else if (d.error_msg) {
        H.displayMsg({
          title: `<?=_("Warning")?>`,
          type: 'warning',
          msg: d.error_msg,
        });
      }
    },
    () => {
      this.loader('hide');
      error_cb && error_cb();
    });
  }
  
  // METHOD fetchTimeout()
  static fetchTimeout(url, ms, {signal, ...options} = {}) {
    const controller = new AbortController();
    const promise = fetch(url, {signal: controller.signal, ...options});

    if (signal) {
      signal.addEventListener('abort', () => controller.abort());
    }

    const timeout = setTimeout(() => controller.abort(), ms);

    return promise.finally(() => clearTimeout(timeout));
  }

  // METHOD fetch ()
  // TODO https://developer.mozilla.org/en-US/docs/Web/API/AbortSignal/timeout
  static async fetch(method, service, args, success_cb, error_cb)
  {
    let ret;
    this.loader('show');

    //console.log (`FETCH: ${method} ${service}`);

    try {
      const controller = new AbortController();
      const r = await this.fetchTimeout(
        `/api/${service}`, 5000, {
          signal: controller.signal,
          method: method,
          cache: 'no-cache',
          headers: {'Content-Type': 'application/json;charset=utf-8'},
          body: args ? encodeURI (JSON.stringify (args)) : null
        });

      if (r.ok) {
        ret = await r.json();

        if (!ret || ret.error) {
          if (error_cb) {
            this.loader('hide');
            error_cb(ret);
          } else {
            this.manageUnknownError(ret);
          }
        } else {
          this.loader('hide');
          success_cb && success_cb(ret);
        }
      } else {
        this.manageUnknownError();
      }
    } catch(e) {
      if (e instanceof DOMException || e instanceof TypeError) {
        H.displayNetworkErrorMsg();
      } else {
        this.loader('hide');
        throw e;
      }
    }

    return ret;
  }

  // METHOD fetchUpload ()
  // Only used for file upload
  //TODO Use fetch() when upload progress will be available!
  static fetchUpload (service, args, success_cb, error_cb)
  {
    //console.log (`AJAX: PUT ${service}`);
  
    const pbar = document.querySelector ("#loader .progress"),
          xhr = $.ajax (
            {
              // Progressbar for file upload
              xhr: ()=>
              {
                const xhr = new window.XMLHttpRequest ();
      
                // LOCAL FUNCTION __progress ()
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
            .always (()=> this.loader ("hide", {force: true}));

    this.loader ("show", {force: true, xhr});
  }
  
  // METHOD checkUploadFileSize ()
  static checkUploadFileSize (args)
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
  static getUploadedFiles (files, type, success_cb, error_cb, cb_msg)
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
  static waitForDOMUpdate (cb)
  {
    window.requestAnimationFrame (() => window.requestAnimationFrame (cb));
  }
  
  // METHOD supportUs ()
  static supportUs ()
  {
    <?php if (WPT_SUPPORT_CAMPAIGN) {?>

    if (wpt_userData.settings.theme &&
        !ST.noDisplay ("support-msg") &&
        !document.querySelector(".modal.show,.popover.show"))
    {
      const popup = document.getElementById ("infoPopup");

      popup.querySelector(".modal-dialog").classList.remove ("modal-sm");
      popup.querySelector(".modal-title").innerHTML = `<i class="fas fa-heart fa-lg fa-fw"></i> <?=_("Support us")?>`;

      popup.querySelector(".modal-body").classList.add ("justify");
      popup.querySelector(".modal-body").innerHTML = `<?=_("As you probably already know, wopits is a free tool, and your data is not shared with any third party.<div class='mt-2 mb-2'>To offer you this service, we nevertheless have fixed costs for hosting and domain name.</div><div>This is why we invite you to participate in the project by making a PayPal donation:</div><div class='mt-3 mb-3 text-center'>%s1</div><div>It will not change the way you access wopits or the features available, but it would be nice to help and it will encourage us to maintain the project and the service for a few more years&nbsp;:-)</div>")?></div><div class='mt-3'><button type='button' class='btn btn-sm btn-primary support'><?=_("I get it !")?></button></div>`.replace("%s1", `<a target="_blank" href="https://www.paypal.com/donate/?hosted_button_id=N3FW372J2NG4E" class="btn btn-secondary btn-xs"><i class="fas fa-heart fa-fw"></i> <?=_("Yes, I support wopits!")?></a>`);

      // EVENT "click" on "I get it!" button"
      popup.querySelector("button.support").addEventListener ("click", (e)=>
        {
          ST.noDisplay ("support-msg", true);
          bootstrap.Modal.getInstance(popup).hide ();
        });

        this.openModal ({item: popup, noeffect: true});
    }

    <?php } ?>
  }

  // METHOD checkForAppUpgrade ()
  static async checkForAppUpgrade (version)
  {
    const html = $("html")[0],
          $popup = $("#infoPopup"),
          officialVersion = (version) ? version : html.dataset.version;

    if (!String(officialVersion).match(/^\d+$/))
    {
      const popup = $popup[0];
      const userVersion = ST.get('version');

      if (userVersion != officialVersion)
      {
        ST.set('version', officialVersion);
        $("#settingsPopup").settings ("set", {version: officialVersion});
  
        if (userVersion)
        {
          // Close current popups if any
          (S.get("mstack")||[])
             .forEach (el=> bootstrap.Modal.getInstance(el).hide ());
  
          this.cleanPopupDataAttr (popup);
  
          $popup.find(".modal-body").html (`<?=_("A new release of wopits is available.")?><br><?=_("The application will be upgraded from v%s1 to v%s2.")?>`.replace("%s1", `<b>${userVersion}</b>`).replace("%s2", `<b>${officialVersion}</b>`));
          $popup.find(".modal-title").html (`<i class="fas fa-fw fa-glass-cheers"></i> <?=_("New release")?>`);
  
          popup.dataset.popuptype = "app-upgrade";
          this.openModal ({item: popup});
  
          return true;
        }
      }
      else if (html.dataset.upgradedone)
      {
        html.removeAttribute ("data-upgradedone");
  
        $popup.find(".modal-dialog").removeClass ("modal-sm");
        $popup.find(".modal-title").html (`<i class="fas fa-glass-cheers"></i> <?=_("Upgrade done")?>`);

        const r = await fetch ("/whats_new/latest.php");
        if (r && r.ok)
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

          popup.querySelector(".modal-body").innerHTML = `${d}<div class="mt-2"><button type="button" class="btn btn-secondary btn-xs"><i class="fas fa-scroll"></i> <?=_("See more...")?></button></div>`;
          popup.querySelector(".modal-body button")
            .addEventListener ("click", (e)=>
            {
              bootstrap.Modal.getInstance(popup).hide ();
              H.loadPopup ("userGuide");
            });

          this.openModal ({item: popup, noeffect: true});
        } else {
          this.manageUnknownError ();
        }
      }
      else
        setTimeout (()=> this.supportUs (), 1000);
    }
  }
  
  // METHOD navigatorIsEdge ()
  static navigatorIsEdge ()
  {
    return navigator.userAgent.match (/edg/i);
  }

  // METHOD isVisible()
  static isVisible(el) {
    return window.getComputedStyle(el, null)
               .getPropertyValue('display') !== 'none';
  }

  // METHOD fixVKBScrollStart()
  static fixVKBScrollStart() {
    const walls = document.getElementById('walls');

    if (!walls) return;

    const body = document.body;

    S.set('vkbData', {
      bodyComputedStyles: window.getComputedStyle(body),
    });

    walls.style.width = `${window.innerWidth}px`;

    body.style.overflow = 'hidden';
    body.style.position = 'fixed';
    body.style.top = `${body.scrollTop * -1}px`;
    body.style.left = `${body.scrollLeft * -1}px`;

    return true;
  }
  
  // METHOD fixVKBScrollStop()
  static fixVKBScrollStop() {
    const {bodyComputedStyles} = S.get('vkbData');
    const walls = document.getElementById('walls');
  
    document.body.style = bodyComputedStyles;
    walls.style.width = 'auto';

    S.unset('vkbData');
  }
}

// GLOBAL VARS
const H = WHelper;
const S = new WSharer()
const ST = new WStorage()
const WS = new WSocket();
