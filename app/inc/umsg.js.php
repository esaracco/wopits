<?php
/**
  Javascript plugin - User's messages

  Scope: Wall
  Element: .umsg
  Description: Manage user's messages
*/

  require_once(__DIR__.'/../prepend.php');

  $Plugin = new Wopits\jQueryPlugin('umsg');
  echo $Plugin->getHeader();

?>

/////////////////////////// PUBLIC METHODS ////////////////////////////

  Plugin.prototype = {
    // METHOD init()
    init() {
      const $umsg = this.element;

      // Init counter
      H.fetch(
        'GET',
        'user/messages',
        null,
        (d) => d.length && $umsg.find('.wpt-badge').show().text(d.length)
      );

      // EVENT "click" on messages count
      $umsg[0].addEventListener('click', (e) => this.open());

      // EVENT "click"
      document.addEventListener('click', (e) => {
        const el = e.target;

        if (!el.matches('.umsg-popover *')) return;

        // EVENT "click" on user messages
        if (el.matches('.msg-body a')) {
          const data = el.dataset;
          // Case "wall" by default
          const infos = {
            type: data.type,
            wallId: Number(data.wallid),
          };

          document.getElementById('popup-layer').click();

          e.stopImmediatePropagation();
          H.preventDefault(e);

          switch (data.type) {
            case 'postit':
            case 'worker':
              infos.postitId = Number(data.postitid);
              break;
            case 'comment':
              infos.postitId = Number(data.postitid);
              infos.commentId = Number(data.commentid);
              break;
          }

          $('<div/>').wall('loadSpecific', infos, true);

        // EVENT "click" on message "delete" button
        } else if (el.matches('.msg-item .close *,.msg-item .close')) {
          const item = el.closest('.msg-item');

          e.stopImmediatePropagation();
          H.preventDefault(e);

          H.fetch(
            'DELETE',
            'user/messages',
            {id: item.getAttribute('data-id')},
            (d) => this.removeMsg(item)
          );
        }
      });
    },

    // METHOD addMsg()
    addMsg(args) {
      // Refresh user's walls browser cache before adding message.
      $('<div/>').wall('refreshUserWallsData', () => {
        const $badge = this.element.find('.wpt-badge');

        $badge.show().text(parseInt($badge.text()) + 1);

        // Refresh popover content if currently opened.
        if (document.querySelector('.msg-popover')) {
          this.open(true);
        }

        // Refresh wall if needed and if opened.
        if (args && args.wallId) {
          const $wall = $(`.wall[data-id="wall-${args.wallId}"]`);

          if ($wall.length) {
            $wall.wall('refresh');
          }
        }
      });
    },

    // METHOD removeMsg()
    removeMsg(item) {
      const $badge = this.element.find('.wpt-badge');
      const count = parseInt($badge.text()) - 1;

      item.remove();
      $badge.text(count);

      if (!count) {
        $badge.hide();
        document.getElementById('popup-layer').click();
      }
    },

    // METHOD open()
    open(refresh) {
      H.fetch(
        'GET',
        'user/messages',
        null,
        (d) => {
          let body = '';
          d.forEach(({content, creationdate, id, title}) => {
            body += `<div class="msg-item" data-id="${id}"><div class="msg-title">${title}<button type="button" class="close" title="<?=_("Delete this message")?>"><span><i class="fas fa-trash fa-xs"></i></span></button></div><div class="msg-date">${H.getUserDate(creationdate, null, "Y-MM-DD H:mm")}</div><div class="msg-body">${content.replace(/\n+/g, "<br>")}</div></div>`;
          });

          if (refresh) {
            $('.umsg-popover .popover-body').html(body);
          } else {
            H.openConfirmPopover({
              type: 'info',
              customClass: 'msg-popover umsg-popover',
              placement: 'bottom',
              item: this.element[0].querySelector('.wpt-badge'),
              title: `<i class="fas fa-envelope fa-fw"></i> <?=_("Messages")?>`,
              content: body,
              cb_after: ($p) => {
                const wH = window.innerHeight - 95;
                const bH = $p.find('.popover-body').height();

                if (bH > wH) {
                  $p.find('.popover-body').css('height', `${wH}px`);
                }
              }
            });
          }
        });
    }
  };

/////////////////////////// AT LOAD INIT //////////////////////////////

  document.addEventListener('DOMContentLoaded', () => {
    if (!H.isLoginPage()) {
      $('#umsg').umsg();
    }
  });

<?php echo $Plugin->getFooter()?>
