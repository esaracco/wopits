<div class="modal" id="createAccountPopup" role="dialog" tabindex="-1">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-user-circle fa-fw"></i> <?=_("Create a account")?></h5>
        <button type="button" class="close" data-dismiss="modal">
          <span>&times;</span>
        </button>
      </div>
      <div class="modal-body">

        <form>

        <div class="main">
        <div class="input-group mb-1">
          <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-user fa-fw"></i></span></div>
          <input type="text" class="form-control" name="username" value="" required placeholder="<?=_("login")?>" autocorrect="off" autocapitalize="none" autofocus maxlength="<?=Wpt_dbCache::getFieldLength('users', 'username')?>">
        </div>
        <div class="input-group mb-1">
          <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-key fa-fw"></i></span></div>
          <input class="form-control" type="password" name="password" value="" required placeholder="<?=_("password")?>" maxlength="<?=Wpt_dbCache::getFieldLength('users', 'password')?>">
        </div>

        <div class="input-group mb-1">
          <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-signature fa-fw"></i></span></div>
          <input type="text" class="form-control" name="fullname" value="" required placeholder="<?=_("full name")?>" autocorrect="off" maxlength="<?=Wpt_dbCache::getFieldLength('users', 'fullname')?>">
        </div>

        <div class="input-group mb-1">
          <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-envelope fa-fw"></i></span></div>
          <input class="form-control" type="email" name="email" value="" required placeholder="<?=_("email")?>" maxlength="<?=Wpt_dbCache::getFieldLength('users', 'email')?>">
        </div>
        </div>

        </form>

      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary"><?=_("Next")?> <i class="fas fa-caret-right"></i></button>
        <button type="button" class="btn btn-secondary" data-dismiss="modal"><?=_("Close")?></button>
      </div>
    </div>
  </div>
</div>
