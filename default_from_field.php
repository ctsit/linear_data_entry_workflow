<?php return function($project_id) {
    global $Proj, $question_by_section, $hidden_edit, $pageFields, $double_data_entry, $user_rights;

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

        // Checking for action tag @DEFAULT-FROM-FIELD.
        $source_field_name = Form::getValueInQuotesActionTag($Proj->metadata[$target_field_name]['misc'], '@DEFAULT-FROM-FIELD');
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

        // Aux function.
        $getFormElementSelector = function($element_type, $element_name) {
            return '#questiontable ' . ($element_type == 'select' ? 'select' : 'input') . '[name="' . $element_name . '"]';
        };

        // Setting up target info.
        $source_field_info = $Proj->metadata[$source_field_name];
        $mappings[$target_field_name] = array(
            'type' => $target_field_info['element_type'],
            'selector' => $getFormElementSelector($target_field_info['element_type'], $target_field_name),
            'source' => $getFormElementSelector($source_field_info['element_type'], $source_field_name),
        );
    }

    if (empty($mappings)) {
        // If no mappings, there is no reason to proceed.
        return;
    }
?>
<script>
    $(document).ready(function() {
        var mappings = <?php print json_encode($mappings); ?>;

        for (var target_name in mappings) {
            var mapping = mappings[target_name];
            var source_value = $(mapping.source).val();

            if (typeof source_value === 'undefined') {
                continue;
            }

            // Setting up default values.
            switch (mapping.type) {
                case 'checkbox':
                    $(mapping.selector + '[code="' + source_value + '"]').click();
                    break;
                case 'radio':
                case 'yesno':
                case 'truefalse':
                    $(mapping.selector).siblings().children('input[value="' + source_value + '"]').click();
                    break;
                default:
                    $(mapping.selector).val(source_value);
                    break;

            }
        }
    });
</script>
<?php
}
?>
