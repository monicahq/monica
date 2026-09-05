# Implementation Plan: Reminder Detail Page

**Branch**: `2026-09-04-reminder-detail` | **Date**: 2026-09-04 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `/specs/020-reminder-detail-page/spec.md`

## Summary

One screen per reminder rule, at `vaults/{vault}/reminders/{reminder}`, reading everything the domain
already holds: the rule, its occurrences, the notifications written for them, and the account's
append-only log. Three things are new. A route and a view model to draw the page, a manual pause built
by adding the one inactive reason the enum's own docblock says is missing, and a delete route onto the
action that already exists. Nothing else is added: no table, no migration, no second editing surface.

## Technical Context

**Language/Version**: PHP 8.4, Laravel 12

**Primary Dependencies**: Blade, Tailwind, Alpine Ajax, Alpine.js. Nothing new.

**Storage**: MySQL/PostgreSQL/SQLite. No schema change: `reminders.inactive_reason` and
`reminder_occurrences.cancelled_reason` are `string(32)` with no check constraint, so a new enum case
needs no migration.

**Testing**: PHPUnit. Feature tests for the controllers, unit tests for the view models, and the two
existing reactivation tests extended for the new reason.

**Target Platform**: the logged-in web app. Laravel monolith, server-rendered.

**Performance Goals**: one page load, a bounded number of queries. Occurrences, logs and notifications
are eager loaded; the history is capped and the occurrence list is capped at what is still ahead.

**Constraints**: every instant read in the rule's frozen zone; nine locales; keyboard-only usable; no
state carried by colour alone.

**Scale/Scope**: one new screen, two new controllers, one new view model with two block objects, one
enum case, one route file edit, one existing partial gains a link.

## Constitution Check

| Principle | How this plan satisfies it |
| --- | --- |
| I, actions hold the logic | No new action. The controllers call `DeactivateReminders`, `ReactivateReminders` and `DestroyReminder`, which every other surface already uses. The view models only read. |
| II, simplicity over abstraction | The manual pause is one enum case and one `match` arm, because the reversible cancellation reason it needs, `RuleDisabled`, was written for it and has no producer today. |
| III, tested before merged | One feature test per controller, one unit test per view model, and the two reactivation tests extended. |
| IV, Laravel conventions | Named routes one per line, camelCase names, `abort_unless(..., 404)` for the vault, `whereUuid` on the binding, no `down()` because there is no migration. |
| V, localized and safe | Every string through `__()` or `trans_choice()`, `php artisan monica:localize` run and all nine locales translated. Delete warns first. |
| VI, nothing changes under anybody's feet | Pausing is explicit, keeps the schedule, and says what it did. Resuming restores only what pausing cancelled. |
| IX, accessibility | The page is audited against WCAG 2.2 AA, which Principle IX binds for every screen a change touches. |
| XI, the written record is short | `spec.md` is at 120 lines, this plan under 100. `data-model.md`, `quickstart.md` and `contracts/` are omitted: the data change is one enum case, named below, and the validation scenarios are the spec's acceptance scenarios. |

**Gate**: passes. No violations, so Complexity Tracking is empty and removed.

## Project Structure

### Documentation (this feature)

```text
specs/020-reminder-detail-page/
├── spec.md, plan.md (this file), research.md, checklists/
└── tasks.md             # /speckit-tasks output, not created here
```

### Source Code

```text
app/Enums/ReminderInactiveReasonEnum.php          # + PausedByUser
app/Enums/ReminderOccurrenceCancelledReasonEnum.php  # + the match arm to RuleDisabled
app/Http/Controllers/App/Vaults/ReminderController.php       # show, destroy
app/Http/Controllers/App/Vaults/ReminderPauseController.php  # update pauses, destroy resumes
app/Http/Controllers/App/Vaults/ReminderOccurrencePostponementController.php  # redirect back
app/Http/Controllers/App/Vaults/ReminderOccurrenceDismissalController.php     # redirect back
app/ViewModels/Vaults/ReminderViewModel.php       # the screen
app/ViewModels/Vaults/ReminderHistory.php         # the timeline block
app/ViewModels/Vaults/ReminderDelivery.php        # the delivery block
resources/views/app/vaults/reminders/show.blade.php
resources/views/app/vaults/reminders/_contact-card.blade.php
resources/views/app/vaults/reminders/_history.blade.php
resources/views/app/vaults/reminders/row.blade.php   # gains the link to the screen
routes/web.php
```

**Structure Decision**: the existing tree. The reminder screens already live in
`app/Http/Controllers/App/Vaults`, `app/ViewModels/Vaults` and
`resources/views/app/vaults/reminders`, and this is one more screen among them.

### Routes

```php
Route::get('vaults/{vault}/reminders/{reminder}', [ReminderController::class, 'show'])->name('reminders.show');
Route::delete('vaults/{vault}/reminders/{reminder}', [ReminderController::class, 'destroy'])->name('reminders.destroy');
Route::put('vaults/{vault}/reminders/{reminder}/pause', [ReminderPauseController::class, 'update'])->name('reminders.pause.update');
Route::delete('vaults/{vault}/reminders/{reminder}/pause', [ReminderPauseController::class, 'destroy'])->name('reminders.pause.destroy');
```

All four take `->whereUuid('reminder')` and are declared after `reminders/by-contact`, `reminders/sent`
and `reminders/occurrences/...`, so a literal segment is never read as a reminder.
