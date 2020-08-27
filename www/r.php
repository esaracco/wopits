<?php

require_once (__DIR__.'/../app/prepend.php');

if ($_SERVER['QUERY_STRING'] == 'u')
  $_SESSION['upgradeDone'] = true;

header ('Cache-Control: no-cache, no-store, must-revalidate'); // HTTP 1.1
header ('Pragma: no-cache'); // HTTP 1.0
header ('Expires: Mon, 26 Jul 1997 00:00:00 GMT'); // Proxies

header ("Location: /\n\n", 'refresh');
exit ();

?>
