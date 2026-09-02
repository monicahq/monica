---

description: "Task list for the reminder foundation"
---

# Tasks: Reminder foundation on a date field

**Input**: [spec.md](spec.md), [plan.md](plan.md), [research.md](research.md), [data-model.md](data-model.md), [quickstart.md](quickstart.md)

**Tests**: not optional. Principle III is non-negotiable, so every new class arrives with its test and every altered assertion is updated in the same task.

**Format**: `[ID] [P?] [Story?] Description with file path`. `[P]` means a different file and no unfinished dependency.

---

## Phase 1: Setup (the vocabularies)

**Purpose**: the closed vocabularies the rest of the feature is written in. Pure value types, no dependency on anything.

- [X] T001 [P] Create `ReminderSourceEnum` with the single case `FieldValue` in `app/Enums/ReminderSourceEnum.php`
- [X] T002 [P] Create `ReminderCadenceEnum` with `Once`, `Weekly`, `Monthly`, `Yearly` and a `recurs()` method in `app/Enums/ReminderCadenceEnum.php`
- [X] T003 [P] Create `ReminderMomentDirectionEnum` with `On`, `Before`, `After` in `app/Enums/ReminderMomentDirectionEnum.php`
- [X] T004 [P] Create `ReminderMomentUnitEnum` with `Days`, `Weeks`, `Months` in `app/Enums/ReminderMomentUnitEnum.php`
- [X] T005 [P] Create `ReminderInactiveReasonEnum` with `ValueCleared`, `RecordArchived`, `RecordDeleted`, `FieldRemoved` in `app/Enums/ReminderInactiveReasonEnum.php`
- [X] T006 [P] Create the `ReminderNeedsADate` exception in `app/Exceptions/ReminderNeedsADate.php`
- [X] T007 [P] Test `recurs()` across the four cadences in `tests/Unit/Enums/ReminderCadenceEnumTest.php` (the other four enums are case lists with no behaviour and get no test)

**Checkpoint**: the vocabularies exist and nothing reads them yet.

---

## Phase 2: Foundational (blocking every story)

**Purpose**: the field type becomes the only switch, the table exists, and the two things all three stories share are built.

**⚠️ CRITICAL**: no story work may begin until this phase is complete.

### The field type is the only switch (FR-001)

- [X] T008 Drop the `is_reminder_source` column from `database/migrations/2026_08_09_000006_create_fields_table.php`
- [X] T009 Make `canDriveAReminder()` true for `Date` as well as `RecurringDate` in `app/Enums/FieldTypeEnum.php`, and flip the assertion in `tests/Unit/Enums/FieldTypeEnumTest.php`
- [X] T010 [P] Remove `is_reminder_source` from `app/Models/Field.php` and `database/factories/FieldFactory.php`, updating `tests/Unit/Models/FieldTest.php`
- [X] T011 [P] Remove the `isReminderSource` input from `app/Actions/CreateField.php` and `app/Actions/UpdateField.php`, updating `tests/Unit/Actions/CreateFieldTest.php` and `tests/Unit/Actions/UpdateFieldTest.php`
- [X] T012 [P] Remove `isReminderSource()` from `app/Enums/DefaultFieldEnum.php` and its use in `app/Jobs/CreateDefaultTemplate.php`, updating `tests/Unit/Jobs/CreateDefaultTemplateTest.php`
- [X] T013 Keep `is_reminder_source` in the allowed-key list of `app/Helpers/StructureFileReader.php` but read and ignore it, dropping the refusal and its string, and remove it from `app/Helpers/StructureWriter.php` and `app/Helpers/MarketplacePayload.php`
- [X] T014 Add a test that a structure file still carrying `is_reminder_source` imports without refusal in `tests/Unit/Helpers/StructureFileReaderTest.php`

### The rule's storage (data-model.md)

- [X] T015 Create the `reminders` table in `database/migrations/2026_09_02_000000_create_reminders_table.php`, columns per data-model.md, foreign keys at the foot without `constrained()`, `up()` only
- [X] T016 Create the `Reminder` model with its casts, `belongsTo` and `morphTo` relations, soft deletes and `active`/`inactive` scopes in `app/Models/Reminder.php`
- [X] T017 [P] Create `database/factories/ReminderFactory.php` with states for each cadence and each inactive reason
- [X] T018 [P] Add the reverse relations: `hasOne` on `app/Models/FieldValue.php`, `hasMany` on `app/Models/Field.php`, `morphMany` on `app/Models/Contact.php`
- [X] T019 Test the model's relations, casts, scopes and soft deletion in `tests/Unit/Models/ReminderTest.php`
- [X] T020 [P] Assert the reverse relations in `tests/Unit/Models/FieldValueTest.php`, `tests/Unit/Models/FieldTest.php` and `tests/Unit/Models/ContactTest.php`

### What all three stories share

- [X] T021 [P] Create `ReminderSchedule` computing the next date from a value and a cadence, and each moment's date, in `app/Helpers/ReminderSchedule.php`
- [X] T022 Test `ReminderSchedule` against 29 February, a short month, an unknown year, every cadence and a rhythm interval in `tests/Unit/Helpers/ReminderScheduleTest.php`
- [X] T023 [P] Create the `ReminderMoments` validation rule refusing an empty list, a duplicate moment, an amount outside 1 to 365, an unknown direction and an unknown unit in `app/Rules/ReminderMoments.php`
- [X] T024 Test every refusal and the shape it accepts in `tests/Unit/Rules/ReminderMomentsTest.php`
- [X] T025 [P] Add the five log actions `ReminderCreated`, `ReminderUpdated`, `ReminderRemoved`, `ReminderDeactivated`, `ReminderReactivated` to `app/Enums/UserActionEnum.php`
- [X] T026 Create `DeactivateReminders` taking a `Contact|Entity|Field` and a reason in `app/Actions/DeactivateReminders.php`
- [X] T027 Test that it silences only the rules in scope, records the reason and the moment, and refuses another account in `tests/Unit/Actions/DeactivateRemindersTest.php`

**Checkpoint**: the table exists, a rule can be built by hand and silenced, and no screen mentions it.

---

## Phase 3: User Story 1 - Ask to be reminded of a date (Priority: P1) 🎯 MVP

**Goal**: ticking the box under a date and saving the block writes a rule that reads that value.

**Independent Test**: tick the box on the supplied **Birthday** field, choose seven days before and on the day, save, and one rule exists against that value carrying both moments.

- [X] T028 [US1] Create `CreateReminder`, freezing the zone, defaulting the wording to the field label and the record name, and refusing a second live rule on one value, in `app/Actions/CreateReminder.php`
- [X] T029 [US1] Test creation, the frozen zone, the defaulted wording, the one-live-rule refusal, the log line and another account's value in `tests/Unit/Actions/CreateReminderTest.php`
- [X] T030 [P] [US1] Create the options panel: wording, the two cadence cards, the frequency select and its interval, the moment rows, the local time, the zone select, the summary and the next date, in `resources/views/components/field-reminder.blade.php`
- [X] T031 [US1] Draw the panel under the control for `date` and `recurring_date` in `resources/views/components/field-input.blade.php`, the checkbox carrying `aria-controls` and `aria-expanded` over a panel toggled with `hidden`
- [X] T032 [US1] Test the panel's labels, its disclosure attributes, its moment rows and its summary in `tests/Feature/Views/Components/FieldReminderTest.php`
- [X] T033 [US1] Give each date field's block its reminder data, the moment phrasing, the summary sentence and the next date in `app/ViewModels/Vaults/ContactViewModel.php`, reading zones from `App\Helpers\TimezoneCatalogue`
- [X] T034 [US1] Test that data, the phrasing at one and at several, and the empty case in `tests/Unit/ViewModels/Vaults/ContactViewModelTest.php`
- [X] T035 [US1] Add the `reminders` rules, keyed by field and by repetition, to `app/Http/Requests/SaveFieldValuesRequest.php`, using `ReminderMoments` and refusing a zone `TimezoneCatalogue` does not know
- [X] T036 [US1] Accept a `reminders` array in `app/Actions/SetFieldValues.php` and create a rule after the value it reads is written, inside the same transaction, throwing `ReminderNeedsADate` when the box is ticked over an empty date
- [X] T037 [US1] Pass the reminders through and catch `ReminderNeedsADate` back onto the open block in `app/Http/Controllers/App/Vaults/ContactSectionController.php`
- [X] T038 [US1] Test creation through the save, inside a repetition, and the empty-date refusal writing neither value nor rule, in `tests/Unit/Actions/SetFieldValuesTest.php` and `tests/Feature/Controllers/App/Vaults/ContactSectionControllerTest.php`

**Checkpoint**: a reminder can be added to a date and is stored. Nothing can yet change or stop it.

---

## Phase 4: User Story 2 - Change it, or stop it (Priority: P2)

**Goal**: the block reads a saved rule back, corrections follow the date, and unticking removes the rule and keeps the date.

**Independent Test**: untick the box, save, and the date is still there while the rule is gone and restorable.

- [X] T039 [P] [US2] Create `UpdateReminder`, which never rewrites the frozen zone unless the zone itself was changed, in `app/Actions/UpdateReminder.php`
- [X] T040 [US2] Test each field changing, the zone staying frozen otherwise, the log line and another account's rule in `tests/Unit/Actions/UpdateReminderTest.php`
- [X] T041 [P] [US2] Create `DestroyReminder` soft deleting the rule and leaving the value untouched in `app/Actions/DestroyReminder.php`
- [X] T042 [US2] Test the soft delete, the value surviving, the log line and another account's rule in `tests/Unit/Actions/DestroyReminderTest.php`
- [X] T043 [US2] Route the save to update an existing rule, destroy it when the box arrives unticked, and deactivate it with `ValueCleared` when the date arrives empty, in `app/Actions/SetFieldValues.php`
- [X] T044 [US2] Read a saved rule back into every control, and warn before the save takes a rule away, in `resources/views/components/field-reminder.blade.php` and `app/ViewModels/Vaults/ContactViewModel.php`
- [X] T045 [US2] Test reading back, correcting the date, unticking, and clearing the date, in `tests/Unit/Actions/SetFieldValuesTest.php` and `tests/Feature/Controllers/App/Vaults/ContactSectionControllerTest.php`

**Checkpoint**: the rule is fully editable from the block that owns it.

---

## Phase 5: User Story 3 - A rule that has gone quiet says why (Priority: P3)

**Goal**: the five causes silence a rule with its reason on the row and in words on the screen, and four of them are reversible.

**Independent Test**: archive the contact and the rule reads inactive because the record is archived; unarchive and it is active again.

- [X] T046 [P] [US3] Create `ReactivateReminders` reviving only the rules whose `inactive_reason` matches the cause being reversed, in `app/Actions/ReactivateReminders.php`
- [X] T047 [US3] Test that one cause never revives another's rules, that the reason and moment are cleared, and that another account is refused, in `tests/Unit/Actions/ReactivateRemindersTest.php`
- [X] T048 [US3] Silence and revive with `RecordArchived` in `app/Actions/ArchiveContact.php` and `app/Actions/UnarchiveContact.php`, replacing the comments that say reminders do not exist
- [X] T049 [P] [US3] Silence and revive with `RecordDeleted` in `app/Actions/DestroyContact.php` and `app/Actions/RestoreContact.php`
- [X] T050 [P] [US3] Silence with `FieldRemoved` in `app/Actions/DetachFieldValues.php` and revive in `app/Actions/ReattachFieldValues.php` (`app/Actions/DestroyField.php` is not wired: it only ever removes a field from a draft, which can hold no values and therefore no rules)
- [X] T051 [US3] Say why a rule is quiet, in words rather than by colour, in `resources/views/components/field-reminder.blade.php` and `app/ViewModels/Vaults/ContactViewModel.php`
- [X] T052 [US3] Extend `tests/Unit/Actions/ArchiveContactTest.php`, `UnarchiveContactTest.php`, `DestroyContactTest.php`, `RestoreContactTest.php`, `DestroyFieldTest.php`, `DetachFieldValuesTest.php` and `ReattachFieldValuesTest.php` with the rule they now silence or revive
- [X] T053 [US3] Test the reason reading on the screen in `tests/Feature/Views/Components/FieldReminderTest.php`

**Checkpoint**: every one of the five causes is visible and four are reversible.

---

## Phase 6: Polish & cross-cutting

- [X] T054 Run `php artisan monica:localize` and translate everything it reports in all nine `lang/*.json` files, counted phrases through `trans_choice()`
- [X] T055 Run `php artisan monica:localize --check` and correct what it refuses in `lang/*.json`
- [X] T056 Rename `spec/models/040-reminder-not-yet.md` to `040-reminder-in-progress.md`, apply the corrections table from data-model.md, and write its **Not done yet** section
- [X] T057 [P] Update `spec/models/017-field-done.md` where it names `is_reminder_source` and where it says only a recurring date drives a rule
- [X] T058 [P] Update `spec/models/022-field-value-done.md` for the rule it now holds, and the status table in `spec/models/README.md`
- [X] T059 Audit `resources/views/components/field-reminder.blade.php` against WCAG 2.2 AA: keyboard alone, focus visible, 200%, both themes, reduced motion (verified structurally; no screen reader and no automated checker was run against a live page)
- [X] T060 Run `vendor/bin/pint --dirty --format agent` over every `app/`, `database/` and `tests/` file this feature touched
- [X] T061 Walk `quickstart.md` and run `composer test`

---

## Dependencies & execution order

- **Phase 1** has no dependencies. T001–T007 are all parallel.
- **Phase 2** depends on Phase 1 and blocks every story. Inside it: T008 before T009–T014; T015 before T016–T020; T016 before T021–T024 and T026; T026 before T027.
- **Phase 3 (US1)** depends on Phase 2. Inside it: T028 before T036; T030 before T031 and T032; T033 before T034; T035–T037 before T038.
- **Phase 4 (US2)** depends on Phase 2. It touches `SetFieldValues` and the component, so it follows US1 in practice rather than by dependency.
- **Phase 5 (US3)** depends on Phase 2 alone, and on nothing in US1 or US2. It can be built alongside either.
- **Phase 6** depends on every story that is being shipped.

### Parallel opportunities

- T001–T007 together.
- T010, T011, T012 together, after T008 and T009.
- T017, T018 together after T015 and T016; T020, T021, T023, T025 together after T016.
- T039 and T041 together; T046, T049, T050 together.
- US3 alongside US1, since the only file they share is the component.

## Implementation strategy

MVP is Phase 1, Phase 2 and Phase 3: a reminder can be added to a date and is stored. Stop there and
validate before Phase 4. US3 is worth pulling forward if the archive comments matter more than
editing, since it depends on nothing US1 builds.
