<div class="modal m-fullscreen" id="settingsPopup" role="dialog" tabindex="-1">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-sliders-h fa-lg fa-fw settings"></i><?=_("Settings")?></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form>
          <div class="mb-3 inline-block">
            <label><?=_("Theme")?>:</label>
            <div class="themes mt-2">
              <?php foreach (WPT_THEMES as $theme) { ?>
                <a class="dot-theme btn-theme-<?=$theme?>" data-theme="theme-<?=$theme?>"></a>
              <?php } ?>
            </div>
          </div>
          <div class="mb-3">
            <label><?=_("Language")?>:</label>
            <div class="locale-picker mt-2">
            <?php foreach (array_keys (WPT_LOCALES) as $k):?>
              <div data-locale="<?=$k?>"></div>
            <?php endforeach?>
            </div>
          </div>
          <div class="mb-3">
            <label><?=_("Timezone")?>:</label>
            <div>
              <select class="form-control timezone mt-2" data-timezone="<?=$User->getTimezone()?>"></select>
            </div>
          </div>
          <div>
            <label class="wall-color"></label>
            <div class="mt-2"><span class="cp"></span></div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fas fa-times"></i> <?=_("Close")?></button>
      </div>
    </div>
  </div>
  </div>
