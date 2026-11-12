# Implementation Plan: Possession Loans

**Branch**: `2026-10-25-location-management` | **Date**: 2026-11-12 | **Spec**: [spec.md](spec.md)

## Summary

A `possession_loans` table, three actions (create, update, destroy), the Loans section screen with
its active panel, list, edit and return screens, a contact search reusing `x-record-picker`, a block on
the contact's page, API endpoints, assistant tools and documentation. A loan may create its contact
and may write two change of custody events into the provenance. Contact merges carry loans. Built the
way 116 built provenance and 117 built stays. Decisions in [research.md](research.md), shapes in
[data-model.md](data-model.md), the API in [contracts/api.md](contracts/api.md), checks in
[quickstart.md](quickstart.md).

## Technical Context

| | |
| --- | --- |
| Language | PHP 8.5, Laravel (current major), Blade, Tailwind, Alpine.js, Alpine Ajax |
| Storage | One new table; nothing existing changes |
| Testing | PHPUnit: enums, actions, merge and undo, view models, web and API controllers, MCP tools |
| Scale | 100 loans on one possession; the contact search returns ten at most |
| Constraints | SQLite, MySQL, PostgreSQL; no new JavaScript file; WCAG 2.2 AA; every wording translated |

## Constitution Check

| Principle | Status |
| --- | --- |
| I Actions | Pass. Create, update and destroy are actions; API, MCP and web share them; contact creation goes through `CreateContact` (R5) |
| II Simplicity | Pass. One table, no pivot, no policy (each rule is stated once, in the action), no new flag |
| III Tested | Pass. Every layer in quickstart |
| IV Laravel | Pass. New migration only, `up()` only, `foreignIdFor()->constrained()` |
| V Localized | Pass. Dates in the user's format; counts through `trans_choice()`; delete warns |
| VI Nothing changes | Pass. A section that was unavailable becomes available; no existing figure changes |
| VII Stable core | **Amendment**. Possession loan becomes a fixed concept (R14) |
| VIII Nothing disappears | Pass. Deleting a contact keeps its loans (R3); merges carry them (R8); deleting a loan warns |
| IX Accessible | Pass. Status in words; the record picker's keyboard route; help and warning as in 116 |
| X API | Pass. Endpoints and tools in the contract |
| XI Short record | Pass. Every artifact within its cap |
| Invariant: imprecision | Pass. A due day is optional; "No return date agreed" says so |

Re-checked after design: unchanged.

## Project Structure

```text
.specify/memory/constitution.md; spec/models/062-possession-in-progress.md; spec/models/077-possession-loan-in-progress.md (new)
database/migrations/2026_11_12_000004_create_possession_loans_table.php   new
database/factories/PossessionLoanFactory.php                new
app/Enums/PossessionLoanDirectionEnum.php, PossessionLoanStatusEnum.php   new
app/Enums/UserActionEnum.php                                four cases
app/Models/PossessionLoan.php                               new, ResolvesModelInVault
app/Models/Possession.php, Contact.php                      loans(), activeLoan(); possessionLoans()
app/Actions/{Create,Update,Destroy}PossessionLoan.php       new
app/Actions/MergeContacts.php, UndoContactMerge.php         loan_moved (R8)
app/Http/Controllers/App/Vaults/PossessionLoanController.php   new (index, create, edit, update, destroy)
app/Http/Controllers/App/Vaults/PossessionLoanReturnController.php   new (new)
app/Http/Controllers/App/Vaults/PossessionLoanContactCandidateController.php   new (index)
app/Http/Controllers/Api/Possessions/PossessionLoanController.php   new
app/Http/Resources/PossessionLoanResource.php               new
app/Mcp/Tools/{List,Get,Create,Update,Delete}PossessionLoan(s).php   new; registered on the server
app/ViewModels/Vaults/PossessionLoansViewModel.php, PossessionLoanContactCandidatesViewModel.php   new
app/ViewModels/Vaults/ContactLoansBlock.php                 new; ContactViewModel::loans()
app/ViewModels/Vaults/PossessionViewModel.php               sections URL and count
resources/views/components/record-picker/candidates.blade.php   default slot after the list (R6)
resources/views/app/vaults/possessions/loans.blade.php, _loan-form.blade.php, loan-contact-candidates.blade.php   new
resources/views/app/vaults/contacts/_loans.blade.php (new), show.blade.php
routes/web.php, routes/api.php; resources/docs/api/39-possession-loans.php
docs/portal/en/9-templates-and-customisation/23-possession-loans.md (new), 16-possessions.md
docs/FEATURES.md; lang/*.json
```

## Order of work

1. Constitution amendment, `spec/models` 062 and 077.
2. Enums, journal cases and their tests.
3. Migration, model, factory and relations.
4. The three actions and their tests, including the contact created with the loan and provenance.
5. Merge and undo, and their tests.
6. Loans view model, contact candidates, web routes, controllers, views; sections list.
7. Contact page block and view.
8. API controller, resource, routes and reference; MCP tools; their tests.
9. Portal pages, FEATURES.md, translations, PHPStan, Pint, whole suite; the page by hand.

## Complexity Tracking

| Item | Why |
| --- | --- |
| `account_id` and `vault_id` copied onto each loan | `ResolvesModelInVault` and `RouteProtectionTest` need them (R1) |
| Two event foreign keys on the loan | Keeps provenance events in step without changing 116's table (R7) |
| A slot added to a shared component | The create offer must sit inside the list the search replaces (R6) |
