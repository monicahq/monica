# 041. ReminderOccurrence

**Status:** exists (`app/Models/ReminderOccurrence.php`), generated, delivered and answered
**Spec:** 10.1, 10.3

## What it is

One concrete deadline produced by one cycle of a rule: "12 May 2027". It is the date the user answers, not the date the source happens to hold and not the message sent through a channel.

A yearly rule reading 12 May produces one occurrence a year. Each one can produce an in-app notification, an email and a notification on every other enabled channel, on its day only. Each one waits for the user to say whether they remembered it or forgot it, and that answer belongs to this date alone: answering the 2027 birthday leaves the 2028 birthday untouched.

## Fields

```
id                          int                             primary key
uuid                        string (36)                     unguessable identifier used by the interface and the API
account_id                  int                             account the occurrence belongs to
vault_id                    int                             vault it belongs to, copied from the rule
reminder_id                 int                             rule that produced it
local_date                  date                            day it falls on, in the zone frozen on the rule
generation_key              string (64)                     stable identity under the rule: {schedule revision}:{local date}
scheduled_for               datetime                        instant it is notified at, in UTC, never moved
notifications_created_at    datetime                        when delivery made its notification rows, null until it has
notified_at                 datetime                        when the first notification actually went out
answer                      enum (remembered, forgotten)    what the user said about this date, null without an answer, never changed once written
answered_at                 datetime                        when the answer was recorded
cancelled_at                datetime                        when it was cancelled, null while it is not
cancelled_reason            enum (rule_deleted, record_deleted, record_archived, field_removed, value_cleared, source_changed, rule_changed)  why it was cancelled, so nothing vanishes unexplained
created_at                  datetime
updated_at                  datetime
```

## Relationships

- `belongsTo` [Account](001-account-in-progress.md), [Vault](007-vault-in-progress.md)
- `belongsTo` [Reminder](040-reminder-done.md)
- `hasMany` [Notification](042-notification-done.md)

## Rules

- **Three separate facts.** The answer, the delivery and the cancellation are three columns and never one status. A notification sent or read never counts as remembered, and a cancellation never counts as forgotten.
- **Active means no answer and no cancellation.** Which group an active occurrence sits in is computed each time it is read, against today in the viewer's time zone: upcoming before `local_date`, today on it (also after the notification went out), overdue from the next day until it is answered. Nothing is stored for the group, so nothing goes stale at midnight.
- **Answering** is allowed for `remembered` from `local_date` on and for `forgotten` from the day after, through `ReminderOccurrencePolicy`. It is recorded in one action, without a confirmation, with a write conditional on the occurrence still being active, so two tabs cannot both answer. It abandons every notification not sent yet and touches no other occurrence and not the rule.
- **An answer is final.** No action undoes, changes or repeats it.
- **One notification per channel, on the day.** Delivery sweeps active occurrences whose instant has come and whose `notifications_created_at` is null, writes one notification per enabled channel, and stamps `notifications_created_at`, so the occurrence is never swept again. A notification held into the next day by quiet hours or the digest still goes out, unless the occurrence was answered or cancelled in the meantime.
- **Generation is idempotent.** `generation_key` is derived from the rule's `schedule_revision` and `local_date`; a unique index on `reminder_id, generation_key` refuses a second write, and generation also skips a day that already holds a live or answered occurrence of the rule.
- **Nothing is generated into the past.** A date whose instant has passed when the rule is made produces nothing.
- **Cancelling has a scope.** Deleting the rule, archiving or deleting the record, or clearing the value cancels every active occurrence, overdue ones included. Changing the rule or its date cancels only the active occurrences after today, and generates replacements. Answered occurrences are never cancelled.
- **Restoring** a reversible cancellation brings back only the occurrences still ahead.
- Each state change emits a webhook (14.2). **Not built.**

## Not done yet

- **No webhook.** Each state change is meant to be listenable and there is no webhook machinery in the product.
- **No retention.** Nothing prunes what has gone by. See the open question below.

## Open questions

- How long past occurrences are kept. They are the history of what the user answered, and they accumulate quickly for a yearly rule multiplied across hundreds of contacts.
