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
	 * Method should return "data" field of the API JSON-encoded respose
	 *
	 * @covers BU\Plugins\Liaison_Inquiry\Spectrum_API::get_requirements
	 */
	public function test_get_requirements() {
		$api = $this->api;
		$interceptor = function ( $return, $args, $url ) use ( $api ) {
			if ( $url === $api::$requirements_url . '?IQS-API-KEY=key' ) {
				return array(
					'body' => '{"data": "data_field"}',
				);
			}
		};

		add_filter( 'pre_http_request', $interceptor, 10, 3 );

		$this->assertEquals( 'data_field', $api->get_requirements() );

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

}
