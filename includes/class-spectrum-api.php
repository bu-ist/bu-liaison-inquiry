<?php
/**
 * Plugin Name: BU Liaison Inquiry
 * Plugin URI: http://developer.bu.edu
 * Author: Boston University IS&T (Jonathan Williams)
 * Author URI: http://developer.bu.edu
 * Description: Provide a form to send data to the Liaison SpectrumEMP API
 * Version: 0.6
 *
 * @package BULiaisonInquiry\SpectrumAPI
 */

namespace BU_Liaison_Inquiry;

/**
 * SpectrumEMP API class.
 *
 * Sends, retrieves, and parses data from SpectrumEMP API.
 */
class Spectrum_API {

	// SpectrumEMP API URL setup.
	const API_URL = 'https://www.spectrumemp.com/api/';
	const REQUIREMENTS_PATH = 'inquiry_form/requirements';
	const SUBMIT_PATH = 'inquiry_form/submit';
	const CLIENT_RULES_PATH = 'field_rules/client_rules';
	const FIELD_OPTIONS_PATH = 'field_rules/field_options';

	// Can't setup with a single statement until php 5.6.
	/**
	 * URL to fetch requirements.
	 *
	 * @var string
	 */
	public static $requirements_url;

	/**
	 * URL to submit form data to Liaison.
	 *
	 * @var string
	 */
	public static $submit_url;

	/**
	 * URL to fetch form validation rules.
	 *
	 * @var string
	 */
	public static $client_rules_url;

	/**
	 * URL to fetch options for form fields.
	 *
	 * @var string
	 */
	public static $field_options_url;

	/**
	 * Liaison API Key.
	 *
	 * @var string
	 */
	public $api_key;

	/**
	 * Liaison Client ID.
	 *
	 * @var string
	 */
	public $client_id;

	/**
	 * Setup API URLs and set instance variables.
	 *
	 * @param string $api_key   API Key.
	 * @param string $client_id Client ID.
	 */
	public function __construct( $api_key, $client_id ) {
		// Setup urls. After php 5.6, these can become class const definitions
		// (prior to 5.6 only flat strings can be class constants).
		self::$requirements_url = self::API_URL . self::REQUIREMENTS_PATH;
		self::$submit_url = self::API_URL . self::SUBMIT_PATH;
		self::$client_rules_url = self::API_URL . self::CLIENT_RULES_PATH;
		self::$field_options_url = self::API_URL . self::FIELD_OPTIONS_PATH;

		$this->api_key = $api_key;
		$this->client_id = $client_id;
	}

	/**
	 * Get info from EMP API about the fields that should be displayed for the form.
	 *
	 * @return array Return "data" field of the decoded JSON response.
	 *
	 * @throws \Exception If API response is not successful.
	 */
	public function get_requirements() {
		$api_query = self::$requirements_url . '?IQS-API-KEY=' . $this->api_key;
		$api_response = wp_remote_get( $api_query );

		// Check for a successful response from external API server.
		if ( is_wp_error( $api_response ) ) {
			$error_message = $api_response->get_error_message();
			error_log( 'Liaison form API call failed: ' . $error_message );
			throw new \Exception( 'Error: ' . $error_message );
		}

		$inquiry_form_decode = json_decode( $api_response['body'] );

		// Check that the response from the API contains actual form data.
		if ( ! isset( $inquiry_form_decode->data ) ) {
			$form_message = $inquiry_form_decode->message;
			error_log( 'Bad response from Liaison API server: ' . $form_message );
			throw new \Exception( 'Error: ' . $form_message );
		}

		return $inquiry_form_decode->data;
	}

	/**
	 * Send inquiry form to EMP API.
	 *
	 * @param array $post_vars An array of form fields to be posted.
	 *
	 * @return array Returns the result of the form submission.
	 */
	public function post_form( $post_vars ) {
		$return = array();

		if ( ! isset( $this->api_key ) ) {
			$return['status'] = 0;
			$return['response'] = 'API Key missing';
			return $return;
		}

		// Set the API Key from the site options.
		$post_vars['IQS-API-KEY'] = $this->api_key;

		// Setup arguments for the external API call.
		$post_args = array(
			'body' => $post_vars,
		);

		$remote_submit = wp_remote_post( self::$submit_url, $post_args );

		if ( is_wp_error( $remote_submit ) ) {
			$return['status'] = 0;
			$return['response'] = 'Failed submitting to Liaison API. Please retry. Error: ' .
								  $remote_submit->get_error_message();
			error_log( sprintf( '%s: %s', __METHOD__, $return['response'] ) );
		} else {
			// Decode the response and activate redirect to the personal url on success.
			$resp = json_decode( $remote_submit['body'] );

			$return['status'] = ( isset( $resp->status ) && 'success' === $resp->status) ? 1 : 0;
			$return['data'] = ( isset( $resp->data ) ) ? $resp->data : '';
			if ( isset( $resp->message ) ) {
				$return['response'] = $resp->message;
			} else {
				$return['response'] = 'Something bad happened, please refresh the page and try again.';
			}
		}

		return $return;
	}
}
