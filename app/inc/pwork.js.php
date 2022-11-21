<?php
/**
Javascript plugin - Notes workers

Scope: Note
Name: pwork
Description: Manage notes workers
*/

require_once(__DIR__.'/../prepend.php');

?>

(() => {
'use strict';

/////////////////////////////////// PLUGIN ////////////////////////////////////

P.register('pwork', class extends Wpt_postitCountPlugin {
  // METHOD constructor()
  constructor(settings) {
    super(settings);

    if (!this.settings.shared || this.settings.readonly &&
        !this.settings.count) {
      this.tag.classList.add('hidden');
    }

    // Create postit top workers icon
    this.addTopIcon('fa-users-cog', 'pwork');

    return this;
  }

  // METHOD display()
  display() {
    const postit = S.getCurrent ('postit');
    const readonly = this.settings.readonly;

    H.loadPopup('usearch', {
      open: false,
      template: 'pwork',
      cb: (p) => {
        const usearch = P.getOrCreate(p, 'usearch');

        usearch.setSettings({
          caller: 'pwork',
          onAdd: () => this.incCount(),
          onRemove: () => this.decCount(),
        });

        usearch.reset({
          full: true,
          readonly: Boolean(this.settings.readonly),
        });

        usearch.displayUsers({
          ...usearch.getIds(),
          // Refresh counter (needed when some users have been deleted)
          then: (c) => this.setCount(c),
        });

        H.openModal({item: p});

        // EVENT "hide.bs.modal" on workers popup
        p.addEventListener('hide.bs.modal', (e) => {
          if (S.get('still-closing')) return;

          // LOCAL FUNCTION __close ()
          const __close = () =>
            H.checkAccess(<?=WPT_WRIGHTS_RW?>) ?
              postit.unedit() : postit.unsetCurrent();

          const pwork = postit.getPlugin('pwork');
          const newUsers = usearch.getNewUsers();

          e.stopImmediatePropagation();

          if (newUsers.length) {
            H.preventDefault(e);
            H.openConfirmPopup({
              type: 'notify-users',
              icon: 'save',
              content: `<?=_("Notify new users?")?>`,
              onConfirm: () => pwork.notifyNewUsers(newUsers),
              onClose: () => {
                S.set('still-closing', true, 500);
                bootstrap.Modal.getInstance(p).hide();
                __close();
              },
            });
          }
          else {
            __close();
          }
        }, {once: true});
      }
    });
  }

  // METHOD notifyNewUsers()
  notifyNewUsers(ids) {
    const {wallId, cellId, postitId} = this.getIds();

    H.request_ws(
      'PUT',
      `wall/${wallId}/cell/${cellId}/postit/${postitId}/notifyWorkers`,
      {ids, postitTitle: this.postit().getTitle()});
  }

  // METHOD open()
  open(refresh) {
    this.postit().edit({}, () => this.display());
  }
});

//////////////////////////////////// INIT ////////////////////////////////////

document.addEventListener ('DOMContentLoaded', () => {
  if (H.isLoginPage ()) return;

  // EVENT "click"
  document.addEventListener('click', (e) => {
    const el = e.target;

    if (S.get('postit-creating')) return;

    // EVENT "click" on workers count
    if (el.matches('.pwork,.pwork *')) {
      const pworkTag = (el.tagName === 'DIV') ? el : el.parentNode;
      const pwork = P.get(pworkTag, 'pwork');

      e.stopImmediatePropagation();

      if (H.checkAccess (<?=WPT_WRIGHTS_RW?>)) {
        pwork.open();
      } else {
        P.get(pworkTag.closest('.postit'), 'postit').setCurrent();
        pwork.display();
      }
    }
  });
});

})();
