<?php

  require_once (__DIR__.'/Wpt_wall.php');

  class Wpt_postit extends Wpt_wall
  {
    private $cellId;
    private $postitId;

    public function __construct ($args = [])
    {
      parent::__construct ($args);

      $this->cellId = $args['cellId']??null;
      $this->postitId = $args['postitId']??null;
    }

    public function create ()
    {
      $ret = [];
      $dir = $this->getWallDir ();

      $r = $this->checkWallAccess (WPT_RIGHTS['walls']['rw']);
      if (!$r['ok'])
        return (isset ($r['id'])) ? $r :
          ['error_msg' =>
             _("You must have write access to perform this action.")];

      $data = [
        'cells_id' => $this->cellId,
        'width' => $this->data->width,
        'height' => $this->data->height,
        'top' => $this->data->top,
        'left' => $this->data->left,
        'classcolor' => $this->data->classcolor,
        'title' => $this->data->title,
        'content' => $this->data->content,
        'creationdate' => time ()
      ];

      try
      {
        $this->executeQuery ('INSERT INTO postits', $data);

        $this->postitId = $this->lastInsertId ();

        mkdir ("$dir/postit/".$this->postitId);

        $data['id'] = $this->postitId;
        $ret = ['wall' => [
          'id' => $this->wallId,
          'partial' => 'postit',
          'action' => 'insert',
          'postit' => $data
        ]];
      }
      catch (Exception $e)
      {
        $msg = $e->getMessage ();

        // If 1452 : the col/row does not exists (has been removed between
        // two synchro).
        if (strpos ($msg, ": 1542") === false)
          $ret['error_msg'] = _("This item has been deleted.");
        else
        {
          error_log (__METHOD__.':'.__LINE__.':'.$msg);
          $ret['error'] = 1;
        }
      }

      return $ret;
    }

    public function getPlugs ($all = false)
    {
      $q = $this->getFieldQuote ();

      // Get postits plugs
      $stmt = $this->prepare ("
        SELECT start, ${q}end$q, label
        FROM postits_plugs
        WHERE ".(($all)?'walls_id':'start')." = ?");
      $stmt->execute ([($all)?$this->wallId:$this->postitId]);

      return $stmt->fetchAll ();
    }

    public function getPostit ()
    {
      $q = $this->getFieldQuote ();
      $stmt = $this->prepare ("
        SELECT
          id, cells_id, width, height, top, ${q}left$q, classcolor,
          title, content, tags, creationdate, deadline, timezone, obsolete,
          attachmentscount
        FROM postits
        WHERE postits.id = ?");
      $stmt->execute ([$this->postitId]);

      return $stmt->fetch ();
    }

//TODO
    public function checkDeadline ()
    {
      // Get all postits with a deadline, and associated alerts if available.
      $stmt = $this->query ('
        SELECT
          postits.id AS postit_id,
          postits.deadline AS postit_deadline,
          postits.title AS postit_title,
          users.id AS alert_user_id,
          users.email AS alert_user_email,
          users.username AS alert_user_fullname,
          postits_alerts.alertshift AS alert_shift,
          walls.id as wall_id
        FROM postits
          INNER JOIN cells ON cells.id = postits.cells_id
          INNER JOIN walls ON walls.id = cells.walls_id
          LEFT JOIN postits_alerts ON postits.id = postits_alerts.postits_id
          LEFT JOIN users ON postits_alerts.users_id = users.id
        WHERE postits.obsolete = 0
          AND deadline IS NOT NULL');

      $now = new DateTime ();;

      $oldTZ = date_default_timezone_get ();
      while ($item = $stmt->fetch ())
      {
        $deleteAlert = false;

        $User = new Wpt_user ();

        $User->userId = $item['alert_user_id'];

        Wpt_common::changeLocale (Wpt_common::getsLocale ($User));

        date_default_timezone_set ($User->getTimezone ());

        $dlEpoch = $item['postit_deadline'];
        $dl = new DateTime("@{$dlEpoch}");
        $days = $dl->diff($now)->days;
        $hours = $dl->diff($now)->h;

        if ($hours)
         ++$days;

        if ($dlEpoch <= $now->format("U") && $hours == 0)
        {
          $this->exec ("
            UPDATE postits SET obsolete = 1 WHERE id = {$item['postit_id']}");

          if (!is_null ($User->userId))
          {
            $deleteAlert = true;

            Wpt_common::mail ([
              'email' => $item['alert_user_email'],
              'subject' => _("Post-it deadline notification"),
              'msg' => sprintf (_("Hello %s,\r\n\r\nThe dealine for the following post-it has expired:\r\n\r\n%s"), $item['alert_user_fullname'], WPT_URL."/a/w/{$item['wall_id']}/p/{$item['postit_id']}")
            ]);
          }
        }
        elseif (!is_null ($User->userId) && $item['alert_shift'] >= $days)
        {
          $deleteAlert = true;

          Wpt_common::mail ([
            'email' => $item['alert_user_email'],
            'subject' => _("Post-it deadline notification"),
            'msg' => ($days == 1 || ($days == 0 && $hours > 0)) ?
              sprintf (_("Hello %s,\r\n\r\nThe deadline for the following post-it will expire soon:\r\n\r\n%s"), $item['alert_user_fullname'], WPT_URL."/a/w/{$item['wall_id']}/p/{$item['postit_id']}") :
              sprintf (_("Hello %s,\r\n\r\nThe deadline for the following post-it will expire in %s days:\r\n\r\n%s"), $item['alert_user_fullname'], $days, WPT_URL."/a/w/{$item['wall_id']}/p/{$item['postit_id']}")
          ]);
        }

        if ($deleteAlert)
          $this->exec ("
            DELETE FROM postits_alerts
            WHERE postits_id = {$item['postit_id']}
              AND users_id = {$item['alert_user_id']}");
      }
      date_default_timezone_set ($oldTZ);
    }

    public function addRemovePlugs ($plugs, $postitId = null)
    {
      $q = $this->getFieldQuote ();

      if (!$postitId)
        $postitId = $this->postitId;

      $this
        ->prepare("
          DELETE FROM postits_plugs
          WHERE start = ? AND ${q}end$q NOT IN (".
            implode(",",array_map([$this, 'quote'], array_keys($plugs))).")")
        ->execute ([$postitId]);

      $stmt = $this->prepare ("
        INSERT INTO postits_plugs (
          walls_id, start, ${q}end$q, label
        ) VALUES (
          :walls_id, :start, :end, :label
        ) {$this->getDuplicateQueryPart (['walls_id', 'start', 'end'])}
        label = :label_1");

      foreach ($plugs as $_id => $_label)
      {
        $this->checkDBValue ('postits_plugs', 'label', $_label);
        $stmt->execute ([
          ':walls_id' => $this->wallId,
          ':start' => $postitId,
          ':end' => $_id,
          ':label' => $_label,
          ':label_1' => $_label
        ]);
      }
    }

    public function deleteAttachment ($args)
    {
      $ret = [];
      $attachmentId = $args['attachmentId'];

      $r = $this->checkWallAccess (WPT_RIGHTS['walls']['rw']);
      if (!$r['ok'])
        return (isset ($r['id'])) ? $r : ['error' => _("Access forbidden")];

      try
      {
        $this->beginTransaction ();

        $stmt = $this->prepare ('
          SELECT link FROM postits_attachments WHERE id = ?');
        $stmt->execute ([$attachmentId]);
        $attach = $stmt->fetch ();

        $this
          ->prepare('DELETE FROM postits_attachments WHERE id = ?')
          ->execute ([$attachmentId]);

        $this
          ->prepare('
            UPDATE postits SET attachmentscount = attachmentscount - 1
            WHERE id = ?')
          ->execute ([$this->postitId]);
      
        $this->commit ();

        Wpt_common::rm (WPT_ROOT_PATH.$attach['link']);
      }
      catch (Exception $e)
      {
        $this->rollback ();

        error_log (__METHOD__.':'.__LINE__.':'.$e->getMessage ());
        $ret['error'] = 1;
      }

      return $ret;
    }
 
    public function addAttachment ()
    {
      $ret = [];
      $dir = $this->getWallDir ();
      $wdir = $this->getWallDir ('web');
      $currentDate = time ();

      $r = $this->checkWallAccess (WPT_RIGHTS['walls']['rw']);
      if (!$r['ok'])
        return (isset ($r['id'])) ? $r : ['error' => _("Access forbidden")];

      list ($ext, $content, $error) = $this->getUploadedFileInfos ($this->data);

      if ($error)
        $ret['error'] = $error;
      else
      {
        $rdir = 'postit/'.$this->postitId;
        $file = Wpt_common::getSecureSystemName (
          "$dir/$rdir/attachment-".hash('sha1', $this->data->content).".$ext");

        if (file_exists ($file))
          $ret['error_msg'] = _("The file is already linked to the post-it.");
        else
        {
          file_put_contents (
            $file, base64_decode(str_replace(' ', '+', $content)));

          // Fix wrong MIME type for images
          if (preg_match ('/(jpe?g|gif|png)/i', $ext))
            list ($file, $this->data->type, $this->data->name) =
              Wpt_common::checkRealFileType ($file, $this->data->name);

          $ret = [
            'postits_id' => $this->postitId,
            'walls_id' => $this->wallId,
            'users_id' => $this->userId,
            'creationdate' => $currentDate,
            'name' => $this->data->name,
            'size' => $this->data->size,
            'type' => $this->data->type,
            'link' => "$wdir/$rdir/".basename($file)
          ];
  
          try
          {
            $this->beginTransaction ();

            $this->executeQuery ('INSERT INTO postits_attachments', $ret);
  
            $ret['id'] = $this->lastInsertId ();
  
            $this
              ->prepare('
                UPDATE postits SET attachmentscount = attachmentscount + 1
                WHERE id = ?')
              ->execute ([$this->postitId]);
            
            $ret['icon'] = Wpt_common::getImgFromMime ($this->data->type);
            $ret['link'] =
              "/api/wall/{$this->wallId}/cell/{$this->cellId}".
              "/postit/{$this->postitId}/attachment/{$ret['id']}";

            $this->commit ();
          }
          catch (Exception $e)
          {
            $this->rollback ();

            error_log (__METHOD__.':'.__LINE__.':'.$e->getMessage ());
            $ret['error'] = 1;
          }
        }
      }

      return $ret;
    }

    public function getAttachment ($args)
    {
      $attachmentId = $args['attachmentId']??null;
      $ret = [];

      $r = $this->checkWallAccess (WPT_RIGHTS['walls']['ro']);
      if (!$r['ok'])
        return (isset ($r['id'])) ? $r : ['error' => _("Access forbidden")];

      // Return all postit attachments
      if (!$attachmentId)
      {
        $data = [];

        $stmt = $this->prepare ('
          SELECT
             postits_attachments.id
            ,postits_attachments.link
            ,postits_attachments.type
            ,postits_attachments.name
            ,postits_attachments.size
            ,users.id AS ownerid
            ,users.fullname AS ownername
            ,postits_attachments.creationdate
          FROM postits_attachments
            LEFT JOIN users
              ON postits_attachments.users_id = users.id
          WHERE postits_id = ?
          ORDER BY postits_attachments.creationdate DESC, name ASC');
        $stmt->execute ([$this->postitId]);

        while ($row = $stmt->fetch ())
        {
          $row['icon'] = Wpt_common::getImgFromMime ($row['type']);
          $row['link'] =
            "/api/wall/{$this->wallId}/cell/{$this->cellId}/postit/".
            "{$this->postitId}/attachment/{$row['id']}";
          $data[] = $row;
        }

        $ret = ['files' => $data];
      }
      else
      {
        $stmt = $this->prepare ('
          SELECT * FROM postits_attachments WHERE id = ?');
        $stmt->execute ([$attachmentId]);
        $data = $stmt->fetch ();

        $data['path'] = WPT_ROOT_PATH.$data['link'];

        Wpt_common::download ($data);
      }

      return $ret;
    }

    public function addPicture ()
    {
      $ret = [];
      $dir = $this->getWallDir ();
      $wdir = $this->getWallDir ('web');

      $r = $this->checkWallAccess (WPT_RIGHTS['walls']['rw']);
      if (!$r['ok'])
        return (isset ($r['id'])) ? $r : ['error' => _("Access forbidden")];

      list ($ext, $content, $error) = $this->getUploadedFileInfos ($this->data);

      if ($error)
        $ret['error'] = $error;
      else
      {
        try
        {
          $rdir = 'postit/'.$this->postitId;
          $file = Wpt_common::getSecureSystemName (
            "$dir/$rdir/picture-".hash('sha1', $this->data->content).".$ext");

          file_put_contents (
            $file, base64_decode (str_replace (' ', '+', $content)));

          if (!file_exists ($file))
            throw new Exception (_("An error occured while uploading file."));

          list ($file, $this->data->type, $width, $height) =
            Wpt_common::resizePicture ($file, 800, 0, false);

          $stmt = $this->prepare ('
            SELECT * FROM postits_pictures WHERE postits_id = ? AND link = ?');
          $stmt->execute ([$this->postitId, "$wdir/$rdir/".basename($file)]);

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
              'type' => $this->data->type,
              'link' => "$wdir/$rdir/".basename($file)
            ];

            $this->executeQuery ('INSERT INTO postits_pictures', $ret);

            $ret['id'] = $this->lastInsertId ();
          }

          $ret['icon'] = Wpt_common::getImgFromMime ($this->data->type);
          $ret['width'] = $width;
          $ret['height'] = $height;
          $ret['link'] =
            "/api/wall/{$this->wallId}/cell/{$this->cellId}".
            "/postit/{$this->postitId}/picture/{$ret['id']}";
        }
        catch (ImagickException $e)
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
        catch (Exception $e)
        {
          @unlink ($file);

          error_log (__METHOD__.':'.__LINE__.':'.$e->getMessage ());
          throw $e;
        }
      }

      return $ret;
    }

    public function getPicture ($args)
    {
      $picId = $args['pictureId'];

      $r = $this->checkWallAccess (WPT_RIGHTS['walls']['ro']);
      if (!$r['ok'])
        return (isset ($r['id'])) ? $r : ['error' => _("Access forbidden")];

      $stmt = $this->prepare ('SELECT * FROM postits_pictures WHERE id = ?');
      $stmt->execute ([$picId]);
      $data = $stmt->fetch ();

      $data['path'] = WPT_ROOT_PATH.$data['link'];

      Wpt_common::download ($data);
    }

    public function deletePictures ($data)
    {
      $pics = (preg_match_all (
        "#/postit/\d+/picture/(\d+)#", $data->content, $m)) ? $m[1] : [];

      $stmt = $this->prepare ('
        SELECT id, link
        FROM postits_pictures
        WHERE postits_id = ?');
      $stmt->execute ([$data->id]);

      $toDelete = [];
      while ( ($pic = $stmt->fetch ()) )
      {
        if (!in_array ($pic['id'], $pics))
        {
          $toDelete[] = $pic['id'];
          Wpt_common::rm (WPT_ROOT_PATH.$pic['link']);
        }
      }

      if (!empty ($toDelete))
        $this->exec ('
          DELETE FROM postits_pictures
          WHERE id IN ('.implode(',', $toDelete).')');
    }

    public function deletePostit ()
    {
      $ret = [];
      $dir = $this->getWallDir ();
      $newTransaction = (!PDO::inTransaction ());

      $r = $this->checkWallAccess (WPT_RIGHTS['walls']['rw']);
      if (!$r['ok'])
        return (isset ($r['id'])) ? $r : ['error' => _("Access forbidden")];
      
      try
      {
        $this
          ->prepare('DELETE FROM postits WHERE id = ?')
          ->execute ([$this->postitId]);

        // Delete postit files
        Wpt_common::rm ("$dir/postit/{$this->postitId}");
      }
      catch (Exception $e)
      {
        error_log (__METHOD__.':'.__LINE__.':'.$e->getMessage ());

        if (PDO::inTransaction ())
          throw $e;
        else
          $ret['error'] = 1;
      }
  
      return $ret;
    }
  }
