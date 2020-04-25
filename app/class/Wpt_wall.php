<?php

  require_once (__DIR__.'/Wpt_dao.php');

  class Wpt_wall extends Wpt_dao
  {
    public $userId;
    public $wallId;
    public $data;

    public function __construct ($args = null)
    {
      parent::__construct ();

      $this->userId = $args['userId'] ?? $GLOBALS['userId'] ??
                      $_SESSION['userId'] ?? null;
      $this->wallId = @$args['wallId'];
      $this->data = @$args['data'];
    }

    public function checkWallAccess ($requiredRole)
    {
      // admin has full access
      $in = WPT_RIGHTS['walls']['admin'];

      // If access need only write right, allow admin and rw roles
      if ($requiredRole == WPT_RIGHTS['walls']['rw'])
        $in .= ','.WPT_RIGHTS['walls']['rw'];
      // If access need at least read right, allow admin, ro and rw roles
      elseif ($requiredRole == WPT_RIGHTS['walls']['ro'])
        $in .= ','.WPT_RIGHTS['walls']['ro'].','.WPT_RIGHTS['walls']['rw'];

      $stmt = $this->prepare ("
        SELECT 1
        FROM walls
        WHERE id = :walls_id_1
          AND users_id = :users_id_1
        UNION
        SELECT 1
        FROM walls_groups
          INNER JOIN users_groups
            ON users_groups.groups_id = walls_groups.groups_id
        WHERE walls_groups.walls_id = :walls_id_2
          AND users_groups.users_id = :users_id_2
          AND walls_groups.access IN($in)
        LIMIT 1");
      $stmt->execute ([
        ':walls_id_1' => $this->wallId,
        ':users_id_1' => $this->userId,
        ':walls_id_2' => $this->wallId,
        ':users_id_2' => $this->userId
      ]);

      if ( !($ret = $stmt->fetch ()) )
      {
        $stmt1 = $this->prepare ('SELECT 1 FROM walls WHERE id = ?');
        $stmt1->execute ([$this->wallId]);
        if (!$stmt1->rowCount ())
          return [
            'ok' => 0,
            'id' => $this->wallId,
            'error_msg' => _("The wall has been deleted"),
            'action' => 'deletedwall'
          ];
      }
      
      return ['ok' => $stmt->rowCount ()];
    }

    public function checkWallName ($name)
    {
      $stmt = $this->prepare ('
        SELECT id
        FROM walls
        WHERE id <> :id
          AND name = :name
          AND users_id = :users_id');
      $stmt->execute ([
        ':id' => $this->wallId??0,
        ':name' => $name,
        ':users_id' => $this->userId
      ]);

      return $stmt->rowCount ();
    }

    protected function isWallCreator ($userId)
    {
      // get wall creator id
      $stmt = $this->prepare ('
        SELECT 1
        FROM walls
        WHERE id = :id
          AND users_id = :users_id');
      $stmt->execute ([
        ':id' => $this->wallId,
        ':users_id' => $userId
      ]);

      return $stmt->fetch ();
    }

    protected function isWallDelegateAdmin ($userId)
    {
      // get wall creator id
      $stmt = $this->prepare ('
        SELECT 1
        FROM walls_groups
          INNER JOIN users_groups
            ON users_groups.groups_id = walls_groups.groups_id
        WHERE walls_groups.walls_id = :walls_id
          AND users_groups.users_id = :users_id
          AND walls_groups.access = '.WPT_RIGHTS['walls']['admin'].'
        LIMIT 1');
      $stmt->execute ([
        ':walls_id' => $this->wallId,
        ':users_id' => $userId
      ]);

      return $stmt->fetch ();
    }

    protected function getWallDir ($type = null)
    {
      return ($type) ?
        WPT_DATA_WPATH."/walls/{$this->wallId}" :
        Wpt_common::getSecureSystemName ("/walls/{$this->wallId}");
    }

    public function getWallInfos ()
    {
      $r = $this->checkWallAccess (WPT_RIGHTS['walls']['ro']);
      if (!$r['ok'])
        return (isset ($r['id'])) ? $r : ['error' => _("Access forbidden")];

      $stmt = $this->prepare ('
        SELECT
          walls.creationdate,
          walls.name,
          walls.description,
          users.id AS user_id,
          users.fullname AS user_fullname,
          users.email AS user_email,
          users.picture AS user_picture,
          users.about AS user_about
        FROM walls
          INNER JOIN users
            ON users.id = walls.users_id
        WHERE walls.id = ?');
      $stmt->execute ([$this->wallId]);

      return $stmt->fetch ();
    }

    public function addHeaderPicture ($args)
    {
      $ret = [];
      $headerId = $args['headerId'];
      $imgPath = null;

      $r = $this->checkWallAccess (WPT_RIGHTS['walls']['admin']);
      if (!$r['ok'])
        return (isset ($r['id'])) ? $r : ['error' => _("Access forbidden")];

      if (!is_object ($this->data) ||
          !preg_match ('#\.([a-z0-9]+)$#i', $this->data->name, $m1) ||
          !preg_match ('#data:([^;]+);base64,(.*)#', $this->data->content, $m2))
      {
        $ret['error'] = _("File format detection error");
      }
      else
      {
        try
        {
          $ext = $m1[1];
          $content = $m2[2];

          $dir = $this->getWallDir ();
          $wdir = $this->getWallDir ('web');
          $rdir = "header/$headerId";

          $imgPath = Wpt_common::getSecureSystemName (
            "$dir/$rdir/img-".hash('sha1', $this->data->content).".$ext");

          file_put_contents (
            $imgPath, base64_decode(str_replace(' ', '+', $content)));

          if (!file_exists ($imgPath))
            throw new Exception ("Error downloading file");

          $stmt = $this->prepare ('SELECT picture FROM headers WHERE id = ?');
          $stmt->execute ([$headerId]);
          $previousPicture = $stmt->fetch()['picture'];

          list ($imgPath, $this->data->type) =
            Wpt_common::resizePicture ($imgPath, 100);

          $img = "$wdir/$rdir/".basename($imgPath);
          $this->executeQuery ('UPDATE headers', [
            'picture' => $img,
            'filetype' => $this->data->type,
            'filesize' => filesize ($imgPath)
          ],
          ['id' => $headerId]);

          $ret['img'] = $img;
  
          // delete old picture if needed
          if ($previousPicture && $previousPicture != $img)
            exec ('rm -f '.
              Wpt_common::getSecureSystemName (WPT_ROOT_PATH.$previousPicture));
        }
        catch (ImagickException $e)
        {
          if ($imgPath)
            @unlink ($file);

          error_log (__METHOD__.':'.__LINE__.':'.$e->getMessage ());
          $ret['error'] = _("Error processing image data");
        }
        catch (Exception $e)
        {
          if ($imgPath)
            @unlink ($file);

          error_log (__METHOD__.':'.__LINE__.':'.$e->getMessage ());
          $ret['error'] = 1;     
        }
      }

      return $ret;
    }

    public function deleteHeaderPicture ($args)
    {
      $ret = [];
      $headerId = $args['headerId'];

      $r = $this->checkWallAccess (WPT_RIGHTS['walls']['admin']);
      if (!$r['ok'])
        return (isset ($r['id'])) ? $r : ['error' => _("Access forbidden")];

      try
      {
        $stmt = $this->prepare ('SELECT picture FROM headers WHERE id = ?');
        $stmt->execute ([$headerId]);
        $r = $stmt->fetch ();

        $this->executeQuery ('UPDATE headers', [
          'picture' => null,
          'filetype' => null,
          'filesize' => null
        ],
        ['id' => $headerId]); 

        exec ('rm -f '.
          Wpt_common::getSecureSystemName(WPT_ROOT_PATH.$r['picture']));
      }
      catch (Exception $e)
      {
        error_log (__METHOD__.':'.__LINE__.':'.$e->getMessage ());
        $ret['error'] = 1;
      }

      return $ret;
    }

    public function getWall ()
    {
      $ret = [];

      // Return all walls
      if (!$this->wallId)
      {
        $stmt = $this->prepare ("
          SELECT
            walls.id,
            walls.creationdate,
            walls.name,
            walls.description,
            '".WPT_RIGHTS['walls']['admin']."' AS access,
            users.id AS ownerid,
            users.fullname AS ownername
          FROM walls
            INNER JOIN users ON walls.users_id = users.id
          WHERE users_id = :users_id_1

          UNION

          SELECT
            walls.id,
            walls.creationdate,
            walls.name AS name,
            walls.description AS description,
            walls_groups.access,
            users.id AS ownerid,
            users.fullname AS ownername
          FROM walls
            INNER JOIN walls_groups
              ON walls_groups.walls_id = walls.id
            INNER JOIN users_groups
              ON users_groups.groups_id = walls_groups.groups_id
            INNER JOIN users
              ON walls.users_id = users.id
          WHERE users_groups.users_id = :users_id_2

          ORDER BY creationdate DESC, ownername, name, access
        ");
        $stmt->execute ([
            ':users_id_1' => $this->userId,
            ':users_id_2' => $this->userId
        ]);

        // If a user is in more than one groups for the same wall, with
        // different rights, take the more powerful right
        $uniq = [];
        while ($item = $stmt->fetch ())
        {
          $id = $item['id'];
          if (!isset ($uniq[$id]))
          {
            $uniq[$id] = true;
            $ret[] = $item;
          }
        }
 
        return ['list' => $ret];
      }

      // If a user is in more than one groups for the same wall, with
      // different rights, take the more powerful right (ORDER BY access)
      $stmt = $this->prepare ("
        SELECT
          id,
          users_id,
          width,
          creationdate,
          name,
          description,
          '".WPT_RIGHTS['walls']['admin']."' AS access
        FROM walls
        WHERE users_id = :users_id_1
          AND walls.id = :walls_id_1

        UNION

        SELECT
          walls.id,
          users_groups.users_id,
          walls.width,
          walls.creationdate,
          walls.name AS name,
          walls.description AS description,
          walls_groups.access
        FROM walls
          INNER JOIN walls_groups
            ON walls_groups.walls_id = walls.id
          INNER JOIN users_groups
            ON users_groups.groups_id = walls_groups.groups_id
        WHERE users_groups.users_id = :users_id_2
          AND walls.id = :walls_id_2
        
        ORDER BY access");

      $stmt->execute ([
        ':users_id_1' => $this->userId,
        ':walls_id_1' => $this->wallId,
        ':users_id_2' => $this->userId,
        ':walls_id_2' => $this->wallId
      ]);
      $data = $stmt->fetch ();

      if (!$data)
      {
        return [
          'id' => $this->wallId,
          'removed' => _("Either you no longer have the right to access this wall, or it has been deleted")
        ];
      }
      else
      {
        // Get headers
        $stmt = $this->prepare ('
          SELECT id, `type`, `order`, width, height, title, picture
          FROM headers
          WHERE walls_id = ?
          ORDER BY `type`, `order` ASC');
        $stmt->execute ([$this->wallId]);
        $data['headers'] = ['cols' => [], 'rows' => []];
        while ($row = $stmt->fetch ())
        {
          $type = $row['type'];
  
          unset ($row['type']);
          unset ($row['order']);
  
          $data['headers'][$type.'s'][] = $row;
        }
  
        // Get cells and postits
        $stmt = $this->prepare ('
          SELECT id, height, width, col, row
          FROM cells
          WHERE walls_id = ?');
        $stmt->execute ([$this->wallId]);
        // Get postits
        $stmt1 = $this->prepare ('
          SELECT
            id, width, height, top, `left`, classcolor, title, content, tags,
            creationdate, deadline, timezone, obsolete, attachmentscount
          FROM postits
          WHERE cells_id = ?');
        $data['cells'] = [];
        while ($row = $stmt->fetch ())
        {
          $stmt1->execute ([$row['id']]);
          $row['postits'] = [];
          while ($row1 = $stmt1->fetch ())
            $row['postits'][] = $row1;
  
          $data['cells'][] = $row;
        }

        // Get postits plugs
        $stmt = $this->prepare ('
          SELECT start, end, label
          FROM postits_plugs
          WHERE walls_id = ?');
        $stmt->execute ([$this->wallId]); 

        $data['postits_plugs'] = $stmt->fetchAll ();
  
        // Check if the wall is shared with other users
        $stmt = $this->prepare ('
          SELECT 1 FROM walls_groups WHERE walls_id = ? LIMIT 1');
        $stmt->execute ([$this->wallId]);
        $data['shared'] = boolval ($stmt->fetch ());
      }

      return $data;
    }

    public function getUsersview (array $usersIds)
    {
      $ret = ['list' => []];

      $stmt = $this->prepare ("
       SELECT
          users.id,
          users.username,
          users.fullname,
          users.picture,
          users.about,
          walls_groups.access AS access
       FROM users
         INNER JOIN users_groups
           ON users_groups.users_id = users.id
         INNER JOIN walls_groups
           ON walls_groups.groups_id = users_groups.groups_id
         INNER JOIN walls ON walls.id = walls_groups.walls_id
       WHERE walls.id = :walls_id_1
         AND users_groups.users_id IN ('".implode("','",$usersIds)."')
       UNION
       SELECT
         users.id,
         users.username,
         users.fullname,
         users.picture,
         users.about,
         '1' AS access
       FROM users
         INNER JOIN walls ON walls.users_id = users.id
       WHERE walls.id = :walls_id_2
         AND users.id IN ('".implode("','",$usersIds)."')
       ORDER BY access, fullname");
      $stmt->execute ([
        ':walls_id_1' => $this->wallId,
        ':walls_id_2' => $this->wallId
      ]);

      //FIXME
      //$ret['list'] = $stmt->fetchAll ();
      // If user is in different groups for the same wall, there is more than
      // one occurence in the SQL result
      $tmp = [];
      while ($item = $stmt->fetch ())
      {
        if (!isset ($tmp[$item['id']]))
          $ret['list'][] = $item;

        $tmp[$item['id']] = 1;
      }

      return $ret;
    }

    public function getHeaderPicture ($args)
    {
      $headerId = $args['headerId'];

      $r = $this->checkWallAccess (WPT_RIGHTS['walls']['ro']);
      if (!$r['ok'])
        return (isset ($r['id'])) ? $r : ['error' => _("Access forbidden")];

      $stmt = $this->prepare ('
        SELECT picture, filetype, filesize FROM headers WHERE id = ?');
      $stmt->execute ([$headerId]);

      if ( ($r = $stmt->fetch ()) )
      {
        $data = [
          'type' => $r['filetype'],
          'name' => basename ($r['picture']),
          'size' => $r['filesize'],
          'path' => WPT_ROOT_PATH.$r['picture']
        ];

        return Wpt_common::download ($data);
      }
    }

    public function deleteWallColRow ($args)
    {
      $ret = [];
      $item = $args['item'];
      $itemPos = $args['itemPos'];
      $dir = $this->getWallDir ();

      if ($item != 'col' && $item != 'row')
        return ['error' => _("Access forbidden")];

      $r = $this->checkWallAccess (WPT_RIGHTS['walls']['admin']);
      if (!$r['ok'])
        return (isset ($r['id'])) ? $r : ['error' => _("Access forbidden")];

      try
      {
        $this->beginTransaction ();

        // Delete headers documents
        $stmt = $this->prepare('
          SELECT id FROM headers
          WHERE walls_id = :walls_id
            AND `type` = :type AND `order` = :order');
        $stmt->execute ([
          ':walls_id' => $this->wallId,
          ':type' => $item,
          ':order' => $itemPos]);
  
        exec ('rm -rf '.
          Wpt_common::getSecureSystemName(
            "$dir/header/".($stmt->fetch ())['id']));

        // Delete header
        $this
          ->prepare('
            DELETE FROM headers
            WHERE walls_id = :walls_id
              AND `type` = :type
              AND `order` = :order')
          ->execute ([
            ':walls_id' => $this->wallId,
            ':type' => $item,
            ':order' => $itemPos
          ]);

        // Reordonate headers
        $this
          ->prepare('
            UPDATE headers SET
              `order` = `order` - 1
            WHERE walls_id = :walls_id
              AND `type` = :type
              AND `order` > :order')
          ->execute ([
              ':walls_id' => $this->wallId,
              ':type' => $item,
              ':order' => $itemPos
            ]);

        // Delete files for all postits
        $stmt = $this->prepare ("
          SELECT postits.id
          FROM cells
            INNER JOIN postits ON postits.cells_id = cells.id
          WHERE cells.walls_id = :walls_id
            AND cells.$item = :item");
        $stmt->execute ([
          ':walls_id' => $this->wallId,
          ':item' => $itemPos
        ]);
        while ($row = $stmt->fetch ())
          exec ('rm -rf '.
            Wpt_common::getSecureSystemName("$dir/postit/{$row['id']}"));

        // Delete
        $this
          ->prepare("
            DELETE FROM cells
            WHERE walls_id = ? AND $item = ?")
          ->execute ([$this->wallId, $itemPos]);

        // Reordonate
        $this
          ->prepare("
            UPDATE cells SET
              $item = $item - 1
            WHERE walls_id = ?
              AND $item > ?")
          ->execute ([$this->wallId, $itemPos]);

        if ($item == 'col')
        {
          $this->executeQuery ('UPDATE walls',
            ['width' => $this->data->wall->width - $this->data->width],
            ['id' => $this->wallId]);
        }
        else
        {
          $this->executeQuery ('UPDATE walls',
            ['width' => $this->data->wall->width],
            ['id' => $this->wallId]);
        }

        $this->commit ();

        $ret = $this->getWall ();
      }
      catch (Exception $e)
      {
        $this->rollback ();

        error_log (__METHOD__.':'.__LINE__.':'.$e->getMessage ());
        $ret['error'] = 1;
      }

      return $ret;
    }

    public function createWallColRow ($args)
    {
      $ret = [];
      $item = $args['item'];
      $dir = $this->getWallDir ();

      if ($item != 'col' && $item != 'row')
        return ['error' => _("Access forbidden")];

      $r = $this->checkWallAccess (WPT_RIGHTS['walls']['admin']);
      if (!$r['ok'])
        return (isset ($r['id'])) ? $r : ['error' => _("Access forbidden")];

      try
      {
        $this->beginTransaction ();

        $stmt = $this->prepare('
        SELECT * FROM headers
        WHERE walls_id = :walls_id
          AND `type` = :type ORDER BY `order` DESC LIMIT 1');
        $stmt->execute ([
          ':walls_id' => $this->wallId,
          ':type' => $item
        ]);
        $header = $stmt->fetch ();
  
        $this->executeQuery ('INSERT INTO headers',
          [
            'walls_id' => $this->wallId,
            'type' => $item,
            'order' => $header['order'] + 1,
            'width' => ($item == 'col') ? $header['width'] : null,
            'height' => $header['height'],
            'title' => ' '
          ]);

        mkdir ("$dir/header/".$this->lastInsertId());
  
        if ($item == 'col')
        {
          $stmt = $this->prepare("
            SELECT * FROM cells
            WHERE walls_id = :walls_id
            AND $item = :item");
          $stmt->execute ([
            ':walls_id' => $this->wallId,
            ':item' => $header['order']]);
        }
        else
        {
          $stmt = $this->prepare('
            SELECT * FROM cells
            WHERE walls_id = ?
            ORDER BY row DESC, col ASC');
          $stmt->execute ([$this->wallId]);
        }
  
        $r = null;
        while ($e = $stmt->fetch ())
        {
          $data = [ 
            'walls_id' => $this->wallId,
            'height' => $e['height']
          ];
  
          // Col
          if ($item == 'col')
          {
            $data['width'] = $header['width'];
            $data['row'] = $e['row'];
            $data['col'] = $header['order'] + 1;
          }
          // Row
          else
          {
            if ($r == null)
              $r = $e['row'];
            elseif ($e['row'] != $r)
              break 1;
  
            $data['width'] = $e['width'];
            $data['row'] = $e['row'] + 1;
            $data['col'] = $e['col'];
          }
  
          $this->executeQuery ('INSERT INTO cells', $data);
        }
  
        if ($item == 'col')
          $this
            ->prepare('
              UPDATE walls SET width = width + ? WHERE id = ?')
            ->execute ([$header['width'], $this->wallId]);

        $this->commit ();

        $ret = ['wall' => $this->getWall ()];
      }
      catch (Exception $e)
      {
        $this->rollback ();

        error_log (__METHOD__.':'.__LINE__.':'.$e->getMessage ());
        $ret['error'] = 1;
      }

      return $ret;
    }

    public function createWall ()
    {
      $ret = [];
      $noGrid = !$this->data->grid;
      $colsCount = ($noGrid) ? 1 : $this->data->colsCount;
      $rowsCount = ($noGrid) ? 1 : $this->data->rowsCount;

      if ($this->checkWallName ($this->data->name))
        return ['error_msg' => _("A wall with the same name already exists")];

      $wall = [
        'name' => $this->data->name,
        'width' => ($noGrid) ? $this->data->width - 50 : 951,
        'headers' => ['cols' => [], 'rows' => []],
        'cells' => []
      ];

      $cellWidth = ($noGrid) ? $this->data->width : 300;
      $cellHeight = ($noGrid) ? $this->data->height - 100 : 200;

      for ($i = 0; $i < $colsCount; $i++)
        $wall['headers']['cols'][] =
          ['width' => $cellWidth, 'height' => 50, 'title' => ' '];

      for ($i = 0; $i < $rowsCount; $i++)
        $wall['headers']['rows'][] =
          ['width' => 50, 'height' => $cellHeight, 'title' => ' '];

      for ($row = 0; $row < $rowsCount; $row++)
      {
        for ($col = 0; $col < $colsCount; $col++) 
          $wall['cells'][] = [
            'row' => $row,
            'col' => $col,
            'width' => $cellWidth,
            'height' => $cellHeight,
            'postits' => []
          ];
      }

      try
      {
        $this->beginTransaction ();

        $this->executeQuery ('INSERT INTO walls', [
          'users_id' => $this->userId,
          'width' => $wall['width'],
          'name' => $wall['name'] ?? '',
          'creationdate' => time ()
        ]);
 
        $this->wallId = $this->lastInsertId ();

        $dir = $this->getWallDir ();
        mkdir ($dir);
        mkdir ("$dir/header");
        mkdir ("$dir/postit");
  
        // INSERT col headers
        for ($i = 0; $i < count($wall['headers']['cols']); $i++)
        {
          $col = $wall['headers']['cols'][$i];
  
          $this->executeQuery ('INSERT INTO headers', [
            'walls_id' => $this->wallId,
            'type' => 'col',
            'order' => $i,
            'width' => $col['width'],
            'height' => $col['height'],
            'title' => $col['title']
          ]);

          mkdir ("$dir/header/{$this->lastInsertId()}");
        }
  
        // INSERT row headers
        for ($i = 0; $i < count($wall['headers']['rows']); $i++)
        {
          $row = $wall['headers']['rows'][$i];

          $this->executeQuery ('INSERT INTO headers', [
            'walls_id' => $this->wallId,
            'type' => 'row',
            'order' => $i,
            'height' => $row['height'],
            'title' => $row['title']
          ]);

          @mkdir ("$dir/header/{$this->lastInsertId()}");
        }
  
        // INSERT cells
        for ($i = 0; $i < count($wall['cells']); $i++)
        {
          $cell = $wall['cells'][$i];
  
          $this->executeQuery ('INSERT INTO cells', [
            'walls_id' => $this->wallId,
            'width' => $cell['width'],
            'height' => $cell['height'],
            'row' => $cell['row'],
            'col' => $cell['col']
          ]);
        }

        $this->commit ();

        $ret = $this->getWall ();
      }
      catch (Exception $e)
      {
        $this->rollback ();

        error_log (__METHOD__.':'.__LINE__.':'.$e->getMessage ());
        $ret['error'] = 1;
      }

      return $ret;
    }
  
    public function deleteWall ()
    {
      $ret = [];

      $r = $this->checkWallAccess (WPT_RIGHTS['walls']['admin']);
      if (!$r['ok'])
        return (isset ($r['id'])) ? $r : ['error' => _("Access forbidden")];

      try
      {
        $this
          ->prepare('DELETE FROM walls WHERE id = ?')
          ->execute ([$this->wallId]);
  
        exec ('rm -rf '.
          escapeshellarg(Wpt_common::getSecureSystemName($this->getWallDir())));
      }
      catch (Exception $e)
      {
        error_log (__METHOD__.':'.__LINE__.':'.$e->getMessage ());
        $ret['error'] = 1;
      }
  
      return $ret;
    }

    public function updateCells ($updateWallWidth = true)
    {
      $newTransaction = (!PDO::inTransaction ());

      try
      {
        if ($newTransaction)
          $this->beginTransaction ();

        for ($i = 0; $i < count($this->data->cells); $i++)
        {
          $cell = $this->data->cells[$i];
  
          $this->executeQuery ('UPDATE cells', [
            'width' => $cell->width,
            'height' => $cell->height
           ],
           ['id' => $cell->id]);
  
          for ($j = 0; $j < count($cell->postits); $j++)
          {
            $postit = $cell->postits[$j];
  
            $this->executeQuery ('UPDATE postits', [
              'width' => $postit->width,
              'height' => $postit->height,
              'top' => $postit->top,
              'left' => $postit->left
            ],
            ['id' => $postit->id]);
          }
        }
  
        if ($updateWallWidth)
          $this->executeQuery ('UPDATE walls',
            ['width' => $this->data->wall->width],
            ['id' => $this->wallId]);

        if ($newTransaction)
          $this->commit ();
      }
      catch (Exception $e)
      {
        if ($newTransaction)
          $this->rollback ();

        throw new Exception ($e->getMessage ());
      }
    }

    public function updateHeaders ($updateWallWidth = true)
    {
      $newTransaction = (!PDO::inTransaction ());

      try
      {
        if ($newTransaction)
          $this->beginTransaction ();

        foreach (['cols', 'rows'] as $type)
        {
          for ($i = 0; $i < count ($this->data->headers->$type); $i++)
          {
            $header = $this->data->headers->$type[$i];
  
            $this->executeQuery ('UPDATE headers', [
              'width' => ($type == 'cols') ? $header->width : null,
              'height' => $header->height,
              'title' => $header->title
            ],
            ['id' => $header->id]);
          }
        }
  
        $this->executeQuery ('UPDATE walls',
          ['width' => $this->data->wall->width],
          ['id' => $this->wallId]);

        if ($newTransaction)
          $this->commit ();
      }
      catch (Exception $e)
      {
        if ($newTransaction)
          $this->rollback ();

        throw new Exception ($e->getMessage ());
      }
    }
  }
