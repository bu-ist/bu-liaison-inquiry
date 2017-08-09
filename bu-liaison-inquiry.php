<?php
/**

 * Plugin Name: BU Liaison Inquiry
 * Plugin URI: http://developer.bu.edu
 * Author: Boston University IS&T (Jonathan Williams)
 * Author URI: http://developer.bu.edu
 * Description: Provide a form to send data to the Liaison SpectrumEMP API
 * Version: 0.6
 *
 * @package BULiaisonInquiry
 */

namespace BU_Liaison_Inquiry;

/**
 * Composer autoload
 */
require __DIR__ . '/vendor/autoload.php';

// Instantiate plugin (only once).
if ( ! isset( $GLOBALS['bu_liaison_inquiry'] ) ) {
	// Get API key and Client ID from option settings.
	$options = get_option( 'bu_liaison_inquiry_options' );
	$api_key = $options['APIKey'];
	$client_id = $options['ClientID'];

	// Check whether in Dev Mode.
	if ( defined( 'BU_LIAISON_INQUIRY_MOCK' ) && BU_LIAISON_INQUIRY_MOCK ) {
		$GLOBALS['bu_liaison_inquiry'] = new Plugin( new Mock_Spectrum_API( $client_id ) );
	} else {
		$GLOBALS['bu_liaison_inquiry'] = new Plugin( new Spectrum_API( $api_key, $client_id ) );
	}
}

// Register js form validation scripts so that they may be enqueued
// by the shortcode handler.
wp_register_script(
	'jquery-ui',
	plugin_dir_url( __FILE__ ) . 'assets/js/jquery/jquery-ui.min.js',
	array( 'jquery' )
);

wp_register_script(
	'jquery-masked',
	plugin_dir_url( __FILE__ ) . 'assets/js/jquery/jquery-masked.js',
	array( 'jquery' )
);

wp_register_script(
	'jquery-pubsub',
	plugin_dir_url( __FILE__ ) . 'assets/js/jquery/jquery-pubsub.js',
	array( 'jquery' )
);

wp_register_script(
	'iqs-validate',
	plugin_dir_url( __FILE__ ) . 'assets/js/iqs/validate.js',
	array( 'jquery' )
);

wp_register_script(
	'field_rules_form_library',
	plugin_dir_url( __FILE__ ) . 'assets/js/field_rules_form_library.js',
	array( 'jquery' )
);

wp_register_script(
	'field_rules_handler',
	plugin_dir_url( __FILE__ ) . 'assets/js/field_rules_handler.js',
	array( 'jquery' )
);

wp_register_script(
	'bu-liaison-main',
	plugin_dir_url( __FILE__ ) . 'assets/js/main.js',
	array( 'jquery' )
);

wp_register_style(
	'liason-form-style',
	plugin_dir_url( __FILE__ ) . 'assets/css/form-style.css'
);

wp_register_style(
	'jquery-ui-css',
	plugin_dir_url( __FILE__ ) . 'assets/js/jquery/jquery-ui.min.css'
);
