<?php require (__DIR__.'/../../app/prepend.php')?>
<div class="modal m-fullscreen no-theme" id="pworkPopup" role="dialog" tabindex="-1">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-users-cog fa-fw"></i> <span><?=_("Users involved")?></span></h5>
        <button type="button" class="close" data-dismiss="modal">
          <span>&times;</span>
        </button>
      </div>
      <div class="modal-body">

        <div class="mb-3 desc"></div>

        <div class="search mb-3">
        </div>

        <div>
          <label class="users-title"><?=_("Users involved in this note:")?></label>
          <label class="nousers-title"><?=_("This note has no users involved.")?></label>
          <div class="scroll mb-2">
            <ul class="list-group attr"></ul>
          </div>
        </div>

      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times"></i> <?=_("Close")?></button>
      </div>
    </div>
  </div>
  </div>
