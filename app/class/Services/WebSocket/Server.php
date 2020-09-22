<?php

namespace Wopits\Services\WebSocket;

require_once (__DIR__.'/../../../config.php');

use Swoole\{Http\Request, WebSocket\Frame, WebSocket\Server as SwooleServer};

use Wopits\{Base, Helper, User, Wall};
use Wopits\Wall\{EditQueue, Group, Postit};
use Wopits\Services\Task;

class Server
{
  private $_server;

  public function __construct ()
  {
    $workerNum = swoole_cpu_num() * 2;

    $this->_server = new SwooleServer ('127.0.0.1', WPT_WS_PORT);

    $this->_server->set ([
      'daemonize' => true,
      'log_file' => WPT_LOG_PATH.'/server-ws.log',
      'pid_file' => __DIR__.'/../../../services/run/server-ws.pid',
      'worker_num' => $workerNum,
      'reactor_num' => $workerNum * 2
    ]);

    $this->_server->db = new VolatileTables ();

    // Attach events.
    foreach (['start', 'open', 'message', 'close' ] as $e)
      $this->_server->on ($e, [$this, "on$e"]);
  }

  public function start ():void
  {
    $this->_server->start ();
  }

  public function onStart (SwooleServer $server):void
  {
    error_log (date('Y-m-d H:i:s').
      ' [INFO][internal] wopits WebSocket server is listening on port '.
         WPT_WS_PORT);
  }

  public function onOpen (SwooleServer $server, Request $req):void
  {
    $fd = $req->fd;
    $header = $req->header;

    // Internal wopits client
    if (empty ($header['x-forwarded-server']) &&
        strpos ($header['user-agent'], 'PHPWebSocketClient') !== false)
    {
      $this->_server->db->internals->set ($fd, []);
    }
    else
    {
      // Common wopits client
      list ($client, $ip) = $this->_createClient ($req);

      if ($client)
      {
        $userId = $client->id;
        $settings = json_decode ($client->settings);

        $this->_server->db->tSet ('clients', $fd, $client);

        $this->_log ($fd, 'info',
          "OPEN (".$this->_server->db->clients->count().
          " connected clients)", $client->ip);

        // Register user opened walls
        $this->_registerOpenedWalls ($fd, $userId, null, $settings);

        // Register user active wall
        $this->_registerActiveWall ($fd, $userId, null, $settings);
  
        if (!empty ($settings->activeWall))
          $this->_pushWallsUsersCount ([$settings->activeWall]);

        $this->_server->db->lAdd ('usersUnique', $userId, $fd);
      }
      else
      {
        $this->_log ($fd, 'error',
          'UNAUTHORIZED login attempt! ('.print_r((array)$req, true).')', $ip);

        //FIXME
        $server->push ($fd, json_encode (['action' => 'exitsession']));
        $server->disconnect ($fd);
      }
    }
  }

  public function onMessage (SwooleServer $server, Frame $frame):void
  {
    $fd = $frame->fd;
    $msg = json_decode ($frame->data);

    // Common wopits client
    if (!$this->_server->db->internals->exist ($fd))
    {
      $data = ($msg->data) ? json_decode (urldecode ($msg->data)) : null;
      $wallId = null;
      $wallsIds = null;
      $postitId = null;
      $push = false;
      $action = '';
      $ret = [];

      // ROUTE ping
      // Nothing special: just keep WS connection with client alive.
      if ($msg->route == 'ping')
        return;
  
      $client = $this->_server->db->tGet ('clients', $fd);

      //////////////////////////// ROUTING PATHS /////////////////////////////

      // ROUTE chat
      if (preg_match ('#^wall/(\d+)/chat$#', $msg->route, $m))
      {
        list (,$wallId) = $m;

        $push = true;
        $action = 'chat';
        $username = $client->username;

        $ret = [
          'method' => $msg->method,
          'wall' => ['id' => $wallId],
          'username' => $username
        ];

        if ($msg->method == 'POST')
          $ret['msg'] = preg_replace ('/<[^>]+>/', '', $data->msg);
        else
        {
          $ret['internal'] = 1;

          $openedChats = explode (',', $client->openedChats);
          $cu = $this->_server->db->jGet ('chatUsers', $wallId);

          switch ($msg->method)
          {
            case 'PUT':
              $cu->$fd = (object)['id' => $client->id,
                                  'name' => $client->username];
              $this->_server->db->jSet ('chatUsers', $wallId, $cu);

              $openedChats[] = $wallId;
              $client->openedChats = implode (',', $openedChats);
              $this->_server->db->tSet ('clients', $fd, $client);

              $ret['msg'] = '_JOIN_';
              break;

            case 'DELETE':
              // Handle some random case (user close its browser...)
              if (!$this->_server->db->isEmpty ($cu))
              {
                $this->_unsetItem ('chatUsers', $wallId, $fd);

                array_splice ($openedChats,
                  array_search ($wallId, $openedChats), 1);
                $client->openedChats = implode (',', $openedChats);
                $this->_server->db->tSet ('clients', $fd, $client);

                $ret['msg'] = '_LEAVE_';
              }
              break;
          }

          $this->_injectChatUsersData ($wallId, $ret);
        }
      }
      // ROUTE User
      // (here we manage users active walls arrays)
      elseif (preg_match ('#^user/(settings|update)$#', $msg->route, $m))
      {
        list (,$type) = $m;

        $User = new User (['data' => $data], $client);

        // User's settings
        if ($type == 'settings')
        {
          $settings = json_decode ($client->settings);
          $oldSettings = json_decode ($User->getSettings()??'{}');
          $newSettings = json_decode ($data->settings);
  
          // Active wall
          $this->_registerActiveWall (
            $fd, $User->userId, $oldSettings, $newSettings);
          $settings->activeWall = $newSettings->activeWall??null;
  
          // Opened walls
          $this->_registerOpenedWalls (
            $fd, $User->userId, $oldSettings, $newSettings);
          $settings->openedWalls = $newSettings->openedWalls??[];

          $client->settings = json_encode ($settings);
          $this->_server->db->tSet ('clients', $fd, $client);

          $ret = $User->saveSettings ();
        }
        // User's data update
        elseif ($type == 'update')
        {
          $ret = $User->update ();

          // If user has checked the invisibility mode, disconnect all users
          // from its walls.
          if (isset ($ret['closewalls']))
          {
            $action = 'closewalls';
            $wallsIds = $ret['closewalls'];
            unset ($ret['closewalls']);
          }
        }

        // Reload all current user sessions if any.
        if (!$client->final)
        {
          foreach (
            $this->_server->db->lGet ('usersUnique', $User->userId) as $_fd)
          {
            if ($_fd != $fd)
            {
              $_client = $this->_server->db->tGet ('clients', $_fd);

              $toSend = ['action' => 'reloadsession'];
              if (isset ($newSettings->locale))
                $toSend['locale'] = $newSettings->locale;

              $_client->final = 1;
              $this->_server->db->tSet ('clients', $_fd, $_client);

              $server->push ($_fd, json_encode ($toSend));
            }
          }
        }
        else
        {
          $client->final = 0;
          $this->_server->db->tSet ('clients', $fd, $client);
        }
      }
      // ROUTE edit queue
      // - PUT to block updates for other users on a item
      // - DELETE to release item and push updates to user's walls
      elseif (preg_match (
                '#^wall/(\d+)/editQueue/'.
                '(wall|cell|header|postit|group)/(\d+)$#', $msg->route, $m))
      {
        list (,$wallId, $item, $itemId) = $m;

        $EditQueue = new EditQueue ([
          'wallId' => $wallId,
          'data' => $data,
          'item' => $item,
          'itemId' => $itemId
        ], $client);

        switch ($msg->method)
        {
          // PUT
          case 'PUT':
            $ret = $EditQueue->addTo ();
            break;

          // DELETE
          case 'DELETE':
            $ret = $EditQueue->removeFrom ();
            if ($data && !isset ($ret['error_msg']) && !isset ($ret['error']))
            {
              $push = true;

              if (empty ($ret['wall']['unlinked']))
              {
                $action = 'refreshwall';

                if ($item == 'postit')
                  $postitId = $itemId;
              }
              else
                $action = 'unlinked';
            }
            break;
        }
      }
      // ROUTE Generic groups
      elseif (preg_match (
                '#^group/?(\d+)?/?(addUser|removeUser)?/?(\d+)?$#',
                $msg->route, $m))
      {
        @list (,$groupId, $type, $userId) = $m;

        $Group = new Group ([
          'data' => $data,
          'groupId' => $groupId
        ], $client);

        switch ($msg->method)
        {
          // POST
          // For both generic and dedicated groups
          case 'POST':
            $ret = $Group->update ();
            break;

          // PUT
          case 'PUT':
            $ret = ($userId) ?
                     $Group->addUser (['userId' => $userId]) :
                     $Group->create (['type' => WPT_GTYPES_GEN]);
            break;

          // DELETE
          case 'DELETE':
            $ret = ($userId) ?
              $this->_removeUserFromGroup ([
                'obj' => $Group,
                'userId' => $userId,
                'wallIds' => $Group->getWallsByGroup ()
              ]) : $Group->delete ();
            break;
        }
      }
      // ROUTE Dedicated groups
      elseif (preg_match (
                '#^wall/(\d+)/group/?(\d+)?/?'.
                '(addUser|removeUser|link|unlink)?/?(\d+)?$#',
                $msg->route, $m))
      {
        @list (, $wallId, $groupId, $type, $userId) = $m;

        $Group = new Group ([
          'wallId' => $wallId,
          'data' => $data,
          'groupId' => $groupId
        ], $client);

        switch ($msg->method)
        {
          // POST
          // For both generic and dedicated groups
          case 'POST':
            if ($type == 'link')
              $ret = $Group->link ();
            elseif ($type == 'unlink')
            {
              $ret = $Group->unlink ();
              if (!isset ($ret['error']))
              {
                $push = true;
                $action = 'unlinked';
              }
            }
            break;

          // PUT
          case 'PUT':
            $ret = ($userId) ?
                     $Group->addUser (['userId' => $userId]) :
                     $Group->create (['type' => WPT_GTYPES_DED]);
            break;

          // DELETE
          case 'DELETE':
            $ret = ($userId) ?
              $this->_removeUserFromGroup ([
                'obj' => $Group,
                'userId' => $userId,
                'wallIds' => [$wallId]
              ]) : $Group->delete ();
            break;
        }
      }
      // ROUTE Remove user from group.
      elseif (preg_match ('#^wall/(\d+)/group/([\d,]+)/removeMe$#',
                $msg->route, $m))
      {
        @list (, $wallId, $groupIds) = $m;

        if ($msg->method == 'DELETE')
          (new Group (['wallId' => $wallId], $client))
             ->removeMe (explode (',', $groupIds));
      }
      // ROUTE Wall users view
      //TODO We should use ajax instead of ws
      elseif (preg_match ('#^wall/(\d+)/usersview$#',
                $msg->route, $m))
      {
        @list (,$wallId) = $m;

        if ($msg->method == 'GET')
          $ret = (new Wall (['wallId' => $wallId], $client))->getUsersview (
            array_keys ((array)$this->_server->db->jGet ('activeWallsUnique',
                                                         $wallId)));
      }
      // ROUTE Postit creation
      elseif (preg_match ('#^wall/(\d+)/cell/(\d+)/postit$#', $msg->route, $m))
      {
        list (,$wallId, $cellId) = $m;
  
        $push = true;
        $action = 'refreshwall';
  
        $ret = (new Postit ([
          'wallId' => $wallId,
          'data' => $data,
          'cellId' => $cellId
        ], $client))->create ();
      }
      // ROUTE Col/row creation/deletion
      elseif (preg_match ('#^wall/(\d+)/(col|row)/?(\d+)?$#', $msg->route, $m))
      {
        @list (,$wallId, $item, $itemPos) = $m;

        $push = true;
        $action = 'refreshwall';
  
        $Wall = new Wall ([
          'wallId' => $wallId,
          'data' => $data
        ], $client);

        switch ($msg->method)
        {
          // PUT
          case 'PUT':
            $ret = $Wall->createWallColRow (['item' => $item]);
            break;

          // DELETE
          case 'DELETE':
            $ret = $Wall->deleteWallColRow ([
              'item' => $item,
              'itemPos' => $itemPos
            ]);
            break;
        }
      }
      // ROUTE for header's pictures
      elseif (preg_match ('#^wall/(\d+)/header/(\d+)/picture$#',
                $msg->route, $m))
      {
        list (,$wallId, $headerId) = $m;

        if ($msg->method == 'DELETE')
          $ret = (new Wall ([
            'wallId' => $wallId,
            'data' => $data
          ], $client))->deleteHeaderPicture (['headerId' => $headerId]);
      }
      // ROUTE Postit attachments
      elseif (preg_match (
                '#^wall/(\d+)/cell/(\d+)/postit/(\d+)/'.
                'attachment/?(\d+)?$#',
                $msg->route, $m))
      {
        @list (,$wallId, $cellId, $postitId, $itemId) = $m;

        if ($msg->method == 'DELETE')
          $ret = (new Postit ([
            'wallId' => $wallId,
            'cellId' => $cellId,
            'postitId' => $postitId
          ], $client))->deleteAttachment (['attachmentId' => $itemId]);
      }
      // ROUTE User profil picture
      elseif ($msg->route == 'user/picture')
      {
        if ($msg->method == 'DELETE')
          $ret = (new User (['data' => $data], $client))->deletePicture ();
      }
      // ROUTE debug
      // Debug
      //<WPTPROD-remove>
      elseif ($msg->route == 'debug')
        $this->_debug ($data);
      //</WPTPROD-remove>

      // If current user has just activated its invisibility mode, close its
      // walls for all current users.
      if ($action == 'closewalls')
      {
        $userId = $client->id;

        foreach ($wallsIds as $_wallId)
          foreach (
            $this->_server->db->jGet ('openedWalls', $_wallId)
              as $_fd => $_userId)
          {
            if ($_userId != $userId)
              $server->push ($_fd, json_encode ([
                'action' => 'unlinked',
                'wall' => ['id' => $_wallId]
              ]));
          }
      }
      else
      {
        $ret['action'] = $action;

        // Boadcast results if needed
        if ($push)
        {
          $cacheName = ($action == 'chat' || $action == 'unlinked') ?
            'openedWalls' : 'activeWalls';

          $w = $this->_server->db->jGet ($cacheName, $wallId);
          if (!$this->_server->db->isEmpty ($w))
          {
            $userId = $client->id;
            $json = json_encode ($ret);

            foreach ($w as $_fd => $_userId)
            {
              // Message sender material will be broadcasted later.
              if ($_fd != $fd)
              {
                // If we are broadcasting on other sender user's sessions.
                if ($_userId == $userId)
                {
                  // Keep broadcasting only for walls updates.
                  if ($action == 'refreshwall')
                    $server->push ($_fd,
                      ($postitId) ?
                        // If postit update, send user's specific data too.
                        json_encode ($this->_injectUserSpecificData (
                                       $ret, $postitId, $client)) : $json);
                }
                else
                  $server->push ($_fd, $json);
              }
            }
          }
        }
      }

      // Respond to the sender.
      $ret['msgId'] = $msg->msgId??null;
      $server->push ($fd, json_encode ($ret));
    }
    // Internal wopits communication.
    else
    {
      switch ($msg->action)
      {
        // ping
        case 'ping':

          $this->_ping ();

          break;

        // close-walls
        case 'close-walls':

          $walls = $this->_server->db->openedWalls;
          for ($walls->rewind (); $walls->current (); $walls->next ())
          {
            $_wallId = $walls->key ();

            if (in_array ($_wallId, $msg->ids))
            {
              foreach (
                $this->_server->db->jGet ('openedWalls', $_wallId)
                  as $_fd => $_userId)
              {
                if ($_userId != $msg->userId)
                  $server->push ($_fd,
                    json_encode ([
                      'action' => 'unlinked',
                      'wall' => ['id' => $_wallId]
                    ]));
              }
            }
          }

          break;
    
        // reload & mainupgrade
        //FIXME TODO If a user has something being edited, wait for him to
        //           finish.
        case 'reload':
        case 'mainupgrade':

          // Purge SQL editing queue.
          (new EditQueue())->purge ();

          $_json = json_encode ($msg);
          $clients = $this->_server->db->clients;
          for ($clients->rewind (); $clients->current (); $clients->next ())
            $server->push ($clients->key (), $_json);

          break;

        // dump-all
        case 'dump-all':

          $tmp = '';

          foreach (['clients', 'openedWalls', 'activeWalls', 'chatUsers'] as $t)
          {
            $tmp .= "\n* $t:\n";
            $tb = $this->_server->db->$t;
            for ($tb->rewind (); $tb->current (); $tb->next ())
              $tmp .= print_r ($tb->get ($tb->key ()), true);
          }

          $server->push ($fd, $tmp);

          break;

        // stat-users
        case 'stat-users':

          $server->push ($fd, ("\n".
            '* Sessions: '.(count($this->_server->connection_list())-1)."\n".
            '* Unique users: '.
              $this->_server->db->usersUnique->count()."\n".
            "----------\n".
            '* Opened walls: '.
              $this->_server->db->openedWalls->count()."\n".
            '* Active walls: '.
              $this->_server->db->activeWalls->count()."\n".
            '* Unique active walls: '.
              $this->_server->db->activeWallsUnique->count()."\n".
            "----------\n".
            '* Current chats: '.
              $this->_server->db->chatUsers->count()."\n"
          ));

          break;

        default:
          $this->_log ($fd, 'error',
                       "Unknown action `{$msg->action}`", 'internal');
      }
    }
  }

  public function onClose (SwooleServer $server, int $fd):void
  {
    // Internal wopits client
    if ($this->_server->db->internals->exist ($fd))
    {
      $this->_server->db->internals->del ($fd);
    }
    // Common wopits client
    elseif ( ($client = $this->_server->db->tGet ('clients', $fd)) &&
             !$this->_server->db->isEmpty ($client) &&
             //FIXME
             !empty ($client->id))
    {
      $userId = $client->id;
      $settings = json_decode ($client->settings);

      if (!empty ($settings->activeWall))
      {
        $wallId = $settings->activeWall;

        // Remove user's action from DB edit queue
        (new EditQueue([], $client))->removeUser ();

        // Remove user from local view queue
        $this->_unsetItem ('activeWalls', $wallId, $fd); 
        $this->_unsetActiveWallsUnique ($wallId, $userId);

        $this->_pushWallsUsersCount ([$wallId]);

        // Leave wall's chat if needed.
        if ( ($cu = $this->_server->db->jGet ('chatUsers', $wallId)) &&
             isset ($cu->$fd))
        {
          $this->_unsetItem ('chatUsers', $wallId, $fd);

          $_ret = [
            'method' => 'DELETE',
            'wall' => ['id' => $wallId],
            'username' =>$client->username,
            'action' => 'chat',
            'internal' => 1,
            'msg' => '_LEAVE_'
          ];

          $this->_injectChatUsersData ($wallId, $_ret);
          $_json = json_encode ($_ret);

          foreach (
            $this->_server->db->jGet ('openedWalls', $wallId)
              as $_fd => $_userId)
          {
            if ($_fd != $fd)
              $server->push ($_fd, $_json);
          }
        }
      }

      if (!empty ($settings->openedWalls))
      {
        foreach ($settings->openedWalls as $_wallId)
          $this->_unsetOpenedWalls ($_wallId, $fd);
      }

      $this->_server->db->clients->del ($fd);

      // Close all current user sessions if any.
      if (!$client->final)
      {
        $json = json_encode (['action' => 'exitsession']);

        foreach ($this->_server->db->lGet('usersUnique', $userId) as $_fd)
        {
          if ($_fd != $fd &&
              ($_client = $this->_server->db->tGet ('clients', $_fd)))
          {
            $_client->final = 1;
            $this->_server->db->tSet ('clients', $_fd, $_client);
            $server->push ($_fd, $json);
          }
        }
      }

      $this->_server->db->lDel ('usersUnique', $userId, $fd);

      $this->_log ($fd, 'info',
        "CLOSE (".$this->_server->db->clients->count().
        " connected clients)", $client->ip);
    }
  }

/*
  public function onManagerStart (SwooleServer $server):void
  {
    // Maintain WS connection with clients.
    $server->tick (30000, function () use ($server)
      {
        foreach ($server->connections as $fd)
        {
          if ($server->isEstablished($fd))
            $server->push ($fd, 'ping', WEBSOCKET_OPCODE_PING);
        }
      });
  }
*/

  private function _createClient (Request $req):array
  {
    $ret = null;
    $User = new User ();
    $ip = $req->header['x-forwarded-for']??'127.0.0.1';
    $token = $req->get['token']??null;

    if ( ($token = $req->get['token']??null) &&
         ($r = $User->loadByToken ($token, $ip)) )
      $ret = (object) [
        'ip' => $ip,
        'sessionId' => $req->fd,
        'id' => $r['users_id'],
        'username' => $r['username'],
        'slocale' => Helper::getsLocale ($User),
        'settings' => $User->getSettings(),
        'openedChats' => '',
        'final' => 0
      ];

    return [$ret, $ip];
  }

  private function _injectChatUsersData (int $wallId, array &$ret):void
  {
    $ret['userslist'] = [];

    if (!$this->_server->db->isEmpty (
           ($cu = $this->_server->db->jGet ('chatUsers', $wallId)) ))
    {
      $dbl = [];

      // If a user has more than one session opened, count only one.
      foreach ($cu as $_fd => $_user)
      {
        if (!isset ($dbl[$_user->id]))
          $ret['userslist'][] = (array)$_user;
        $dbl[$_user->id] = true;
      }

      $ret['userscount'] = count ($ret['userslist']) - 1;
    }
    else
      $ret['userscount'] = 0;
  }

  private function _registerActiveWall (int $fd, int $userId,
                                        ?object $oldSettings,
                                        object $newSettings):void
  {
    $oldWallId = null;

    if ($oldSettings)
    {
      // Deassociate previous wall from user
      if ( ($oldWallId = $oldSettings->activeWall??null) )
      {
        $this->_unsetItem ('activeWalls', $oldWallId, $fd);
        $this->_unsetActiveWallsUnique ($oldWallId, $userId);
      }
    }

    // Associate new wall to user
    if ( ($newWallId = $newSettings->activeWall??null) )
    {
      $aw = $this->_server->db->jGet ('activeWalls', $newWallId);
      $awu = $this->_server->db->jGet ('activeWallsUnique', $newWallId);

      $aw->$fd = $userId;
      $awu->$userId = $fd;

      $this->_server->db->jSet ('activeWalls', $newWallId, $aw);
      $this->_server->db->jSet ('activeWallsUnique', $newWallId, $awu);
    }

    if ($oldWallId || $newWallId)
    {
      $args = [];

      if ($oldWallId)
        $args[] = $oldWallId;
      if ($newWallId)
        $args[] = $newWallId;

      $this->_pushWallsUsersCount ($args);
    }
  }

  private function _registerOpenedWalls (int $fd, int $userId,
                                         ?object $oldSettings,
                                         object $newSettings):void
  {
    $haveOld = ($oldSettings && !empty ($oldSettings->openedWalls));

    if ($haveOld)
    {
      foreach ($oldSettings->openedWalls as $_oldWallId)
        $this->_unsetOpenedWalls ($_oldWallId, $fd);

      foreach (
        array_diff ($oldSettings->openedWalls, $newSettings->openedWalls??[])
          as $_wallId)
      {
        $this->_unsetItem ('chatUsers', $_wallId, $fd);
      }
    }

    // Associate new wall to user
    if (!empty ($newSettings->openedWalls))
    {
      foreach ($newSettings->openedWalls as $_newWallId)
      {
        $_ow = $this->_server->db->jGet ('openedWalls', $_newWallId);
        $_ow->$fd = $userId;
        $this->_server->db->jSet ('openedWalls', $_newWallId, $_ow);
      }
    }

    if ($haveOld)
    {
      foreach (
        array_diff ($oldSettings->openedWalls, $newSettings->openedWalls??[])
          as $_wallId)
      {
        $this->_unsetItem ('chatUsers', $_wallId, $fd);

        foreach (
          $this->_server->db->jGet ('openedWalls', $_wallId)
            as $_fd => $_userId)
        {
          $_count =
            count ((array)$this->_server->db->jGet ('chatUsers', $_wallId));

          if ($_count)
            $this->_server->push ($fd,
              json_encode ([
                'action' => 'chatcount',
                'count' => $_count - 1,
                'wall' => ['id' => $_wallId]
              ]));
        }
      }
    }
  }

  private function _removeUserFromGroup (array $args):array
  {
    $Group = $args['obj'];
    $userId = $args['userId'];
    $wallIds = $args['wallIds'];

    $ret = $Group->removeUser (['userId' => $userId]);

    if (isset ($ret['wall']))
    {
      $toSend = $ret;
      $toSend['action'] = 'unlinked';

      foreach ($wallIds as $_wallId)
      {
        $toSend['wall']['id'] = $_wallId;
        $json = json_encode ($toSend);

        foreach (
          $this->_server->db->jGet ('openedWalls', $_wallId)
            as $_fd => $_userId)
        {
          if ($_userId == $userId)
          {
            $this->_server->push ($_fd, $json);
            break;
          }
        }
      }

      $ret = [];
    }

    return $ret;
  }

  private function _unsetItem (string $key, int $wallId, int $fd):void
  {
    $items = $this->_server->db->jGet ($key, $wallId);

    if (isset ($items->$fd))
    {
      unset ($items->$fd);
      $this->_server->db->jSet ($key, $wallId, $items);
    }
  }

  private function _unsetActiveWallsUnique (int $wallId, int $userId):void
  {
    $awu = $this->_server->db->jGet ('activeWallsUnique', $wallId);
    if (isset ($awu->$userId))
    {
      $remove = true;

      foreach (
        $this->_server->db->jGet ('activeWalls', $wallId) as $_fd => $_userId)
      {
        if ($_userId == $userId)
        {
          $remove = false;
          break;
        }
      }

      if ($remove)
      {
        unset ($awu->$userId);
        $this->_server->db->jSet ('activeWallsUnique', $wallId, $awu);
      }
    }
  }

  private function _unsetOpenedWalls (int $wallId, int $fd):void
  {
    $ow = $this->_server->db->jGet ('openedWalls', $wallId);

    if (isset ($ow->$fd))
    {
      unset ($ow->$fd);
      $this->_server->db->jSet ('openedWalls', $wallId, $ow);
    }
  }

  private function _pushWallsUsersCount (array $diff):void
  {
    foreach ($diff as $_wallId)
    {
      $json = json_encode ([
        'action' => 'viewcount',
        'count' => count (
          (array)$this->_server->db->jGet ('activeWallsUnique', $_wallId)) - 1,
        'wall' => ['id' => $_wallId]
      ]);

      foreach (
        $this->_server->db->jGet ('activeWalls', $_wallId)
          as $_fd => $_userId)
      {
        $this->_server->push ($_fd, $json);
      }
    }
  }

  private function _injectUserSpecificData (array $ret, int $postitId,
                                            object $client):array
  {
    $ret['wall']['postit']['alertshift'] =
      (new Postit (['postitId' => $postitId], $client))->getPostitAlertShift ();

    return $ret;
  }

  private function _ping ():void
  {
    // Keep database connection alive.
    (new Base())->ping ();

    // Keep Task server connection alive.
    (new Task())->execute (['event' => Task::EVENT_TYPE_DUM]);
  }

  private function _log (int $fd, string $type, string $msg,
                         string $ip = null):void
  {
    error_log (sprintf("%s [%s][%s:%s] %s",
      date('Y-m-d H:i:s'), strtoupper ($type), $ip, $fd, $msg));
  }

  //<WPTPROD-remove>
  private function _debug ($data):void
  {
    error_log (print_r ($data, true));
  }
  //</WPTPROD-remove>
}
