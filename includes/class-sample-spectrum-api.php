<?php
/**
 * Class mocking API responses
 *
 * @package BU_Liaison_Inquiry
 */

namespace BU\Plugins\Liaison_Inquiry;

/**
 * Mock requests to EMP API.
 *
 * Primarily used during development to avoid sending any requests to the remote server.
 * Used in place of SpectrumAPI class.
 *
 * @codeCoverageIgnore
 */
class Sample_Spectrum_API {

	/**
	 * URL to fetch form validation rules (required by front-end code)
	 *
	 * @var string
	 */
	public static $client_rules_url = 'https://www.spectrumemp.com/api/field_rules/client_rules';

	/**
	 * URL to fetch options for form fields (required by front-end code)
	 *
	 * @var string
	 */
	public static $field_options_url = 'https://www.spectrumemp.com/api/field_rules/field_options';

	/**
	 * Liaison Client ID (required by front-end code)
	 *
	 * @var string
	 */
	public $client_id;

	/**
	 * Setup $client_id.
	 *
	 * @param string $client_id Client ID.
	 */
	public function __construct( $client_id ) {
		$this->client_id = $client_id;
	}

	/**
	 * Load a json files from the local directory
	 *
	 * @param string $name Filename.
	 *
	 * @return array Decoded file content.
	 */
	public function load_mock( $name ) {
		$json = file_get_contents( dirname( __FILE__ ) . '/../sample/' . $name . '.json' );

		return json_decode( $json );
	}

	/**
	 * Simulate getting the list of required fields from API.
	 *
	 * @return array Mock value formatted as a "data" field of the API response.
	 */
	public function get_requirements() {
		return $this->load_mock( 'requirements' );
	}

	/**
	 * Simulate sending an inquiry form to EMP API.
	 *
	 * @param array $post_vars An array of form fields to be posted.
	 *
	 * @return array Mock value simulating the result of the form submission.
	 */
	public function post_form( $post_vars ) {
		if ( defined( 'BU_LIAISON_INQUIRY_POST_FAIL' ) && BU_LIAISON_INQUIRY_POST_FAIL ) {
			return $this->load_mock( 'bad_form' );
		} elseif ( defined( 'BU_LIAISON_INQUIRY_POST_DUPLICATE' ) && BU_LIAISON_INQUIRY_POST_DUPLICATE ) {
			return $this->load_mock( 'duplicate_form' );
		} else {
			return $this->load_mock( 'good_form' );
		}
	}
}
