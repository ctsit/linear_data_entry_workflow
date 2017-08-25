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
        include_once 'includes/rfio_dashboard.php';
        include_once 'includes/rfio_record_home.php';
        include_once 'includes/default_from_field.php';
        include_once 'includes/force_data_entry_constraints.php';

        print '<script src="' . $this->getUrl('js/default-from-field-helper.js') . '"></script>';

        $exceptions = $this->getProjectSetting('rfio-exceptions', $project_id);
        linear_data_entry_workflow_rfio_dashboard($project_id, $exceptions);
        linear_data_entry_workflow_rfio_record_home($project_id, $exceptions);
        linear_data_entry_workflow_default_from_field();
        linear_data_entry_workflow_force_data_entry_constraints();
    }

    /**
     * @inheritdoc
     */
    function hook_data_entry_form($project_id, $record, $instrument, $event_id, $group_id) {
        include_once 'includes/rfio_data_entry.php';
        linear_data_entry_workflow_rfio_data_entry($project_id, $record, $this->getProjectSetting('rfio-exceptions', $project_id));

        if (!($form_names = $this->getProjectSetting('form-name', $project_id)) || !in_array($instrument, $form_names)) {
            return;
        }

        $fields = $this->getProjectSetting('fields', $project_id);
        if (count($fields) != count($form_names)) {
            return;
        }

        include_once 'includes/copy_values_from_previous_event.php';

        $fields = array_combine($form_names, $fields);
        linear_data_entry_workflow_copy_values_from_previous_event($project_id, $event_id, $fields[$instrument]);
    }
}
