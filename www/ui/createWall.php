<?php require (__DIR__.'/../../app/prepend.php')?>
<div class="modal m-fullscreen" id="createWallPopup" role="dialog" tabindex="-1">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-border-all fa-lg fa-fw"></i><?=_("New wall")?></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form>
          <div class="input-group"><input type="text" class="form-control" required autofocus placeholder="<?=_("wall name")?>" maxlength="<?=Wopits\DbCache::getFieldLength('walls', 'name')?>"></div>
          <div class="form-check form-switch mt-3 mb-2">
            <input type="checkbox" checked="checked" class="form-check-input" id="w-grid">
            <label class="form-check-label" for="w-grid"><?=_("With grid")?></label>
          </div>

          <div class="wall-size">
            <div class="input-group input-group-sm cols-rows">
              <span class="input-group-text"><?=_("Columns/Rows")?>:</span>
              <input type="number" name="wall-cols" step="1" min="1" class="form-control">
              <input type="number" name="wall-rows" step="1" min="1" class="form-control">
            </div>
          
            <div class="input-group input-group-sm width-height">
              <span class="input-group-text"><?=_("Width/Height")?>:</span>
              <input type="number" name="wall-width" step="50" min="300" max="20000" class="form-control">
              <input type="number" name="wall-height" step="50" min="300" max="20000" class="form-control">
            </div>
          </div>

        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary"><i class="fas fa-bolt"></i> <?=_("Create")?></button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fas fa-undo-alt"></i> <?=_("Cancel")?></button>
      </div>
    </div>
  </div>
  </div>
