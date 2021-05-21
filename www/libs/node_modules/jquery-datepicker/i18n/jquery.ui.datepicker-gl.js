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
		global.jqueryUiDatepickerGl = mod.exports;
	}
})(this, function (module, exports) {
	'use strict';

	Object.defineProperty(exports, "__esModule", {
		value: true
	});

	exports.default = function (jQuery) {
		/* Galician localization for 'UI date picker' jQuery extension. */
		/* Translated by Jorge Barreiro <yortx.barry@gmail.com>. */
		jQuery(function ($) {
			$.datepicker.regional['gl'] = {
				closeText: 'Pechar',
				prevText: '&#x3C;Ant',
				nextText: 'Seg&#x3E;',
				currentText: 'Hoxe',
				monthNames: ['Xaneiro', 'Febreiro', 'Marzo', 'Abril', 'Maio', 'Xuño', 'Xullo', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Decembro'],
				monthNamesShort: ['Xan', 'Feb', 'Mar', 'Abr', 'Mai', 'Xuñ', 'Xul', 'Ago', 'Set', 'Out', 'Nov', 'Dec'],
				dayNames: ['Domingo', 'Luns', 'Martes', 'Mércores', 'Xoves', 'Venres', 'Sábado'],
				dayNamesShort: ['Dom', 'Lun', 'Mar', 'Mér', 'Xov', 'Ven', 'Sáb'],
				dayNamesMin: ['Do', 'Lu', 'Ma', 'Mé', 'Xo', 'Ve', 'Sá'],
				weekHeader: 'Sm',
				dateFormat: 'dd/mm/yy',
				firstDay: 1,
				isRTL: false,
				showMonthAfterYear: false,
				yearSuffix: '' };
			$.datepicker.setDefaults($.datepicker.regional['gl']);
		});
	};

	;
	module.exports = exports['default'];
});