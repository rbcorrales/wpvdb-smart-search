import { useEffect, useState } from 'react';
import { __ } from '@wordpress/i18n';

const STORAGE_KEY = 'wpvdb-ss-theme';

function detectInitial() {
	try {
		const stored = localStorage.getItem( STORAGE_KEY );
		if ( stored === 'dark' || stored === 'light' ) {
			return stored;
		}
	} catch ( e ) {
		// localStorage can throw in restricted contexts; fall through.
	}
	return window.matchMedia( '(prefers-color-scheme: dark)' ).matches
		? 'dark'
		: 'light';
}

export default function ThemeToggle() {
	const [ theme, setTheme ] = useState( detectInitial );

	useEffect( () => {
		document.documentElement.setAttribute( 'data-theme', theme );
		try {
			localStorage.setItem( STORAGE_KEY, theme );
		} catch ( e ) {
			// Ignore.
		}
	}, [ theme ] );

	// Follow OS preference changes unless the user has explicitly chosen.
	useEffect( () => {
		const mq = window.matchMedia( '(prefers-color-scheme: dark)' );
		function handler( e ) {
			try {
				if ( localStorage.getItem( STORAGE_KEY ) ) {
					return;
				}
			} catch ( err ) {
				return;
			}
			setTheme( e.matches ? 'dark' : 'light' );
		}
		mq.addEventListener( 'change', handler );
		return () => mq.removeEventListener( 'change', handler );
	}, [] );

	const isDark = theme === 'dark';
	const toggleLabel = isDark
		? __( 'Switch to light mode', 'wpvdb-smart-search' )
		: __( 'Switch to dark mode', 'wpvdb-smart-search' );

	return (
		<button
			type="button"
			onClick={ () => setTheme( isDark ? 'light' : 'dark' ) }
			aria-label={ toggleLabel }
			title={ toggleLabel }
			className="themed-surface inline-flex h-9 w-9 cursor-pointer items-center justify-center rounded-full border border-[var(--color-border)] bg-[var(--color-panel)] text-[var(--color-ink)] shadow-[var(--shadow-panel)] hover:bg-[var(--color-hover)] focus:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-accent)]"
		>
			{ isDark ? <SunIcon /> : <MoonIcon /> }
		</button>
	);
}

function SunIcon() {
	return (
		<svg
			width="18"
			height="18"
			viewBox="0 0 24 24"
			fill="none"
			stroke="currentColor"
			strokeWidth="2"
			strokeLinecap="round"
			strokeLinejoin="round"
			aria-hidden="true"
		>
			<circle cx="12" cy="12" r="4" />
			<path d="M12 2v2" />
			<path d="M12 20v2" />
			<path d="M4.93 4.93l1.41 1.41" />
			<path d="M17.66 17.66l1.41 1.41" />
			<path d="M2 12h2" />
			<path d="M20 12h2" />
			<path d="M4.93 19.07l1.41-1.41" />
			<path d="M17.66 6.34l1.41-1.41" />
		</svg>
	);
}

function MoonIcon() {
	return (
		<svg
			width="18"
			height="18"
			viewBox="0 0 24 24"
			fill="none"
			stroke="currentColor"
			strokeWidth="2"
			strokeLinecap="round"
			strokeLinejoin="round"
			aria-hidden="true"
		>
			<path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z" />
		</svg>
	);
}
