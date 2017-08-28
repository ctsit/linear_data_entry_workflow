<?php
/**
 * @file
 * Provides ExternalModule class for Linear Data Entry Workflow.
 */

namespace LinearDataEntryWorkflow\ExternalModule;

use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;

/**
 * ExternalModule class for Linear Data Entry Workflow.
 */
class ExternalModule extends AbstractExternalModule {

    /**
     * @inheritdoc
     */
    function hook_every_page_top($project_id) {
        // Initializing settings JS variable.
        echo '<script>linearDataEntryWorkflow = {};</script>';

        switch (PAGE) {
            case 'DataEntry/record_home.php':
                if (empty($_GET['id'])) {
                    break;
                }

            case 'DataEntry/record_status_dashboard.php':
                $this->loadRFIO($project_id, $_GET['id'], str_replace('.php', '', str_replace('DataEntry/', '', PAGE)));
                break;

            case 'surveys/index.php':
                // Checking additional conditions for survey pages.
                if (!(isset($_GET['s']) && defined('NOAUTH'))) {
                    break;
                }

            case 'Surveys/theme_view.php':
                $this->LoadFDEC();
                break;
        }
    }

    /**
     * @inheritdoc
     */
    function hook_data_entry_form($project_id, $record, $instrument, $event_id, $group_id) {
        $this->loadRFIO($project_id, $record, 'data_entry_form', $event_id);
        $this->LoadFDEC($instrument, array('', 0, 1));
    }

    /**
     * Loads RFIO (review fields in order) feature.
     *
     * @param int $project_id
     *   The project ID.
     * @param int $record
     *   The data entry record ID.
     * @param string $location
     *   The location to apply RFIO. Can be:
     *   - data_entry_form
     *   - record_home
     *   - record_status_dashboard
     * @param int $event_id
     *   The event ID. Only required when $location = "data_entry_form".
     */
    protected function loadRFIO($project_id, $record, $location, $event_id = null) {
        // Proj is a REDCap var used to pass information about the current project.
        global $Proj;

        // Get form names used internally by REDCap.
        $forms = array_keys(\REDCap::getInstrumentNames());

        // Use form names to contruct complete_status field names.
        foreach ($forms as $index => $form_name) {
            $forms[$index] = $form_name . '_complete';
        }

        $completed_forms = \REDCap::getData($project_id, 'array', $record, $forms);
        if (!$exceptions = $this->getProjectSetting('forms-exceptions', $project_id)) {
            $exceptions = array();
        }

        $settings = array(
            'completedForms' => $completed_forms,
            'exceptions' => $exceptions,
            'location' => $location,
        );

        if ($event_id) {
            $settings['previousEventsCompleted'] = true;

            $arm = $Proj->eventInfo[$event_id]['arm_num'];
            foreach (array_keys($Proj->events[$arm]['events']) as $event) {
                if ($event >= $event_id) {
                    break;
                }

                foreach ($Proj->eventsForms[$event] as $instrument) {
                    if (in_array($instrument, $exceptions)) {
                        continue;
                    }

                    if ($completed_forms[$record][$event][$instrument . '_complete'] != 2) {
                        // The previous events are not completed.
                        $settings['previousEventsCompleted'] = false;
                        break 2;
                    }
                }
            }
        }

        $this->setJsSetting('rfio', $settings);
        $this->includeJs('js/rfio.js');
    }

    /**
     * Loads FDEC (force data entry constraints) feature.
     *
     * (optional) @param string $instrument
     *   The instrument/form ID.
     * (optional) @param array $statuses_bypass
     *   An array of form statuses to bypass FDEC. Possible statuses:
     *   - 0 (Incomplete)
     *   - 1 (Unverified)
     *   - 2 (Completed)
     *   - "" (Empty status)
     */
    protected function loadFDEC($instrument = '', $statuses_bypass = array()) {
        if ($instrument) {
            $exceptions = $this->getProjectSetting('forms-exceptions', $project_id);
            if ($exceptions && in_array($instrument, $exceptions)) {
                return;
            }
        }

        global $Proj;

        // Markup of required fields bullets list.
        $bullets = '';

        // Selectors to search for empty required fields.
        $req_fields_selectors = array();

        // Getting required fields from form config.
        foreach ($Proj->metadata as $field_name => $field_info) {
            if (!$field_info['field_req']) {
                continue;
            }

            // The bullets are hidden for default, since we do not know which ones will be empty.
            $field_label = filter_tags(label_decode($field_info['element_label']));
            $bullets .= '<div class="req-bullet req-bullet--' . $field_name . '" style="margin-left: 1.5em; text-indent: -1em; display: none;"> &bull; ' . $field_label . '</div>';

            $req_fields_selectors[] = '#questiontable ' . ($field_info['element_type'] == 'select' ? 'select' : 'input') . '[name="' . $field_name . '"]:visible';
        }

        // Printing required fields popup (hidden yet).
        echo '
            <div id="preemptiveReqPopup" title="Some fields are required!" style="display:none;text-align:left;">
                <p>You did not provide a value for some fields that require a value. Please enter a value for the fields on this page that are listed below.</p>
                <div style="font-size:11px; font-family: tahoma, arial; font-weight: bold; padding: 3px 0;">' . $bullets . '</div>
            </div>';

        $settings = array(
            'statusesBypass' => array_map(function($value) { return (string) $value; }, $statuses_bypass),
            'requiredFieldsSelector' => implode(',', $req_fields_selectors),
            'instrument' => $instrument,
        );

        $this->setJsSetting('fdec', $settings);
        $this->includeJs('js/fdec.js');

    }

    /**
     * Includes a local JS file.
     *
     * @param string $path
     *   The relative path to the js file.
     */
    protected function includeJs($path) {
        echo '<script src="' . $this->getUrl($path) . '"></script>';
    }

    /**
     * Sets a JS setting.
     *
     * @param string $key
     *   The setting key to be appended to the module settings object.
     * @param mixed $value
     *   The setting value.
     */
    protected function setJsSetting($key, $value) {
        echo '<script>linearDataEntryWorkflow.' . $key . ' = ' . json_encode($value) . ';</script>';
    }
}
