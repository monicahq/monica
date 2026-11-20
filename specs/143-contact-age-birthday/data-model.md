# Data model: Contact age and upcoming birthday

No migration. Nothing is stored: the age and the next birthday are worked out on every read.

## Birthday (existing, `field_values`)

The value of the field whose `field_key` is `birthday`, attached to a contact
(`valuable_type = Contact`), at the top level (`field_value_row_id` null), not detached.

| Column | Use here |
| --- | --- |
| `value_date` | Day, month and year; the parts flagged unknown hold placeholders |
| `year_is_unknown` | When true: no age, countdown only |
| `month_is_unknown` | When true: no age and no countdown |
| `field.is_sensitive` | When true: hidden from an API token without `sensitive-fields` |

## `Contact::birthday()` (new relationship)

`MorphOne<FieldValue, $this>` with the conditions above. Carries conditions of its own, so it gets a
model test (`.claude/rules/testing.md`): a detached value, a value inside a repeated row and a value of
another field are not returned.

## `App\Helpers\Birthday` (new, not persisted)

Built from a `FieldValue` and `CarbonImmutable $today` (start of the reader’s day, in their zone).

| Method | Returns | Null when |
| --- | --- | --- |
| `age()` | Whole years on `$today` | year or month unknown, no date, or the date is after `$today` |
| `next()` | The next anniversary on or after `$today`; 29 February falls on 28 February in a common year | month unknown, or no date |
| `daysUntilNext()` | Days from `$today` to `next()`, 0 on the day | as `next()` |
| `isUpcoming()` | `daysUntilNext() <= ReminderOccurrenceCategoryEnum::UpcomingDays` | false rather than null |

## Window

`ReminderOccurrenceCategoryEnum::UpcomingDays = 7`. Read by `VaultDashboardAttention`,
`VaultReminderDates::DefaultDays` and `ContactViewModel`.

## State of the header line

| Age known | Days until next | Line |
| --- | --- | --- |
| yes | 0 | Turns [age] today |
| yes | 1 | [age] years old · Turns [age + 1] tomorrow |
| yes | 2 to 7 | [age] years old · Turns [age + 1] in [n] days, on [weekday] [date] |
| yes | over 7 | [age] years old |
| no (year unknown) | 0 / 1 / 2 to 7 | Birthday today / Birthday tomorrow / Birthday in [n] days, on [weekday] [date] |
| no | over 7, or unknown | absent |

On the day, `age()` already returns the new age, so "Turns [age] today" uses it as is.
