<div class="modal m-fullscreen" id="createAccountPopup" role="dialog" tabindex="-1">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-user-circle fa-fw"></i> <?=_("Create a account")?></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">

        <form>

        <div class="main">
        <div class="input-group mb-1">
          <span class="input-group-text"><i class="fas fa-user fa-fw"></i></span>
          <input type="text" class="form-control" name="username" autocomplete="username" value="" required placeholder="<?=_("login")?>" autocorrect="off" autocapitalize="off" maxlength="<?=Wopits\DbCache::getFieldLength('users', 'username')?>">
        </div>
        <div class="input-group mb-1">
          <span class="input-group-text"><i class="fas fa-key fa-fw"></i></span>
          <input class="form-control" type="password" autocomplete="current-password" name="password" value="" required placeholder="<?=_("password")?>" maxlength="<?=Wopits\DbCache::getFieldLength('users', 'password')?>" autocorrect="off" autocapitalize="off">
        </div>

        <div class="input-group mb-1">
          <span class="input-group-text"><i class="fas fa-signature fa-fw"></i></span>
          <input type="text" class="form-control" name="fullname" value="" required placeholder="<?=_("full name")?>" autocorrect="off" maxlength="<?=Wopits\DbCache::getFieldLength('users', 'fullname')?>">
        </div>

        <div class="input-group mb-1">
          <span class="input-group-text"><i class="fas fa-envelope fa-fw"></i></span>
          <input class="form-control" type="email" name="email" value="" required placeholder="<?=_("email")?>" maxlength="<?=Wopits\DbCache::getFieldLength('users', 'email')?>">
        </div>
        </div>

        </form>

      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary"><?=_("Next")?> <i class="fas fa-caret-right"></i></button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fas fa-times"></i> <?=_("Close")?></button>
      </div>
    </div>
  </div>
</div>
