#!/usr/bin/php
<?php
  require (__DIR__.'/../../../site-config.php');

  exec (__DIR__.'/../../websocket/client.php -n');
  exec (WPT_APACHE_RESTART);
  exec (WPT_WOPITS_RESTART);
?>
