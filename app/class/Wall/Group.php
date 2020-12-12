<?php

namespace Wopits\Wall;

require_once (__DIR__.'/../../config.php');

use Wopits\{Helper, User, Wall, Services\Task};

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
      ($stmt = $this->prepare ('
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

    ($stmt = $this->prepare ('
      SELECT walls_id
      FROM walls_groups
      WHERE walls_groups.groups_id = ?'))
       ->execute ([$groupId??$this->groupId]);

    while ($item = $stmt->fetch ())
      $ret[] = $item['walls_id'];

    return $ret;
  }

  public function getUsers (bool $withEmail = false):array
  {
    if (!$this->_checkGroupAccess ())
      return ['error' => _("Access forbidden")];

    ($stmt = $this->prepare ('
      SELECT id, '.($withEmail?'email,':'').'username, fullname
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
      $this->beginTransaction ();

      $this->executeQuery ('INSERT INTO users_groups', [
        'groups_id' => $this->groupId,
        'users_id' => $groupUserId
      ]);

      // Performance helper:
      // Link user to group's walls with specific access.
      ($stmt = $this->prepare ('
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
        ->prepare('
          UPDATE groups SET userscount = userscount + 1
          WHERE id = ?')
        ->execute ([$this->groupId]);

      $this->commit ();
    }
    catch (\Exception $e) 
    {
      $this->rollback ();

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
      $this->beginTransaction ();

      $params = [$this->groupId, $groupUserId];

      // Unlink user from group
      $this
        ->prepare('
          DELETE FROM users_groups
          WHERE groups_id = ? AND users_id = ?')
        ->execute ($params);

      if (!$this->wallId)
      {
        // Performance helper:
        // Get wall id.
        ($stmt = $this->prepare ('
          SELECT walls_id FROM _perf_walls_users
          WHERE groups_id = ? AND users_id = ?'))
           ->execute ($params);
        $this->wallId = $stmt->fetch()['walls_id'];
      }

      // Remove user's postits alerts
      if ($this->wallId)
        $this
          ->prepare('
            DELETE FROM postits_alerts
            WHERE walls_id = ? AND users_id = ?')
          ->execute ([$this->wallId, $groupUserId]);

      // Unlink user from group's walls
      $this
        ->prepare('
          DELETE FROM _perf_walls_users
          WHERE groups_id = ? AND users_id = ?')
        ->execute ($params);

      $this
        ->prepare('
          UPDATE groups SET userscount = userscount - 1
          WHERE id = ?')
        ->execute ([$this->groupId]);

      $this->commit ();

      if (!$me && $this->wallId)
        $ret['wall'] = ['id' => $this->wallId];
    }
    catch (\Exception $e) 
    {
      $this->rollback ();

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

    ($stmt = $this->prepare ($sql))->execute ($data);
    if ($stmt->fetch ())
      $ret['error_msg'] = _("This group already exists.");
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

    ($stmt = $this->prepare ('
      SELECT name FROM groups WHERE id <> ? AND users_id = ? AND name = ?'))
       ->execute ([$this->groupId, $this->userId, $this->data->name]);
    if ($stmt->fetch ())
      $ret['error_msg'] = _("This group already exists.");
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
      ($stmt = $this->prepare ('
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
        WHERE groups.users_id = :users_id
          AND walls_groups.walls_id = :walls_id
        ORDER BY name, access'))
         ->execute ([
           ':users_id' => $this->userId,
           ':walls_id' => $this->wallId
         ]);

      $ret['in'] = $stmt->fetchAll ();

      // NOT INT
      ($stmt = $this->prepare ('
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
      ($stmt = $this->prepare ('
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
                _("You must have admin access to perform this action.")];

    return $ret;
  }

  public function unlink ():array
  {
    $ret = [];

    // only wall creator can unlink a group
    if (!$this->isWallCreator ($this->userId))
      return ['error' => _("Access forbidden")];

    try
    {
      $this->beginTransaction ();

      // Unlink group from wall
      $this
        ->prepare('
          DELETE FROM walls_groups WHERE groups_id = ? AND walls_id = ?')
        ->execute ([$this->groupId, $this->wallId]);

      // Performance helper:
      // Unlink group's users to wall.
      $this
        ->prepare('
          DELETE FROM _perf_walls_users
          WHERE groups_id = ? AND walls_id = ?')
        ->execute ([$this->groupId, $this->wallId]);

      // Remove user's postits alerts
      $this
        ->prepare("
          DELETE FROM postits_alerts
          WHERE walls_id = ? AND users_id IN (
            SELECT users_id FROM users_groups WHERE groups_id = ?)")
        ->execute ([$this->wallId, $this->groupId]);

      $this->commit ();

      $ret['wall'] = [
        'id' => $this->wallId,
        'unlinked' => $this->getWallName ()
      ];
    }
    catch (\Exception $e)
    {
      $this->rollback ();

      error_log (__METHOD__.':'.__LINE__.':'.$e->getMessage ());
      $ret['error'] = 1;
    }

    return $ret;
  }

  public function unlinkUserFromOthersGroups ():void
  {
    // Decrement userscount from user's groups.
    $this
      ->prepare('
        UPDATE groups SET userscount = userscount - 1
        WHERE id IN (
          SELECT groups_id FROM _perf_walls_users
          WHERE users_id = ? AND groups_id IS NOT NULL)')
      ->execute ([$this->userId]);

    $this
      ->prepare('DELETE FROM users_groups WHERE users_id = ?')
      ->execute ([$this->userId]);

    $this
      ->prepare('
        DELETE FROM _perf_walls_users
        WHERE users_id = ? AND groups_id IS NOT NULL')
      ->execute ([$this->userId]);

    $this
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
      $this->beginTransaction ();

      // Link group to wall with specific access
      $this->executeQuery ('INSERT INTO walls_groups', [
        'groups_id' => $this->groupId,
        'walls_id' => $this->wallId,
        'access' => $this->data->access
      ]);

      // Performance helper:
      // Link group's users to wall with specific access.
      $this
        ->prepare("
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

        $_args = [
          'users' => $this->getUsers(true)['users'],
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
             'event' => Task::EVENT_TYPE_SEND_MAIL,
             'method' => 'wallSharing',
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

      $this->commit ();
    }
    catch (\Exception $e)
    {
      $this->rollback ();

      error_log (__METHOD__.':'.__LINE__.':'.$e->getMessage ());
      $ret['error'] = 1;
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
        ->prepare('
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
    ($stmt = $this->prepare ('
      SELECT 1 FROM _perf_walls_users
      WHERE access = '.WPT_WRIGHTS_ADMIN.' AND users_id = ?
      LIMIT 1'))
       ->execute ([$this->userId]);

    return $stmt->rowCount ();
  }
}
