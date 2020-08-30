<?php

namespace Wopits\WebSocket;

require_once (__DIR__.'/../../config.php');

use Swoole\WebSocket\Server;
use Swoole\Http\Request;
use Swoole\WebSocket\Frame;

use Wopits\Helper;
use Wopits\Base;
use Wopits\User;
use Wopits\Wall;
use Wopits\Wall\Postit;
use Wopits\Wall\Group;
use Wopits\Wall\EditQueue;

class ServerClass
{
  private $server;
  private $cache;
  private $ip;

  public function __construct (Server $server)
  {
    $this->server = $server;

    $this->cache = new \EasySwoole\Redis\Redis (
      new \EasySwoole\Redis\Config\RedisConfig([
        'host' => '127.0.0.1',
        'port' => '6379',
        'serialize' => \EasySwoole\Redis\Config\RedisConfig::SERIALIZE_PHP]));
  }

  public function onOpen (Request $req)
  {
    $fd = $req->fd;

    // Internal wopits client
    if (empty ($req->header['x-forwarded-server']))
    {
      $this->cache->hSet ('internals', $fd, 1);
    }
    else
    {
      // Common wopits client
      list ($client, $ip) = $this->_createClient ($req);

      if ($client)
      {
        $userId = $client->id;
        $settings = $client->settings;

        $this->cache->hSet ('clients', $fd, $client);
  
        $this->_log ($fd, 'info',
          "OPEN (".$this->cache->hLen('clients').
          " connected clients)", $client->ip);

        // Register user opened walls
        $this->_registerOpenedWalls ($fd, $userId, null, $settings);

        // Register user active wall
        $this->_registerActiveWall ($fd, $userId, null, $settings);
  
        if (isset ($settings->activeWall))
          $this->_pushWallsUsersCount ([$settings->activeWall]);

        $usersUnique = $this->cache->hGet('usersUnique', $userId)??[];
        $usersUnique[] = $fd;
        $this->cache->hSet ('usersUnique', $userId, $usersUnique);
      }
      else
      {
        $this->_log ($fd, 'error', "UNAUTHORIZED login attempt!", $ip);

        //FIXME
        $this->server->push ($fd, json_encode (['action' => 'exitsession']));
        $this->server->disconnect ($fd);
      }
    }
  }

  public function onMessage (int $fd, $fdata)
  {
    $msg = json_decode ($fdata);

    // Common wopits client
    if (!$this->cache->hGet ('internals', $fd))
    {
      $data = ($msg->data) ? json_decode (urldecode ($msg->data)) : null;
      $wallId = null;
      $wallsIds = null;
      $postitId = null;
      $push = false;
      $action = '';
      $ret = [];
  
      $client = $this->cache->hGet ('clients', $fd);

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

          $chatUsers = $this->cache->hGet('chatUsers', $wallId)??[];

          switch ($msg->method)
          {
            case 'PUT':
              $chatUsers[$fd] = [
                'id' => $client->id,
                'name' => $client->username
              ];

              $client->openedChats[$wallId] = 1;
              $this->cache->hSet ('clients', $fd, $client); 
              $this->cache->hSet ('chatUsers', $wallId, $chatUsers);

              $ret['msg'] = '_JOIN_';
              break;

            case 'DELETE':
              // Handle some random case (user close its browser...)
              if (!empty ($chatUsers))
              {
                $this->_unsetItem ('chatUsers', $wallId, $fd);

                unset ($client->openedChats[$wallId]);
                $this->cache->hSet ('clients', $fd, $client); 

                $ret['msg'] = '_LEAVE_';
              }
              break;
          }

          $ret['userslist'] = [];
          $chatUsers = $this->cache->hGet ('chatUsers', $wallId);

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

          $this->cache->hSet ('clients', $fd, $client); 

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
          foreach ($this->cache->hGet ('usersUnique', $User->userId) as $_fd)
          {
            $_client = $this->cache->hGet ('clients', $_fd);

            if ($_client && $_fd != $fd)
            {
              $toSend = ['action' => 'reloadsession'];
              if (isset ($newSettings->locale))
                $toSend['locale'] = $newSettings->locale;

              $_client->final = true;
              $this->cache->hSet ('clients', $_fd, $_client);

              $this->server->push ($_fd, json_encode ($toSend));
            }
          }
        }
        else
        {
          $client->final = false;
          $this->cache->hSet ('clients', $fd, $client);
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
            array_keys ($this->cache->hGet ('activeWallsUnique', $wallId)));
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
        $this->_ping ();
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
        $clients = $this->cache->hGetAll ('openedWalls');

        foreach ($wallsIds as $_wallId)
          if (isset ($clients[$_wallId]))
            foreach ($clients[$_wallId] as $_fd => $_userId)
              if ($_userId != $userId)
                $this->server->push ($_fd, json_encode ([
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
          $clients = $this->cache->hGetAll (
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
                    $this->server->push ($_fd,
                      ($postitId) ?
                        // If postit update, send user's specific data too.
                        json_encode ($this->_injectUserSpecificData (
                                       $ret, $postitId, $client)) : $json);
                }
                else
                  $this->server->push ($_fd, $json);
              }
            }
          }
        }
      }

      // Respond to the sender.
      $ret['msgId'] = $msg->msgId ?? null;
      $this->server->push ($fd, json_encode ($ret));
    }
    // Internal wopits client (broadcast msg to all clients)
    else
    {
      switch ($msg->action)
      {
        case 'ping':

          $this->_ping ();

          break;
    
        case 'dump-all':

          $this->server->push ($fd,
            "* activeWalls array:\n".
            print_r ($this->cache->hGetAll('activeWalls'), true)."\n".
            "* openedWalls array:\n".
            print_r ($this->cache->hGetAll('openedWalls'), true)."\n".
            "* chatUsers array:\n".
            print_r ($this->cache->hGetAll('chatUsers'), true)."\n"

          );

          break;

        case 'stat-users':

          $this->server->push ($fd, ("\n".
            '* Sessions: '.$this->cache->hLen('clients')."\n".
            '* Active walls: '.$this->cache->hLen('activeWalls')."\n".
            '* Opened walls: '.$this->cache->hLen('openedWalls')."\n".
            '* Current chats: '.$this->cache->hlen('chatUsers')."\n"
          ));

          break;

        //FIXME TODO If a user has something being edited, wait for him to
        //           finish.
        case 'reload':
        case 'mainupgrade':

          $clients = $this->cache->hGetAll ('clients');

          // Purge Redis data.
          $this->cache->flushDb ();

          // Purge SQL editing queue.
          (new EditQueue())->purge ();

          foreach ($clients as $_fd => $_client)
            $this->server->push ($_fd, json_encode ($msg));

          break;

        default:
          $this->_log ($fd, 'error', "Unknown internal action {$msg->action}");
      }
    }
  }

  public function onClose (int $fd)
  {
    $internals = $this->cache->hGet ('internals', $fd);

    // Internal wopits client
    if ($internals)
    {
      unset ($internals[$fd]);
      $this->cache->hSet ('internals', $fd, $internals);
    }
    // Common wopits client
    elseif ( ($client = $this->cache->hGet ('clients', $fd)) )
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
      }

      if (isset ($client->settings->openedWalls))
        foreach ($client->settings->openedWalls as $_wallId)
          $this->_unsetOpenedWalls ($_wallId, $fd);

      foreach ($client->openedChats as $_wallId => $_dum)
      {
        $_chatUsers = $this->cache->hGet ('chatUsers', $_wallId);
        unset ($_chatUsers[$fd]);
        $this->cache->hSet ('chatUsers', $_wallId, $_chatUsers);
      }

      $this->cache->hDel ('clients', $fd);

      // Close all current user sessions if any.
      if (!$client->final)
      {
        $json = json_encode (['action' => 'exitsession']);

        foreach ($this->cache->hGet ('usersUnique', $userId) as $_fd)
        {
          if ($_fd != $fd && ($_client = $this->cache->hGet ('clients', $_fd)))
          {
            $_client->final = true;
            $this->cache->hSet ('clients', $_fd, $_client);

            $this->server->push ($_fd, $json);
          }
        }
      }

      $usersUnique = $this->cache->hGet ('usersUnique', $userId);
      unset ($usersUnique[array_search ($fd, $usersUnique)]);

      if (empty ($usersUnique))
        $this->cache->hDel ('usersUnique', $userId);
      else
        $this->cache->hSet ('usersUnique', $userId, $usersUnique);

      $this->_log ($fd, 'info',
        "CLOSE (".$this->cache->hLen('clients').
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
      $activeWalls = $this->cache->hGet ('activeWalls', $newWallId);

      if (!$activeWalls)
      {
        $activeWalls = [];
        $activeWallsUnique = [];
      }
      else
        $activeWallsUnique =
          $this->cache->hGet ('activeWallsUnique', $newWallId);

      $activeWalls[$fd] = $userId;
      $activeWallsUnique[$userId] = $fd;

      $this->cache->hSet ('activeWalls', $newWallId, $activeWalls);
      $this->cache->hSet ('activeWallsUnique', $newWallId, $activeWallsUnique);
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

      foreach ($diff = array_diff ($oldSettings->openedWalls,
                                   $newSettings->openedWalls??[]) as $_wallId)
        $this->_unsetItem ('chatUsers', $_wallId, $fd);
    }

    // Associate new wall to user
    if (isset ($newSettings->openedWalls))
    {
      foreach ($newSettings->openedWalls as $_newWallId)
      {
        $_openedWalls = $this->cache->hGet('openedWalls', $_newWallId)??[];
        $_openedWalls[$fd] = $userId;
        $this->cache->hSet ('openedWalls', $_newWallId, $_openedWalls);
      }
    }

    if ($haveOld)
    {
      foreach ($diff = array_diff ($oldSettings->openedWalls,
                                   $newSettings->openedWalls??[]) as $_wallId)
      {
        $this->_unsetItem ('chatUsers', $_wallId, $fd);

        $_openedWalls = $this->cache->hGet ('openedWalls', $_wallId);
        if ($_openedWalls)
        {
          foreach ($_openedWalls as $_fd => $_userId)
          {
            $_chatUsers = $this->cache->hGet ('chatUsers', $_wallId);

            if ($_chatUsers && $this->cache->hExists ('clients', $_fd))
              $this->server->push ($fd,
                json_encode ([
                  'action' => 'chatcount',
                  'count' => count ($_chatUsers) - 1,
                  'wall' => ['id' => $_wallId]
                ]));
          }
        }
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
        $_openedWalls = $this->cache->hGet ('openedWalls', $_wallId);
        if ($_openedWalls)
        {
          $toSend['wall']['id'] = $_wallId;
          $json = json_encode ($toSend);

          foreach ($_openedWalls as $_fd => $_userId)
          {
            if ($_userId == $userId)
            {
              $this->server->push ($_fd, $json);
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
    $items = $this->cache->hGet ($key, $wallId);

    if (isset ($items[$fd]))
    {
      unset ($items[$fd]);

      if (empty ($items))
        $this->cache->hDel ($key, $wallId);
      else
        $this->cache->hSet ($key, $wallId, $items);
    }
  }

  private function _unsetActiveWallsUnique ($wallId, $userId)
  {
    $activeWallsUnique = $this->cache->hGet ('activeWallsUnique', $wallId);

    if (isset ($activeWallsUnique[$userId]))
    {
      $remove = true;

      $activeWalls = $this->cache->hGet ('activeWalls', $wallId);

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
          $this->cache->hDel ('activeWallsUnique', $wallId);
        else
          $this->cache->hSet ('activeWallsUnique', $wallId, $activeWallsUnique);
      }
    }
  }

  private function _unsetOpenedWalls ($wallId, $fd)
  {
    $openedWalls = $this->cache->hGet ('openedWalls', $wallId);

    if (isset ($openedWalls[$fd]))
    {
      unset ($openedWalls[$fd]);

      if (empty ($openedWalls))
        $this->cache->hDel ('openedWalls', $wallId);
      else
        $this->cache->hSet ('openedWalls', $wallId, $openedWalls);
    }
  }

  private function _pushWallsUsersCount ($diff)
  {
    foreach ($diff as $_wallId)
    {
      if ($this->cache->hExists ('activeWalls', $_wallId))
      {
        $usersCount = count ($this->cache->hGet('activeWallsUnique', $_wallId));
        $json = json_encode ([
          'action' => 'viewcount',
          'count' => $usersCount - 1,
          'wall' => ['id' => $_wallId]
        ]);

        foreach ($this->cache->hGet ('activeWalls', $_wallId)
                   as $_fd => $_userId)
        {
          if ($this->cache->hExists ('clients', $_fd))
            $this->server->push ($_fd, $json);
        }
      }
    }
  }

  private function _injectUserSpecificData ($ret, $postitId, $client)
  {
    $ret['wall']['postit']['alertshift'] =
      (new Postit (['postitId' => $postitId], $client))->getPostitAlertShift ();

    return $ret;
  }

  private function _ping ()
  {
    (new Base ())->ping ();
  }

  private function _log (int $fd, $type, $msg, $ip = null)
  {
    printf ("%s:%s [%s][%s] %s\n",
      $ip,
      $fd,
      date('Y-m-d H:i:s'),
      strtoupper ($type),
      $msg);
  }

  //<WPTPROD-remove>
  private function _debug ($data)
  {
    error_log (print_r ($data, true));
  }
  //</WPTPROD-remove>
}
