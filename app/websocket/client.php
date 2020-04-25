#!/usr/bin/php
<?php

require (__DIR__.'/../libs/vendor/autoload.php');
require_once (__DIR__."/../class/Wpt_common.php");

$options = getopt ('prnd::s::');

\Ratchet\Client\connect('ws://localhost:'.WPT_WS_PORT)->then (
  function ($conn)
  {
    global $options;

    $conn->on('message', function($msg) use ($conn)
    {
      echo "{$msg}\n";

      $conn->close ();
    });

    // Broadcast reload order
    if (isset ($options['r']))
    {
      $conn->send (json_encode ([
        'action' => 'reload'
      ]));

      $conn->close ();
    }
    // Broadcast new release announce
    elseif (isset ($options['n']))
    {
      $conn->send (json_encode ([
        'action' => 'mainupgrade',
        'version' => WPT_VERSION
      ]));

      $conn->close ();
    }
    // Keep WS connection and database persistent connection alive
    elseif (isset ($options['p']))
    {
      $conn->send (json_encode ([
        'action' => 'ping'
      ]));

      $conn->close ();
    }
    // Display server statistics
    elseif (isset ($options['s']))
    {
      $action = empty($options['s'])?'users':$options['s'];

      $conn->send (json_encode ([
        'action' => "stat-$action"
      ]));
    }
    // Dump server data
    elseif (isset ($options['d']))
    {
      $action = empty($options['d'])?'all':$options['d'];

      $conn->send (json_encode ([
        'action' => "dump-$action"
      ]));
    }
    else
      exit ("Usage: ./client [OPTION]...\n".
            "Communicate with wopits WebSocket server.\n\n".
            "  -d\tdump server data\n".
            "  -n\tannounce new release to connected clients if needed\n".
            "  -p\tping WS and DB to keep them alive\n".
            "  -r\treload clients\n".
            "  -s\tdisplay server statistics\n");
  },
  function ($e)
  {
    echo "Could not connect: {$e->getMessage()}\n";
  });
