<?php
/**
Javascript plugin - Notes workers

Scope: Note
Element: .pwork
Description: Manage notes workers
*/

require_once(__DIR__.'/../prepend.php');

$Plugin = new Wopits\jQueryPlugin('pwork');
echo $Plugin->getHeader();

?>

/////////////////////////////////// PRIVATE //////////////////////////////////

let _$mainPopup;
let _$editPopup;

/////////////////////////////////// PUBLIC ///////////////////////////////////

<?=$Plugin->getPublicSection()?>

// Inherit from Wpt_postitCountPlugin
Plugin.prototype = Object.create(Wpt_postitCountPlugin.prototype);
Object.assign(Plugin.prototype, {
  // METHOD init()
  init(args) {
    const settings = this.settings;

    if (!args.shared || settings.readonly && !settings.count) {
      this.element[0].classList.add('hidden');
    }

    // Create postit top workers icon
    this.addTopIcon('fa-users-cog', 'pwork');

    return this;
  },

  // METHOD display()
  display() {
    const $postit = S.getCurrent ('postit');
    const readonly = this.settings.readonly;

    H.loadPopup('usearch', {
      open: false,
      template: 'pwork',
      settings: {
        caller: 'pwork',
        onAdd: () => this.incCount(),
        onRemove: () => this.decCount(),
      },
      cb: ($p) => {
        const p = $p[0];

        $p.usearch('reset', {
          full: true,
          readonly: Boolean(this.settings.readonly),
        });

        $p.usearch('displayUsers', {
          ...$p.usearch('getIds'),
          // Refresh counter (needed when some users have been deleted)
          then: (c) => this.setCount(c),
        });

        H.openModal({item: p});

        // EVENT "hide.bs.modal" on workers popup
        p.addEventListener('hide.bs.modal', (e) => {
          if (S.get('still-closing')) return;

          // LOCAL FUNCTION __close ()
          const __close = () =>
            $postit.postit(H.checkAccess(<?=WPT_WRIGHTS_RW?>) ?
              'unedit' : 'unsetCurrent');

          const pwork = $postit.postit('getPlugin', 'pwork');
          const newUsers = $p.usearch('getNewUsers');

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
  },

  // METHOD notifyNewUsers()
  notifyNewUsers(ids) {
    const {wallId, cellId, postitId} = this.getIds();

    H.request_ws(
      'PUT',
      `wall/${wallId}/cell/${cellId}/postit/${postitId}/notifyWorkers`,
      {ids, postitTitle: this.postit().getTitle()});
  },

  // METHOD open()
  open(refresh) {
    this.postit().edit({}, () => this.display());
  },
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
      const $pwork = $((el.tagName === 'DIV') ? el : el.parentNode);

      e.stopImmediatePropagation();

      if (H.checkAccess (<?=WPT_WRIGHTS_RW?>)) {
        $pwork.pwork('open');
      } else {
        $pwork.closest('.postit').postit('setCurrent');
        $pwork.pwork('display');
      }
    }
  });
});

<?=$Plugin->getFooter()?>
