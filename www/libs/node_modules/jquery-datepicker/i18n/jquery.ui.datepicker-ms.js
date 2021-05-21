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
		global.jqueryUiDatepickerMs = mod.exports;
	}
})(this, function (module, exports) {
	'use strict';

	Object.defineProperty(exports, "__esModule", {
		value: true
	});

	exports.default = function (jQuery) {
		/* Malaysian initialisation for the jQuery UI date picker plugin. */
		/* Written by Mohd Nawawi Mohamad Jamili (nawawi@ronggeng.net). */
		jQuery(function ($) {
			$.datepicker.regional['ms'] = {
				closeText: 'Tutup',
				prevText: '&#x3C;Sebelum',
				nextText: 'Selepas&#x3E;',
				currentText: 'hari ini',
				monthNames: ['Januari', 'Februari', 'Mac', 'April', 'Mei', 'Jun', 'Julai', 'Ogos', 'September', 'Oktober', 'November', 'Disember'],
				monthNamesShort: ['Jan', 'Feb', 'Mac', 'Apr', 'Mei', 'Jun', 'Jul', 'Ogo', 'Sep', 'Okt', 'Nov', 'Dis'],
				dayNames: ['Ahad', 'Isnin', 'Selasa', 'Rabu', 'Khamis', 'Jumaat', 'Sabtu'],
				dayNamesShort: ['Aha', 'Isn', 'Sel', 'Rab', 'kha', 'Jum', 'Sab'],
				dayNamesMin: ['Ah', 'Is', 'Se', 'Ra', 'Kh', 'Ju', 'Sa'],
				weekHeader: 'Mg',
				dateFormat: 'dd/mm/yy',
				firstDay: 0,
				isRTL: false,
				showMonthAfterYear: false,
				yearSuffix: '' };
			$.datepicker.setDefaults($.datepicker.regional['ms']);
		});
	};

	;
	module.exports = exports['default'];
});