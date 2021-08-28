<?php
  include (__DIR__.'/../app/header-common.php');

  use Wopits\DbCache;

  $_SESSION['_check'] = time ();

  if (isset ($_SESSION['directURL']))
  {
    $directURL = $_SESSION['directURL'];
    unset ($_SESSION['directURL']);
  }
  else
    $directURL = '';
?>
<body id="login-page">

  <!-- MSG CONTAINER -->
  <div id="msg-container" class="position-absolute top-0 start-50 translate-middle-x"></div>
  
  <!-- MENU -->
  <nav class="navbar bg-dark navbar-dark fixed-top navbar-expand-lg shadow-sm wopits">
    <a href="#" class="navbar-brand fw-bold" data-action="about">wopits</a>
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
          include ((file_exists (__DIR__."/../app/inc/popups/$popup.php"))?
                     __DIR__."/../app/inc/popups/$popup.php":
                     __DIR__."/ui/$popup.php");
    //</WPTPROD-remove>
  ?>
  
  <div class="main-login<?=WPT_USE_LDAP?' ldap':''?>">

  <?php if (WPT_LOGIN_WELCOME):?>
  <div id="desc-container"></div>
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
              <input type="hidden" name="_directURL" value="<?=$directURL?>">

              <?php if (WPT_USE_LDAP):?>
                <div class="ldap-msg mb-3">
                  <?=_("Log in with your LDAP account")?>
                </div>
              <?php endif?>

              <div class="input-group mb-1">
                <span class="input-group-text"><i class="fas fa-user"></i></span>
                <input type="text" class="form-control" name="login" value="" required placeholder="<?=_("login")?>" maxlength="<?=DbCache::getFieldLength('users', 'username')?>" autocorrect="off" autocapitalize="off" autofocus>
              </div>
              <div class="input-group mb-2">
                <span class="input-group-text"><i class="fas fa-key"></i></span>
                <input type="password" class="form-control" name="password" value="" maxlength="<?=DbCache::getFieldLength('users', 'password')?>" required placeholder="<?=_("password")?>" autocorrect="off" autocapitalize="off">
              </div>
              <div class="form-group">
                <div class="form-check remember">
                  <input type="checkbox" class="form-check-input" id="remember" value="1">
                  <label class="form-check-label" for="remember"><?=_("Remember me")?></label>
                </div>
              </div>
              <div class="d-flex justify-content-center mt-3">
                <button data-type="login" type="button" name="button" class="btn btn-success"><?=_("Log in")?></button>
              </div>
              <?php if (!WPT_USE_LDAP):?>
                <div class="d-flex justify-content-center mt-3">
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
