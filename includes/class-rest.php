<?php
/**
 * REST route: POST /wpvdb-smart-search/v1/search
 *
 * Public, rate-limited. Accepts { query, limit, mode, collapse_by_post }
 * where mode is one of hybrid | dense | sparse.
 *
 * @package WPVDB_Smart_Search
 */

namespace WPVDB_Smart_Search;

defined( 'ABSPATH' ) || exit;

/**
 * Registers and handles the public search REST route.
 */
class Rest {
	const NAMESPACE_      = 'wpvdb-smart-search/v1';
	const RATE_WINDOW_SEC = 60;
	const RATE_MAX        = 20;

	/**
	 * Register hooks.
	 */
	public static function init() {
		add_action( 'rest_api_init', [ __CLASS__, 'register' ] );
	}

	/**
	 * Register the /search route.
	 */
	public static function register() {
		register_rest_route(
			self::NAMESPACE_,
			'/search',
			[
				'methods'             => 'POST',
				'callback'            => [ __CLASS__, 'handle' ],
				'permission_callback' => '__return_true',
				'args'                => [
					'query'            => [
						'type'     => 'string',
						'required' => true,
					],
					'limit'            => [
						'type'    => 'integer',
						'default' => 10,
					],
					'mode'             => [
						'type'    => 'string',
						'default' => 'hybrid',
					],
					'collapse_by_post' => [
						'type'    => 'boolean',
						'default' => false,
					],
				],
			]
		);
	}

	/**
	 * Handle POST /search.
	 *
	 * @param \WP_REST_Request $req Request.
	 * @return \WP_Error|\WP_REST_Response
	 */
	public static function handle( \WP_REST_Request $req ) {
		if ( self::rate_limited() ) {
			return new \WP_Error( 'rate_limited', __( 'Too many requests, slow down.', 'wpvdb-smart-search' ), [ 'status' => 429 ] );
		}

		$result = \WPVDB_Search\Search::run(
			[
				'query'            => (string) $req->get_param( 'query' ),
				'limit'            => (int) $req->get_param( 'limit' ),
				'mode'             => (string) $req->get_param( 'mode' ),
				'collapse_by_post' => (bool) $req->get_param( 'collapse_by_post' ),
				'include_debug'    => true,
			]
		);

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$debug = isset( $result['debug'] ) && is_array( $result['debug'] ) ? $result['debug'] : [];
		unset( $result['debug'] );

		return rest_ensure_response(
			array_merge(
				[
					'mode'  => $result['mode'],
					'query' => $result['query'],
					'limit' => $result['limit'],
				],
				$debug,
				[
					'results' => $result['results'],
				]
			)
		);
	}

	/**
	 * Best-effort client IP for rate-limiting only. Not used for authz.
	 *
	 * @return string
	 */
	private static function client_ip() {
		// phpcs:ignore WordPressVIPMinimum.Variables.ServerVariables.UserControlledHeaders, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPressVIPMinimum.Variables.RestrictedVariables.cache_constraints___SERVER__REMOTE_ADDR__ -- Used only as a rate-limit bucket key; spoofing only affects the attacker's own bucket.
		$ip       = isset( $_SERVER['REMOTE_ADDR'] ) ? (string) $_SERVER['REMOTE_ADDR'] : 'unknown';
		$filtered = preg_replace( '/[^a-zA-Z0-9\.\:]/', '', $ip );
		return '' === $filtered ? 'unknown' : $filtered;
	}

	/**
	 * Increment the bucket for the current IP and return whether we should throttle.
	 *
	 * @return bool
	 */
	private static function rate_limited() {
		$key   = 'wpvdb_ss_rl_' . md5( self::client_ip() );
		$count = (int) get_transient( $key );
		if ( $count >= self::RATE_MAX ) {
			return true;
		}
		set_transient( $key, $count + 1, self::RATE_WINDOW_SEC );
		return false;
	}
}
