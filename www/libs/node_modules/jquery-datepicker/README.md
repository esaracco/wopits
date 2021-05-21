# jquery-datepicker

Just the datepicker lib from jquery-ui 1.12.1

The module itself and translation files are split out into factory modules for smaller builds

```js
import $ from 'jquery';
import datepickerFactory from 'jquery-datepicker';
import datepickerJAFactory from 'jquery-datepicker/i18n/jquery.ui.datepicker-ja';

// Just pass your jquery instance and you're done
datepickerFactory($);
datepickerJAFactory($);

$(function() {
  $('#datepicker').datepicker();
  $.datepicker.regional['ja'];
});
```

If you aren't using a bundler, `jqueryDatepicker` will become an available global factory once the main file is consumed.

```js
require('jquery-datepicker');

$(function() {
  jqueryDatepicker($);
  $('#datepicker').datepicker();
  $.datepicker.regional['ja'];
});

```