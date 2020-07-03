<?php require_once (__DIR__.'/../class/Wpt_common.php')?>
* {
  outline: none !important;
}

::placeholder {
  font-style:italic !important;
  opacity:0.4 !important;
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

.accordion kbd {
  padding:2px;
  background:#343a40;
  font-size:0.7rem;
}

.accordion .btn-link {
  text-decoration:none !important;
}

.accordion .btn-link {
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
  top:65px;
  left:13px;
  cursor:pointer;
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

.alert b {
  font-size:0.9rem;
}

.alert {
  box-shadow: 0 0 20px #cecece;
  z-index:60000 !important;
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
  color:#000;
  opacity:0.3;
}

.nav-tabs.walls a.active button.close {
  display:block;
}

.nav-tabs a.active span {
  opacity:1;
}

.wall-caption {
  margin-top:10px;
  cursor:pointer;
  font-style:italic;
}

.layer {
  display:none;
  position:fixed;
  top:0;
  left:0;
  width:100%;
  height:100%;
  z-index:5016;
}

.bs-popover-auto[x-placement^=bottom] .arrow:after {
  border-color: #555 transparent;
}

.bs-popover-auto[x-placement^=bottom] .popover-header::before {
  border-bottom:none;
}

.popover,
.postit-mark {
  z-index:5017;
}

.popover p {
  margin-bottom:0.5rem;
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
#wallPropertiesPopup textarea {
  height:100px;
}

.user-picture {
  text-align:center;
  color:#555;
}

#accountPopup .user-picture {
  cursor:pointer;
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

.navbar-brand i:hover {
  cursor:pointer;
  opacity:0.6;
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

/*FIXME*/
#postitAttachmentsPopup,
#shareWallPopup {
  z-index:5015;
}

.modal-backdrop {
  z-index:5003;
}

.tox {
  z-index:5018 !important;
}

.readonly {
  pointer-events: none;
  opacity: .6;
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

.list-group-item .item-right-icon {
  position:absolute;
  right:0;
  top:3px;
  border:0;
  font-size:1.5rem;
  background-color:transparent;
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

.list-group li div.item-infos {
  max-width:70% !important;
}

#openWallPopup .list-group-item .item-infos {
  left:50px;
}

#openWallPopup .list-group-item {
  height:55px;
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
  font-size:0.9rem;
  vertical-align:top;
  padding:10px;
  border: 1px solid #cecece;
  background:#f1f1f1;
  text-align:center;
  white-space:nowrap;
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

.wall thead th:first-child {
  color:#555;
  padding-left:0;
  cursor:pointer;
}

.wall thead th .title {
  margin:18px 20px 0 20px;
}

.wall td {
  min-height:200px;
  height:200px;
  min-width:300px;
  border:1px solid #f2f2f2;
}

.wall tbody th {
  padding:0;
  min-width:51px;
  max-width:301px;
}

.wall tbody th .title {
  margin-top:25px;
  padding:0 10px 0 10px;
  text-overflow: ellipsis;
  overflow:hidden;
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
  font-size:1rem;
}

.wall th div.img {
  cursor:pointer;
  display:inline-block;
  margin-top:5px;
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

svg.leader-line text {
  fill-opacity:0 !important;
}

.dropdown-item:active,
.dropdown-item:hover {
  background-color:#f1f1f1;
  border-color:#cecece;
}

.dropdown-item:active {
  color:#555;
}

div.plug-label {
  z-index:1000;
  position:absolute;
  cursor:pointer;
  padding:0 5px;
  border:1px solid #e8e8e8;
  background:#fff;
  border-radius:5px;
  white-space:nowrap;
  font-size:0.8rem;
  margin-top:-1px;
  margin-left:-4px;
}

div.plug-label > a {
  color:#bfbfbf;
  text-decoration:none;
}

div.plug-label:hover {
  color:#a2a2a2;
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

.postit-menu .submenu {
  margin-left:3px;
  margin-top:-6px;
  height:32px;
}

.submenu .dropdown-toggle::after {
  content: none;
}

.submenu i[data-action="plug"] {
  color:#43474a;
}

.submenu .dropdown-menu {
  top:-25px !important;
  left:20px !important;
  padding:3px 0;
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

.submenu.line-menu {
  height:auto;
}

.submenu.line-menu:hover {
  z-index:6000!important;
}

.submenu.line-menu .dropdown-menu {
  min-width:100px;
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

div.postit-mark {
  color:#343a40;
  position:absolute;
  display:inline-block;
}

div.postit {
  -webkit-touch-callout: none;
  -webkit-user-select: none;
  -khtml-user-select: none;
  -moz-user-select: none;
  -ms-user-select: none;
/*  user-select: none;*/
  z-index:1010;
  position:absolute !important;
  background:#ffffc6;
  min-height:120px;
  min-width:180px;
  height:120px;
  width:180px;
  border-radius:0 3px 3px 3px;
  border:2px solid #cecece;
  box-shadow: 0 0 5px #cecece;
}

div.postit .postit-header {
  position:absolute;
  font-size:0.8rem;
  border-radius:5px 5px 0 0;
  background:#ffffc6;
  top:-21px;
  margin-left:-2px;
  max-width:180px;
  padding:0 5px 0 5px;
  white-space: nowrap;
  text-overflow: ellipsis;
  overflow:hidden;
  border:2px solid #cecece;
  border-bottom:0;
  box-shadow: 0 0 5px #cecece;
}

.postit.search-match .postit-header,
.postit.search-match {
  border-color:#343a40;
  box-shadow: 0 0 25px 5px #b1b1b1;
}

.postit.search-match .postit-menu {
  border-color:#343a40;
}

div.postit div.attachmentscount i {
  background:transparent !important;
}

div.postit .postit-header i {
  cursor:pointer;
  margin-right:10px;
}

div.postit .postit-header span {
  font-weight:bold;
  text-decoration: underline;
}

div.postit div.postit-menu {
  display:none;
  position:absolute;
  top:-45px;
  width:30px;
  margin-left:-30px;
  cursor:pointer;
  z-index:5000;
  border-radius:5px;
  border:2px solid #cecece;
  box-shadow: 0 0 5px #cecece;
}

div.postit div.postit-menu > div {
  text-align:center;
  margin-top:3px;
}

div.postit div.postit-menu i {
  font-size:1rem;
}

div.postit div.postit-menu > div:first-child {
  margin-top:0;
  margin-left:-1px;
}

div.postit div.postit-menu > div:first-child i {
  text-shadow: 1px 2px 3px rgba(0,0,0, 0.5);
  font-size:1.2rem !important;
  opacity:0.6;
}

div.postit div.postit-tags {
  display:none;
  position:absolute;
  border-radius:0 5px 5px 0;
  width:15px;
  top:3px;
  right:-7px;
  padding: 0;
  cursor:pointer;
  line-height: 20px;
  z-index:10000;
}

div.postit div.postit-tags i {
  margin-left:-2px;
  background:none !important;
  text-shadow: 1px 2px 3px rgba(0,0,0, 0.5);
}

<?php
  foreach (WPT_MODULES['tagPicker']['items'] as $item => $color)
  {
echo <<<EOC
div.postit div.postit-tags i.fa-$item,
.tag-picker i.fa-$item {
  color:$color !important;
}
EOC;
  }
?>

.toolbox {
  background:#f1f1f1;
  border: 1px solid #cecece;
  box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
}

.chatroom {
  z-index:5004;
  display:none;
  position:fixed;
  border-radius:3px;
  padding:3px;
  width:250px;
  bottom:15px;
  left:5px;
  cursor:pointer;
  min-width:200px;
}

.chatroom-alert {
  position:fixed;
  bottom:15px;
  left:10px;
  cursor:pointer;
  z-index: 5010;
}

.chatroom .textarea,
.chatroom input {
  width:100%;
}

.chatroom .textarea {
  height:100px;
  margin-bottom:5px;
  font-size:0.8rem;
  padding: 0 3px 0 3px;
  overflow-y:auto;
}

.chatroom ul {
  list-style-type:none;
  margin:5px 0 5px 0;
  padding:0;
}

.chatroom .textarea ul li span {
  font-weight:bold;
  opacity:0.5;
}

.chatroom .textarea ul li.internal {
  font-size:0.7rem;
  font-weight:bold;
  font-style:italic;
  color:#000;
}

.chatroom li i.fas {
  color:#555;
}

.chatroom .textarea ul li.internal.join i {
  color:green !important;
}

.chatroom .textarea ul li.internal.leave i {
  color:red !important;
}

.chatroom button.btn-primary {
  margin-top:5px;
  float:right;
}

.chatroom h2 {
  font-size:15px;
  font-weight:bold;
  text-align:center;
  margin-bottom:0;
  white-space:nowrap;
  margin: 0 20px 0 10px;
}

.chatroom button.erase {
  position:fixed;
  margin-top:-10px;
  margin-left:-7px;
  padding: 0;
  background-color: transparent;
  border: 0;
}

.chatroom .erase {
  font-size: 1rem;
  font-weight: 700;
  line-height: 1;
  color: #000;
  text-shadow: 0 1px 0 #fff;
  opacity: .5;
}

.filters {
  z-index:5004;
  display:none;
  position:fixed;
  border-radius:3px;
  padding:3px;
  width:90px;
  top:60px;
  left:5px;
  cursor:pointer;
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
  border:1px solid silver;
}

.filters-items div > div {
  float:left;
  margin: 2px;
}

.filters-items .colors > div {
  border:1px solid silver;
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

.btn-group-sm > .btn-xs, .btn-xs {
  padding: .20rem .4rem;
  font-size: .8rem;
}

button.btn-primary i {
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

#shareWallPopup div.scroll {
  max-height:300px;
  overflow-y:auto;
  border-radius:5px;
}

#shareWallPopup div.scroll.one {
  overflow-y:unset;
}

#shareWallPopup span.nogroup,
#usersSearchPopup label.nousers-title {
  font-style:italic;
  opacity:0.6;
  font-size:0.9rem;
}

div.userscount {
  display:block;
  position:absolute;
  border-radius:0 5px 5px 0;
  top:10px;
  left:0;
  padding: 5px 0 5px 5px;
  cursor:pointer;
}

span.wpt-badge {
  background-color: #343a40;
  color:white;
  position: absolute;
  border-radius: 50%;
  font-size:0.8em;
  height: 1.5em;
  line-height: 1.5em;
  min-width: 0;
  padding: 0;
  width: 1.5em;
  text-align:center;
}

div.userscount span.wpt-badge {
  left: 15px;
  top:-2px;
}

div.usersviewcounts {
  display:inline-block;
  position:relative;
  height: 1.2em;
  line-height: 1.2em;
  width: 1.2em;
  color:#555;
}

div.usersviewcounts span.wpt-badge,
.chatroom .usersviewcounts span.wpt-badge {
  font-size:0.6rem;
}

div.postit div.attachmentscount {
  display:block;
  position:absolute;
  border-radius:0 5px 5px 0;
  background:none;
  top:-35px;
  left:30px;
  padding: 5px 0 5px 5px;
  cursor:pointer;
  line-height: 20px;
  z-index:10000;
  font-size:12px;
}

div.postit div.attachmentscount span.wpt-badge {
  left:12px;
}

/*.modal-dialog:not(.modal-sm) .modal-body  {*/
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

.modal-body .list-group li span.name {
  margin-left:30px;
}

.modal-body .list-group li span.desc {
  font-style:italic;
  opacity:0.5;
}

.nav-item .icon i,
.list-group-item i {
  margin-right:5px;
  color: silver;
}

.nav-item .icon i.notowner {
  font-size:0.7rem;
  position:absolute;
  margin-left:-14px;
  margin-top:-4px;
  opacity:0.6;
}

.list-group-item i.fa {
  color:#555;
  margin-right: 10px;
}

.list-group-item:hover  {
  background:#f5f5f5;
}

.list-group-item.active {
  color:inherit;
  background:#f5f5f5;
  border: 1px solid rgba(0,0,0,.125);
}

.result button.closemenu {
  display:none;
  border-left:1px solid silver;
  border-right:1px solid silver;
}

.autocomplete .list-group-item:first-child {
  border-top-left-radius:0;
  border-top-right-radius:0;
}

input.form-control.autocomplete {
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

#groupAccessPopup .input-group-prepend span {
  margin-right: 10px;
}

#shareWallPopup .delegate-admin-only {
  font-size:0.9rem;
  opacity:0.6;
  margin-top:-10px;
  margin-bottom:20px;
}

textarea:focus,
input:focus,
button:focus {
  box-shadow: none !important;
  outline: 0 !important;
}

input {
  box-shadow: none !important;
  outline: 0 !important;
}

<?php
  foreach (WPT_MODULES['colorPicker']['items'] as $name => $color)
  {
echo <<<EOC
.color-$name,
.modal-header.color-$name,
.modal-header.color-$name h5,
.modal-footer.color-$name,
div.postit.color-$name .dates,
div.postit.color-$name div.postit-menu,
div.postit.color-$name .postit-header,
div.postit.color-$name .postit-edit {
  background:$color;
  color:#43474a;
}
EOC;
  }
?>

.modal a:not(.list-group-item):not(.close) {
  color: #343a40;
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

a.download {
  display:none;
}

input.upload {
  display:none;
}

div.postit .postit-delete {
  display:none;
  float:right;
  margin-top:-21px;
  margin-right:-18px;
  padding:3px;
  text-shadow: 1px 2px 3px rgba(0,0,0, 0.5);
}

div.postit div.postit-edit {
  position:absolute;
  font-size:12px;
  height:100%;
  width:100%;
  right:0;
  top:0;
  overflow:auto;
  border-radius:0 1px 1px 1px;
  padding:5px 5px 20px 5px;
}

.postit-edit p,
#postitViewPopup .modal-body p {
  margin:0;
}

div.postit .dates {
  position:absolute;
  bottom:0;
  left:0;
  width:100%;
  font-size:0.6rem;
  box-shadow: 0 -5px 5px -5px #cecece;
  background:#ffffc6;
  border-radius:3px;
}

div.postit .dates i:before {
 position:absolute;
 top:3px;
}

div.postit .creation i:before {
  left:4px;
}

div.postit .end i:first-child:before {
  top:2px;
  left:-20px;
}

div.postit .end i:before {
  left:-5px;
}

div.postit .end i.fa-times-circle {
  display:none;
}

div.postit.obsolete .dates,
div.postit.obsolete .dates * {
  color:#fff;
  font-weight:bold;
  background-color:red !important;
}

div.postit .dates div {
  display:inline-block;
}

div.postit .creation {
  margin-left:10px;
}

div.postit .end {
  position:absolute;
  right:15px;
  cursor:pointer;
}

div.postit .date-picker {
  width:0;
  height:0;
  overflow:hidden;
  background:transparent;
  border:none;
}

.ui-datepicker {
  z-index:5020 !important;
}

.tag-picker {
  display:none;
  position:absolute;
  cursor:pointer;
  background-color:#fff;
  border:1px solid silver;
  padding: 3px;
  border-radius:5px;
  z-index:5016;
  width:100px;
  text-align:center;
}

.tag-picker > div.selected,
.filters-items .tags > div.selected {
  background-color:#dadada;
  border: 1px solid silver;
  border-radius: 5px;
}

.tag-picker > div,
.filters-items .tags > div {
  float:left;
  border: 1px solid transparent;
  margin:2px;
}

.tag-picker > div i,
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

.color-picker {
  display:none;
  position:absolute;
  cursor:pointer;
  background-color:#fff;
  border:1px solid silver;
  padding: 3px;
  border-radius:5px;
  z-index:5020;
  width:87px;
}

.color-picker > div {
  float:left;
  width:20px;
  height:20px;
  margin:3px;
  border:1px solid silver;
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

.locale-picker > div.selected {
  background-color:#dadada;
}

.locale-picker img {
  margin-top:-1px;
}

#main-menu.nofullview ul.display-section li[data-action^=zoom],
#main-menu.nofullview ul.display-section .dropdown-divider {
  display:none;
}

#main-menu.noarrows ul.display-section li[data-action=arrows] {
  display:none;
}

@media (max-width:576px) {
  /*FIXME zoom does not work with some mobile browsers*/
  #main-menu ul.display-section li[data-action^=zoom],
  #main-menu ul.display-section .dropdown-divider {
    display:none;
  }
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
