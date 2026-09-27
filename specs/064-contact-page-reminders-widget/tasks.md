# Tasks: Reminders widget on the contact page

**Input**: [spec.md](spec.md), [plan.md](plan.md), [research.md](research.md). Tests are required by
constitution III.

## Phase 1: Foundational

- [X] T001 Give `emptyTitle(?string $filter = null)` and `emptyLine(?string $filter = null)` an optional filter defaulting to `$this->filter()` (research R2) in app/ViewModels/Vaults/ContactRemindersViewModel.php
- [X] T002 Test the filter argument of `emptyTitle()` and `emptyLine()` in tests/Unit/ViewModels/Vaults/ContactRemindersViewModelTest.php
- [X] T003 Move the Edit and Delete menu out of the row into a partial taking `$row` (research R5) in resources/views/app/vaults/contacts/reminders/_menu.blade.php and include it from resources/views/app/vaults/contacts/reminders/_row.blade.php
- [X] T004 Create `ContactDeadlinesBlock` (user, vault, contact) wrapping `ContactRemindersViewModel` with the All filter: `rows()` as `list<array{category: string, row: ReminderRow}>` in the order of `groups()`, `filters()` without History, `emptyTitle(string $filter)`, `emptyLine(string $filter)`, `holdsNothing()`, `blankTitle()` ("Nothing to remember about :name yet"), `blankLine()` ("Ask to be told about a date on their page, such as a birthday."), `remindersUrl()`, `allLabel()` ("All of :name’s reminders") in app/ViewModels/Vaults/ContactDeadlinesBlock.php
- [X] T005 Add `deadlines(): ContactDeadlinesBlock` memoised with `once()`, next to `immediateFamily()`, in app/ViewModels/Vaults/ContactViewModel.php

## Phase 2: User Story 1, see and answer what is due (P1)

**Independent test**: a deadline in each category shows on the overview; answering the overdue one returns to the overview without it.

- [X] T006 [US1] Test `ContactDeadlinesBlock`: rows earliest first with their category, counts per filter without History, cancelled deadlines and deleted reminders left out, the foot label names the contact, in tests/Unit/ViewModels/Vaults/ContactDeadlinesBlockTest.php
- [X] T007 [US1] Write the panel: `x-panel` titled Reminders with icon `reminder` and `:flush="true"`, one row per deadline with wording, Overdue badge, `whenLine()` with the clock icon, `cadence()` with the `calendar-repeat` icon, `_menu` at the top right, `app.vaults.reminders._answers` under the text, and the foot link in the `footer` slot, no Add button, in resources/views/app/vaults/contacts/_reminders.blade.php
- [X] T008 [US1] Include `_reminders` with `'deadlines' => $viewModel->deadlines()` after `_immediate-family` in resources/views/app/vaults/contacts/show.blade.php
- [X] T009 [US1] Test the view: the overview shows the panel with a row's wording, its answer forms posting to the answer route, the foot link to `contacts.reminders.index`, and no Add, in tests/Feature/Views/Vaults/ContactRemindersPanelTest.php

## Phase 3: User Story 2, a contact with nothing to remember (P1)

**Independent test**: a contact with nothing under All shows the blank panel.

- [X] T010 [US2] Test `holdsNothing()`, `blankTitle()` and `blankLine()` for a contact without reminders and for one whose only reminder is deleted in tests/Unit/ViewModels/Vaults/ContactDeadlinesBlockTest.php
- [X] T011 [US2] Draw the blank state with `x-empty-state` and the `reminder` icon, no tabs, no rows and no footer, in resources/views/app/vaults/contacts/_reminders.blade.php
- [X] T012 [US2] Test the blank panel's copy and the absence of the tab list and the foot link in tests/Feature/Views/Vaults/ContactRemindersPanelTest.php

## Phase 4: User Story 3, narrow to one category (P2)

**Independent test**: each filter shows its rows, its count and its empty wording.

- [X] T013 [US3] Add `x-data="{ filter: 'all' }"`, `x-tabs` with four `x-tabs.item` buttons carrying `:count`, `@click` and `x-bind:aria-selected`, rows wrapped in `role="tabpanel"`, each row `x-show` by category, the badge `x-show="filter !== 'overdue'"`, and one empty message per filter whose count is 0, `x-show` and `x-cloak`, in resources/views/app/vaults/contacts/_reminders.blade.php
- [X] T014 [US3] Test `emptyTitle()` and `emptyLine()` per filter return the Reminders screen's wording in tests/Unit/ViewModels/Vaults/ContactDeadlinesBlockTest.php
- [X] T015 [US3] Test the tab contract: `role="tablist"`, four `role="tab"` with their labels, `aria-selected`, `role="tabpanel"`, and the Alpine hook `x-data="{ filter: 'all' }"` in tests/Feature/Views/Vaults/ContactRemindersPanelTest.php

## Phase 5: User Story 4, edit or delete from the row (P2)

**Independent test**: deleting from the overview returns to the overview.

- [X] T016 [US4] Return `back()` with the existing flash instead of the Reminders screen from `destroy()` (research R6) in app/Http/Controllers/App/Vaults/ContactReminderController.php
- [X] T017 [US4] Test that a deletion sent from the contact's overview redirects back to it, and update the existing redirect assertion, in tests/Feature/Controllers/App/Vaults/ContactReminderControllerTest.php

## Phase 6: Polish

- [X] T018 Run `php artisan monica:localize`, translate every missing string in every configured locale, and pass `php artisan monica:localize --check`, in lang/*.json
- [X] T019 Run the whole suite, since `_row` and the contact overview are shared: `php artisan test --compact`
- [X] T020 Run `vendor/bin/phpstan analyse --no-progress` and `vendor/bin/pint --dirty --format agent`

## Dependencies

- T001 before T002 and T004. T004 before T005, T006, T007, T010, T014. T003 before T007.
- US1 (T006 to T009) before US2, US3 and US4, which share `_reminders.blade.php`; US4's T016 and T017 need only T003.
- T018 to T020 last.
- Parallel: T002 with T003; T016 and T017 with any of US1 to US3.
