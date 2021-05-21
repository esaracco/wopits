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
		global.jqueryUiDatepickerNb = mod.exports;
	}
})(this, function (module, exports) {
	'use strict';

	Object.defineProperty(exports, "__esModule", {
		value: true
	});

	exports.default = function (jQuery) {
		/* Norwegian Bokmål initialisation for the jQuery UI date picker plugin. */
		/* Written by Bjørn Johansen (post@bjornjohansen.no). */
		jQuery(function ($) {
			$.datepicker.regional['nb'] = {
				closeText: 'Lukk',
				prevText: '&#xAB;Forrige',
				nextText: 'Neste&#xBB;',
				currentText: 'I dag',
				monthNames: ['januar', 'februar', 'mars', 'april', 'mai', 'juni', 'juli', 'august', 'september', 'oktober', 'november', 'desember'],
				monthNamesShort: ['jan', 'feb', 'mar', 'apr', 'mai', 'jun', 'jul', 'aug', 'sep', 'okt', 'nov', 'des'],
				dayNamesShort: ['søn', 'man', 'tir', 'ons', 'tor', 'fre', 'lør'],
				dayNames: ['søndag', 'mandag', 'tirsdag', 'onsdag', 'torsdag', 'fredag', 'lørdag'],
				dayNamesMin: ['sø', 'ma', 'ti', 'on', 'to', 'fr', 'lø'],
				weekHeader: 'Uke',
				dateFormat: 'dd.mm.yy',
				firstDay: 1,
				isRTL: false,
				showMonthAfterYear: false,
				yearSuffix: ''
			};
			$.datepicker.setDefaults($.datepicker.regional['nb']);
		});
	};

	;
	module.exports = exports['default'];
});