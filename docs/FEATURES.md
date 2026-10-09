# Features

A chronological list of every feature and improvement shipped during development. Newest entries go at the end.

* Foundation: Added the account and user models.
* Emails: Sent transactional emails and recorded what was sent to whom.
* Logs: Recorded the actions users perform.
* Auth: Added the registration screen, which creates the account and its first user.
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
* Foundation: Dropped companies and employees. An account holds users, and every user of an account can do the same things.
* Settings/account/profile: Let somebody change the email address they sign in with.
* Welcome: Greeted a new account once with a letter from the people behind Monica, before anything else.
* API/profile: Let an application dismiss the welcome screen and read when it was dismissed.
* Docs: Added a portal at /docs leading to every documentation area.
* Docs: Published /llms.txt, which lists the documentation as Markdown for assistants.
* Top bar: Docs leads to the documentation portal.
* Docs: Added the MCP page at /docs/mcp, with the setup for each assistant, the tools it gets, and a Markdown version.
* MCP: Let an assistant manage templates, open and publish drafts, and add, change, move and remove their sections and fields.
* API/templates: Created a new section while placing it in a draft, and refused changes to a published version as invalid instead of failing.
* Docs: Explained on /docs/mcp how an assistant edits a template through a draft.
* MCP: Let an assistant create, change, reorder, switch off and delete entity types.
* MCP: Let an assistant create, change, reorder, switch off and delete relationship types and their categories.
* Docs: Listed the entity type tools on /docs/mcp and explained what an entity type is.
* Docs: Listed the relationship type tools on /docs/mcp and explained what a relationship type is.
* MCP: Let an assistant create, change, reorder, switch off and delete life event types and their categories, and choose the fields a type asks for.
* Docs: Listed the life event type tools on /docs/mcp and explained what a life event type is.
* Admin: Register the Telegram bot, check it and generate its secret from the configuration screen.
* Fix: The Telegram address answers a token saved on the screen, and refuses a call when no secret is set.
* MCP: Let an assistant read the account's activity, a page at a time.
* MCP: Let an assistant read the emails sent to the account and what each said.
* Settings/account/logs: Stopped keeping any link or web address in the copy of an email sent.
* Docs: Listed the log tools on /docs/mcp and explained what an assistant can read in the logs.
* Example account: Added Jack and Erica Bing, Frank Buffay Jr., Ursula Buffay, Barry Farber, Nora Bing and Helena Bing to the seeded cast.
* Example account: Linked every seeded person to the others as the show does, with ended relationships marked as ended.
* Example account: Gave every seeded person at least twenty life events from the show, across every life event category.
* MCP: Let an assistant connect to the MCP server through OAuth, after the person allows it on a consent screen.
* Settings/account/security: Listed the assistants connected through OAuth and let somebody disconnect each one.
* Fix: Kept a deactivated person out of the API, CardDAV and MCP, and not only out of the sign in screen.
* Fix: Remembered somebody who ticked Remember me when two factor authentication asked them for a code.
* Contacts: Removed the Archived list, since nothing archives a contact yet.
* Docs: Counted the API rate limit per person rather than per key, and described the paginated lists that exist.
* Docs: Explained on /docs/mcp how an assistant connects through OAuth, and how to disconnect it.
* Docs: Built the OpenAPI document on every request instead of keeping the first one forever.
* Auth: Closed registration on an instance with `MONICA_REGISTRATION_ENABLED`, refusing every way of creating an account.
* Admin: Opened or closed registration from the configuration screen, with a warning before opening it.
* Fix: Warned before switching off sign in links on the configuration screen, which never asked before.
* Auth: Kept the browser signed in for the instance's remembered duration after signing in with a passkey, a link by email or a provider.
* Auth: Kept the browser signed in for the instance's remembered duration after creating an account.
* Auth: Ticked Remember me by default on the sign in form.
* Settings/security: Signed out every other device when the password is changed or reset.
* Fix: Remembered somebody who passed two factor authentication for the instance's duration rather than always 400 days.
