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
		global.jqueryUiDatepickerArDZ = mod.exports;
	}
})(this, function (module, exports) {
	'use strict';

	Object.defineProperty(exports, "__esModule", {
		value: true
	});

	exports.default = function (jQuery) {
		/* Algerian Arabic Translation for jQuery UI date picker plugin. (can be used for Tunisia)*/
		/* Mohamed Cherif BOUCHELAGHEM -- cherifbouchelaghem@yahoo.fr */

		jQuery(function ($) {
			$.datepicker.regional['ar-DZ'] = {
				closeText: 'إغلاق',
				prevText: '&#x3C;السابق',
				nextText: 'التالي&#x3E;',
				currentText: 'اليوم',
				monthNames: ['جانفي', 'فيفري', 'مارس', 'أفريل', 'ماي', 'جوان', 'جويلية', 'أوت', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'],
				monthNamesShort: ['1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12'],
				dayNames: ['الأحد', 'الاثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت'],
				dayNamesShort: ['الأحد', 'الاثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت'],
				dayNamesMin: ['الأحد', 'الاثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت'],
				weekHeader: 'أسبوع',
				dateFormat: 'dd/mm/yy',
				firstDay: 6,
				isRTL: true,
				showMonthAfterYear: false,
				yearSuffix: '' };
			$.datepicker.setDefaults($.datepicker.regional['ar-DZ']);
		});
	};

	;
	module.exports = exports['default'];
});