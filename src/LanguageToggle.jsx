import { useEffect, useRef, useState } from 'react';
import { __ } from '@wordpress/i18n';

/**
 * Available languages. Add new entries here — the dropdown picks them up
 * automatically. An empty `code` means "use the site default" (no ?lang param).
 */
const LANGS = [
	{ code: '', label: 'EN', name: 'English' },
	{ code: 'es_ES', label: 'ES', name: 'Español' },
];

function getCurrentCode() {
	if ( typeof window === 'undefined' ) {
		return '';
	}
	const params = new URLSearchParams( window.location.search );
	return params.get( 'lang' ) || '';
}

/**
 * Language dropdown. Switching triggers a full page reload so the server can
 * re-emit the PHP-rendered placeholders/example labels + the JS localeData
 * blob for the target locale. Other URL params (q, mode, limit) are preserved.
 */
export default function LanguageToggle() {
	const [ open, setOpen ] = useState( false );
	const wrapRef = useRef( null );
	const current = getCurrentCode();
	const currentLang = LANGS.find( ( l ) => l.code === current ) || LANGS[ 0 ];

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

	function pick( code ) {
		const params = new URLSearchParams( window.location.search );
		if ( code ) {
			params.set( 'lang', code );
		} else {
			params.delete( 'lang' );
		}
		const qs = params.toString();
		window.location.href =
			window.location.pathname + ( qs ? '?' + qs : '' );
	}

	const buttonLabel = __( 'Language', 'wpvdb-smart-search' );

	return (
		<div ref={ wrapRef } className="relative">
			<button
				type="button"
				onClick={ () => setOpen( ( o ) => ! o ) }
				aria-haspopup="listbox"
				aria-expanded={ open }
				aria-label={ buttonLabel }
				title={ buttonLabel }
				className="themed-surface inline-flex h-9 min-w-[44px] cursor-pointer items-center justify-center rounded-full border border-[var(--color-border)] bg-[var(--color-panel)] px-3 text-[12px] font-semibold tracking-wide text-[var(--color-ink)] shadow-[var(--shadow-panel)] hover:bg-[var(--color-hover)] focus:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-accent)]"
			>
				{ currentLang.label }
			</button>
			{ open && (
				<ul
					role="listbox"
					aria-label={ buttonLabel }
					className="themed-surface absolute right-0 top-full z-10 mt-2 min-w-[160px] rounded-lg border border-[var(--color-border)] bg-[var(--color-panel)] py-1 shadow-[var(--shadow-popover)]"
				>
					{ LANGS.map( ( l ) => (
						<li key={ l.code || 'default' } role="none">
							<button
								type="button"
								role="option"
								aria-selected={ l.code === current }
								onClick={ () => pick( l.code ) }
								className={ `flex w-full cursor-pointer items-center justify-between px-3 py-1.5 text-left text-[13px] no-underline hover:bg-[var(--color-hover)] focus:bg-[var(--color-hover)] focus:outline-none ${
									l.code === current
										? 'bg-[var(--color-selected)] font-medium'
										: ''
								}` }
							>
								<span>{ l.name }</span>
								<span className="ml-3 text-[11px] text-[var(--color-muted)]">
									{ l.label }
								</span>
							</button>
						</li>
					) ) }
				</ul>
			) }
		</div>
	);
}
