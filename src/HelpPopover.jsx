import { useEffect, useRef, useState } from 'react';
import { __, sprintf } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';

/**
 * Help button + popover. Pinned top-right alongside the theme toggle.
 * Contains the technical blurb that used to live as an always-visible subtitle.
 */
export default function HelpPopover( { siteName, homeUrl } ) {
	const [ open, setOpen ] = useState( false );
	const wrapRef = useRef( null );

	useEffect( () => {
		if ( ! open ) {
			return;
		}
		function onDocClick( e ) {
			if ( wrapRef.current && ! wrapRef.current.contains( e.target ) ) {
				setOpen( false );
			}
		}
		function onKey( e ) {
			if ( e.key === 'Escape' ) {
				setOpen( false );
			}
		}
		document.addEventListener( 'mousedown', onDocClick );
		document.addEventListener( 'keydown', onKey );
		return () => {
			document.removeEventListener( 'mousedown', onDocClick );
			document.removeEventListener( 'keydown', onKey );
		};
	}, [ open ] );

	const aboutLabel = __( 'About this demo', 'wpvdb-smart-search' );

	// MariaDB function names render as links (monospace styling comes from the
	// link classes). We avoid nesting <code> inside placeholders because
	// createInterpolateElement rejects conversionMap values with no children
	// context, which caused a runtime WSOD.
	const blurb = createInterpolateElement(
		sprintf(
			/* translators: %s: site name (rendered as a link to the homepage). */
			__(
				'Hybrid vector + keyword search over the corpus indexed on <site>%s</site>. Only the query text is sent to OpenAI (once, to <embed>embed it</embed>); all ranking happens inside MariaDB via <cosine>VEC_DISTANCE_COSINE</cosine> (dense) and <match>MATCH … AGAINST</match> (sparse), merged with <rrf>Reciprocal Rank Fusion</rrf>.',
				'wpvdb-smart-search'
			),
			siteName
		),
		{
			site: <a href={ homeUrl } />,
			embed: (
				<a
					href="https://developers.openai.com/api/docs/guides/embeddings"
					target="_blank"
					rel="noopener noreferrer"
				/>
			),
			cosine: (
				<a
					href="https://mariadb.com/docs/server/reference/sql-functions/vector-functions/vec_distance_cosine"
					target="_blank"
					rel="noopener noreferrer"
					className="font-mono"
				/>
			),
			match: (
				<a
					href="https://mariadb.com/docs/server/reference/sql-functions/string-functions/match-against"
					target="_blank"
					rel="noopener noreferrer"
					className="font-mono"
				/>
			),
			rrf: (
				<a
					href="https://plg.uwaterloo.ca/~gvcormac/cormacksigir09-rrf.pdf"
					target="_blank"
					rel="noopener noreferrer"
				/>
			),
		}
	);

	return (
		<div ref={ wrapRef } className="relative">
			<button
				type="button"
				aria-label={ aboutLabel }
				aria-expanded={ open }
				onClick={ () => setOpen( ( o ) => ! o ) }
				className="themed-surface inline-flex h-9 w-9 cursor-pointer items-center justify-center rounded-full border border-[var(--color-border)] bg-[var(--color-panel)] text-[14px] font-medium text-[var(--color-ink)] shadow-[var(--shadow-panel)] hover:bg-[var(--color-hover)] focus:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-accent)]"
			>
				?
			</button>
			{ open && (
				<div
					role="dialog"
					aria-label={ aboutLabel }
					className="themed-surface absolute right-0 top-full mt-2 w-[min(380px,calc(100vw-2rem))] rounded-lg border border-[var(--color-border)] bg-[var(--color-panel)] p-4 text-[13px] leading-relaxed text-[var(--color-muted)] shadow-[var(--shadow-popover)]"
				>
					<h2 className="mb-1.5 text-[14px] font-semibold text-[var(--color-ink)]">
						{ __( 'How this works', 'wpvdb-smart-search' ) }
					</h2>
					<p>{ blurb }</p>
				</div>
			) }
		</div>
	);
}
