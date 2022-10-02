<?php require (__DIR__.'/../../app/prepend.php')?>
<div class="modal" id="userViewPopup" role="dialog" tabindex="-1">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-user-circle fa-lg fa-fw"></i> <span></span></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">

      <div class="content-centered"><div class="user-picture mb-3"></div></div>

      <dl>
        <div class="name">
          <dt><?=_("Name")?></dt>
          <dd>
          </dd>
        </div>
        <!-- Wall description -->
        <div class="about">
          <dt><?=_("About")?></dt>
          <dd>
          </dd>
        </div>
      </dl>

      </div>
    </div>
  </div>
  </div>
