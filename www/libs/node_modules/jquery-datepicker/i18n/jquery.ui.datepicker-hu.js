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
		global.jqueryUiDatepickerHu = mod.exports;
	}
})(this, function (module, exports) {
	'use strict';

	Object.defineProperty(exports, "__esModule", {
		value: true
	});

	exports.default = function (jQuery) {
		/* Hungarian initialisation for the jQuery UI date picker plugin. */
		/* Written by Istvan Karaszi (jquery@spam.raszi.hu). */
		jQuery(function ($) {
			$.datepicker.regional['hu'] = {
				closeText: 'bezár',
				prevText: 'vissza',
				nextText: 'előre',
				currentText: 'ma',
				monthNames: ['Január', 'Február', 'Március', 'Április', 'Május', 'Június', 'Július', 'Augusztus', 'Szeptember', 'Október', 'November', 'December'],
				monthNamesShort: ['Jan', 'Feb', 'Már', 'Ápr', 'Máj', 'Jún', 'Júl', 'Aug', 'Szep', 'Okt', 'Nov', 'Dec'],
				dayNames: ['Vasárnap', 'Hétfő', 'Kedd', 'Szerda', 'Csütörtök', 'Péntek', 'Szombat'],
				dayNamesShort: ['Vas', 'Hét', 'Ked', 'Sze', 'Csü', 'Pén', 'Szo'],
				dayNamesMin: ['V', 'H', 'K', 'Sze', 'Cs', 'P', 'Szo'],
				weekHeader: 'Hét',
				dateFormat: 'yy.mm.dd.',
				firstDay: 1,
				isRTL: false,
				showMonthAfterYear: true,
				yearSuffix: '' };
			$.datepicker.setDefaults($.datepicker.regional['hu']);
		});
	};

	;
	module.exports = exports['default'];
});