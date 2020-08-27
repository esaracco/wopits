<?php

  require_once (__DIR__.'/../../app/prepend.php');

  if (php_sapi_name() != 'cli')
    header ('Content-type: text/css');

  foreach (['main', 'login'] as $inc)
    include (__DIR__."/../../app/inc/$inc.css.php");

?>
