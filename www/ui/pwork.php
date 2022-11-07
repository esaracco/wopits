<?php require(__DIR__.'/../../app/prepend.php')?>
<div class="modal m-fullscreen no-theme" id="pworkPopup" role="dialog" tabindex="-1">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-users-cog fa-fw"></i> <span><?=_("Users involved")?></span></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3 desc"></div>
        <div class="search mb-3">
        </div>
        <div>
          <label class="users-title"><?=_("Users involved:")?></label>
          <label class="nousers-title"><?=_("The note has no user involved.")?></label>
          <div class="scroll mt-2 mb-2">
            <ul class="list-group attr"></ul>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
