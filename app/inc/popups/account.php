<?php
  $user = $User->getUser ();
?>
<div class="modal m-fullscreen" id="accountPopup" role="dialog" tabindex="-1">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-user-circle fa-lg fa-fw"></i><?=_("Account")?></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">

        <form>

        <div class="main">

        <div class="content-centered"><div class="user-picture mb-3"><?=($user['picture']) ? '<button type="button" class="btn-close img-delete"></button><img src="'.$user['picture'].'">' : '<i class="fas fa-camera-retro fa-3x"></i>'?></div></div>

        <?php if (WPT_USE_LDAP):?>
          <div class="mb-2 ldap-msg">
            <?=_("You are connected with your LDAP account")?>
         </div>
        <?php endif?>

        <div class="input-group mb-1">
          <span class="input-group-text"><i class="fas fa-user fa-fw"></i></span>
          <input type="text" name="username" class="form-control" value="<?=htmlentities($user['username'])?>" placeholder="<?=_("login")?>" autocorrect="off" autocapitalize="none" readonly>
          <?php if (!WPT_USE_LDAP):?>
            <button type="button" class="btn btn-change-input"><i class="fas fa-edit fa-fw"></i></button>
          <?php endif?>
        </div>

        <?php if (!WPT_USE_LDAP):?>
          <div class="input-group mb-1">
            <span class="input-group-text"><i class="fas fa-key fa-fw"></i></span>
            <input type="password" name="password" class="form-control" value="******" placeholder="<?=_("password")?>" readonly>
            <button type="button" class="btn btn-change-input"><i class="fas fa-edit fa-fw"></i></button>
          </div>
        <?php endif?>

        <div class="input-group mb-1">
          <span class="input-group-text"><i class="fas fa-envelope fa-fw"></i></span>
          <input type="email" name="email" class="form-control" value="<?=$user['email']?>" placeholder="<?=_("email")?>" readonly>
          <?php if (!WPT_USE_LDAP):?>
            <button type="button" class="btn btn-change-input"><i class="fas fa-edit fa-fw"></i></button>
          <?php endif?>
        </div>

        <div class="input-group mb-3">
          <span class="input-group-text"><i class="fas fa-signature fa-fw"></i></span>
          <input type="text" name="fullname" class="form-control" value="<?=htmlentities($user['fullname'])?>" placeholder="<?=_("full name")?>" autocorrect="off" readonly>
          <button type="button" class="btn btn-change-input"><i class="fas fa-edit fa-fw"></i></button>
        </div>



        <div class="input-group mb-3">
          <span class="input-group-text"><i class="fas fa-address-card fa-fw"></i></span>
          <textarea class="form-control" name="about" data-oldvalue="<?=htmlentities($user['about'])?>" placeholder="<?=_("About you")?>" maxlength="<?=Wopits\DbCache::getFieldLength('users', 'about')?>"><?=$user['about']?></textarea>
        </div>
        </div>

          <div class="form-check form-switch" title="<?=_("By unchecking this option, you will no longer receive email notifications")?>">
            <input type="checkbox" class="form-check-input" name="allow_emails" id="allow_emails"<?=($user['allow_emails'] == 1)?' checked':''?>>
            <label class="form-check-label" for="allow_emails"> <?=_("Notify me by email")?></label>
          </div>

          <div class="form-check form-switch" title="<?=_("By checking this option, sharing will not be possible and no one will see you")?>">
            <input type="checkbox" class="form-check-input" name="visible" id="visible"<?=($user['visible'] != 1)?' checked':''?>>
            <label class="form-check-label" for="visible"> <?=_("Invisible mode")?></label>
          </div>

        </form>

        <?php if (!WPT_USE_LDAP):?>
          <hr>
          <div class="delete"><a href="#" data-action="delete-account"><i class="fas fa-bomb fa-lg"></i> <?=_("Delete my account")?></a></div>
        <?php endif?>

      </div>
    </div>
  </div>
  </div>
