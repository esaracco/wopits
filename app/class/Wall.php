<?php

namespace Wopits;

require_once (__DIR__.'/../prepend.php');

use Wopits\Common;
use Wopits\DbCache;
use Wopits\Base;

class Wall extends Base
{
  public function checkWallAccess ($requiredRole)
  {
    // Wall admin has full access
    $in = WPT_WRIGHTS_ADMIN;

    // If access needs only write right, allow admin and rw roles
    if ($requiredRole == WPT_WRIGHTS_RW)
      $in .= ','.WPT_WRIGHTS_RW;
    // If access need at least read right, allow admin, ro and rw roles
    elseif ($requiredRole == WPT_WRIGHTS_RO)
      $in .= ','.WPT_WRIGHTS_RO.','.WPT_WRIGHTS_RW;

    $stmt = $this->prepare ("
      SELECT 1 FROM _perf_walls_users
      WHERE users_id = ? AND walls_id = ? AND access IN($in)
      LIMIT 1");
    $stmt->execute ([$this->userId, $this->wallId]);

    return ['ok' => !empty ($stmt->fetch())];
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

  protected function getWallName ()
  {
    if (!$this->wallName)
    {
      $stmt = $this->prepare ('SELECT name FROM walls WHERE id = ?');
      $stmt->execute ([$this->wallId]);

      $this->wallName = $stmt->fetch()['name'];
    }

    return $this->wallName;
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
      WHERE access = '.WPT_WRIGHTS_ADMIN.'
        AND groups_id IS NOT NULL
        AND walls_id = ?
        AND users_id = ?
      LIMIT 1');
    $stmt->execute ([$this->wallId, $userId]);

    return $stmt->fetch ();
  }

  public function getWallInfos ()
  {
    $r = $this->checkWallAccess (WPT_WRIGHTS_RO);
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

    $r = $this->checkWallAccess (WPT_WRIGHTS_ADMIN);
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

        $file = Common::getSecureSystemName (
          "$dir/$rdir/img-".hash('sha1', $this->data->content).".$ext");

        file_put_contents (
          $file, base64_decode(str_replace(' ', '+', $content)));

        if (!file_exists ($file))
          throw new \Exception (_("An error occured while uploading file."));

        $stmt = $this->prepare ('SELECT picture FROM headers WHERE id = ?');
        $stmt->execute ([$headerId]);
        $previousPicture = $stmt->fetch()['picture'];

        list ($file, $this->data->item_type) =
          Common::resizePicture ($file, 100);

        $img = "$wdir/$rdir/".basename($file);
        $this->executeQuery ('UPDATE headers', [
          'picture' => $img,
          'filetype' => $this->data->item_type,
          'filesize' => filesize ($file)
        ],
        ['id' => $headerId]);

        $ret['img'] = $img;

        // delete old picture if needed
        if ($previousPicture && $previousPicture != $img)
          Common::rm (WPT_ROOT_PATH.$previousPicture);
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

  public function deleteHeaderPicture ($args)
  {
    $ret = [];
    $headerId = $args['headerId'];

    $r = $this->checkWallAccess (WPT_WRIGHTS_ADMIN);
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

      Common::rm (WPT_ROOT_PATH.$r['picture']);
    }
    catch (\Exception $e)
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
    $versionErrorMsg = _("The file you are trying to import is not compatible with the current wopits version.");

    if (!$exportFile)
      list (, $content, $error) = $this->getUploadedFileInfos ($this->data);

    $zip = new \ZipArchive ();

    if ($error)
    {
      $ret['error'] = $error;
    }
    else
    {
      $tmpPath = "{$this->getUserDir()}/tmp";
      $importPath = "$tmpPath/import";
      $zipPath = "$importPath/import.zip";

      //FIXME //TODO Remove this later (done in user account creation)
      if (!file_exists ($tmpPath))
        mkdir ($tmpPath);

      if (file_exists ($importPath))
        Common::rm ($importPath);

      mkdir ($importPath);

      if ($exportFile)
        $zipPath = $exportFile;
      else
        file_put_contents (
          $zipPath, base64_decode(str_replace(' ', '+', $content)));

      // Extract ZIP
      if (!file_exists ($zipPath) || $zip->open ($zipPath) !== true ||
          $zip->extractTo ($importPath) !== true ||
          !($wall=@json_decode(@file_get_contents("$importPath/wall.json"))))
      {
        $ret['error'] = $errorMsg;
      }
      // Check export file compatibility with this wopits version
      elseif (!preg_match ('/^([0-9\.]+)/',
                $wall->_exportInfos->wopitsVersion, $m) ||
              $m[1] < WPT_EXPORT_MIN_VERSION)
      {
        $ret['error'] = $versionErrorMsg;
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

        // Change wall name if it already exists
        $stmt = $this->prepare ('
          SELECT name FROM walls WHERE users_id = ?');
        $stmt->execute ([$this->userId]);

        if ( ($names = $stmt->fetchAll ()) )
        {
          $names = array_column ($names, 'name');
          $maxLength = DbCache::getFieldLength ('walls', 'name');
          $v = '';
          $i = 0;
          while (array_search ("$wallName$v", $names) !== false)
          {
            $v = ' ('.(++$i).')';
            if (mb_strlen ("$wallName$v") > $maxLength)
              $wallName = mb_substr ($wallName, 0,
                                     $maxLength - mb_strlen ($v));
          }
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

              $this->executeQuery ('UPDATE headers', [
                'picture' => str_replace (
                  ["walls/{$wall->id}/", "header/{$item->id}/"],
                  ["walls/{$this->wallId}/", "header/$headerId/"],
                  $item->picture)
              ],
              ['id' => $headerId]);
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

                $this->executeQuery ('UPDATE postits',
                  ['content' => $content],
                  ['id' => $postitId]);
              }

              // ADD plugs
              foreach ($postit->items->plugs as $item)
              {
                $this->executeQuery ('INSERT INTO postits_plugs',
                  $this->_getImportItemData ($item, [
                    'item_start' => $postitId,
                    'item_end' => $idsMap['postits'][$item->item_end],
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
           'access' => WPT_WRIGHTS_ADMIN
          ]);

          $this->commit ();
        }
        catch (\Exception $e)
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
        Common::rm ($importPath);
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

    $data['_exportInfos'] = [
      'date' => date ('Y-m-d H:i:s'),
      'user' => $this->userId,
      'wopitsVersion' => WPT_VERSION
    ];

    $zip = new \ZipArchive ();

    $dir = $this->getWallDir ();
    // This is not be deleted at the end of the process.
    //TODO Make a cron that will purge old wall.zip files for all walls.
    $zipPath = "$dir/wopits-wall-{$this->wallId}.zip";

    if ($zip->open ($zipPath, \ZipArchive::CREATE) !== true)
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
      SELECT * FROM postits_plugs WHERE item_start = ?');

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

    return ($clone) ?
      $zipPath :
      Common::download ([
        'item_type' => 'application/zip',
        'name' => basename ($zipPath),
        'size' => filesize ($zipPath),
        'path' => $zipPath,
        'unlink' => true
      ]);
  }

  public function getWall ($withAlerts = false, $basic = false)
  {
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
          ".WPT_WRIGHTS_ADMIN." AS access,
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
        ".WPT_WRIGHTS_ADMIN." AS access
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

    if ( !($data = $stmt->fetch ()) )
      return ['removed' => true];

    if (!$basic)
    {
      $stmt = $this->prepare ('
        SELECT displayexternalref
        FROM _perf_walls_users WHERE walls_id = ? AND users_id = ? LIMIT 1');
      $stmt->execute ([$this->wallId, $this->userId]);
      $data['displayexternalref'] = $stmt->fetch()['displayexternalref'];

      // Get headers
      $stmt = $this->prepare ("
        SELECT id, item_type, item_order, width, height, title, picture
        FROM headers
        WHERE walls_id = ?
        ORDER BY item_type, item_order ASC");
      $stmt->execute ([$this->wallId]);
      $data['headers'] = ['cols' => [], 'rows' => []];
      while ($row = $stmt->fetch ())
      {
        $type = $row['item_type'];

        unset ($row['item_type']);
        unset ($row['item_order']);

        $data['headers'][$type.'s'][] = $row;
      }

      // Get cells and postits
      $stmt = $this->prepare ('
        SELECT id, height, width, item_col, item_row
        FROM cells
        WHERE walls_id = ?');
      $stmt->execute ([$this->wallId]);
      // Get postits
      $stmt1 = $this->prepare (($withAlerts) ?
        "SELECT
           postits.id, width, height, item_top, item_left, classcolor, title,
           content, tags, creationdate, deadline, timezone, obsolete,
           attachmentscount, postits_alerts.alertshift
         FROM postits
           LEFT JOIN postits_alerts
             ON postits_alerts.postits_id = postits.id
               AND postits_alerts.users_id = ?
         WHERE cells_id = ?"
        :
        "SELECT
           id, width, height, item_top, item_left, classcolor, title,
           content, tags, creationdate, deadline, timezone, obsolete,
           attachmentscount
         FROM postits
         WHERE cells_id = ?");
      $data['cells'] = [];
      while ($row = $stmt->fetch ())
      {
        $stmt1->execute (($withAlerts) ? [$this->userId, $row['id']] :
                                         [$row['id']]);
        $row['postits'] = [];
        while ($row1 = $stmt1->fetch ())
          $row['postits'][] = $row1;

        $data['cells'][] = $row;
      }

      // Get postits plugs
      $stmt = $this->prepare ("
        SELECT item_start, item_end, label
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
       1 AS access
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

    $r = $this->checkWallAccess (WPT_WRIGHTS_RO);
    if (!$r['ok'])
      return (isset ($r['id'])) ? $r : ['error' => _("Access forbidden")];

    $stmt = $this->prepare ('
      SELECT picture, filetype, filesize FROM headers WHERE id = ?');
    $stmt->execute ([$headerId]);

    if ( ($r = $stmt->fetch ()) )
      return Common::download ([
        'item_type' => $r['filetype'],
        'name' => basename ($r['picture']),
        'size' => $r['filesize'],
        'path' => WPT_ROOT_PATH.$r['picture']
      ]);
  }

  public function deleteWallColRow ($args)
  {
    $item = $args['item'];
    $itemPos = $args['itemPos'];
    $dir = $this->getWallDir ();
    $ret = [];

    if ($item != 'col' && $item != 'row')
      return ['error' => _("Access forbidden")];

    $r = $this->checkWallAccess (WPT_WRIGHTS_ADMIN);
    if (!$r['ok'])
      return (isset ($r['id'])) ? $r : ['error' => _("Access forbidden")];

    try
    {
      $this->beginTransaction ();

      // Delete headers documents
      $stmt = $this->prepare("
        SELECT id FROM headers
        WHERE walls_id = :walls_id
          AND item_type = :item_type AND item_order = :item_order");
      $stmt->execute ([
        ':walls_id' => $this->wallId,
        ':item_type' => $item,
        ':item_order' => $itemPos]);

      Common::rm ("$dir/header/".($stmt->fetch ())['id']);

      // Delete header
      $this
        ->prepare("
          DELETE FROM headers
          WHERE walls_id = :walls_id
            AND item_type = :item_type
            AND item_order = :item_order")
        ->execute ([
          ':walls_id' => $this->wallId,
          ':item_type' => $item,
          ':item_order' => $itemPos
        ]);

      // Reordonate headers
      $this
        ->prepare("
          UPDATE headers SET
            item_order = item_order - 1
          WHERE walls_id = :walls_id
            AND item_type = :item_type
            AND item_order > :item_order")
        ->execute ([
            ':walls_id' => $this->wallId,
            ':item_type' => $item,
            ':item_order' => $itemPos
          ]);

      // Delete files for all postits
      $stmt = $this->prepare ("
        SELECT postits.id
        FROM cells
          INNER JOIN postits ON postits.cells_id = cells.id
        WHERE cells.walls_id = :walls_id
          AND cells.item_$item = :item");
      $stmt->execute ([
        ':walls_id' => $this->wallId,
        ':item' => $itemPos
      ]);
      while ($row = $stmt->fetch ())
        Common::rm ("$dir/postit/{$row['id']}");

      // Delete
      $this
        ->prepare("
          DELETE FROM cells
          WHERE walls_id = ? AND item_$item = ?")
        ->execute ([$this->wallId, $itemPos]);

      // Reordonate
      $this
        ->prepare("
          UPDATE cells SET
            item_$item = item_$item - 1
          WHERE walls_id = ?
            AND item_$item > ?")
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
    catch (\Exception $e)
    {
      $this->rollback ();

      error_log (__METHOD__.':'.__LINE__.':'.$e->getMessage ());
      $ret['error'] = 1;
    }

    return $ret;
  }

  public function createWallColRow ($args)
  {
    $item = $args['item'];
    $dir = $this->getWallDir ();
    $ret = [];

    if ($item != 'col' && $item != 'row')
      return ['error' => _("Access forbidden")];

    $r = $this->checkWallAccess (WPT_WRIGHTS_ADMIN);
    if (!$r['ok'])
      return (isset ($r['id'])) ? $r :
        ['error' =>
           _("You must have admin access to perform this action.")];

    try
    {
      $this->beginTransaction ();

      $stmt = $this->prepare("
      SELECT item_order FROM headers
      WHERE walls_id = :walls_id
        AND item_type = :item_type ORDER BY item_order DESC LIMIT 1");
      $stmt->execute ([
        ':walls_id' => $this->wallId,
        ':item_type' => $item
      ]);
      $order = $stmt->fetch()['item_order'];

      $this->executeQuery ('INSERT INTO headers', [
        'walls_id' => $this->wallId,
        'item_type' => $item,
        'item_order' => $order + 1,
        'width' => ($item == 'col') ? 300 : 51,
        'height' => ($item == 'row') ? 200 : 42,
        'title' => ' '
      ]);

      mkdir ("$dir/header/".$this->lastInsertId());

      if ($item == 'col')
      {
        $stmt = $this->prepare("
          SELECT item_row, item_col FROM cells
          WHERE walls_id = ? AND item_$item = ?");
        $stmt->execute ([$this->wallId, $order]);
      }
      else
      {
        $stmt = $this->prepare('
          SELECT item_row, item_col FROM cells
          WHERE walls_id = ? ORDER BY item_row DESC, item_col ASC');
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
          $data['item_row'] = $e['item_row'];
          $data['item_col'] = $order + 1;
        }
        // Row
        else
        {
          if ($r == null)
            $r = $e['item_row'];
          elseif ($e['item_row'] != $r)
            break 1;

          $data['item_row'] = $e['item_row'] + 1;
          $data['item_col'] = $e['item_col'];
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
    catch (\Exception $e)
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
          'item_row' => $row,
          'item_col' => $col,
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
          'item_type' => 'col',
          'item_order' => $i,
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
          'item_type' => 'row',
          'item_order' => $i,
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
          'item_row' => $cell['item_row'],
          'item_col' => $cell['item_col']
        ]);
      }

      // Performance helper:
      // Link wall creator to wall with admin access.
      $this->executeQuery ('INSERT INTO _perf_walls_users', [
        'walls_id' => $this->wallId,
        'users_id' => $this->userId,
        'access' => WPT_WRIGHTS_ADMIN
      ]);

      $this->commit ();

      $ret = $this->getWall ();
    }
    catch (\Exception $e)
    {
      $this->rollback ();

      error_log (__METHOD__.':'.__LINE__.':'.$e->getMessage ());
      $ret['error'] = 1;
    }

    return $ret;
  }

  public function deleteWall ($force = false, $returnName = false)
  {
    $ret = [];

    if (!$force)
    {
      $r = $this->checkWallAccess (WPT_WRIGHTS_ADMIN);
      if (!$r['ok'])
        return (isset ($r['id'])) ? $r : ['error' => _("Access forbidden")];
    }

    try
    {
      if ($returnName)
      {
        $stmt = $this->prepare ('SELECT name FROM walls WHERE id = ?');
        $stmt->execute ([$this->wallId]);
        $ret['name'] = $stmt->fetch()['name'];
      }

      $this
        ->prepare('DELETE FROM walls WHERE id = ?')
        ->execute ([$this->wallId]);

      Common::rm ($this->getWallDir());
    }
    catch (\Exception $e)
    {
      error_log (__METHOD__.':'.__LINE__.':'.$e->getMessage ());
      $ret['error'] = 1;
    }

    return $ret;
  }

  public function updateCells ()
  {
    $newTransaction = (!\PDO::inTransaction ());

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
              'item_top' => $postit->item_top,
              'item_left' => $postit->item_left
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
    catch (\Exception $e)
    {
      if ($newTransaction)
        $this->rollback ();

      throw $e;
    }
  }

  public function updateHeaders ()
  {
    $newTransaction = (!\PDO::inTransaction ());

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
    catch (\Exception $e)
    {
      if ($newTransaction)
        $this->rollback ();

      throw $e;
    }
  }
}
