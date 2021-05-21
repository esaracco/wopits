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
		global.jqueryUiDatepickerRm = mod.exports;
	}
})(this, function (module, exports) {
	'use strict';

	Object.defineProperty(exports, "__esModule", {
		value: true
	});

	exports.default = function (jQuery) {
		/* Romansh initialisation for the jQuery UI date picker plugin. */
		/* Written by Yvonne Gienal (yvonne.gienal@educa.ch). */
		jQuery(function ($) {
			$.datepicker.regional['rm'] = {
				closeText: 'Serrar',
				prevText: '&#x3C;Suandant',
				nextText: 'Precedent&#x3E;',
				currentText: 'Actual',
				monthNames: ['Schaner', 'Favrer', 'Mars', 'Avrigl', 'Matg', 'Zercladur', 'Fanadur', 'Avust', 'Settember', 'October', 'November', 'December'],
				monthNamesShort: ['Scha', 'Fev', 'Mar', 'Avr', 'Matg', 'Zer', 'Fan', 'Avu', 'Sett', 'Oct', 'Nov', 'Dec'],
				dayNames: ['Dumengia', 'Glindesdi', 'Mardi', 'Mesemna', 'Gievgia', 'Venderdi', 'Sonda'],
				dayNamesShort: ['Dum', 'Gli', 'Mar', 'Mes', 'Gie', 'Ven', 'Som'],
				dayNamesMin: ['Du', 'Gl', 'Ma', 'Me', 'Gi', 'Ve', 'So'],
				weekHeader: 'emna',
				dateFormat: 'dd/mm/yy',
				firstDay: 1,
				isRTL: false,
				showMonthAfterYear: false,
				yearSuffix: '' };
			$.datepicker.setDefaults($.datepicker.regional['rm']);
		});
	};

	;
	module.exports = exports['default'];
});