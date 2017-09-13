# Linear Data Entry Workflow

<<<<<<< HEAD
This extension forces a linear data entry workflow across REDCap forms and events. The linear workflow is enforced by removing access to any form which does not immediately follow the last completed form. In this way, if a user has not filled out the first form, they cannot proceed to the second (or and subsequent) form.
=======
This module forces a linear data entry workflow across REDCap forms and events. The linear workflow is enforced by removing access to any form which does not immediately follow the last completed form. In this way, if a user has not filled out the first form, they cannot procede to the second (or and subsequent) form.
>>>>>>> 4903041d1e0d6f53c4ef31f0302c61c8cfa26888

This module also facilitates form completion for users. If the project is longitudinal, then users can specify fields that should be automatically filled using the entries from the previous event.

The last major feature of this module is addition of the action tag `@DEFAULT-FROM-FIELD`. This actiontag allows default values to be set for checkbox, truefalse, and textbox fields based on the value entered into a previous field.

## Prerequisites
- [REDCap Modules](https://github.com/vanderbilt/redcap-external-modules)


## Installation
- Clone this repo into to `<redcap-root>/modules/linear_data_entry_workflow_v2.0.0`.
- Go to **Control Center > Manage External Modules** and enable Linear Data Entry Workflow.
- For each project you want to use this module, go to the project home page, click on **Manage External Modules** link, and then enable Linear Data Entry Workflow for that project.

<<<<<<< HEAD
1. The rfio\_dashboard hook ensures the record status dashboard only reveals forms that should be accessible. The hook goes through each record, and disables links to all forms that are not complete or immediately following a complete form. If there are multiple events, forms are evaluated one event at a time with the assumption that each event must be completed before any forms on the next event can be accessed.

  Continuing with the above example: if forms X, Y, and Z are designated for both a January and February event, January's form Z must be complete before the user can fill out February X.
2. The rfio\_record\_home hook performs much the same function as rfio_dashboard, but on an individual record's home page. This means that each event is evaluated separately, and the form immediately ensuing the last complete form is the last accessible form.
3. The rfio\_data\_entry hook prevents users from using the left hand sidebar links to navigate to forms that should be inaccessible.

These hooks can be preconfigured to ignore certain forms by using [custom_project_settings](https://github.com/ctsit/custom_project_settings).

Here is a sample JSON that can be used for these hooks:

  [
    "lab_results",
    "end_survey"
  ]

 Note: The above data should be saved into an attribute field with the name "rfio_hooks".  The value field should be valid json like that structured above. The values inside the JSON above are the names of the forms you would like to rfio_hooks to ignore.

### force_data_entry_constraints.php

This hook prevents users from marking a form as complete if the required fields are not filled in. Additionally, field verifications must be satisfied before the form can be marked `Completed`.

For example, if the field 'Age' requires a number from 0 to 99 and the user enters 'fifty' the form cannot be marked as complete. If the user attempts to mark the form complete with an empty field or unverified field, a popup window will direct them to the field(s) that need to be adjusted before they can mark the form complete.
=======
>>>>>>> 4903041d1e0d6f53c4ef31f0302c61c8cfa26888

## Functionality of Linear Workflow Extensions

<<<<<<< HEAD
### copy_values_from_previous_event.php

The hook reads a configurable list of field values from previous form events and writes them into the same named values as a new form is opened. Data on the old form-event is not modified. Data on the new form event can be saved as is or modified. If there is no previous event or the previous event fields are blank, no data is copied and no error is returned.

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
                "state",
                "postal_code"
            ]
        }
    ]

 Note: The above data should be saved into an attribute field with the name "copy_values_from_previous_event".  The value field should be valid json like that structured above.  `form_name` identifies the forms with fields that will be copied from previous events as each new form-event is created.  `fields` is an list of field names to be copied for each such form.

=======
### Reveal Forms In Order (RFIO)
>>>>>>> 4903041d1e0d6f53c4ef31f0302c61c8cfa26888

This feature operate under the assumption that only complete forms and the immediately ensuing non-complete form should be accessible. For this module, complete is indicated by selecting `Completed` from the completion dropdown.

For example, if you have 3 forms, X, Y, and Z - in that order, and only form X has been completed, then the user can access form X and form Y, but not form Z. Form Z will become available after form Y is complete.

This feature is split in 3 parts:

1. **RFIO Dashboard** ensures the record status dashboard only reveals forms that should be accessible. It goes through each record, and disables links to all forms that are not complete or immediately following a complete form. If there are multiple events, forms are evaluated one event at a time with the assumption that each event must be completed before any forms on the next event can be accessed.

Continuing with the above example: if forms X, Y, and Z are designated for both a January and February event, January's form Z must be complete before the user can fill out February X.

2. The **RFIO Record Home** performs much the same function as RFIO Dashboard, but on an individual record's home page. This means that each event is evaluated separately, and the form immediately ensuing the last complete form is the last accesible form.
3. The **RFIO Data Entry** prevents users from using the left hand sidebar links to navigate to forms that should be inaccessible.

### Force Data Entry Constraints

This feature prevents users from marking a form as complete if the required fields are not filled in. Additionally, field verifications must be satisfied before the form can be marked `Completed`.

For example, if the field 'Age' requires a number from 0 to 99 and the user enters 'fifty' the form cannot be marked as complete. If the user attempts to mark the form complete with an empty field or unverified field, a popup window will direct them to the field(s) that need to be adjusted before they can mark the form complete.


### Copy Values From Previous Event

The feature reads a configurable list of field values from previous form events and writes them into the same named values as a new form is opened. Data on the old form-event is not modified. Data on the new form event can be saved as is or modified. If there is no previous event or the previous event fields are blank, no data is copied and no error is returned.

To configure the fields list, go to **Manage External Modules** section of your project, and then click on Linear Data Entry Workflow's configure button.

### @DEFAULT-FROM-FIELD Action Tag

This module provides a new action tag named `@DEFAULT-FROM-FIELD`. It allows users to set up a field's initial value from an existing field _on the same form_. This is useful when:
- Using hidden fields as source for visible fields - e.g. `@DEFAULT-FROM-FIELD='hidden_first_name'`.
- If a form field has been populated in the backend by a DET or API call. `@DEFAULT` cannot do this.
