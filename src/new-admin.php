<?php
/**
 * Register Admin page for Liaison form settings.
 *
 * @package BU_Liaison_Inquiry
 */

namespace BU\Plugins\Liaison_Inquiry;

// WordPress functions are in global namespace.
use function add_action;
use function add_options_page;
use function current_user_can;
use function get_current_screen;
use function plugin_dir_path;
use function plugins_url;
use function wp_enqueue_script;
use function wp_enqueue_style;
use function wp_set_script_translations;

/**
 * Render the React admin page with a div that will be replaced by the React app.
 *
 * @return void
 */
function render_admin_page() {
	// Check if the user has the required capability to view this page.
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	// Include the form template.
	echo '<div id="bu-liaison-inquiry-admin-app"></div>';
}

add_action(
	'admin_menu',
	function () {
		add_options_page(
			__( 'Liaison Forms', 'bu_liaison_inquiry' ),
			__( 'Liaison Forms', 'bu_liaison_inquiry' ),
			'manage_options',
			'bu_liaison_inquiry',
			__NAMESPACE__ . '\render_admin_page'
		);
	}
);


/**
 * Enqueue scripts and styles for the admin page.
 *
 * @return void
 */
function enqueue_admin_scripts() {
	$page = get_current_screen();
	if ( 'settings_page_bu_liaison_inquiry' !== $page->id ) {
		return;
	}

	$asset_file = include plugin_dir_path( __DIR__ ) . 'dist/js/build/index.asset.php';

	wp_enqueue_script(
		'bu-liaison-admin',
		plugins_url( 'dist/js/build/index.js', __DIR__ ),
		$asset_file['dependencies'],
		$asset_file['version'],
		true
	);

	wp_set_script_translations(
		'bu-liaison-admin',
		'bu-liaison-inquiry'
	);

	// Enqueue WordPress components styles and the plugin's compiled CSS.
	wp_enqueue_style( 'wp-components' );
	wp_enqueue_style(
		'bu-liaison-admin',
		plugins_url( 'dist/js/build/index.css', __DIR__ ),
		array( 'wp-components' ), // Make sure our styles load after WP components.
		$asset_file['version']
	);
}
add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\enqueue_admin_scripts' );
