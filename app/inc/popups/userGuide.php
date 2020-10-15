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
            <h2 class="mb-0"><button class="btn btn-link" type="button" data-toggle="collapse" data-target="#collapse1" aria-expanded="false"><i class="fas fa-border-all fa-lg fa-fw"></i> <?=_("Walls management")?></button></h2>
          </div>
          <div id="collapse1" class="collapse" data-parent="#ug-accordion">
            <div class="card-body">
      <?=_("<p>To create a wall, click on <kbd>Wall</kbd>&rarr;<kbd>New...</kbd> and enter its name. If some walls are already open, you can use the shortcut <i class='fas fa-plus'></i> to the right of the tab titles.</p><p>To create a wall occupying the entire screen, without columns or rows, uncheck the <kbd>With grid</kbd> checkbox option.</p><p>Once the wall is created, you can open the rows and columns menu by clicking on <i class='far fa-caret-square-down'></i>. You have the option to rename it, associate an image or completely delete the item. Editing header titles can also be done more directly by clicking on them.</p><p>You can edit the name and description of a wall via the menu <kbd>Edit</kbd>&rarr;<kbd>Properties...</kbd> or by clicking directly on the name of the wall.</p><p>To add a row or column, use the menu <kbd>Edit</kbd>.</p><p>To open walls already created or shared with you by other users, click on <kbd>Wall</kbd>&rarr;<kbd>Open...</kbd>.</p><p>Your own walls always appear with the icon <i class='fas fa-shield-alt'></i>. Walls shared with you by other users may appear with the following icons:<dl class='row'><dt class='col-sm-2'><i class='fas fa-sm fa-eye fa-fw'></i></dt><dd class='col-sm-9'>Sharing is read-only.</dd><dt class='col-sm-2'><i class='fas fa-sm fa-edit fa-fw'></i></dt><dd class='col-sm-9'>Sharing allows you to create and modify sticky notes.</dd><dt class='col-sm-2'><i class='fas fa-sm fa-shield-alt fa-fw'></i></dt><dd class='col-sm-9'>Sharing allows you to create sticky notes and manage the wall. It's the sharing that gives you the most rights. You will also have partial group management.</dd></dl><p>If other users have opened the wall you are viewing, an icon <i class='fas fa-user-friends'></i> appears at the top of the wall on the left.</p><p>You can open multiple walls at the same time. They will be automatically reloaded when you return to the application.</p><p>To have a complete view of the wall (it may be difficult to visualize large walls), click on <kbd>View</kbd>&rarr;<kbd>Full view</kbd>. In this mode, the wall will no longer be editable. To be able to edit it again, click on <i class='fas fa-crosshairs'></i> or on <kbd>View</kbd>&rarr;<kbd>Standard view</kbd>.</p><p>To display sticky notes as lists, click on <kbd>View</kbd>&rarr;<kbd>List mode</kbd>. In this mode, not all operations are available, but you can still edit and create post-its. To return to the standard view use <kbd>View</kbd>&rarr;<kbd>Sticky notes mode</kbd>. You can also choose a particular mode for each table cell via the menu located at the top right of each cell.</p><p>You can export a wall with the <kbd>Wall</kbd>&rarr;<kbd>Export</kbd> menu. Depending on its content, the export size can be substantial! Click on <kbd>Wall</kbd>&rarr;<kbd>Import...</kbd> to re-import it.</p><p>At any time you can clone a wall using the <kbd>Wall</kbd>&rarr;<kbd>Clone...</kbd> menu. Depending on its content, the cloning of a wall can take a long time!</p>")?>
            </div>
          </div>
        </div>

        <div class="card">
          <div class="card-header">
            <h2 class="mb-0"><button class="btn btn-link" type="button" data-toggle="collapse" data-target="#collapse2" aria-expanded="false"><i class="fas fa-sticky-note fa-lg fa-fw"></i> <?=_("Sticky notes management")?></button></h2>
          </div>
          <div id="collapse2" class="collapse" data-parent="#ug-accordion">
            <div class="card-body">
      <?=_("<p>To create a sticky note, double-click inside a cell. You can then move it anywhere on the wall. The creation date appears at the bottom left.</p><p>By clicking on the menu <i class='far fa-caret-square-down'></i> of the sticky note you access the different icons allowing you to modify it:</p><dl class='row'><dt class='col-sm-2'><i class='fas fa-sm fa-times fa-fw'></i></dt><dd class='col-sm-9'>Delete the sticky note. All associated documents will be deleted.</dd><dt class='col-sm-2'><i class='fas fa-sm fa-edit fa-fw'></i></dt><dd class='col-sm-9'>Edit title and content.</dd><dt class='col-sm-2'><i class='fas fa-sm fa-tags fa-fw'></i></dt><dd class='col-sm-9'>Manage associated tags. If some tags are already associated, their icons appear on the right. You can also click on them to manage them.</dd><dt class='col-sm-2'><i class='fas fa-sm fa-palette fa-fw'></i></dt><dd class='col-sm-9'>Change the color.</dd><dt class='col-sm-2'><i class='fas fa-sm fa-hourglass-end fa-fw'></i></dt><dd class='col-sm-9'>Set the deadline and email notification. You can then reset it by clicking on <i class='fas fa-xs fa-times-circle fa-fw'></i>. When the deadline arrives, the bottom of the sticky note will appear in red.</dd><dt class='col-sm-2'><i class='fas fa-sm fa-paperclip fa-fw'></i></dt><dd class='col-sm-9'>Manage associated documents. If documents are already associated, an icon appears at the top left. You can also click on this icon to manage them.</dd><dt class='col-sm-2'><i class='fas fa-sm fa-bezier-curve fa-fw'></i></dt><dd class='col-sm-9'>Manage relationships between sticky notes.</dd></dl><p>Changing the title of a sticky note can also be done more directly by clicking on it.</p><p>By default, to respect your privacy, the external content (images or videos) of the sticky notes are blocked so that your IP address is not disclosed. The <kbd>View</kbd>&rarr;<kbd>Block/Show external content</kbd> menu allows you to control this behavior.</p><p>You can search in the sticky notes of the current wall by clicking on <kbd>Edit</kbd>&rarr;<kbd>Search...</kbd>. Sticky notes containing the searched string will be highlighted.</p><p>It is possible to filter the sticky notes using the filtering tool. To display it, click on <kbd>View</kbd>&rarr;<kbd>Filters</kbd>. You can filter by tags and colors.</p><p>On some little screens it can be useful to use the <kbd>Arrows</kbd> tool in order to move around the wall.</p>")?>
            </div>
          </div>
        </div>

        <div class="card">
          <div class="card-header">
            <h2 class="mb-0"><button class="btn btn-link" type="button" data-toggle="collapse" data-target="#collapse3" aria-expanded="false"><i class="fas fa-share fa-lg fa-fw"></i> <?=_("Sharing management")?></button></h2>
          </div>
          <div id="collapse3" class="collapse" data-parent="#ug-accordion">
            <div class="card-body">
      <?=_("<p>In order to share a wall, you must first create user groups. To create a group, click on <kbd>Wall</kbd>&rarr;<kbd>Share...</kbd>.</p><p>There are two types of groups:</p><dl class='row'><dt class='col-sm-3'>Dedicated group</dt><dd class='col-sm-9'>This type of group will only be available for the current wall.</dd><dt class='col-sm-3'>Generic group</dt><dd class='col-sm-9'>This type of group will be available for the current wall but also for all your other walls.</dd></dl><p>Once you have created a group, click on the icon <i class='fas fa-user-friends'></i> to manage users. Enter their name in the search field. Users appearing in the list can be added to the group by clicking on <i class='fas fa-plus-circle'></i>.</p><p>To remove users, click on <i class='fas fa-minus-circle'></i>.</p><p>For the moment you have only done group management. Now you have to attach these groups to the current wall to define the type of sharing. Click on the button <button type='button' class='btn btn-secondary btn-xs'><i class='fas fa-plus-circle'></i> Share</button> of the group line. You can use the following types of sharing:</p><dl class='row'><dt class='col-sm-2'><i class='fas fa-sm fa-eye fa-fw'></i></dt><dd class='col-sm-9'>Sharing is read-only. Users will be able to view the wall but not modify it.</dd><dt class='col-sm-2'><i class='fas fa-sm fa-edit fa-fw'></i></dt><dd class='col-sm-9'>Sharing allows users to create and modify sticky notes (their own and those of others).</dd><dt class='col-sm-2'><i class='fas fa-sm fa-shield-alt fa-fw'></i></dt><dd class='col-sm-9'>Sharing allows users to create sticky notes and administer the wall. It is the sharing that gives the most rights to users. They will also have partial management of the groups.</dd></dl><p>If you want the users of the group to be notified of the sharing, select the <i>Send an email to notify group users</i> option before validating.</p><p>By the way, you can chat with other wall users by clicking <kbd>View</kbd>&rarr;<kbd>Chat room</kbd>. Chat rooms are wall specific, i.e. you can have as many as you have open walls.</p>")?>
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
