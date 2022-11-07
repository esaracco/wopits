<?php require(__DIR__.'/../../app/prepend.php')?>
<div class="modal m-fullscreen no-theme" id="postitCommentsPopup" role="dialog" tabindex="-1">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-comments fa-fw"></i> <?=_("Comments")?></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="editing"></div>
        <div class="content"></div>
      </div>
    </div>
  </div>
</div>
