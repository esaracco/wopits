<?php include (__DIR__.'/../app/header-common.php')?>
<body>

  <div id="popup-loader" class="layer">
    <div id="loader">
      <div class="progress"></div>
      <i></i>
      <span></span>
      <button type="button" class="btn btn-xs btn-secondary">
        <?=_("Stop")?>
      </button>
    </div>
  </div>
  <div id="tpick"></div>
  <div id="cpick"></div>
  <div id="upload-layer" class="layer"></div>

  <!-- MSG CONTAINER -->
  <div id="msg-container" class="position-absolute top-0 start-50 translate-middle-x"></div>

  <!-- MENU main -->
  <nav class="navbar navbar-expand-sm fixed-top navbar-expand-lg">
    <div id="umsg"><span class="wpt-badge">0</span></div>
    <a href="#" class="navbar-brand fw-bold"><i class="fas fa-user-circle fa-lg" id="account"></i><i class="fas fa-eye-slash fa-xs invisible-mode" title="<?=_("Invisible mode: sharing is not possible and no one can see you")?>"></i> | <i class="fas fa-power-off fa-xs" id="logout"></i></a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#main-menu" aria-controls="main-menu" aria-expanded="false"><i class="fas fa-ellipsis-v"></i></button>
    <div class="container-fluid">
    <div id="main-menu" class="collapse navbar-collapse">
      <ul class="navbar-nav mr-auto">

        <!--Wall-->
        <li class="nav-item dropdown">
          <div id="dropdownWall" href="#" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" class="nav-link dropdown-toggle"><i class="fa-fw fas fa-border-all"></i> <?=_("Wall")?></div>
          <ul class="dropdown-menu border-0 shadow">
            <li data-action="new"><a class="dropdown-item" href="#"><i class="fa-fw fas fa-plus"></i> <?=_("New...")?></a></li>
            <li data-action="open"><a class="dropdown-item" href="#"><i class="fa-fw fas fa-folder-open"></i> <?=_("Open...")?></a></li>
            <li data-action="import"><a class="dropdown-item" href="#"><i class="fa-fw fas fa-file-import"></i> <?=_("Import...")?></a></li>
            <li class="dropdown-divider"></li>
            <li data-action="clone"><a class="dropdown-item disabled" href="#"><i class="fa-fw fas fa-clone"></i> <?=_("Clone")?></a></li>
            <li data-action="export"><a class="dropdown-item disabled" href="#"><i class="fa-fw fas fa-file-export"></i> <?=_("Export")?></a></li>
            <li class="dropdown-divider"></li>
            <li data-action="close-walls"><a class="dropdown-item disabled" href="#"><i class="fa-fw fas fa-times"></i> <?=_("Close the walls")?></a></li>
            <li data-action="delete"><a class="dropdown-item disabled" href="#"><i class="fa-fw fas fa-trash"></i> <?=_("Delete")?></a></li>
          </ul>
        </li>
        <!--View-->
        <li class="nav-item dropdown">
          <div id="dropdownView" href="#" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" class="nav-link dropdown-toggle disabled"><i class="fa-fw fas fa-desktop display"></i> <?=_("Display")?></div>
          <ul class="dropdown-menu border-0 shadow display-section">
            <li data-action="zoom+"><a class="dropdown-item" href="#"><i class="fa-fw fas fa-search-plus"></i> <?=_("Zoom +")?></a></li>
            <li data-action="zoom-"><a class="dropdown-item" href="#"><i class="fa-fw fas fa-search-minus"></i> <?=_("Zoom -")?></a></li>
            <li data-action="zoom-screen"><a class="dropdown-item" href="#"><i class="fa-fw fas fa-vector-square"></i> <?=_("Full view")?></a></li>
            <li data-action="zoom-normal"><a class="dropdown-item disabled" href="#"><i class="fa-fw fas fa-crosshairs"></i> <?=_("Standard view")?></a></li>
            <li class="dropdown-divider"></li>
            <li data-action="chat"><a class="dropdown-item" href="#"><input type="checkbox" class="form-check-input"> <i class="fa-fw fas fa-comments"></i> <?=_("Chat room")?></a></li>
            <li data-action="filters"><a class="dropdown-item" href="#"><input type="checkbox" class="form-check-input"> <i class="fa-fw fas fa-filter"></i> <?=_("Filters")?></a></li>
          </ul>
        </li>

        <!--Settings-->
        <li class="nav-item" data-action="settings"><a href="#" class="nav-link"><i class="fa-fw fas fa-sliders-h settings"></i> <?=_("Settings")?></a></li>

        <!--Help-->
        <li class="nav-item dropdown">
          <div id="dropdownHelp" href="#" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" class="nav-link dropdown-toggle"><i class="fa-fw fas fa-life-ring display"></i> <?=_("Help")?></div>
          <ul class="dropdown-menu border-0 shadow">
            <li data-action="about"><a class="dropdown-item" href="#"><i class="fa-fw fas fa-info-circle"></i> <?=_("About")?></a></li>
            <li data-action="user-guide"><a class="dropdown-item" href="#"><i class="fa-fw fas fa-hands-helping"></i> <?=_("User guide")?></a></li>
          </ul>
        </li>

      </ul>
    </div>
    </div>
  </nav>

  <!--<WPTPROD-inject-WPT_POPUPS['index']/>-->
  <?php
    //<WPTPROD-remove>
      foreach (WPT_POPUPS['index'] as $popup)
        include (__DIR__."/../app/inc/popups/$popup.php");
    //</WPTPROD-remove>
  ?>

  <!-- WELCOM MSG -->
  <div id="welcome" data-action="new">
    <p><?=_("Create walls, and let ideas shine!")?></p>
    <img src="/img/wopits.png?<?=$version?>">
  </div>

  <!-- MAIN CONTENT -->
  <div id="page-main">
    <!-- WALLS tabs -->
    <div><nav class="nav nav-tabs walls"><a href="#" data-action="new"><i class="fas fa-plus"></i></a></nav></div>
    <!-- WALLS content -->
    <div id="walls"><div class="tab-content walls"></div></div>
  </div>

</body>
</html>
