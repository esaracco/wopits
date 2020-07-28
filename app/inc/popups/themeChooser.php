<div class="modal m-fullscreen" id="themeChooserPopup" role="dialog" tabindex="-1">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-theater-masks fa-fw"></i> <?=_("Theme")?></h5>
        <button type="button" class="close" data-dismiss="modal">
          <span>&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <p><?=_("The gray color of the default theme may not suit your mood today!")?></p>
        <p><?=_("If so, do not hesitate to test others:")?></p>
        <div class="themes mt-3 mb-3" style="text-align:center">
          <a class="dot-theme btn-theme-default" data-theme="theme-default"></a>
          <?php foreach (WPT_THEMES as $theme) { ?>
            <a class="dot-theme btn-theme-<?=$theme?>" data-theme="theme-<?=$theme?>"></a>
          <?php } ?>
        </div>
        <p><?=sprintf(_("You can change it at any time in your %sSettings%s."), '<button type="button" class="btn btn-secondary btn-xs settings"><i class="fa-fw fas fa-sliders-h"></i>&nbsp;', '</button>')?></p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal"><?=_("OK")?></button>
      </div>
    </div>
  </div>
  </div>
