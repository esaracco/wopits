<?php

  require_once (__DIR__.'/Wpt_wall.php');
  require_once (__DIR__.'/Wpt_emailsQueue.php');

  class Wpt_group extends Wpt_wall
  {
    public $groupId;

    public function __construct ($args)
    {
      parent::__construct ($args);

      $this->groupId = $args['groupId']??null;
    }
  
    public function searchUser ($args)
    {
      $ret = ['users' => null];

      // user must be logged to view users
      if (empty ($this->userId))
        return ['error' => _("Access forbidden")];

      if ( ($search = Wpt_common::unaccent ($args['search'])) )
      {
        //FIXME SQL optimization.
        $stmt = $this->prepare ('
          SELECT id, fullname
          FROM users
          WHERE id <> :users_id
            AND searchdata LIKE :search
            AND id NOT IN
            (
              SELECT users_id FROM users_groups
              WHERE users_groups.groups_id = :groups_id_1

              UNION

              SELECT users_id FROM groups
              WHERE id = :groups_id_2
            )
          LIMIT 10');
        $stmt->execute ([
          ':users_id' => $this->userId,
          ':search' => "%$search%",
          ':groups_id_1' => $this->groupId,
          ':groups_id_2' => $this->groupId
        ]);

        $ret['users'] = $stmt->fetchAll ();
      }

      return $ret;
    }

    public function getWallsByGroup ($groupId = null)
    {
      $ret = [];

      if (!$this->_checkGroupAccess ())
        return ['error' => _("Access forbidden")];

      $stmt = $this->prepare ('
        SELECT walls_id
        FROM walls_groups
        WHERE walls_groups.groups_id = ?');
      $stmt->execute ([$groupId ?? $this->groupId]);

      while ($item = $stmt->fetch ())
        $ret[] = $item['walls_id'];

      return $ret;
    }

    public function getUsers ()
    {
      if (!$this->_checkGroupAccess ())
        return ['error' => _("Access forbidden")];

      $stmt = $this->prepare ('
        SELECT id, fullname
        FROM users
          INNER JOIN users_groups ON users_groups.users_id = users.id
        WHERE users_groups.groups_id = ?
        ORDER BY fullname');
      $stmt->execute ([$this->groupId]);

      return ['users' => $stmt->fetchAll ()];
    }

    public function addUser ($args)
    {
      $ret = [];
      $groupUserId = $args['userId'];
      
      if (!$this->_checkGroupAccess ())
        return ['error' => _("Access forbidden")];

      try
      {
        $this->beginTransaction ();

        $this->executeQuery ('INSERT INTO users_groups', [
          'groups_id' => $this->groupId,
          'users_id' => $groupUserId
        ]);

        // Performance helper:
        // Link user to group's walls with specific access.
        $stmt = $this->prepare ('
          SELECT walls_id, access FROM walls_groups WHERE groups_id = ?');
        $stmt->execute ([$this->groupId]);
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
          ->execute([$this->groupId]);

        $this->commit ();
      }
      catch (Exception $e) 
      {
        $this->rollback ();

        error_log (__METHOD__.':'.__LINE__.':'.$e->getMessage ());
        $ret['error'] = 1;
      }

      return $ret;
    }

    public function removeUser ($args)
    {
      $ret = [];
      $groupUserId = $args['userId'];

      if (!$this->_checkGroupAccess ())
        return ['error' => _("Access forbidden")];

      try
      {
        $this->beginTransaction ();

        $params = [$this->groupId, $groupUserId];

        $this
          ->prepare('
            DELETE FROM users_groups
            WHERE groups_id = ? AND users_id = ?')
          ->execute ($params);

        // Performance helper:
        // Get wall id.
        $stmt = $this->prepare ('
          SELECT walls_id FROM _perf_walls_users
          WHERE groups_id = ? AND users_id = ?');
        $stmt->execute ($params);
        $this->wallId = $stmt->fetch()['walls_id'];
        // Unlink user to group's walls.
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

        $ret['wall'] = [
          'id' => $this->wallId,
          'unlinked' => sprintf(_("You no longer have the necessary rights to access the «%s» wall!"), $this->getWallName ())
        ];
      }
      catch (Exception $e) 
      {
        $this->rollback ();

        error_log (__METHOD__.':'.__LINE__.':'.$e->getMessage ());
        $ret['error'] = 1;
      }

      return $ret;
    }

    public function create ($args)
    {
      $ret = [];
      $type = $args['type'];

      // only wall creator can create dedicated group
      // and a user must be logged to create generic group
      if ($type == WPT_GTYPES_DED &&
          !$this->isWallCreator ($this->userId) || empty ($this->userId))
        return ['error' => _("Access forbidden")];

      $stmt = $this->prepare ('
        SELECT name FROM groups WHERE users_id = ? AND name = ?');
      $stmt->execute ([$this->userId, $this->data->name]);
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
          ], ($type == WPT_GTYPES_DED) ?
               ['walls_id' => $this->wallId] : []));
        }
        catch (Exception $e)
        {
          error_log (__METHOD__.':'.__LINE__.':'.$e->getMessage ());
          $ret['error'] = 1;
        }
      }

      return $ret;
    }

    public function update ()
    {
      $ret = [];

      // no need to check rights here (users_id = users_id in WHERE clause)

      $stmt = $this->prepare ('
        SELECT name FROM groups WHERE id <> ? AND users_id = ? AND name = ?');
      $stmt->execute ([$this->groupId, $this->userId, $this->data->name]);
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
        catch (Exception $e)
        {
          error_log (__METHOD__.':'.__LINE__.':'.$e->getMessage ());
          $ret['error'] = 1;
        }
      }

      return $ret;
    }

    public function getGroup ()
    {
      $isCreator = $this->isWallCreator ($this->userId);
      $ret = [];

      // wall creator can view all groups
   
      if ($isCreator)
      {
        // IN
        $stmt = $this->prepare ('
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
          ORDER BY name, access');
        $stmt->execute ([
          ':users_id' => $this->userId,
          ':walls_id' => $this->wallId
        ]);
        $ret['in'] = $stmt->fetchAll ();
  
        // NOT INT

        $stmt = $this->prepare ("
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
                item_type = ".WPT_GTYPES_GEN."
              )
              OR
              (
                users_id = :users_id_2 AND
                item_type = ".WPT_GTYPES_DED." AND
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
          ORDER BY name");
        $stmt->execute ([
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
        $stmt = $this->prepare ('
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
          ORDER BY name, access');
        $stmt->execute ([$this->wallId]);
        $ret['in'] = $stmt->fetchAll ();
      }
      // only wall creator and wall delegate admin can view groups
      else
        $ret = ['error_msg' =>
                  _("You must have admin access to perform this action.")];

      return $ret;
    }
  
    public function unlink ()
    {
      $ret = [];

      // only wall creator can unlink a group
      if (!$this->isWallCreator ($this->userId))
        return ['error' => _("Access forbidden")];

      try
      {
        $this->beginTransaction ();

        $this
          ->prepare("
            DELETE FROM emails_queue
            WHERE item_type = 'wallSharing'
              AND walls_id = ?
              AND groups_id = ?")
          ->execute ([$this->wallId, $this->groupId]);

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

        $ret['wall'] = [
          'id' => $this->wallId,
          'removed' => $this->getRemovedWallMessage ()
        ];

        $this->commit ();
      }
      catch (Exception $e)
      {
        $this->rollback ();

        error_log (__METHOD__.':'.__LINE__.':'.$e->getMessage ());
        $ret['error'] = 1;
      }
  
      return $ret;
    }

    public function link ()
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
        $stmt = $this->prepare ("
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
          FROM users_groups WHERE groups_id = ?");
        $stmt->execute ([$this->groupId]);

        if ($this->data->sendmail)
        {
          $EmailsQueue = new Wpt_emailsQueue ();
          $sharerName = $this->data->sendmail->userFullname;
          $wallTitle = $this->data->sendmail->wallTitle;
          $access = $this->data->access;

          foreach ($this->getUsers()['users'] as $user)
          {
            $EmailsQueue->addTo ([
              'item_type' => 'wallSharing',
              'users_id' => $user['id'],
              'walls_id' => $this->wallId,
              'groups_id' => $this->groupId,
              'data' => [
                'recipientName' => $user['fullname'],
                'sharerName' => $sharerName,
                'wallTitle' => $wallTitle,
                'access' => $access
              ]
            ]);
          }
        }

        $this->commit ();
      }
      catch (Exception $e)
      {
        $this->rollback ();

        error_log (__METHOD__.':'.__LINE__.':'.$e->getMessage ());
        $ret['error'] = 1;
      }

      return $ret;
    }

    public function delete ()
    {
      $ret = [];

      // no need to check rights here (users_id = users_id in WHERE clause)

      try
      {
        $this
          ->prepare('
            DELETE FROM groups
            WHERE id = ?
              AND users_id = ?')
          ->execute ([$this->groupId, $this->userId]);
      }
      catch (Exception $e)
      {
        error_log (__METHOD__.':'.__LINE__.':'.$e->getMessage ());
        $ret['error'] = 1;
      }
  
      return $ret;
    }

    private function _checkGroupAccess ()
    {
      $stmt = $this->prepare ('
        SELECT 1 FROM _perf_walls_users
        WHERE access = '.WPT_WRIGHTS_ADMIN.' AND users_id = ?
        LIMIT 1');
      $stmt->execute ([$this->userId]);

      return $stmt->fetch ();
    }
  }
