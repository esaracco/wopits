<?php require(__DIR__.'/../../app/prepend.php')?>
<div class="modal" id="psearchPopup" role="dialog" tabindex="-1">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-search fa-lg fa-fw"></i> <span><?=_("Search")?></span></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3"><?=_("Find notes in the current wall.")?></div>
        <div class="input-group">
          <span class="input-group-text"><i class="fas fa-search fa-xs fa-fw"></i></span>
          <input type="text" class="form-control" value="" autocorrect="off" autocapitalize="none"><button class="btn clear-input" type="button"><i class="fa fa-times"></i></button>
        </div>
        <div class="mt-3 result text-center"></div>
      </div>
    </div>
  </div>
</div>
