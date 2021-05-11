<?php

namespace Wopits\Wall;

require_once (__DIR__.'/../../config.php');

use Wopits\{Helper, User, Wall, Services\Task};

class Postit extends Wall
{
  private $cellId;
  private $postitId;

  public function __construct (array $args = [], object $ws = null)
  {
    parent::__construct ($args, $ws);

    $this->cellId = $args['cellId']??null;
    $this->postitId = $args['postitId']??null;
  }

  public function create (array $data = null):array
  {
    $ret = [];
    $dir = $this->getWallDir ();

    $r = $this->checkWallAccess (WPT_WRIGHTS_RW);
    if (!$r['ok'])
      return
        ['error_msg' =>
            _("You must have write access to perform this action.")];

    // Check for the col/row (it could have been removed while user was
    // creating the new post-it.
    ($stmt = $this->db->prepare ('SELECT 1 FROM cells WHERE id = ?'))
      ->execute ([$this->cellId]);
    if (!$stmt->fetch ())
      return ['error_msg' => _("The row/column has been deleted.")];

    $_data = $data??[
      'cells_id' => $this->cellId,
      'width' => $this->data->width,
      'height' => $this->data->height,
      'item_top' => $this->data->item_top,
      'item_left' => $this->data->item_left,
      'item_order' => 0,
      'classcolor' => $this->data->classcolor,
      'title' => $this->data->title,
      'content' => $this->data->content,
      'creationdate' => time ()
    ];

    try
    {
      $this->executeQuery ('INSERT INTO postits', $_data);
      $this->postitId = $this->db->lastInsertId ();

      mkdir ("$dir/postit/".$this->postitId);

      if (!$data)
      {
        $_data['id'] = $this->postitId;
        $ret = ['wall' => [
          'id' => $this->wallId,
          'partial' => 'postit',
          'action' => 'insert',
          'postit' => $_data
        ]];
      }
    }
    catch (\Exception $e)
    {
      error_log (__METHOD__.':'.__LINE__.':'.$e->getMessage ());
      $ret['error'] = 1;
    }

    return $ret;
  }

  public function updatePostitsColor ():array
  {
    $ret = ['walls' => []];
    $wallsIds = [];
    $color = $this->data->color;

    try
    {
      $this->db->beginTransaction ();

      // Update color if it is a known color class
      if (in_array (substr ($color, 6), array_keys (WPT_POSTIT_COLORS)))
      {
        foreach ($this->data->postits as $_postitId)
        {
          // Update postit if user can write it
          if ( ($wallId =
                  $this->checkPostitAccess (WPT_WRIGHTS_RW, $_postitId) ))
          {
            $wallsIds[] = $wallId;
            $this->executeQuery ('UPDATE postits',
              ['classcolor' => $color],
              ['id' => $_postitId]);
          }
        }
      }

      $this->db->commit ();

      $ret['walls'] = $this->getWallsById ($wallsIds);
    }
    catch (\Exception $e)
    {
      $this->db->rollBack ();

      error_log (__METHOD__.':'.__LINE__.':'.$e->getMessage ());
      $ret['error'] = 1;
    }

    return $ret;
  }

  public function copyPostits ($move = false):array
  {
    $ret = ['walls' => []];
    $wallId = $this->wallId;
    $cellId = $this->cellId;
    $wallsIds = [$wallId];

    $r = $this->checkWallAccess (WPT_WRIGHTS_RW);
    if (!$r['ok'])
      return ['error' => _("Access forbidden")];

    try
    {
      $this->db->beginTransaction ();

      $i = 5;
      foreach ($this->db->query ('
        SELECT postits.*, cells.walls_id
        FROM postits
          INNER JOIN cells ON cells.id = postits.cells_id
        WHERE postits.id IN ('.
          implode(',', array_map ([$this->db, 'quote'],
                    $this->data->postits)).')')
            as $p)
      {
        $srcPostitId = $p['id'];
        $srcCellId = $p['cells_id'];
        $srcWallId = $p['walls_id'];
        $copyComments = ($move && $srcWallId == $wallId);
        unset ($p['id']);
        unset ($p['walls_id']);

        // Copy postit
        $p['item_top'] = $i + 20;
        $p['item_left'] = $i + 5;
        $p['cells_id'] = $this->cellId;
        $this->create ($p);
        $postitId = $this->postitId;

        $havePictures = false;

        // Copy associated items (attachments, pictures, comments)
        // -> plugs are not copied
        $items = ['attachments', 'pictures'];
        // Copy comments only in same wall
        if ($copyComments)
          $items[] = 'comments';

        foreach ($items as $item)
        {
          ($stmt = $this->db->prepare ("
            SELECT * FROM postits_$item WHERE postits_id = ?"))
              ->execute ([$srcPostitId]);

          foreach ($stmt->fetchAll () as $a)
          {
            $a['walls_id'] = $wallId;
            $a['postits_id'] = $postitId;

            $srcDir = null;
            $srcItemId = $a['id'];
            unset ($a['id']);

            // Comments have no attached files
            if ($item != 'comments')
            {
              $srcDir = $a['link'];
              $a['link'] = str_replace (
                [
                  "/{$srcWallId}/",
                  "/{$srcPostitId}/"
                ],
                [
                  "/{$wallId}/",
                  "/{$postitId}/"
                ], $a['link']);
            }

            // Move item
            if ($move)
            {
              if (isset ($a['link']))
                rename (WPT_ROOT_PATH."/$srcDir",
                        WPT_ROOT_PATH."/{$a['link']}");

              $this->executeQuery ("UPDATE postits_$item",
                $a, ['id' => $srcItemId]);

              $itemId = $srcItemId;
            }
            // Copy item
            else
            {
              if (isset ($a['link']))
                copy (WPT_ROOT_PATH."/$srcDir",
                      WPT_ROOT_PATH."/{$a['link']}");

              $this->executeQuery ("INSERT INTO postits_$item", $a);

              $itemId = $this->db->lastInsertId ();
            }

            // Change postit body internal img links if needed
            if ($item == 'pictures')
            {
              $havePictures = true;

              $p['content'] = preg_replace (
                "#wall/\d+/cell/\d+/postit/\d+/".
                  "picture/{$srcItemId}#",
                "wall/{$wallId}/cell/{$cellId}/postit/{$postitId}/".
                  "picture/{$itemId}",
                $p['content']);
            }
          }
        }

        // Delete src postit if needed
        if ($move)
        {
          $wallsIds[] = $srcWallId;
          $this->wallId = $srcWallId;
          $this->deletePostit ($srcPostitId);
          $this->wallId = $wallId;
        }

        // Update dest postit data if needed
        $sqlData = [];
        // Update postit body internal img links if needed
        if ($havePictures)
          $sqlData['content'] = $p['content'];
        // Reset comments count if needed
        if (!$copyComments)
          $sqlData['commentscount'] = 0;
        if ($sqlData)
          $this->executeQuery ('UPDATE postits', $sqlData, ['id' => $postitId]);

        $i += 10;
      }

      $this->db->commit ();

      $ret['walls'] = $this->getWallsById ($wallsIds);
    }
    catch (\Exception $e)
    {
      $this->db->rollBack ();

      error_log (__METHOD__.':'.__LINE__.':'.$e->getMessage ());
      $ret['error'] = 1;
    }

    return $ret;
  }

  public function getPlugs (bool $all = false):array
  {
    // Get postits plugs
    ($stmt = $this->db->prepare ('
      SELECT * FROM postits_plugs
      WHERE '.(($all)?'walls_id':'item_start').' = ?'))
       ->execute ([($all)?$this->wallId:$this->postitId]);

    return $stmt->fetchAll ();
  }

  // Return type is mixed: array or false.
  public function getPostit ()
  {
    ($stmt = $this->db->prepare ('
      SELECT
        id, cells_id, width, height, item_top, item_left, item_order,
        classcolor, title, content, tags, creationdate, deadline, timezone,
        obsolete, attachmentscount, workerscount, commentscount, progress
      FROM postits
      WHERE postits.id = ?'))
       ->execute ([$this->postitId]);

    return $stmt->fetch ();
  }

  public function getPostitAlertShift ():?int
  {
    ($stmt = $this->db->prepare ('
      SELECT alertshift
      FROM postits_alerts
      WHERE postits_id = ? AND users_id = ?'))
       ->execute ([$this->postitId, $this->userId]);

    return ($r = $stmt->fetch ()) ? $r['alertshift'] : null;
  }

  public function checkDeadline ():void
  {
    // Get all postits with a deadline, and associated alerts if available.
    $stmt = $this->db->query ('
      SELECT
        postits.id AS postit_id,
        postits.deadline AS postit_deadline,
        postits.title AS postit_title,
        users.id AS alert_user_id,
        users.email AS alert_user_email,
        users.fullname AS alert_user_fullname,
        users.allow_emails AS alert_user_allow_emails,
        postits_alerts.alertshift AS alert_shift,
        walls.id as wall_id
      FROM postits
        INNER JOIN cells ON cells.id = postits.cells_id
        INNER JOIN walls ON walls.id = cells.walls_id
        LEFT JOIN postits_alerts ON postits.id = postits_alerts.postits_id
        LEFT JOIN users ON postits_alerts.users_id = users.id
      WHERE postits.obsolete = 0
        AND deadline IS NOT NULL');

    $now = new \DateTime ();
    $Task = new Task ();

    while ($item = $stmt->fetch ())
    {
      $deleteAlert = false;

      $dlEpoch = $item['postit_deadline'];
      $dl = new \DateTime ("@{$dlEpoch}");
      $days = $dl->diff($now)->days;
      $hours = $dl->diff($now)->h;

      if ($hours)
       ++$days;

      if ($dlEpoch <= $now->format ('U'))
      {
        $this->executeQuery ('UPDATE postits',
          ['obsolete' => 1],
          ['id' => $item['postit_id']]);

        if (!is_null ($item['alert_user_id']))
        {
          $deleteAlert = true;

          $Task->execute ([
            'event' => Task::EVENT_TYPE_SEND_MESSAGE,
            'method' => 'deadlineAlert_1',
            'sendmail' => $item['alert_user_allow_emails'],
            'userId' => $item['alert_user_id'],
            'email' => $item['alert_user_email'],
            'wallId' => $item['wall_id'],
            'postitId' => $item['postit_id'],
            'fullname' => $item['alert_user_fullname'],
            'title' => $item['postit_title']
          ]);

          sleep (2);
        }
      }
      elseif (!is_null ($item['alert_user_id']) &&
              $item['alert_shift'] >= $days)
      {
        $deleteAlert = true;

        $Task->execute ([
          'event' => Task::EVENT_TYPE_SEND_MESSAGE,
          'method' => 'deadlineAlert_2',
          'sendmail' => $item['alert_user_allow_emails'],
          'userId' => $item['alert_user_id'],
          'email' => $item['alert_user_email'],
          'wallId' => $item['wall_id'],
          'postitId' => $item['postit_id'],
          'fullname' => $item['alert_user_fullname'],
          'title' => $item['postit_title'],
          'days' => $days,
          'hours' => $hours
        ]);

        sleep (2);
      }

      if ($deleteAlert)
        $this
          ->db->prepare ('
            DELETE FROM postits_alerts WHERE postits_id =  ? AND users_id = ?')
          ->execute ([$item['postit_id'], $item['alert_user_id']]);
    }
  }

  public function addRemovePlugs (array $plugs, int $postitId = null):void
  {
    if (!$postitId)
      $postitId = $this->postitId;

    $this
      ->db->prepare('
        DELETE FROM postits_plugs
        WHERE item_start = ? AND item_end NOT IN ('.
          implode(',',array_map([$this->db, 'quote'], array_keys($plugs))).')')
      ->execute ([$postitId]);

    $stmt = $this->db->prepare ("
      INSERT INTO postits_plugs (
        walls_id, item_start, item_end, item_top, item_left, label,
        line_size, line_type, line_color, line_path
      ) VALUES (
        :walls_id, :item_start, :item_end, :item_top, :item_left, :label,
        :line_size, :line_type, :line_color, :line_path
      ) {$this->getDuplicateQueryPart (['walls_id', 'item_start', 'item_end'])}
       label = :label_1, item_top = :item_top_1, item_left = :item_left_1,
       line_size = :line_size_1, line_type = :line_type_1,
       line_color = :line_color_1, line_path = :line_path_1");

    foreach ($plugs as $_id => $_p)
    {
      $top = $_p->top??null;
      $left = $_p->left??null;

      //TODO Optimization
      $this->checkDBValue ('postits_plugs', 'walls_id', $this->wallId);
      $this->checkDBValue ('postits_plugs', 'item_start', $postitId);
      $this->checkDBValue ('postits_plugs', 'item_end', $_id);
      $this->checkDBValue ('postits_plugs', 'item_top', $top);
      $this->checkDBValue ('postits_plugs', 'item_left', $left);
      $this->checkDBValue ('postits_plugs', 'label', $_p->label);
      $this->checkDBValue ('postits_plugs', 'line_size', $_p->line_size);
      $this->checkDBValue ('postits_plugs', 'line_type', $_p->line_type);
      $this->checkDBValue ('postits_plugs', 'line_color', $_p->line_color);
      $this->checkDBValue ('postits_plugs', 'line_path', $_p->line_path);

      $stmt->execute ([
        ':walls_id' => $this->wallId,
        ':item_start' => $postitId,
        ':item_end' => $_id,
        ':item_top' => $top,
        ':item_left' => $left,
        ':label' => $_p->label,
        ':line_size' => $_p->line_size,
        ':line_type' => $_p->line_type,
        ':line_color' => $_p->line_color,
        ':line_path' => $_p->line_path,

        ':label_1' => $_p->label,
        ':item_top_1' => $top,
        ':item_left_1' => $left,
        ':line_size_1' => $_p->line_size,
        ':line_type_1' => $_p->line_type,
        ':line_color_1' => $_p->line_color,
        ':line_path_1' => $_p->line_path
      ]);
    }
  }

  public function addPicture ():array
  {
    $ret = [];
    $dir = $this->getWallDir ();
    $wdir = $this->getWallDir ('web');

    $r = $this->checkWallAccess (WPT_WRIGHTS_RW);
    if (!$r['ok'])
      return ['error' => _("Access forbidden")];

    list ($ext, $content, $error) = $this->getUploadedFileInfos ($this->data);

    if ($error)
      $ret['error'] = $error;
    else
    {
      try
      {
        $rdir = 'postit/'.$this->postitId;
        $file = Helper::getSecureSystemName (
          "$dir/$rdir/picture-".hash('sha1', $this->data->content).".$ext");

        file_put_contents (
          $file, base64_decode (str_replace (' ', '+', $content)));

        if (!file_exists ($file))
          throw new \Exception (_("An error occured while uploading file."));

        list ($file, $this->data->item_type, $width, $height) =
          Helper::resizePicture ($file, 800, 0, false);

        ($stmt = $this->db->prepare ('
          SELECT * FROM postits_pictures WHERE postits_id = ? AND link = ?'))
           ->execute ([$this->postitId, "$wdir/$rdir/".basename($file)]);

        $ret = $stmt->fetch ();

        if (!$ret)
        {
          $ret = [
            'postits_id' => $this->postitId,
            'walls_id' => $this->wallId,
            'users_id' => $this->userId,
            'creationdate' => time (),
            'name' => $this->data->name,
            'size' => filesize ($file),
            'item_type' => $this->data->item_type,
            'link' => "$wdir/$rdir/".basename($file)
          ];

          $this->executeQuery ('INSERT INTO postits_pictures', $ret);

          $ret['id'] = $this->db->lastInsertId ();
        }

        $ret['icon'] = Helper::getImgFromMime ($this->data->item_type);
        $ret['width'] = $width;
        $ret['height'] = $height;
        $ret['link'] =
          "/api/wall/{$this->wallId}/cell/{$this->cellId}".
          "/postit/{$this->postitId}/picture/{$ret['id']}";
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

  public function getPicture (array $args):?array
  {
    $picId = $args['pictureId'];

    $r = $this->checkWallAccess (WPT_WRIGHTS_RO);
    if (!$r['ok'])
      return ['error' => _("Access forbidden")];

    ($stmt = $this->db->prepare ('SELECT * FROM postits_pictures WHERE id = ?'))
      ->execute ([$picId]);
    $data = $stmt->fetch ();

    $data['path'] = WPT_ROOT_PATH.$data['link'];

    Helper::download ($data);
  }

  public function deletePictures (object $data):void
  {
    $pics = (preg_match_all (
      "#/postit/\d+/picture/(\d+)#", $data->content, $m)) ? $m[1] : [];

    ($stmt = $this->db->prepare ('
      SELECT id, link FROM postits_pictures WHERE postits_id = ?'))
       ->execute ([$data->id]);

    $toDelete = [];
    while ( ($pic = $stmt->fetch ()) )
    {
      if (!in_array ($pic['id'], $pics))
      {
        $toDelete[] = $pic['id'];
        Helper::rm (WPT_ROOT_PATH.$pic['link']);
      }
    }

    if (!empty ($toDelete))
      $this->db->exec ('
        DELETE FROM postits_pictures
        WHERE id IN ('.
          implode(',',array_map([$this->db, 'quote'],
                    array_keys($toDelete))).')');
  }

  public function deletePostit (int $postitId = null):array
  {
    $ret = [];
    $dir = $this->getWallDir ();

    if (!$postitId)
      $postitId = $this->postitId;

    $r = $this->checkWallAccess (WPT_WRIGHTS_RW);
    if (!$r['ok'])
      return ['error' => _("Access forbidden")];
    
    try
    {
      $this
        ->db->prepare('DELETE FROM postits WHERE id = ?')
        ->execute ([$postitId]);

      // Delete postit files
      Helper::rm ("$dir/postit/{$postitId}");
    }
    catch (\Exception $e)
    {
      error_log (__METHOD__.':'.__LINE__.':'.$e->getMessage ());

      if ($this->db->inTransaction ())
        throw $e;
      else
        $ret['error'] = 1;
    }

    return $ret;
  }

  public function checkPostitAccess (int $requiredRole, int $postitId):?int
  {
    ($stmt = $this->db->prepare ("
      SELECT _perf_walls_users.walls_id
      FROM _perf_walls_users
        INNER JOIN cells ON cells.walls_id = _perf_walls_users.walls_id
        INNER JOIN postits ON postits.cells_id = cells.id
      WHERE users_id = ?
        AND postits.id = ?
        AND access IN(".$this->buildAccessRightsSQL($requiredRole).")
      LIMIT 1"))
       ->execute ([$this->userId, $postitId]);

    return $stmt->fetch(\PDO::FETCH_COLUMN)??0;
  }
}
