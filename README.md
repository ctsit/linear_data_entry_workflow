# Linear Data Entry Workflow

This program facilitate the user to enter data in a linear work flow, and forces the user to enter all the mandatory fields in a form and also aids the user by auto fill repeated fields(like name, address from previous events). This is made possible with the help of following hooks.

1. 

2.

3. Copy values from Previous Event Hook:
While entering the data in the redcap, as soon as the user opens a event, values for repeated fields like name, address etc are fetched from redcap apis and automatically filled in the form. If the redcap apis do not return any values then nothing will be filled. These autofilled fields can also be edited, so that if the users wishes to enter a new value, he or she can enter a new value and save the record. Then the record is saved with the new values and in this process the field values for previous events is not modified. Now, if the user enters a new event, where the repeated fields are present, the hook fetches the newly saved values for those fields for that record.
The fields which are eligible for autofilling has to be preconfigured using custom_project_settings for that project.

Sample entry for Custom Project Settings is:

```
attribute : "copy_values_from_previous_event_hook",
value : "{
	"demographics" : [
		"fist_name",
		"last_name"
	],
	"rx" : [
		"address_line_1",
		"city",
		"state",
		"pincode"
	]
}"
'''
Make sure the attribute field is "copy_values_from_previous_event_hook" and value field should be a valid json with keys being the form names not form titles and values is list of field names that needs to be auto filled by the Copy Values form Previous Event Hook.



## Testing

## Activating Hooks
If you are deploying these hooks using UF CTS-IT's [redcap_deployment](https://github.com/ctsit/redcap_deployment) tools ([https://github.com/ctsit/redcap_deployment](https://github.com/ctsit/redcap_deployment)), you can activate these hooks with those tools as well.  If you had an environment named `vagrant` the activation would look like this:

```


fab instance:vagrant activate_hook:redcap_data_entry_form,linear_data_entry_workflow/copy_values_from_previous_event.php
```

## Deploying the hooks in other environments
These hooks are designed to be activated as redcap_data_entry_form hook functions. They are dependent on a hook framework that calls _anonymous_ PHP functions such as UF CTS-IT's [Extensible REDCap Hooks](https://github.com/ctsit/extensible-redcap-hooks) ([https://github.com/ctsit/extensible-redcap-hooks](https://github.com/ctsit/extensible-redcap-hooks)).  If you are not use such a framework, each hook will need to be edited by changing `return function($project_id)` to `function redcap_data_entry_form($project_id)`.


## Customizing the hooks in other projects



## Developer Notes

When using the local test environment provided by UF CTS-IT's [redcap_deployment](https://github.com/ctsit/redcap_deployment) tools ([https://github.com/ctsit/redcap_deployment](https://github.com/ctsit/redcap_deployment)), you can use the deployment tools to configure these hooks for testing in the local VM.  If clone this repo as a child of the redcap_deployment repo, you can configure from the root of the redcap_deployment repo like this:

```


fab instance:vagrant test_hook:redcap_data_entry_form,linear_data_entry_workflow/copy_values.php
```