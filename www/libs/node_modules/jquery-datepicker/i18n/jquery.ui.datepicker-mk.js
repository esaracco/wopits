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
		global.jqueryUiDatepickerMk = mod.exports;
	}
})(this, function (module, exports) {
	'use strict';

	Object.defineProperty(exports, "__esModule", {
		value: true
	});

	exports.default = function (jQuery) {
		/* Macedonian i18n for the jQuery UI date picker plugin. */
		/* Written by Stojce Slavkovski. */
		jQuery(function ($) {
			$.datepicker.regional['mk'] = {
				closeText: 'Затвори',
				prevText: '&#x3C;',
				nextText: '&#x3E;',
				currentText: 'Денес',
				monthNames: ['Јануари', 'Февруари', 'Март', 'Април', 'Мај', 'Јуни', 'Јули', 'Август', 'Септември', 'Октомври', 'Ноември', 'Декември'],
				monthNamesShort: ['Јан', 'Фев', 'Мар', 'Апр', 'Мај', 'Јун', 'Јул', 'Авг', 'Сеп', 'Окт', 'Ное', 'Дек'],
				dayNames: ['Недела', 'Понеделник', 'Вторник', 'Среда', 'Четврток', 'Петок', 'Сабота'],
				dayNamesShort: ['Нед', 'Пон', 'Вто', 'Сре', 'Чет', 'Пет', 'Саб'],
				dayNamesMin: ['Не', 'По', 'Вт', 'Ср', 'Че', 'Пе', 'Са'],
				weekHeader: 'Сед',
				dateFormat: 'dd.mm.yy',
				firstDay: 1,
				isRTL: false,
				showMonthAfterYear: false,
				yearSuffix: '' };
			$.datepicker.setDefaults($.datepicker.regional['mk']);
		});
	};

	;
	module.exports = exports['default'];
});