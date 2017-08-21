<?php

class BU_Liaison_Inquiry_Test_Plugin extends WP_UnitTestCase {

	public function setUp() {
		$this->spectrum = $this->createMock(BU\Plugins\Liaison_Inquiry\Spectrum_API::class);
	}

	/**
	 * @covers BU\Plugins\Liaison_Inquiry\Plugin::liaison_inquiry_form
	 */
	public function test_liaison_inquiry_form() {
		$shortcode_attributes = null;
		$requirements = 'requirements coming from api';
		$form_definition = 'form definition';
		$form_html = 'html response';

		$plugin = $this->getMockBuilder(BU\Plugins\Liaison_Inquiry\Plugin::class)
									 ->setConstructorArgs([$this->spectrum])
									 ->setMethods(['minify_form_definition', 'get_form_html'])
									 ->getMock();

		// Spectrum_API::get_requirements is called
		$this->spectrum->expects($this->once())
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

}
