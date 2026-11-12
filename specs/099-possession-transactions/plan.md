# Implementation Plan: Possession Transactions

**Branch**: `2026-10-25-location-management` | **Date**: 2026-10-26 | **Spec**: [spec.md](spec.md)

## Summary

A `possession_transactions` table, a pivot to files, three actions plus two for documents, one
section screen, a currency picker component, API endpoints and assistant tools. The possession's own
acquisition columns are converted into transactions and dropped; acquisition date, price and costs
are derived. Section icons take colours. Decisions in [research.md](research.md), shapes in
[data-model.md](data-model.md), the API in [contracts/api.md](contracts/api.md), checks in
[quickstart.md](quickstart.md).

## Technical Context

| | |
| --- | --- |
| Language | PHP 8.5, Laravel (current major), Blade, Tailwind, Alpine.js, Alpine Ajax |
| Storage | Two new tables; three columns of `possessions` converted then dropped (R6) |
| Testing | PHPUnit: enums, models, actions, migration, view models, web and API controllers, component, MCP tools |
| Scale | 200 transactions on one possession |
| Constraints | SQLite, MySQL, PostgreSQL; no new JavaScript file; WCAG 2.2 AA; every wording translated |

## Constitution Check

| Principle | Status |
| --- | --- |
| I Actions | Pass. Create, update, destroy, attach and detach a document are actions; API, MCP and web share them |
| II Simplicity | Pass. Derived figures are two model methods (R4); no policy, no flag of its own |
| III Tested | Pass. Every layer in quickstart |
| IV Laravel | Pass. New migrations only, `up()` only, `foreignIdFor()->constrained()` |
| V Localized | Pass. Money and dates in the user's formats; type labels translated; delete warns |
| VI Nothing changes | Pass with note. Converting rewrites how an acquisition is stored, not what it says (SC-002) |
| VII Stable core | **Amendment**. Possession transaction becomes a fixed concept, the possession sentence changes (R11) |
| VIII Nothing disappears | Pass. Documents are detached, so they keep the grace period; deleting warns |
| IX Accessible | Pass. Listbox contract in a component (R7); colour never alone, every icon has its label |
| X API | Pass. Endpoints and tools in the contract; one breaking change to `v1` flagged (R10) |
| XI Short record | Pass. spec 119/120, plan, research 79/80 and the others within caps |
| Invariant: imprecision | Pass. Undated transactions allowed, never invented (R5) |

Re-checked after design: unchanged.

## Project Structure

```text
.specify/memory/constitution.md; spec/models/062-possession-*.md; spec/models/066-possession-transaction-in-progress.md
database/migrations/2026_10_27_00000{0..3}_*.php            create transactions, pivot, convert, drop columns
app/Enums/PossessionTransactionTypeEnum.php                new
app/Enums/CurrencyEnum.php, VaultColourEnum.php, UserActionEnum.php   code() flag(); iconForeground(); three cases
app/Models/PossessionTransaction.php                       new, ResolvesModelInVault, total(), totalSource()
app/Models/Possession.php                                  transactions(), acquisitionTransaction(); acquisition columns gone
app/Actions/{Create,Update,Destroy}PossessionTransaction.php           new
app/Actions/{Attach,Detach}PossessionTransactionDocument.php           new
app/Actions/{Create,Update,Destroy}Possession.php          acquisition arguments removed; destroy detaches documents
app/Helpers/PossessionAcquisitionAttributes.php            keeps the declared value only
app/Http/Controllers/App/Vaults/PossessionTransaction{,Document}Controller.php   new
app/Http/Controllers/Api/Possessions/PossessionTransaction{,Document}Controller.php  new
app/Http/Resources/PossessionTransactionResource.php       new; PossessionResource derives acquisition
app/Mcp/Tools/{List,Create,Update,Delete}PossessionTransaction(s).php  new; Create/UpdatePossession trimmed
app/ViewModels/Vaults/PossessionTransactionsViewModel.php  new; PossessionViewModel colours, count, facts
resources/views/components/currency-picker.blade.php       new
resources/views/app/vaults/possessions/show.blade.php      sections link, coloured
resources/views/app/vaults/possessions/transactions.blade.php, _transaction-form.blade.php  new
resources/views/app/vaults/possessions/_acquisition.blade.php   declared value only, currency picker
routes/web.php, routes/api.php; resources/docs/api/27-possessions.php, 30-possession-transactions.php
docs/portal/en/9-templates-and-customisation/16-possessions.md; docs/FEATURES.md; lang/*.json
```

## Order of work

1. Constitution amendment, `spec/models` 062 and 066.
2. Enums (type, currency `code()`/`flag()`, colour `iconForeground()`, journal cases) and their tests.
3. Migrations, `PossessionTransaction` model and factory, `Possession` methods; model and migration tests.
4. The five transaction actions, then remove acquisition from `CreatePossession`, `UpdatePossession`
   and `PossessionAcquisitionAttributes`; destroy detaches documents. Action tests.
5. `x-currency-picker` and its test; swap it into `_acquisition.blade.php`; whole suite (shared form).
6. View models, web routes, controllers, `transactions.blade.php`; sections list links and colours.
7. API controllers, resources, routes and reference; MCP tools; their tests.
8. Guide, FEATURES.md, translations, PHPStan, Pint, whole suite; the page by hand as quickstart says.

## Complexity Tracking

| Item | Why |
| --- | --- |
| `account_id` and `vault_id` copied onto each transaction | `ResolvesModelInVault` and `RouteProtectionTest` need them (R1) |
| Breaking change to the possession API in `v1` | FR-012 removes the fields; 422 makes it loud rather than silent (R10) |
