<?php
  include (__DIR__.'/../app/header-common.php');

  $_SESSION['_check'] = time ();

  if (isset ($_SESSION['_directURL']))
  {
    $_directURL = $_SESSION['_directURL'];
    unset ($_SESSION['_directURL']);
  }
  else
    $_directURL = '';
?>
<body class="login-page">

  <!-- LOADER -->
  <div id="popup-loader" class="layer">
    <div id="loader"><i class="fas fa-cog fa-spin fa-lg"></i> <span><?=_("Please wait")?>...</span></div>
  </div>
  
  <!-- MSG CONTAINER -->
  <div id="msg-container"></div>
  
  <!-- MENU -->
  <nav class="navbar bg-dark navbar-dark fixed-top navbar-expand-lg shadow-sm wopits">
    <a href="#" class="navbar-brand font-weight-bold" data-action="about">wopits</a>
    <div class="themes">
      <a class="dot-theme btn-theme-default" data-theme="theme-default"></a>
    <?php foreach (WPT_THEMES as $theme) { ?>
      <a class="dot-theme btn-theme-<?=$theme?>" data-theme="theme-<?=$theme?>"></a>
    <?php } ?>
    </div>
  
  <!--MENU main-->
    <div id="main-menu">
      <!--About-->
      <ul class="navbar-nav mr-auto">
        <li class="nav-item" data-action="about"><a href="#" class="nav-link"><i class="fas fa-info-circle fa-lg"></i></a></li>
      </ul>
    </div>
  </nav>

  <!--<WPTPROD-inject-WPT_POPUPS['login']/>-->
  <?php
    //<WPTPROD-remove>
      foreach (WPT_POPUPS['login'] as $popup)
        include (__DIR__."/../app/inc/popups/$popup.php");
    //</WPTPROD-remove>
  ?>
  
  <div class="main-login<?=WPT_USE_LDAP?' ldap':''?>">

  <?php if (WPT_LOGIN_WELCOME):?>
  <div id="desc-container">
    <div class="alert alert-primary alert-dismissible fade show" role="alert">
    <button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">&times;</span></button>
    <h4 class="alert-heading"><?=_("Welcome to wopits!")?></h4>
    <p><?=sprintf(_("A free, open-source post-its manager that respects your privacy, that's good, right? %s will let you do a lot of things, without ever having to worrying about where your data is going to or how it is monetized."), '<a href="https://wopits.esaracco.fr" target="_blank">wopits</a>')?></p>
    <hr>
    <p class="mb-0"><?=sprintf(_("Besides, if you are suspicious, nobody is forcing you to use it here online! You just have to %sget the code%s to install it yourself..."), '<a href="https://github.com/esaracco/wopits" target="_blank">', '</a>')?></p></div>
  </div>
  <?php endif?>

    <div class="container h-100" id="login">
      <div class="d-flex justify-content-center h-100">
        <div class="user-card">
          <div class="d-flex justify-content-center div-logo">
            <div class="brand-logo-container">
              <img src="/img/wopits-192x192.png" class="brand-logo" alt="Logo">
            </div>
          </div>
          <div class="d-flex justify-content-center form-container">
            <form>
              <input type="hidden" name="_check" value="<?=$_SESSION['_check']?>">
              <input type="hidden" name="_directURL" value="<?=$_directURL?>">

              <?php if (WPT_USE_LDAP):?>
                <div class="ldap-msg mb-3">
                  <?=_("Log in with your LDAP account")?>
                </div>
              <?php endif?>

              <div class="input-group mb-1">
                <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-user"></i></span></div>
                <input type="text" class="form-control" name="login" value="" required placeholder="<?=_("login")?>" maxlength="<?=Wpt_dbCache::getFieldLength('users', 'username')?>" autocorrect="off" autocapitalize="none" autofocus>
              </div>
              <div class="input-group mb-2">
                <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-key"></i></span></div>
                <input type="password" class="form-control" name="password" value="" maxlength="<?=Wpt_dbCache::getFieldLength('users', 'password')?>" required placeholder="<?=_("password")?>">
              </div>
              <div class="form-group">
                <div class="custom-control custom-checkbox">
                  <input type="checkbox" class="custom-control-input" id="remember" value="1">
                  <label class="custom-control-label" for="remember"><?=_("Remember me")?></label>
                </div>
              </div>
              <div class="d-flex justify-content-center mt-3 login-container">
                <button data-type="login" type="button" name="button" class="btn btn-success"><?=_("Log in")?></button>
              </div>
              <?php if (!WPT_USE_LDAP):?>
                <div class="d-flex justify-content-center mt-3 login-container">
                  <button data-type="create" type="button" name="button" class="btn btn-secondary"><?=_("Create a account")?></button>
                </div>
              <?php endif?>
            </form>
          </div>
          <?php if (!WPT_USE_LDAP):?>
            <div class="mt-4">
              <div class="d-flex justify-content-center links">
                <a data-type="forgot" href="#"><?=_("Forgot your password?")?></a>
              </div>
            </div>
          <?php endif?>
        </div>
      </div>
    </div>
  </div>

</body>
</html>
