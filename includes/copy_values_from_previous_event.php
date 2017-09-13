<?php
/**
 * @file
 * Provides Copy Values from Previous Event feature.
 */

/**
 * Implements Copy Values from Previous Event feature.
 */
function linear_data_entry_workflow_copy_values_from_previous_event($project_id, $event_id, $fields) {
    $result = array();
    $custom_data = REDCap::getData($project_id, 'json');
    $encoded_data = json_decode($custom_data);
    $max_event_id = 0;
    foreach ($encoded_data as $item) {
        $unique_event_name = $item->redcap_event_name;
        $unique_event_id = REDCap::getEventIdFromUniqueEvent($unique_event_name);
        $settings = array();
        foreach ($fields as $field) {
            // Get names and values of the fields that need to be autofilled.
            if (!empty($item->$field)) {
                $latest_data_obj = new stdClass();
                $latest_data_obj->value = $item->$field;
                $max_event_id = $unique_event_id;
                $latest_data_obj->field_name = $field;
                $latest_data_obj->event_id = $unique_event_id;
                $settings[] = $latest_data_obj;
            }
        }
        // Keep track of maximum event id so that fields are autofilled only for
        // newly opened instrument.
        if ($unique_event_id > $max_event_id) {
            $max_event_id = $unique_event_id;
        }

        // Keep track of values of fields that need to be autofilled from latest
        // event.
        if (!empty($settings)) {
            $result = $settings;
        }
    }
    $encoded_result = json_encode($result);

?>
<script type="text/javascript">
    var eventId = '<?php echo $event_id; ?>';
    var maxEventId = '<?php echo $max_event_id; ?>';
    var phpArray = '<?php echo $encoded_result; ?>';
    if (eventId >= maxEventId) {
        var resultArray = JSON.parse(phpArray);
        for (var i=0; i<resultArray.length; i++){
            $('[name="' + resultArray[i].field_name + '"]').val(resultArray[i].value);
        }
    }
</script>
<?php
};
