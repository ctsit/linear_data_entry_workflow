<?php
/**
 * @file
 * Provides "Force Data Entry Constraints" feature.
 */

/**
 * If set as completed, forces data entry to be fully complete and consistent.
 */
function linear_data_entry_workflow_force_data_entry_constraints() {
    global $Proj;

    // Checking if we are in a data entry or survey page.
    if (!in_array(PAGE, array('DataEntry/index.php', 'surveys/index.php', 'Surveys/theme_view.php'))) {
        return;
    }

    // Checking additional conditions for survey pages.
    if (PAGE == 'surveys/index.php' && !(isset($_GET['s']) && defined('NOAUTH'))) {
        return;
    }

    // Checking current record ID.
    if (empty($_GET['id'])) {
        return;
    }

    // Defines which form statuses can bypass validation.
    $statuses_bypass = array();
    if (PAGE == 'DataEntry/index.php') {
        // If this is a data entry form, let's allow all statuses but Completed.
        $statuses_bypass = array('', '0', '1');
    }

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

        $req_fields_selectors[] = '#questiontable ' . ($field_info['element_type'] == 'select' ? 'select' : 'input') . '[name="' . $field_name . '"]:visible';
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
        // Error color constant.
        const FORM_ERROR_COLOR = 'rgb(255, 183, 190)';

        // Defines which form statuses can bypass validation.
        var statuses_bypass = <?php echo json_encode($statuses_bypass); ?>;

        // Selector to search for the required fields.
        var required_fields_selector = '<?php echo implode(', ', $req_fields_selectors); ?>';

        // Overriding message that says that wrong values are admissible.
        $('#valtext_divs #valtext_rangesoft2').text('You may wish to verify.');

        /**
         * Form validation callback.
         */
        function formValidate(elements_validate, required_fields_selector = '', statuses_bypass = []) {
            // Checking if current form status can bypass validation.
            if (statuses_bypass.includes($('#questiontable select[name="<?php echo $_GET['page']; ?>_complete"]').val())) {
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

        // Handling submit buttons.
        $('#submit-btn-saverecord, #submit-btn-savecontinue, #submit-btn-savenextform, button[name="submit-btn-saverecord"]').each(function() {
            // Storing onclick callback of the submit button.
            $(this).data('onclick', this.onclick);

            // Overriding onclick callback of submit buttons.
            this.onclick = function(event) {
                if (!formValidate(true, required_fields_selector, statuses_bypass)) {
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
                        if (!formValidate(true, required_fields_selector, statuses_bypass)) {
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
};
?>
