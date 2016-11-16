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

	// Setup dummy value for required fields that aren't part of the mini form.
	const MINI_DUMMY_VALUE = 'mini-form';

	/**
	 * Path to plugin directory.
	 *
	 * @var      string
	 */
	static $plugin_dir;


	// Can't setup with a single statement until php 5.6.
	/**
	 * URL to fetch requirements.
	 *
	 * @var      string
	 */
	static $requirements_url;

	/**
	 * URL to submit form data to Liaison.
	 *
	 * @var      string
	 */
	static $submit_url;

	/**
	 * URL to fetch form validation rules.
	 *
	 * @var      string
	 */
	static $client_rules_url;

	/**
	 * URL to fetch options for form fields.
	 *
	 * @var      string
	 */
	static $field_options_url;

	/**
	 * Setup API URLs, and define form rendering and processing handlers.
	 */
	public function __construct() {
		// Store the plugin directory.
		self::$plugin_dir = dirname( __FILE__ );

		// Setup urls.  After php 5.6, these can become class const definitions (prior to 5.6 only flat strings can be class constants).
		self::$requirements_url = self::API_URL . self::REQUIREMENTS_PATH;
		self::$submit_url = self::API_URL . self::SUBMIT_PATH;
		self::$client_rules_url = self::API_URL . self::CLIENT_RULES_PATH;
		self::$field_options_url = self::API_URL . self::FIELD_OPTIONS_PATH;

		// Include the admin interface.
		include( self::$plugin_dir . '/admin/admin.php' );

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

		if ( $atts ) {
			// Assign any preset field ids in the shortcode attributes.
			$presets = array();
			foreach ( $atts as $att_key => $att ) {
				// Look for integer numbers, these are field ids.
				if ( intval( $att_key ) === $att_key ) {
					$presets[ $att_key ] = $att;
				}
			}
		}

		// Get info from EMP about the fields that should be displayed for the form.
		$api_query = self::$requirements_url . '?IQS-API-KEY=' . $api_key;
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

		// Enqueue form specific CSS.
		wp_enqueue_style( 'liason-form-style' );
		wp_enqueue_style( 'jquery-ui-css' );

		// Setup field ids if a restricted field set was specified in the shortcode.
		$field_ids = array();
		if ( isset( $atts['fields'] ) ) {
			// Parse fields attribute.
			$fields = explode( ',', $atts['fields'] );
			foreach ( $fields as $field ) {
				// Only use integer values.
				if ( ctype_digit( $field ) ) {
					$field_ids[] = $field;
				}
			}
		}

		$inquiry_form = $this->minify_form_definition( $inquiry_form_decode->data, $field_ids, $presets );

		// Setup nonce for form to protect against various possible attacks.
		$nonce = wp_nonce_field( 'liaison_inquiry', 'liaison_inquiry_nonce', false, false );

		// Include template file.
		ob_start();
		include( self::$plugin_dir . '/templates/form-template.php' );
		$form_html = ob_get_contents();
		ob_end_clean();

		return $form_html;
	}

	/**
	 * Takes the form definition returned by the Liaison API, strips out any unspecified fields for the mini form, and sets hidden defaults for required fields
	 *
	 * @param array $inquiry_form Parsed JSON data from Liaison API.
	 * @param array $field_ids List of fields to show. If not specified, the full form is returned.
	 * @param array $presets Array of preset field ids and values.
	 * @return array Returns a data array of the processed form data to be passed to the template
	 */
	function minify_form_definition( $inquiry_form, $field_ids, $presets ) {
		// If field_ids are specified, remove any fields that aren't in the specified set.
		if ( 0 < count( $field_ids ) ) {
			foreach ( $inquiry_form->sections as $section ) {
				foreach ( $section->fields as $field_key => $field ) {
					// Field by field processing.
					if ( ! in_array( $field->id, $field_ids ) ) {
						// If a field isn't listed and isn't required, just remove it.
						if ( '1' != $field->required ) {
							unset( $section->fields[ $field_key ] );
						} else {
							// If a field isn't listed but is required, set the hidden flag and preset the value.
							$field->hidden = true;
							if ( isset( $presets[ $field->id ] ) ) {
								$field->hidden_value = $presets[ $field->id ];
							} else {
								$field->hidden_value = self::MINI_DUMMY_VALUE;
							}
						}
					}
				}
			}
		}
		return $inquiry_form;
	}

	/**
	 * Handle incoming form data
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

		// Clear the verified nonce from $_POST so that it doesn't get passed on to Liaison.
		unset( $_POST['liaison_inquiry_nonce'] );

		// Necessary to get the API key from the options, can't expose the key by passing it through the form.
		$options = get_option( 'bu_liaison_inquiry_options' );
		// Check for a valid API key value.
		if ( ! isset( $options['APIKey'] ) ) {
			$return['status'] = 0;
			$return['response'] = 'API Key missing';
			echo json_encode( $return );
			return;
		}

		// Phone number fields are given special formatting, phone field ids are passed as a hidden field in the form.
		$phone_fields = sanitize_text_field( $_POST['phone_fields'] );
		$phone_fields = explode( ',', $phone_fields );
		unset( $_POST['phone_fields'] );

		$post_vars = $this->prepare_form_post( $_POST, $phone_fields );

		// Set the API Key from the site options.
		$post_vars['IQS-API-KEY'] = $options['APIKey'];

		// Setup arguments for the external API call.
		$post_args = array( 'body' => $post_vars );

		// Make the external API call.
		$remote_submit = wp_remote_post( self::$submit_url, $post_args );

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

	/**
	 * Sanitize and format post data for submission
	 *
	 * @param array $incoming_post_vars $_POST values as submitted.
	 * @param array $phone_fields Array of phone field ids.
	 * @return array Returns an array of sanitized and prepared post values.
	 */
	function prepare_form_post( $incoming_post_vars, $phone_fields ) {
		// Process all of the existing values into a new array.
		$post_vars = array();
		foreach ( $incoming_post_vars as $key => $value ) {
			if ( in_array( $key, $phone_fields ) ) {
				// If it is a phone field, apply special formatting.
				// Strip out everything except numerals.
				$value = preg_replace( '/[^0-9]/', '', $value );
				$value = '%2B1' . $value;		// Append +1 for US, but + needs to be %2B for posting.

			} elseif ( stripos( $key, '-text-opt-in' ) !== false ) {
				// If this checkbox field is set then it was checked.
				$value = '1';
			} else {
				// Apply basic field sanitization.
				$value = sanitize_text_field( $value );
			}

			$post_vars[ $key ] = $value;
		}
		return $post_vars;
	}
}

// Instantiate plugin (only once).
if ( ! isset( $GLOBALS['bu_liaison_inquiry'] ) ) {
	$GLOBALS['bu_liaison_inquiry'] = new BU_Liaison_Inquiry();
}

// Register js form validation scripts so that they may be enqueued by the shortcode handler.
wp_register_script( 'jquery-ui', plugin_dir_url( __FILE__ ) . 'assets/js/jquery/jquery-ui.min.js', array( 'jquery' ) );
wp_register_script( 'jquery-masked', plugin_dir_url( __FILE__ ) . 'assets/js/jquery/jquery-masked.js', array( 'jquery' ) );
wp_register_script( 'jquery-pubsub', plugin_dir_url( __FILE__ ) . 'assets/js/jquery/jquery-pubsub.js', array( 'jquery' ) );
wp_register_script( 'iqs-validate', plugin_dir_url( __FILE__ ) . 'assets/js/iqs/validate.js', array( 'jquery' ) );

wp_register_script( 'field_rules_form_library', plugin_dir_url( __FILE__ ) . 'assets/js/field_rules_form_library.js', array( 'jquery' ) );
wp_register_script( 'field_rules_handler', plugin_dir_url( __FILE__ ) . 'assets/js/field_rules_handler.js', array( 'jquery' ) );

wp_register_script( 'bu-liaison-main', plugin_dir_url( __FILE__ ) . 'assets/js/main.js', array( 'jquery' ) );

wp_register_style( 'liason-form-style',  plugin_dir_url( __FILE__ ) . 'assets/css/form-style.css' );
wp_register_style( 'jquery-ui-css',  plugin_dir_url( __FILE__ ) . 'assets/js/jquery/jquery-ui.min.css' );



