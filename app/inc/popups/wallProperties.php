<div class="modal m-fullscreen" id="wallPropertiesPopup" role="dialog" tabindex="-1">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-list fa-lg fa-fw"></i><?=_("Wall properties")?></h5>
        <button type="button" class="close" data-dismiss="modal">
          <span>&times;</span>
        </button>
      </div>
      <div class="modal-body">

      <form>

      <dl>
        <!-- Wall creator -->
        <dt><?=_("Creator")?></dt><dd class="creator"></dd>
        <!-- Wall creation date -->
        <dt><?=_("Creation date")?></dt><dd class="creationdate"></dd>
        <!-- Wall name -->
        <div class="name">
          <dt><?=_("Name")?></dt>
          <dd>
            <!-- adm -->
            <div class="input-group"><input type="text" class="form-control adm" required placeholder="<?=_("wall name")?>" maxlength="<?=Wpt_dbCache::getFieldLength('walls', 'name')?>"></div>
            <!-- others -->
           <div class="ro"></div>
          </dd>
        </div>
        <!-- Wall size -->
        <div class="size">
          <dt><?=_("Size")?></dt>
          <dd class="wall-size">
            <div class="input-group input-group-sm width-height">
              <div class="input-group-prepend">
                <span class="input-group-text"><?=_("Width/Height")?>:</span>
              </div>
              <input type="number" name="wall-width" step="50" min="300" max="20000" class="form-control">
              <input type="number" name="wall-height" step="50" min="300" max="20000" class="form-control">
            </div>
          </dd>
        </div>
        <!-- Wall description -->
        <div class="description">
          <dt><?=_("Description")?></dt>
          <dd>
            <!-- adm -->
            <textarea class="form-control adm" placeholder="<?=_("wall description")?>" maxlength="<?=Wpt_dbCache::getFieldLength('walls', 'description')?>"></textarea>
            <!-- others -->
            <div class="ro"></div>
          </dd>
        </div>
      </dl>

      </form>

      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary"><?=_("Save")?></button>
        <button type="button" class="btn btn-secondary" data-dismiss="modal"><?=_("Close")?></button>
      </div>
    </div>
  </div>
  </div>
