#!/usr/bin/php
<?php

  require_once (__DIR__.'/../config.php');

  $user = new Wopits\User ();

  // Remove expired authentication users tokens
  $user->purgeTokens ();

  // Manage inactive users
  if (!WPT_USE_LDAP)
    $user->manageInactive ();

?>
