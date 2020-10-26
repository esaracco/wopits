<?php

require_once (__DIR__.'/prepend.php');

$version = \Wopits\Helper::getWopitsVersion ();
$theme = 'theme-default';

if (!empty ($_SESSION['userId']))
{
  $User->userId = $_SESSION['userId'];
  $theme = $User->getSettings(false)->theme??'theme-default';
}

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
<html lang="<?=$slocale?>" data-fulllocale="<?=$locale?>" data-version="<?=$version?>" <?=(isset ($upgradeDone))?'data-upgradedone="1"':''?>>
<head>
  <meta charset="utf-8">
  <title>wopits - <?=_("Let your ideas shine!")?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1.0, shrink-to-fit=no, user-scalable=no">

<?php if ($scriptName == '/login.php'):?>
  <meta name="description" lang="<?=$slocale?>" content="<?=_("wopits is a multilingual application through which you can manage all kinds of projects by simply using sticky notes to share and collaborate with other users simultaneously.")?>">
  <script>window.onload=function(){try{eval("var f=(x)=>x")}catch(e){document.title="<?=_("Deprecated browser")?>";document.body.innerHTML = "<center><?=sprintf (_("Please use the latest version of a recent browser like %s or %s."), "<a href='https://www.mozilla.org'>Firefox</a>", "<a href='https://www.google.com/chrome'>Chrome</a>")?></center>"}}</script>
<?php endif?>

  <script>var wpt_userData=<?=$User->getUserDataJson()?></script>

  <link rel="manifest" href="/manifest.json?<?=$version?>">

  <link rel="stylesheet" href="/libs/node_modules/bootstrap/dist/css/bootstrap.min.css?<?=$version?>">
  <link rel="stylesheet" href="/libs/node_modules/@fortawesome/fontawesome-free/css/all.min.css?<?=$version?>">
  <link rel="stylesheet" href="/libs/node_modules/jquery-ui-dist/jquery-ui.min.css?<?=$version?>">
  <link rel="stylesheet" href="/libs/node_modules/vanderlee-colorpicker/jquery.colorpicker.css?<?=$version?>">
  <link rel="stylesheet" href="<?=$css?>">

<?php if ($theme != 'theme-default'):
        $color = explode('-', $theme)[1]?>
  <link rel="stylesheet" href="/css/themes/<?=$color?>.css<?=((WPT_DEV_MODE)?'.php':'')."?$version"?>" id="<?=$theme?>">
<?php endif?>

<!--//<WPTPROD-remove>-->
<?php if (WPT_DEV_MODE):?>
  <?php foreach (WPT_JS_NODE_MODULES as $mod):?>
    <script src="/libs/node_modules/<?=$mod?>?<?=$version?>"></script>
  <?php endforeach?>
  <?php foreach (WPT_JS_LOCAL_MODULES as $mod):?>
    <script src="/libs/<?=$mod?>?<?=$version?>"></script>
  <?php endforeach?>
<?php else:?>
<!--//</WPTPROD-remove>-->
  <script src="/libs/modules.js?<?=$version?>"></script>
<?php endif?><!--//WPTPROD-remove-->
  <script src="/libs/node_modules/moment/min/moment.min.js?<?=$version?>"></script>
  <script src="/libs/node_modules/moment-timezone/builds/moment-timezone-with-data.min.js?<?=$version?>"></script>
  <script src="<?=$js?>"></script>

  <script defer src="/libs/node_modules/tinymce/tinymce.min.js?<?=$version?>"></script>
  <script defer src="/libs/node_modules/jquery-ui/ui/i18n/datepicker-<?=($slocale == 'en')?'en-GB':$slocale?>.js?<?=$version?>"></script>
</head>
