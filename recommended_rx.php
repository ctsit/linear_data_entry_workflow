<?php return function($project_id) {
    global $Proj, $question_by_section, $pageFields, $double_data_entry, $user_rights;

    if ($hidden_edit) {
        // This page has data.
        return;
    }

    $entry_num = ($double_data_entry && $user_rights['double_data'] != 0) ? '--' . $user_rights['double_data'] : '';
    if ($question_by_section && Records::fieldsHaveData($_GET['id'].$entry_num, $pageFields[$_GET['__page__']], $_GET['event_id'])) {
        // This page has data.
        return;
    }

    if (Records::formHasData($_GET['id'].$entry_num, $_GET['page'], $_GET['event_id'], $_GET['instance'])) {
        // This page has data.
        return;
    }

    $mappings = array();
    foreach ($Proj->metadata as $target_field_name => $target_field_info) {
        // Checking for action tags.
        if (empty($target_field_info['misc'])) {
            continue;
        }

        // Checking for action tag @DEFAULT.
        if (Form::getValueInQuotesActionTag($Proj->metadata[$target_field_name]['misc'], '@DEFAULT')) {
            // We do not want to override @DEFAULT behavior.
            continue;
        }

        // Checking for action tag @RECOMMENDED_RX.
        $source_field_name = Form::getValueInQuotesActionTag($Proj->metadata[$target_field_name]['misc'], '@RECOMMENDED_RX');
        if (empty($source_field_name)) {
            continue;
        }

        // Checking if source field exists.
        if (empty($Proj->metadata[$source_field_name])) {
            continue;
        }

        // Handling checkbox case.
        if ($target_field_info['element_type'] == 'checkbox') {
            $target_field_name = '__chkn__' . $target_field_name;
        }

        // Setting up target info.
        $source_field_info = $Proj->metadata[$source_field_name];
        $mappings[$target_field_name] = array(
            'selector' => '#questiontable ' . ($target_field_info['element_type'] == 'select' ? 'select' : 'input') . '[name="' . $target_field_name . '"]',
            'type' => $target_field_info['element_type'],
            'source' => '#questiontable ' . ($source_field_info['element_type'] == 'select' ? 'select' : 'input') . '[name="' . $source_field_name . '"]',
        );
    }

    if (empty($mappings)) {
        // If no mappings, there is no reason to proceed.
        return;
    }
?>
<script>
    $(document).ready(function() {
        // Setting up useful vars.
        var mappings = <?php print json_encode($mappings); ?>;

        for (var target_name in mappings) {
            var mapping = mappings[target_name];
            var source_value = $(mapping.source).val();

            if (typeof source_value === 'undefined') {
                continue;
            }

            // Handling checkbox case.
            if (mapping.type === 'checkbox') {
                $(mapping.selector + '[code="' + source_value + '"]').prop('checked', true);
            }
            else {
                // Loading target element and source value.
                var $target = $(mapping.selector);

                // Setting target value.
                $target.val(source_value);

                switch (mapping.type) {
                    case 'radio':
                    case 'yesno':
                    case 'truefalse':
                        // Handling fields that required to be checked.
                        $target.siblings().children('input[value="' + source_value + '"]').prop('checked', true);
                        break;
                }
            }
        }
    });
</script>
<?php
}
?>
