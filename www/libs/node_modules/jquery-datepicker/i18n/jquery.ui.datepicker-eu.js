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
		global.jqueryUiDatepickerEu = mod.exports;
	}
})(this, function (module, exports) {
	'use strict';

	Object.defineProperty(exports, "__esModule", {
		value: true
	});

	exports.default = function (jQuery) {
		/* Euskarako oinarria 'UI date picker' jquery-ko extentsioarentzat */
		/* Karrikas-ek itzulia (karrikas@karrikas.com) */
		jQuery(function ($) {
			$.datepicker.regional['eu'] = {
				closeText: 'Egina',
				prevText: '&#x3C;Aur',
				nextText: 'Hur&#x3E;',
				currentText: 'Gaur',
				monthNames: ['urtarrila', 'otsaila', 'martxoa', 'apirila', 'maiatza', 'ekaina', 'uztaila', 'abuztua', 'iraila', 'urria', 'azaroa', 'abendua'],
				monthNamesShort: ['urt.', 'ots.', 'mar.', 'api.', 'mai.', 'eka.', 'uzt.', 'abu.', 'ira.', 'urr.', 'aza.', 'abe.'],
				dayNames: ['igandea', 'astelehena', 'asteartea', 'asteazkena', 'osteguna', 'ostirala', 'larunbata'],
				dayNamesShort: ['ig.', 'al.', 'ar.', 'az.', 'og.', 'ol.', 'lr.'],
				dayNamesMin: ['ig', 'al', 'ar', 'az', 'og', 'ol', 'lr'],
				weekHeader: 'As',
				dateFormat: 'yy-mm-dd',
				firstDay: 1,
				isRTL: false,
				showMonthAfterYear: false,
				yearSuffix: '' };
			$.datepicker.setDefaults($.datepicker.regional['eu']);
		});
	};

	;
	module.exports = exports['default'];
});