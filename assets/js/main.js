jQuery(document).ready(function () {
	main(jQuery);
});

function main($) {

	var that = [];
	
	// Function to reset submit button to its original state
	function resetSubmitButton(toRetryState) {
		var $button = $('.btn-warning.btn-primary, .btn-warning, .btn-primary');
		
		if (toRetryState) {
			$button.html('Retry Submission <i class="icon-chevron-right icon-white"></i>')
				.removeClass('btn-primary')
				.addClass('btn-warning')
				.removeAttr('disabled');
		} else {
			$button.html('Go <i class="icon-chevron-right icon-white"></i>')
				.removeClass('btn-warning')
				.addClass('btn-primary')
				.removeAttr('disabled');
		}
		
		// Re-enable click handler for retry state
		if (toRetryState) {
			$button.one('click', function(e) {
				e.preventDefault();
				
				// Reset the retry state when starting a new manual retry
				isFirstRetry = true;
				
				// When clicked, update the referring page to indicate manual retry
				modifyReferringPage('manual');
				
				// Show submitting state
				$(this).html('Submitting...').attr('disabled', 'disabled');
				
				// Trigger form validation and submission using the stored validator
				if (that.formValidator.form()) {
					// If validation passes, trigger submission with a flag
					var submitEvent = $.Event('submit');
					submitEvent.isValidated = true;
					$('#form_example').trigger(submitEvent);
				}
			});
		}
	}
    
    // Track if we're on our first or second retry attempt
    var isFirstRetry = true;
    
    // Helper function to modify the referring_page hidden field to track retry attempts
    function modifyReferringPage(retryType) {
        var $referringField = $('input[name="referring_page"]');
        var currentValue = $referringField.val();
        
        // Don't add suffix multiple times
        if (!currentValue.includes('-browser-retry-')) {
            $referringField.val(currentValue + '-browser-retry-' + retryType);
        }
    }
    
    // Helper function to create or update the retry notification
    function updateRetryNotice(isFirstAttempt) {
        var title = isFirstAttempt ? 
            'Your form submission timed out.' : 
            'Your form submission timed out again.';
            
        var message = isFirstAttempt ?
            '<span class="retry-message">Automatically retrying in <span class="countdown">3</span> seconds...</span>' :
            '<span class="retry-message">Please click the button below to try again.</span>';
        
        if ($('.form-retry-notice').length === 0) {
            $('<div class="alert alert-warning form-retry-notice">' +
                '<strong>' + title + '</strong> ' +
                message +
                '</div>').insertAfter('.form-submit-danger');
        } else {
            $('.form-retry-notice').html(
                '<strong>' + title + '</strong> ' +
                message
            );
        }
        
        // Show the notice
        $('.form-retry-notice').show();
    }
    
    // Function to reset the retry state and clear any old retry indicators
    function resetRetryState() {
        // Reset the referring_page field to its original value by removing any retry suffixes
        var $referringField = $('input[name="referring_page"]');
        var currentValue = $referringField.val();
        var baseValue = currentValue.split('-browser-retry-')[0];
        $referringField.val(baseValue);
    }
    
    // Function to set up and start the automatic countdown timer
    function startCountdownAndAutoRetry() {
        var count = 3;
        var countdownTimer = setInterval(function() {
            count--;
            $('.countdown').text(count);
            
            if (count <= 0) {
                clearInterval(countdownTimer);
                // Change message to "Retrying now..."
                $('.retry-message').text('Retrying now...');
                
                // Submit the form automatically after countdown
                setTimeout(function() {
                    isFirstRetry = false; // Mark that we've done the first retry
                    $('#form_example').submit();
                }, 100);
            }
        }, 1000);
        
        // Store the timer ID so we can cancel it if needed
        window.retryCountdownTimer = countdownTimer;
    }
    
    // Function to set up the manual retry button
    function setupManualRetryButton() {
        resetSubmitButton(true); // Reset to retry state with proper click handler
    }
    
    // Main function to initiate and manage the browser-side retry workflow
    function initiateBrowserRetry() {
        // Clear any previous countdown timer
        if (window.retryCountdownTimer) {
            clearInterval(window.retryCountdownTimer);
        }
        
        // Reset retry state before starting a new cycle
        resetRetryState();
        
        // Hide standard error messages
        $('.form-submit-danger').hide();
        
        if (isFirstRetry) {
            // First timeout - do automatic retry with countdown
            updateRetryNotice(true);
            modifyReferringPage('auto');
            startCountdownAndAutoRetry();
        } else {
            // Second timeout - switch to manual retry
            updateRetryNotice(false);
            setupManualRetryButton();
        }
    }

	that.init = function() {
		// Prevent any direct form submissions - everything should go through validation
		$('#form_example').on('submit', function(e) {
			// Always prevent default form submission
			e.preventDefault();
			
			// Only trigger validation if it wasn't already validated
			if (!e.isValidated) {
				that.formValidator.form();
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

		// Store the validator instance for use throughout the application
		that.formValidator = $('#form_example').validate({

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
							resetSubmitButton(false);
						} else if (message.toLowerCase().indexOf('failed submitting') >= 0 && 
								  message.indexOf('cURL error 28:') >= 0) {
							// Initiate browser retry workflow for timeout errors
							$('.form-submit-danger').hide();
							initiateBrowserRetry();
						} else {
							// Check if this was a manual retry attempt by looking at the referring_page field
							var $referringField = $('input[name="referring_page"]');
							var isManualRetry = $referringField.val().indexOf('-browser-retry-manual') > -1;
							if (isManualRetry) {
								// Show special message for failed manual retry
								message = 'Manual retry attempt also failed. ' + message;
								$('.form-retry-notice').hide();
							}
							$('.form-submit-danger').html(message).show();
							resetSubmitButton(isManualRetry);
						}
					}

				}, 'json').fail(function(jqXHR, textStatus, errorThrown) {
					// Handle AJAX failures (network errors, etc.)
					var $referringField = $('input[name="referring_page"]');
					var isManualRetry = $referringField.val().indexOf('-browser-retry-manual') > -1;
					var message = 'Connection error: ' + textStatus;
					
					if (isManualRetry) {
						message = 'Manual retry failed due to connection error. Please try again later.';
					}
					
					$('.form-submit-danger').html(message).show();
					resetSubmitButton(false);
				});
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
