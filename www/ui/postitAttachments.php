<?php require (__DIR__.'/../../app/prepend.php')?>
<div class="modal m-fullscreen no-theme" id="postitAttachmentsPopup" role="dialog" tabindex="-1">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-paperclip fa-fw"></i> <?=_("Attached files")?></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <a class="download"></a>
        <div class="accordion" id="pa-accordion">
          <div class="list-group"></div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary"><i class="fas fa-plus"></i> <?=_("Add")?></button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fas fa-times"></i> <?=_("Close")?></button>
      </div>
    </div>
  </div>

  <div class="edit-popup modal-collapse">
    <div class="modal-body justify">
      <dd class="file"></dd>
      <div class="file-infos mb-2"></div>
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
      <div class="text-center">
        <button type="button" class="btn btn-primary btn-sm" data-action="save"><i class="fas fa-save"></i> <?=_("Save")?></button> <button type="button" class="btn btn-secondary btn-sm" data-action="delete"><i class="fas fa-trash"></i> <?=_("Delete")?></button> <button type="button" class="btn btn-secondary btn-sm" data-action="download"><i class="fas fa-download"></i> <?=_("Download")?></button>
      </div>
    </div>
  </div>

 </div>
