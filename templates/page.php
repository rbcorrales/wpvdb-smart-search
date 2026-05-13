<?php
/**
 * Standalone HTML mount point for the React app.
 *
 * All UI is rendered client-side by React. We only emit a root div, a config
 * blob on window.WPVDB_SMART_SEARCH, and link the compiled CSS + JS bundles.
 *
 * @package WPVDB_Smart_Search
 *
 * @var array $args {
 *     @type string $rest_url         REST endpoint for search.
 *     @type string $site_name        Escaped site name.
 *     @type string $home_url         Site home URL.
 *     @type string $html_lang        HTML language attribute value.
 *     @type string $css_url          Versioned CSS URL.
 *     @type string $js_url           Versioned JS URL.
 *     @type int    $max_results      Max results cap.
 *     @type int    $max_query        Max query length.
 *     @type int    $rate_max         Rate limit ceiling.
 *     @type bool   $fulltext_ready   Whether the FULLTEXT index is in place.
 *     @type array  $examples_pool    Pool of [label, full_query] pairs.
 *     @type int    $examples_visible How many examples to show per load.
 *     @type array  $placeholders     Query input placeholder pool.
 *     @type array  $locale_data      Locale data for @wordpress/i18n.
 * }
 */

defined( 'ABSPATH' ) || exit;

$rest_url         = isset( $args['rest_url'] ) ? (string) $args['rest_url'] : '';
$site_name        = isset( $args['site_name'] ) ? (string) $args['site_name'] : '';
$home_url         = isset( $args['home_url'] ) ? (string) $args['home_url'] : '';
$html_lang        = isset( $args['html_lang'] ) ? (string) $args['html_lang'] : 'en';
$css_url          = isset( $args['css_url'] ) ? (string) $args['css_url'] : '';
$js_url           = isset( $args['js_url'] ) ? (string) $args['js_url'] : '';
$max_results      = isset( $args['max_results'] ) ? (int) $args['max_results'] : 20;
$max_query        = isset( $args['max_query'] ) ? (int) $args['max_query'] : 500;
$rate_max         = isset( $args['rate_max'] ) ? (int) $args['rate_max'] : 20;
$fulltext_ready   = ! empty( $args['fulltext_ready'] );
$examples_pool    = isset( $args['examples_pool'] ) && is_array( $args['examples_pool'] ) ? $args['examples_pool'] : [];
$examples_visible = isset( $args['examples_visible'] ) ? (int) $args['examples_visible'] : 5;
$placeholders     = isset( $args['placeholders'] ) && is_array( $args['placeholders'] ) ? $args['placeholders'] : [];
$locale_data      = isset( $args['locale_data'] ) && is_array( $args['locale_data'] ) ? $args['locale_data'] : [];

$config = [
	'restUrl'         => $rest_url,
	'siteName'        => $site_name,
	'homeUrl'         => $home_url,
	'rateMax'         => $rate_max,
	'maxQuery'        => $max_query,
	'maxResults'      => $max_results,
	'examplesPool'    => $examples_pool,
	'examplesVisible' => $examples_visible,
	'placeholders'    => $placeholders,
	'fulltextReady'   => (bool) $fulltext_ready,
	'localeData'      => $locale_data,
];
?><!DOCTYPE html>
<html lang="<?php echo esc_attr( $html_lang ); ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?php echo esc_html__( 'Smart Search', 'wpvdb-smart-search' ); ?> &middot; <?php echo esc_html( $site_name ); ?></title>
<script>
	// Anti-FOUC: set data-theme on <html> before the stylesheet paints so the
	// initial colors match the user's stored or system preference.
	( function () {
		try {
			var stored = localStorage.getItem( 'wpvdb-ss-theme' );
			var dark =
				stored === 'dark' ||
				( ! stored &&
					window.matchMedia( '(prefers-color-scheme: dark)' ).matches );
			document.documentElement.setAttribute( 'data-theme', dark ? 'dark' : 'light' );
		} catch ( e ) {}
	} )();
</script>
<link rel="stylesheet" href="<?php echo esc_url( $css_url ); ?>">
</head>
<body class="flex min-h-screen flex-col">
	<div id="wpvdb-smart-search-root" class="flex flex-col flex-1"></div>
	<script>
		window.WPVDB_SMART_SEARCH = <?php echo wp_json_encode( $config, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT ); ?>;
	</script>
	<script src="<?php echo esc_url( $js_url ); ?>" defer></script>
</body>
</html>
