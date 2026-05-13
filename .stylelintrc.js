module.exports = {
	extends: [ './node_modules/@wordpress/scripts/config/.stylelintrc.json' ],
	rules: {
		'no-descending-specificity': null,
		'scss/at-rule-no-unknown': [
			true,
			{
				ignoreAtRules: [ 'theme' ],
			},
		],
	},
};
