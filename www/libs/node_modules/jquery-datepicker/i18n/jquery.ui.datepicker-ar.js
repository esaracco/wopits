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
		global.jqueryUiDatepickerAr = mod.exports;
	}
})(this, function (module, exports) {
	'use strict';

	Object.defineProperty(exports, "__esModule", {
		value: true
	});

	exports.default = function (jQuery) {
		/* Arabic Translation for jQuery UI date picker plugin. */
		/* Khaled Alhourani -- me@khaledalhourani.com */
		/* NOTE: monthNames are the original months names and they are the Arabic names, not the new months name فبراير - يناير and there isn't any Arabic roots for these months */
		jQuery(function ($) {
			$.datepicker.regional['ar'] = {
				closeText: 'إغلاق',
				prevText: '&#x3C;السابق',
				nextText: 'التالي&#x3E;',
				currentText: 'اليوم',
				monthNames: ['كانون الثاني', 'شباط', 'آذار', 'نيسان', 'مايو', 'حزيران', 'تموز', 'آب', 'أيلول', 'تشرين الأول', 'تشرين الثاني', 'كانون الأول'],
				monthNamesShort: ['1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12'],
				dayNames: ['الأحد', 'الاثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت'],
				dayNamesShort: ['الأحد', 'الاثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت'],
				dayNamesMin: ['ح', 'ن', 'ث', 'ر', 'خ', 'ج', 'س'],
				weekHeader: 'أسبوع',
				dateFormat: 'dd/mm/yy',
				firstDay: 6,
				isRTL: true,
				showMonthAfterYear: false,
				yearSuffix: '' };
			$.datepicker.setDefaults($.datepicker.regional['ar']);
		});
	};

	;
	module.exports = exports['default'];
});