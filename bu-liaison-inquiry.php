<?php
/*
Plugin Name: BU Liaison Inquiry
Plugin URI: http://developer.bu.edu
Author: Boston University (IS&T)
Author URI: http://developer.bu.edu
Description: Provide a form to send data to the Liaison SpectrumEMP API
Version: 0.1
*/


define( 'BU_LIAISON_INQUIRY_PLUGIN_DIR', dirname( __FILE__ ) );


// SpectrumEMP constants.
define( 'API_URL', 				'https://www.spectrumemp.com/api/' );
define( 'REQUIREMENTS_URL', 	API_URL . 'inquiry_form/requirements' );
define( 'SUBMIT_URL', 			API_URL . 'inquiry_form/submit' );
define( 'CLIENT_RULES_URL', 	API_URL . 'field_rules/client_rules' );
define( 'FIELD_OPTIONS_URL', 	API_URL . 'field_rules/field_options' );

/**
 * Include the admin interface.
 */
include( BU_LIAISON_INQUIRY_PLUGIN_DIR . '/admin/admin.php' );


/**
 * Shortcode definition that creates the Liaison inquiry form.
 *
 * @param array $atts Attributes specified in the shortcode.
 * @return string Returns full markup to replace the shortcode.
 */
function liaison_inquiry_form( $atts ) {

	// Get API key from option setting.
	$options = get_option( 'bu_liaison_inquiry_options' );
	$api_key = $options['APIKey'];
	$client_id = $options['ClientID'];

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

	// Enqueue the validation scripts
	wp_enqueue_script( 'jquery-ui' );
	wp_enqueue_script( 'jquery-masked' );
	wp_enqueue_script( 'jquery-pubsub' );
	wp_enqueue_script( 'iqs-validate' );
	wp_enqueue_script( 'bu-liaison-main' );
	wp_enqueue_script( 'field_rules_form_library' );
	wp_enqueue_script( 'field_rules_handler' );


	$inquiry_form = $inquiry_form_decode->data;

	// Include a template file like bu-navigation does.
	include( BU_LIAISON_INQUIRY_PLUGIN_DIR . '/templates/form-template.php' );

	return $html;
}

add_shortcode( 'liaison_inquiry_form', 'liaison_inquiry_form' );


function handle_liaison_inquiry() {

	//@todo this seems weak, not sure why it was in the example.  Best thing to do would be replace this with a proper wordpress nonce
	// Check that it looks like the right form.
	if ( 'liaison_inquiry_form' != $_POST['form_submit'] ) {
		//now what?
		echo 'wrong form';
		return;
	}

	//@todo the example operates directly on the $_POST array, which seems contrary to the best practice of sanitizing $_POST first
	unset( $_POST['form_submit'] );

	// Necessary to get the API key from the options, can't expose the key by passing it through the form.
	$options = get_option( 'bu_liaison_inquiry_options' );
	// Check for a valid API key value.
	if ( ! isset( $options['APIKey'] ) ) {
		echo 'no API key';
		return;
	}

	$_POST['IQS-API-KEY'] = $options['APIKey'];


	// From EMP API example
	$phone_fields = $_POST['phone_fields'];
	$phone_fields = explode( ',', $phone_fields );
	unset( $_POST['phone_fields'] );


	$post_vars = array();
	foreach ($_POST as $k => $v) {
		if ( in_array( $k, $phone_fields ) ) {
			$v = preg_replace( '/[^0-9]/', '', $v );
			$v = '%2B1' . $v;		// Append +1 for US, but + needs to be %2B for posting.
		}
		// if this checkbox field is set then it was checked
		if (stripos($k, '-text-opt-in') !== false) {
			$v = '1';
		}
	}

	// Shim.
	$post_vars = $_POST;

	// End EMP API Example segment.

	// Setup arguments for the external API call.
	$post_args = array( 'body' => $post_vars );

	// Make the external API call.
	$remote_submit = wp_remote_post( SUBMIT_URL, $post_args );

	// Decode the response and activate redirect to the personal url on success.

	$resp = json_decode( $remote_submit['body'] );

	//From EMP API example
	//uses jquery api callback
	$return = array();
	$return['status'] = 0;

	$return['status'] 		= ( isset( $resp->status ) && $resp->status == 'success' ) ? 1 : 0;
	$return['response'] 	= ( isset( $resp->message ) ) ? $resp->message : 'Something bad happened, please refresh the page and try again.';
	$return['data'] 		= ( isset( $resp->data ) ) ? $resp->data : '';

	// Return a JSON encoded reply for the validation javascript.
	echo json_encode($return);

	// End EMP API Example segment.

	//for raw answer, not for jquery ajax callback
	//$redirect_url = urldecode( $return['data'] );


	//echo "<script>window.location.href = '" . esc_url( $redirect_url ) . "';</script>";

}

add_action( 'admin_post_nopriv_liaison_inquiry', 'handle_liaison_inquiry' );
add_action( 'admin_post_liaison_inquiry', 'handle_liaison_inquiry' );

// Register js form validation scripts so that they may be enqueued by the shortcode handler.
wp_register_script( 'jquery-ui', plugin_dir_url( __FILE__ ) . 'assets/js/jquery/jquery-ui.js', array( 'jquery' ) );
wp_register_script( 'jquery-masked', plugin_dir_url( __FILE__ ) . 'assets/js/jquery/jquery-masked.js', array( 'jquery' ) );
wp_register_script( 'jquery-pubsub', plugin_dir_url( __FILE__ ) . 'assets/js/jquery/jquery-pubsub.js', array( 'jquery' ) );
wp_register_script( 'iqs-validate', plugin_dir_url( __FILE__ ) . 'assets/js/iqs/validate.js', array( 'jquery' ) );

//should register jquery-ui css styles too?

wp_register_script( 'field_rules_form_library', plugin_dir_url( __FILE__ ) . 'assets/js/field_rules_form_library.js', array( 'jquery' ) );
wp_register_script( 'field_rules_handler', plugin_dir_url( __FILE__ ) . 'assets/js/field_rules_handler.js', array( 'jquery' ) );

wp_register_script( 'bu-liaison-main', plugin_dir_url( __FILE__ ) . 'assets/js/main.js', array( 'jquery' ) );





