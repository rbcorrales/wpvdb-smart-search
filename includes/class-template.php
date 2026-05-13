<?php
/**
 * Template: serves /smart-search as a standalone HTML page with no theme
 * chrome. Uses parse_request to avoid rewrite-rule flushes.
 *
 * @package WPVDB_Smart_Search
 */

namespace WPVDB_Smart_Search;

defined( 'ABSPATH' ) || exit;

/**
 * Renders the public playground page at /smart-search.
 */
class Template {
	const PUBLIC_PATH = 'smart-search';

	/**
	 * Register hooks.
	 */
	public static function init() {
		add_action( 'parse_request', [ __CLASS__, 'maybe_render' ], 0 );
	}

	/**
	 * If the current request is /smart-search, render and exit.
	 */
	public static function maybe_render() {
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- We only compare against a literal path, no echo.
		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) $_SERVER['REQUEST_URI'] : '';
		$uri         = strtok( $request_uri, '?' );
		$uri         = '/' . trim( $uri, '/' );
		if ( '/' . self::PUBLIC_PATH !== $uri ) {
			return;
		}
		self::render();
		exit;
	}

	/**
	 * Emit the standalone HTML page.
	 */
	private static function render() {
		nocache_headers();
		header( 'Content-Type: text/html; charset=UTF-8' );

		// Allow a `?lang=es_ES` query param for quick locale testing. Load our
		// plugin's .mo file directly for that locale rather than relying on
		// switch_to_locale (which silently no-ops if WP core lang files for
		// that locale aren't installed on the site).
		$requested_lang = self::requested_lang();
		$active_locale  = determine_locale();
		if ( $requested_lang ) {
			$mo_file = WPVDB_SMART_SEARCH_DIR . '/languages/wpvdb-smart-search-' . $requested_lang . '.mo';
			if ( file_exists( $mo_file ) ) {
				unload_textdomain( 'wpvdb-smart-search' );
				load_textdomain( 'wpvdb-smart-search', $mo_file );
				$active_locale = $requested_lang;
			}
		}

		$page_content = Content::page_content();

		$ctx = [
			'rest_url'         => esc_url_raw( rest_url( Rest::NAMESPACE_ . '/search' ) ),
			'site_name'        => esc_html( get_bloginfo( 'name' ) ),
			'home_url'         => esc_url( home_url( '/' ) ),
			'html_lang'        => str_replace( '_', '-', $active_locale ),
			'css_url'          => self::asset_url( 'dist/smart-search.css' ),
			'js_url'           => self::asset_url( 'dist/smart-search.js' ),
			'max_results'      => \WPVDB_Search\Search::MAX_LIMIT,
			'max_query'        => \WPVDB_Search\Search::MAX_QUERY,
			'rate_max'         => Rest::RATE_MAX,
			'fulltext_ready'   => \WPVDB_Search\Schema::has_fulltext_index(),
			'examples_pool'    => $page_content['examples_pool'],
			'examples_visible' => $page_content['examples_visible'],
			'placeholders'     => $page_content['placeholders'],
			'locale_data'      => self::locale_data( $active_locale ),
		];

		load_template( WPVDB_SMART_SEARCH_DIR . '/templates/page.php', true, $ctx );

		if ( $requested_lang ) {
			// Restore the default locale's textdomain for subsequent requests.
			unload_textdomain( 'wpvdb-smart-search' );
			load_plugin_textdomain(
				'wpvdb-smart-search',
				false,
				dirname( plugin_basename( WPVDB_SMART_SEARCH_FILE ) ) . '/languages'
			);
		}
	}

	/**
	 * Export the currently-loaded translations for our text domain as a
	 * locale-data object ready for @wordpress/i18n's setLocaleData(). Emitted
	 * via window.WPVDB_SMART_SEARCH.localeData so the React bundle can
	 * translate JS strings without needing wp_enqueue_script + make-json.
	 *
	 * @param string|null $effective_locale Locale to export.
	 * @return array
	 */
	private static function locale_data( $effective_locale = null ) {
		$domain = 'wpvdb-smart-search';

		if ( null === $effective_locale ) {
			$effective_locale = determine_locale();
		}

		$locale_data = [
			'' => [
				'domain' => $domain,
				'lang'   => $effective_locale,
			],
		];

		// Parse the .mo file directly rather than relying on the loaded textdomain
		// state — the WP global $l10n store can return a NOOP_Translations or an
		// incomplete Translations object in this context, leaving entries empty.
		$mo_file = WPVDB_SMART_SEARCH_DIR . '/languages/' . $domain . '-' . $effective_locale . '.mo';
		if ( ! file_exists( $mo_file ) ) {
			return $locale_data;
		}

		$mo = new \MO();
		if ( ! $mo->import_from_file( $mo_file ) ) {
			return $locale_data;
		}

		if ( ! empty( $mo->headers['Plural-Forms'] ) ) {
			$locale_data['']['plural_forms'] = $mo->headers['Plural-Forms'];
		}

		foreach ( $mo->entries as $entry ) {
			if ( empty( $entry->translations ) ) {
				continue;
			}
			$key = $entry->singular;
			if ( ! empty( $entry->context ) ) {
				$key = $entry->context . "\x04" . $key;
			}
			$locale_data[ $key ] = array_values( $entry->translations );
		}

		return $locale_data;
	}

	/**
	 * Return the requested locale override from the query string.
	 *
	 * @return string
	 */
	private static function requested_lang() {
		if ( isset( $_GET['lang'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return sanitize_text_field( wp_unslash( $_GET['lang'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}

		return '';
	}

	/**
	 * Build a cache-busted URL for a built asset using its mtime.
	 *
	 * @param string $relative Path relative to the plugin directory.
	 * @return string
	 */
	private static function asset_url( $relative ) {
		$path    = WPVDB_SMART_SEARCH_DIR . '/' . ltrim( $relative, '/' );
		$version = file_exists( $path ) ? filemtime( $path ) : WPVDB_SMART_SEARCH_VERSION;
		return WPVDB_SMART_SEARCH_URL . ltrim( $relative, '/' ) . '?v=' . $version;
	}
}
