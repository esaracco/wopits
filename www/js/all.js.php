<?php

require_once (__DIR__.'/../../app/prepend.php');

if (php_sapi_name() != 'cli')
  header ('Content-type: application/javascript; charset=utf-8');

foreach (array_keys (WPT_MODULES) as $inc)
  include (__DIR__."/../../app/inc/$inc.js.php");

?>
