jQuery(document).ready(function () {
	main(jQuery);
});

function main($) {

	var that = [];
	
	// Function to reset submit button to its original state
	function resetSubmitButton() {
		$('.btn-warning.btn-primary, .btn-warning').html('Go <i class="icon-chevron-right icon-white"></i>')
			.removeClass('btn-warning')
			.addClass('btn-primary')
			.removeAttr('disabled');
	}
	
	// Function to show retry message UI
	function showRetryMessage() {
		// Create retry notification if it doesn't exist
		if ($('.form-retry-notice').length === 0) {
			$('<div class="alert alert-warning form-retry-notice">' +
				'<strong>Your form submission timed out.</strong> ' +
				'Please click the submit button below to try again.' +
				'</div>').insertAfter('.form-submit-danger');
		}
		
		// Show the notice
		$('.form-retry-notice').show();
		
		// Update the submit button text to indicate retry
		$('.btn-primary').html('Retry Submission <i class="icon-chevron-right icon-white"></i>')
			.removeClass('btn-primary')
			.addClass('btn-warning')
			.removeAttr('disabled');
	}

	that.init = function() {
		// Prevent any direct form submissions - everything should go through validation
		$('#form_example').on('submit', function(e) {
			// Only prevent default if not triggered by validation
			if (!e.isDefaultPrevented()) {
				e.preventDefault();
				$(this).validate().form();
			}
		});

		//$('.twipsy').tooltip({
		//	'placement':'top'
		//});

		$(".bu-liaison-modal").dialog({
			autoOpen: false,
			show: {
				effect: "fade",
				duration: 300
			},
			buttons: {
				Close: function() {
					$(this).dialog('close');
				}
			}
		});

		$("#opt-in-trigger").on("click", function(e) {
			e.preventDefault();
			$("#text-message-opt-in-modal").dialog("open");
		});

		$('.iqs-form-phone-number').mask("(999) 999-9999", {
			placeholder: "_",
			autoclear: false
		});

		$('#form_example').validate({

			requiredClass: 'required',
			errorClass: 'error',
			errorApplyTo: 'div.form-group',

			onSuccess: function($form) {

				var $sb = $form.find('.btn-primary').html('Submitting...').attr('disabled', 'disabled');

				$.post( $form.attr('action'), $form.serialize(), function(r) {

					if (r.status == 1) {
						$('.form-submit-danger, .form-retry-notice').hide();
						$('.form-submit-success').html(r.response).show();
						window.location.href = r.data;

					} else {
						var message = r.response;
						$('.form-submit-success').hide();
						
						if (message.toLowerCase().indexOf('already exist') >= 0) {
							window.location.href = r.data;
						} else if (message.toLowerCase().indexOf('incomplete or invalid') >= 0) {
							$.each(r.data, function (i, item) {
								message += '<br />' + item.displayName;
								$('#' + item.id).parents('div.control-group').addClass('error');
							});
							$('.form-submit-danger').html(message).show();
							resetSubmitButton();
						} else if (message.toLowerCase().indexOf('failed submitting') >= 0 && 
								  message.indexOf('cURL error 28:') >= 0) {
							// Show retry UI for timeout errors
							$('.form-submit-danger').hide();
							showRetryMessage();
						} else {
							$('.form-submit-danger').html(message).show();
							resetSubmitButton();
						}
					}

				}, 'json');
			},

			onError: function($form) {
				$('.form-submit-success').hide();
				$('.form-submit-danger').html('Oops, it appears you weren\'t quite finished yet. Go ahead and fill in the fields we\'ve highlighted').show();
			}
		});

		function setup_field_rules() {
		    var form_rule_handler = SITE.field_rules_form_library;

	        form_rule_handler.setup_library();
	        var fields_by_id = {};

			$.each($('[name]'), function(key, field) {
			    // we need to strip out [] if they trail a field name - such as 608[]
			    field_name = $(field).attr('name');
			    field_name = field_name.replace('[]', '');
			    if (!fields_by_id[field_name]) {
			    	fields_by_id[field_name] = [];
				}
				fields_by_id[field_name].push(field);
		    });

		    form_rule_handler.register_fields(fields_by_id, $('form'));
	    }

	    setup_field_rules();
	}();
}
