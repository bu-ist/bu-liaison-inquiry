<?php
/**
 * Class building inquiry form
 *
 * @package BU_Liaison_Inquiry
 */

namespace BU\Plugins\Liaison_Inquiry;

/**
 * Inquiry Form builder
 *
 * Renders form HTML and sends submitted forms to API endpoints.
 */
class Inquiry_Form {

	// Setup dummy value for required fields that aren't part of the mini form.
	const MINI_DUMMY_VALUE = 'mini-form';

	/**
	 * Name of the nonce field included in the form
	 *
	 * @var string
	 */
	public static $nonce_field_name = 'liaison_inquiry_nonce';

	/**
	 * Nonce name
	 *
	 * @var string
	 */
	public static $nonce_name = 'liaison_inquiry';

	/**
	 * Path to plugin directory
	 *
	 * @var string
	 */
	private static $plugin_dir;


	/**
	 * Setup class variables
	 *
	 * @param Object $api API class instance.
	 */
	public function __construct( $api ) {
		$this->api = $api;

		// Store the plugin directory.
		self::$plugin_dir = dirname( __FILE__ ) . '/..';
	}

	/**
	 * Retrievs form definition from the API and builds the form
	 *
	 * @param  array $attrs Attributes specified in the shortcode.
	 * @return string Form HTML
	 */
	public function get_html( $attrs ) {
		if ( isset( $attrs['form_id'] ) ) {
			$form_id = $attrs['form_id'];
		} else {
			$form_id = null;
		}
		unset( $attrs['form_id'] );

		try {
			$inquiry_form = $this->api->get_requirements( $form_id );
		} catch ( \Exception $e ) {
			return $e->getMessage();
		}

		if ( count( $attrs ) ) {
			$inquiry_form = $this->minify_form_definition( $inquiry_form, $attrs );
		}

		$inquiry_form = $this->autofill_parameters(
			$inquiry_form, Settings::list_utm_values(), function ( $parameter_name ) {
				if ( isset( $_GET[ $parameter_name ] ) ) {
					return $_GET[ $parameter_name ];
				}
				return '';
			}
		);

		$inquiry_form = $this->autofill_parameters(
			$inquiry_form, Settings::page_title_values(), function ( $parameter_name ) {
				return get_the_title();
			}
		);

		return $this->render_template( $inquiry_form, $form_id );
	}

	public function autofill_parameters( $inquiry_form, $auto_list, $callback ) {
		foreach ( $inquiry_form->sections as $section ) {
			foreach ( $section->fields as $field_key => $field ) {
				if ( in_array( $field->id, $auto_list ) ) {
					$field->hidden       = true;
					$field->hidden_value = $callback( array_search( $field->id, $auto_list ) );
				}
			}
		}
		return $inquiry_form;
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
		if ( isset( $presets ) && is_array( $presets ) ) {
			foreach ( $presets as $preset_key => $preset_val ) {
				// Prepend any preset fields to the $section->fields array as
				// hidden inputs. First check if it is already a visible field.
				// If so, throw an error in to the error logs and drop it.
				$field_exists = false;
				foreach ( $inquiry_form->sections as $section ) {
					foreach ( $section->fields as $field ) {
						if ( $field->id == $preset_key ) {
							$field_exists = true;
						}
					}
				}

				if ( $field_exists ) {
					// Don't want to preset a hidden value for an existing field.
					// Who knows what might happen?
					$warning = sprintf(
						'Field key %s was found in a shortcode, ' .
						'but it already exists in the liason form. ' .
						'Dropping preset value.',
						$preset_key
					);
					// @codeCoverageIgnoreStart
					if ( defined( 'BU_CMS' ) && BU_CMS ) {
						error_log( $warning );
					}// @codeCoverageIgnoreEnd
				} else {
					$hidden_field               = new \stdClass();
					$hidden_field->hidden       = true;
					$hidden_field->id           = $preset_key;
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
	 * Render the form via a template
	 *
	 * @param  stdClass    $inquiry_form Object representing the form.
	 * @param  string|null $form_id Form's ID, null for default one.
	 * @return string Returns full form markup
	 */
	public function render_template( $inquiry_form, $form_id ) {
		// Setup nonce for form to protect against various possible attacks.
		$nonce = wp_nonce_field(
			self::$nonce_name,
			self::$nonce_field_name,
			false,
			false
		);

		// Include template file.
		ob_start();
		include self::$plugin_dir . '/templates/form-template.php';
		$form_html = ob_get_contents();
		ob_end_clean();

		return $form_html;
	}

	/**
	 * Handle incoming form data
	 *
	 * @return string Returns the result of the form submission as a JSON formatted array for the javascript validation
	 */
	public function handle_liaison_inquiry() {
		if ( ! $this->verify_nonce() ) {
			$return             = array();
			$return['status']   = 0;
			$return['response'] = 'There was a problem with the form nonce, please reload the page';
			return $return;
		}

		$post_vars = $this->prepare_form_post( $_POST );

		// Make the external API call.
		$return = $this->api->post_form( $post_vars );

		// Return a JSON encoded reply for the validation javascript.
		return $return;
	}

	/**
	 * Use wp nonce to verify the form was submitted correctly
	 *
	 * Must be used only once during processing a request because it removes nonce
	 * field from $_POST parameters
	 *
	 * @return boolean Whether or not nonce verification was successful
	 */
	public function verify_nonce() {
		if ( ! empty( $_POST[ self::$nonce_field_name ] ) ) {
			$verify_nonce_status = wp_verify_nonce(
				sanitize_key( $_POST[ self::$nonce_field_name ] ),
				self::$nonce_name
			);
		} else {
			$verify_nonce_status = false;
		}

		// Clear the verified nonce from $_POST so that it doesn't get passed on.
		unset( $_POST[ self::$nonce_field_name ] );

		return (bool) $verify_nonce_status;
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

				if ( $value ) {
					// Append +1 for US, but + needs to be %2B for posting.
					$value = '%2B1' . $value;
				}
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
