# Contract: web routes

All under `vaults/{vault}`, authenticated, bound models scoped by `ResolvesModelInVault`.

## Added

| Method and path | Name | Controller |
| --- | --- | --- |
| GET `contacts/{contact}/reminders/{filter?}` | `contacts.reminders.index` | `ContactReminderController@index` |
| GET `contacts/{contact}/reminders/new` | `contacts.reminders.new` | `ContactReminderController@new` |
| POST `contacts/{contact}/reminders` | `contacts.reminders.create` | `ContactReminderController@create` |
| GET `contacts/{contact}/reminders/{reminder}/edit` | `contacts.reminders.edit` | `ContactReminderController@edit` |
| PUT `contacts/{contact}/reminders/{reminder}` | `contacts.reminders.update` | `ContactReminderController@update` |
| DELETE `contacts/{contact}/reminders/{reminder}` | `contacts.reminders.destroy` | `ContactReminderController@destroy` |
| PUT `reminders/occurrences/{reminderOccurrence}/answer` | `reminders.occurrences.answer.update` | `ReminderOccurrenceAnswerController@update` |
| GET `reminders/history` | `reminders.history` | `VaultReminderController@history` |

- `{filter?}` is one of `all`, `overdue`, `today`, `upcoming`, `history`; `new` is declared first.
  All of these sit before `contacts/{contact}/{page?}` (`routes/web.php:309`).
- `answer` takes `answer` = `remembered` or `forgotten`. A policy refusal returns to the previous
  screen with the policy's wording as an error; success returns there with a flash.
- `create` and `update` take `name`, `starts_on` (manual only), `cadence`, `interval`, `time`,
  `timezone` (create only). A field rule's edit link keeps going to the date panel.
- `reminders.destroy` (existing) keeps deleting from the reminder's page; the contact screen deletes
  through `contacts.reminders.destroy`, which returns there. Both warn first.

## Removed

`reminders.occurrences.postponement.update`, `reminders.occurrences.dismissal.update`,
`reminders.pause.update`, `reminders.pause.destroy`, and their controllers.

## Changed

- `reminders.index`: groups Overdue, Today, Upcoming (by date) or by contact; rows carry the answers.
- `reminders.show`: no pause, no postpone, no moments; the next deadline card carries the answers; the
  history lists every past deadline with its answer or "No answer".
- The date panel (`SaveFieldValuesRequest`, `field-reminder`): the `moments` inputs go.
