<?php
/**

 * Plugin Name: BU Liaison Inquiry
 * Plugin URI: http://developer.bu.edu
 * Author: Boston University IS&T (Jonathan Williams)
 * Author URI: http://developer.bu.edu
 * Description: Provide a form to send data to the Liaison SpectrumEMP API
 * Version: 0.8.2
 *
 * @package BU_Liaison_Inquiry
 */

namespace BU\Plugins\Liaison_Inquiry;

/**
 * Composer autoload
 */
require __DIR__ . '/vendor/autoload.php';

// Load the new admin page.
require_once __DIR__ . '/src/new-admin.php';

// Load the new REST API endpoints.
require_once __DIR__ . '/src/admin-rest-endpoints.php';

// Load the form REST API endpoints.
require_once __DIR__ . '/src/form-rest-endpoints.php';

$admin = new Admin();

// Initialize the admin settings.
add_action( 'admin_init', array( $admin, 'bu_liaison_inquiry_settings_init' ) );

// Register the page in the admin menu.
add_action( 'admin_menu', array( $admin, 'bu_liaison_inquiry_options_page' ) );


// Instantiate plugin (only once).
if ( ! isset( $GLOBALS['bu_liaison_inquiry'] ) ) {
	// Check whether in Dev Mode.
	if ( defined( 'BU_LIAISON_INQUIRY_SAMPLE' ) && BU_LIAISON_INQUIRY_SAMPLE ) {
		$plugin = new Plugin( Sample_Spectrum_API::class );
	} else {
		$plugin = new Plugin( Spectrum_API::class );
	}
}

$GLOBALS['bu_liaison_inquiry'] = $plugin;

// Assign inquiry form shortcode.
add_shortcode( 'liaison_inquiry_form', array( $plugin, 'liaison_inquiry_form' ) );

// Setup form submission handlers.
add_action( 'admin_post_nopriv_liaison_inquiry', array( $plugin, 'ajax_inquiry_form_post' ) );
add_action( 'admin_post_liaison_inquiry', array( $plugin, 'ajax_inquiry_form_post' ) );

// Register scripts and styles now, enqueue in the shortcode handler.
add_action( 'wp_enqueue_scripts', __namespace__ . '\register_validation_files' );

/**
 *  Register js form validation scripts so that they may be enqueued later
 */
function register_validation_files() {

	wp_register_script(
		'jquery-ui',
		plugin_dir_url( __FILE__ ) . 'assets/js/jquery/jquery-ui.min.js',
		array( 'jquery' ),
		null,
		true
	);

	wp_register_script(
		'jquery-masked',
		plugin_dir_url( __FILE__ ) . 'assets/js/jquery/jquery-masked.js',
		array( 'jquery' ),
		null,
		true
	);

	wp_register_script(
		'jquery-pubsub',
		plugin_dir_url( __FILE__ ) . 'assets/js/jquery/jquery-pubsub.js',
		array( 'jquery' ),
		null,
		true
	);

	wp_register_script(
		'iqs-validate',
		plugin_dir_url( __FILE__ ) . 'assets/js/iqs/validate.js',
		array( 'jquery' ),
		null,
		true
	);

	wp_register_script(
		'field_rules_form_library',
		plugin_dir_url( __FILE__ ) . 'assets/js/field_rules_form_library.js',
		array( 'jquery' ),
		null,
		true
	);

	wp_register_script(
		'field_rules_handler',
		plugin_dir_url( __FILE__ ) . 'assets/js/field_rules_handler.js',
		array( 'jquery' ),
		null,
		true
	);

	wp_register_script(
		'bu-liaison-main',
		plugin_dir_url( __FILE__ ) . 'assets/js/main.js',
		array( 'jquery' ),
		null,
		true
	);

	wp_register_style(
		'liason-form-style',
		plugin_dir_url( __FILE__ ) . 'assets/css/form-style.css'
	);

	wp_register_style(
		'jquery-ui-css',
		plugin_dir_url( __FILE__ ) . 'assets/js/jquery/jquery-ui.min.css'
	);

}
