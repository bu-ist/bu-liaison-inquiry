jQuery(document).ready(function () {
	main(jQuery);
});

function main($) {

	var that = [];

	that.init = function() {

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
						$('.form-submit-danger').hide();
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

						}
						$('.form-submit-danger').html(message).show();
						$sb.html('Go <i class="icon-chevron-right icon-white"></i>').removeAttr('disabled');

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
