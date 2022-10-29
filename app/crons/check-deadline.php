#!/usr/bin/php
<?php

require_once(__DIR__.'/../config.php');

// Check deadline and set "obsolete" flag as needed
(new Wopits\Wall\Postit())->checkDeadline();

?>
