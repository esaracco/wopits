<div class="modal" id="createWallPopup" role="dialog" tabindex="-1">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-signature fa-lg fa-fw"></i><?=_("New wall")?></h5>
        <button type="button" class="close" data-dismiss="modal">
          <span>&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <form>
          <div class="input-group"><input type="text" class="form-control" required autofocus placeholder="<?=_("wall name")?>" maxlength="<?=Wpt_dbCache::getFieldLength('walls', 'name')?>"></div>
          <div><input type="checkbox" checked="checked" id="w-grid"> <label for="w-grid"><?=_("With grid")?></label></div>

          <div class="wall-size">
            <div class="input-group input-group-sm cols-rows">
              <div class="input-group-prepend">
                <span class="input-group-text"><?=_("Columns/Rows")?>:</span>
              </div>
              <input type="number" name="wall-cols" step="1" min="1" class="form-control">
              <input type="number" name="wall-rows" step="1" min="1" class="form-control">
            </div>
          
            <div class="input-group input-group-sm width-height">
              <div class="input-group-prepend">
                <span class="input-group-text"><?=_("Width/Height")?>:</span>
              </div>
              <input type="number" name="wall-width" step="50" min="300" max="20000" class="form-control">
              <input type="number" name="wall-height" step="50" min="300" max="20000" class="form-control">
            </div>
          </div>

        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary"><?=_("Create")?></button>
        <button type="button" class="btn btn-secondary" data-dismiss="modal"><?=_("Close")?></button>
      </div>
    </div>
  </div>
  </div>
