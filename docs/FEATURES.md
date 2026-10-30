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
* Hosting: Let whoever runs the instance name the queues behind the high, default and low priorities.
* Auth: Kept the browser signed in for the instance's remembered duration after signing in with a passkey, a link by email or a provider.
* Auth: Kept the browser signed in for the instance's remembered duration after creating an account.
* Auth: Ticked Remember me by default on the sign in form.
* Settings/security: Signed out every other device when the password is changed or reset.
* Fix: Remembered somebody who passed two factor authentication for the instance's duration rather than always 400 days.
* Admin/flags: Put CardDAV and CalDAV synchronisation behind the carddav flag, off for every account until switched on.
* Admin/flags: Put the vCard download behind the vcard-export flag, off for every account until switched on.
* Admin/flags: Put text message notifications behind the sms-notifications flag, off for every account until switched on.
* Admin/flags: Put every file upload behind the file-upload flag, off for every account until switched on.
* Admin/flags: Added the mcp-server flag, on for every account, so the administrator can withdraw the MCP server.
* Settings: Told an account holding a phone number, a connected assistant or an uploaded file when its capability is withdrawn, and that its data is kept.
* Docs: Left the CardDAV, MCP and vCard documentation out while the capability is switched off for every account.
* Fix: Saved a contact from a device whose card holds an email address or a phone number, instead of failing.
* CardDAV: Saved every email address, phone number and address a device sends, in the order of the card.
* CardDAV: Accepted vCard 2.1 cards and cards written slightly outside the standard, repaired rather than refused.
* CardDAV: Kept a line of a card that cannot be read and handed it back to the device unchanged.
* CardDAV: Named a contact sent with no name after its organisation, nickname, email address or phone number.
* Templates: Let the default contact template of a new account hold several addresses.
* CardDAV: Stopped writing the name of the field as the label of an email address, phone number, address or website that has none.
* Forms: Said why a form was refused when the page had expired, the same request came too often, or the action was not allowed, instead of reloading the page silently.
* Docs: Opened the Markdown version of a page with one request instead of two.
* MCP: Recorded every tool call an assistant makes, with its real result, including reads, refusals, invalid parameters, errors and calls that never finished.
* MCP: Masked passwords, tokens and the values of sensitive fields before a call is recorded, and capped the size of what is kept.
* Settings/security/MCP history: Added a page listing every call assistants made, grouped by day, each one unfolding into its parameters and response.
* Settings/security/MCP history: Added filters by period, connection, result and tool.
* Settings/security/MCP history: Kept calls for 90 days, a period the instance can change, then removed them automatically.
* API: Added endpoints to list and read the MCP history.
* UI: Loaded the next page in advance while the pointer rests on its link, and left page changes to the browser again rather than to Turbo.
* UI: Changed pages without a full reload for most links inside the app, loading the next page while the pointer rests on its link.
* Settings/account/notifications: Let somebody remove the phone number of the text message channel, after a warning, so it is erased and nothing more is sent to it.
* Settings/account/notifications: Let somebody disconnect Telegram, after a warning, so the conversation is forgotten and a connection still in progress stops working.
* Settings/account/notifications: Let somebody ask for another text message code, once a minute and up to the limit, with the reason shown when it cannot be sent yet.
* Settings/account/notifications: Let somebody remove a phone number kept while text messages are unavailable on their account.
* API: Added an endpoint to remove the setup of a notification channel, and said on every channel whether another code may be sent.
* Settings and vaults: Drew every section that deletes what a screen is about (a vault, a contact, a template, a type) as a red box with a solid red button, the same on every screen.
* Settings and vaults: Kept a deletion that is refused in its red box, its button dimmed with the reason beside it.
* Settings and vaults: Showed every delete button in a list or inside a section in red text, where most of them had been drawn in black.
* UI: Made the label of solid red buttons readable in the dark theme.
* Settings/account/security: Listed every device signed in to the account, with the one in hand marked.
* Settings/account/security: Let somebody sign out any other device, which also ends remembered sign in on every device.
* Settings/account/security: Let somebody with a password sign out every device but the one in hand at once, remembered ones included.
* Vaults/contacts: Let somebody add a new category to a contact while editing it, created only when the contact is saved.
* Vaults/contacts: Picked the existing category when a typed one matches it whatever its case, and refused one that is no longer offered.
* Vaults/people: Let somebody narrow the people of a vault to one or more categories, with how many people hold each.
* CardDAV: Synchronised a contact's categories with devices both ways through the vCard CATEGORIES property.
* Settings/account/imports: Imported Monica 4 tags as categories rather than as a note, and removed the ones an import added when it is rolled back.
* API: Added an endpoint to save a section of a contact, which can add new categories as it saves.
* API: Added an endpoint listing the categories held in a vault, and a categories filter on the list of contacts.
* API: Returned the categories a contact holds with every contact.
* MCP: Let the contact list tool narrow the people of a vault to some categories.
