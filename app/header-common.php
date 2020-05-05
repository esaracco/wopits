<?php
  require_once (__DIR__.'/class/Wpt_common.php');

  $version = Wpt_common::getWopitsVersion ();
  $slocale = $_SESSION['slocale'];
  $userId = $_SESSION['userId'] ?? 0;

  if (WPT_DEV_MODE) // PROD-remove
  { // PROD-remove
    $css = 'css/all.css.php'; // PROD-remove
    $js = 'js/all.js.php'; // PROD-remove
  } // PROD-remove
  else // PROD-remove
  { // PROD-remove
    $css = 'css/all.css';
    $js = "js/all-$slocale.js";
  } // PROD-remove

  $css .= "?$version";
  $js .= "?$version";

  if (!empty($_SESSION['upgradeDone']))
  {
    $upgradeDone = true;

    unset ($_SESSION['upgradeDone']);
  }
?>
<!doctype html>
<html lang="<?=$slocale?>" data-fulllocale="<?=$_SESSION['locale']?>" data-version="<?=$version?>" <?=(isset ($upgradeDone))?'data-upgradedone="1"':''?>>
<head>
  <meta charset="utf-8">
  <title>wopits - <?=_("Let your ideas shine!")?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no, user-scalable=0">

  <script>window.onload=function(){try{eval("var f=(x)=>x")}catch(e){document.title="<?=_("Deprecated browser")?>";document.body.innerHTML = "<center><?=sprintf (_("Please use the latest version of a recent browser like %s or %s."), "<a href='https://www.mozilla.org'>Firefox</a>", "<a href='https://www.google.com/chrome'>Chrome</a>")?></center>"}}</script>

  <script>var wpt_userData={<?php if ($userId):?>token:"<?=$_SESSION['userToken']??''?>",id:<?=$userId?>,settings:<?=(new Wpt_user())->getSettings()?>,walls:<?=json_encode ((new Wpt_wall())->getWall())?>,<?php else:?>id:0,settings:{},walls:[]<?php endif?>}</script>

  <link rel="manifest" href="/manifest.json?<?=$version?>">

  <link rel="stylesheet" href="libs/bootstrap-4.4.1/css/bootstrap.min.css">
  <link rel="stylesheet" href="libs/font-awesome-5.11.2/css/all.min.css">
  <link rel="stylesheet" href="libs/jquery-ui-1.12.1/jquery-ui.min.css">
  <link rel="stylesheet" href="libs/colorpicker-1.2.20/jquery.colorpicker.css">
  <link rel="stylesheet" href="<?=$css?>">

  <?php foreach (WPT_THEMES as $theme) { ?>
    <link rel="stylesheet" href="css/themes/<?=$theme?>.css<?=((WPT_DEV_MODE)?'.php':'')."?$version"?>" id="theme-<?=$theme?>" media="none">
  <?php } ?>

  <script src="libs/jquery-3.5.1.min.js"></script>
  <script src="libs/jquery-ui-1.12.1/jquery-ui.min.js"></script>
  <script src="libs/bootstrap-4.4.1/js/bootstrap.bundle.min.js"></script>
  <script src="libs/jquery.ui.touch-punch.min.js?<?=$version?>"></script>
  <script src="libs/jquery.double-tap-wopits.js?<?=$version?>"></script>
  <script src="libs/moment-2.24.0/moment.min.js"></script>
  <script src="libs/moment-2.24.0/moment-timezone-with-data.min.js"></script>
  <script src="<?=$js?>"></script>

  <script defer src="libs/tinymce-5.2.0/js/tinymce/tinymce.min.js"></script>
  <script defer src="libs/jquery-ui-1.12.1/datepicker-<?=$slocale?>.js"></script>
  <script defer src="libs/colorpicker-1.2.20/jquery.colorpicker.js"></script>
  <script defer src="libs/leader-line.min.js?<?=$version?>"></script>
</head>
