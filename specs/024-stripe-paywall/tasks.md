---
description: "Task list for the Stripe paywall"
---

# Tasks: Stripe Paywall

**Input**: Design documents in `/specs/024-stripe-paywall/`

**Prerequisites**: [plan.md](plan.md), [spec.md](spec.md), [research.md](research.md), [data-model.md](data-model.md), [contracts/](contracts/), [quickstart.md](quickstart.md)

**Reference**: branch `2026-09-06-paddle-paywall`, feature 022. Every file named below that has a counterpart there is ported from it, with what [research.md](research.md) says Stripe changes. Read the counterpart first, never merge the branch.

**Tests**: included and not optional. Principle III makes a test a delivery condition for every action, model, listener, notification, view model, resource and controller. Stripe is faked with the fixture client of T006; webhooks are tested by dispatching Cashier's events.

**Format**: `[ID] [P?] [Story?] Description with file path`. `[P]` means it touches no file another incomplete task touches.

## Phase 1: Setup

**Goal**: install the dependency, give the tests a fake provider.

- [X] T001 Require `laravel/cashier` ^16.8 in `composer.json`, publish its config only (`--tag=cashier-config`, never `cashier-migrations`), run Pint on `config/cashier.php`
- [X] T002 Add Cashier's customer columns `stripe_id`, `pm_type`, `pm_last_four`, `trial_ends_at` to `database/migrations/0000_01_01_000000_create_accounts_table.php`, and write `..._create_subscriptions_table.php` and `..._create_subscription_items_table.php` keyed on `account_id`, mirroring the columns Cashier ships, `up()` only, foreign keys at the bottom
- [X] T003 [P] Add `free_vault_limit`, `free_contact_limit`, `paid_storage_limit_in_bytes`, `offer_tag` and a `currency` per locale to `config/monica.php`, and `MONICA_*`, `STRIPE_KEY`, `STRIPE_SECRET`, `STRIPE_WEBHOOK_SECRET`, `CASHIER_CURRENCY` to `.env.example`
- [X] T004 [P] Exempt `stripe/*` from CSRF in `bootstrap/app.php`
- [X] T005 Add `configureBilling()` to `app/Providers/AppServiceProvider.php`: `Cashier::useCustomerModel(Account::class)`, `keepPastDueSubscriptionsActive()`, `calculateTaxes()`
- [X] T006 [P] Add `tests/Fixtures/Stripe/FakeStripeClient.php` implementing the SDK's `ClientInterface`, answering canned bodies by method and path and recording requests, and the helpers `fakeStripe()`, `stripePrices()`, `subscribeAccount()`, `paidInvoicePayload()` in `tests/TestCase.php`

## Phase 2: Foundational (blocking prerequisites)

**Goal**: drop the stamped flag, give the Account its accessors, the catalogue, the payment table and the enums every story reads.

- [X] T007 Remove the `is_self_hosted` column from `database/migrations/0000_01_01_000000_create_accounts_table.php`
- [X] T008 In `app/Models/Account.php`: remove `is_self_hosted`, read `isMetered()` from `config('monica.self_hosted')`, add `Laravel\Cashier\Billable`, and port from 022 `hasFullAccess()`, `storageLimitInBytes()`, `vaultLimit()`, `contactLimit()`, `contactCount()`, `isOnFreeOffer()`, `accessKind()`, `availableOffers()`, `offersAvailable()`, `subscriptionOfferKind()`, `nextBillingDate()`, plus `payments()` and `purchasePayment()` on the new table
- [X] T009 [P] Remove the `is_self_hosted` write from `app/Actions/CreateAccount.php`, the `selfHosted()` state from `database/factories/AccountFactory.php`, read the config in `app/ViewModels/Admin/AccountViewModel.php`, and set `config(['monica.self_hosted' => true])` in the six tests 022 lists
- [X] T010 [P] Port `app/Enums/OfferKindEnum.php` (`billingInterval()` answering Stripe's `month`, `year` or null), `AccessKindEnum.php`, `AccessEndReasonEnum.php` with `Disputed` added, the `UserActionEnum` cases and `EmailType::AccessUnlocked` and `AccessEnded`, with their tests in `tests/Unit/Enums/`
- [X] T011 Add `database/migrations/..._create_payments_table.php` per [data-model.md](data-model.md), `app/Models/Payment.php` with `total()` and `tax()` written for the reader's locale, `database/factories/PaymentFactory.php`, tested in `tests/Unit/Models/PaymentTest.php`
- [X] T012 Add `app/Helpers/StripeCatalogue.php`: active prices with `currency_options` expanded, tag from `metadata`, billing cycle checked, an hour of shape checked cache, failures logged and not cached, `currencies()`, `kindOf()`, `kindOfSubscription()`, `kindOfPayment()`, `forget()`, tested in `tests/Unit/Helpers/StripeCatalogueTest.php`
- [X] T013 Point `app/Actions/CreateFile.php` at `storageLimitInBytes()`, test the accessors in `tests/Unit/Models/AccountTest.php` across self hosted, free, lifetime, granted and subscribed, and the paid ceiling in `tests/Unit/Actions/CreateFileTest.php`
- [X] T014 Run `php artisan test --compact --filter=Account` and confirm dropping the column broke nothing

## Phase 3: User Story 1, buy full access (P1)

**Goal**: an account is sent to Stripe's checkout for either offer and comes back holding full access once the webhook lands.

**Independent test**: on a free account complete each offer in turn against the fake client; access opens, one payment row exists with its invoice, the unlock email goes out once, and a lifetime holder is offered nothing further.

- [X] T015 [P] [US1] Test `RecordPayment` in `tests/Unit/Actions/RecordPaymentTest.php`: one row per invoice, a repeated event updates it, kind derived from whether the invoice belongs to a subscription, invoice number and URLs stored
- [X] T016 [P] [US1] Test `RecordPurchase` in `tests/Unit/Actions/RecordPurchaseTest.php`: sets the flag, `purchased_at` and `billing_email`, refuses an owner, stops a running subscription renewing, notifies once
- [X] T017 [P] [US1] Test `RecordSubscription` in `tests/Unit/Actions/RecordSubscriptionTest.php`: writes nothing to the account, logs, notifies once
- [X] T018 [P] [US1] Test `StartCheckout` in `tests/Unit/Actions/StartCheckoutTest.php`: payment mode with `invoice_creation` for lifetime, subscription mode otherwise, the picked currency, `automatic_tax`, `customer_update.address`, return URLs, refuses a price the account may not take, `ProviderUnreachable` when Stripe fails
- [X] T019 [P] [US1] Test `HandleStripeInvoicePaid` in `tests/Unit/Listeners/HandleStripeInvoicePaidTest.php`: `invoice.paid` records the payment, no subscription behind it records the purchase, an unknown customer and any other event change nothing
- [X] T020 [P] [US1] Test `HandleStripeSubscription` in `tests/Unit/Listeners/HandleStripeSubscriptionTest.php` on `WebhookHandled` for `customer.subscription.created`
- [X] T021 [P] [US1] Test `AccessUnlocked` in `tests/Unit/Notifications/AccessUnlockedTest.php`: subject, panel, action URL, outro lines, `emailType()`
- [X] T022 [P] [US1] Test `BillingViewModel` in `tests/Unit/ViewModels/Settings/Account/Billing/BillingViewModelTest.php`: offers priced tax inclusive in the picked currency, the currency fallback chain, empty when self hosted or lifetime, awaiting confirmation after `?checkout=success`
- [X] T023 [P] [US1] Test the screen in `tests/Feature/Controllers/App/Settings/Account/Billing/BillingControllerTest.php`, checkout redirects in `CheckoutControllerTest.php` and the picker in `BillingCurrencyControllerTest.php`, same folder
- [X] T024 [P] [US1] Test the `offer-list` component in `tests/Feature/Views/Components/OfferListTest.php`: three columns, price, one form per offer posting to the checkout route
- [X] T025 [P] [US1] Add `app/Exceptions/AccountAlreadyOwnsTheProduct.php` and `app/Exceptions/ProviderUnreachable.php`
- [X] T026 [P] [US1] Add `app/Actions/RecordPayment.php`
- [X] T027 [P] [US1] Add `app/Actions/RecordPurchase.php` and `app/Actions/RecordSubscription.php`
- [X] T028 [US1] Add `app/Actions/StartCheckout.php` returning Cashier's `Checkout`, the one place both controllers ask for a session
- [X] T029 [US1] Add `app/Notifications/AccessUnlocked.php` implementing `HasEmailType`, queue `high`
- [X] T030 [US1] Add `app/Listeners/HandleStripeInvoicePaid.php` and `app/Listeners/HandleStripeSubscription.php`, both `ShouldQueue`
- [X] T031 [US1] Add `app/ViewModels/Settings/Account/Billing/BillingViewModel.php`: offers with their price, `currencies()`, `currency()`, `isAwaitingConfirmation()`
- [X] T032 [US1] Add `BillingController@index`, `CheckoutController@store` and `BillingCurrencyController@update` in `app/Http/Controllers/App/Settings/Account/Billing/`, with `settings.billing.index`, `settings.checkout.store` and `settings.billingCurrency.update` in `routes/web.php`
- [X] T033 [US1] Add `resources/views/components/offer-list.blade.php` (a form per offer, no script), `resources/views/app/settings/account/billing/index.blade.php` (notices, currency picker, offers) and `_tabs.blade.php`
- [X] T034 [US1] Add the Billing row to `app/ViewModels/Settings/SettingsViewModel.php` and `resources/views/components/settings/sidebar.blade.php`, updating `tests/Unit/ViewModels/Settings/SettingsViewModelTest.php`
- [X] T035 [US1] Add `app/Http/Resources/BillingResource.php`, `app/Http/Controllers/Api/BillingController.php@show` and `Api/CheckoutSessionController.php@store`, their routes in `routes/api.php`, tested in `tests/Feature/Controllers/Api/BillingControllerTest.php` and `CheckoutSessionControllerTest.php`
- [X] T036 [US1] Add `resources/docs/api/12-billing.php` with `billing-show` and `billing-checkout-sessions-create`, update the section count in `tests/Feature/Controllers/App/Docs/ApiDocsControllerTest.php` and the arms in `ApiDocsReturnFieldsTest.php`
- [X] T037 [US1] Test in `tests/Feature/StripeWebhookTest.php` that a payload signed with the configured secret posted to `stripe/webhook` reaches the listeners and an unsigned one is refused

## Phase 4: User Story 2, be told why an action is refused (P2)

**Goal**: a free account at a cap is refused with the cap named and the offers presented, and loses nothing.

**Independent test**: drive a free account to each ceiling; every refusal names its cap, everything already created stays readable and editable, and a self hosted instance shows no cap and no offer.

- [X] T038 [P] [US2] Test the vault cap in `tests/Unit/Actions/CreateVaultTest.php`, including an account already over it keeping and editing what it has, and raise the ceiling in the existing vault tests that create several
- [X] T039 [P] [US2] Test the contact cap in `tests/Unit/Actions/CreateContactTest.php`, excluding the Me contact and partial contacts, counting archived ones
- [X] T040 [P] [US2] Test the web refusals in `tests/Feature/Controllers/App/Vaults/VaultControllerTest.php` and `ContactControllerTest.php`, and the 422s in `tests/Feature/Controllers/Api/VaultControllerTest.php` and `ContactControllerTest.php`
- [X] T041 [P] [US2] Add `app/Exceptions/TooManyVaults.php` and `app/Exceptions/TooManyContacts.php`
- [X] T042 [US2] Throw them from `app/Actions/CreateVault.php` and `app/Actions/CreateContact.php` when the ceiling is reached
- [X] T043 [P] [US2] Catch them in `app/Http/Controllers/App/Vaults/VaultController.php` and `ContactController.php` with `back()->withErrors()`, and in `app/Http/Controllers/Api/VaultController.php` and `ContactController.php` with `ValidationException`
- [X] T044 [P] [US2] Add `resources/views/components/paywall-prompt.blade.php`, tested in `tests/Feature/Views/Components/PaywallPromptTest.php`
- [X] T045 [US2] Show it from `ceilingMessage()` in `app/ViewModels/Vaults/VaultFormViewModel.php` and `ContactFormViewModel.php` on `resources/views/app/vaults/form.blade.php` and `resources/views/app/vaults/contacts/form.blade.php`, tested in their view model tests

## Phase 5: User Story 3, manage a subscription (P3)

**Goal**: a subscriber can cancel and take it back, move up with nothing charged today, buy outright, open the portal, and a lapse, a refund, a dispute or a deleted account ends things the right way.

**Independent test**: subscribe, cancel, resume, move up; then end access by each of abandonment, full refund and dispute, and delete an account holding a subscription.

- [X] T046 [P] [US3] Test `CancelSubscription` in `tests/Unit/Actions/CancelSubscriptionTest.php` (period end, never now) and `ResumeSubscription` in `ResumeSubscriptionTest.php` (only while ending)
- [X] T047 [P] [US3] Test `UpgradeSubscription` in `tests/Unit/Actions/UpgradeSubscriptionTest.php`: `proration_behavior=none` and no anchor change sent, refuses no subscription, an ending one, a price that is not a longer subscription, and a Stripe refusal
- [X] T048 [P] [US3] Test `EndAccess` in `tests/Unit/Actions/EndAccessTest.php`: withdraws the flag only for a refund or dispute of the purchase, cancels a subscription now for one of a renewal, leaves `purchased_at` and the payment row, leaves a grant standing, notifies with the reason
- [X] T049 [P] [US3] Test `HandleStripeSubscriptionEnded` in `tests/Unit/Listeners/HandleStripeSubscriptionEndedTest.php`: `payment_failed` becomes `PaymentFailed`, anything else `Cancelled`, `customer.subscription.updated` changes nothing
- [X] T050 [P] [US3] Test `HandleStripeRefund` in `tests/Unit/Listeners/HandleStripeRefundTest.php`: a full `charge.refunded` and a `charge.dispute.created` end access, a partial refund does not, a charge the account never paid does nothing
- [X] T051 [P] [US3] Test `AccessEnded` in `tests/Unit/Notifications/AccessEndedTest.php` for every reason
- [X] T052 [P] [US3] Test `DestroyAccount` in `tests/Unit/Actions/DestroyAccountTest.php`: cancels a valid subscription now, deletes the payment rows, refuses with `ProviderUnreachable` when Stripe fails and deletes nothing
- [X] T053 [P] [US3] Test the web side in `tests/Feature/Controllers/App/Settings/Account/Billing/SubscriptionControllerTest.php`, `SubscriptionCancellationControllerTest.php`, `SubscriptionPortalControllerTest.php`, and the deletion refusal in `tests/Feature/Controllers/App/Settings/Account/AccountControllerTest.php`
- [X] T054 [P] [US3] Add `app/Exceptions/AccountHasNoSubscription.php` and `app/Exceptions/SubscriptionCannotBeChanged.php`
- [X] T055 [P] [US3] Add `app/Actions/CancelSubscription.php`, `ResumeSubscription.php`, `UpgradeSubscription.php` (`noProrate()->swap()`) and `EndAccess.php`
- [X] T056 [US3] Add `app/Notifications/AccessEnded.php` implementing `HasEmailType`, one wording per `AccessEndReasonEnum`
- [X] T057 [US3] Add `app/Listeners/HandleStripeSubscriptionEnded.php` on `WebhookHandled` and `app/Listeners/HandleStripeRefund.php` on `WebhookReceived`, both `ShouldQueue`
- [X] T058 [US3] Cancel a valid subscription now from `app/Actions/DestroyAccount.php` before deleting, refusing on `ProviderUnreachable`, and say so in the deletion warning on `resources/views/app/settings/account/profile/index.blade.php`, caught in `app/Http/Controllers/App/Settings/Account/AccountController.php`
- [X] T059 [US3] Add `SubscriptionController@update` and `@destroy`, `SubscriptionCancellationController@destroy` and `SubscriptionPortalController@show` (through `billingPortalUrl()`) in `app/Http/Controllers/App/Settings/Account/Billing/`, with their routes in `routes/web.php`
- [X] T060 [US3] Add to `resources/views/app/settings/account/billing/index.blade.php` the cancel warning naming the end date, the keep button, the portal link, and render a longer subscription in `resources/views/components/offer-list.blade.php` as a confirmed switch whose modal says nothing is charged today, flagged by `BillingViewModel::offers()`
- [X] T061 [US3] Add `Api/SubscriptionController.php` (`update`, `destroy`), `Api/SubscriptionCancellationController.php@destroy` and `Api/PortalSessionController.php@store` in `app/Http/Controllers/`, their routes in `routes/api.php`, tested in `tests/Feature/Controllers/Api/SubscriptionControllerTest.php`, `SubscriptionCancellationControllerTest.php` and `PortalSessionControllerTest.php`
- [X] T062 [US3] Document `billing-subscription-update`, `billing-subscription-destroy`, `billing-subscription-cancellation-destroy` and `billing-portal-sessions-create` in `resources/docs/api/12-billing.php`, updating the two docs tests
- [X] T077 [US3] Name every webhook event this feature acts on in `config/cashier.php`, since none of the five is in the package's default list, guarded by two tests in `tests/Feature/Webhooks/StripeWebhookTest.php`
- [X] T078 [US3] Add `app/Helpers/StripeCharge.php`, `app/Actions/RestoreAccess.php` and the listeners `HandleStripeDisputeClosed` and `HandleStripeRefundReversed`, so a dispute won or a refund that fails puts the purchase back, with their tests

## Phase 6: User Story 4, see what is on record (P4)

**Goal**: the billing screen, the invoices tab and the API report the offer held, the dates, the billing email and every payment with its invoice.

**Independent test**: read the screen, the tab and the API for a lifetime account, a subscriber, an ending subscriber, an administrator grant, an account that never paid and a self hosted instance.

- [X] T063 [P] [US4] Test `InvoicesViewModel` in `tests/Unit/ViewModels/Settings/Account/Billing/InvoicesViewModelTest.php`: newest first, label per kind, amount written for the reader's locale, empty when nothing was paid
- [X] T064 [P] [US4] Test the on record panel in `tests/Feature/Controllers/App/Settings/Account/Billing/BillingControllerTest.php` (a grant shows no invoice) and the tab in `InvoiceControllerTest.php` (list, empty state, `show` redirecting to the hosted invoice, 404 for another account's)
- [X] T065 [P] [US4] Test `GET billing/payments` and `GET billing/payments/{payment}` in `tests/Feature/Controllers/Api/PaymentControllerTest.php`, and the `payment`, `access_kind`, `renews_at` and `ends_at` fields across every state in `BillingControllerTest.php`
- [X] T066 [US4] Extend `app/ViewModels/Settings/Account/Billing/BillingViewModel.php` with `accessKind()`, `accessKindLabel()`, `subscriptionLabel()`, `renewsAt()`, `endsAt()`, `isEnding()`, `wasGranted()`, `paymentCount()`, `vaultsHeld()`, `contactsHeld()`, `storageHeld()`, and add `InvoicesViewModel.php` beside it
- [X] T067 [US4] Add `InvoiceController@index` and `@show` in `app/Http/Controllers/App/Settings/Account/Billing/`, their routes, and `resources/views/app/settings/account/billing/invoices.blade.php` with its empty state
- [X] T068 [US4] Add the access panel and the on record list to `resources/views/app/settings/account/billing/index.blade.php`
- [X] T069 [US4] Add `app/Http/Resources/PaymentResource.php`, `app/Http/Controllers/Api/PaymentController.php` (`index`, `show`), their routes, the `payment` field in `BillingResource`, and `billing-payments-index` and `billing-payments-show` in `resources/docs/api/12-billing.php`, updating the two docs tests
- [X] T070 [US4] Show the paid ceiling on `resources/views/app/admin/accounts/show.blade.php` through `app/ViewModels/Admin/AccountViewModel.php`, updating `tests/Unit/ViewModels/Admin/AccountViewModelTest.php` and `tests/Feature/Controllers/App/Admin/AccountControllerTest.php`

## Phase 7: Polish and cross cutting

- [X] T071 Run `php artisan monica:localize` and translate every string it reports in all nine locales, following `.claude/rules/translations.md`, then `php artisan monica:localize --check`
- [X] T072 Audit `resources/views/app/settings/account/billing/index.blade.php`, `invoices.blade.php`, `resources/views/components/offer-list.blade.php` and `paywall-prompt.blade.php` for keyboard operation, screen reader announcement, contrast, visible focus and 200% zoom
- [X] T073 [P] Run `vendor/bin/pint --dirty --format agent`
- [X] T074 [P] Run `vendor/bin/phpstan analyse` and fix new findings
- [ ] T075 Run `php artisan test --compact` against each of SQLite, MySQL and PostgreSQL
- [ ] T076 Walk [quickstart.md](quickstart.md) end to end in Stripe test mode, the dunning cancellation, the full and partial refund and the account deletion included

## Dependencies

- **Phase 1** blocks everything. T005 depends on T001; T006 on T001.
- **Phase 2** blocks every story. T007 to T009 are one refactor and land together; T008 depends on T001, T002 and T011; T012 depends on T006 and T010.
- **Phase 3 (US1)** depends on Phase 2 only. Tests T015 to T024 before their implementation; T025 before T027 and T028; T026 and T027 before T030; T031 before T032 and T033; T035 before T036.
- **Phase 4 (US2)** depends on Phase 2 only, and on nothing in US1 except that T045 links to the billing route from T032.
- **Phase 5 (US3)** depends on US1, since there is no subscription to manage until one can be bought. T054 before T055; T055 before T057 to T061; T060 edits the files T033 wrote.
- **Phase 6 (US4)** depends on US1 and US3 for what it reports. T066 edits T031's view model, T068 edits T033's screen, T069 edits T035's resource.
- **Phase 7** depends on every story being done. T071 runs once, after the last string is written.
- Within a story: tests before implementation, exceptions before the actions that throw them, actions before listeners and controllers, controllers before views, routes before docs.

**MVP**: Phase 1 + Phase 2 + US1 + US2. Neither US1 nor US2 is worth shipping alone: US1 without US2 takes money for nothing that was gated, and US2 without US1 refuses people with no way to pay.
