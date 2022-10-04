<?php

namespace Wopits\Wall;

require_once (__DIR__.'/../../config.php');

use Wopits\{Helper, User, Wall, Services\Task, Wall\Worker};

class Group extends Wall
{
  public $groupId;

  public function __construct (array $args = null, object $ws = null)
  {
    parent::__construct ($args, $ws);

    $this->groupId = $args['groupId']??null;
  }

  public function searchUser (array $args):array
  {
    $ret = ['users' => null];

    // user must be logged to view users
    if (empty ($this->userId))
      return ['error' => _("Access forbidden")];

    if ( ($search = Helper::unaccent ($args['search'])) )
    {
      //FIXME SQL optimization
      ($stmt = $this->db->prepare ('
        SELECT id, username, fullname
        FROM users
        WHERE id <> :users_id
          AND visible = 1
          AND searchdata LIKE :search
          AND id NOT IN
          (
            SELECT users_id FROM users_groups
            WHERE users_groups.groups_id = :groups_id_1

            UNION

            SELECT users_id FROM groups
            WHERE id = :groups_id_2
          )
        LIMIT 10'))
         ->execute ([
           ':users_id' => $this->userId,
           ':search' => "%$search%",
           ':groups_id_1' => $this->groupId,
           ':groups_id_2' => $this->groupId
         ]);

      $ret['users'] = $stmt->fetchAll ();
    }

    return $ret;
  }

  public function getWallsByGroup (int $groupId = null):array
  {
    $ret = [];

    if (!$this->_checkGroupAccess ())
      return ['error' => _("Access forbidden")];

    ($stmt = $this->db->prepare ('
      SELECT walls_id FROM walls_groups WHERE walls_groups.groups_id = ?'))
       ->execute ([$groupId??$this->groupId]);

    while ($item = $stmt->fetch ())
      $ret[] = $item['walls_id'];

    return $ret;
  }

  public function getUsers (bool $withEmail = false):array
  {
    if (!$this->_checkGroupAccess ())
      return ['error' => _("Access forbidden")];

    ($stmt = $this->db->prepare ('
      SELECT id, '.($withEmail?'email,allow_emails,':'').'username, fullname
      FROM users
        INNER JOIN users_groups ON users_groups.users_id = users.id
      WHERE users_groups.groups_id = ?
      ORDER BY fullname'))
       ->execute ([$this->groupId]);

    return ['users' => $stmt->fetchAll ()];
  }

  public function addUser (array $args):array
  {
    $ret = [];
    $groupUserId = $args['userId'];
    
    if (!$this->_checkGroupAccess ())
      return ['error' => _("Access forbidden")];

    // If user does not exists anymore.
    if (!(new User([], $this->ws))->exists ($groupUserId))
      return ['notfound' => 1];

    try
    {
      $this->db->beginTransaction ();

      $this->executeQuery ('INSERT INTO users_groups', [
        'groups_id' => $this->groupId,
        'users_id' => $groupUserId
      ]);

      // Performance helper:
      // Link user to group's walls with specific access.
      ($stmt = $this->db->prepare ('
        SELECT walls_id, access FROM walls_groups WHERE groups_id = ?'))
         ->execute ([$this->groupId]);
      while ( ($item = $stmt->fetch ()) )
      {
        $this->executeQuery ('INSERT INTO _perf_walls_users', [
          'groups_id' => $this->groupId,
          'walls_id' => $item['walls_id'],
          'users_id' => $groupUserId,
          'access' => $item['access']
        ]);
      }

      $this
        ->db->prepare('
            UPDATE groups SET userscount = userscount + 1 WHERE id = ?')
        ->execute ([$this->groupId]);

      $this->db->commit ();
    }
    catch (\Exception $e) 
    {
      $this->db->rollBack ();

      error_log (__METHOD__.':'.__LINE__.':'.$e->getMessage ());
      $ret['error'] = 1;
    }

    return $ret;
  }

  public function removeMe (array $groupIds):array
  {
    foreach ($groupIds as $groupId)
    {
      $this->groupId = $groupId;
      $ret = $this->removeUser (['userId' => $this->userId], true);
      if (isset ($ret['error']))
        return $ret;
    }

    return [];
  }

  public function removeUser (array $args, bool $me = false):array
  {
    $ret = [];
    $groupUserId = $args['userId'];
    // For generic groups
    $wallIds = $args['wallIds'] ?? null;

    if (!$me)
    {
      if (!$this->_checkGroupAccess ())
        return ['error' => _("Access forbidden")];

      // If user does not exists anymore.
      if (!(new User([], $this->ws))->exists ($groupUserId))
        return ['notfound' => 1];
    }

    try
    {
      $this->db->beginTransaction ();

      $params = [$this->groupId, $groupUserId];

      // Unlink user from group
      ($stmt = $this->db->prepare('
        DELETE FROM users_groups WHERE groups_id = ? AND users_id = ?'))
          ->execute ($params);

      if (!$stmt->rowCount ())
        return ['error' => 1];

       // Decrement group users count
      $this
        ->db->prepare('
            UPDATE groups SET userscount = userscount - 1 WHERE id = ?')
        ->execute ([$this->groupId]);

      $this->removeUserDependencies ([
        'userId' => $groupUserId,
        'wallIds' => $wallIds,
      ]);

      $this->db->commit ();

      if (!$me && $this->wallId)
        $ret['wall'] = ['id' => $this->wallId];
    }
    catch (\Exception $e) 
    {
      $this->db->rollBack ();

      error_log (__METHOD__.':'.__LINE__.':'.$e->getMessage ());
      $ret['error'] = 1;
    }

    return $ret;
  }

  public function create (array $args):array
  {
    $ret = [];
    $type = $args['type'];
    $isDed = ($type == WPT_GTYPES_DED);

    // Only wall creator can create dedicated group
    // and a user must be logged to create generic group.
    if ($isDed &&
        !$this->isWallCreator ($this->userId) || empty ($this->userId))
      return ['error' => _("Access forbidden")];

    $sql = 'SELECT name FROM groups WHERE users_id = ? AND name = ?';
    $data = [$this->userId, $this->data->name];

    if ($isDed)
    {
      $sql .= ' AND walls_id = ?';
      $data[] = $this->wallId;
    }

    ($stmt = $this->db->prepare ($sql))->execute ($data);
    if ($stmt->fetch ())
      $ret['error_msg'] = _("The group already exists");
    else
    {
      try
      {
        $this->executeQuery ('INSERT INTO groups', array_merge ([
          'item_type' => $type,
          'name' => $this->data->name,
          'description' => ($this->data->description) ?
            $this->data->description : null,
          'users_id' => $this->userId
        ], ($isDed) ? ['walls_id' => $this->wallId] : []));
      }
      catch (\Exception $e)
      {
        error_log (__METHOD__.':'.__LINE__.':'.$e->getMessage ());
        $ret['error'] = 1;
      }
    }

    return $ret;
  }

  public function update ():array
  {
    $ret = [];

    // no need to check rights here (users_id = users_id in WHERE clause)

    ($stmt = $this->db->prepare ('
      SELECT name FROM groups WHERE id <> ? AND users_id = ? AND name = ?'))
       ->execute ([$this->groupId, $this->userId, $this->data->name]);
    if ($stmt->fetch ())
      $ret['error_msg'] = _("The group already exists");
    else
    {
      try
      {
        $this->executeQuery ('UPDATE groups', [
          'name' => $this->data->name,
          'description' => ($this->data->description) ?
            $this->data->description : null
        ],
        [
          'id' => $this->groupId,
          'users_id' => $this->userId
        ]);
      }
      catch (\Exception $e)
      {
        error_log (__METHOD__.':'.__LINE__.':'.$e->getMessage ());
        $ret['error'] = 1;
      }
    }

    return $ret;
  }

  public function getGroup ():array
  {
    $isCreator = $this->isWallCreator ($this->userId);
    $ret = [];

    // wall creator can view all groups
 
    if ($isCreator)
    {
      // IN
      ($stmt = $this->db->prepare ('
        SELECT
          groups.userscount,
          groups.item_type,
          groups.id,
          groups.name,
          groups.description,
          walls_groups.walls_id,
          walls_groups.access
        FROM walls_groups
          INNER JOIN groups
            ON groups.id = walls_groups.groups_id
        WHERE groups.users_id = ?
          AND walls_groups.walls_id = ?
        ORDER BY name, access'))
         ->execute ([$this->userId, $this->wallId]);

      $ret['in'] = $stmt->fetchAll ();

      // NOT INT
      ($stmt = $this->db->prepare ('
        SELECT
          userscount,
          item_type,
          id,
          name,
          description
        FROM groups
        WHERE
          (
            (
              users_id = :users_id_1 AND
              item_type = '.WPT_GTYPES_GEN.'
            )
            OR
            (
              users_id = :users_id_2 AND
              item_type = '.WPT_GTYPES_DED.' AND
              walls_id = :walls_id_1
            )
          )
          AND id NOT IN
          (
            SELECT groups.id
            FROM walls_groups
              INNER JOIN groups
                ON groups.id = walls_groups.groups_id
            WHERE groups.users_id = :users_id_3
              AND walls_groups.walls_id = :walls_id_2
          )
        ORDER BY name'))
         ->execute ([
             ':users_id_1' => $this->userId,
             ':users_id_2' => $this->userId,
             ':walls_id_1' => $this->wallId,
             ':users_id_3' => $this->userId,
             ':walls_id_2' => $this->wallId
         ]);

      $ret['notin'] = $stmt->fetchAll ();
    }
    // deletegate admins can view only wall dedicated groups
    elseif ($this->isWallDelegateAdmin ($this->userId))
    {
      $ret = [
        'delegateAdminId' => $this->userId,
        'notin' => []
      ];

      // IN
      ($stmt = $this->db->prepare ('
        SELECT
          groups.userscount,
          groups.item_type,
          groups.id,
          groups.name,
          groups.description,
          walls_groups.walls_id,
          walls_groups.access
        FROM walls_groups
          INNER JOIN groups
            ON groups.id = walls_groups.groups_id
        WHERE walls_groups.walls_id = ?
          AND groups.item_type = '.WPT_GTYPES_DED.'
        ORDER BY name, access'))
         ->execute ([$this->wallId]);

      $ret['in'] = $stmt->fetchAll ();
    }
    // only wall creator and wall delegate admin can view groups
    else
      $ret = ['error_msg' =>
                _("You must have admin access to perform this action")];

    return $ret;
  }

  private function _RUD_getKey($el) {
    return $el['users_id'].'-'.$el['walls_id'];
  }

    // Extract users ids from array keys
  private function _RUD_extractUserIds($arr) {
    return array_map(
      function($k) {return explode('-', $k)[0];},
      array_keys($arr));
  }

  private function removeUserDependencies(array $args = []):array {
    $userId = $args['userId'] ?? null;
    $argsWallIds = $args['wallIds'] ?? [$this->wallId];
    $wallIds = implode(',', array_map([$this->db, 'quote'], $argsWallIds));
    $force = $args['force'] ?? false;

    // Get all users for the group to unlink
    ($stmt = $this
       ->db->prepare('
         SELECT users_id, walls_id FROM _perf_walls_users
         WHERE groups_id IS NOT NULL
           AND groups_id = ?
           AND walls_id IN ('.$wallIds.') '.
           ($userId ? ' AND users_id = ?' : '')))
       ->execute(array_merge([$this->groupId], $userId ? [$userId] : []));
    $users = [];
    while ($el = $stmt->fetch()) {
      $users[$this->_RUD_getKey($el)] = $el;
    }
    $usersAlerts = $users;

    ($stmt = $this
       ->db->prepare('
         SELECT users_id, walls_id, access FROM _perf_walls_users
         WHERE groups_id IS NOT NULL
           AND groups_id <> ?
           AND walls_id IN ('.$wallIds.') '.
           ($userId ? ' AND users_id = ?' : '')))
       ->execute (array_merge([$this->groupId], $userId ? [$userId] : []));
    $tmp = [];
    while ($el = $stmt->fetch()) {
      $tmp[$this->_RUD_getKey($el)] = $el;
    }

    foreach (array_keys($users) as $id) {
      if (isset($tmp[$id])) {
        // Do not remove this user from workers if it belongs to other
        // wall groups
        unset($users[$id]);

        // Do not remove alerts for user if it belongs to other
        // RW or ADMIN wall groups
        if ($tmp[$id]['access'] != WPT_WRIGHTS_RO) {
          unset($usersAlerts[$id]);
        }
      }
    }

    // Performance helper:
    // Unlink group's users from wall
    $this
      ->db->prepare('
        DELETE FROM _perf_walls_users
        WHERE groups_id = ? AND walls_id IN ('.$wallIds.') '.
        ($userId ? ' AND users_id = ?' : ''))
      ->execute(array_merge([$this->groupId], $userId ? [$userId] : []));

    // Remove user's postits alerts
    if (!empty($usersAlerts)) {
      $this
        ->db->prepare('
          DELETE FROM postits_alerts
          WHERE walls_id IN ('.$wallIds.') AND users_id IN ('.
          implode(',',
            array_map([$this->db,'quote'],
            $this->_RUD_extractUserIds($usersAlerts))).')')
        ->execute();
    }

    ($stmt = $this
       ->db->prepare('
         SELECT walls_id, postits_id, users_id
         FROM postits_workers
         WHERE walls_id IN('.$wallIds.') '.
         ($userId ? ' AND users_id = ?' : '')))
       ->execute($userId ? [$userId] : []);

    // Remove group users from workers
    while ($el = $stmt->fetch()) {
      // Remove from workers only if user is not in another wall's group
      if ($force || isset($users[$this->_RUD_getKey($el)])) {
        $worker = new Worker([
          'userId' => $el['users_id'],
          'wallId' => $el['walls_id'],
        ]);
        $worker->postitId = $el['postits_id'];
        $worker->delete($el['users_id'], true);
      }
    }

    return $this->_RUD_extractUserIds($users);
  }

  public function unlink ():array
  {
    $ret = [];

    // only wall creator can unlink a group
    if (!$this->isWallCreator ($this->userId))
      return ['error' => _("Access forbidden")];

    try
    {
      $this->db->beginTransaction ();

      // Unlink group from wall
      $this
        ->db->prepare('
          DELETE FROM walls_groups WHERE walls_id = ? AND groups_id = ?')
        ->execute ([$this->wallId, $this->groupId]);

      $users = $this->removeUserDependencies ();

      // Take creator in account if no groups remain
      ($stmt = $this->db
         ->prepare('SELECT 1 FROM walls_groups WHERE walls_id = ?'))
         ->execute ([$this->wallId]);
      if (!$stmt->rowCount()) {
        $this->removeUserDependencies ([
          'userId' => $this->userId,
          'force' => true,
        ]);
      }

      if (isset ($users['error']))
        throw \Exception ("Error deleting user's dependencies");

      $this->db->commit ();

      $ret['wall'] = [
        'id' => $this->wallId,
        'unlinked' => $this->getWallName (),
        'usersIds' => $users ? array_keys ($users) : []
      ];
    }
    catch (\Exception $e)
    {
      $this->db->rollBack ();

      error_log (__METHOD__.':'.__LINE__.':'.$e->getMessage ());
      $ret['error'] = 1;
    }

    return $ret;
  }

  public function unlinkUserFromOthersGroups ():void
  {
    // Decrement userscount from user's groups.
    $this->db
       ->prepare('
        UPDATE groups SET userscount = userscount - 1
        WHERE id IN (SELECT groups_id FROM users_groups WHERE users_id = ?)')
      ->execute ([$this->userId]);

    $this->db
      ->prepare('DELETE FROM users_groups WHERE users_id = ?')
      ->execute ([$this->userId]);

    $this->db
      ->prepare('
        DELETE FROM _perf_walls_users
        WHERE groups_id IS NOT NULL
          AND walls_id IN (
            SELECT walls_id FROM _perf_walls_users WHERE users_id = ?)')
      ->execute ([$this->userId]);

    $this->db
      ->prepare('
        DELETE FROM _perf_walls_users
        WHERE users_id = ? AND groups_id IS NOT NULL')
      ->execute ([$this->userId]);

    $this->db
      ->prepare('
        DELETE FROM walls_groups
        WHERE walls_id IN (SELECT walls.id FROM walls
          INNER JOIN walls_groups ON walls_groups.walls_id = walls.id
          INNER JOIN groups ON groups.id = walls_groups.groups_id
          INNER JOIN users ON users.id = groups.users_id
        WHERE users.id = ?)')
      ->execute ([$this->userId]);
  }

  public function link ():array
  {
    $ret = [];

    // Only wall creator can link a group
    if (!$this->isWallCreator ($this->userId))
      return ['error' => _("Access forbidden")];

    try
    {
      $this->db->beginTransaction ();

      // Link group to wall with specific access
      $this->executeQuery ('INSERT INTO walls_groups', [
        'groups_id' => $this->groupId,
        'walls_id' => $this->wallId,
        'access' => $this->data->access
      ]);

      // Performance helper:
      // Link group's users to wall with specific access.
      $this->checkDBValue ('_perf_walls_users', 'groups_id', $this->groupId);
      $this->checkDBValue ('_perf_walls_users', 'walls_id', $this->wallId);
      $this->checkDBValue ('_perf_walls_users', 'access', $this->data->access);
      $this
        ->db->prepare("
          INSERT INTO _perf_walls_users (
            groups_id,
            walls_id,
            users_id,
            access
          )
          SELECT
            {$this->groupId} AS groups_id,
            {$this->wallId} AS walls_id,
            users_id,
            {$this->data->access} AS access
          FROM users_groups WHERE groups_id = ?")
        ->execute ([$this->groupId]);

      if ($this->data->sendmail)
      {
        $sharerName = $this->data->sendmail->userFullname;
        $wallTitle = $this->data->sendmail->wallTitle;
        $access = $this->data->access;

        $users = $this->getUsers(true)['users'];

        $ret = ['wallId' => $this->wallId, 'users' => []];

        // Only return users ids
        foreach ($users as $user)
          $ret['users'][] = $user['id'];

        $_args = [
          'users' => $users,
          'wallId' => $this->wallId,
          'sharerName' => $sharerName,
          'wallTitle' => $wallTitle,
          'access' => $access
       ];

       // Use async Coroutine to safely use sleep in order to relieve SMTP.
       go (function () use ($_args)
       {
         $Task = new Task ();

         foreach ($_args['users'] as $user)
         {
           $Task->execute ([
             'event' => Task::EVENT_TYPE_SEND_MESSAGE,
             'method' => 'wallSharing',
             'sendmail' => $user['allow_emails'],
             'userId' => $user['id'],
             'email' => $user['email'],
             'wallId' => $_args['wallId'],
             'recipientName' => $user['fullname'],
             'sharerName' => $_args['sharerName'],
             'wallTitle' => $_args['wallTitle'],
             'access' => $_args['access']
           ]);

           \Swoole\Coroutine::sleep (2);
         }
       });
      }

      $this->db->commit ();
    }
    catch (\Exception $e)
    {
      $this->db->rollBack ();

      error_log (__METHOD__.':'.__LINE__.':'.$e->getMessage ());
      $ret = ['error' => 1];
    }

    return $ret;
  }

  public function delete ():array
  {
    $ret = [];

    // no need to check rights here (users_id = users_id in WHERE clause)

    try
    {
      $this
        ->db->prepare('
          DELETE FROM groups
          WHERE id = ? AND users_id = ?')
        ->execute ([$this->groupId, $this->userId]);
    }
    catch (\Exception $e)
    {
      error_log (__METHOD__.':'.__LINE__.':'.$e->getMessage ());
      $ret['error'] = 1;
    }

    return $ret;
  }

  private function _checkGroupAccess ():int
  {
    ($stmt = $this->db->prepare ('
      SELECT 1 FROM _perf_walls_users
      WHERE access = '.WPT_WRIGHTS_ADMIN.' AND users_id = ?
      LIMIT 1'))
       ->execute ([$this->userId]);

    return $stmt->rowCount ();
  }
}
