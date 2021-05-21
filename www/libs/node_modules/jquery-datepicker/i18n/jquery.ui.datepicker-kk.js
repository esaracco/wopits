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
		global.jqueryUiDatepickerKk = mod.exports;
	}
})(this, function (module, exports) {
	'use strict';

	Object.defineProperty(exports, "__esModule", {
		value: true
	});

	exports.default = function (jQuery) {
		/* Kazakh (UTF-8) initialisation for the jQuery UI date picker plugin. */
		/* Written by Dmitriy Karasyov (dmitriy.karasyov@gmail.com). */
		jQuery(function ($) {
			$.datepicker.regional['kk'] = {
				closeText: 'Жабу',
				prevText: '&#x3C;Алдыңғы',
				nextText: 'Келесі&#x3E;',
				currentText: 'Бүгін',
				monthNames: ['Қаңтар', 'Ақпан', 'Наурыз', 'Сәуір', 'Мамыр', 'Маусым', 'Шілде', 'Тамыз', 'Қыркүйек', 'Қазан', 'Қараша', 'Желтоқсан'],
				monthNamesShort: ['Қаң', 'Ақп', 'Нау', 'Сәу', 'Мам', 'Мау', 'Шіл', 'Там', 'Қыр', 'Қаз', 'Қар', 'Жел'],
				dayNames: ['Жексенбі', 'Дүйсенбі', 'Сейсенбі', 'Сәрсенбі', 'Бейсенбі', 'Жұма', 'Сенбі'],
				dayNamesShort: ['жкс', 'дсн', 'ссн', 'срс', 'бсн', 'жма', 'снб'],
				dayNamesMin: ['Жк', 'Дс', 'Сс', 'Ср', 'Бс', 'Жм', 'Сн'],
				weekHeader: 'Не',
				dateFormat: 'dd.mm.yy',
				firstDay: 1,
				isRTL: false,
				showMonthAfterYear: false,
				yearSuffix: '' };
			$.datepicker.setDefaults($.datepicker.regional['kk']);
		});
	};

	;
	module.exports = exports['default'];
});