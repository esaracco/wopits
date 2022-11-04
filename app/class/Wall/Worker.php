<?php

namespace Wopits\Wall;

require_once(__DIR__.'/../../config.php');

use Wopits\{Helper, User, Wall, Services\Task};

class Worker extends Wall {
  public $cellId;
  public $postitId;

  public function __construct(array $args = [], object $ws = null) {
    parent::__construct($args, $ws);

    $this->cellId = $args['cellId'] ?? null;
    $this->postitId = $args['postitId'] ?? null;
  }

  public function delete(int $userId, bool $noCheck = false):array {
    $newTransaction = (!$this->db->inTransaction());
    $ret = [];

    if (!$userId) {
      return ['error' => 'id is needed'];
    }

    if (!$noCheck) {
      $r = $this->checkWallAccess(WPT_WRIGHTS_RW);
      if (!$r['ok']) {
        return ['error' => _("Access forbidden")];
      }
    }

    try {
      if ($newTransaction) {
        $this->db->beginTransaction();
      }

      $stmt = $this->db->prepare('
        DELETE FROM postits_workers WHERE postits_id = ? AND users_id = ?');
      $stmt->execute([$this->postitId, $userId]);

      if ($stmt->rowCount()) {
        $this
          ->db->prepare('
            UPDATE postits SET workerscount = workerscount - 1
            WHERE id = ?')
          ->execute([$this->postitId]);
      }

      if ($newTransaction) {
        $this->db->commit();
      }
    } catch(\Exception $e) {
      if ($newTransaction) {
        $this->db->rollBack();
      }
      $ret['error'] = 1;
      error_log (__METHOD__.':'.__LINE__.':'.$e->getMessage ());
    }

    return $ret;
  }

  public function search(array $args):array {
    $ret = ['users' => null];

    // User must be logged to view users
    if (empty($this->userId)) {
      return ['error' => _("Access forbidden")];
    }

    if ( ($search = Helper::unaccent($args['search'])) ) {
      ($stmt = $this->db
        ->prepare('
          SELECT DISTINCT U.id, U.fullname, U.username
          FROM users AS U
            INNER JOIN _perf_walls_users AS PWU ON PWU.users_id = U.id
          WHERE PWU.walls_id = ?
            AND U.searchdata LIKE ?
            AND U.id NOT IN (
              SELECT users_id FROM postits_workers WHERE postits_id = ?
            )'))
        ->execute([$this->wallId, "%$search%", $this->postitId]);

      $ret['users'] = $stmt->fetchAll();
    }

    return $ret;
  }

  public function notify() {
    $ret = [];
    $ids = $this->data->ids ?? [];

    $r = $this->checkWallAccess(WPT_WRIGHTS_RW);
    if (!$r['ok']) {
      return ['error' => _("Access forbidden")];
    }

    ($stmt = $this->db->prepare('SELECT fullname FROM users WHERE id = ?'))
       ->execute([$this->userId]);
    $sharerName = $stmt->fetch(\PDO::FETCH_COLUMN, 0);

    ($stmt = $this->db
      ->prepare('
        SELECT u.id, u.email, u.allow_emails, u.fullname
        FROM users AS u
          INNER JOIN postits_workers AS pw ON pw.users_id = u.id
        WHERE postits_id = ?
          AND u.id IN ('.
        implode(',', array_map([$this->db, 'quote'], $ids)).')'))
      ->execute([$this->postitId]);

    $_args = [
      'users' => $stmt->fetchAll(),
      'userId' => $this->userId,
      'wallId' => $this->wallId,
      'postitId' => $this->postitId,
      'sharerName' => $sharerName,
      'title' => $this->data->postitTitle ?? null,
    ];

    // Use async Coroutine to safely use sleep in order to relieve SMTP
    go(function() use ($_args) {
      $Task = new Task();

      foreach ($_args['users'] as $user) {
        if ($user['id'] != $_args['userId']) {
          $Task->execute([
            'event' => Task::EVENT_TYPE_SEND_MESSAGE,
            'method' => 'notifyWorker',
            'sendmail' => $user['allow_emails'],
            'sharerName' => $_args['sharerName'],
            'userId' => $user['id'],
            'fullname' => $user['fullname'],
            'title' => $_args['title'],
            'email' => $user['email'],
            'wallId' => $_args['wallId'],
            'postitId' => $_args['postitId'],
           ]);

           \Swoole\Coroutine::sleep(2);
         }
       }
    });
  }

  public function add(int $userId):array {
    $ret = [];

    $r = $this->checkWallAccess(WPT_WRIGHTS_RW);
    if (!$r['ok']) {
      return ['error' => _("Access forbidden")];
    }

    ($stmt = $this->db->prepare('
      SELECT 1 FROM postits_workers
      WHERE postits_id = ? AND users_id = ?'))
       ->execute([$this->postitId, $userId]);

    if ($stmt->fetch()) {
      $ret['error_msg'] = _("The user is already involved in this note");
    } else {
      try {
        $this->db->beginTransaction();

        $this->executeQuery('INSERT INTO postits_workers', [
          'walls_id' => $this->wallId,
          'postits_id' => $this->postitId,
          'users_id' => $userId,
        ]);

        $ret['id'] = $this->db->lastInsertId();

        $this
          ->db->prepare('
            UPDATE postits SET workerscount = workerscount + 1
            WHERE id = ?')
          ->execute([$this->postitId]);

        $this->db->commit();
      } catch(\Exception $e) {
        $this->db->rollBack();
        $ret['error'] = 1;
        error_log (__METHOD__.':'.__LINE__.':'.$e->getMessage ());
      }
    }

    return $ret;
  }

  public function get(int $id = null):array {
    $ret = [];

    $r = $this->checkWallAccess(WPT_WRIGHTS_RO);
    if (!$r['ok']) {
      return ['error' => _("Access forbidden")];
    }

    ($stmt = $this->db->prepare('
      SELECT
        U.id,
        U.fullname,
        U.username,
        U.picture,
        U.about
      FROM postits_workers AS PW
        LEFT JOIN users AS U
          ON PW.users_id = U.id
      WHERE postits_id = ?
      ORDER BY U.fullname, U.username'))
       ->execute([$this->postitId]);

    return ['users' => $stmt->fetchAll()];
  }
}
