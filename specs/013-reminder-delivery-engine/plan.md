# Implementation Plan: Reminders that actually fire

**Branch**: `2026-09-02-reminder-delivery-engine` | **Date**: 2026-09-02 | **Spec**: [spec.md](spec.md)

## Summary

A rule already stores its cadence, moments, local time and frozen zone correctly. This adds the three
things behind it: the deadline it produces, the notification that carries the deadline to somebody,
and the preferences that say how. Generation expands a rule over a rolling window into one row per
pair of cycle and moment, keyed so a second run finds the same row. Every path that already changes or
silences a rule gains a reconciliation of the deadlines still ahead. Delivery reads the account's
preferences, holds what quiet hours say to hold, and hands email to the chain the product already uses.
Decisions: [research.md](research.md). Tables and states: [data-model.md](data-model.md). Proof:
[quickstart.md](quickstart.md).

## Technical Context

| | |
| --- | --- |
| **Language/Version** | PHP 8.4, current major Laravel |
| **Primary Dependencies** | Eloquent, the queue, the scheduler, the existing `SendEmail` job and `Resend` behind it |
| **Storage** | three new tables, one new column on `reminders`; the suite runs on SQLite, MySQL and PostgreSQL, so no driver specific SQL |
| **Testing** | PHPUnit, `RefreshDatabase`, `Queue::fake()`, `Mail::fake()`, `Carbon::setTestNow()` |
| **Target Platform** | the signed in web application and its queue workers |
| **Project Type** | server rendered Laravel application, no frontend work in this change |
| **Performance Goals** | a deadline delivered within five minutes of falling due (SC-002); generation over one rule's 90 day window is a handful of queries |
| **Constraints** | Larastan level 5 over `app/`, `routes/` and `database/`; Pint clean; every user facing string through `__()` and translated in all nine locales |
| **Scale/Scope** | one account is one user; a yearly rule with three moments is three rows a year, so a busy vault is thousands of rows, not millions |

## Constitution Check

*GATE: checked before Phase 0 and again after Phase 1. Two violations, both declared below.*

| Principle | How this change satisfies it |
| --- | --- |
| I Actions hold the logic | Every operation is one action. The three scheduled jobs iterate and delegate; they hold no rules of their own. |
| II Simplicity | Two helpers only, and both already have a second caller: the window enumeration extends `ReminderSchedule`, and quiet hours are read by delivery and by the digest. |
| III Tested before merged | Every new model, action, job, helper, enum and mailable gets a test, including the compartment tests: another account's deadline is not found. |
| IV Laravel conventions | `up()` only, no `constrained()`, one class per file, array notation, camelCase route names, early returns. |
| V Localised and safe | Notification titles and bodies through `__()` and `trans_choice()`, `monica:localize` run, all nine locales translated. No screen, so no destructive confirmation is in scope. |
| VI Nothing changes under anybody's feet | The zone stays frozen on the rule; the instant is computed once and stored absolute. Quiet hours are read at delivery on purpose, since they gate delivery rather than fix a deadline. |
| VII Stable core | Reminders are one of the nine fixed concepts and this adds no second scheduling mechanism. Tasks will reuse it, as 10.5 requires. |
| VIII Nothing disappears silently | Seven cancellation reasons, a restorable previous status, and a failed notification that keeps its row and its reason. |
| IX Accessible to everybody | No screen is touched, so nothing is in scope. The first screen that renders a notification carries this. |
| X Everything goes through the API | **Violated, declared below.** |
| XI The written record is short | **`spec.md` is over its cap, declared below.** `plan.md`, `research.md`, `data-model.md` and `quickstart.md` are within theirs, and `contracts/` is omitted. |

## Project Structure

### Documentation (this feature)

```text
specs/013-reminder-delivery-engine/
├── plan.md              # this file
├── research.md          # Phase 0: the decisions and what was rejected
├── data-model.md        # Phase 1: tables, states and the keys that make generation idempotent
├── quickstart.md        # Phase 1: how to prove the chain works end to end
└── tasks.md             # written by /speckit-tasks, not here
```

`contracts/` is deliberately absent: this change exposes no HTTP surface, its interfaces are actions,
and `data-model.md` carries their inputs.

### Source code (repository root)

```text
app/Enums/          ReminderOccurrenceStatusEnum, ReminderOccurrenceCancelledReasonEnum,
                    NotificationChannelEnum, NotificationTypeEnum, NotificationDeliveryModeEnum,
                    plus new UserActionEnum cases and a ReminderDue case on EmailType
app/Models/         ReminderOccurrence, Notification, NotificationPreference; occurrences() on Reminder
app/Helpers/        ReminderSchedule gains window enumeration; QuietHours is new
app/Actions/        GenerateReminderOccurrences, CancelReminderOccurrences, RestoreReminderOccurrences,
                    PostponeReminderOccurrence, DismissReminderOccurrence,
                    CreateNotificationPreference, UpdateNotificationPreference,
                    NotifyReminderOccurrence, DeliverNotification, SendNotificationDigest
app/Jobs/           GenerateDueReminderOccurrences, DeliverDueNotifications, SendNotificationDigests
app/Mail/           ReminderDue
app/Exceptions/     the named exceptions replacing InvalidArgumentException in the two rule actions
app/Http/Requests/  SaveFieldValuesRequest judges the moments it currently only shapes
config/monica.php   the generation window, the delivery cadence and the retry bound
routes/console.php  generation daily, delivery every five minutes, digests hourly
database/           three create migrations, schedule_revision on reminders, a preferences backfill
tests/Unit/         mirroring every folder above
tests/Feature/      the section save refusing a bad reminder under the block
spec/models/        040 to 043 rewritten to what is built; 001-account gains the preferences line
```

**Structure Decision**: the existing tree, unchanged. No new top level folder, which the constitution
would make an amendment.

## Complexity Tracking

| Violation | Why needed | Simpler alternative rejected because |
| --- | --- | --- |
| Principle X: no API for rules or deadlines | The request defers it, and screens are deferred too, so postpone and dismiss ship reachable only from their actions and tests. | Building the API now was excluded from the request. The debt is real and belongs in the pull request: until a screen or an endpoint arrives, neither action has a human surface. |
| Principle XI: `spec.md` is 158 lines against a cap of 120 | Three models, two engines and reconciliation across ten existing paths. The requirements were already consolidated from 67 to 46 and moved into a table, and the rationale was moved out into `research.md`. | Cutting further would drop requirements rather than words. Splitting into three features was rejected because the first two would deliver nothing a user can observe. |
| One new column on `reminders`, `schedule_revision` | It is what makes regeneration after a change collide with nothing, and what lets a rule changed and changed back produce fresh rows instead of colliding with cancelled ones. | Deriving the key from the computed instant instead was tried on paper and breaks exactly on the change-and-change-back case. See `research.md`. |
