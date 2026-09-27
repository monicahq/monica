# Data model: Reminder answers

Edited in the create migrations ([R1](research.md#r1-schema-edited-in-place-rebuilt-with-migratefresh)); only changes are listed.

## `reminders`

| Change | Column |
| --- | --- |
| drop | `moments` |
| add | `starts_on` date, nullable: first date of a manual rule, null for a field rule |
| nullable | `field_id` (null for a manual rule; `field_value_id` already is) |
| widen | `source`: `manual`, `field_value` |
| narrow | `inactive_reason`: `value_cleared`, `record_archived`, `record_deleted`, `field_removed` |

Rules: exactly one of `starts_on` and `field_id` is set, by source. `remindable` is a contact.

## `reminder_occurrences`

```
id, uuid, account_id, vault_id, reminder_id      unchanged
local_date                 date       the deadline's day, in the rule's frozen zone
generation_key             string(64) {schedule_revision}:{local_date}, unique with reminder_id
scheduled_for              timestamp  local_date at the rule's time, in UTC; never moves
notifications_created_at   timestamp  when the sweep made its notification rows, null before
notified_at                timestamp  first notification actually sent, for the Sent tab (kept)
answer                     string(16) remembered, forgotten, or null
answered_at                timestamp  when the answer was recorded, null without one
cancelled_at               timestamp  null while not cancelled
cancelled_reason           string(32) rule_deleted, record_archived, record_deleted, field_removed,
                                      value_cleared, source_changed, rule_changed
timestamps
```

Dropped: `source_local_date`, the three `moment_*`, `original_scheduled_for`, `status`, `postponed_to`,
`postponed_count`, `dismissed_at`, `status_before_cancellation`.

Indexes: `[reminder_id, generation_key]` unique, `[vault_id, local_date]`, `[scheduled_for, notifications_created_at]`, `[vault_id, notified_at]`.

States, derived rather than stored:

| State | Condition |
| --- | --- |
| active | `answer` null and `cancelled_at` null |
| Upcoming, Today, Overdue | active, and `local_date` after, equal to, before today ([R4](research.md#r4-categories-are-computed-never-stored)) |
| answered | `answer` set; never changes again |
| cancelled | `cancelled_at` set; never answerable, never Overdue |

Due for the sweep: active, `notifications_created_at` null, `scheduled_for <= now`.

## `notifications`

Unique index becomes `[notifiable_type, notifiable_id, channel, user_id]`, one row per deadline and channel.

## Enums

- New `ReminderAnswerEnum`: `Remembered`, `Forgotten`.
- New `ReminderOccurrenceCategoryEnum`: `Upcoming`, `Today`, `Overdue`, with `for()`.
- `ReminderSourceEnum` gains `Manual`. `UserActionEnum` gains `ReminderOccurrenceRemembered` and `ReminderOccurrenceForgotten`.
- Deleted: `ReminderOccurrenceStatusEnum`, `ReminderMomentDirectionEnum`, `ReminderMomentUnitEnum`,
  `ReminderPostponePresetEnum`; `PausedByUser`; `ReminderOccurrencePostponement` and
  `ReminderOccurrenceDismissal` in `UserActionEnum`.
