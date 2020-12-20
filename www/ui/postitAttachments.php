<?php require (__DIR__.'/../../app/prepend.php')?>
<div class="modal m-fullscreen no-theme" id="postitAttachmentsPopup" role="dialog" tabindex="-1">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-paperclip fa-fw"></i> <?=_("Attachments")?></h5>
        <button type="button" class="close" data-dismiss="modal">
          <span>&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <a class="download"></a>
        <div class="accordion" id="pa-accordion">
          <ul class="list-group"></ul>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary"><i class="fas fa-plus"></i> <?=_("Add")?></button>
        <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times"></i> <?=_("Close")?></button>
      </div>
    </div>
  </div>

  <div class="edit-popup modal-collapse">
    <div class="modal-body justify">
      <dd class="file"></dd>
      <div class="no-details"><?=_("No further information")?></div>
      <div class="title">
        <dt><?=_("Title")?></dt>
        <dd>
          <div class="input-group"><input autofocus type="text" class="form-control adm" maxlength="<?=Wopits\DbCache::getFieldLength('postits_attachments', 'title')?>"></div>
         <div class="ro"></div>
        </dd>
      </div>
      <div class="description">
        <dt><?=_("Description")?></dt>
        <dd>
          <textarea class="form-control adm" maxlength="<?=Wopits\DbCache::getFieldLength('postits_attachments', 'description')?>"></textarea>
          <div class="ro"></div>
        </dd>
      </div>
      <div class="img"><img src="" class="mt-3 mb-3"></div>
      <button type="button" class="btn btn-primary btn-sm"><i class="fas fa-save"></i> <?=_("Save")?></button>
      </div>
  </div>

 </div>
