<?php
return function ($project_id) {
    global $Proj;

    // Markup of required fields bullets list.
    $bullets = '';

    // Selectors to search for empty required fields.
    $req_fields_selectors = array();

    // Getting required fields from form config.
    foreach ($Proj->metadata as $field_name => $field_info) {
        if (!$field_info['field_req']) {
            continue;
        }

        // The bullets are hidden for default, since we do not know which ones will be empty.
        $field_label = filter_tags(label_decode($field_info['element_label']));
        $bullets .= '<div class="req-bullet req-bullet--' . $field_name . '" style="margin-left: 1.5em; text-indent: -1em; display: none;"> &bull; ' . $field_label . '</div>';

        $req_fields_selectors[] = '#questiontable ' . ($field_info['element_type'] == 'select' ? 'select' : 'input') . '[name="' . $field_name . '"]';
    }

    // Printing required fields popup (hidden yet).
    echo '
        <div id="preemptiveReqPopup" title="Some fields are required!" style="display:none;text-align:left;">
            <p>You did not provide a value for some fields that require a value. Please enter a value for the fields on this page that are listed below.</p>
            <div style="font-size:11px; font-family: tahoma, arial; font-weight: bold; padding: 3px 0;">' . $bullets . '</div>
        </div>';
?>
<script>
    $(document).ready(function() {
        // Setting up constants.
        const FORM_STATUS_COMPLETED = 2;
        const FORM_ERROR_COLOR = 'rgb(255, 183, 190)';

        // Overriding message that says that wrong values are admissible.
        $('#valtext_divs #valtext_rangesoft2').text('You may wish to verify.');

        // Selector to search for the required fields.
        var required_fields_selector = '<?php echo implode(', ', $req_fields_selectors); ?>';

        /**
         * Function that checks whether fields values are consistent.
         */
        function fieldsConsistencyValidate() {
            var validated = true;

            // Running the validation callback of each form element (e.g. checking for numbers out of range).
            $('#questiontable input, #questiontable select').each(function() {
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
         * Form validation callback.
         */
        function formValidate(required_fields_selector = '', elements_validate = true) {
            // Checking if form status is set as 'Complete'.
            if ($('#questiontable select[name="<?php echo $_GET['page'];  ?>_complete"]').val() != FORM_STATUS_COMPLETED) {
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

        // Handling submit buttons.
        $('#submit-btn-saverecord, #submit-btn-savecontinue').each(function() {
            // Storing onclick callback of the submit button.
            $(this).data('onclick', this.onclick);

            // Overriding onclick callback of submit buttons.
            this.onclick = function(event) {
                if (!formValidate(required_fields_selector)) {
                    return false;
                }

                // Go ahead with normal procedure.
                $(this).data('onclick').call(this, event || window.event);
            };
        });

        // Handling 'Stay on Page' popup, which opens the door for wrong/incomplete submissions.
        $('#stayOnPageReminderDialog').on('dialogopen', function(event, ui) {
            var buttons = $(this).dialog('option', 'buttons');

            for (var i = 0; i < buttons.length; i++) {
                if (buttons[i].class === 'dataEntrySaveLeavePageBtn') {
                    // Storing click callback of Save & Leave button.
                    $(this).data('dataEntrySaveLeavePageBtn', buttons[i].click);

                    // Overriding click callback of Save & Leave button.
                    buttons[i].click = function () {
                        if (!formValidate(required_fields_selector)) {
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
</script>
<?php
}
?>
