/**
 * WordPress dependencies
 */
import { render } from '@wordpress/element';

/**
 * Internal dependencies
 */
import App from './components/App';

/**
 * Initialize the app
 */
const init = () => {
    const container = document.getElementById('bu-liaison-inquiry-admin-app');
    if (!container) {
        return;
    }

    render(
        <App />,
        container
    );
};

// Initialize when DOM is ready
if (document.readyState !== 'loading') {
    init();
} else {
    document.addEventListener('DOMContentLoaded', init);
}
