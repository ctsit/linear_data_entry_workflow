<?php return function($project_id) {
    global $hidden_edit, $double_data_entry, $user_rights, $quesion_by_section, $pageFields, $Proj;

    // Checking if we are in a data entry or survey page.
    if (!in_array(PAGE, array('DataEntry/index.php', 'surveys/index.php', 'Surveys/theme_view.php'))) {
        return;
    }

    if (PAGE == 'surveys/index.php' && !(isset($_GET['s']) && defined('NOAUTH'))) {
        return;
    }

    // Checking if the record exists.
    if ($hidden_edit) {
        $record = $_GET['id'];
        if ($double_data_entry && $user_rights['double_data'] != 0) {
            $record = $record . '--' . $user_rights['double_data'];
        }

        $is_survey = PAGE != 'DataEntry/index.php';
        if ($is_survey && $question_by_section && Records::fieldsHaveData($record, $pageFields[$_GET['__page__']], $_GET['event_id'])) {
            // The page has data.
            return;
        }

        if (Records::formHasData($record, $_GET['page'], $_GET['event_id'], $_GET['instance'])) {
            // The page has data.
            return;
        }
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
};
?>
