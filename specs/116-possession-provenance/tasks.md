# Tasks: Possession Provenance

**Input**: [plan.md](plan.md), [spec.md](spec.md), [research.md](research.md), [data-model.md](data-model.md), [contracts/api.md](contracts/api.md), [quickstart.md](quickstart.md)
**Tests**: required by constitution principle III, written with each piece, per `.claude/rules/testing.md`. Data from The Office. Mirror the transaction files of 099 (edit screen included) and the valuation files of 115 for every kind of file.

## Phase 1: Setup

- [X] T001 Amend `.specify/memory/constitution.md` to 2.18.0 (research R13): principle VII lists Possession provenance event as a fixed concept (twenty four), the possession sentence says it holds its provenance events, one sentence saying an event records one moment of origin, ownership, custody or authenticity, holds no money and may point at one transaction of the same possession; Sync Impact Report at the top
- [X] T002 [P] Update `spec/models/062-possession-in-progress.md` and create `spec/models/075-possession-provenance-event-in-progress.md` from data-model.md, in the shape of 074

## Phase 2: Foundational

- [X] T003 [P] Create `app/Enums/PossessionProvenanceEventTypeEnum.php` (twelve cases, `label()`, `icon()`, `group()` returning `ownership`, `custody` or `authenticity`, `translationKeys()`, values, labels, icons and groups as data-model.md) and `app/Enums/PossessionProvenanceDatePrecisionEnum.php` (`day`, `month`, `year`, `circa`, `unknown`, `label()` and `help()` with the design's sentences); tests in `tests/Unit/Enums/PossessionProvenanceEventTypeEnumTest.php` and `PossessionProvenanceDatePrecisionEnumTest.php`
- [X] T004 [P] Add `PossessionProvenanceEventCreation`, `PossessionProvenanceEventUpdate`, `PossessionProvenanceEventDeletion` to `app/Enums/UserActionEnum.php`, copy in the voice of the transaction cases, naming the title (or the type) and the possession
- [X] T005 Create `database/migrations/2026_11_12_000002_create_possession_provenance_events_table.php`: `account_id`, `vault_id`, `possession_id` (cascade on delete), `possession_transaction_id` nullable "null on delete", `type` string required, `title` string(255) nullable, `date_precision` string default `day`, `occurred_on` date nullable, `from`, `to`, `location`, `reference_number`, `source_url` string(255) nullable, `description` text nullable, `is_verified` boolean default false, index on `(possession_id, occurred_on)`
- [X] T006 Create `app/Models/PossessionProvenanceEvent.php` (full docblock, `#[RouteKey('uuid')]`, `ResolvesModelInVault`, casts, `possession()`, `transaction(): BelongsTo<PossessionTransaction, $this>`) and `database/factories/PossessionProvenanceEventFactory.php`; add `provenanceEvents(): HasMany` to `app/Models/Possession.php`

## Phase 3: User Story 1, record a provenance event (P1) MVP

Independent test: on a possession without provenance, add an Origin around 1921 and an Acquisition linked to a purchase, and find both listed in date order.

- [X] T007 [US1] Create `app/Actions/CreatePossessionProvenanceEvent.php` (account check; `occurred_on` string matching its precision per research R3, normalised to a date or null, `ValidationException` on `occurred_on` otherwise; valid `source_url`; description at most 5000 characters; transaction of the same possession or `ValidationException` on `possession_transaction_id`; empty title stored null; journal) and `tests/Unit/Actions/CreatePossessionProvenanceEventTest.php`
- [X] T008 [US1] Create `app/ViewModels/Vaults/PossessionProvenanceViewModel.php` delegating header and sections to `PossessionViewModel`, with the form's grouped types, the title placeholder per type, precisions and their help sentences, and the transaction options oldest first (type, date, counterparty, `total()` in its currency)
- [X] T009 [US1] Routes in `routes/web.php` after the valuation routes: `possessions.provenance.index`, `possessions.provenance.create` at `vaults/{vault}/possessions/{possession}/provenance`
- [X] T010 [US1] Create `app/Http/Controllers/App/Vaults/PossessionProvenanceEventController.php` with `index` and `create` (inline validation, transaction looked up by uuid inside the possession, delegating to the action)
- [X] T011 [US1] Create `resources/views/app/vaults/possessions/provenance.blade.php` and `_provenance-form.blade.php` from the design: "Add an event" hides while the form is open, the date `<fieldset>` switched by precision with Alpine and emptied on change (research R4), the verified checkbox with its line
- [X] T012 [US1] In `PossessionViewModel::sections()` give Provenance its URL and count; update `tests/Unit/ViewModels/Vaults/PossessionViewModelTest.php`
- [X] T013 [US1] `tests/Feature/Controllers/App/Vaults/PossessionProvenanceEventControllerTest.php`: index renders, create stores and flashes, validation error, wrong possession 404
- [X] T014 [P] [US1] API `create` in `app/Http/Controllers/Api/Possessions/PossessionProvenanceEventController.php`, `app/Http/Resources/PossessionProvenanceEventResource.php` per contracts/api.md (`occurred_on` read back in the shape of its precision), routes in `routes/api.php`; tests
- [X] T015 [P] [US1] MCP `app/Mcp/Tools/CreatePossessionProvenanceEvent.php`, registered on the server; test

## Phase 4: User Story 2, read where the possession comes from (P1)

Independent test: with five events of mixed precision and verification, the summary, the order, the filters and the empty states read right.

- [X] T016 [US2] View model: chain order (research R5), date by precision in the user's date format, title fallback, who line, parts, linked transaction label and total, summary per research R8 with counted phrases through `trans_choice()`, filter counts, both empty states
- [X] T017 [US2] Render the summary, the help mark (`x-help` pointing at the portal page), the filter chips (`aria-pressed`, Alpine state as `transactions.blade.php`), the chain and the empty states in `provenance.blade.php`; the linked transaction leads to `possessions.transactions.index`
- [X] T018 [US2] `tests/Unit/ViewModels/Vaults/PossessionProvenanceViewModelTest.php` per quickstart
- [X] T019 [P] [US2] API `index` and `show`; MCP `ListPossessionProvenanceEvents` and `GetPossessionProvenanceEvent`; tests

## Phase 5: User Story 3, correct or remove an event (P2)

Independent test: edit an event's precision and see it move in the chain; delete a linked one after its warning and find the transaction still there.

- [X] T020 [US3] `app/Actions/UpdatePossessionProvenanceEvent.php` (same rules as create, journal) and its test
- [X] T021 [US3] `app/Actions/DestroyPossessionProvenanceEvent.php` (journal, linked transaction untouched) and its test; a case in `tests/Unit/Actions/DestroyPossessionTransactionTest.php` proving a linked event survives with its link cleared
- [X] T022 [US3] Routes `possessions.provenance.edit`, `possessions.provenance.update`, `possessions.provenance.destroy` with parameter `possessionProvenanceEvent`; controller `edit`, `update`, `destroy`; the edit screen renders the filled form in place of the entry; the delete warning with its wording from the view model; tests
- [X] T023 [P] [US3] API `update` and `destroy`; MCP `UpdatePossessionProvenanceEvent` and `DeletePossessionProvenanceEvent`; tests

## Phase 6: Polish

- [X] T024 API reference `resources/docs/api/37-possession-provenance.php`, via the `api-docs-writer` skill
- [X] T025 [P] Guide `docs/portal/en/9-templates-and-customisation/21-possession-provenance.md`, lines in `16-possessions.md` and `17-possession-transactions.md`, via the `writer` skill; `docs/FEATURES.md`
- [X] T026 Example provenance in the seeded account: `database/seeders/ExamplePossessions.php` and `ExampleAccountSeeder::createPossessions()`, asserted in `tests/Unit/Seeders/ExampleAccountSeederTest.php`
- [X] T027 `php artisan monica:localize`, translate every reported string in every locale (translation subagents on Sonnet), `--check` passes
- [X] T028 `vendor/bin/phpstan analyse --no-progress`, `vendor/bin/pint --dirty --format agent`, `php artisan test --compact`
- [ ] T029 The quickstart's checks by hand at erica.test

## Dependencies

Setup, then Foundational, then US1. US2 and US3 need US1's screen. Polish last.
Parallel: T002 beside T001; T003 and T004; within each story the tasks marked [P] (API, MCP) beside the web work.

## Strategy

MVP is US1 plus US2: record and read. Then US3.
