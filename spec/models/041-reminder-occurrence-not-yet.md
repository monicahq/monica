# 041. ReminderOccurrence

**Status:** to create
**Spec:** 10.1, 10.3

## What it is

One concrete deadline produced by one moment in one cycle of a rule: "5 May 2027 at 9am". It is the interruption the user acts on, not the date the source happens to hold and not the message sent through a channel.

The distinction is exact. A yearly rule reading 12 May with moments seven days before and on the day has one 2027 cycle anchored on 12 May and produces two occurrences: 5 May and 12 May. Each occurrence can produce an in-app notification, an email and a push notification. The two moments do not become two notifications attached to one occurrence; they become two occurrences, and the channels become notifications beneath each one.

This is what gives the actions in section 10.3 their proper boundary. Postponing the 5 May occurrence touches neither the 12 May occurrence in the same cycle nor the occurrences generated for 2028. The source date remains 12 May throughout.

Section 10.3 turns that into three distinct actions, and the distinction is a product decision worth stating in full because getting it wrong is the classic bug in this kind of feature.

- **Postpone** moves this deadline only and leaves the following ones alone.
- **Skip** makes this deadline go away without disabling the rule. Skipping this year's birthday does not remove next year's.
- **Complete** means the thing was done.
- **Disabling the rule** is the only action on the rule itself that cancels every future deadline, and it lives on [Reminder](040-reminder-in-progress.md).

## Fields

```
id                          int                             primary key
uuid                        string (36)                     unguessable identifier used by the interface and the API
account_id                  int                             account the occurrence belongs to
vault_id                    int                             vault it belongs to, copied from the rule
reminder_id                 int                             rule that produced it
source_local_date           date                            local date of the cadence cycle before its moment is applied
moment_direction            enum (on, before, after)        direction copied from the moment that produced it
moment_amount               smallint                        amount copied from that moment, null when it falls on the day
moment_unit                 enum (days, weeks, months)      unit copied from that moment, null when it falls on the day
generation_key              string (64)                     stable identity of this generated deadline, unique under the rule
scheduled_for               datetime                        current time it is due, stored in UTC and changed when it is postponed
original_scheduled_for      datetime                        first generated due time, kept forever when it is postponed
local_date                  date                            current local day it falls on, changed with scheduled_for so a day view queries without converting
status                      enum (pending, notified, postponed, skipped, completed, cancelled)  what became of it
postponed_to                datetime                        when it was moved to, null when it was not
postponed_count             int                             how many times it was moved, so a reminder nobody acts on is visible
notified_at                 datetime                        when the first notification for it went out
completed_at                datetime                        when it was marked done
skipped_at                  datetime                        when it was skipped
cancelled_at                datetime                        when it was cancelled
status_before_cancellation  enum (pending, notified, postponed)  actionable state restored after a reversible cancellation
cancelled_reason            enum (rule_disabled, record_deleted, record_archived, field_removed, value_cleared, source_changed, rule_changed)  why it was cancelled, so nothing vanishes unexplained
created_at                  datetime
updated_at                  datetime
```

## Relationships

- `belongsTo` [Account](001-account-in-progress.md), [Vault](007-vault-in-progress.md)
- `belongsTo` [Reminder](040-reminder-in-progress.md)
- `hasMany` [Notification](042-notification-not-yet.md)

## Rules

- **One moment in one cycle produces one occurrence.** Generation first finds `source_local_date` from the source and cadence, applies exactly one normalized moment, combines the result with the rule's local time and frozen zone, and stores the resulting instant. Every other moment in that cycle produces a sibling occurrence of its own.
- **The moment is snapshotted.** Its direction, amount and unit never change on an existing occurrence. They keep the history intelligible after the rule changes and distinguish two configured moments even if calendar clamping makes them land at the same instant.
- **Generation is idempotent.** `generation_key` is derived from the rule, `source_local_date`, the normalized moment and `original_scheduled_for`. Generating the same rolling window twice finds the same key rather than inserting the deadline twice. A unique index on `reminder_id, generation_key` enforces it.
- One occurrence can produce several notifications, one per enabled channel (10.1). The notification copies `scheduled_for`; it never applies the moment's offset again. That is why the notification is its own model and not a column here.
- `original_scheduled_for` is computed once from the zone frozen on the rule and stored absolute. Recomputing it on read would reintroduce exactly the drift section 10.3 forbids. `scheduled_for` starts equal to it and becomes the current effective deadline when the occurrence is postponed.
- Postponing writes the new instant to both `scheduled_for` and `postponed_to`, updates `local_date` in the rule's frozen zone, increments `postponed_count`, keeps `original_scheduled_for`, and touches no sibling occurrence.
- Skipping and completing affect this occurrence and nothing else, including a sibling made from another moment in the same cycle.
- A cancelled occurrence keeps its reason. Nothing disappears without an explanation (3.3).
- Deleting or archiving a record cancels its future actionable occurrences and records their previous status. Restoring or unarchiving it restores only occurrences cancelled for that matching reason when their deadline is still ahead, and generates whatever else the rolling window now needs. Past occurrences are kept, because they are history.
- Correcting the source or changing the cadence, interval, moments, local time or frozen zone cancels future occurrences with `source_changed` or `rule_changed` and generates replacements. Those cancellations are not reversed: the replacement rows are the deadlines defined by the new schedule.
- Removing the field or clearing its value cancels future occurrences with `field_removed` or `value_cleared`. Putting a field back may restore matching cancellations; typing a value again creates a new rule and therefore new occurrences, as [Reminder](040-reminder-in-progress.md) specifies.
- An archived [Contact](019-contact-done.md) or [Entity](020-entity-done.md) is due nothing, since archiving is how somebody says they no longer want to hear about a record.
- Quiet hours are applied at delivery, not here (10.4). An occurrence due at 3am stays due at 3am and its notification is held until 8am. Moving the occurrence itself would corrupt the history of when things were actually due.
- Each state change emits a webhook (14.2).

## Open questions

- How long past occurrences are kept. They are the history of whether reminders were acted on, and they accumulate quickly for a yearly rule multiplied across hundreds of contacts.
