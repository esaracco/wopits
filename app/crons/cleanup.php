#!/usr/bin/php
<?php

  require_once (__DIR__.'/../class/Wpt_common.php');

  // Remove expired authentication users tokens
  (new Wpt_user())->purgeTokens ();
?>
