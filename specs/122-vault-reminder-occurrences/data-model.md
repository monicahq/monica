# Data Model: Vault-wide reminder occurrences

No table changes. The feature reads existing rows and adds one value object.

## VaultReminderDate (value object, `app/Helpers/VaultReminderDate.php`)

One row of the vault's dates.

| Property | Type | Meaning |
| --- | --- | --- |
| `occurrence` | `ReminderOccurrence\|null` | The occurrence, persisted or computed. Null for a birthday without a reminder. |
| `subject` | `Contact\|Group\|Entity\|null` | Who or what the date concerns. Null only when the rule's record is gone. |
| `date` | `CarbonImmutable` | The local date it falls on. |
| `age` | `int\|null` | The age reached that day: birthdays only, year known, field not hidden. |

A row without an occurrence is a birthday without a reminder: category today or upcoming, never
answered, never in history.

## Existing rows read

- `reminders`: `vault_id`, `account_id`, `is_active`, `deleted_at`, `remindable_*`, `field_value_id`.
- `reminder_occurrences`: `vault_id`, `account_id`, `local_date`, `answer`, `answered_at`, `cancelled_at`.
- `field_values`: `field_key = 'birthday'`, `valuable_type = Contact`, `is_detached`, `field_value_row_id`,
  `value_date`, `year_is_unknown`, `month_is_unknown`.
- `fields.is_sensitive`, through `field_values.field_id`.
- `contacts`: `vault_id`, `is_archived`, `is_partial`.

## Validation

| Parameter | Rule |
| --- | --- |
| `category` | nullable, one of `overdue`, `today`, `upcoming`, `history` |
| `kind` | nullable, one of `all`, `birthdays` |
| `days` | nullable, integer, 1 to 365 |
| `page`, `per_page` | as every list endpoint |
