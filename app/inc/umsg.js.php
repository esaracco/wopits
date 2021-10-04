<?php
/**
  Javascript plugin - User's messages

  Scope: Wall
  Element: .umsg
  Description: Manage user's messages
*/

  require_once (__DIR__.'/../prepend.php');

  $Plugin = new Wopits\jQueryPlugin ('umsg');
  echo $Plugin->getHeader ();

?>

/////////////////////////// PUBLIC METHODS ////////////////////////////

  Plugin.prototype =
  {
    // METHOD init ()
    init ()
    {
      const plugin = this,
            $umsg = plugin.element;

      // Init counter
      H.fetch (
        "GET",
        "user/messages",
        null,
        (d)=> d.length && $umsg.find(".wpt-badge").show().text (d.length)
      );

      // EVENT "click" on messages count
      $umsg[0].addEventListener ("click", (e)=> plugin.open ());

      // EVENT "click"
      document.body.addEventListener ("click", (e)=>
        {
          const el = e.target;

          if (!el.matches (".umsg-popover *"))
            return;

          // EVENT "click" on user messages
          if (el.matches (".msg-body a"))
          {
            const data = el.dataset;
            let infos;

            document.getElementById("popup-layer").click ();

            e.stopImmediatePropagation ();
            e.preventDefault ();

            switch (data.type)
            {
              case "wall":
                infos = {
                  wallId: data.wallid
                };
                break;

              case "postit":
              case "worker":
                infos = {
                  wallId: data.wallid,
                  postitId: data.postitid
                };
                break;

              case "comment":
                infos = {
                  wallId: data.wallid,
                  postitId: data.postitid,
                  commentId: data.commentid
                };
                break;
            }

            infos.type = data.type;

            $("<div/>").wall ("loadSpecific", infos, true);
          }
          // EVENT "click" on message "delete" button
          else if (el.matches (".msg-item .close *,.msg-item .close"))
          {
            const item = el.closest (".msg-item");

            e.stopImmediatePropagation ();
            e.preventDefault ();

            H.fetch (
              "DELETE",
              "user/messages",
              {id: item.getAttribute("data-id")},
              (d)=> plugin.removeMsg (item)
            );
          }
        });
    },

    // METHOD addMsg ()
    addMsg (args)
    {
      // Refresh user's walls browser cache before adding message.
      $("<div/>").wall ("refreshUserWallsData", ()=>
        {
          const $badge = this.element.find (".wpt-badge");

          $badge.show().text (parseInt($badge.text()) + 1);

          // Refresh popover content if currently opened.
          if (document.querySelector (".msg-popover"))
            this.open (true);

          // Refresh wall if needed and if opened.
          if (args && args.wallId)
          {
            const $wall = $(`.wall[data-id="wall-${args.wallId}"]`);

            if ($wall.length)
              $wall.wall ("refresh");
          }
        });
    },

    // METHOD removeMsg ()
    removeMsg (item)
    {
      const $badge = this.element.find(".wpt-badge"),
            count = parseInt($badge.text()) - 1;

      item.remove ();

      $badge.text (count);

      if (!count)
      {
        $badge.hide ();
        document.getElementById("popup-layer").click ();
      }
    },

    // METHOD open ()
    open (refresh)
    {
      H.fetch (
        "GET",
        "user/messages",
        null,
        (d)=>
        {
          let content = "";

          d.forEach (m=>
          {
            content += `<div class="msg-item" data-id="${m.id}"><div class="msg-title">${m.title}<button type="button" class="close" title="<?=_("Delete this message")?>"><span><i class="fas fa-trash fa-xs"></i></span></button></div><div class="msg-date">${H.getUserDate(m.creationdate, null, "Y-MM-DD H:mm")}</div><div class="msg-body">${m.content.replace(/\n\n|\n/g, "<br>")}</div></div>`;
          });

          if (refresh)
            $(".umsg-popover .popover-body").html (content);
          else
          H.openConfirmPopover ({
            type: "info",
            customClass: "msg-popover umsg-popover",
            placement: "bottom",
            item: this.element.find (".wpt-badge"),
            title: `<i class="fas fa-envelope fa-fw"></i> <?=_("Messages")?>`,
            content: content,
            cb_after:($p)=>
            {
              const wH = window.innerHeight - 95,
                    bH = $p.find(".popover-body").height ();

              if (bH > wH)
                $p.find(".popover-body").css ("height", `${wH}px`);
            }
          });
        }
      );
    }
  };

/////////////////////////// AT LOAD INIT //////////////////////////////

  document.addEventListener ("DOMContentLoaded", ()=>
    {
      if (!H.isLoginPage ())
        setTimeout (()=> $("#umsg").umsg (), 0);
    });

<?php echo $Plugin->getFooter ()?>
