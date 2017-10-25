# Change Log
All notable changes to the REDCap Linear Data Entry Workflow module will be documented in this file.
This project adheres to [Semantic Versioning](http://semver.org/).

## [1.0.0] - 2017-07-25
### Added
- First Release
- This module forces a linear data entry workflow across REDCap forms and events. The linear workflow is enforced by removing access to any form which does not immediately follow the last completed form. In this way, if a user has not filled out the first form, they cannot procede to the second (or and subsequent) form.
- This module also facilitates form completion for users. If the project is longitudinal, then users can specify fields that should be automatically filled using the entries from the previous event.
- The last major feature of this module is addition of the action tag @DEFAULT-FROM-FIELD. This actiontag allows default values to be set for checkbox, truefalse, and textbox fields based on the value entered into a previous field.

## [2.0.0] - 2017-08-15
- Turned into a REDCap module
- Custom Project Settings is not required anymore

### Changed
- Small bugfixes. (Tiago Bember Simeao)
- Bringing complexity to the backend and denying access to non allowed pages. (Tiago Bember Simeao)
- Refactoring Linear Data Entry Workflow. (Tiago Bember Simeao)
- Managing conflicts with "Add exception list feature" pull request (Tiago Bember Simeao)
- Remaming .inc files to .php. (Tiago Bember Simeao)
- Changing version to 2.0.0. (Tiago Bember Simeao)
- Converting XMan extension into a REDCap module. (Tiago Bember Simeao)
- Replacing authors with project URL on XMan table. (Tiago Bember Simeao)
- Turning this project as a XMan extension. (Tiago Bember Simeao)
