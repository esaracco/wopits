<?php

namespace Wopits\Wall;

require_once(__DIR__.'/../../config.php');

use Wopits\{User, Wall};

class EditQueue extends Wall {
  private $item;
  private $itemId;

  public function __construct(array $args = null, object $ws = null) {
    parent::__construct($args, $ws);

    $this->item = $args['item'] ?? null;
    $this->itemId = $args['itemId'] ?? null;
  }

  public function addTo():array {
    $item = $this->item;
    $ret = [];

    $r = $this->_checkQueueAccess($item);
    if (!$r['ok']) {
      return $r;
    }

    try {
      if (!empty($this->data->todelete)) {
        $item = 'wall-delete';

        ($stmt = $this->db->prepare('
          SELECT users_id FROM edit_queue
          WHERE walls_id = ? AND users_id <> ? LIMIT 1'))
           ->execute([$this->wallId, $this->userId]);
      } else {
        ($stmt = $this->db->prepare ('
          SELECT users_id FROM edit_queue WHERE item = ? AND item_id = ?'))
            ->execute([$item, $this->itemId]);
      }

      if ( ($r = $stmt->fetch()) ) {
        // If item is already edited by other user, error
        if ($r['users_id'] != $this->userId) {
          $ret['error_msg'] = _("Item is locked by another user");
        }

      // If item is free for editing
      } elseif (
          ($item === 'postit' || $item === 'header') &&
          ($stmt = $this->db->prepare("
           SELECT 1 FROM {$item}s WHERE id = ?"))->execute([$this->itemId]) &&
          !$stmt->fetch()) {
        $ret['error_msg'] = _("Item has been deleted");
      } else {
        $editIds = [$this->itemId];

        // If postit, set editing mode for all of other postits
        // plugged with it at first level
        if ($item === 'postit') {
          ($stmt = $this->db->prepare('
            SELECT item_start, item_end
            FROM postits_plugs WHERE item_start = ? OR item_end = ?'))
             ->execute([$this->itemId, $this->itemId]);

          foreach ($stmt->fetchAll() as $plug) {
            $editIds[] = ($plug['item_start'] == $this->itemId) ?
              $plug['item_end'] : $plug['item_start'];
          }
        }

        foreach ($editIds as $id) {
          $this->executeQuery('INSERT INTO edit_queue', [
            'walls_id' => $this->wallId,
            'item_id' => $id,
            'is_end' => ($id !== $this->itemId) ? 1 : 0,
            'users_id' => $this->userId,
            // FIXME Useless
            'session_id' => $this->sessionId,
            'item' => $item,
          ]);
        }
      }
    } catch(\Exception $e) {
      $ret['error'] = 1;
      error_log (__METHOD__.':'.__LINE__.':'.$e->getMessage ());
    }

    return $ret;
  }

  public function purge():void {
    $this->db->exec('DELETE FROM edit_queue');
  }

  public function removeUser():void {
    $this
      ->db->prepare('DELETE FROM edit_queue WHERE users_id = ?')
      ->execute([$this->userId]);
  }

  public function removeFrom():array {
    $User = new User([], $this->ws);

    $item = $this->item;
    $update = !empty($this->data);
    $ret = $this->_checkQueueAccess($item);

    if (!isset($ret['error_msg'])) {
      try {
        $this->db->beginTransaction();

        switch ($item) {
          case 'wall':
            if (!empty($this->data->todelete)) {
              $ret = $this->deleteWall(false, true);

              // Will be broadcast to users who have this
              if (!isset($ret['error'])) {
                $ret = ['wall' => [
                  'id' => $this->wallId,
                  'unlinked' => $ret['name'],
                ]];
              }
            } elseif ($update) {
              if (!$this->checkWallName($this->data->name)) {
                $this->setBasicProperties();

                $ret = ['wall' => [
                  'id' => $this->wallId,
                  'partial' => 'wall',
                  'wall' => $this->getWall(false, true),
                ]];
              }
              else {
                $ret['error_msg'] =
                  _("A wall with the same name already exists");
              }
            }
            break;
          case 'postit':
            if (!empty($this->data->todelete) || $update) {
              $Postit = new Postit([
                'userId' => $this->userId,
                'wallId' => $this->wallId,
                'postitId' => $this->data->id ?? null,
               ], $this->ws);

              // DELETE the postit
              if (!empty($this->data->todelete)) {
                $Postit->deletePostit();

                $ret = ['wall' => [
                  'id' => $this->wallId,
                  'partial' => 'postit',
                  'action' => 'delete',
                  'postit' => ['id' => $this->data->id],
                  'postits_plugs' => $Postit->getPlugs(true),
                ]];

              // UPDATE the postit
              } elseif ($update) {
                $plugs = (array)$this->data->plugs;

                // Postits plugs update only
                if (isset($this->data->updateplugs)) {
                  foreach ($this->data->plugs as $_postit) {
                    $plugs = (array)$_postit->plugs;

                    if (!empty($plugs)) {
                      $Postit->addRemovePlugs($plugs, $_postit->id);
                    } else {
                       $this
                         ->db->prepare('
                           DELETE FROM postits_plugs WHERE item_start = ?')
                         ->execute([$_postit->id]);
                    }
                  }

                  $ret = ['wall' => [
                    'id' => $this->wallId,
                    'partial' => 'plugs',
                    'action' => 'update',
                    'postits_plugs' => $Postit->getPlugs(true),
                  ]];

                // Full postit update
                } else {
                  $content = $this->data->content;
                  $deadline = empty($this->data->deadline) ?
                                null : $this->data->deadline;
                  $alertShift = ($this->data->alertshift == '') ?
                                  null : $this->data->alertshift;

                  if ($deadline && !is_numeric($deadline)) {
                    $deadline = $User->getUnixDate ($deadline);
                  }

                  // Fix wrong img src URL (tinymce).
                  if (strpos($content, '"api/') !== false) {
                    $content = preg_replace(
                      '#src="api#', 'src="/api', $content);
                  } elseif (strpos($content, '../api/') !== false) {
                    $content = preg_replace(
                      '#src="(\.\./)+api#', 'src="/api', $content);
                  }

                  $data = [
                    'cells_id' => $this->data->cellId,
                    'width' => $this->data->width,
                    'height' => $this->data->height,
                    'item_top' => $this->data->item_top,
                    'item_left' => $this->data->item_left,
                    'item_order' => $this->data->item_order,
                    'classcolor' => $this->data->classcolor,
                    'title' => $this->data->title,
                    'content' => $content,
                    'tags' => $this->data->tags,
                    'obsolete' => empty($this->data->obsolete) ?
                                    0 : $this->data->obsolete,
                    'deadline' => ($deadline == 0) ? null : $deadline,
                    'progress' => $this->data->progress,
                  ];

                  // Set deadline timezone with user timezone only if
                  // deadline has changed
                  if ($this->data->updatetz) {
                    $data['timezone'] = $User->getTimezone();
                  }

                  $this->executeQuery('UPDATE postits', $data,
                    ['id' => $this->data->id]);

                  // Clear all alerts if deadline is reset
                  if (!$deadline) {
                    $this
                      ->db->prepare('
                          DELETE FROM postits_alerts WHERE postits_id = ?')
                      ->execute([$this->data->id]);
                  // Remove user deadline alert if not set
                  } elseif (is_null($alertShift)) {
                    $this
                      ->db->prepare('
                          DELETE FROM postits_alerts
                          WHERE postits_id = ? AND users_id = ?')
                      ->execute([$this->data->id, $this->userId]);
                  } else {
                    $this->checkDBValue(
                      'postits_alerts', 'postits_id', $this->data->id);
                    $this->checkDBValue(
                      'postits_alerts', 'users_id', $this->userId);
                    $this->checkDBValue(
                      'postits_alerts', 'alertshift', $alertShift);

                    ($stmt = $this->db->prepare("
                      INSERT INTO postits_alerts (
                        postits_id, users_id, walls_id, alertshift
                      ) VALUES (
                        :postits_id, :users_id, :walls_id, :alertshift
                      ) {$this->getDuplicateQueryPart(
                         ['postits_id', 'users_id'])}
                      alertshift = :alertshift_1"))
                       ->execute([
                         ':postits_id' => $this->data->id,
                         ':users_id' => $this->userId,
                         ':walls_id' => $this->wallId,
                         ':alertshift' => $alertShift,
                         ':alertshift_1' => $alertShift,
                       ]);
                  }

                  // Delete postit content pictures if necessary
                  if ($this->data->hadpictures ||
                      $this->data->hasuploadedpictures) {
                    $Postit->deletePictures($this->data);
                  }

                  $ret = ['wall' => [
                    'id' => $this->wallId,
                    'partial' => 'postit',
                    'action' => 'update',
                    'postit' => $Postit->getPostit(),
                  ]];

                  if (!empty($plugs)) {
                    $Postit->addRemovePlugs($plugs);
                    $ret['wall']['postits_plugs'] = $Postit->getPlugs();
                  }
                }
              }
            }
            break;
          case 'cell':
            if ($update) {
              // If we are moving col/row
              if (!empty($this->data->move)) {
                $ret = $this->moveRow($this->data->move);
              } else {
                $ret['wall'] = $this->updateCells();
              }
            }
            break;
          case 'header':
            if ($update) {
              $this->updateHeaders();
              $ret['wall'] = $this->updateCells();
            }
            break;
        }

        $this->db->commit();
      } catch(\Exception $e) {
        $this->db->rollBack();
        $ret['error'] = 1;
        error_log (__METHOD__.':'.__LINE__.':'.$e->getMessage ());
      }
    }

    // Clean up the edit queue, whatever happened
    $this->removeUser();

    return $ret;
  }

  private function _checkQueueAccess(string $item):array {
    $needAdminAccess = ($item === 'header' || $item === 'wall');

    // Wall update need admin rights, else need write access
    $access = $needAdminAccess ? WPT_WRIGHTS_ADMIN : WPT_WRIGHTS_RW;

    $r = $this->checkWallAccess($access);
    if (!$r['ok']) {
      return ['ok' => 0,
              'error_msg' => $needAdminAccess ?
                 _("You must have admin access to perform this action") :
                 _("You must have write access to perform this action")];
    }

    return $r;
  }
}
