# Linear Data Entry Workflow

This extension forces a linear data entry workflow across REDCap forms and events. The linear workflow is enforced by removing access to any form which does not immediately follow the last completed form. In this way, if a user has not filled out the first form, they cannot procede to the second (or and subsequent) form.

This extension also facilitates form completion for users. If the project is longitudinal, then users can specify fields that should be automatically filled using the entries from the previous event.

The last major feature of this extension is addition of the action tag @DEFAULT-FROM-FIELD. This actiontag allows default values to be set for checkbox, truefalse, and textbox fields based on the value entered into a previous field.


## Functionality of Linear Workflow Extensions

### Reveal Forms In Order (RFIO) hooks

   These hooks operate under the assumption that only complete forms and the immediately ensuing non-complete form should be accessible. For this extension, complete is indicated by selecting `Completed` from the completion dropdown.

For example, if you have 3 forms, X, Y, and Z - in that order, and only form X has been completed, then the user can access form X and form Y, but not form Z. Form Z will become available after form Y is complete.

This series of hooks refines the options users have to navigate through the project.

1. The rfio\_dashboard hook ensures the record status dashboard only reveals forms that should be accessible. The hook goes through each record, and disables links to all forms that are not complete or immediately following a complete form. If there are multiple events, forms are evaluated one event at a time with the assumption that each event must be completed before any forms on the next event can be accessed.

  Continuing with the above example: if forms X, Y, and Z are designated for both a January and February event, January's form Z must be complete before the user can fill out February X.
2. The rfio\_record\_home hook performs much the same function as rfio_dashboard, but on an individual record's home page. This means that each event is evaluated separately, and the form immediately ensuing the last complete form is the last accesible form.
3. The rfio\_data\_entry hook prevents users from using the left hand sidebar links to navigate to forms that should be inaccessible.

### force_data_entry_constraints.php

This hook prevents users from marking a form as complete if the required fields are not filled in. Additionally, field verifications must be satisfied before the form can be marked `Completed`.

For example, if the field 'Age' requires a number from 0 to 99 and the user enters 'fifty' the form cannot be marked as complete. If the user attempts to mark the form complete with an empty field or unverified field, a popup window will direct them to the field(s) that need to be adjusted before they can mark the form complete.


### copy_values_from_previous_event.php

The hook reads a configurable list of field values from previous form events and writes them into the same named values as a new form is opened. Data on the old form-event is not modified. Data on the new form event can be saved as is or modified. If there is no previosu event or the previous event fields are blank, no data is copied and no error is returned.

The fields eligible for autofilling have to be preconfigured using [custom_project_settings](https://github.com/ctsit/custom_project_settings) for that project.

Sample entry for Custom Project Settings that works with the included test project, [LinearDataEntryWorkflow.xml](LinearDataEntryWorkflow.xml) is:

    [
        {
            "form_name": "current_medications",
            "fields": [
                "medication_name",
                "indication"
            ]
        },
        {
            "form_name": "rx",
            "fields": [
                "address_line_1",
                "city",
                "state"
            ]
        }
    ]

 Note: The above data should be saved into an attribute field with the name "copy_values_from_previous_event".  The value field should be valid json like that structured above.  `form_name` identifies the forms with fields that will be copied from previous events as each new form-event is created.  `fields` is an list of field names to be copied for each such form.


### @DEFAULT-FROM-FIELD Action Tag via default_from_field.php

This extension provides a new action tag named @DEFAULT-FROM-FIELD. It allows users to set up a field's initial value from an existing field _on the same form_. This is useful when using hidden fields as source for visible fields - e.g. @DEFAULT-FROM-FIELD='hidden_first_name'.

This is useful if a form field has been populated in the backend by a DET or API call. @DEFAULT cannot do this.


### Help for @DEFAULT-FROM-FIELD Action Tag via default_from_field_help.php

This extension provides help for using aand adding the `@DEFAULT-FROM-FIELD` action tag to a new field on the online editor. As this modifies a different form than one where `@DEFAULT-FROM-FIELD` is implemented, it has ot be a separate hook.


## Activating Hooks
If you are deploying these hooks using UF CTS-IT's [redcap_deployment](https://github.com/ctsit/redcap_deployment) tools ([https://github.com/ctsit/redcap_deployment](https://github.com/ctsit/redcap_deployment)), you can activate these hooks with those tools as well.  If you had an environment named `vagrant` the activation would look like this:

    MYPID=123
    fab instance:vagrant activate_hook:redcap_data_entry_form,rfio_dashboard.php,$MYPID
    fab instance:vagrant activate_hook:redcap_data_entry_form,rfio_data_entry.php,$MYPID
    fab instance:vagrant activate_hook:redcap_data_entry_form,rfio_record_home.php,$MYPID
    fab instance:vagrant activate_hook:redcap_data_entry_form,force_data_entry_constraints.php,$MYPID
    fab instance:vagrant activate_hook:redcap_data_entry_form,copy_values_from_previous_event.php,$MYPID
    fab instance:vagrant activate_hook:redcap_data_entry_form,default_from_field.php,$MYPID
    fab instance:vagrant activate_hook:redcap_every_page_top,default_from_field_help.php,$MYPID


## Deploying the hooks in other environments
These hooks are designed to be activated as redcap_data_entry_form hook functions. They are dependent on a hook framework that calls _anonymous_ PHP functions such as UF CTS-IT's [Extensible REDCap Hooks](https://github.com/ctsit/extensible-redcap-hooks) ([https://github.com/ctsit/extensible-redcap-hooks](https://github.com/ctsit/extensible-redcap-hooks)).  If you are not use such a framework, each hook will need to be edited by changing `return function($project_id)` to `function redcap_data_entry_form($project_id)`.


## Developer Notes

When using the local test environment provided by UF CTS-IT's [redcap_deployment](https://github.com/ctsit/redcap_deployment) tools ([https://github.com/ctsit/redcap_deployment](https://github.com/ctsit/redcap_deployment)), you can use the deployment tools to configure these hooks for testing in the local VM.  If clone this repo as a child of the redcap_deployment repo, you can configure from the root of the redcap_deployment repo like this:

    fab vagrant test_hook:redcap_data_entry_form,linear_data_entry_workflow/rfio_dashboard.php
    fab vagrant test_hook:redcap_data_entry_form,linear_data_entry_workflow/rfio_data_entry.php
    fab vagrant test_hook:redcap_data_entry_form,linear_data_entry_workflow/rfio_record_home.php
    fab vagrant test_hook:redcap_data_entry_form,linear_data_entry_workflow/force_data_entry_constraints.php
    fab vagrant test_hook:redcap_data_entry_form,linear_data_entry_workflow/copy_values_from_previous_event.php
    fab vagrant test_hook:redcap_data_entry_form,linear_data_entry_workflow/default_from_field.php
    fab vagrant test_hook:redcap_every_page_top,linear_data_entry_workflow/default_from_field_help.php
