document.addEventListener('DOMContentLoaded', function() {
    var settings = linearDataEntryWorkflow.rfio;

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
     * Checks access to a link and disables it, if needed.
     */
    function checkLinkAccess(link) {
        var params = getQueryParameters(link.href);
        if (!settings.formsAccess[params.id][params.event_id][params.page]) {
            disableForm(link);
        }
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
        for (var i = 0; i < $links.length; i++) {
            var childLinks = $links[i].querySelectorAll('a');

            for (var j = 0; j < childLinks.length; j++) {
                checkLinkAccess(childLinks[j]);
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
            // Start at 1 to avoid disabling Record ID column.
            for (var j = 1; j < $rows[i].cells.length; j++) {
                var link = $rows[i].cells[j].getElementsByTagName('a')[0];
                checkLinkAccess(link);
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

        // Start at 1 to avoid disabling "Data Collection Instrument"
        // column.
        for (var i = 1; i < $rows[0].cells.length; i++) {
            for (var j = 0; j < $rows.length; j++) {
                // Skips cells that do not have links or are members of the
                // "Delete all data on event" row.
                var link = $rows[j].cells[i].getElementsByTagName('a')[0];
                if (link === undefined || $rows[j].cells[0].innerHTML === 'Delete all data on event:') {
                    continue;
                }

                checkLinkAccess(link);
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
