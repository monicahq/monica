# Implementation Plan: Stripe Paywall

**Branch**: `024-stripe-paywall` | **Date**: 2026-09-07 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `/specs/024-stripe-paywall/spec.md`. Feature 022 built the same paywall on Paddle; its branch `2026-09-06-paddle-paywall` is the reference for everything that does not name the provider, and is never merged.

## Summary

Gate the hosted instance behind the offers Stripe's catalogue carries, a one time lifetime purchase and monthly or yearly subscriptions, opened through Cashier's hosted Checkout and unlocking exactly the same thing. Three caps on the free offer, listeners that move the account's access state off Stripe's webhooks, cancellation, an in place move up billed at the next renewal, a billing screen with an invoices tab, and the API beside it. Prices are tax inclusive and Stripe computes the tax share. `accounts.is_self_hosted` is dropped. [research.md](research.md) carries the decisions.

## Technical Context

**Language/Version**: PHP 8.4

**Primary Dependencies**: Laravel 13.30 and `laravel/cashier` ^16.8 (Stripe), which brings `stripe/stripe-php` ^21, `moneyphp/money` and two Symfony polyfills. Stripe's SDK speaks cURL directly, so the Guzzle 8 conflict that pinned 022 to a dev branch does not arise.

**Storage**: Cashier's `subscriptions` and `subscription_items` and its four customer columns on `accounts`, created by migrations of ours since the package's stay unpublished, and one table of our own, `payments`, because Cashier Stripe keeps no invoice locally. `accounts.is_self_hosted` dropped. SQLite, MySQL and PostgreSQL, no driver specific SQL.

**Testing**: PHPUnit 12.5, `RefreshDatabase`, factories. Webhooks are tested by dispatching Cashier's `WebhookReceived` and `WebhookHandled` with payload arrays; outbound Stripe calls go through a fixture HTTP client set on the SDK, recorded and asserted like `Http::fake()` was in 022.

**Target Platform**: Linux server, responsive web. Checkout and the portal are Stripe hosted pages reached by redirect, so no script of the provider runs in our screens.

**Project Type**: Blade server rendered application with a JSON API beside it.

**Performance Goals**: no screen waits on Stripe. The catalogue is cached for an hour, payments are read from our table, every listener is queued.

**Constraints**: ceilings read from configuration at check time, the stamped free storage ceiling never overwritten, no offer carrying an entitlement, no price held by the product, every price tax inclusive.

**Scale/Scope**: 3 cap checks, 4 listeners, 2 screens, 8 API endpoints, roughly 26 new files and 14 edited ones.

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

**Passed.** The product invariant was amended on 2026-09-06 (#143) to hold the subscription beside the one time purchase, and `spec/spec.md` §3.4 and `spec/models/001-account-in-progress.md` went with it (FR-013). Every row below is checked against the amended text.

| Principle | How this design satisfies it |
| --- | --- |
| I, actions hold the logic | Caps inside `CreateVault`, `CreateContact`, `CreateFile`. `RecordPurchase`, `RecordSubscription`, `RecordPayment`, `EndAccess`, `CancelSubscription`, `ResumeSubscription`, `UpgradeSubscription` move the state; `DestroyAccount` gains the cancellation. Listeners and controllers add transport only. |
| III, tested before merged | Every action, accessor, listener, notification, view model, controller, resource and component gets a test. The lapse path gets the most, because FR-015 fires routinely. |
| IV, Laravel conventions | The first party package is the documented way to integrate Stripe. `up()` only, no `constrained()`, inline validation, camelCase route names. Cashier's migrations stay unpublished; the tables it needs come from migrations of ours, keyed on `account_id`. |
| V, localized and safe | Every string through `__()`, nine locales, prices quoted by the catalogue and written for the reader's locale. Cancelling and deleting warn first (FR-019, FR-022). |
| VI, nothing changes underfoot | Paying never overwrites the stamped free ceiling. A lapse removes nothing the owner created. |
| VIII, nothing disappears | A cap refuses additions only. Existing vaults, contacts and files stay reachable and editable after a lapse. |
| IX, accessible | The refusal is an ordinary form error. Offers, the cancel confirmation and the deletion warning are text, links and forms, never colour alone. Checkout is Stripe's own audited page. |
| X, everything through the API | Reading access and payments, opening a checkout, cancelling, resuming, moving up and opening the portal are all in `api/v1/billing` (FR-011). |
| XI, written record is short | Every artifact inside its cap, `spec.md` at 120 of 120. The webhook table lives in research.md only; there is no second contract file. |

Invariant check: the offers differ in how often they bill and in nothing else (FR-018). No tier, no per offer entitlement, no plan managed inside the product, because the catalogue lives at the provider (FR-017).

## Complexity Tracking

| Violation | Why Needed | Simpler Alternative Rejected Because |
|-----------|------------|-------------------------------------|
| A table of our own, `payments` | Cashier Stripe reads invoices from the API on every call. FR-005 forbids a screen waiting on the provider, FR-009 lists payments, FR-022 deletes them with the account | Live API reads on the invoices tab and in `GET billing/payments`, rejected for FR-005 and for the API's rate limit |
| `config/cashier.php` holds service credentials, against the rule putting them in `config/services.php` | It is the package's own config file | Rewriting it means reconciling the package's defaults by hand on every upgrade |

## Project Structure

### Documentation (this feature)

```text
specs/024-stripe-paywall/
├── spec.md, plan.md (this file), research.md, data-model.md, quickstart.md
└── contracts/billing-api.md, checklists/requirements.md, tasks.md (later)
```

### Source Code (repository root)

```text
app/
├── Actions/            RecordPurchase, RecordSubscription, RecordPayment, EndAccess, CancelSubscription, ResumeSubscription, UpgradeSubscription
│                       CreateVault, CreateContact, CreateFile, CreateAccount, DestroyAccount (edited)
├── Enums/              OfferKindEnum, AccessKindEnum, AccessEndReasonEnum, UserActionEnum and EmailType (edited)
├── Exceptions/         TooManyVaults, TooManyContacts, AccountAlreadyOwnsTheProduct, AccountHasNoSubscription, SubscriptionCannotBeChanged, ProviderUnreachable
├── Helpers/            StripeCatalogue
├── Http/Controllers/   App/Settings/Account/Billing/{Billing,Checkout,Subscription,SubscriptionCancellation,SubscriptionPortal,Invoice,BillingCurrency}Controller
│                       Api/{Billing,CheckoutSession,Payment,Subscription,SubscriptionCancellation,PortalSession}Controller
├── Http/Resources/     BillingResource, PaymentResource
├── Listeners/          HandleStripeInvoicePaid, HandleStripeSubscription, HandleStripeSubscriptionEnded, HandleStripeRefund
├── Models/             Account (edited, Billable), Payment
├── Notifications/      AccessUnlocked, AccessEnded
└── ViewModels/         Settings/Account/Billing/{Billing,Invoices}ViewModel, Admin/AccountViewModel and Vaults/{Vault,Contact}FormViewModel (edited)

config/                 monica.php (edited), cashier.php (published)
database/
├── factories/          AccountFactory (edited), PaymentFactory
└── migrations/         create_subscriptions_table, create_subscription_items_table, create_payments_table, 0000_01_01_000000_create_accounts_table.php (edited: column dropped, customer columns added)
resources/views/        app/settings/account/billing/{index,invoices,_tabs}.blade.php, components/{paywall-prompt,offer-list}.blade.php
resources/docs/api/     12-billing.php
routes/                 web.php, api.php (edited); bootstrap/app.php (CSRF exception for `stripe/*`)
```

**Structure Decision**: everything of ours lands in folders that already exist. Cashier registers its own webhook route at `stripe/webhook`, so no inbound endpoint of ours is needed. The 022 branch is read for every file above that has a Paddle counterpart; what changes in each is in [research.md](research.md).
