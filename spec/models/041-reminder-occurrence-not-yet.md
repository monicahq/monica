# 041. ReminderOccurrence

**Status:** to create
**Spec:** 10.1, 10.3

## What it is

One concrete deadline produced by a rule: "5 May 2027 at 9am". Section 10.1 separates it from the rule because postponing, skipping and completing all act on a single deadline and must not touch the rule behind it.

Section 10.3 turns that into three distinct actions, and the distinction is a product decision worth stating in full because getting it wrong is the classic bug in this kind of feature.

- **Postpone** moves this deadline only and leaves the following ones alone.
- **Skip** makes this deadline go away without disabling the rule. Skipping this year's birthday does not remove next year's.
- **Complete** means the thing was done.
- **Disabling the rule** is the only action on the rule itself that cancels every future deadline, and it lives on [Reminder](040-reminder-in-progress.md).

## Fields

```
id                          int                             primary key
account_id                  int                             account the occurrence belongs to
vault_id                    int                             vault it belongs to, copied from the rule
reminder_id                 int                             rule that produced it
scheduled_for               datetime                        when it is due, stored in UTC and computed from the rule's frozen zone
original_scheduled_for      datetime                        when it was originally due, kept when it is postponed
local_date                  date                            the local day it falls on, so a day view queries without converting
status                      enum (pending, notified, postponed, skipped, completed, cancelled)  what became of it
postponed_to                datetime                        when it was moved to, null when it was not
postponed_count             int                             how many times it was moved, so a reminder nobody acts on is visible
notified_at                 datetime                        when the first notification for it went out
completed_at                datetime                        when it was marked done
skipped_at                  datetime                        when it was skipped
cancelled_reason            enum (rule_disabled, record_deleted, record_archived, source_changed)  why it was cancelled, so nothing vanishes unexplained
created_at                  datetime
updated_at                  datetime
```

## Relationships

- `belongsTo` [Account](001-account-in-progress.md), [Vault](007-vault-in-progress.md)
- `belongsTo` [Reminder](040-reminder-in-progress.md)
- `hasMany` [Notification](042-notification-not-yet.md)

## Rules

- One deadline can produce several notifications, on several channels (10.1). That is why the notification is its own model and not a column here.
- `scheduled_for` is computed once, from the zone frozen on the rule, and stored absolute. Recomputing it on read would reintroduce exactly the drift section 10.3 forbids.
- Postponing writes `postponed_to`, keeps `original_scheduled_for`, and touches no other occurrence.
- Skipping affects this one and nothing else.
- A cancelled occurrence keeps its reason. Nothing disappears without an explanation (3.3).
- Deleting a record cancels its future occurrences and restores them when the record comes back (10.3). Past occurrences are kept, because they are history.
- Archiving a record does the same, with `record_archived`, and unarchiving puts the future deadlines back. An archived [Contact](019-contact-done.md) or [Entity](020-entity-done.md) is due nothing, since archiving is how somebody says they no longer want to hear about a record.
- Quiet hours are applied at delivery, not here (10.4). An occurrence due at 3am stays due at 3am and its notification is held until 8am. Moving the occurrence itself would corrupt the history of when things were actually due.
- Each state change emits a webhook (14.2).

## Open questions

- How long past occurrences are kept. They are the history of whether reminders were acted on, and they accumulate quickly for a yearly rule multiplied across hundreds of contacts.
