# Implementation Plan: The order behind a purchase

**Branch**: `025-stripe-products` | **Date**: 2026-09-08 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `/specs/025-stripe-products/spec.md`

## Summary

The one time purchase raises no invoice at Stripe, so the confirmation the product waits for never arrives and the buyer is charged and left on the free offer. This replaces the wait with an order of its own: a row in `stripe_orders` written when the checkout session opens, settled from the session the return address names, and settled again by `checkout.session.completed` for a buyer who never comes back. Full access is then read from a settled order, a valid subscription or a standing grant, and `accounts.has_lifetime_access` and `accounts.purchased_at` are removed.

## Technical Context

**Language/Version**: PHP 8.4, Laravel 12

**Primary Dependencies**: `laravel/cashier` 16.8, `stripe/stripe-php` 21.3, API version `2026-08-26.dahlia`

**Storage**: one new table, `stripe_orders`, shaped like `subscriptions`. Two columns leave `accounts` and one arrives

**Testing**: PHPUnit, with Stripe faked through `TestCase::fakeStripe()` and the fake client in `tests/Fixtures/Stripe`

**Target Platform**: the hosted instance. A self hosted instance writes no order and shows none of it

**Project Type**: server rendered web application with a JSON API beside it

**Performance Goals**: the return page makes one call to Stripe. Every other read of access is local

**Constraints**: the suite passes on SQLite, MySQL and PostgreSQL. PHPStan level 5 over `app/`, `routes/` and `database/`. No new dependency

**Scale/Scope**: one table, one route, four actions reworked, two removed columns, nine locales

## Constitution Check

*GATE: passed before Phase 0, re-checked after Phase 1.*

| Principle | How this feature satisfies it |
| --- | --- |
| I. Actions and view models | Writing the order sits inside `StartCheckout`. Settling it is `SettleOrder`, reached by the return controller and by the listener. View models only read |
| II. Simplicity | No linkage from a charge back to a session, because a refund of the purchase is not expected. `type` is `default`, as on `subscriptions` |
| III. Tested before merged | Every action, listener, controller, view model and the model itself gets a test, including the compartment tests for a session belonging to another account |
| IV. The Laravel way | `up()` only, no `constrained()`, a comment on every column, and the accounts migration is edited in place since nothing has shipped |
| V. Every string translated | New copy on the billing and admin screens goes through `monica:localize` in nine locales |
| VIII. Deactivate, never vanish | An order is never deleted. A refund leaves it in place carrying what Stripe now says of it |
| X. API first | `BillingResource` answers with the order. The API settles through the webhook, since an API client is never redirected |
| XI. Artifact caps | plan 100, research 80, data model 60, quickstart 60, contract 60 |

Product invariant: one thing on sale, no tiers, no per offer features. A settled order means full access and nothing more.

## Complexity Tracking

| Thing | Why it is worth it |
| --- | --- |
| A return route that writes | Stripe redirects with `GET`, and the settlement has to happen somewhere. The screen stays a read: the route settles, then redirects to it |
| Two ways to settle one order | The return address is the fast path and the webhook is the honest one. Without the webhook a closed tab loses the purchase, which is the bug being fixed |
| A column on `accounts` for the grant | A grant orders nothing at Stripe, so it cannot be a row in `stripe_orders`. `access_granted_at` replaces the marker for it |

## Project Structure

### Documentation (this feature)

```text
specs/025-stripe-products/
├── plan.md              # This file
├── research.md          # Phase 0
├── data-model.md        # Phase 1
├── quickstart.md        # Phase 1
└── contracts/billing-api.md   # Phase 1, the API answer that changes
```

### Source Code (repository root)

```text
app/
├── Actions/
│   ├── StartCheckout.php            # writes the order, carries session_id on the return address
│   ├── SettleOrder.php              # new, replaces RecordPurchase
│   ├── EndAccess.php                # acts on the order and on the grant, not on the marker
│   ├── RestoreAccess.php            # puts the order back
│   ├── GrantLifetimeAccess.php      # writes access_granted_at, WithdrawLifetimeAccess clears it and ends the order
│   └── CreateAccount.php            # stops setting the marker
├── Http/Controllers/App/Settings/Account/Billing/CheckoutReturnController.php  # new
├── Http/Resources/BillingResource.php
├── Listeners/HandleStripeCheckoutSessionCompleted.php   # new, discovered rather than registered
├── Models/
│   ├── StripeOrder.php              # new
│   └── Account.php                  # orders(), settledOrder(), hasFullAccess(), accessKind()
└── ViewModels/Settings/Account/Billing/BillingViewModel.php
database/
├── factories/                       # StripeOrderFactory is new, withLifetimeAccess() writes an order
└── migrations/2026_09_08_000000_create_stripe_orders_table.php                  # new
resources/views/app/admin/accounts/show.blade.php
config/cashier.php                   # checkout.session.completed added to the events
docs/STRIPE.md                       # the invoice section goes, the event table gains a row
routes/web.php                       # settings.checkoutReturn.show
```

## Phases

- **Phase 0**, [research.md](research.md): what Cashier gives us, what Stripe calls a paid session, why no charge is translated back into a session, and what the removal of the marker touches.
- **Phase 1**, [data-model.md](data-model.md), [contracts/billing-api.md](contracts/billing-api.md), [quickstart.md](quickstart.md): the table, the columns leaving `accounts`, the API answer and the walk that proves it.
- **Phase 2**, `/speckit-tasks`: the ordered task list.
