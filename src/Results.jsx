import { __, _n, sprintf } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';

function truncate( s, n ) {
	const str = String( s || '' );
	return str.length > n ? str.slice( 0, n ) + '\u2026' : str;
}

function SimBar( { sim } ) {
	const pct = Math.max( 0, Math.min( 100, sim * 100 ) );
	return (
		<>
			<span className="relative inline-block h-[14px] w-[100px] overflow-hidden rounded-full bg-[var(--color-border)] align-middle">
				<span
					className="absolute inset-y-0 left-0 rounded-full bg-[var(--color-accent)]"
					style={ { width: pct.toFixed( 1 ) + '%' } }
				/>
			</span>
			<span className="ml-1.5 inline-block min-w-[42px] text-[12px] tabular-nums text-[var(--color-ink)]">
				{ pct.toFixed( 1 ) }%
			</span>
		</>
	);
}

function SourceBadge( { sources } ) {
	if ( sources.length === 2 ) {
		return (
			<span className="inline-block rounded-full bg-[var(--color-badge-both-bg)] px-2 py-[1px] text-[11px] font-medium text-[var(--color-badge-both-fg)]">
				{ __( 'dense + sparse', 'wpvdb-smart-search' ) }
			</span>
		);
	}
	if ( sources[ 0 ] === 'dense' ) {
		return (
			<span className="inline-block rounded-full bg-[var(--color-badge-dense-bg)] px-2 py-[1px] text-[11px] font-medium text-[var(--color-badge-dense-fg)]">
				{ __( 'dense', 'wpvdb-smart-search' ) }
			</span>
		);
	}
	return (
		<span className="inline-block rounded-full bg-[var(--color-badge-sparse-bg)] px-2 py-[1px] text-[11px] font-medium text-[var(--color-badge-sparse-fg)]">
			{ __( 'sparse', 'wpvdb-smart-search' ) }
		</span>
	);
}

function StatusLine( { data, groupedCount } ) {
	if ( ! data || ! data.results?.length ) {
		return null;
	}
	const elapsedMs = data.elapsed_ms || 0;
	const totalLabel =
		elapsedMs >= 1000
			? ( elapsedMs / 1000 ).toFixed( 2 ) + 's'
			: elapsedMs + 'ms';
	const codeCls =
		'rounded bg-[var(--color-code-bg)] px-1 py-0.5 font-mono text-[12px] text-[var(--color-ink)]';
	const emCls = 'not-italic font-medium text-[var(--color-ink)]';

	const summary = createInterpolateElement(
		sprintf(
			/* translators: 1: number of results; 2: quoted query text. */
			__( 'Showing <n>%1$d</n> results for <q>“%2$s”</q>', 'wpvdb-smart-search' ),
			data.results.length,
			data.query
		),
		{
			n: <em className={ emCls } />,
			q: <em className={ emCls } />,
		}
	);

	return (
		<div className="mx-0.5 mt-3.5 text-[13px] text-[var(--color-muted)]">
			<p>{ summary }</p>
			<p className="mt-0.5">
				{ __( 'mode', 'wpvdb-smart-search' ) }{ ' ' }
				<em className={ emCls }>{ data.mode }</em> &middot;{ ' ' }
				{ sprintf(
					/* translators: %d: number of posts. */
					_n( '%d post', '%d posts', groupedCount, 'wpvdb-smart-search' ),
					groupedCount
				) }{ ' ' }
				&middot;{ ' ' }
				{ createInterpolateElement(
					sprintf(
						/* translators: %d: total number of indexed vectors searched. */
						__( 'searched <n>%d</n> vectors', 'wpvdb-smart-search' ),
						data.total_vectors || 0
					),
					{ n: <em className={ emCls } /> }
				) }{ ' ' }
				&middot; { __( 'dense', 'wpvdb-smart-search' ) }{ ' ' }
				<code className={ codeCls }>{ data.dense_ms }ms</code>
				{ ( data.dense_embed_ms != null || data.dense_db_ms != null ) && (
					<>
						{ ' ' }
						<span title={ __( 'embedding API roundtrip for the query text + MariaDB VEC_DISTANCE_COSINE scan', 'wpvdb-smart-search' ) }>
							({ __( 'embed', 'wpvdb-smart-search' ) }{ ' ' }
							<code className={ codeCls }>{ data.dense_embed_ms || 0 }ms</code>
							{ ' + ' }
							{ __( 'db', 'wpvdb-smart-search' ) }{ ' ' }
							<code className={ codeCls }>{ data.dense_db_ms || 0 }ms</code>)
						</span>
					</>
				) }
				{ ' ' }&middot;{ ' ' }
				{ __( 'sparse', 'wpvdb-smart-search' ) }{ ' ' }
				<code className={ codeCls }>{ data.sparse_ms }ms</code> &middot;{ ' ' }
				{ createInterpolateElement(
					sprintf(
						/* translators: %s: total query time (already formatted with its unit, e.g. "530ms" or "1.25s"). */
						__( 'total <n>%s</n>', 'wpvdb-smart-search' ),
						totalLabel
					),
					{ n: <em className={ emCls } /> }
				) }
			</p>
		</div>
	);
}

function ResultRow( { entry, index } ) {
	const chunk = entry.chunks[ 0 ];
	return (
		<div className="themed-surface mb-2 rounded-lg border border-[var(--color-border)] bg-[var(--color-panel)] px-4 py-3 shadow-[var(--shadow-panel)]">
			<h3 className="mb-1 text-[15px] font-semibold">
				<span className="mr-1.5 text-[var(--color-subtle)]">{ index + 1 }.</span>
				{ entry.link ? (
					<a
						href={ entry.link }
						target="_blank"
						rel="noopener noreferrer"
						className="no-underline hover:underline"
					>
						{ entry.title }
					</a>
				) : (
					entry.title
				) }
			</h3>
			<div className="flex flex-wrap items-center gap-2.5 text-[12px] text-[var(--color-muted)]">
				{ entry.best_sim !== null ? (
					<span>
						<SimBar sim={ entry.best_sim } />
					</span>
				) : (
					<span className="inline-block rounded-full bg-[var(--color-code-bg)] px-2 py-[1px] text-[11px]">
						{ __( 'no dense match', 'wpvdb-smart-search' ) }
					</span>
				) }
				{ entry.best_distance !== null && (
					<span>
						{ __( 'distance', 'wpvdb-smart-search' ) }{ ' ' }
						<code className="rounded bg-[var(--color-code-bg)] px-1 py-0.5 font-mono text-[12px]">
							{ entry.best_distance.toFixed( 4 ) }
						</code>
					</span>
				) }
				{ entry.best_sparse !== null && (
					<span title={ __( 'MariaDB FULLTEXT relevance score', 'wpvdb-smart-search' ) }>
						{ __( 'fulltext', 'wpvdb-smart-search' ) }{ ' ' }
						<code className="rounded bg-[var(--color-code-bg)] px-1 py-0.5 font-mono text-[12px]">
							{ entry.best_sparse.toFixed( 4 ) }
						</code>
					</span>
				) }
				<SourceBadge sources={ entry.sources } />
				<span>
					{ sprintf(
						/* translators: %d: number of chunks that matched for a post. */
						_n(
							'%d chunk',
							'%d chunks',
							entry.chunks.length,
							'wpvdb-smart-search'
						),
						entry.chunks.length
					) }
				</span>
			</div>
			{ chunk && (
				<div className="mt-2 whitespace-pre-wrap rounded-md border border-[var(--color-border)] bg-[var(--color-chunk-bg)] px-3 py-2 text-[13px]">
					{ truncate( chunk.chunk_content || chunk.summary || '', 400 ) }
				</div>
			) }
		</div>
	);
}

export default function Results( { loading, error, data, grouped } ) {
	if ( loading ) {
		return (
			<div className="mt-4">
				<div
					className="wpvdb-spinner"
					role="status"
					aria-label={ __( 'Searching', 'wpvdb-smart-search' ) }
				/>
			</div>
		);
	}
	if ( error ) {
		return (
			<div className="mt-3 rounded-md border border-[var(--color-error-border)] bg-[var(--color-error-bg)] px-3 py-2 text-[var(--color-error-fg)]">
				{ error }
			</div>
		);
	}
	if ( ! data ) {
		return null;
	}

	return (
		<div aria-live="polite">
			<StatusLine data={ data } groupedCount={ grouped.length } />
			<div className="mt-4">
				{ grouped.length === 0 ? (
					<p className="py-3 italic text-[var(--color-muted)]">
						{ __( 'No results.', 'wpvdb-smart-search' ) }
					</p>
				) : (
					grouped.map( ( entry, i ) => (
						<ResultRow key={ entry.post_id } entry={ entry } index={ i } />
					) )
				) }
			</div>
		</div>
	);
}
