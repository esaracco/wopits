<?php

require_once (__DIR__.'/class/Common.php');

use Wopits\Common;

$version = Common::getWopitsVersion ();
$slocale = $_SESSION['slocale'];
$userId = $_SESSION['userId'] ?? 0;

if ($userId)
  $User->userId = $userId;

//<WPTPROD-remove>
if (WPT_DEV_MODE)
{
  $css = '/css/all.css.php';
  $js = '/js/all.js.php';
}
else
{
//</WPTPROD-remove>
  $css = '/css/all.css';
  $js = "/js/all-$slocale.js";
}//WPTPROD-remove

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
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1.0, shrink-to-fit=no, user-scalable=no">
  <meta name="description" lang="<?=$slocale?>" content="<?=_("wopits is a multilingual application through which you can manage all kinds of projects by simply using sticky notes to share and collaborate with other users simultaneously.")?>">

  <script>window.onload=function(){try{eval("var f=(x)=>x")}catch(e){document.title="<?=_("Deprecated browser")?>";document.body.innerHTML = "<center><?=sprintf (_("Please use the latest version of a recent browser like %s or %s."), "<a href='https://www.mozilla.org'>Firefox</a>", "<a href='https://www.google.com/chrome'>Chrome</a>")?></center>"}}</script>

  <script>var wpt_userData=<?=$User->getUserDataJson()?></script>

  <link rel="manifest" href="/manifest.json?<?=$version?>">

  <link rel="stylesheet" href="/libs/bootstrap-4.5.0/css/bootstrap.min.css">
  <link rel="stylesheet" href="/libs/font-awesome-5.13.1/css/all.min.css">
  <link rel="stylesheet" href="/libs/jquery-ui-1.12.1/jquery-ui.min.css">
  <link rel="stylesheet" href="/libs/colorpicker-1.2.20/jquery.colorpicker.css">
  <link rel="stylesheet" href="<?=$css?>">

  <?php foreach (WPT_THEMES as $theme) { ?>
    <link rel="stylesheet" href="/css/themes/<?=$theme?>.css<?=((WPT_DEV_MODE)?'.php':'')."?$version"?>" id="theme-<?=$theme?>" media="none">
  <?php } ?>

  <script src="/libs/jquery-3.5.1.min.js"></script>
  <script src="/libs/jquery-ui-1.12.1/jquery-ui.min.js"></script>
  <script src="/libs/bootstrap-4.5.0/js/bootstrap.bundle.min.js"></script>
  <script src="/libs/jquery.ui.touch-punch.min.js?<?=$version?>"></script>
  <script src="/libs/jquery.double-tap-wopits.js?<?=$version?>"></script>
  <script src="/libs/moment-2.27.0/moment.min.js"></script>
  <script src="/libs/moment-2.27.0/moment-timezone-with-data.min.js"></script>
  <script src="<?=$js?>"></script>

  <script defer src="/libs/tinymce-5.4.2/js/tinymce/tinymce.min.js"></script>
  <script defer src="/libs/jquery-ui-1.12.1/datepicker-<?=$slocale?>.js"></script>
  <script defer src="/libs/colorpicker-1.2.20/jquery.colorpicker.js"></script>
  <script defer src="/libs/leader-line.min.js?<?=$version?>"></script>
</head>
