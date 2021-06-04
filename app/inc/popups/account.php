<?php
  require (__DIR__.'/../../prepend.php');

  $user = (new Wopits\User (['userId' => $_SESSION['userId']]))->getUser();
?>
<div class="modal m-fullscreen" id="accountPopup" role="dialog" tabindex="-1">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-user-circle fa-lg fa-fw"></i><?=_("Account")?></h5>
        <button type="button" class="close" data-dismiss="modal">
          <span>&times;</span>
        </button>
      </div>
      <div class="modal-body">

        <form>

        <div class="main">

        <div class="content-centered"><div class="user-picture mb-3"><?=($user['picture']) ? '<button type="button" class="close img-delete"><span>&times;</span></button><img src="'.$user['picture'].'">' : '<i class="fas fa-camera-retro fa-3x"></i>'?></div></div>

        <?php if (WPT_USE_LDAP):?>
          <div class="mb-2 ldap-msg">
            <?=_("You are connected with your LDAP account")?>
         </div>
        <?php endif?>

        <div class="input-group mb-1">
          <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-user fa-fw"></i></span></div>
          <input type="text" name="username" class="form-control" value="<?=htmlentities($user['username'])?>" placeholder="<?=_("login")?>" autocorrect="off" autocapitalize="none" readonly>
          <?php if (!WPT_USE_LDAP):?>
            <div class="input-group-append"><button type="button" class="btn btn-change-input"><i class="fas fa-edit fa-fw"></i></button></div>
          <?php endif?>
        </div>

        <?php if (!WPT_USE_LDAP):?>
          <div class="input-group mb-1">
            <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-key fa-fw"></i></span></div>
            <input type="password" name="password" class="form-control" value="******" placeholder="<?=_("password")?>" readonly>
            <div class="input-group-append"><button type="button" class="btn btn-change-input"><i class="fas fa-edit fa-fw"></i></button></div>
          </div>
        <?php endif?>

        <div class="input-group mb-1">
          <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-envelope fa-fw"></i></span></div>
          <input type="email" name="email" class="form-control" value="<?=$user['email']?>" placeholder="<?=_("email")?>" readonly>
          <?php if (!WPT_USE_LDAP):?>
            <div class="input-group-append"><button type="button" class="btn btn-change-input"><i class="fas fa-edit fa-fw"></i></button></div>
          <?php endif?>
        </div>

        <div class="input-group mb-3">
          <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-signature fa-fw"></i></span></div>
          <input type="text" name="fullname" class="form-control" value="<?=htmlentities($user['fullname'])?>" placeholder="<?=_("full name")?>" autocorrect="off" readonly>
          <div class="input-group-append"><button type="button" class="btn btn-change-input"><i class="fas fa-edit fa-fw"></i></button></div>
        </div>



        <div class="input-group mb-3">
          <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-address-card fa-fw"></i></span></div>
          <textarea class="form-control" name="about" data-oldvalue="<?=htmlentities($user['about'])?>" placeholder="<?=_("About you")?>" maxlength="<?=Wopits\DbCache::getFieldLength('users', 'about')?>"><?=$user['about']?></textarea>
        </div>
        </div>

          <div class="custom-control custom-switch">
            <input type="checkbox" class="custom-control-input" name="allow_emails" id="allow_emails"<?=($user['allow_emails'] == 1)?' checked':''?>>
            <label class="custom-control-label" for="allow_emails"> <?=_("Notify me by email")?></label> <button type="button" class="help" data-toggle="tooltip" data-html="true" title="<?=_("By unchecking this option, you will no longer receive email notifications.")?>"><i class="fas fa-info-circle fa-xs"></i></button>
          </div>

          <div class="custom-control custom-switch">
            <input type="checkbox" class="custom-control-input" name="visible" id="visible"<?=($user['visible'] != 1)?' checked':''?>>
            <label class="custom-control-label" for="visible"> <?=_("Invisible mode")?></label> <button type="button" class="help" data-toggle="tooltip" data-html="true" title="<?=_("<b>Invisible mode</b>: sharing is not possible and no one can see you")?>"><i class="fas fa-info-circle fa-xs"></i></button>
          </div>

        </form>

        <?php if (!WPT_USE_LDAP):?>
          <hr>
          <div class="delete"><a href="#" data-action="delete-account"><i class="fas fa-bomb fa-lg"></i> <?=_("Delete my account")?></a></div>
        <?php endif?>

      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times"></i> <?=_("Close")?></button>
      </div>
    </div>
  </div>
  </div>
