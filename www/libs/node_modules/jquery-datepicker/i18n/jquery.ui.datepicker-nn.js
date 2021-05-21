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
		global.jqueryUiDatepickerNn = mod.exports;
	}
})(this, function (module, exports) {
	'use strict';

	Object.defineProperty(exports, "__esModule", {
		value: true
	});

	exports.default = function (jQuery) {
		/* Norwegian Nynorsk initialisation for the jQuery UI date picker plugin. */
		/* Written by Bjørn Johansen (post@bjornjohansen.no). */
		jQuery(function ($) {
			$.datepicker.regional['nn'] = {
				closeText: 'Lukk',
				prevText: '&#xAB;Førre',
				nextText: 'Neste&#xBB;',
				currentText: 'I dag',
				monthNames: ['januar', 'februar', 'mars', 'april', 'mai', 'juni', 'juli', 'august', 'september', 'oktober', 'november', 'desember'],
				monthNamesShort: ['jan', 'feb', 'mar', 'apr', 'mai', 'jun', 'jul', 'aug', 'sep', 'okt', 'nov', 'des'],
				dayNamesShort: ['sun', 'mån', 'tys', 'ons', 'tor', 'fre', 'lau'],
				dayNames: ['sundag', 'måndag', 'tysdag', 'onsdag', 'torsdag', 'fredag', 'laurdag'],
				dayNamesMin: ['su', 'må', 'ty', 'on', 'to', 'fr', 'la'],
				weekHeader: 'Veke',
				dateFormat: 'dd.mm.yy',
				firstDay: 1,
				isRTL: false,
				showMonthAfterYear: false,
				yearSuffix: ''
			};
			$.datepicker.setDefaults($.datepicker.regional['nn']);
		});
	};

	;
	module.exports = exports['default'];
});