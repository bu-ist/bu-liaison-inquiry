<?php
/**
 * Collection of static method retrieving current settings
 *
 * @package BU_Liaison_Inquiry
 */

namespace BU\Plugins\Liaison_Inquiry;

/**
 * Settings class.
 *
 * Allows to easily retrieve saved settings.
 */
class Settings {

	const NAME               = 'bu_liaison_inquiry_options';
	const PAGE_TITLE_SETTING = 'page_title';

	static function get( $setting_name ) {
		$options = get_option( self::NAME );

		if ( ! $options ) {
			return '';
		}

		if ( ! array_key_exists( $setting_name, $options ) ) {
			return '';
		}

		return $options[ $setting_name ];
	}

	static function list_utm_titles() {
		return [
			'utm_source'   => __( 'Source', 'bu_liaison_inquiry' ),
			'utm_campaign' => __( 'Campaign Name', 'bu_liaison_inquiry' ),
			'utm_content'  => __( 'Content', 'bu_liaison_inquiry' ),
			'utm_medium'   => __( 'Medium', 'bu_liaison_inquiry' ),
			'utm_term'     => __( 'Term', 'bu_liaison_inquiry' ),
		];
	}

	static function list_utm_values() {
		$result = array();
		foreach ( self::UTM_SETTINGS as $index => $setting_name ) {
			$value = self::get( $setting_name );
			if ( $value ) {
				$result[ $setting_name ] = $value;
			}
		}
		return $result;
	}

	static function page_title_values( $default_value ) {
		$result                             = array();
		$result[ self::PAGE_TITLE_SETTING ] = self::get( self::PAGE_TITLE_SETTING );
		return $result;
	}
}
