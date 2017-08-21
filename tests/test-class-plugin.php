<?php

class BU_Liaison_Inquiry_Test_Plugin extends WP_UnitTestCase {

	public function setUp() {
		$this->spectrum = $this->createMock(BU\Plugins\Liaison_Inquiry\Spectrum_API::class);
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

}
