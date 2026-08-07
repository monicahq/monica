# Features

A chronological list of every feature and improvement shipped during development. Newest entries go at the end.

* Foundation: Added the company and user models.
* Foundation: Added the employee model, separate from the user account that signs in.
* Emails: Sent transactional emails and recorded what was sent to whom.
* Logs: Recorded the actions users perform.
* Auth: Added the registration screen, which creates the company and its first owner.
* Auth: Added the login screen.
* Auth: Made the guest screens accessible to screen readers and keyboard users.
* App-wide: Added Turbo, Alpine Ajax and instant.page so screens update without a full reload.
* Settings/account/profile: Added the profile screen, where somebody edits their own details.
* Auth: Added a local sign in shortcut on the login screen for development.
* Settings/account/profile: Saved the profile without reloading the page.
* App-wide: Confirmed a save with a toast instead of a banner.
* Settings/account/profile: Paired the first and last name fields on one row.
* Settings/account/profile: Let somebody upload a photo of themselves.
* Settings/account/logs: Let somebody read the log of their own actions.
* Settings/account/logs: Let somebody read the emails we sent them.
* Settings: Gave the logs and the emails sent their own screen in the settings sidebar.
* Settings/account/security: Let somebody change their own password from a security screen.
* Settings/account/security: Recorded and showed when the password was last changed.
* App-wide: Laid the application out for a phone.
* App-wide: Gave the empty lists a proper blank state.
* App-wide: Added a row component for the lists inside a box.
* Settings/account/preferences: Added a preferences screen for language and time format.
* Settings/account/security: Let somebody turn two factor authentication on from their settings.
* App-wide: Laid the rows inside a box out properly on a narrow screen.
* App-wide: Gave cancel and back actions a secondary button.
* Settings/account/security: Let somebody create and revoke their own API keys.
* Settings/account/security: Created and revoked an API key without reloading the page.
