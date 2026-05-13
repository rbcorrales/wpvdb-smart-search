/**
 * Thin override on top of @wordpress/scripts defaults.
 *
 * - Entry is the React app's index.js.
 * - DependencyExtractionWebpackPlugin is removed: by default it marks
 *   react/react-dom/react-jsx-runtime as external globals (meant for the WP
 *   admin context where those ship as wp-element). This plugin runs on a
 *   public page without those globals, so we bundle React instead.
 * - CSS gets extracted to dist/smart-search.css via MiniCssExtractPlugin.
 */

const path = require( 'path' );
const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );

const plugins = ( defaultConfig.plugins || [] ).filter(
	( plugin ) => plugin.constructor.name !== 'DependencyExtractionWebpackPlugin'
);

module.exports = {
	...defaultConfig,
	entry: {
		'smart-search': path.resolve( __dirname, 'src/index.js' ),
	},
	output: {
		...defaultConfig.output,
		path: path.resolve( __dirname, 'dist' ),
		filename: '[name].js',
	},
	plugins,
};
