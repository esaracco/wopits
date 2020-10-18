<?php require (__DIR__.'/../../app/prepend.php')?>
<div class="modal" id="postitsSearchPopup" role="dialog" tabindex="-1">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-search fa-lg fa-fw"></i><span><?=_("Search")?></span></h5>
        <button type="button" class="close" data-dismiss="modal">
          <span>&times;</span>
        </button>
      </div>
      <div class="modal-body">
       
        <div class="mb-3"><?=_("Search for sticky notes in the current wall.")?></div>

        <div class="input-group">
          <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-search fa-xs fa-fw"></i></span></div>
          <input type="input" class="form-control" value="" autocorrect="off" autocapitalize="none" autofocus>
        </div>

        <div class="mt-3 result text-center"></div>

      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times"></i> <?=_("Close")?></button>
      </div>
    </div>
  </div>
  </div>
