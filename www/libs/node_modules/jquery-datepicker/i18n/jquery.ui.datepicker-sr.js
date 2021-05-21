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
		global.jqueryUiDatepickerSr = mod.exports;
	}
})(this, function (module, exports) {
	'use strict';

	Object.defineProperty(exports, "__esModule", {
		value: true
	});

	exports.default = function (jQuery) {
		/* Serbian i18n for the jQuery UI date picker plugin. */
		/* Written by Dejan Dimić. */
		jQuery(function ($) {
			$.datepicker.regional['sr'] = {
				closeText: 'Затвори',
				prevText: '&#x3C;',
				nextText: '&#x3E;',
				currentText: 'Данас',
				monthNames: ['Јануар', 'Фебруар', 'Март', 'Април', 'Мај', 'Јун', 'Јул', 'Август', 'Септембар', 'Октобар', 'Новембар', 'Децембар'],
				monthNamesShort: ['Јан', 'Феб', 'Мар', 'Апр', 'Мај', 'Јун', 'Јул', 'Авг', 'Сеп', 'Окт', 'Нов', 'Дец'],
				dayNames: ['Недеља', 'Понедељак', 'Уторак', 'Среда', 'Четвртак', 'Петак', 'Субота'],
				dayNamesShort: ['Нед', 'Пон', 'Уто', 'Сре', 'Чет', 'Пет', 'Суб'],
				dayNamesMin: ['Не', 'По', 'Ут', 'Ср', 'Че', 'Пе', 'Су'],
				weekHeader: 'Сед',
				dateFormat: 'dd.mm.yy',
				firstDay: 1,
				isRTL: false,
				showMonthAfterYear: false,
				yearSuffix: '' };
			$.datepicker.setDefaults($.datepicker.regional['sr']);
		});
	};

	;
	module.exports = exports['default'];
});