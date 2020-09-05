<?php

namespace Wopits\Services\Task;

require_once (__DIR__.'/../../../config.php');

use \Wopits\Services\Task;

class Run
{
  public function receive ($serv, $fd, $fromId, $data) { }

  public function task ($serv, $taskId, $fromId, $data)
  {
    try
    {
      switch ($data['event'])
      {
        // Send email.
        case Task::EVENT_TYPE_SEND_MAIL:
          $this->_log ('info', Task::EVENT_TYPE_SEND_MAIL,
            'Executing task');
          return (new \Wopits\Mailer())->send ($data);
      }
    }
    catch (\Exception $e)
    {
      $msg = 'Task exception:'.$e->getMessage();
      $this->_log ('error', $data['event'], $msg);
      throw new \Exception ($msg);
    }
  }

  public function finish ($serv, $taskId, $data)
  {
    return true;
  }

  private function _log ($type, $event, $msg)
  {
    error_log (sprintf ("[%s][%s] %s\n", strtoupper ($type), $event, $msg));
  }
}

