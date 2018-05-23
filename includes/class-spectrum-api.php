<?php
/**
 * Class wrapping over-the-network communication with the API server
 *
 * @package BU_Liaison_Inquiry
 */

namespace BU\Plugins\Liaison_Inquiry;

/**
 * SpectrumEMP API class.
 *
 * Sends, retrieves, and parses data from SpectrumEMP API.
 */
class Spectrum_API {

	// SpectrumEMP API URL setup.
	const API_URL = 'https://www.spectrumemp.com/api/';
	const REQUIREMENTS_URL = self::API_URL . 'inquiry_form/requirements';
	const SUBMIT_URL = self::API_URL . 'inquiry_form/submit';
	const CLIENT_RULES_URL = self::API_URL . 'field_rules/client_rules';
	const FIELD_OPTIONS_URL = self::API_URL . 'field_rules/field_options';

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
	 * Setup $client_id and $api_key variables.
	 *
	 * @param string $client_id Client ID.
	 * @param string $api_key   API Key.
	 */
	public function __construct( $client_id, $api_key ) {
		$this->client_id = $client_id;
		$this->api_key = $api_key;
	}

	/**
	 * Get info from EMP API about the fields that should be displayed for the form.
	 *
	 * @return array Return "data" field of the decoded JSON response.
	 *
	 * @throws \Exception If API response is not successful.
	 */
	public function get_requirements() {
		$api_query = self::REQUIREMENTS_URL . '?IQS-API-KEY=' . $this->api_key;
		$api_response = wp_remote_get( $api_query );

		// Check for a successful response from external API server.
		if ( is_wp_error( $api_response ) ) {
			$error_message = $api_response->get_error_message();
			// @codeCoverageIgnoreStart
			if ( defined( 'BU_CMS' ) && BU_CMS ) {
				error_log( 'Liaison form API call failed: ' . $error_message );
			}// @codeCoverageIgnoreEnd
			throw new \Exception( 'Error: ' . $error_message );
		}

		$inquiry_form_decode = json_decode( $api_response['body'] );

		// Check that the response from the API contains actual form data.
		if ( ! isset( $inquiry_form_decode->data ) ) {
			$form_message = $inquiry_form_decode->message;
			// @codeCoverageIgnoreStart
			if ( defined( 'BU_CMS' ) && BU_CMS ) {
				error_log( 'Bad response from Liaison API server: ' . $form_message );
			}// @codeCoverageIgnoreEnd
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

		$remote_submit = wp_remote_post( self::SUBMIT_URL, $post_args );

		if ( is_wp_error( $remote_submit ) ) {
			$return['status'] = 0;
			$return['response'] = 'Failed submitting to Liaison API. Please retry. Error: ' .
								  $remote_submit->get_error_message();
			// @codeCoverageIgnoreStart
			if ( defined( 'BU_CMS' ) && BU_CMS ) {
				error_log( sprintf( '%s: %s', __METHOD__, $return['response'] ) );
			}// @codeCoverageIgnoreEnd
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