<?php require (__DIR__.'/../../app/prepend.php')?>
<div class="modal m-fullscreen no-theme" id="postitCommentsPopup" role="dialog" tabindex="-1">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-comments fa-fw"></i> <?=_("Comments")?></h5>
        <button type="button" class="close" data-dismiss="modal">
          <span>&times;</span>
        </button>
      </div>
      <div class="modal-body">

        <div class="search mb-1"><textarea class="form-control" autofocus maxlength="<?=Wopits\DbCache::getFieldLength('postits_comments', 'content')?>"></textarea><span class="btn btn-sm btn-secondary btn-circle btn-clear"><i class="fa fa-broom"></i></span><div class="result-container"><ul class="result autocomplete list-group"></ul></div></div><div class="tip"><i class="far fa-lightbulb"></i> <?=_("Use @ to refer to another user.")?></div><button type="button" class="btn btn-primary btn-xs"><?=_("Send")?></button>

        <div class="content"></div>

      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times"></i> <?=_("Close")?></button>
      </div>
    </div>
  </div>

 </div>
