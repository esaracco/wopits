<?php require (__DIR__.'/../../app/prepend.php')?>
<div class="modal m-fullscreen" id="themeChooserPopup" role="dialog" tabindex="-1">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-theater-masks fa-fw"></i> <?=_("Theme")?></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p><?=_("The default theme may not suit your mood today!")?></p>
        <p><?=_("If so, do not hesitate to test others:")?></p>
        <div class="themes mb-3 inline-block">
          <?php foreach (WPT_THEMES as $theme) { ?>
            <a class="dot-theme btn-theme-<?=$theme?>" data-theme="theme-<?=$theme?>"></a>
          <?php } ?>
        </div>
        <p><?=sprintf(_("You can change it at any time in your %sSettings%s"), '<button type="button" class="btn btn-secondary btn-xs settings"><i class="fa-fw fas fa-sliders-h"></i>&nbsp;', '</button>')?></p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?=_("OK")?></button>
      </div>
    </div>
  </div>
  </div>
