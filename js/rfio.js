document.addEventListener('DOMContentLoaded', function() {
    var settings = linearDataEntryWorkflow.rfio;
    var $links;

    switch (settings.location) {
        case 'data_entry_form':
            hideNextFormButtons();
            $links = $('.formMenuList a');
            break;
        case 'record_home':
            $links = $('#event_grid_table a');
            break;
        case 'record_status_dashboard':
            $links = $('#record_status_table a');
            break;
    }

    if (typeof $links === 'undefined' || $links.length === 0) {
        return false;
    }

    $links.each(function() {
        if (this.href.indexOf(app_path_webroot + 'DataEntry/index.php?') === -1) {
            return;
        }

        var params = getQueryParameters(this.href);
        if (!settings.formsAccess[params.id][params.event_id][params.page]) {
            disableForm(this);
        }
    });

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
     * Hide "Save and Continue to Next Form" buttons.
     */
    function hideNextFormButtons() {
        if (settings.isLastForm) {
            return;
        }

        const FORM_STATUS_COMPLETE = '2';

        var $complete = $('[name="' + settings.instrument + '_complete"]');
        var $buttonsBottom = $('#__SUBMITBUTTONS__-div .btn-group');
        var $buttonsTop = $('#formSaveTip .btn-group');

        // Checking if "Save & Go To Next Record" button is configured to be
        // hidden.
        if (settings.hideNextRecordButton) {
            removeButtons('savenextrecord');
        }

        // Storing original buttons markup.
        var originalBottom = $buttonsBottom.html();
        var originalTop = $buttonsTop.html();

        // Checking initial form status.
        if ($complete.val() !== FORM_STATUS_COMPLETE) {
            removeButtons('savenextform');
        }

        // Dinamically remove or restore buttons according with form status.
        $complete.change(function() {
            if ($(this).val() === FORM_STATUS_COMPLETE) {
                resetButtons();
            }
            else {
                removeButtons('savenextform');
            }
        });

        /**
         * Removes the given submit buttons set.
         */
        function removeButtons(buttonName) {
            var $buttons = $('button[name="submit-btn-' + buttonName + '"]');

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
                $('a[id="submit-btn-' + buttonName + '"]').hide();
            }
        }

        /**
         * Restores the original buttons.
         */
        function resetButtons() {
            $buttonsBottom.html(originalBottom);
            $buttonsTop.html(originalTop);
        }
    }
});
