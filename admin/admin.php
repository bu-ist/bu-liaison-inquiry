<?php
/**
 * @internal    never define functions inside callbacks.
 *              these functions could be run multiple times; this would result in a fatal error.
 */

/**
 * Custom option and settings
 */
function bu_liaison_inquiry_settings_init() {
	// Register a new setting for "bu_liaison_inquiry" page.
	register_setting( 'bu_liaison_inquiry', 'bu_liaison_inquiry_options' );

	// Register a new section in the "bu_liaison_inquiry" page.
	add_settings_section(
		'bu_liaison_inquiry_section_developers',
		__( 'Enter SpectrumEMP API Key and Client ID', 'bu_liaison_inquiry' ),
		'bu_liaison_inquiry_section_developers_cb',
		'bu_liaison_inquiry'
	);

	// Register a new field in the "bu_liaison_inquiry_section_developers" section, inside the "bu_liaison_inquiry" page.
	add_settings_field(
		'APIKey',
		__( 'API Key', 'bu_liaison_inquiry' ),
		'bu_liaison_inquiry_field_APIKey_cb',
		'bu_liaison_inquiry',
		'bu_liaison_inquiry_section_developers',
		array( 'label_for' => 'APIKey', 'class' => 'bu_liaison_inquiry_row', 'bu_liaison_inquiry_custom_data' => 'custom' )
	);

	// Register the ClientID field.
	add_settings_field(
		'ClientID',
		__( 'Client ID', 'bu_liaison_inquiry' ),
		'bu_liaison_inquiry_field_ClientID_cb',
		'bu_liaison_inquiry',
		'bu_liaison_inquiry_section_developers',
		array( 'label_for' => 'ClientID', 'class' => 'bu_liaison_inquiry_row', 'bu_liaison_inquiry_custom_data' => 'custom' )
	);
}

/**
 * Register our bu_liaison_inquiry_settings_init to the admin_init action hook
 */
add_action( 'admin_init', 'bu_liaison_inquiry_settings_init' );

/**
 * Custom option and settings:
 * callback functions
 */

// developers section cb

// section callbacks can accept an $args parameter, which is an array.
// $args have the following keys defined: title, id, callback.
// the values are defined at the add_settings_section() function.
function bu_liaison_inquiry_section_developers_cb( $args ){
	?>
	<p id="<?php echo esc_attr($args['id']); ?>"><?php echo esc_html__('Set the parameters for your organization to fetch the correct form.', 'bu_liaison_inquiry'); ?></p>
	<?php
}
/////// field callback
// field callbacks can accept an $args parameter, which is an array.
// $args is defined at the add_settings_field() function.
// wordpress has magic interaction with the following keys: label_for, class.
// the "label_for" key value is used for the "for" attribute of the <label>.
// the "class" key value is used for the "class" attribute of the <tr> containing the field.
// you can add custom key value pairs to be used inside your callbacks.
function bu_liaison_inquiry_field_APIKey_cb( $args ) {
	// Get the value of the setting we've registered with register_setting().
	$options = get_option( 'bu_liaison_inquiry_options' );

	// Output the field.
?>
	<input type="text" size="50" id="<?php echo esc_attr( $args['label_for'] ); ?>"
			data-custom="<?php echo esc_attr( $args['bu_liaison_inquiry_custom_data'] ); ?>"
			name="bu_liaison_inquiry_options[<?php echo esc_attr( $args['label_for'] ); ?>]"
			value="<?php echo esc_html( $options['APIKey'] ); ?>"
	>

	<p class="description">
		<?php echo esc_html( 'The API Key allows access to SpectrumEMP.', 'bu_liaison_inquiry' ); ?>
	</p>


	<?php
}

function bu_liaison_inquiry_field_ClientID_cb( $args ) {
	// Get the value of the setting we've registered with register_setting().
	$options = get_option( 'bu_liaison_inquiry_options' );

	// Output the field.
?>
	<input type="text" size="10" id="<?php echo esc_attr( $args['label_for'] ); ?>"
			data-custom="<?php echo esc_attr( $args['bu_liaison_inquiry_custom_data'] ); ?>"
			name="bu_liaison_inquiry_options[<?php echo esc_attr( $args['label_for'] ); ?>]"
			value="<?php echo esc_html( $options['ClientID'] ); ?>"
	>

	<p class="description">
		<?php echo esc_html( 'The Client ID specifies the organizational account.', 'bu_liaison_inquiry' ); ?>
	</p>


	<?php
}
/**
 * Create a submenu page.
 */
function bu_liaison_inquiry_options_page() {

	add_submenu_page(
		'tools.php',
		'Liaison API Keys',
		'Liaison API Keys',
		'manage_options',
		'bu_liaison_inquiry',
		'bu_liaison_inquiry_options_page_html'
	);
}
 
/**
 * register our bu_liaison_inquiry_options_page to the admin_menu action hook
 */
add_action( 'admin_menu', 'bu_liaison_inquiry_options_page' );

/**
 * top level menu:
 * callback functions
 */
function bu_liaison_inquiry_options_page_html() {
	// Check user capabilities.
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	// Add error/update messages.
	// check if the user have submitted the settings
	// wordpress will add the "settings-updated" $_GET parameter to the url.
	if ( isset( $_GET['settings-updated'] ) ) {
		// Add settings saved message with the class of "updated".
		add_settings_error( 'bu_liaison_inquiry_messages', 'bu_liaison_inquiry_message', __( 'Settings Saved', 'bu_liaison_inquiry' ), 'updated' );
	}

	// Show error/update messages.
	settings_errors( 'bu_liaison_inquiry_messages' );
	?>
	<div class="wrap">
		<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
		<form action="options.php" method="post">
			<?php
			// Output security fields for the registered setting "bu_liaison_inquiry".
			settings_fields( 'bu_liaison_inquiry' );
			// Output setting sections and their fields.
			// (sections are registered for "bu_liaison_inquiry", each field is registered to a specific section)
			do_settings_sections( 'bu_liaison_inquiry' );
			// Output save settings button.
			submit_button( 'Save Settings' );
	        ?>
	    </form>
	</div>
	<?php
}
