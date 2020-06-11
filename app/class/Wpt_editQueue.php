<?php

  require_once (__DIR__.'/Wpt_wall.php');

  class Wpt_editQueue extends Wpt_wall
  {
    private $item;
    private $itemId;

    public function __construct ($args = null)
    {
      parent::__construct ($args);

      $this->item = $args['item']??null;
      $this->itemId = $args['itemId']??null;
    }

    public function addTo ()
    {
      $q = $this->getFieldQuote ();
      $item = $this->item;
      $editIds = [$this->itemId];
      $ret = [];

      $r = $this->_checkQueueAccess ($item);
      
      if (!$r['ok'])
        return $r;

      try
      {
        $stmt = null;

        if (!empty ($this->data->todelete))
        {
          $item = 'wall-delete';

          $stmt = $this->prepare ("
            SELECT session_id FROM edit_queue
            WHERE walls_id = :walls_id
              AND session_id <> :session_id LIMIT 1");
          $stmt->execute ([
            ':walls_id' => $this->wallId,
            ':session_id' => $GLOBALS['sessionId']
          ]);
        }
        else
        {
          // If postit, set editing mode for all of other postits plugged
          // with it
          if ($item == 'postit')
          {
            $stmt = $this->prepare ("
              SELECT start, ${q}end$q
              FROM postits_plugs
              WHERE start = ? OR ${q}end$q = ?");
            $stmt->execute ([$this->itemId, $this->itemId]);
            while ($plug = $stmt->fetch ())
              $editIds[] = ($plug['start'] == $this->itemId) ?
                             $plug['end'] : $plug['start'];
          }

          $stmt = $this->prepare ('
            SELECT session_id FROM edit_queue
            WHERE item = ?
              AND item_id IN ('.
                implode(",", array_map ([$this, 'quote'], $editIds)).
              ') LIMIT 1');
          $stmt->execute ([$item]);
        }

        if ($r = $stmt->fetch ())
        {
          // If item is already edited by other user, error
          if ($r['session_id'] != $GLOBALS['sessionId'])
            $ret['error_msg'] = _("Someone is editing this element.");
        }
        // If item is free for editing
        elseif (
          ($item == 'postit' || $item == 'header') &&
          ($stmt = $this->prepare ("SELECT 1 FROM {$item}s WHERE id = ?")) &&
          $stmt->execute ([$this->itemId]) &&
          !$stmt->fetch ())
        {
          $ret['error_msg'] = _("This item has been deleted.");
        }
        else
        {
          foreach ($editIds as $id)
            $this->executeQuery ('INSERT INTO edit_queue', [
              'walls_id' => $this->wallId,
              'item_id' => $id,
              'users_id' => $this->userId,
              'session_id' => $GLOBALS['sessionId'],
              'item' => $item
            ]);
        }
      }
      catch (Exception $e)
      {
        error_log (__METHOD__.':'.__LINE__.':'.$e->getMessage ());
        $ret['error'] = 1;
      }

      return $ret;
    }

    public function removeUser ()
    {
      $this
        ->prepare('DELETE FROM edit_queue WHERE session_id = ?')
        ->execute ([$GLOBALS['sessionId']]);
    }

    public function removeFrom ()
    {
      $User = new Wpt_user ();

      $item = $this->item;
      $update = (!empty ($this->data));
      $ret = $this->_checkQueueAccess ($item);

      if (!isset ($ret['error_msg']))
      {
        try
        {
          $this->beginTransaction ();
  
          switch ($item)
          {
            case 'wall':
  
              if (!empty ($this->data->todelete))
              {
                $ret = $this->deleteWall ();
  
                if (!isset ($ret['error']))
                  $ret = ['wall' => [
                    'id' => $this->wallId,
                    // This message will be broadcast to users who have this
                    // wall opened
                    'removed' => _("This wall has been deleted!")]
                  ];
              }
              elseif ($update)
              {
                if (!$this->checkWallName ($this->data->name))
                {
                  $this->setBasicProperties ();

                  $ret = ['wall' => [
                    'id' => $this->wallId,
                    'partial' => 'wall',
                    'wall' => $this->getWall (true)
                  ]];
                }
                else
                  $ret['error_msg']=
                    _("A wall with the same name already exists.");
              }
  
              break;
  
            case 'postit':
  
              if (!empty ($this->data->todelete) || $update)
              {
                require_once (__DIR__.'/Wpt_postit.php');

                $Postit = new Wpt_postit ([
                  'userId' => $this->userId,
                  'wallId' => $this->wallId,
                  'postitId' => $this->data->id ?? null
                 ]);

                // DELETE the postit
                if (!empty ($this->data->todelete))
                {
                  $Postit->deletePostit ();

                  $ret = ['wall' => [
                    'id' => $this->wallId,
                    'partial' => 'postit',
                    'action' => 'delete',
                    'postit' => ['id' => $this->data->id],
                    'postits_plugs' => $Postit->getPlugs (true)
                  ]];
                }
                // UPDATE the postit
                elseif ($update)
                {
                  $plugs = (array) $this->data->plugs;

                  // Postits plugs update only
                  if (isset ($this->data->updateplugs))
                  {
                    foreach ($this->data->plugs as $_postit)
                    {
                      $plugs = (array) $_postit->plugs;

                      if (!empty ($plugs))
                        $Postit->addRemovePlugs ($plugs, $_postit->id);
                       else
                         $this
                           ->prepare('
                             DELETE FROM postits_plugs WHERE start = ?')
                           ->execute ([$_postit->id]);
                    }

                    //FIXME
                    $ret = ['wall' => [
                      'id' => $this->wallId,
                      'partial' => 'plugs',
                      'action' => 'update',
                      'postits_plugs' => $Postit->getPlugs (true)
                    ]];
                  }
                  // Full postit update
                  else
                  {
                    $deadline = (empty($this->data->deadline)) ?
                                  null : $this->data->deadline;

                    if ($deadline && !is_numeric ($deadline))
                      $deadline = $User->getUnixDate ($deadline);

                    $data = [
                      'cells_id' => $this->data->cellId,
                      'width' => $this->data->width,
                      'height' => $this->data->height,
                      'top' => $this->data->top,
                      'left' => $this->data->left,
                      'classcolor' => $this->data->classcolor,
                      'title' => $this->data->title,
                      'content' => $this->data->content,
                      'tags' => $this->data->tags,
                      'obsolete' => (empty ($this->data->obsolete)) ?
                                      0 : $this->data->obsolete,
                      'deadline' => ($deadline == 0) ? null : $deadline
                    ];

                    // Set deadline timezone with user timezone only if
                    // deadline has changed
                    if ($this->data->updatetz)
                      $data['timezone'] = $User->getTimezone ();

                    $this->executeQuery ('UPDATE postits', $data,
                      ['id' => $this->data->id]);

                    // Delete postit content pictures if necessary
                    if ($this->data->hadpictures ||
                        $this->data->hasuploadedpictures)
                      $Postit->deletePictures ($this->data);

                    $ret = ['wall' => [
                      'id' => $this->wallId,
                      'partial' => 'postit',
                      'action' => 'update',
                      'postit' => $Postit->getPostit ()
                    ]];

                    if (!empty ($plugs))
                    {
                      $Postit->addRemovePlugs ($plugs);
                      $ret['wall']['postits_plugs'] = $Postit->getPlugs ();
                    }
                  }
                }
              }
  
              break;
  
            case 'cell':
  
              $this->updateCells ();

              //FIXME
              $ret['wall'] = $this->getWall ();
  
             break;
  
            case 'header':
  
              if ($update)
              {
                $this->updateHeaders ();
                $this->updateCells ();

                //FIXME
                $ret['wall'] = $this->getWall ();
              }
  
              break;
          }
  
          $this->commit ();
        }
        catch (Exception $e)
        {
          $this->rollback ();
  
          error_log (__METHOD__.':'.__LINE__.':'.$e->getMessage ());
          $ret['error'] = 1;
        }
      }

      // Clean up the edit queue, whatever happened
      $this->removeUser ();

      return $ret;
    }

    private function _checkQueueAccess ($item)
    {
      $needAdminAccess = ($item == 'header' || $item == 'wall');

      // Wall update need admin rights, else need write access
      $access = ($needAdminAccess) ?
                  WPT_RIGHTS['walls']['admin'] : WPT_RIGHTS['walls']['rw'];

      $r = $this->checkWallAccess ($access);

      if (!$r['ok'])
        return (isset ($r['id'])) ? $r :
                 ['ok' => 0,
                  'error_msg' => ($needAdminAccess) ?
                    _("You must have admin access to perform this action.") :
                    _("You must have write access to perform this action.")];

      return $r;
    }
  }
