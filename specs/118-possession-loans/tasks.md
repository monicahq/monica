# Tasks: Possession Loans

**Input**: [plan.md](plan.md), [spec.md](spec.md), [research.md](research.md), [data-model.md](data-model.md), [contracts/api.md](contracts/api.md), [quickstart.md](quickstart.md)
**Tests**: required by constitution principle III, written with each piece, per `.claude/rules/testing.md`. Data from The Office. Mirror the provenance files of 116 and the stay files of 117 for every kind of file.

## Phase 1: Setup

- [X] T001 Amend `.specify/memory/constitution.md` to 2.20.0 (research R14): principle VII lists Possession loan (twenty six), the possession sentence says it holds its loans, one sentence on the loan; Sync Impact Report at the top
- [X] T002 [P] Update `spec/models/062-possession-in-progress.md` and create `spec/models/077-possession-loan-in-progress.md` from data-model.md, in the shape of 076

## Phase 2: Foundational

- [X] T003 [P] Create `app/Enums/PossessionLoanDirectionEnum.php` (`Lent` = `lent`, `Borrowed` = `borrowed`) and `app/Enums/PossessionLoanStatusEnum.php` (`Planned`, `Active`, `Returned`), `Translatable`, labels per data-model.md; tests in `tests/Unit/Enums/`
- [X] T004 [P] Add `PossessionLoanCreation`, `PossessionLoanUpdate`, `PossessionLoanReturn`, `PossessionLoanDeletion` to `app/Enums/UserActionEnum.php` (research R13), copy in the voice of the provenance cases
- [X] T005 Create `database/migrations/2026_11_12_000004_create_possession_loans_table.php` per data-model.md: `contact_id`, `condition_out_id`, `condition_back_id`, `departure_provenance_event_id`, `return_provenance_event_id` nullable with `nullOnDelete()`; `possession_id` cascade; `deposit_amount` "decimal(12,2), nullable, 0 to 9999999999.99"; index `(possession_id, started_on)`
- [X] T006 Create `app/Models/PossessionLoan.php` (full docblock, `#[RouteKey('uuid')]`, `ResolvesModelInVault`, enum and date casts, `possession()`, `contact()` with trashed, `conditionOut()`, `conditionBack()`, `departureEvent()`, `returnEvent()`) and `database/factories/PossessionLoanFactory.php` (states `active()`, `returned()`, `planned()`); `loans()` and `activeLoan()` on `app/Models/Possession.php`, `possessionLoans()` on `app/Models/Contact.php`

## Phase 3: User Story 1, lend a possession to a contact (P1) MVP

Independent test: lend a possession to a contact of the vault and borrow one from another; both rows are saved, no transaction is written.

- [X] T007 [US1] Create `app/Actions/CreatePossessionLoan.php` per research R4 (account check, contact of the possession's vault or `ValidationException` on `contact_id`, row lock, second active loan refused on `status` with "It is already with :name. Mark that loan as returned first.", due day "not before `started_on`", `returned_on` "required when Returned, not before `started_on`", condition ids of the account, currency required with an amount, journal) and `tests/Unit/Actions/CreatePossessionLoanTest.php`
- [X] T008 [US1] Add a default slot after the list to `resources/views/components/record-picker/candidates.blade.php` (research R6); run every test using the record picker
- [X] T009 [US1] Create `app/ViewModels/Vaults/PossessionLoanContactCandidatesViewModel.php` (vault contacts matching `query`, first ten by name when empty, empty line "Nobody called “:name” in your contacts.") and `app/Http/Controllers/App/Vaults/PossessionLoanContactCandidateController.php`, route `possessions.loans.contactCandidates`, view `resources/views/app/vaults/possessions/loan-contact-candidates.blade.php`; view model test leaves other vaults out
- [X] T010 [US1] Create `app/ViewModels/Vaults/PossessionLoansViewModel.php` delegating header and sections to `PossessionViewModel`, with the conditions, currencies and form defaults (Active, today, Lent)
- [X] T011 [US1] Routes in `routes/web.php` after the location stay routes: `possessions.loans.index` and `possessions.loans.create` at `vaults/{vault}/possessions/{possession}/loans`; `app/Http/Controllers/App/Vaults/PossessionLoanController.php` with `index` and `create` (inline validation, contact looked up by uuid inside the vault, delegating to the action)
- [X] T012 [US1] Create `resources/views/app/vaults/possessions/loans.blade.php` and `_loan-form.blade.php` from the design: "Add a loan" hidden while the form is open, direction and status selects, `x-record-picker` with the label "Lent to" or "Borrowed from", dates, conditions, deposit, purpose, "Add to the provenance", and the summary line computed in Alpine with the spec's wordings
- [X] T013 [US1] In `PossessionViewModel::sections()` give Loans its URL and count; update `tests/Unit/ViewModels/Vaults/PossessionViewModelTest.php`
- [X] T014 [US1] `tests/Feature/Controllers/App/Vaults/PossessionLoanControllerTest.php`: index renders, create saves and flashes, one refusal reaches the form, wrong vault 404; a test of the candidates controller
- [X] T015 [P] [US1] API `create` in `app/Http/Controllers/Api/Possessions/PossessionLoanController.php`, `app/Http/Resources/PossessionLoanResource.php` per contracts/api.md, routes in `routes/api.php`; tests
- [X] T016 [P] [US1] MCP `app/Mcp/Tools/CreatePossessionLoan.php`, registered on the server; test

## Phase 4: User Story 2, see who has it and mark it returned (P1)

Independent test: with a returned loan, an active overdue one and a planned one, the panel, list, badges and lines read right; marking returned empties the panel.

- [X] T017 [US2] View model: the active panel (initials, sentence by direction, due line), "Every loan" newest first with verb, contact name linking to their page or "A deleted contact", badge (Overdue derived per research R2), period and length or due line, purpose, facts with "Not recorded", summary "2 loans, 1 lent out" and lengths through `trans_choice()`, empty state, help text
- [X] T018 [US2] Create `app/Actions/UpdatePossessionLoan.php` per research R4 (same refusals as create, clears return day and condition back unless Returned, journals Return when the status becomes Returned, otherwise Update) and its test
- [X] T019 [US2] Route `possessions.loans.return.new` and `app/Http/Controllers/App/Vaults/PossessionLoanReturnController.php` (`new`) rendering the edit form with Returned and today; routes `possessions.loans.edit` and `.update` with parameter `possessionLoan`, controller `edit` and `update`; render the panel, list, help (`x-help`) and empty state in `loans.blade.php`
- [X] T020 [US2] `tests/Unit/ViewModels/Vaults/PossessionLoansViewModelTest.php` per quickstart; controller tests for return and update
- [X] T021 [P] [US2] API `index`, `show` and `update`; MCP `ListPossessionLoans`, `GetPossessionLoan`, `UpdatePossessionLoan`; tests

## Phase 5: User Story 3, edit or delete a loan (P2)

Independent test: edit a loan's start day, delete an active loan after its warning, and read the panel and count.

- [X] T022 [US3] `app/Actions/DestroyPossessionLoan.php` (deletes its provenance events, journal) and its test
- [X] T023 [US3] Route `possessions.loans.destroy`, controller `destroy`; "Edit loan" title and "Save changes" on the edit screen; the delete warning with `x-delete-action` and the spec's wordings; tests
- [X] T024 [P] [US3] API `destroy`; MCP `DeletePossessionLoan`; tests

## Phase 6: User Story 4, create the contact from the loan (P3)

Independent test: lend to "Nate" who does not exist; Nate and the loan exist together, and a refused loan leaves no Nate.

- [X] T025 [US4] `CreatePossessionLoan` takes `newContactFirstName` and `newContactLastName`, calling `CreateContact` in the same transaction (research R5); cases in its test, including the rollback
- [X] T026 [US4] The create offer in the candidates view's slot, the first and last name fields, "New contact" badge and "Search your contacts instead" as Alpine state in `_loan-form.blade.php`; validation "Give the new contact at least a first name."; controller and API `new_contact` cases

## Phase 7: User Story 5, the contact's page and the provenance (P3)

Independent test: a loan in the provenance writes and removes its two events; the contact's page lists the loan; merging and undoing a merge carry it.

- [X] T027 [US5] Provenance per research R7 in the three actions (departure event while active or returned, return event while returned, both removed on untick or delete); cases in the three action tests
- [X] T028 [US5] `app/ViewModels/Vaults/ContactLoansBlock.php`, `ContactViewModel::loans()`, `resources/views/app/vaults/contacts/_loans.blade.php` included in `show.blade.php` (research R10); block test
- [X] T029 [US5] `loan_moved` in `app/Actions/MergeContacts.php` and `app/Actions/UndoContactMerge.php` (research R8); cases in both tests

## Phase 8: Polish

- [X] T030 API reference `resources/docs/api/39-possession-loans.php` via the `api-docs-writer` skill
- [X] T031 [P] Guide `docs/portal/en/9-templates-and-customisation/23-possession-loans.md`, a line in `16-possessions.md`, via the `writer` skill; `docs/FEATURES.md`
- [X] T032 Example loans in the seeded account in `database/seeders/ExampleAccountSeeder.php`, asserted in `tests/Unit/Seeders/ExampleAccountSeederTest.php`
- [X] T033 `php artisan monica:localize`, translate every reported string in every locale (translation subagents on Sonnet), `--check` passes
- [X] T034 `vendor/bin/phpstan analyse --no-progress`, `vendor/bin/pint --dirty --format agent`, `php artisan test --compact`
- [ ] T035 The quickstart's checks by hand at monica.test

## Dependencies

Setup, then Foundational, then US1. US2 and US3 need US1's screen; US3's edit screen comes from US2 (T019). US4 needs T007 and T009. US5 needs T018 and T022. Polish last.
Parallel: T002 beside T001; T003 and T004 beside T005; within each story the tasks marked [P] (API, MCP) beside the web work; T028 and T029 beside T027.

## Strategy

MVP is US1 plus US2: lend, see who has it, mark it returned. Then US3, then US4 and US5.
