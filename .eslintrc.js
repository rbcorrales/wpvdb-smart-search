module.exports = {
	extends: [ './node_modules/@wordpress/scripts/config/.eslintrc.js' ],
	env: {
		browser: true,
		es2022: true,
	},
	ignorePatterns: [ 'node_modules/', 'vendor/', 'dist/', 'release/' ],
	rules: {
		'@wordpress/i18n-no-flanking-whitespace': 'off',
	},
};
