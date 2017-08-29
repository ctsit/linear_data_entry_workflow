document.addEventListener('DOMContentLoaded', function() {
    var settings = linearDataEntryWorkflow.rfio;
    var completedForms = settings.completedForms;

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
     * Checks if completed forms values are undefined.
     * It is needed because REDCap does not enter data for incomplete/new forms.
     */
    function checkCompletedFormsValues(link, previousFormCompleted) {
        var param = getQueryParameters(link.href);

        // Check if form is an exception.
        if (settings.exceptions.indexOf(param.page) !== -1) {
            return previousFormCompleted;
        }

        if (!previousFormCompleted) {
            disableForm(link);
            return previousFormCompleted;
        }

        var key = pageToFormComplete(param.page);
        return completedForms[param.id] !== undefined &&
            completedForms[param.id][param.event_id] !== undefined &&
            completedForms[param.id][param.event_id][key] !== undefined &&
            completedForms[param.id][param.event_id][key] === "2";
    }

    /**
     * Run RFIO for data entry forms.
     */
    function runDataEntryForm() {
        // Hide "Save and Continue to Next Form" buttons.
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

        var $links = $('.formMenuList');
        var previousFormCompleted = settings.previousEventsCompleted;

        for (var i = 0; i < $links.length; i++) {
            var childLinks = $links[i].querySelectorAll('a');

            for (var j = 0; j < childLinks.length; j++) {
                var previousFormCompletedOld = previousFormCompleted;
                previousFormCompleted = checkCompletedFormsValues(childLinks[j], previousFormCompleted);

                if (previousFormCompletedOld && !previousFormCompleted) {
                    // Skip text link next to button if its there.
                    j++;
                }
            }
        }
    }

    /**
     * Run RFIO for record status dashboard page.
     */
    function runRecordStatusDashboard() {
        var $rows = $('#record_status_table tbody tr');
        if ($rows.length === 0) {
            return false;
        }

        for (var i = 0; i < $rows.length; i++) {
            var previousFormCompleted = true;

            // Start at 1 to avoid disabling Record ID column.
            for (var j = 1; j < $rows[i].cells.length; j++) {
                var link = $rows[i].cells[j].getElementsByTagName('a')[0];
                previousFormCompleted = checkCompletedFormsValues(link, previousFormCompleted);
            }
        }
    }

    /**
     * Run RFIO for record home page.
     */
    function runRecordHome() {
        var $rows = $('#event_grid_table tbody tr');
        if ($rows.length === 0) {
            return false;
        }

        var previousFormCompleted = true;

        // Start at 1 to avoid disabling "Data Collection Instrument"
        // column.
        for (var i = 1; i < $rows[0].cells.length; i++) {
            for (var j = 0; j < $rows.length; j++) {
                // Skips cells that do not have links or are members of the
                // "Delete all data on event" row.
                var link = $rows[j].cells[i].getElementsByTagName('a')[0];
                if (link === undefined ||
                    $rows[j].cells[0].innerHTML === 'Delete all data on event:') {
                    continue;
                }

                previousFormCompleted = checkCompletedFormsValues(link, previousFormCompleted);
            }
        }
    }

    /**
     * Main business logic.
     */
    switch (settings.location) {
        case 'data_entry_form':
            runDataEntryForm();
            break;
        case 'record_home':
            runRecordHome();
            break;
        case 'record_status_dashboard':
            runRecordStatusDashboard();
            break;
    }
});
