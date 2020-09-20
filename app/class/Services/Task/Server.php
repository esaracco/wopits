<?php

namespace Wopits\Services\Task;

require_once (__DIR__.'/../../../config.php');

use \Swoole\Server as SwooleServer;

class Server
{
  private $_server;
  private $_run;

  public function __construct()
  {
    $workerNum = swoole_cpu_num() * 2;

    $this->_server = new SwooleServer ('127.0.0.1', WPT_TASK_PORT);
    $this->_server->set ([
      'daemonize' => true,
      'log_file' => WPT_LOG_PATH.'/server-task.log',
      'pid_file' => __DIR__.'/../../../services/run/server-task.pid',
      'worker_num' => $workerNum,
      'task_worker_num' => $workerNum * 2,
      'reactor_num' => $workerNum * 2
    ]);

    // Attach events.
    foreach (['start', 'connect', 'receive', 'workerstart',
              'task', 'finish', 'close'] as $e)
      $this->_server->on ($e, [$this, "on$e"]);
  }

  public function start ():void
  {
    $this->_server->start ();
  }

  public function onConnect (SwooleServer $server, int $fd, int $fromId):void { }
  public function onClose (SwooleServer $server, int $fd, int $fromId):void { }

  public function onStart (SwooleServer $server):void
  {
    error_log (date('Y-m-d H:i:s').
      ' [INFO][internal] wopits Task server is listening on port '.
        WPT_TASK_PORT);
  }

  public function onWorkerStart (SwooleServer $server, int $workerId):void
  {
    $this->_run = new Run ();
  }

  public function onReceive (SwooleServer $server, int $fd, int $fromId,
                             string $data):void
  {
    $data = $this->unpack ($data);

    $this->_run->receive ($server, $fd, $fromId, $data);

    // Posted a task to task process.
    if (!empty($data['event']))
      $server->task (array_merge ($data , ['fd' => $fd]));
  }

  public function onTask (SwooleServer $server, int $taskId, int $fromId,
                          array $data):void
  {
    $this->_run->task ($server, $taskId, $fromId, $data);
  }

  public function onFinish (SwooleServer $server, int $taskId,
                            array $data):void
  {
    $this->_run->finish ($server, $taskId, $data);
  }

  public function unpack (string $data):array
  {
    return (!($data = str_replace("\r\n", '', $data)) ||
            !($data = json_decode($data, true)) ||
            !is_array($data)) ? [] : $data;
  }
}
