# Tasks: Possession Insurance

**Input**: [plan.md](plan.md), [spec.md](spec.md), [research.md](research.md), [data-model.md](data-model.md), [contracts/api.md](contracts/api.md), [quickstart.md](quickstart.md)
**Tests**: required by constitution principle III, written with each piece, per `.claude/rules/testing.md`. Data from The Office. Mirror the transaction files of 099 for every kind of file.

## Phase 1: Setup

- [X] T001 Amend `.specify/memory/constitution.md` to 2.14.0 (research R10): principle VII lists Possession insurance record as a fixed concept (sixteen), the possession sentence says it holds its insurance records, one sentence on what a record carries; Sync Impact Report at the top
- [X] T002 [P] Update `spec/models/062-possession-in-progress.md` and create `spec/models/067-possession-insurance-record-in-progress.md` from data-model.md, in the shape of 066

## Phase 2: Foundational

- [X] T003 [P] Create `app/Enums/PossessionInsuranceStatusEnum.php` with `label()`, `tone()`, `icon()`, `position()` and `translationKeys()` as the data-model.md table; test in `tests/Unit/Enums/PossessionInsuranceStatusEnumTest.php`
- [X] T004 [P] Add `PossessionInsuranceRecordCreation`, `PossessionInsuranceRecordUpdate`, `PossessionInsuranceRecordDeletion` to `app/Enums/UserActionEnum.php`, copy in the voice of the transaction cases
- [X] T005 Create migration `database/migrations/2026_10_27_000004_create_possession_insurance_records_table.php` per data-model.md
- [X] T006 Create `app/Models/PossessionInsuranceRecord.php` (full docblock, `ResolvesModelInVault`, casts) and `database/factories/PossessionInsuranceRecordFactory.php`
- [X] T007 Add `insuranceRecords(): HasMany` and `currentInsuranceRecord()` to `app/Models/Possession.php` (research R4); test in `tests/Unit/Models/PossessionTest.php`

## Phase 3: User Story 1, record an insurance record (P1) MVP

Independent test: on a possession without records, add one and find it listed with its insured value.

- [X] T008 [US1] Create `app/Actions/CreatePossessionInsuranceRecord.php` (account check, figure bounds, end not before start, journal) and its test
- [X] T009 [US1] Create `app/ViewModels/Vaults/PossessionInsuranceViewModel.php` delegating header and sections to `PossessionViewModel`, with the form's statuses, currencies and preselected currency
- [X] T010 [US1] Routes in `routes/web.php` after the transaction routes: index, create, edit, update, destroy under `possessions.insuranceRecords.*`
- [X] T011 [US1] Create `app/Http/Controllers/App/Vaults/PossessionInsuranceRecordController.php` with `index` and `create`, inline validation
- [X] T012 [US1] Create `resources/views/app/vaults/possessions/insurance.blade.php` and `_insurance-form.blade.php` from the design, without documents (FR-009)
- [X] T013 [US1] In `PossessionViewModel::sections()` move Insurance under Transactions with its URL and count; update `PossessionViewModelTest`
- [X] T014 [US1] Controller test `tests/Feature/Controllers/App/Vaults/PossessionInsuranceRecordControllerTest.php`: index renders, create stores and flashes, validation error, wrong possession 404
- [X] T015 [P] [US1] API `create` in `app/Http/Controllers/Api/Possessions/PossessionInsuranceRecordController.php`, `app/Http/Resources/PossessionInsuranceRecordResource.php`, route; tests
- [X] T016 [P] [US1] MCP `app/Mcp/Tools/CreatePossessionInsuranceRecord.php`, registered on the server; test

## Phase 4: User Story 2, read how the possession is covered (P1)

Independent test: with an active record below the declared value, the summary and the warning read the gap.

- [X] T017 [US2] View model: summary (insured for now, current estimated value, gap, next renewal per R5), warning text, list in order with each entry's parts, contact links, stamps
- [X] T018 [US2] Render the summary, the warning, the help mark and the list in `insurance.blade.php`
- [X] T019 [US2] Test in `tests/Unit/ViewModels/Vaults/PossessionInsuranceViewModelTest.php` per quickstart
- [X] T020 [P] [US2] API `index` and `show`; MCP `ListPossessionInsuranceRecords` and `GetPossessionInsuranceRecord`; tests

## Phase 5: User Story 3, correct or remove a record (P2)

Independent test: edit a record's policy number, then delete a record after its warning.

- [X] T021 [US3] Actions `UpdatePossessionInsuranceRecord` and `DestroyPossessionInsuranceRecord` and their tests
- [X] T022 [US3] Controller `edit`, `update`, `destroy`; the filled form in place of the entry; the delete warning; tests
- [X] T023 [P] [US3] API `update` and `destroy`; MCP update and delete tools; tests

## Phase 6: User Story 4, the Overview fact (P3)

- [X] T024 [US4] `PossessionViewModel::facts()` adds "Insured" (research R8); test

## Phase 7: Polish

- [X] T025 API reference `resources/docs/api/31-possession-insurance-records.php`, via the `api-docs-writer` skill
- [X] T026 [P] Guide `docs/portal/en/9-templates-and-customisation/16-possessions.md` and `docs/FEATURES.md`
- [X] T027 `php artisan monica:localize`, translate every reported string in every locale, `--check` passes
- [X] T028 `vendor/bin/phpstan analyse --no-progress`, `vendor/bin/pint --dirty --format agent`, `php artisan test --compact`
- [ ] T029 The quickstart's checks by hand at monica.test

## Dependencies

Setup, then Foundational, then US1. US2 and US3 need US1's screen. US4 needs only Foundational. Polish last.
Parallel: T003 and T004; within each story the tasks marked [P] (API, MCP) beside the web work.

## Strategy

MVP is US1 plus US2: record and read. Then US3, then US4.
