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
				'permission_callback' => function() {
					return current_user_can( 'manage_options' );
				},
			),
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => __NAMESPACE__ . '\create_credential',
				'permission_callback' => function() {
					return current_user_can( 'manage_options' );
				},
			),
		)
	);
}

/**
 * Get all organization credentials
 *
 * @param \WP_REST_Request $request The request object.
 * @return \WP_REST_Response|\WP_Error
 */
function get_credentials( $request ) {
	$options = get_option( 'bu_liaison_inquiry_options', array() );
	
	// Format the response.
	/*
    $credentials = array();
	if ( ! empty( $options['org_credentials'] ) ) {
		$credentials = $options['org_credentials'];
	}
    */
	return rest_ensure_response( $options );
}

/**
 * Create a new organization credential
 *
 * @param \WP_REST_Request $request The request object.
 * @return \WP_REST_Response|\WP_Error
 */
function create_credential( $request ) {
	$params = $request->get_json_params();

	// Validate required fields.
	$required = array( 'org_key', 'api_key', 'client_id' );
	foreach ( $required as $field ) {
		if ( empty( $params[ $field ] ) ) {
			return new \WP_Error(
				'missing_required_field',
				sprintf( __( 'Missing required field: %s', 'bu-liaison-inquiry' ), $field ),
				array( 'status' => 400 )
			);
		}
	}

	// Get existing options.
	$options = get_option( 'bu_liaison_inquiry_options', array() );
	if ( empty( $options['org_credentials'] ) ) {
		$options['org_credentials'] = array();
	}

	// Add new credential.
	$options['org_credentials'][ $params['org_key'] ] = array(
		'org_key'   => sanitize_text_field( $params['org_key'] ),
		'api_key'   => sanitize_text_field( $params['api_key'] ),
		'client_id' => sanitize_text_field( $params['client_id'] ),
	);

	// Update options.
	update_option( 'bu_liaison_inquiry_options', $options );

	return rest_ensure_response( $options['org_credentials'][ $params['org_key'] ] );
}

// Register routes.
add_action( 'rest_api_init', __NAMESPACE__ . '\register_rest_routes' );

