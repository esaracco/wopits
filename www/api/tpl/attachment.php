<?php
  require_once (__DIR__.'/../../../app/class/Wpt_dbCache.php');
?>
<div id="attachmentPopup" class="modal-collapse">
  <div class="modal-body">
  <!-- File -->
  <dd class="file"></dd>
  <!-- Title -->
  <div class="title">
    <dt><?=_("Title")?></dt>
    <dd>
      <!-- adm -->
      <div class="input-group"><input type="text" class="form-control adm" maxlength="<?=Wpt_dbCache::getFieldLength('postits_attachments', 'title')?>"></div>
      <!-- others -->
     <div class="ro"></div>
    </dd>
  </div>
  <!-- Description -->
  <div class="description">
    <dt><?=_("Description")?></dt>
    <dd>
      <!-- adm -->
      <textarea class="form-control adm" maxlength="<?=Wpt_dbCache::getFieldLength('postits_attachments', 'description')?>"></textarea>
      <!-- others -->
      <div class="ro"></div>
    </dd>
  </div>
  <div class="img"><img src="" class="mt-3 mb-3"></div>
  </div>
  <button type="button" class="btn btn-primary btn-sm"><i class="fas fa-save"></i> <?=_("Save")?></button>
</div>
