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
		global.jqueryUiDatepickerHi = mod.exports;
	}
})(this, function (module, exports) {
	'use strict';

	Object.defineProperty(exports, "__esModule", {
		value: true
	});

	exports.default = function (jQuery) {
		/* Hindi initialisation for the jQuery UI date picker plugin. */
		/* Written by Michael Dawart. */
		jQuery(function ($) {
			$.datepicker.regional['hi'] = {
				closeText: 'बंद',
				prevText: 'पिछला',
				nextText: 'अगला',
				currentText: 'आज',
				monthNames: ['जनवरी ', 'फरवरी', 'मार्च', 'अप्रेल', 'मई', 'जून', 'जूलाई', 'अगस्त ', 'सितम्बर', 'अक्टूबर', 'नवम्बर', 'दिसम्बर'],
				monthNamesShort: ['जन', 'फर', 'मार्च', 'अप्रेल', 'मई', 'जून', 'जूलाई', 'अग', 'सित', 'अक्ट', 'नव', 'दि'],
				dayNames: ['रविवार', 'सोमवार', 'मंगलवार', 'बुधवार', 'गुरुवार', 'शुक्रवार', 'शनिवार'],
				dayNamesShort: ['रवि', 'सोम', 'मंगल', 'बुध', 'गुरु', 'शुक्र', 'शनि'],
				dayNamesMin: ['रवि', 'सोम', 'मंगल', 'बुध', 'गुरु', 'शुक्र', 'शनि'],
				weekHeader: 'हफ्ता',
				dateFormat: 'dd/mm/yy',
				firstDay: 1,
				isRTL: false,
				showMonthAfterYear: false,
				yearSuffix: '' };
			$.datepicker.setDefaults($.datepicker.regional['hi']);
		});
	};

	;
	module.exports = exports['default'];
});