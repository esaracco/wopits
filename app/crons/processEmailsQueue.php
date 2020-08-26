#!/usr/bin/php
<?php

require_once (__DIR__.'/../config.php');

// Send emails waiting in queue.
(new Wopits\EmailsQueue())->process ();

?>
