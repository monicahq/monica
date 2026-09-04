# Research: Vault reminders list

Decisions taken before implementation, with what was rejected. One line per alternative.

## Answered by the user, 2026-09-04

| Question | Decision | Rejected |
| --- | --- | --- |
| What does the Active tab list, given deadlines exist only 90 days out? | Actionable occurrences inside the existing window; the screen states the horizon. | Widening the window to 365 days (cheap, one config default, but changes the engine). Listing rules and computing next dates (unbounded, but not sortable in SQL, and rows without an occurrence cannot carry three of four buttons). |
| What does the Sent tab's Outcome column say, given the design shows "Completed"? | Only states the product holds: dismissed, postponed, still open. | Adding completion (migration, action, fifth button). Relabelling dismissal as completion (contradicts a documented decision in `DismissReminderOccurrence`). |
| What does the header's "Add a reminder" do? | Dropped from this feature. | A contact-then-date picker (new screen, search, the "value already has a rule" case). Linking to People (reads as a dead end). |

**Consequence of the first**, carried into FR-004: the commonest reminder here is a yearly birthday, which sits outside a 90 day window about three quarters of the year. The Active tab will often be short. The horizon copy and the two-way empty state are the mitigation and are load-bearing, not decorative. Reversing it later is `MONICA_REMINDER_GENERATION_WINDOW_IN_DAYS` and no code.

## Technical decisions

**Active tab query.** Occurrences joined to their rule, scoped to the vault, status in `ReminderOccurrenceStatusEnum::actionable()`, `scheduled_for` inside the window, ordered by `scheduled_for`. Rejected: computing next dates per rule through `ReminderSchedule::nextDate()`, which walks a calendar and cannot be ordered by the database.

**Bucketing.** Computed in PHP over the fetched rows, in the reader's zone. The suite must pass on SQLite, MySQL and PostgreSQL, so date arithmetic in SQL is out. The set is bounded by one vault's 90 days, so sorting it in memory is not a scale concern. Rejected: `CASE WHEN` bucketing in SQL (driver-specific), a bucket column (denormalisation for a presentation concern).

**Boundary between buckets.** Decided once, in the view model, from a single `now()`. Rejected: each bucket testing its own range, which lets a row due exactly on a boundary land in two.

**Sent tab query.** Occurrences in the vault with `notified_at` not null, ordered by `notified_at` descending, paginated. Needs a new index; see below. Rejected: querying `notifications` instead, which returns one row per channel and would show one reminder several times.

**Sent tab channel mark.** From the occurrence's `notifications` relation, eager loaded. Rejected: a channel column on the occurrence.

**Outcome value.** Derived from `status`: `Dismissed`, `Postponed`, otherwise still open. No new column.

**Postpone presets.** A new `ReminderPostponePresetEnum` in `app/Enums`, four cases, each resolving to an instant from the rule's frozen zone and carrying its own label. Rejected: computing the instant in the browser, which cannot honour a zone frozen on the rule; a helper class, since the four choices are a closed set and the project spells those as enums.

**Time zone named in the dialog.** The rule's `timezone`. `Vault` has no timezone column, so the design's "your vault's time zone" is not implementable as written and the copy changes. Rejected: `NotificationPreference.timezone`, which is per account and would disagree with the instant the engine actually fires at.

**Send now.** A new action that builds the same notification `NotifyReminderOccurrence` builds, sends it immediately to the requesting user, records the send, and leaves the occurrence's `notified_at`, `status` and `scheduled_for` untouched. Because the Sent tab keys off `notified_at`, a test send cannot appear there. Rejected: calling `NotifyReminderOccurrence`, which consumes the real occurrence; sending without recording anything, which loses history Principle VIII wants kept.

**Addresses.** One route per tab, `vaults/{vault}/reminders` and `vaults/{vault}/reminders/sent`, with `sent` registered before the detail route the next pull request adds. Rejected: `reminders/{tab?}` mirroring `contacts/{filter?}`, because a one-segment optional parameter collides with `reminders/{reminder}` and the collision would only surface when the detail screen lands.

**Grouping.** A query string on the index route, swapped with Alpine Ajax. `code-style.md` discourages query strings without a good reason: a grouping is a view option rather than part of a resource's identity, and a route segment would put presentation into the URL structure. This is a named deviation. Rejected: a route per grouping; grouping held only in Alpine, which cannot be linked to.

**Row mark for the kind of date.** Mapped from `DefaultFieldEnum` in the view model, falling back to `calendar`. `Field` has no icon column and does not need one for a presentational choice. Every icon the design uses already exists in `resources/views/components/icon.blade.php`.

**Relation label.** The contact's first `FieldTypeEnum::Relation` value, absent when there is none. There is no relationship model; Principle VII keeps relations an ordinary field.

**Pagination.** The Sent tab paginates. The Active tab does not, being bounded by the window.

## Schema change

One index, `reminder_occurrences (vault_id, notified_at)`. The three existing indexes are `(status, scheduled_for)`, `(reminder_id, status, scheduled_for)` and `(account_id, local_date)`; none serves a vault-scoped history ordered by send time. New migration, `up()` only, no `constrained()`.

## Deferred, and why

**API coverage.** Principle X makes a capability incomplete until the API covers it. `routes/api.php` exists and has no reminder routes: 012 and 013 shipped the whole engine without any, so the debt predates this feature. This feature adds one new capability (send now) and three reachable operations. Deferring is a deviation and must be named in the pull request. Recommended as the next feature after the detail screen, since it is then one pass over a settled surface.

**Completion.** The constitution names "postponing, skipping, completing and deactivating" as four distinct reminder actions. The engine implements postponement and dismissal only. Absent since 013; the Outcome decision above leaves it absent. Not introduced here, and not fixed here.

## Copy that changes from the design

- "Reminders arrive by email on the morning of the day they are due" is wrong three ways: delivery is in-app and email, the hour is the rule's `time_of_day`, and a moment may fire days or weeks before the date.
- "Times are in your vault's time zone" becomes the rule's zone.
- "Completed" and "Not completed" become the states above.
