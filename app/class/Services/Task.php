<?php

namespace Wopits\Services;

require_once (__DIR__.'/../../config.php');

class Task
{
  private $client;

  const EVENT_TYPE_SEND_MAIL = 'send-mail';
  const EVENT_TYPE_DUM = 'dum';

  public function __construct ()
  {
    $this->client = new \Swoole\Client (SWOOLE_SOCK_TCP);

    if (!$this->client->connect ('127.0.0.1', WPT_TASK_PORT))
      throw new \Exception ("Error: swoole client connect failed");
  }

  public function execute ($data)
  {
    $this->client->send (json_encode ($data));
  }
}

