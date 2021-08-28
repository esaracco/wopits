<?php require (__DIR__.'/../../app/prepend.php')?>
<div class="modal" id="usearchPopup" role="dialog" tabindex="-1">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-user-friends fa-fw"></i> <span><?=_("Users")?></span></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">

        <div class="mb-3 desc"></div>

        <div class="search mb-3">
        </div>

        <div>
          <label class="users-title"><?=_("Users in this group:")?></label>
          <label class="nousers-title"><?=_("No user in this group.")?></label>
          <div class="scroll mt-2 mb-2">
            <ul class="list-group attr"></ul>
          </div>
        </div>

      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fas fa-times"></i> <?=_("Close")?></button>
      </div>
    </div>
  </div>
  </div>
