#!/usr/bin/php
<?php

require_once (__DIR__.'/../config.php');

// Remove expired authentication users tokens
(new Wopits\User())->purgeTokens ();

?>
