<?php require (__DIR__.'/../../app/prepend.php')?>
<div class="modal m-fullscreen" id="owallPopup" role="dialog" tabindex="-1">
  <div class="modal-dialog modal-dialog-scrollable" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-folder-open fa-lg fa-fw"></i><?=_("Open a wall")?></h5>
        <button type="button" class="close" data-dismiss="modal">
          <span>&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div class="input-group mb-2">
          <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-search fa-sm fa-fw"></i></span></div>
          <input type="text" class="form-control" value="" autocorrect="off" autocapitalize="none" autofocus>
        </div>

      <div class="text-center ow-filters">
        <div class="custom-control custom-radio custom-control-inline">
          <input type="radio" id="ow-all" name="ow-filter" class="custom-control-input" checked>
          <label class="custom-control-label" for="ow-all"><?=_("All")?></label>
        </div>
        <div class="custom-control custom-radio custom-control-inline">
          <input type="radio" id="ow-recent" name="ow-filter" class="custom-control-input">
          <label class="custom-control-label" for="ow-recent"><?=_("Recently viewed")?></label>
        </div>
        <div class="custom-control custom-radio custom-control-inline">
          <input type="radio" id="ow-shared" name="ow-filter" class="custom-control-input">
          <label class="custom-control-label" for="ow-shared"><?=_("Shared with you")?></label>
        </div>
        <hr>
      </div>

      <span class="btn btn-sm btn-secondary btn-circle btn-clear" data-toggle="tooltip" title="<?=_("Clear history")?>"><i class="fa fa-broom"></i></span>

        <div class="list-group"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" data-dismiss="modal"><i class="fas fa-folder-open"></i> <?=_("Open")?></button>
        <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times"></i> <?=_("Close")?></button>
      </div>
    </div>
  </div>
  </div>
