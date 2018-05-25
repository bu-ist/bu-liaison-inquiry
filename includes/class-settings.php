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
    const PAGE_TITLE_SETTING = 'page_title';
    const UTM_SETTINGS = array('utm_source', 'utm_campaign', 'utm_content', 'utm_medium', 'utm_term');
    const UTM_SETTINGS_TITLES = array('Source', 'Campaign Name', 'Content', 'Medium', 'Term');

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

    static function list_utm_titles() {
        $result = array();
        foreach (self::UTM_SETTINGS as $index => $setting_name) {
            $result[$setting_name] = SELF::UTM_SETTINGS_TITLES[$index];
        }
        return $result;
    }

    static function list_utm_values() {
        $result = array();
        foreach (self::UTM_SETTINGS as $index => $setting_name) {
            $value = self::get( $setting_name );
            if ( $value ) {
                $result[$setting_name] = $value;
            }
        }
        return $result;
    }

    static function page_title_value() {
        return self::get( self::PAGE_TITLE_SETTING );
    }
}