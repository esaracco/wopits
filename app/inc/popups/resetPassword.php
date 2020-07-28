<div class="modal" id="resetPasswordPopup" role="dialog" tabindex="-1">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-key fa-fw"></i> <?=_("Reset")?></h5>
        <button type="button" class="close" data-dismiss="modal">
          <span>&times;</span>
        </button>
      </div>
      <div class="modal-body">

        <div class="mb-3"><?=_("Your new password will be sent to the address below:")?></div>

        <form>
        <div class="input-group mb-1">
          <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-envelope"></i></span></div>
          <input class="form-control" name="email" type="email" value="" required placeholder="<?=_("email")?>" autofocus>
        </div>
        </form>

      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary"><?=_("Send")?></button>
        <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times"></i> <?=_("Close")?></button>
      </div>
    </div>
  </div>
</div>
