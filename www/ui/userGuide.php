<?php require (__DIR__.'/../../app/prepend.php')?>
<div class="modal m-fullscreen" id="userGuidePopup" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-hands-helping fa-lg fa-fw"></i><?=_("User guide")?></h5>
        <button type="button" class="close" data-dismiss="modal">
          <span>&times;</span>
        </button>
      </div>
      <div class="modal-body justify">

      <div class="accordion" id="ug-accordion">

        <div class="card">
          <div class="card-header">
            <h2 class="mb-0"><button class="btn btn-link" type="button" data-toggle="collapse" data-target="#collapse1" aria-expanded="false"><i class="fas fa-border-all fa-lg fa-fw"></i> <?=_("Walls")?></button></h2>
          </div>
          <div id="collapse1" class="collapse" data-parent="#ug-accordion">
            <div class="card-body">
      <?=_("<p>To create a wall, click on <kbd>Wall</kbd>&rarr;<kbd>New...</kbd> and enter its name. If some walls are already open, you can use the shortcut <i class='fas fa-plus'></i> to the right of the tab titles.</p><p>To create a wall occupying the entire screen, without columns or rows, uncheck the <kbd>With grid</kbd> checkbox option.</p><p>Once the wall is created, you can open the rows and columns menu by clicking on <i class='far fa-caret-square-down'></i>. You have the option to rename it, associate an image or completely delete the item. Editing header titles can also be done by clicking on them.</p><p>You can edit the name and description of a wall by clicking on its tab title.</p><p>To add a row or column, use the menu <kbd>Edit</kbd>.</p><p>To open walls already created or shared with you by other users, click on <kbd>Wall</kbd>&rarr;<kbd>Open...</kbd>.</p><p>Your own walls always appear with the icon <i class='fas fa-shield-alt'></i>. Walls shared with you by other users may appear with the following icons:<dl class='row'><dt class='col-sm-2'><i class='fas fa-sm fa-eye fa-fw'></i></dt><dd class='col-sm-9'>Sharing is read-only.</dd><dt class='col-sm-2'><i class='fas fa-sm fa-edit fa-fw'></i></dt><dd class='col-sm-9'>Sharing allows you to create and modify notes.</dd><dt class='col-sm-2'><i class='fas fa-sm fa-shield-alt fa-fw'></i></dt><dd class='col-sm-9'>Sharing allows you to create notes and manage the wall. It's the sharing that gives you the most rights. You will also have partial generic groups management.</dd></dl><p>If other users have opened the wall you are viewing, an icon <i class='fas fa-user-friends'></i> appears at the bottom of the wall's menu.</p><p>You can open multiple walls at the same time. They will be automatically reloaded when you return to the application.</p><p>To have a complete view of the wall (it may be difficult to visualize large walls), click on <kbd>View</kbd>&rarr;<kbd>Full view</kbd>. Some features are not available in this mode. To return to the standard view, click on the <i class='fas fa-crosshairs'></i> button or use <kbd>View</kbd>&rarr;<kbd>Standard view</kbd>.</p><p>To display notes as lists, click the <i class='fas fa-tasks'></i> icon on the wall's menu. In this mode, not all operations are available, but you can still edit and create notes. To return to the standard view use the <i class='fas fa-sticky-note'></i> icon. You can also choose a particular mode for each cell by clicking on its own menu.</p><p>You can export a wall with the <kbd>Wall</kbd>&rarr;<kbd>Export</kbd> menu. Click on <kbd>Wall</kbd>&rarr;<kbd>Import...</kbd> to re-import it.</p><p>At any time you can clone a wall using the <kbd>Wall</kbd>&rarr;<kbd>Clone...</kbd> menu.</p>")?>
            </div>
          </div>
        </div>

        <div class="card">
          <div class="card-header">
            <h2 class="mb-0"><button class="btn btn-link" type="button" data-toggle="collapse" data-target="#collapse2" aria-expanded="false"><i class="fas fa-sticky-note fa-lg fa-fw"></i> <?=_("Notes")?></button></h2>
          </div>
          <div id="collapse2" class="collapse" data-parent="#ug-accordion">
            <div class="card-body">
      <?=_("<p>To create a note, double-click inside a cell. You can then move it anywhere on the wall. The creation date appears at the bottom left.</p><p>By clicking on the note's menu <i class='far fa-caret-square-down'></i> you will have the choice between the following options:</p><dl class='row'><dt class='col-sm-2'><i class='fas fa-sm fa-times fa-fw'></i></dt><dd class='col-sm-9'>Delete the note. All associated documents will be deleted.</dd><dt class='col-sm-2'><i class='fas fa-sm fa-edit fa-fw'></i></dt><dd class='col-sm-9'>Edit title and content.</dd><dt class='col-sm-2'><i class='fas fa-sm fa-tags fa-fw'></i></dt><dd class='col-sm-9'>Manage associated tags. If some tags are already associated, their icons appear on the right. You can also click on them to manage them.</dd><dt class='col-sm-2'><i class='fas fa-sm fa-palette fa-fw'></i></dt><dd class='col-sm-9'>Change the color.</dd><dt class='col-sm-2'><i class='fas fa-sm fa-hourglass-end fa-fw'></i></dt><dd class='col-sm-9'>Set the deadline and email notification. You can then reset it by clicking on <i class='fas fa-xs fa-times-circle fa-fw'></i>. When the deadline arrives, the bottom of the note will be displayed in red.</dd><dt class='col-sm-2'><i class='fas fa-sm fa-paperclip fa-fw'></i></dt><dd class='col-sm-9'>Manage associated documents. If documents are already associated, an icon appears at the top left. You can also click on this icon to manage them.</dd><dt class='col-sm-2'><i class='fas fa-sm fa-bezier-curve fa-fw'></i></dt><dd class='col-sm-9'>Manage relationships between notes.</dd></dl><p>Changing the note's title can also be done by clicking on it.</p><p>By default, to respect your privacy, external content (images and videos) are filtered so that your IP address is not disclosed. The <i class='fas fa-umbrella'></i> icon of the wall's menu indicate that you are protected. Just click on it to allow external content to be displayed.</p><p>You can search in the notes of the current wall by using the <i class='fas fa-search'></i> icon of the wall's menu. Notes containing the searched string will be highlighted.</p><p>It is possible to filter the notes by using the filtering tool. To display it, click on <kbd>View</kbd>&rarr;<kbd>Filters</kbd>. You can filter by tags and colors.</p><p>On some little screens it can be useful to use the <kbd>Arrows</kbd> tool in order to move around the wall.</p>")?>
            </div>
          </div>
        </div>

        <div class="card">
          <div class="card-header">
            <h2 class="mb-0"><button class="btn btn-link" type="button" data-toggle="collapse" data-target="#collapse3" aria-expanded="false"><i class="fas fa-share fa-lg fa-fw"></i> <?=_("Sharing")?></button></h2>
          </div>
          <div id="collapse3" class="collapse" data-parent="#ug-accordion">
            <div class="card-body">
      <?=_("<p>In order to share a wall, you must first create user groups. To create a group, use the <i class='fas fa-share'></i> icon of the wall's menu.</p><p>There are two types of groups:</p><dl class='row'><dt class='col-sm-3'>Dedicated group</dt><dd class='col-sm-9'>This type of group will only be available for the current wall.</dd><dt class='col-sm-3'>Generic group</dt><dd class='col-sm-9'>This type of group will be available for the current wall but also for all your other walls.</dd></dl><p>Once you have created a group, click on the icon <i class='fas fa-user-friends'></i> to manage users. Enter their name in the search field. Users appearing in the list can be added to the group by clicking on <i class='fas fa-plus-circle'></i>.</p><p>To remove users, click on <i class='fas fa-minus-circle'></i>.</p><p>For the moment you have only done group management. Now you have to attach these groups to the current wall to define the type of sharing. Click on the button <button type='button' class='btn btn-secondary btn-xs'><i class='fas fa-plus-circle'></i> Share</button> of the group line. You can use the following types of sharing:</p><dl class='row'><dt class='col-sm-2'><i class='fas fa-sm fa-eye fa-fw'></i></dt><dd class='col-sm-9'>Sharing is read-only. Users will be able to view the wall but not modify it.</dd><dt class='col-sm-2'><i class='fas fa-sm fa-edit fa-fw'></i></dt><dd class='col-sm-9'>Sharing allows users to create and modify notes (their own and those of others).</dd><dt class='col-sm-2'><i class='fas fa-sm fa-shield-alt fa-fw'></i></dt><dd class='col-sm-9'>Sharing allows users to create notes and administer the wall. It is the sharing that gives the most rights to users. They will also have partial management of generic groups.</dd></dl><p>If you want the users of the group to be notified of the sharing, select the <i>Send an email to notify group users</i> option before validating.</p><p>By the way, you can chat with other wall users by clicking <kbd>View</kbd>&rarr;<kbd>Chat room</kbd>. Chat rooms are wall specific, i.e. you can have as many as you have open walls.</p><p>Sharing will not be available if you checked the invisible mode option in your account page.</p>")?>
            </div>
          </div>
        </div>

        <div class="card">
          <div class="card-header">
            <h2 class="mb-0"><button class="btn btn-link" type="button" data-toggle="collapse" data-target="#collapse4" aria-expanded="false"><i class="fas fa-cogs fa-lg fa-fw"></i> <?=_("Batch actions")?></button></h2>
          </div>
          <div id="collapse4" class="collapse" data-parent="#ug-accordion">
            <div class="card-body">
      <?=_("<p>To act on several notes at the same time, select them with <kbd>ctrl</kbd>+click. A new floating menu will appear. Select as many notes as you want among all your opened walls.</p><p>Depending on your rights to the walls, you will have the choice between the following options:</p><dl class='row'><dt class='col-sm-2'><i class='fas fa-sm fa-paste fa-fw'></i></dt><dd class='col-sm-9'>Copy the selected notes by <kbd>ctrl</kbd>+click then on the destination cell.</dd><dt class='col-sm-2'><i class='fas fa-sm fa-cut fa-fw'></i></dt><dd class='col-sm-9'>Move the selected notes by <kbd>ctrl</kbd>+click then on the destination cell.</dd><dt class='col-sm-2'><i class='fas fa-sm fa-palette fa-fw'></i></dt><dd class='col-sm-9'>Change selected notes color.</dd><dt class='col-sm-2'><i class='fas fa-sm fa-trash fa-fw'></i></dt><dd class='col-sm-9'>Delete selected notes.</dd></dl>")?>
            </div>
          </div>
        </div>

      </div>

      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times"></i> <?=_("Close")?></button>
      </div>
    </div>
  </div>
  </div>
