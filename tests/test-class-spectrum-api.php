<?php
/**
 * Unit test file
 *
 * @package BU_Liaison_Inquiry
 */

/**
 * Cover BU\Plugins\Liaison_Inquiry\Spectrum_API class
 *
 * @group bu-liaison-inquiry
 * @group bu-liaison-inquiry-spectrum-api
 */
class BU_Liaison_Inquiry_Test_Spectrum_API extends WP_UnitTestCase {

	/**
	 * Setup the testcase
	 */
	public function setUp() {
		$this->api = new BU\Plugins\Liaison_Inquiry\Spectrum_API( 'key', 'client_id' );
	}

	/**
	 * API URLs and instance variables should be set
	 *
	 * @covers BU\Plugins\Liaison_Inquiry\Spectrum_API::__construct
	 */
	public function test_constructor() {
		$this->assertNotEmpty( $this->api::$requirements_url );
		$this->assertNotEmpty( $this->api::$submit_url );
		$this->assertNotEmpty( $this->api::$client_rules_url );
		$this->assertNotEmpty( $this->api::$field_options_url );

		$this->assertEquals( 'key', $this->api->api_key );
		$this->assertEquals( 'client_id', $this->api->client_id );
	}

	/**
	 * Method should return "data" field of the API JSON-encoded response
	 *
	 * @covers BU\Plugins\Liaison_Inquiry\Spectrum_API::get_requirements
	 */
	public function test_get_requirements() {
		$api = $this->api;
		$interceptor = function ( $return, $args, $url ) use ( $api ) {
			if ( $url === $api::$requirements_url . '?IQS-API-KEY=key' ) {
				return array(
					'body' => '{"data": "some data"}',
				);
			}
		};

		add_filter( 'pre_http_request', $interceptor, 10, 3 );

		$this->assertEquals( 'some data', $api->get_requirements() );

		remove_filter( 'pre_http_request', $interceptor, 10 );
	}

	/**
	 * Method should throw an exception if the API response isn't successful
	 *
	 * @covers BU\Plugins\Liaison_Inquiry\Spectrum_API::get_requirements
	 */
	public function test_get_requirements_api_error() {
		$api = $this->api;
		$interceptor = function ( $return, $args, $url ) use ( $api ) {
			if ( $url === $api::$requirements_url . '?IQS-API-KEY=key' ) {
				return new WP_ERROR();
			}
		};

		add_filter( 'pre_http_request', $interceptor, 10, 3 );

		$this->expectException( \Exception::class );

		$api->get_requirements();

		remove_filter( 'pre_http_request', $interceptor, 10 );
	}

	/**
	 * Method should throw an exception if the API response doesn't contain form data
	 *
	 * @covers BU\Plugins\Liaison_Inquiry\Spectrum_API::get_requirements
	 */
	public function test_get_requirements_response_error() {
		$api = $this->api;
		$interceptor = function ( $return, $args, $url ) use ( $api ) {
			if ( $url === $api::$requirements_url . '?IQS-API-KEY=key' ) {
				return array(
					'body' => '{"message": "something went wrong"}',
				);
			}
		};

		add_filter( 'pre_http_request', $interceptor, 10, 3 );

		$this->expectException( \Exception::class );

		$api->get_requirements();

		remove_filter( 'pre_http_request', $interceptor, 10 );
	}

	/**
	 * Method should return the decoded API response
	 *
	 * @covers BU\Plugins\Liaison_Inquiry\Spectrum_API::post_form
	 */
	public function test_post_form() {
		$form_data = array();

		$api = $this->api;
		$interceptor = function ( $return, $args, $url ) use ( $api ) {
			if ( $url === $api::$submit_url ) {
				return array(
					'body' => '{"status": "success", "data": "some data", "message": "some message"}',
				);
			}
		};

		add_filter( 'pre_http_request', $interceptor, 10, 3 );

		$return = $api->post_form( $form_data );
		$this->assertEquals( 1, $return['status'] );
		$this->assertEquals( 'some data', $return['data'] );
		$this->assertEquals( 'some message', $return['response'] );

		remove_filter( 'pre_http_request', $interceptor, 10 );
	}

	/**
	 * Method should return bas status if API key is missing
	 *
	 * @covers BU\Plugins\Liaison_Inquiry\Spectrum_API::post_form
	 */
	public function test_post_form_key_error() {
		$api = $this->api;
		unset( $api->api_key );

		$return = $api->post_form( null );

		$this->assertEquals( 0, $return['status'] );
		$this->assertEquals( 'API Key missing', $return['response'] );
	}

	/**
	 * Method should return bad status when API request ends up with an error
	 *
	 * @covers BU\Plugins\Liaison_Inquiry\Spectrum_API::post_form
	 */
	public function test_post_form_request_error() {
		$form_data = array();

		$api = $this->api;
		$interceptor = function ( $return, $args, $url ) use ( $api ) {
			if ( $url === $api::$submit_url ) {
				return new WP_ERROR();
			}
		};

		add_filter( 'pre_http_request', $interceptor, 10, 3 );

		$return = $api->post_form( $form_data );
		$this->assertEquals( 0, $return['status'] );
		$this->assertNotEmpty( $return['response'] );

		remove_filter( 'pre_http_request', $interceptor, 10 );
	}

	/**
	 * Method should return bad status and error message on bad API response
	 *
	 * @covers BU\Plugins\Liaison_Inquiry\Spectrum_API::post_form
	 */
	public function test_post_form_response_error() {
		$form_data = array();

		$api = $this->api;
		$interceptor = function ( $return, $args, $url ) use ( $api ) {
			if ( $url === $api::$submit_url ) {
				return array(
					'body' => '{"status": "failure", "data": "some data"}',
				);
			}
		};

		add_filter( 'pre_http_request', $interceptor, 10, 3 );

		$return = $api->post_form( $form_data );
		$this->assertEquals( 0, $return['status'] );
		$this->assertEquals( 'some data', $return['data'] );
		$this->assertNotEmpty( $return['response'] );

		remove_filter( 'pre_http_request', $interceptor, 10 );
	}

}
