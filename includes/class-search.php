<?php
/**
 * Hybrid search: dense (wpvdb + MariaDB VECTOR) + sparse (MariaDB FULLTEXT
 * natural-language), merged with Reciprocal Rank Fusion.
 *
 * @package WPVDB_Smart_Search
 */

namespace WPVDB_Smart_Search;

defined( 'ABSPATH' ) || exit;

/**
 * Hybrid search runner.
 */
class Search {
	const MODES     = [ 'hybrid', 'dense', 'sparse' ];
	const RRF_K     = 60;
	const MAX_LIMIT = 20;
	const MAX_QUERY = 500;

	/**
	 * Run a search in the requested mode.
	 *
	 * @param string $query Query text.
	 * @param int    $limit Max results to return (capped at MAX_LIMIT).
	 * @param string $mode  One of hybrid | dense | sparse.
	 * @return array|\WP_Error Response payload or error.
	 */
	public static function run( $query, $limit, $mode ) {
		$t_start = microtime( true );

		$query = trim( (string) $query );
		if ( '' === $query ) {
			return new \WP_Error( 'empty_query', __( 'Query text is required.', 'wpvdb-smart-search' ), [ 'status' => 400 ] );
		}
		if ( strlen( $query ) > self::MAX_QUERY ) {
			return new \WP_Error(
				'query_too_long',
				sprintf(
					/* translators: %d: maximum number of characters allowed in a query. */
					__( 'Query too long (max %d chars).', 'wpvdb-smart-search' ),
					self::MAX_QUERY
				),
				[ 'status' => 400 ]
			);
		}
		$limit = max( 1, min( self::MAX_LIMIT, (int) $limit ) );
		$mode  = in_array( $mode, self::MODES, true ) ? $mode : 'hybrid';

		if ( ! class_exists( '\\WPVDB\\Core' ) || ! class_exists( '\\WPVDB\\Settings' ) || ! class_exists( '\\WPVDB\\Database' ) ) {
			return new \WP_Error( 'wpvdb_missing', __( 'wpvdb plugin classes not available.', 'wpvdb-smart-search' ), [ 'status' => 500 ] );
		}

		global $wpdb;
		$table = Schema::table();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Trusted table name, no caching needed for a live count.
		$total_vectors = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );

		// Each candidate pool fetches extra rows so RRF has material to re-rank.
		$pool = max( $limit * 3, 30 );

		$dense_ms       = 0;
		$dense_embed_ms = 0;
		$dense_db_ms    = 0;
		$sparse_ms      = 0;
		$dense_rows     = [];
		$sparse_rows    = [];

		if ( 'dense' === $mode || 'hybrid' === $mode ) {
			$t              = microtime( true );
			$dense_timings  = [];
			$dense_rows     = self::dense_query( $query, $pool, $dense_timings );
			$dense_ms       = (int) round( ( microtime( true ) - $t ) * 1000 );
			$dense_embed_ms = (int) ( $dense_timings['embed_ms'] ?? 0 );
			$dense_db_ms    = (int) ( $dense_timings['db_ms'] ?? 0 );
			if ( is_wp_error( $dense_rows ) ) {
				return $dense_rows;
			}
		}

		if ( 'sparse' === $mode || 'hybrid' === $mode ) {
			$t           = microtime( true );
			$sparse_rows = self::sparse_query( $query, $pool );
			$sparse_ms   = (int) round( ( microtime( true ) - $t ) * 1000 );
			if ( is_wp_error( $sparse_rows ) ) {
				return $sparse_rows;
			}
		}

		$merged   = self::merge( $dense_rows, $sparse_rows, $mode, $limit );
		$enriched = self::enrich( $merged );

		return [
			'mode'           => $mode,
			'query'          => $query,
			'limit'          => $limit,
			'total_vectors'  => $total_vectors,
			'elapsed_ms'     => (int) round( ( microtime( true ) - $t_start ) * 1000 ),
			'dense_ms'       => $dense_ms,
			'dense_embed_ms' => $dense_embed_ms,
			'dense_db_ms'    => $dense_db_ms,
			'sparse_ms'      => $sparse_ms,
			'dense_count'    => count( is_array( $dense_rows ) ? $dense_rows : [] ),
			'sparse_count'   => count( is_array( $sparse_rows ) ? $sparse_rows : [] ),
			'fulltext_ready' => Schema::has_fulltext_index(),
			'results'        => $enriched,
		];
	}

	/**
	 * Dense / vector query via MariaDB VEC_DISTANCE_COSINE.
	 *
	 * @param string $query Query text.
	 * @param int    $limit Pool size.
	 * @param array  $timings Timing measurements populated by reference.
	 * @return array|\WP_Error
	 */
	private static function dense_query( $query, $limit, &$timings = [] ) {
		$timings = [
			'embed_ms' => 0,
			'db_ms'    => 0,
		];

		$provider = \WPVDB\Settings::get_active_provider();
		if ( empty( $provider ) ) {
			$provider = 'openai';
		}
		$model    = \WPVDB\Settings::get_default_model();
		$api_key  = \WPVDB\Settings::get_api_key_for_provider( $provider );
		$api_base = \WPVDB\Settings::get_api_base_for_provider( $provider );

		if ( empty( $api_key ) || empty( $api_base ) ) {
			return new \WP_Error( 'not_configured', __( 'Embedding provider is not configured.', 'wpvdb-smart-search' ), [ 'status' => 500 ] );
		}

		$t_embed             = microtime( true );
		$embedding           = \WPVDB\Core::get_embedding( $query, $model, $api_base, $api_key );
		$timings['embed_ms'] = (int) round( ( microtime( true ) - $t_embed ) * 1000 );
		if ( is_wp_error( $embedding ) ) {
			return $embedding;
		}

		// Skip wpvdb's Database->has_native_vector_support() — it runs an
		// information_schema.columns scan that's ~500ms on shared MariaDB.
		// This plugin hard-requires MariaDB 11.8+ (per AGENTS.md); if the DB
		// doesn't support VEC_DISTANCE_COSINE, the query below will surface
		// a useful SQL error.
		global $wpdb;
		$table = Schema::table();
		$json  = wp_json_encode( $embedding );
		$vf    = "VEC_FromText('" . esc_sql( $json ) . "')";
		$df    = "VEC_DISTANCE_COSINE(embedding, $vf)";

		$sql = "SELECT id, doc_id, chunk_id, chunk_content, summary, $df as distance
		        FROM {$table}
		        ORDER BY distance
		        LIMIT " . (int) $limit;

		$t_db = microtime( true );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared -- Query is assembled from trusted constants and esc_sql'd JSON; wpvdb-style vector fn calls can't be passed through prepare().
		$rows             = $wpdb->get_results( $sql, ARRAY_A );
		$timings['db_ms'] = (int) round( ( microtime( true ) - $t_db ) * 1000 );
		if ( $wpdb->last_error ) {
			return new \WP_Error( 'db_error', $wpdb->last_error, [ 'status' => 500 ] );
		}
		return empty( $rows ) ? [] : $rows;
	}

	/**
	 * Sparse / FULLTEXT natural-language query. Returns [] if the index is
	 * missing or the query is all stopwords; hybrid mode degrades gracefully
	 * in that case.
	 *
	 * @param string $query Query text.
	 * @param int    $limit Pool size.
	 * @return array
	 */
	private static function sparse_query( $query, $limit ) {
		if ( ! Schema::has_fulltext_index() ) {
			return [];
		}

		global $wpdb;
		$table = Schema::table();

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is trusted; user input is bound via prepare().
		$sql = $wpdb->prepare(
			"SELECT id, doc_id, chunk_id, chunk_content, summary,
			        MATCH(chunk_content) AGAINST (%s IN NATURAL LANGUAGE MODE) as score
			 FROM {$table}
			 WHERE MATCH(chunk_content) AGAINST (%s IN NATURAL LANGUAGE MODE)
			 ORDER BY score DESC
			 LIMIT %d",
			$query,
			$query,
			(int) $limit
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared -- $sql is the product of $wpdb->prepare() above.
		$rows = $wpdb->get_results( $sql, ARRAY_A );
		return empty( $rows ) ? [] : $rows;
	}

	/**
	 * Reciprocal Rank Fusion merge.
	 *
	 * Keyed by chunk primary id. Each row keeps its raw signals (distance,
	 * sparse score) plus the ranks it had in each source list and the RRF
	 * combined score. For dense-only or sparse-only mode, we skip fusion and
	 * just trim the source list.
	 *
	 * @param array  $dense_rows  Dense result rows.
	 * @param array  $sparse_rows Sparse result rows.
	 * @param string $mode        hybrid | dense | sparse.
	 * @param int    $limit       Final result count.
	 * @return array
	 */
	private static function merge( $dense_rows, $sparse_rows, $mode, $limit ) {
		if ( 'dense' === $mode ) {
			return array_slice( self::normalize_dense_only( $dense_rows ), 0, $limit );
		}
		if ( 'sparse' === $mode ) {
			return array_slice( self::normalize_sparse_only( $sparse_rows ), 0, $limit );
		}

		$k      = self::RRF_K;
		$merged = [];

		foreach ( $dense_rows as $rank => $row ) {
			$id            = (int) $row['id'];
			$merged[ $id ] = [
				'row'          => $row,
				'dense_rank'   => $rank + 1,
				'sparse_rank'  => null,
				'distance'     => isset( $row['distance'] ) ? (float) $row['distance'] : null,
				'sparse_score' => null,
				'sources'      => [ 'dense' ],
				'rrf'          => 1.0 / ( $k + $rank + 1 ),
			];
		}

		foreach ( $sparse_rows as $rank => $row ) {
			$id           = (int) $row['id'];
			$contribution = 1.0 / ( $k + $rank + 1 );
			if ( isset( $merged[ $id ] ) ) {
				$merged[ $id ]['sparse_rank']  = $rank + 1;
				$merged[ $id ]['sparse_score'] = (float) $row['score'];
				$merged[ $id ]['sources'][]    = 'sparse';
				$merged[ $id ]['rrf']         += $contribution;
			} else {
				$merged[ $id ] = [
					'row'          => $row,
					'dense_rank'   => null,
					'sparse_rank'  => $rank + 1,
					'distance'     => null,
					'sparse_score' => (float) $row['score'],
					'sources'      => [ 'sparse' ],
					'rrf'          => $contribution,
				];
			}
		}

		uasort(
			$merged,
			static function ( $a, $b ) {
				return $b['rrf'] <=> $a['rrf'];
			}
		);

		return array_slice( array_values( $merged ), 0, $limit );
	}

	/**
	 * Wrap dense rows in the same shape as the RRF-merged result, without fusion.
	 *
	 * @param array $rows Dense rows.
	 * @return array
	 */
	private static function normalize_dense_only( $rows ) {
		$out = [];
		foreach ( $rows as $rank => $row ) {
			$out[] = [
				'row'          => $row,
				'dense_rank'   => $rank + 1,
				'sparse_rank'  => null,
				'distance'     => isset( $row['distance'] ) ? (float) $row['distance'] : null,
				'sparse_score' => null,
				'sources'      => [ 'dense' ],
				'rrf'          => null,
			];
		}
		return $out;
	}

	/**
	 * Wrap sparse rows in the same shape as the RRF-merged result, without fusion.
	 *
	 * @param array $rows Sparse rows.
	 * @return array
	 */
	private static function normalize_sparse_only( $rows ) {
		$out = [];
		foreach ( $rows as $rank => $row ) {
			$out[] = [
				'row'          => $row,
				'dense_rank'   => null,
				'sparse_rank'  => $rank + 1,
				'distance'     => null,
				'sparse_score' => isset( $row['score'] ) ? (float) $row['score'] : null,
				'sources'      => [ 'sparse' ],
				'rrf'          => null,
			];
		}
		return $out;
	}

	/**
	 * Attach post title / permalink and decode HTML entities in chunk text.
	 * Drops rows whose post is no longer published.
	 *
	 * @param array $merged Merged rows from self::merge().
	 * @return array
	 */
	private static function enrich( $merged ) {
		if ( empty( $merged ) ) {
			return [];
		}

		$doc_ids = [];
		foreach ( $merged as $m ) {
			$doc_ids[] = (int) $m['row']['doc_id'];
		}
		$doc_ids = array_values( array_unique( array_filter( $doc_ids ) ) );

		$meta = [];
		if ( ! empty( $doc_ids ) ) {
			$posts = get_posts(
				[
					'include'     => $doc_ids,
					'post_type'   => 'any',
					'post_status' => [ 'publish' ],
					'numberposts' => count( $doc_ids ),
				]
			);
			foreach ( $posts as $p ) {
				$meta[ $p->ID ] = [
					'title' => html_entity_decode( wp_strip_all_tags( get_the_title( $p ) ), ENT_QUOTES, 'UTF-8' ),
					'link'  => get_permalink( $p ),
				];
			}
		}

		$out = [];
		foreach ( $merged as $m ) {
			$row = $m['row'];
			$pid = (int) $row['doc_id'];
			if ( ! isset( $meta[ $pid ] ) ) {
				continue;
			}
			$distance   = $m['distance'];
			$similarity = ( null !== $distance ) ? max( 0.0, 1.0 - $distance ) : null;
			$out[]      = [
				'post_id'       => $pid,
				'title'         => $meta[ $pid ]['title'],
				'link'          => $meta[ $pid ]['link'],
				'chunk_id'      => (int) $row['chunk_id'],
				'chunk_content' => html_entity_decode( (string) $row['chunk_content'], ENT_QUOTES, 'UTF-8' ),
				'summary'       => html_entity_decode( (string) ( $row['summary'] ?? '' ), ENT_QUOTES, 'UTF-8' ),
				'distance'      => null !== $distance ? (float) $distance : null,
				'similarity'    => null !== $similarity ? (float) $similarity : null,
				'sparse_score'  => null !== $m['sparse_score'] ? (float) $m['sparse_score'] : null,
				'dense_rank'    => $m['dense_rank'],
				'sparse_rank'   => $m['sparse_rank'],
				'rrf_score'     => null !== $m['rrf'] ? (float) $m['rrf'] : null,
				'sources'       => $m['sources'],
			];
		}
		return $out;
	}
}
