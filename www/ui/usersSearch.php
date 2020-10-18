<?php require (__DIR__.'/../../app/prepend.php')?>
<div class="modal" id="usersSearchPopup" role="dialog" tabindex="-1">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-user-friends fa-fw"></i> <span><?=_("Manage users")?></span></h5>
        <button type="button" class="close" data-dismiss="modal">
          <span>&times;</span>
        </button>
      </div>
      <div class="modal-body">

        <div class="mb-3 desc"></div>

        <div class="search mb-3">
        </div>

        <div>
          <label class="users-title"><?=_("Users in this group:")?></label>
          <label class="nousers-title"><?=_("This group does not contain any users!")?></label>
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
