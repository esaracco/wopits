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
		global.jqueryUiDatepickerTj = mod.exports;
	}
})(this, function (module, exports) {
	'use strict';

	Object.defineProperty(exports, "__esModule", {
		value: true
	});

	exports.default = function (jQuery) {
		/* Tajiki (UTF-8) initialisation for the jQuery UI date picker plugin. */
		/* Written by Abdurahmon Saidov (saidovab@gmail.com). */
		jQuery(function ($) {
			$.datepicker.regional['tj'] = {
				closeText: 'Идома',
				prevText: '&#x3c;Қафо',
				nextText: 'Пеш&#x3e;',
				currentText: 'Имрӯз',
				monthNames: ['Январ', 'Феврал', 'Март', 'Апрел', 'Май', 'Июн', 'Июл', 'Август', 'Сентябр', 'Октябр', 'Ноябр', 'Декабр'],
				monthNamesShort: ['Янв', 'Фев', 'Мар', 'Апр', 'Май', 'Июн', 'Июл', 'Авг', 'Сен', 'Окт', 'Ноя', 'Дек'],
				dayNames: ['якшанбе', 'душанбе', 'сешанбе', 'чоршанбе', 'панҷшанбе', 'ҷумъа', 'шанбе'],
				dayNamesShort: ['якш', 'душ', 'сеш', 'чор', 'пан', 'ҷум', 'шан'],
				dayNamesMin: ['Як', 'Дш', 'Сш', 'Чш', 'Пш', 'Ҷм', 'Шн'],
				weekHeader: 'Хф',
				dateFormat: 'dd.mm.yy',
				firstDay: 1,
				isRTL: false,
				showMonthAfterYear: false,
				yearSuffix: '' };
			$.datepicker.setDefaults($.datepicker.regional['tj']);
		});
	};

	;
	module.exports = exports['default'];
});