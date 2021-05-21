(function (global, factory) {
	if (typeof define === "function" && define.amd) {
		define(['module', 'exports'], factory);
	} else if (typeof exports !== "undefined") {
		factory(module, exports);
	} else {
		var mod = {
			exports: {}
		};
		factory(mod, mod.exports);
		global.jqueryUiDatepickerFa = mod.exports;
	}
})(this, function (module, exports) {
	'use strict';

	Object.defineProperty(exports, "__esModule", {
		value: true
	});

	exports.default = function (jQuery) {
		/* Persian (Farsi) Translation for the jQuery UI date picker plugin. */
		/* Javad Mowlanezhad -- jmowla@gmail.com */
		/* Jalali calendar should supported soon! (Its implemented but I have to test it) */
		jQuery(function ($) {
			$.datepicker.regional['fa'] = {
				closeText: 'بستن',
				prevText: '&#x3C;قبلی',
				nextText: 'بعدی&#x3E;',
				currentText: 'امروز',
				monthNames: ['فروردين', 'ارديبهشت', 'خرداد', 'تير', 'مرداد', 'شهريور', 'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند'],
				monthNamesShort: ['1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12'],
				dayNames: ['يکشنبه', 'دوشنبه', 'سه‌شنبه', 'چهارشنبه', 'پنجشنبه', 'جمعه', 'شنبه'],
				dayNamesShort: ['ی', 'د', 'س', 'چ', 'پ', 'ج', 'ش'],
				dayNamesMin: ['ی', 'د', 'س', 'چ', 'پ', 'ج', 'ش'],
				weekHeader: 'هف',
				dateFormat: 'yy/mm/dd',
				firstDay: 6,
				isRTL: true,
				showMonthAfterYear: false,
				yearSuffix: '' };
			$.datepicker.setDefaults($.datepicker.regional['fa']);
		});
	};

	;
	module.exports = exports['default'];
});