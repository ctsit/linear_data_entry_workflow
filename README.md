# Linear Data Entry Workflow and Automatic Field Population

This extension will create a linear workflow for users. The linear workflow is enforced by removing access to any form which does not immediately follow the last completed form. In this way, if a user has not filled out the first form, they cannot procede to the second (or and subsequent) form.  
This extension also facilitates form completion for users. If the project is longitudinal, then users can specify fields that sould be autmoatically filled using the entries from the previous event. If the project uses repeating forms, users may also specify fields which will be populated using the previous instance's entries.
The last major feature of this extension is addition of the action tag @RECOMMENDED\_RX. This actiontag allows default values to be set for checkbox, truefalse, and textbox fields based on the value entered into a previous field (e.g., "recommended\_rx").

## Functionality of Linear Workflow Extensions

### 1. Reveal Forms In Order (RFIO) hooks

   These hooks operate under the assumption that only complete forms and the immediately ensuing non-complete form should be accessible. For this extension, complete is indicated by selecting complete from the completion dropdown.  

For example, if you have 3 forms, X, Y, and Z - in that order, and only form X has been completed, then the user can access form Y and form Y, but not form Z. Form Z will become available after form Y is complete.   

This series of hooks refines the options users have to navigate through the project.

1. The rfio\_dashboard hook ensures the record status dashboard only reveals forms that should be accesible. The hook goes through each record, and disables links to all forms that are not complete or immediately ensuing a complete form. If there are multiple events, forms are evaluated one event at a time with the assumption that each event must be completed before any forms on the next event can be accessed.  

  Continuing with the above example: if forms X, Y, and Z are designate for both a January and February event, January's form Z must be complete before the user can fill out February X. 
2. The rfio\_record\_home hook performs much the same fucntion as rfio_dashboard, but on an individual record's home page. This means that each event is evaluated separately, and the form immediately ensuing the last complete form is the last accesible form.
3. The rfio\_data\_entry hook prevents users from using the left hand sidebar links to navigate to forms that should be inaccessible.

### 2. Force Data Entry Constraints

This hook prevents users from marking a form as complete if the required fields are not filled in. Additionally, field verifications must be satisfied before the form can be marked complete.  

For example, if the field 'Age' requires a number from 0 to 99 and the user enters 'fifty' the form cannot be marked as complete. If the user attempts to mark the form complete with an empty field or unverified field, a popup window will direct them to the field(s) that need to be adjusted before they can mark the form complete.

## Use Recommended Values

### 1. Default From Field

This hook will attempt to populate target fields based on specified trigger values from other fields. The hook will search for user specified values in the trigger field. When a user specified value is found, the target field will be autopopulated with designated values for each case. **Note**: The default values are editable, they are not permanent.

For example: Using the CPS extension, the user designates 'recommended\_drug\_name' as a trigger field and 'recommended\_drug\_code' and 'recommended\_daily\_dosage' as target fields. Then the user specifies that albuterol will correspond with a recommended drug code of 55555 and a recommnded daily dosage of 8 mg. The next time the user enters 'albuterol' into the 'recommended\_drug\_name' field, the code 55555 and dosage 8 mg will be automatically populated (by default) in the target fields.

### 2. Recommended Rx

This hook uses the same principles as the previous hook.

## Copy Values From Previous Events and Instances

### 1. Copy Values From Previous Events

This hook will automatically populate specified fields with the previous event's entry values in order to facilitate the completion of the form. 

First, using the custom project settings extention, the user specifies what fields should be filled with copied values. The attibute field will be "copy\_values\_from\_previous\_event\_hook" and the value field will be a JSON object with form names as keys and repeated fields as values. 

For example: using the custom project settings, a user enters "copy\_values\_from\_previous\_event\_hook" as the attribute field and the following in the value field:

```
value:
	"{
		"demographics: : [
			"first_name",
			"last_name"
		],
		"rx" : [
			"address_line_1",
			"city",
			"state",
			"pincode"
		]
	}"
```
In the example above, the user specified that any fields labeled "first\_name", "last\_name", "address\_line\_1", "city", "state", or "pincode" will be filled in with their values from the previous event. So if the user is entered Omaha as "city" in event 3, and they open "rx" on event 4, then "Omaha" will be the value of "city" on event 4.

### 2. Copy Values From Previous Instances

This hook uses the same preinciples as the previous hook, except that fields will be filled with the entry values from te previous instance of specified fields. Again, the user uses the custom project settings extention to specify what fields should be automatically copied form their previous instance. Just like the previous hook, this hook requires valid json to be entered fro the value field.

For example: using the custom project settings, a user enters "copy\_values\_from\_previous\_instance\_hook" as the attribute field and the following in the value field:

```
value:
	"{
		"rx" : [
			"address_line_1",
			"city",
			"state",
			"pincode"
		]
	}"
```
In the example above, the user has specified that "address\_line\_1", "city", "state", and "pincode" be copied from their previous instance. So if the user entered "Omaha" on the first instance of the rx from, the next instance would have "Omaha" filled in as the value for "city".

## Testing

Any of these hooks may be tested by copying the necesarry php file and dependancies (any scripts included by the main php file) to your local redcad_deployment directory. Then the command below can be used to temporarily deploy the hook in your instance.
`fab <instance> test_hook:<desired_recap_hook_function>,<php_path>`

**Note**: using this command, hooks will not persist if your instance is restarted. For more information on fabric commands, type `fab --list`. For more information on the redcap hook fucntions, see 

