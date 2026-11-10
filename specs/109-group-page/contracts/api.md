# API and MCP: Group page

Every route is in `routes/api.php` under `api.v1.`, behind `flag:groups`; the file routes also take
`flag:file-upload`. Paths start at `vaults/{vault}/groups/{group}`. A group or record of another
account, of another vault or of another group answers 404. The reference pages are
`resources/docs/api/28-group-notes.php` to `31-group-files.php`, which hold the bodies and refusals.

| Verb | Path | Name | Controller | MCP tool |
| --- | --- | --- | --- | --- |
| GET | `/notes` | `groups.notes` | `GroupNoteController@index` | `list-group-notes` |
| GET | `/notes/{note}` | `groups.notes.show` | `GroupNoteController@show` | `get-group-note` |
| POST | `/notes` | `groups.notes.create` | `GroupNoteController@create` | `create-group-note` |
| PUT | `/notes/{note}` | `groups.notes.update` | `GroupNoteController@update` | `update-group-note` |
| DELETE | `/notes/{note}` | `groups.notes.destroy` | `GroupNoteController@destroy` | `delete-group-note` |
| GET | `/life-events` | `groups.lifeEvents` | `GroupLifeEventController@index` | `list-group-life-events` |
| POST | `/life-events` | `groups.lifeEvents.create` | `GroupLifeEventController@create` | `create-group-life-event` |
| GET | `/reminders` | `groups.reminders` | `GroupReminderController@index` | `list-group-reminders` |
| GET | `/reminders/{reminder}` | `groups.reminders.show` | `GroupReminderController@show` | `get-group-reminder` |
| POST | `/reminders` | `groups.reminders.create` | `GroupReminderController@create` | `create-group-reminder` |
| PUT | `/reminders/{reminder}` | `groups.reminders.update` | `GroupReminderController@update` | `update-group-reminder` |
| DELETE | `/reminders/{reminder}` | `groups.reminders.destroy` | `GroupReminderController@destroy` | `delete-group-reminder` |
| GET | `/reminder-occurrences` | `groups.reminderOccurrences` | `GroupReminderOccurrenceController@index` | `list-group-reminder-occurrences` |
| PUT | `/reminder-occurrences/{occurrence}/answer` | `groups.reminderOccurrences.answer.update` | `GroupReminderOccurrenceAnswerController@update` | `answer-group-reminder-occurrence` |
| GET | `/files` | `groups.files` | `GroupFileController@index` | `list-group-files` |
| GET | `/files/{file}` | `groups.files.show` | `GroupFileController@show` | none |
| POST | `/files` | `groups.files.create` | `GroupFileController@create` | none (no upload over MCP) |
| DELETE | `/files/{file}` | `groups.files.destroy` | `GroupFileController@destroy` | `remove-group-file` |
| GET | `/files/{file}/content` | `groups.files.content.show` | `GroupFileContentController@show` | none |

Editing and deleting an activity go through the existing life event endpoints and tools.

## Changed shapes

- `LifeEventResource` gains `group`: `{id, name}`, or null when the life event was not recorded on a
  group or the group is deleted.
- `ReminderResource` and `ReminderOccurrenceResource` gain `group` the same way, null for a contact;
  their links point at the group routes for a group's reminder.
- `{file}` is the id of the attachment (`group_files.uuid`), not of the stored file.
