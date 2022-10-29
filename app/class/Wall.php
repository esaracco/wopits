<?php

namespace Wopits;

require_once(__DIR__.'/../config.php');

class Wall extends Base {
  protected function buildAccessRightsSQL(int $requiredRole) {
    // Wall admin has full access
    $in = WPT_WRIGHTS_ADMIN;

    // If access needs only write right, allow admin and rw roles
    if ($requiredRole === WPT_WRIGHTS_RW) {
      $in .= ','.WPT_WRIGHTS_RW;
    // If access need at least read right, allow admin, ro and rw roles
    } elseif ($requiredRole === WPT_WRIGHTS_RO) {
      $in .= ','.WPT_WRIGHTS_RO.','.WPT_WRIGHTS_RW;
    }

    return $in;
  }

  public function checkWallAccess(int $requiredRole):array {
    ($stmt = $this->db->prepare('
      SELECT 1 FROM _perf_walls_users
      WHERE users_id = ? AND walls_id = ?
        AND access IN('.$this->buildAccessRightsSQL($requiredRole).')
      LIMIT 1'))->execute([$this->userId, $this->wallId]);

    return ['ok' => !empty($stmt->fetch())];
  }

  public function checkWallName(string $name):int {
    ($stmt = $this->db->prepare('
      SELECT id FROM walls WHERE id <> ? AND name = ? AND users_id = ?'))
       ->execute([$this->wallId ? $this->wallId : 0, $name, $this->userId]);

    return $stmt->rowCount();
  }

  protected function getWallName():string {
    if (!$this->wallName) {
      ($stmt = $this->db->prepare('SELECT name FROM walls WHERE id = ?'))
        ->execute([$this->wallId]);
      $this->wallName = $stmt->fetch(\PDO::FETCH_COLUMN, 0);
    }

    return $this->wallName;
  }

  protected function isWallCreator(int $userId):int {
    ($stmt = $this->db->prepare('
      SELECT 1 FROM walls WHERE id = ? AND users_id = ?'))
       ->execute([$this->wallId, $userId]);

    return $stmt->rowCount();
  }

  protected function isWallDelegateAdmin(int $userId):int {
    ($stmt = $this->db->prepare('
      SELECT 1 FROM _perf_walls_users
      WHERE access = '.WPT_WRIGHTS_ADMIN.'
        AND groups_id IS NOT NULL
        AND walls_id = ?
        AND users_id = ?
      LIMIT 1'))
       ->execute([$this->wallId, $userId]);

    return $stmt->rowCount();
  }

  public function getWallInfos():array {
    $r = $this->checkWallAccess(WPT_WRIGHTS_RO);
    if (!$r['ok']) {
      return ['error' => _("Access forbidden")];
    }

    ($stmt = $this->db->prepare('
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
      WHERE walls.id = ?'))
       ->execute([$this->wallId]);

    if ( ($ret = $stmt->fetch()) ) {
      ($stmt = $this->db->prepare('
        SELECT groups_id FROM _perf_walls_users
        WHERE walls_id = ? AND users_id = ?'))
         ->execute([$this->wallId, $this->userId]);
      $ret['groups'] = $stmt->fetchAll(\PDO::FETCH_COLUMN, 0);
    }

    return $ret;
  }

  public function searchUser(array $args):array {
    $ret = ['users' => null];

    $r = $this->checkWallAccess(WPT_WRIGHTS_RO);
    if (!$r['ok']) {
      return ['error' => _("Access forbidden")];
    }

    $search = $args['search'];

    if (empty($search) || ($search = Helper::unaccent($args['search'])) ) {
      ($stmt = $this->db->prepare('
         SELECT DISTINCT(u.username), u.fullname
         FROM users AS u
           INNER JOIN _perf_walls_users AS pwu ON pwu.users_id = u.id
         WHERE walls_id = ?
           AND u.id <> ?
           AND u.searchdata LIKE ?
         LIMIT 10'))
         ->execute([$this->wallId, $this->userId, "%$search%"]);

      $ret['users'] = $stmt->fetchAll();
    }

    return $ret;
  }

  public function addHeaderPicture(array $args):array {
    $ret = [];
    $headerId = $args['headerId'];

    $r = $this->checkWallAccess(WPT_WRIGHTS_ADMIN);
    if (!$r['ok']) {
      return ['error' => _("Access forbidden")];
    }

    list($ext, $content, $error) = $this->getUploadedFileInfos($this->data);

    if ($error) {
      $ret['error'] = $error;
   }  else {
      try {
        $dir = $this->getWallDir();
        $wdir = $this->getWallDir('web');
        $rdir = "header/$headerId";

        $file = Helper::getSecureSystemName(
          "$dir/$rdir/img-".hash('sha1', $this->data->content).".$ext");

        file_put_contents(
          $file, base64_decode(str_replace(' ', '+', $content)));

        if (!file_exists($file)) {
          throw new \Exception(_("An error occurred while uploading"));
        }

        ($stmt = $this->db->prepare('
           SELECT picture FROM headers WHERE id = ?'))
             ->execute([$headerId]);
        $previousPicture = $stmt->fetch(\PDO::FETCH_COLUMN, 0);

        list($file, $this->data->item_type) = Helper::resizePicture($file, 100);

        $img = "$wdir/$rdir/".basename($file);
        $this->executeQuery('UPDATE headers', [
          'picture' => $img,
          'filetype' => $this->data->item_type,
          'filesize' => filesize($file),
        ],
        ['id' => $headerId]);

        $ret['img'] = $img;

        // delete old picture if needed
        if ($previousPicture && $previousPicture !== $img) {
          Helper::rm(WPT_ROOT_PATH.$previousPicture);
        }
      }
      catch(\ImagickException $e) {
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

  public function deleteHeaderPicture(array $args):array {
    $ret = [];
    $headerId = $args['headerId'];

    $r = $this->checkWallAccess(WPT_WRIGHTS_ADMIN);
    if (!$r['ok']) {
      return ['error' => _("Access forbidden")];
    }

    try {
      ($stmt = $this->db->prepare('SELECT picture FROM headers WHERE id = ?'))
        ->execute([$headerId]);
      $r = $stmt->fetch();

      $this->executeQuery('UPDATE headers', [
        'picture' => null,
        'filetype' => null,
        'filesize' => null,
      ],
      ['id' => $headerId]); 

      Helper::rm(WPT_ROOT_PATH.$r['picture']);
    } catch(\Exception $e) {
      error_log(__METHOD__.':'.__LINE__.':'.$e->getMessage());
      $ret['error'] = 1;
    }

    return $ret;
  }

  public function clone():array {
    return $this->import($this->export(true));
  }

  public function setBasicProperties():void {
    $this->executeQuery('UPDATE walls', [
      'name' => $this->data->name,
      'description' => $this->data->description,
    ],
    ['id' => $this->wallId]);
  }

  public function import(string $exportFile = null):array {
    $ret = [];
    $error = null;
    $errorMsg = $exportFile ?
      _("An error occurred while cloning the wall") :
      _("An error occurred while processing import");
    $versionErrorMsg = _("The file to import is not compatible with the current wopits version");

    if (!$exportFile) {
      list(, $content, $error) = $this->getUploadedFileInfos($this->data);
    }

    $zip = new \ZipArchive();

    if ($error) {
      $ret['error'] = $error;
    } else {
      $tmpPath = "{$this->getUserDir()}/tmp";
      $importPath = "$tmpPath/import";
      $zipPath = "$importPath/import.zip";

      //FIXME //TODO Remove this later (done in user account creation)
      if (!file_exists($tmpPath)) {
        mkdir($tmpPath);
      }

      if (file_exists($importPath)) {
        Helper::rm($importPath);
      }

      mkdir($importPath);

      if ($exportFile) {
        $zipPath = $exportFile;
      } else {
        $content = str_replace(' ', '+', $content);
        $content = base64_decode($content);
        file_put_contents($zipPath, $content);
        unset($content);
      }

      // Extract ZIP
      if (!file_exists($zipPath) ||
          $zip->open($zipPath) !== true ||
          $zip->extractTo($importPath) !== true ||
          !($wall=@json_decode(@file_get_contents("$importPath/wall.json")))) {
        $ret['error'] = $errorMsg;
      // Check export file compatibility with this wopits version
      } elseif (!preg_match('/^([0-9\.]+)/',
                   $wall->_exportInfos->wopitsVersion, $m) ||
              $m[1] < WPT_EXPORT_MIN_VERSION) {
        $ret['error'] = $versionErrorMsg;
      // Process ZIP data
      } else {
        // Get old cells and postits ids for mapping with new ids
        $idsMap = ['cells' => [], 'postits' => []];
        foreach ($wall->cells as $cell) {
          $idsMap['cells'][$cell->id] = null;

          foreach ($cell->postits as $postit) {
            $idsMap['postits'][$postit->id] = null;
          }
        }

        $wallName = $wall->name;

        // Change wall name if it already exists
        ($stmt = $this->db->prepare('
          SELECT name FROM walls WHERE users_id = ?'))
           ->execute([$this->userId]);

        if ( ($names = $stmt->fetchAll(\PDO::FETCH_COLUMN, 0)) ) {
          $maxLength = DbCache::getFieldLength('walls', 'name');
          $v = '';
          $i = 0;
          while (array_search("$wallName$v", $names) !== false) {
            $v = ' ('.(++$i).')';
            if (mb_strlen("$wallName$v") > $maxLength) {
              $wallName = mb_substr($wallName, 0, $maxLength - mb_strlen($v));
            }
          }
          $wallName = "$wallName$v";
        }

        try {
          $this->db->beginTransaction();

          $dir = $this->_insertWall([
            'users_id' => $this->userId,
            'width' => $wall->width,
            'name' => $wallName,
            'description' => $wall->description,
            'creationdate' => $wall->creationdate,
          ]);
  
          $ret = ['wallId' => $this->wallId];
    
          // INSERT col & row headers
          foreach ($wall->headers as $item) {
            $headerId = $this->_insertHeader(
              $this->_getImportItemData($item), $dir);

            // ADD header picture
            if ($item->picture) {
              $fname = basename($item->picture);
              rename("$importPath/header/{$item->id}/$fname",
                     "$dir/header/$headerId/$fname");

              $this->executeQuery('UPDATE headers', [
                'picture' => str_replace(
                  ["walls/{$wall->id}/", "header/{$item->id}/"],
                  ["walls/{$this->wallId}/", "header/$headerId/"],
                  $item->picture),
              ],
              ['id' => $headerId]);
            }
          }

          // INSERT cells
          foreach ($wall->cells as $cell) {
            $this->executeQuery('INSERT INTO cells',
              $this->_getImportItemData($cell, null, ['postits']));

            $cellId = $this->db->lastInsertId();
            $idsMap['cells'][$cell->id] = $cellId;

            // ADD postits
            foreach ($cell->postits as $postit) {
              $this->executeQuery('INSERT INTO postits',
                $this->_getImportItemData(
                  $postit, ['cells_id' => $cellId], ['items']));
      
              $postitId = $this->db->lastInsertId();
              $idsMap['postits'][$postit->id] = $postitId;

              mkdir("$dir/postit/$postitId");
            }
          }

          $stmtSP = $this->db->prepare('
            SELECT content FROM postits WHERE id = ?');

          // INSERT postit attachments / pictures / plugs
          foreach ($wall->cells as $cell) {
            $cellId = $idsMap['cells'][$cell->id];

            foreach ($cell->postits as $postit) {
              $postitId = $idsMap['postits'][$postit->id];

              // ADD attachments
              foreach ($postit->items->attachments as $item) {
                $this->executeQuery('INSERT INTO postits_attachments',
                  $this->_getImportItemData($item, [
                    'postits_id' => $postitId,
                    'link' => str_replace(
                      ["walls/{$wall->id}/", "postit/{$postit->id}/"],
                      ["walls/{$this->wallId}/", "postit/$postitId/"],
                      $item->link),
                  ]));

                $fname = basename($item->link);
                rename("$importPath/postit/{$postit->id}/$fname",
                       "$dir/postit/$postitId/$fname");
              }

              // If postit has associated pictures, replace URL ids in its
              // content
              if (!empty($postit->items->pictures)) {
                $stmtSP->execute([$postitId]);
                $content = $stmtSP->fetch(\PDO::FETCH_COLUMN, 0);

                // ADD pictures
                foreach ($postit->items->pictures as $item) {
                  $this->executeQuery('INSERT INTO postits_pictures',
                    $this->_getImportItemData($item, [
                    'postits_id' => $postitId,
                    'link' => str_replace(
                      ["walls/{$wall->id}/", "postit/{$postit->id}/"],
                      ["walls/{$this->wallId}/", "postit/$postitId/"],
                      $item->link),
                  ]));

                  // Update postit content pictures src with new ids
                  $content = preg_replace(
                    "#wall/{$wall->id}/cell/{$cell->id}".
                    "/postit/{$postit->id}/picture/{$item->id}#s",
                    "wall/{$this->wallId}/cell/{$cellId}".
                    "/postit/{$postitId}/picture/{$this->db->lastInsertId()}",
                     $content);

                  $fname = basename($item->link);
                  rename("$importPath/postit/{$postit->id}/$fname",
                         "$dir/postit/$postitId/$fname");
                }

                $this->executeQuery('UPDATE postits',
                  ['content' => $content],
                  ['id' => $postitId]);
              }

              // INSERT plugs
              foreach ($postit->items->plugs as $item) {
                $this->executeQuery('INSERT INTO postits_plugs',
                  $this->_getImportItemData($item, [
                    'item_start' => $postitId,
                    'item_end' => $idsMap['postits'][$item->item_end],
                  ]));
              }
            }
          }
  
          $this->db->commit();
        } catch(\Exception $e) {
          $this->db->rollBack();

          $this->deleteWall(true);

          error_log(__METHOD__.':'.__LINE__.':'.$e->getMessage());
          $ret['error'] = 1;
        }
      }

      @$zip->close();

      if ($exportFile) {
        unlink($exportFile);
      }

      if (file_exists($importPath)) {
        Helper::rm($importPath);
      }
    }

    return $ret;
  }

  public function export(bool $clone = false):?string {
    // If a user is in more than one groups for the same wall, with
    // different rights, take the more powerful right (ORDER BY access)
    ($stmt = $this->db->prepare('
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
        AND walls.id = :walls_id_2'))
       ->execute([
         ':users_id_1' => $this->userId,
         ':walls_id_1' => $this->wallId,
         ':users_id_2' => $this->userId,
         ':walls_id_2' => $this->wallId,
       ]);
    $data = $stmt->fetch();

    $data['_exportInfos'] = [
      'date' => date('Y-m-d H:i:s'),
      'user' => $this->userId,
      'wopitsVersion' => WPT_VERSION,
    ];

    $zip = new \ZipArchive();

    $dir = $this->getWallDir();
    // This is not be deleted at the end of the process.
    //TODO Make a cron that will purge old wall.zip files for all walls.
    $zipPath = "$dir/wopits-wall-{$this->wallId}.zip";

    if ($zip->open($zipPath, \ZipArchive::CREATE) !== true) {
      return ['error' => _("An error occurred during the export")];
    }

    // Get headers
    ($stmt = $this->db->prepare('SELECT * FROM headers WHERE walls_id = ?'))
      ->execute([$this->wallId]);
    $data['headers'] = [];
    while ( ($header = $stmt->fetch()) ) {
      if ($header['picture']) {
        $zip->addFile(WPT_ROOT_PATH."/{$header['picture']}",
          "header/{$header['id']}/".basename($header['picture']));
      }

      $data['headers'][] = $header;
    }

    // Get cells and postits
    ($stmt = $this->db->prepare('SELECT * FROM cells WHERE walls_id = ?'))
      ->execute([$this->wallId]);
    // Get postits
    $stmt1 = $this->db->prepare('
      SELECT * FROM postits WHERE cells_id = ?');
    $stmt2 = $this->db->prepare('
      SELECT * FROM postits_attachments WHERE postits_id = ?');
    $stmt3 = $this->db->prepare('
      SELECT * FROM postits_pictures WHERE postits_id = ?');
    $stmt4 = $this->db->prepare('
      SELECT * FROM postits_plugs WHERE item_start = ?');

    $data['cells'] = [];
    while ( ($cell = $stmt->fetch()) ) {
      $stmt1->execute([$cell['id']]);
      $cell['postits'] = [];
      while ( ($postit = $stmt1->fetch()) ) {
        $postit['items'] = [];

        // Get postit attachments
        $postit['items']['attachments'] = [];
        $stmt2->execute([$postit['id']]);
        while ( ($attachment = $stmt2->fetch()) ) {
          $postit['items']['attachments'][] = $attachment;

          $zip->addFile(WPT_ROOT_PATH."/{$attachment['link']}",
            "postit/{$attachment['postits_id']}/".
              basename($attachment['link']));
        }

        // Get postit pictures
        $postit['items']['pictures'] = [];
        $stmt3->execute([$postit['id']]);
        while ( ($picture = $stmt3->fetch()) ) {
          $postit['items']['pictures'][] = $picture;

          $zip->addFile(WPT_ROOT_PATH."/{$picture['link']}",
            "postit/{$picture['postits_id']}/".basename($picture['link']));
        }

        // Get postit plugs
        $stmt4->execute([$postit['id']]);
        $postit['items']['plugs'] = $stmt4->fetchAll();

        // Do not export postit comments
        $postit['commentscount'] = 0;
        // Do not export workers comments
        $postit['workerscount'] = 0;

        $cell['postits'][] = $postit;
      }

      $data['cells'][] = $cell;
    }

    $zip->addFromString('wall.json', json_encode($data));
    $zip->close();

    return $clone ?
      $zipPath :
      Helper::download([
        'item_type' => 'application/zip',
        'name' => basename($zipPath),
        'size' => filesize($zipPath),
        'path' => $zipPath,
        'unlink' => true,
      ]);
  }

  protected function getWallsById(array $wallsIds):array {
    $walls = [];

    $oldId = $this->wallId;
    foreach ($wallsIds as $_wallId) {
      $this->wallId = $_wallId;
      $walls[$_wallId] = $this->getWall();
    }
    $this->wallId = $oldId;

    return $walls;
  }

  public function getWall(bool $withAlerts = false, bool $basic = false,
                          bool $owner = false):array {
    $ret = [];

    // Return walls list
    if (!$this->wallId) {
      $data = [':users_id_1' => $this->userId];
      $sql = '
        SELECT
          walls.id,
          walls.creationdate,
          walls.name,
          walls.description,
          '.WPT_WRIGHTS_ADMIN.' AS access,
          users.id AS ownerid,
          users.fullname AS ownername
        FROM walls
          INNER JOIN users ON walls.users_id = users.id
        WHERE users_id = :users_id_1';

      if (!$owner) {
        $data[':users_id_2'] = $this->userId;
        $sql .= '
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
        WHERE users_groups.users_id = :users_id_2';
      }

      ($stmt = $this->db->prepare(
         "$sql ORDER BY creationdate DESC, ownername, name, access"))
         ->execute($data);

      // If a user is in more than one groups for the same wall, with
      // different rights, take the more powerful right
      $uniq = [];
      while ($item = $stmt->fetch()) {
        $id = $item['id'];
        if (!isset($uniq[$id])) {
          $uniq[$id] = true;
          $ret[] = $item;
        }
      }
 
      return ['list' => $ret];
    }

    // If a user is in more than one groups for the same wall, with
    // different rights, take the more powerful right (ORDER BY access)
    ($stmt = $this->db->prepare('
      SELECT
        id,
        users_id AS ownerid,
        width,
        creationdate,
        name,
        description,
        '.WPT_WRIGHTS_ADMIN.' AS access
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
      
      ORDER BY access'))
       ->execute([
         ':users_id_1' => $this->userId,
         ':walls_id_1' => $this->wallId,
         ':users_id_2' => $this->userId,
         ':walls_id_2' => $this->wallId,
       ]);

    if ( !($data = $stmt->fetch()) ) {
      return ['removed' => true];
    }

    if (!$basic) {
      ($stmt = $this->db->prepare('
        SELECT displayexternalref, displaymode, displayheaders, settings
        FROM _perf_walls_users WHERE walls_id = ? AND users_id = ? LIMIT 1'))
         ->execute([$this->wallId, $this->userId]);
      $row = $stmt->fetch();
      $data['displayexternalref'] = $row['displayexternalref'] ?? 0;
      $data['displaymode'] = $row['displaymode'] ?? 'postit-mode';
      $data['displayheaders'] = $row['displayheaders'] ?? 1;
      $data['usersettings'] = json_decode($row['settings'] ?? '{}');

      // Get headers
      ($stmt = $this->db->prepare('
        SELECT id, item_type, item_order, width, height, title, picture
        FROM headers
        WHERE walls_id = ?
        ORDER BY item_type, item_order ASC'))
         ->execute([$this->wallId]);
      $data['headers'] = ['cols' => [], 'rows' => []];
      while ($row = $stmt->fetch()) {
        $type = $row['item_type'];

        unset($row['item_type']);
        unset($row['item_order']);

        $data['headers'][$type.'s'][] = $row;
      }

      // Get cells and postits
      ($stmt = $this->db->prepare('
        SELECT id, height, width, item_col, item_row
        FROM cells
        WHERE walls_id = ?'))
         ->execute([$this->wallId]);
      // Get postits
      $stmt1 = $this->db->prepare($withAlerts ?
        'SELECT
           postits.id, width, height, item_top, item_left, item_order,
           classcolor, title, content, tags, creationdate, deadline, timezone,
           obsolete, attachmentscount, workerscount, commentscount, progress,
           postits_alerts.alertshift
         FROM postits
           LEFT JOIN postits_alerts
             ON postits_alerts.postits_id = postits.id
               AND postits_alerts.users_id = ?
         WHERE cells_id = ?'
        :
        'SELECT
           id, width, height, item_top, item_left, item_order, classcolor,
           title, content, tags, creationdate, deadline, timezone, obsolete,
           attachmentscount, workerscount, commentscount, progress
         FROM postits
         WHERE cells_id = ?');
      $data['cells'] = [];
      while ($row = $stmt->fetch()) {
        $stmt1->execute($withAlerts ? [$this->userId, $row['id']] :
                                      [$row['id']]);
        $row['postits'] = [];
        while ($row1 = $stmt1->fetch()) {
          $row['postits'][] = $row1;
        }

        $data['cells'][] = $row;
      }

      // Get postits plugs
      ($stmt = $this->db->prepare('
       SELECT * FROM postits_plugs WHERE walls_id = ?'))
         ->execute([$this->wallId]);
      $data['postits_plugs'] = $stmt->fetchAll();
    }

    // Check if the wall is shared with other users
    ($stmt = $this->db->prepare('
      SELECT 1 FROM walls_groups WHERE walls_id = ? LIMIT 1'))
       ->execute([$this->wallId]);
    $data['shared'] = boolval($stmt->fetch());

    // Get locks
    ($stmt = $this->db->prepare('
      SELECT item, item_id, u.id AS user_id, u.fullname AS user_name
      FROM edit_queue AS eq INNER JOIN users AS u
        ON eq.users_id = u.id
      WHERE walls_id = ? AND users_id <> ? AND is_end = 0'))
       ->execute([$this->wallId, $this->userId]);
    $data['locks'] = $stmt->fetchAll();

    return $data;
  }

  public function getUsersview(array $usersIds):array {
    $ret = ['list' => []];
    // No quotes needed. $usersIds contains trusted values (from the
    // WebSocket server)
    $ids = implode("','", $usersIds);

    ($stmt = $this->db->prepare("
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
     ORDER BY access, fullname"))
      ->execute([
        ':walls_id_1' => $this->wallId,
        ':walls_id_2' => $this->wallId,
      ]);

    //FIXME
    //$ret['list'] = $stmt->fetchAll();
    // If user is in different groups for the same wall, there is more than
    // one occurence in the SQL result. UNION DISTINCT is not ok here.
    $tmp = [];
    while ($item = $stmt->fetch()) {
      if (!isset($tmp[$item['id']])) {
        $ret['list'][] = $item;
        $tmp[$item['id']] = 1;
      }
    }

    return $ret;
  }

  public function getHeaderPicture(array $args):?array {
    $headerId = $args['headerId'];

    $r = $this->checkWallAccess(WPT_WRIGHTS_RO);
    if (!$r['ok']) {
      return ['error' => _("Access forbidden")];
    }

    ($stmt = $this->db->prepare('
      SELECT picture, filetype, filesize FROM headers WHERE id = ?'))
       ->execute([$headerId]);

    if ( ($r = $stmt->fetch()) ) {
      return Helper::download([
        'item_type' => $r['filetype'],
        'name' => basename($r['picture']),
        'size' => $r['filesize'],
        'path' => WPT_ROOT_PATH.$r['picture'],
      ]);
    }
  }

  public function deleteWallColRow(array $args):array {
    $item = $args['item'];
    $itemPos = $args['itemPos'];
    $dir = $this->getWallDir();
    $toDelete = [];
    $ret = [];

    if ($item !== 'col' && $item !== 'row') {
      return ['error' => _("Access forbidden")];
    }

    $r = $this->checkWallAccess(WPT_WRIGHTS_ADMIN);
    if (!$r['ok']) {
      return ['error' => _("Access forbidden")];
    }

    try {
      $this->db->beginTransaction();

      ///////////////////////// headers

      // Delete headers documents
      ($stmt = $this->db->prepare('
        SELECT id FROM headers
        WHERE walls_id = :walls_id
          AND item_type = :item_type AND item_order = :item_order'))
         ->execute([
           ':walls_id' => $this->wallId,
           ':item_type' => $item,
           ':item_order' => $itemPos,
         ]);

      $toDelete[] = "$dir/header/{$stmt->fetch(\PDO::FETCH_COLUMN, 0)}";

      // Delete header
      $this
        ->db->prepare('
          DELETE FROM headers
          WHERE walls_id = ? AND item_type = ? AND item_order = ?')
        ->execute([$this->wallId, $item, $itemPos]);

      // Reordonate headers
      $this
        ->db->prepare('
          UPDATE headers SET
            item_order = item_order - 1
          WHERE walls_id = ? AND item_type = ? AND item_order > ?')
        ->execute([$this->wallId, $item, $itemPos]);

      ///////////////////////// postits

      // Delete files for all postits
      ($stmt = $this->db->prepare("
        SELECT postits.id
        FROM cells
          INNER JOIN postits ON postits.cells_id = cells.id
        WHERE cells.walls_id = ? AND cells.item_$item = ?"))
         ->execute([$this->wallId, $itemPos]);

      $ids = $stmt->fetchAll(\PDO::FETCH_COLUMN);

      if (!empty($ids)) {
        if ($this->db->query("
            SELECT 1 FROM edit_queue
            WHERE item = 'postit' AND item_id IN (".
              implode(',',
                array_map([$this->db, 'quote'], $ids)).') LIMIT 1') ->fetch()) {
          $this->db->rollBack();
          return ['error_msg' => _("Someone is editing a note in the col/row you want to delete")];
        }

        foreach ($ids as $id) {
          $toDelete[] = "$dir/postit/$id";
        }
      }

      ///////////////////////// cells

      ($stmt = $this->db->prepare("SELECT id FROM cells
          WHERE walls_id = ? AND item_$item = ?"))
         ->execute([$this->wallId, $itemPos]);

      $ids = $stmt->fetchAll(\PDO::FETCH_COLUMN);
      $idsStr = implode(',', array_map([$this->db, 'quote'], $ids));

      if ($this->db->query("
          SELECT 1 FROM edit_queue
          WHERE item = 'cell' AND item_id IN ($idsStr) LIMIT 1")->fetch()) {
        $this->db->rollBack();
        return ['error_msg' => _("Someone is editing a cell in the col/row you want to delete")];
      }

      // Delete
      $this->db->exec("DELETE FROM cells WHERE id IN ($idsStr)");

      // Reordonate
      $this
        ->db->prepare("
          UPDATE cells SET
            item_$item = item_$item - 1
          WHERE walls_id = ? AND item_$item > ?")
        ->execute([$this->wallId, $itemPos]);

      if ($item === 'col') {
        $this->executeQuery('UPDATE walls',
          ['width' => $this->data->wall->width - $this->data->width],
          ['id' => $this->wallId]);
      } else {
        $this->executeQuery('UPDATE walls',
          ['width' => $this->data->wall->width],
          ['id' => $this->wallId]);
      }

      // Apply deletions
      foreach ($toDelete as $f) {
        Helper::rm($f);
      }

      $this->db->commit();

      $ret['wall'] = $this->getWall();
    } catch(\Exception $e) {
      $this->db->rollBack();

      error_log(__METHOD__.':'.__LINE__.':'.$e->getMessage());
      $ret['error'] = 1;
    }

    return $ret;
  }

  public function createWallColRow(array $args):array {
    $item = $args['item'];
    $dir = $this->getWallDir();
    $ret = [];

    if ($item !== 'col' && $item !== 'row') {
      return ['error' => _("Access forbidden")];
    }

    $r = $this->checkWallAccess(WPT_WRIGHTS_ADMIN);
    if (!$r['ok']) {
      return ['error' =>
                _("You must have admin access to perform this action")];
    }

    try {
      $this->db->beginTransaction();

      ($stmt = $this->db->prepare('
        SELECT item_order FROM headers
        WHERE walls_id = :walls_id
          AND item_type = :item_type ORDER BY item_order DESC LIMIT 1'))
         ->execute([
           ':walls_id' => $this->wallId,
           ':item_type' => $item,
         ]);
      $order = $stmt->fetch(\PDO::FETCH_COLUMN, 0);

      $this->executeQuery('INSERT INTO headers', [
        'walls_id' => $this->wallId,
        'item_type' => $item,
        'item_order' => $order + 1,
        'width' => ($item === 'col') ? 300 : 51,
        'height' => ($item === 'row') ? 200 : 42,
        'title' => ' ',
      ]);

      mkdir("$dir/header/".$this->db->lastInsertId());

      if ($item === 'col') {
        ($stmt = $this->db->prepare("
          SELECT item_row, item_col, height FROM cells
          WHERE walls_id = ? AND item_$item = ?"))
           ->execute([$this->wallId, $order]);
      } else {
        ($stmt = $this->db->prepare('
          SELECT item_row, item_col, width FROM cells
          WHERE walls_id = ? ORDER BY item_row DESC, item_col ASC'))
           ->execute([$this->wallId]);
      }

      $r = null;
      while ($e = $stmt->fetch()) {
        // Col
        if ($item === 'col') {
          $data = [
            'walls_id' => $this->wallId,
            'width' => 300,
            'height' => $e['height'] ?? 200,
            'item_row' => $e['item_row'],
            'item_col' => $order + 1,
          ];
        // Row
        } else {
          if ($r === null) {
            $r = $e['item_row'];
          } elseif ($e['item_row'] !== $r) {
            break 1;
          }

          $data = [
            'walls_id' => $this->wallId,
            'width' => $e['width'] ?? 300,
            'height' => 200,
            'item_row' => $e['item_row'] + 1,
            'item_col' => $e['item_col'],
          ];
        }

        $this->executeQuery('INSERT INTO cells', $data);
      }

      if ($item === 'col') {
        $this
          ->db->prepare('UPDATE walls SET width = width + 300 WHERE id = ?')
          ->execute([$this->wallId]);
      }

      $this->db->commit();

      $ret = ['wall' => $this->getWall()];
    } catch(\Exception $e) {
      $this->db->rollBack();

      error_log(__METHOD__.':'.__LINE__.':'.$e->getMessage());
      $ret['error'] = 1;
    }

    return $ret;
  }

  private function _insertWall(array $args):string {
    $this->executeQuery('INSERT INTO walls', $args);

    $this->wallId = $this->db->lastInsertId();

    $dir = $this->getWallDir();
    mkdir("$dir/header", 02770, true);
    mkdir("$dir/postit");

    // Performance helper:
    // Link wall creator to wall with admin access.
    $this->executeQuery('INSERT INTO _perf_walls_users', [
      'walls_id' => $this->wallId,
      'users_id' => $this->userId,
      'access' => WPT_WRIGHTS_ADMIN,
    ]);

    return $dir;
  }

  private function _insertHeader(array $args, string $dir):int {
    $this->executeQuery('INSERT INTO headers', $args);

    $id = $this->db->lastInsertId();

    mkdir("$dir/header/{$id}");

    return $id;
  }

  public function createWall():array {
    $ret = [];
    $noGrid = !$this->data->grid;

    if ($noGrid) {
      $colsCount = 1;
      $rowsCount = 1;

      if (!$this->data->width) {
        $this->data->width = 300;
      }
      if (!$this->data->height) {
        $this->data->height = 300;
      }
    } else {
      $colsCount = intval(trim($this->data->colsCount));
      $rowsCount = intval(trim($this->data->rowsCount));

      if (!$colsCount) {
        $colsCount = 3;
      }
      if (!$rowsCount) {
        $rowsCount = 3;
      }
    }

    if ($this->checkWallName($this->data->name)) {
      return ['error_msg' => _("A wall with the same name already exists")];
    }

    $wall = [
      'name' => $this->data->name,
      'width' => $noGrid ? $this->data->width : $colsCount * 300,
      'headers' => ['cols' => [], 'rows' => []],
      'cells' => [],
    ];

    $cellWidth = $noGrid ? $this->data->width : 300;
    $cellHeight = $noGrid ? $this->data->height : 200;

    for ($i = 0; $i < $colsCount; $i++) {
      $wall['headers']['cols'][] =
        ['width' => $cellWidth, 'height' => 50, 'title' => ' '];
    }

    for ($i = 0; $i < $rowsCount; $i++) {
      $wall['headers']['rows'][] =
        ['width' => 50, 'height' => $cellHeight, 'title' => ' '];
    }

    for ($row = 0; $row < $rowsCount; $row++) {
      for ($col = 0; $col < $colsCount; $col++) {
        $wall['cells'][] = [
          'item_row' => $row,
          'item_col' => $col,
          'width' => $cellWidth,
          'height' => $cellHeight,
          'postits' => [],
        ];
      }
    }

    try {
      $this->db->beginTransaction();

      $dir = $this->_insertWall([
        'users_id' => $this->userId,
        'width' => $wall['width'],
        'name' => $wall['name'] ?? '',
        'creationdate' => time(),
      ]);

      // INSERT col headers
      for ($i = 0, $iLen = count($wall['headers']['cols']); $i < $iLen; $i++) {
        $col = $wall['headers']['cols'][$i];

        $this->_insertHeader([
          'walls_id' => $this->wallId,
          'item_type' => 'col',
          'item_order' => $i,
          'width' => $col['width'],
          'height' => $col['height'],
          'title' => $col['title'],
        ], $dir);
      }

      // INSERT row headers
      for ($i = 0, $iLen = count($wall['headers']['rows']); $i < $iLen; $i++) {
        $row = $wall['headers']['rows'][$i];

        $this->_insertHeader([
          'walls_id' => $this->wallId,
          'item_type' => 'row',
          'item_order' => $i,
          'height' => $row['height'],
          'title' => $row['title'],
        ], $dir);
      }

      // INSERT cells
      for ($i = 0, $iLen = count($wall['cells']); $i < $iLen; $i++) {
        $cell = $wall['cells'][$i];

        $this->executeQuery('INSERT INTO cells', [
          'walls_id' => $this->wallId,
          'width' => $cell['width'],
          'height' => $cell['height'],
          'item_row' => $cell['item_row'],
          'item_col' => $cell['item_col'],
        ]);
      }

      $this->db->commit();

      $ret = $this->getWall();
    } catch(\Exception $e) {
      $this->db->rollBack();

      error_log(__METHOD__.':'.__LINE__.':'.$e->getMessage());
      $ret['error'] = 1;
    }

    return $ret;
  }

  public function deleteWall(bool $force = false,
                             bool $returnName = false):array {
    $ret = [];

    if (!$force) {
      $r = $this->checkWallAccess(WPT_WRIGHTS_ADMIN);
      if (!$r['ok']) {
        return ['error' => _("Access forbidden")];
      }
    }

    try {
      if ($returnName) {
        ($stmt = $this->db->prepare('SELECT name FROM walls WHERE id = ?'))
          ->execute([$this->wallId]);
        $ret['name'] = $stmt->fetch(\PDO::FETCH_COLUMN, 0);
      }

      $this
        ->db->prepare('DELETE FROM walls WHERE id = ?')
        ->execute([$this->wallId]);

      Helper::rm($this->getWallDir());
    } catch(\Exception $e) {
      error_log(__METHOD__.':'.__LINE__.':'.$e->getMessage());
      $ret['error'] = 1;
    }

    return $ret;
  }

  public function moveRow(object $moveData):array {
    $data = $this->data;
    $newTransaction = !$this->db->inTransaction();
    $ret = [];

    try {
      if ($newTransaction) {
        $this->db->beginTransaction();
      }

      foreach (['cols', 'rows'] as $item) {
        $items = $moveData->headers->$item;
        for ($j = 0, $jLen = count($items); $j < $jLen; $j++) {
          $header = $items[$j];

          $this->executeQuery('UPDATE headers',
            ['item_order' => $j],
            ['id' => $header->id]);
        }
      }

      for ($i = 0, $iLen = count($data->cells); $i < $iLen; $i++) {
        $cell = $data->cells[$i];

        $this->executeQuery('UPDATE cells', [
          'item_col' => $cell->item_col,
          'item_row' => $cell->item_row,
         ],
         ['id' => $cell->id]);
      }

      $ret = ['wall' => [
        'id' => $this->wallId,
        'partial' => 'wall',
        'action' => 'movecolrow',
        'move' => $moveData->move,
        'header' => ['id' => $moveData->headerId],
      ]];

      if ($newTransaction) {
        $this->db->commit();
      }
    }
    catch(\Exception $e) {
      if ($newTransaction) {
        $this->db->rollBack();
      }

      throw $e;
    }

    return $ret;
  }

  public function updateCells():array {
    $data = $this->data;
    $newTransaction = !$this->db->inTransaction();

    try {
      if ($newTransaction) {
        $this->db->beginTransaction();
      }

      for ($i = 0, $iLen = count($data->cells); $i < $iLen; $i++) {
        $cell = $data->cells[$i];

        $this->executeQuery('UPDATE cells', [
          'width' => $cell->width,
          'height' => $cell->height,
         ],
         ['id' => $cell->id]);

        if (!empty($cell->postits)) {
          for ($j = 0, $jLen = count($cell->postits); $j < $jLen; $j++) {
            $postit = $cell->postits[$j];

            $this->executeQuery('UPDATE postits', [
              'width' => $postit->width,
              'height' => $postit->height,
              'item_top' => $postit->item_top,
              'item_left' => $postit->item_left,
              'item_order' => $postit->item_order,
            ],
            ['id' => $postit->id]);
          }
        }
      }

      $this->executeQuery('UPDATE walls',
        ['width' => $data->wall->width],
        ['id' => $this->wallId]);

      if ($newTransaction) {
        $this->db->commit();
      }
    } catch(\Exception $e) {
      if ($newTransaction) {
        $this->db->rollBack();
      }

      throw $e;
    }

    return $this->getWall();
  }

  public function updateHeaders():void {
    $newTransaction = !$this->db->inTransaction();

    try {
      if ($newTransaction) {
        $this->db->beginTransaction();
      }

      foreach (['cols', 'rows'] as $type) {
        for ($i = 0, $iLen = count($this->data->headers->$type);
             $i < $iLen; $i++) {
          $header = $this->data->headers->$type[$i];

          $this->executeQuery('UPDATE headers', [
            'width' => ($type == 'cols') ? $header->width : null,
            'height' => $header->height,
            'title' => $header->title,
          ],
          ['id' => $header->id]);
        }
      }

      $this->executeQuery('UPDATE walls',
        ['width' => $this->data->wall->width],
        ['id' => $this->wallId]);

      if ($newTransaction) {
        $this->db->commit();
      }
    } catch(\Exception $e) {
      if ($newTransaction) {
        $this->db->rollBack();
      }

      throw $e;
    }
  }

  private function _getImportItemData(object $item, array $replace = null,
                                      array $unset = null):array {
    $data = (array) $item;

    if (array_key_exists('id', $data)) {
      unset($data['id']);
    }

    if (array_key_exists('walls_id', $data)) {
      $data['walls_id'] = $this->wallId;
    }

    if (array_key_exists('users_id', $data)) {
      $data['users_id'] = $this->userId;
    }

    if (is_array($replace)) {
      foreach ($replace as $k => $v) {
        $data[$k] = $v;
      }
    }

    if (is_array($unset)) {
      foreach ($unset as $k) {
        unset($data[$k]);
      }
    }

    return $data;
  }
}
