# Tasks: Possession Valuations

**Input**: [plan.md](plan.md), [spec.md](spec.md), [research.md](research.md), [data-model.md](data-model.md), [contracts/api.md](contracts/api.md), [quickstart.md](quickstart.md)
**Tests**: required by constitution principle III, written with each piece, per `.claude/rules/testing.md`. Data from The Office. Mirror the transaction files of 099 (documents included) and the insurance files of 102 for every kind of file.

## Phase 1: Setup

- [X] T001 Amend `.specify/memory/constitution.md` to 2.17.0 (research R13): principle VII lists Possession valuation as a fixed concept (twenty three), the possession sentence says it holds its valuations, one sentence on what a valuation carries; Sync Impact Report at the top
- [X] T002 [P] Update `spec/models/062-possession-in-progress.md` and create `spec/models/074-possession-valuation-in-progress.md` from data-model.md, in the shape of 067

## Phase 2: Foundational

- [X] T003 [P] Create `app/Enums/PossessionValuationTypeEnum.php` (six cases, `label()`, `icon()`, `translationKeys()`, values and icons as data-model.md) and `app/Enums/PossessionValuationConfidenceEnum.php` (`unknown`, `certain`, `probable`, `uncertain`, `label()`); test in `tests/Unit/Enums/PossessionValuationTypeEnumTest.php`
- [X] T004 [P] Add `PossessionValuationCreation`, `PossessionValuationDeletion`, `PossessionValuationDocumentAddition`, `PossessionValuationDocumentRemoval` to `app/Enums/UserActionEnum.php`, copy in the voice of the transaction cases
- [X] T005 Create `database/migrations/2026_11_12_000000_create_possession_valuations_table.php`: `type` string required, `valued_on` date required, `confidence` string default `unknown`, `amount` decimal(12, 2) required, `currency` string(3) required, `valuer`, `method`, `reference_number`, `source_url` string(255) nullable, `note` text nullable, index on `(possession_id, valued_on)`; and `2026_11_12_000001_create_possession_valuation_file_table.php` copied from `possession_transaction_file`
- [X] T006 Create `app/Models/PossessionValuation.php` (full docblock, `ResolvesModelInVault`, casts, `documents(): BelongsToMany`) and `database/factories/PossessionValuationFactory.php`
- [X] T007 Add `valuations(): HasMany`, `currentValuation()` (latest `valued_on`, ties by highest id) and `currentValue()` (valuation, else declared value, else null) to `app/Models/Possession.php` (research R4); test in `tests/Unit/Models/PossessionTest.php`

## Phase 3: User Story 1, record a valuation (P1) MVP

Independent test: on a possession without valuations, record one with a PDF and find it listed with its amount and document.

- [X] T008 [US1] Create `app/Actions/CreatePossessionValuation.php` (account check, amount 0 to `MAX_DECLARED_VALUE`, valid `source_url`, note at most 5000 characters, journal) and `tests/Unit/Actions/CreatePossessionValuationTest.php`
- [X] T009 [P] [US1] Create `app/Actions/AttachPossessionValuationDocument.php` and `DetachPossessionValuationDocument.php` from the transaction pair, with their tests
- [X] T010 [US1] Create `app/ViewModels/Vaults/PossessionValuationViewModel.php` delegating header and sections to `PossessionViewModel`, with the form's types, confidences, currencies, preselected currency and the preview data and four translated sentences (research R8)
- [X] T011 [US1] Routes in `routes/web.php` after the insurance routes: `possessions.valuations.index`, `possessions.valuations.create`, `possessions.valuations.destroy`, no flag on the route; the view model's `canAttachDocuments()` asks `FlagState::isOn('file-upload', ...)` and the controller ignores a file when it is off, as `PossessionTransactionsViewModel` does
- [X] T012 [US1] Create `app/Http/Controllers/App/Vaults/PossessionValuationController.php` with `index` and `create` (inline validation, optional `document` attached through the attach action)
- [X] T013 [US1] Create `resources/views/app/vaults/possessions/valuations.blade.php` and `_valuation-form.blade.php` from the design: form, preview line `aria-live="polite"`, opening on `#new-valuation`, `x-file-input` as `_transaction-form`
- [X] T014 [US1] In `PossessionViewModel::sections()` give Valuations its URL and count; update `tests/Unit/ViewModels/Vaults/PossessionViewModelTest.php`
- [X] T015 [US1] `tests/Feature/Controllers/App/Vaults/PossessionValuationControllerTest.php`: index renders, create stores and flashes, with a file, validation error, wrong possession 404
- [X] T016 [P] [US1] API `create` in `app/Http/Controllers/Api/Possessions/PossessionValuationController.php`, `app/Http/Resources/PossessionValuationResource.php` per contracts/api.md, `PossessionValuationDocumentController` (create, destroy), routes in `routes/api.php`; tests
- [X] T017 [P] [US1] MCP `app/Mcp/Tools/CreatePossessionValuation.php`, registered on the server; test

## Phase 4: User Story 2, read what the possession is worth (P1)

Independent test: with three valuations and an insurance record below the latest, the summary, the history and the gap read right.

- [X] T018 [US2] View model: summary (current value, change from the previous, insured for and gap per R11), history newest first with each entry's change, parts, note, documents and stamp; empty state
- [X] T019 [US2] Render the summary, the help mark (`x-help` pointing at the portal page), the history and the empty state in `valuations.blade.php`
- [X] T020 [US2] `tests/Unit/ViewModels/Vaults/PossessionValuationViewModelTest.php` per quickstart
- [X] T021 [P] [US2] API `index` and `show`; MCP `ListPossessionValuations` and `GetPossessionValuation`; tests

## Phase 5: User Story 3, remove a valuation (P2)

Independent test: delete the current valuation after its warning; the previous one becomes current.

- [X] T022 [US3] `app/Actions/DestroyPossessionValuation.php` (detaches its documents through `DetachFile`, journal) and its test
- [X] T023 [US3] Controller `destroy`; the delete warning per entry with its wording from the view model; tests
- [X] T024 [P] [US3] API `destroy`; MCP `DeletePossessionValuation`; tests

## Phase 6: User Story 4, the value elsewhere on the page (P3)

Independent test: record a valuation, then read it on the Overview and in Insurance.

- [X] T025 [US4] Overview value panel in `show.blade.php` from `PossessionViewModel`: amount, type, valuer, date, change, "Record a valuation" link; declared value without one; test
- [X] T026 [US4] "Record a valuation" item in the Edit menu of `_header.blade.php`, linking to the section with `#new-valuation`
- [X] T027 [US4] `PossessionInsuranceViewModel` reads `currentValue()` with the two wordings of research R5; update `tests/Unit/ViewModels/Vaults/PossessionInsuranceViewModelTest.php`

## Phase 7: Polish

- [X] T028 API reference `resources/docs/api/36-possession-valuations.php`, via the `api-docs-writer` skill
- [X] T029 [P] Guide `docs/portal/en/9-templates-and-customisation/20-possession-valuations.md`, lines in `16-possessions.md` and `18-possession-insurance.md`, via the `writer` skill; `docs/FEATURES.md`
- [X] T030 `php artisan monica:localize`, translate every reported string in every locale, `--check` passes
- [X] T031 `vendor/bin/phpstan analyse --no-progress`, `vendor/bin/pint --dirty --format agent`, `php artisan test --compact`
- [X] T033 Example valuations in the seeded account: `database/seeders/ExamplePossessions.php` and `ExampleAccountSeeder::createPossessions()`, asserted in `tests/Unit/Seeders/ExampleAccountSeederTest.php`
- [ ] T032 The quickstart's checks by hand at erica.test

## Dependencies

Setup, then Foundational, then US1. US2 and US3 need US1's screen. US4 needs only Foundational. Polish last.
Parallel: T002 beside T001; T003 and T004; within each story the tasks marked [P] (documents, API, MCP) beside the web work.

## Strategy

MVP is US1 plus US2: record and read. Then US3, then US4.
