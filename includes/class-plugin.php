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
	 */
	public function get_api_instance() {
		// Get API key and Client ID from option settings.
		$client_id = Settings::get( 'ClientID' );
		$api_key   = Settings::get( 'APIKey' );

		$class = $this->api_class;

		return new $class( $client_id, $api_key );
	}

	/**
	 * Creates an inquiry form instance
	 */
	public function get_form() {
		$api = $this->get_api_instance();
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
		$form = $this->get_form();
		return $form->get_html( $attrs );
	}

	/**
	 * WordPress Ajax request handler
	 */
	public function ajax_inquiry_form_post() {
		$form = $this->get_form();
		wp_send_json( $form->handle_liaison_inquiry() );
	}

}
