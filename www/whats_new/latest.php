<?php require ($_SERVER['DOCUMENT_ROOT'].'/../app/prepend.php')?>
<div class="newfeatures justify latest">
<?=_("<p class='warning'><b><i class='fas fa-exclamation-triangle fa-fw'></i>Warning</b> Please, note that wopits is still in alpha stage! You are warmly invited to report bugs: <a href='mailto:report@wopit.com?subject=Bug%20report'>report@wopits.com</a> or <a href='https://github.com/esaracco/wopits/issues' target='_blank'>GitHub bug tracker</a>.</p>")?>
<?=_("<p class='warning'><b><i class='fas fa-exclamation-triangle fa-fw'></i>BREAKING CHANGES&nbsp;!</b> Users are not concerned here, but if you contribute to wopits or do self-hosting, read the <code>BREAKING_CHANGES.md</code> file.</p>")?>
<?=_("<p><b><i class='fas fa-smile-beam fa-fw'></i>Cookie</b> More secure cookie (<code>SameSite</code> set to <code>Strict</code>).</p>")?>

<?=_("<p><b><i class='fas fa-smile-beam fa-fw'></i>Global</b> External modules are no longer embedded on Git.</p>")?>
<?=_("<p><b><i class='fas fa-bug fa-fw'></i>Walls</b> Fixed issues with edit locks.</p>")?>
<?=_("<p><b><i class='fas fa-bug fa-fw'></i>Walls</b> Fixed performance issues for walls with lots of postits and relationships.</p>")?>
<?=_("<p><b><i class='fas fa-bug fa-fw'></i>Compatibility</b> Fixed issues with touch devices.</p>")?>
<?=_("<p><b><i class='fas fa-bug fa-fw'></i>Deployment</b> Fixed an issue with deploy script <code>-M</code> option.</p>")?>
<?=_("<p><b><i class='fas fa-bug fa-fw'></i>Global</b> Fixed version detection on multiple user devices.</p>")?>
</div>
