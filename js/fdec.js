document.addEventListener('DOMContentLoaded', function() {
    // Error color constant.
    const FORM_ERROR_COLOR = 'rgb(255, 183, 190)';

    // Loading settings.
    var settings = linearDataEntryWorkflow.fdec;

    // Getting form status element.
    var $formStatus = $('#questiontable select[name="' + settings.instrument + '_complete"]');

    // Overriding message that says that wrong values are admissible.
    $('#valtext_divs #valtext_rangesoft2').text('You may wish to verify.');

    // Overriding submit callbacks according to our needs.
    overrideSubmitCallbacks();

    $formStatus.change(function() {
        if (!settings.statusesBypass.includes($formStatus.val())) {
            // Submit callbacks need to be overriden if form status changes.
            overrideSubmitCallbacks();
        }
    });

    /**
     * Form validation callback.
     */
    function formValidate(elements_validate, required_fields_selector = '', statuses_bypass = []) {
        // Checking if current form status can bypass validation.
        if (statuses_bypass.length && statuses_bypass.includes($formStatus.val())) {
            return true;
        }

        // Checking for inconsistent data.
        if (elements_validate && !fieldsConsistencyValidate()) {
            return false;
        }

        // Checking for empty required fields.
        if (required_fields_selector && !requiredFieldsValidate(required_fields_selector)) {
            return false;
        }

        return true;
    }

    /**
     * Function that checks whether fields values are consistent.
     */
    function fieldsConsistencyValidate() {
        var validated = true;

        // Running the validation callback of each form element (e.g. checking for numbers out of range).
        $('#questiontable input:visible, #questiontable select:visible').each(function() {
            if (typeof this.onblur === 'function') {
                this.onblur.call(this);

                if ($(this).css('background-color') === FORM_ERROR_COLOR) {
                    // We've got a validation error.
                    validated = false;
                    return false;
                }
            }
        });

        return validated;
    }

    /**
     * Function that checks whether the required fields are filled.
     * Displays a popup if required and needed.
     */
    function requiredFieldsValidate(required_fields_selector, display_popup = true) {
        validated = true;

        // Looking for empty required fields.
        $(required_fields_selector).each(function() {
            if ($(this).val() === '') {
                // This required field is empty, so let's show up its bullet.
                $('.req-bullet--' + $(this).attr('name')).show();
                validated = false;
            }
        });

        if (!validated && display_popup) {
            // We've got empty required fields, let's display popup.
            $('#preemptiveReqPopup').dialog({
                bgiframe: true,
                modal: true,
                width: (isMobileDevice ? $(window).width() : 570),
                open: function() {
                    fitDialog(this);
                },
                close: function() {
                    $('.req-bullet').hide();
                },
            });
        }

        return validated;
    }

    /**
     * Overrides submit callbacks in order to add extra validation.
     */
    function overrideSubmitCallbacks() {
        $('[id^="submit-btn-save"]').each(function() {
            if ($(this).data('onclick')) {
                // Checking if the submit callback has been overriden already.
                return;
            }

            // Storing onclick callback of the submit button.
            $(this).data('onclick', this.onclick);

            // Overriding onclick callback of submit buttons.
            this.onclick = function(event) {
                if (!formValidate(true, settings.requiredFieldsSelector, settings.statusesBypass)) {
                    return false;
                }

                // Go ahead with normal procedure.
                return $(this).data('onclick').call(this, event || window.event);
            };
        });
    }

    // Handling 'Stay on Page' popup, which opens the door for wrong/incomplete submissions.
    $('#stayOnPageReminderDialog').on('dialogopen', function(event, ui) {
        var buttons = $(this).dialog('option', 'buttons');

        for (var i = 0; i < buttons.length; i++) {
            if (buttons[i].class === 'dataEntrySaveLeavePageBtn') {
                // Storing click callback of Save & Leave button.
                $(this).data('dataEntrySaveLeavePageBtn', buttons[i].click);

                // Overriding click callback of Save & Leave button.
                buttons[i].click = function () {
                    if (!formValidate(true, settings.requiredFieldsSelector, settings.statusesBypass)) {
                        return false;
                    }

                    // Go ahead with normal procedure.
                    $(this).data('dataEntrySaveLeavePageBtn').call();
                };

                // Saving overriden button to popup.
                $(this).dialog('option', 'buttons', buttons);

                break;
            }
        };
    });
});
