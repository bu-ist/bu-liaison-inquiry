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
	const API_URL           = 'https://www.spectrumemp.com/api/';
	const SUBMITTABLE_URL   = self::API_URL . 'forms/submittable';
	const REQUIREMENTS_URL  = self::API_URL . 'forms/requirements';
	const SUBMIT_URL        = self::API_URL . 'forms/submit';
	const CLIENT_RULES_URL  = self::API_URL . 'field_rules/client_rules';
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
		$this->api_key   = $api_key;
	}

	/**
	 * Throw exception and log an error if an instance of WP_Error passed as argument.
	 *
	 * @param mixed $var Variable to check.
	 *
	 * @throws \Exception If $var is an instance of WP_Error.
	 */
	private function throw_on_error( $var ) {
		// Check for a successful response from external API server.
		if ( is_wp_error( $var ) ) {
			$error_message = $var->get_error_message();
			// @codeCoverageIgnoreStart
			if ( defined( 'BU_CMS' ) && BU_CMS ) {
				$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? ' (Request URI: ' . sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) . ')' : '';
				error_log( 'Liaison form API call failed: ' . $error_message . $request_uri );
			}// @codeCoverageIgnoreEnd
			throw new \Exception( 'Error: ' . $error_message );
		}
	}

	/**
	 * Get the list of forms from EMP API. The list is always prepended by
	 * "Inquiry Form" which is missing from API response.
	 *
	 * Do not cache, as this is for the admin interface, where it isn't used heavily and we don't want the data to be out of date.
	 *
	 * @return array Return the list of forms as an associative array in the format:
	 *               [(string)'Form Name' => (string|null) 'Form ID']
	 */
	public function get_forms_list() {
		// Default to the inquiry form that always exists.
		$result = array(
			'Inquiry Form' => null,
		);

		$api_query = self::SUBMITTABLE_URL . '?IQS-API-KEY=' . $this->api_key;

		// Set a longer timeout (10 seconds) to prevent timeouts when API is slow to respond.
		$args = array(
			'timeout' => 10,
		);

		$api_response = wp_remote_get( $api_query, $args );

		$this->throw_on_error( $api_response );

		$response_decode = json_decode( $api_response['body'], true );

		if ( isset( $response_decode['data'] ) && isset( $response_decode['data']['sem_forms'] ) ) {
			return array_merge( $result, $response_decode['data']['sem_forms'] );
		} else {
			return $result;
		}
	}

	/**
	 * Get info from EMP API about the fields that should be displayed for the form.
	 *
	 * Caches the result as a transient for 15 minutes. By default transients are stored
	 * in the WordPress database, but with a Redis implementation they will be stored in Redis.
	 *
	 * @param  string|null $form_id Form's ID, null for default one.
	 * @return array Return "data" field of the decoded JSON response.
	 *
	 * @throws \Exception If API response is not successful.
	 */
	public function get_requirements( $form_id ) {
		// Generate a unique cache key for this form and API key.
		$cache_key = 'liaison_form_req_' . md5( $this->api_key . '_' . ( $form_id ? $form_id : 'default' ) );

		// Try to get cached data first.
		$cached_data = get_transient( $cache_key );

		if ( false !== $cached_data ) {
			return $cached_data;
		}

		// If no cached data available, make the API call.
		$api_query = self::REQUIREMENTS_URL . '?IQS-API-KEY=' . $this->api_key;
		if ( $form_id ) {
			$api_query .= '&formID=' . $form_id;
		}

		// Set a longer timeout (10 seconds) to prevent timeouts when API is slow to respond.
		$args = array(
			'timeout' => 10,
		);

		$api_response = wp_remote_get( $api_query, $args );

		$this->throw_on_error( $api_response );

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

		// Cache the successful response for 15 minutes (900 seconds).
		set_transient( $cache_key, $inquiry_form_decode->data, 900 );

		return $inquiry_form_decode->data;
	}

	/**
	 * Send inquiry form to EMP API with retry capability.
	 *
	 * Makes up to 3 attempts to submit the form if retryable errors occur:
	 * 1. First attempt: 10 second timeout
	 * 2. Second attempt: 10 second timeout
	 * 3. Final attempt: 5 second timeout
	 *
	 * Total maximum time including 0.1s delays between attempts: ~25.2 seconds,
	 * staying well within CloudFront's 30-second timeout limit.
	 *
	 * @param array $post_vars   An array of form fields to be posted.
	 * @param int   $retry_count Optional. Number of retries already attempted. Default 0.
	 *
	 * @return array Returns the result of the form submission.
	 */
	public function post_form( $post_vars, $retry_count = 0 ) {
		$return      = array();
		$max_retries = 2; // Maximum number of retries (total attempts = 3).

		if ( ! isset( $this->api_key ) ) {
			$return['status']   = 0;
			$return['response'] = 'API Key missing';
			return $return;
		}

		// Capture the referring page before removing it from post vars.
		$referring_page = isset( $post_vars['referring_page'] ) ? $post_vars['referring_page'] : '';

		// Remove our custom fields before sending to API.
		unset( $post_vars['org'], $post_vars['referring_page'] );

		// Set the API Key from the site options.
		$post_vars['IQS-API-KEY'] = $this->api_key;

		// Use shorter timeout on final attempt to stay within CloudFront's 30-second limit.
		$timeout = ( 2 === $retry_count ) ? 5 : 10;

		// Setup arguments for the external API call.
		$post_args = array(
			'body'    => $post_vars,
			'timeout' => $timeout, // Timeout is either 10 or 5 seconds, depending which attempt this is.
		);

		$remote_submit = wp_remote_post( self::SUBMIT_URL, $post_args );

		if ( is_wp_error( $remote_submit ) ) {
			$error        = $remote_submit->get_error_message();
			$error_code   = $remote_submit->get_error_code();
			$should_retry = $retry_count < $max_retries && $this->is_retryable_error( $error_code );

			// @codeCoverageIgnoreStart
			if ( defined( 'BU_CMS' ) && BU_CMS ) {
				$page_info = ! empty( $referring_page ) ? ' (Referring Page: ' . $referring_page . ')' : '';

				// Build status message with attempt number and outcome.
				$attempt_num = $retry_count + 1;
				$outcome     = $should_retry ? 'retrying' : 'giving up';
				$status_msg  = sprintf( 'Try %d failed, %s', $attempt_num, $outcome );

				error_log( sprintf( '%s: %s - %s%s', __METHOD__, $status_msg, "Error: {$error}", $page_info ) );
			}// @codeCoverageIgnoreEnd

			// If we should retry, do so with an incremented retry count.
			if ( $should_retry ) {
				// Minimal delay (0.1s) before retry to allow transient issues to resolve.
				usleep( 100000 );
				return $this->post_form( $post_vars, $retry_count + 1 );
			}

			// If we shouldn't retry or have exhausted retries, return the error.
			$return['status']   = 0;
			$return['response'] = 'Failed submitting to Liaison API. Please retry. Error: ' . $error;
		} else {
			// Decode the response and activate redirect to the personal url on success.
			$resp = json_decode( $remote_submit['body'] );

			$return['status'] = ( isset( $resp->status ) && 'success' === $resp->status ) ? 1 : 0;
			$return['data']   = ( isset( $resp->data ) ) ? $resp->data : '';
			if ( isset( $resp->message ) ) {
				$return['response'] = $resp->message;
			} else {
				$return['response'] = 'Something bad happened, please refresh the page and try again.';
			}
		}

		return $return;
	}

	/**
	 * Determine if an error should trigger a retry attempt.
	 *
	 * Generally with most transport setups, the error code will be 'http_request_failed',
	 * but other transports may use different error codes so we try some additional ones here.
	 *
	 * @param string $error_code The WP_Error code from the failed request.
	 * @return bool True if the error is retryable, false otherwise.
	 */
	private function is_retryable_error( $error_code ) {
		// List of error codes that should trigger a retry.
		$retryable_errors = array(
			'http_request_failed', // General failure (includes timeouts).
			'curl_error',         // cURL specific errors.
			'http_500',           // Server errors that might be temporary.
			'http_503',           // Service unavailable (often temporary).
			'http_request_timeout', // Explicit timeout.
		);

		return in_array( $error_code, $retryable_errors, true );
	}
}
