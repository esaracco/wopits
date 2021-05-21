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
		global.jqueryUiDatepickerBs = mod.exports;
	}
})(this, function (module, exports) {
	'use strict';

	Object.defineProperty(exports, "__esModule", {
		value: true
	});

	exports.default = function (jQuery) {
		/* Bosnian i18n for the jQuery UI date picker plugin. */
		/* Written by Kenan Konjo. */
		jQuery(function ($) {
			$.datepicker.regional['bs'] = {
				closeText: 'Zatvori',
				prevText: '&#x3C;',
				nextText: '&#x3E;',
				currentText: 'Danas',
				monthNames: ['Januar', 'Februar', 'Mart', 'April', 'Maj', 'Juni', 'Juli', 'August', 'Septembar', 'Oktobar', 'Novembar', 'Decembar'],
				monthNamesShort: ['Jan', 'Feb', 'Mar', 'Apr', 'Maj', 'Jun', 'Jul', 'Aug', 'Sep', 'Okt', 'Nov', 'Dec'],
				dayNames: ['Nedelja', 'Ponedeljak', 'Utorak', 'Srijeda', 'Četvrtak', 'Petak', 'Subota'],
				dayNamesShort: ['Ned', 'Pon', 'Uto', 'Sri', 'Čet', 'Pet', 'Sub'],
				dayNamesMin: ['Ne', 'Po', 'Ut', 'Sr', 'Če', 'Pe', 'Su'],
				weekHeader: 'Wk',
				dateFormat: 'dd.mm.yy',
				firstDay: 1,
				isRTL: false,
				showMonthAfterYear: false,
				yearSuffix: '' };
			$.datepicker.setDefaults($.datepicker.regional['bs']);
		});
	};

	;
	module.exports = exports['default'];
});