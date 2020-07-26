#!/usr/bin/php
<?php

  require_once (__DIR__.'/../class/Wpt_common.php');
  require_once (__DIR__.'/../class/Wpt_emailsQueue.php');

  // Send emails waiting in queue.
  (new Wpt_emailsQueue())->process ();
?>
