<?php
/*
Plugin Name: BU Liaison API
Plugin URI: http://developer.bu.edu
Author: Boston University (IS&T)
Author URI: http://developer.bu.edu
Description: Provide a form to send data to the Liaison SpectrumEMP API
Version: 0.1
*/


define( 'BU_LIAISON_API_PLUGIN_DIR', dirname( __FILE__ ) );

$config['SpectrumEMPAPIKey'] = 'e8149913b261c4d5471212847821e59557cc60be';
$config['SpectrumEMPClientID'] = '266';

// SpectrumEMP constants.
define( 'API_URL', 				'https://www.spectrumemp.com/api/' );
define('REQUIREMENTS_URL', 		API_URL . 'inquiry_form/requirements');
define('SUBMIT_URL', 			API_URL . 'inquiry_form/submit');
define('CLIENT_RULES_URL', 		API_URL . 'field_rules/client_rules');
define('FIELD_OPTIONS_URL', 	API_URL . 'field_rules/field_options');



function liaison_inquiry_form( $atts ){
	// @todo Globals are undesirable, put the constants in a class definition?
	global $config;

	// Get info from EMP about the fields that should be displayed for the form.
	$api_query = REQUIREMENTS_URL . '?IQS-API-KEY=' . $config['SpectrumEMPAPIKey'];
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
	include( BU_LIAISON_API_PLUGIN_DIR . '/templates/form-template.php' );

	return $html;
}

add_shortcode( 'liaison_inquiry_form', 'liaison_inquiry_form' );
