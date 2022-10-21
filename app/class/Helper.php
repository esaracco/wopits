<?php

namespace Wopits;

class Helper {
  private static function _sendToWSServer(array $data):bool {
    $ret = false;

    try {
      $client = new Services\WebSocket\Client('127.0.0.1', WPT_WS_PORT);

      if (@$client->connect()) {
        $client->send(json_encode($data));
        $ret = true;
      } else {
        throw new \Exception();
      }
    } catch(\Exception $e) {
      error_log(__METHOD__.':'.__LINE__.
                ":Error while sending msg to WS server (".
                print_r($data, true).").");
    }

    return $ret;
  }

  // Keep WS connection and database persistent connection alive 
  public static function ping():bool {
    return self::_sendToWSServer(['action' => 'ping']);
  }

  public static function sendToWSServer(array $data):bool {
    return self::_sendToWSServer($data);
  }

  public static function changeLocale(string $slocale):string {
    $locale = $slocale.'_'.(($slocale === 'en') ? 'US' : strtoupper($slocale));

    putenv("LANGUAGE=$locale.UTF-8");
    bindtextdomain('wopits', __DIR__.'/../locale');
    textdomain('wopits');
    setlocale(LC_ALL, "$locale.UTF-8");
    bind_textdomain_codeset('wopits', 'UTF-8');

    return $locale;
  }

  public static function getsLocale(User $User = null):string {
    $slocale = '';

    if (isset($_SESSION['slocale'])) {
      $slocale = $_SESSION['slocale'];
    } elseif (!empty($User->userId)) {
      $slocale = @(json_decode($User->getSettings()))->locale;
    }

    if (!$slocale) {
      $slocale = self::getBrowserLocale();
    }

    return $slocale;
  }

  public static function unaccent(string $str):string {
    //FIXME iconv does not work here with user search.
    //return strtolower(iconv('utf-8', 'ascii//TRANSLIT', $str));

    return
      strtolower(
        preg_replace(['#&lt;#', '#&gt;#', '#&[^;]+;#'], ['<', '>', ''],
          preg_replace('#&([A-za-z]{2})(?:lig);#', '\1',
            preg_replace(
              '#&([A-za-z])(?:acute|cedil|caron|circ|grave|orn|ring|'.
              'slash|th|tilde|uml);#', '\1',
              htmlentities($str, ENT_NOQUOTES, 'utf-8')))));
  }

  public static function rm(string $path, bool $firstCall = true):bool {
    // First call? -> check the path
    if ($firstCall) {
      $path = self::getSecureSystemName($path);

      // Delete only under the data directory and not the data directory
      // itself or an external path
      if (!preg_match('#'.WPT_DATA_SPATH.'/.#', $path)) {
        return error_log(__METHOD__.':'.__LINE__.
                            ":Forbidden root path `$path`.");
      }

      // If the item does not exists, return
      if (!file_exists($path)) {
        return false;
      }

      // If the item to delete is a file (and not directory) and return
      if (is_file($path)) {
        return unlink($path);
      }
    }

    foreach (scandir($path) as $file) {
      if ($file !== '.' && $file !== '..') {
        if (is_dir("$path/$file")) {
          self::rm("$path/$file", false);
        } else {
          unlink("$path/$file");
        }
      }
    }

    return rmdir($path);
  }


  public static function getWopitsVersion():string {
    //<WPTPROD-remove>
    if (WPT_DEV_MODE)
      return date('U');
    else
    //</WPTPROD-remove>
      return WPT_VERSION;
  }

  public static function getSecureSystemName(string $name):string {
    do {
      $old = $name;
      $name = preg_replace(
                 ['#[^a-z0-9/_\-\.]#i', '#([/\.])\1+#'],
                 ['', '$1'], $name);
    } while ($old !== $name);

    return (strpos($name, WPT_DATA_SPATH) === 0) ?
              $name : WPT_DATA_SPATH."/$name";
  }

  public static function deleteCookie() {
    setCookie('wopits', '', [
      // 24 * 3600 == 86400
      'expires' => time() - 86400,
      'path' => '/',
      'secure' => boolval(WPT_USE_HTTPS),
      'httponly' => true,
      'samesite' => 'Strict',
    ]);
  }

  public static function setCookie(string $value):void {
    setCookie('wopits', $value, [
      'expires' => mktime(0, 0, 0, 1, 1, 2035),
      'path' => '/',
      'secure' => boolval(WPT_USE_HTTPS),
      'httponly' => true,
      'samesite' => 'Strict',
    ]);
  }

  public static function getCookie():string {
    return (preg_match('/wopits=([^;]+)/',
            $_SERVER['HTTP_COOKIE'] ?? '', $m)) ? $m[1] : '';
  }

  public static function getBrowserLocale():string {
    $l = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? null;

    if ($l && preg_match_all('/([a-z]{2})[,-;$]/', $l, $m)) {
      foreach ($m[1] as $browserLocale) {
        if (isset(WPT_LOCALES[$browserLocale])) {
          return $browserLocale;
        }
      }
    }

    return WPT_DEFAULT_LOCALE;
  }

  public static function download(array $args):void {
    $itemType = $args['item_type'];

    // Tell our client API that this file is not available anymore
    if ($itemType === '404') {
      header("Content-Type: $itemType");
    } else {
      header('Content-Description: File Transfer');
      header("Content-Type: $itemType");
      header('Expires: '.gmdate('D, d M Y H:i:s').' GMT');
      header('Content-Disposition: attachment; filename="'.
        preg_replace('/[^\.a-z0-9\-_\()\[\]:\s]+/i', '_', $args['name']).'"');
      header('Pragma: no-cache');
      header("Content-Length: {$args['size']}");

      flush();

      readfile($args['path']);

      if ($args['unlink'] ?? false) {
        unlink($args['path']);
      }
    }

    exit;
  }

  public static function getImgFromMime(string $mime):string {
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
      'java' => 'fa-file-code',
    ] as $text => $icon) {
      if (strpos($mime, $text) !== false) {
        return $icon;
      }
    }
  
    return 'fa-file';
  }

  public static function checkRealFileType(string $filename,
                                           string $name = null,
                                           \Imagick $_imagick = null):array {
    $imagick = $_imagick ? $_imagick : new \Imagick($filename);

    $mime = $imagick->getImageMimeType();
    $ext = explode('/', $mime)[1];
    $newFilename = mb_substr($filename, 0, mb_strrpos($filename, '.')).".$ext";

    if ($newFilename !== $filename) {
      rename($filename, $newFilename);
      $filename = $newFilename;

      if ($name) {
        $name = mb_substr($name, 0, mb_strrpos($name, '.')).".$ext";
      }
    }

    if (!$_imagick) {
      $imagick->destroy();
    }

    return [$filename, $mime, $name];
  }

  public static function resizePicture(string $filename,
                                       int $newWidth , int $newHeight = 0,
                                       bool $force = true):array {
    $imagick = new \Imagick($filename);

    list($filename, $mime) = self::checkRealFileType($filename, null, $imagick);

    $dim = $imagick->getImageGeometry();

    if ($force || !$force && $dim['width'] > $newWidth) {
      $imagick->scaleimage($newWidth, $newHeight);
      $imagick->writeImage($filename);

      $dim = $imagick->getImageGeometry();

      $imagick->destroy();
    }

    return [$filename, $mime, $dim['width'], $dim['height']];
  }

  public static function getIncludeContent(string $file):string {
    ob_start();
    require($file);
    return ob_get_clean();
  }

  public static function isBlacklistedDomain(string $email):?string {
    return (defined('WPT_BLACKLISTED_DOMAINS') &&
            !empty(WPT_BLACKLISTED_DOMAINS) &&
            preg_match('/'.WPT_BLACKLISTED_DOMAINS.'/', $email)) ?
      sprintf(_("Please, use another email! We have blacklisted the following domains: %s."), str_replace('|', ', ', WPT_BLACKLISTED_DOMAINS)) : null;
  }

  //<WPTPROD-remove>
  public static function buildPostitMenu() {
    // Post-it menu definition
    $items = [
      ['delete', _('Remove'), 'trash'],
      ['edit', _('Edit'), 'edit'],
      ['cpick', _('Background color'), 'palette'],
      ['dpick', _('Deadline'), 'hourglass-end'],
      ['tpick', _('Tags'), 'tags'],
      ['pwork', _('Users involved'), 'users-cog'],
      ['pcomm', _('Comments'), 'comments'],
      ['patt', _('Attached files'), 'paperclip'],
      ['add-plug', _('Add relation'), 'bezier-curve'],
    ];

    $menu = '<div class="postit-menu right">';
    foreach ($items as $d) {
      $menu .= "<span data-action=\"{$d[0]}\" class=\"btn btn-sm btn-secondary btn-circle\" title=\"{$d[1]}\"><i class=\"fa-{$d[2]} fas fa-fw\"></i></span>";
    }
    $menu .= '</div>';

    return $menu;
  }
  //</WPTPROD-remove>
}
