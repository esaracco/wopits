<?php
/**
Javascript plugin - User's messages

Scope: Wall
Name: umsg
Description: Manage user's messages
*/

require_once(__DIR__.'/../prepend.php');

?>

(() => {
'use strict';

/////////////////////////////////// PLUGIN ////////////////////////////////////

P.register('umsg', class extends Wpt_pluginBase {
  // METHOD constructor()
  constructor(settings) {
    super(settings);

    this.badgeTag = this.tag.querySelector('.wpt-badge');
    this.popoverBody = null;

    const tag = this.tag;

    // Init counter
    H.fetch(
      'GET',
      'user/messages',
      null,
      (d) => {
        if (d.length) {
          this.badgeTag.innerText = d.length;
          H.show(this.badgeTag);
        }
      },
    );

    // EVENT "click" on messages count
    tag.addEventListener('click', (e) => this.open());

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

        const wallDiv = document.createElement('div');
        P.getOrCreate(wallDiv, 'wall').loadSpecific(infos, true);
        P.remove(wallDiv, 'wall');

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
  }

  // METHOD addMsg()
  addMsg(args) {
    // Refresh user's walls browser cache before adding message.
    const wallDiv = document.createElement('div');
    P.getOrCreate(wallDiv, 'wall').refreshUserWallsData(() => {
      const badgeTag = this.badgeTag;

      badgeTag.innerText = parseInt(badgeTag.innerText) + 1;
      H.show(badgeTag);

      // Refresh popover content if currently opened
      if (document.querySelector('.msg-popover')) {
        this.open(true);
      }

      // Refresh wall if needed and if opened.
      if (args && args.wallId) {
        const wall = P.get(document.querySelector(
          `.wall[data-id="wall-${args.wallId}"]`), 'wall');

        if (wall) {
          wall.refresh();
        }
      }

      P.remove(wallDiv, 'wall');
    });
  }

  // METHOD removeMsg()
  removeMsg(item) {
    const badgeTag = this.badgeTag;
    const count = parseInt(badgeTag.innerText) - 1;

    item.remove();
    badgeTag.innerText = count;

    if (!count) {
      H.hide(badgeTag);
      document.getElementById('popup-layer').click();
    }
  }

  // METHOD isPopoverVisible()
  isPopoverVisible() {
    return this.popoverBody && H.isVisible(this.popoverBody);
  }

  // METHOD fixHeight()
  fixHeight() {
    this.popoverBody.style.maxHeight = `${window.innerHeight - 80}px`;
    bootstrap.Popover.getInstance(this.badgeTag).update();
  }

  // METHOD open()
  open(refresh) {
    H.fetch(
      'GET',
      'user/messages',
      null,
      (d) => {
        let body = '';
        d.forEach(({content, creationdate, id, title}) => {
          body += `<div class="msg-item" data-id="${id}"><div class="msg-title">${title}<button type="button" class="close" title="<?=_("Delete this message")?>"><span><i class="fas fa-trash fa-xs"></i></span></button></div><div class="msg-date">${U.formatDate(creationdate, null, "Y-MM-DD H:mm")}</div><div class="msg-body">${content.replace(/\n+/g, '<br>')}</div></div>`;
        });

        if (refresh) {
          this.popoverBody.innerHTML = body;
        } else {
          H.openConfirmPopover({
            type: 'info',
            customClass: 'msg-popover umsg-popover',
            placement: 'bottom',
            item: this.badgeTag,
            title: `<i class="fas fa-envelope fa-fw"></i> <?=_("Messages")?>`,
            content: body,
            then: (p) => {
              this.popoverBody = p.querySelector('.popover-body');
              this.fixHeight();
            },
          });
        }
      });
  }
});

//////////////////////////////////// INIT ////////////////////////////////////

document.addEventListener('DOMContentLoaded', () => {
  if (!H.isLoginPage()) {
    P.create(document.getElementById('umsg'), 'umsg');
  }
});

})();
