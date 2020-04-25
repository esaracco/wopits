#!/usr/bin/php
<?php

  require_once (__DIR__.'/../class/Wpt_common.php');
  require_once (__DIR__.'/../class/Wpt_postit.php');

  // Check deadline and set "obsolete" flag as needed
  (new Wpt_postit())->checkDeadline ();
?>
