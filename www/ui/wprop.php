<?php require (__DIR__.'/../../app/prepend.php')?>
<div class="modal m-fullscreen" id="wpropPopup" role="dialog" tabindex="-1">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-list fa-lg fa-fw"></i> <?=_("Wall properties")?></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">

      <form>

      <div class="mb-2 reject-sharing"><button type="button" class="btn btn-sm btn-secondary"><i class="fas fa-heart-broken fa-sm"></i> <?=_("Reject sharing")?></button></div>

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
            <div class="input-group"><input type="text" class="form-control adm" required placeholder="<?=_("wall name")?>" maxlength="<?=Wopits\DbCache::getFieldLength('walls', 'name')?>"></div>
            <!-- others -->
           <div class="ro"></div>
          </dd>
        </div>
        <!-- Wall size -->
        <div class="size">
          <dt><?=_("Size")?></dt>
          <dd class="wall-size">
            <div class="input-group input-group-sm width-height">
              <span class="input-group-text"><?=_("Width/Height")?>:</span>
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
            <textarea class="form-control adm" placeholder="<?=_("wall description")?>" maxlength="<?=Wopits\DbCache::getFieldLength('walls', 'description')?>"></textarea>
            <!-- others -->
            <div class="ro"></div>
          </dd>
        </div>
      </dl>

      </form>

      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary"><i class="fas fa-save"></i> <?=_("Save")?></button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fas fa-times"></i> <?=_("Close")?></button>
      </div>
    </div>
  </div>
  </div>
