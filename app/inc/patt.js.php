<?php
/**
  Javascript plugin - Notes attachments

  Scope: Note
  Element: .patt
  Description: Manage notes attachments
*/

  require_once (__DIR__.'/../prepend.php');

  $Plugin = new Wopits\jQueryPlugin ('patt');
  echo $Plugin->getHeader ();

?>

  let $_mainPopup,
      $_editPopup;

  /////////////////////////// PUBLIC METHODS ////////////////////////////

  Plugin.prototype =
  {
    // METHOD init ()
    init ()
    {
      const settings = this.settings;

      $(`<i data-action="attachments" class="fas fa-paperclip"></i><span ${settings.count?"":`style="display:none"`} class="wpt-badge">${settings.count}</span></div>`).appendTo (this.element);

      return this;
    },

    // METHOD getIds ()
    getIds ()
    {
      const ps = this.settings.postitPlugin.settings,
            {wallId, cellId} = ps;

      return {wallId, cellId, postitId: ps.id}
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
            li = $_mainPopup[0].querySelector (`li[data-id="${id}"]`);

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
            const next = li.nextSibling;

            if (next && next.classList.contains ("collapse"))
              next.remove ();

            li.remove ();

            this.decCount ();

            if (!$_mainPopup[0].querySelector ("li"))
              $_mainPopup.find("ul.list-group").html (
                `<?=_("This note has no attached files.")?>`);
          }
        }
      );
    },

    // METHOD getTemplate ()
    getTemplate (item, noWriteAccess)
    {
      const tz = wpt_userData.settings.timezone,
            d = `<button type="button" data-action="delete"><i class="fas fa-trash fa-xs fa-fw"></i></button>`,
            c = (item.ownerid!==undefined && item.ownerid!=wpt_userData.id) ?
                  `<span class="ownername"><i class="far fa-user fa-xs"></i>${item.ownername||`<s><?=_("deleted")?></s>`}</span>` : "";

      return `<li data-target="#file${item.id}" data-toggle="collapse" data-id="${item.id}" data-url="${item.link}" data-icon="${item.icon}" data-fname="${H.htmlEscape(item.name)}" data-description="${H.htmlEscape(item.description||"")}" data-title="${H.htmlEscape(item.title||"")}" class="list-group-item"><div><i class="fa fa-lg ${item.icon} fa-fw"></i> <span>${item.title||item.name}</span> <div class="item-infos"><span class="creationdate">${H.getUserDate (item.creationdate)}</span><span class="file-size">${H.getHumanSize(item.size)}</span>${c}</div><div class="right-icons"><button type="button" data-action="download"><i class="fas fa-download fa-xs fa-fw"></i></button>${noWriteAccess?"":d}</div></li><div id="file${item.id}" class="collapse list-group-item" data-parent="#pa-accordion"></div>`;
    },

    // METHOD upload ()
    upload ()
    {
      document.querySelector(".upload.postit-attachment").click ();
    },

    // METHOD setCount ()
    setCount (count)
    {
      const el = this.element[0].querySelector ("span");

      el.style.display = count ? "inline-block": "none";
      el.innerText = count;
    },

    // METHOD getCount ()
    getCount ()
    {
      return parseInt (this.element[0].querySelector("span").innerText);
    },

    // METHOD incCount ()
    incCount ()
    {
      this.setCount (this.getCount() + 1);
    },

    // METHOD decCount ()
    decCount ()
    {
      this.setCount (this.getCount() - 1);
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
            body = `<?=_("This note has no attached files.")?>`;
          else
            d.files.forEach (a => body += this.getTemplate (a, !writeAccess));

          if (writeAccess)
            $p.find(".btn-primary").show ();
          else
            $p.find(".btn-primary").hide ();

          $p.find(".modal-body ul").html (body);

          $p[0].dataset.noclosure = true;
          H.openModal ($p);
        }
      });
    },

    // METHOD open ()
    open (refresh)
    {
      this.settings.postitPlugin.edit ({}, ()=> this.display ());
    },
  };

  /////////////////////////// AT LOAD INIT //////////////////////////////

  if (!H.isLoginPage ())
    $(function()
      {
        setTimeout (()=>{

        // EVENT click on attachment count
        $(document).on("click", ".patt", function (e)
          {
            if (H.checkAccess ("<?=WPT_WRIGHTS_RW?>"))
              $(this).patt ("open");
            else
            {
              $(this).closest(".postit").postit ("setCurrent");
              $(this).patt ("display");
            }
          });

        // EVENT click on attachment line buttons
        $(document).on("click", "#postitAttachmentsPopup .modal-body li button",
          function (e)
          {
            const action = this.dataset.action,
                  item = this.closest ("li");

            e.stopImmediatePropagation ();

            if (action == "delete")
            {
              const id = item.dataset.id;

              item.classList.add ("active");

              H.openConfirmPopover ({
                item: $(this),
                placement: "left",
                title: `<i class="fas fa-trash fa-fw"></i> <?=_("Delete")?>`,
                content: `<?=_("Delete this file?")?>`,
                cb_close: ()=>
                  {
                    const el = document.querySelector (
                                 ".modal li.list-group-item.active");

                    if (el && el.getAttribute ("aria-expanded") != "true")
                      el.classList.remove ("active");
                  },
                cb_ok: ()=>
                  S.getCurrent("postit").find(".patt").patt ("delete", id)
              });
            }
            else
              H.download (item.dataset);
          });

        // EVENT click on attachment thumbnail to preview
        $(document).on("click", "#postitAttachmentsPopup .edit-popup img",
          function (e)
          {
            $("body")
              .append(`<div id="img-viewer"><div class="close"><i class="fas fa-times-circle fa-2x"></i></div><img src="${this.getAttribute("src")}"></div>`)
              .find(".close")
              .on("click", ()=> $("#popup-layer").click());

            H.openPopupLayer (
              ()=> document.getElementById("img-viewer").remove());
          });

        // EVENT click on edit popup "Save" button
        $(document).on("click",
                       "#postitAttachmentsPopup .edit-popup .btn-primary",
          function (e)
          {
            const popup = $_editPopup[0];

            e.stopImmediatePropagation ();

            S.getCurrent("postit").find(".patt").patt ("update", {
              id: popup.dataset.id,
              title: H.noHTML (popup.querySelector("input").value),
              description: H.noHTML (popup.querySelector("textarea").value)
            });
          });

        // EVENT hidden.bs.collapse attachment row
        $(document).on("hidden.bs.collapse",
                       "#postitAttachmentsPopup .list-group-item.collapse",
          function (e)
          {
            const li = this.previousSibling;

            li.classList.remove ("no-bottom-radius");
            li.classList.remove ("active");
          });

        // EVENT show.bs.collapse attachment row
        $(document).on("show.bs.collapse",
                       "#postitAttachmentsPopup .list-group-item.collapse",
          function (e)
          {
            const li = this.previousSibling,
                  popup = $_editPopup[0],
                  liActive = $_mainPopup[0].querySelector ("li.active"),
                  fileVal = li.dataset.fname,
                  titleVal = li.dataset.title,
                  descVal = li.dataset.description,
                  img = popup.querySelector (".img"),
                  isImg = fileVal.match (/\.(jpe?g|gif|png)$/);

            li.classList.add ("no-bottom-radius");

            liActive && liActive.classList.remove ("active");
            li.classList.add ("active");

            popup.dataset.id = li.dataset.id;

            popup.querySelector(".no-details").style.display = "none";
            popup.querySelector(".title").style.display = "block";
            popup.querySelector(".description").style.display = "block";
            img.querySelector("img").setAttribute ("src", "");
            img.style.display = "none";

            popup.querySelector(".file").innerText = fileVal;

            if (H.checkAccess ("<?=WPT_WRIGHTS_ADMIN?>"))
            {
              popup.querySelector(".btn-primary").style.display = "block";
              popup.querySelectorAll(".ro").forEach (el =>
                el.style.display = "none");
              popup.querySelectorAll(".adm").forEach (el =>
                el.style.display = "block");

              popup.querySelector(".title input").value = titleVal;
              popup.querySelector(".description textarea").value = descVal;

              H.setAutofocus ($(popup));
            }
            else
            {
              popup.querySelector(".btn-primary").style.display = "none";
              popup.querySelectorAll(".ro").forEach (el =>
                el.style.display = "block");
              popup.querySelectorAll(".adm").forEach (el =>
                el.style.display = "none");

              if (!isImg && !titleVal && !descVal)
                popup.querySelector(".no-details").style.display = "block";

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

            $(this.appendChild (popup)).show ("fade");
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
                        ".list-group li[data-fname='"+
                          H.htmlEscape(file.name)+"']").length)
                    return H.displayMsg ({
                      type: "warning",
                      msg: `<?=_("The file is already linked to the note.")?>`
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
                      const pa = plugin.getPlugin ("attachments"),
                            $body = $_mainPopup.find("ul.list-group");

                      $_mainPopup.find(".modal-body").scrollTop (0);
                      $_mainPopup.find("div.collapse.show").collapse ("hide");

                      if (d.error_msg)
                        return H.displayMsg ({
                          type: "warning",
                          msg: d.error_msg
                        });

                      if (!$body.find("li").length)
                        $body.html ("");

                      $body.prepend (pa.getTemplate (d));
                      pa.incCount ();

                      H.waitForDOMUpdate (()=>$body.find("li:eq(0)").click ());
                    });
                }
              });
            }
          }).appendTo ("body");

        }, 0);

      });

<?php echo $Plugin->getFooter ()?>
