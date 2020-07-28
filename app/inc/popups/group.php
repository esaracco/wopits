<div class="modal" id="groupPopup" role="dialog" tabindex="-1">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-layer-group fa-fw"></i> <span></span></h5>
        <button type="button" class="close" data-dismiss="modal">
          <span>&times;</span>
        </button>
      </div>
      <div class="modal-body">

        <form>

        <div class="desc mb-2"></div>

        <div class="input-group mb-1">
          <input type="text" class="form-control" value="" required placeholder="<?=_("group name")?>" autofocus maxlength="<?=Wpt_dbCache::getFieldLength('groups', 'name')?>">
        </div>

        <div class="input-group mb-1">
          <input type="text" class="form-control" value="" placeholder="<?=_("group description")?>" maxlength="<?=Wpt_dbCache::getFieldLength('groups', 'description')?>">
        </div>

        </form>

      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary"></button>
        <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-undo-alt"></i> <?=_("Cancel")?></button>
      </div>
    </div>
  </div>
  </div>
