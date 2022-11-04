<?php

namespace Wopits\Wall;

require_once(__DIR__.'/../../config.php');

use Wopits\{Helper, User, Wall, Services\Task};

class Comment extends Wall {
  private $cellId;
  private $postitId;

  public function __construct(array $args = [], object $ws = null) {
    parent::__construct($args, $ws);

    $this->cellId = $args['cellId'] ?? null;
    $this->postitId = $args['postitId'] ?? null;
  }

  public function delete(int $id):array {
    $ret = [];

    if (!$id) {
      return ['error' => 'id is needed'];
    }

    $r = $this->checkWallAccess(WPT_WRIGHTS_RW);
    if (!$r['ok']) {
      return ['error' => _("Access forbidden")];
    }

    try {
      $this->db->beginTransaction();

      $stmt = $this->db->prepare(
                 'DELETE FROM postits_comments WHERE users_id = ? AND id = ?');
      $stmt->execute([$this->userId, $id]);

      if ($stmt->rowCount()) {
        $this
          ->db->prepare('
            UPDATE postits SET commentscount = commentscount - 1 WHERE id = ?')
          ->execute([$this->postitId]);
      }

      $this->db->commit();
    } catch(\Exception $e) {
      $this->db->rollBack();
      $ret['error'] = 1;
      error_log(__METHOD__.':'.__LINE__.':'.$e->getMessage ());
    }

    return $ret;
  }
 
  public function add():array {
    $ret = [];
    $dir = $this->getWallDir();
    $wdir = $this->getWallDir('web');
    $currentDate = time();
    $content = $this->data->content;
    $sharerName = $this->data->userFullname;

    $r = $this->checkWallAccess(WPT_WRIGHTS_RW);
    if (!$r['ok']) {
      return ['error' => _("Access forbidden")];
    }

    $ret = [
      'postits_id' => $this->postitId,
      'walls_id' => $this->wallId,
      'users_id' => $this->userId,
      'creationdate' => $currentDate,
      'content' => $content,
    ];

    try {
      $this->db->beginTransaction();

      $this->executeQuery('INSERT INTO postits_comments', $ret);

      $ret['id'] = $this->db->lastInsertId();

      $this
        ->db->prepare('
          UPDATE postits SET commentscount = commentscount + 1
          WHERE id = ?')
        ->execute([$this->postitId]);
      
      $this->db->commit();

      // Send a message if user ref (@xxxx) in msg content.
      if (preg_match_all('/@([^\s\?\.:!,;"]+)/', $content, $m)) {
        foreach ($m as $username) {
          // The wall must be shared with the user.
          ($stmt = $this->db->prepare('
             SELECT id, email, allow_emails, fullname
             FROM users AS u
               INNER JOIN _perf_walls_users AS pwu ON pwu.users_id = u.id
             WHERE walls_id = ? AND LOWER(u.username) = ?'))
               ->execute([$this->wallId, strtolower($username[0])]);

          if ( ($user = $stmt->fetch()) && $user['id'] != $this->userId) {
            (new Task())->execute([
              'event' => Task::EVENT_TYPE_SEND_MESSAGE,
              'method' => 'commentToUser',
              'sendmail' => $user['allow_emails'],
              'sharerName' => $sharerName,
              'userId' => $user['id'],
              'fullname' => $user['fullname'],
              'title' => $this->data->postitTitle ?? null,
              'email' => $user['email'],
              'wallId' => $this->wallId,
              'postitId' => $this->postitId,
              'commentId' => $ret['id'],
              'msg' => $content,
             ]);
          }
        }
      }
    } catch(\Exception $e) {
      $this->db->rollBack();
      $ret['error'] = 1;
      error_log (__METHOD__.':'.__LINE__.':'.$e->getMessage ());
    }

    return $ret;
  }

  public function get(array $args = []):array {
    $id = $args['id'] ?? null;
    $ret = [];

    $r = $this->checkWallAccess(WPT_WRIGHTS_RO);
    if (!$r['ok']) {
      return ['error' => _("Access forbidden")];
    }

    // Return all postit comments
    if (!$id) {
      $data = [];

      ($stmt = $this->db->prepare('
        SELECT
           postits_comments.id
          ,postits_comments.content
          ,users.id AS ownerid
          ,users.fullname AS ownername
          ,postits_comments.creationdate
        FROM postits_comments
          LEFT JOIN users
            ON postits_comments.users_id = users.id
        WHERE postits_id = ?
        ORDER BY postits_comments.creationdate DESC'))
         ->execute([$this->postitId]);

      $ret = $stmt->fetchAll();
    } else {
      ($stmt = $this->db->prepare('
        SELECT * FROM postits_comments WHERE id = ?'))
         ->execute([$id]);

      $ret = $stmt->fetch();
    }

    return $ret;
  }
}
