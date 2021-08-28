<?php require (__DIR__.'/../../app/prepend.php')?>
<div class="modal no-theme m-fullscreen" id="dpickPopup" role="dialog" tabindex="-1">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-hourglass-end fa-fw"></i> <?=_("Deadline")?></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body text-center">
        <div class="inline-block">
          <div class="dpick"></div>
          <div class="mt-3">
            <div class="dpick-notify">
              <div class="form-check form-switch disabled">
                <input type="checkbox" class="form-check-input" name="dp-notify" id="dp-notify">
                <label class="form-check-label" for="dp-notify"><?=_("Notify me")?></label>
              </div>
              <div>
              <div class="form-check">
                <input type="radio" class="form-check-input" name="dp-shift" id="_dp-shift1"> <label class="form-check-label" for="_dp-shift1"><?=_("the same day")?></label>
              </div>
              <div class="form-check">
                <input type="radio" class="form-check-input" name="dp-shift" id="_dp-shift2"> <label class="form-check-label" for="_dp-shift2"><input type="number" min="1" value="1"> <?=_("day(s) before")?></label>
              </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary"><i class="fas fa-save"></i> <?=_("Save")?></button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fas fa-times"></i> <?=_("Close")?></button>
      </div>
    </div>
  </div>
  </div>
