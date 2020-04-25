<?php

  require_once (__DIR__.'/Wpt_wall.php');

  class Wpt_postit extends Wpt_wall
  {
    private $cellId;
    private $postitId;

    public function __construct ($args = [])
    {
      parent::__construct ($args);

      $this->cellId = @$args['cellId'];
      $this->postitId = @$args['postitId'];
    }

    public function create ()
    {
      $User = new Wpt_user ();

      $ret = [];
      //FIXME
      $postitId = $this->data->id;
      $dir = $this->getWallDir ();

      $r = $this->checkWallAccess (WPT_RIGHTS['walls']['rw']);
      if (!$r['ok'])
        return (isset ($r['id'])) ? $r : ['error' => _("Access forbidden")];

      try
      {
        $this->executeQuery ('INSERT INTO postits', [
          'id' => $postitId,
          'cells_id' => $this->cellId,
          'width' => $this->data->width,
          'height' => $this->data->height,
          'top' => $this->data->top,
          'left' => $this->data->left,
          'classcolor' => $this->data->classcolor,
          'title' => $this->data->title,
          'content' => $this->data->content,
          'creationdate' => time ()
        ]);

        mkdir ("$dir/postit/$postitId");

        $ret['wall'] = $this->getWall ();
      }
      catch (Exception $e)
      {
        $msg = $e->getMessage ();

        // If 1452 : the col/row does not exists (has been removed between
        // two synchro).
        if (strpos ($msg, ": 1542") === false)
          $ret['error_msg'] = _("This item has been deleted");
        else
        {
          error_log (__METHOD__.':'.__LINE__.':'.$msg);
          $ret['error'] = 1;
        }
      }

      return $ret;
    }

    public function checkDeadline ()
    {
      $User = new Wpt_user ();
      $time = time ();

      $stmt = $this->query ('
        SELECT
          users.id AS userId,
          postits.id AS postitId,
          postits.timezone
        FROM postits
          INNER JOIN cells ON cells.id = postits.cells_id
          INNER JOIN walls ON walls.id = cells.walls_id
          INNER JOIN users ON users.id = walls.users_id
        WHERE postits.obsolete = 0
          AND deadline IS NOT NULL');

      while ($item = $stmt->fetch ())
      {
        $this->query ("
          UPDATE postits SET obsolete = 1
          WHERE id = '{$item['postitId']}'
            AND DATE(FROM_UNIXTIME(deadline)) <=
              '{$User->getDate($time, $item['timezone'])}'");
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

        exec ('rm -f '.
          Wpt_common::getSecureSystemName(WPT_ROOT_PATH.$attach['link']));
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

      if (!is_object ($this->data) ||
          !$this->data->size ||
          !preg_match ('#\.([a-z0-9]+)$#i', $this->data->name, $m1) ||
          !preg_match ('#data:([^;]+);base64,(.*)#', $this->data->content, $m2))
      {
        $ret['error'] = _("Empty file or bad file format");
      }
      else
      {
        $ext = $m1[1];
        $content = $m2[2];

        $rdir = 'postit/'.$this->postitId;
        $file = Wpt_common::getSecureSystemName (
          "$dir/$rdir/attachment-".hash('sha1', $this->data->content).".$ext");

        if (file_exists ($file))
          $ret['error_msg'] = _("The file is already linked to the post-it");
        else
        {
          file_put_contents (
            $file, base64_decode(str_replace(' ', '+', $content)));

          // Fix wrong MIME type for images
          if (preg_match ('/(jpe?g|gif|png)/', $ext))
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
      $attachmentId = @$args['attachmentId'];
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
        exec ('rm -rf '.
          Wpt_common::getSecureSystemName("$dir/postit/{$this->postitId}"));
      }
      catch (Exception $e)
      {
        error_log (__METHOD__.':'.__LINE__.':'.$e->getMessage ());

        if (PDO::inTransaction ())
          throw new Exception ($e->getMessage ());
        else
          $ret['error'] = 1;
      }
  
      return $ret;
    }
  }
