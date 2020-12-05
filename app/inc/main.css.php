<?php
/**
  Main CSS file
*/

  require_once (__DIR__.'/../prepend.php');

?>
* {
  outline: none !important;
}

::placeholder {
  font-style:italic !important;
  opacity:0.4 !important;
}

img {
  image-orientation: from-image;
}

.custom-control-input:checked ~ .custom-control-label::before {
  color: #fff;
  border-color: #343a40;
  background-color: #343a40;
}

.ldap-msg {
  color:#a5a5a5;
  text-align:center;
}

.global-error {
  position:fixed;
  left:50%;
  top:40%;
  transform:translate(-50%, -50%);
}

#w-grid {
  margin-top:15px;
}

#aboutPopup .desc {
  color: #9d9d9d;
  font-size:0.9rem;
}

#aboutPopup .warning {
  font-size:0.9rem;
  border:1px solid silver;
  border-radius:5px;
  padding:10px;
}

#aboutPopup .warning h2 {
  width:100%;
  border-radius:3px;
  padding:3px;
  text-align:center;
  font-size:0.9rem;
  font-weight:bold;
}

#aboutPopup .btn {
  color:#fff !important;
}

.project-title {
  line-height:1.2rem;
  font-style:italic;
}

.project-title span {
  font-size:0.8rem !important;
}

.accordion {
  font-size:0.9rem;
}

.accordion p {
  margin-bottom:0.3rem;
}

.accordion dl {
  margin-top:0;
  margin-bottom:0;
}

kbd {
  padding:2px;
  font-size:0.7rem;
  background-color: #6c757d;
  border-color: #6c757d;
}

.accordion .btn-link {
  text-decoration:none !important;
  color:#555;
  width:100%;
  text-align:left;
}

.accordion button:focus {
  border-color:transparent !important;
}

.accordion .card-header {
  padding:0.5rem;
}

#popup-loader {
  display:none;
  background:rgba(52, 58, 64, 0.28);
  z-index:59000;  
}

#loader {
  background:#007aff;
  padding:10px;
  border-radius:5px;
  position: fixed;
  left: 50%;
  top: 50%;
  transform: translate(-50%, -50%);
  white-space:nowrap;
  z-index:60000;
  box-shadow: 0 0 25px 5px #b1b1b1;
  color:#fff;
}

#loader span {
  font-size:14px;
}

#loader .progress {
  display:none;
  height:14px;
  color:#fff;
  font-weight:bold;
  font-size:10px;
  padding-top:7px;
  padding-left:3px;
  background:orange;
  margin-bottom:5px;
}

#loader button {
  display:none;
  background: #e2747f !important;
  border-color: #da4e5b !important;
}

#loader button:hover {
  background:#da4e5b !important;
}

#normal-display-btn {
  display:none;
  z-index:1031;
  position:absolute;
  top:59px;
  left:13px;
  cursor:pointer;
  border:1px dashed #dee2e6;
  padding:8px;
  border-radius:5px;
  background:#f6f6f6;
}

#normal-display-btn i {
  vertical-align:middle;
}

#msg-container {
  position:fixed;
  left:50%;
  top:50%;
  transform:translate(-50%, -50%);
  z-index:60000;
  width:90%;
  max-width:400px;
}

.alert:not(.welcome) {
  min-width:300px;
  max-width:500px;
  position:fixed;
  left:50%;
  transform:translate(-50%, -50%);
  box-shadow: 0 0 20px #cecece;
  z-index:60000;
}

.alert-dismissible {
  padding-right:3rem;
}

.alert b {
  font-size:0.9rem;
}

.alert ul {
  padding-left:20px;
  margin-bottom:0;
}

.alert span {
  font-size:0.9rem;
}

.inner-page {
  margin-left:auto;
  margin-right:auto;
  margin-top:70px;
  max-width:800px;
  border:1px solid #cecece;
  border-radius:5px;
  height:100%;
  padding:10px;
}

#walls {
  overflow-x:auto !important;
  padding-left:10px;
  position:relative;
}

i.settings {
  display:inline-block;
  transform: rotate(90deg);
}

.cp {
  display:inline-block;
  vertical-align:top;
}

.cp .ui-widget.ui-widget-content {
  border:none;
}

.cp .ui-colorpicker-border {
  border-color:#ced4da;
}

#welcome {
  z-index:1;
  position: absolute;
  left: 50%;
  top: 50%;
  transform: translate(-50%, -50%);
  text-align:center;
  max-width:300px;
  font-style:italic;
  cursor:pointer;
}

#welcome img {
  width:131px;
  height:131px;
}

.tooltip {
  z-index:5050;
}

.tooltip-inner {
  text-align:left;
}

button[data-toggle="tooltip"].help {
  margin:0 0 0 5px;
  padding:0;
  border:0;
  background:transparent;
  position:absolute;
  top:-5px;
  cursor:help;
}

.navbar.fixed-top {
  box-shadow: 0 4px 10px -2px silver;
  z-index:5005;
}

#main-menu ul.dropdown-menu {
  overflow-x: hidden;
}

#main-menu a {
  font-size:0.9rem;
}

.menu {
  margin:auto;
}

.table-menu {
  display:none;
}

.nav-link {
  white-space:nowrap;
}

.nav-tabs.walls {
  position:relative;
  z-index:1030;
  text-align:center;
  padding-top:70px;
  background:white;
}

.nav-tabs.walls button.close {
  display:none;
  margin-top:-10px;
  margin-right:-15px;
}

.nav-tabs.walls > a {
  float:none;
  display:inline-block;
}

.nav-tabs.walls > a:last-child {
  margin-left:10px;
  color:#555;
}

.nav-tabs a span {
  color:#212529;
  opacity:0.3;
}

.nav-tabs.walls a.active button.close {
  display:block;
}

.nav-tabs a.active span {
  opacity:1;
}

.layer {
  display:none;
  position:fixed;
  top:0;
  left:0;
  width:100%;
  height:100%;
  z-index:5018;
}

.bs-popover-auto[x-placement^=bottom] .arrow:after {
  border-color: #555 transparent;
}

.bs-popover-auto[x-placement^=bottom] .popover-header::before {
  border-bottom:none;
}

.popover {
  z-index:5019;
}

.popover p {
  margin-bottom:0.5rem;
}

.popover-body ul {
  margin:0;
  padding:0 0 0 10px;
  list-style-type: square;
}

#upload-layer {
  background-color:transparent;
}

.nav.walls {
  display:none;
}

.input-group.required input {
  background-color:#fff1f1;
}

form span.required {
  color:#d45050;
  font-size:0.9rem;
  font-style:italic;
}

#logout {
  margin-left:5px;
}

#account {
  margin-right:5px;
}

#accountPopup textarea,
#wpropPopup textarea {
  height:100px;
}

#accountPopup .user-picture {
  cursor:pointer;
}

#accountPopup .delete {
  font-size:0.8rem;
  text-align:center;
}

#wpropPopup .reject-sharing {
  text-align: center;
}

.user-picture {
  display:inline-block;
  color:#555;
}

.content-centered {
  width:100%;
  text-align:center;
}

.user-picture img {
  border-radius:5px;
  box-shadow:0 0 10px 0 #555;
}

.user-picture .close.img-delete {
  border-top-left-radius:5px;
}

dd {
  opacity:0.8;
  font-size:0.9rem;
}

.navbar-brand {
  margin-right:20px !important;
}

.navbar-brand i:hover {
  opacity:0.6;
}

.navbar-brand i.invisible-mode {
  display:none;
  position:absolute;
  margin-top:-10px;
  margin-left:-15px;
  cursor:help;
}

.login-page .navbar-brand {
  background:none;
  color:#fff !important;
}

.login-page .themes * {
  float:left;
  margin-bottom:2px;
}

#settingsPopup .themes {
  margin-bottom: -8px;
}

select.timezone {
  max-width:300px;
}

.ui-datepicker select {
  background-color:#fff !important;
  font-size:0.8rem !important;
  margin-right:5px !important;
  opacity:0.8 !important;
  padding:3px !important;
}

.modal {
  z-index:5017;
}

.modal-body {
  overflow: auto;
}

.wall-size * {
  font-size:0.8rem;
  max-width:250px;
}

.wall-size .input-group-text {
  border:none;
}

.wall-size .form-control:first-of-type {
  border-top-left-radius:.2rem;
  border-bottom-left-radius:.2rem;
}

#postitAttachmentsPopup,
#swallPopup {
  z-index:5015;
}

.modal-backdrop {
  z-index:5003;
}

.tox {
  z-index:5018 !important;
}

.readonly,
.locked {
  pointer-events:none !important;
  opacity:.4 !important;
}

.invisible {
  pointer-events:none;
  opacity:0;
}

.list-group-item {
  cursor:pointer;
}

.list-group-item.readonly {
  font-style:italic;
}

.list-group-item.title {
  pointer-events: none;
  text-align:center;
  font-weight:bold;
  color:#555;
  background-color:#f1f1f1;
  height:35px !important;
  padding:5px !important;
}

.list-group-item span.ownername {
  font-size:0.8rem;
}

.list-group-item .right-icons {
  position:absolute;
  right:0;
  top:3px;
}

.list-group-item .right-icons button {
  float:left;
  font-size:1.5rem;
  background-color:transparent;
  border:0;
  margin:0;
  padding:0;
}

.list-group-item .item-infos {
  position:absolute;
  left:60px;
  bottom:0;
  font-size:0.8rem;
  font-style:italic;
  opacity:0.6;
  display:inline-block;
  text-overflow: ellipsis !important;
  overflow:hidden !important;
}

.list-group-item .item-infos span:not(:last-child):after {
  content: " - ";
}

.list-group .item-infos {
  max-width:70% !important;
}

.list-group .list-group-item-action.first {
  border-width:1px;
  border-top-left-radius: .25rem;
  border-top-right-radius: .25rem;
}

.list-group .list-group-item-action.last {
  border-bottom-left-radius: .25rem;
  border-bottom-right-radius: .25rem;
}

#owallPopup .list-group-item .item-infos {
  left:50px;
}

#owallPopup .list-group-item {
  height:55px;
}

#owallPopup .list-group-item .custom-checkbox {
  display:inline-block;
  top:-1px;
}

#owallPopup .btn-clear {
  position:absolute;
  left:50%;
  right:50%;
  z-index:2;
  width:30px;
  height:30px;
  margin-left:-30px;
  margin-top:-34px;
}

.btn-circle {
  border-radius: 50% !important;
  text-align: center;
  vertical-align: middle;
  box-shadow: 0 0 3px 1px #989898;
}

.btn-clear .fa-broom {
  margin-left:-3px;
}

.dropdown-item.disabled i {
  color: #6c757d;
  pointer-events: none;
  background-color: transparent;
}

#main-menu .dropdown-item {
  font-size:0.9rem;
}

#main-menu .dropdown-divider {
  margin:0.20rem 0;
}

.wall {
  border-collapse: collapse;
  margin-left:auto;
  margin-right:auto;
  margin-top:10px;
}

.wall th {
  vertical-align:top;
  padding:10px;
  background:#f1f1f1;
  text-align:center;
  white-space:nowrap;
  font-size:14px;
  border-right:1px solid #dfdfdf;
}

.wall th ul.navbar-nav {
  display:block;
  width:1px;
}

.wall th ul.submenu {
  text-align:left;
}

.wall th a.dropdown-toggle {
  color:#555;
}

.wall tbody tr.to-delete {
  background-color: yellow;
}

.wall thead th .title {
  margin:28px 20px 0 20px;
}

.wall td {
  min-height:200px;
  height:200px;
  min-width:300px;
  border-right:1px solid #dfdfdf;
}

.wall tr {
  border:1px solid #dfdfdf;
}

.wall tbody th {
  padding:0;
  min-width:51px;
  max-width:301px;
}

.wall tbody th .title {
  margin-top:28px;
  padding:0 10px 0 10px;
  text-overflow: ellipsis;
  overflow:hidden;
}

@keyframes blinker {
  from {opacity: .5}
  to {opacity: .1}
}
.blink {
  animation: blinker .5s infinite alternate;
}

.user-writing {
  pointer-events:none;
  position:absolute;
  z-index:4999;
  border-radius:10px;
  box-shadow: 0px 0px 6px #B2B2B2;
  background:#fff;
  color:#555;
  padding:10px 10px 0 10px;
  font-size:0.8rem;
  top:-70px;
  left:-22px;
}

.user-writing-min {
  pointer-events:none;
  position:absolute;
  z-index:4999;
  border-radius:25px;
  height:25px;
  width:25px;
  box-shadow:0px 0px 6px #B2B2B2;
  background:#fff;
  color:#555;
  line-height:25px;
  padding:10px 10px 0 10px;
  font-size:0.8rem;
  text-align:center;
}

.cell-list-mode li span.user-writing-min {
  opacity:1!important;
}

span.user-writing-min {
  position:absolute;
  padding:0!important;
}

.user-writing.main,
.user-writing-min.main {
  font-weight:bold;
}

.user-writing:not(.main) {
  top:auto;
  left:auto;
  width:100%;
  height:100%;
  text-align:center;
  font-size:2rem;
  border-radius:0;
  background:none;
  box-shadow:none;
}

.user-writing:not(.main) i {
  position: absolute;
  top: 45%;
  left: 50%;
  transform:translate(-50%,-50%);
  text-shadow: 0px 0px 6px #B2B2B2;
  padding:10px;
  width:60px;
  height:60px;
  opacity:.4;
  border-radius:60px;
  background:white;
  border:1px solid gray;
}

.postit.locked.main,
.postit-min.locked.main {
  opacity:.8!important;
}

a.active .user-writing {
  margin-top:30px;
  top:auto;
  left:auto;
}

th .user-writing {
  top:auto;
  left:auto;
  border-top-left-radius:0;
  font-weight:normal;
}

thead th .user-writing {
  margin-top:5px;
  margin-left:5px;
}

tbody th .user-writing {
  margin-left:14px;
  margin-top:15px;
}

td > .user-writing {
  bottom:10px;
  right:10px;
  top:auto;
  left:auto;
  border-bottom-right-radius:0;
}

.user-writing:after {
  background-color: #fff;
  box-shadow:-2px 2px 2px 0 rgba( 178, 178, 178, .4 );
  content:"";
  display:block;
  height:10px;
  left:10px;
  position:relative;
  top:5px;
  transform:rotate(315deg);
  width:10px;
}

.user-writing:not(.main):after {
  background:none;
  box-shadow:none;
}

a.active .user-writing:after {
  top:-34px;
  transform:rotate(135deg);
}

th .user-writing:after {
  left:-15px;
  top:-14px;
  transform:rotate(45deg);
}

td > .user-writing:after,
th .user-writing:after {
  visibility:hidden;
}

.editable.editing input {
  z-index:5001;
  border-top:none;
  border-right:4px solid silver;
  border-bottom:1px dashed gray;
  border-left:4px solid silver;
  background:transparent;
  font-weight:bold;
  white-space:nowrap;
}

th .editable.editing input {
  position:absolute;
  text-align:center;
  transform:translate(-50%);
  min-width:45px;
  padding:0 5px 0 5px;
}

.postit .editable.editing input {
  height:16px;
  min-width:30px;
  margin-left:-6px;
  padding:0 3px 0 3px;
  border-right:3px solid silver;
  border-left:3px solid silver;
}

#sandbox {
  width: auto;
  display: inline-block;
  visibility: hidden;
  position: fixed;
  overflow:auto;
  font-weight: bold;
}

th .close.img-delete,
.user-picture .close.img-delete {
  float:none !important;
  vertical-align:top;
  margin:0 -15px 0 0 !important;
  background:#fff;
  padding: 0 2px !important;
}

th .close.img-delete {
  visibility:visible !important;
  margin-top:0 !important;
  margin-right:-13px !important;
  font-size:0.9rem;
}

.wall th div.img {
  cursor:pointer;
  display:inline-block;
  margin-top:5px;
}

.wall:not([data-access="<?=WPT_WRIGHTS_ADMIN?>"]) th div.img {
  cursor:auto !important;
}

.wall tbody th div.img {
  margin-left:10px;
  margin-right:10px;
}

.wall th div.img img {
  max-width:100px;
  max-height:100px;
  border-radius:3px;
  box-shadow: 0 0 5px rgba(0, 0, 0, 0.6);
}

/* MS "clear field" X button on inputs */
input::-ms-clear {
  display: none;
}

.leader-line text {
  fill-opacity:0 !important;
  stroke-opacity:0 !important;
}

#userGuidePopup .latest-dt {
  font-style:italic;
  margin-top:-30px;
  font-size:0.7rem;
  float:right;
  background:#fff;
  padding:2px;
  border:1px solid #cecece;
  border-radius:5px;
  color:#555;
}

.newfeatures.latest p,
.dropdown-item:active,
.dropdown-item:hover {
  background-color:#f1f1f1;
  border-color:#cecece;
}

.dropdown-item:active {
  color:#555;
}

#plug-rabbit {
  position:absolute;
}

.plug-label {
  z-index:1000;
  position:absolute;
  cursor:pointer;
  border:1px solid #f3f3f3;
  background:#fff;
  border-radius:5px;
  white-space:nowrap;
  font-size:.8rem;
}

.plug-label:hover {
  z-index:1021;
}

[data-access="<?=WPT_WRIGHTS_RO?>"] .plug-label,
[data-access="<?=WPT_WRIGHTS_RO?>"] .plug-label a {
  cursor:auto !important;
}

.plug-label > a {
  color:#8c8c8c;
  text-decoration:none;
  padding:5px;
}

.plug-label:hover {
  color:#a2a2a2;
}

.plug-label i.fa-thumbtack {
  display:none;
  position:absolute;
  top:-5px;
  left:-2px;
  color:#bfbfbf;
  transform: rotate(-45deg);
}

li[data-action="position-auto"] {
  border-top:1px solid #e9ecef;
  margin-top:3px;
  padding-top:3px;
}

.wall thead th .submenu {
  margin-top:-19px;
  margin-left:-7px;
  height:1px;
}

.wall tbody th .submenu {
  margin-top:-9px;
  margin-left:2px;
  height:1px;
}

.cell-menu {
  position:absolute;
  right:-1px;
  top:-4px;
  color:#555;
  cursor:pointer;
  z-index:1011;
  padding:5px;
}

.cell-menu .wpt-badge {
  font-size:0.6rem;
  margin-left:-10px;
  margin-top:-1px;
}

.cell-menu .btn-circle {
  width:23px;
  height:23px;
  font-size:0.9rem;
}

.cell-menu .btn-circle i {
  position:relative;
  margin-left:-5px;
  top:-4px;
  font-size:.8rem;
}

.cell-menu .btn-circle {
  margin-left:-5px;
}

.cell-list-mode {
  width:100%;
  height:100%;
  position:absolute;
  left:0;
  top:0;
  font-size:14px;
}

.cell-list-mode ul {
  padding:0;
  overflow-y:auto;
}

.cell-list-mode li {
  padding:3px;
  border:1px solid transparent;
  border-bottom:1px dashed #a6a6a6;
  list-style:none;
  white-space: nowrap;
  overflow:hidden;
  text-overflow:ellipsis;
  cursor:pointer;
}

.cell-list-mode li span {
  cursor:move;
  padding:5px 10px;
  opacity:0.5;
}

.wall[data-access="<?=WPT_WRIGHTS_RO?>"] .cell-list-mode li span,
.cell-list-mode li:only-of-type span {
  cursor:pointer;
}

.postit-menu .submenu {
  margin-left:3px;
  margin-top:-6px;
  height:32px;
}

.submenu .dropdown-toggle::after {
  content: none;
}

.submenu .dropdown-menu {
  top:-25px !important;
  left:20px !important;
  padding:3px 0;
}

.submenu-link {
  position:absolute;
  z-index:5019;
}

.submenu-link .dropdown-menu {
  left:5px !important;
  min-width:auto;
}


.submenu .dropdown-menu * {
  font-size:0.8rem !important;
}

.submenu .dropdown-item {
  padding:0.20rem 0.70rem;
}

.submenu .dropdown-divider {
  margin:0.20rem 0;
}

.plug-label.submenu {
  height:auto;
}

.plug-label.submenu:hover {
  z-index:6000!important;
}

.plug-label.submenu .dropdown-menu {
  min-width:100px;
  border:none;
  box-shadow: 0 0 5px #cecece;
}

.submenu .dropdown-menu i.fa-edit {
  margin-left:2px;
  margin-right:-2px;
}

th .submenu .dropdown-menu {
  top:-28px !important;
  left:15px !important;
  z-index:5003!important;
}

.postit {
  z-index:1010;
  position:absolute !important;
  min-height:50px;
  min-width:160px;
  height:120px;
  width:180px;
  border-radius:0 3px 3px 3px;
  border:2px solid #cecece;
  box-shadow: 0 0 5px #cecece;
  -webkit-touch-callout: none;
  -webkit-user-select: none;
  -khtml-user-select: none;
  -moz-user-select: none;
  -ms-user-select: none;
  /*  user-select: none;*/
}

.postit.hover {
  z-index:5000!important;
}

.postit .btn-menu {
  position:absolute;
  padding:5px;
  z-index:93;
  top:-25px;
}

.postit .btn-menu i {
  cursor: pointer;
}

.postit .postit-header {
  position:absolute;
  font-size:14px;
  border-radius:5px 5px 5px 0;
  top:-21px;
  margin-left:-2px;
  padding:0 5px 0 30px;
  white-space: nowrap;
  border:2px solid #cecece;
  box-shadow: 0 0 5px #cecece;
  z-index:92;
}

.wall[data-access="<?=WPT_WRIGHTS_RO?>"] .postit .postit-header {
  padding-left:5px;
}

:not([class^='color']).selected {
  background-color:#cecece;
}

.postit.selected,
.postit.selected .postit-header {
  border-color:#343a40;
  border-style:dashed;
  box-shadow:0 0 25px 5px #b1b1b1;
}

.postit-min.selected {
  font-weight:bold;
  border-left:1px solid #343a40;
  border-right:1px solid #343a40;
  text-shadow: 1px 1px #b1b1b1;
}

.postit .attachmentscount i {
  background:transparent !important;
}

.postit .postit-header i {
  cursor:pointer;
  margin-right:10px;
}

.postit .postit-header .title {
  font-weight:bold;
  display:inline-block;
}

.postit-menu .btn-circle:last-child {
  margin-top:-2px;
  margin-left:-3px;
}

.postit-menu.right .btn-circle:last-child i {
  margin-left:-2px;
}

.postit-menu.right {
  display:none;
  position:absolute;
  min-width:250px;
  top:-53px;
  cursor:pointer;
  z-index:5000;
  left:-5px;
}

.postit-menu.right .btn-circle {
  float:left;
  width:32px;
  height:32px;
  margin-right:3px;
}

.postit-menu div.submenu {
  display:inline-block;
}

.postit-menu.left {
  display:none;
  position:absolute;
  max-width:70px;
  min-height:150px;
  top:-20px;
  left:-72px;
  cursor:pointer;
  z-index:5000;
}

.postit-menu.left .btn-circle {
  width:32px;
  height:32px;
  margin-bottom:3px;
  margin-left:3px;
}

.postit-menu.left .btn-circle:last-child {
  margin-left:0;
}

.postit-menu.left .btn-circle:last-child i {
  margin-left:-2px;
}

.postit .postit-tags {
  display:none;
  position:absolute;
  border-radius:0 5px 5px 0;
  width:15px;
  top:3px;
  right:-7px;
  padding: 0;
  cursor:pointer;
  line-height: 20px;
  z-index:92;
}

.postit .postit-tags i {
  margin-left:-2px;
  background:none !important;
  text-shadow: 1px 2px 3px rgba(0,0,0, 0.5);
}

.postit-edit [external-src] {
  background-color:#f1f1f1;
  border: 2px dashed #cecece;
  box-shadow: 0 0 10px #c5c5c5;
  width:50px;
  height:50px;
}

.postit-edit i.externalref {
  margin-left:-35px;
  opacity:.2;
}

<?php
  foreach (WPT_MODULES['tpick']['items'] as $item => $color)
  {
echo <<<EOC
.postit .postit-tags i.fa-$item,
#tpick i.fa-$item {
  color:$color !important;
}
EOC;
  }
?>

.toolbox {
  z-index:5003;
  display:none;
  position:fixed;
  border-radius:3px;
  padding:3px;
  left:5px;
  background:#f1f1f1;
  border: 1px solid #cecece;
  box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
}

.chat {
  width:250px;
  bottom:15px;
  min-width:200px;
}

.chat .textarea,
.chat input {
  width:100%;
}

.chat .textarea {
  height:100px;
  margin-bottom:5px;
  font-size:0.8rem;
  padding: 0 3px 0 3px;
  overflow-y:auto;
}

.chat ul {
  list-style-type:none;
  margin:5px 0 5px 0;
  padding:0;
}

.chat .textarea ul li span {
  font-weight:bold;
  opacity:0.5;
}

.chat .textarea .internal {
  font-size:0.7rem;
  font-weight:bold;
  font-style:italic;
  color:#000;
}

.chat li i.fas {
  color:#555;
}

.chat .textarea .internal.join i {
  color:green !important;
}

.chat .textarea ul li.internal.leave i {
  color:red !important;
}

.chat .btn-primary {
  margin-top:5px;
  float:right;
}

.chat h2 {
  font-size:15px;
  font-weight:bold;
  text-align:center;
  margin-bottom:0;
  white-space:nowrap;
  margin: 0 20px 0 10px;
}

.chat .btn-clear {
  position:absolute;
  z-index:2;
  width:30px;
  height:30px;
  margin-left:-5px;
  margin-top:-25px;
}

.filters {
  width:90px;
  top:60px;
}

.filters h2 {
  font-size:15px;
  font-weight:bold;
  text-align:center;
  margin-bottom:0;
}

.filters h3 {
  font-size:14px;
}

.filters-items > div {
  float:left;
  background:#fff;
  border-radius:3px;
  margin:2px;
  padding:5px;
  border:1px solid #e6e6e6;
  cursor:pointer;
}

.filters-items div > div {
  float:left;
  margin: 2px;
}

.filters-items .colors > div {
  border:1px solid #e6e6e6;
  width:20px;
  height:20px;
  border-radius:3px;
}

.filters-items .colors > div.selected {
  border: 2px solid gray;
}

.arrows {
  display:none;
}

.arrows .goto-box {
  color:#343a40;
  z-index:5010;
  position:fixed;
  cursor:pointer;
  left:0;
  text-align:center;
}

.arrows .goto-box-x i:not(:first-child){
  margin-left:10px;
}

.arrows .goto-box-y {
  bottom:40px;
  width:35px;
}

.arrows .goto-box-x {
  bottom:0;
}

ul#mmenu {
  list-style-type:none;
  text-align:center;
  cursor:move;
  background:#fff;
  color:#555;
  top:60px;
  left:60px;
  width:50px;
  padding:10px;
  margin-left:auto;
  margin-right:auto;
}

#mmenu .wpt-badge {
  top:2px;
  left:4px;
}

#mmenu i {
  cursor:pointer;
}

#mmenu button.close {
  margin-top:-12px;
  margin-right:-8px;
}

.btn-group-sm > .btn-xs, .btn-xs {
  padding: .20rem .4rem;
  font-size: .8rem;
}

.btn-primary i {
  color:white;
}

.dot-theme {
  height: 25px;
  width: 25px;
  border-radius: 50%;
  display: inline-block;
  margin-right:5px;
  cursor:pointer;
  opacity:1;
  border:2px solid silver;
}

.dot-theme:hover {
  opacity:0.6;
  border:2px solid #fff;
}

.btn-theme {
  color:#fff;
}

.btn-theme:hover {
  color:#fff;
}

.btn-theme-default {
  background-color: #343a40;
}

.btn-theme-blue {
  background-color: #007bff !important;
}

.btn-theme-green {
  background-color: #28a745 !important;
}

.btn-theme-orange {
  background-color: #ffbf00;
  color: #6b6a6a;
}

.btn-theme-red {
  background-color: #dc3545;
}

.btn-theme-purple {
  background-color: #c63fca;
}

.modal-dialog.modal-sm .modal-title {
  font-size: 1em;
}

#postitAttachmentsPopup .modal-body > ul {
  margin-top:-30px;
}

#postitAttachmentsPopup .modal-body .list-group li div {
  display:inline-block;
  max-width:90%;
  text-overflow:ellipsis !important;
  overflow:hidden !important;
}

#postitAttachmentsPopup .list-group-item.collapse:hover {
  cursor:auto !important;
}

#postitAttachmentsPopup .collapsing {
  -webkit-transition:none;
  transition:none;
  display:none;
}

#postitAttachmentsPopup .list-group-item[aria-expanded=true] {
  border-bottom:2px solid silver;
}

#postitAttachmentsPopup .edit-popup .modal-body {
  padding:0;
}

#postitAttachmentsPopup .edit-popup {
  display:none;
}

#postitAttachmentsPopup .edit-popup .img {
  text-align:center;
}

#postitAttachmentsPopup .edit-popup .img img {
  max-width:250px;
  cursor:zoom-in;
}

#postitAttachmentsPopup .edit-popup i {
  color:#fff;
}

#postitAttachmentsPopup .edit-popup .file {
  text-align:center;
  font-weight:bold;
}

#postitAttachmentsPopup .edit-popup .no-details {
  font-style: italic;
  text-align:center;
  opacity:0.5;
}

#postitAttachmentsPopup .list-group-item:nth-last-child(2) {
  border-bottom-left-radius:inherit;
  border-bottom-right-radius:inherit;
}

#postitAttachmentsPopup .list-group-item.no-bottom-radius {
  border-bottom-left-radius:0;
  border-bottom-right-radius:0;
}

#img-viewer img {
  position:absolute;
  left: 50%;
  top: 50%;
  max-height:100%;
  max-width:100%;
  transform: translate(-50%, -50%);
  border-radius:10px;
  box-shadow: 1px 1px 12px #555;
  z-index:5017;
}

#img-viewer .close {
  position:fixed;
  left:10px;
  top:50%;
  margin-top:-24px;
  z-index:5018;
  color:#f1f1f1;
  cursor:pointer;
}

#swallPopup .scroll {
  max-height:300px;
  overflow-y:auto;
  border-radius:5px;
}

#swallPopup .scroll.one {
  overflow-y:unset;
}

#swallPopup .creator-only {
  border:1px solid #eceaea;
  border-radius: 5px;
  padding:5px;
}

#swallPopup .list-group .btn-xs i {
  color:#fff;
}

#swallPopup li .btn-share {
  float:right;
  margin-right:10px;
}

#swallPopup .nogroup,
#usearchPopup .nousers-title {
  font-style:italic;
  opacity:0.6;
  text-align:center;
}

button.clear-input {
  margin-left:-40px;
  color:#555;
  z-index:3;
}

button.clear-input:focus {
  border-color:transparent !important;
}

.newfeatures p {
  border:5px solid #f7f7f7;
  border-radius:5px;
  padding:10px;
  color:#212529;
  font-size:0.9rem;
  margin-bottom: 0.3rem;
}

.newfeatures b i.fas {
  color:#555;
  margin-right:5px;
}

.newfeatures b:after {
  content: " \002014"
}

.newfeatures p.warning {
  border:2px dashed #555 !important;
}

.hidden {
  display:none;
}

.userscount {
  display:block;
  position:absolute;
  border-radius:0 5px 5px 0;
  top:8px;
  left:0;
  padding: 5px 0 5px 5px;
  cursor:pointer;
}

.wpt-badge {
  background-color: #343a40;
  color:white;
  position: absolute;
  border-radius: 50%;
  font-size:0.6rem;
  height: 1.5em;
  line-height: 1.5em;
  min-width: 0;
  padding: 0;
  width: 1.5em;
  text-align:center;
  margin-left:-2px;
}

.userscount .wpt-badge {
  left: 20px;
  top:5px;
}

.chat .usersviewcounts .wpt-badge {
  font-size:0.5rem;
  margin-left:0;
}

ul.wall-menu {
  display:inline-block;
  visibility:hidden;
  background:#fff;
  top:60px;
  left:5px;
  box-shadow:0 0 5px #cecece;
  padding:10px;
  z-index:5003;
  width:50px;
  color:#555;
  list-style-type:none;
  cursor:move;
  text-align:center;
}

.wall-menu li {
  cursor:pointer;
  white-space:nowrap;
}

.usersviewcounts {
  display:none;
  color:#555;
  z-index:1030;
  cursor:pointer;
}

li.divider {
  border-top:1px dashed #c7c6c6;
  margin:5px 0 10px 0;
}

.popover-body i,
i.set {
  color:#151719;
}

.wall-menu .wpt-badge {
  margin-left:-30px;
  margin-top:11px;
}

#mmenu li:not(:last-child) i.fa-lg,
.wall-menu li:not(:last-child) i.fa-lg {
  margin-bottom:10px;
}

.chat .usersviewcounts {
  position:relative;
  display:inline-block;
}

.postit .attachmentscount {
  position:absolute;
  border-radius:0 5px 5px 0;
  background:pink;
  height:0;
  top:-30px;
  left:30px;
  padding-left: 5px;
  cursor:pointer;
  line-height: 20px;
  font-size:12px;
  z-index:92;
}

.postit .attachmentscount .wpt-badge {
  left:12px;
}

/*.modal-dialog:not(.modal-sm) .modal-body  {*/
.newfeatures.justify,
.popover-body.justify,
.modal-body.justify {
  text-align: justify;
  -webkit-hyphens: auto;
  -moz-hyphens: auto;
  -ms-hyphens: auto;
  -o-hyphens: auto;
   hyphens: auto;
}

.modal-body .list-group li {
  white-space:nowrap;
  text-overflow:ellipsis !important;
  overflow:hidden !important;
}

.modal-body .list-group li i {
  cursor:pointer;
}

.modal-body .list-group span.name {
  margin-left:20px;
}

.modal-body .list-group span.desc {
  font-style:italic;
  opacity:0.5;
}

.nav-item .icon i,
.list-group-item i {
  margin-right:5px;
  color: silver;
}

.nav-item .icon i.wallname-icon {
  font-size:0.7rem;
  position:absolute;
  margin-top:-4px;
  opacity:0.6;
}

.nav-item .icon i.fa-user-slash.wallname-icon {
  margin-left:-14px;
}

.nav-item .icon i.fa-share.wallname-icon {
  margin-left:-10px;
}

.list-group-item i.fa {
  color:#555;
  margin-right: 10px;
}

.list-group-item:hover  {
  background:#f5f5f5;
}

#postitAttachmentsPopup .list-group-item.collapse:hover {
  background:inherit;
}

.list-group-item.active {
  color:inherit;
  background:#f5f5f5;
  border: 1px solid rgba(0,0,0,.125);
}

.result .closemenu {
  display:none;
  border-left:1px solid silver;
  border-right:1px solid silver;
}

.autocomplete .list-group-item:first-child {
  border-top-left-radius:0;
  border-top-right-radius:0;
}

.form-control.autocomplete {
  border-bottom-left-radius:0;
  border-bottom-right-radius:0;
}

.form-control:disabled,
.form-control[readonly] {
  background-color: #f5f5f5;
}

.input-group-text {
  background:#fff;
  color:#555;
  border-radius:0.25rem 0 0 0.25rem;
}

.btn-change-input {
  color:#555;
  border:1px solid #ced4da;
}

.input-group-prepend.icon {
  color:#555;
  margin-right:10px;
}

#groupAccessPopup .send-msg * {
  font-size:0.9rem;
}

.custom-control.disabled {
  opacity:0.6;
}

#groupAccessPopup .custom-switch .custom-control-label::before,
#dpickPopup .custom-switch .custom-control-label::before {
  top:.15rem;
}

#groupAccessPopup .custom-switch .custom-control-label::after,
#dpickPopup .custom-switch .custom-control-label::after {
  top:.25rem;
}

#swallPopup .delegate-admin-only {
  font-size:0.9rem;
  opacity:0.6;
  margin-top:-10px;
  margin-bottom:20px;
}

textarea:focus,
input:focus,
button:focus,
a.btn:focus {
  box-shadow: none !important;
  outline: 0 !important;
}

input {
  box-shadow: none !important;
  outline: 0 !important;
}

<?php
  foreach (WPT_MODULES['cpick']['items'] as $name => $color)
  {
echo <<<EOC
.color-$name,
.postit.color-$name .dates,
.postit.color-$name .postit-header,
.postit.color-$name .postit-edit {
  background:$color !important;
  color:#43474a !important;
}
EOC;
  }
?>

.modal a:not(.list-group-item):not(.close),
.modal span.name i {
  color: #343a40;
}

#accountPopup i.fa-bomb {
  color:#555;
}

#changePasswordPopup,
#updateOneInputPopup,
#userViewPopup {
  z-index:5018;
}

i.fa-xs {
  vertical-align:middle;
}

.modal-header {
  padding: 0.9rem;
}

.modal-footer {
  padding:0.5rem;
}

.modal-header, .modal-title, .modal-footer {
  background: #f1f1f1;
  color:#555;
}

.modal:not(.no-theme) .modal-dialog .modal-content {
  border: 2px solid #f1f1f1;
}

.popover-header,
.modal-dialog.shadow .modal-header,
.modal-dialog.shadow .modal-title,
.modal-dialog.shadow .modal-footer {
  background: #555;
  color:#fff;
}

.modal:not(.no-theme) .modal-dialog.shadow .modal-content {
  border: 2px solid #555 !important;
}

.modal:not(.no-theme) .modal-header,
.modal:not(.no-theme) .modal-footer {
  border-radius:0;
}

.modal-dialog:not(.modal-sm) .modal-title i.fa-lg {
  margin-right:20px;
}

#postitUpdatePopup .modal-body {
  min-height:375px;
}

.slider label span {
  color:#555;
}

.slider input {
  width:100%;
}

.download {
  display:none;
}

.upload {
  display:none;
}

.postit .postit-edit {
  position:absolute;
  font-size:12px;
  height:100%;
  width:100%;
  right:0;
  top:0;
  overflow:auto;
  border-radius:0 1px 1px 1px;
  padding:5px 5px 20px 5px;
  z-index:92;
}

.postit .postit-progress-container {
  height:100%;
  width:12px;
  box-shadow:inset 0 0 5px rgba(0, 0, 0, 0.5);
  border-radius:0 0 5px 5px;
  padding:1px;
  margin-left:-14px;
}

.postit-min .postit-progress-container {
  padding:1px;
  background:#fff;
  border-radius:3px;
  box-shadow:inset 0 0 3px rgba(0, 0, 0, 0.5);
  margin-bottom:-2px;
  margin-top:-3px;
}

.postit .postit-progress {
  position:absolute;
  bottom:1px;
  max-height:98%;
  width:10px;
  z-index:92;
  border-radius:0 0 5px 5px;
  box-shadow:inset 0 -2px 5px rgba(0, 0, 0, 0.5);
  opacity:.8;
}

.postit-min .postit-progress {
  width:100%;
  height:3px;
  border-radius:5px;
  line-height:3px;
}

.postit-min .postit-progress span {
  position:absolute;
  font-size:.6rem;
  font-weight:bold;
  color:#000;
  margin-top:-5px;
  left:50%;
}

.postit .postit-progress-container span {
  font-weight:bold;
}

.postit .postit-progress-container div:first-child {
  position:absolute;
  left:-21px;
  width:26px;
  transform: translateY(-50%);
  -webkit-transform: rotate(270deg);
  font-size:10px;
  bottom:50%;
  z-index:93;
}

.postit-edit p,
#postitViewPopup .modal-body p {
  margin:0;
}

.ui-icon-gripsmall-diagonal-se {
  z-index:92 !important;
}

.postit .dates {
  position:absolute;
  bottom:0;
  left:0;
  width:100%;
  font-size:0.6rem;
  box-shadow: 0 -5px 5px -5px #cecece;
  border-radius:0 0 1px 1px;
  z-index:92;
}

.postit .end i {
  position:absolute;
  padding:3px;
  text-shadow: 1px 1px 3px rgba(0,0,0, 0.5);
}

.postit.obsolete .dates {
  color:#fff !important;
  font-weight:bold;
  background-color:red !important;
}

.postit .dates div {
  display:inline-block;
}

.postit .creation span {
  margin-left:5px;
  opacity:.6;
}

.postit .end {
  position:absolute;
  cursor:pointer;
  right:15px;
  top:-5px;
  padding:5px 10px 0 0;
}

.postit .end span {
  margin-left:22px;
}

.wall[data-access="<?=WPT_WRIGHTS_RO?>"] .postit {
  cursor:pointer;
}

.postit .end.with-alert {
  font-weight:bold;
  text-shadow: 1px 2px 3px rgba(0,0,0, 0.5);
}

#dpickPopup .dpick a {
  color:#555!important;
}

#dpickPopup .dpick a.ui-state-active {
  color:#fff!important;
  font-weight:bold;
}

#dpickPopup .dpick-notify {
  font-size:0.9rem;
  text-align:left;
}

#dpickPopup .dpick-notify input[type="number"] {
  width:3rem;
}

.ui-datepicker {
  z-index:5020 !important;
}

button.ui-datepicker-close {
  font-size:0.8rem;
}

#tpick {
  width:100px;
  text-align:center;
}

#tpick > .selected,
.filters-items .tags > .selected {
  background-color:#dadada;
  border: 1px solid silver;
  border-radius: 5px;
}

#tpick > div,
.filters-items .tags > div {
  float:left;
  border: 1px solid transparent;
  margin:2px;
}

#tpick > div i,
.filters-items .tags > div i {
  margin:2px;
  color:#555;
}

.ui-colorpicker-swatches {
  height:auto;
}

.ui-colorpicker-swatch {
  height:20px;
  width:20px;
}

#tpick,
#cpick {
  z-index:5020;
  display:none;
  position:absolute;
  cursor:pointer;
  background-color:#fff;
  border:1px solid #e6e6e6;
  padding: 3px;
  border-radius:5px;
}

#cpick {
  width:87px;
}

#cpick > div {
  float:left;
  width:20px;
  height:20px;
  margin:3px;
  border:1px solid #e6e6e6;
  border-radius:3px;
}

.resizable-helper {
  border:1px dashed #555;
}

.locale-picker > div {
  display:inline-block;
  padding: 0 3px 0 3px;
  border-radius: 5px;
  border:2px solid silver;
  cursor: pointer;
  margin-right:3px;
}

.locale-picker > .selected {
  background-color:#dadada;
}

.locale-picker img {
  margin-top:-1px;
}

#main-menu.noarrows .display-section [data-action=arrows] {
  display:none;
}

@media (max-width:576px) {
  .navbar-nav .dropdown-menu {
    float: left !important;
  }
}

@media (max-width:800px) {
  .modal.m-fullscreen .modal-dialog {
    height: 100%;
    min-width: 100%;
    margin: 0;
    padding: 0;
  }
  
  .modal.m-fullscreen .modal-header,
  .modal.m-fullscreen .modal-content,
  .modal.m-fullscreen .modal-footer {
    border-radius:0 !important;
  }

  .modal.m-fullscreen .modal-content {
    min-height: 100%;
    border: 0 none;
  }
  
  .modal.m-fullscreen .modal-dialog-scrollable {
    max-height:100%;
  }
}
