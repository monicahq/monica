# Tasks: Possession Maintenance

**Input**: [plan.md](plan.md), [spec.md](spec.md), [research.md](research.md), [data-model.md](data-model.md), [contracts/api.md](contracts/api.md), [quickstart.md](quickstart.md)
**Tests**: required by constitution principle III, written with each piece, per `.claude/rules/testing.md`. Data from The Office. Mirror the insurance files of 102 for every kind of file.

## Phase 1: Setup

- [X] T001 Amend `.specify/memory/constitution.md` to 2.15.0 (research R10): principle VII lists Possession maintenance record as a fixed concept (seventeen), the possession sentence says it holds its maintenance records, one sentence on what a record carries; Sync Impact Report at the top
- [X] T002 [P] Update `spec/models/062-possession-in-progress.md` and `spec/models/065-possession-condition-in-progress.md` (named by maintenance records), create `spec/models/068-possession-maintenance-record-in-progress.md` from data-model.md, in the shape of 067

## Phase 2: Foundational

- [X] T003 [P] Create `app/Enums/PossessionMaintenanceTypeEnum.php` with `label()`, `colour(): VaultColourEnum` and `translationKeys()` per the data-model.md table; test in `tests/Unit/Enums/PossessionMaintenanceTypeEnumTest.php`, including that no two types share a colour
- [X] T004 [P] Add `PossessionMaintenanceRecordCreation`, `PossessionMaintenanceRecordUpdate`, `PossessionMaintenanceRecordDeletion` to `app/Enums/UserActionEnum.php`, in the voice of the insurance cases
- [X] T005 Create migration `database/migrations/2026_10_27_000005_create_possession_maintenance_records_table.php` per data-model.md: `type` string, `title` string(255), `performed_by` string(255) nullable, `performed_on` date, `cost` decimal(12, 2) nullable, `currency` string(3) nullable, `condition_before_id` and `condition_after_id` nullable to `possession_conditions` with `nullOnDelete()`, `next_due_on` date nullable, `description` text nullable, possession cascade on delete, index on `possession_id`
- [X] T006 Create `app/Models/PossessionMaintenanceRecord.php` (full docblock, `ResolvesModelInVault`, `#[RouteKey('uuid')]`, casts `cost` `decimal:2`, `type` and `currency` enums, dates; `conditionBefore()` and `conditionAfter()`) and `database/factories/PossessionMaintenanceRecordFactory.php`
- [X] T007 Add `maintenanceRecords(): HasMany` ordered by `performed_on` then `id` descending, and `overdueMaintenanceRecord(CarbonImmutable $today)` to `app/Models/Possession.php` (research R5); rewrite the "nothing points at a condition" sentence in `app/Models/PossessionCondition.php`; tests in `tests/Unit/Models/PossessionTest.php`
- [X] T008 Create `app/Helpers/PossessionMaintenanceRecordAttributes.php`, the rules create and update share: plain text title required, cost 0 to `MAX_DECLARED_VALUE`, currency required with a cost and cleared without one, `performed_on` not after today in the user's timezone, `next_due_on` not before `performed_on` (R6), description at most 5000 characters, conditions of the user's account or `ModelNotFoundException` (R4)

## Phase 3: User Story 1, record a maintenance job (P1) MVP

Independent test: on a possession without records, add one and find it listed with the count in the sections list.

- [X] T009 [US1] Create `app/Actions/CreatePossessionMaintenanceRecord.php` (account check, attributes helper, journal) and `tests/Unit/Actions/CreatePossessionMaintenanceRecordTest.php` per quickstart
- [X] T010 [US1] Create `app/ViewModels/Vaults/PossessionMaintenanceViewModel.php` delegating header and sections to `PossessionViewModel`, with the form's types, the account's conditions in order with "Not recorded" first, currencies and the preselected currency
- [X] T011 [US1] Routes in `routes/web.php` after the insurance routes: index, create, edit, update, destroy under `possessions.maintenanceRecords.*`, path `maintenance`
- [X] T012 [US1] Create `app/Http/Controllers/App/Vaults/PossessionMaintenanceRecordController.php` with `index` and `create`, inline validation, `abort_unless` on the possession
- [X] T013 [US1] Create `resources/views/app/vaults/possessions/maintenance.blade.php` and `_maintenance-form.blade.php` (research R7), without documents (FR-008)
- [X] T014 [US1] In `PossessionViewModel::sections()` give `maintenance` its URL and count, place and colour unchanged; update `tests/Unit/ViewModels/Vaults/PossessionViewModelTest.php`
- [X] T015 [US1] Controller test `tests/Feature/Controllers/App/Vaults/PossessionMaintenanceRecordControllerTest.php`: index renders, create stores and flashes, a validation error, wrong possession 404

## Phase 4: User Story 2, read the maintenance history (P1)

Independent test: with two records, the latest past its next due date, only it reads "Overdue".

- [X] T016 [US2] View model: each card's type chip (label and colour), title, cost or "No cost", "Performed by" or "Not recorded", date, condition wording (data-model.md), "Next due" or "Not scheduled", description, overdue flag from `overdueMaintenanceRecord()` with today in the user's timezone; the empty list
- [X] T017 [US2] Render the cards, the help mark, the overdue badge and the empty list in `maintenance.blade.php`; truncated text keeps its full value in `title` and for assistive technology
- [X] T018 [US2] Test in `tests/Unit/ViewModels/Vaults/PossessionMaintenanceViewModelTest.php` per quickstart

## Phase 5: User Story 3, correct or remove a record (P2)

Independent test: edit a record's cost, then delete a record after its warning.

- [X] T019 [US3] Actions `app/Actions/UpdatePossessionMaintenanceRecord.php` and `app/Actions/DestroyPossessionMaintenanceRecord.php` and their tests
- [X] T020 [US3] Controller `edit`, `update`, `destroy`; the filled form in place of the card; the delete warning "Delete the “:title” record?" with `x-delete-action`; controller tests

## Phase 6: User Story 4, the API, the assistant and the docs (P2)

Independent test: create, read, update, list and delete a record through the API and through each tool.

- [X] T021 [US4] `app/Http/Resources/PossessionMaintenanceRecordResource.php` per contracts/api.md, `is_overdue` from `overdueMaintenanceRecord()`
- [X] T022 [US4] `app/Http/Controllers/Api/Possessions/PossessionMaintenanceRecordController.php` with the five methods and routes in `routes/api.php` after the insurance routes; `tests/Feature/Controllers/Api/Possessions/PossessionMaintenanceRecordControllerTest.php`
- [X] T023 [P] [US4] MCP tools `app/Mcp/Tools/{List,Get,Create,Update,Delete}PossessionMaintenanceRecord(s).php`, registered on `app/Mcp/Servers/MonicaServer.php`; tests in `tests/Unit/Mcp/Tools/`
- [X] T024 [US4] API reference `resources/docs/api/32-possession-maintenance-records.php`, through the `api-docs-writer` skill
- [X] T025 [P] [US4] Guide `docs/portal/en/9-templates-and-customisation/19-possession-maintenance.md` through the `writer` skill, linked from `16-possessions.md`

## Phase 7: Polish

- [X] T026 Condition delete warning: `PossessionConditionsViewModel` counts the maintenance records naming each condition, `_row.blade.php` adds a `trans_choice()` line only above zero (research R4); test
- [X] T027 [P] Entry in `docs/FEATURES.md`
- [X] T028 `php artisan monica:localize`, translate every reported string in every locale, `--check` passes
- [X] T029 `vendor/bin/phpstan analyse --no-progress`, `vendor/bin/pint --dirty --format agent`, `php artisan test --compact`
- [ ] T030 The quickstart's checks by hand at erica.test

## Dependencies

Setup, then Foundational, then US1. US2 and US3 need US1's screen. US4 needs Foundational and the actions of US1 and US3. Polish last.
Parallel: T002 beside T003 and T004; T023 and T025 beside the API controller work.

## Strategy

MVP is US1 plus US2: record and read. Then US3, then US4, then Polish.
