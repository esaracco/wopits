<?php

namespace Wopits;

require_once (__DIR__.'/../config.php');

use Wopits\{Services\Task, Wall\Group};

class User extends Base
{
  public $settings;

  public function logout ():array
  {
    $ret = [];

    try
    {
      $this
        ->prepare('DELETE FROM users_tokens WHERE users_id = ?')
        ->execute ([$this->userId]);

      session_destroy ();
      session_start ();

      Helper::deleteCookie ();

      $this->userId = null;
    }
    catch (\Exception $e)
    {
      error_log (__METHOD__.':'.__LINE__.':'.$e->getMessage ());
      $ret['error'] = 1;
    }

    return $ret;
  }

  public function getUnixDate (string $dt):string
  {
    $oldTZ = date_default_timezone_get ();

    date_default_timezone_set ($this->getTimezone ());
    $ret = strtotime ($dt);
    date_default_timezone_set ($oldTZ);

    return $ret;
  }

  public function getDate (int $dt, string $tz = null,
                           string $fmt = '%Y-%m-%d'):string
  {
    $oldTZ = date_default_timezone_get ();
    $newTZ = $tz ?? $this->getTimezone ();

    if ($newTZ != $oldTZ)
    {
      date_default_timezone_set ($newTZ);
      $ret = strftime ($fmt, $dt);
      date_default_timezone_set ($oldTZ);
    }
    else
      $ret = strftime ($fmt, $dt);

    return $ret;
  }

  public function resetPassword ():array
  {
    $ret = [];
    $password = $this->_generatePassword ();

    try
    {
      ($stmt = $this->prepare ('
        SELECT id, username, fullname FROM users WHERE email = ?'))
         ->execute ([$this->data->email]);

      if ($r = $stmt->fetch ())
      {
        $this->executeQuery ('UPDATE users', [
          'password' => hash ('sha1', $password),
          'updatedate' => time ()
        ],
        ['id' => $r['id']]);

        (new Task())->execute ([
          'event' => Task::EVENT_TYPE_SEND_MAIL,
          'method' => 'resetPassword',
          'userId' => $r['id'],
          'email' => $this->data->email,
          'username' => $r['username'],
          'fullname' => $r['fullname'],
          'password' => $password
        ]);
      }
    }
    catch (\Exception $e)
    {
      error_log (__METHOD__.':'.__LINE__.':'.$e->getMessage ());
      $ret['error'] = 1;
    }

    return $ret;
  }

  public function getUser ():array
  {
    $ret = [];

    ($stmt = $this->prepare ('
      SELECT email, username, fullname, about, visible, picture FROM users
      WHERE id = ?'))
       ->execute ([$this->userId]);
    
    if (! ($ret = $stmt->fetch ()))
      $ret['error'] = _("Unable to retrieve your account information");

    return $ret;
  }

  public function getUserDataJson ():string
  {
    return json_encode ($this->userId ?
      [
        'id' => $this->userId,
        'settings' => $this->getSettings (false),
        'walls' => (new Wall (['userId' => $this->userId],
                                $this->ws))->getWall (),
        'token' => $_SESSION['userToken']??''
      ]
      :
      [
        'id' => 0,
        'settings' => [],
        'walls' => []
      ]);
  }

  public function exists (int $id = null):int
  {
    ($stmt = $this->prepare ('
      SELECT visible FROM users WHERE id = ? AND visible = 1'))
       ->execute ([$id??$this->userId]);

    return $stmt->rowCount ();
  }

  public function delete ():array
  {
    $ret = [];
    $dir = $this->getUserDir ();

    // Close opened user's walls opened by others.
    $tmp = (new Wall (['userId' =>
              $this->userId]))->getWall (false, false, true);
    if (!empty ($tmp['list']))
    {
      $wallIds = [];
      foreach ($tmp['list'] as $_wall)
        $wallIds[] = $_wall['id'];

      // Push WS close request.
      $this->sendWsClient ([
        'action' => 'close-walls',
        'userId' => $this->userId,
        'ids' => $wallIds
      ]);
    }

    try
    {
      $this->beginTransaction ();

      // No SQL CASCADE here. We delete attachments manually and decrement
      // postit attachments count.
      ($stmt = $this->prepare ('
        SELECT id, postits_id
        FROM postits_attachments WHERE users_id = ?'))
         ->execute ([$this->userId]);
      while ($r = $stmt->fetch ())
      {
        $this->exec ("
          DELETE FROM postits_attachments WHERE id = {$r['id']}");
        $this->exec ("
          UPDATE postits SET attachmentscount = attachmentscount - 1
          WHERE id = {$r['postits_id']}");
      }

      // Decrement userscount from user's groups.
      $this
        ->prepare('
          UPDATE groups SET userscount = userscount - 1
          WHERE id IN (
            SELECT groups_id FROM _perf_walls_users WHERE users_id = ?)')
        ->execute ([$this->userId]);

      // Remove user's walls directories.
      ($stmt = $this->prepare ('
        SELECT id FROM walls WHERE users_id = ?'))->execute ([$this->userId]);
      while ($item = $stmt->fetch ())
      {
        $this->wallId = $item['id'];
        Helper::rm ($this->getWallDir ());
      }

      // Delete user
      $this
        ->prepare('DELETE FROM users WHERE id = ?')
        ->execute ([$this->userId]);

      $this->commit ();

      $this->logout ();

      Helper::rm ($dir);
    }
    catch (\Exception $e)
    {
      $this->rollback ();

      error_log (__METHOD__.':'.__LINE__.':'.$e->getMessage ());
      $ret['error'] = 1;
    }
  
    return $ret;
  }

  public function checkSession ():void
  {
    ($stmt = $this->prepare('SELECT 1 FROM users_tokens WHERE users_id = ?'))
      ->execute ([$_SESSION['userId']]);

    if (!$stmt->fetch ())
      $this->logout ();
  }

  public function loginByCookie ():bool
  {
    if ( ($token = Helper::getCookie ()) )
    {
      ($stmt = $this->prepare ('
        SELECT
          users_id,
          users.settings
        FROM users_tokens
          INNER JOIN users ON users_tokens.users_id = users.id
        WHERE expiredate IS NOT NULL
          AND token = ?'))
         ->execute ([hash ('sha1', $_SERVER['REMOTE_ADDR']).$token]);

      if ($r = $stmt->fetch ())
      {
        $this->userId = $r['users_id'];
        $this->register (@json_decode($r['settings']));

        $_SESSION['userToken'] = $token;

        $this->refreshUpdateDate ();

        return true;
      }
    }

    return false;
  }

  // Return type is mixed: array or false.
  public function loadByToken (string $token, string $ip)
  {
    ($stmt = $this->prepare ('
      SELECT
        users_id,
        users.username
      FROM users_tokens
        INNER JOIN users ON users_tokens.users_id = users.id
      WHERE token = ? LIMIT 1'))
       ->execute ([hash ('sha1', $ip).$token]);

    if ( ($ret = $stmt->fetch ()) )
      $this->userId = $ret['users_id'];

    return $ret;
  }

  public function register (object $settings = null):void
  {
    $this->executeQuery ('UPDATE users',
      ['lastconnectiondate' => time ()],
      ['id' => $this->userId]);

    // All were OK, user is logged
    $_SESSION['userId'] = $this->userId;

    if ($settings)
    {
      if (isset ($settings->locale))
        $_SESSION['slocale'] = $settings->locale;

      $this->settings = (array) $settings;
    }
  }

  public function refreshUpdateDate ():void
  {
    $this->executeQuery ('UPDATE users',
      ['updatedate' => time ()],
      ['id' => $this->userId]);
  }

  public function purgeTokens ():void
  {
    $current = time ();
    $diff = 30 * 60; // 30mn

    $this->exec ("
      DELETE FROM users_tokens
      WHERE token IN (
        SELECT token
        FROM users_tokens
        WHERE expiredate IS NOT NULL
          AND expiredate <= $current

        UNION

        SELECT token
        FROM users_tokens AS ut
          INNER JOIN users ON users.id = ut.users_id
        WHERE ut.expiredate IS NULL
          AND $current - users.updatedate > $diff)");
  }

  public function createUpdateLdapUser (array $args):?array
  {
    $fromScript = isset ($args['fromScript']);
    $data = null;

    ($stmt = $this->prepare ('
      SELECT id, settings FROM users WHERE username = ?'))
       ->execute ([$args['username']]);
    $data = $stmt->fetch ();

    // If user account has not been yet created on wopits, silently
    // create it (user will not receive creation email).
    if (!$data)
    {
      if ($this->_isDuplicate (['email' => $args['mail']]))
        return ['error_msg' => sprintf (_("Another account with the same email as the LDAP account email `%s` already exists on wopits!"), $args['mail'])];

      $this->data = (object)[
        'email' => $args['mail'],
        'username' => $args['username'],
        'fullname' => $args['cn']??'',
        'password' => $args['password']
      ];
      if (empty ($this->create (!$fromScript)))
      {
        $data = ['id' => $this->userId];

        if ($fromScript)
          echo "CREATE {$args['username']}, {$args['mail']}\n";
      }
    }
    // Update local password with LDAP password.
    else
    {
      $this->executeQuery ('UPDATE users',
        ['password' => hash ('sha1', $args['password'])],
        ['id' => $data['id']]);

      if ($fromScript)
        echo "UPDATE {$args['username']}, {$args['mail']}\n";
    }

    return $data;
  }

  public function login (bool $remember = false):array
  {
    $ret = [];
    $data = null;

    // If we must use LDAP, get user infos in LDAP and bind with its
    // password.
    if (WPT_USE_LDAP)
    {
      $Ldap = new Ldap ();

      if (!$Ldap->connect ())
        return ['error_msg' => _("Can't contact LDAP server!")];

      if (!($ldapData = $Ldap->getUserData ($this->data->username)))
        return ['error_msg' => _("Your connection attempt failed!")];

      // If user has been found in LDAP, try to bind with its password.
      if ($Ldap->bind ($ldapData['dn'], $this->data->password))
      {
        if  (empty ($ldapData['mail']))
          return ['error_msg' => _("No email address is configured in your LDAP account. Please fix the problem before logging in again on wopits.")];

        $data = $this->createUpdateLdapUser ([
          'username' => $this->data->username,
          'password' => $this->data->password,
          'mail' => $ldapData['mail'],
          'cn' => $ldapData['cn']
        ]);
      }
    }
    else
    {
      ($stmt = $this->prepare ('
        SELECT id, settings FROM users WHERE username = ? AND password = ?'))
         ->execute ([
           $this->data->username,
           hash ('sha1', $this->data->password)
         ]);
      $data = $stmt->fetch ();
    }

    // User not found
    if (empty ($data))
      return ['error_msg' => _("Your connection attempt failed!")];

    $this->userId = $data['id'];

    try
    {
      $this->_createToken ($data['settings']??null, $remember);

      $this->refreshUpdateDate ();
    }
    catch (\Exception $e)
    {
      error_log (__METHOD__.':'.__LINE__.':'.$e->getMessage ());
      $ret['error'] = 1;
    }

    return $ret;
  }

  public function getSettings (bool $json = true)
  {
    ($stmt = $this->prepare ('SELECT settings FROM users WHERE id = ?'))
      ->execute ([$this->userId]);

    $ret = ( ($ret = $stmt->fetch ()) ) ? $ret['settings'] : '[]';

    if (strpos ($ret, '"timezone":') === false)
    {
      $ret = json_decode ($ret);
      $ret->timezone = $this->getTimezone ();
      $ret = json_encode ($ret);
      $this->saveSettings ($ret); 
    }
    
    return $json ? $ret : json_decode ($ret);
  }

  public function getSetting (string $key):string
  {
    $userId = $this->userId??$_SESSION['userId']??null;

    if ($userId && !isset ($this->settings[$key]))
    {
      ($stmt = $this->prepare ('SELECT settings FROM users WHERE id = ?'))
        ->execute ([$userId]);
      if ( ($r = $stmt->fetch ()) )
        $this->settings = (array) @json_decode($r['settings']);
    }

    return $this->settings[$key]??'';
  }

  public function getTimezone ():string
  {
    return (empty ($ret = $this->getSetting ('timezone'))) ?
              WPT_LOCALES[$this->slocale] : $ret;
  }

  public function saveSettings (string $settings = null):array
  {
    $settings = $settings ?? $this->data->settings;
    $ret = [];

    try
    {
      $this->executeQuery ('UPDATE users',
        ['settings' => $settings],
        ['id' => $this->userId]);

      $this->settings = (array) json_decode($settings);
    }
    catch (\Exception $e)
    {
      error_log (__METHOD__.':'.__LINE__.':'.$e->getMessage ());
      $ret['error'] = 1;
    }

    return $ret;
  }

  public function getWallSettings (int $wallId):object
  {
    ($stmt = $this->prepare ('
      SELECT settings FROM _perf_walls_users
      WHERE users_id = ? AND walls_id = ?'))
        ->execute ([$this->userId, $wallId]);

    return json_decode ($stmt->fetch (\PDO::FETCH_COLUMN));
  }

  public function saveWallSettings (int $wallId, object $settings):array
  {
    $ret = [];

    try
    {
      $this->executeQuery ('UPDATE _perf_walls_users',
        ['settings' => json_encode ($settings)],
        ['users_id' => $this->userId, 'walls_id' => $wallId]);
    }
    catch (\Exception $e)
    {
      error_log (__METHOD__.':'.__LINE__.':'.$e->getMessage ());
      $ret['error'] = 1;
    }

    return $ret;
  }

  public function setWallSettings (int $wallId):array
  {
    $key = $this->data->key;
    $value = $this->data->value;

    $settings = $this->getWallSettings ($wallId);
    $settings->$key = $value;

    return $this->saveWallSettings ($wallId, $settings);
  }

  public function setWallDisplayMode (int $wallId):array
  {
    $ret = [];
    $displayMode = $this->data->display;

    try
    {
      $this->executeQuery ('UPDATE _perf_walls_users',
        ['displaymode' => $displayMode],
        ['users_id' => $this->userId, 'walls_id' => $wallId]);

      // Update settings for all cells
      if (!empty ( (array)($settings = $this->getWallSettings ($wallId)) ))
      {
        // If postit mode (standard mode), remove keys
        $delete = ($displayMode == 'postit-mode');
        foreach ($settings as $key => $value)
        {
          if (strpos ($key, 'cell') !== false)
          {
            if ($delete)
              unset ($settings->$key);
            else
              $settings->$key->displaymode = $displayMode;
          }
        }
        $this->saveWallSettings ($wallId, $settings);
      }
    }
    catch (\Exception $e)
    {
      error_log (__METHOD__.':'.__LINE__.':'.$e->getMessage ());
      $ret['error'] = 1;
    }

    return $ret;
  }

  public function setWallExternalRef (int $wallId):array
  {
    $ret = [];

    try
    {
      $this->executeQuery ('UPDATE _perf_walls_users',
        ['displayexternalref' => intval ($this->data->display)],
        ['users_id' => $this->userId, 'walls_id' => $wallId]);
    }
    catch (\Exception $e)
    {
      error_log (__METHOD__.':'.__LINE__.':'.$e->getMessage ());
      $ret['error'] = 1;
    }

    return $ret;
  }

  public function getPicture (array $args):?array
  {
    $userId = $args['userId'];

    if (!$this->userId)
      return ['error' => _("Access forbidden")];

    ($stmt = $this->prepare ('
      SELECT picture, filetype, filesize FROM users WHERE id = ?'))
       ->execute ([$userId]);

    if ( ($r = $stmt->fetch ()) )
      Helper::download ([
        'item_type' => $r['filetype'],
        'name' => basename ($r['picture']),
        'size' => $r['filesize'],
        'path' => WPT_ROOT_PATH.$r['picture']
      ]);
  }

  public function deletePicture ():array
  {
    $ret = [];

    if (!$this->userId)
      return ['error' => _("Access forbidden")];

    try
    {
      ($stmt = $this->prepare ('SELECT picture FROM users WHERE id = ?'))
        ->execute ([$this->userId]);
      $r = $stmt->fetch ();

      $this->executeQuery ('UPDATE users', [
        'picture' => null,
        'filetype' => null,
        'filesize' => null
      ],
      ['id' => $this->userId]);

      Helper::rm (WPT_ROOT_PATH.$r['picture']);
    }
    catch (\Exception $e)
    {
      error_log (__METHOD__.':'.__LINE__.':'.$e->getMessage ());
      $ret['error'] = 1;
    }

    return $ret;
  }

  public function updatePicture ():array
  {
    $ret = [];

    if (!$this->userId)
      return ['error' => _("Access forbidden")];

    list ($ext, $content, $error) = $this->getUploadedFileInfos ($this->data);

    if ($error)
      $ret['error'] = $error;
    else
    {
      try
      {
        $dir = $this->getUserDir ();
        $wdir = $this->getUserDir ('web');

        $file = Helper::getSecureSystemName (
          "$dir/img-".hash('sha1', $this->data->content).".$ext");

        file_put_contents (
          $file, base64_decode(str_replace(' ', '+', $content)));

        if (!file_exists ($file))
          throw new \Exception (_("An error occured while uploading file."));

        ($stmt = $this->prepare ('SELECT picture FROM users WHERE id = ?'))
          ->execute ([$this->userId]);
        $previousPicture = $stmt->fetch()['picture'];

        list ($file, $this->data->item_type) =
          Helper::resizePicture ($file, 200);

        $img = "$wdir/".basename($file);
        $this->executeQuery ('UPDATE users', [
          'picture' => $img,
          'filetype' => $this->data->type,
          'filesize' => filesize ($file)
        ],
        ['id' => $this->userId]);

        // delete old picture if needed
        if ($previousPicture && $previousPicture != $img)
          Helper::rm (WPT_ROOT_PATH.$previousPicture);

        $ret = ['src' => $img];
      }
      catch (\ImagickException $e)
      {
        @unlink ($file);

        if ($e->getCode () == 425)
          return ['error' => _("The file type was not recognized.")];
        else
        {
          error_log (__METHOD__.':'.__LINE__.':'.$e->getMessage ());
          throw $e;
        }
      }
      catch (\Exception $e)
      {
        @unlink ($file);

        error_log (__METHOD__.':'.__LINE__.':'.$e->getMessage ());
        throw $e;
      }
    }

    return $ret;
  }

  public function update ():array
  {
    $ret = [];

    try
    {
      if (!isset ($this->data->password))
      {
        $data = (array) $this->data;
        $field = preg_replace('/^[^a-z]+$/', '', array_keys($data)[0]);
        $value = $data[$field];

        if (!isset ($data['about']) && !isset ($data['visible']) &&
            ($dbl = $this->_isDuplicate ([$field => $value])) )
          $ret['error_msg'] = sprintf (($dbl == 'username') ?
            _("The login `%s` already exists.") :
            _("The email `%s` already exists."), $value);
        else
        {
          $this->beginTransaction ();

          $this->checkDBValue ('users', $field, $value);
          $this
            ->prepare("UPDATE users SET $field = :$field WHERE id = :id")
            ->execute ([$field => $value, 'id' => $this->userId]);

          ($stmt = $this->prepare ('
            SELECT username, fullname, email, about, visible, picture
            FROM users where id = ?'))
             ->execute ([$this->userId]);
          $ret = $stmt->fetch ();

          if ($field == 'visible')
          {
            $settings = json_decode ($this->getSettings ());
            $settings->visible = $value;
            $this->saveSettings (json_encode ($settings));
          }
          else
            $this->executeQuery ('UPDATE users',
              ['searchdata' =>
                Helper::unaccent ($ret['username'].','.$ret['fullname'])],
              ['id' => $this->userId]);

          $this->commit ();

          // Remove user from all groups except his own.
          if ($field == 'visible' && $value == 0)
          {
            // Deassociate user from all groups and user's groups users too.
            (new Group(['userId' => $this->userId], $this->ws))
              ->unlinkUserFromOthersGroups ();

            // Get all user's walls to disconnect users from them.
            ($stmt = $this->prepare ('
              SELECT id FROM walls WHERE users_id = ?'))
               ->execute ([$this->userId]);
            if (!empty ( ($r = $stmt->fetchAll (\PDO::FETCH_COLUMN)) ))
              $ret['closewalls'] = $r;
          }
        }
      }
      else
      {
        $pwd = $this->data->password;

        ($stmt = $this->prepare ('
          SELECT id FROM users WHERE password = ? AND id = ?'))
           ->execute ([hash ('sha1', $pwd->current), $this->userId]);
        if (!$stmt->fetch ())
          throw new \Exception (_("Wrong current password."));

        $this->executeQuery ('UPDATE users',
         ['password' => hash ('sha1', $pwd->new)],
         ['id' => $this->userId]);
      }
    }
    catch (\Exception $e)
    {
      $msg = $e->getMessage ();

      if (\PDO::inTransaction ())
        $this->rollback ();

      error_log (__METHOD__.':'.__LINE__.':'.$msg);
      $ret['error_msg'] = $msg;
    }

    return $ret;
  }

  private function _createToken (string $settings = null,
                                 bool $remember = false)
  {
    session_regenerate_id ();

    $token = hash ('sha1',
      $this->userId.$this->data->username.$this->data->password);

    $this
      ->prepare('DELETE FROM users_tokens WHERE users_id = ?')
      ->execute ([$this->userId]);

    $this->executeQuery ('INSERT INTO users_tokens', [
      'creationdate' => time(),
      'users_id' => $this->userId,
      'token' => hash ('sha1', $_SERVER['REMOTE_ADDR']).$token,
      'expiredate' => ($remember) ? time() + 3600*24*7 : null
    ]);

    if ($remember)
      Helper::setCookie ($token);

    $this->register (@json_decode($settings));

    $_SESSION['userToken'] = $token;
  }

  public function create (bool $createToken = true):array
  {
    $ret = [];

    // Check for SPAM only if standard auth mode
    if (!WPT_USE_LDAP)
    {
      $checkRef = $_SESSION['_check']??null;
      $checkCompare = $this->data->_check??null;

      if (is_null ($checkRef) || is_null ($checkCompare) ||
          $checkCompare != $checkRef ||
          (time() - $checkCompare) < 10)
      {
        $this->logout ();
        error_log ("SPAM detection");
        return ['error' => _("The account creation forms were filled out too quickly. Please reload this page and try again to verify that you are not a robot...")];
      }
    }

    try
    {
      // Check for duplicate (username or email)
      if ( ($dbl = $this->_isDuplicate ([
              'username' => $this->data->username,
              'email' => $this->data->email])) )
        throw new \Exception (($dbl == 'username') ?
          sprintf (_("The login `%s` already exists."),
                     $this->data->username) :
          sprintf (_("The email `%s` already exists."),
                     $this->data->email));

      // Create user
      $currentDate = time ();
      $this->executeQuery ('INSERT INTO users', [
        'email' => $this->data->email,
        'password' => hash ('sha1', $this->data->password),
        'username' => $this->data->username,
        'fullname' => $this->data->fullname,
        'searchdata' => Helper::unaccent (
                          "{$this->data->username},{$this->data->fullname}"),
        'creationdate' => $currentDate,
        'updatedate' => $currentDate,
        'lastconnectiondate' => $currentDate,
        'settings' => json_encode ([
          'locale' => $_SESSION['slocale']??WPT_DEFAULT_LOCALE,
          'timezone' => $this->getTimezone (),
          'visible' => 1
        ])
      ]);

      $this->userId = $this->lastInsertId ();

      // All is OK, user is logged
      $_SESSION['userId'] = $this->userId;

      // Send account creation email only in standard auth mode.
      if (!WPT_USE_LDAP)
        (new Task())->execute ([
          'event' => Task::EVENT_TYPE_SEND_MAIL,
          'method' => 'accountCreation',
          'userId' => $this->userId,
          'email' => $this->data->email,
          'username' => $this->data->username,
          'fullname' => $this->data->fullname
        ]);

      mkdir ("{$this->getUserDir()}/tmp", 02770, true);

      if ($createToken)
        $this->_createToken ();
    }
    catch (\Exception $e)
    {
      $msg = $e->getMessage ();

      error_log (__METHOD__.':'.__LINE__.':'.$msg);

      if (!WPT_USE_LDAP)
        $ret['error_msg'] = $msg;
    }

    return $ret;
  }

  private function _generatePassword ():string
  {
    // Randomize letters pool order
    // -> No "I", "l" nor "0" to prevent user reading errors.
    $chars =
      str_split ('abcdefghijkmnpqrstuxyzABCDEFGHJKLMNPQRSTUXYZ23456789');
    shuffle ($chars);
    $chars = implode ($chars);
    $len = strlen($chars) - 1;

    // Build 8 letters password with lowercase, uppercase and number
    do
    {
      for ($i = 0, $result = ''; $i < 8; $i++)
        $result .= $chars{rand (0, $len)};
    }
    while (!preg_match ('/[a-z]/', $result) ||
           !preg_match ('/[A-Z]/', $result) ||
           !preg_match ('/[0-9]/', $result));

    return $result;
  }

  private function _isDuplicate (array $args):?string
  {
    $ret = null;
    $keys = array_keys ($args);
    $data = [];

    $where = ' ( ';
    foreach ($args as $k => $v)
    {
      $where .= " $k = :$k OR ";
      $data[":$k"] = $v;
    }
    $where .= ")";
    $where = str_replace ('OR )', ')', $where);

    if ($this->userId)
    {
      $where .= ' AND id <> :id ';
      $data[':id'] = $this->userId;
    }

    // Check for duplicate (username or email)
    ($stmt = $this->prepare ("
      SELECT ".implode(',', $keys)." FROM users WHERE $where"))
       ->execute ($data);
    
    if ($dbl = $stmt->fetch ())
    {
      foreach ($dbl as $k => $v)
      {
        if ($data[":$k"] == $v)
        {
          $ret = $k;
          break;
        }
      }
    }

    return $ret;
  }
}
