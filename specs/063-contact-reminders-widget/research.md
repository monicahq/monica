# Research: Reminder answers and the contact's reminders

## R1. Schema edited in place, rebuilt with `migrate:fresh`

The create migrations `2026_09_02_000000` to `000003` are edited, as the migrations skill asks until
production. Rejected: a transition migration (the request forbids it).

## R2. The occurrence loses `status`

`status` mixed three facts: delivery, dismissal and cancellation. Each becomes its own column, so the
answer stays apart from delivery and cancellation (FR-015).

| Old | New |
| --- | --- |
| `status = notified` (sweep made the rows) | `notifications_created_at` |
| `notified_at` (first send) | kept: the Sent tab sorts and pages on it |
| `status = dismissed`, `postponed` | gone with the actions |
| `status = cancelled`, `status_before_cancellation` | `cancelled_at` alone: restoring clears it |

Active means `answer IS NULL AND cancelled_at IS NULL`. Rejected: new `status` cases (answer and
delivery in one column again).

## R3. One deadline per cycle

`source_local_date`, the moment columns and `original_scheduled_for` go; `generation_key` becomes
`{schedule_revision}:{local_date}`, and `scheduled_for` leaves the notifications unique index.

## R4. Categories are computed, never stored

`ReminderOccurrenceCategoryEnum::for($occurrence, $today)` compares `local_date` (the rule's day, in
its frozen zone) with today in `users.timezone`. A stored category would go stale at midnight.

## R5. Answering is a policy, not only a check in the action

Screen and action decide the same thing, which `policies.md` says earns a policy:
`ReminderOccurrencePolicy::remember` and `::forget`. The write is conditional on `answer IS NULL AND
cancelled_at IS NULL`, so of two tabs one wins and the other is refused.

## R6. Cancelling takes a scope

`CancelReminderOccurrences` today touches only what is still ahead. It gains `includePast`:

true when a rule is deleted, its contact archived or deleted, or its value cleared (FR-025); false
when the rule or its date changes. `RestoreReminderOccurrences` still restores only what is ahead.

## R7. Pause goes, and so does most of reactivation

`paused_by_user`, `ReminderPauseController` and its routes go. `ReactivateReminders` stays for its
last caller, `UndoContactMerge`. `rule_disabled` becomes `rule_deleted`.

## R8. A manual reminder carries its own date

`source` gains `manual`. A manual rule (own action, `CreateContactReminder`) stores `starts_on` and leaves `field_id` and `field_value_id`
null; a field rule does the reverse, and `ReminderSchedule` anchors on whichever is set. Rejected: a
separate model for manual reminders (constitution VII: one Reminders concept).

## R9. Upcoming shows one row per rule, beyond the 90 day window

Per active rule: its first active deadline after today, else `ReminderSchedule::nextDate()` (no
`uuid`, no answer). Rejected: widening the generation window (thousands of idle rows).

## R10. The contact screen

- Route `contacts/{contact}/reminders/{filter?}` before the `{page?}` catch-all, which never sees
  `reminders` again, so no reserved page key is needed.
- Filters are `x-chip` links with `x-target.push`, like the contact list. Page shell like `set-aside`.
- The chart is inline markup (one file), `role="img"` with a label, percentages written beside it.
- The way in is a header fact, "Reminders", with the count awaiting an answer.

## R11. API and MCP

Contact scoped, resolved like `ContactFavouriteController`. A field rule can be read, edited and
deleted but not created: field values have no `uuid` (out of scope). MCP tools call the actions through
`MirrorsAnApiCapability`. Policy refusals become 422.

## R12. What else reads the old columns

CalDAV (`CalendarBackend.php:234`) lists active deadlines from today on and stops naming dismissing.
`NotifyReminderOccurrence` drops `howFarOff()`. `ExampleAccountSeeder` drops moments and adds two
manual reminders and a few answers.
