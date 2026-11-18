# Implementation Plan: Stripe status in the admin space

**Branch**: `claude/beautiful-mestorf-1fe6ed` | **Date**: 2026-11-17 | **Spec**: [spec.md](spec.md)

## Summary

A Billing screen in the admin space. One action reads the keys and asks Stripe for its confirmation
endpoints over HTTP, which also proves the secret key is accepted. The catalogue comes from the
existing `StripeCatalogue` reader. The business figures and the confirmations received are counted
in the database by two small objects beside the view model. One more action reads the catalogue
again and writes the administrator log. No table, no migration, no new dependency. Decisions are in
[research.md](research.md), what is read in [data-model.md](data-model.md), the routes and the Stripe
call in [contracts/http.md](contracts/http.md).

## Technical Context

| | |
| --- | --- |
| Language | PHP 8.4, Laravel 13, Blade, Tailwind 4, Alpine.js |
| Packages | `laravel/cashier` (Stripe), Laravel's HTTP client. No new dependency |
| Storage | None new. Reads `payments`, `subscriptions`, `stripe_orders`, `stripe_events`, `accounts`. One more cache key beside the catalogue (R6) |
| Testing | PHPUnit, `RefreshDatabase`, `Http::fake()` for the endpoint call, `fakeStripe()` for the catalogue |
| Constraints | Every Stripe check under 5 seconds (FR-010): the endpoint call has a 3 second timeout, and the catalogue is read only when that call succeeded (R3, R5). A cold catalogue cache is the one unbounded call, tracked below |
| Scale | One Stripe call per page load, a handful of grouped `count`/`sum` queries over tables with one row per payment or account |

## Constitution Check

| Principle | How the plan meets it |
| --- | --- |
| I. Actions | `CheckStripeConnection` holds the check, `ReloadStripeCatalogue` the reload. The view model and its two blocks query and word, they decide nothing |
| II. Simplicity | One check action, no client wrapper, no value object (R2). The catalogue reader is reused, not copied (R5) |
| III. Tested | Both actions, the view model and its blocks, one test per controller, `AdminAccessTest` and `NoVaultContentTest` entries |
| IV. Conventions | One named route per line, thin controllers, array shape docblocks |
| V. Localized and safe | All copy through `__()` and `trans_choice()`. Amounts through the existing money formatter, per currency (R11) |
| VI, VII | Not touched |
| VIII. Nothing disappears silently | Nothing is deleted. Reloading the catalogue is written to the administrator log (R7) |
| IX. Accessible | Every state in words beside its tone, real tables with headers, reviewed by the `accessibility-reviewer` agent |
| X. API | **Not met**, decided in the spec's assumptions. Tracked below |
| XI. Short record | All artifacts short. `data-model.md` lists what is read, since nothing is stored |
| No vault content in admin, same product everywhere | Totals only (FR-013, FR-019). The screen exists on a self hosted instance and holds one sentence (FR-002) |

Gate: passes, with the one violation below.

## Project Structure

### Documentation (this feature)

```text
specs/127-admin-stripe-status/
├── spec.md
├── plan.md
├── research.md
├── data-model.md
├── quickstart.md
├── contracts/http.md
└── checklists/requirements.md
```

### Source Code (repository root)

```text
app/Actions/CheckStripeConnection.php                           keys, modes, warnings, the endpoint call (R1 to R4)
app/Actions/ReloadStripeCatalogue.php                           forgets and reads the catalogue, logs (R6, R7)
app/Helpers/StripeCatalogue.php                                 records when it last read Stripe, readAt() (R6)
app/Enums/AdministratorActionEnum.php                           new case StripeCatalogueReloaded
app/ViewModels/Admin/BillingStatusViewModel.php                 takes the check's answer: self hosted sentence, keys, catalogue, wording
app/ViewModels/Admin/BillingFigures.php                         access, subscriptions, payments, refunds (R8 to R10)
app/ViewModels/Admin/StripeConfirmations.php                    confirmations received, last 7 and 30 days (R12)
app/Http/Controllers/App/Admin/BillingStatusController.php      index: runs the check unless self hosted, builds the view model
app/Http/Controllers/App/Admin/StripeCatalogueReloadController.php create
routes/administrator.php                                        GET admin/billing, POST admin/billing/catalogue-reload
resources/views/app/admin/billing/index.blade.php               the four blocks and the reload button
resources/views/layouts/admin.blade.php                         Billing entry, icon `credit-card`
lang/*.json                                                     via monica:localize
docs/FEATURES.md                                                one entry
tests/Unit/Actions/{CheckStripeConnection,ReloadStripeCatalogue}Test.php
tests/Unit/Helpers/StripeCatalogueTest.php                      one more test for readAt()
tests/Unit/ViewModels/Admin/{BillingStatusViewModel,BillingFigures,StripeConfirmations}Test.php
tests/Feature/Controllers/App/Admin/{BillingStatus,StripeCatalogueReload}ControllerTest.php
tests/Feature/Controllers/App/Admin/{AdminAccess,NoVaultContent}Test.php   one more URL each
```

**Structure Decision**: the existing Laravel tree, following the Search screen of spec 115 file for file.

### Order of work

1. `CheckStripeConnection` and its test: no keys, key modes and both warnings, then the endpoint call answering 200, 401, 403 and timing out, then the missing kinds of confirmation.
2. `StripeCatalogue::readAt()`, `ReloadStripeCatalogue`, the enum case, their tests.
3. `BillingFigures` and `StripeConfirmations` and their tests (User Story 4, User Story 2.4 and 2.5).
4. `BillingStatusViewModel` and its test, including the self hosted path and the hosted path with no secret key.
5. Routes, controllers, view, navigation entry, controller tests, `AdminAccessTest`, `NoVaultContentTest`.
6. Translations, `docs/FEATURES.md`.
7. Full suite, PHPStan, Pint, `monica:localize --check`, accessibility and rules reviews.
8. Manual check against a Stripe test account ([quickstart.md](quickstart.md)): the endpoint response shape of R4 comes from Stripe's documentation and no test here can prove it.

## Complexity Tracking

| Violation | Why Needed | Simpler Alternative Rejected Because |
| --- | --- | --- |
| Principle X: no API method and no MCP tool for the status or the reload | Decided in the spec's assumptions. Search, Flags, Configuration and Overview are web only too | An API surface would need the `administration` scope, a resource, two MCP tools and their docs. The pull request must name this gap |
| FR-010 on a cold catalogue cache: one `stripe-php` call with no timeout of its own (R5) | The catalogue rules must stay in `StripeCatalogue`, and its client cannot take a timeout per call | A global `stripe-php` timeout shortens checkout too. A second reader copies the rules. Reading the cache only hides US1.7. The pull request must name this |
