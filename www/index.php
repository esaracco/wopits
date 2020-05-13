<?php include (__DIR__.'/../app/header-common.php')?>
<body>

  <!-- LOADER -->
  <div id="popup-loader" class="layer">
    <div id="loader"><i class="fas fa-cog fa-spin fa-lg"></i> <span><?=_("Please wait")?>...</span></div>
  </div>

  <!-- upload LAYER -->
  <div id="upload-layer" class="layer"></div>

  <div id="normal-display-btn" data-content="<?=_("Back to standard view")?>"><i class="fas fa-crosshairs fa-2x"></i></div>

  <!-- MSG CONTAINER -->
  <div id="msg-container"></div>

  <!-- COLOR PICKER -->
  <div class="color-picker"></div>

  <!-- TAG PICKER -->
  <div class="tag-picker"></div>

  <!-- MENU main -->
  <nav class="navbar navbar-expand-sm bg-dark navbar-dark fixed-top navbar-expand-lg shadow-sm">
    <a href="#" class="navbar-brand font-weight-bold"><i class="fas fa-user-circle fa-lg" id="account"></i> | <i class="fas fa-power-off fa-xs" id="logout"></i></a>
    <button type="button" data-toggle="collapse" data-target="#main-menu" aria-controls="navbars" aria-expanded="false" class="navbar-toggler"><span class="navbar-toggler-icon"></span></button>
    <div id="main-menu" class="collapse navbar-collapse">
      <ul class="navbar-nav mr-auto">

        <!--Wall-->
        <li class="nav-item dropdown">
          <a id="dropdownWall" href="#" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" class="nav-link dropdown-toggle"><i class="fa-fw fas fa-border-all"></i> <?=_("Wall")?></a>
          <ul class="dropdown-menu border-0 shadow">
            <li data-action="new"><a class="dropdown-item" href="#"><i class="fa-fw fas fa-plus"></i> <?=_("New...")?></a></li>
            <li data-action="open"><a class="dropdown-item" href="#"><i class="fa-fw fas fa-folder-open"></i> <?=_("Open...")?></a></li>
            <li data-action="import"><a class="dropdown-item" href="#"><i class="fa-fw fas fa-file-import"></i> <?=_("Import...")?></a></li>
            <li class="dropdown-divider"></li>
            <li data-action="clone"><a class="dropdown-item disabled" href="#"><i class="fa-fw fas fa-clone"></i> <?=_("Clone")?></a></li>
            <li data-action="export"><a class="dropdown-item disabled" href="#"><i class="fa-fw fas fa-file-export"></i> <?=_("Export")?></a></li>
            <li class="dropdown-divider"></li>
            <li data-action="share"><a class="dropdown-item disabled" href="#"><i class="fa-fw fas fa-share"></i> <?=_("Share...")?></a></li>
            <li class="dropdown-divider"></li>
            <li data-action="close-walls"><a class="dropdown-item disabled" href="#"><i class="fa-fw fas fa-times"></i> <?=_("Close the walls")?></a></li>
            <li data-action="delete"><a class="dropdown-item disabled" href="#"><i class="fa-fw fas fa-trash"></i> <?=_("Delete")?></a></li>
          </ul>
        </li>

        <!--Edit-->
        <li class="nav-item dropdown">
          <a id="dropdownEdit" href="#" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" class="nav-link dropdown-toggle disabled"><i class="fa-fw far fa-list-alt"></i> <?=_("Edit")?></a>
          <ul class="dropdown-menu border-0 shadow">
            <li data-action="add-col"><a class="dropdown-item disabled" href="#"><i class="fa-fw fas fa-grip-lines-vertical"></i> <?=_("Add column")?></a></li>
            <li data-action="add-row"><a class="dropdown-item disabled" href="#"><i class="fa-fw fas fa-grip-lines"></i> <?=_("Add row")?></a></li>
            <li class="dropdown-divider"></li>
            <li data-action="search"><a class="dropdown-item disabled" href="#"><i class="fa-fw fas fa-search"></i> <?=_("Search...")?></a></li>
            <li data-action="view-properties"><a class="dropdown-item disabled" href="#"><i class="fa-fw fas fa-list"></i> <?=_("Properties...")?></a></li>
          </ul>
        </li>

        <!--View-->
        <li class="nav-item dropdown">
          <a id="dropdownView" href="#" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" class="nav-link dropdown-toggle disabled"><i class="fa-fw fas fa-desktop display"></i> <?=_("View")?></a>
          <ul class="dropdown-menu border-0 shadow display-section">
            <li data-action="zoom+"><a class="dropdown-item" href="#"><i class="fa-fw fas fa-search-plus"></i> <?=_("Zoom +")?></a></li>
            <li data-action="zoom-"><a class="dropdown-item" href="#"><i class="fa-fw fas fa-search-minus"></i> <?=_("Zoom -")?></a></li>
            <li data-action="zoom-screen"><a class="dropdown-item" href="#"><i class="fa-fw fas fa-vector-square"></i> <?=_("Full view")?></a></li>
            <li data-action="zoom-normal"><a class="dropdown-item disabled" href="#"><i class="fa-fw fas fa-crosshairs"></i> <?=_("Standard view")?></a></li>
            <li class="dropdown-divider"></li>
            <li data-action="chatroom"><a class="dropdown-item" href="#"><input type="checkbox"> <i class="fa-fw fas fa-comments"></i> <?=_("Chat room")?></a></li>
            <li data-action="filters"><a class="dropdown-item" href="#"><input type="checkbox"> <i class="fa-fw fas fa-filter"></i> <?=_("Filters")?></a></li>
            <li data-action="arrows"><a class="dropdown-item" href="#"><input type="checkbox"> <i class="fa-fw fas fa-arrows-alt"></i> <?=_("Arrows")?></a></li>
          </ul>
        </li>

        <!--Settings-->
        <li class="nav-item" data-action="settings"><a href="#" class="nav-link"><i class="fa-fw fas fa-sliders-h settings"></i> <?=_("Settings")?></a></li>

        <!--Help-->
        <li class="nav-item dropdown">
          <a id="dropdownHelp" href="#" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" class="nav-link dropdown-toggle"><i class="fa-fw fas fa-life-ring display"></i> <?=_("Help")?></a>
          <ul class="dropdown-menu border-0 shadow">
            <li data-action="about"><a class="dropdown-item" href="#"><i class="fa-fw fas fa-info-circle"></i> <?=_("About")?></a></li>
            <li data-action="user-guide"><a class="dropdown-item" href="#"><i class="fa-fw fas fa-hands-helping"></i> <?=_("User guide")?></a></li>
          </ul>
        </li>

      </ul>
    </div>
  </nav>

  <?php
    foreach (WPT_POPUPS['index'] as $popup)
      include (__DIR__."/../app/inc/popups/$popup.php");
  ?>

  <!-- WELCOM MSG -->
  <div id="welcome" data-action="new">
    <p><?=_("Create your walls of post-its, and let your ideas shine!")?></p>
    <img src="img/wopits.png?<?=$version?>">
  </div>

  <!-- MAIN CONTENT -->
  <div id="page-main">
    <!-- WALLS tabs -->
    <nav class="nav nav-tabs walls"><a href="#" data-action="new"><i class="fas fa-plus"></i></a></nav>
    <!-- WALLS content -->
    <div id="walls"><div class="tab-content walls"></div></div>
  </div>

</body>
</html>
