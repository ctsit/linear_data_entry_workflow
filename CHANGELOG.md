# Change Log
All notable changes to the REDCap Linear Data Entry Workflow module will be documented in this file.
This project adheres to [Semantic Versioning](http://semver.org/).


## [2.5.2] - 2019-09-01
### Changed
- document config changes in README (Kyle Chesney)
- introduce option to always hide next record button hide-next-record-button is now radio instead of checkbox (Kyle Chesney)
- remove additional parens from logic eval (Kyle Chesney)
- warn user, disable complete option with bad data reenables option once data meets standards (Kyle Chesney)


## [2.5.1] - 2018-08-13
### Changed
- Fixing regression for non-independent events. (Tiago Bember Simeao)


## [2.5.0] - 2018-08-08
### Added
- Add support for FRSL 3.2.0's event-relative control fields (Tiago Bember Simeao)
- Find last completed form looking backward from the end instead of forward from the beginning (Tiago Bember Simeao)
- Add Zenodo DOI to README (Philip Chase)


## [2.4.0] - 2018-07-19
### Added
- Added option "Allow events to be filled out independently from each other" (tbembersimeao)


## [2.3.0] - 2018-05-22
### Added
- Allow autolocking of completed forms for specified roles (Dileep)


## [2.2.0] - 2018-05-03
### Changed
- Preventing false alarms/errors on redirect. (Tiago Bember Simeao)
- Handling repeated forms. (Tiago Bember Simeao)


## [2.1.2] - 2018-03-29
### Changed
- Fixing infinite call stack error on form submit Issue #26. (Tiago Bember Simeao)


## [2.1.1] - 2018-02-07
### Changed
- Forcing submit callbacks override when form status field is changed. (Tiago Bember Simeao)
- Improving delete buttons accuracy. (Tiago Bember Simeao)
- Mention form exceptions and suppressing 'Save & Go To Next Record' in README (Philip Chase)
- Ignore hide/show buttons logic on the last instrument. (Tiago Bember Simeao)
- Bring documentation, attribution and versioning up to CTS-IT's current standards (Philip Chase)


## [2.1] - 2017-11-22
### Added
- Avoiding conflicts with Form Render Skip Logic module. (Tiago Bember Simeao)
- Adding project level configuration to set 'Save & Go To Next Record' button visibility. (Tiago Bember Simeao)
- Making hidden buttons to re-appear when form is complete. (Tiago Bember Simeao)


## [2.0.0] - 2017-08-15
### Added
- Turned into a REDCap module

### Changed
- Deny access to forbidden pages. (Tiago Bember Simeao)

### Removed
- Custom Project Settings is not required anymore


## [1.0.0] - 2017-07-25
### Added
- First Release
- This module forces a linear data entry workflow across REDCap forms and events. The linear workflow is enforced by removing access to any form which does not immediately follow the last completed form. In this way, if a user has not filled out the first form, they cannot procede to the second (or and subsequent) form.
- This module also facilitates form completion for users. If the project is longitudinal, then users can specify fields that should be automatically filled using the entries from the previous event.
- The last major feature of this module is addition of the action tag @DEFAULT-FROM-FIELD. This actiontag allows default values to be set for checkbox, truefalse, and textbox fields based on the value entered into a previous field.

