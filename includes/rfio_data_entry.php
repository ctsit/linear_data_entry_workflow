<?php
/**
 * @file
 * Provides RFIO Data Entry feature.
 */

/**
 * Applies review forms in order (RFIO) rule to data entry forms.
 */
function linear_data_entry_workflow_rfio_data_entry($project_id, $record, $exceptions) {
    // Proj is a REDCap var used to pass information about the current project.
    global $Proj;

    // Get form names used internally by REDCap.
    $forms = array_keys(REDCap::getInstrumentNames());

    // Use form names to contruct complete_status field names.
    foreach ($forms as $index => $form_name) {
        $forms[$index] = $form_name . '_complete';
    }

    // Request data as an array to get corresponding record ids and events with
    // complete forms.
    $completed_forms = REDCap::getData($project_id, 'array', $record, $forms);
?>

<script>
    $('document').ready(function() {
        var completedForms = <?php echo json_encode($completed_forms) ?>;
        var exceptions = <?php echo json_encode($exceptions); ?>;

        /**
         * Converts a pageName on a link to the corresponding form's complete_status
         * field name
         */
        function pageToFormComplete(pageName) {
            return pageName + '_complete';
        }

        /**
         * Disables a link to a form.
         */
        function disableForm(cell) {
            cell.style.pointerEvents = 'none';
            cell.style.opacity = '.1';
        }

        /**
         * Returns the query string of the given url string.
         */
        function getQueryString(url) {
            url = decodeURI(url);
            return url.match(/\?.+/)[0];
        }

        /**
         * Returns a set of key-value pairs that correspond to the query
         * parameters in the given url.
         */
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

        /**
         * Returns the arm number of the given event.
         */
        function getArm(eventId) {
            var eventInfo = <?php echo json_encode($Proj->eventInfo) ?>;
            return eventInfo[eventId]['arm_num'];
        }

        /**
         * Returns an array of instrument names for the given event.
         */
        function getEventForms(eventId) {
            var eventForms = <?php echo json_encode($Proj->eventsForms) ?>;
            return eventForms[eventId];
        }

        /**
         * Returns an array of event ids for a given arm number.
         */
        function getEventsInArm(armNum) {
            var events = <?php echo json_encode($Proj->events) ?>;
            return Object.keys(events[armNum]['events']);
        }

        /**
         * Checks if all of the forms before the current event have been completed.
         */
        function previousFormsComplete(record_id, event_id) {
            var forms = completedForms[record_id];
            var arm = getArm(event_id);
            var eventsInArm = getEventsInArm(arm);
            var previousFormCompleted = true;

            for (var index in eventsInArm) {
                var currentEvent = eventsInArm[index];
                if (currentEvent >= event_id) {
                    break;
                }

                if (!instrumentsComplete(currentEvent, forms[currentEvent])) {
                    previousFormCompleted = false;
                    break;
                }
            }

            return previousFormCompleted;
        }

        /**
         * Checks if every instrument for the given event has been completed.
         */
        function instrumentsComplete(eventId, completionData) {
            var complete = true;
            var instruments = getEventForms(eventId)

            for (index in instruments) {
                var instrument = instruments[index];

                // Check if current form is an exception.
                if (exceptions.indexOf(instrument) !== -1) {
                    continue;
                }

                // Check completion status.
                if (completionData[pageToFormComplete(instrument)] !== "2") {
                    complete = false;
                    break;
                }
            }

            return complete;
        }

        /**
         * Main business logic.
         */
        function run() {
            var $links = $('.formMenuList');
            var previousFormCompleted = previousFormsComplete(<?php echo $_GET['id'], ',', $_GET['event_id']?>);

            for (var i = 0; i < $links.length; i++) {
                var childLinks = $links[i].querySelectorAll('a');

                for (var j = 0; j < childLinks.length; j++) {
                    var url = childLinks[j].href;
                    var param = getQueryParameters(url);

                    // If last form was incomplete disable every form after it.
                    if (exceptions.indexOf(param.page) != -1) {
                        continue;
                    }

                    // If last form was incomplete disable every form after it.
                    if (!previousFormCompleted) {
                        disableForm(childLinks[j]);
                        continue;
                    }

                    // Need to check if completed forms value's are undefined
                    // because REDcap does not enter data for incomplete/new
                    // forms.
                    if (completedForms[param.id] === undefined ||
                        completedForms[param.id][param.event_id] === undefined ||
                        completedForms[param.id][param.event_id][pageToFormComplete(param.page)] === undefined ||
                        completedForms[param.id][param.event_id][pageToFormComplete(param.page)] !== "2") {
                        previousFormCompleted = false;

                        // Skip text link next to button if its there.
                        j++;
                        continue;
                    }
                }
            }
        }

        // Hide "Save and Continue to Next Form" buttons.
        $(window).load(function() {
            var $buttons = $('button[name="submit-btn-savenextform"]');

            // Check if buttons are outside the dropdown menu.
            if ($buttons.length !== 0) {
                $.each($buttons, function(index, button) {
                    // Get first button in dropdown-menu.
                    var replacement = $(button).siblings('.dropdown-menu').find('a')[0];

                    // Modify button to behave like $replacement.
                    button.id = replacement.id;
                    button.name = replacement.name;
                    button.onclick = replacement.onclick;
                    button.innerHTML = replacement.innerHTML;

                    // Get rid of replacement.
                    $(replacement).remove();

                });
            }
            else {
                // Disable button inside the dropdown menu.
                $('a[onclick="dataEntrySubmit(\'submit-btn-savenextform\');return false;"]').hide();
            }
        });

        // Run the hook.
        run();
    });
</script>

<?php
}
