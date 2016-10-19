<?php
/*
Plugin Name: BU Liaison API
Plugin URI: http://developer.bu.edu
Author: Boston University (IS&T)
Author URI: http://developer.bu.edu
Description: Provide a form to send data to the Liaison SpectrumEMP API
Version: 0.1
*/


define( 'BU_LIAISON_INQUIRY_PLUGIN_DIR', dirname( __FILE__ ) );


// SpectrumEMP constants.
define( 'API_URL', 				'https://www.spectrumemp.com/api/' );
define('REQUIREMENTS_URL', 		API_URL . 'inquiry_form/requirements');
define('SUBMIT_URL', 			API_URL . 'inquiry_form/submit');
define('CLIENT_RULES_URL', 		API_URL . 'field_rules/client_rules');
define('FIELD_OPTIONS_URL', 	API_URL . 'field_rules/field_options');

include(BU_LIAISON_INQUIRY_PLUGIN_DIR . '/admin/admin.php');

function liaison_inquiry_form( $atts ){

	// Get API key from option setting.
	$options = get_option( 'bu_liaison_inquiry_options' );
	$api_key = $options['APIKey'];

	// Optionally override the API key with the shortcode attribute if present.
	if ( isset( $atts['api_key'] ) ) { $api_key = $atts['api_key']; }

	// Get info from EMP about the fields that should be displayed for the form.
	$api_query = REQUIREMENTS_URL . '?IQS-API-KEY=' . $api_key;
	$api_response = wp_remote_get( $api_query );

	// Check for a successful response from external API server.
	if ( is_wp_error( $api_response ) ) {
		error_log( 'Liaison form API call failed: ' . $api_response->get_error_message() );
		return 'Form Error: ' .   $api_response->get_error_message();
	}

	$inquiry_form_decode = json_decode( $api_response['body'] );

	// Check that the response from the API contains actual form data.
	if ( ! isset( $inquiry_form_decode->data ) ) {
		error_log( 'Bad response from Liaison API server: ' . $inquiry_form_decode->message );
		return 'Error in Form API response';
	}

	$inquiry_form = $inquiry_form_decode->data;

	// Include a template file like bu-navigation does.
	include( BU_LIAISON_INQUIRY_PLUGIN_DIR . '/templates/form-template.php' );

	return $html;
}

add_shortcode( 'liaison_inquiry_form', 'liaison_inquiry_form' );


function handle_liaison_inquiry() {
	var_export( $_POST );
	exit();
}

add_action( 'admin_post_nopriv_liaison_inquiry', 'handle_liaison_inquiry' );
add_action( 'admin_post_liaison_inquiry', 'handle_liaison_inquiry' );
