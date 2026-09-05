# Research: Reminder Detail Page

Decisions the plan rests on, read against the working tree of 2026-09-04.

## The manual pause is already built, bar one enum case

**Decision**: add `ReminderInactiveReasonEnum::PausedByUser = 'paused_by_user'` and map it in
`ReminderOccurrenceCancelledReasonEnum::forInactiveReason()` to the existing `RuleDisabled`. Nothing
else in the domain changes.

**Rationale**: `DeactivateReminders` and `ReactivateReminders` already accept a single `Reminder` as
subject and take the reason as an argument. `ReactivateReminders` filters on
`where('inactive_reason', $this->reason)`, and `RestoreReminderOccurrences` filters on
`where('cancelled_reason', $this->reason)` and refuses outright when the reason is not reversible. So
resuming a paused reminder can only revive what that pause cancelled; an occurrence cancelled by
archiving or by a cleared value is unreachable from here. The asymmetry is enforced by code that
already exists rather than by anything this feature writes.

`RuleDisabled` is already in the cancellation enum, already listed in `isReversible()`, and has no
producer. Its docblock names "switching the rule off" as the case it exists for, and
`ReminderInactiveReasonEnum`'s docblock names the missing fifth reason, the owner switching a rule off
by hand. This feature is that producer.

**Rejected**: a `paused_at` column with its own code path, duplicating the cancel-and-restore
machinery. A `PausedByUser` cancellation reason, when `RuleDisabled` already means it.

**Consequence**: `forInactiveReason()` is an exhaustive `match` with no default, so PHPStan fails until the arm is written. That is wanted.

## No migration

Both `reminders.inactive_reason` and `reminder_occurrences.cancelled_reason` are `string(32)` nullable with no check constraint, so a new backing value needs no schema change on any of the three engines the suite runs on.

## History is derived, not stored

**Decision**: build the timeline from `Log` rows plus the `Notification` rows written for the
occurrences. No new table.

**Rationale**: every reminder action already writes a log with `loggable` set, to the reminder for create, update, destroy, deactivate and reactivate, and to the occurrence for postpone and dismiss. So the query is one account-scoped read over `loggable_type`/`loggable_id` matching this reminder or any of its occurrences. `Log` exposes `description` and `author` accessors that already translate the sentence and pick the plural wording, so the panel renders those and the instant.

Delivery is not a user action and carries no log. "Notification sent" comes instead from `Notification` rows whose `notifiable` is an occurrence and whose `sent_at` is set.

**Rejected**: a `reminder_events` table, a second record of what the account journal already records, and one that would start empty for every reminder that exists.

**Known limit**: nothing is backfilled, and a postponement records where it moved to rather than each step, so an old or repeatedly-moved reminder shows less than the design's mock. The spec says so.

## Postponing and dismissing return to where they were done

**Decision**: change both occurrence controllers to
`redirect()->back(fallback: route('reminders.index', $vault))`, keeping their status messages.

**Rationale**: both hardcode `reminders.index`, which is right from the list and wrong from this page. One line, no new route, and the existing feature tests stay green because a test posting without a previous URL takes the fallback, which is the route they already assert.

**Rejected**: a `return` field naming a route, machinery around a question the referrer answers. A second pair of routes under the reminder, two controllers doing one thing.

## Edit links out rather than editing here

The button links to `contacts.show` at the page carrying the field's section, resolved through
`Field → sectionVersion → TemplateVersionSection → templateVersionPage` against the contact's own
template version, falling back to the overview page. A reminder is created and changed inside the date
field's block, saved by `SetFieldValues` with the rest of it; a second editing surface would be two
places to change one rule.

## The contact card drops three things this product does not have

There is no `Group` model, no `Tag` model and no last-conversation concept. `Contact` carries
`last_activity_at` and `activity_count`, which are a different claim and would be that claim dressed
as the design's. The card keeps the name, avatar, source date and the contact's reminder count.

## Tokens map onto the existing theme

The design's `--muted`, `--hairline`, `--warning-tint`, `--warning-edge`, `--success-tint` and
`--success-edge` already exist as Tailwind utilities the reminders list uses. `x-panel`, `x-badge`,
`x-breadcrumb`, `x-page-header`, `x-modal.form` and `x-contact-avatar` cover every block the design
draws, and `x-panel` already has the `tone="danger"` and `flush` variants the delete panel and the
occurrence list need. No new CSS.
