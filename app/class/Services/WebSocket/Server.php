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

    // Create WebSocket server
    $server = new SwooleServer ('127.0.0.1', WPT_WS_PORT);
    $server->set ([
      'daemonize' => true,
      'log_file' => WPT_LOG_PATH.'/server-ws.log',
      'pid_file' => __DIR__.'/../../../services/run/server-ws.pid',
      'worker_num' => $workerNum,
      'reactor_num' => $workerNum * 2
    ]);

    // Create Swoole session tables
    $server->db = new VolatileTables ();

    // Attach events to WebSocket server
    foreach (['start', 'open', 'message', 'close' ] as $e)
      $server->on ($e, [$this, "on$e"]);

    $this->_server = $server;
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
    $db = $server->db;
    $fd = $req->fd;
    $header = $req->header;

    // If fd does not exist, silently quit.
    if (!$server->isEstablished ($fd))
      return;

    // Internal wopits client
    if (empty ($header['x-forwarded-server']) &&
        strpos ($header['user-agent'], 'PHPWebSocketClient') !== false)
    {
      $db->internals->set ($fd, []);
    }
    else
    {
      // Common wopits client
      list ($client, $ip) = $this->_createClient ($req);

      if ($client)
      {
        $userId = $client->id;
        $settings = json_decode ($client->settings);

        $db->tSet ('clients', $fd, $client);

        // Register user opened walls
        $this->_registerOpenedWalls ($fd, $userId, null, $settings);

        // Register user active wall
        $this->_registerActiveWall ($fd, $userId, null, $settings);
  
        $db->lAdd ('usersUnique', $userId, $fd);

        // Ping WS client to maintain connection and check if it is still alive
        $server->tick (30000, function ($id) use ($server, $fd)
        {
          if (!$server->isEstablished ($fd) || !$server->push ($fd, 'ping'))
          {
            $server->clearTimer ($id);
            $this->onClose ($server, $fd);
          }
        });

        $this->_log ($fd, 'info', 'OPEN', $client->ip);
      }
      else
      {
        if ($server->isEstablished ($fd))
          $server->push ($fd, json_encode (['action' => 'exitsession']));
        else
          $this->_log ($fd, 'warning',
          'UNAUTHORIZED connection attempt!', $ip, (array)$req);

        $server->disconnect ($fd);
      }
    }
  }

  public function onMessage (SwooleServer $server, Frame $frame):void
  {
    $db = $server->db;
    $fd = $frame->fd;
    $msg = json_decode ($frame->data);

    // Common wopits client
    if (!$db->internals->exist ($fd))
    {
      $data = ($msg->data) ? json_decode (urldecode ($msg->data)) : null;
      $wallId = null;
      $wallsIds = null;
      $postitId = null;
      $push = false;
      $action = '';
      $ret = [];

      $client = $db->tGet ('clients', $fd);

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
          $cu = $db->jGet ('chatUsers', $wallId);

          switch ($msg->method)
          {
            case 'PUT':
              $cu->$fd = (object)['id' => $client->id,
                                  'name' => $client->username];
              $db->jSet ('chatUsers', $wallId, $cu);

              $openedChats[] = $wallId;
              $client->openedChats = implode (',', $openedChats);
              $db->tSet ('clients', $fd, $client);

              $ret['msg'] = '_JOIN_';
              break;

            case 'DELETE':
              // Handle some random case (user close its browser...)
              if (!$db->isEmpty ($cu))
              {
                $this->_unsetItem ('chatUsers', $wallId, $fd);

                array_splice ($openedChats,
                  array_search ($wallId, $openedChats), 1);
                $client->openedChats = implode (',', $openedChats);
                $db->tSet ('clients', $fd, $client);

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
          $db->tSet ('clients', $fd, $client);

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

        // Reload all user's sessions
        if (!$client->final)
        {
          foreach ($db->lGet ('usersUnique', $User->userId) as $_fd)
          {
            if ($_fd != $fd && $server->isEstablished ($_fd))
            {
              $_client = $db->tGet ('clients', $_fd);

              $toSend = ['action' => 'reloadsession'];
              if (isset ($newSettings->locale))
                $toSend['locale'] = $newSettings->locale;

              $_client->final = 1;
              $db->tSet ('clients', $_fd, $_client);

              $server->push ($_fd, json_encode ($toSend));
            }
          }
        }
        else
        {
          $client->final = 0;
          $db->tSet ('clients', $fd, $client);
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

        $push = true;

        $EditQueue = new EditQueue ([
          'wallId' => $wallId,
          'data' => $data,
          'item' => $item,
          'itemId' => $itemId
        ], $client);

        $userData = [
          'id' => $client->id,
          'name' => $client->fullname
        ];

        switch ($msg->method)
        {
          // PUT
          case 'PUT':
            $ret = $EditQueue->addTo ();

            if (empty ($ret))
            {
              $action = 'userwriting';
              $ret = [
                'item' => $item,
                'itemId' => $itemId,
                'user' => $userData
              ];
            }
            break;

          // DELETE
          case 'DELETE':
            $ret = $EditQueue->removeFrom ();
            if (!isset ($ret['error_msg']) && !isset ($ret['error']))
            {
              $ret['userstoppedwriting'] = [
                'item' => $item,
                'itemId' => $itemId,
                'user' => $userData
              ];

              if ($data)
              {
                if (empty ($ret['wall']['unlinked']))
                {
                  $action = 'refreshwall';

                  if ($item == 'postit')
                    $postitId = $itemId;
                }
                else
                  $action = 'unlinked';
              }
              else
                $action = 'userstoppedwriting';
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
            array_keys ((array)$db->jGet ('activeWallsUnique', $wallId)));
      }
      // ROUTE Postits color update
      elseif (preg_match ('#^postits/color$#', $msg->route, $m))
      {
        $push = true;
        $action = 'refreshwall';

        // POST
        if ($msg->method == 'POST')
          $ret = (new Postit (['data' => $data], $client))
                   ->updatePostitsColor ();
      }
      // ROUTE Postit creation, postits copy/paste
      elseif (preg_match ('#^wall/(\d+)/cell/(\d+)/(postit|postits)/'.
                          '?(copy|move)?$#', $msg->route, $m))
      {
        @list (,$wallId, $cellId, $item, $type) = $m;

        $push = true;
        $action = 'refreshwall';

        $Postit = new Postit ([
          'wallId' => $wallId,
          'data' => $data,
          'cellId' => $cellId
        ], $client);

        // Create a postit
        if ($item == 'postit')
          $ret = $Postit->create ();
        // Copy/cut postits
        elseif ($item == 'postits')
          $ret = $Postit->copyPostits (($type == 'move'));
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
          foreach ($db->jGet ('openedWalls', $_wallId) as $_fd => $_userId)
            if ($_userId != $userId && $server->isEstablished ($_fd))
              $server->push ($_fd, json_encode ([
                'action' => 'unlinked',
                'wall' => ['id' => $_wallId]
              ]));
      }
      elseif ($wallId || isset ($ret['walls']))
      {
        $ret['action'] = $action;

        // Boadcast results if needed
        if ($push)
        {
          // If SuperAction result from SuperMenu (action on multiple items)
          $isSActionResult = isset ($ret['walls']);
          $cacheName = ($action == 'chat' || $action == 'unlinked' ||
                        $action == 'userwriting' ||
                        $action == 'userstoppedwriting') ?
            'openedWalls' : 'activeWalls';

          if ($isSActionResult)
          {
            $walls = $ret['walls'];
            unset ($ret['walls']);
            $wallsIds = array_keys ($walls);
          }
          else
            $wallsIds = [$wallId];

          foreach ($wallsIds as $_wallId)
          {
            $w = $db->jGet ($cacheName, $_wallId);
            if (!$db->isEmpty ($w))
            {
              $userId = $client->id;

              if ($isSActionResult)
              {
                $ret['wall'] = $walls[$_wallId];
                $ret['wall']['reorganize'] = true;
              }
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
                elseif ($isSActionResult)
                  $server->push ($_fd, $json);
              }
            }
          }
        }
      }

      if ($action == 'userwriting' || $action == 'userstoppedwriting')
        $ret = [];

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

          $walls = $db->openedWalls;
          for ($walls->rewind (); $walls->current (); $walls->next ())
          {
            $_wallId = $walls->key ();

            if (in_array ($_wallId, $msg->ids))
            {
              foreach ($db->jGet ('openedWalls', $_wallId) as $_fd => $_userId)
                if ($_userId != $msg->userId && $server->isEstablished ($_fd))
                  $server->push ($_fd,
                    json_encode ([
                      'action' => 'unlinked',
                      'wall' => ['id' => $_wallId]
                    ]));
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
          $clients = $db->clients;
          for ($clients->rewind (); $clients->current (); $clients->next ())
          {
            $_fd = $clients->key ();

            if ($server->isEstablished ($_fd))
              $server->push ($_fd, $_json);
          }

          break;

        // dump-all
        case 'dump':

          $tmp = '';
          $sections = $msg->section ? [$msg->section] : WS_SERVER_SECTIONS;

          foreach ($sections as $t)
          {
            $tmp .= "\n\e[1;34m$t\e[0m:\n";
            $tb = $db->$t;
            for ($tb->rewind (); $tb->current (); $tb->next ())
            {
              $k = $tb->key ();
              $item = (array)$tb->get ($k);

              $tmp .= "\e[33m$k\e[0m: ";

              if (count ($item) == 1)
                $tmp .= print_r(array_shift ($item), true)."\n";
              else
                $tmp .= "\n".print_r($item, true);
            }
          }

          $server->push ($fd, $tmp);

          break;

        // stat-users
        case 'stat-users':

          $server->push ($fd, ("\n".
            "\e[1;34mSessions\e[0m: ".
              (count($server->connection_list())-1)."\n".
            "\e[1;34mUnique users\e[0m: ".$db->usersUnique->count()."\n".
            "----------\n".
            "\e[1;34mOpened walls\e[0m: ".$db->openedWalls->count()."\n".
            "\e[1;34mActive walls\e[0m: ".$db->activeWalls->count()."\n".
            "\e[1;34mUnique active walls\e[0m: ".
              $db->activeWallsUnique->count()."\n".
            "----------\n".
            "\e[1;34mCurrent chats\e[0m: ".$db->chatUsers->count()."\n"
          ));

          break;

        default:
          $this->_log($fd, 'error', 'Unknown action!', 'internal', (array)$msg);
      }
    }
  }

  public function onClose (SwooleServer $server, int $fd):void
  {
    $db = $server->db;

    // Internal wopits client
    if ($db->internals->exist ($fd))
    {
      $db->internals->del ($fd);
    }
    // Common wopits client
    else
    {
      // Get client infos and delete from cache.
      $client = $db->tGet ('clients', $fd);
      $db->clients->del ($fd);

      // If closure is from a valid wopits client.
      if (!empty ($client->id))
      {
        $userId = $client->id;
        $settings = json_decode ($client->settings);
        $activeWallId = $settings->activeWall??null;

        // Get user's sessions and delete from cache.
        $sessions = $db->lGet ('usersUnique', $userId);
        $db->lDel ('usersUnique', $userId, $fd);

        // If user have at least one wall opened.
        if ($activeWallId)
        {
          // Purge user's actions from edit queue.
          $this->_cleanUserQueue ($client, $activeWallId, $fd);

          // Purge from opened walls queue.
          if (!empty ( ($ow = $settings->openedWalls) ))
            foreach ($ow as $_wallId)
              $this->_unsetOpenedWalls ($_wallId, $fd);

          // Purge from active walls queue.
          $this->_unsetItem ('activeWalls', $activeWallId, $fd);
          $this->_unsetActiveWallsUnique ($activeWallId, $userId);

          // Leave wall's chat.
          if (isset (($db->jGet ('chatUsers', $activeWallId))->$fd))
          {
            $this->_unsetItem ('chatUsers', $activeWallId, $fd);

            $_ret = [
              'method' => 'DELETE',
              'wall' => ['id' => $activeWallId],
              'username' =>$client->username,
              'action' => 'chat',
              'internal' => 1,
              'msg' => '_LEAVE_'
            ];

            $this->_injectChatUsersData ($activeWallId, $_ret);
            $_json = json_encode ($_ret);

            foreach (
              $db->jGet ('openedWalls', $activeWallId) as $_fd => $_userId)
                if ($_fd != $fd && $server->isEstablished ($_fd))
                  $server->push ($_fd, $_json);
          }
        }

        $sessionsCount = count ($sessions);

        // Close all user's sessions.
        if (!$client->final && $sessionsCount > 1)
        {
          $_json = json_encode (['action' => 'exitsession']);

          foreach ($sessions as $_fd)
          {
            if ($_fd != $fd && $server->isEstablished ($_fd) &&
                ($_client = $db->tGet ('clients', $_fd)) &&
                !$db->isEmpty ($_client))
            {
              $_client->final = 1;
              $db->tSet ('clients', $_fd, $_client);

              $server->push ($_fd, $_json);
            }
          }
        }

        // Push new walls users count
        if ($sessionsCount == 1 && $activeWallId)
          $this->_pushWallsUsersCount ([$activeWallId], $fd);

        $this->_log ($fd, 'info', 'CLOSE', $client->ip);
      }
    }
  }

  private function _createClient (Request $req):array
  {
    $ret = null;
    $User = new User ();
    $ip = $req->header['x-forwarded-for']??'127.0.0.1';

    if ( ($token = $req->get['token']??null) &&
         ($r = $User->loadByToken ($token, $ip)) )
      $ret = (object) [
        'ip' => $ip,
        'sessionId' => $req->fd,
        'id' => $r['users_id'],
        'username' => $r['username'],
        'fullname' => $r['fullname'],
        'slocale' => Helper::getsLocale ($User),
        'settings' => $User->getSettings(),
        'openedChats' => '',
        'final' => 0
      ];

    return [$ret, $ip];
  }

  private function _injectChatUsersData (int $wallId, array &$ret):void
  {
    $server = $this->_server;
    $db = $server->db;
    $ret['userslist'] = [];

    $cu = $db->jGet ('chatUsers', $wallId);
    if (!$db->isEmpty ($cu))
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
    $server = $this->_server;
    $db = $server->db;
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
      $aw = $db->jGet ('activeWalls', $newWallId);
      $awu = $db->jGet ('activeWallsUnique', $newWallId);

      $aw->$fd = $userId;
      $awu->$userId = $fd;

      $db->jSet ('activeWalls', $newWallId, $aw);
      $db->jSet ('activeWallsUnique', $newWallId, $awu);
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
    $server = $this->_server;
    $db = $server->db;
    $haveOld = !empty ($oldSettings->openedWalls);

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
        $_ow = $db->jGet ('openedWalls', $_newWallId);
        $_ow->$fd = $userId;
        $db->jSet ('openedWalls', $_newWallId, $_ow);
      }
    }

    if ($haveOld)
    {
      foreach (
        array_diff ($oldSettings->openedWalls, $newSettings->openedWalls??[])
          as $_wallId)
      {
        $this->_unsetItem ('chatUsers', $_wallId, $fd);

        foreach ($db->jGet('openedWalls', $_wallId) as $_fd => $_userId)
        {
          $_count = count ((array)$db->jGet ('chatUsers', $_wallId));

          if ($_count && $server->isEstablished ($fd))
            $server->push ($fd,
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
    $server = $this->_server;
    $db = $server->db;
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

        foreach ($db->jGet('openedWalls', $_wallId) as $_fd => $_userId)
          if ($_userId == $userId && $server->isEstablished ($_fd))
          {
            $server->push ($_fd, $json);
            break;
          }
      }

      $ret = [];
    }

    return $ret;
  }

  private function _unsetItem (string $key, int $wallId, int $fd):void
  {
    $server = $this->_server;
    $db = $server->db;

    $items = $db->jGet ($key, $wallId);
    if (isset ($items->$fd))
    {
      unset ($items->$fd);
      $db->jSet ($key, $wallId, $items);
    }
  }

  private function _unsetActiveWallsUnique (int $wallId, int $userId):void
  {
    $server = $this->_server;
    $db = $server->db;

    $awu = $db->jGet ('activeWallsUnique', $wallId);
    if (isset ($awu->$userId))
    {
      $remove = true;

      foreach ($db->jGet ('activeWalls', $wallId) as $_fd => $_userId)
        if ($_userId == $userId)
        {
          $remove = false;
          break;
        }

      if ($remove)
      {
        unset ($awu->$userId);
        $db->jSet ('activeWallsUnique', $wallId, $awu);
      }
    }
  }

  private function _unsetOpenedWalls (int $wallId, int $fd):void
  {
    $server = $this->_server;
    $db = $server->db;

    $ow = $db->jGet ('openedWalls', $wallId);
    if (isset ($ow->$fd))
    {
      unset ($ow->$fd);
      $db->jSet ('openedWalls', $wallId, $ow);
    }
  }

  private function _cleanUserQueue (object $client, int $activeWallId,
                                    int $fd = null):void
  {
    $server = $this->_server;
    $db = $server->db;

    (new EditQueue([], $client))->removeUser ();

    $_json = json_encode ([
      'action' => 'userstoppedwriting',
      'userstoppedwriting' => ['user' => ['id' => $client->id]]
    ]);

    foreach ($db->jGet ('activeWalls', $activeWallId) as $_fd => $_userId)
      if ($_fd != $fd && $server->isEstablished ($_fd))
        $server->push ($_fd, $_json);
  }

  private function _pushWallsUsersCount (array $diff, int $fd = null):void
  {
    $server = $this->_server;
    $db = $server->db;

    foreach ($diff as $_wallId)
    {
      $_json = json_encode ([
        'action' => 'viewcount',
        'count' => count ((array)$db->jGet ('activeWallsUnique', $_wallId)) - 1,
        'wall' => ['id' => $_wallId]
      ]);

      foreach ($db->jGet ('activeWalls', $_wallId) as $_fd => $_userId)
        if ($_fd != $fd && $server->isEstablished ($_fd))
          $server->push ($_fd, $_json);
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
                         string $ip = null, array $details = null):void
  {
    error_log (sprintf("%s [%s][%s:%s] %s",
      date ('Y-m-d H:i:s'), strtoupper ($type), $ip, $fd, $msg));

    if (WPT_LOG_DETAILS && $details)
      error_log (print_r ($details, true));
  }

  //<WPTPROD-remove>
  private function _debug ($data):void
  {
    error_log (print_r ($data, true));
  }
  //</WPTPROD-remove>
}
