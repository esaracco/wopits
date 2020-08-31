#!/usr/bin/php
<?php

require_once (__DIR__.'/../config.php');

use Swoole\WebSocket\Server;
use Swoole\Http\Request;
use Swoole\WebSocket\Frame;

use Wopits\WebSocket\ServerClass;

$server = new Server ('127.0.0.1', WPT_WS_PORT);

$server->on ('start', function (Server $server)
{
  echo "[INFO][internal] wopits WebSocket server is listening on port ".
         WPT_WS_PORT."\n\n";
});

$server->on ('open', function (Server $server, Request $req)
{
  (new ServerClass($server))->onOpen ($req);
});

$server->on ('message', function (Server $server, Frame $frame)
{
  (new ServerClass($server))->onMessage ($frame->fd, $frame->data);
});

$server->on ('close', function (Server $server, int $fd)
{
  (new ServerClass($server))->onClose ($fd);
});

$server->start ();

?>
