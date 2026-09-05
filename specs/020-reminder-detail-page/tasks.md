---

description: "Task list for the reminder detail page"
---

# Tasks: Reminder Detail Page

**Input**: [spec.md](spec.md), [plan.md](plan.md), [research.md](research.md)

**Tests**: included and not optional. Constitution Principle III is non-negotiable, so every controller
and view model this feature adds carries a test, and the actions it reuses have theirs extended.

**Format**: `[ID] [P?] [Story?] Description with file path`. `[P]` means a different file with no
dependency on an unfinished task.

## Phase 1: Setup

- [X] T001 Create branch `2026-09-04-reminder-detail` off an up-to-date `main`

---

## Phase 2: Foundational (blocking)

**Purpose**: the enum case the manual pause needs. Adding it breaks two exhaustive `match` statements,
which is wanted: both must be answered before anything else compiles under PHPStan.

- [X] T002 Add `case PausedByUser = 'paused_by_user'` to `app/Enums/ReminderInactiveReasonEnum.php`, replacing the docblock paragraph that says the fifth reason has no case
- [X] T003 Add the `PausedByUser => RuleDisabled` arm to `forInactiveReason()` in `app/Enums/ReminderOccurrenceCancelledReasonEnum.php`, and correct its docblock, which counts seven reasons and four reversible
- [X] T004 Add the `PausedByUser` arm to `quietBecause()` in `app/ViewModels/Vaults/ContactReminderBlock.php`, saying the reminder is paused and can be resumed from its own screen
- [X] T005 Extend `tests/Unit/Enums/ReminderOccurrenceCancelledReasonEnumTest.php` for the new mapping

**Checkpoint**: the suite and PHPStan are green again, and nothing user-facing has changed yet.

---

## Phase 3: User Story 1 - Read one reminder end to end (P1) 🎯 MVP

**Goal**: the screen exists at `vaults/{vault}/reminders/{reminder}` and explains the rule.

**Independent Test**: open a reminder built from a birthday and read its cadence, moments, zone, next
date and history without leaving the page.

- [X] T006 [P] [US1] Write `tests/Feature/Controllers/App/Vaults/ReminderControllerTest.php` covering show, a reminder of another account as not found, a reminder of the wrong vault as not found, and a quiet reminder naming its reason
- [X] T007 [P] [US1] Write `tests/Unit/ViewModels/Vaults/ReminderHistoryTest.php` covering logs on the rule, logs on its occurrences, notifications sent, and a reminder whose only event is its creation
- [X] T008 [P] [US1] Write `tests/Unit/ViewModels/Vaults/ReminderDeliveryTest.php` covering the channels a notification went out on, what it says, and a reminder that has never fired
- [X] T009 [P] [US1] Write `tests/Unit/ViewModels/Vaults/ReminderViewModelTest.php` covering the state, the next date in the rule's frozen zone, the schedule fields, the contact card and the edit url
- [X] T010 [P] [US1] Create `app/ViewModels/Vaults/ReminderHistory.php` reading `Log` rows whose `loggable` is the reminder or one of its occurrences, plus `Notification` rows with `sent_at` set, newest first and capped
- [X] T011 [P] [US1] Create `app/ViewModels/Vaults/ReminderDelivery.php` reading the channels, the notification title and body, and the last delivery
- [X] T012 [US1] Create `app/ViewModels/Vaults/ReminderViewModel.php` assembling the header, the state, the next notification, the schedule, the contact card and the edit url, reusing `ReminderRow` for the occurrences (depends on T010, T011)
- [X] T013 [US1] Create `app/Http/Controllers/App/Vaults/ReminderController.php` with `show`, refusing the wrong vault with `abort_unless(..., 404)`
- [X] T014 [US1] Declare `reminders.show` in `routes/web.php` with `->whereUuid('reminder')`, after `reminders/by-contact`, `reminders/sent` and `reminders/occurrences/...`
- [X] T015 [US1] Create `resources/views/app/vaults/reminders/show.blade.php` with the breadcrumb, header, state badge, schedule panel, delivery panel and occurrences panel
- [X] T016 [P] [US1] Create `resources/views/app/vaults/reminders/_contact-card.blade.php` with the avatar, name, source date and reminder count
- [X] T017 [P] [US1] Create `resources/views/app/vaults/reminders/_history.blade.php` drawing the timeline
- [X] T018 [US1] Link each row to its reminder in `resources/views/app/vaults/reminders/row.blade.php`, deleting the docblock paragraph that says the screen does not exist

**Checkpoint**: the page is reachable from the list and explains any reminder, active or quiet.

---

## Phase 4: User Story 2 - Act on what is coming, from here (P2)

**Goal**: postponing and dismissing work from this page and return to it.

**Independent Test**: postpone the next occurrence and see the banner and the occurrence list both move
to the new instant.

- [X] T019 [P] [US2] Extend `tests/Feature/Controllers/App/Vaults/ReminderOccurrencePostponementControllerTest.php` and `...DismissalControllerTest.php` for returning to the page the request came from
- [X] T020 [P] [US2] Change the redirect in `app/Http/Controllers/App/Vaults/ReminderOccurrencePostponementController.php` to `back(fallback: route('reminders.index', $vault))`, keeping its status messages
- [X] T021 [P] [US2] Do the same in `app/Http/Controllers/App/Vaults/ReminderOccurrenceDismissalController.php`
- [X] T022 [US2] Draw the next-notification banner and the per-occurrence controls in `resources/views/app/vaults/reminders/show.blade.php`, reusing `_postpone.blade.php`, and offer neither on a reminder that is not active
- [X] T023 [US2] Extend `tests/Feature/Controllers/App/Vaults/ReminderControllerTest.php` for the controls being absent on a quiet reminder

**Checkpoint**: the page acts on occurrences and stays where it was.

---

## Phase 5: User Story 3 - Pause a reminder, and bring it back (P3)

**Goal**: somebody can silence a reminder by hand and get it back whole.

**Independent Test**: pause, confirm nothing is delivered, resume, and see the same next date return.

- [X] T024 [P] [US3] Write `tests/Feature/Controllers/App/Vaults/ReminderPauseControllerTest.php` covering pausing, resuming, a reminder of another account, and a reminder already quiet for a system cause
- [X] T025 [P] [US3] Extend `tests/Unit/Actions/DeactivateRemindersTest.php` for the `PausedByUser` reason cancelling occurrences as `RuleDisabled`
- [X] T026 [P] [US3] Extend `tests/Unit/Actions/ReactivateRemindersTest.php` proving resuming a paused reminder revives only what the pause cancelled, and leaves an occurrence cancelled by archiving alone
- [X] T027 [US3] Create `app/Http/Controllers/App/Vaults/ReminderPauseController.php` with `update` calling `DeactivateReminders` and `destroy` calling `ReactivateReminders`, both with `PausedByUser`
- [X] T028 [US3] Declare `reminders.pause.update` and `reminders.pause.destroy` in `routes/web.php`
- [X] T029 [US3] Add the toggle label, its url and the paused wording to `app/ViewModels/Vaults/ReminderViewModel.php`, offering no resume when the cause is not somebody's to lift
- [X] T030 [US3] Draw the toggle beside the header in `resources/views/app/vaults/reminders/show.blade.php`

**Checkpoint**: a reminder can be paused and resumed, and a system-silenced one still cannot be.

---

## Phase 6: User Story 4 - Delete the reminder from here (P4)

**Goal**: the page ends with the one destructive thing it offers.

**Independent Test**: delete a reminder and find the date still on the contact.

- [X] T031 [P] [US4] Extend `tests/Feature/Controllers/App/Vaults/ReminderControllerTest.php` for destroy, the redirect to the list, the field value surviving, and a reminder of another account
- [X] T032 [US4] Add `destroy` to `app/Http/Controllers/App/Vaults/ReminderController.php` calling `DestroyReminder`
- [X] T033 [US4] Declare `reminders.destroy` in `routes/web.php`
- [X] T034 [US4] Draw the `tone="danger"` panel in `resources/views/app/vaults/reminders/show.blade.php`, warning before it deletes and saying the date on the contact stays

**Checkpoint**: every story is done.

---

## Phase 7: Polish

- [X] T035 Run `php artisan monica:localize` and translate everything it reports, in all nine locales
- [X] T036 Run `php artisan monica:localize --check` and correct what it refuses
- [X] T037 Audit the new screen against WCAG 2.2 AA: keyboard-only operation, focus order, the dialog's focus trap, and no state carried by colour alone
- [X] T038 [P] Run `vendor/bin/pint --dirty --format agent`
- [X] T039 [P] Run PHPStan at level 5, which CI runs and the checklist in `CLAUDE.md` omits
- [X] T040 Run the reminder tests, then the full suite

---

## Dependencies & Execution Order

- **Phase 1** blocks nothing but should be done first.
- **Phase 2** blocks Phase 5 outright, and blocks everything else in practice: T002 breaks two exhaustive `match` statements, so the tree does not analyse until T003 and T004 land.
- **Phase 3 (US1)** is a real prerequisite for Phases 4, 5 and 6, unlike the usual independent-story shape. Every control those stories add is drawn on the page US1 creates. Their controllers and actions are still independently testable, and T019 to T021 need no page at all.
- **Within US1**: T010 and T011 before T012; T012 before T013; T013 before T014; T014 before T015. T006 to T009 are written first and fail. T016 and T017 are parallel to each other and to T015's first draft.
- **T015 is touched by four phases** (T022, T030, T034), so those three cannot run in parallel with each other.
- **Phase 7** depends on every story that is being shipped.

### Parallel opportunities

- T006, T007, T008, T009 together, then T010 and T011 together.
- T016 and T017 together.
- T019, T020, T021 together.
- T024, T025, T026 together.
- T038 and T039 together.

### MVP

Phase 1, Phase 2, Phase 3. That is the screen the spec's P1 describes, reachable from the list, with
every panel except the four controls the later stories add.
