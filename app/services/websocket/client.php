#!/usr/bin/php
<?php

require_once (__DIR__.'/../../config.php');

$options = getopt ('prnd::s::', ['from-script']);

$client = new Wopits\Services\WebSocket\Client ('127.0.0.1', WPT_WS_PORT);

if (!@$client->connect ())
{
  if (isset ($options['from-script']) &&
      (isset ($options['r']) || isset ($options['n'])))
    fwrite (STDERR,
      "[\e[1;95;38;5;214mWARNING\e[0m] WebSocket server was not listening on port ".WPT_WS_PORT."\e[0m!\n".
      "\e[3mIf this is the first time you execute this script, you can ignore this warning.\n".
      "If not, please investigate!\e[0m\n");
  else
    fwrite (STDERR,
      "[\e[1;95;38;5;214mWARNING\e[0m] WebSocket server is not listening on port ".WPT_WS_PORT."\e[0m!\n");
  exit (1);
}

// Broadcast reload order.
if (isset ($options['r']))
{
  $client->send (json_encode (['action' => 'reload']));
}
// Broadcast new release announce.
elseif (isset ($options['n']))
{
  $client->send (json_encode ([
    'action' => 'mainupgrade',
    'version' => WPT_VERSION
  ]));
}
// Keep WS connection and database persistent connection alive
elseif (isset ($options['p']))
{
  $client->send (json_encode (['action' => 'ping']));
}
// Display server statistics
elseif (isset ($options['s']))
{
  $action = empty($options['s'])?'users':$options['s'];

  $client->send (json_encode (['action' => "stat-$action"]));

  echo $client->recv()."\n";
}
// Dump server data
elseif (isset ($options['d']))
{
  $action = empty($options['d'])?'all':$options['d'];

  $client->send (json_encode (['action' => "dump-$action"]));

  echo $client->recv()."\n";
}
else
  exit ("\nUsage: ./".basename($argv[0])." [OPTION]...\n".
        "Communicate with wopits WebSocket server.\n\n".
        "  -d\tdump server data\n".
        "  -n\tannounce new release to connected clients\n".
        "  -p\tping Swoole and DB to keep connection alive\n".
        "  -r\treload clients\n".
        "  -s\tdisplay server statistics\n\n");
