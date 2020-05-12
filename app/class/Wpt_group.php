<?php

  require_once (__DIR__.'/Wpt_wall.php');

  class Wpt_group extends Wpt_wall
  {
    private $groupId;

    public function __construct ($args)
    {
      parent::__construct ($args);

      $this->groupId = @$args['groupId'];
    }
  
    public function searchUser ($args)
    {
      $ret = [];

      // user must be logged to view users
      if (empty ($this->userId))
        return ['error' => _("Access forbidden")];

      //FIXME SQL optimization!
      $stmt = $this->prepare ('
        SELECT id, fullname
        FROM users
        WHERE id <> :users_id
          AND searchdata COLLATE utf8_general_ci LIKE :search
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
        ':search' => "%{$args['search']}%",
        ':groups_id_1' => $this->groupId,
        ':groups_id_2' => $this->groupId
      ]);

      return ['users' => $stmt->fetchAll ()];
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

        $this
          ->prepare('
            DELETE FROM users_groups
            WHERE groups_id = ?
              AND users_id = ?')
          ->execute ([$this->groupId, $groupUserId]);

        $this
          ->prepare('
            UPDATE groups SET userscount = userscount - 1
            WHERE id = ?')
          ->execute ([$this->groupId]);

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

    public function create ($args)
    {
      $ret = [];
      $type = $args['type'];

      // only wall creator can create dedicated group
      // and a user must be logged to create generic group
      if ($type == WPT_GTYPES['dedicated'] &&
          !$this->isWallCreator ($this->userId) || empty ($this->userId))
        return ['error' => _("Access forbidden")];

      try
      {
        $this->executeQuery ('INSERT INTO groups', array_merge ([
          'type' => $type,
          'name' => $this->data->name,
          'description' => ($this->data->description) ?
            $this->data->description : null,
          'users_id' => $this->userId
        ], ($type == WPT_GTYPES['dedicated']) ?
             ['walls_id' => $this->wallId] : []));
      }
      catch (Exception $e)
      {
        $msg = $e->getMessage ();

        // If duplicated entry
        if (stripos ($msg, "duplicate") !== false)
          $ret['error_msg'] = _("This group already exists.");
        else
        {
          error_log (__METHOD__.':'.__LINE__.':'.$msg);
          $ret['error'] = 1;
        }
      }

      return $ret;
    }

    public function update ()
    {
      $ret = [];

      // no need to check rights here (users_id = users_id in WHERE clause)

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
        $msg = $e->getMessage ();

        //FIXME
        // duplicated entry
        if (stripos ($msg, "duplicate") !== false)
          $ret['error_msg'] = _("This group already exists.");
        else
        {
          error_log (__METHOD__.':'.__LINE__.':'.$msg);
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
            groups.type,
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

        $stmt = $this->prepare ('
          SELECT
            userscount,
            `type`,
            id,
            name,
            description
          FROM groups
          WHERE
            (
              (
                users_id = :users_id_1 AND
                `type` = '.WPT_GTYPES['generic'].'
              )
              OR
              (
                users_id = :users_id_2 AND
                `type` = '.WPT_GTYPES['dedicated'].' AND
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
          ORDER BY name');
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
            groups.type,
            groups.id,
            groups.name,
            groups.description,
            walls_groups.walls_id,
            walls_groups.access
          FROM walls_groups
            INNER JOIN groups
              ON groups.id = walls_groups.groups_id
          WHERE walls_groups.walls_id = ?
            AND groups.type = '.WPT_GTYPES['dedicated'].'
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
        $this
          ->prepare('
            DELETE FROM walls_groups
            WHERE groups_id = ?
              AND walls_id = ?')
          ->execute ([$this->groupId, $this->wallId]);
      }
      catch (Exception $e)
      {
        error_log (__METHOD__.':'.__LINE__.':'.$e->getMessage ());
        $ret['error'] = 1;
      }
  
      return $ret;
    }

    public function link ()
    {
      $ret = [];

      // only wall creator can link a group
      if (!$this->isWallCreator ($this->userId))
        return ['error' => _("Access forbidden")];

      try
      {
        $this->executeQuery ('INSERT INTO walls_groups', [
          'groups_id' => $this->groupId,
          'walls_id' => $this->wallId,
          'access' => $this->data->access
        ]);
      }
      catch (Exception $e)
      {
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
      $sql = "
        SELECT 1
        FROM groups
        WHERE groups.id = :groups_id_1
        AND users_id = :users_id_1";

      $d = [
        ':groups_id_1' => $this->groupId,
        ':users_id_1' => $this->userId,
      ];

      if ($this->wallId)
      {
        $sql .= "
          UNION
 
          SELECT 1
          FROM walls_groups
            INNER JOIN users_groups
              ON walls_groups.groups_id = users_groups.groups_id
            INNER JOIN groups
              ON groups.id = walls_groups.groups_id
          WHERE walls_groups.walls_id = :walls_id_2
            AND groups.type = ".WPT_GTYPES['dedicated']."
            AND users_groups.users_id = :users_id_3
            AND walls_groups.access = '".WPT_RIGHTS['walls']['admin']."'";

        $d[':walls_id_2'] = $this->wallId;
        $d[':users_id_3'] = $this->userId;
      }

      $stmt = $this->prepare ("$sql LIMIT 1");
      $stmt->execute ($d);

      return $stmt->fetch ();
    }
  }
