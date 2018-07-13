<?php
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
?>

<script type='text/javascript'>
	var SITE = {};
	SITE.data = {
		client_rules_url: "<?php echo $this->api::CLIENT_RULES_URL; ?>",
		field_options_url: "<?php echo $this->api::FIELD_OPTIONS_URL; ?>",
		client_id: "<?php echo $this->api->client_id; ?>"
	};
</script>

<form id="form_example" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">

<?php
	// Include form ID if available.
	echo $form_id ? '<input type="hidden" name="formID" value="' . $form_id .'">' : '';

	// Initialize modal and phone fields.
	$modals = array();
	$phone_fields = array();
?>
<?php foreach ( $inquiry_form->sections as $section_index => $section ) : ?>

	<div class="section">
		<h3 class="page-header"><?php echo $section->name; ?><small><?php echo $section->description; ?></small></h3>

			<?php foreach ( $section->fields as $field_index => $field ) : ?>

			<?php
			// Setup address field labels.
			$label = $field->displayName;

			if ( 6 == $field->id ) {
				// Address Line 1.
				$label = 'Address';
			} else if ( 7 == $field->id ) {
				// Address Line 2.
				$label = '';
			}
			// End address field labels.
			?>

			<?php
			// Mini form needs to pass dummy values to otherwise required fields; insert them here as hidden inputs.
			if ( isset( $field->hidden ) && $field->hidden ) : ?>
				<input type="hidden" name="<?php echo $field->id;?>" value="<?php echo $field->hidden_value;?>">
			<?php
			// Begin handler for two types of html elements: input-text or select.
			elseif ( 'input-text' == $field->htmlElement ) :
				// Begin input text handler.
				$class = '';

				if ( stripos( $field->description, 'phone number' ) !== false ) {
					$class = ' iqs-form-phone-number';
					$phone_fields[] = $field->id;
				} elseif ( false !== stripos( $field->description, 'valid email' ) ) {
					$class = ' iqs-form-email';
				} else {
					$class = ' iqs-form-text';
				}
			?>

				<div class="row">
					<div class="form-group">
						<label for="<?php echo $field->id; ?>" class="col-sm-4 control-label"><?php echo $label . ( ( $field->required ) ? ' <span class="asterisk">*</span>' : '' ); ?></label>
						<div class="col-sm-6 col-md-5">
							<input type="text"
								name="<?php echo $field->id; ?>"
								id="<?php echo $field->id; ?>"
								class="form-control<?php echo ( ( $field->required ) ? ' required' : '' ) . $class; ?>" placeholder="<?php echo $field->displayName; ?>" />

				<?php
				// Begin phone field specific handler.
				if ( ' iqs-form-phone-number' == $class && isset( $section->fields[ $field_index + 1 ] )
					&& ( $section->fields[ $field_index ]->order + 0.1 ) == $section->fields[ $field_index + 1 ]->order ) :
						// Inject opt-in message and modal for phone number field.
						$element_id = $section->fields[ $field_index + 1 ]->id;
						$label_text = trim( $section->fields[ $field_index + 1 ]->displayName );
						$opt_in_text = '<a href="#text-message-opt-in-modal" id="opt-in-trigger">opt-in policy</a>';
						$label_text = str_ireplace( 'opt-in policy', $opt_in_text, $label_text );

						$modals[] = '
							<div id="text-message-opt-in-modal" title="Text Message Opt-in Policy" class="modal">
								<div class="modal-body">
											 ' . $section->fields[ $field_index + 1 ]->helpText . '
								</div>
							</div>
						';
				?>

						<input type="checkbox" name="<?php echo $element_id; ?>" id="<?php echo $element_id; ?>">
						<label id="label-<?php echo $element_id; ?>" for="<?php echo $element_id; ?>"><?php echo $label_text; ?></label>

				<?php endif;
				// End phone field specific handler.
				?>


				<?php if ( '' !== $field->helpText ) :?>
					<p class="help-block"><?php echo $field->helpText; ?></p>
				<?php endif; ?>


						</div>
					</div><!-- end class="form-group" -->
				</div><!-- end class="row" -->

			<?php
			// End input text handler.
			?>
			<?php elseif ( 'select' == $field->htmlElement ) :
			// Begin select handler.
			$class = ' iqs-form-single-select';
			?>

				<div class="row">
					<div class="form-group">
						<label for="<?php echo $field->id; ?>" class="col-sm-4 control-label"><?php echo $label . (($field->required) ? ' <span class="asterisk">*</span>' : ''); ?></label>
						<div class="col-sm-6 col-md-5">
							<select
								name="<?php echo $field->id; ?>"
								id="<?php echo $field->id; ?>"
								class="input-sm form-control<?php echo (($field->required) ? ' required' : '') . $class; ?>">
								<option value=""></option>

			<?php // Add extra option to the state or region field.
			if ( 9 == $field->id ) :  ?>
				<option value="Outside US & Canada">Outside US & Canada</option>
			<?php endif; ?>

			<?php foreach ( $field->options as $option ) : ?>
				<?php if ( isset( $option->options ) ) : ?>
					<optgroup label="<?php echo $option->label; ?>">

					<?php foreach ( $option->options as $sub_option ) : ?>
						<option value="<?php echo $sub_option->id; ?>"><?php echo $sub_option->value; ?></option>
					<?php endforeach; ?>

					</optgroup>

				<?php else : ?>
					<option value="<?php echo $option->id; ?>"><?php echo $option->value; ?></option>
				<?php endif; ?>
			<?php endforeach; ?>

							</select>
							<?php echo (($field->helpText !== '') ? '<p class="help-block">' . $field->helpText . '</p>' : ''); ?>
						</div>
					</div><!-- end class="form-group" -->
				</div><!-- end class="row" -->

		<?php endif; // End select field handler.?>
	<?php endforeach; // End field handler.?>


		</div><!-- end class="section" -->

<?php endforeach; // End section handler.?>


	<div class="clear"></div>

	<br />

	<div class="alert alert-success form-submit-success"></div>

	<div class="alert alert-danger form-submit-danger"></div>

	<div class="form-actions">
		<button type="submit" class="btn btn-primary">Go <i class="icon-chevron-right icon-white"></i></button>
	</div>

	<input type="hidden" id="phone_fields" name="phone_fields" value="<?php echo implode(',', $phone_fields); ?>" />

	<div class="clear"></div>

	<br />

	<input type="hidden" name="action" value="liaison_inquiry">

<?php echo $nonce; ?>

</form>

<?php echo implode( '', $modals ); ?>
