<?php
/**
 * Class containing methods to render shortcode and process AJAX form submissions
 *
 * @package BU_Liaison_Inquiry
 */

namespace BU\Plugins\Liaison_Inquiry;

/**
 * Main plugin class.
 *
 * Provides inquiry form shortcode and ajax form submission handlers.
 */
class Plugin {

	/**
	 * API class name
	 *
	 * @var String
	 */
	public $api_class;

	/**
	 * Setup class variables
	 *
	 * @param String $api_class API class name.
	 */
	public function __construct( $api_class ) {
		$this->api_class = $api_class;
	}

	/**
	 * Initializes API class
	 *
	 * @param string|null $org_key Optional org key for alternate credentials.
	 */
	public function get_api_instance( $org_key = null ) {
		$creds = Settings::get_credentials_for_org( $org_key );
		$class = $this->api_class;
		return new $class( $creds['ClientID'], $creds['APIKey'] );
	}

	/**
	 * Creates an inquiry form instance
	 *
	 * @param string|null $org_key Optional org key for alternate credentials.
	 */
	public function get_form( $org_key = null ) {
		$api = $this->get_api_instance( $org_key );
		return new Inquiry_Form( $api );
	}

	/**
	 * Shortcode definition that creates the Liaison inquiry form
	 *
	 * @param  array|string $attrs Attributes specified in the shortcode.
	 * @return string Returns full form markup to replace the shortcode.
	 */
	public function liaison_inquiry_form( $attrs ) {
		// $attrs will be an empty string when no shortcode attributes were used.
		// Convert it into an empty array.
		if ( '' === $attrs ) {
			$attrs = [];
		}
		$org_key = isset( $attrs['org'] ) ? sanitize_text_field( $attrs['org'] ) : null;
		$form    = $this->get_form( $org_key );
		return $form->get_html( $attrs );
	}

	/**
	 * Handles AJAX form submissions for the Liaison inquiry form.
	 *
	 * Selects the correct API credentials based on the submitted org key,
	 * instantiates the form, invokes the form handler, and returns the result as a JSON response.
	 * This is what actually sends submitted data to the Liaison API.
	 * Nonce verification and sensitive processing are handled in the form handler.
	 */
	public function ajax_inquiry_form_post() {
		// We only use $_POST['org'] here to select credentials; all sensitive processing is nonce-protected in handle_liaison_inquiry().
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$org_key = isset( $_POST['org'] ) ? sanitize_text_field( $_POST['org'] ) : null;

		// Get the form instance.
		$form = $this->get_form( $org_key );

		// Handle the inquiry form submission and return the result as JSON.
		wp_send_json( $form->handle_liaison_inquiry() );
	}

}
