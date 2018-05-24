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

    const NAME = 'bu_liaison_inquiry_options';

    static function get( $setting_name ) {
        $options = get_option( self::NAME );

        if ( !$options ) {
            return '';
        }

        if ( !array_key_exists( $setting_name, $options ) ) {
            return '';
        }

        return $options[$setting_name];
    }

}