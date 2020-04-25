<div class="modal" id="aboutPopup" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><img src="img/wopits.png?<?=$version?>" class="float-left" width="32">&nbsp;wopits v<?=WPT_VERSION?></h5>
        <button type="button" class="close" data-dismiss="modal">
          <span>&times;</span>
        </button>
      </div>
      <div class="modal-body justify">

        <p><i><b>w</b>orld <b>o</b>f <b>p</b>ost-<b>its</b></i></p>

        <p class="mt-2 mb-2">
          <?=_("wopits is a world in which you can manage all kinds of projects by simply using post-its to share and collaborate with other users simultaneously.")?>
        </p>

        <p class="desc mb-2">
          <?=sprintf(_("Project source files are hosted on %s and distributed under the %s license."), '<a href="https://github.com/esaracco/wopits" target="_blank">GitHub</a>', '<a href="https://www.gnu.org/licenses/gpl-3.0.html" target="_blank">GPL</a>')?>
        </p>

        <p class="desc mb-2">
          <?=sprintf(_("If you need professional hosting or special features, don't hesitate to contact %s."), '<a href="https://www.easter-eggs.com" target="_blank">Easter-eggs</a>')?>
        </p>

        <?php if ($_SERVER['HTTP_HOST'] == 'www.wopits.com'):?>
          <div class="mt-4 warning">
            <h2 class="bg-warning"><?=_("Warning")?></h2>
  
            <p class="mb-2"><?=_("We do not provide any guarantee on the availability on service or on the backups of your data. This is not a professional hosting site but a place allowing you to easily use wopits without having to install it yourself on a server.")?></p>
  
            <p class="mb2"><?=_("Of course we are doing everything to ensure a quality service, but the best solution is hosting on your own servers or with a professional host. wopits is a free project, anyone can get it and install it!")?></p>
  
          </div>
  
          <div class="mt-4 warning">
            <h2 class="bg-info"><?=_("Privacy policy")?></h2>
  
            <p class="mb-2"><?=_("Unlike other applications offering you a free service, you are not considered here as a commodity or as a product.")?></p>
  
            <p class="mb-2"><?=_("Neither the data you create nor your account information is shared with any third parties.")?></p>
  
            <p class="mb-2"><?=_("When you delete your account, your walls and all your personnal data are permanently erased.")?></p>
  
          </div>
        <?php endif?>

      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal"><?=_("Close")?></button>
      </div>
    </div>
  </div>
  </div>
