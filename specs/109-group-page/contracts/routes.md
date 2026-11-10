# Routes: Group page

Every route is in `routes/web.php`, inside the signed-in group, behind `flag:groups`. The file
routes also take `flag:file-upload`. `{group}` binds by slug as today; the controller checks the
vault with `abort_unless(..., 404)`.

## Screens

| Method | URL | Name | Answers |
| --- | --- | --- | --- |
| GET | `vaults/{vault}/groups/{group}` | `groups.show` | Overview tab (existing route, new screen) |
| GET | `vaults/{vault}/groups/{group}/members` | `groups.members.index` | Members tab, paginated as today |
| GET | `vaults/{vault}/groups/{group}/files` | `groups.files.index` | Files tab |

## Writes

| Method | URL | Name | Action | Back to |
| --- | --- | --- | --- | --- |
| POST | `…/groups/{group}/life-events` | `groups.lifeEvents.create` | `CreateLifeEvent` (R1) | Overview, entry marked as just added |
| POST | `…/groups/{group}/notes` | `groups.notes.create` | `CreateGroupNote` | Overview |
| GET | `…/groups/{group}/notes/{groupNote}/edit` | `groups.notes.edit` | | Overview, note in edit mode |
| PUT | `…/groups/{group}/notes/{groupNote}` | `groups.notes.update` | `UpdateGroupNote` | Overview |
| DELETE | `…/groups/{group}/notes/{groupNote}` | `groups.notes.destroy` | `DestroyGroupNote` | Overview |
| GET | `…/groups/{group}/reminders/new` | `groups.reminders.new` | | The reminder form, as for a contact |
| POST | `…/groups/{group}/reminders` | `groups.reminders.create` | `CreateManualReminder` | Overview |
| GET | `…/groups/{group}/reminders/{reminder}/edit` | `groups.reminders.edit` | | The reminder form, as for a contact |
| PUT | `…/groups/{group}/reminders/{reminder}` | `groups.reminders.update` | `UpdateReminder` | Overview |
| DELETE | `…/groups/{group}/reminders/{reminder}` | `groups.reminders.destroy` | `DestroyReminder` | Overview |
| POST | `…/groups/{group}/files` | `groups.files.create` | `AddGroupFile` | the page it was sent from |
| DELETE | `…/groups/{group}/files/{groupFile}` | `groups.files.destroy` | `RemoveGroupFile` | Files tab |

Ticking a reminder in the panel uses the existing `reminders.occurrences.answer.update`. Downloading
a file uses the existing `files.show`. Editing an activity uses the existing
`contacts.lifeEvents.edit` (R3).

## Inline validation

| Route | Fields |
| --- | --- |
| `groups.lifeEvents.create` | `life_event_type_id` required integer; `happened_on` required date; `title` nullable string max 255; `narrative` nullable string max 65535; `participants` required array, min 1, `participants.*` integer distinct |
| `groups.notes.*` | `body` required string max 65535 |
| `groups.reminders.*` | the fields `contacts.reminders.*` validates today |
| `groups.files.create` | `file` required file, within the upload size the account allows |

A participant who is not a contact of the vault answers not found. A refusal from an action reaches
the form as an error on the field it names.

## Wording that reaches the reader

Flash after an activity: the existing `:type, :date, is on the timeline of :names.` Flash after a
note: "The note is saved on the group." Counted phrases: `:count person belongs to it|:count people
belong to it`, `:count of :total was there|:count of :total were there`, `At :count activity|At
:count activities`.
