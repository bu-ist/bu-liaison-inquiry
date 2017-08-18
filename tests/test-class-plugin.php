<?php

class BU_Liaison_Inquiry_Test_Plugin extends WP_UnitTestCase {

	public function setUp() {
		$this->fixture_api = new BU\Plugins\Liaison_Inquiry\Mock_Spectrum_API('', '');
		$this->real_plugin_instance = new BU\Plugins\Liaison_Inquiry\Plugin($this->fixture_api);
	}

	/**
	 * @covers BU\Plugins\Liaison_Inquiry\Plugin::liaison_inquiry_form
	 */
	public function test_liaison_inquiry_form() {
		$shortcode_attributes = null;
		$requirements = $this->fixture_api->get_requirements();
		$form_definition = $this->real_plugin_instance->minify_form_definition(
			$requirements,
			$shortcode_attributes
		);
		$form_html = 'html response';

		$api = $this->getMockBuilder(BU\Plugins\Liaison_Inquiry\Mock_Spectrum_API::class)
								->setConstructorArgs(['', ''])
								->setMethods(['get_requirements'])
								->getMock();

		$plugin = $this->getMockBuilder(BU\Plugins\Liaison_Inquiry\Plugin::class)
									 ->setConstructorArgs([$api])
									 ->setMethods(['minify_form_definition', 'get_form_html'])
									 ->getMock();

		// Spectrum_API::get_requirements is called
		$api->expects($this->once())
				->method('get_requirements')
				->willReturn($requirements);

		// Plugin::minify_form_definition is called with proper arguments
		$plugin->expects($this->once())
					 ->method('minify_form_definition')
					 ->with($requirements, $shortcode_attributes)
					 ->willReturn($form_definition);

		// Plugin::get_form_html is called with proper arguments
		$plugin->expects($this->once())
					 ->method('get_form_html')
					 ->with($form_definition)
					 ->willReturn($form_html);

		// Method returns the return value of Plugin::get_form_html
		$this->assertEquals($form_html, $plugin->liaison_inquiry_form(null));
	}

	/**
	 * @covers BU\Plugins\Liaison_Inquiry\Plugin::liaison_inquiry_form
	 */
	public function test_liaison_inquiry_form_api_error()
	{
		$exception_message = 'error in api response';

		$api = $this->getMockBuilder(BU\Plugins\Liaison_Inquiry\Mock_Spectrum_API::class)
								->setConstructorArgs(['', ''])
								->setMethods(['get_requirements'])
								->getMock();

		$plugin = $this->getMockBuilder(BU\Plugins\Liaison_Inquiry\Plugin::class)
									 ->setConstructorArgs([$api])
									 ->setMethods(null)
									 ->getMock();

		// Spectrum_API::get_requirements throws the exception.
		$api->method('get_requirements')
				->will($this->throwException(new \Exception($exception_message)));

		// Method returns the value of Exception::getMessage
		$this->assertEquals($exception_message, $plugin->liaison_inquiry_form(null));
	}

}
