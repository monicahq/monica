# Tasks: Reminder detail page redesign

**Input**: [spec.md](spec.md), [plan.md](plan.md), [research.md](research.md), [quickstart.md](quickstart.md)

**Tests**: required by constitution principle III and `.claude/rules/testing.md`. View model tests own what the screen shows; the controller test proves the page renders and where deletion lands.

## Phase 1: Setup

- [X] T001 Read `.claude/skills/view-models/SKILL.md`, `.claude/skills/views/SKILL.md`, `.claude/rules/components.md` and `.claude/rules/testing.md` before touching `app/ViewModels/Vaults/ReminderViewModel.php`

## Phase 2: Foundational

- [X] T002 Rewrite the frame of `resources/views/app/vaults/reminders/show.blade.php`: breadcrumb People › contact › Reminders (`contacts.reminders.index`) › name, falling back to Reminders › name when there is no contact; sidebar `current="contacts"` when there is a contact; remove the contact card, Next card, Schedule, Still open, History, Activity and danger panels
- [X] T003 Add `contactRemindersUrl(): ?string` to `app/ViewModels/Vaults/ReminderViewModel.php` and reshape `contact()` to `{name, url, avatarUrl, initials}|null`

## Phase 3: User Story 1, read a reminder and answer its dates (P1) MVP

**Independent test**: a manual monthly reminder with overdue, today and upcoming dates and five answers renders all four blocks; answering the overdue date moves it to History.

- [X] T004 [US1] Test `scheduleLine()` for weekly, monthly, yearly, every-two-months and once, in `tests/Unit/ViewModels/Vaults/ReminderViewModelTest.php`
- [X] T005 [US1] Implement `scheduleLine()` per research R1 in `app/ViewModels/Vaults/ReminderViewModel.php`
- [X] T006 [US1] Test `awaiting()` returns open dates oldest first with category, relative wording and the `ReminderRow`, in `tests/Unit/ViewModels/Vaults/ReminderViewModelTest.php`
- [X] T007 [US1] Implement `awaiting()` returning `list<array{date: string, relative: string, category: ReminderOccurrenceCategoryEnum, row: ReminderRow}>` in `app/ViewModels/Vaults/ReminderViewModel.php`
- [X] T008 [US1] Test `history()` pages closed dates newest first four at a time with outcome and "Answered :date at :time", and `historyCount()`, in `tests/Unit/ViewModels/Vaults/ReminderViewModelTest.php`
- [X] T009 [US1] Implement `history()` with `simplePaginate(4)` and `historyCount()` per research R3 in `app/ViewModels/Vaults/ReminderViewModel.php`
- [X] T010 [US1] Render the header (title, contact line with `x-avatar`, schedule line, Edit when `editUrl()` is not null) in `resources/views/app/vaults/reminders/show.blade.php`
- [X] T011 [US1] Render "Dates awaiting your response" with `x-badge` Overdue (danger), Today (warning), Upcoming (neutral) and `@include('app.vaults.reminders._answers')` in `resources/views/app/vaults/reminders/show.blade.php`
- [X] T012 [US1] Render History in `x-list-panel` with `merge="append"`, its `nextPageUrl`, and the empty line "Each date you answer is kept here, with what you said." in `resources/views/app/vaults/reminders/show.blade.php`
- [X] T013 [US1] Replace the show tests with one render test per kind asserting the copy that identifies the page, in `tests/Feature/Controllers/App/Vaults/ReminderControllerTest.php`

## Phase 4: User Story 2, a reminder built from a date (P1)

**Independent test**: a birthday reminder shows "Based on … · Edit the date" linking to the contact page holding the field, and no Edit button.

- [X] T014 [US2] Test `sourceLine()`, `editDateUrl()` and a null `editUrl()` for a derived reminder, in `tests/Unit/ViewModels/Vaults/ReminderViewModelTest.php`
- [X] T015 [US2] Implement `sourceLine(): ?string` ("Based on :name’s :label"), `editDateUrl(): ?string` from today's derived branch of `editUrl()`, and make `editUrl()` return null for a derived reminder, in `app/ViewModels/Vaults/ReminderViewModel.php`
- [X] T016 [US2] Render the source line with the `link` icon and "Edit the date" in `resources/views/app/vaults/reminders/show.blade.php`

## Phase 5: User Story 3, a reminder that cannot fire (P2)

**Independent test**: a reminder stopped for each `ReminderInactiveReasonEnum` case shows its notice and no Upcoming row.

- [X] T017 [US3] Test `problem()` for every inactive reason and null when active, and that `awaiting()` drops upcoming dates while stopped, in `tests/Unit/ViewModels/Vaults/ReminderViewModelTest.php`
- [X] T018 [US3] Implement `problem(): ?array{title: string, body: string}` per research R6 and filter upcoming rows in `awaiting()` while inactive, in `app/ViewModels/Vaults/ReminderViewModel.php`
- [X] T019 [US3] Render `x-notice tone="warning"` under the header and "No upcoming date until this is resolved." at the foot of the waiting list, in `resources/views/app/vaults/reminders/show.blade.php`

## Phase 6: User Story 4, delete and delivery record (P3)

**Independent test**: More actions › Delete reminder › confirm lands on the contact's reminders; "Delivery details" toggles with the keyboard.

- [X] T020 [P] [US4] Test that `destroy` redirects to `contacts.reminders.index` for a contact reminder with the flash, in `tests/Feature/Controllers/App/Vaults/ReminderControllerTest.php`
- [X] T021 [US4] Redirect to the contact's reminders when the reminder is about a contact in `app/Http/Controllers/App/Vaults/ReminderController.php`
- [X] T022 [US4] Render the "More actions" `x-actions-menu` with a `x-modal.warning` trigger "Delete reminder", body via `trans_choice()` naming the answer count, shaped like `resources/views/app/vaults/contacts/reminders/_menu.blade.php`, in `resources/views/app/vaults/reminders/show.blade.php`
- [X] T023 [US4] Rewrite `resources/views/app/vaults/reminders/_history.blade.php` as the collapsed delivery log: toggle button with `:aria-expanded` and `aria-controls`, entries from `ReminderHistory` as timestamp and wording, per research R7
- [X] T024 [US4] Add `deliveryLog()` returning `ReminderHistory::get()` (the old `history()` name now belongs to answers) in `app/ViewModels/Vaults/ReminderViewModel.php`, and include `_history` under a hairline at the foot of `resources/views/app/vaults/reminders/show.blade.php`

## Phase 7: Polish

- [X] T030 Return to the reminder after saving an edit, and point Cancel on the edit form at the reminder, in `app/Http/Controllers/App/Vaults/ContactReminderController.php` and `app/ViewModels/Vaults/ContactReminderForm.php` (asked during implementation)
- [X] T031 Add "View reminder" to the row menu and drop Edit for a reminder built from a date, in `resources/views/app/vaults/contacts/reminders/_menu.blade.php` and `app/ViewModels/Vaults/ReminderRow.php` (asked during implementation)
- [X] T032 Link the reminder title to its detail page in `resources/views/app/vaults/contacts/_reminders.blade.php` and `resources/views/app/vaults/contacts/reminders/_row.blade.php` (asked during implementation)

- [X] T025 Remove `state()`, `next()`, `schedule()`, `createdLine()`, `builtFrom()`, `answers()`, `occurrences()`, `stillToComeEmptyLine()`, `delivery()`, `quietBecause()` and the tests that cover them, in `app/ViewModels/Vaults/ReminderViewModel.php` and `tests/Unit/ViewModels/Vaults/ReminderViewModelTest.php`
- [X] T026 Delete `resources/views/app/vaults/reminders/_contact-card.blade.php` only after an explicit yes
- [X] T027 Run `php artisan monica:localize`, translate every reported string in every locale, then `php artisan monica:localize --check`
- [X] T028 Run the whole suite `php artisan test --compact`, then `vendor/bin/phpstan analyse --no-progress` and `vendor/bin/pint --dirty --format agent`
- [ ] T029 Walk the by-hand table in `specs/066-reminder-detail-page/quickstart.md`

## Dependencies

- T001 → T002, T003 → every story phase.
- US1 (T004–T013) before US3, which filters `awaiting()`.
- US2 and US4 depend only on Phase 2.
- T025 after all stories; T027–T029 last.
- Within a story, the test task precedes its implementation task; view tasks follow the view model task.

## Parallel opportunities

- US2 (T014–T016) and US4 (T020–T024) can run beside US1 once Phase 2 is done; they share `show.blade.php`, so merge the view tasks in order.
- T020–T021 (controller) run in parallel with any view model task.

## Implementation strategy

MVP is Phases 1–3: the page renders for a manual reminder and answers work. Then US2, US3, US4, polish.
