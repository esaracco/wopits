<div class="modal m-fullscreen" id="shareWallPopup" role="dialog" tabindex="-1">
  <div class="modal-dialog" role="document">
    <div class="modal-content">

      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-share fa-lg fa-fw"></i><?=_("Share this wall")?></h5>
        <button type="button" class="close" data-dismiss="modal">
          <span>&times;</span>
        </button>
      </div>

      <div class="modal-body">


        <div class="delegate-admin-only">(<?=_("not being the creator of this wall, you have a restricted administration of access groups")?>)</div>
        <div class="scroll mb-3">
          <ul class="list-group attr"></ul>
        </div>

        <div class="creator-only">

          <label class="grp-lb"><?=_("Available groups:")?></label>

          <ul class="nav nav-tabs" role="tablist">
            <li class="nav-item">
              <a class="nav-link active gtype-<?=WPT_GTYPES['dedicated']?>" data-toggle="tab" href="#gtype-<?=WPT_GTYPES['dedicated']?>" role="tab" aria-controls="gtype-<?=WPT_GTYPES['dedicated']?>" aria-selected="false"><i class="fas fa-asterisk fa-xs"></i> <span><?=_("Dedicated")?></span></a>
            </li>
            <li class="nav-item">
              <a class="nav-link gtype-<?=WPT_GTYPES['generic']?>" data-toggle="tab" href="#gtype-<?=WPT_GTYPES['generic']?>" role="tab" aria-controls="gtype-<?=WPT_GTYPES['generic']?>" aria-selected="true"><i class="far fa-circle fa-xs"></i> <span><?=_("Generic")?></span></a>
            </li>
          </ul>
  
          <div class="tab-content">
  
            <div class="tab-pane show active mt-2" role="tabpanel" id="gtype-<?=WPT_GTYPES['dedicated']?>">
              <label><?=_("For this wall only.")?></label>
              <div class="mb-2">
                <button type="button" data-action="add-gtype-<?=WPT_GTYPES['dedicated']?>" class="btn btn-primary btn-sm"><i class="fas fa-bolt fa-xs"></i> <?=_("Create")?></button>
              </div>
              <div class="scroll">
                <ul class="list-group gtype-<?=WPT_GTYPES['dedicated']?> noattr"></ul>
              </div>
            </div>
    
            <div class="tab-pane mt-2" role="tabpanel" id="gtype-<?=WPT_GTYPES['generic']?>">
              <label><?=_("For all your walls.")?></label>
              <div class="mb-2">
                <button type="button" data-action="add-gtype-<?=WPT_GTYPES['generic']?>" class="btn btn-primary btn-sm"><i class="fas fa-bolt fa-xs"></i> <?=_("Create")?></button>
              </div>
              <div class="scroll">
                <ul class="list-group gtype-<?=WPT_GTYPES['generic']?> noattr"></ul>
              </div>
            </div>
  
          </div>

        </div>

      </div><!--/modal-body-->

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times"></i> <?=_("Close")?></button>
      </div>
    </div>
  </div>
  </div>
