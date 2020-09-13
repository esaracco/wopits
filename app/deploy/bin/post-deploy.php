#!/usr/bin/php
<?php
  require (__DIR__.'/../../../site-config.php');

  // Create services directory if needed.
  $runPath = __DIR__.'/../../services/run';
  if (!file_exists ($runPath))
  {
    mkdir ($runPath, 0755);
    chown ($runPath, WPT_SERVICE_USER);
  }

  // Tell WS clients to load the new release.
  exec (__DIR__.'/../../services/websocket/client.php -n');

  // Restart apache.
  exec (WPT_APACHE_RESTART);

  // Restart Task & WS servers.
  exec (WPT_WOPITS_TASK_RESTART);
  exec (WPT_WOPITS_WS_RESTART);
?>
