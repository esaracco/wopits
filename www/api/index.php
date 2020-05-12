<?php
  require_once (__DIR__."/../../app/class/Wpt_common.php");

  // Ajax access point
  //
  // Things with which it is useless to stress WebSocket server with, or that
  // we cannot do in a WebSocket request because either we have to access the
  // PHP session or the WebSocket connection has not yet been
  // created (i.e. login page)

  $ret = [];

  $class = getParam ('class');
  $data = json_decode (urldecode (file_get_contents("php://input")));

  if (preg_match ('/^postit|user|wall$/', $class))
    require_once (__DIR__."/../../app/class/Wpt_$class.php");

  switch ($_SERVER['REQUEST_METHOD'])
  {
    // PUT
    case 'PUT':

      if ($class == 'user')
        $ret = (new Wpt_user(['data' => $data]))->create ();
      break;

    // GET
    case 'GET':

      switch ($class)
      {
        case 'common':
          if (getParam ('item') == 'timezones')
            $ret = timezone_identifiers_list ();
          break;

        case 'postit':
          $Postit = new Wpt_postit ([
              'wallId' => getParam ('wallId'),
              'cellId' => getParam ('cellId'),
              'postitId' => getParam ('postitId')
            ]);
          $item = getParam ('item');
          if ($item == 'attachment')
            $ret = $Postit->getAttachment ([
              'attachmentId' => getParam ('itemId')]);
          elseif ($item == 'picture')
            $ret = $Postit->getPicture ([
              'pictureId' => getParam ('itemId')]);
          break;

        case 'user':
          if (getParam ('item') == 'file')
            $ret = (new Wpt_user())->getPicture ([
              'userId' => getParam ('userId')
            ]);
          break;

        case 'wall':
          $Wall = new Wpt_wall (['wallId' => getParam ('wallId')]);
          $item = getParam ('item');
          if ($item == 'file')
            $ret = $Wall->getHeaderPicture ([
              'headerId' => getParam ('headerId')]);
          elseif ($item == 'export')
            $ret = $Wall->export ();
          break;
      }
      break;

    // POST
    case 'POST':

      if ($class == 'user')
      {
        $User = new Wpt_user (['data' => $data]);
        switch (getParam ('action'))
        {
          case 'login':
            $ret = $User->login ($data->remember);
            break;

          case 'logout':
            $ret = $User->logout ();
            break;

          case 'resetPassword':
            $ret = $User->resetPassword ();
            break;
        }
      }
      break;

    // DELETE
    case 'DELETE':

      if ($class == 'user')
        $ret = (new Wpt_user())->delete ();
      break;
  }

  echo json_encode ($ret);

  //////////////////////////////////////// Local functions

  function getParam ($param)
  {
    return trim ($_GET[$param]??'');
  }
?>
