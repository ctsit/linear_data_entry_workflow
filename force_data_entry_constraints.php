<?php // force_data_entry_constraints.php
return function ($project_id) {
    global $Proj, $isMobileDevice;

    $bullets = '';
    $selectors = array();

    foreach ($Proj->metadata as $field_name => $field_info) {
        if (!$field_info['field_req']) {
            continue;
        }

        $field_label = filter_tags(label_decode($field_info['element_label']));
        $bullets .= '<div class="req-bullet req-bullet--' . $field_name . '" style="margin-left: 1.5em; text-indent: -1em; display: none;"> &bull; ' . $field_label . '</div>';

        $selectors[] = '#questiontable ' . ($field_info['element_type'] == 'select' ? 'select' : 'input') . '[name="' . $field_name . '"]';
    }

    if (empty($selectors)) {
        return;
    }

    $selectors = implode(', ', $selectors);

    print '
        <div id="preemptiveReqPopup" title="Some fields are required!" style="display:none;text-align:left;">
            <p>You did not provide a value for some fields that require a value. Please enter a value for the fields on this page that are listed below.</p>
            <div style="font-size:11px; font-family: tahoma, arial; font-weight: bold; padding: 3px 0;">' . $bullets . '</div>
        </div>';
?>
<script>
    var FORM_STATUS_COMPLETED = 2;

    $('#submit-btn-saverecord, #submit-btn-savecontinue').each(function() {
        $(this).data('onclick', this.onclick);

        this.onclick = function(event) {
            if ($('#questiontable select[name="<?php print $_GET['page'];  ?>_complete"]').val() == FORM_STATUS_COMPLETED) {
                var form_is_complete = true;
                var $req_fields = $('<?php print $selectors; ?>');

                $req_fields.each(function() {
                    if ($(this).val() === '') {
                        $('.req-bullet--' + $(this).attr('name')).show();
                        form_is_complete = false;
                    }
                });

                if (!form_is_complete) {
                    $('#preemptiveReqPopup').dialog({
                        bgiframe: true,
                        modal: true,
                        width: (isMobileDevice ? $(window).width() : 570),
                        open: function() {
                            fitDialog(this);
                        },
                    });

                    return false;
                }
            }

            $(this).data('onclick').call(this, event || window.event);
        };
    });
</script>
<?php
}
?>
