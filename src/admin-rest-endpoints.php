<?php
/**
 * Register REST API endpoints for the admin interface
 *
 * @package BU_Liaison_Inquiry
 */

namespace BU\Plugins\Liaison_Inquiry;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register REST routes for the admin interface
 *
 * @return void
 */
function register_rest_routes() {
	register_rest_route(
		'bu-liaison-inquiry/v1',
		'/credentials',
		array(
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => __NAMESPACE__ . '\get_credentials',
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			),
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => __NAMESPACE__ . '\update_credentials',
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			),
		)
	);
}

/**
 * Get all organization credentials
 *
 * @return \WP_REST_Response|\WP_Error
 */
function get_credentials() {
	$options = get_option( 'bu_liaison_inquiry_options', array() );
	return rest_ensure_response( $options );
}

/**
 * Update all credentials and settings.
 *
 * @param \WP_REST_Request $request The request object.
 * @return \WP_REST_Response|\WP_Error
 */
function update_credentials( $request ) {
	$params = $request->get_json_params();

	// Ensure we have all expected fields with defaults if not provided.
	$params = wp_parse_args(
		$params,
		array(
			'APIKey'                => '',
			'ClientID'              => '',
			'utm_source'            => '',
			'utm_campaign'          => '',
			'utm_content'           => '',
			'utm_medium'            => '',
			'utm_term'              => '',
			'page_title'            => '',
			'alternate_credentials' => array(),
		)
	);

	// Basic sanitization.
	$sanitized = array(
		'APIKey'                => sanitize_text_field( $params['APIKey'] ),
		'ClientID'              => sanitize_text_field( $params['ClientID'] ),
		'utm_source'            => sanitize_text_field( $params['utm_source'] ),
		'utm_campaign'          => sanitize_text_field( $params['utm_campaign'] ),
		'utm_content'           => sanitize_text_field( $params['utm_content'] ),
		'utm_medium'            => sanitize_text_field( $params['utm_medium'] ),
		'utm_term'              => sanitize_text_field( $params['utm_term'] ),
		'page_title'            => sanitize_text_field( $params['page_title'] ),
		'alternate_credentials' => isset( $params['alternate_credentials'] ) ? $params['alternate_credentials'] : array(),
	);

	// Update options.
	update_option( 'bu_liaison_inquiry_options', $sanitized );

	return rest_ensure_response( $sanitized );
}

// Register routes.
add_action( 'rest_api_init', __NAMESPACE__ . '\register_rest_routes' );
