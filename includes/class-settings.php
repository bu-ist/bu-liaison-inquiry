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
        // TODO: check if setting exists before retrieving it
        return $options[$setting_name];
    }

}