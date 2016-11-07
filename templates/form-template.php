<h1><?php echo $inquiry_form->form->header; ?></h1>
<p><?php echo $inquiry_form->form->subHeader;?></p>

<script type='text/javascript'>
	var SITE = {};
	SITE.data = {
		client_rules_url: "<?php echo self::$client_rules_url; ?>",
		field_options_url: "<?php echo self::$field_options_url; ?>",
		client_id: "<?php echo $client_id; ?>"
	};
</script>

<form id="form_example" action="<?php echo esc_url( admin_url('admin-post.php') ); ?>" method="post">

<?php
	// Initialize modal and phone fields. 
	$modals = array();
	$phone_fields = array();
?>
<?php foreach ( $inquiry_form->sections as $section_index => $section ) : ?>

	<div class="section">
		<h3 class="page-header"><?php echo $section->name; ?><small><?php echo $section->description; ?></small></h3>
	
			<?php foreach ( $section->fields as $field_index => $field ) : ?>

			<?php
			//setup
			$label = $field->displayName;

			if ( 6 == $field->id ) {
				// Address Line 1.
				$label = 'Address';
			} else if ( 7 == $field->id ) {
				// Address Line 2.
				$label = '';
			}
			//end setup
			?>

			<?php
			// Mini form needs to pass dummy values to otherwise required fields; insert them here as hidden inputs.
			if ( isset( $field->hidden ) && $field->hidden ) : ?>
				<input type="hidden" name="<?php echo $field->id;?>" value="<?php echo $field->hidden_value;?>">
			<?php
			//begin 2 types of html elements: input-text or select
			elseif ( $field->htmlElement == 'input-text' ) :
				//begin input text
				$class = '';

				if ( stripos( $field->description, 'phone number' ) !== false ) {
					$class = ' iqs-form-phone-number';
					$phone_fields[] = $field->id;
				} else {
					$class = ' iqs-form-text';
				}
			?>

				<div class="row">
					<div class="form-group">
						<label for="<?php echo $field->id; ?>" class="col-sm-4 control-label"><?php echo $label . (($field->required) ? ' <span class="asterisk">*</span>' : ''); ?></label>
						<div class="col-sm-6 col-md-5">
							<input type="text"
								name="<?php echo $field->id; ?>"
								id="<?php echo $field->id; ?>"
								class="form-control<?php echo (($field->required) ? ' required' : '') . $class; ?>" placeholder="<?php echo $field->displayName; ?>" />
			
			<?php
			if ($class == ' iqs-form-phone-number' && isset($section->fields[$field_index + 1])
				&& ($section->fields[$field_index]->order + 0.1) == $section->fields[$field_index + 1]->order) :
					//begin iqs-form-phone-number
					$element_id = $section->fields[$field_index + 1]->id;
					$label_text = trim($section->fields[$field_index + 1]->displayName);
					$opt_in_text = '<a href="#text-message-opt-in-modal" class="blue" data-toggle="modal">opt-in policy</a>';
					$label_text = str_ireplace('opt-in policy', $opt_in_text, $label_text);
					
					$modals[] = '
						<div id="text-message-opt-in-modal" class="modal fade">
					    	<div class="modal-dialog">
						    	<div class="modal-content">
									<div class="modal-header">
										<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
										<h4 class="modal-title">Text Message Opt-in Policy</h4>
									</div>
									<div class="modal-body">
										 ' . $section->fields[$field_index + 1]->helpText . '
									</div>
									<div class="modal-footer">
										<button type="button" class="btn" data-dismiss="modal">Close</button>
									</div>
						    	</div>
					    	</div>
						</div>
					';
			?>
					
						<input type="checkbox" name="<?php echo $element_id; ?>" id="<?php echo $element_id; ?>">
						<label id="label-<?php echo $element_id;?>" for="<?php $element_id; ?>"><?php echo $label_text; ?></label>
					
			<?php endif; //end iqs-form-phone number ?>
			
			
			<?php if ($field->helpText !== '') : ?>
				<p class="help-block"><?php echo $field->helpText; ?></p>
			<?php endif; ?>
			
			
						</div>
					</div><!-- end class="form-group" -->
				</div><!-- end class="row" -->
			
			<?php //end input txt ?>
			<?php elseif ($field->htmlElement == 'select') : ?>
			
			<?php //begin select
			$class = ' iqs-form-single-select';
			?>
			
				<div class="row">
					<div class="form-group">
						<label for="<?php echo $field->id; ?>" class="col-sm-4 control-label"><?php echo $label . (($field->required) ? ' <span class="asterisk">*</span>' : ''); ?></label>
						<div class="col-sm-6 col-md-5">
							<select
								name="<?php echo $field->id; ?>"
								id="<?php $field->id; ?>"
								class="input-sm form-control<?php echo (($field->required) ? ' required' : '') . $class; ?>">
								<option value=""></option>
			
			<?php if ($field->id == 9) : // State ?>
				<option value="Outside US & Canada">Outside US & Canada</option>
			<?php endif; ?>
			
			<?php foreach ($field->options as $option) : ?>
				<?php if (isset($option->options)) : ?>
					<optgroup label="<?php echo $option->label; ?>">
					
					<?php foreach ($option->options as $sub_option) : ?>
						<option value="<?php echo $sub_option->id; ?>"><?php echo $sub_option->value; ?></option>
					<?php endforeach; ?>
					
					</optgroup>
					
				<?php else: ?>
					<option value="<?php echo $option->id; ?>"><?php echo $option->value; ?></option>
				<?php endif; ?>
			<?php endforeach; ?>

							</select>
							<?php echo (($field->helpText !== '') ? '<p class="help-block">' . $field->helpText . '</p>' : ''); ?> 
						</div>
					</div><!-- end class="form-group" -->
				</div><!-- end class="row" -->
			
		<?php endif; //end select ?>
	<?php endforeach; //end field ?>

	
		</div><!-- end class="section" -->
	
<?php endforeach; //end section ?>


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
