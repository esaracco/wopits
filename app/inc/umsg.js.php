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

      $umsg.on ("click", ()=> plugin.open ());

      $(document).on("click", ".msg-body a", function (e)
        {
          const wallId = this.dataset.wallid;
          let data;

          $("#popup-layer").click ();

          e.preventDefault ();

          switch (this.dataset.type)
          {
            case "wall":
              data = {wallId};
              break;

            case "postit":
              data = {
                wallId,
                postitId: this.dataset.postitid
              };
              break;

            case "comment":
              data = {
                wallId,
                postitId: this.dataset.postitid,
                commentId: this.dataset.commentid
              };
              break;
          }

          $("<div/>").wall ("loadSpecific", data, true);
        });

      $(document).on("click", ".umsg-popover .msg-item .close", function (e)
        {
          const $item = $(this).closest (".msg-item");

          e.preventDefault ();
          e.stopImmediatePropagation ();

          H.fetch (
            "DELETE",
            "user/messages",
            {id:$item.attr("data-id")},
            (d)=> plugin.removeMsg ($item)
          );
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
    removeMsg ($item)
    {
      const $badge = this.element.find(".wpt-badge"),
            count = parseInt($badge.text()) - 1;

      $item.find("[data-toggle='tooltip']").tooltip ("dispose");
      $item.remove ();

      $badge.text (count);

      if (!count)
      {
        $badge.hide ();
        $("#popup-layer").click ();
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
            content += `<div class="msg-item" data-id="${m.id}"><div class="msg-title">${m.title}<button type="button" class="close" data-toggle="tooltip" title="<?=_("Delete this message")?>"><span>&times;</span></button></div><div class="msg-date">${H.getUserDate(m.creationdate, null, "Y-MM-DD H:mm")}</div><div class="msg-body">${m.content.replace(/\n\n|\n/g, "<br>")}</div></div>`;
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
                $p.find(".popover-body").css ("height", wH+"px");
            }
          });
        }
      );
    }
  };

/////////////////////////// AT LOAD INIT //////////////////////////////

  $(function ()
    {
      if (!H.isLoginPage ())
        setTimeout (()=> $("#umsg").umsg (), 0);
    });

<?php echo $Plugin->getFooter ()?>
