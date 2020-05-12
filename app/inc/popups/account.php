<?php
  $user = (new Wpt_user (['userId' => $_SESSION['userId']]))->getUser();
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

        <div class="user-picture mb-3"><?=($user['picture']) ? '<button type="button" class="close img-delete"><span>&times;</span></button><img src="'.$user['picture'].'">' : '<i class="fas fa-camera-retro fa-3x"></i>'?></div>

        <div class="input-group mb-1">
          <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-user fa-fw"></i></span></div>
          <input type="text" name="username" class="form-control" value="<?=htmlentities($user['username'])?>" placeholder="<?=_("login")?>" autocorrect="off" autocapitalize="none" readonly>
          <div class="input-group-append"><button type="button" class="btn btn-change-input"><i class="fas fa-edit fa-fw"></i></button></div>
        </div>

        <div class="input-group mb-1">
          <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-key fa-fw"></i></span></div>
          <input type="password" name="password" class="form-control" value="******" placeholder="<?=_("password")?>" readonly>
          <div class="input-group-append"><button type="button" class="btn btn-change-input"><i class="fas fa-edit fa-fw"></i></button></div>
        </div>

        <div class="input-group mb-1">
          <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-signature fa-fw"></i></span></div>
          <input type="text" name="fullname" class="form-control" value="<?=htmlentities($user['fullname'])?>" placeholder="<?=_("full name")?>" autocorrect="off" readonly>
          <div class="input-group-append"><button type="button" class="btn btn-change-input"><i class="fas fa-edit fa-fw"></i></button></div>
        </div>

        <div class="input-group mb-3">
          <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-envelope fa-fw"></i></span></div>
          <input type="email" name="email" class="form-control" value="<?=$user['email']?>" placeholder="<?=_("email")?>" readonly>
          <div class="input-group-append"><button type="button" class="btn btn-change-input"><i class="fas fa-edit fa-fw"></i></button></div>
        </div>

        <div class="input-group mb-3">
          <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-address-card fa-fw"></i></span></div>
          <textarea class="form-control" name="about" data-oldvalue="<?=htmlentities($user['about'])?>" placeholder="<?=_("About you")?>"><?=$user['about']?></textarea>
        </div>
        </div>

        </form>

        <a href="#" data-action="delete-account"><?=_("Delete my account")?></a>

      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal"><?=_("Close")?></button>
      </div>
    </div>
  </div>
  </div>
