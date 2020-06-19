<div class="modal" id="changePasswordPopup" role="dialog" tabindex="-1">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-shield-alt"></i> <?=_("Password")?></h5>
        <button type="button" class="close" data-dismiss="modal">
          <span>&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <form>
          <div><?=_("Your current password:")?></div>
          <div class="input-group mb-4">
          <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-key"></i></span></div>
          <input type="password" class="form-control" name="password" value="" required placeholder="<?=_("current password")?>" autofocus>
        </div>

          <div><?=_("The new password:")?></div>
          <div class="input-group mb-1">
          <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-key fa-xs"></i></span></div>
          <input type="password" class="form-control" name="password2" value="" required placeholder="<?=_("new password")?>" maxlength="<?=Wpt_dbCache::getFieldLength('users', 'password')?>">
        </div>
          <div><?=_("New password confirmation:")?></div>
          <div class="input-group mb-1">
          <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-key fa-fw fa-xs"></i></span></div>
          <input type="password" class="form-control" name="password3" value="" required placeholder="<?=_("new password confirmation")?>" maxlength="<?=Wpt_dbCache::getFieldLength('users', 'password')?>">
        </div>

        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary"><?=_("Save")?></button>
        <button type="button" class="btn btn-secondary" data-dismiss="modal"><?=_("Close")?></button>
      </div>
    </div>
  </div>
  </div>
