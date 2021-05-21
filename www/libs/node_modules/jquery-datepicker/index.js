// Testing/Dev via Parcel

import $ from 'jquery';
import datepickerFactory from './jquery-datepicker';
import datepickerJAFactory from './i18n/jquery.ui.datepicker-ja';

datepickerFactory($);
datepickerJAFactory($);

$(function() {
  $('#datepicker').datepicker();
  $.datepicker.regional['ja'];
});
