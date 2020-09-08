<?php

namespace Wopits\Services\Task;

require_once (__DIR__.'/../../../config.php');

class Server
{
  private $_server;
  private $_run;

  public function __construct()
  {
    $workerNum = swoole_cpu_num() * 2;
    $this->_server = new \Swoole\Server ('127.0.0.1', WPT_TASK_PORT);
    $this->_server->set ([
      'worker_num' => $workerNum,
      'daemonize' => false,
      'task_worker_num' => $workerNum * 2
    ]);

    // Attach events.
    foreach (['start', 'connect', 'receive', 'workerstart',
              'task', 'finish', 'close'] as $e)
      $this->_server->on ($e, [$this, "on$e"]);
  }

  public function start ()
  {
    $this->_server->start ();
  }

  public function onConnect ($server, $fd, $fromId) { }
  public function onClose ($server, $fd, $fromId) { }

  public function onStart ($server)
  {
    error_log (
      "[INFO][internal] wopits Task server is listening on port ".
      WPT_TASK_PORT);
  }

  public function onWorkerStart ($server, $workerId)
  {
    $this->_run = new Run ();
  }

  public function onReceive ($server, $fd, $fromId, $data)
  {
    $data = $this->unpack ($data);

    $this->_run->receive ($server, $fd, $fromId, $data);

    // Posted a task to task process.
    if (!empty($data['event']))
      $server->task (array_merge ($data , ['fd' => $fd]));
  }

  public function onTask ($server, $taskId, $fromId, $data)
  {
    $this->_run->task ($server, $taskId, $fromId, $data);
  }

  public function onFinish ($server, $taskId, $data)
  {
    $this->_run->finish ($server, $taskId, $data);
  }

  public function unpack ($data)
  {
    return (!($data = str_replace("\r\n", '', $data)) ||
            !($data = json_decode($data, true)) ||
            !is_array($data)) ? false : $data;
  }
}
