<?php
/**
  Main CSS file
*/

  require_once (__DIR__.'/../prepend.php');

?>

:root {
  --modal-zindex: 5017;
}

/* Disable user zoom on safari (meta are not efficient here) */
body * {
  touch-action: pan-x pan-y;
}

* {
  outline: none !important;
  font-family: arial, verdana, sans-serif;
}

::placeholder {
  font-style: italic !important;
  opacity: .4 !important;
}

img {
  image-orientation: from-image;
}

.navbar {
  background: var(--menubar-bg-color) !important;
  box-shadow: 0 4px 10px -2px silver !important;
  z-index: 5005;
  padding-left: 20px;
  padding-right: 20px;
}

.form-check-input:checked {
  background-color: var(--modal-1-theme-bg-color);
  border-color: var(--wall-border-color);
}

.form-switch .form-check-input:focus {
  background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='-4 -4 8 8'%3e%3ccircle r='3' fill='rgba%280, 0, 0, 0.25%29'/%3e%3c/svg%3e");
}

.form-check-input:checked ~ .form-check-label::before {
  color: #fff;
  border-color: #343a40;
  background-color: #343a40;
}

.ldap-msg {
  color: #a5a5a5;
  text-align: center;
}

.global-error {
  position: fixed;
  left: 50%;
  top: 40%;
  transform: translate(-50%, -50%);
}

.app-short-desc {
  text-align: center;
  font-variant: small-caps;
  margin: 0 20px 20px 20px;
  line-height: 1rem;
}

#aboutPopup .desc {
  color: var(--usercard-links-color);
  font-size: .9rem;
}

#aboutPopup .warning {
  font-size: .9rem;
  border: 1px solid silver;
  border-radius: 5px;
  padding: 10px;
}

#aboutPopup .warning h2 {
  width: 100%;
  border-radius: 3px;
  padding: 3px;
  text-align: center;
  font-size: .9rem;
  font-weight: bold;
}

#aboutPopup .btn {
  color: #fff;
}

.project-title {
  line-height: 1.2rem;
  font-style: italic;
}

.project-title span a {
  font-size: .8rem;
}

.accordion,
#main-menu .nav-link {
  font-size: .9rem;
}

.accordion p {
  margin-bottom: .3rem;
}

.accordion dl {
  margin-top: 0;
  margin-bottom: 0;
}

kbd {
  padding: 2px;
  font-size: .7rem;
}

.accordion button:focus {
  border-color: transparent !important;
}

.accordion .card-header {
  padding: .5rem;
}

#popup-loader {
  display: none;
  background: rgba(52, 58, 64, .28);
  z-index: 59000;  
}

#loader {
  background: var(--menubar-bg-color);
  padding: 10px;
  border-radius: 5px;
  position: fixed;
  min-width: 250px;
  text-align: center;
  left: 50%;
  top: 50%;
  transform: translate(-50%, -50%);
  white-space: nowrap;
  z-index: 60000;
  box-shadow: 0 0 25px 5px var(--postit-selected-shadow-color);
  color: var(--modal-2-theme-color);
}

#loader span {
  font-size:14px;
}

#loader .progress {
  display: none;
  height: 16px;
  color: #fff;
  font-weight: bold;
  font-size: 10px;
  padding-left: 3px;
  background: orange;
  margin-bottom: 5px;
}

#loader button {
  display: none;
  background: #e2747f !important;
  border-color: #da4e5b !important;
  float: right;
}

#loader button:hover {
  background: #da4e5b !important;
}

#normal-display-btn {
  display: none;
  z-index: 1031;
  position: absolute;
  top: 59px;
  left: 13px;
  cursor: pointer;
  border: 1px dashed #dee2e6;
  padding: 8px;
  border-radius: 5px;
  background: #f6f6f6;
}

#normal-display-btn i {
  vertical-align: middle;
}

.inner-page {
  margin-left: auto;
  margin-right: auto;
  margin-top: 70px;
  max-width: 800px;
  border: 1px solid var(--wall-border-color);
  border-radius: 5px;
  height: 100%;
  padding: 10px;
}

#walls {
  overflow-x: auto;
  padding-left: 10px;
  position: relative;
}

i.settings {
  display: inline-block;
  transform: rotate(90deg);
}

.cp {
  display: inline-block;
  vertical-align: top;
}

.cp .ui-widget.ui-widget-content {
  border: none;
}

.ui-colorpicker-swatches {
  height: auto;
  overflow: hidden;
}

.ui-colorpicker-swatch {
  height: 20px;
  width: 20px;
}

.ui-colorpicker-border {
  border-color: #ced4da;
}

.ui-colorpicker-swatch {
  text-align: center;
  text-shadow: 0 0 1px #fff;
  color: var(--modal-theme-color-dark);
  font-size: 19px;
  line-height: 18px;
}

.ui-colorpicker-swatch.cp-selected:before {
  content: " # ";
}

#welcome {
  z-index: 1;
  position: absolute;
  left: 50%;
  top: 50%;
  transform: translate(-50%, -50%);
  text-align: center;
  max-width: 300px;
  font-style: italic;
  cursor: pointer;
}

#welcome img {
  width: 131px;
  height: 131px;
}

.dropdown-menu,
.navbar {
  -webkit-touch-callout: none;
  -webkit-user-select: none;
  -khtml-user-select: none;
  -moz-user-select: none;
  -ms-user-select: none;
  /*  user-select: none;*/
}

#main-menu ul.dropdown-menu {
  overflow-x: hidden;
}

.menu {
  margin: auto;
}

.table-menu {
  display: none;
}

.nav-link {
  white-space: nowrap;
  cursor: pointer;
}

.navbar-toggler {
  border: none;
}

.navbar-toggler i {
  color: var(--modal-2-theme-color);
}

.navbar .nav-link,
.navbar-brand i,
a.navbar-brand {
  color: var(--modal-2-theme-color);
}

a.navbar-brand:hover {
  color: var(--modal-2-theme-color);
}

.navbar .nav-link:hover,
.navbar-brand i:hover {
  color: var(--modal-2-theme-color);
  opacity: .6;
}

.nav-link.disabled {
  opacity: .2;
}

.navbar-brand i.invisible-mode {
  display: none;
  position: absolute;
  margin-top: -10px;
  margin-left: -15px;
}

.nav-tabs.walls {
  position: relative;
  z-index: 1030;
  text-align: center;
  padding-top: 70px;
  background: #fff;
}

.nav-tabs.walls .nav-link.active:focus-visible {
  border-color: var(--bs-nav-tabs-link-active-border-color);
}

.list-group-item .close {
  margin-top: -8px;
  padding: 0;
}

button.close {
  background: transparent;
  border: 0;
  float: right;
  font-size: 1.5rem;
  text-shadow: 0 1px 0 #fff;
  font-weight: bold;
  opacity: .5;
  color: var(--modal-theme-color-dark);
}

.nav-tabs.walls button.close {
  display:none;
  margin-top:-19px;
  margin-right:-15px;
  padding:0;
}

.nav-tabs.walls > a {
  float:none;
  display:inline-block;
}

.nav-tabs.walls > a:last-child {
  margin-left: 10px;
  color: var(--modal-theme-color-dark);
}

.nav-tabs a span {
  color: #212529;
  opacity: .3;
}

.nav-tabs.walls a.active button.close {
  display: block;
}

.nav-tabs a.active span {
  opacity: 1;
}

.layer {
  display: none;
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  z-index: calc(var(--modal-zindex) + 1);
}

.bs-popover-bottom>.popover-arrow::after,
.bs-popover-bottom>.popover-arrow::before {
  border-color: var(--modal-links-color) transparent;
}

.bs-popover-auto[data-popper-placement^="bottom"] > .popover-arrow::after {
  border-bottom-color: var(--modal-1-theme-bg-color);
}

.bs-popover-bottom .popover-header::before {
  width: 0;
}
.bs-popover-bottom>.popover-arrow::before {
  border-bottom: none;
}

.popover {
  z-index: calc(var(--modal-zindex) + 2);
}

.popover p {
  margin-bottom: .5rem;
}

.popover-body ul,
#postitCommentsPopup ul {
  margin: 0;
  padding: 0 0 0 10px;
  list-style-type: square;
  background: #fff;
}

.tip {
  color: #757575;
  font-style: italic;
  font-size: .7rem;
  line-height: .9rem;
  margin-bottom: 5px;
}

.pcomm-popover .result-container,
#postitCommentsPopup .result-container {
  position: absolute;
  overflow: auto;
  z-index: 1;
  border-radius: 0 0 .25rem .25rem;
}

.pcomm-popover ul.result,
#postitCommentsPopup ul.result {
  padding: 0;
  z-index: 1;
  margin-top: -1px;
}

.pcomm-popover textarea,
#postitCommentsPopup textarea {
  resize: none;
  height: 80px;
  font-size: .8rem;
  line-height: 1rem;
}

.msg-popover {
  max-width: 98%;
  max-height: 98%;
}

.pcomm-popover {
  max-width: 300px;
  min-width: 250px;
}

.msg-popover .popover-body {
  overflow-y: auto;
}

.msg-item quote {
  opacity: .6;
  font-style: italic;
}

.msg-popover .msg-item {
  margin-top: 10px;
}

#postitCommentsPopup .msg-item {
  margin-bottom: 5px;
}

#postitCommentsPopup .btn-primary {
  margin-bottom:10px;
}

.msg-title {
  font-weight: bold;
  background: #e3e3e3;
  padding: 5px;
  font-size: .8rem;
  border-radius: 3px 3px 0 0;
}

.pcomm-popover .msg-title {
  font-size: .6rem;
}

.msg-body {
  padding: 15px;
  font-size: .8rem;
  color: var(--modal-theme-color-dark);
  border: 1px solid #e3e3e3;
  border-top: none;
  border-radius: 0 0 10px 10px;
}

.pcomm-popover .msg-body {
  padding: 5px;
}

.msg-item button.close {
  font-size: 1.2rem;
  margin-top: -8px;
  margin-right: -4px;
}

.pcomm-popover .msg-item button.close {
  font-size: 1rem;
}

.umsg-popover {
  max-width: 500px;
}

.msg-date {
  opacity: .6;
  font-size: .6rem;
  margin-right: 5px;
  white-space: nowrap;
  float: right;
}

.pcomm-popover .msg-date {
  font-size: .5rem;
  font-weight: normal;
}

#upload-layer {
  background-color: transparent;
}

.nav.walls {
  display: none;
}

.input-group.required input {
  background-color: #fff1f1;
}

form span.required {
  color: #d45050;
  font-size: .9rem;
  font-style: italic;
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
  font-size: .8rem;
  text-align: center;
}

#wpropPopup .reject-sharing {
  text-align: center;
}

.user-picture {
  display: inline-block;
  color: var(--modal-theme-color-dark);
}

.content-centered {
  width: 100%;
  text-align: center;
}

.user-picture img {
  border-radius: 5px;
  box-shadow:0 0 10px 0 var(--modal-theme-color-dark);
}

dd {
  opacity: .8;
  font-size: .9rem;
}

#umsg {
  position:absolute;
  cursor:pointer;
  top: 6px;
  left: 8px;
}

#umsg .wpt-badge {
  display: none;
  background-color: #ff0000;
}

.inline-block {
  display: inline-block;
}

select.timezone {
  max-width:300px;
}

.ui-datepicker .ui-datepicker-title select {
  background-color: #fff;
  font-size: .8rem;
  margin-right: 5px;
  opacity: .8;
  padding: 3px;
}

.modal {
  z-index: var(--modal-zindex);
}

.modal.modal-sm {
  z-index: calc(var(--modal-zindex) + 1);
}

.zindexmax {
  z-index: 10000;
}

.modal-body {
  overflow: auto;
}

.modal-body .btn-sm {
  margin-top: 1px;
}

#msg-container {
  z-index: 6000;
  margin-top: 5px;
}

.toast {
  width: auto;
  min-width: 280px;
  max-width: 380px;
  margin-bottom: 1px;
}

.toast-header i {
  margin-right: 5px;
}

.toast-body {
  background-color: rgb(255, 255, 255, .2);
  width: 100%;
  border-top-right-radius: .25rem;
  border-bottom-right-radius: .25rem;
}

.wall-size {
  max-width: 250px;
}

.wall-size .input-group-text {
  border: none;
}

.wall-size input[type="number"]:first-of-type {
  border-top-left-radius: .2rem !important;
  border-bottom-left-radius: .2rem !important;
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
  pointer-events: none !important;
  opacity: .6 !important;
}

.list-group-item {
  cursor: pointer;
  color: var(--modal-theme-color-dark);
}

.list-group-item.readonly {
  font-style:italic;
}

.list-group-item.title {
  pointer-events: none;
  text-align: center;
  font-weight: bold;
  background-color: var(--wall-border-color);
  height: 35px;
  padding: 5px;
}

.list-group-item span.ownername {
  font-size: .8rem;
}

.accordion-button span {
  text-overflow:ellipsis;
  overflow:hidden;
  white-space:nowrap;
}

.list-group-item .item-infos {
  margin-left: 29px;
  font-size: .8rem;
  font-style: italic;
  opacity: .6;
  text-overflow: ellipsis;
  overflow: hidden;
}

#owallPopup .list-group-item .item-infos {
  left:50px;
}

.list-group .list-group-item.first {
  border-width: 1px;
  border-top-left-radius: .25rem;
  border-top-right-radius: .25rem;
}

.list-group .list-group-item.last {
  border-bottom-left-radius: .25rem;
  border-bottom-right-radius: .25rem;
}

#pworkPopup .list-group-item .item-infos,
#usearchPopup .list-group-item .item-infos {
  margin-top: -5px;
  margin-left: 1px;
}

#wallUsersviewPopup .list-group-item .item-infos {
  margin-top: -5px;
}

.list-group-item .item-infos span:not(:last-child):after {
  content: " - ";
}

.list-group .item-infos {
  max-width: 70%;
}

#usearchPopup .list-group-item .item-infos,
.pcomm-popover .list-group-item .item-infos,
#postitCommentsPopup .list-group-item .item-infos {
  left:19px;
}

.btn-clear .fa-broom {
  margin-left:-3px;
}

#owallPopup .list-group .form-check {
  padding-left:40px; 
}

#owallPopup .list-group-item .item-infos {
  margin-left:70px;
}

#owallPopup .list-group-item .wpt-checkbox {
  margin:0;
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

.chat .btn-clear {
  position:absolute;
  z-index:2;
  width:30px;
  height:30px;
  margin-left:-5px;
  margin-top:-25px;
}

.btn-circle {
  border-radius: 50%;
  text-align: center;
  vertical-align: middle;
  box-shadow: 0 0 3px 1px #989898;
}

.dropdown-item.disabled i {
  color: #6c757d;
  pointer-events: none;
  background-color: transparent;
}

#main-menu .dropdown-item {
  font-size:.9rem;
}

#main-menu .dropdown-divider {
  margin:.20rem 0;
}

.wall {
  border-collapse: collapse;
  margin-left:auto;
  margin-right:auto;
  margin-top:10px;
}

.wall th.wpt {
  vertical-align:top;
  padding:10px;
  background: var(--wall-th-bg-color);
  text-align:center;
  white-space:nowrap;
  font-size:14px;
  border-right:1px solid var(--wall-border-color);
}

.wall th.wpt ul.navbar-nav {
  display: block;
  width: 1px;
}

.wall th.wpt ul.submenu {
  text-align: left;
}

.wall th.wpt .dropdown-toggle {
  color: var(--modal-theme-color-dark);
}

.wall tbody.wpt tr.wpt.to-delete {
  background-color: yellow;
}

.wall thead.wpt th.wpt .title {
  margin: 28px 20px 0 20px;
  text-overflow: ellipsis;
  white-space: nowrap;
  overflow: hidden;
}

.wall td.wpt {
  min-height:200px;
  height:200px;
  min-width:300px;
  border:none;
  border-right:1px solid var(--wall-border-color);
}

.wall tr.wpt {
  border:1px solid var(--wall-border-color);
}

.wall tbody.wpt th.wpt {
  padding:0;
  min-width:51px;
  max-width:301px;
}

.wall tbody.wpt th.wpt .title {
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
  pointer-events: none;
  position: absolute;
  z-index: 5001;
  border-radius: 10px;
  box-shadow: 0px 0px 6px #b2b2b2;
  background: #fff;
  color: var(--modal-theme-color-dark);
  padding: 10px 10px 0 10px;
  font-size: .8rem;
  top: -50px;
  left: -25px;
}

.user-writing-min {
  pointer-events: none;
  position: absolute;
  z-index: 5001;
  border-radius: 25px;
  height: 25px;
  width: 25px;
  box-shadow: 0px 0px 6px #b2b2b2;
  background: #fff;
  color: var(--modal-theme-color-dark);
  margin-top: -2px;
  line-height: 25px;
  padding: 10px 10px 0 10px;
  font-size: .8rem;
  text-align: center;
}

.cell-list-mode li span.user-writing-min {
  opacity:1!important;
}

span.user-writing-min {
  position:absolute;
  padding:0!important;
}

.user-writing:not(.main) {
  top:auto;
  left:auto;
  width:100%;
  height:100%;
  margin-top:3px;
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
  text-shadow: 0px 0px 6px #b2b2b2;
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
  opacity:.8 !important;
}

a.active .user-writing {
  margin-top:30px;
  top:auto;
  left:auto;
}

.wall th.wpt .user-writing {
  top:auto;
  left:auto;
  border-top-left-radius:0;
  font-weight:normal;
}

.wall th.wpt.display {
  position:auto;
  visibility:visible;
}

.wall th.wpt.hide {
  position:absolute;
  visibility:hidden;
}

thead.wpt th.wpt .user-writing {
  margin-top:5px;
  margin-left:5px;
}

tbody.wpt th.wpt .user-writing {
  margin-left:14px;
  margin-top:15px;
}

td.wpt > .user-writing {
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

th.wpt .user-writing:after {
  left:-15px;
  top:-14px;
  transform:rotate(45deg);
}

td.wpt > .user-writing:after,
th.wpt .user-writing:after {
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

th.wpt .editable.editing input {
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

.wall th.wpt div.img {
  cursor:pointer;
  display:inline-block;
  margin-top:5px;
}

.wall:not([data-access="<?=WPT_WRIGHTS_ADMIN?>"]) th.wpt div.img {
  cursor:auto !important;
}

.wall tbody.wpt th.wpt div.img {
  margin-left:10px;
  margin-right:10px;
}

.wall th.wpt div.img img {
  max-width:100px;
  max-height:100px;
  border-radius:3px;
  box-shadow: 0 0 5px rgba(0, 0, 0, .6);
}

/* MS "clear field" X button on inputs */
input::-ms-clear {
  display: none;
}

.leader-line text {
  fill-opacity: 0 !important;
  stroke-opacity: 0 !important;
}

#userGuidePopup .latest-dt {
  position: absolute;
  top: 56px;
  right: 38px;
  font-style: italic;
  font-size: .7rem;
  background: #fff;
  padding: 2px;
  border: 1px solid var(--wall-border-color);
  border-radius: 5px;
  color: var(--modal-theme-color-dark);
  z-index: 3;
}

.accordion-button i {
  margin-right: 10px;
}

.newfeatures.latest p,
.dropdown-item:active,
.dropdown-item:hover {
  background-color: var(--wall-th-bg-color);
  border-color: var(--wall-border-color);
}

.dropdown-item:active {
  color: var(--modal-theme-color-dark);
}

#plug-rabbit {
  position:absolute;
  z-index:5002;
}

.plug-label {
  z-index: 1000;
  position: absolute;
  cursor: pointer;
  border: 1px solid #f3f3f3;
  background: #fff;
  border-radius: 5px;
  white-space: nowrap;
  font-size: .8rem;
}

[data-access="<?=WPT_WRIGHTS_RO?>"] .plug-label,
[data-access="<?=WPT_WRIGHTS_RO?>"] .plug-label a {
  cursor:auto;
}

.plug-label > .dropdown-toggle {
  color:#8c8c8c;
  padding:0 5px;
}

.plug-label:hover {
  z-index:1021;
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

#plugprop-sample {
  position:relative;
  height:100px;
  padding:10px;
  border:1px solid #f5f5f5;
  border-radius:10px;
  box-shadow: inset 0 0 10px #ececec;
}

#plugprop-sample div:first-child {
  position: fixed;
}

#plugprop-sample div:nth-child(2) {
  position:absolute;
  bottom:10px;
  right:10px;
}

#plugpropPopup input[type="number"] {
  width:4rem;
}

#plugpropPopup .items-left > div {
  float:left;
  margin-left:20px;
}

#plugpropPopup .items-left > div:first-child {
  margin-left:auto;
}

li[data-action="properties"] {
  border-top:1px solid #e9ecef;
  margin-top:3px;
  padding-top:3px;
}

.wall thead.wpt th.wpt .submenu {
  margin-top:-19px;
  margin-left:-7px;
  height:1px;
}

.wall tbody.wpt th.wpt .submenu {
  margin-top:-9px;
  margin-left:2px;
  height:1px;
}

.btn.btn-primary,
.btn.btn.btn-success {
  background-color: var(--btn-primary-bg-color);
  border-color: var(--btn-primary-border-color);
  color: #fff;
}

.btn.btn-primary:hover,
.btn.btn-success:hover {
  background-color: var(--btn-primary-border-color);
  border-color: var(--btn-primary-bg-color);
}

kbd,
.btn.btn-secondary {
  background-color: var(--btn-secondary-border-color) !important;
  border-color: var(--btn-secondary-bg-color) !important;
  color: #fff;
}

.btn.btn-secondary:hover {
  background-color: var(--btn-secondary-bg-color) !important;
  border-color: var(--btn-secondary-border-color) !important;
  color: #fff;
}

.cell-menu {
  position:absolute;
  right:-1px;
  top:-4px;
  color: var(--menubar-bg-color);
  cursor:pointer;
  padding:5px;
}

.cell-menu .wpt-badge {
  font-size: .6rem;
  margin-left: -10px;
  margin-top: -1px;
}

.topicon .wpt-badge {
  margin-left: -5px;
}

.cell-menu .btn-circle {
  width: 23px;
  height: 23px;
}

.cell-menu .btn-circle i {
  position: relative;
  margin-left: -5px;
  top: -4px;
  font-size: .8rem;
}

.cell-list-mode {
  width: 100%;
  position: absolute;
  top: 0;
  font-size: 14px;
}

.cell-list-mode ul {
  padding: 0;
  overflow-y: auto;
}

.cell-list-mode li {
  padding: 3px;
  border: 1px solid transparent;
  border-bottom: 1px dashed #a6a6a6;
  list-style: none;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  cursor: pointer;
}

.cell-list-mode li span {
  cursor: move;
  padding: 5px 10px;
  opacity: .5;
}

.wall[data-access="<?=WPT_WRIGHTS_RO?>"] .cell-list-mode li span,
.cell-list-mode li:only-of-type span {
  cursor: pointer;
}

.submenu .dropdown-menu {
  min-width: 100px;
  padding: 3px 0;
  inset: auto !important;
  border: none;
}

.submenu .dropdown-menu * {
  font-size: .8rem !important;
}

.submenu .dropdown-menu i.fa-edit {
  margin-left: 2px;
  margin-right: -2px;
}

.submenu .dropdown-toggle::after {
  content: none;
}

.submenu-link {
  position: absolute;
  z-index: calc(var(--modal-zindex) + 2);
}

.submenu-link .dropdown-menu {
  left: 5px !important;
  min-width: auto;
}

.submenu .dropdown-item {
  padding: .20rem .70rem;
}

.submenu .dropdown-divider {
  margin: .20rem 0;
}

.plug-label.submenu {
  height: auto;
}

.plug-label.submenu:hover {
  z-index: calc(var(--modal-zindex) - 1) !important;
}

.plug-label.submenu .dropdown-menu {
  top: -25px !important;
  left: 20px !important;
}

th.wpt .submenu .dropdown-menu {
  top: -28px !important;
  left: 15px !important;
  z-index: 5003 !important;
}

.postit {
  border: 1px solid #ccc;
  box-shadow: 6px 6px 7px -6px rgba(0,0,0,.8);
  z-index:1010;
  position:absolute !important;
  min-height:50px;
  min-width:160px;
  height:120px;
  width:180px;
  -webkit-touch-callout: none;
  -webkit-user-select: none;
  -khtml-user-select: none;
  -moz-user-select: none;
  -ms-user-select: none;
  /*  user-select: none;*/
}

.wall[data-access="<?=WPT_WRIGHTS_RO?>"] .postit {
  cursor:pointer;
}

.postit.hover {
  z-index:5000!important;
}

.postit .btn-menu {
  position: absolute;
  padding: 0 5px 0 5px;
  z-index: 94;
}

#mmenu i,
.postit .btn-menu i {
  cursor: pointer;
}

.postit .postit-header {
  position:absolute;
  width:100%;
  font-size:14px;
  padding:0 5px 0 30px;
  white-space: nowrap;
  z-index:93;
/*  box-shadow: 0 5px 5px -5px #b3b3b3;*/
  text-overflow: ellipsis;
  overflow:hidden;
  height:22px;
}

.wall[data-access="<?=WPT_WRIGHTS_RO?>"] .postit .postit-header {
  padding-left:5px;
}

:not([class^='color']).selected {
  background-color: var(--wall-border-color);
}

.postit.selected {
  border-color: var(--menubar-bg-color);
  border-style:dashed;
  box-shadow:0 0 25px 5px var(--postit-selected-shadow-color);
}

.postit-min.selected {
  font-weight: bold;
  border-left: 1px solid var(--menubar-bg-color);
  border-right: 1px solid var(--menubar-bg-color);
  text-shadow: 1px 1px var(--postit-selected-shadow-color);
}

.postit .postit-header i {
  cursor: pointer;
  margin-right: 10px;
}

.postit .postit-header .title {
  font-weight: bold;
}

.postit-menu {
  display: none;
  position: absolute;
  cursor: pointer;
  z-index: 94;
}

.postit-menu.right {
  max-width: 206px;
  top: 26px;
  left: -4px;
}

.postit-menu.left {
  max-width: 72px;
  top: -4px;
  left: -68px;
}

.postit-menu i {
  margin-left: -2px;
}

.postit-menu .btn-circle {
  width: 32px;
  height: 32px;
  margin: 1px;
}

.postit .postit-tags {
  display: none;
  padding: 5px;
  position: absolute;
  border-radius: 0 5px 5px 0;
  width: 25px;
  top: -9px;
  right: -13px;
  cursor: pointer;
  line-height: 20px;
  z-index: 93;
}

.wall[data-access="<?=WPT_WRIGHTS_RO?>"] .postit .postit-tags {
  cursor: auto;
}

.postit .postit-tags i {
  margin-left: -2px;
  text-shadow: 1px 2px 3px rgba(0, 0, 0, .5);
}

.postit-edit [external-src] {
  background-color: var(--wall-th-bg-color);
  border: 2px dashed var(--wall-border-color);
  box-shadow: 0 0 10px #c5c5c5;
  width: 50px;
  height: 50px;
}

.postit-edit i.externalref {
  margin-left: -35px;
  opacity: .2;
}

.postit-edit tbody, td, tfoot, th, thead, tr {
  border-style: inherit;
  border-width: inherit;
}

<?php
  foreach (WPT_MODULES['tpick']['items'] as $item => $color)
  {
echo <<<EOC
.postit .postit-tags i.fa-$item,
#tpick i.fa-$item {
  color: $color !important;
}
EOC;
  }
?>

.toolbox {
  z-index: 5003;
  display: none;
  position: fixed;
  border-radius: 3px;
  padding: 3px;
  left: 5px;
  background: var(--wall-th-bg-color);
  border: 1px solid var(--wall-border-color);
}

.toolbox .btn-close {
  float: right;
}

#mmenu .btn-close {
  margin-top: -10px;
  margin-right: -12px;
}

.chat {
  width: 250px;
  bottom: 15px;
  min-width: 200px;
}

.chat .textarea,
.chat input {
  width: 100%;
}

.chat .textarea {
  height: 100px;
  margin-bottom: 5px;
  font-size: .8rem;
  padding: 0 3px 0 3px;
  overflow-y: auto;
}

.chat ul {
  list-style-type: none;
  margin: 5px 0 5px 0;
  padding: 0;
}

.chat .textarea ul li span {
  font-weight: bold;
  opacity: .5;
}

.chat .textarea .internal {
  font-size: .7rem;
  font-weight: bold;
  font-style: italic;
  color: #000;
}

.chat li i.fas {
  color: var(--modal-theme-color-dark);
}

.chat .textarea .internal.join i {
  color: green !important;
}

.chat .textarea ul li.internal.leave i {
  color: red !important;
}

.chat .btn-primary {
  margin-top:5px;
  float: right;
}

.chat h2 {
  font-size: 15px;
  font-weight: bold;
  text-align: center;
  margin-bottom: 0;
  white-space: nowrap;
  margin: 0 20px 0 10px;
}

.filters {
  width: 90px;
  top: 60px;
}

.filters h2 {
  font-size: 15px;
  font-weight: bold;
  text-align: center;
  margin-bottom: 0;
}

.filters h3 {
  font-size: 14px;
}

.filters-items > div {
  float: left;
  background: #fff;
  border-radius: 3px;
  margin: 2px;
  padding: 5px;
  border: 1px solid #e6e6e6;
  cursor: pointer;
}

.filters-items div > div {
  float: left;
  margin: 2px;
}

.filters-items .colors > div {
  border: 1px solid #e6e6e6;
  width: 20px;
  height: 20px;
  border-radius: 3px;
}

.filters-items .colors > div.selected {
  border: 2px solid gray;
}

ul#mmenu {
  list-style-type: none;
  text-align: center;
  cursor: move;
  background: #fff;
  color: var(--modal-theme-color-dark);
  top: 60px;
  left: 60px;
  width: 50px;
  padding: 10px;
  margin-left: auto;
  margin-right: auto;
}

#mmenu .wpt-badge {
  top: 2px;
  left: 4px;
}

#mmenu button.close {
  margin-top: -18px;
  margin-right: -16px;
}

.btn-group-sm > .btn-xs, .btn-xs {
  padding: .20rem .4rem;
  font-size: .8rem;
}

.dot-theme {
  height: 25px;
  width: 25px;
  border-radius: 50%;
  display: inline-block;
  margin-right: 5px;
  cursor: pointer;
  opacity: 1;
  border: 2px solid silver;
}

.dot-theme:hover {
  opacity: .6;
  border: 2px solid #fff;
}

.btn-theme {
  color: #fff;
}

.btn-theme:hover {
  color: #fff;
}

.btn-theme-black {
  background-color: #343a40;
}

.btn-theme-default,
.btn-theme-blue {
  background-color: #007bff;
}

.btn-theme-green {
  background-color: #28a745;
}

.btn-theme-orange {
  background-color: #ffbf00;
  color: #6b6a6a;
}

.btn-theme-purple {
  background-color: #c63fca;
}

.btn-theme-red {
  background-color: #dc3545;
}

.modal.modal-sm .modal-title {
  font-size: 1em;
}

.modal-backdrop {
  background-color: transparent;
}

.modal.fade .modal-dialog {
  transition: opacity .1s linear;
  transform: none;
}

.list-group-item.collapse:hover {
  cursor: auto !important;
}

#postitAttachmentsPopup .collapsing {
  -webkit-transition: none;
  transition: none;
  display: none;
}

#postitAttachmentsPopup .edit-popup .modal-body {
  padding:0;
}

#postitAttachmentsPopup .edit-popup {
  display: none;
}

#postitAttachmentsPopup .edit-popup .img {
  text-align: center;
}

#postitAttachmentsPopup .edit-popup .img img {
  max-width: 250px;
  cursor: zoom-in;
}

#postitAttachmentsPopup .edit-popup i {
  color: #fff;
}

#postitAttachmentsPopup .edit-popup .file {
  text-align: center;
}

#postitAttachmentsPopup .file-infos {
  text-align: center;
  font-style: italic;
  font-size: .7rem;
  opacity: .5;
  margin-top: -5px;
}

#img-viewer img {
  position: absolute;
  left: 50%;
  top: 50%;
  max-height: 100%;
  max-width: 100%;
  transform: translate(-50%, -50%);
  border-radius: 10px;
  box-shadow: 1px 1px 12px var(--modal-theme-color-dark);
  z-index: calc(var(--modal-zindex) + 1);
}

#swallPopup .scroll {
  max-height: 300px;
  overflow-y: auto;
  border-radius:5px;
}

#swallPopup .scroll.one {
  overflow-y: unset;
}

#swallPopup .creator-only {
  border:1px solid #eceaea;
  border-radius: 5px;
  padding:5px;
}

#swallPopup .list-group .btn-xs i {
  color: #fff;
}

#swallPopup li .btn-share {
  float: right;
  margin-right: 10px;
}

#swallPopup .tab-content {
  border:1px solid #dee2e6;
  border-top:0;
  padding:5px;
  border-radius:0 0 5px 5px;
}

.clear-input {
  margin-left: -35px !important;
  z-index: 10 !important;
}

#usearchPopup .clear-input {
  margin-left: -29px !important;
}

.clear-input:focus,
.clear-textarea:focus {
  border-color: transparent !important;
}

.clear-textarea {
  position: absolute;
  top: 49px;
  right: 6px;
}

#postitCommentsPopup .clear-textarea {
  top:5px;
}

.toolbox.chat .clear-textarea {
  top:16px;
  left:50%;
  margin-left:-20px;
}

.btn.clear-input i,
.btn.clear-textarea i {
  color: #b9b9b9;
}

.btn.clear-input:hover i,
.btn.clear-textarea:hover i {
  color: var(--modal-theme-color-dark);
}

.newfeatures code {
  color: var(--modal-theme-color-dark);
}

.newfeatures p {
  border: 5px solid #f7f7f7;
  border-radius: 5px;
  padding: 10px;
  color: #212529;
  font-size: .9rem;
  margin-bottom: .3rem;
}

.newfeatures b i.fas {
  color: var(--btn-secondary-bg-color);
  margin-right: 5px;
}

.newfeatures b:after {
  content: " \002014"
}

.newfeatures p.warning {
  border: 2px dashed var(--modal-theme-color-dark) !important;
}

.hidden {
  display: none;
}

.userscount {
  display: block;
  position: absolute;
  border-radius: 0 5px 5px 0;
  top: 4px;
  left: 0;
  padding: 5px 0 5px 5px;
  cursor: pointer;
}

.wpt-badge {
  background-color: var(--menubar-bg-color);
  color: #fff;
  position: absolute;
  border-radius: 50%;
  font-size: .6rem;
  height: 1.5em;
  line-height: 1.5em;
  min-width: 1.5em;
  padding: 0;
  text-align: center;
  margin-left: -2px;
}

.userscount .wpt-badge {
  left: 20px;
  top: 5px;
}

.chat .usersviewcounts .wpt-badge {
  font-size: .5rem;
  margin-left: 0;
}

ul.wall-menu {
  display: inline-block;
  visibility: hidden;
  background: #fff;
  top: 60px;
  left: 5px;
  padding: 10px;
  z-index: 5003;
  width: 50px;
  color: var(--modal-theme-color-dark);
  list-style-type: none;
  cursor: move;
  text-align: center;
}

.wall-menu li {
  cursor: pointer;
  white-space: nowrap;
}

.usersviewcounts {
  display: none;
  color: var(--modal-theme-color-dark);
  z-index: 1030;
  cursor: pointer;
}

li.divider {
  border-top:1px dashed #c7c6c6;
  margin:5px 0 10px 0;
}

i.notset {
  opacity: .3;
}

.wall-menu .wpt-badge {
  margin-left: -30px;
  margin-top: 11px;
}

#mmenu li:not(:last-child) i.fa-lg,
.wall-menu li:not(:last-child) i.fa-lg {
  margin-bottom: 10px;
}

.chat .usersviewcounts {
  position: relative;
  display: inline-block;
}

.postit .topicon {
  position: absolute;
  top: -28px;
  cursor: pointer;
  z-index: 93;
  text-shadow: 1px 1px 3px rgba(0, 0, 0, .5);
}

.postit .topicon i {
  opacity: .4;
}

.postit .topicon > div {
  float: left;
  padding: 5px 9px 0 0;
}

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

.list-group li div.label {
  display: inline-block;
  white-space: nowrap;
  max-width: 85%;
  text-overflow: ellipsis !important;
  overflow: hidden !important;
}

.modal-body .list-group span.name {
  margin-left:20px;
}

.modal-body .list-group span.desc {
  font-style: italic;
  font-size: .8rem;
  opacity: .5;
}

.nav-item .icon i,
.list-group-item i {
  margin-right: 5px;
  color: var(--modal-theme-color-dark);
}

.nav-item .icon i.wallname-icon {
  font-size: .7rem;
  position: absolute;
  margin-top: -4px;
  opacity: .6;
}

.nav-item .icon i.fa-user-slash.wallname-icon {
  margin-left:-14px;
}

.nav-item .icon i.fa-share.wallname-icon {
  margin-left:-10px;
}

.list-group-item {
  background: var(--wall-th-bg-color);
}

.accordion-button:hover,
.list-group-item:hover {
  background: var(--wall-border-color);
}

.accordion-button {
  cursor: pointer;
  color: var(--modal-theme-color-dark);
  background-color: rgba(0,0,0,.03);
}

.accordion-button:not(.collapsed) {
  color: var(--modal-theme-color-dark);
  background: var(--wall-border-color);
  box-shadow: inset 0px -4px 0 #c0c0c082;
}

.list-group-item .accordion-body {
  background:#fff;
}

#postitAttachmentsPopup .list-group-item {
  padding:0;
}

.accordion-button::after {
  visibility: hidden;
}

.list-group-item.active {
  color: inherit;
  background: var(--wall-th-bg-color);
  border: 1px solid rgba(0,0,0,.125);
}

.result .closemenu {
  display: none;
  border-left: 1px solid #ced4da;
  border-right: 1px solid #ced4da;
}

.form-control:disabled,
.form-control[readonly] {
  background-color: var(--wall-th-bg-color);
}

.input-group-text {
  background: #fff;
  color: var(--modal-theme-color-dark);
  border-radius: .25rem 0 0 .25rem;
}

.btn.btn-change-input {
  color: var(--modal-theme-color-dark);
  border: 1px solid #ced4da;
}

.btn.btn-change-input:hover {
  border: 1px solid #ced4da;
}

.form-check.disabled {
  opacity: .6;
}

#swallPopup .delegate-admin-only {
  font-size: .9rem;
  opacity: .6;
  margin-top: -10px;
  margin-bottom: 20px;
}

#groupAccessPopup .input-group span {
  color: var(--modal-theme-color-dark);
  margin-right: 10px;
}

textarea:focus,
input:focus,
button:focus,
a.btn:focus {
  border-color: var(--btn-secondary-border-color) !important;
  box-shadow: none !important;
  outline: 0 !important;
}

<?php
  foreach (WPT_MODULES['cpick']['items'] as $name => $color)
  {
echo <<<EOC
.color-$name,
.postit.color-$name .postit-header,
.postit.color-$name .postit-edit,
.postit.color-$name .dates {
  background:$color!important;
  color:#43474a;
}
EOC;
  }
?>

.modal a:not(.list-group-item):not(.close):not(.btn),
.modal span.name i,
.popover a,
.msg-userref {
  color: var(--modal-links-color);
}

#accountPopup i.fa-bomb {
  color: var(--modal-theme-color-dark);
}

#changePasswordPopup,
#updateOneInputPopup,
#userViewPopup {
  z-index:5018;
}

i.fa-xs {
  vertical-align: middle;
}

.modal-header {
  padding: .9rem;
}

.modal-footer {
  padding: .5rem;
}

.popover-header,
.modal:not(.no-theme) .modal-header,
.modal:not(.no-theme) .modal-title,
.modal:not(.no-theme) .modal-footer {
  background: var(--modal-1-theme-bg-color) !important;
  color: var(--modal-1-theme-color);
}

.modal.modal-sm:not(.no-theme) .modal-header,
.modal.modal-sm:not(.no-theme) .modal-title,
.modal.modal-sm:not(.no-theme) .modal-footer {
  background: var(--modal-2-theme-bg-color) !important;
  color: var(--modal-2-theme-color);
}

.modal.modal-sm .modal-header,
.modal.modal-sm .modal-title,
.modal.modal-sm .modal-footer {
  background: var(--modal-links-color);
  color:#fff;
}

.modal:not(.no-theme) .modal-content {
  border: none !important;
  /*border: 2px solid var(--modal-1-theme-bg-color);*/
}

.modal.modal-sm:not(.no-theme) .modal-content {
  border: none !important;
  /*border: 2px solid var(--modal-2-theme-bg-color);*/
}

.modal.no-theme .modal-content {
  border: none !important;
}

#postitUpdatePopup .modal-body {
  min-height:375px;
}

.slider label span {
  color: var(--modal-theme-color-dark);
}

.slider input {
  cursor: pointer;
}

.upload,
.download {
  display: none;
}

.nousers-title {
  font-size: .9rem;
}

.postit .postit-edit {
  position: absolute;
  font-size: 12px;
  height: 100%;
  width: 100%;
  overflow: auto;
  border-radius: 0 1px 1px 1px;
  padding: 25px 5px 20px 5px;
}

.postit .postit-progress-container {
  position: absolute;
  height: 100%;
  width: 12px;
  box-shadow: inset 0 0 5px rgba(0, 0, 0, .5);
  border-radius: 0 0 5px 5px;
  padding: 1px;
  margin-left: -14px;
}

.wall[data-access="<?=WPT_WRIGHTS_RO?>"] .postit .postit-progress-container {
  cursor: auto !important;
}

.postit-min .postit-progress-container {
  padding: 1px;
  background: #fff;
  border-radius: 3px;
  box-shadow: inset 0 0 3px rgba(0, 0, 0, .5);
  margin-bottom: -2px;
  margin-top: -3px;
}

.postit .postit-progress {
  position: absolute;
  bottom: 1px;
  max-height: 98%;
  width: 10px;
  z-index: 92;
  border-radius: 0 0 5px 5px;
  box-shadow: inset 0 -2px 5px rgba(0, 0, 0, .5);
  opacity: .8;
}

.postit-min .postit-progress {
  width:100%;
  height:3px;
  border-radius:5px;
  line-height:3px;
}

.postit-min .postit-progress span {
  position: absolute;
  font-size: .6rem;
  font-weight: bold;
  color: #000;
  margin-top: -5px;
  left: 50%;
}

.postit .postit-progress-container span {
  font-weight: bold;
}

.postit .postit-progress-container div:first-child {
  position: absolute;
  left: -8px;
  width: 26px;
  transform: translateY(-50%);
  -webkit-transform: rotate(270deg);
  font-size: 10px;
  bottom: 50%;
  z-index: 93;
}

.postit-edit p,
#postitViewPopup .modal-body p {
  margin: 0;
}

.ui-icon-gripsmall-diagonal-se {
  z-index: 92 !important;
}

.postit .dates {
  position: absolute;
  bottom: 0;
  left: 0;
  width: 100%;
  font-size: .6rem;
  box-shadow: 0 -5px 5px -5px #b3b3b3;
  border-radius: 0 0 1px 1px;
  z-index: 92;
}

.postit .end i {
  margin-top: 3px;
  margin-right: -20px;
  text-shadow: 1px 1px 3px rgba(0, 0, 0, .5);
}

.postit.obsolete .dates {
  color: #fff !important;
  font-weight: bold;
  background-color: red !important;
}

.postit .dates div {
  display: inline-block;
}

.postit .creation span {
  margin-left:5px;
  opacity:.6;
}

.postit .end {
  position: absolute;
  cursor: pointer;
  right: 15px;
  top: -5px;
  padding: 5px 10px 0 0;
}

.wall[data-access="<?=WPT_WRIGHTS_RO?>"] .postit .end {
  cursor: auto;
}

.postit .end span {
  margin-left: 22px;
}

.postit .end.with-alert {
  font-weight: bold;
  text-shadow: 1px 2px 3px rgba(0, 0, 0, .5);
}

#dpickPopup .dpick a {
  color: var(--modal-theme-color-dark);
}

#dpickPopup .dpick a.ui-state-active {
  color: #fff;
  font-weight: bold;
}

#dpickPopup .dpick-notify {
  font-size: .9rem;
  text-align: left;
}

#dpickPopup .dpick-notify input[type="number"] {
  width: 3rem;
}

.ui-datepicker {
  z-index: 5020 !important;
}

button.ui-datepicker-close {
  font-size: .8rem;
}

#tpick {
  width: 100px;
  text-align: center;
}

#tpick > .selected,
.filters-items .tags > .selected {
  background-color: #dadada;
  border: 1px solid silver;
  border-radius: 5px;
}

#tpick > div,
.filters-items .tags > div {
  float: left;
  border: 1px solid transparent;
  margin: 2px;
}

#tpick > div i,
.filters-items .tags > div i {
  margin: 2px;
  color: var(--modal-theme-color-dark);
}

#tpick,
#cpick {
  z-index: 5020;
  visibility: hidden;
  position: absolute;
  cursor: pointer;
  background-color: #fff;
  border: 1px solid #e6e6e6;
  padding: 3px;
  border-radius: 5px;
}

#cpick {
  width: 87px;
}

#cpick > div {
  float: left;
  width: 20px;
  height: 20px;
  margin: 3px;
  border: 1px solid #e6e6e6;
  border-radius: 3px;
}

.resizable-helper {
  border: 1px dashed var(--modal-theme-color-dark);
}

.locale-picker > div {
  display: inline-block;
  padding: 0 3px 0 3px;
  border-radius: .25rem;
  border: 1px solid var(--wall-border-color);
  cursor: pointer;
  margin-right: 3px;
}

.locale-picker > .selected {
  background-color: var(--wall-th-bg-color);
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
}

/******************************** Login page ********************************/

#login-page .navbar {
  padding-left: 20px;
  padding-right: 20px;
}

#login-page .navbar-brand {
  background: none;
  color: #fff !important;
}

#login-page .themes * {
  float: left;
  margin-bottom: 2px;
}

#login-page .main-login {
  height: 80%;
  width: 100%;
  margin-top: 80px;
}

#login-page .links a,
#login-page .remember label {
  color: var(--usercard-links-color);
  cursor: pointer;
  text-decoration: none;
}

#login-page .nowelcome label {
  color: #35b351;
}

#login-page .container {
  display: flex;
  flex-direction: column;
  align-items: center;
}

#login-page #desc-container {
  position: absolute;
  top: 80px;
  width: 100%;
  z-index: 2;
  text-align: center;
}

#login-page #desc-container .alert {
  display: inline-block;
  margin: 10px;
  max-width: 600px;
  text-align: justify;
  -webkit-hyphens: auto;
  -moz-hyphens: auto;
  -ms-hyphens: auto;
  -o-hyphens: auto;
   hyphens: auto;
}

#login-page .user-card {
  z-index: 1; 
  min-width: 200px;
  max-width: 600px;
  width: 100%;
  border: 5px solid var(--wall-border-color);
  padding: 20px;
  border-radius: 20px;
}

#login-page .brand-logo {
  height: 100px;
  width: 100px;
  border-radius: 50%;
  border: 2px solid white;
}

#login-page .form-container {
  padding: 20px;
}

#login-page #login button {
  width: 100%;
}

#login-page .user-card form span.required {
  background: #5e666f;
  color: #ff8383;
  border-radius: 20px;
  padding: 2px;
}
