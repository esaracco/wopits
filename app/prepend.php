<?php

require_once (__DIR__.'/config.php');

use Wopits\{Helper, User};

$isCLI = (php_sapi_name () == 'cli');

// On CLI mode (deployment or cron), get locale from command line
if ($isCLI)
{
  $slocale = (isset ($argv[1])) ? $argv[1] : WPT_DEFAULT_LOCALE;
}
// Only in web mode
else
{
  $User = new User ();

  if (!isset ($_SESSION))
  {
    session_set_cookie_params (0, '/', $_SERVER['HTTP_HOST']??null, true, true);
    session_start ();
  }

  $scriptName = $_SERVER['SCRIPT_NAME'];

  // if css or js request, send appropriate header
  if (($isJS = strpos ($_SERVER['SCRIPT_FILENAME'], '.js.php') !== false) ||
      (strpos ($_SERVER['SCRIPT_FILENAME'], '.css.php') !== false))
  {
    header ('Content-Type: '.
      (($isJS) ? 'application/javascript; charset=utf-8' : 'text/css'));
  }

  // Try to log user from its auth cookie
  if (!isset ($_SESSION['userId']))
    $User->loginByCookie ();
  else
    $User->checkSession ();

  // "l" arg is in querystring if "r.php" was called to change current locale
  // if so, we use its value,
  // else get it from session,
  // else from users settings if user is auth,
  // else from browser
  if ( !($slocale = filter_input (INPUT_GET, 'l', FILTER_SANITIZE_STRING)))
    $slocale = Helper::getsLocale ($User);
}

// Locale "en" by default if no valid locale
if  (!isset (WPT_LOCALES[$slocale]))
  $slocale = WPT_DEFAULT_LOCALE;

// Apply new locale
$locale = Helper::changeLocale ($slocale);

// Only in web mode
if (!$isCLI)
{
  $isDirectURL = false;

  if (!isset ($_SESSION['userId']) &&
      preg_match ('#^(/unsubscribe|/(a|s)/\d+(/\d+)?)$#',
                     $_SERVER['QUERY_STRING']))
  {
    $isDirectURL = true;
    $_SESSION['_directURL'] = $_SERVER['QUERY_STRING'];
  }

  $_SESSION['locale'] = $locale;
  $_SESSION['slocale'] = $slocale;

  // If user is not auth
  if (
    (!isset ($_SESSION['userId']) || !isset ($_SESSION['userToken']))
    &&
    $scriptName != '/login.php'
    &&
    (
      $isDirectURL
      ||
      $scriptName == '/index.php'
      ||
      (
        (strpos ($scriptName, '/api') !== false)
        &&
        // Those pages are used for the login process where user do not
        // need to be auth
        (
          $scriptName != '/api/user/resetPassword' &&
          $scriptName != '/api/user/login' &&
          $scriptName != '/api/user'
        )
      )
    )
  )
  {
    header ("Location: /login.php");
    exit;
  }
}
