<?php
/**
 * Class containing methods that add the settings page into the admin panel
 *
 * @package BU_Liaison_Inquiry
 */

namespace BU\Plugins\Liaison_Inquiry;

/**
 *  Admin class.
 *
 * Adds a settings page to the admin panel.
 */
class Admin {

	/**
	 * Wrapper for WP `add_settings_section`.
	 *
	 * @param string $id String for use in the "id" attribute for tags,
	 *                   as in `add_settings_section`.
	 * @param string $title Title of the section, as in `add_settings_section`.
	 * @param string $description Description of the section.
	 */
	private function add_section( $id, $title, $description ) {
		add_settings_section(
			$id,
			$title,
			function ( $args ) {
				$escaped_description = esc_html( $description );
				echo wp_kses( "<p id='" . esc_attr( $args['id'] ) . "'>" . $escaped_description . '</p>', [ 'p' => [ 'id' => [] ] ] );
			},
			'bu_liaison_inquiry'
		);
	}

	/**
	 * Wrapper for WP `add_settings_field`.
	 *
	 * @param string   $section_name Slug-name of the section, as in `add_settings_field`.
	 * @param string   $setting_name Slug-name of the field, as in `add_settings_field`.
	 * @param string   $setting_title Formatted title of the field, as in `add_settings_field`.
	 * @param callable $callback Function that fills the field, as in `add_settings_field`.
	 * @param string   $description (Optional) Text description of the field.
	 */
	private function add_setting( $section_name, $setting_name, $setting_title, $callback, $description = '' ) {
		$args = array(
			'label_for'                      => $setting_name,
			'class'                          => 'bu_liaison_inquiry_row',
			'bu_liaison_inquiry_custom_data' => 'custom',
		);

		if ( $description ) {
			$args['description'] = $description;
		}

		add_settings_field(
			$setting_name,
			$setting_title,
			$callback,
			'bu_liaison_inquiry',
			$section_name,
			$args
		);
	}

	/**
	 * Generate HTML for the setting input with an optional description
	 *
	 * @param  string  $value Input's value or an empty string.
	 * @param  integer $size  Input's size.
	 * @param  array   $args  List of extra settings.
	 * @return void
	 */
	private function echo_setting_html( $value, $size, $args ) {
		$description = '';
		if ( $args['description'] ) {
			$esc_description = esc_html( $args['description'], 'bu_liaison_inquiry' );
			$description     = '<p class="description">' . $esc_description . '</p>';
		}

		echo wp_kses(
			'<input' .
			' type="text" size="' . esc_html( $size ) . '" id="' . esc_attr( $args['label_for'] ) . '"' .
			' data-custom="' . esc_attr( $args['bu_liaison_inquiry_custom_data'] ) . '"' .
			' name="bu_liaison_inquiry_options[' . esc_attr( $args['label_for'] ) . ']"' .
			' value="' . esc_html( $value ) . '"' .
			'>' . $description,
			[
				'p'     => [ 'class' => [] ],
				'input' => [
					'type'        => [],
					'id'          => [],
					'size'        => [],
					'data-custom' => [],
					'name'        => [],
					'value'       => [],
				],
			]
		);
	}

	/**
	 * Register the settings option and define the settings page
	 */
	public function bu_liaison_inquiry_settings_init() {
		// Register a new setting for "bu_liaison_inquiry" page.
		register_setting( 'bu_liaison_inquiry', 'bu_liaison_inquiry_options' );

		// Register the API Key and Client ID section.
		$this->add_section(
			'bu_liaison_inquiry_admin_section_key',
			__( 'Enter SpectrumEMP API Key and Client ID', 'bu_liaison_inquiry' ),
			__( 'Set the parameters for your organization to fetch the correct forms.', 'bu_liaison_inquiry' )
		);

		$this->add_setting(
			'bu_liaison_inquiry_admin_section_key',
			'APIKey',
			__( 'API Key', 'bu_liaison_inquiry' ),
			function ( $args ) {
				$this->echo_setting_html( Settings::get( 'APIKey' ), 50, $args );
			},
			'The API Key allows access to SpectrumEMP.'
		);

		$this->add_setting(
			'bu_liaison_inquiry_admin_section_key',
			'ClientID',
			__( 'Client ID', 'bu_liaison_inquiry' ),
			function ( $args ) {
				$this->echo_setting_html( Settings::get( 'ClientID' ), 10, $args );
			},
			'The Client ID specifies the organizational account.'
		);

		// Register the UTM Parameters section.
		$this->add_section(
			'bu_liaison_inquiry_admin_section_utm',
			__( 'UTM Parameters', 'bu_liaison_inquiry' ),
			__( 'Specify Spectrum EMP field IDs associated with UTM parameters.', 'bu_liaison_inquiry' )
		);

		foreach ( Settings::list_utm_titles() as $name => $title ) {
			$this->add_setting(
				'bu_liaison_inquiry_admin_section_utm',
				$name,
				$title,
				function ( $args ) use ( $name ) {
					$this->echo_setting_html( Settings::get( $name ), 10, $args );
				}
			);
		}

		$this->add_setting(
			'bu_liaison_inquiry_admin_section_utm',
			Settings::PAGE_TITLE_SETTING,
			__( 'Page Title', 'bu_liaison_inquiry' ),
			function ( $args ) {
				$this->echo_setting_html( Settings::get( Settings::PAGE_TITLE_SETTING ), 10, $args );
			}
		);
	}

	/**
	 * Create an admin page.
	 */
	public function bu_liaison_inquiry_options_page() {
		add_options_page(
			'Liaison Forms',
			'Liaison Forms',
			'manage_categories',
			'bu_liaison_inquiry',
			array( $this, 'bu_liaison_inquiry_options_page_html' )
		);
	}

	/**
	 * Check if the current user can edit settings.
	 *
	 * @return boolean
	 */
	public function check_edit_capability() {
		$required_capabilty = apply_filters(
			'option_page_capability_bu_liaison_inquiry', 'manage_options'
		);
		return current_user_can( $required_capabilty );
	}

	/**
	 * Outputs the form on the admin page using the defined actions.
	 */
	public function bu_liaison_inquiry_options_page_html() {
		// Check user capabilities.
		if ( ! current_user_can( 'manage_categories' ) ) {
			return;
		}

		// Handle add alternate credential action.
		if ( isset( $_POST['new_org_key'], $_POST['new_client_id'], $_POST['new_api_key'] ) && check_admin_referer( 'add_alt_cred' ) ) {
			$org_key   = sanitize_text_field( wp_unslash( $_POST['new_org_key'] ) );
			$client_id = sanitize_text_field( wp_unslash( $_POST['new_client_id'] ) );
			$api_key   = sanitize_text_field( wp_unslash( $_POST['new_api_key'] ) );
			if ( $org_key && $client_id && $api_key ) {
				$alternates = Settings::get_alternate_credentials();
				if ( ! isset( $alternates[ $org_key ] ) ) {
					Settings::add_alternate_credential( $org_key, $client_id, $api_key );
					add_settings_error( 'bu_liaison_inquiry_messages', 'alt_cred_added', __( 'Alternate credential added.', 'bu_liaison_inquiry' ), 'updated' );
				} else {
					add_settings_error( 'bu_liaison_inquiry_messages', 'alt_cred_exists', __( 'Org Key already exists.', 'bu_liaison_inquiry' ), 'error' );
				}
			}
		}

		// Handle delete alternate credential action.
		if ( isset( $_POST['delete_alt_cred'] ) ) {
			$raw_org_key = sanitize_text_field( wp_unslash( $_POST['delete_alt_cred'] ) );
			// Verify nonce for security.
			if ( check_admin_referer( 'delete_alt_cred_' . $raw_org_key ) ) {
				// Remove the alternate credential.
				Settings::remove_alternate_credential( $raw_org_key );
				// Add a success message.
				add_settings_error( 'bu_liaison_inquiry_messages', 'alt_cred_deleted', __( 'Alternate credential deleted.', 'bu_liaison_inquiry' ), 'updated' );
			}
		}

		// Show status messages.
		settings_errors( 'bu_liaison_inquiry_messages' );
			?>
			<div class="wrap">
				<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
				<?php if ( $this->check_edit_capability() ) : ?>
					<form action="options.php" method="post">
						<?php
						// Output security fields for the registered setting.
						settings_fields( 'bu_liaison_inquiry' );
						// Output setting sections and their fields.
						// (sections are registered for "bu_liaison_inquiry", each field is registered to a specific section).
						do_settings_sections( 'bu_liaison_inquiry' );
						// Output save settings button.
						submit_button( 'Save Settings' );
						?>
					</form>
				<hr>
				<?php else : ?>
					<div class="notice notice-info">
							<p><?php echo esc_html( 'To change settings, please contact Administrator.', 'bu_liaison_inquiry' ); ?></p>
					</div>
				<?php endif; ?>
			</div>
		<?php
		// If there is already a key set, use it to fetch and display a field inventory.
		$options = get_option( 'bu_liaison_inquiry_options' );
		if ( ! empty( $options['APIKey'] ) ) {
			$api = new Spectrum_API( null, $options['APIKey'] );
			?>
		<h2>Select Liaison Form</h2>
		<p>Select a form below to see the list of field IDs that it contains. </p>
			<?php
			try {
				$forms_list = $api->get_forms_list();
			} catch ( \Exception $e ) {
				echo esc_html( $e->getMessage() );
				return;
			}
			?>

		<script>
			jQuery(document).ready(function(){
				jQuery('#select_form').change(function () {
					// Hide inventory of every form
					jQuery('[id^=form_]').hide();

					var selected = jQuery('#select_form').val();

					var searchParams = new URLSearchParams(window.location.search);
					searchParams.set( 'form_id', selected );
					window.location.search = searchParams.toString();
				});
			});
		</script>

		<select id="select_form">
			<option value="">Select</option>
			<?php
			foreach ( $forms_list as $name => $form_id ) {
				$caption  = $name . ( $form_id ? ': ' . $form_id : '' );
				$value    = $form_id ? $form_id : 'default';
				$selected = $value == ( $_GET['form_id'] ?? '' ) ? 'selected="selected"' : '';
				?>
		<option value="<?php echo esc_attr( $value ); ?>" <?php echo $selected; ?>><?php echo esc_html( $caption ); ?></option>
		<?php } ?>
		</select>
			<?php
			foreach ( $forms_list as $name => $form_id ) {
				$value    = $form_id ? $form_id : 'default';
				if ( $value != $_GET['form_id'] ?? '' ) {
					continue;
				}
				?>
		<div id="form_<?php echo $form_id ? esc_attr( $form_id ) : 'default'; ?>">
		<h2>Sample shortcode</h2>

		[liaison_inquiry_form<?php echo $form_id ? ' form_id="' . esc_attr( $form_id ) . '"' : ''; ?>]


		<h2>Field inventory</h2>
				<?php
				try {
					$inquiry_form = $api->get_requirements( $form_id );
				} catch ( \Exception $e ) {
					echo esc_html( $e->getMessage() );
					return;
				}

				foreach ( $inquiry_form->sections as $section ) {
					foreach ( $section->fields as $field_key => $field ) {
						echo '<p>' . esc_html( $field->displayName ) . ' = ' . esc_html( $field->id ) . '</p>';
					}
				}
				?>
		</div>
				<?php
			}
		}
		?>
		<hr>
		<h2><?php esc_html_e( 'Alternate Credentials (for additional organizations)', 'bu_liaison_inquiry' ); ?></h2>
		<p><?php esc_html_e( 'Use the Org Key in your shortcode:', 'bu_liaison_inquiry' ); ?> <code>[liaison_inquiry_form org="org_key"]</code></p>
		<table class="widefat" style="max-width:600px;">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Org Key', 'bu_liaison_inquiry' ); ?></th>
					<th><?php esc_html_e( 'API Key', 'bu_liaison_inquiry' ); ?></th>
					<th><?php esc_html_e( 'Client ID', 'bu_liaison_inquiry' ); ?></th>
					<th><?php esc_html_e( 'Actions', 'bu_liaison_inquiry' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( Settings::get_alternate_credentials() as $org_key => $creds ) : ?>
					<tr>
						<td><?php echo esc_html( $org_key ); ?></td>
						<td><?php echo esc_html( substr( $creds['APIKey'], 0, 10 ) ) . '...'; ?></td>
						<td><?php echo esc_html( $creds['ClientID'] ); ?></td>
						<td>
							<form method="post" style="display:inline;">
								<?php wp_nonce_field( 'delete_alt_cred_' . $org_key ); ?>
								<input type="hidden" name="delete_alt_cred" value="<?php echo esc_attr( $org_key ); ?>">
								<button type="submit" class="button button-small" onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to delete this alternate credential?', 'bu_liaison_inquiry' ); ?>');">Delete</button>
							</form>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<h3><?php esc_html_e( 'Add New Alternate Credential', 'bu_liaison_inquiry' ); ?></h3>
		<form method="post" style="max-width:600px;">
			<?php wp_nonce_field( 'add_alt_cred' ); ?>
			<input name="new_org_key" placeholder="Org Key" required style="width:100px;">
			<input name="new_api_key" placeholder="API Key" required style="width:300px;">
			<input name="new_client_id" placeholder="Client ID" required style="width:60px;">
			<button type="submit" class="button button-primary"><?php esc_html_e( 'Add Alternate', 'bu_liaison_inquiry' ); ?></button>
		</form>
		<?php
	}

}
