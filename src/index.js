import { createRoot } from 'react-dom/client';
import { setLocaleData } from '@wordpress/i18n';
import App from './App';
import './styles.css';

const container = document.getElementById( 'wpvdb-smart-search-root' );
const config = window.WPVDB_SMART_SEARCH || {};

// Hydrate translations before anything calls __() so the first render is
// already localized. Data comes from PHP (the plugin's loaded .mo for the
// current locale).
if ( config.localeData ) {
	setLocaleData( config.localeData, 'wpvdb-smart-search' );
}

if ( container ) {
	createRoot( container ).render( <App config={ config } /> );
}
