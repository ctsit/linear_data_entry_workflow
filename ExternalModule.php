<?php
/**
 * @file
 * Provides ExternalModule class for Linear Data Entry Workflow.
 */

namespace LinearDataEntryWorkflow\ExternalModule;

use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;
use REDCap;

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

                $this->loadRFIO($location, $arm, $record);
                break;
        }
    }

    /**
     * @inheritdoc
     */
    function hook_data_entry_form($project_id, $record = null, $instrument, $event_id, $group_id = null) {
        global $Proj;

        if (!$record) {
            $record = $_GET['id'];
        }

        if ($this->loadRFIO('data_entry_form', $Proj->eventInfo[$event_id]['arm_num'], $record, $event_id, $instrument)) {
            $this->loadFDEC($instrument);
            $this->loadAutoLock($instrument);
        }
    }

    /**
     * Loads RFIO (review fields in order) feature.
     *
     * @param string $location
     *   The location to apply RFIO. Can be:
     *   - data_entry_form
     *   - record_home
     *   - record_status_dashboard
     * @param string $arm
     *   The arm name.
     * @param int $record
     *   The data entry record ID.
     * @param int $event_id
     *   The event ID. Only required when $location = "data_entry_form".
     * @param string $instrument
     *   The form/instrument name.
     *
     * @return bool
     *   TRUE if the current user has access to the current form;
     *   FALSE if the user is going to be redirected out the page.
     */
    protected function loadRFIO($location, $arm, $record = null, $event_id = null, $instrument = null) {
        // Proj is a REDCap var used to pass information about the current project.
        global $Proj;

        // Use form names to contruct complete_status field names.
        $fields = array();
        foreach (array_keys($Proj->forms) as $form_name) {
            $fields[$form_name] = $form_name . '_complete';
        }

        $completed_forms = REDCap::getData($Proj->project_id, 'array', $record, $fields);
        if ($record && !isset($completed_forms[$record])) {
            // Handling new record case.
            $completed_forms = array($record => array());
        }

        if (!$exceptions = $this->getProjectSetting('forms-exceptions', $Proj->project_id)) {
            $exceptions = array();
        }

        // Handling possible conflicts with CTSIT's Form Render Skip Logic.
        $prefix = 'form_render_skip_logic';
        $enabled_modules = ExternalModules::getEnabledModules($Proj->project_id);
        if (isset($enabled_modules[$prefix])) {
            $frsl = ExternalModules::getModuleInstance($prefix, $enabled_modules[$prefix]);
            $frsl_forms_access = $frsl->getFormsAccessMatrix($arm, $record);
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

                    if (isset($frsl_forms_access) && !$frsl_forms_access[$id][$event][$form]) {
                        continue;
                    }

                    if (!$prev_form_completed) {
                        if ($id == $record && $event == $event_id && $instrument == $form) {
                            // Access denied to the current page.
                            $this->redirect(APP_PATH_WEBROOT . 'DataEntry/record_home.php?pid=' . $Proj->project_id . '&id=' . $record . '&arm=' . $arm);
                            return false;
                        }

                        $forms_access[$id][$event][$form] = false;
                        continue;
                    }

                    if (empty($data['repeat_instances'][$event][$form])) {
                        $prev_form_completed = !empty($data[$event][$fields[$form]]) && $data[$event][$fields[$form]] == 2;
                        continue;
                    }

                    // Repeat instances case.
                    foreach ($data['repeat_instances'][$event][$form] as $instance) {
                        if (empty($instance[$fields[$form]]) || $instance[$fields[$form]] != 2) {
                            // Block access to next instrument if an instance is
                            // not completed.
                            $prev_form_completed = false;
                            break;
                        }
                    }
                }
            }
        }

        $settings = array(
            'formsAccess' => $forms_access,
            'location' => $location,
            'instrument' => $instrument,
            'isException' => in_array($instrument, $exceptions),
            'forceButtonsDisplay' => $Proj->lastFormName == $instrument ? 'show' : false,
            'hideNextRecordButton' => $this->getProjectSetting('hide-next-record-button', $Proj->project_id),
        );

        if (!$settings['forceButtonsDisplay']) {
            $i = array_search($instrument, $Proj->eventsForms[$event_id]);
            $next_form = $Proj->eventsForms[$event_id][$i + 1];

            if (in_array($next_form, $exceptions)) {
                // Handling the case where the next form is an exception,
                // so we need to show the buttons no matter the form status.
                $settings['forceButtonsDisplay'] = 'show';
            }
            elseif ($settings['isException']) {
                // Handling 2 cases for exception forms:
                // - Case A: the next form is not accessible, so we need to keep
                //   the buttons hidden, no matter if form gets shifted to
                //   Complete status.
                // - Case B: the next form is accessible, so we need to keep the
                //   buttons visible, no matter if form gets shifted to a non
                //   Completed status.
                $settings['forceButtonsDisplay'] = $forms_access[$record][$event_id][$next_form] ? 'show' : 'hide';
            }
        }

        $this->setJsSetting('rfio', $settings);
        $this->includeJs('js/rfio.js');

        return true;
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
     * Loads auto-lock feature.
     */
    protected function loadAutoLock($instrument) {
      global $user_rights;
      global $Proj;

      //get list of exceptions
      if (!$exceptions = $this->getProjectSetting('forms-exceptions', $Proj->project_id)) {
          $exceptions = array();
      }

      //if current form is in the exception list then disable auto-locking
      if (in_array($instrument, $exceptions)) {
        return;
      }

      //get list of roles to enforce auto-locking on
      $roles_to_lock = $this->getProjectSetting("auto-locked-roles", $Proj->project_id);

      //load auto-lock script if user is in an auto-locked role
      if (in_array($user_rights["role_id"], $roles_to_lock)) {
        $this->includeJs("js/auto-lock.js");
      }
    }

    /**
     * Redirects user to the given URL.
     *
     * This function basically replicates redirect() function, but since EM
     * throws an error when an exit() is called, we need to adapt it to the
     * EM way of exiting.
     */
    protected function redirect($url) {
        if (headers_sent()) {
            // If contents already output, use javascript to redirect instead.
            echo '<script>window.location.href="' . $url . '";</script>';
        }
        else {
            // Redirect using PHP.
            header('Location: ' . $url);
        }

        $this->exitAfterHook();
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
