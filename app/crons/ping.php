#!/usr/bin/php
<?php

  require_once (__DIR__.'/../class/Wpt_common.php');

  // Keep WS connection and database persistent connection alive
  Wpt_common::ping ();
?>
