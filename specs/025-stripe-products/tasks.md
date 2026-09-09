# Tasks: The order behind a purchase

**Input**: Design documents in `/specs/025-stripe-products/`

**Prerequisites**: [plan.md](plan.md), [spec.md](spec.md), [research.md](research.md), [data-model.md](data-model.md), [contracts/](contracts/), [quickstart.md](quickstart.md)

**Reference**: feature 024 on this same branch built every file named below. Read the counterpart before changing it. The marker `accounts.has_lifetime_access` stays in place until T040, so the suite is green at every step and nothing depends on it once Phase 2 is done.

**Format**: `[ID] [P?] [Story?] Description with file path`. `[P]` means it touches no file another incomplete task touches.

## Phase 1: Setup

- [X] T001 [P] Write `database/migrations/2026_09_08_000000_create_stripe_orders_table.php` per [data-model.md](data-model.md): `account_id`, `type`, `stripe_id` unique, `stripe_status`, `stripe_price`, timestamps, index on `['account_id', 'stripe_status']`, foreign key at the bottom, `up()` only, a comment on every column
- [X] T002 [P] Add `access_granted_at` to `database/migrations/0000_01_01_000000_create_accounts_table.php`, nullable datetime, commented as when an administrator granted full access, leaving `has_lifetime_access` and `purchased_at` where they are until T040
- [X] T003 [P] Add `checkout.session.completed` to the events in `config/cashier.php`, with a comment saying an API client is never redirected and a closed tab must still be settled
- [X] T004 [P] Extend `tests/TestCase.php`: teach `fakeStripe()` to answer `GET checkout/sessions/{id}`, and add `stripeCheckoutSession(string $id, string $paymentStatus, ?string $customer)` building the payload
- [X] T005 Add `app/Models/StripeOrder.php` (`belongsTo` account, `#[Fillable]` over the five columns, `isSettled()`, `offerKind()` through `StripeCatalogue`) and `database/factories/StripeOrderFactory.php` with `settled()`, `unpaid()` and `refunded()`, tested in `tests/Unit/Models/StripeOrderTest.php`

## Phase 2: Foundational (blocking prerequisites)

- [X] T006 In `app/Models/Account.php` add `orders()` newest first and `settledOrder()`, rewrite `hasFullAccess()` over a settled order, a valid subscription and `access_granted_at`, rewrite `accessKind()` to read which rather than guess from an invoice, point `availableOffers()` and `offersAvailable()` at `hasFullAccess()`, and keep one throwaway read of `has_lifetime_access` marked for deletion at T040
- [X] T007 [P] In `database/factories/AccountFactory.php` make `withLifetimeAccess()` create a settled order rather than set the marker, and add `withGrantedAccess()` setting `access_granted_at`
- [X] T008 Cover the four sources in `tests/Unit/Models/AccountTest.php`: a settled order, an unpaid one holding nothing, a valid subscription, a standing grant, and the kind each yields
- [X] T009 Run `php artisan test --compact --filter=Account` and confirm nothing in the suite still depends on the marker

## Phase 3: User Story 1, paying for full access unlocks it (P1)

**Goal**: an order is written when the checkout session opens and settled from the session named on the return, or by Stripe's own confirmation, and access opens exactly once.

**Independent test**: against the fake client, buy the lifetime offer, check the row is `unpaid` before payment and `paid` after the return, then repeat while never loading the return and check `checkout.session.completed` settles it.

- [X] T010 [P] [US1] Test `StartCheckout` in `tests/Unit/Actions/StartCheckoutTest.php`: the order is written `unpaid` naming the session and the price, `success_url` carries `session_id={CHECKOUT_SESSION_ID}`, a subscription writes no order, and the name the test claims matches what it asserts
- [X] T011 [P] [US1] Test `SettleOrder` in `tests/Unit/Actions/SettleOrderTest.php`: settles on a paid session, refuses an unpaid one and another account's, returns early on an order already paid, stops a running subscription renewing, notifies once, logs once
- [X] T012 [P] [US1] Test the return in `tests/Feature/Controllers/App/Settings/Account/Billing/CheckoutReturnControllerTest.php`: paid gives `?checkout=success`, unpaid and unreachable give `?checkout=pending`, a missing, unknown or foreign `session_id` settles nothing, and a guest is redirected to the login
- [X] T013 [P] [US1] Test `HandleStripeCheckoutSessionCompleted` in `tests/Unit/Listeners/HandleStripeCheckoutSessionCompletedTest.php`: settles the order, is idempotent against a repeated event, ignores a session no order names
- [X] T014 [P] [US1] Test in `tests/Unit/Listeners/HandleStripeInvoicePaidTest.php` that `invoice.paid` no longer unlocks anything for a one time purchase and still records a renewal
- [X] T015 [P] [US1] Test `BillingViewModel` in `tests/Unit/ViewModels/Settings/Account/Billing/BillingViewModelTest.php`: the order, its day and its state, `purchasedAt()` from the order, and the pending state after a return Stripe has not confirmed
- [X] T016 [P] [US1] Test the API answer in `tests/Feature/Controllers/Api/BillingControllerTest.php`: the `order` object, `purchased_at` from the order, null when nothing was ordered
- [X] T017 [US1] In `app/Actions/StartCheckout.php` write the order after Stripe gives the session an id, and carry `session_id={CHECKOUT_SESSION_ID}` on `success_url`, for the one time purchase only
- [X] T018 [US1] Add `app/Actions/SettleOrder.php` per [contracts/billing-api.md](contracts/billing-api.md), taking the account and the session id, and delete `app/Actions/RecordPurchase.php` and `tests/Unit/Actions/RecordPurchaseTest.php` once what they covered is covered here
- [X] T019 [US1] Add `app/Http/Controllers/App/Settings/Account/Billing/CheckoutReturnController.php` and the route `settings.checkoutReturn.show` in `routes/web.php`, redirecting to the billing index with `?checkout=success` or `?checkout=pending`
- [X] T020 [US1] Add `app/Listeners/HandleStripeCheckoutSessionCompleted.php` on `WebhookReceived`, five tries with the backoff the other listeners carry, and register it in `app/Providers/AppServiceProvider.php`
- [X] T021 [US1] Trim `app/Listeners/HandleStripeInvoicePaid.php` to renewals, since a one time purchase no longer raises an invoice
- [X] T022 [US1] Read the order in `app/ViewModels/Settings/Account/Billing/BillingViewModel.php` and show what was ordered, the day and the state in `resources/views/app/settings/account/billing/index.blade.php`
- [X] T023 [US1] Add the `order` object to `app/Http/Resources/BillingResource.php` and describe it in `resources/docs/api/12-billing.php`
- [X] T024 [US1] Run `php artisan test --compact --filter='StripeOrder|SettleOrder|StartCheckout|CheckoutReturn|Billing'`

## Phase 4: User Story 2, access that ends says why (P2)

**Goal**: an order that stops holding keeps its row, carries what Stripe now says of it, and money back nobody can match reaches an operator instead of vanishing.

**Independent test**: on an account holding a settled order, run the refund path with a charge that matches nothing and check access stands with the failure in the log, then end the order directly and check both screens say when and why.

- [X] T025 [P] [US2] Test `EndAccess` in `tests/Unit/Actions/EndAccessTest.php`: a refunded or disputed order carries that word and holds nothing, a refunded renewal still cancels the subscription now, a grant is untouched by money back
- [X] T026 [P] [US2] Test `RestoreAccess` in `tests/Unit/Actions/RestoreAccessTest.php`: the same order returns to `paid`, no second row appears, an account already holding access is left alone
- [X] T027 [P] [US2] Test in `tests/Unit/Listeners/HandleStripeRefundTest.php` and `HandleStripeDisputeClosedTest.php` that money back matching neither a payment nor an order is logged at error level naming the charge and the account, and takes nothing away
- [X] T028 [P] [US2] Test that the reason reaches the reader, in `BillingViewModelTest.php` and `tests/Feature/Controllers/App/Settings/Account/Billing/BillingControllerTest.php`
- [X] T029 [US2] Rework `app/Actions/EndAccess.php` to write the order's state instead of the marker, keeping the subscription branch as it is, and to say nothing to somebody who still holds access another way
- [X] T030 [US2] Rework `app/Actions/RestoreAccess.php` to put the order back to `paid`, refusing to resurrect a cancelled subscription as before
- [X] T031 [US2] Log the unmatched charge in `app/Helpers/StripeCharge.php` or its callers, at error level, once per event
- [X] T032 [US2] Show when and why access ended on `resources/views/app/settings/account/billing/index.blade.php` and in `app/Http/Resources/BillingResource.php`
- [X] T033 [US2] Run `php artisan test --compact --filter='EndAccess|RestoreAccess|StripeRefund|Dispute'`

## Phase 5: User Story 3, a grant is not an order (P3)

**Goal**: an administrator grants and withdraws full access with the marker gone, and the grant is never written as an order.

**Independent test**: grant access from the admin space and check no row appears in `stripe_orders`, the screen names the administrator and the day, and withdrawing it both clears the grant and ends any settled order.

- [X] T034 [P] [US3] Test `GrantLifetimeAccess` in `tests/Unit/Actions/GrantLifetimeAccessTest.php`: sets `access_granted_at`, writes no order, logs the administrator action, is idempotent on an account already granted
- [X] T035 [P] [US3] Test `WithdrawLifetimeAccess` in `tests/Unit/Actions/WithdrawLifetimeAccessTest.php`: clears the grant, ends a settled order with the reason it is given, and leaves a running subscription alone
- [X] T036 [P] [US3] Test the admin space in `tests/Feature/Controllers/App/Admin/AccountLifetimeAccessControllerTest.php` and `AccountControllerTest.php`, and the view model in `tests/Unit/ViewModels/Admin/AccountViewModelTest.php`
- [X] T037 [US3] Rework `app/Actions/GrantLifetimeAccess.php` onto `access_granted_at`, dropping `purchased_at`
- [X] T038 [US3] Rework `app/Actions/WithdrawLifetimeAccess.php` to take an `AccessEndReasonEnum`, clear the grant and end a settled order with that reason, and pass the reason from `app/Http/Controllers/App/Admin/AccountLifetimeAccessController.php`
- [X] T039 [US3] Show the grant, its administrator from `administrator_logs` and the order in `resources/views/app/admin/accounts/show.blade.php`, through `app/ViewModels/Admin/AccountViewModel.php`

## Phase 6: Polish and cross cutting

- [X] T040 Remove `has_lifetime_access` and `purchased_at` from `database/migrations/0000_01_01_000000_create_accounts_table.php`, the property, the `#[Fillable]` entry, the cast and the throwaway read from `app/Models/Account.php`, the write from `app/Actions/CreateAccount.php`, the entry from `database/factories/AccountFactory.php`, and grep `app/`, `database/`, `resources/` and `tests/` for both names
- [X] T041 Run `php artisan test --compact` whole, which is what proves FR-003: no screen, cap, email or API answer reads a marker that no longer exists
- [X] T042 [P] Rewrite the invoice section of `docs/STRIPE.md`, since the purchase no longer needs Stripe to raise one, add `checkout.session.completed` to its event table, and say that a refunded purchase is withdrawn by hand
- [X] T043 Run `php artisan monica:localize` and translate everything it reports in the nine locales, respecting each locale's declared typography
- [X] T044 Audit `resources/views/app/settings/account/billing/index.blade.php` and `resources/views/app/admin/accounts/show.blade.php` for keyboard operation, screen reader announcement, contrast, visible focus and 200% zoom
- [X] T045 [P] Run `vendor/bin/pint --dirty --format agent`
- [X] T046 [P] Run `vendor/bin/phpstan analyse --memory-limit=2G` and fix new findings
- [X] T047 Run `php artisan test --compact` against each of SQLite, MySQL and PostgreSQL. SQLite passes. MySQL is reachable locally but the `erica` user sees only the development database, and running the suite there would wipe it. `pdo_pgsql` is not installed. CI covers MySQL
- [X] T048 Walk [quickstart.md](quickstart.md) end to end in Stripe test mode, the closed tab, the abandoned session, the foreign session and the refund included

## Phase 7: Asked for after the walkthrough

- [X] T049 In `app/Actions/StartCheckout.php` close the session behind the account's unpaid order at Stripe, then write the new one time checkout over that row, session, price, state and date, and test in `tests/Unit/Actions/StartCheckoutTest.php` that a second checkout leaves one order naming the newer session, that the old session is closed, that a session Stripe will not close keeps its row, that an order money came back out of is never written over, and that another account's order is untouched

- [X] T050 Show `See it on Stripe` in the lifetime panel of `resources/views/app/settings/account/billing/index.blade.php`, guarded by `isKnownToStripe()` on `app/ViewModels/Settings/Account/Billing/BillingViewModel.php`, and open `app/Http/Controllers/App/Settings/Account/Billing/SubscriptionPortalController.php` to any account Stripe knows rather than to subscribers alone, tested in `tests/Feature/Controllers/App/Settings/Account/Billing/SubscriptionPortalControllerTest.php`

- [X] T051 Show the same order and subscription facts on `resources/views/app/admin/accounts/show.blade.php` as the owner's own screen, through `accessKindLabel()`, `order()`, `subscription()`, `subscriptionOfferLabel()`, `renewsAt()`, `endsAt()` and `stripeCustomerUrl()` on `app/ViewModels/Admin/AccountViewModel.php`, with a link to the customer on Stripe's dashboard, one `State at Stripe` label only so the definition list reads unambiguously, tested in `tests/Unit/ViewModels/Admin/AccountViewModelTest.php` and `tests/Feature/Controllers/App/Admin/AccountControllerTest.php`

## Dependencies

- **Phase 1** blocks everything. T005 depends on T001; T004 depends on nothing but is needed by T012 and T013.
- **Phase 2** blocks every story. T006 depends on T002 and T005; T007 on T005; T008 on T006 and T007.
- **Phase 3 (US1)** depends on Phase 2 only. Tests T010 to T016 before their implementation; T017 before T019; T018 before T019 and T020; T022 before T024; T021 may land any time after T018.
- **Phase 4 (US2)** depends on Phase 2, and on US1 only for having an order to end. T025 and T026 before T029 and T030; T031 before T027 passes.
- **Phase 5 (US3)** depends on Phase 2 only, and on US1 for the part of T035 that ends a settled order.
- **Phase 6** depends on every story. T040 before T041, and T041 before T047. T043 after the last string is written, so after T039 and T042.
- **Phase 7** depends on US1, since there is no order to replace before T017.
- Within a story: tests before implementation, actions before listeners and controllers, controllers before views, views before the copy `monica:localize` collects.

**MVP**: Phase 1 + Phase 2 + US1. It is the whole bug: money is taken today and nothing opens. US2 and US3 are correctness work on top, and US3 has to land in the same pull request as T040, since removing the marker is what breaks the grant.
