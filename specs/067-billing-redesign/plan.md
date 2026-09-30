# Implementation Plan: Billing screen redesign

**Branch**: `2026-09-29-billing-redesign` | **Date**: 2026-09-29 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `/specs/067-billing-redesign/spec.md`

## Summary

Two pull requests. First, "offer" becomes "plan" wherever a reader sees it, with every locale
retranslated. Second, `settings/account/billing` is rebuilt after `Billing.dc.html`: a summary for
a paying account, the plans with a currency toggle, what changes, questions, what is on record, and
why Monica asks for money. No schema, route, action or API change. Decisions: [research.md](research.md).

## Technical Context

**Language/Version**: PHP 8.4, Laravel 13

**Primary Dependencies**: Blade, Tailwind CSS 4, Alpine.js; Laravel Cashier for price formatting

**Storage**: none new; reads `accounts`, `subscriptions`, `stripe_orders`, `payments`

**Testing**: PHPUnit (view model unit tests, controller and component feature tests)

**Target Platform**: web, light and dark themes

**Project Type**: web application (single Laravel project)

**Performance Goals**: no query added per row; payments read once and cut to three

**Constraints**: WCAG 2.2 AA; every string in every configured locale; no JavaScript added

**Scale/Scope**: one screen, one component, one view model, plus a wording pass over about 60 files

## Constitution Check

| Principle | Status |
| --- | --- |
| I. Actions hold the business logic | Pass. No new rule; checkout, switch, cancel and keep reuse their actions |
| II. Simplicity | Pass. No new class; `x-offer-list` rewritten, not duplicated |
| III. Tested before merged | Pass. View model methods and the component get tests; copy assertions follow the rename |
| V. Localized | Pass with work: 27 renamed keys and every new phrase in 30 locales, `plan` added to the glossary |
| VIII. Nothing disappears silently | Pass. Cancelling still says nothing is deleted, in the summary and the questions |
| IX. Accessible | Pass. Native disclosure, `aria-pressed` toggles, bars hidden behind their text, one `h1` |
| X. Through the API | Pass. No new capability; API fields unchanged (FR-013) |
| XI. Short record | Pass. No data model or contract file: nothing changes shape |

Governance: the rename amends the constitution's wording (PATCH, 2.4.2); that pull request says so.

Re-checked after design: unchanged.

## Project Structure

### Documentation (this feature)

```text
specs/067-billing-redesign/
├── spec.md
├── plan.md
├── research.md
├── quickstart.md
└── tasks.md   (next step)
```

### Source Code (repository root)

```text
# Pull request 1: the rename
lang/*.json                                         # through monica:localize, values retranslated
app/Enums/{InstanceSettingEnum,UserActionEnum}.php
app/Notifications/AccessEnded.php
app/Policies/{VaultPolicy,ContactPolicy}.php
app/Actions/{UpgradeSubscription,StartCheckout}.php
app/Http/Controllers/App/Settings/Account/Billing/{Checkout,CheckoutReturn}Controller.php
app/ViewModels/{Settings/SettingsViewModel,Admin/AccountViewModel}.php
resources/views/components/paywall-prompt.blade.php
resources/views/app/admin/accounts/show.blade.php
docs/portal/en/**  docs/STRIPE.md  .specify/memory/constitution.md  .claude/rules/translations.md
tests/**                                            # the 13 files asserting the old copy

# Pull request 2: the screen
app/ViewModels/Settings/Account/Billing/BillingViewModel.php    # yearlySaving, usage, recentPayments, amount
resources/views/app/settings/account/billing/index.blade.php    # rewritten, sections as partials
resources/views/app/settings/account/billing/_summary.blade.php
resources/views/app/settings/account/billing/_benefits.blade.php
resources/views/app/settings/account/billing/_questions.blade.php
resources/views/app/settings/account/billing/_record.blade.php
resources/views/app/settings/account/billing/invoices.blade.php # tabs replaced by x-back-link
resources/views/components/offer-list.blade.php                 # rewritten: subscription card, lifetime card
tests/Unit/ViewModels/Settings/Account/Billing/BillingViewModelTest.php
tests/Feature/Views/Components/OfferListTest.php
tests/Feature/Controllers/App/Settings/Account/Billing/BillingControllerTest.php
```

**Structure Decision**: existing folders only. Sections are partials beside `index.blade.php`,
following `_tabs.blade.php`. `_tabs.blade.php` loses its callers; deleting it needs a separate yes.

## Complexity Tracking

None.
