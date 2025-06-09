<?php

function bu_liaison_render_new_admin_page() {
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
		add_menu_page(
			__( 'BU Liaison Inquiry New', 'bu_liaison_inquiry' ),
			__( 'Liaison Inquiry', 'bu_liaison_inquiry' ),
			'manage_options',
			'bu-liaison-new-admin',
			'bu_liaison_render_new_admin_page',
			'dashicons-feedback',
			6
		);
	}
);


/**
 * Enqueue scripts and styles for the admin page.
 *
 * @return void
 */
function bu_liaison_enqueue_admin_scripts() {
	$page = get_current_screen();
	if ( 'toplevel_page_bu-liaison-new-admin' !== $page->id ) {
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
add_action( 'admin_enqueue_scripts', 'bu_liaison_enqueue_admin_scripts' );
