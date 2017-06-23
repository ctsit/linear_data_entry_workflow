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
        if ($target_field_info['element_type'] == 'checkbox') {
            // TODO: Handle checkboxes.
            continue;
        }

        // Checking for action tags.
        if (empty($target_field_info['misc'])) {
            continue;
        }

        // Checking for action tag @DEFAULT.
        $default_value = Form::getValueInQuotesActionTag($Proj->metadata[$target_field_name]['misc'], '@DEFAULT');
        if (empty($default_value)) {
            continue;
        }

        // Checking for piping on action tag @DEFAULT.
        preg_match('/\[(.*?)\]/', $default_value, $matches);
        if (empty($matches)) {
            continue;
        }

        // Skipping full string.
        array_shift($matches);

        // Setting up sources info.
        $sources = array();
        foreach ($matches as $source_field_name) {
            if (empty($Proj->metadata[$source_field_name])) {
                continue;
            }

            $source_field_info = $Proj->metadata[$source_field_name];
            $sources[$source_field_name] = '#questiontable ' . ($source_field_info['element_type'] == 'select' ? 'select' : 'input') . '[name="' . $source_field_name . '"]';
        }

        // Checking if form fields are being used as placeholders.
        if (empty($sources)) {
            continue;
        }

        // Setting up target info.
        $mappings[$target_field_name] = array(
            'selector' => '#questiontable ' . ($target_field_info['element_type'] == 'select' ? 'select' : 'input') . '[name="' . $target_field_name . '"]',
            'type' => $target_field_info['element_type'],
            'sources' => $sources,
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
        var aux_replacement = '[aux_replacement_value]';
        var missing_data_replacement = '______';
        var missing_data_replacement_regex = new RegExp(missing_data_replacement, 'g');

        for (var target_name in mappings) {
            // Getting mapping info.
            var mapping = mappings[target_name];

            // Loading target element and value.
            var $target = $(mapping.selector);
            var target_value = $target.val();

            if (mapping.type === 'truefalse') {
                // TODO.
            }
            else {
                // Checking if the number of placeholders matches the number of sources.
                var placeholders_count = (target_value.match(missing_data_replacement_regex) || []).length;
                if (placeholders_count !== Object.keys(mapping.sources).length) {
                    continue;
                }
            }

            for (var source_name in mapping.sources) {
                var source_value = $(mapping.sources[source_name]).val();

                // If the source value is empty, we must keep the placeholder.
                // However, for loop reasons, let's Temporarily replace placeholder with an aux string.
                // It will be restored later.
                if (source_value === '') {
                    source_value = aux_replacement;
                }

                target_value = target_value.replace(missing_data_replacement, source_value);
            }

            // Restoring placeholders.
            target_value.replace(aux_replacement, missing_data_replacement_regex);

            // Setting target value.
            $target.val(target_value);

            // Handling radios.
            if (mapping.type === 'radio') {
                $target.siblings().children('input[value="' + target_value + '"]').prop('checked', true);
            }
        }
    });
</script>
<?php
}
?>
