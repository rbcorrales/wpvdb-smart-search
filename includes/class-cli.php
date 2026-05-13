<?php
/**
 * WP-CLI commands for wpvdb-smart-search.
 *
 * @package WPVDB_Smart_Search
 */

namespace WPVDB_Smart_Search;

defined( 'ABSPATH' ) || exit;

/**
 * `wp wpvdb-smart-search` subcommands.
 */
class CLI {

	/**
	 * Register hooks.
	 */
	public static function init() {
		\WP_CLI::add_command( 'wpvdb-smart-search prewarm', [ __CLASS__, 'prewarm' ] );
	}

	/**
	 * Pre-compute embeddings for every example query so the first user click on
	 * an example pill hits the wpvdb cache instead of OpenAI. Idempotent: re-running
	 * is cheap (cache hits return immediately for queries already warmed).
	 *
	 * ## OPTIONS
	 *
	 * [--model=<model>]
	 * : Override the embedding model. Defaults to wpvdb's active model setting.
	 *
	 * [--dry-run]
	 * : List the queries that would be embedded without calling the provider.
	 *
	 * ## EXAMPLES
	 *
	 *   wp wpvdb-smart-search prewarm
	 *   wp wpvdb-smart-search prewarm --dry-run
	 *   wp wpvdb-smart-search prewarm --model=text-embedding-3-small
	 *
	 * @param array $args        Positional args (unused).
	 * @param array $assoc_args  Flag args.
	 */
	public static function prewarm( $args, $assoc_args ) {
		unset( $args );
		if ( ! class_exists( '\WPVDB\Core' ) || ! class_exists( '\WPVDB\Settings' ) ) {
			\WP_CLI::error( 'wpvdb plugin not active; cannot pre-warm.' );
		}

		$pairs   = Content::example_queries();
		$queries = array_values( array_unique( array_map( static fn( $row ) => (string) $row[1], $pairs ) ) );
		if ( empty( $queries ) ) {
			\WP_CLI::warning( 'No example queries defined; nothing to do.' );
			return;
		}

		$model    = isset( $assoc_args['model'] ) && '' !== $assoc_args['model']
			? (string) $assoc_args['model']
			: \WPVDB\Settings::get_default_model();
		$api_base = \WPVDB\Settings::get_api_base();
		$api_key  = \WPVDB\Settings::get_api_key();
		$dry      = ! empty( $assoc_args['dry-run'] );

		\WP_CLI::log( sprintf( 'Pre-warming %d example queries on model "%s"%s.', count( $queries ), $model, $dry ? ' (dry-run)' : '' ) );

		$progress = \WP_CLI\Utils\make_progress_bar( 'Pre-warming', count( $queries ) );
		$ok       = 0;
		$failed   = 0;
		$skipped  = 0;
		$t_start  = microtime( true );

		foreach ( $queries as $q ) {
			if ( $dry ) {
				\WP_CLI::log( '  - ' . $q );
				$progress->tick();
				continue;
			}

			if ( false !== \WPVDB\Cache::get_embedding( $q, $model ) ) {
				$skipped++;
				$progress->tick();
				continue;
			}

			$result = \WPVDB\Core::get_embedding( $q, $model, $api_base, $api_key );
			if ( is_wp_error( $result ) ) {
				$failed++;
				\WP_CLI::warning( sprintf( 'Failed to embed "%s": %s', $q, $result->get_error_message() ) );
			} else {
				$ok++;
			}
			$progress->tick();
		}

		$progress->finish();

		$elapsed = number_format( microtime( true ) - $t_start, 2 );
		if ( $dry ) {
			\WP_CLI::success( sprintf( 'Dry-run complete. %d queries listed.', count( $queries ) ) );
		} else {
			\WP_CLI::success( sprintf( '%d warmed, %d already cached, %d failed in %ss.', $ok, $skipped, $failed, $elapsed ) );
		}
	}
}
