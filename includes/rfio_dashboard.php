<?php
/**
 * @file
 * Provides RFIO Dashboard feature.
 */

/**
 * Applies review fields in order (RFIO) rule to the dashboard page.
 */
function linear_data_entry_workflow_rfio_dashboard($project_id) {
    // Check if we are on the right page.
    if (PAGE != 'DataEntry/record_status_dashboard.php') {
        return;
    }

    // Get form names used internally by REDCap.
    $forms = array_keys(REDCap::getInstrumentNames());

    // Use form names to contruct complete_status field names.
    foreach ($forms as $index => $form_name) {
        $forms[$index] = $form_name . '_complete';
    }

    // Request data as an array to get corresponding record ids and events with
    // complete forms.
    $completed_forms = REDCap::getData($project_id, 'array', null, $forms);

?>

<script>
    $('document').ready(function() {
        var completedForms = <?php echo json_encode($completed_forms) ?>;

        /**
         * Converts a pageName on a link to the corresponding form's
         * complete_status field name.
         */
        function pageToFormComplete(pageName) {
            return pageName + '_complete';
        }

        function disableForm(cell) {
            cell.style.pointerEvents = 'none';
            cell.style.opacity = '.1';
        }

        function getQueryString(url) {
            url = decodeURI(url);
            return url.match(/\?.+/)[0];
        }

        function getQueryParameters(url) {
            var parameters = {};
            var queryString = getQueryString(url);
            var reg = /([^?&=]+)=?([^&]*)/g;
            var keyValuePair;
            while (keyValuePair = reg.exec(queryString)) {
                parameters[keyValuePair[1]] = keyValuePair[2];
            }
            return parameters;
        }

        function run() {
            var $rows = $('#record_status_table tbody tr');
            if ($rows.length === 0) {
                return false;
            }

            for (var i = 0; i < $rows.length; i++) {
                var previousFormCompleted = true;

                // Start at 1 to avoid disabling Record ID column.
                for (var j = 1; j < $rows[i].cells.length; j++) {

                    // If last form was incomplete disable every form after it.
                    if (!previousFormCompleted) {
                      disableForm($rows[i].cells[j]);
                      continue;
                    }

                    var link = $rows[i].cells[j].getElementsByTagName('a')[0].href;
                    var param = getQueryParameters(link);

                    // Need to check if event_id is defined in completedForms
                    // first  because js crashes if it has to check the property
                    // of an undefined variable.
                    if (completedForms[param.id][param.event_id] === undefined ||
                        completedForms[param.id][param.event_id][pageToFormComplete(param.page)] === undefined ||
                        completedForms[param.id][param.event_id][pageToFormComplete(param.page)] !== "2") {
                        previousFormCompleted = false;
                        continue;
                    }
                }
            }
        }

        // Run the hook.
        run();
    });
</script>

<?php
}
