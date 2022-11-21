#!/usr/bin/php
<?php

require_once(__DIR__.'/../../app/config.php');

class _Local extends Wopits\Base {
  public function upgrade():void {
    $stmt = $this->db->query('SELECT id FROM users');
    while ($u = $stmt->fetch()) {
      $User = new Wopits\User(['userId' => $u['id']]);

      $s = $User->getSettings(false);

      if (isset($s->openedWalls)) {
        for ($i = 0; $i < count($s->openedWalls); $i++) {
          $s->openedWalls[$i] = intval($s->openedWalls[$i]);
        }
      }

      if (isset($s->recentWalls)) {
        for ($i = 0; $i < count($s->recentWalls); $i++) {
          $s->recentWalls[$i] = intval($s->recentWalls[$i]);
        }
      }

      if (isset($s->activeWall)) {
        $s->activeWall = intval($s->activeWall);
      }

      $User->saveSettings(json_encode($s));
    }
  }
}

(new _Local())->upgrade();

?>
