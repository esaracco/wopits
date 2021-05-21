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
		global.jqueryUiDatepickerLv = mod.exports;
	}
})(this, function (module, exports) {
	'use strict';

	Object.defineProperty(exports, "__esModule", {
		value: true
	});

	exports.default = function (jQuery) {
		/* Latvian (UTF-8) initialisation for the jQuery UI date picker plugin. */
		/* @author Arturas Paleicikas <arturas.paleicikas@metasite.net> */
		jQuery(function ($) {
			$.datepicker.regional['lv'] = {
				closeText: 'Aizvērt',
				prevText: 'Iepr.',
				nextText: 'Nāk.',
				currentText: 'Šodien',
				monthNames: ['Janvāris', 'Februāris', 'Marts', 'Aprīlis', 'Maijs', 'Jūnijs', 'Jūlijs', 'Augusts', 'Septembris', 'Oktobris', 'Novembris', 'Decembris'],
				monthNamesShort: ['Jan', 'Feb', 'Mar', 'Apr', 'Mai', 'Jūn', 'Jūl', 'Aug', 'Sep', 'Okt', 'Nov', 'Dec'],
				dayNames: ['svētdiena', 'pirmdiena', 'otrdiena', 'trešdiena', 'ceturtdiena', 'piektdiena', 'sestdiena'],
				dayNamesShort: ['svt', 'prm', 'otr', 'tre', 'ctr', 'pkt', 'sst'],
				dayNamesMin: ['Sv', 'Pr', 'Ot', 'Tr', 'Ct', 'Pk', 'Ss'],
				weekHeader: 'Ned.',
				dateFormat: 'dd.mm.yy',
				firstDay: 1,
				isRTL: false,
				showMonthAfterYear: false,
				yearSuffix: '' };
			$.datepicker.setDefaults($.datepicker.regional['lv']);
		});
	};

	;
	module.exports = exports['default'];
});