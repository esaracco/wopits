#!/usr/bin/php
<?php

require_once(__DIR__.'/../../config.php');

(new Wopits\Services\Task\Server())->start();

?>
