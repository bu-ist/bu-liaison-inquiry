<?php
/**
 * Register REST API endpoints for form browsing functionality
 *
 * @package BU_Liaison_Inquiry
 */

namespace BU\Plugins\Liaison_Inquiry;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register REST routes for form browsing
 *
 * @return void
 */
function register_form_routes() {
	// Get list of available forms.
	register_rest_route(
		'bu-liaison-inquiry/v1',
		'/forms',
		array(
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => __NAMESPACE__ . '\get_forms',
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			),
		)
	);

	// Get field inventory for a specific form.
	register_rest_route(
		'bu-liaison-inquiry/v1',
		'/forms/(?P<form_id>[a-zA-Z0-9-]+)/fields',
		array(
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => __NAMESPACE__ . '\get_form_fields',
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
				'args'                => array(
					'form_id' => array(
						'required'          => false,
						'validate_callback' => function ( $param ) {
							return is_string( $param );
						},
					),
				),
			),
		)
	);
}

/**
 * Get list of available forms
 *
 * @return \WP_REST_Response|\WP_Error
 */
function get_forms() {
	try {
		// Get current settings.
		$options = get_option( 'bu_liaison_inquiry_options', array() );

		if ( empty( $options['APIKey'] ) ) {
			return new \WP_Error(
				'missing_api_key',
				__( 'API Key is required to fetch forms.', 'bu-liaison-inquiry' ),
				array( 'status' => 400 )
			);
		}

		// Initialize API with current settings.
		$api = new Spectrum_API( null, $options['APIKey'] );

		// Get forms list.
		$forms = $api->get_forms_list();

		return rest_ensure_response( $forms );

	} catch ( \Exception $e ) {
		return new \WP_Error(
			'api_error',
			$e->getMessage(),
			array( 'status' => 500 )
		);
	}
}

/**
 * Get field inventory for a specific form
 *
 * @param \WP_REST_Request $request The request object.
 * @return \WP_REST_Response|\WP_Error
 */
function get_form_fields( $request ) {
	try {
		// Get form ID from request.
		$form_id = $request->get_param( 'form_id' );
		if ( 'default' === $form_id ) {
			$form_id = null;
		}

		// Get current settings.
		$options = get_option( 'bu_liaison_inquiry_options', array() );

		if ( empty( $options['APIKey'] ) ) {
			return new \WP_Error(
				'missing_api_key',
				__( 'API Key is required to fetch form fields.', 'bu-liaison-inquiry' ),
				array( 'status' => 400 )
			);
		}

		// Initialize API with current settings.
		$api = new Spectrum_API( null, $options['APIKey'] );

		// Get form requirements.
		$fields = $api->get_requirements( $form_id );

		return rest_ensure_response( $fields );

	} catch ( \Exception $e ) {
		return new \WP_Error(
			'api_error',
			$e->getMessage(),
			array( 'status' => 500 )
		);
	}
}

// Register routes.
add_action( 'rest_api_init', __NAMESPACE__ . '\register_form_routes' );
