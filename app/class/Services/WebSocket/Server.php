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
  private $_cache;
  private $_internals = [];

  public function __construct ()
  {
    $this->_server = new SwooleServer ('127.0.0.1', WPT_WS_PORT);

    $this->_cache = new \EasySwoole\Redis\Redis (
      new \EasySwoole\Redis\Config\RedisConfig([
        'host' => '127.0.0.1',
        'port' => '6379',
        'serialize' => \EasySwoole\Redis\Config\RedisConfig::SERIALIZE_PHP]));

    // Attach events.
    foreach (['start', 'open', 'message', 'close' ] as $e)
      $this->_server->on ($e, [$this, "on$e"]);
  }

  public function start ()
  {
    $this->_server->start ();
  }

  public function onStart (SwooleServer $server)
  {
    error_log (
      "[INFO][internal] wopits WebSocket server is listening on port ".
      WPT_WS_PORT);
  }

  public function onOpen (SwooleServer $server, Request $req)
  {
    $fd = $req->fd;

    // Internal wopits client
    if (empty ($req->header['x-forwarded-server']))
    {
      $this->_internals[$fd] = 1;
    }
    else
    {
      // Common wopits client
      list ($client, $ip) = $this->_createClient ($req);

      if ($client)
      {
        $userId = $client->id;
        $settings = $client->settings;

        $this->_cache->hSet ('clients', $fd, $client);
  
        $this->_log ($fd, 'info',
          "OPEN (".$this->_cache->hLen('clients').
          " connected clients)", $client->ip);

        // Register user opened walls
        $this->_registerOpenedWalls ($fd, $userId, null, $settings);

        // Register user active wall
        $this->_registerActiveWall ($fd, $userId, null, $settings);
  
        if (isset ($settings->activeWall))
          $this->_pushWallsUsersCount ([$settings->activeWall]);

        $usersUnique = $this->_cache->hGet('usersUnique', $userId)??[];
        $usersUnique[] = $fd;
        $this->_cache->hSet ('usersUnique', $userId, $usersUnique);
      }
      else
      {
        $this->_log ($fd, 'error', "UNAUTHORIZED login attempt!", $ip);

        //FIXME
        $server->push ($fd, json_encode (['action' => 'exitsession']));
        $server->disconnect ($fd);
      }
    }
  }

  public function onMessage (SwooleServer $server, Frame $frame)
  {
    $fd = $frame->fd;
    $msg = json_decode ($frame->data);

    // Common wopits client
    if (!isset ($this->_internals[$fd]))
    {
      $data = ($msg->data) ? json_decode (urldecode ($msg->data)) : null;
      $wallId = null;
      $wallsIds = null;
      $postitId = null;
      $push = false;
      $action = '';
      $ret = [];
  
      $client = $this->_cache->hGet ('clients', $fd);

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

          $chatUsers = $this->_cache->hGet('chatUsers', $wallId)??[];

          switch ($msg->method)
          {
            case 'PUT':
              $chatUsers[$fd] = [
                'id' => $client->id,
                'name' => $client->username
              ];

              $client->openedChats[$wallId] = 1;
              $this->_cache->hSet ('clients', $fd, $client);
              $this->_cache->hSet ('chatUsers', $wallId, $chatUsers);

              $ret['msg'] = '_JOIN_';
              break;

            case 'DELETE':
              // Handle some random case (user close its browser...)
              if (!empty ($chatUsers))
              {
                $this->_unsetItem ('chatUsers', $wallId, $fd);

                unset ($client->openedChats[$wallId]);
                $this->_cache->hSet ('clients', $fd, $client);

                $ret['msg'] = '_LEAVE_';
              }
              break;
          }

          $this->_injectChatUsersData (
            $this->_cache->hGet ('chatUsers', $wallId), $ret);
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
          $oldSettings = json_decode ($User->getSettings()??'{}');
          $newSettings = json_decode ($data->settings);
  
          // Active wall
          $this->_registerActiveWall (
            $fd, $User->userId, $oldSettings, $newSettings);
          $client->settings->activeWall = $newSettings->activeWall??null;
  
          // Opened walls
          $this->_registerOpenedWalls (
            $fd, $User->userId, $oldSettings, $newSettings);
          $client->settings->openedWalls = $newSettings->openedWalls??[];

          $this->_cache->hSet ('clients', $fd, $client);

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
          foreach ($this->_cache->hGet ('usersUnique', $User->userId) as $_fd)
            if ($_fd != $fd)
            {
              $_client = $this->_cache->hGet ('clients', $_fd);

              $toSend = ['action' => 'reloadsession'];
              if (isset ($newSettings->locale))
                $toSend['locale'] = $newSettings->locale;

              $_client->final = true;
              $this->_cache->hSet ('clients', $_fd, $_client);

              $server->push ($_fd, json_encode ($toSend));
            }
        }
        else
        {
          $client->final = false;
          $this->_cache->hSet ('clients', $fd, $client);
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

        $Wall = new Wall (['wallId' => $wallId], $client);

        if ($msg->method == 'GET')
          $ret = $Wall->getUsersview (
            array_keys ($this->_cache->hGet ('activeWallsUnique', $wallId)));
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
      // ROUTE ping
      // Keep WS connection and database persistent connection alive
      elseif ($msg->route == 'ping')
      {
        $this->_ping (false);
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
        $clients = $this->_cache->hGetAll ('openedWalls');

        foreach ($wallsIds as $_wallId)
          if (isset ($clients[$_wallId]))
            foreach ($clients[$_wallId] as $_fd => $_userId)
              if ($_userId != $userId)
                $server->push ($_fd, json_encode ([
                  'action' => 'unlinked',
                  'wall' => ['id' => $_wallId]
                ]));
      }
      else
      {
        $ret['action'] = $action;

        // Boadcast results if needed
        if ($push)
        {
          $clients = $this->_cache->hGetAll (
            ($action == 'chat' || $action == 'unlinked') ?
              'openedWalls' : 'activeWalls');

          if (isset ($clients[$wallId]))
          {
            $userId = $client->id;
            $json = json_encode ($ret);

            foreach ($clients[$wallId] as $_fd => $_userId)
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
      $ret['msgId'] = $msg->msgId ?? null;
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

          foreach ($this->_cache->hGetAll('openedWalls') as $_wallId => $_item)
            if ($_wallId == in_array ($_wallId, $msg->ids))
              foreach ($_item as $_fd => $_userId)
                if ($_userId != $msg->userId)
                  $server->push ($_fd,
                    json_encode ([
                      'action' => 'unlinked',
                      'wall' => ['id' => $_wallId]
                    ]));

          break;
    
        // reload & mainupgrade
        //FIXME TODO If a user has something being edited, wait for him to
        //           finish.
        case 'reload':
        case 'mainupgrade':

          $clients = $this->_cache->hGetAll ('clients');

          // Purge Redis data.
          $this->_cache->flushDb ();

          // Purge SQL editing queue.
          (new EditQueue())->purge ();

          foreach ($clients as $_fd => $_client)
            $server->push ($_fd, json_encode ($msg));

          break;

        // dump-all
        case 'dump-all':

          $server->push ($fd,
            "* activeWalls array:\n".
            print_r ($this->_cache->hGetAll('activeWalls'), true)."\n".
            "* openedWalls array:\n".
            print_r ($this->_cache->hGetAll('openedWalls'), true)."\n".
            "* chatUsers array:\n".
            print_r ($this->_cache->hGetAll('chatUsers'), true)."\n"

          );

          break;

        // stat-users
        case 'stat-users':

          $server->push ($fd, ("\n".
            '* Sessions: '.$this->_cache->hLen('clients')."\n".
            '* Unique users: '.$this->_cache->hLen('usersUnique')."\n".
            "----------\n".
            '* Opened walls: '.$this->_cache->hLen('openedWalls')."\n".
            '* Active walls: '.$this->_cache->hLen('activeWalls')."\n".
            '* Unique active walls: '.$this->_cache->hLen('activeWallsUnique')."\n".
            "----------\n".
            '* Current chats: '.$this->_cache->hLen('chatUsers')."\n"
          ));

          break;

        default:
          $this->_log ($fd, 'error',
                       "Unknown action `{$msg->action}`", 'internal');
      }
    }
  }

  public function onClose (SwooleServer $server, int $fd)
  {
    // Internal wopits client
    if (isset ($this->_internals[$fd]))
    {
      unset ($this->_internals[$fd]);
    }
    // Common wopits client
    elseif ( ($client = $this->_cache->hGet ('clients', $fd)) )
    {
      $userId = $client->id;

      if (isset ($client->settings->activeWall))
      {
        $wallId = $client->settings->activeWall;

        // Remove user's action from DB edit queue
        (new EditQueue([], $client))->removeUser ();

        // Remove user from local view queue
        $this->_unsetItem ('activeWalls', $wallId, $fd); 
        $this->_unsetActiveWallsUnique ($wallId, $userId);

        $this->_pushWallsUsersCount ([$wallId]);

        // Leave wall's chat if needed.
        if (isset (($this->_cache->hGet('chatUsers', $wallId)??[])[$fd]))
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

          $this->_injectChatUsersData (
            $this->_cache->hGet('chatUsers', $wallId)??[], $_ret);
          $_json = json_encode ($_ret);

          foreach ($this->_cache->hGetAll('openedWalls')[$wallId]
                     as $_fd => $_userId)
            if ($_fd != $fd)
              $server->push ($_fd, $_json);
        }
      }

      if (isset ($client->settings->openedWalls))
        foreach ($client->settings->openedWalls as $_wallId)
          $this->_unsetOpenedWalls ($_wallId, $fd);

      $this->_cache->hDel ('clients', $fd);

      // Close all current user sessions if any.
      if (!$client->final)
      {
        $json = json_encode (['action' => 'exitsession']);

        foreach ($this->_cache->hGet ('usersUnique', $userId) as $_fd)
          if ($_fd != $fd &&
              $server->exist ($_fd) &&
              ($_client = $this->_cache->hGet ('clients', $_fd)) )
          {
            $_client->final = true;
            $this->_cache->hSet ('clients', $_fd, $_client);

            $server->push ($_fd, $json);
          }
      }

      $usersUnique = $this->_cache->hGet ('usersUnique', $userId);
      unset ($usersUnique[array_search ($fd, $usersUnique)]);

      if (empty ($usersUnique))
        $this->_cache->hDel ('usersUnique', $userId);
      else
        $this->_cache->hSet ('usersUnique', $userId, $usersUnique);

      $this->_log ($fd, 'info',
        "CLOSE (".$this->_cache->hLen('clients').
        " connected clients)", $client->ip);
    }
  }

  private function _createClient (Request $req)
  {
    $ret = null;
    $User = new User ();
    $ip = $req->header['x-forwarded-for']??'127.0.0.1';

    if ( ($r = $User->loadByToken ($req->get['token'], $ip)) )
      $ret = (object) [
        'ip' => $ip,
        'sessionId' => $req->fd,
        'id' => $r['users_id'],
        'username' => $r['username'],
        'slocale' => Helper::getsLocale ($User),
        'settings' => json_decode ($User->getSettings()??'{}'),
        'openedChats' => [],
        'final' => false
      ];

    return [$ret, $ip];
  }

  private function _injectChatUsersData ($chatUsers, &$ret)
  {
    $ret['userslist'] = [];

    if ($chatUsers)
    {
      $dbl = [];

      // If a user has more than one session opened, count only one.
      foreach ($chatUsers as $_wallId => $_fd)
      {
        if (!isset ($dbl[$_fd['id']]))
          $ret['userslist'][] = $_fd;
        $dbl[$_fd['id']] = true;
      }

      $ret['userscount'] = count ($ret['userslist']) - 1;
    }
    else
      $ret['userscount'] = 0;
  }

  private function _registerActiveWall ($fd, $userId,
                                        $oldSettings, $newSettings)
  {
    if ($oldSettings)
    {
      // Deassociate previous wall from user
      if ( ($oldWallId = $oldSettings->activeWall ?? null) )
      {
        $this->_unsetItem ('activeWalls', $oldWallId, $fd);
        $this->_unsetActiveWallsUnique ($oldWallId, $userId);
      }
    }

    // Associate new wall to user
    if ( ($newWallId = $newSettings->activeWall ?? null) )
    {
      $activeWalls = $this->_cache->hGet ('activeWalls', $newWallId);

      if (!$activeWalls)
      {
        $activeWalls = [];
        $activeWallsUnique = [];
      }
      else
        $activeWallsUnique =
          $this->_cache->hGet ('activeWallsUnique', $newWallId);

      $activeWalls[$fd] = $userId;
      $activeWallsUnique[$userId] = $fd;

      $this->_cache->hSet ('activeWalls', $newWallId, $activeWalls);
      $this->_cache->hSet ('activeWallsUnique', $newWallId, $activeWallsUnique);
    }

    $this->_pushWallsUsersCount ((!$oldSettings) ?
      [$newWallId] : [$oldWallId, $newWallId]);
  }

  private function _registerOpenedWalls ($fd, $userId,
                                         $oldSettings, $newSettings)
  {
    $haveOld = ($oldSettings && isset ($oldSettings->openedWalls));

    if ($haveOld)
    {
      foreach ($oldSettings->openedWalls as $_oldWallId)
        $this->_unsetOpenedWalls ($_oldWallId, $fd);

      foreach (array_diff ($oldSettings->openedWalls,
                           $newSettings->openedWalls??[]) as $_wallId)
        $this->_unsetItem ('chatUsers', $_wallId, $fd);
    }

    // Associate new wall to user
    if (isset ($newSettings->openedWalls))
    {
      foreach ($newSettings->openedWalls as $_newWallId)
      {
        $_openedWalls = $this->_cache->hGet('openedWalls', $_newWallId)??[];
        $_openedWalls[$fd] = $userId;
        $this->_cache->hSet ('openedWalls', $_newWallId, $_openedWalls);
      }
    }

    if ($haveOld)
    {
      foreach (array_diff ($oldSettings->openedWalls,
                           $newSettings->openedWalls??[]) as $_wallId)
      {
        $this->_unsetItem ('chatUsers', $_wallId, $fd);

        if ( ($_openedWalls = $this->_cache->hGet ('openedWalls', $_wallId)) )
          foreach ($_openedWalls as $_fd => $_userId)
            if ( ($_chatUsers = $this->_cache->hGet ('chatUsers', $_wallId)) )
              $this->_server->push ($fd,
                json_encode ([
                  'action' => 'chatcount',
                  'count' => count ($_chatUsers) - 1,
                  'wall' => ['id' => $_wallId]
                ]));
      }
    }
  }

  private function _removeUserFromGroup ($args)
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
        $_openedWalls = $this->_cache->hGet ('openedWalls', $_wallId);
        if ($_openedWalls)
        {
          $toSend['wall']['id'] = $_wallId;
          $json = json_encode ($toSend);

          foreach ($_openedWalls as $_fd => $_userId)
          {
            if ($_userId == $userId)
            {
              $this->_server->push ($_fd, $json);
              break;
            }
          }
        }
      }

      $ret = [];
    }

    return $ret;
  }

  private function _unsetItem ($key, $wallId, $fd)
  {
    $items = $this->_cache->hGet ($key, $wallId);

    if (isset ($items[$fd]))
    {
      unset ($items[$fd]);

      if (empty ($items))
        $this->_cache->hDel ($key, $wallId);
      else
        $this->_cache->hSet ($key, $wallId, $items);
    }
  }

  private function _unsetActiveWallsUnique ($wallId, $userId)
  {
    $activeWallsUnique = $this->_cache->hGet ('activeWallsUnique', $wallId);

    if (isset ($activeWallsUnique[$userId]))
    {
      $remove = true;

      $activeWalls = $this->_cache->hGet ('activeWalls', $wallId);

      if ($activeWalls)
      {
        foreach ($activeWalls as $_fd => $_userId)
        {
          if ($_userId == $userId)
          {
            $remove = false;
            break;
          }
        }
      }

      if ($remove)
      {
        unset ($activeWallsUnique[$userId]);

        if (empty ($activeWallsUnique))
          $this->_cache->hDel ('activeWallsUnique', $wallId);
        else
          $this->_cache->hSet ('activeWallsUnique', $wallId, $activeWallsUnique);
      }
    }
  }

  private function _unsetOpenedWalls ($wallId, $fd)
  {
    $openedWalls = $this->_cache->hGet ('openedWalls', $wallId);

    if (isset ($openedWalls[$fd]))
    {
      unset ($openedWalls[$fd]);

      if (empty ($openedWalls))
        $this->_cache->hDel ('openedWalls', $wallId);
      else
        $this->_cache->hSet ('openedWalls', $wallId, $openedWalls);
    }
  }

  private function _pushWallsUsersCount ($diff)
  {
    foreach ($diff as $_wallId)
      if ( ($activeWalls = $this->_cache->hGet ('activeWalls', $_wallId)) )
      {
        $json = json_encode ([
          'action' => 'viewcount',
          'count' =>
            count ($this->_cache->hGet('activeWallsUnique', $_wallId)) - 1,
          'wall' => ['id' => $_wallId]
        ]);

        foreach ($activeWalls as $_fd => $_userId)
          $this->_server->push ($_fd, $json);
      }
  }

  private function _injectUserSpecificData ($ret, $postitId, $client)
  {
    $ret['wall']['postit']['alertshift'] =
      (new Postit (['postitId' => $postitId], $client))->getPostitAlertShift ();

    return $ret;
  }

  private function _ping ($full = true)
  {
    (new Base())->ping ();

    if ($full)
      (new Task())->execute (['event' => Task::EVENT_TYPE_DUM]);
  }

  private function _log (int $fd, $type, $msg, $ip = null)
  {
    error_log (sprintf("[%s][%s:%s] %s\n",strtoupper ($type), $ip, $fd, $msg));
  }

  //<WPTPROD-remove>
  private function _debug ($data)
  {
    error_log (print_r ($data, true));
  }
  //</WPTPROD-remove>
}
