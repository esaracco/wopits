<div class="modal no-theme m-fullscreen" id="datePickerPopup" role="dialog" tabindex="-1">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-hourglass-end fa-fw"></i> <?=_("Deadline")?></h5>
        <button type="button" class="close" data-dismiss="modal">
          <span>&times;</span>
        </button>
      </div>
      <div class="modal-body text-center">
        <div style="display:inline-block">
          <div class="date-picker"></div>
          <div class="mt-3">
            <div class="date-picker-notify">
              <div class="custom-control custom-switch disabled">
                <input type="checkbox" class="custom-control-input" name="dp-notify" id="dp-notify">
                <label class="custom-control-label" for="dp-notify"><?=_("Notify me by email")?></label>
              </div>
              <div>
              <div class="custom-control custom-radio">
                <input type="radio" class="custom-control-input" name="dp-shift" id="_dp-shift1"> <label class="custom-control-label" for="_dp-shift1"><?=_("the same day")?></label>
              </div>
              <div class="custom-control custom-radio">
                <input type="radio" class="custom-control-input" name="dp-shift" id="_dp-shift2"> <label class="custom-control-label" for="_dp-shift2"><input type="number" min="1" value="1"> <?=_("day(s) before")?></label>
              </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary"><i class="fas fa-save"></i> <?=_("Save")?></button>
        <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times"></i> <?=_("Close")?></button>
      </div>
    </div>
  </div>
  </div>
