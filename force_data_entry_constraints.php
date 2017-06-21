<?php
return function ($project_id) {
    global $Proj;

    // Markup of required fields bullets list.
    $bullets = '';

    // Selectors to search for empty required fields.
    $selectors = array();

    // Getting required fields from form config.
    foreach ($Proj->metadata as $field_name => $field_info) {
        if (!$field_info['field_req']) {
            continue;
        }

        // The bullets are hidden for default, since we do not know yet which ones are empty.
        $field_label = filter_tags(label_decode($field_info['element_label']));
        $bullets .= '<div class="req-bullet req-bullet--' . $field_name . '" style="margin-left: 1.5em; text-indent: -1em; display: none;"> &bull; ' . $field_label . '</div>';

        $selectors[] = '#questiontable ' . ($field_info['element_type'] == 'select' ? 'select' : 'input') . '[name="' . $field_name . '"]';
    }

    if (empty($selectors)) {
        return;
    }

    $selectors = implode(', ', $selectors);

    // Printing required fields popup (hidden yet).
    print '
        <div id="preemptiveReqPopup" title="Some fields are required!" style="display:none;text-align:left;">
            <p>You did not provide a value for some fields that require a value. Please enter a value for the fields on this page that are listed below.</p>
            <div style="font-size:11px; font-family: tahoma, arial; font-weight: bold; padding: 3px 0;">' . $bullets . '</div>
        </div>';
?>
<script>
    var FORM_STATUS_COMPLETED = 2;

    $(document).ready(function() {
        var dataEntryFormValidate = function() {
            var form_is_ok = true;

            // Checking if form status is set as Complete.
            if ($('#questiontable select[name="<?php print $_GET['page'];  ?>_complete"]').val() == FORM_STATUS_COMPLETED) {
                // Checking for empty required fields.
                $('<?php print $selectors; ?>').each(function() {
                    if ($(this).val() === '') {
                        // This required field is empty, so let's show up its bullet.
                        $('.req-bullet--' + $(this).attr('name')).show();
                        form_is_ok = false;
                    }
                });

                // If there is empty required fields, display popup.
                if (!form_is_ok) {
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
            };

            return form_is_ok;
        }

        // Handling submit buttons.
        $('#submit-btn-saverecord, #submit-btn-savecontinue').each(function() {
            // Storing onclick callback of the submit button.
            $(this).data('onclick', this.onclick);

            // Overriding onclick callback of submit buttons.
            this.onclick = function(event) {
                if (!dataEntryFormValidate()) {
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
                if (buttons[i].class == 'dataEntrySaveLeavePageBtn') {
                    // Storing click callback of Save & Leave button.
                    $(this).data('dataEntrySaveLeavePageBtn', buttons[i].click);

                    // Overriding click callback of Save & Leave button.
                    buttons[i].click = function () {
                        if (!dataEntryFormValidate()) {
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
