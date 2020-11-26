<div class="modal m-fullscreen no-theme" id="postitUpdatePopup" role="dialog" tabindex="-1">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-edit fa-fw"></i> <?=_("Note update")?></h5>
        <button type="button" class="close" data-dismiss="modal">
          <span>&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <form>
           <div class="slider">
            <label><?=_("Progress")?>: <span></span></label>
            <div class="slider"><input type="range" min="0" max="100" value="0"></div>
          </div>
           <div class="form-group">
            <label for="postitUpdatePopupTitle"><?=_("Title")?></label>
            <input type="text" class="form-control" id="postitUpdatePopupTitle" autofocus maxlength="<?=Wopits\DbCache::getFieldLength('postits', 'title')?>">
          </div>
          <div class="form-group">
            <label for="postitUpdatePopupBody"><?=_("Content")?></label>
            <textarea class="form-control" id="postitUpdatePopupBody"></textarea>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary"><i class="fas fa-save"></i> <?=_("Save")?></button>
        <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times"></i> <?=_("Close")?></button>
      </div>
    </div>
  </div>
  </div>
