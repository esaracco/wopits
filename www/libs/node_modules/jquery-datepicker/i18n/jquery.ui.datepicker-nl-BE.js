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
		global.jqueryUiDatepickerNlBE = mod.exports;
	}
})(this, function (module, exports) {
	'use strict';

	Object.defineProperty(exports, "__esModule", {
		value: true
	});

	exports.default = function (jQuery) {
		/* Dutch (Belgium) initialisation for the jQuery UI date picker plugin. */
		/* David De Sloovere @DavidDeSloovere */
		jQuery(function ($) {
			$.datepicker.regional['nl-BE'] = {
				closeText: 'Sluiten',
				prevText: '←',
				nextText: '→',
				currentText: 'Vandaag',
				monthNames: ['januari', 'februari', 'maart', 'april', 'mei', 'juni', 'juli', 'augustus', 'september', 'oktober', 'november', 'december'],
				monthNamesShort: ['jan', 'feb', 'mrt', 'apr', 'mei', 'jun', 'jul', 'aug', 'sep', 'okt', 'nov', 'dec'],
				dayNames: ['zondag', 'maandag', 'dinsdag', 'woensdag', 'donderdag', 'vrijdag', 'zaterdag'],
				dayNamesShort: ['zon', 'maa', 'din', 'woe', 'don', 'vri', 'zat'],
				dayNamesMin: ['zo', 'ma', 'di', 'wo', 'do', 'vr', 'za'],
				weekHeader: 'Wk',
				dateFormat: 'dd/mm/yy',
				firstDay: 1,
				isRTL: false,
				showMonthAfterYear: false,
				yearSuffix: '' };
			$.datepicker.setDefaults($.datepicker.regional['nl-BE']);
		});
	};

	;
	module.exports = exports['default'];
});