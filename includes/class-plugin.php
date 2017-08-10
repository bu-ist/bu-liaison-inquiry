<?php
/**
 * Plugin Name: BU Liaison Inquiry
 * Plugin URI: http://developer.bu.edu
 * Author: Boston University IS&T (Jonathan Williams)
 * Author URI: http://developer.bu.edu
 * Description: Provide a form to send data to the Liaison SpectrumEMP API
 * Version: 0.6
 *
 * @package BULiaisonInquiry\Plugin
 */

namespace BU_Liaison_Inquiry;

/**
 * Main plugin class.
 *
 * Provides inquiry form shortcode, and form handler.
 */
class Plugin {


	// Setup dummy value for required fields that aren't part of the mini form.
	const MINI_DUMMY_VALUE = 'mini-form';

	/**
	 * Path to plugin directory.
	 *
	 * @var string
	 */
	private static $plugin_dir;

	/**
	 * An instance of API class that performs remote calls
	 *
	 * @var object
	 */
	public $api;

	/**
	 * Define form rendering and processing handlers.
	 *
	 * @param object $api An instance of API class that performs remote calls.
	 */
	public function __construct( $api ) {
		$this->api = $api;

		// Store the plugin directory.
		self::$plugin_dir = dirname( __FILE__ ) . '/..';
	}

	/**
	 * Shortcode definition that creates the Liaison inquiry form.
	 *
	 * @param  array $atts Attributes specified in the shortcode.
	 * @return string Returns full form markup to replace the shortcode.
	 */
	public function liaison_inquiry_form( $atts ) {
		try {
			$inquiry_form_data = $this->api->get_requirements();
		} catch ( Exception $e ) {
			return $e->getMessage();
		}

		$inquiry_form = $this->minify_form_definition(
			$inquiry_form_data,
			$atts
		);

		// Setup nonce for form to protect against various possible attacks.
		$nonce = wp_nonce_field(
			'liaison_inquiry',
			'liaison_inquiry_nonce',
			false,
			false
		);
		// Enqueue the validation scripts.
		wp_enqueue_script( 'jquery-ui' );
		wp_enqueue_script( 'jquery-masked' );
		wp_enqueue_script( 'jquery-pubsub' );
		wp_enqueue_script( 'iqs-validate' );
		wp_enqueue_script( 'bu-liaison-main' );
		wp_enqueue_script( 'field_rules_form_library' );
		wp_enqueue_script( 'field_rules_handler' );

		// Enqueue form specific CSS.
		wp_enqueue_style( 'liason-form-style' );
		wp_enqueue_style( 'jquery-ui-css' );

		// Include template file.
		ob_start();
		include self::$plugin_dir . '/templates/form-template.php';
		$form_html = ob_get_contents();
		ob_end_clean();

		return $form_html;
	}

	/**
	 * Takes the form definition returned by the Liaison API, strips out any unspecified fields for the mini form,
	 * and sets hidden defaults for required fields
	 *
	 * @param  array $inquiry_form Parsed JSON data from Liaison API.
	 * @param  array $atts Attributes specified in the shortcode.
	 * @return array Returns a data array of the processed form data to be passed to the template
	 */
	public function minify_form_definition( $inquiry_form, $atts ) {
		if ( $atts ) {
			// Assign any preset field ids in the shortcode attributes.
			$presets = array();
			foreach ( $atts as $att_key => $att ) {
				// Look for integer numbers, these are field ids.
				if ( intval( $att_key ) === $att_key ) {
					$presets[ $att_key ] = $att;
				}
				// There is a SOURCE value that can be set as well:
				// is this the only non-integer field label?
				if ( 'source' === $att_key ) {
					// Shortcode attributes appear to be processed as lower case,
					// while Liaison uses UPPERCASE for this field label.
					$presets['SOURCE'] = $att;
				}
			}
		}

		// Setup field ids if a restricted field set was specified in the shortcode.
		$field_ids = array();
		if ( isset( $atts['fields'] ) ) {
			// Parse fields attribute.
			$fields = explode( ',', $atts['fields'] );
			foreach ( $fields as $field ) {
				// Only use integer values.
				if ( ctype_digit( $field ) ) {
					$field_ids[] = $field;
				}
			}
		}

		// If field_ids are specified, remove any fields that aren't in the
		// specified set.
		if ( 0 < count( $field_ids ) ) {
			foreach ( $inquiry_form->sections as $section ) {
				foreach ( $section->fields as $field_key => $field ) {
					// Field by field processing.
					if ( ! in_array( $field->id, $field_ids ) ) {
						// If a field isn't listed and isn't required,
						// just remove it.
						if ( '1' != $field->required ) {
							unset( $section->fields[ $field_key ] );
						} else {
							// If a field isn't listed but is required,
							// set the hidden flag and preset the value.
							$field->hidden = true;
							if ( isset( $presets[ $field->id ] ) ) {
								$field->hidden_value = $presets[ $field->id ];
								// Now remove it from the $presets array
								// so that we don't double process it.
								unset( $presets[ $field->id ] );
							} else {
								$field->hidden_value = self::MINI_DUMMY_VALUE;
							}
						}
					}
				}
			}
		}
		// Any other preset values that weren't covered by the minify function
		// should be inserted as hidden values.
		if ( is_array( $presets ) ) {
			foreach ( $presets as $preset_key => $preset_val ) {
				// Prepend any preset fields to the $section->fields array as
				// hidden inputs. First check if it is already a visible field.
				// If so, throw an error in to the error logs and drop it.
				$field_exists = false;
				foreach ( $inquiry_form->sections as $section ) {
					if ( array_key_exists( $preset_key, $section->fields ) ) {
						$field_exists = true;
					}
				}

				if ( $field_exists ) {
					// Don't want to preset a hidden value for an existing field.
					// Who knows what might happen?
					error_log(
						sprintf(
							'Field key %s was found in a shortcode, ' .
							'but it already exists in the liason form. ' .
							'Dropping preset value.',
							$preset_key
						)
					);
				} else {
					$hidden_field = new stdClass();
					$hidden_field->hidden = true;
					$hidden_field->id = $preset_key;
					$hidden_field->hidden_value = $preset_val;
					array_unshift(
						$inquiry_form->sections[0]->fields,
						$hidden_field
					);
				}
			}
		}

		return $inquiry_form;
	}

	/**
	 * Handle incoming form data
	 *
	 * @return string Returns the result of the form submission as a JSON formatted array for the javascript validation
	 */
	public function handle_liaison_inquiry() {
		// Use wp nonce to verify the form was submitted correctly.
		if ( ! empty( $_POST['liaison_inquiry_nonce'] ) ) {
			$verify_nonce_status = wp_verify_nonce(
				sanitize_key( $_POST['liaison_inquiry_nonce'] ),
				'liaison_inquiry'
			);
		} else {
			$verify_nonce_status = false;
		}

		if ( ! $verify_nonce_status ) {
			$return = array();
			$return['status'] = 0;
			$return['response'] = 'There was a problem with the form nonce, please reload the page';
			wp_send_json( $return );
			return;
		}
		// Clear the verified nonce from $_POST so that it doesn't get passed on.
		unset( $_POST['liaison_inquiry_nonce'] );

		$post_vars = $this->prepare_form_post( $_POST );

		// Make the external API call.
		$return = $this->api->post_form( $post_vars );

		// Return a JSON encoded reply for the validation javascript.
		wp_send_json( $return );
	}

	/**
	 * Sanitize and format post data for submission
	 *
	 * @param  array $post_parameters $_POST values as submitted.
	 *
	 * @return array Returns an array of sanitized and prepared post values.
	 */
	public function prepare_form_post( $post_parameters ) {
		// Phone number fields are given special formatting,
		// phone field ids are passed as a hidden field in the form.
		if ( ! empty( $post_parameters['phone_fields'] ) ) {
			$phone_fields = sanitize_text_field( wp_unslash( $post_parameters['phone_fields'] ) );
			$phone_fields = explode( ',', $phone_fields );
			unset( $post_parameters['phone_fields'] );
		} else {
			$phone_fields = array();
		}

		// Process all of the existing values into a new array.
		$post_vars = array();
		foreach ( $post_parameters as $key => $value ) {

			$value = wp_unslash( $value );

			if ( in_array( $key, $phone_fields ) ) {
				// If it is a phone field, apply special formatting.
				// Strip out everything except numerals.
				$value = preg_replace( '/[^0-9]/', '', $value );

				// Append +1 for US, but + needs to be %2B for posting.
				$value = '%2B1' . $value;
			} elseif ( stripos( $key, '-text-opt-in' ) !== false ) {
				// If this checkbox field is set then it was checked.
				$value = '1';
			} else {
				// Apply basic field sanitization.
				$value = sanitize_text_field( $value );
			}

			$post_vars[ $key ] = $value;
		}
		return $post_vars;
	}
}
