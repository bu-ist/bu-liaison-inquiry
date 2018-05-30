<?php
/**
 * Unit test file
 *
 * @package BU_Liaison_Inquiry
 */

use BU\Plugins\Liaison_Inquiry\Spectrum_API;
use BU\Plugins\Liaison_Inquiry\Inquiry_Form;

/**
 * Cover BU\Plugins\Liaison_Inquiry\Inquiry_Form class
 *
 * @group bu-liaison-inquiry
 * @group bu-liaison-inquiry-inquiry-form
 */
class BU_Liaison_Inquiry_Test_Inquiry_Form extends WP_UnitTestCase {

	/**
	 * Setup the testcase
	 */
	public function setUp() {
		$this->spectrum      = $this->createMock( Spectrum_API::class );
		$this->form_instance = new Inquiry_Form( $this->spectrum );
	}

	/**
	 * Call the method two times, with and without attributes, and check that
	 * it doesn't try to minify form definition when no attributes were passed
	 *
	 * @covers BU\Plugins\Liaison_Inquiry\Inquiry_Form::get_html
	 */
	public function test_get_html() {
		$default_form_id                  = null;
		$form_id                          = 'form_id';
		$shortcode_attributes             = [
			'some' => 'value',
		];
		$shortcode_attributes_with_form   = array_merge(
			$shortcode_attributes, [
				'form_id' => $form_id,
			]
		);
		$form_definition                  = 'form_definition coming from api, non-default form';
		$form_definition_default          = 'form_definition coming from api, default form';
		$minified_form_definition         = 'minified form definition, non-default form';
		$minified_form_definition_default = 'minified form definition, default form';
		$form_html                        = 'html response, non-default form';
		$form_html_default                = 'html response, default form';
		$form_html_mini                   = 'html response mini, non-default form';
		$form_html_mini_default           = 'html response mini, default form';

		$form = $this->getMockBuilder( Inquiry_Form::class )
					   ->setConstructorArgs( [ $this->spectrum ] )
					   ->setMethods( [ 'minify_form_definition', 'render_template' ] )
					   ->getMock();

		// Spectrum_API::get_requirements is called with form id as argument.
		$this->spectrum->expects( $this->exactly( 4 ) )
					   ->method( 'get_requirements' )
					->withConsecutive(
						[ $this->equalTo( $form_id ) ],
						[ $this->equalTo( $default_form_id ) ],
						[ $this->equalTo( $form_id ) ],
						[ $this->equalTo( $default_form_id ) ]
					)
					->willReturnOnConsecutiveCalls(
						$form_definition,
						$form_definition_default,
						$form_definition,
						$form_definition_default
					);

		// Plugin::minify_form_definition is called with proper arguments.
		$form->expects( $this->exactly( 2 ) )
			   ->method( 'minify_form_definition' )
			->withConsecutive(
				[ $form_definition, $shortcode_attributes ],
				[ $form_definition_default, $shortcode_attributes ]
			)
			   ->willReturnOnConsecutiveCalls( $minified_form_definition, $minified_form_definition_default );

		$map = [
			[ $form_definition, $form_id, $form_html ],
			[ $form_definition_default, $default_form_id, $form_html_default ],
			[ $minified_form_definition, $form_id, $form_html_mini ],
			[ $minified_form_definition_default, $default_form_id, $form_html_mini_default ],
		];

		$form->method( 'render_template' )->will( $this->returnValueMap( $map ) );

		// Method returns the return value of Plugin::render_template.
		$this->assertEquals( $form_html, $form->get_html( array( 'form_id' => $form_id ) ) );
		$this->assertEquals( $form_html_default, $form->get_html( array( 'form_id' => $default_form_id ) ) );
		$this->assertEquals( $form_html_mini, $form->get_html( $shortcode_attributes_with_form ) );
		$this->assertEquals( $form_html_mini_default, $form->get_html( $shortcode_attributes ) );
	}

	/**
	 * Emulate Spectrum_API::get_requirements throwing an exception
	 *
	 * @covers BU\Plugins\Liaison_Inquiry\Inquiry_Form::get_html
	 */
	public function test_get_html_api_error() {
		$exception_message = 'error in api response';

		$form = $this->getMockBuilder( Inquiry_Form::class )
					   ->setConstructorArgs( [ $this->spectrum ] )
					   ->setMethods( null )
					   ->getMock();

		// Spectrum_API::get_requirements throws the exception.
		$this->spectrum->method( 'get_requirements' )
					   ->will( $this->throwException( new \Exception( $exception_message ) ) );

		// Method returns the value of Exception::getMessage.
		$this->assertEquals( $exception_message, $form->get_html( null ) );
	}

	/**
	 * Try all possible shortcode attributes with different types of form fields
	 *
	 * @covers BU\Plugins\Liaison_Inquiry\Inquiry_Form::minify_form_definition
	 */
	public function test_minify_form_definition() {
		$field_1           = new stdClass();
		$field_1->id       = '1';
		$field_1->required = '1';

		$field_2           = new stdClass();
		$field_2->id       = '2';
		$field_2->required = '0';

		$field_3           = new stdClass();
		$field_3->id       = '3';
		$field_3->required = '1';

		$field_4           = new stdClass();
		$field_4->id       = '4';
		$field_4->required = '1';

		$form_section              = new stdClass();
		$form_section->fields      = [ $field_1, $field_2, $field_3, $field_4 ];
		$form_definition           = new stdClass();
		$form_definition->sections = [ $form_section ];

		$attributes = array(
			'fields' => '1,4',
			'source' => 'some source',
			'3'      => 'preset value',
			'4'      => 'ignored',
		);

		$form = $this->form_instance;

		$minified_form   = $form->minify_form_definition( $form_definition, $attributes );
		$minified_fields = $minified_form->sections[0]->fields;

		$this->assertCount( 4, $minified_fields );

		$this->assertEquals( $minified_fields[0]->id, 'SOURCE' );
		$this->assertEquals( $minified_fields[0]->hidden, true );
		$this->assertEquals( $minified_fields[0]->hidden_value, 'some source' );

		$this->assertEquals( $minified_fields[1]->id, '1' );
		$this->assertEquals( $minified_fields[1]->required, '1' );

		$this->assertEquals( $minified_fields[2]->id, '3' );
		$this->assertEquals( $minified_fields[2]->required, '1' );
		$this->assertEquals( $minified_fields[2]->hidden, true );
		$this->assertEquals( $minified_fields[2]->hidden_value, 'preset value' );

		$this->assertEquals( $minified_fields[3]->id, '4' );
		$this->assertEquals( $minified_fields[3]->required, '1' );
		$this->assertFalse( property_exists( $minified_fields[3], 'hidden_value' ) );
	}

	/**
	 * Ensure that nonce is verified and form fields are processed
	 * before passing them to Spectrum_API::form_post
	 *
	 * @covers BU\Plugins\Liaison_Inquiry\Inquiry_Form::handle_liaison_inquiry
	 */
	public function test_handle_liaison_inquiry() {
		$prepared_form = 'form prepared to be sent to API';
		$api_response  = 'return value of the API call';

		$form = $this->getMockBuilder( Inquiry_Form::class )
					   ->setConstructorArgs( [ $this->spectrum ] )
					   ->setMethods( [ 'verify_nonce', 'prepare_form_post' ] )
					   ->getMock();

		// Assert Plugin::verify_nonce called.
		$form->expects( $this->once() )
			   ->method( 'verify_nonce' )
			   ->willReturn( true );

		// Assert Plugin::prepare_form_post called with $_POST as a parameter.
		$form->expects( $this->once() )
			   ->method( 'prepare_form_post' )
			   ->with( $_POST )
			   ->willReturn( $prepared_form );

		// Assert Spectrum_API::post_form called with the return value of Plugin::prepare_form_post.
		$this->spectrum->expects( $this->once() )
					   ->method( 'post_form' )
					   ->with( $prepared_form )
					   ->willReturn( $api_response );

		// Method returns the return value of the Spectrum_API::form_post.
		$this->assertEquals( $api_response, $form->handle_liaison_inquiry() );
	}

	/**
	 * Emulate Plugin::verify_nonce failure and make sure that client-side is
	 * getting notified about it
	 *
	 * @covers BU\Plugins\Liaison_Inquiry\Inquiry_Form::handle_liaison_inquiry
	 */
	public function test_handle_liaison_inquiry_nonce_error() {
		$form = $this->getMockBuilder( Inquiry_Form::class )
					   ->setConstructorArgs( [ $this->spectrum ] )
					   ->setMethods( [ 'verify_nonce', 'prepare_form_post' ] )
					   ->getMock();

		// Assert Plugin::verify_nonce called.
		$form->expects( $this->once() )
			   ->method( 'verify_nonce' )
			   ->willReturn( false );

		$return = $form->handle_liaison_inquiry();

		// Method return error status.
		$this->assertEquals( 0, $return['status'] );
		$this->assertNotEmpty( $return['response'] );
	}

	/**
	 * Modify $_POST and insure proper nonce verification
	 *
	 * @covers BU\Plugins\Liaison_Inquiry\Inquiry_Form::verify_nonce
	 */
	public function test_verify_nonce() {
		$form = $this->form_instance;

		$_POST                             = array();
		$_POST[ $form::$nonce_field_name ] = 'asdf';

		// Nonce with the wrong value must fail.
		$this->assertFalse( $form->verify_nonce() );
		$this->assertEmpty( $_POST );

		$_POST[ $form::$nonce_field_name ] = wp_create_nonce( $form::$nonce_name );

		// Nonce with the correct value must succeed.
		$this->assertTrue( $form->verify_nonce() );
		$this->assertEmpty( $_POST );

		// $_POST with no nonce field must fail.
		$this->assertFalse( $form->verify_nonce() );
	}

	/**
	 * Pass different types of fields to the method and make sure that
	 * every type is properly processed
	 *
	 * @covers BU\Plugins\Liaison_Inquiry\Inquiry_Form::prepare_form_post
	 */
	public function test_prepare_form_post() {
		$form = $this->form_instance;

		$return = $form->prepare_form_post(
			array(
				'pass_through'         => 'value',
				'needs_sanitation'     => '<',
				'checkbox-text-opt-in' => '',
			)
		);

		$this->assertEquals( 'value', $return['pass_through'] );
		$this->assertEquals( '&lt;', $return['needs_sanitation'] );
		$this->assertEquals( '1', $return['checkbox-text-opt-in'] );
		$this->assertCount( 3, $return );

		$return = $form->prepare_form_post(
			array(
				'number'       => '999-999-9999',
				'phone_fields' => 'number',
			)
		);

		$this->assertEquals( '%2B19999999999', $return['number'] );
		$this->assertCount( 1, $return );
	}
}
