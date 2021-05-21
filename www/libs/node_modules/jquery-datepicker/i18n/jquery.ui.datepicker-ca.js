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
		global.jqueryUiDatepickerCa = mod.exports;
	}
})(this, function (module, exports) {
	'use strict';

	Object.defineProperty(exports, "__esModule", {
		value: true
	});

	exports.default = function (jQuery) {
		/* Inicialització en català per a l'extensió 'UI date picker' per jQuery. */
		/* Writers: (joan.leon@gmail.com). */
		jQuery(function ($) {
			$.datepicker.regional['ca'] = {
				closeText: 'Tanca',
				prevText: 'Anterior',
				nextText: 'Següent',
				currentText: 'Avui',
				monthNames: ['gener', 'febrer', 'març', 'abril', 'maig', 'juny', 'juliol', 'agost', 'setembre', 'octubre', 'novembre', 'desembre'],
				monthNamesShort: ['gen', 'feb', 'març', 'abr', 'maig', 'juny', 'jul', 'ag', 'set', 'oct', 'nov', 'des'],
				dayNames: ['diumenge', 'dilluns', 'dimarts', 'dimecres', 'dijous', 'divendres', 'dissabte'],
				dayNamesShort: ['dg', 'dl', 'dt', 'dc', 'dj', 'dv', 'ds'],
				dayNamesMin: ['dg', 'dl', 'dt', 'dc', 'dj', 'dv', 'ds'],
				weekHeader: 'Set',
				dateFormat: 'dd/mm/yy',
				firstDay: 1,
				isRTL: false,
				showMonthAfterYear: false,
				yearSuffix: '' };
			$.datepicker.setDefaults($.datepicker.regional['ca']);
		});
	};

	;
	module.exports = exports['default'];
});