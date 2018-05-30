<?php
/**
 * Unit test file
 *
 * @package BU_Liaison_Inquiry
 */

use BU\Plugins\Liaison_Inquiry\Inquiry_Form;
use BU\Plugins\Liaison_Inquiry\Plugin;

/**
 * Cover BU\Plugins\Liaison_Inquiry\Plugin class
 *
 * @group bu-liaison-inquiry
 * @group bu-liaison-inquiry-plugin
 */
class BU_Liaison_Inquiry_Test_Plugin extends WP_UnitTestCase {

	/**
	 * Method should return form HTML
	 *
	 * @covers BU\Plugins\Liaison_Inquiry\Plugin::liaison_inquiry_form
	 */
	public function test_liaison_inquiry_form() {
		$shortcode_attributes = array(
			'some' => 'value',
		);
		$html                 = 'rendered form html';

		$form   = $this->createMock( Inquiry_Form::class );
		$plugin = $this->getMockBuilder( Plugin::class )
					   ->setConstructorArgs( [ null ] )
					   ->setMethods( [ 'get_form' ] )
					   ->getMock();

		$plugin->expects( $this->once() )
			   ->method( 'get_form' )
			   ->willReturn( $form );

		$form->expects( $this->once() )
			 ->method( 'get_html' )
			 ->with( $shortcode_attributes )
			 ->willReturn( $html );

		// Method returns the return value of Plugin::get_form_html.
		$this->assertEquals( $html, $plugin->liaison_inquiry_form( $shortcode_attributes ) );
	}

}
