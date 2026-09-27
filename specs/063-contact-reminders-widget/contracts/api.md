# Contract: API and MCP

Under `/api/v1`, Sanctum, strings bound, `{vault}` and `{contact}` by slug or uuid, the others by uuid.
Shapes follow the existing resources (`type`, `id`, `attributes`, `links`), unix timestamps.

## Endpoints

| Method and path | Name | Answers |
| --- | --- | --- |
| GET `vaults/{vault}/contacts/{contact}/reminders` | `api.v1.contacts.reminders` | 200, paginated rules |
| POST `vaults/{vault}/contacts/{contact}/reminders` | `.create` | 201, manual rule |
| GET `.../reminders/{reminder}` | `.show` | 200 |
| PUT `.../reminders/{reminder}` | `.update` | 200 |
| DELETE `.../reminders/{reminder}` | `.destroy` | 204 |
| GET `vaults/{vault}/contacts/{contact}/reminder-occurrences` | `api.v1.contacts.reminderOccurrences` | 200, paginated |
| PUT `.../reminder-occurrences/{occurrence}/answer` | `.answer.update` | 200 |

`category=upcoming` returns unsaved dates beyond the generation window with a null `id`.

- `reminder-occurrences` takes `category` = `overdue`, `today`, `upcoming` or `history`; without it,
  every deadline newest first. `history` is every answered deadline, newest answer first.
- `answer` takes `answer`: `remembered` or `forgotten`. Refused with 422 when the policy refuses
  (too early, already answered, cancelled).
- `create` refuses a one-off whose only date has passed its send time (FR-023) with 422.

## Resources

`ReminderResource` attributes: `name`, `source`, `starts_on`, `field` (key and label, null for manual),
`cadence`, `interval`, `time`, `timezone`, `is_active`, `inactive_reason`, `next_date`.

`ReminderOccurrenceResource` attributes: `date` (local, `Y-m-d`), `scheduled_for`, `category` (null
once answered or cancelled), `answer`, `answered_at`, `cancelled_reason`, plus `reminder` link.

## MCP tools

Mirroring the endpoints one to one, in `app/Mcp/Tools`, registered on `EricaServer`:
`list-contact-reminders`, `get-contact-reminder`, `create-contact-reminder`, `update-contact-reminder`,
`delete-contact-reminder` (destructive), `list-contact-reminder-occurrences`, `answer-reminder-occurrence`. Two new resolvers on
`MirrorsAnApiCapability`: `reminder()`, `reminderOccurrence()`. The server instructions gain a
paragraph saying an answer is final and never inferred.

## Documentation

`resources/docs/api/20-reminders.php` (checked by `ApiDocsViewModelTest` and
`ApiDocsReturnFieldsTest`); a Reminders group in `McpDocsViewModel::toolGroups()`, tool count 78.
