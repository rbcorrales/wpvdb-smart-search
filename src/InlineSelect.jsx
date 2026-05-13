import { useEffect, useRef, useState } from 'react';

/**
 * Inline disclosure-menu select: renders like running text with the current
 * value emphasized and a tiny caret. Click opens a small popover list.
 *
 * Kept dependency-free on purpose; if we add more menu surfaces we can swap
 * in Radix or Headless UI.
 */
export default function InlineSelect( { label, value, options, onChange } ) {
	const [ open, setOpen ] = useState( false );
	const wrapRef = useRef( null );
	const triggerRef = useRef( null );
	const listRef = useRef( null );
	const current = options.find( ( o ) => o.value === value ) || options[ 0 ];

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
				triggerRef.current?.focus();
			}
		}
		document.addEventListener( 'mousedown', onDocClick );
		document.addEventListener( 'keydown', onKey );
		return () => {
			document.removeEventListener( 'mousedown', onDocClick );
			document.removeEventListener( 'keydown', onKey );
		};
	}, [ open ] );

	function onTriggerKeyDown( e ) {
		if ( e.key === 'ArrowDown' || e.key === 'Enter' || e.key === ' ' ) {
			e.preventDefault();
			setOpen( true );
			// Focus the first / selected item on the next paint.
			requestAnimationFrame( () => {
				const items = listRef.current?.querySelectorAll( '[role="option"]' );
				const idx = Math.max(
					0,
					options.findIndex( ( o ) => o.value === value )
				);
				items?.[ idx ]?.focus();
			} );
		}
	}

	function onItemKeyDown( e, i ) {
		const items = listRef.current?.querySelectorAll( '[role="option"]' );
		if ( ! items ) {
			return;
		}
		if ( e.key === 'ArrowDown' ) {
			e.preventDefault();
			items[ ( i + 1 ) % items.length ]?.focus();
		} else if ( e.key === 'ArrowUp' ) {
			e.preventDefault();
			items[ ( i - 1 + items.length ) % items.length ]?.focus();
		}
	}

	function pick( v ) {
		onChange( v );
		setOpen( false );
		triggerRef.current?.focus();
	}

	return (
		<span ref={ wrapRef } className="relative inline-block">
			<span className="text-[var(--color-muted)]">{ label }:</span>{ ' ' }
			<button
				ref={ triggerRef }
				type="button"
				aria-haspopup="listbox"
				aria-expanded={ open }
				onClick={ () => setOpen( ( o ) => ! o ) }
				onKeyDown={ onTriggerKeyDown }
				className="inline-flex items-center gap-1 font-medium text-[var(--color-ink)] underline decoration-dotted underline-offset-2 hover:decoration-solid focus:outline-none focus-visible:decoration-solid"
			>
				{ current.label }
				<svg
					width="10"
					height="10"
					viewBox="0 0 10 10"
					aria-hidden="true"
					className={ `transition-transform ${ open ? 'rotate-180' : '' }` }
				>
					<path d="M2 3.5l3 3 3-3" fill="none" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round" />
				</svg>
			</button>
			{ open && (
				<ul
					ref={ listRef }
					role="listbox"
					className="themed-surface absolute left-0 top-full z-10 mt-1 min-w-[240px] rounded-lg border border-[var(--color-border)] bg-[var(--color-panel)] py-1 shadow-[var(--shadow-popover)]"
				>
					{ options.map( ( o, i ) => (
						<li key={ o.value } role="none">
							<button
								type="button"
								role="option"
								aria-selected={ o.value === value }
								tabIndex={ -1 }
								onClick={ () => pick( o.value ) }
								onKeyDown={ ( e ) => {
									if ( e.key === 'Enter' || e.key === ' ' ) {
										e.preventDefault();
										pick( o.value );
									} else {
										onItemKeyDown( e, i );
									}
								} }
								className={ `block w-full cursor-pointer px-3 py-1.5 text-left no-underline hover:bg-[var(--color-hover)] focus:bg-[var(--color-hover)] focus:outline-none ${
									o.value === value ? 'bg-[var(--color-selected)]' : ''
								}` }
							>
								<span
									className={ `block text-[13px] text-[var(--color-ink)] ${
										o.value === value ? 'font-medium' : ''
									}` }
								>
									{ o.label }
								</span>
								{ o.description && (
									<span className="mt-0.5 block text-[11px] leading-snug text-[var(--color-muted)]">
										{ o.description }
									</span>
								) }
							</button>
						</li>
					) ) }
				</ul>
			) }
		</span>
	);
}
