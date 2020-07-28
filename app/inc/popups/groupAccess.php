<div class="modal" id="groupAccessPopup" role="dialog" tabindex="-1">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-plus-circle fa-fw"></i> <?=_("Sharing type")?></h5>
        <button type="button" class="close" data-dismiss="modal">
          <span>&times;</span>
        </button>
      </div>
      <div class="modal-body">

        <div class="input-group">
          <div class="input-group-prepend"><span><i class="fas fa-eye fa-fw"></i></span></div>
          <div class="custom-control custom-radio">
            <input type="radio" value="<?=WPT_RIGHTS['walls']['ro']?>" class="custom-control-input" id="_access1" name="access" checked>
            <label class="custom-control-label" for="_access1"><?=_("Read only")?></label>
          </div>
        </div>
        
        <div class="input-group">
          <div class="input-group-prepend"><span><i class="fas fa-edit fa-fw"></i></span></div>
          <div class="custom-control custom-radio">
            <input type="radio" value="<?=WPT_RIGHTS['walls']['rw']?>" class="custom-control-input" id="_access2" name="access">
            <label class="custom-control-label" for="_access2"><?=_("Sticky note creation")?></label>
          </div>
        </div>
        
        <div class="input-group">
          <div class="input-group-prepend"><span><i class="fas fa-shield-alt fa-fw"></i></span></div>
          <div class="custom-control custom-radio">
            <input type="radio" value="<?=WPT_RIGHTS['walls']['admin']?>" class="custom-control-input" id="_access3" name="access">
            <label class="custom-control-label" for="_access3"><?=_("Full access (admin)")?></label>
          </div>
        </div>

        <hr>

        <div class="custom-control custom-switch mt-3 send-msg disabled">
          <input type="checkbox" class="custom-control-input" id="_sm-1">
          <label class="custom-control-label" for="_sm-1"><?=_("Notify users")?></label>
        </div>

      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary"><i class="fas fa-share"></i> <?=_("Share")?></button>
        <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-undo-alt"></i> <?=_("Cancel")?></button>
      </div>
    </div>
  </div>
  </div>
