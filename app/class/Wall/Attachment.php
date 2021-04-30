<?php

namespace Wopits\Wall;

require_once (__DIR__.'/../../config.php');

use Wopits\{Helper, User, Wall};

class Attachment extends Wall
{
  private $cellId;
  private $postitId;

  public function __construct (array $args = [], object $ws = null)
  {
    parent::__construct ($args, $ws);

    $this->cellId = $args['cellId']??null;
    $this->postitId = $args['postitId']??null;
  }

  public function delete (int $id):array
  {
    $ret = [];

    if (!$id)
      return ['error' => 'id is needed'];

    $r = $this->checkWallAccess (WPT_WRIGHTS_RW);
    if (!$r['ok'])
      return (isset ($r['id'])) ? $r : ['error' => _("Access forbidden")];

    try
    {
      $this->beginTransaction ();

      ($stmt = $this->prepare ('
          SELECT link FROM postits_attachments WHERE id = ?'))->execute ([$id]);
      $attach = $stmt->fetch ();

      $stmt = $this->prepare ('DELETE FROM postits_attachments WHERE id = ?');
      $stmt->execute ([$id]);

      if ($stmt->rowCount ())
        $this
          ->prepare('
            UPDATE postits SET attachmentscount = attachmentscount - 1
            WHERE id = ?')
          ->execute ([$this->postitId]);
    
      $this->commit ();

      Helper::rm (WPT_ROOT_PATH.$attach['link']);
    }
    catch (\Exception $e)
    {
      $this->rollback ();

      error_log (__METHOD__.':'.__LINE__.':'.$e->getMessage ());
      $ret['error'] = 1;
    }

    return $ret;
  }
 
  public function update (int $id):array
  {
    $ret = [];

    if (!$id)
      return ['error' => 'id is needed'];

    $r = $this->checkWallAccess (WPT_WRIGHTS_RW);
    if (!$r['ok'])
      return (isset ($r['id'])) ? $r : ['error' => _("Access forbidden")];

    if (!empty ($this->data->title))
    {
      ($stmt = $this->prepare ('
        SELECT 1 FROM postits_attachments
        WHERE postits_id = ? AND title = ? AND id <> ?'))
         ->execute ([$this->postitId, $this->data->title, $id]);
      if ($stmt->fetch ())
        return ['error_msg' => _("This title already exists.")];
    }

    try
    {
      $this->executeQuery ('UPDATE postits_attachments', [
        'title' => $this->data->title,
        'description' => $this->data->description,
      ],
      ['id' => $id]);
    }
    catch (\Exception $e)
    {
      error_log (__METHOD__.':'.__LINE__.':'.$e->getMessage ());
      $ret['error'] = 1;
    }

    return $ret;
  }

  public function add ():array
  {
    $ret = [];
    $dir = $this->getWallDir ();
    $wdir = $this->getWallDir ('web');
    $currentDate = time ();

    $r = $this->checkWallAccess (WPT_WRIGHTS_RW);
    if (!$r['ok'])
      return (isset ($r['id'])) ? $r : ['error' => _("Access forbidden")];

    list ($ext, $content, $error) = $this->getUploadedFileInfos ($this->data);

    if ($error)
      $ret['error'] = $error;
    else
    {
      $rdir = 'postit/'.$this->postitId;
      $file = Helper::getSecureSystemName (
        "$dir/$rdir/attachment-".hash('sha1', $this->data->content).
           ($ext?".$ext":''));
      $fname = basename ($file);

      if (($p = strrpos($fname, '.')) !== false)
        $fname = substr ($fname, 0, $p);

      ($stmt = $this->prepare ('
        SELECT 1 FROM postits_attachments
        WHERE postits_id = ? AND link LIKE ?'))
         ->execute ([
           $this->postitId,
           "%$fname%"
        ]);

      if ($stmt->fetch ())
        $ret['error_msg'] =
          _("The file is already linked to the note!");
      else
      {
        file_put_contents (
          $file, base64_decode(str_replace(' ', '+', $content)));

        // Fix wrong MIME type for images
        if ($ext && preg_match ('/(jpe?g|gif|png)/i', $ext))
          list ($file, $this->data->item_type, $this->data->name) =
            Helper::checkRealFileType ($file, $this->data->name);

        $ret = [
          'postits_id' => $this->postitId,
          'walls_id' => $this->wallId,
          'users_id' => $this->userId,
          'creationdate' => $currentDate,
          'name' => $this->data->name,
          'size' => $this->data->size,
          'item_type' => empty($this->data->item_type) ? 
                           'text/plain':$this->data->item_type,
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
          
          $ret['icon'] = Helper::getImgFromMime ($this->data->item_type);
          $ret['link'] =
            "/api/wall/{$this->wallId}/cell/{$this->cellId}".
            "/postit/{$this->postitId}/attachment/{$ret['id']}";

          $this->commit ();
        }
        catch (\Exception $e)
        {
          $this->rollback ();

          error_log (__METHOD__.':'.__LINE__.':'.$e->getMessage ());
          $ret['error'] = 1;
        }
      }
    }

    return $ret;
  }

  public function get (int $id = null):array
  {
    $ret = [];

    $r = $this->checkWallAccess (WPT_WRIGHTS_RO);
    if (!$r['ok'])
      return (isset ($r['id'])) ? $r : ['error' => _("Access forbidden")];

    // Return all postit attachments
    if (!$id)
    {
      $data = [];

      ($stmt = $this->prepare ('
        SELECT
           postits_attachments.id
          ,postits_attachments.link
          ,postits_attachments.item_type
          ,postits_attachments.name
          ,postits_attachments.size
          ,postits_attachments.title
          ,postits_attachments.description
          ,users.id AS ownerid
          ,users.fullname AS ownername
          ,postits_attachments.creationdate
        FROM postits_attachments
          LEFT JOIN users
            ON postits_attachments.users_id = users.id
        WHERE postits_id = ?
        ORDER BY postits_attachments.creationdate DESC, name ASC'))
         ->execute ([$this->postitId]);

      while ($row = $stmt->fetch ())
      {
        $row['icon'] = Helper::getImgFromMime ($row['item_type']);
        $row['link'] =
          "/api/wall/{$this->wallId}/cell/{$this->cellId}/postit/".
          "{$this->postitId}/attachment/{$row['id']}";
        $data[] = $row;
      }

      $ret = ['files' => $data];
    }
    else
    {
      ($stmt = $this->prepare ('
        SELECT * FROM postits_attachments WHERE id = ?'))->execute ([$id]);

      // If the file has been deleted by admin while a user with readonly
      // access was taking a look at the attachments list.
      if ( !($data = $stmt->fetch ()) )
        $data = ['item_type' => 404];
      else
        $data['path'] = WPT_ROOT_PATH.$data['link'];

      Helper::download ($data);
    }

    return $ret;
  }
}
