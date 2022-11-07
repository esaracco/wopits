<?php
  require(__DIR__.'/../../app/prepend.php');
  $news = Wopits\Helper::getIncludeContent(__DIR__.'/../whats_new/latest.php');
  $i = 0;
?>
<div class="modal m-fullscreen" id="userGuidePopup" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-hands-helping fa-lg fa-fw"></i> <?=_("User guide")?></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body justify">
        <div class="accordion" id="ug-accordion">
          <div class="accordion-item">
            <h2 class="accordion-header" id="h<?=$i?>">
              <div class="accordion-button" data-bs-toggle="collapse" data-bs-target="#c<?=$i?>" aria-expanded="true" aria-controls="c<?=$i?>"><i class="fas fa-bullhorn fa-lg fa-fw"></i> <?=_("News")?></div>
            </h2>
            <div id="c<?=$i?>" class="accordion-collapse collapse show" data-bs-parent="#ug-accordion" aria-labelledby="h<?=$i?>">
              <div class="accordion-body">
                <?php
                  if (trim($news)) {
                    echo '<div class="latest-dt">'.
                           _("Update").': '.WPT_LAST_UPDATE.'</div>'.$news;
                  }
                  include(__DIR__.'/../whats_new/mostRecents.php');
                ?>
              </div>
            </div>
          </div>
          <?php ++$i ?>
          <div class="accordion-item">
            <h2 class="accordion-header" id="h<?=$i?>">
              <div class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#c<?=$i?>" aria-expanded="false" aria-controls="c<?=$i?>"><i class="fas fa-border-all fa-lg fa-fw"></i> <?=_("Walls")?></div>
            </h2>
            <div id="c<?=$i?>" class="accordion-collapse collapse" data-bs-parent="#ug-accordion" aria-labelledby="h<?=$i?>">
              <div class="accordion-body">
                <?=_("<p>To create a wall, click on <kbd>Wall</kbd>&rarr;<kbd>New...</kbd> and enter its name. If some walls are already open, you can use the shortcut <i class='fas fa-plus'></i> to the right of the tab titles.</p><p>To create a wall occupying the entire screen, without columns or rows, uncheck the <kbd>With grid</kbd> checkbox option.</p><p>Once the wall is created, you can open the rows and columns menu by clicking on <i class='far fa-caret-square-down'></i>. You have the option to rename the element, associate an image, shift left, right, top and up, or delete it. Editing header titles can also be done by clicking on them.</p><p>You can edit the name and description of a wall by clicking on its tab title.</p><p>To add a row or column, use the icons <i class='fas fa-grip-lines'></i> and <i class='fas fa-grip-lines-vertical'></i> of the wall's menu.</p><p>To open walls already created or shared with you by other users, click on <kbd>Wall</kbd>&rarr;<kbd>Open...</kbd>.</p><p>Your own walls always appear with the icon <i class='fas fa-shield-alt'></i>. Walls shared with you by other users may appear with the following icons:<dl class='row'><dt class='col-sm-2'><i class='fas fa-sm fa-eye fa-fw'></i></dt><dd class='col-sm-9'>Sharing is read-only.</dd><dt class='col-sm-2'><i class='fas fa-sm fa-edit fa-fw'></i></dt><dd class='col-sm-9'>Sharing allows you to create and modify notes.</dd><dt class='col-sm-2'><i class='fas fa-sm fa-shield-alt fa-fw'></i></dt><dd class='col-sm-9'>Sharing allows you to create notes and manage the wall. It's the sharing that gives you the most rights. You will also have partial management of dedicated groups.</dd></dl><p>If other users have opened the wall you are viewing, an icon <i class='fas fa-user-friends'></i> appears at the bottom of the wall's menu.</p><p>You can open multiple walls at the same time. They will be automatically reloaded when you return to the application.</p><p>To have a complete view of the wall (it may be difficult to visualize large walls), click on <kbd>View</kbd>&rarr;<kbd>Full view</kbd>. Some features are not available in this mode. To return to the standard view, click on the <i class='fas fa-crosshairs'></i> button or use <kbd>View</kbd>&rarr;<kbd>Standard view</kbd>.</p><p>To display notes as lists, click the <i class='fas fa-tasks'></i> icon on the wall's menu. In this mode, not all operations are available, but you can still edit and create notes. To return to the standard view use the <i class='fas fa-sticky-note'></i> icon. You can also choose a particular mode for each cell by clicking on its own menu.</p><p>You can export a wall with the <kbd>Wall</kbd>&rarr;<kbd>Export</kbd> menu. Click on <kbd>Wall</kbd>&rarr;<kbd>Import...</kbd> to re-import it.</p><p>At any time you can clone a wall using the <kbd>Wall</kbd>&rarr;<kbd>Clone...</kbd> menu.</p>")?>
              </div>
            </div>
          </div>
          <?php ++$i?>
          <div class="accordion-item">
            <h2 class="accordion-header" id="h<?=$i?>">
              <div class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#c<?=$i?>" aria-expanded="false" aria-controls="c<?=$i?>"><i class="fas fa-sticky-note fa-lg fa-fw"></i> <?=_("Notes")?></div>
            </h2>
            <div id="c<?=$i?>" class="accordion-collapse collapse" data-bs-parent="#ug-accordion" aria-labelledby="h<?=$i?>">
              <div class="accordion-body">
                <?=_("<p>To create a note, double-click inside a cell. You can then move it anywhere on the wall. The creation date appears at the bottom left.</p><p>By clicking on the main menu of the note <i class='far fa-caret-square-down'></i> you will have the choice between the following features:</p><dl class='row'><dt class='col-sm-2'><i class='fas fa-sm fa-trash fa-fw'></i></dt><dd class='col-sm-9'>Delete the note.</dd><dt class='col-sm-2'><i class='fas fa-sm fa-edit fa-fw'></i></dt><dd class='col-sm-9'>Edit title, content, and the percentage of progress of the task.</dd><dt class='col-sm-2'><i class='fas fa-sm fa-palette fa-fw'></i></dt><dd class='col-sm-9'>Change the color.</dd><dt class='col-sm-2'><i class='fas fa-sm fa-hourglass-end fa-fw'></i></dt><dd class='col-sm-9'>Set the deadline and email notification. You can then reset it by clicking on <i class='fas fa-xs fa-times-circle fa-fw'></i>. When the deadline arrives, the bottom of the note will be displayed in red.</dd><dt class='col-sm-2'><i class='fas fa-sm fa-tags fa-fw'></i></dt><dd class='col-sm-9'>Manage tags. If some tags are already associated, their icons appear on the right.</dd><dt class='col-sm-2'><i class='fas fa-sm fa-bezier-curve fa-fw'></i></dt><dd class='col-sm-9'>Add relation.</dd></dl><p>By clicking on the secondary menu on the top of the note you will have the choice between the following features:<dl class='row'><dt class='col-sm-2'><i class='fas fa-sm fa-paperclip fa-fw'></i></dt><dd class='col-sm-9'>Manage attached files.</dd><dt class='col-sm-2'><i class='fas fa-sm fa-comments fa-fw'></i></dt><dd class='col-sm-9'>View comments. If you have write access to the wall, you can add comments. Use <i class='fas fa-sm fa-at'></i> in the editing window to refer to another user.</dd></dl></p><p>Changing the note's title can also be done by clicking on it.</p><p>By default, to respect your privacy, external content (images and videos) are filtered so that your IP address is not disclosed. The <i class='fas fa-umbrella'></i> icon of the wall's menu indicate that you are protected. Just click on it to allow external content to be displayed.</p><p>You can search in the notes of the current wall by using the <i class='fas fa-search'></i> icon of the wall's menu. Notes containing the searched string will be highlighted.</p><p>It is possible to filter the notes by using the filtering tool. To display it, click on <kbd>View</kbd>&rarr;<kbd>Filters</kbd>. You can filter by tags and colors.</p><p>On some little screens it can be useful to use the <kbd>Arrows</kbd> tool in order to move around the wall.</p>")?>
              </div>
            </div>
          </div>
          <?php ++$i?>
          <div class="accordion-item">
            <h2 class="accordion-header" id="h<?=$i?>">
              <div class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#c<?=$i?>" aria-expanded="false" aria-controls="c<?=$i?>"><i class="fas fa-share fa-lg fa-fw"></i> <?=_("Sharing")?></div>
            </h2>
            <div id="c<?=$i?>" class="accordion-collapse collapse" data-bs-parent="#ug-accordion" aria-labelledby="h<?=$i?>">
              <div class="accordion-body">
                <?=_("<p>In order to share a wall, you must first create user groups. To create a group, use the <i class='fas fa-share'></i> icon of the wall's menu.</p><p>There are two types of groups:</p><dl class='row'><dt class='col-sm-3'>Dedicated group</dt><dd class='col-sm-9'>This type of group will only be available for the current wall.</dd><dt class='col-sm-3'>Generic group</dt><dd class='col-sm-9'>This type of group will be available for the current wall but also for all your other walls.</dd></dl><p>Once you have created a group, click on the icon <i class='fas fa-user-friends'></i> to manage users. Enter their name in the search field. Users appearing in the list can be added to the group by clicking on <i class='fas fa-plus-circle'></i>.</p><p>To remove users, click on <i class='fas fa-minus-circle'></i>.</p><p>For the moment you have only done group management. Now you have to attach these groups to the current wall to define the type of sharing. Click on the button <button type='button' class='btn btn-secondary btn-xs'><i class='fas fa-plus-circle'></i> Share</button> of the group line. You can use the following types of sharing:</p><dl class='row'><dt class='col-sm-2'><i class='fas fa-sm fa-eye fa-fw'></i></dt><dd class='col-sm-9'>Sharing is read-only. Users will be able to view the wall but not modify it.</dd><dt class='col-sm-2'><i class='fas fa-sm fa-edit fa-fw'></i></dt><dd class='col-sm-9'>Sharing allows users to create and modify notes (their own and those of others).</dd><dt class='col-sm-2'><i class='fas fa-sm fa-shield-alt fa-fw'></i></dt><dd class='col-sm-9'>Sharing allows users to create notes and administer the wall. It is the sharing that gives the most rights to users. They will also have partial management of dedicated groups.</dd></dl><p>If you want the users of the group to be notified of the sharing, select the <i>Send an email to notify group users</i> option before validating.</p><p>By the way, you can chat with other wall users by clicking <kbd>View</kbd>&rarr;<kbd>Chat room</kbd>. Chat rooms are wall specific, i.e. you can have as many as you have open walls.</p><p>Sharing will not be available if you checked the invisible mode option in your account page.</p>")?>
              </div>
            </div>
          </div>
          <?php ++$i?>
          <div class="accordion-item">
            <h2 class="accordion-header" id="h<?=$i?>">
              <div class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#c<?=$i?>" aria-expanded="false" aria-controls="c<?=$i?>"><i class="fas fa-bolt fa-lg fa-fw"></i> <?=_("Meta menu")?></div>
            </h2>
            <div id="c<?=$i?>" class="accordion-collapse collapse" data-bs-parent="#ug-accordion" aria-labelledby="h<?=$i?>">
              <div class="accordion-body">
                <?=_("<p>To act on several notes at the same time, select them with <kbd>ctrl+click</kbd>. A new floating menu will appear. Select as many notes as you want among all your opened walls.</p><p>Depending on your rights to the walls, you will have the choice between the following options:</p><dl class='row'><dt class='col-sm-2'><i class='fas fa-sm fa-paste fa-fw'></i></dt><dd class='col-sm-9'>Copy the selected notes by <kbd>ctrl+click</kbd> then on the destination cell.</dd><dt class='col-sm-2'><i class='fas fa-sm fa-cut fa-fw'></i></dt><dd class='col-sm-9'>Move the selected notes by <kbd>ctrl+click</kbd> then on the destination cell.</dd><dt class='col-sm-2'><i class='fas fa-sm fa-palette fa-fw'></i></dt><dd class='col-sm-9'>Change selected notes color.</dd><dt class='col-sm-2'><i class='fas fa-sm fa-trash fa-fw'></i></dt><dd class='col-sm-9'>Delete selected notes.</dd></dl><p>It is also possible to select the desired option using the following key combinations:<dl class='row'><dt class='col-sm-2 text-nowrap'>CTRL+C</dt><dd class='col-sm-9'>Will select the <i class='fas fa-sm fa-paste fa-fw'></i> copy option.</dd><dt class='col-sm-2 text-nowrap'>CTRL+X</dt><dd class='col-sm-9'>Will select the <i class='fas fa-sm fa-cut fa-fw'></i> move option.</dd><dt class='col-sm-2 text-nowrap'>DEL</dt><dd class='col-sm-9'>Will delete selected notes.</dd></dl></p><p>Instead of using <kbd>ctrl+click</kbd> to paste the notes, you can also use <kbd>ctrl+v</kbd> key combination after moving the cursor over the target cell.</p>")?>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
