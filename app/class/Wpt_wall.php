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
      $wr = WPT_RIGHTS['walls'];
      // Wall admin has full access
      $in = $wr['admin'];

      // If access needs only write right, allow admin and rw roles
      if ($requiredRole == $wr['rw'])
        $in .= ','.$wr['rw'];
      // If access need at least read right, allow admin, ro and rw roles
      elseif ($requiredRole == $wr['ro'])
        $in .= ','.$wr['ro'].','.$wr['rw'];

      $stmt = $this->prepare ("
        SELECT 1 FROM _perf_walls_users
        WHERE users_id = ? AND walls_id = ? AND access IN($in)
        LIMIT 1");
      $stmt->execute ([$this->userId, $this->wallId]);

      if ( !($allowed = $stmt->fetch ()) )
      {
        $stmt = $this->prepare ('SELECT 1 FROM walls WHERE id = ?');
        $stmt->execute ([$this->wallId]);
        if (!$stmt->fetch ())
          return [
            'ok' => 0,
            'id' => $this->wallId,
            // This message will be broadcast to users who have this
            // wall opened
            'error_msg' => _("The wall has been deleted."),
            'action' => 'deletedwall'
          ];
      }
      
      return ['ok' => $allowed];
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
        ':id' => $this->wallId?$this->wallId:'0',
        ':name' => $name,
        ':users_id' => $this->userId
      ]);

      return $stmt->rowCount ();
    }

    protected function isWallCreator ($userId)
    {
      $stmt = $this->prepare ('
        SELECT 1 FROM walls WHERE id = ? AND users_id = ?');
      $stmt->execute ([$this->wallId, $userId]);

      return $stmt->fetch ();
    }

    protected function isWallDelegateAdmin ($userId)
    {
      $stmt = $this->prepare ('
        SELECT 1 FROM _perf_walls_users
        WHERE access = '.WPT_RIGHTS['walls']['admin'].'
          AND groups_id IS NOT NULL
          AND walls_id = ?
          AND users_id = ?
        LIMIT 1');
      $stmt->execute ([$this->wallId, $userId]);

      return $stmt->fetch ();
    }

    protected function getUserDir ($type = null)
    {
      return ($type) ?
        WPT_DATA_WPATH."/users/{$this->userId}" :
        Wpt_common::getSecureSystemName ("/users/{$this->userId}");
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

    public function getUploadedFileInfos ($data)
    {
      $ret = [];

      if (!is_object ($data) ||
          !$data->size ||
          !preg_match ('#\.([a-z0-9]+)$#i', $data->name, $m1) ||
          !preg_match ('#data:([^;]+);base64,(.*)#', $data->content, $m2))
      {
        $ret = [null, null, _("Empty file or bad file format")];
      }
      else
        $ret = [$m1[1], $m2[2], null];

      return $ret;
    }

    public function addHeaderPicture ($args)
    {
      $ret = [];
      $headerId = $args['headerId'];
      $imgPath = null;

      $r = $this->checkWallAccess (WPT_RIGHTS['walls']['admin']);
      if (!$r['ok'])
        return (isset ($r['id'])) ? $r : ['error' => _("Access forbidden")];

      list ($ext, $content, $error) = $this->getUploadedFileInfos ($this->data);

      if ($error)
        $ret['error'] = $error;
      else
      {
        try
        {
          $dir = $this->getWallDir ();
          $wdir = $this->getWallDir ('web');
          $rdir = "header/$headerId";

          $imgPath = Wpt_common::getSecureSystemName (
            "$dir/$rdir/img-".hash('sha1', $this->data->content).".$ext");

          file_put_contents (
            $imgPath, base64_decode(str_replace(' ', '+', $content)));

          if (!file_exists ($imgPath))
            throw new Exception (_("An error occured while uploading file."));

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
          $ret['error'] = _("A error occured while processing file.");
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

    private function _getImportItemData ($item, $replace = null, $unset = null)
    {
      $data = (array) $item;

      if (array_key_exists ('id', $data))
        unset ($data['id']);

      if (array_key_exists ('walls_id', $data))
        $data['walls_id'] = $this->wallId;

      if (array_key_exists ('users_id', $data))
        $data['users_id'] = $this->userId;

      if (is_array ($replace))
        foreach ($replace as $k => $v)
          $data[$k] = $v;

      if (is_array ($unset))
        foreach ($unset as $k)
          unset ($data[$k]);

      return $data;
    }

    public function clone ()
    {
      return $this->import ($this->export (true));;
    }

    public function setBasicProperties ()
    {
      $this->executeQuery ('UPDATE walls', [
        'name' => $this->data->name,
        'description' => $this->data->description
      ],
      ['id' => $this->wallId]);
    }

    public function import ($exportFile = null)
    {
      $ret = [];
      $error = null;
      $errorMsg = ($exportFile) ?
        _("An error occured while cloning the wall.") :
        _("An error occured while processing import.");
      $sumErrorMsg = _("The file to import was not recognized. Was it really generated from this website?");

      if (!$exportFile)
        list (, $content, $error) = $this->getUploadedFileInfos ($this->data);

      $zip = new ZipArchive ();

      if ($error)
        $ret['error'] = $error;
      else
      {
        $tmpPath = "{$this->getUserDir()}/tmp";
        $importPath = "$tmpPath/import";
        $zipPath1 = "$importPath/import.zip";
        $zipPath2 = "$importPath/wall.zip";

        //FIXME //TODO Remove this later (done in user account creation)
        if (!file_exists ($tmpPath))
          mkdir ($tmpPath);

        if (file_exists ($importPath))
          exec ('rm -rf '.
            escapeshellarg(Wpt_common::getSecureSystemName($importPath)));
        mkdir ($importPath);

        if (!$exportFile)
          file_put_contents (
            $zipPath1, base64_decode(str_replace(' ', '+', $content)));
        else
          $zipPath1 = $exportFile;

        // Extract first ZIP
        if ($zip->open ($zipPath1) !== true ||
            $zip->extractTo ($importPath) !== true)
        {
          $ret['error'] = $errorMsg;
        }
        // Check for ZIP integrity
        elseif (!file_exists ($zipPath2) ||
                !file_exists ("$importPath/sum.sha1") ||
                !($importSum = file_get_contents("$importPath/sum.sha1")) ||
                !($sum = hash ('sha1', sha1_file ($zipPath2).WPT_SECRET_KEY))||
                $sum != $importSum)
        {
          $ret['error'] = $sumErrorMsg;
        }
        // Extract second ZIP
        elseif (!$zip->close () ||
                $zip->open ($zipPath2) !== true ||
                $zip->extractTo ($importPath) !== true ||
                !($wall =
                  json_decode (file_get_contents("$importPath/wall.json"))))
        {
          $ret['error'] = $sumErrorMsg;
        }
        // Process ZIP data
        else
        {
          // Get old cells and postits ids for mapping with new ids
          $idsMap = ['cells' => [], 'postits' => []];
          foreach ($wall->cells as $cell)
          {
            $idsMap['cells'][$cell->id] = null;
            foreach ($cell->postits as $postit)
              $idsMap['postits'][$postit->id] = null;
          }

          $wallName = $wall->name;

          // Change wall name if needed
          $stmt = $this->prepare ('
            SELECT name FROM walls WHERE users_id = ?');
          $stmt->execute ([$this->userId]);

          if ( ($names = $stmt->fetchAll ()) )
          {
            $names = array_column ($names, 'name');
            $v = '';
            $i = 0;
            while (array_search ("$wallName$v", $names) !== false)
              $v = ' ('.(++$i).')';
            $wallName = "$wallName$v";
          }
  
          try
          {
            $this->beginTransaction ();

            //FIXME //TODO factorization with createWall()
            $this->executeQuery ('INSERT INTO walls', [
              'users_id' => $this->userId,
              'width' => $wall->width,
              'name' => $wallName,
              'description' => $wall->description,
              'creationdate' => $wall->creationdate
            ]);
     
            $this->wallId = $this->lastInsertId ();
            $ret = ['wallId' => $this->wallId];
    
            $dir = $this->getWallDir ();
            mkdir ("$dir/header", 02770, true);
            mkdir ("$dir/postit");
      
            $stmt = $this->prepare ('
              UPDATE headers SET picture = ? WHERE id = ?');

            // ADD headers
            foreach ($wall->headers as $item)
            {
              $this->executeQuery ('INSERT INTO headers',
                $this->_getImportItemData ($item));

              $headerId = $this->lastInsertId ();
              mkdir ("$dir/header/$headerId");

              // ADD header picture
              if ($item->picture)
              {
                $fname = basename ($item->picture);
                rename ("$importPath/header/{$item->id}/$fname",
                        "$dir/header/$headerId/$fname");

                $stmt->execute ([
                  str_replace (
                    ["walls/{$wall->id}/", "header/{$item->id}/"],
                    ["walls/{$this->wallId}/", "header/$headerId/"],
                    $item->picture),
                  $headerId
                ]); 
              }
            }

            // ADD cells
            foreach ($wall->cells as $cell)
            {
              $this->executeQuery ('INSERT INTO cells',
                $this->_getImportItemData ($cell, null, ['postits']));
  
              $cellId = $this->lastInsertId ();
              $idsMap['cells'][$cell->id] = $cellId;
  
              // ADD postits
              foreach ($cell->postits as $postit)
              {
                $this->executeQuery ('INSERT INTO postits',
                  $this->_getImportItemData (
                    $postit, ['cells_id' => $cellId], ['items']));
        
                $postitId = $this->lastInsertId();
                $idsMap['postits'][$postit->id] = $postitId;
  
                mkdir ("$dir/postit/$postitId");
              }
            }

            $stmt = $this->prepare ('
              SELECT content FROM postits WHERE id = ?');
            $stmt1 = $this->prepare ('
              UPDATE postits SET content = ? WHERE id = ?');

            // ADD postit attachments / pictures / plugs
            foreach ($wall->cells as $cell)
            {
              $cellId = $idsMap['cells'][$cell->id];

              foreach ($cell->postits as $postit)
              {
                $postitId = $idsMap['postits'][$postit->id];
  
                // ADD attachments
                foreach ($postit->items->attachments as $item)
                {
                  $this->executeQuery ('INSERT INTO postits_attachments',
                    $this->_getImportItemData ($item, [
                      'postits_id' => $postitId,
                      'link' => str_replace (
                        ["walls/{$wall->id}/", "postit/{$postit->id}/"],
                        ["walls/{$this->wallId}/", "postit/$postitId/"],
                        $item->link)
                    ]));

                  $fname = basename ($item->link);
                  rename ("$importPath/postit/{$postit->id}/$fname",
                          "$dir/postit/$postitId/$fname");
                }

                // If postit has associated pictures, replace URL ids in its
                // content
                if (!empty ($postit->items->pictures))
                {
                  $stmt->execute ([$postitId]);
                  $content = $stmt->fetch()['content'];

                  // ADD pictures
                  foreach ($postit->items->pictures as $item)
                  {
                    $this->executeQuery ('INSERT INTO postits_pictures',
                      $this->_getImportItemData ($item, [
                      'postits_id' => $postitId,
                      'link' => 
                        str_replace (
                          ["walls/{$wall->id}/", "postit/{$postit->id}/"],
                          ["walls/{$this->wallId}/", "postit/$postitId/"],
                          $item->link)
                    ]));

                    // Update postit content pictures src with new ids
                    $content = preg_replace (
                      "#wall/{$wall->id}/cell/{$cell->id}".
                      "/postit/{$postit->id}/picture/{$item->id}#s",
                      "wall/{$this->wallId}/cell/{$cellId}".
                      "/postit/{$postitId}/picture/{$this->lastInsertId()}",
                       $content);

                    $fname = basename ($item->link);
                    rename ("$importPath/postit/{$postit->id}/$fname",
                            "$dir/postit/$postitId/$fname");
                  }

                  $stmt1->execute ([$content, $postitId]);
                }

                // ADD plugs
                foreach ($postit->items->plugs as $item)
                {
                  $this->executeQuery ('INSERT INTO postits_plugs',
                    $this->_getImportItemData ($item, [
                      'start' => $postitId,
                      'end' => $idsMap['postits'][$item->end],
                      'label' => $item->label
                    ]));
                }
              }
            }
    
            // Performance helper:
            // Link wall creator to wall with admin access.
            $this->executeQuery ('INSERT INTO _perf_walls_users', [
              'walls_id' => $this->wallId,
              'users_id' => $this->userId,
             'access' => WPT_RIGHTS['walls']['admin']
            ]);

            $this->commit ();
          }
          catch (Exception $e)
          {
            $this->rollback ();

            $this->deleteWall (true);
  
            error_log (__METHOD__.':'.__LINE__.':'.$e->getMessage ());
            $ret['error'] = 1;
          }
        }

        @$zip->close ();

        if ($exportFile)
          unlink ($exportFile);

        if (file_exists ($importPath))
          exec ('rm -rf '.
            escapeshellarg(Wpt_common::getSecureSystemName($importPath)));
      }

      return $ret;
    }

    public function export ($clone = false)
    {
      // If a user is in more than one groups for the same wall, with
      // different rights, take the more powerful right (ORDER BY access)
      $stmt = $this->prepare ("
        SELECT *
        FROM walls
        WHERE users_id = :users_id_1
          AND walls.id = :walls_id_1

        UNION

        SELECT walls.*
        FROM walls
          INNER JOIN walls_groups
            ON walls_groups.walls_id = walls.id
          INNER JOIN users_groups
            ON users_groups.groups_id = walls_groups.groups_id
        WHERE users_groups.users_id = :users_id_2
          AND walls.id = :walls_id_2");

      $stmt->execute ([
        ':users_id_1' => $this->userId,
        ':walls_id_1' => $this->wallId,
        ':users_id_2' => $this->userId,
        ':walls_id_2' => $this->wallId
      ]);
      $data = $stmt->fetch ();

      if (!$data)
        return [
          'id' => $this->wallId,
          'removed' => _("Either you no longer have the right to access this wall, or it has been deleted")
        ];

      $data['_exportInfos'] = [
        'date' => date ('Y-m-d H:i:s'),
        'user' => $this->userId,
        'wopitsVersion' => WPT_VERSION
      ];

      $zip = new ZipArchive ();

      $dir = $this->getWallDir ();
      $zipPath1 = "$dir/wall.zip";
      $zipPath2 = "$dir/wopits-wall-{$this->wallId}.zip";

      if ($zip->open ($zipPath1, ZipArchive::CREATE) !== true)
        return ['error' => _("An error occurred while exporting wall data.")];

      // Get headers
      $stmt = $this->prepare ('SELECT * FROM headers WHERE walls_id = ?');
      $stmt->execute ([$this->wallId]);
      $data['headers'] = [];
      while ( ($header = $stmt->fetch ()) )
      {
        if ($header['picture'])
          $zip->addFile (WPT_ROOT_PATH."/{$header['picture']}",
            "header/{$header['id']}/".basename($header['picture']));

        $data['headers'][] = $header;
      }

      // Get cells and postits
      $stmt = $this->prepare ('
        SELECT * FROM cells WHERE walls_id = ?');
      $stmt->execute ([$this->wallId]);
      // Get postits
      $stmt1 = $this->prepare ('
        SELECT * FROM postits WHERE cells_id = ?');
      $stmt2 = $this->prepare ('
        SELECT * FROM postits_attachments WHERE postits_id = ?');
      $stmt3 = $this->prepare ('
        SELECT * FROM postits_pictures WHERE postits_id = ?');
      $stmt4 = $this->prepare ('
        SELECT * FROM postits_plugs WHERE start = ?');

      $data['cells'] = [];
      while ( ($cell = $stmt->fetch ()) )
      {
        $stmt1->execute ([$cell['id']]);
        $cell['postits'] = [];
        while ( ($postit = $stmt1->fetch ()) )
        {
          $postit['items'] = [];

          // Get postit attachments
          $postit['items']['attachments'] = [];
          $stmt2->execute ([$postit['id']]);
          while ( ($attachment = $stmt2->fetch ()) )
          {
            $postit['items']['attachments'][] = $attachment;

            $zip->addFile (WPT_ROOT_PATH."/{$attachment['link']}",
              "postit/{$attachment['postits_id']}/".
                basename($attachment['link']));
          }

          // Get postit pictures
          $postit['items']['pictures'] = [];
          $stmt3->execute ([$postit['id']]);
          while ( ($picture = $stmt3->fetch ()) )
          {
            $postit['items']['pictures'][] = $picture;

            $zip->addFile (WPT_ROOT_PATH."/{$picture['link']}",
              "postit/{$picture['postits_id']}/".basename($picture['link']));
          }

          // Get postit plugs
          $stmt4->execute ([$postit['id']]);
          $postit['items']['plugs'] = $stmt4->fetchAll ();

          $cell['postits'][] = $postit;
        }

        $data['cells'][] = $cell;
      }

      $zip->addFromString ("wall.json", json_encode ($data));
      $zip->close ();

      // Encapsulate first ZIP in another one
      if ($zip->open ($zipPath2, ZipArchive::CREATE) !== true)
        return ['error' => _("An error occurred while exporting wall data.")];

      //TODO Password protect $zipPath1 with $sum to prevent bots from
      //     snooping around

      $zip->addFile ($zipPath1, basename ($zipPath1));
      $sum = hash ('sha1', sha1_file ($zipPath1).WPT_SECRET_KEY);
      $zip->addFromString ("sum.sha1", $sum);
      $zip->addFromString ("README", _("Warning: if you modify the content of this archive, you will no longer be able to import it with wopits!"));
      $zip->close ();

      return ($clone) ?
        $zipPath2 :
        Wpt_common::download ([
          'type' => 'application/zip',
          'name' => basename ($zipPath2),
          'size' => filesize ($zipPath2),
          'path' => $zipPath2,
          'unlink' => true
        ]);
    }

    public function getWall ($basic = false)
    {
      $q = $this->getFieldQuote ();
      $ret = [];

      // Return walls list
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
          users_id AS ownerid,
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
          walls.users_id AS ownerid,
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
      elseif (!$basic)
      {
        // Get headers
        $stmt = $this->prepare ("
          SELECT id, ${q}type$q, ${q}order$q, width, height, title, picture
          FROM headers
          WHERE walls_id = ?
          ORDER BY ${q}type$q, ${q}order$q ASC");
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
        $stmt1 = $this->prepare ("
          SELECT
            id, width, height, top, ${q}left$q, classcolor, title, content,
            tags, creationdate, deadline, timezone, obsolete, attachmentscount
          FROM postits
          WHERE cells_id = ?");
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
        $stmt = $this->prepare ("
          SELECT start, ${q}end$q, label
          FROM postits_plugs
          WHERE walls_id = ?");
        $stmt->execute ([$this->wallId]); 
        $data['postits_plugs'] = $stmt->fetchAll ();
       }

      // Check if the wall is shared with other users
      $stmt = $this->prepare ('
        SELECT 1 FROM walls_groups WHERE walls_id = ? LIMIT 1');
      $stmt->execute ([$this->wallId]);
      $data['shared'] = boolval ($stmt->fetch ());

      return $data;
    }

    public function getUsersview (array $usersIds)
    {
      $ret = ['list' => []];
      // No quotes needed. $usersIds contains trusted values (from the
      // WebSocket server)
      $ids = implode ("','", $usersIds);

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
         AND users_groups.users_id IN ('$ids')
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
         AND users.id IN ('$ids')
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
        {
          $ret['list'][] = $item;
          $tmp[$item['id']] = 1;
        }
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
        return Wpt_common::download ([
          'type' => $r['filetype'],
          'name' => basename ($r['picture']),
          'size' => $r['filesize'],
          'path' => WPT_ROOT_PATH.$r['picture']
        ]);
    }

    public function deleteWallColRow ($args)
    {
      $q = $this->getFieldQuote ();
      $item = $args['item'];
      $itemPos = $args['itemPos'];
      $dir = $this->getWallDir ();
      $ret = [];

      if ($item != 'col' && $item != 'row')
        return ['error' => _("Access forbidden")];

      $r = $this->checkWallAccess (WPT_RIGHTS['walls']['admin']);
      if (!$r['ok'])
        return (isset ($r['id'])) ? $r : ['error' => _("Access forbidden")];

      try
      {
        $this->beginTransaction ();

        // Delete headers documents
        $stmt = $this->prepare("
          SELECT id FROM headers
          WHERE walls_id = :walls_id
            AND ${q}type$q = :type AND ${q}order$q = :order");
        $stmt->execute ([
          ':walls_id' => $this->wallId,
          ':type' => $item,
          ':order' => $itemPos]);
  
        exec ('rm -rf '.
          Wpt_common::getSecureSystemName(
            "$dir/header/".($stmt->fetch ())['id']));

        // Delete header
        $this
          ->prepare("
            DELETE FROM headers
            WHERE walls_id = :walls_id
              AND ${q}type$q = :type
              AND ${q}order$q = :order")
          ->execute ([
            ':walls_id' => $this->wallId,
            ':type' => $item,
            ':order' => $itemPos
          ]);

        // Reordonate headers
        $this
          ->prepare("
            UPDATE headers SET
              ${q}order$q = ${q}order$q - 1
            WHERE walls_id = :walls_id
              AND ${q}type$q = :type
              AND ${q}order$q > :order")
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
      $q = $this->getFieldQuote ();
      $item = $args['item'];
      $dir = $this->getWallDir ();
      $ret = [];

      if ($item != 'col' && $item != 'row')
        return ['error' => _("Access forbidden")];

      $r = $this->checkWallAccess (WPT_RIGHTS['walls']['admin']);
      if (!$r['ok'])
        return (isset ($r['id'])) ? $r :
          ['error' =>
             _("You must have admin access to perform this action.")];

      try
      {
        $this->beginTransaction ();

        $stmt = $this->prepare("
        SELECT ${q}order$q FROM headers
        WHERE walls_id = :walls_id
          AND ${q}type$q = :type ORDER BY ${q}order$q DESC LIMIT 1");
        $stmt->execute ([
          ':walls_id' => $this->wallId,
          ':type' => $item
        ]);
        $order = $stmt->fetch()['order'];
  
        $this->executeQuery ('INSERT INTO headers', [
          'walls_id' => $this->wallId,
          'type' => $item,
          'order' => $order + 1,
          'width' => ($item == 'col') ? 300 : 51,
          'height' => ($item == 'row') ? 200 : 42,
          'title' => ' '
        ]);

        mkdir ("$dir/header/".$this->lastInsertId());
  
        if ($item == 'col')
        {
          $stmt = $this->prepare("
            SELECT row, col FROM cells
            WHERE walls_id = ? AND $item = ?");
          $stmt->execute ([$this->wallId, $order]);
        }
        else
        {
          $stmt = $this->prepare('
            SELECT row, col FROM cells
            WHERE walls_id = ? ORDER BY row DESC, col ASC');
          $stmt->execute ([$this->wallId]);
        }
  
        $r = null;
        while ($e = $stmt->fetch ())
        {
          $data = [ 
            'width' => 300,
            'height' => 200,
            'walls_id' => $this->wallId
          ];
  
          // Col
          if ($item == 'col')
          {
            $data['row'] = $e['row'];
            $data['col'] = $order + 1;
          }
          // Row
          else
          {
            if ($r == null)
              $r = $e['row'];
            elseif ($e['row'] != $r)
              break 1;
  
            $data['row'] = $e['row'] + 1;
            $data['col'] = $e['col'];
          }
  
          $this->executeQuery ('INSERT INTO cells', $data);
        }
  
        if ($item == 'col')
          $this
            ->prepare('UPDATE walls SET width = width + 300 WHERE id = ?')
            ->execute ([$this->wallId]);

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

      if ($noGrid)
      {
        $colsCount = 1;
        $rowsCount = 1;

        if (!$this->data->width)
          $this->data->width = 300;
        if (!$this->data->height)
          $this->data->height = 300;
      }
      else
      {
        $colsCount = intval (trim ($this->data->colsCount));
        $rowsCount = intval (trim ($this->data->rowsCount));

        if (!$colsCount)
          $colsCount = 3;
        if (!$rowsCount)
          $rowsCount = 3;
      }

      if ($this->checkWallName ($this->data->name))
        return ['error_msg' => _("A wall with the same name already exists.")];

      $wall = [
        'name' => $this->data->name,
        'width' => ($noGrid) ? $this->data->width : $colsCount * 300,
        'headers' => ['cols' => [], 'rows' => []],
        'cells' => []
      ];

      $cellWidth = ($noGrid) ? $this->data->width : 300;
      $cellHeight = ($noGrid) ? $this->data->height : 200;

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

        //FIXME //TODO factorization with import()
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
        for ($i = 0, $iLen = count($wall['headers']['cols']); $i < $iLen; $i++)
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
        for ($i = 0, $iLen = count($wall['headers']['rows']); $i < $iLen; $i++)
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
        for ($i = 0, $iLen = count($wall['cells']); $i < $iLen; $i++)
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

        // Performance helper:
        // Link wall creator to wall with admin access.
        $this->executeQuery ('INSERT INTO _perf_walls_users', [
          'walls_id' => $this->wallId,
          'users_id' => $this->userId,
          'access' => WPT_RIGHTS['walls']['admin']
        ]);

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
  
    public function deleteWall ($force = false)
    {
      $ret = [];

      if (!$force)
      {
        $r = $this->checkWallAccess (WPT_RIGHTS['walls']['admin']);
        if (!$r['ok'])
          return (isset ($r['id'])) ? $r : ['error' => _("Access forbidden")];
      }

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

    public function updateCells ()
    {
      $newTransaction = (!PDO::inTransaction ());

      try
      {
        if ($newTransaction)
          $this->beginTransaction ();

        for ($i = 0, $iLen = count($this->data->cells); $i < $iLen; $i++)
        {
          $cell = $this->data->cells[$i];
  
          $this->executeQuery ('UPDATE cells', [
            'width' => $cell->width,
            'height' => $cell->height
           ],
           ['id' => $cell->id]);
  
          if (!empty ($cell->postits))
          {
            for ($j = 0, $jLen = count($cell->postits); $j < $jLen; $j++)
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

    public function updateHeaders ()
    {
      $newTransaction = (!PDO::inTransaction ());

      try
      {
        if ($newTransaction)
          $this->beginTransaction ();

        foreach (['cols', 'rows'] as $type)
        {
          for ($i = 0, $iLen = count ($this->data->headers->$type);
                 $i < $iLen; $i++)
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
