<?php
/**
  Javascript plugin - Notes attachments

  Scope: Note
  Element: .patt
  Description: Manage notes attachments
*/

  require_once (__DIR__.'/../prepend.php');

  $Plugin = new Wopits\jQueryPlugin ('patt', '', 'postitElement');
  echo $Plugin->getHeader ();

?>

  let $_mainPopup,
      $_editPopup;

  /////////////////////////// PUBLIC METHODS ////////////////////////////

  // Inherit from Wpt_postitCountPlugin
  Plugin.prototype = Object.create(Wpt_postitCountPlugin.prototype);
  Object.assign (Plugin.prototype,
  {
    // METHOD init ()
    init ()
    {
      const settings = this.settings;

      if (settings.readonly && !settings.count)
        this.element[0].style.display = "none";

      $(`<i data-action="patt" class="fa-fw fas fa-paperclip"></i><span ${settings.count?"":`style="display:none"`} class="wpt-badge">${settings.count}</span></div>`).appendTo (this.element);

      return this;
    },

    // METHOD update ()
    update (args)
    {
      const {wallId, cellId, postitId} = this.getIds ();

      H.fetch (
        "POST",
        `wall/${wallId}/cell/${cellId}/postit/${postitId}/`+
          `attachment/${args.id}`,
        {title: args.title, description: args.description},
        // success cb
        (d) =>
        {
          if (d.error_msg)
            H.raiseError (null, d.error_msg);
          else
            this.display ();
        }
      );
    },

    // METHOD delete ()
    delete (id)
    {
      const {wallId, cellId, postitId} = this.getIds (),
            li = $_mainPopup[0].querySelector (`.accordion-item[data-id="${id}"]`);

      H.request_ws (
        "DELETE",
        `wall/${wallId}/cell/${cellId}/postit/${postitId}/attachment/${id}`,
        null,
        // success cb
        (d) =>
        {
          if (d.error_msg)
            H.raiseError (null, d.error_msg);
          else
          {
            li.remove ();

            this.decCount ();

            if (!$_mainPopup[0].querySelector (".accordion-item"))
              $_mainPopup.find(".list-group").html (
                `<?=_("The note has no attached file.")?>`);
          }
        }
      );
    },

    // METHOD getTemplate ()
    getTemplate (item, noWriteAccess)
    {
      const tz = wpt_userData.settings.timezone,
            d = `<button type="button" data-action="delete"><i class="fas fa-trash fa-xs fa-fw"></i></button>`,
            owner = (item.ownerid !== undefined &&
                     item.ownerid != wpt_userData.id) ?
                       item.ownername||`<s><?=_("Former user")?></s>` : "";

      return `<div class="accordion-item" data-id="${item.id}" data-url="${item.link}" data-icon="${item.icon}" data-fname="${H.htmlEscape(item.name)}" data-description="${H.htmlEscape(item.description||"")}" data-title="${H.htmlEscape(item.title||"")}" data-creationdate="${H.getUserDate (item.creationdate)}" data-size="${H.getHumanSize(item.size)}" data-owner="${H.htmlEscape(owner)}"><div class="accordion-header" id="hfile${item.id}"><div class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#cfile${item.id}" aria-expanded="false" aria-controls="cfile${item.id}"><i class="fa fa-lg ${item.icon} fa-fw"></i> <span>${item.title||item.name}</div></div><div id="cfile${item.id}" class="accordion-collapse collapse" aria-labelledby="hfile${item.id}" data-bs-parent="#pa-accordion"><div class="accordion-body"></div></div></div></div>`;
    },

    // METHOD upload ()
    upload ()
    {
      document.querySelector(".upload.postit-attachment").click ();
    },

    // METHOD display ()
    display ()
    {
      const {wallId, cellId, postitId} = this.getIds ();

      if (!this.getCount ())
        this._display ();
      else
      H.fetch (
        "GET",
        `wall/${wallId}/cell/${cellId}/postit/${postitId}/attachment`,
        null,
        // success cb
        (d)=> this._display (d));

    },

    // METHOD _display ()
    _display (d)
    {
      H.loadPopup ("postitAttachments", {
        open: false,
        init: ($p)=>
        {
          $_mainPopup = $p;
          $_editPopup = $p.find (".edit-popup");
        },
        cb: ($p)=>
        {
          const writeAccess = !this.settings.readonly;
          let body = "";

          if (!d)
            body = `<?=_("The note has no attached file.")?>`;
          else
            d.files.forEach (a => body += this.getTemplate (a, !writeAccess));

          if (writeAccess)
            $p.find(".btn-primary").show ();
          else
            $p.find(".btn-primary").hide ();

          $p.find(".modal-body .list-group").html (body);

          $p[0].dataset.noclosure = true;
          H.openModal ({item: $p});
        }
      });
    },

    // METHOD open ()
    open (refresh)
    {
      this.postit().edit ({}, ()=> this.display ());
    },
  });

  /////////////////////////// AT LOAD INIT //////////////////////////////

  document.addEventListener ("DOMContentLoaded", ()=>
    {
      if (H.isLoginPage ())
        return;

      setTimeout (()=>
      {
        // EVENT "click"
        document.body.addEventListener ("click", (e)=>
          {
            const el = e.target;

            // EVENT "click" on attachment count
            if (el.matches (".patt,.patt *"))
            {
              const $patt = $((el.tagName == "DIV")?el:el.closest("div"));

              if (H.checkAccess ("<?=WPT_WRIGHTS_RW?>"))
                $patt.patt ("open");
              else
              {
                $patt.closest(".postit").postit ("setCurrent");
                $patt.patt ("display");
              }
            }
            // EVENT "click" on attachment thumbnail to preview
            else if (el.matches ("#postitAttachmentsPopup .edit-popup img"))
            {
              $("body")
                .append(`<div id="img-viewer"><div class="close"><i class="fas fa-times-circle fa-2x"></i></div><img src="${el.getAttribute("src")}"></div>`)
                .find(".close")
                .on("click",
                   ()=> document.getElementById("popup-layer").click());

              H.openPopupLayer (
                ()=> document.getElementById("img-viewer").remove());
            }
            // EVENT "click" on edit attachment buttons
            else if (el.matches("#postitAttachmentsPopup .edit-popup button,"+
                                "#postitAttachmentsPopup .edit-popup button *"))
            {
              const btn = (el.tagName=="BUTTON")?el:el.closest("button"),
                    action = btn.dataset.action,
                    item = btn.closest (".accordion-item");
  
              e.stopImmediatePropagation ();
  
              switch (action)
              {
                // "Delete" button
                case "delete":
                  const id = item.dataset.id;
  
                  item.classList.add ("active");
  
                  H.openConfirmPopover ({
                    item: $(btn),
                    title: `<i class="fas fa-trash fa-fw"></i> <?=_("Delete")?>`,
                    content: `<?=_("Delete the file?")?>`,
                    cb_close: ()=>
                      {
                        const active = document.querySelector (
                                         ".modal .accordion-item.active");
  
                        if (active &&
                            active.getAttribute ("aria-expanded") != "true")
                          active.classList.remove ("active");
                      },
                    cb_ok: ()=>
                      S.getCurrent("postit").find(".patt").patt ("delete", id)
                  });
                  break;
  
                // "Download" button
                case "download":
                  H.download (item.dataset);
                  break;
  
                // "Save" button
                case "save":
                  const popup = $_editPopup[0];
  
                  S.getCurrent("postit").find(".patt").patt ("update", {
                    id: popup.dataset.id,
                    title: H.noHTML (popup.querySelector("input").value),
                    description: H.noHTML(popup.querySelector("textarea").value)
                  });
              }
            }
          });

        // EVENT "hidden.bs.collapse"
        document.body.addEventListener ("hidden.bs.collapse", (e)=>
          {
            const el = e.target;

            // EVENT "hidden.bs.collapse" attachment row
            if (el.matches ("#postitAttachmentsPopup .collapse"))
            {
              const li = el.closest (".accordion-item");

              el.classList.remove ("no-bottom-radius");
              el.classList.remove ("active");
            }
          });

        // EVENT "show.bs.collapse"
        document.body.addEventListener ("show.bs.collapse", (e)=>
          {
            const el = e.target;

            // EVENT "show.bs.collapse" attachment row
            if (el.matches ("#postitAttachmentsPopup .collapse"))
            {
              const li = el.closest (".accordion-item"),
                    body = $(li).find(".accordion-body")[0],
                    popup = $_editPopup[0],
                    liActive = $_mainPopup[0].querySelector ("div.active"),
                    fileVal = li.dataset.fname,
                    fileInfosVal = (li.dataset.owner?li.dataset.owner+", ":"")+
                                   li.dataset.creationdate+", "+
                                   li.dataset.size,
                    titleVal = li.dataset.title,
                    descVal = li.dataset.description,
                    img = popup.querySelector (".img"),
                    isImg = fileVal.match (/\.(jpe?g|gif|png)$/);
  
              li.classList.add ("no-bottom-radius");
  
              liActive && liActive.classList.remove ("active");
              li.classList.add ("active");
  
              popup.dataset.id = li.dataset.id;
  
              popup.querySelector(".title").style.display = "block";
              popup.querySelector(".description").style.display = "block";
              img.querySelector("img").setAttribute ("src", "");
              img.style.display = "none";
  
              popup.querySelector(".file").innerText = fileVal;
              popup.querySelector(".file-infos").innerHTML = fileInfosVal;
  
              if (H.checkAccess ("<?=WPT_WRIGHTS_ADMIN?>"))
              {
                // Display "Save" button
                popup.querySelector(".btn-primary")
                  .style.display = "inline-block";
                // Display "Delete" button
                popup.querySelector(".btn-secondary")
                  .style.display = "inline-block";
                popup.querySelectorAll(".ro").forEach (el =>
                  el.style.display = "none");
                popup.querySelectorAll(".adm").forEach (el =>
                  el.style.display = "block");
  
                popup.querySelector(".title input").value = titleVal;
                popup.querySelector(".description textarea").value = descVal;
  
                H.setAutofocus (popup);
              }
              else
              {
                // Hide "Save" button
                popup.querySelector(".btn-primary").style.display = "none";
                // Hide "Delete" button
                popup.querySelector(".btn-secondary").style.display = "none";
                popup.querySelectorAll(".ro").forEach (el =>
                  el.style.display = "block");
                popup.querySelectorAll(".adm").forEach (el =>
                  el.style.display = "none");
  
                if (titleVal)
                  popup.querySelector(".title .ro").innerText = titleVal;
                else
                  popup.querySelector(".title").style.display = "none";
  
                if (descVal)
                  popup.querySelector(".description .ro")
                    .innerHTML = H.nl2br (descVal);
                else
                  popup.querySelector(".description").style.display = "none";
              }
  
              if (isImg)
              {
                img.querySelector("img").setAttribute ("src", li.dataset.url);
                img.style.display = "block";
              }
  
              $(body.appendChild (popup)).show ("fade");
            }
          });

        // EVENT "shown" on attachment row
        document.body.addEventListener ("shown.bs.collapse", (e)=>
          {
            const el = e.target;

            if (el.matches ("#postitAttachmentsPopup .collapse"))
              H.setAutofocus (e.target);
          });

        // EVENT Attachment upload
        $(`<input type="file" class="upload postit-attachment">`)
          .on("change", function (e)
          {
            const $upload = $(this),
                  plugin = S.getCurrent("postit").postit ("getClass"),
                  settings = plugin.settings;

            if (e.target.files && e.target.files.length)
            {
              H.getUploadedFiles (e.target.files, "all",
                (e, file) =>
                {
                  $upload.val ("");

                  if ($_mainPopup.find(
                        `.list-group .accordion-item`+
                           `[data-fname="${H.htmlEscape(file.name)}"]`).length)
                    return H.displayMsg ({
                      title: `<?=_("Attached files")?>`,
                      type: "warning",
                      msg: `<?=_("The file is already linked to the note")?>`
                    });

                  if (H.checkUploadFileSize ({size: e.total}) &&
                      e.target.result)
                  {
                    H.fetchUpload (
                      `wall/${settings.wallId}/cell/${settings.cellId}/`+
                        `postit/${settings.id}/attachment`,
                    {
                      name: file.name,
                      size: file.size,
                      item_type: file.type,
                      content: e.target.result
                    },
                    // success cb
                    (d) =>
                    {
                      const pa = plugin.getPlugin ("patt"),
                            $body = $_mainPopup.find(".list-group");

                      $_mainPopup.find(".modal-body").scrollTop (0);
                      $_mainPopup.find("div.collapse.show").collapse ("hide");

                      if (d.error_msg)
                        return H.displayMsg ({
                          title: `<?=_("Attached files")?>`,
                          type: "warning",
                          msg: d.error_msg
                        });

                      if (!$body.find(".accordion-item").length)
                        $body.html ("");

                      $body.prepend (pa.getTemplate (d));
                      pa.incCount ();

                      H.waitForDOMUpdate (
                        ()=> $body.find(".accordion-item:eq(0)").click ());
                    });
                }
              });
            }
          }).appendTo ("body");
        }, 0);
      });

<?php echo $Plugin->getFooter ()?>
