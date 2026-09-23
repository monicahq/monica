# Tasks: Reminder row actions

**Input**: [spec.md](spec.md), [plan.md](plan.md), [research.md](research.md), [quickstart.md](quickstart.md)

**Tests**: required by constitution principle III. Rules: `.claude/rules/testing.md`; no class strings or markup order.

## Phase 1: Setup

- [X] T001 Read `.claude/skills/views/SKILL.md`, `.claude/skills/view-models/SKILL.md` and `.claude/rules/components.md` before editing any file below

## Phase 2: Foundational

- [X] T002 Add a `variant` prop (default `secondary`) to `resources/views/components/actions-menu/index.blade.php`, render the trigger as `<x-button :variant="$variant" type="button" size="small">` with `x-ref="menuTrigger"`, keep every existing attribute, and add `@var string $variant` to its docblock (research R2)

## Phase 3: User Story 1, Dismiss asks before it acts (P1)

Independent test: press Dismiss, nothing changes until the modal is confirmed.

- [X] T003 [P] [US1] Add `dismissalNote(): string` to `app/ViewModels/Vaults/ReminderRow.php`: "This one will not be sent. The next one stays scheduled." when `reminder?->cadence->recurs() === true`, otherwise "This one will not be sent, and nothing comes after it." (research R6)
- [X] T004 [P] [US1] Test both wordings of `dismissalNote()` with a yearly and a one time rule in `tests/Unit/ViewModels/Vaults/ReminderRowTest.php`
- [X] T005 [US1] Create `resources/views/app/vaults/reminders/_dismiss.blade.php`: `x-modal.form` with `method="put"`, `action` `$row->dismissUrl()`, `title` `__('Dismiss “:text”?', ['text' => $row->text()])`, `description` `$row->dismissalNote()`, `confirm` `__('Dismiss')`, trigger `x-button.secondary size="small" type="button" @click="open = true"` with a `check` icon and the word Dismiss; one line comment and `@var` block first
- [X] T006 [US1] Replace the dismiss `x-form` in `resources/views/app/vaults/reminders/row.blade.php` with `@include('app.vaults.reminders._dismiss', ['row' => $row])`
- [X] T007 [US1] In `tests/Feature/Controllers/App/Vaults/VaultReminderControllerTest.php`, add one test asserting the list shows the dismiss question, the note and the `dismissUrl` form action

## Phase 4: User Story 2, the row reads the way 2a draws it (P1)

Independent test: the list row holds one link to the reminder, Dismiss and More, no other control.

- [X] T008 [US2] Redraw `resources/views/app/vaults/reminders/row.blade.php` per the plan's "Row layout" table: flap, stamp (month, day, cadence), name line with chevron, text line with `x-tooltip` around `relative()`; remove the side stripe, year, dashed rule and the "Open this reminder", test and postpone icon buttons
- [X] T009 [US2] In the same file wrap stamp and text in `<a href="{{ $row->reminderUrl() }}" data-turbo="true">` when `($linkToReminder ?? true) && $row->reminderUrl() !== null`, else a `<div>`; inside the link the contact name is plain text and `x-tooltip` is not focusable, outside it the name links to `contactUrl()` and the tooltip is focusable (research R7)
- [X] T010 [US2] In `tests/Feature/Controllers/App/Vaults/ReminderControllerTest.php`, rewrite `the_list_links_to_the_screen_of_each_reminder` and `the_reminder_s_own_screen_does_not_link_to_itself` to assert the reminder URL and the absence of `aria-label="Open this reminder"` without relying on the removed button (research R8)

## Phase 5: User Story 3, the More menu (P2)

Independent test: More lists four entries; the two actions open modals, Go to the reminder navigates.

- [X] T011 [US3] Add an `inMenu` flag to `resources/views/app/vaults/reminders/_postpone.blade.php`: when true the trigger is `<button type="button" role="menuitem">` with the menu item class string from `_category-actions.blade.php`, a `clock` icon, "Remind me later", and `@click="menuOpen = false; $refs.menuTrigger.focus(); open = true"`; otherwise today's trigger; document it in the `@var` block (research R3, R4)
- [X] T012 [US3] In `resources/views/app/vaults/reminders/row.blade.php` add `x-actions-menu` with `variant="ghost"`, `:text="__('More')"`, `:label="__('More actions for :name', ['name' => $row->text()])"` and a `chevron-down` glyph holding, in order: `_postpone` with `inMenu`, the test modal, a `role="separator"` hairline, and `x-actions-menu.item` "Go to the reminder" with `arrow-right` only when the row links to the reminder (plan "Menu contents")
- [X] T013 [US3] Write the test modal in the same file: `x-modal.form` `method="post"`, `action` `$row->testUrl()`, title `__('Send yourself a test?')`, description `__('This reminder is emailed to you now, as it will arrive. Nothing else changes.')`, confirm `__('Send it')`, trigger a menu item button with `mail` icon, "Send me a test", and the same handler as T011
- [X] T014 [US3] In `tests/Feature/Controllers/App/Vaults/ReminderControllerTest.php` rewrite `every_control_it_draws_carries_its_own_name` to assert "Dismiss", "More", "Remind me later", "Send me a test", `aria-haspopup="menu"` and the `testUrl` form action, and assert the history row on the reminder's screen omits "Go to the reminder"
- [X] T015 [US3] Keep `VaultReminderControllerTest` postponement tests green; adjust `the_postpone_dialog_opens_on_the_reminder_and_where_it_stands` only if the trigger change breaks it

## Phase 6: User Story 4, the reminder's own screen asks too (P2)

Independent test: Dismiss on the next deadline card opens the same modal.

- [X] T016 [US4] In `resources/views/app/vaults/reminders/show.blade.php` replace the next deadline card's dismiss `x-form` with `@include('app.vaults.reminders._dismiss', ['row' => $nextRow])`
- [X] T017 [US4] Assert in `tests/Feature/Controllers/App/Vaults/ReminderControllerTest.php` that the reminder's screen shows the dismiss question and the `dismissUrl` form action

## Phase 7: Polish

- [X] T018 Run `php artisan monica:localize`, translate every reported string in every configured locale per `.claude/rules/translations.md`, then `php artisan monica:localize --check`
- [X] T019 Run `php artisan test --compact` (whole suite, `x-actions-menu` is shared) and fix what the change broke
- [X] T020 Run `vendor/bin/phpstan analyse --no-progress` and `vendor/bin/pint --dirty --format agent`
- [X] T021 Walk the manual table in `specs/043-reminder-row-actions/quickstart.md` by rendering the screens, and report any step not confirmed
- [X] T022 Remove the intro line under the title of the active and sent tabs, at the user's request: `intro()` in `app/ViewModels/Vaults/VaultRemindersViewModel.php` and `VaultRemindersSentViewModel.php`, the `description` in `index.blade.php` and `sent.blade.php`, and its test in `tests/Unit/ViewModels/Vaults/VaultRemindersViewModelTest.php`

## Dependencies

- T002 before T012. T003 before T005. T005 before T006 and T016. T008 before T009 before T012.
- T011 and T013 depend on T002 (the `menuTrigger` ref). Phase 7 after all stories.
- US1 and US2 both edit `row.blade.php`: do them in order, not in parallel. US4 needs only T005.

## Parallel opportunities

- T003 and T004 with T002. T016 and T017 once T005 is done, alongside Phase 4 and 5.

## Implementation strategy

MVP is US1 plus T016: every Dismiss asks first, which is the reported defect. US2 and US3 deliver the
2a layout and must ship together, since removing the icon buttons (T008) needs the menu (T012) in the
same pull request.
