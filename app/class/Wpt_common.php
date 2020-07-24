<?php

  // Important!
  // Apache and wopits WebSocket server manipulate the same files.
  // They must have the same system group and umask must be 002.
  umask (002);

  use PHPMailer\PHPMailer\PHPMailer;
//  use PHPMailer\PHPMailer\SMTP;
//  use PHPMailer\PHPMailer\Exception;

  require_once (__DIR__.'/../config.php');
  require_once (__DIR__.'/Wpt_user.php');

  $User = new Wpt_user ();

  $isCLI = (php_sapi_name () == 'cli');
  $scriptName = '';

  // On CLI mode (deployment or cron), get locale from command line
  if ($isCLI)
  {
    $slocale = (isset ($argv[1])) ? $argv[1] : 'en';
  }
  // Only in web mode
  else
  {
    session_set_cookie_params (0, '/', $_SERVER['HTTP_HOST'], true, true);
    session_start ();

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
      $slocale = Wpt_common::getsLocale ($User);
  }

  // Locale "en" by default if no valid locale
  if  (!isset (WPT_LOCALES[$slocale]))
    $slocale = 'en';

  // Apply new locale
  $locale = Wpt_common::changeLocale ($slocale);

  // Only in web mode
  if (!$isCLI)
  {
    $isDirectURL = false;

    if (!isset ($_SESSION['userId']) &&
        preg_match ('#^/(a|s)/w/\d+(/p/\d+)?$#', $_SERVER['QUERY_STRING']))
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

  //date_default_timezone_set ($User->getTimezone ());

  class Wpt_common
  {
    // Keep WS connection and database persistent connection alive 
    public static function ping ()
    {
      exec (WPT_ROOT_PATH.'/app/websocket/client.php -p');
    }

    public static function changeLocale ($slocale)
    {
      $locale =
        $slocale.'_'.(($slocale == 'en') ? 'US' : strtoupper ($slocale));

      putenv ("LANGUAGE=$locale.UTF-8");
      bindtextdomain ('wopits', __DIR__.'/../locale');
      textdomain ('wopits');
      setlocale (LC_ALL, "$locale.UTF-8");
      bind_textdomain_codeset ('wopits', 'UTF-8');

      return $locale;
    }

    public static function getsLocale ($User)
    {
      $slocale = '';

      if (isset ($_SESSION['slocale']))
        $slocale = $_SESSION['slocale'];
      elseif ($User->userId)
        $slocale = @(json_decode ($User->getSettings()))->locale;

      if (!$slocale)
        $slocale = Wpt_common::getBrowserLocale ();

      return $slocale;
    }

    public static function unaccent ($str)
    {
      return strtolower (iconv ('utf-8', 'ascii//TRANSLIT', $str));
    }

    public static function rm ($path, $firstCall = true)
    {
      // First call? -> check the path
      if ($firstCall)
      {
        $path = self::getSecureSystemName ($path);

        // Delete only under the data directory and not the data directory
        // itself or an external path.
        if (!preg_match ('#'.WPT_DATA_SPATH.'/.#', $path))
          return error_log (__METHOD__.':'.__LINE__.
                              ":Forbidden root path `$path`!");

        // If directory does not exists, return.
        if (!file_exists ($path))
          return;

        // If the item to delete is a file (and not directory) and return.
        if (is_file ($path))
          return unlink ($path);
      }

      foreach (scandir ($path) as $file)
      {
        if ($file != '.' && $file != '..')
          (is_dir("$path/$file")) ?
            self::rm ("$path/$file", false) : unlink ("$path/$file");
      }

      return rmdir ($path);
    }

    public static function mail ($args)
    {
      require_once (__DIR__.'/../libs/vendor/autoload.php');

      //<WPTPROD-remove>
      if (WPT_DEV_MODE)
        $args['email'] = WPT_EMAIL_CONTACT;
      //</WPTPROD-remove>

      $mail = new PHPMailer (true);
      try
      {
        //$mail->SMTPDebug = SMTP::DEBUG_SERVER;
        $mail->CharSet = 'UTF-8';
        $mail->Encoding = 'base64';
        $mail->isHTML (false);

        if (defined ('WPT_SMTP_HOST') && !empty (WPT_SMTP_HOST))
        {
          $mail->isSMTP ();
          $mail->Host = WPT_SMTP_HOST;

          if (!empty (WPT_SMTP_PORT))
            $mail->Port = WPT_SMTP_PORT;
        }

        $mail->setFrom (WPT_EMAIL_FROM, 'wopits');
        $mail->addAddress ($args['email']);
        $mail->Subject = $args['subject'];

        if (WPT_USE_DKIM)
        {
          $mail->DKIM_domain = WPT_DKIM_DOMAIN;
          $mail->DKIM_private = __DIR__.'/../dkim/dkim.private';
          $mail->DKIM_selector = WPT_DKIM_SELECTOR;
          $mail->DKIM_passphrase = '';
          $mail->DKIM_identity = $mail->From;
        }

        $mail->Body =
          $args['msg'].
          "\n\n"._("The wopits team,")."\n\n--\n".
          _("This message was sent automatically.")."\n".WPT_URL;

        $mail->send ();
      }
      catch (Exception $e)
      {
        throw new Exception (
          "Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
      }
    }

    public static function getWopitsVersion ()
    {
      //<WPTPROD-remove>
      if (WPT_DEV_MODE)
        return date ('U');
      else
      //</WPTPROD-remove>
        return WPT_VERSION;
    }

    public static function getSecureSystemName ($name)
    {
      do
      {
        $old = $name;
        $name = preg_replace (
                 ['#[^a-z0-9/_\-\.]#i', '#([/\.])\1+#'],
                 ['', '$1'], $name);
      }
      while ($old != $name);

      return (strpos ($name, WPT_DATA_SPATH) === 0) ?
               $name : WPT_DATA_SPATH."/$name";
    }

    public static function deleteCookie ()
    {
      // 24 * 3600 == 86400
      setcookie (WPT_COOKIE, '', time () - 86400, '/', null, true, true);
    }

    public static function setCookie ($value)
    {
      setcookie (WPT_COOKIE, $value, mktime (0, 0, 0, 1, 1, 2035),
                 '/', null, true, true);
    }


    public static function getCookie ()
    {
      return (preg_match ('/'.WPT_COOKIE.'=([^;]+)/',
              $_SERVER['HTTP_COOKIE'] ?? '', $m)) ? $m[1] : '';
    }

    public static function getBrowserLocale ()
    {
      $l = $GLOBALS['Accept-Language'] ??
           $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? null;

      if ($l && preg_match_all ('/([a-z]{2})[,-;$]/', $l, $m))
      {
        foreach ($m[1] as $browserLocale)
        {
          if (isset (WPT_LOCALES[$browserLocale]))
            return $browserLocale;
        }
      }

      return WPT_DEFAULT_LOCALE;
    }

    public static function download ($args)
    {
      header ('Content-Description: File Transfer');
      header ("Content-Type: {$args['type']}");
      header ('Expires: '.gmdate('D, d M Y H:i:s').' GMT');
      header ('Content-Disposition: attachment; filename="'.
        preg_replace('/[^\.a-z0-9\-_\()\[\]:\s]+/i', '_', $args['name']).'"');
      header ('Pragma: no-cache');
      header ("Content-Length: {$args['size']}");

      flush ();
    
      readfile ($args['path']);

      if ($args['unlink']??false)
        unlink ($args['path']);

      exit;
    }

    public static function getImgFromMime ($mime)
    {
      foreach ([
        // Media
        'image' => 'fa-file-image',
        'audio' => 'fa-file-audio',
        'video' => 'fa-file-video',
        // Documents
        'pdf' => 'fa-file-pdf',
        'word' => 'fa-file-word',
        'opendocument' => 'fa-file-word',
        'wordprocessingml' => 'fa-file-word',
        'excel' => 'fa-file-excel',
        'csv' => 'fa-file-csv',
        'spreadsheet' => 'fa-file-excel',
        'powerpoint' => 'fa-file-powerpoint',
        'presentation' => 'fa-file-powerpoint',
        // Archives
        'zip' => 'fa-file-archive',
        'compressed' => 'fa-file-archive',
        // Code
        'text/html' => 'fa-file-code',
        'json' => 'fa-file-code',
        'php' => 'fa-file-code',
        'perl' => 'fa-file-code',
        'sql' => 'fa-file-code',
        'python' => 'fa-file-code',
        'java' => 'fa-file-code'] as $text => $icon)
      {
        if (strpos ($mime, $text) !== false)
          return $icon;
      }
    
      return 'fa-file';
    }

    public static function checkRealFileType ($filename, $name = null,
                                              $_imagick = null)
    {
      $imagick = ($_imagick) ? $_imagick : new Imagick ($filename);

      $mime = $imagick->getImageMimeType ();
      $ext = explode('/', $mime)[1];
      $newFilename = substr($filename, 0, strrpos($filename, '.')).".$ext";

      if ($newFilename != $filename)
      {
        rename ($filename, $newFilename);
        $filename = $newFilename;

        if ($name)
          $name = substr($name, 0, strrpos($name, '.')).".$ext";
      }

      if (!$_imagick)
        $imagick->destroy ();

      return [$filename, $mime, $name];
    }

    public static function resizePicture ($filename,
                                          $newWidth , $newHeight = 0,
                                          $force = true)
    {
      $imagick = new Imagick ($filename);

      list ($filename, $mime) =
        self::checkRealFileType ($filename, null, $imagick);

      $dim = $imagick->getImageGeometry ();

      if ($force || !$force && $dim['width'] > $newWidth)
      {
        $imagick->scaleimage ($newWidth, $newHeight);

        $imagick->writeImage ($filename);

        $dim = $imagick->getImageGeometry ();

        $imagick->destroy ();
      }

      return [$filename, $mime, $dim['width'], $dim['height']];
    }
  }
