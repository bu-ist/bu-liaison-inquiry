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
	const UTM_SETTINGS       = array( 'utm_source', 'utm_campaign', 'utm_content', 'utm_medium', 'utm_term' );

	/**
	 * Return an associative array of UTM parameter titles.
	 *
	 * @return array Format: [(string)'name' => (string)'title']
	 */
	public static function list_utm_titles() {
		$titles = [
			__( 'Source', 'bu_liaison_inquiry' ),
			__( 'Campaign Name', 'bu_liaison_inquiry' ),
			__( 'Content', 'bu_liaison_inquiry' ),
			__( 'Medium', 'bu_liaison_inquiry' ),
			__( 'Term', 'bu_liaison_inquiry' ),
		];
		$result = array();
		foreach ( self::UTM_SETTINGS as $index => $setting_name ) {
			$result[ $setting_name ] = $titles[ $index ];
		}
		return $result;
	}

	/**
	 * Get the option value for the given setting name or empty string
	 * if the option with this name doesn't exist.
	 *
	 * @param  string $setting_name Setting name.
	 * @return string Option value
	 */
	public static function get( $setting_name ) {
		$options = get_option( self::NAME );

		if ( ! $options ) {
			return '';
		}

		if ( ! array_key_exists( $setting_name, $options ) ) {
			return '';
		}

		return $options[ $setting_name ];
	}

	/**
	 * Return an associative array of UTM parameter field IDs.
	 *
	 * @return array Format: [(string)'name' => (string)'field ID']
	 */
	public static function list_utm_values() {
		$result = array();
		foreach ( self::UTM_SETTINGS as $index => $setting_name ) {
			$value = self::get( $setting_name );
			if ( $value ) {
				$result[ $setting_name ] = $value;
			}
		}
		return $result;
	}

	/**
	 * Return UTM page title field ID as associative array.
	 *
	 * @return array Format: [(string)'page title setting name' => (string)'field ID']
	 */
	public static function page_title_values() {
		$result                             = array();
		$result[ self::PAGE_TITLE_SETTING ] = self::get( self::PAGE_TITLE_SETTING );
		return $result;
	}

	/**
	 * Get the array of alternate credentials.
	 *
	 * @return array Associative array of alternates: [org_key => [ 'ClientID' => ..., 'APIKey' => ... ]]
	 */
	public static function get_alternate_credentials() {
		$options = get_option( self::NAME );
		if ( ! $options || ! isset( $options['alternate_credentials'] ) || ! is_array( $options['alternate_credentials'] ) ) {
			return array();
		}
		return $options['alternate_credentials'];
	}

	/**
	 * Set the array of alternate credentials.
	 *
	 * @param array $alternates Associative array of alternates.
	 * @return void
	 */
	public static function set_alternate_credentials( $alternates ) {
		$options = get_option( self::NAME );
		if ( ! is_array( $options ) ) {
			$options = array();
		}
		$options['alternate_credentials'] = $alternates;
		update_option( self::NAME, $options );
	}

	/**
	 * Get credentials for a given org key, or default if not found or not specified.
	 *
	 * @param string|null $org_key The org key to look up. If null or not found, returns default credentials.
	 * @return array [ 'ClientID' => ..., 'APIKey' => ... ]
	 */
	public static function get_credentials_for_org( $org_key = null ) {
		if ( $org_key ) {
			$alternates = self::get_alternate_credentials();
			if ( isset( $alternates[ $org_key ] ) && isset( $alternates[ $org_key ]['ClientID'] ) && isset( $alternates[ $org_key ]['APIKey'] ) ) {
				return array(
					'ClientID' => $alternates[ $org_key ]['ClientID'],
					'APIKey'   => $alternates[ $org_key ]['APIKey'],
				);
			}
		}
		// Fallback to default.
		return array(
			'ClientID' => self::get( 'ClientID' ),
			'APIKey'   => self::get( 'APIKey' ),
		);
	}

	/**
	 * Add or update a single alternate credential set.
	 *
	 * @param string $org_key   The unique key identifying the organization.
	 * @param string $client_id The Client ID for the organization.
	 * @param string $api_key   The API Key for the organization.
	 * @return void
	 */
	public static function add_alternate_credential( $org_key, $client_id, $api_key ) {
		$alternates             = self::get_alternate_credentials();
		$alternates[ $org_key ] = array(
			'ClientID' => $client_id,
			'APIKey'   => $api_key,
		);
		self::set_alternate_credentials( $alternates );
	}

	/**
	 * Remove an alternate credential set by org key.
	 *
	 * @param string $org_key The unique key identifying the organization.
	 * @return void
	 */
	public static function remove_alternate_credential( $org_key ) {
		$alternates = self::get_alternate_credentials();
		if ( isset( $alternates[ $org_key ] ) ) {
			unset( $alternates[ $org_key ] );
			self::set_alternate_credentials( $alternates );
		}
	}
}
