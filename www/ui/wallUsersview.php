<?php require (__DIR__.'/../../app/prepend.php')?>
<div class="modal m-fullscreen" id="wallUsersviewPopup" role="dialog" tabindex="-1">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-user-friends"></i> <?=_("Connected users")?></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <?=_("The following users are viewing this wall:")?>
        <ul class="list-group mt-3"></ul>
      </div>
    </div>
  </div>
  </div>
