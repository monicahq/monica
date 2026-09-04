# 041. ReminderOccurrence

**Status:** exists (`app/Models/ReminderOccurrence.php`), generated, reconciled and acted on
**Spec:** 10.1, 10.3

## What it is

One concrete deadline produced by one moment in one cycle of a rule: "5 May 2027 at 9am". It is the interruption the user acts on, not the date the source happens to hold and not the message sent through a channel.

The distinction is exact. A yearly rule reading 12 May with moments seven days before and on the day has one 2027 cycle anchored on 12 May and produces two occurrences: 5 May and 12 May. Each occurrence can produce an in-app notification, an email and a push notification. The two moments do not become two notifications attached to one occurrence; they become two occurrences, and the channels become notifications beneath each one.

This is what gives the actions in section 10.3 their proper boundary. Postponing the 5 May occurrence touches neither the 12 May occurrence in the same cycle nor the occurrences generated for 2028. The source date remains 12 May throughout.

Section 10.3 turns that into two distinct actions, and their exact scope is a product decision worth stating in full because getting it wrong is the classic bug in this kind of feature.

- **Postpone** means "not now, remind me at this new date". It moves this deadline to an exact future instant and leaves the following ones alone.
- **Dismiss** means "do not interrupt me about this deadline again". It closes this occurrence without claiming that an action was completed and without disabling the rule. Dismissing this year's birthday does not remove next year's.
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
generation_key              string (64)                     stable identity of this generated deadline, unique under the rule, written as {schedule revision}:{source local date}:{direction}:{amount}:{unit}
scheduled_for               datetime                        current time it is due, stored in UTC and changed when it is postponed
original_scheduled_for      datetime                        first generated due time, kept forever when it is postponed
local_date                  date                            current local day it falls on, changed with scheduled_for so a day view queries without converting
status                      enum (pending, notified, postponed, dismissed, cancelled)  what became of it
postponed_to                datetime                        when it was moved to, null when it was not
postponed_count             int                             how many times it was moved, so a reminder nobody acts on is visible
notified_at                 datetime                        when the first notification for it went out
dismissed_at                datetime                        when somebody stopped this occurrence from interrupting them again
cancelled_at                datetime                        when it was cancelled
status_before_cancellation  enum (pending, notified, postponed)  actionable state restored after a reversible cancellation
cancelled_reason            enum (rule_disabled, record_deleted, record_archived, field_removed, value_cleared, source_changed, rule_changed)  why it was cancelled, so nothing vanishes unexplained
created_at                  datetime
updated_at                  datetime
```

## Relationships

- `belongsTo` [Account](001-account-in-progress.md), [Vault](007-vault-in-progress.md)
- `belongsTo` [Reminder](040-reminder-in-progress.md)
- `hasMany` [Notification](042-notification-done.md)

## Rules

- **One moment in one cycle produces one occurrence.** Generation first finds `source_local_date` from the source and cadence, applies exactly one normalized moment, combines the result with the rule's local time and frozen zone, and stores the resulting instant. Every other moment in that cycle produces a sibling occurrence of its own.
- **The moment is snapshotted.** Its direction, amount and unit never change on an existing occurrence. They keep the history intelligible after the rule changes and distinguish two configured moments even if calendar clamping makes them land at the same instant.
- **Generation is idempotent.** `generation_key` is derived from the rule's `schedule_revision`, `source_local_date` and the normalized moment. Generating the same rolling window twice finds the same key rather than inserting the deadline twice, and the write goes through `insertOrIgnore`, so two workers arriving together produce one row between them rather than a duplicate or an error somebody has to catch. A unique index on `reminder_id, generation_key` enforces it.
- **The revision is in the key, and the first due instant is not.** Deriving the identity from `original_scheduled_for` was the earlier plan and it does not work: changing a rule's local time cancels its future deadlines and generates replacements under new keys, correctly, but changing the time back generates keys identical to the cancelled rows, the unique index refuses them, and the rule silently stops producing anything. A monotonic revision on the rule gives every schedule it has ever had its own space of identities.
- **Nothing is generated into the past.** The window starts where the generator is standing. A rule made today over a date next week produces nothing for the moment seven days before it, because that moment has already gone and a deadline that passed before its rule existed was never a deadline.
- One occurrence can produce several notifications, one per enabled channel (10.1). The notification copies `scheduled_for`; it never applies the moment's offset again. That is why the notification is its own model and not a column here.
- `original_scheduled_for` is computed once from the zone frozen on the rule and stored absolute. Recomputing it on read would reintroduce exactly the drift section 10.3 forbids. `scheduled_for` starts equal to it and becomes the current effective deadline when the occurrence is postponed.
- Postponing writes the exact future instant chosen by the caller to both `scheduled_for` and `postponed_to`, updates `local_date` in the rule's frozen zone, increments `postponed_count`, keeps `original_scheduled_for`, and touches no sibling occurrence. It may happen more than once, and no fixed postponement duration belongs to the model; presets are an interface concern.
- Postponing abandons every notification that has not gone out for the old instant. A sent notification stays as history, and the moved occurrence produces new notifications when its new instant arrives. Moving it beyond a sibling or the following cycle never merges the independent occurrences.
- Dismissing writes `dismissed` and `dismissed_at`, abandons every notification that has not gone out, and affects this occurrence only. Notifications already sent stay as history. A dismissed occurrence cannot be postponed or dismissed again and is never regenerated, while sibling and later occurrences remain untouched.
- A cancelled occurrence keeps its reason. Nothing disappears without an explanation (3.3).
- Deleting or archiving a record cancels its future actionable occurrences and records their previous status. Restoring or unarchiving it restores only occurrences cancelled for that matching reason when their deadline is still ahead, and generates whatever else the rolling window now needs. Past occurrences are kept, because they are history.
- Correcting the source or changing the cadence, interval, moments, local time or frozen zone cancels future occurrences with `source_changed` or `rule_changed` and generates replacements. Those cancellations are not reversed: the replacement rows are the deadlines defined by the new schedule.
- Removing the field or clearing its value cancels future occurrences with `field_removed` or `value_cleared`. Putting a field back may restore matching cancellations; typing a value again creates a new rule and therefore new occurrences, as [Reminder](040-reminder-in-progress.md) specifies.
- An archived [Contact](019-contact-done.md) or [Entity](020-entity-done.md) is due nothing, since archiving is how somebody says they no longer want to hear about a record.
- Quiet hours are applied at delivery, not here (10.4). An occurrence due at 3am stays due at 3am and its notification is held until 8am. Moving the occurrence itself would corrupt the history of when things were actually due.
- **Delivery reads `pending` and `postponed`, and writes `notified`.** A deadline somebody put off is still one waiting to happen. `notified` means the notification layer has dealt with the deadline, which is what stops the sweep finding it every five minutes for ever, including on an account that asked for silence and where there was nothing to make. Whether anything actually went out is `notified_at`, which stays null when nothing did.
- **A deadline somebody acted on is never regenerated.** Putting one off or dismissing it leaves its identity taken, so the next sweep over the same window writes nothing in its place.
- Each state change emits a webhook (14.2). **Not built.**

## Not done yet

- **No screen.** Nothing lists what is coming, nothing shows the history, and the postpone and dismiss actions are reachable only from the actions themselves and their tests. The vault's Reminders page and the due badge are unbuilt.
- **No API.** Principle X is unmet here for the same reason it is unmet on [Reminder](040-reminder-in-progress.md): a rule created from a value cannot be addressed from outside at all yet. The `uuid` is on the row waiting for it.
- **No webhook.** Each state change is meant to be listenable and there is no webhook machinery in the product.
- **No retention.** Nothing prunes what has gone by. See the open question below.

## Open questions

- How long past occurrences are kept. They are the history of whether reminders were acted on, and they accumulate quickly for a yearly rule multiplied across hundreds of contacts.
