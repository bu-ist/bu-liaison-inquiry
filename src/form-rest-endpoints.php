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
 * Get API credentials based on organization key
 *
 * @param string|null $org_key Optional organization key for alternate credentials.
 * @return array{api_key: string, error: ?\WP_Error} API key and potential error.
 */
function get_api_credentials( $org_key = null ) {
	$options = get_option( 'bu_liaison_inquiry_options', array() );
	$result  = array(
		'api_key' => '',
		'error'   => null,
	);

	if ( $org_key && isset( $options['alternate_credentials'][ $org_key ] ) ) {
		$creds = $options['alternate_credentials'][ $org_key ];
		if ( empty( $creds['APIKey'] ) ) {
			$result['error'] = new \WP_Error(
				'missing_api_key',
				sprintf(
					/* translators: %s: organization key */
					__( 'API Key is required for organization: %s', 'bu-liaison-inquiry' ),
					$org_key
				),
				array( 'status' => 400 )
			);
			return $result;
		}
		$result['api_key'] = $creds['APIKey'];
	} else {
		if ( empty( $options['APIKey'] ) ) {
			$result['error'] = new \WP_Error(
				'missing_api_key',
				__( 'API Key is required.', 'bu-liaison-inquiry' ),
				array( 'status' => 400 )
			);
			return $result;
		}
		$result['api_key'] = $options['APIKey'];
	}

	return $result;
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
				'permission_callback' => function() {
					return current_user_can( 'manage_options' );
				},
				'args'                => array(
					'org_key' => array(
						'required'          => false,
						'validate_callback' => function( $param ) {
							return is_string( $param );
						},
					),
				),
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
				'permission_callback' => function() {
					return current_user_can( 'manage_options' );
				},
				'args'                => array(
					'form_id' => array(
						'required'          => false,
						'validate_callback' => function( $param ) {
							return is_string( $param );
						},
					),
					'org_key' => array(
						'required'          => false,
						'validate_callback' => function( $param ) {
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
 * @param \WP_REST_Request $request The request object.
 * @return \WP_REST_Response|\WP_Error The response.
 */
function get_forms( $request ) {
	try {
		$org_key = $request->get_param( 'org_key' );
		$creds   = get_api_credentials( $org_key );

		if ( $creds['error'] ) {
			return $creds['error'];
		}

		// Initialize API with credentials.
		$api = new Spectrum_API( null, $creds['api_key'] );

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
 * @return \WP_REST_Response|\WP_Error The response.
 */
function get_form_fields( $request ) {
	try {
		// Get form ID from request.
		$form_id = $request->get_param( 'form_id' );
		if ( 'default' === $form_id ) {
			$form_id = null;
		}

		$org_key = $request->get_param( 'org_key' );
		$creds   = get_api_credentials( $org_key );

		if ( $creds['error'] ) {
			return $creds['error'];
		}

		// Initialize API with credentials.
		$api = new Spectrum_API( null, $creds['api_key'] );

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
