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
		global.jqueryUiDatepickerDa = mod.exports;
	}
})(this, function (module, exports) {
	'use strict';

	Object.defineProperty(exports, "__esModule", {
		value: true
	});

	exports.default = function (jQuery) {
		/* Danish initialisation for the jQuery UI date picker plugin. */
		/* Written by Jan Christensen ( deletestuff@gmail.com). */
		jQuery(function ($) {
			$.datepicker.regional['da'] = {
				closeText: 'Luk',
				prevText: '&#x3C;Forrige',
				nextText: 'Næste&#x3E;',
				currentText: 'Idag',
				monthNames: ['Januar', 'Februar', 'Marts', 'April', 'Maj', 'Juni', 'Juli', 'August', 'September', 'Oktober', 'November', 'December'],
				monthNamesShort: ['Jan', 'Feb', 'Mar', 'Apr', 'Maj', 'Jun', 'Jul', 'Aug', 'Sep', 'Okt', 'Nov', 'Dec'],
				dayNames: ['Søndag', 'Mandag', 'Tirsdag', 'Onsdag', 'Torsdag', 'Fredag', 'Lørdag'],
				dayNamesShort: ['Søn', 'Man', 'Tir', 'Ons', 'Tor', 'Fre', 'Lør'],
				dayNamesMin: ['Sø', 'Ma', 'Ti', 'On', 'To', 'Fr', 'Lø'],
				weekHeader: 'Uge',
				dateFormat: 'dd-mm-yy',
				firstDay: 1,
				isRTL: false,
				showMonthAfterYear: false,
				yearSuffix: '' };
			$.datepicker.setDefaults($.datepicker.regional['da']);
		});
	};

	;
	module.exports = exports['default'];
});