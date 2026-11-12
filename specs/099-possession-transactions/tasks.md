# Tasks: Possession Transactions

**Input**: [plan.md](plan.md), [spec.md](spec.md), [research.md](research.md), [data-model.md](data-model.md), [contracts/api.md](contracts/api.md), [quickstart.md](quickstart.md)
**Tests**: required by constitution principle III, written with each piece, per `.claude/rules/testing.md`. Data from The Office. Read the matching skill (`actions`, `models`, `migrations`, `view-models`, `views`, `controllers`, `routes`, `api-writer`, `mcp-development`) before each kind of file.

## Phase 1: Setup

- [X] T001 Amend `.specify/memory/constitution.md` to 2.13.0 (research R11): principle VII lists Possession transaction as a fixed concept (fifteen), the possession sentence says it "records what it would cost to replace and its transactions", a transaction belongs to one possession and carries a type, an optional date, a text counterparty, four figures, a total and a currency; Sync Impact Report at the top
- [X] T002 [P] Update `spec/models/062-possession-in-progress.md` (acquisition now read from transactions) and create `spec/models/066-possession-transaction-in-progress.md` from data-model.md, in the shape of 064

## Phase 2: Foundational

- [X] T003 [P] Create `app/Enums/PossessionTransactionTypeEnum.php`, eleven cases with values, `label()`, `icon()`, `changesOwnership()`, `isAcquisition()`, `counterpartyLabel()` exactly as the data-model.md table; implements `Translatable` with `translationKeys()`; test in `tests/Unit/Enums/PossessionTransactionTypeEnumTest.php` (acquisitions are purchase, exchange, gift_received, inheritance; money only types read "Counterparty")
- [X] T004 [P] Add `code(): string` (uppercase value) and `flag(): string` (CSS background in the style of `config/localization.php` flags) to `app/Enums/CurrencyEnum.php`; test in `tests/Unit/Enums/CurrencyEnumTest.php` that every case has both and `code()` of `Euro` is `EUR`
- [X] T005 [P] Add `PossessionTransactionCreation` `possession_transaction_created`, `PossessionTransactionUpdate` `possession_transaction_updated`, `PossessionTransactionDeletion` `possession_transaction_deleted` to `app/Enums/UserActionEnum.php` with copy "Recorded a :type for :possession", "Changed a :type of :possession", "Deleted a :type of :possession"
- [X] T006 Create migration `database/migrations/2026_10_27_000000_create_possession_transactions_table.php` (`up()` only, column comments as siblings do): `id`, `uuid` unique, `foreignIdFor(Account::class)->constrained()->cascadeOnDelete()`, same for `Vault` and `Possession`, `type` string, `occurred_on` date nullable, `counterparty` string(255) nullable, `amount`, `tax`, `fees`, `shipping`, `entered_total` decimal(12, 2) nullable, `currency` string(3) nullable, `reference` string(255) nullable, `source_url` string(2048) nullable, `note` text nullable, timestamps, index `(possession_id, occurred_on)`
- [X] T007 Create migration `database/migrations/2026_10_27_000001_create_possession_transaction_file_table.php`: `foreignIdFor(PossessionTransaction::class)->constrained()->cascadeOnDelete()`, `foreignIdFor(File::class)->constrained()->cascadeOnDelete()`, unique together, timestamps
- [X] T008 Create `app/Models/PossessionTransaction.php` (full model docblock): `#[Fillable]`, `#[RouteKey('uuid')]`, `ResolvesModelInVault`, casts `type` enum, `occurred_on` `immutable_date`, the five figures `decimal:2`, `currency` `CurrencyEnum`; `possession()`, `documents(): BelongsToMany<File>` on `possession_transaction_file`; `total(): ?string` (`entered_total`, else `bcadd` of the figures set at scale 2, else null) and `totalSource(): string` (`entered`, `sum`, `unknown`); factory `database/factories/PossessionTransactionFactory.php` taking account and vault from the possession
- [X] T009 Add to `app/Models/Possession.php`: `transactions(): HasMany` and `acquisitionTransaction(): ?PossessionTransaction` over the loaded `transactions`, acquisitions only, undated first, then `occurred_on`, then `id` (research R4); docblock updated
- [X] T010 Tests: `tests/Unit/Models/PossessionTransactionTest.php` (entered total wins, "40.00" + "3.20" + "6.00" is "49.20", nothing set is null and `unknown`); `tests/Unit/Models/PossessionTest.php` (earliest wins, undated before dated, tie by id, a sale is never the acquisition, none gives null)

## Phase 3: User Story 1, record a transaction (P1) MVP

Independent test: on a possession without transactions, add one and find it listed with its total.

- [X] T011 [US1] Create `app/Actions/CreatePossessionTransaction.php`: constructor `User $user`, `Possession $possession`, `PossessionTransactionTypeEnum $type`, then nullable `occurredOn`, `counterparty`, `amount`, `tax`, `fees`, `shipping`, `enteredTotal`, `CurrencyEnum $currency`, `reference`, `sourceUrl`, `note`; refuses another account with `ModelNotFoundException`; each figure "0 to `MAX_DECLARED_VALUE`" else `ValidationException` on its key; a figure or total without currency refused on `currency`; copies `account_id` and `vault_id` from the possession; dispatches `LogUserAction` `PossessionTransactionCreation` on `low`
- [X] T012 [US1] Test in `tests/Unit/Actions/CreatePossessionTransactionTest.php`: writes every column (`assertDatabaseHas`), undated allowed, negative amount refused, amount without currency refused, another account refused, journal pushed (`Queue::fake()`)
- [X] T013 [P] [US1] Create `resources/views/components/currency-picker.blade.php` (research R7; read `language-picker.blade.php` and `components.md` first): props `name`, `id`, `label`, `value` (?CurrencyEnum), `error`; button showing flag and code; `role="listbox"` panel with `role="option"` rows, `aria-selected`, accessible name `label()`, tick on the chosen one; hidden input `name`; ArrowUp, ArrowDown, Home, End, Enter, Escape, click outside; inline Alpine only
- [X] T014 [P] [US1] Test in `tests/Feature/Views/Components/CurrencyPickerTest.php`: ten options, the chosen one `aria-selected="true"`, option named "Euro (EUR)", hidden input carries `eur`, `aria-expanded` on the button
- [X] T015 [US1] Create `app/ViewModels/Vaults/PossessionTransactionsViewModel.php` (constructor `User $user`, `Possession $possession`; loads `transactions.documents` once): `header()` and `sections()` reused from `PossessionViewModel` by delegating to it; `types()` grouped "Change of ownership" and "Money only"; `currencies()`; `preselectedCurrency()` (latest transaction's, else `declared_value_currency`, else null); `createUrl()`; `counterpartyLabels()` keyed by type for Alpine
- [X] T016 [US1] Add routes to `routes/web.php` after `possessions.featured.update`: `GET vaults/{vault}/possessions/{possession}/transactions` `possessions.transactions.index` and `POST` `possessions.transactions.create`, `whereUuid('possession')`
- [X] T017 [US1] Create `app/Http/Controllers/App/Vaults/PossessionTransactionController.php`: `index` renders `app.vaults.possessions.transactions`; `create` validates inline (`type` enum required, `occurred_on` nullable date, `counterparty` nullable max 255, figures nullable numeric min 0, `currency` nullable enum, `reference` max 255, `source_url` nullable url max 2048, `note` nullable), calls the action, redirects to index with `status` "The transaction is recorded."
- [X] T018 [US1] Create `resources/views/app/vaults/possessions/transactions.blade.php` and `_transaction-form.blade.php` from the design: same header and sections nav as `show.blade.php` (extract the shared header and nav into `_header.blade.php` and `_sections.blade.php`, included with every variable named); title, help mark (`x-help`) with the spec's text, "Add a transaction" opening the form (Alpine), form fields in the design's three rows, counterparty label switching with the type, live total line ("Total of this transaction", the sum and the parts named, or "as entered"), `x-currency-picker`, Cancel
- [X] T019 [US1] In `PossessionViewModel::sections()` make Transactions available with its `url` and `count`; in `show.blade.php` render available sections as links, `aria-current="page"` on the current one
- [X] T020 [US1] Test in `tests/Feature/Controllers/App/Vaults/PossessionTransactionControllerTest.php`: index renders "Transactions" and "Add a transaction"; create stores and redirects with the flash; a negative amount returns an error on `amount`; another possession of the same vault in the URL answers 404
- [X] T021 [P] [US1] API create: `app/Http/Controllers/Api/Possessions/PossessionTransactionController.php` `create` and `app/Http/Resources/PossessionTransactionResource.php` in the contract's shape; route in `routes/api.php` named `possessions.transactions.create`; test 201 and 422 in `tests/Feature/Controllers/Api/Possessions/PossessionTransactionControllerTest.php`
- [X] T022 [P] [US1] MCP `app/Mcp/Tools/CreatePossessionTransaction.php`, registered in `app/Mcp/Servers/MonicaServer.php`; test in `tests/Unit/Mcp/Tools/CreatePossessionTransactionTest.php`

## Phase 4: User Story 2, read what changed hands (P1)

Independent test: with a purchase and two fees, the summary and the list read the right sums.

- [X] T023 [US2] Add to `PossessionTransactionsViewModel`: `summary()` (acquired date in the user's format or "Unknown", how and counterparty, price via `PossessionValueFormatter` in its currency, costs since per data-model.md "Derived", count left out); `transactions()` newest first, undated last on screen (oldest), each with type label and icon, `isAcquisition`, date or "Date unknown", counterparty or "No counterparty recorded", total or "Unknown" with its source line, the four parts, note, reference, link, documents (name, kind, size), added and edited lines, `changesOwnership`; `counts()` for the two filters
- [X] T024 [US2] Test in `tests/Unit/ViewModels/Vaults/PossessionTransactionsViewModelTest.php`: no transaction gives "Unknown"; purchase then two fees sums the fees; a refund subtracts; a fee in another currency is left out and counted; list order; filter counts
- [X] T025 [US2] In `transactions.blade.php` render the summary panel, the two filter chips (`x-chip`, Alpine state, "Ownership changes" first, "Newest first"), and the list entries as the design draws them; "No transaction recorded yet." when empty; switch to "All transactions" after recording a money only one (flash key)
- [X] T026 [P] [US2] Add `iconForeground(): string` to `app/Enums/VaultColourEnum.php` (the `text-[...] dark:text-[...]` half of `tint()`); add `colour` to each entry of `PossessionViewModel::sections()` per research R8 and use it in `_sections.blade.php`; test the mapping in `tests/Unit/ViewModels/Vaults/PossessionViewModelTest.php`
- [X] T027 [P] [US2] API `index` and `show` with `possessions.transactions` and `possessions.transactions.show` routes; tests for the JSON structure and that another possession's transactions are left out
- [X] T028 [P] [US2] MCP `app/Mcp/Tools/ListPossessionTransactions.php`, registered; test

## Phase 5: User Story 3, correct or remove a transaction (P2)

Independent test: edit figures and see the total change; delete one and see it leave the list and summary.

- [X] T029 [US3] Create `app/Actions/UpdatePossessionTransaction.php` (same rules as create, changes only what is passed, journals `PossessionTransactionUpdate`) and `app/Actions/DestroyPossessionTransaction.php` (runs `DetachFile` per document, deletes, journals `PossessionTransactionDeletion`); tests in `tests/Unit/Actions/`
- [X] T030 [US3] Routes `GET .../transactions/{possessionTransaction}/edit` `possessions.transactions.edit`, `PUT` `.update`, `DELETE` `.destroy`; controller methods with `abort_unless($possessionTransaction->possession_id === $possession->id, 404)`; edit renders the page with the entry replaced by the filled form titled "Edit transaction"
- [X] T031 [US3] Delete warning in the entry (Alpine): title "Delete this :type of :date?", body from `deleteWarning()` on the view model (the next acquisition, or no acquisition left; "Its documents are deleted with it." when it has any), "Keep it" and Delete; test `deleteWarning()` and the controller routes
- [X] T032 [P] [US3] API `update`, `destroy`; MCP `UpdatePossessionTransaction`, `DeletePossessionTransaction`; tests
- [X] T033 [US3] `app/Actions/DestroyPossession.php` detaches every document of its transactions before deleting; test

## Phase 6: User Story 5, existing acquisitions become transactions (P2)

Independent test: a possession recorded as bought on a date reads one purchase on that date after migrating.

- [X] T034 [US5] Migration `2026_10_27_000002_convert_possession_acquisitions.php` (research R6): per possession with `acquisition`, insert a transaction (`bought` purchase, `gift` gift_received, `inherited` inheritance, `made` other), `occurred_on` from `acquired_on`, counterparty the contact's name (`Contact::withTrashed()`), no figure, no currency; test in `tests/Unit/Migrations/ConvertPossessionAcquisitionsTest.php` like `BackfillLocationsTest`
- [X] T035 [US5] Migration `2026_10_27_000003_drop_acquisition_from_possessions.php` drops `acquired_from_contact_id` (foreign key first), `acquisition`, `acquired_on`
- [X] T036 [US5] Remove acquisition from `app/Actions/CreatePossession.php`, `UpdatePossession.php`, `app/Helpers/PossessionAcquisitionAttributes.php` (keep the declared value), `app/Models/Possession.php` (columns, `acquiredFrom()`), the web controllers, `_acquisition.blade.php` (declared value with `x-currency-picker` only), `contact-candidates` if nothing else uses it; delete `PossessionAcquisitionEnum` once unreferenced (ask before deleting any other class, per memory); fix their tests
- [X] T037 [US5] `PossessionViewModel::facts()` reads Acquired and From from `acquisitionTransaction()`; test
- [X] T038 [US5] API: `PossessionResource` derives `acquisition` per the contract; create and update answer 422 on `acquisition`, `acquired_from_id`, `acquired_on`; MCP `CreatePossession`, `UpdatePossession` lose them; tests updated

## Phase 7: User Story 4, documents (P3)

Independent test: attach a file to a transaction and open it from the entry.

- [X] T039 [US4] Actions `AttachPossessionTransactionDocument` (`CreateFile` then `AttachFile`, pivot row) and `DetachPossessionTransactionDocument` (pivot row removed, `DetachFile`); tests
- [X] T040 [US4] Web routes `POST .../transactions/{possessionTransaction}/documents` behind `flag:file-upload` and `DELETE .../documents/{file}`; controller `PossessionTransactionDocumentController`; the add form's file input attaches after create; entry lists documents linking to `files.show`, "No invoice or receipt attached yet." otherwise; attaching absent when the flag is off; tests
- [X] T041 [P] [US4] API document endpoints per the contract; tests

## Phase 8: Polish

- [X] T042 API reference `resources/docs/api/30-possession-transactions.php` and the changed acquisition in `27-possessions.php`, via the `api-docs-writer` skill
- [X] T043 [P] Guide `docs/portal/en/9-templates-and-customisation/16-possessions.md` and `docs/FEATURES.md`
- [X] T044 `php artisan monica:localize`, translate every reported string in every locale (glossary in `.claude/rules/translations.md`), `--check` passes
- [X] T045 `vendor/bin/phpstan analyse --no-progress`, `vendor/bin/pint --dirty --format agent`, `php artisan test --compact`
- [ ] T046 The quickstart's checks by hand at erica.test: light, dark, `fr_FR`, 375 px, 200%, keyboard only

## Dependencies

Setup, then Foundational, then US1. US2 needs US1's screen. US3 needs US2's list. US5 needs only Foundational and may run beside US1 to US3 except T036 and T037, which touch files US1 edits. US4 needs US3 (destroy detaches). Polish last.
Parallel: T003 to T005; within each story the tasks marked [P] (API, MCP, component) beside the web work.

## Strategy

MVP is US1 plus US2: record and read. Then US3, then US5 (the conversion ships with the removal in one deployment), then US4.
