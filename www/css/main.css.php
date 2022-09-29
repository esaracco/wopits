<?php

  require_once (__DIR__.'/../../app/prepend.php');

  if (php_sapi_name() != 'cli')
    header ('Content-type: text/css');

  include (__DIR__."/../../app/inc/main.css.php");

?>
