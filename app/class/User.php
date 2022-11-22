<?php

namespace Wopits;

require_once(__DIR__.'/../config.php');

use Wopits\{Services\Task, Wall\Group};

class User extends Base {
  public $settings;

  public function logout():array {
    $ret = [];

    try {
      $this
        ->db->prepare('DELETE FROM users_tokens WHERE users_id = ?')
        ->execute([$this->userId]);

      @session_destroy();
      @session_start();
      $_SESSION = [];

      Helper::deleteCookie();

      $this->userId = null;
    }
    catch(\Exception $e) {
      error_log(__METHOD__.':'.__LINE__.':'.$e->getMessage());
      $ret['error'] = 1;
    }

    return $ret;
  }

  public function getUnixDate(string $dt):string {
    $oldTZ = date_default_timezone_get();

    date_default_timezone_set($this->getTimezone());
    $ret = strtotime($dt);
    date_default_timezone_set($oldTZ);

    return $ret;
  }

  public function getDate(int $dt, string $tz = null,
                          string $fmt = '%Y-%m-%d'):string {
    $oldTZ = date_default_timezone_get();
    $newTZ = $tz ?? $this->getTimezone();

    if ($newTZ !== $oldTZ) {
      date_default_timezone_set($newTZ);
      $ret = strftime($fmt, $dt);
      date_default_timezone_set($oldTZ);
    } else {
      $ret = strftime ($fmt, $dt);
    }

    return $ret;
  }

  public function resetPassword():array {
    $ret = [];
    $password = $this->_generatePassword();

    try {
      ($stmt = $this->db->prepare('
        SELECT id, username, fullname FROM users WHERE email = ?'))
         ->execute([$this->data->email]);

      if ($r = $stmt->fetch()) {
        $this->executeQuery('UPDATE users', [
          'password' => hash('sha1', $password),
          'updatedate' => time(),
        ],
        ['id' => $r['id']]);

        (new Task())->execute([
          'event' => Task::EVENT_TYPE_SEND_MESSAGE,
          'method' => 'resetPassword',
          'userId' => $r['id'],
          'email' => $this->data->email,
          'username' => $r['username'],
          'fullname' => $r['fullname'],
          'password' => $password,
        ]);
      }
    } catch(\Exception $e) {
      error_log(__METHOD__.':'.__LINE__.':'.$e->getMessage());
      $ret['error'] = 1;
    }

    return $ret;
  }

  public function getUser():array {
    $ret = [];

    ($stmt = $this->db->prepare('
      SELECT email, username, fullname, about, allow_emails, visible, picture
        FROM users WHERE id = ?'))
          ->execute([$this->userId]);
    
    if (! ($ret = $stmt->fetch()) ) {
      $ret['error'] = _("Unable to retrieve your account information");
    }

    return $ret;
  }

  public function getUserDataJson():string {
    $userId = $this->userId ?? null;

    return json_encode($userId ?
      [
        'id' => intval($userId),
        'settings' => $this->getSettings(false),
        'walls' =>
          (new Wall(['userId' => $userId], $this->ws))->getWall()['list'],
        'token' => $_SESSION['userToken'] ?? '',
      ]
      :
      [
        'id' => 0,
        'settings' => [],
        'walls' => [],
      ]);
  }

  public function exists(int $id = null):int {
    ($stmt = $this->db->prepare('
      SELECT 1 FROM users WHERE id = ? AND visible = 1'))
       ->execute([$id ?? $this->userId]);

    return $stmt->rowCount();
  }

  public function delete():array {
    $ret = [];
    $userId = $this->userId;
    $dir = $this->getUserDir();

    // Close opened user's walls opened by others
    $tmp = (new Wall(['userId' => $userId]))->getWall(false, false, true);
    if (!empty ($tmp['list'])) {
      $wallIds = [];
      foreach ($tmp['list'] as $_wall) {
        $wallIds[] = $_wall['id'];
      }

      // Push WS close request.
      $this->sendWsClient([
        'action' => 'close-walls',
        'userId' => $userId,
        'ids' => $wallIds,
      ]);
    }

    try {
      $this->db->beginTransaction();

      // Decrement user's groups userscount
      $this
        ->db->prepare('
          UPDATE groups SET userscount = userscount - 1
          WHERE id IN (
            SELECT groups_id FROM _perf_walls_users WHERE users_id = ?
            UNION
            SELECT groups_id FROM users_groups WHERE users_id = ?)')
        ->execute([$userId, $userId]);

      // Decrement postits workers count
      $this
        ->db->prepare('
          UPDATE postits SET workerscount = workerscount - 1
          WHERE id IN (
            SELECT postits_id FROM postits_workers
            WHERE users_id = ?)')
        ->execute([$userId]);

      // Delete user from workers table
      $this
        ->db->prepare('DELETE FROM postits_workers WHERE users_id = ?')
        ->execute([$userId]);

      // Remove user's walls directories.
      ($stmt = $this->db->prepare('
        SELECT id FROM walls WHERE users_id = ?'))->execute([$userId]);
      while ($item = $stmt->fetch()) {
        $this->wallId = $item['id'];
        Helper::rm($this->getWallDir());
      }

      // Delete user
      $this
        ->db->prepare('DELETE FROM users WHERE id = ?')
        ->execute([$userId]);

      $this->db->commit();

      $this->logout();

      Helper::rm($dir);
    }
    catch(\Exception $e) {
      $this->db->rollBack();

      error_log (__METHOD__.':'.__LINE__.':'.$e->getMessage());
      $ret['error'] = 1;
    }
  
    return $ret;
  }

  public function checkSession():void {
    ($stmt = $this->db->prepare('
      SELECT 1 FROM users_tokens WHERE users_id = ?'))
      ->execute([$_SESSION['userId']]);

    if (!$stmt->fetch()) {
      $this->logout ();
    }
  }

  public function loginByCookie():bool {
    if ( ($token = Helper::getCookie()) ) {
      ($stmt = $this->db->prepare('
        SELECT
          users_id,
          users.settings
        FROM users_tokens
          INNER JOIN users ON users_tokens.users_id = users.id
        WHERE persistent = 1 AND token = ?'))
         ->execute([hash('sha1', $_SERVER['REMOTE_ADDR']).$token]);

      if ($r = $stmt->fetch()) {
        $this->userId = $r['users_id'];
        $this->register(@json_decode($r['settings']));

        $_SESSION['userToken'] = $token;

        $this->refreshUpdateDate();
        $this->refreshInactiveData();

        return true;
      }
    }

    return false;
  }

  // Return type is mixed: array or false
  public function loadByToken(string $token, string $ip) {
    ($stmt = $this->db->prepare('
      SELECT
        users_id,
        users.username,
        users.fullname
      FROM users_tokens
        INNER JOIN users ON users_tokens.users_id = users.id
      WHERE token = ? LIMIT 1'))
       ->execute([hash('sha1', $ip).$token]);

    if ( ($ret = $stmt->fetch()) ) {
      $this->userId = $ret['users_id'];
    }

    return $ret;
  }

  public function unsubscribe():void {
    $this->executeQuery('UPDATE users',
      ['allow_emails' => 0],
      ['id' => $this->userId]);
  }

  public function register(object $settings = null):void {
    $this->executeQuery('UPDATE users',
      ['lastconnectiondate' => time()],
      ['id' => $this->userId]);

    // All were OK, user is logged
    $_SESSION['userId'] = $this->userId;

    if ($settings) {
      if (isset($settings->locale)) {
        $_SESSION['slocale'] = $settings->locale;
      }

      $this->settings = (array) $settings;
    }
  }

  public function refreshUpdateDate():void {
    $this->executeQuery ('UPDATE users',
      ['updatedate' => time()],
      ['id' => $this->userId]);
  }

  public function refreshInactiveData():void {
    // Remove user from users inactive table if any
    $this->db
      ->prepare('DELETE FROM users_inactive WHERE users_id = ?')
      ->execute([$this->userId]);
  }

  // Only called by crons
  public function manageInactive():void {
    $current = time();

    $user = new User();
    $task = new Task();

    // Delete too old accounts

    // More or less 4 weeks (we do not need precision here)
    $diff = 28 * (24 * 3600);

    $stmt = $this->db->query("
      SELECT U.id, U.username, U.fullname, U.email
      FROM users AS U
        INNER JOIN users_inactive AS UI ON UI.users_id = U.id
      WHERE $current - UI.creationdate > $diff");

     while ($u = $stmt->fetch()) {
       $task->execute([
         'event' => Task::EVENT_TYPE_SEND_MESSAGE,
         'method' => 'accountAutoDeleted',
         'userId' => $u['id'],
         'email' => $u['email'],
         'username' => $u['username'],
         'fullname' => $u['fullname'],
       ]);

       sleep(2);

       $user->userId = $u['id'];
       $user->delete();
     }

     // Warn users that their account will be deleted soon

     // More or less 1 year (we do not need precision here)
     $diff = 365 * (24 * 3600);

     $stmt = $this->db->query("
       SELECT id, username, fullname, email FROM users
       WHERE $current - updatedate > $diff
         AND id NOT IN (SELECT users_id FROM users_inactive)");

     while ($u = $stmt->fetch()) {
       $task->execute([
         'event' => Task::EVENT_TYPE_SEND_MESSAGE,
         'method' => 'accountInactive',
         'userId' => $u['id'],
         'email' => $u['email'],
         'username' => $u['username'],
         'fullname' => $u['fullname'],
       ]);

       $this->executeQuery('INSERT INTO users_inactive', [
         'users_id' => $u['id'],
         'creationdate' => $current,
       ]);

       sleep(2);
     }
  }

  public function purgeTokens():void {
    $current = time();
    $diff = 30 * 60; // 30mn

    $this->db->exec("
      DELETE FROM users_tokens
      WHERE token IN (
        SELECT token
        FROM users_tokens
          INNER JOIN users ON users.id = users_tokens.users_id
        WHERE users_tokens.persistent = 0
          AND $current - users.updatedate > $diff)");
  }

  public function createUpdateLdapUser(array $args):?array {
    $fromScript = isset($args['fromScript']);
    $data = null;

    ($stmt = $this->db->prepare('
      SELECT id, settings FROM users WHERE username = ?'))
       ->execute([$args['username']]);
    $data = $stmt->fetch();

    // If user account has not been yet created on wopits, silently
    // create it (user will not receive creation email)
    if (!$data) {
      if ($this->_isDuplicate(['email' => $args['mail']])) {
        return ['error_msg' => sprintf(_("Another account with the same email as the LDAP account email `%s` already exists on wopits"), $args['mail'])];
      }

      $this->data = (object)[
        'email' => $args['mail'],
        'username' => $args['username'],
        'fullname' => $args['cn'] ?? '',
        'password' => $args['password'],
      ];

      if (empty($this->create(!$fromScript))) {
        $data = ['id' => $this->userId];

        if ($fromScript) {
          echo "CREATE {$args['username']}, {$args['mail']}\n";
        }
      }

    // Update local password with LDAP password
    } else {
      $this->executeQuery('UPDATE users',
        ['password' => hash('sha1', $args['password'])],
        ['id' => $data['id']]);

      if ($fromScript) {
        echo "UPDATE {$args['username']}, {$args['mail']}\n";
      }
    }

    return $data;
  }

  public function login(bool $remember = false):array {
    $ret = [];
    $data = null;

    // If we must use LDAP, get user infos in LDAP and bind with its
    // password
    if (WPT_USE_LDAP) {
      $Ldap = new Ldap();

      if (!$Ldap->connect()) {
        return ['error' => _("Can't contact LDAP server")];
      }

      if ( !($ldapData = $Ldap->getUserData ($this->data->username)) ) {
        return ['error_msg' => _("Your connection attempt failed")];
      }

      // If user has been found in LDAP, try to bind with its password.
      if ($Ldap->bind($ldapData['dn'], $this->data->password)) {
        if (empty($ldapData['mail'])) {
          return ['error_msg' => _("No email address is configured in your LDAP account. Please fix the problem before logging in again on wopits")];
        }

        $data = $this->createUpdateLdapUser([
          'username' => $this->data->username,
          'password' => $this->data->password,
          'mail' => $ldapData['mail'],
          'cn' => $ldapData['cn'],
        ]);
      }
    // Local database auth
    } else {
      ($stmt = $this->db->prepare('
        SELECT id, settings FROM users WHERE username = ? AND password = ?'))
         ->execute([
           $this->data->username,
           hash('sha1', $this->data->password),
         ]);
      $data = $stmt->fetch();
    }

    // User not found
    if (empty($data)) {
      return ['error_msg' => _("Your connection attempt failed")];
    }

    $this->userId = $data['id'];

    try {
      $this->_createToken($data['settings'] ?? null, $remember);

      $this->refreshUpdateDate();
      $this->refreshInactiveData();
    } catch(\Exception $e) {
      error_log(__METHOD__.':'.__LINE__.':'.$e->getMessage ());
      $ret['error'] = 1;
    }

    return $ret;
  }

  public function getSettings(bool $json = true) {
    ($stmt = $this->db->prepare('SELECT settings FROM users WHERE id = ?'))
      ->execute([$this->userId]);

    $ret = ( ($ret = $stmt->fetch()) ) ? $ret['settings'] : '[]';

    if (strpos($ret, '"timezone":') === false) {
      $ret = json_decode($ret);
      $ret->timezone = $this->getTimezone();
      $ret = json_encode($ret);
      $this->saveSettings($ret); 
    }
    
    return $json ? $ret : json_decode($ret);
  }

  public function getSetting(string $key):string {
    $userId = $this->userId ?? $_SESSION['userId'] ?? null;

    if ($userId && !isset ($this->settings[$key])) {
      ($stmt = $this->db->prepare('SELECT settings FROM users WHERE id = ?'))
        ->execute([$userId]);
      if ( ($r = $stmt->fetch()) ) {
        $this->settings = (array) @json_decode($r['settings']);
      }
    }

    return $this->settings[$key] ?? '';
  }

  public function getTimezone():string {
    return (empty( $ret = $this->getSetting('timezone') )) ?
              WPT_LOCALES[$this->slocale] : $ret;
  }

  public function saveSettings(string $settings = null):array {
    $settings = $settings ?? $this->data->settings;
    $ret = [];

    try {
      $this->executeQuery('UPDATE users',
        ['settings' => $settings],
        ['id' => $this->userId]);

      $this->settings = (array) json_decode($settings);
    } catch(\Exception $e) {
      error_log(__METHOD__.':'.__LINE__.':'.$e->getMessage());
      $ret['error'] = 1;
    }

    return $ret;
  }

  public function getWallSettings(int $wallId):object {
    ($stmt = $this->db->prepare('
      SELECT settings FROM _perf_walls_users
      WHERE users_id = ? AND walls_id = ?'))
        ->execute([$this->userId, $wallId]);

    $r = $stmt->fetch(\PDO::FETCH_COLUMN);

    return json_decode(empty($r) ? '{}' : $r);
  }

  public function saveWallSettings(int $wallId, object $settings):array {
    $ret = [];

    try {
      $this->executeQuery('UPDATE _perf_walls_users',
        ['settings' => json_encode($settings)],
        ['users_id' => $this->userId, 'walls_id' => $wallId]);
    } catch(\Exception $e) {
      error_log(__METHOD__.':'.__LINE__.':'.$e->getMessage());
      $ret['error'] = 1;
    }

    return $ret;
  }

  public function setWallSettings(int $wallId):array {
    $key = $this->data->key;
    $value = $this->data->value;

    $settings = $this->getWallSettings($wallId);
    $settings->$key = $value;

    return $this->saveWallSettings($wallId, $settings);
  }

  public function setWallOption(int $wallId, string $option):array {
    $ret = [];

    try {
      switch ($option) {
        case 'displaymode':
          $displayMode = $this->data->value;
          // Update settings for all cells
          if (!empty( (array)($settings = $this->getWallSettings($wallId)) )) {
            // If postit mode (standard mode), remove keys
            $delete = ($displayMode === 'postit-mode');
            foreach ($settings as $key => $value) {
              if (strpos($key, 'cell') !== false) {
                if ($delete) {
                  unset($settings->$key);
                } else {
                  $settings->$key->displaymode = $displayMode;
                }
              }
            }
            $this->saveWallSettings($wallId, $settings);
          }
          break;
        case 'displayexternalref':
        case 'displayheaders':
          break;
        default:
          $unknown = true;
          break;
      }

      if (!isset($unknown)) {
        $this->executeQuery('UPDATE _perf_walls_users',
          [$option => $this->data->value],
          ['users_id' => $this->userId, 'walls_id' => $wallId]);
      }
    } catch(\Exception $e) {
      error_log(__METHOD__.':'.__LINE__.':'.$e->getMessage());
      $ret['error'] = 1;
    }

    return $ret;
  }

  public function deleteMessage():?array {
    $ret = [];

    try {
      $this
        ->db->prepare(
          'DELETE FROM messages_queue WHERE users_id = ? AND id = ?')
        ->execute([$this->userId, intval($this->data->id ?? 0)]);
    } catch(\Exception $e) {
      error_log(__METHOD__.':'.__LINE__.':'.$e->getMessage());
      $ret['error'] = 1;
    }

    return $ret;
  }

  public function getMessages():?array {
    ($stmt = $this->db->prepare('
       SELECT id, creationdate, title, content
         FROM messages_queue WHERE users_id = ? ORDER BY id DESC'))
           ->execute([$this->userId]);

    return $stmt->fetchAll();
  }

  public function getPicture(array $args):?array {
    $userId = $args['userId'];

    if (!$this->userId) {
      return ['error' => _("Access forbidden")];
    }

    ($stmt = $this->db->prepare('
      SELECT picture, filetype, filesize FROM users WHERE id = ?'))
       ->execute([$userId]);

    if ( ($r = $stmt->fetch()) ) {
      Helper::download ([
        'item_type' => $r['filetype'],
        'name' => basename($r['picture']),
        'size' => $r['filesize'],
        'path' => WPT_ROOT_PATH.$r['picture'],
      ]);
    }
  }

  public function deletePicture():array {
    $ret = [];

    if (!$this->userId) {
      return ['error' => _("Access forbidden")];
    }

    try {
      ($stmt = $this->db->prepare('SELECT picture FROM users WHERE id = ?'))
        ->execute([$this->userId]);
      $r = $stmt->fetch();

      $this->executeQuery('UPDATE users', [
        'picture' => null,
        'filetype' => null,
        'filesize' => null,
      ],
      ['id' => $this->userId]);

      Helper::rm(WPT_ROOT_PATH.$r['picture']);
    } catch(\Exception $e) {
      error_log(__METHOD__.':'.__LINE__.':'.$e->getMessage());
      $ret['error'] = 1;
    }

    return $ret;
  }

  public function updatePicture():array {
    $ret = [];

    if (!$this->userId) {
      return ['error' => _("Access forbidden")];
    }

    list($ext, $content, $error) = $this->getUploadedFileInfos($this->data);

    if ($error) {
      $ret['error'] = $error;
    } else {
      try {
        $dir = $this->getUserDir();
        $wdir = $this->getUserDir('web');

        $file = Helper::getSecureSystemName(
          "$dir/img-".hash('sha1', $this->data->content).".$ext");

        $content = str_replace(' ', '+', $content);
        $content = base64_decode($content);
        file_put_contents($file, $content);

        if (!file_exists($file)) {
          throw new \Exception (_("An error occurred while uploading"));
        }

        ($stmt = $this->db->prepare('SELECT picture FROM users WHERE id = ?'))
          ->execute([$this->userId]);
        $previousPicture = $stmt->fetch(\PDO::FETCH_COLUMN, 0);

        list($file, $this->data->item_type) = Helper::resizePicture($file, 200);

        $img = "$wdir/".basename($file);
        $this->executeQuery('UPDATE users', [
          'picture' => $img,
          'filetype' => $this->data->type,
          'filesize' => filesize($file),
        ],
        ['id' => $this->userId]);

        // Delete old picture if needed
        if ($previousPicture && $previousPicture !== $img) {
          Helper::rm(WPT_ROOT_PATH.$previousPicture);
        }

        $ret = ['src' => $img];
      } catch(\ImagickException $e) {
        @unlink($file);

        if ($e->getCode() === 425) {
          return ['error' => _("Unknown file type")];
        } else {
          error_log(__METHOD__.':'.__LINE__.':'.$e->getMessage());
          throw $e;
        }
      } catch(\Exception $e) {
        @unlink($file);

        error_log(__METHOD__.':'.__LINE__.':'.$e->getMessage());
        throw $e;
      }
    }

    return $ret;
  }

  public function update():array {
    $ret = [];

    try {
      if (!isset($this->data->password)) {
        $data = (array) $this->data;
        $field = preg_replace('/^[^a-z]+$/', '', array_keys($data)[0]);
        $value = $data[$field];

        // Check for duplicate (username or email)
        if ((isset($data['username']) || isset($data['email'])) &&
            ( ($dbl = $this->_isDuplicate([$field => $value])) )) {
          return ['error_msg' => sprintf(($dbl === 'username') ?
            _("The login `%s` is already in use") :
            _("The email `%s` is already in use"), $value)];
        }

        // Check for blacklisted domains
        if (isset($data['email']) &&
            ($msg = Helper::isBlacklistedDomain($this->data->email)) ) {
          return ['error_msg' => $msg];
        }

        $this->db->beginTransaction();

        $this->checkDBValue('users', $field, $value);
        $this->db
          ->prepare("UPDATE users SET $field = :$field WHERE id = :id")
          ->execute([$field => $value, 'id' => $this->userId]);

        ($stmt = $this->db->prepare('
          SELECT username, fullname, email, about, allow_emails, visible,
                 picture
          FROM users where id = ?'))
           ->execute([$this->userId]);
        $ret = $stmt->fetch();

        if ($field === 'visible') {
          $settings = json_decode($this->getSettings());
          $settings->visible = $value;
          $this->saveSettings(json_encode($settings));
        } else {
          $this->executeQuery ('UPDATE users',
            ['searchdata' =>
              Helper::unaccent($ret['username'].','.$ret['fullname'])],
            ['id' => $this->userId]);
        }

        $this->db->commit();

        // If user is invisible, remove him from all groups except his own
        if ($field === 'visible' && $value === 0) {
          // Deassociate user from all groups and user's groups users too.
          (new Group(['userId' => $this->userId], $this->ws))
            ->unlinkUserFromOthersGroups();

          // Get all user's walls to disconnect users from them.
          ($stmt = $this->db->prepare('
            SELECT id FROM walls WHERE users_id = ?'))
             ->execute([$this->userId]);
          if (!empty( ($r = $stmt->fetchAll (\PDO::FETCH_COLUMN)) )) {
            $ret['closewalls'] = $r;
          }
        }
      } else {
        $pwd = $this->data->password;

        ($stmt = $this->db->prepare('
          SELECT id FROM users WHERE password = ? AND id = ?'))
           ->execute([hash('sha1', $pwd->current), $this->userId]);
        if (!$stmt->fetch()) {
          return ['error_msg' => _("Wrong current password")];
        }

        $this->executeQuery('UPDATE users',
         ['password' => hash('sha1', $pwd->new)],
         ['id' => $this->userId]);
      }
    } catch(\Exception $e) {
      $msg = $e->getMessage();

      if ($this->db->inTransaction()) {
        $this->db->rollBack ();
      }

      error_log(__METHOD__.':'.__LINE__.':'.$msg);
      $ret['error'] = $msg;
    }

    return $ret;
  }

  private function _createToken (string $settings = null,
                                 bool $remember = false) {
    session_regenerate_id();

    $token = hash('sha1',
      $this->userId.$this->data->username.$this->data->password);

    $this
      ->db->prepare('DELETE FROM users_tokens WHERE users_id = ?')
      ->execute([$this->userId]);

    $this->executeQuery('INSERT INTO users_tokens', [
      'creationdate' => time(),
      'users_id' => $this->userId,
      'token' => hash('sha1', $_SERVER['REMOTE_ADDR']).$token,
      'persistent' => intval($remember),
    ]);

    if ($remember) {
      Helper::setCookie($token);
    }

    $this->register(@json_decode($settings));

    $_SESSION['userToken'] = $token;
  }

  public function create(bool $createToken = true):array {
    $ret = [];

    // Check for SPAM only if standard auth mode
    if (!WPT_USE_LDAP) {
      $checkRef = intval($_SESSION['_check']);
      $checkCompare = intval($this->data->_check);

      if (!$checkRef ||
          !$checkCompare ||
          ($checkCompare !== $checkRef) ||
          (time() - $checkCompare) < 5) {
        $this->logout ();
        error_log('SPAM detection');
        return ['error' => _("The account creation form was completed too quickly. Please reload this page and try again to confirm that you are not a robot...")];
      }
    }

    try {
      // Check for blacklisted domains
      if ( ($msg = Helper::isBlacklistedDomain($this->data->email)) ) {
        return ['error_msg' => $msg];
      }

      // Check for duplicate (username or email)
      if ( ($dbl = $this->_isDuplicate([
              'username' => $this->data->username,
              'email' => $this->data->email])) ) {
        return ['error_msg' => ($dbl === 'username') ?
          sprintf(_("The login `%s` is already in use"),
            $this->data->username) :
          sprintf(_("The email `%s` is already in use"),
            $this->data->email)];
       }

      // Create user
      $currentDate = time();
      $this->executeQuery('INSERT INTO users', [
        'email' => $this->data->email,
        'password' => hash('sha1', $this->data->password),
        'username' => $this->data->username,
        'fullname' => $this->data->fullname,
        'searchdata' =>
          Helper::unaccent("{$this->data->username},{$this->data->fullname}"),
        'creationdate' => $currentDate,
        'updatedate' => $currentDate,
        'lastconnectiondate' => $currentDate,
        'settings' => json_encode([
          'locale' => $_SESSION['slocale'] ?? WPT_DEFAULT_LOCALE,
          'timezone' => $this->getTimezone(),
          'visible' => 1,
          'allow_emails' => 1,
        ]),
      ]);

      $this->userId = $this->db->lastInsertId();

      // All is OK, user is logged
      $_SESSION['userId'] = $this->userId;

      // Send account creation email only in standard auth mode.
      if (!WPT_USE_LDAP) {
        (new Task())->execute ([
          'event' => Task::EVENT_TYPE_SEND_MESSAGE,
          'method' => 'accountCreation',
          'userId' => $this->userId,
          'email' => $this->data->email,
          'username' => $this->data->username,
          'fullname' => $this->data->fullname,
        ]);
      }

      mkdir("{$this->getUserDir()}/tmp", 02770, true);

      if ($createToken) {
        $this->_createToken();
      }
    } catch(\Exception $e) {
      $msg = $e->getMessage();

      error_log(__METHOD__.':'.__LINE__.':'.$msg);

      if (!WPT_USE_LDAP) {
        $ret['error'] = $msg;
      }
    }

    return $ret;
  }

  private function _generatePassword():string {
    // Randomize letters pool order
    // -> No "I", "l" nor "0" to prevent user reading errors
    $chars = str_split('abcdefghijkmnpqrstuxyzABCDEFGHJKLMNPQRSTUXYZ23456789');
    shuffle($chars);
    $chars = implode($chars);
    $len = strlen($chars) - 1;

    // Build 8 letters password with lowercase, uppercase and number
    do {
      for ($i = 0, $result = ''; $i < 8; $i++) {
        $result .= $chars[rand(0, $len)];
      }
    } while (!preg_match ('/[a-z]/', $result) ||
             !preg_match ('/[A-Z]/', $result) ||
             !preg_match ('/[0-9]/', $result));

    return $result;
  }

  private function _isDuplicate(array $args):?string {
    $keys = array_keys($args);
    $data = [];

    $where = ' ( ';
    foreach ($args as $k => $v) {
      $where .= " $k = :$k OR ";
      $data[":$k"] = $v;
    }
    $where .= ")";
    $where = str_replace('OR )', ')', $where);

    if ($this->userId) {
      $where .= ' AND id <> :id ';
      $data[':id'] = $this->userId;
    }

    // Check for duplicate (username or email)
    ($stmt = $this->db->prepare("
      SELECT ".implode(',', $keys)." FROM users WHERE $where"))
       ->execute($data);
    
    if ( ($dbl = $stmt->fetch()) ) {
      foreach ($dbl as $k => $v) {
        if ($data[":$k"] === $v) {
          return $k;
        }
      }
    }

    return null;
  }
}
