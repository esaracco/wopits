<?php require ($_SERVER['DOCUMENT_ROOT'].'/../app/prepend.php')?>
<div class="modal no-theme m-fullscreen" id="plugpropPopup" role="dialog" tabindex="-1">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-cogs fa-lg fa-fw"></i><?=_("Relation properties")?></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
          <div class="form-group mb-3">
            <label><?=_("Label")?>:</label>
            <input type="text" name="label" class="form-control form-control-sm" maxlength="<?=Wopits\DbCache::getFieldLength('postits_plugs', 'label')?>">
          </div>
          <div class="text-center">
            <button type="button" class="btn btn-xs btn-secondary reset"><i class="fas fa-undo"></i> <?=_("Default style")?></button>
          </div>
        <div id="plugprop-sample" class="mt-3 mb-4">
          <div></div>
          <div></div>
        </div>
        <form>
<div class="items-left">
          <div class="form-group">
            <label><?=_("Size")?>:</label>
            <input type="number" name="size" step="1" min="1" max="20" class="form-control">
          </div>
          <div class="items-left">

          <div class="form-group">
            <label><?=_("Type")?>:</label>
            <div class="form-check">
              <input type="radio" class="form-check-input" name="type" value="solid" id="_ra1"> <label class="form-check-label" for="_ra1"><?=_("Solid")?></label>
            </div>
            <div class="form-check">
              <input type="radio" class="form-check-input" name="type" value="dashed" id="_ra2"> <label class="form-check-label" for="_ra2"><?=_("Dashed")?></label>
            </div>
            <div class="form-check">
              <input type="radio" class="form-check-input" name="type" value="a-dashed" id="_ra3"> <label class="form-check-label" for="_ra3"><?=_("Animated dashed")?></label>
            </div>
          </div>

          <div class="form-group">
            <label>&nbsp;</label>
            <div class="form-check">
              <input type="radio" class="form-check-input" name="path" value="fluid" id="_ra4"> <label class="form-check-label" for="_ra4"><?=_("Fluid")?></label>
            </div>
            <div class="form-check">
              <input type="radio" class="form-check-input" name="path" value="straight" id="_ra5"> <label class="form-check-label" for="_ra5"><?=_("Straight")?></label>
            </div>
            <div class="form-check custom-radio">
              <input type="radio" class="form-check-input" name="path" value="arc" id="_ra6"> <label class="form-check-label" for="_ra6"><?=_("Arc")?></label>
            </div>
            <div class="form-check custom-radio">
              <input type="radio" class="form-check-input" name="path" value="magnet" id="_ra7"> <label class="form-check-label" for="_ra7"><?=_("Magnet")?></label>
            </div>
            <div class="form-check custom-radio">
              <input type="radio" class="form-check-input" name="path" value="grid" id="_ra8"> <label class="form-check-label" for="_ra8"><?=_("Grid")?></label>
            </div>

          </div>


          </div>
</div>
          <div style="clear:both"></div>
          <div class="form-group">
            <label><?=_("Color")?>:</label>
            <div><span class="cp"></span></div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary"><i class="fas fa-save"></i> <?=_("Save")?></button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fas fa-times"></i> <?=_("Close")?></button>
      </div>
    </div>
  </div>
  </div>
