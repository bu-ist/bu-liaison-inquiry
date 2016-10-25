<?php

/*
Plugin Name: BU Liaison Inquiry
Plugin URI: http://developer.bu.edu
Author: Boston University IS&T (Jonathan Williams)
Author URI: http://developer.bu.edu
Description: Provide a form to send data to the Liaison SpectrumEMP API
Version: 0.1
*/


/**
 * Main plugin class.
 *
 * Provides inquiry form shortcode, and form handler.
 */
class BU_Liaison_Inquiry {

	// SpectrumEMP API URL setup.
	const API_URL = 'https://www.spectrumemp.com/api/';
	const REQUIREMENTS_PATH = 'inquiry_form/requirements';
	const SUBMIT_PATH = 'inquiry_form/submit';
	const CLIENT_RULES_PATH = 'field_rules/client_rules';
	const FIELD_OPTIONS_PATH = 'field_rules/field_options';

	/**
	 * Path to plugin directory.
	 *
	 * @var      string
	 */
	public $plugin_dir;


	// Can't setup with a single statement until php 5.6.
	/**
	 * URL to fetch requirements.
	 *
	 * @var      string
	 */
	public $requirements_url;

	/**
	 * URL to submit form data to Liaison.
	 *
	 * @var      string
	 */
	public $submit_url;

	/**
	 * URL to fetch form validation rules.
	 *
	 * @var      string
	 */
	public $client_rules_url;

	/**
	 * URL to fetch options for form fields.
	 *
	 * @var      string
	 */
	public $field_options_url;

	/**
	 * Setup API URLs, and define form rendering and processing handlers.
	 */
	public function __construct() {
		// Store the plugin directory.
		$this->plugin_dir = dirname( __FILE__ );

		// Setup urls.  After php 5.6, these can become class const definitions (prior to 5.6 only flat strings can be class constants).
		$this->requirements_url = self::API_URL . self::REQUIREMENTS_PATH;
		$this->submit_url = self::API_URL . self::SUBMIT_PATH;
		$this->client_rules_url = self::API_URL . self::CLIENT_RULES_PATH;
		$this->field_options_url = self::API_URL . self::FIELD_OPTIONS_PATH;

		// Include the admin interface.
		include( $this->plugin_dir . '/admin/admin.php' );

		// Assign inquiry form shortcode.
		add_shortcode( 'liaison_inquiry_form', array( $this, 'liaison_inquiry_form' ) );

		// Setup form submission handlers.
		add_action( 'admin_post_nopriv_liaison_inquiry', array( $this, 'handle_liaison_inquiry' ) );
		add_action( 'admin_post_liaison_inquiry', array( $this, 'handle_liaison_inquiry' ) );
	}

	/**
	 * Shortcode definition that creates the Liaison inquiry form.
	 *
	 * @param array $atts Attributes specified in the shortcode.
	 * @return string Returns full form markup to replace the shortcode.
	 */
	function liaison_inquiry_form( $atts ) {

		// Get API key from option setting.
		$options = get_option( 'bu_liaison_inquiry_options' );
		$api_key = $options['APIKey'];
		$client_id = $options['ClientID'];

		// Optionally override the API key with the shortcode attribute if present.
		if ( isset( $atts['api_key'] ) ) { $api_key = $atts['api_key']; }

		// Get info from EMP about the fields that should be displayed for the form.
		$api_query = $this->requirements_url . '?IQS-API-KEY=' . $api_key;
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

		// Enqueue the validation scripts.
		wp_enqueue_script( 'jquery-ui' );
		wp_enqueue_script( 'jquery-masked' );
		wp_enqueue_script( 'jquery-pubsub' );
		wp_enqueue_script( 'iqs-validate' );
		wp_enqueue_script( 'bu-liaison-main' );
		wp_enqueue_script( 'field_rules_form_library' );
		wp_enqueue_script( 'field_rules_handler' );


		$inquiry_form = $inquiry_form_decode->data;

		// Setup nonce for form to protect against various possible attacks.
		$nonce = wp_nonce_field( 'liaison_inquiry', 'liaison_inquiry_nonce', false, false );

		// Include a template file like bu-navigation does.
		include( $this->plugin_dir . '/templates/form-template.php' );

		return $html;
	}

	/**
	 * Shortcode definition that creates the Liaison inquiry form.
	 *
	 * @return string Returns the result of the form submission as a JSON formatted array for the javascript validation script
	 */
	function handle_liaison_inquiry() {

		// Use wp nonce to verify the form was submitted correctly.
		if ( ! wp_verify_nonce( $_REQUEST['liaison_inquiry_nonce'], 'liaison_inquiry' ) ) {
			$return['status'] = 0;
			$return['response'] = 'There was a problem with the form nonce, please reload the page';
			echo json_encode( $return );
			return;
		}

		// Necessary to get the API key from the options, can't expose the key by passing it through the form.
		$options = get_option( 'bu_liaison_inquiry_options' );
		// Check for a valid API key value.
		if ( ! isset( $options['APIKey'] ) ) {
			$return['status'] = 0;
			$return['response'] = 'API Key missing';
			echo json_encode( $return );
			return;
		}

		//@todo the example operates directly on the $_POST array, which seems contrary to the best practice of sanitizing $_POST first
		$_POST['IQS-API-KEY'] = $options['APIKey'];

		// From EMP API example.
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
		$remote_submit = wp_remote_post( $this->submit_url, $post_args );

		// Decode the response and activate redirect to the personal url on success.
		$resp = json_decode( $remote_submit['body'] );

		// From EMP API example.
		$return = array();
		$return['status'] = 0;

		$return['status'] 		= ( isset( $resp->status ) && 'success' == $resp->status) ? 1 : 0;
		$return['response'] 	= ( isset( $resp->message ) ) ? $resp->message : 'Something bad happened, please refresh the page and try again.';
		$return['data'] 		= ( isset( $resp->data ) ) ? $resp->data : '';

		// Return a JSON encoded reply for the validation javascript.
		echo json_encode( $return );
	}
}

// Instantiate plugin (only once).
if ( ! isset( $GLOBALS['bu_liaison_inquiry'] ) ) {
	$GLOBALS['bu_liaison_inquiry'] = new BU_Liaison_Inquiry();
}

// Register js form validation scripts so that they may be enqueued by the shortcode handler.
wp_register_script( 'jquery-ui', plugin_dir_url( __FILE__ ) . 'assets/js/jquery/jquery-ui.js', array( 'jquery' ) );
wp_register_script( 'jquery-masked', plugin_dir_url( __FILE__ ) . 'assets/js/jquery/jquery-masked.js', array( 'jquery' ) );
wp_register_script( 'jquery-pubsub', plugin_dir_url( __FILE__ ) . 'assets/js/jquery/jquery-pubsub.js', array( 'jquery' ) );
wp_register_script( 'iqs-validate', plugin_dir_url( __FILE__ ) . 'assets/js/iqs/validate.js', array( 'jquery' ) );

//should register jquery-ui css styles too?

wp_register_script( 'field_rules_form_library', plugin_dir_url( __FILE__ ) . 'assets/js/field_rules_form_library.js', array( 'jquery' ) );
wp_register_script( 'field_rules_handler', plugin_dir_url( __FILE__ ) . 'assets/js/field_rules_handler.js', array( 'jquery' ) );

wp_register_script( 'bu-liaison-main', plugin_dir_url( __FILE__ ) . 'assets/js/main.js', array( 'jquery' ) );





