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
		global.jqueryUiDatepickerBe = mod.exports;
	}
})(this, function (module, exports) {
	'use strict';

	Object.defineProperty(exports, "__esModule", {
		value: true
	});

	exports.default = function (jQuery) {
		/* Belarusian initialisation for the jQuery UI date picker plugin. */
		/* Written by Pavel Selitskas <p.selitskas@gmail.com> */
		jQuery(function ($) {
			$.datepicker.regional['be'] = {
				closeText: 'Зачыніць',
				prevText: '&larr;Папяр.',
				nextText: 'Наст.&rarr;',
				currentText: 'Сёньня',
				monthNames: ['Студзень', 'Люты', 'Сакавік', 'Красавік', 'Травень', 'Чэрвень', 'Ліпень', 'Жнівень', 'Верасень', 'Кастрычнік', 'Лістапад', 'Сьнежань'],
				monthNamesShort: ['Сту', 'Лют', 'Сак', 'Кра', 'Тра', 'Чэр', 'Ліп', 'Жні', 'Вер', 'Кас', 'Ліс', 'Сьн'],
				dayNames: ['нядзеля', 'панядзелак', 'аўторак', 'серада', 'чацьвер', 'пятніца', 'субота'],
				dayNamesShort: ['ндз', 'пнд', 'аўт', 'срд', 'чцв', 'птн', 'сбт'],
				dayNamesMin: ['Нд', 'Пн', 'Аў', 'Ср', 'Чц', 'Пт', 'Сб'],
				weekHeader: 'Тд',
				dateFormat: 'dd.mm.yy',
				firstDay: 1,
				isRTL: false,
				showMonthAfterYear: false,
				yearSuffix: '' };
			$.datepicker.setDefaults($.datepicker.regional['be']);
		});
	};

	;
	module.exports = exports['default'];
});