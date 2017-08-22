<?php

class BU_Liaison_Inquiry_Test_Plugin extends WP_UnitTestCase {

	public function setUp() {
		$this->spectrum = $this->createMock(BU\Plugins\Liaison_Inquiry\Spectrum_API::class);
		$this->plugin_instance = new BU\Plugins\Liaison_Inquiry\Plugin(null);
	}

	/**
	 * @covers BU\Plugins\Liaison_Inquiry\Plugin::liaison_inquiry_form
	 */
	public function test_liaison_inquiry_form() {
		$shortcode_attributes = array('some' => 'value');
		$form_definition = 'form_definition coming from api';
		$minified_form_definition = 'minified form definition';
		$form_html = 'html response';
		$form_html_mini = 'html response mini';

		$plugin = $this->getMockBuilder(BU\Plugins\Liaison_Inquiry\Plugin::class)
									 ->setConstructorArgs([$this->spectrum])
									 ->setMethods(['minify_form_definition', 'get_form_html'])
									 ->getMock();

		// Spectrum_API::get_requirements is called
		$this->spectrum->expects($this->exactly(2))
									 ->method('get_requirements')
									 ->willReturn($form_definition);

		// Plugin::minify_form_definition is called with proper arguments
		$plugin->expects($this->once())
					 ->method('minify_form_definition')
					 ->with($form_definition, $shortcode_attributes)
					 ->willReturn($minified_form_definition);

		$map = [
			[$form_definition, $form_html],
			[$minified_form_definition, $form_html_mini]
		];

		$plugin->method('get_form_html')->will($this->returnValueMap($map));

		// Method returns the return value of Plugin::get_form_html
		$this->assertEquals($form_html, $plugin->liaison_inquiry_form(array()));
		$this->assertEquals($form_html_mini, $plugin->liaison_inquiry_form($shortcode_attributes));
	}

	/**
	 * @covers BU\Plugins\Liaison_Inquiry\Plugin::liaison_inquiry_form
	 */
	public function test_liaison_inquiry_form_api_error()
	{
		$exception_message = 'error in api response';

		$plugin = $this->getMockBuilder(BU\Plugins\Liaison_Inquiry\Plugin::class)
									 ->setConstructorArgs([$this->spectrum])
									 ->setMethods(null)
									 ->getMock();

		// Spectrum_API::get_requirements throws the exception.
		$this->spectrum->method('get_requirements')
									 ->will($this->throwException(new \Exception($exception_message)));

		// Method returns the value of Exception::getMessage
		$this->assertEquals($exception_message, $plugin->liaison_inquiry_form(null));
	}

	/**
	 * @covers BU\Plugins\Liaison_Inquiry\Plugin::minify_form_definition
	 */
	public function test_minify_form_definition()
	{
		$field_1 = new stdClass();
		$field_1->id = '1';
		$field_1->required = '1';

		$field_2 = new stdClass();
		$field_2->id = '2';
		$field_2->required = '0';

		$field_3 = new stdClass();
		$field_3->id = '3';
		$field_3->required = '1';

		$field_4 = new stdClass();
		$field_4->id = '4';
		$field_4->required = '1';

		$form_section = new stdClass();
		$form_section->fields = [$field_1, $field_2, $field_3, $field_4];
		$form_definition = new stdClass();
		$form_definition->sections = [$form_section];

		$attributes = array(
			'fields' => '1,4',
			'source' => 'some source',
			'3' => 'preset value',
			'4' => 'ignored'
		);

		$plugin = $this->plugin_instance;

		$minified_form = $plugin->minify_form_definition($form_definition, $attributes);
		$minified_fields = $minified_form->sections[0]->fields;

		$this->assertCount(4, $minified_fields);

		$this->assertEquals($minified_fields[0]->id, 'SOURCE');
		$this->assertEquals($minified_fields[0]->hidden, true);
		$this->assertEquals($minified_fields[0]->hidden_value, 'some source');

		$this->assertEquals($minified_fields[1]->id, '1');
		$this->assertEquals($minified_fields[1]->required, '1');

		$this->assertEquals($minified_fields[2]->id, '3');
		$this->assertEquals($minified_fields[2]->required, '1');
		$this->assertEquals($minified_fields[2]->hidden, true);
		$this->assertEquals($minified_fields[2]->hidden_value, 'preset value');

		$this->assertEquals($minified_fields[3]->id, '4');
		$this->assertEquals($minified_fields[3]->required, '1');
		$this->assertFalse(property_exists($minified_fields[3], 'hidden_value'));
	}

	/**
	 * @covers BU\Plugins\Liaison_Inquiry\Plugin::handle_liaison_inquiry
	 */
	public function test_handle_liaison_inquiry()
	{
		$prepared_form = 'form prepared to be sent to API';
		$api_response = 'return value of the API call';

		$plugin = $this->getMockBuilder(BU\Plugins\Liaison_Inquiry\Plugin::class)
									 ->setConstructorArgs([$this->spectrum])
									 ->setMethods(['verify_nonce', 'prepare_form_post'])
									 ->getMock();

		// Assert Plugin::verify_nonce called
		$plugin->expects($this->once())
					 ->method('verify_nonce')
					 ->willReturn(true);

		// Assert Plugin::prepare_form_post called with $_POST as a parameter
		$plugin->expects($this->once())
					 ->method('prepare_form_post')
					 ->with($_POST)
					 ->willReturn($prepared_form);

		// Assert Spectrum_API::post_form called with the return value of Plugin::prepare_form_post
		$this->spectrum->expects($this->once())
									 ->method('post_form')
									 ->with($prepared_form)
									 ->willReturn($api_response);

		// Method returns the return value of the Spectrum_API::form_post
		$this->assertEquals($api_response, $plugin->handle_liaison_inquiry());
	}

	/**
	 * @covers BU\Plugins\Liaison_Inquiry\Plugin::handle_liaison_inquiry
	 */
	public function test_handle_liaison_inquiry_nonce_error()
	{
		$plugin = $this->getMockBuilder(BU\Plugins\Liaison_Inquiry\Plugin::class)
									 ->setConstructorArgs([$this->spectrum])
									 ->setMethods(['verify_nonce', 'prepare_form_post'])
									 ->getMock();

		// Assert Plugin::verify_nonce called
		$plugin->expects($this->once())
					 ->method('verify_nonce')
					 ->willReturn(false);

		$return = $plugin->handle_liaison_inquiry();

		// Method return error status
		$this->assertEquals(0, $return['status']);
		$this->assertNotEmpty($return['response']);
	}

	/**
	 * @covers BU\Plugins\Liaison_Inquiry\Plugin::verify_nonce
	 */
	public function test_verify_nonce()
	{
		$plugin = $this->plugin_instance;

		$_POST = array();
		$_POST[$plugin::$nonce_field_name] = 'asdf';

		// Nonce with the wrong value must fail
		$this->assertFalse($plugin->verify_nonce());
		$this->assertEmpty($_POST);

		$_POST[$plugin::$nonce_field_name] = wp_create_nonce($plugin::$nonce_name);

		// Nonce with the correct value must succeed
		$this->assertTrue($plugin->verify_nonce());
		$this->assertEmpty($_POST);

		// $_POST with no nonce field must fail
		$this->assertFalse($plugin->verify_nonce());
	}

	/**
	 * @covers BU\Plugins\Liaison_Inquiry\Plugin::prepare_form_post
	 */
	public function test_prepare_form_post()
	{
		$plugin = $this->plugin_instance;

		$return = $plugin->prepare_form_post(array(
			'pass_through' => 'value',
			'needs_sanitation' => '<',
			'checkbox-text-opt-in' => ''
		));

		$this->assertEquals('value', $return['pass_through']);
		$this->assertEquals('&lt;', $return['needs_sanitation']);
		$this->assertEquals('1', $return['checkbox-text-opt-in']);
		$this->assertCount(3, $return);

		$return = $plugin->prepare_form_post(array(
			'number' => '999-999-9999',
			'phone_fields' => 'number'
		));

		$this->assertEquals('%2B19999999999', $return['number']);
		$this->assertCount(1, $return);
	}
}
