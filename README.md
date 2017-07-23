# Linear Data Entry Workflow

This program facilitates the user to enter data in a linear work flow, forces the user to enter all the mandatory fields in a form, and aids the user by automatically filling repeated fields from previous events. This is made possible with the help of following hooks.


#### 1. force_data_entry_constraints.php

This extension prevents users from saving a record entry as Complete if it required fields are missing of data validation rules are violated.


#### 2. copy_values_from_previous_event.php

The hook reads a configurable list of field values from previous form events and writes them into the same named values as a new form is opened. Data on the old form-event is not modified. Data on the new form event can be saved as is or modified. If there is no previosu event or the previous event fields are blank, no data is copied and no error is returned.

The fields eligible for autofilling have to be preconfigured using [custom_project_settings](https://github.com/ctsit/custom_project_settings) for that project.

Sample entry for Custom Project Settings that works with the included test project, [LinearDataEntryWorkflow.xml](LinearDataEntryWorkflow.xml) is:

	attribute : "copy_values_from_previous_event",
	value : "[
				{
					'form_name':'current_medications',
					'fields':['medication_name','indication']
				},
				{
					'form_name': 'rx',
					'fields':['address', 'city', 'state']
				}

			]"

 Note: The above data should be saved into an attribute field with the name "copy_values_from_previous_event".  The value field should be valid json like that structured above.  `form_name` identifies the forms with fields that will be copied from previous events as each new form-event is created.  `fields` is an list of field names to be copied for each such form.


#### 3. @DEFAULT-FROM-FIELD Action Tag via default_from_field.php

This extension provides a new action tag named @DEFAULT-FROM-FIELD. It allows users to set up a field's initial value from an existing field _on the same form_. This is useful when using hidden fields as source for visible fields - e.g. @DEFAULT-FROM-FIELD='hidden_first_name'.

This is useful if a form field has been populated in the backend by a DET or API call. @DEFAULT cannot do this.


#### 4. Help for @DEFAULT-FROM-FIELD Action Tag via default_from_field_help.php

This extension provides help for using aand adding the `@DEFAULT-FROM-FIELD` action tag to a new field on the online editor. As this modifies a different form than one where `@DEFAULT-FROM-FIELD` is implemented, it has ot be a separate hook.


## Testing

## Activating Hooks
If you are deploying these hooks using UF CTS-IT's [redcap_deployment](https://github.com/ctsit/redcap_deployment) tools ([https://github.com/ctsit/redcap_deployment](https://github.com/ctsit/redcap_deployment)), you can activate these hooks with those tools as well.  If you had an environment named `vagrant` the activation would look like this:

	MYPID=123
	fab instance:vagrant activate_hook:redcap_data_entry_form,force_data_entry_constraints.php,$MYPID
	fab instance:vagrant activate_hook:redcap_data_entry_form,copy_values_from_previous_event.php,$MYPID
	fab instance:vagrant activate_hook:redcap_data_entry_form,default_from_field.php,$MYPID
	fab instance:vagrant activate_hook:redcap_every_page_top,default_from_field_help.php,$MYPID


## Deploying the hooks in other environments
These hooks are designed to be activated as redcap_data_entry_form hook functions. They are dependent on a hook framework that calls _anonymous_ PHP functions such as UF CTS-IT's [Extensible REDCap Hooks](https://github.com/ctsit/extensible-redcap-hooks) ([https://github.com/ctsit/extensible-redcap-hooks](https://github.com/ctsit/extensible-redcap-hooks)).  If you are not use such a framework, each hook will need to be edited by changing `return function($project_id)` to `function redcap_data_entry_form($project_id)`.


## Developer Notes

When using the local test environment provided by UF CTS-IT's [redcap_deployment](https://github.com/ctsit/redcap_deployment) tools ([https://github.com/ctsit/redcap_deployment](https://github.com/ctsit/redcap_deployment)), you can use the deployment tools to configure these hooks for testing in the local VM.  If clone this repo as a child of the redcap_deployment repo, you can configure from the root of the redcap_deployment repo like this:

	fab vagrant test_hook:redcap_data_entry_form,linear_data_entry_workflow/force_data_entry_constraints.php
	fab vagrant test_hook:redcap_data_entry_form,linear_data_entry_workflow/copy_values_from_previous_event.php
	fab vagrant test_hook:redcap_data_entry_form,linear_data_entry_workflow/default_from_field.php
	fab vagrant test_hook:redcap_every_page_top,linear_data_entry_workflow/default_from_field_help.php

