<?php

  require_once (__DIR__.'/../../app/prepend.php');

  use Wopits\{User, Wall};
  use Wopits\Wall\{Group, Postit, Comment, Attachment, Worker};

  // Ajax access point
  //
  // Things with which it is useless to stress WebSocket server with, or that
  // we cannot do in a WebSocket request because either we have to access the
  // PHP session or the WebSocket connection has not yet been
  // created (i.e. login page)

  $ret = [];

  $class = getParam('class');
  $data = json_decode(urldecode(file_get_contents('php://input')));

  switch ($_SERVER['REQUEST_METHOD']) {
    // PUT
    case 'PUT':
      switch ($class) {
        case 'user':
          $User = new User(['data' => $data]);

          switch (getParam('action')) {
            case 'picture':
              $ret = $User->updatePicture();
              break;
            default:
              $ret = $User->create();
          }
          break;
        case 'wall':
          $Wall = new Wall([
            'wallId' => getParam('wallId'),
            'data' => $data,
          ]);

          if (getParam('item') === 'header') {
            $ret = $Wall->addHeaderPicture(['headerId' => getParam('itemId')]);
          } else {
            switch (getParam('action')) {
              case 'import':
                $ret = $Wall->import();
                break;
              case 'clone':
                $ret = $Wall->clone();
                break;
              default:
                $ret = $Wall->createWall();
            }
          }
          break;
        case 'attachment':
          $ret = (new Attachment([
            'wallId' => getParam('wallId'),
            'cellId' => getParam('cellId'),
            'postitId' => getParam('postitId'),
            'data' => $data,
          ]))->add();
          break;
        case 'worker':
          $ret = (new Worker([
            'wallId' => getParam('wallId'),
            'cellId' => getParam('cellId'),
            'postitId' => getParam('postitId'),
            'data' => $data,
          ]))->add();
          break;
        case 'postit':
          if (getParam('item') === 'picture') {
            $ret = (new Postit([
              'wallId' => getParam('wallId'),
              'cellId' => getParam('cellId'),
              'postitId' => getParam('postitId'),
              'data' => $data,
            ]))->addPicture();
          }
          break;
      }
      break;
    // GET
    case 'GET':
      switch ($class) {
        case 'common':
          if (getParam ('item') === 'timezones') {
            $ret = timezone_identifiers_list ();
          }
          break;
        case 'postit':
          if (getParam('item') === 'picture') {
            $ret = (new Postit ([
              'wallId' => getParam('wallId'),
              'cellId' => getParam('cellId'),
              'postitId' => getParam('postitId'),
            ]))->getPicture (['pictureId' => getParam('itemId')]);
          }
          break;
        case 'attachment':
          $ret = (new Attachment([
            'wallId' => getParam('wallId'),
            'cellId' => getParam('cellId'),
            'postitId' => getParam('postitId'),
//FIXME
          ]))->get(intval(getParam('itemId')));
          break;
        case 'comment':
          $ret = (new Comment([
            'wallId' => getParam('wallId'),
            'cellId' => getParam('cellId'),
            'postitId' => getParam('postitId'),
          ]))->get();
          break;
        case 'worker':
          $Worker = new Worker([
            'wallId' => getParam('wallId'),
            'cellId' => getParam('cellId'),
            'postitId' => getParam('postitId'),
          ]);
          if (getParam('action') === 'search') {
            $ret = $Worker->search(['search' => getParam('search')]);
          } else {
            $ret = $Worker->get();
          }
          break;
        case 'user':
          $User = new User();

          switch (getParam('action')) {
            case 'ping':
              $ret = $User->refreshUpdateDate();
              break;
            case 'getFile':
              $ret = $User->getPicture(['userId' => getParam('userId')]);
              break;
            case 'messages':
              $ret = $User->getMessages(['userId' => getParam('userId')]);
              break;
          }
          break;
        case 'wall':
          $Wall = new Wall(['wallId' => getParam('wallId')]);

          switch (getParam ('action')) {
            case 'infos':
              $ret = $Wall->getWallInfos();
              break;
            case 'searchUsers':
              $ret = $Wall->searchUser(['search' => getParam('search')]);
              break;
            case 'getFile':
              $ret = $Wall->getHeaderPicture([
                'headerId' => getParam('headerId')]);
              break;
            case 'export':
              $ret = $Wall->export();
              break;
            default:
              // Get wall with user postits alerts
              $ret = $Wall->getWall(true);
          }
          break;
        case 'group':
          $Group = new Group([
            'wallId' => getParam('wallId'),
            'groupId' => getParam('groupId'),
          ]);

          switch (getParam('action')) {
            case 'searchUsers':
              $ret = $Group->searchUser(['search' => getParam('search')]);
              break;
            case 'getUsers':
              $ret = $Group->getUsers();
              break;
            default:
              $ret = $Group->getGroup();
          }
          break;
      }
      break;
    // POST
    case 'POST':
      switch ($class) {
        case 'user':
          $action = getParam('action');
          $User = new User(['data' => $data]);

          if (getParam('item') === 'wall') {
            $wallId = getParam('wallId');

            switch ($action) {
              case 'settings':
                $ret = $User->setWallSettings($wallId);
                break;
              case 'displaymode':
              case 'displayexternalref':
              case 'displayheaders':
                $ret = $User->setWallOption($wallId, $action);
                break;
            }
          } else {
            switch ($action)
            {
              case 'login':
                $ret = $User->login($data->remember);
                break;
              case 'logout':
                $ret = $User->logout();
                break;
              case 'resetPassword':
                $ret = $User->resetPassword();
                break;
            }
          }
          break;
        case 'attachment':
          $ret = (new Attachment([
            'wallId' => getParam('wallId'),
            'cellId' => getParam('cellId'),
            'postitId' => getParam('postitId'),
            'data' => $data,
          ]))->update(intval(getParam('itemId')));
          break;
      }
      break;
    // DELETE
    case 'DELETE':
      $User = new User(['data' => $data]);

      if ($class === 'user') {
        if (getParam('action') === 'messages') {
          $ret = $User->deleteMessage();
        } else {
          $ret = $User->delete();
        }
      }
      break;
  }

  echo json_encode($ret);

  //////////////////////////////////////// Local functions

  function getParam($param) {
    return trim($_GET[$param] ?? '');
  }
