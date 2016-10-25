<?php
// Assemble form markup from API response.
$html = '';

$html .= '<h1>' . $inquiry_form->form->header . '</h1>';
$html .= '<p>' . $inquiry_form->form->subHeader . '<p>';

// Inject JS validation rule config values.
$html .= "\n";

$html .= "<script type='text/javascript'>";
$html .= "\n";
$html .= 'var SITE = {};';
$html .= "\n";
$html .= 'SITE.data = {client_rules_url: "' . $this->client_rules_url . '", field_options_url: "' . $this->field_options_url . '", client_id: "' . $client_id .'"};';
$html .= "\n";
$html .= '</script>';

$html .= "\n";

// Markup code directly copied from Spectrum EMP API example.
// better markup - echo esc_url( admin_url('admin-post.php') );
$html .= '<form id="form_example" action="' . get_admin_url() . 'admin-post.php" method="post">';

foreach ( $inquiry_form->sections as $section_index => $section ) {
	$html .= '
		<div class="section">
			<h3 class="page-header">' . $section->name . ' <small>' . $section->description . '</small></h3>
	';

	foreach ( $section->fields as $field_index => $field ) {
		$label = $field->displayName;

		if ( 6 == $field->id ) {
			// Address Line 1.
			$label = 'Address';
		} else if ( 7 == $field->id ) {
			// Address Line 2.
			$label = '';
		}

		if ( $field->htmlElement == 'input-text' ) {
			$class = '';

			if ( stripos( $field->description, 'phone number' ) !== false ) {
				$class = ' iqs-form-phone-number';
				$phone_fields[] = $field->id;
			} else {
				$class = ' iqs-form-text';
			}

			$html .= '
				<div class="row">
					<div class="form-group">
						<label for="' . $field->id . '" class="col-sm-4 control-label">' . $label . (($field->required) ? ' <span class="asterisk">*</span>' : '') . '</label>
						<div class="col-sm-6 col-md-5">
							<input type="text"
								name="' . $field->id . '"
								id="' . $field->id . '"
								class="form-control' . (($field->required) ? ' required' : '') . $class . '" placeholder="' . $field->displayName . '" />
			';
			
			if ($class == ' iqs-form-phone-number' && isset($section->fields[$field_index + 1])
				&& ($section->fields[$field_index]->order + 0.1) == $section->fields[$field_index + 1]->order) {
					
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
					
					$html .= '
						<input type="checkbox" name="' . $element_id . '" id="' . $element_id . '">
						<label id="label-' . $element_id . '" for="' . $element_id . '">' . $label_text . '</label>
					';
			}
			
			if ($field->helpText !== '') {
				$html .= '<p class="help-block">' . $field->helpText . '</p>';
			}
			
			$html .= '
						</div>
					</div><!-- end class="form-group" -->
				</div><!-- end class="row" -->
			';
			
		} else if ($field->htmlElement == 'select') {
			$class = ' iqs-form-single-select';
			
			$html .= '
				<div class="row">
					<div class="form-group">
						<label for="' . $field->id . '" class="col-sm-4 control-label">' . $label . (($field->required) ? ' <span class="asterisk">*</span>' : '') . '</label>
						<div class="col-sm-6 col-md-5">
							<select
								name="' . $field->id . '"
								id="' . $field->id . '"
								class="input-sm form-control' . (($field->required) ? ' required' : '') . $class . '">
								<option value=""></option>
			';
			
			if ($field->id == 9) { // State
				$html .= '<option value="Outside US & Canada">Outside US & Canada</option>';
			}
			
			foreach ($field->options as $option) {
				if (isset($option->options)) {
					$html .= '<optgroup label="' . $option->label . '">';
					
					foreach ($option->options as $sub_option) {
						$html .= '<option value="' . $sub_option->id . '">' . $sub_option->value . '</option>';
					}
					
					$html .= '</optgroup>';
					
				} else {
					$html .= '<option value="' . $option->id . '">' . $option->value . '</option>';
				}
			}
			
			$html .= '
							</select>
							' . (($field->helpText !== '') ? '<p class="help-block">' . $field->helpText . '</p>' : '') . '
						</div>
					</div><!-- end class="form-group" -->
				</div><!-- end class="row" -->
			';
		}
	}
	
	$html .= '
		</div><!-- end class="section" -->
	';
}

$html .= '
	<div class="clear"></div>
	
	<br />
	
	<div class="alert alert-success form-submit-success"></div>
	
	<div class="alert alert-danger form-submit-danger"></div>
	
	<div class="form-actions">
		<button type="submit" class="btn btn-primary">Go <i class="icon-chevron-right icon-white"></i></button>
	</div>
	
	<input type="hidden" id="form_submit" name="form_submit" value="liaison_inquiry_form" />
	<input type="hidden" id="phone_fields" name="phone_fields" value="' . implode(',', $phone_fields) . '" />
	
	<div class="clear"></div>
	
	<br />

	<input type="hidden" name="action" value="liaison_inquiry">
';

$html .= $nonce;

$html .= '</form>';


// End copied markup code