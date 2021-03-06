<?php require ($_SERVER['DOCUMENT_ROOT'].'/../app/prepend.php')?>
<div class="modal m-fullscreen" id="aboutPopup" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><img src="/img/wopits.png?<?=\Wopits\Helper::getWopitsVersion ()?>" class="float-left" width="32">&nbsp;wopits v<?=WPT_VERSION?></h5>
        <button type="button" class="close" data-dismiss="modal">
          <span>&times;</span>
        </button>
      </div>
      <div class="modal-body justify">

        <p class="project-title">
          <b>w</b>orld <b>o</b>f <b>p</b>ost-<b>its</b><br>
          <span class="desc"><?=sprintf(_("developed by %s"), '<a href="https://www.esaracco.fr" target="_blank">Emmanuel Saracco</a>')?></span>
        </p>

        <p class="mt-2 mb-3">
          <?=_("A app for managing projects online just using sticky notes to share and collaborate.")?>
        </p>

        <p class="desc mb-1">
          <?=sprintf(_("Project source files are hosted on %s and distributed under the %s license."), '<a href="https://github.com/esaracco/wopits" target="_blank">GitHub</a>', '<a href="https://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GPL</a>')?>
        </p>

        <p class="desc mb-1">
          <?=sprintf(_("If you need professional hosting or special features, don't hesitate to contact %s."), '<a href="https://www.easter-eggs.com" target="_blank">Easter-eggs</a>')?>
        </p>

        <form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_blank"><input type="hidden" name="cmd" value="_s-xclick"><input type="hidden" name="hosted_button_id" value="YJE4Z8T66QLQ4">
          <p class="desc mt-3 mb-1 text-center">
            <a class="btn btn-secondary btn-xs" role="button" target="_blank" href="https://github.com/esaracco/wopits/issues"><i class="fas fa-bug fa-fw"></i> <?=_("Report")?></a>
            <a class="btn btn-secondary btn-xs" role="button" target="_blank" href="https://twitter.com/wopitsapp"><i class="fab fa-twitter fa-fw"></i> @wopitsapp</a>
            <button class="btn btn-secondary btn-xs"><i class="fas fa-donate fa-fw"></i> <?=_("Support")?></button>
          </p>
        </form>

        <?php if (defined ('WPT_ABOUT_WARNING') && WPT_ABOUT_WARNING):?>
          <div class="mt-4 warning">
            <h2 class="bg-warning"><?=_("Warning")?></h2>
  
            <p class="mb-2"><?=_("This is not a professional hosting site but a place allowing you to easily use wopits without having to install it yourself on a server.")?></p>
  
            <p class="mb2"><?=_("Of course we are doing everything to ensure a quality service, but the best solution is hosting on your own servers or with a professional host. wopits is a free project, anyone can get it and install it!")?></p>
  
          </div>
        <?php endif?>
  
        <?php if (defined ('WPT_ABOUT_PRIVACY') && WPT_ABOUT_PRIVACY):?>
          <div class="mt-4 warning">
            <h2 class="bg-info"><?=_("Privacy policy")?></h2>
  
            <p class="mb-2"><?=_("Unlike other applications offering you a free service, you are not considered here as a commodity or as a product.")?></p>
  
            <p class="mb-2"><?=_("Neither the data you create nor your account information is shared with any third parties.")?></p>
  
            <p class="mb-2"><?=_("When you delete your account, your walls and all your personnal data are permanently erased.")?></p>
  
          </div>
        <?php endif?>

      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times"></i> <?=_("Close")?></button>
      </div>
    </div>
  </div>
  </div>
