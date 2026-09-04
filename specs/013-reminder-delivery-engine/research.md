# Research: Reminders that actually fire

Every decision taken where [spec.md](spec.md) left room, with what was rejected. One line each unless
the reasoning is load-bearing.

| Decision | Rationale | Rejected |
| --- | --- | --- |
| Generation window of 90 days, in `config/monica.php` | Long enough that no realistic outage loses a deadline, short enough that a yearly rule across hundreds of contacts stays thousands of rows. Answers the open question in model 040. | A year ahead (multiplies the table for no reader); generating on read (the question this feature exists to close). |
| Deadlines already past are never generated | A deadline that passed before its rule existed was never a deadline, and inventing it would fire a reminder for yesterday the moment somebody ticks a box. | Generating and immediately marking them dismissed, which puts noise in the history nobody created. |
| An occurrence has only Postpone and Dismiss as user actions | Postpone means "remind me at this exact future instant"; Dismiss means "do not interrupt me about this occurrence again". Neither says that work was completed, because a reminder is not a task. | Complete, which invents an action to finish; Skip, whose intended outcome is exactly Dismiss but whose wording is less direct. |
| Postpone and Dismiss abandon unsent obsolete notifications and keep sent ones | A notification held by quiet hours or already queued must not escape after the occurrence moved or closed, while deleting a sent row would erase history. Delivery also checks the occurrence state and current instant to close the race. | Deleting stale rows, which loses the explanation; relying only on the action update, which leaves a concurrent delivery race. |
| A rule carries `schedule_revision`, bumped whenever cadence, interval, moments, local time, zone or the value it reads changes | It is what makes the identity below collide with nothing. See the note under the table. | Deriving identity from the computed instant, which breaks on change-and-change-back. |
| Quiet hours reach every channel, in-app included | Section 10.4 says nothing may disturb and carves nothing out. | Exempting in-app as passive, which is a judgement the specification does not make. One line to reverse. |
| The digest hour is never held by quiet hours | It is an hour the account chose deliberately; holding it is an implicit setting overruling an explicit one. | Holding it, which would silently move a digest somebody scheduled. |
| A moment is capped per unit: 365 days, 52 weeks, 12 months | One cap of 365 for all three units is exactly what lets "365 months before" through today. All three caps sit about a year out, past which the reminder is about a different date. | Converting every unit to days on the way in, which loses what a month means across February. |
| Nonexistent and doubled local times are left to PHP's own normalisation, and tested | PHP moves a nonexistent local time forward past the gap, and resolves a doubled one to the later of its two instants, the one on standard time. Both are defensible and neither loses a deadline, so the work is a test pinning them, not code reimplementing them. | Hand rolled DST handling, which is a second implementation of something the runtime already gets right. |
| Generation daily, delivery every five minutes, digests hourly | Delivery has to meet SC-002. Generation over 90 days has no reason to be frequent, and digests only need to catch each account's chosen hour. | A single per-minute sweep doing all three, which runs generation 1440 times a day to find nothing. |
| Idempotency by unique index plus `insertOrIgnore` | Portable across SQLite, MySQL and PostgreSQL, which the suite requires, and it makes two racing workers a no-op rather than an error to catch. | `firstOrCreate`, which is a read then a write and races; database specific upsert syntax, which the constitution forbids. |
| One notification per occurrence, due instant, channel and user, enforced by a unique index over those five columns | A postponed occurrence must be able to notify again at its new instant. A digest row has no occurrence, and a NULL never collides on the nullable drivers, so one index covers both shapes. | A partial or filtered index, which is written differently on each driver. |
| One live rule per value held by locking the value row inside the transaction | Adds no column and keeps the enforcement where model 040 deliberately put it, in the action. | A unique index, which cannot express "one *live* row" under soft deletes; a `live_field_value_id` column set to NULL on delete, which works but adds a column to carry a race nobody has hit. |
| Named exceptions in `app/Exceptions`, added to the controller's existing catch list | The section controller already catches six of them and turns them into a message over the block. This is the same path, not a new one. | Catching `InvalidArgumentException` in the controller, which would swallow genuine programming errors as form messages. |
| Moments judged only when the box is ticked, through the same rule class | An untouched reminder sends an empty set of moments, and the rule refuses an empty set. The condition belongs in the request, the judgement in the rule. | Relaxing the rule to accept an empty set, which would let a ticked box save a reminder that says nothing. |
| Preferences backfilled by a migration | It has to run exactly once for every existing account, which is what a migration is. | An Artisan command, which somebody has to remember to run on every deployment. |
| Push ships as an enum case and a preference flag with no driver | The constitution requires push to exist server side before the native applications, and the request defers device handling. The shape is there and nothing claims to deliver. | Omitting the case, which would mean a migration later for a column the specification already names. |
| `minimum_reminder_lead_time` is not built | The rule's moments already became deadlines at the instants they name, so a lead time here applies the offset twice. Recorded as settled in FR-030 rather than left pending. | Building it as an override, which would give two places to answer one question. |
| Retention stays undecided | It is an open question in models 041 and 042 today, the request put it after the MVP, and no user is affected until the tables are large. | Picking a period here, which would be inventing a product decision nobody took. |

## Why the identity is a revision and not an instant

Model 041 derives `generation_key` from the rule, the cycle date, the moment and the first due instant.
That last part is what breaks. Changing a rule's local time cancels its future deadlines and generates
replacements under new keys, which is correct. Changing the time back generates keys identical to the
cancelled rows, and the unique index refuses them, so the rule silently stops producing anything.

A monotonic `schedule_revision` on the rule fixes it in one column. The key becomes
`{revision}:{cycle local date}:{direction}:{amount}:{unit}`, which is short, readable in the table
while debugging, and fresh after every schedule change. Two runs inside one revision produce the same
key, which is the idempotency the feature needs; two runs across a change produce different keys, which
is the regeneration it needs. Model 041 has to be corrected to say this.

## Enumerating a window rather than asking for the next date

`ReminderSchedule::nextDate()` answers what a screen asks. A generator asks a different question: every
deadline whose instant falls inside a window. A moment reaches outside the window in both directions,
so the cycles to expand run from `window start minus the furthest "after" moment` to `window end plus
the furthest "before" moment`, in local dates. Each cycle is expanded through every moment, each result
is combined with the rule's local time in its frozen zone, and only the instants landing inside the
window are kept. Months are bounded generously (31 days) when working out the reach, since a wider scan
costs a few candidates and a narrow one loses deadlines.
