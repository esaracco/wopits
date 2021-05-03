<?php

namespace Wopits\Services\Task;

require_once (__DIR__.'/../../../config.php');

use \Swoole\Server;
use \Wopits\Services\Task;

class Run
{
  public function receive (Server $server, int $fd, int $fromId,
                           array $data):void { }

  public function task (Server $server, int $taskId, int $fromId,
                        array $data):void
  {
    try
    {
      switch ($data['event'])
      {
        // Send email.
        case Task::EVENT_TYPE_SEND_MESSAGE:
          $this->_log ('info', Task::EVENT_TYPE_SEND_MESSAGE,
            'Executing task');
          (new \Wopits\Message())->send ($data);
          return;

        case Task::EVENT_TYPE_DUM:
          //$this->_log ('info', Task::EVENT_TYPE_DUM, 'Ping...');
          break;

        default:
          $this->_log ('warning', $data['event'], 'Unknown event.');
      }
    }
    catch (\Exception $e)
    {
      $msg = 'Task exception:'.$e->getMessage();
      $this->_log ('error', $data['event'], $msg);
      throw new \Exception ($msg);
    }
  }

  public function finish (Server $server, int $taskId, $data):bool
  {
    return true;
  }

  private function _log (string $type, string $event, string $msg):void
  {
    error_log (sprintf ("%s [%s][%s] %s",
      date('Y-m-d H:i:s'), strtoupper ($type), $event, $msg));
  }
}

