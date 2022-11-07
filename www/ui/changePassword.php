<?php
  require(__DIR__.'/../../app/prepend.php');
  $pLen = Wopits\DbCache::getFieldLength('users', 'password');
?>
<div class="modal" id="changePasswordPopup" role="dialog" tabindex="-1">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-shield-alt"></i> <?=_("Password")?></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form>
          <div><?=_("Your current password:")?></div>
          <div class="input-group mb-4">
            <span class="input-group-text"><i class="fas fa-key"></i></span>
            <input type="password" autocomplete="current-password" class="form-control" name="password" value="" required placeholder="<?=_("current password")?>" autocorrect="off" autocapitalize="off">
          </div>
          <div><?=_("The new password:")?></div>
          <div class="input-group mb-1">
            <span class="input-group-text"><i class="fas fa-key fa-xs"></i></span>
            <input type="password" autocomplete="new-password" class="form-control" name="password2" value="" required placeholder="<?=_("new password")?>" maxlength="<?=$pLen?>" autocorrect="off" autocapitalize="off">
          </div>
          <div><?=_("New password confirmation:")?></div>
          <div class="input-group mb-1">
            <span class="input-group-text"><i class="fas fa-key fa-fw fa-xs"></i></span>
            <input type="password" autocomplete="current-password" class="form-control" name="password3" value="" required placeholder="<?=_("new password confirmation")?>" maxlength="<?=$pLen?>" autocorrect="off" autocapitalize="off">
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary"><i class="fas fa-save"></i> <?=_("Save")?></button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fas fa-times"></i> <?=_("Close")?></button>
      </div>
    </div>
  </div>
</div>
