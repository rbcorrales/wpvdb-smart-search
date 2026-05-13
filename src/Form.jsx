import { __ } from '@wordpress/i18n';
import InlineSelect from './InlineSelect';

const FIELD_BASE =
	'w-full rounded-md border border-[var(--color-border)] bg-[var(--color-panel)] px-3 py-2 text-[15px] font-inherit text-[var(--color-ink)] placeholder:italic placeholder:text-[var(--color-subtle)] focus:border-[var(--color-accent)] focus:outline-none focus:ring-2 focus:ring-[var(--color-accent)]/30';

function modeOptions() {
	return [
		{
			value: 'hybrid',
			label: __( 'Hybrid (RRF)', 'wpvdb-smart-search' ),
			description: __(
				'Runs both dense and sparse, merges via Reciprocal Rank Fusion. Best default.',
				'wpvdb-smart-search'
			),
		},
		{
			value: 'dense',
			label: __( 'Dense', 'wpvdb-smart-search' ),
			description: __(
				'Semantic vector search via VEC_DISTANCE_COSINE. Matches by meaning; weaker on exact keywords.',
				'wpvdb-smart-search'
			),
		},
		{
			value: 'sparse',
			label: __( 'Sparse', 'wpvdb-smart-search' ),
			description: __(
				'Full-text keyword match via MATCH … AGAINST. Matches exact terms; no semantic understanding.',
				'wpvdb-smart-search'
			),
		},
	];
}

function searchLabel( mode ) {
	return mode === 'sparse'
		? __( 'Keyword Search', 'wpvdb-smart-search' )
		: __( 'Semantic Search', 'wpvdb-smart-search' );
}

function buildLimitOptions( max ) {
	const presets = [ 3, 5, 10, 15, 20 ].filter( ( n ) => n <= max );
	return presets.map( ( n ) => ( { value: n, label: String( n ) } ) );
}

export default function Form( {
	query,
	setQuery,
	mode,
	setMode,
	limit,
	setLimit,
	maxQuery,
	maxResults,
	placeholder,
	loading,
	onSearch,
	onLucky,
	examples,
	onPickExample,
} ) {
	function onSubmit( ev ) {
		ev.preventDefault();
		onSearch();
	}

	return (
		<form
			onSubmit={ onSubmit }
			className="themed-surface rounded-lg border border-[var(--color-border)] bg-[var(--color-panel)] p-4 shadow-[var(--shadow-panel)]"
		>
			<input
				id="q"
				type="text"
				value={ query }
				maxLength={ maxQuery }
				placeholder={ placeholder }
				autoComplete="off"
				autoFocus
				aria-label={ __( 'Search query', 'wpvdb-smart-search' ) }
				onChange={ ( e ) => setQuery( e.target.value ) }
				className={ FIELD_BASE }
			/>

			<div className="mt-4 flex justify-center gap-2.5">
				<button
					type="submit"
					disabled={ loading }
					className="cursor-pointer rounded-md border border-[var(--color-accent)] bg-[var(--color-accent)] px-5 py-2 font-medium text-[var(--color-accent-fg)] hover:bg-[var(--color-accent-hover)] hover:border-[var(--color-accent-hover)] disabled:cursor-wait disabled:opacity-60"
				>
					{ searchLabel( mode ) }
				</button>
				<button
					type="button"
					disabled={ loading }
					onClick={ onLucky }
					className="cursor-pointer rounded-md border border-[var(--color-border)] bg-[var(--color-panel-alt)] px-5 py-2 font-medium text-[var(--color-ink)] hover:bg-[var(--color-hover)] disabled:cursor-wait disabled:opacity-60"
				>
					{ __( 'Surprise Me!', 'wpvdb-smart-search' ) }
				</button>
			</div>

			<div className="mt-3 flex flex-wrap items-center justify-center gap-x-5 gap-y-1 text-[13px] text-[var(--color-muted)]">
				<InlineSelect
					label={ __( 'Mode', 'wpvdb-smart-search' ) }
					value={ mode }
					options={ modeOptions() }
					onChange={ setMode }
				/>
				<InlineSelect
					label={ __( 'Results', 'wpvdb-smart-search' ) }
					value={ limit }
					options={ buildLimitOptions( maxResults ) }
					onChange={ setLimit }
				/>
			</div>

			{ examples.length > 0 && (
				<div className="mt-3 text-center text-[13px] text-[var(--color-muted)]">
					{ __( 'Try:', 'wpvdb-smart-search' ) }{ ' ' }
					{ examples.map( ( [ label, fullQuery ], i ) => (
						<a
							key={ i }
							href="#"
							data-q={ fullQuery }
							className="mr-2.5 cursor-pointer"
							onClick={ ( e ) => {
								e.preventDefault();
								onPickExample( fullQuery );
							} }
						>
							{ label }
						</a>
					) ) }
				</div>
			) }
		</form>
	);
}
