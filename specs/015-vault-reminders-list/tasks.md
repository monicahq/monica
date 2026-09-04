# Tasks: Vault reminders list

**Input**: [spec.md](spec.md), [plan.md](plan.md), [research.md](research.md), [quickstart.md](quickstart.md)

**Tests**: not optional here. Principle III is non-negotiable, so a task that adds a class adds its test in the same task, and both files are named.

**Format**: `[ID] [P?] [Story?] Description with file paths`. `[P]` means a different file and no unfinished dependency.

**Phase order follows priority.** One shared file crosses stories: `row.blade.php` is created in US1 and gains a control in each of US3, US4, US5 and US6, so those four tasks are sequential against each other even though the rest of their stories are not.

**Every phase is done** (T001 to T032), except T023, which is not buildable yet and says why below. Twelve tasks were carried out differently from how they were written. Each is noted inline.

## Phase 1: Setup

- [X] T001 Register the routes of plan.md in `routes/web.php`, with camelCase names and kebab URLs. **Changed:** only `reminders.index` was registered. A route pointing at a controller method that does not exist yet answers 500 and fails static analysis, so `reminders.sent` lands with T014 and the three row actions with their own controllers. The ordering note about `reminders/sent` preceding `reminders/{reminder}` is recorded in a comment beside the route.
- [X] T002 [P] Create `app/Enums/ReminderPostponePresetEnum.php` (in_an_hour, tomorrow, in_a_week, in_a_month) with a translated label and an instant resolved in a rule's frozen zone, and `tests/Unit/Enums/ReminderPostponePresetEnumTest.php` covering a rule zone that differs from the reader's

## Phase 2: Foundational (blocks every story)

- [X] T003 Add `scopeUpcomingInVault` and `scopeSentInVault` to `app/Models/ReminderOccurrence.php`, scoping by vault, by account and by actionable or notified state with no driver-specific SQL, and cover both in `tests/Unit/Models/ReminderOccurrenceTest.php`
- [X] T004 [P] Add an index on `(vault_id, notified_at)`. **Changed:** the index went into the existing `database/migrations/2026_09_02_000001_create_reminder_occurrences_table.php` rather than into a new migration, because `.claude/skills/migrations/SKILL.md` requires altering existing migrations until production. plan.md and research.md said to add one; the skill wins.
- [X] T005 Create `app/ViewModels/Vaults/ReminderRow.php` turning one occurrence into its row facts (date parts, cadence wording, contact, first relation value, `DefaultFieldEnum` icon falling back to calendar, relative wording, exact instant with zone and offset), and `tests/Unit/ViewModels/Vaults/ReminderRowTest.php` covering the elapsed wording of a past instant, an absent relation label and the icon fallback
- [X] T006 Point the sidebar Reminders entry at `reminders.index` with its upcoming count and `current` key in `resources/views/components/vault/sidebar.blade.php`, and extend `tests/Feature/Views/Components/SidebarTest.php` for the link, the count and no count at zero

## Phase 3: User Story 1, see upcoming reminders (P1, MVP)

- [X] T007 [US1] Create `app/ViewModels/Vaults/VaultRemindersViewModel.php` reading `scopeUpcomingInVault`, bucketing in PHP from a single `now()` in the reader's zone, grouping by contact alphabetically, and `tests/Unit/ViewModels/Vaults/VaultRemindersViewModelTest.php` covering the five buckets, empty buckets omitted, a boundary row in one bucket only, and the two-way empty state
- [X] T008 [US1] Create `app/Http/Controllers/App/Vaults/VaultReminderController.php` with `index`, refusing a foreign vault with `abort_unless(..., 404)`, and `tests/Feature/Controllers/App/Vaults/VaultReminderControllerTest.php` covering bucket order and counts, a rule with no occurrence absent, a reminder beyond the horizon absent, another vault of the same account excluded and another account returning 404
- [X] T009 [US1] Create `resources/views/app/vaults/reminders/index.blade.php` with the page header naming the vault and stating channels, hour and the 90 day horizon truthfully, `x-tabs` carrying both counts through `trans_choice`, the grouping toggle, the five buckets, the Overdue bucket marked by tint and a heading icon as well as wording, and both empty states
- [X] T010 [US1] Create `resources/views/app/vaults/reminders/row.blade.php` with the date stamp, cadence line, contact avatar and link, reminder text beside its icon, and the relative date whose exact instant is reachable on hover and on focus. **Changed twice.** The four controls are not drawn: none of them can act yet, and a control that is drawn and does nothing is worse than one not yet there, which is the reasoning `components/vault/sidebar.blade.php` already records for unbuilt destinations. Each arrives with the phase that makes it work. The relation label is also absent: `FieldValue` stores a relation as `related_contact_id`, a pointer to another contact, so there is no word like "son" to show and no relationship model to get one from.
- [X] T011 [US1] Wire the grouping toggle as a query string swapped with Alpine Ajax in `resources/views/app/vaults/reminders/index.blade.php`
- [X] T012 [P] [US1] Assert the horizon copy, the bucket headings, the counted phrases and the row's parts. **Changed:** the assertions went into `tests/Feature/Controllers/App/Vaults/VaultReminderControllerTest.php` rather than a new `tests/Feature/Views/RemindersIndexTest.php`. `tests/Feature/Views` holds only `Components` and `Layouts`; every screen in this suite is asserted through its controller test, and inventing a third location for one screen would be the deviation.

## Phase 4: User Story 2, see what was sent (P2)

- [X] T013 [US2] Create `app/ViewModels/Vaults/VaultRemindersSentViewModel.php` reading `scopeSentInVault`, paginating, eager loading notifications and contacts, deriving the outcome from `status` as dismissed, postponed or still open, and `tests/Unit/ViewModels/Vaults/VaultRemindersSentViewModelTest.php` covering ordering, pagination, the channel mark and every outcome value. **Changed:** the row shape went onto the existing `app/ViewModels/Vaults/ReminderRow.php` as a `sent()` method beside `get()`, rather than into a second row object. It is one occurrence read two ways, the five facts about the record it is about are the same either way, and plan.md budgets one row object. The channels are ordered by the enum rather than by whichever notification was written first, so two rows that went out the same two ways read the same way round.
- [X] T014 [US2] Add `sent` to `app/Http/Controllers/App/Vaults/VaultReminderController.php` and extend its test for newest first, an occurrence never notified absent, a row surviving its rule's deletion and another account returning 404
- [X] T015 [US2] Create `resources/views/app/vaults/reminders/sent.blade.php` with the four columns, the `x-badge` outcome, pagination and its own empty state. **Changed:** the assertions went into `tests/Feature/Controllers/App/Vaults/VaultReminderControllerTest.php` rather than a new `tests/Feature/Views/RemindersSentTest.php`, for the reason T012 already records: `tests/Feature/Views` holds only `Components` and `Layouts`, and every screen in this suite is asserted through its controller test. The tab bar also lands here, in `resources/views/app/vaults/reminders/_tabs.blade.php` shared by both screens, since T009 deliberately drew no bar while there was only one tab to put in it.

## Phase 5: User Story 3, postpone one occurrence (P3)

- [X] T016 [US3] Create `app/Http/Controllers/App/Vaults/ReminderOccurrencePostponementController.php` with `update`, validating preset or explicit date and time in array notation, delegating to `PostponeReminderOccurrence`, catching `ReminderCannotBePostponedIntoThePast` and `ReminderOccurrenceIsFinished` into a readable message, and `tests/Feature/Controllers/App/Vaults/ReminderOccurrencePostponementControllerTest.php` covering a preset, an explicit instant, both refusals, the sibling and next cycle untouched, the original instant kept and another account returning 404. **Changed:** an unparseable time comes back through the same `postponement` message as the two refusals rather than as a validation error on the field. `date_format:H:i` would reject the seconds some browsers send, so the format is checked with `ReminderTime::isATime()`, which is what the rest of the reminder code uses.
- [X] T017 [US3] Create the postpone dialog over `x-modal.form`, naming the contact, the reminder text, the current instant, the four presets with their resulting instant in words, the explicit date and time inputs and the rule's zone, with a focus trap, Escape to close and focus returned to its button. **Changed twice.** The file is `resources/views/app/vaults/reminders/_postpone.blade.php`, an underscore-prefixed partial included by the row, matching `_tabs.blade.php` and `_sent-row.blade.php`; a top-level `postpone.blade.php` beside `index` and `sent` would read as a third screen. The assertions went into `tests/Feature/Controllers/App/Vaults/VaultReminderControllerTest.php` rather than a new `tests/Feature/Views/RemindersPostponeTest.php`, for the reason T012 and T015 already record. The four presets and their instants are computed in `ReminderRow`, since they depend on the rule's frozen zone and its own hour. The postpone control on `row.blade.php` also lands here, which is what T010 said would happen.

## Phase 6: User Story 4, dismiss one occurrence (P4)

- [X] T018 [US4] Create `app/Http/Controllers/App/Vaults/ReminderOccurrenceDismissalController.php` with `update`, delegating to `DismissReminderOccurrence` and catching `ReminderOccurrenceIsFinished`, and `tests/Feature/Controllers/App/Vaults/ReminderOccurrenceDismissalControllerTest.php` covering the sibling still listed, the rule still active, the refusal and another account returning 404
- [X] T019 [US4] Wire the dismiss control to that route in `resources/views/app/vaults/reminders/row.blade.php`, with no confirmation dialog

## Phase 7: User Story 5, send the notification now (P5)

- [X] T020 [US5] Create `app/Actions/SendTestReminderNotification.php` reusing the notification `NotifyReminderOccurrence` builds, sending it immediately to the requesting user, recording the send, leaving `notified_at`, `status` and `scheduled_for` untouched, and `tests/Unit/Actions/SendTestReminderNotificationTest.php` covering the enabled channels, delivery during quiet hours, the untouched occurrence and another account raising `ModelNotFoundException` **Changed:** the wording moved to `ReminderOccurrence::howFarOff()` and `NotifyReminderOccurrence` now calls it too, so a test send says exactly what the real one will. The action sends the `ReminderDue` notification straight to the asker; the send is recorded by the `RecordEmailSent` listener like every other email, so nothing extra writes history.
- [X] T021 [US5] Create `app/Http/Controllers/App/Vaults/ReminderOccurrenceTestNotificationController.php` with `create` delegating to that action, and `tests/Feature/Controllers/App/Vaults/ReminderOccurrenceTestNotificationControllerTest.php` covering the happy path, no Sent row produced and another account returning 404
- [X] T022 [US5] Wire the send-now control and its confirmation through `x-toaster` in `resources/views/app/vaults/reminders/row.blade.php` **Changed:** no wiring was needed for the toast. The controller redirects with a `status`, and `x-toaster` reads the session, so the confirmation comes for free.

## Phase 8: User Story 6, reach the reminder's own screen (P6)

- [ ] T023 [US6] Render the detail control as a named link addressed to the reminder. **Not done, and not doable as written.** There is no `reminders.show` route, so `route()` cannot build the address and a hardcoded path would answer 404 when pressed. T010 already settled that a control which does nothing is worse than one not yet drawn, and a link that 404s is the same case. The control arrives with the detail screen, in the pull request that adds the route it points at.

## Phase 9: Polish and the written record

- [X] T024 Give each of the four icon-only controls an accessible name and a description shown on focus as well as on hover, in `resources/views/app/vaults/reminders/row.blade.php` **Changed:** the three controls that exist share a new `resources/views/components/icon-button.blade.php` rather than repeating the markup. `x-tooltip` could not be used: it is `role="img"`, which takes a button inside it out of the accessibility tree, and moving it inside the button instead loses the focus trigger, because `focusin` travels up from the button and never reaches a listener below it. The new component keeps the state on the button, which is both the target and the thing being named.
- [X] T025 Verify `resources/views/app/vaults/reminders/index.blade.php` and `sent.blade.php` at 200% zoom, with reduced motion, on a narrow window with no sideways scroll, and with the tab bar operable by arrow key **Found and fixed:** the row did not wrap, so at phone width the name, the date and the controls shared one line and ran into each other. The name and text now take the full width below `sm`, dropping the date and controls onto their own line. **Two gaps left, both in shared components and neither introduced here.** `x-tabs` marks itself `role="tablist"` but has no arrow-key handling, and `x-tooltip` and `x-icon-button` fade their bubble with `x-transition.opacity`, which Alpine drives in JS and which the reduced-motion block in `app.css` therefore does not reach. Fixing either means changing a component the template builder also uses.
- [X] T026 Run `php artisan monica:localize` and translate every reported key in all nine locales, honouring `.claude/rules/translations.md`
- [X] T027 Run `php artisan monica:localize --check` and fix every counted phrase not going through `trans_choice`
- [X] T028 [P] Run `vendor/bin/pint --format agent`
- [X] T029 [P] Run `vendor/bin/phpstan analyse` at level 5 and fix findings rather than ignoring them
- [X] T030 Assert a query count that does not grow with the number of rows in `tests/Feature/Controllers/App/Vaults/VaultReminderControllerTest.php`, for both tabs
- [X] T031 Walk [quickstart.md](quickstart.md) end to end against a seeded vault
- [X] T032 Name both constitution deviations in the pull request: no API coverage for reminders (Principle X) and the query string for the grouping toggle

## Dependencies

- Phase 1 has no dependency. Phase 2 depends on Phase 1 and blocks every story.
- T003 blocks T007 and T013. T005 blocks T007 and T010. T006 depends on T003.
- US1 depends on Phase 2 only. It is the MVP and can ship alone, with the four controls rendered and only the detail link working.
- US2 depends on Phase 2 and on T008 for the controller it extends. It is otherwise independent of US1.
- US3 depends on T002 for the preset enum and on T010 for the control it opens. US4, US5 and US6 depend on T010 only.
- T019, T022 and T023 all edit `row.blade.php` and are sequential against each other.
- Phase 9 depends on every story phase in this pull request. T026 blocks T027.
- Within a phase, `[P]` tasks touch different files and can run together. Everything else in a phase is sequential.
