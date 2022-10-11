<?php require (__DIR__.'/../../app/prepend.php')?>
<div class="modal m-fullscreen no-theme" id="postitCommentsPopup" role="dialog" tabindex="-1">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-comments fa-fw"></i> <?=_("Comments")?></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">

        <div class="editing">
          <div class="search mb-1">
            <button class="btn clear-textarea" type="button"><i class="fa fa-times"></i></button>
            <textarea class="form-control" maxlength="<?=Wopits\DbCache::getFieldLength('postits_comments', 'content')?>"></textarea>
            <div class="result-container">
              <ul class="result autocomplete list-group"></ul>
            </div>
          </div>
          <div class="tip"><i class="far fa-lightbulb"></i> <?=_("Use @ to refer to another user.")?></div>
          <button type="button" class="btn btn-primary btn-xs"><?=_("Send")?></button>
        </div>

        <div class="content"></div>

      </div>
    </div>
  </div>

 </div>
