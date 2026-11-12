# Tasks: Reminder answers and the contact's reminders

**Input**: [spec.md](spec.md), [plan.md](plan.md), [research.md](research.md), [data-model.md](data-model.md), [contracts/](contracts/), [quickstart.md](quickstart.md)

**Tests**: required by constitution principle III. Rules: `.claude/rules/testing.md` (one layer per rule, no class strings, The Office data, `#[Test]`, named arguments).

## Phase 1: Setup

- [X] T001 Read `.claude/skills/{actions,migrations,models,view-models,views,controllers,routes,api-writer,api-docs-writer,mcp-development,translations}/SKILL.md`, `.claude/rules/{components,policies,testing}.md` before editing
- [X] T002 Confirm the constitution amendment (plan, Complexity Tracking) has landed on `main` or that the user agreed to ship without it; stop otherwise

## Phase 2: Foundational (schema and engine; the suite is red from T003 to T024)

- [X] T003 Edit `database/migrations/2026_09_02_000000_create_reminders_table.php`: drop `moments`; add `starts_on` `date` nullable, comment "first date of a manual rule, null for a field rule"; make `field_id` nullable; update the `source` and `inactive_reason` comments to `manual, field_value` and `value_cleared, record_archived, record_deleted, field_removed`
- [X] T004 Edit `database/migrations/2026_09_02_000001_create_reminder_occurrences_table.php` to exactly the column list of `data-model.md` (`answer` string(16) nullable, `answered_at`, `notifications_created_at`, `cancelled_at`, `cancelled_reason` string(32)); drop the eleven listed columns; indexes `[reminder_id, generation_key]` unique, `[vault_id, local_date]`, `[scheduled_for, notifications_created_at]`
- [X] T005 Edit `database/migrations/2026_09_02_000003_create_notifications_table.php`: unique `notifications_told_once_unique` on `['notifiable_type', 'notifiable_id', 'channel', 'user_id']`, rewrite the comment above it
- [X] T006 [P] Create `app/Enums/ReminderAnswerEnum.php` (`Remembered = 'remembered'`, `Forgotten = 'forgotten'`, `values()`) and `app/Enums/ReminderOccurrenceCategoryEnum.php` (`Upcoming`, `Today`, `Overdue`, `static for(ReminderOccurrence $occurrence, CarbonImmutable $today): self` comparing `local_date` with `$today`) per research R4
- [X] T007 [P] Add `Manual = 'manual'` to `app/Enums/ReminderSourceEnum.php`; remove `PausedByUser` and `isLiftedByUser()` from `app/Enums/ReminderInactiveReasonEnum.php`; rename `RuleDisabled` to `RuleDeleted = 'rule_deleted'` in `app/Enums/ReminderOccurrenceCancelledReasonEnum.php` and drop its `PausedByUser` mapping
- [X] T008 [P] In `app/Enums/UserActionEnum.php` add `ReminderOccurrenceAnswer` with its label; remove `ReminderOccurrencePostponement` and `ReminderOccurrenceDismissal`
- [X] T009 Update `app/Models/Reminder.php`: `#[Fillable]` and casts without `moments`, with `starts_on` (`immutable_date`); drop `MINIMUM_AMOUNT`
- [X] T010 Update `app/Models/ReminderOccurrence.php`: fillable and casts for the new columns (`answer` → `ReminderAnswerEnum`); replace `due`, `upcomingInVault`, `sentInVault`, `isActionable`, `howFarOff` with scopes `active`, `answered`, `dueForNotification` (active, `notifications_created_at` null, `scheduled_for <= now()`) and `isStillWarranted()` without the scheduled instant argument
- [X] T011 [P] Update `database/factories/ReminderFactory.php` (no moments; state `manual(string $startsOn)`) and `database/factories/ReminderOccurrenceFactory.php` (no status; states `past`, `cancelled(reason)`, `remembered`, `forgotten`, `notificationsCreated`)
- [X] T012 Rewrite `app/Helpers/ReminderSchedule.php` without moments: `occurrencesBetween()` yields one local date per cycle, anchored on `starts_on` for a manual rule and on the value for a field rule (research R3, R8); keep `nextDate()` and `instantOf()`
- [X] T013 Update `app/Actions/GenerateReminderOccurrences.php`: one row per cycle, `generation_key` = `{schedule_revision}:{local_date}`, never a date whose `scheduled_for` has passed (FR-023)
- [X] T014 Update `app/Actions/CancelReminderOccurrences.php` with `bool $includePast`: true cancels every active row, false only rows whose `local_date` is after today in the rule's zone (research R6); `RestoreReminderOccurrences.php` clears `cancelled_at` and `cancelled_reason` on rows still ahead
- [X] T015 Update `app/Actions/DeactivateReminders.php`, `DestroyReminder.php` (reason `RuleDeleted`), `ArchiveContact.php` and `DestroyContact.php` callers to cancel with `includePast: true`; `UpdateReminder.php` and `SetFieldValues::rebuildIfTheDateMoved` with `includePast: false`
- [X] T016 Update `app/Actions/CreateReminder.php` and `UpdateReminder.php`: no `moments` argument; `CreateReminder` accepts either a `FieldValue` or a `Contact` with `startsOn`, stores `source` accordingly, and refuses a one off whose only date has passed its send time (FR-023)
- [X] T017 Update `app/Actions/NotifyReminderOccurrence.php` (write `notifications_created_at`, drop `howFarOff()` from the body) `DeliverNotification.php` (abandon when the deadline is answered or cancelled, stop writing `notified_at`) and `SendTestReminderNotification.php` (no `howFarOff()`, `local_date` for `source_local_date`); update `app/Jobs/DeliverDueNotifications.php` to `dueForNotification()`
- [X] T018 Delete `app/Actions/PostponeReminderOccurrence.php`, `DismissReminderOccurrence.php`, `app/Enums/{ReminderOccurrenceStatusEnum,ReminderMomentDirectionEnum,ReminderMomentUnitEnum,ReminderPostponePresetEnum}.php`, `app/Rules/ReminderMoments.php`, `app/Exceptions/{ReminderMomentIsNotValid,ReminderCannotBePostponedIntoThePast,ReminderOccurrenceIsFinished}.php` and their tests
- [X] T019 Delete `ReminderOccurrencePostponementController`, `ReminderOccurrenceDismissalController`, `ReminderPauseController` in `app/Http/Controllers/App/Vaults/`, their four routes in `routes/web.php`, their feature tests, and `resources/views/app/vaults/reminders/{_postpone,_dismiss}.blade.php`
- [X] T020 Remove moments from `app/Http/Requests/SaveFieldValuesRequest.php`, `app/Actions/SetFieldValues.php`, `app/ViewModels/Vaults/ContactReminderBlock.php`, `resources/views/components/field-reminder.blade.php`, `resources/js/field-reminder.js`; the hint no longer says "ahead of time"
- [X] T021 Update `app/ViewModels/Vaults/{ReminderRow,ReminderViewModel,ReminderHistory,ReminderWords,VaultRemindersViewModel,VaultRemindersSentViewModel,VaultsViewModel}.php` and their views to the new columns only (no pause, no postpone, no outcome "Dismissed"); answers come in US1 and US4
- [X] T022 Update `app/Http/Dav/CalendarBackend.php` to list active deadlines from today, and its read only message to name no gesture that no longer exists (research R12)
- [X] T023 Update `database/seeders/ExampleAccountSeeder.php`: no moments; add two manual reminders and three answered deadlines (research R12)
- [X] T024 Rewrite every test that `grep -rlE 'moments|ReminderOccurrenceStatusEnum|postpon|dismiss|paus|original_scheduled_for|source_local_date|notified_at' tests` lists for reminders (about seventy files); run `php artisan test --compact` until green on SQLite
- [X] T025 [P] Test `ReminderSchedule` one date per cycle and the manual anchor in `tests/Unit/Helpers/ReminderScheduleTest.php`; `includePast` both ways in `tests/Unit/Actions/CancelReminderOccurrencesTest.php`; FR-023 in `tests/Unit/Actions/{GenerateReminderOccurrences,CreateReminder}Test.php`
- [X] T026 [P] Test FR-024 and one notification per channel in `tests/Unit/Actions/DeliverNotificationTest.php` and `NotifyReminderOccurrenceTest.php`: a held notification goes out the next day unless answered; a second sweep writes nothing
- [X] T027 [P] Test `ReminderOccurrenceCategoryEnum::for()` on the day before, the day, the day after in `tests/Unit/Enums/ReminderOccurrenceCategoryEnumTest.php`

## Phase 3: User Story 1, answer a deadline (P1) MVP

Independent test: a deadline due today offers only I remembered; answering removes it from the active list at once, and the next deadline is untouched.

- [X] T028 [US1] Create `app/Policies/ReminderOccurrencePolicy.php`: `remember` allows an active deadline whose `local_date` is on or before today in `users.timezone`, `forget` one strictly before; each denial carries its own wording (too early, already answered, no longer active) per research R5
- [X] T029 [US1] Create `app/Actions/AnswerReminderOccurrence.php` (`user`, `occurrence`, `ReminderAnswerEnum answer`): account check, `$user->authorize()`, conditional update on `answer IS NULL AND cancelled_at IS NULL` setting `answer` and `answered_at`, abandon unsent notifications, dispatch `LogUserAction` `ReminderOccurrenceAnswer`; zero rows updated refuses as already answered
- [X] T030 [P] [US1] Test the policy permitted, refused and `nobody_is_signed_in` for both abilities in `tests/Unit/Policies/ReminderOccurrencePolicyTest.php`
- [X] T031 [P] [US1] Test the action in `tests/Unit/Actions/AnswerReminderOccurrenceTest.php`: both answers, answered twice refused, cancelled refused, another account `ModelNotFoundException`, unsent notification abandoned, sent one kept, next deadline untouched, log dispatched
- [X] T032 [US1] Create `app/Http/Controllers/App/Vaults/ReminderOccurrenceAnswerController.php` (`update`, validates `answer` in `ReminderAnswerEnum::values()`, `abort_unless` vault match, policy refusal back with errors) and its route `reminders.occurrences.answer.update` in `routes/web.php`
- [X] T033 [US1] Add `rememberUrl()`, `canRemember()`, `canForget()` and their refusal reasons to `app/ViewModels/Vaults/ReminderRow.php`, asking `$user->inspect()`
- [X] T034 [US1] In `resources/views/app/vaults/reminders/row.blade.php` replace Dismiss with I remembered and I forgot (`x-form` PUT, secondary small, no modal); remove Remind me later from the menu
- [X] T035 [US1] Test the controller in `tests/Feature/Controllers/App/Vaults/ReminderOccurrenceAnswerControllerTest.php`: answers, invalid `answer` refused, one policy refusal shown on the form, wrong vault 404

## Phase 4: User Story 2, a deadline nobody answered stays open (P1)

Independent test: an unanswered deadline three weeks past is Overdue, still answerable, and never notified twice.

- [X] T036 [US2] Regroup `app/ViewModels/Vaults/VaultRemindersViewModel.php` by date into Overdue, Today, Upcoming via `ReminderOccurrenceCategoryEnum`; Overdue and Today list every active deadline, Upcoming one row per active rule (research R9)
- [X] T037 [US2] Change `app/View/Components/Vault/Sidebar.php::reminderCount()` and `VaultRemindersSentViewModel::activeCount()` to count active deadlines on or before today
- [X] T038 [P] [US2] Test the grouping, the overdue count and the one row per rule in `tests/Unit/ViewModels/Vaults/VaultRemindersViewModelTest.php`, and the count in `tests/Feature/Views/Components/SidebarTest.php`

## Phase 5: User Story 3, the contact's reminders (P1)

Independent test: a contact with a deadline in each category and two answers shows each group, each filter with its count, and the summary.

- [X] T039 [US3] Add routes `contacts.reminders.new`, `.create`, `.edit`, `.update`, `.index` (with `{filter?}` in `all, overdue, today, upcoming, history`) to `routes/web.php` before line 309, per `contracts/web.md`; add `reminders` to the reserved page keys in `app/ViewModels/Vaults/ContactViewModel.php`
- [X] T040 [US3] Create `app/ViewModels/Vaults/ContactRemindersViewModel.php`: filters with counts, groups with titles and notes from the design, history rows newest answer first, empty title and line per filter, rows with wording, date, relative day, cadence wording, "Follows" label and link, answer availability
- [X] T041 [US3] Create `app/ViewModels/Vaults/ReminderAnswerSummary.php`: over deadlines due in the last twelve months and not cancelled, the remembered and forgotten percentages, the waiting share, the total, and the chart label sentence
- [X] T042 [US3] Create `app/ViewModels/Vaults/ContactReminderForm.php` for the manual form: wording, `starts_on`, cadence options, interval, time, time zone, action URL, edit or create
- [X] T043 [US3] Create `app/Http/Controllers/App/Vaults/ContactReminderController.php` (`index`, `new`, `create`, `edit`, `update`), inline validation, delegating to `CreateReminder` and `UpdateReminder`; a field rule's edit redirects to the date panel
- [X] T044 [US3] Create `resources/views/app/vaults/contacts/reminders/index.blade.php` per `Contact Reminders.dc.html`: shell and breadcrumb like `set-aside.blade.php`, back link, title and Add a reminder, `_summary`, `x-chip` filters with `x-target.push`, groups, empty state
- [X] T045 [US3] Create `resources/views/app/vaults/contacts/reminders/_row.blade.php` (badges Overdue, You remembered, You forgot; answer forms; `x-actions-menu` with Edit and `x-delete-action` Delete) and `_summary.blade.php` (inline chart `role="img"` with `aria-label`, percentages as text)
- [X] T046 [US3] Create `resources/views/app/vaults/contacts/reminders/form.blade.php` for new and edit
- [X] T047 [US3] Add the "Reminders" header fact with the count awaiting an answer to `app/ViewModels/Vaults/ContactViewModel.php::headerFacts()` and `resources/views/app/vaults/contacts/show.blade.php`
- [X] T048 [P] [US3] Test `ContactRemindersViewModel` (filters, counts, groups, history order, empty wording, Upcoming one row per rule) in `tests/Unit/ViewModels/Vaults/ContactRemindersViewModelTest.php`
- [X] T049 [P] [US3] Test `ReminderAnswerSummary` (percentages, cancelled excluded, twelve month bound, nothing at all) in `tests/Unit/ViewModels/Vaults/ReminderAnswerSummaryTest.php`
- [X] T050 [P] [US3] Test the manual path of `CreateReminder` and `UpdateReminder` in their action tests
- [X] T051 [US3] Test `ContactReminderController` in `tests/Feature/Controllers/App/Vaults/ContactReminderControllerTest.php`: the screen renders its copy, each filter answers, create and update redirect with a flash, validation refuses, wrong vault 404, `new` is not read as a filter

## Phase 6: User Story 4, same rules on the vault page and the reminder page (P2)

Independent test: neither page offers postpone, dismiss or pause; both offer the answers the day allows.

- [X] T052 [US4] Add `VaultReminderController@history`, route `reminders.history`, a History tab in `resources/views/app/vaults/reminders/_tabs.blade.php`, and `resources/views/app/vaults/reminders/history.blade.php` reusing the answered rows (FR-026)
- [X] T053 [US4] In `app/ViewModels/Vaults/ReminderViewModel.php` and `resources/views/app/vaults/reminders/show.blade.php`: remove pause and resume and the moments list; the next deadline card offers the answers; the history lists every past deadline with its answer or "No answer"; delete behind `x-delete-action` warning it is final
- [X] T054 [P] [US4] Test `ReminderViewModel` and `ReminderHistory` answers in their unit tests; the history view model in `tests/Unit/ViewModels/Vaults/VaultRemindersHistoryViewModelTest.php`
- [X] T055 [US4] Rewrite `tests/Feature/Controllers/App/Vaults/{VaultReminderController,ReminderController}Test.php` for the History tab and the answer controls

## Phase 7: User Story 5, the API and MCP (P3)

Independent test: a client answers a deadline, is refused on a second answer and on a deadline due tomorrow, and reads the history.

- [X] T056 [P] [US5] Create `app/Http/Resources/ReminderResource.php` and `ReminderOccurrenceResource.php` with the attributes of `contracts/api.md`
- [X] T057 [US5] Create `app/Http/Controllers/Api/{ContactReminderController,ContactReminderOccurrenceController,ReminderOccurrenceAnswerController}.php` and the seven routes in `routes/api.php` per `contracts/api.md`; policy refusals as 422
- [X] T058 [US5] Document the seven endpoints in `resources/docs/api/20-reminders.php`
- [X] T059 [P] [US5] Test the three API controllers in `tests/Feature/Controllers/Api/`: shapes, pagination, category filter, 422 refusals, another account's records left out
- [X] T060 [US5] Add `reminder()` and `reminderOccurrence()` to `app/Mcp/Tools/MirrorsAnApiCapability.php`; create the seven tools of `contracts/api.md` in `app/Mcp/Tools/`; register them and add the answer paragraph in `app/Mcp/Servers/MonicaServer.php`
- [X] T061 [P] [US5] One test per tool in `tests/Unit/Mcp/Tools/`, including `it_names_the_api_capability_it_mirrors`
- [X] T062 [US5] Add the Reminders group to `app/ViewModels/Docs/McpDocsViewModel.php::toolGroups()`; update the count to 78 and the changing groups in `tests/Unit/ViewModels/Docs/McpDocsViewModelTest.php`

## Phase 8: Polish

- [X] T063 [P] Update `spec/spec.md` 10.1 to 10.3 and 14.2 and `spec/models/040` to `043`; the files keep `-done`, since the tables match the Fields lists once this ships
- [X] T064 Run `php artisan monica:localize`, translate what it reports in every locale, then `php artisan monica:localize --check`
- [X] T065 Run `vendor/bin/pint --dirty --format agent`, `vendor/bin/phpstan analyse --no-progress`, the whole suite, then walk `quickstart.md` scenarios 1 to 12

## Dependencies

T001, T002 → Phase 2 (T003 to T024 in order; T025 to T027 after T024) → US1 → US2 → US3 → US4 → US5 → Polish. US3 needs US1 (answer controller) and US2 (grouping); US4 needs US1; US5 needs US1 and the manual path of T016.

## Parallel examples

- Phase 2: T006, T007, T008, T011 together after T005.
- US1: T030 and T031 together after T029.
- US3: T048, T049, T050 together after T043.
- US5: T056 while T057 is written; T059 and T061 together.

## Strategy

MVP is Phase 2 plus US1 and US2: the new engine, the answers and the categories on the existing vault page. US3 (the contact screen) and US5 (API and MCP) may each be a separate pull request (plan, Risks).
