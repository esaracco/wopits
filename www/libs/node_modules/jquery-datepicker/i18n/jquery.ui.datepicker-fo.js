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
		global.jqueryUiDatepickerFo = mod.exports;
	}
})(this, function (module, exports) {
	'use strict';

	Object.defineProperty(exports, "__esModule", {
		value: true
	});

	exports.default = function (jQuery) {
		/* Faroese initialisation for the jQuery UI date picker plugin */
		/* Written by Sverri Mohr Olsen, sverrimo@gmail.com */
		jQuery(function ($) {
			$.datepicker.regional['fo'] = {
				closeText: 'Lat aftur',
				prevText: '&#x3C;Fyrra',
				nextText: 'Næsta&#x3E;',
				currentText: 'Í dag',
				monthNames: ['Januar', 'Februar', 'Mars', 'Apríl', 'Mei', 'Juni', 'Juli', 'August', 'September', 'Oktober', 'November', 'Desember'],
				monthNamesShort: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Aug', 'Sep', 'Okt', 'Nov', 'Des'],
				dayNames: ['Sunnudagur', 'Mánadagur', 'Týsdagur', 'Mikudagur', 'Hósdagur', 'Fríggjadagur', 'Leyardagur'],
				dayNamesShort: ['Sun', 'Mán', 'Týs', 'Mik', 'Hós', 'Frí', 'Ley'],
				dayNamesMin: ['Su', 'Má', 'Tý', 'Mi', 'Hó', 'Fr', 'Le'],
				weekHeader: 'Vk',
				dateFormat: 'dd-mm-yy',
				firstDay: 1,
				isRTL: false,
				showMonthAfterYear: false,
				yearSuffix: '' };
			$.datepicker.setDefaults($.datepicker.regional['fo']);
		});
	};

	;
	module.exports = exports['default'];
});