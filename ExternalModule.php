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
        if (!$project_id) {
            return;
        }

        // Initializing settings JS variable.
        echo '<script>var linearDataEntryWorkflow = {};</script>';
        $record = null;

        switch (PAGE) {
            case 'DataEntry/record_home.php':
                if (empty($_GET['id'])) {
                    break;
                }

                $record = $_GET['id'];

            case 'DataEntry/record_status_dashboard.php':
                $location = str_replace('.php', '', str_replace('DataEntry/', '', PAGE));
                $arm = empty($_GET['arm']) ? 1 : $_GET['arm'];

                $this->loadRFIO($project_id, $location, $arm, $record);
                break;
        }
    }

    /**
     * @inheritdoc
     */
    function hook_data_entry_form($project_id, $record, $instrument, $event_id, $group_id) {
        global $Proj;

        if (!$record) {
            $record = $_GET['id'];
        }

        $this->loadRFIO($project_id, 'data_entry_form', $Proj->eventInfo[$event_id]['arm_num'], $record, $event_id, $instrument);
        $this->loadFDEC($instrument);
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
    protected function loadRFIO($project_id, $location, $arm, $record = null, $event_id = null, $instrument = null) {
        // Proj is a REDCap var used to pass information about the current project.
        global $Proj;

        // Use form names to contruct complete_status field names.
        $fields = array();
        foreach (array_keys($Proj->forms) as $form_name) {
            $fields[$form_name] = $form_name . '_complete';
        }

        $completed_forms = \REDCap::getData($project_id, 'array', $record, $fields);
        if ($record && !isset($completed_forms[$record])) {
            // Handling new record case.
            $completed_forms = array($record => array());
        }

        if (!$exceptions = $this->getProjectSetting('forms-exceptions', $project_id)) {
            $exceptions = array();
        }

        // Building forms access matrix.
        $forms_access = array();
        foreach ($completed_forms as $id => $data) {
            $forms_access[$id] = array();
            $prev_form_completed = true;

            foreach (array_keys($Proj->events[$arm]['events']) as $event) {
                $forms_access[$id][$event] = array();

                foreach ($Proj->eventsForms[$event] as $form) {
                    $forms_access[$id][$event][$form] = true;

                    if (in_array($form, $exceptions)) {
                        continue;
                    }

                    if (!$prev_form_completed) {
                        if ($id == $record && $event == $event_id && $instrument == $form) {
                            // Access denied to the current page.
                            redirect(APP_PATH_WEBROOT . 'DataEntry/record_home.php?pid=' . $project_id . '&id=' . $record . '&arm=' . $arm);
                        }

                        $forms_access[$id][$event][$form] = false;
                        continue;
                    }

                    $prev_form_completed = !empty($data[$event]) && !empty($data[$event][$fields[$form]]) && $data[$event][$fields[$form]] == 2;
                }
            }
        }

        $settings = array(
            'formsAccess' => $forms_access,
            'location' => $location,
            'instrument' => $instrument,
        );

        $this->setJsSetting('rfio', $settings);
        $this->includeJs('js/rfio.js');
    }

    /**
     * Loads FDEC (force data entry constraints) feature.
     *
     * @param string $instrument
     *   The instrument/form ID.
     * (optional) @param array $statuses_bypass
     *   An array of form statuses to bypass FDEC. Possible statuses:
     *   - 0 (Incomplete)
     *   - 1 (Unverified)
     *   - 2 (Completed)
     *   - "" (Empty status)
     */
    protected function loadFDEC($instrument, $statuses_bypass = array('', 0, 1)) {
        $exceptions = $this->getProjectSetting('forms-exceptions', $project_id);
        if ($exceptions && in_array($instrument, $exceptions)) {
            return;
        }

        global $Proj;

        // Markup of required fields bullets list.
        $bullets = '';

        // Selectors to search for empty required fields.
        $req_fields_selectors = array();

        // Getting required fields from form config.
        foreach (array_keys($Proj->forms[$instrument]['fields']) as $field_name) {
            $field_info = $Proj->metadata[$field_name];
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
