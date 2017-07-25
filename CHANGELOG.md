# Change Log
All notable changes to the REDCap Linear Data Entry Workflow extension will be documented in this file.
This project adheres to [Semantic Versioning](http://semver.org/).

## [1.0.0] - 2017-07-25
### Added
- First Release
- This extension forces a linear data entry workflow across REDCap forms and events. The linear workflow is enforced by removing access to any form which does not immediately follow the last completed form. In this way, if a user has not filled out the first form, they cannot procede to the second (or and subsequent) form.
- This extension also facilitates form completion for users. If the project is longitudinal, then users can specify fields that should be automatically filled using the entries from the previous event.
- The last major feature of this extension is addition of the action tag @DEFAULT-FROM-FIELD. This actiontag allows default values to be set for checkbox, truefalse, and textbox fields based on the value entered into a previous field.
