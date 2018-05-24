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

	private function add_setting( $section_name, $setting_name, $setting_title, $callback_name, $description='' ) {
		$args = array(
			'label_for' => $setting_name,
			'class' => 'bu_liaison_inquiry_row',
			'bu_liaison_inquiry_custom_data' => 'custom',
		);

		if ($description) {
			$args['description'] = $description;
		}

		add_settings_field(
			$setting_name,
			__( $setting_title, 'bu_liaison_inquiry' ),
			array( $this, $callback_name ),
			'bu_liaison_inquiry',
			$section_name,
			$args
		);
	}

	private function setting_html_input( $value, $size, $args ) {
		$description = '';
		if ( $args['description'] ) {
			$esc_description = esc_html( $args['description'], 'bu_liaison_inquiry' );
			$description = '<p class="description">' . $esc_description . '</p>';
		}

		return '<input' .
		' type="text" size="' . esc_html($size) . '" id="' . esc_attr( $args['label_for'] ) . '"' .
		' data-custom="' . esc_attr( $args['bu_liaison_inquiry_custom_data'] ) . '"' .
		' name="bu_liaison_inquiry_options[' . esc_attr( $args['label_for'] ) . ']"' .
		' value="' . esc_html( $value ) . '"' .
		'>' . $description;
	}

	/**
	 * Register the settings option and define the settings page
	 */
	function bu_liaison_inquiry_settings_init() {
		// Register a new setting for "bu_liaison_inquiry" page.
		register_setting( 'bu_liaison_inquiry', 'bu_liaison_inquiry_options' );

		// Register the API Key and Client ID section.
		add_settings_section(
			'bu_liaison_inquiry_admin_section_key',
			__( 'Enter SpectrumEMP API Key and Client ID', 'bu_liaison_inquiry' ),
			array( $this, 'bu_liaison_inquiry_admin_section_key_callback' ),
			'bu_liaison_inquiry'
		);

		$this->add_setting(
			'bu_liaison_inquiry_admin_section_key',
			'APIKey',
			'API Key',
			'apikey_callback',
			'The API Key allows access to SpectrumEMP.'
		);

		$this->add_setting(
			'bu_liaison_inquiry_admin_section_key',
			'ClientID',
			'Client ID',
			'clientid_callback',
			'The Client ID specifies the organizational account.'
		);
	}

	/**
	 * Outputs a section header for the admin page, called by add_settings_section()
	 *
	 * @param array $args Contains keys for title, id, callback.
	 */
	function bu_liaison_inquiry_admin_section_key_callback( $args ) {
		echo "<p id='" . esc_attr( $args['id'] ) . "'>" . esc_html__( 'Set the parameters for your organization to fetch the correct forms.', 'bu_liaison_inquiry' ) . '</p>';
	}

	/**
	 * Outputs the form field for the API Key setting
	 *
	 * @param array $args Contains keys for label_for, class, bu_liaison_inquiry_custom_data.
	 */
	function apikey_callback( $args ) {
		echo $this->setting_html_input( Settings::get('APIKey'), 50, $args );
	}

	/**
	 * Outputs the form field for the Client ID setting
	 *
	 * @param array $args Contains keys for label_for, class, bu_liaison_inquiry_custom_data.
	 */
	function clientid_callback( $args ) {
		echo $this->setting_html_input( Settings::get('ClientID'), 10, $args );
	}


	/**
	 * Create an admin page.
	 */
	function bu_liaison_inquiry_options_page() {

		add_options_page(
			'Liaison API Keys',
			'Liaison API Keys',
			'manage_categories',
			'bu_liaison_inquiry',
			array( $this, 'bu_liaison_inquiry_options_page_html' )
		);
	}

	/**
	 * Outputs the form on the admin page using the defined actions.
	 */
	function bu_liaison_inquiry_options_page_html() {
		// Check user capabilities.
		if ( ! current_user_can( 'manage_categories' ) ) {
			return;
		}

		// Add status messages.
		// WordPress will add the "settings-updated" $_GET parameter to the url.
		if ( isset( $_GET['settings-updated'] ) ) {
			// Add settings saved message with the class of "updated".
			add_settings_error( 'bu_liaison_inquiry_messages', 'bu_liaison_inquiry_message', __( 'Settings Saved', 'bu_liaison_inquiry' ), 'updated' );
		}

		// Show status messages.
		settings_errors( 'bu_liaison_inquiry_messages' );
		?>
		<div class="wrap">
		<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
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
		</div>
		<?php
		// If there is already a key set, use it to fetch and display a field inventory.
		$options = get_option( 'bu_liaison_inquiry_options' );
		if ( ! empty( $options['APIKey'] ) ) {
			$api = new Spectrum_API( null, $options['APIKey'] );
		?>
		<h2>Select Liaison Form</h2>

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
					jQuery('#form_' + selected).show();
				});

				// Hide inventory of every form
				jQuery('[id^=form_]').hide();
				// Show the default one
				jQuery('#form_default').show();
			});
		</script>

		<select id="select_form">
		<?php 
			foreach ($forms_list as $name => $form_id) {
				$caption = $name . ($form_id ? ': ' . $form_id : '');
				$value = $form_id ? $form_id : 'default';
				$selected = $form_id ? '' : 'selected';
		?>
			<option value="<?php echo $value ?>" <?php echo $selected ?>><?php echo $caption ?></option>
		<?php }?>
		</select>
		<?php 
			foreach ($forms_list as $name => $form_id) {
		?>
		<div id="form_<?php echo $form_id ? $form_id : 'default' ?>">
		<h2>Sample shortcode</h2>

		[liaison_inquiry_form<?php echo $form_id ? ' form_id="'.$form_id.'"' : '' ?>]


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
	}

}
