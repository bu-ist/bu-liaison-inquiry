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
				'callback'            => __NAMESPACE__ . '\create_credential',
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
 * Create a new organization credential
 * 
 * Not tested yet
 *
 * @param \WP_REST_Request $request The request object.
 * @return \WP_REST_Response|\WP_Error
 */
function create_credential( $request ) {
	$params = $request->get_json_params();

	// Update options.
	update_option( 'bu_liaison_inquiry_options', $params );

	return rest_ensure_response( $params );
}

// Register routes.
add_action( 'rest_api_init', __NAMESPACE__ . '\register_rest_routes' );
