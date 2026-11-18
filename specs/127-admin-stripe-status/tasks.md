# Tasks: Stripe status in the admin space

**Input**: Design documents from `specs/127-admin-stripe-status/`
**Prerequisites**: [plan.md](plan.md), [spec.md](spec.md), [research.md](research.md), [data-model.md](data-model.md), [contracts/http.md](contracts/http.md), [quickstart.md](quickstart.md)

**Tests**: Included. Principle III of the constitution makes them a delivery condition. Each test is written first, in the layer `.claude/rules/testing.md` assigns, and must fail before the code it covers exists.

**Organization**: One phase per user story, in priority order: US1 (P1), US2 (P2), US4 (P2), US3 (P3).

## Format: `[ID] [P?] [Story] Description`

- **[P]**: can run in parallel (different files, no dependency on an unfinished task)
- **[Story]**: the user story of spec.md the task serves

Every PHP file starts with `declare(strict_types=1);`. Every Blade file opens with its one line `{{-- --}}` comment and its `@var` block. Copy goes through `__()` or `trans_choice()`; a phrase with `:count` uses `trans_choice()`.

---

## Phase 1: Setup

**Purpose**: Read what the screen is modelled on before writing anything.

- [X] T001 Read `app/Http/Controllers/App/Admin/SearchEngineController.php`, `app/ViewModels/Admin/SearchEngineViewModel.php`, `resources/views/app/admin/search/index.blade.php`, `app/Actions/CheckSearchEngine.php`, `app/Actions/StartSearchIndexRebuild.php` and their tests, and `.claude/rules/components.md`; list the components the Search screen uses (`x-page-header`, `x-page-section`, `x-detail-list`, `x-badge`) for reuse

---

## Phase 2: Foundational (blocks every story)

**Purpose**: The screen exists, is guarded, and holds the self hosted sentence. Every story adds a block to it.

- [X] T002 [P] Add `['admin/billing', ...]` to the URL provider of `tests/Feature/Controllers/App/Admin/AdminAccessTest.php` and of `tests/Feature/Controllers/App/Admin/NoVaultContentTest.php`, so a visitor, a regular user and a deactivated administrator get not found (US1 scenario 9)
- [X] T003 [P] Write `tests/Unit/ViewModels/Admin/BillingStatusViewModelTest.php` with `it_says_in_one_sentence_that_a_self_hosted_instance_takes_no_payment` (config `monica.self_hosted` true, `Http::fake()`, assert the sentence and `Http::assertNothingSent()`)
- [X] T004 Create `app/ViewModels/Admin/BillingStatusViewModel.php`, constructor `private readonly User $user, private readonly ?array $connection`, with `isSelfHosted(): bool` reading `config('monica.self_hosted')` and `selfHostedSentence(): string` ("This instance takes no payment, so there is nothing to show about Stripe.")
- [X] T005 Create `app/Http/Controllers/App/Admin/BillingStatusController.php` with `index(Request $request): View`: for now it always passes `connection: null`; T013 makes it run `CheckStripeConnection` on a hosted instance and never on a self hosted one (R14); render `app.admin.billing.index` with `viewModel`
- [X] T006 Add `Route::get('admin/billing', [BillingStatusController::class, 'index'])->name('admin.billing.index');` to the `administrator` group of `routes/administrator.php`, after the Search route
- [X] T007 Create `resources/views/app/admin/billing/index.blade.php` in the admin layout with `x-page-header` titled "Billing" and, when self hosted, the one sentence and nothing else
- [X] T008 Add `['label' => __('Billing'), 'route' => 'admin.billing.index', 'section' => 'admin.billing.*', 'icon' => 'credit-card']` to the navigation array of `resources/views/layouts/admin.blade.php`, after Search
- [X] T009 Write `tests/Feature/Controllers/App/Admin/BillingStatusControllerTest.php` with `it_shows_the_billing_screen_to_an_administrator` (heading copy, `Queue::fake()` then `Queue::assertNothingPushed()` for FR-011); run T002, T003 and T009

**Checkpoint**: `/admin/billing` answers an administrator, 404 for everybody else, and holds one sentence on a self hosted instance.

---

## Phase 3: User Story 1, can billing take money (Priority: P1) 🎯 MVP

**Goal**: Keys set and their modes, both warnings, whether Stripe accepts the secret key, and the offers on sale.

**Independent Test**: With live keys and three tagged prices, the screen says live mode, every key set, Stripe reachable, and lists three offers with their amounts.

### Tests for User Story 1

- [X] T010 [P] [US1] Write `tests/Unit/Actions/CheckStripeConnectionTest.php`: `it_reports_not_configured_and_sends_nothing_without_a_secret_key`; `it_reads_the_mode_of_each_key_from_its_prefix` (`pk_test_`, `sk_live_`, `rk_test_`, an unrecognised prefix); `it_says_when_the_modes_of_the_keys_disagree`; `it_warns_when_production_runs_on_test_keys` and `it_warns_when_another_environment_runs_on_live_keys` (switch `app.env`); `it_reports_reachable_with_the_response_time_when_stripe_answers`; `it_reports_refused_when_stripe_answers_401`; `it_reports_unreachable_with_the_reason_on_a_timeout` (`Http::fake()` throwing `ConnectionException`); `it_never_returns_a_key_value` (serialize the result for success and for a failure whose message quotes the key; assert neither key appears). Use `Http::fake(['api.stripe.com/v1/webhook_endpoints*' => ...])` and Office data for names
- [X] T011 [P] [US1] Add to `tests/Unit/ViewModels/Admin/BillingStatusViewModelTest.php`: `it_names_what_stops_working_without_each_missing_key`; `it_lists_the_offers_with_their_amount_in_each_currency` (`fakeStripe()`); `it_names_the_kinds_of_offer_with_no_price_on_sale` (`fakeStripe(kinds: ['monthly'])`); `it_tells_an_unreadable_catalogue_apart_from_an_empty_one`; `it_does_not_read_the_catalogue_when_stripe_is_unreachable` (assert the fake Stripe client received no `prices` request)
- [X] T012 [P] [US1] Add `it_shows_the_keys_block_and_sends_nothing_when_the_secret_key_is_missing` to `tests/Feature/Controllers/App/Admin/BillingStatusControllerTest.php` (hosted, `cashier.secret` null, `Http::assertNothingSent()`, the response holds the missing key copy)

### Implementation for User Story 1

- [X] T013 [US1] Create `app/Actions/CheckStripeConnection.php`, and wire it into `BillingStatusController::index()` for a hosted instance only, per [data-model.md](data-model.md) "The action's answer" and [contracts/http.md](contracts/http.md): class docblock of one sentence; `execute(): array` with an `@return array{...}` shape; key modes from prefixes `pk_test_`, `sk_test_`, `rk_test_` (test) and `pk_live_`, `sk_live_`, `rk_live_` (live), anything else `unknown`, unset `null`; `environment_warning` from the secret key's mode against `app()->isProduction()`; without a secret key return `connection: not_configured` and call nothing; otherwise `Http::withToken($secret)->timeout(3)->connectTimeout(2)->get('https://api.stripe.com/v1/webhook_endpoints', ['limit' => 100])`, timed with `hrtime()`; 200 reachable, 401 refused, 403 reachable with endpoints `restricted`, other status unreachable with the code, `ConnectionException` unreachable with its message after `str_replace` of the secret with `[secret]`. Leave `endpoints` and `missing_events` filled by US2 (return `null` and `[]` for now)
- [X] T014 [US1] Extend `app/ViewModels/Admin/BillingStatusViewModel.php`: `keys(): array` (label, set or not, mode in words, what stops working when missing: "No checkout can start without it." for the two keys, "No payment can unlock anything without it." for the signing secret); `modesDisagree(): bool`; `environmentWarning(): ?string` ("This is the production instance and it runs on test keys, so no real money is taken." / "This instance is not production and it runs on live keys, so real cards are charged."); `connection(): array` (state in words: Reachable, Refused, Unreachable, Not configured; tone; response time; reason); `catalogueWasRead(): bool` (true only when reachable); `offers(): array` from `StripeCatalogue::offers()` with each amount through `Cashier::formatAmount($amount, $currency, app()->getLocale())`; `isCatalogueAvailable(): bool` from `StripeCatalogue::isAvailable()`; `kindsWithoutPrice(): array` from `OfferKindEnum::cases()` minus the offers' kinds
- [X] T015 [US1] Add the keys block and the catalogue block to `resources/views/app/admin/billing/index.blade.php` with `x-page-section` and `x-detail-list`: each state in words beside its badge tone (FR-005, never colour alone), the warning and the disagreement as text, the offers as a table with `<th scope="col">` headers, the kinds without a price, "Stripe was not reached, so the catalogue was not read." and "The catalogue could not be read." as distinct sentences, and a "Check again" link to `route('admin.billing.index')`
- [X] T016 [US1] Run T010, T011, T012 and the Phase 2 tests

**Checkpoint**: User Story 1 works alone and is the MVP.

---

## Phase 4: User Story 2, do confirmations arrive (Priority: P2)

**Goal**: The endpoints Stripe holds for this instance, the kinds of confirmation they leave out, and the confirmations received.

**Independent Test**: With an endpoint that leaves out `invoice.paid`, the screen names it as missing.

### Tests for User Story 2

- [X] T017 [P] [US2] Add to `tests/Unit/Actions/CheckStripeConnectionTest.php`: `it_lists_the_endpoints_at_this_instance_address`; `it_names_every_kind_no_enabled_endpoint_of_this_instance_covers`; `it_treats_a_wildcard_endpoint_as_covering_every_kind`; `it_ignores_a_disabled_endpoint_when_naming_what_is_missing`; `it_does_not_count_an_endpoint_at_another_address_and_hides_its_address`; `it_reports_restricted_when_stripe_answers_403`
- [X] T018 [P] [US2] Write `tests/Unit/ViewModels/Admin/StripeConfirmationsTest.php`: `it_counts_each_kind_over_seven_and_thirty_days` (`StripeEvent::factory()` with `created_at` 2, 10 and 40 days ago; the 40 day row is not counted); `it_names_the_most_recent_confirmation_and_its_kind`; `it_says_none_arrived_without_claiming_any_was_lost`

### Implementation for User Story 2

- [X] T019 [US2] In `app/Actions/CheckStripeConnection.php` fill `endpoints` and `missing_events` from the 200 answer per [research.md](research.md) R4: an endpoint is this instance's when `data[].url === route('cashier.webhook')`; it is enabled when `status === 'enabled'`; `*` in `enabled_events` covers everything; `missing_events` is `config('cashier.webhook.events')` minus every kind covered by an enabled endpoint of this instance; an endpoint at another address keeps `url: null`
- [X] T020 [P] [US2] Create `app/ViewModels/Admin/StripeConfirmations.php` (no constructor arguments): `countsByKind(): array` from one grouped query on `stripe_events` with `type`, the count where `created_at >= now()->subDays(7)` and the count where `created_at >= now()->subDays(StripeEvent::RETENTION_IN_DAYS)`, written with `Model::query()` spelled out; `latest(): ?array` (kind and `created_at`); `isEmpty(): bool`
- [X] T021 [US2] Extend `app/ViewModels/Admin/BillingStatusViewModel.php` with `endpoints(): array`, `missingEvents(): array` (each kind with what goes wrong without it, a fixed `match` worded from `docs/STRIPE.md`: `checkout.session.completed` "A buyer who closes the tab is charged and left on the free offer.", `invoice.paid` "No renewal is ever recorded.", and one sentence for each other kind in `config/cashier.php`), `endpointsAreRestricted(): bool` and `confirmations(): StripeConfirmations`
- [X] T022 [US2] Add the confirmations block to `resources/views/app/admin/billing/index.blade.php`: the endpoints (address, enabled in words), the missing kinds with their consequence, the restricted key sentence, "Stripe holds no enabled endpoint for this instance." when none, then the counts table (kind, last 7 days, last 30 days) with `trans_choice()` for counts, the most recent confirmation through `x-timestamp`, and "No confirmation arrived in the last thirty days." when empty
- [X] T023 [US2] Run T017, T018 and the view model test

**Checkpoint**: User Stories 1 and 2 work together.

---

## Phase 5: User Story 4, what billing brings in (Priority: P2)

**Goal**: Access, subscriptions, payments of this and last month, refunds and disputes, all from the database.

**Independent Test**: Two monthly subscribers, one yearly, one purchase made once, one grant and three payments this month show those counts and the month's total.

### Tests for User Story 4

- [X] T024 [P] [US4] Write `tests/Unit/ViewModels/Admin/BillingFiguresTest.php`: `it_counts_access_in_the_order_account_access_kind_uses` (an account with a paid order and a grant counts once, as a purchase; build accounts with factories and check each against `$account->accessKind()`); `it_counts_subscribers_by_the_kind_of_their_latest_payment`; `it_counts_a_subscription_with_no_payment_as_kind_not_known`; `it_counts_the_accounts_holding_no_full_access`; `it_counts_active_cancelled_but_running_and_past_due_subscriptions`; `it_totals_this_month_and_last_month_per_currency_and_kind` (Michael pays in `eur`, Dwight in `usd`; amounts in the smallest unit; `assertSame` on the sums and the tax); `it_leaves_out_payments_older_than_last_month`; `it_counts_full_refunds_and_disputes_of_the_last_thirty_days` (`StripeEvent` rows of `charge.refunded` and `charge.dispute.created`, one 31 days old not counted); `it_says_no_payment_was_ever_recorded`. Use `$this->travelTo()` to fix the month
- [X] T025 [P] [US4] Add `it_shows_the_figures_when_stripe_is_unreachable` to `tests/Unit/ViewModels/Admin/BillingStatusViewModelTest.php` (an unreachable connection, figures still present)

### Implementation for User Story 4

- [X] T026 [US4] Create `app/ViewModels/Admin/BillingFigures.php` (no constructor arguments), every query with `Model::query()` spelled out: `access(): array{lifetime: int, monthly: int, yearly: int, kind_not_known: int, granted: int, none: int}` per [research.md](research.md) R8, using `Account::query()` with `whereHas('orders', paid)`, then `whereNotNull('access_granted_at')`, then `whereHas('subscriptions', fn ($query) => $query->where(fn ($inner) => $inner->active()->orWhere->onTrial()->orWhere->onGracePeriod()))` in that precedence; the kind of a subscription from the `kind` of its latest `payments` row by `stripe_subscription_id`; `subscriptions(): array{active: int, cancelled_running: int, past_due: int}` from Cashier's `active()->notOnGracePeriod()`, `onGracePeriod()` and `pastDue()` scopes; `payments(): array` grouped by month (current and previous calendar month of `billed_at` in `config('app.timezone')`), `currency` and `kind`, with `count(*)`, `sum(total)`, `sum(tax)`; `refunds(): int` and `disputes(): int` from `stripe_events` of type `charge.refunded` and `charge.dispute.created` in the last 30 days; `hasAnyPayment(): bool`
- [X] T027 [US4] Extend `app/ViewModels/Admin/BillingStatusViewModel.php` with `figures(): BillingFigures` and the wording: month names through the date helpers the admin space already uses, amounts through `Cashier::formatAmount()`, offer kinds through `OfferKindEnum`'s label if it has one (check the enum), "kind not known" otherwise
- [X] T028 [US4] Add the figures block to `resources/views/app/admin/billing/index.blade.php` under the health blocks: access counts, subscription states, one table per month with headers (kind, currency, payments, amount paid, tax within it), the sentence "Amounts are shown as invoiced. Refunds are not subtracted.", the refund and dispute counts with "Partial refunds are not counted.", and "No payment has been recorded yet." when there is none
- [X] T029 [US4] Run T024, T025 and the view model test

**Checkpoint**: User Stories 1, 2 and 4 work together.

---

## Phase 6: User Story 3, read the catalogue again (Priority: P3)

**Goal**: An administrator reads the catalogue again without waiting the hour, and the administrator log says so.

**Independent Test**: A newly tagged price appears on the screen right after pressing the button.

### Tests for User Story 3

- [X] T030 [P] [US3] Add to `tests/Unit/Helpers/StripeCatalogueTest.php`: `it_records_when_it_read_the_catalogue` (`$this->travelTo()`, `offers()`, `assertEquals` on `readAt()`); `it_forgets_when_it_read_the_catalogue_with_the_catalogue`; `it_records_no_read_time_when_the_catalogue_cannot_be_read`
- [X] T031 [P] [US3] Write `tests/Unit/Actions/ReloadStripeCatalogueTest.php`: `it_reads_the_catalogue_again_and_logs_who_did_it` (`Queue::fake()`, `Queue::assertPushedOn('low', LogAdministratorAction::class)` with `AdministratorActionEnum::StripeCatalogueReloaded`); `it_reports_that_the_catalogue_could_not_be_read` (`fakeStripe()` with `failOn('prices*')`, returns false)
- [X] T032 [P] [US3] Write `tests/Feature/Controllers/App/Admin/StripeCatalogueReloadControllerTest.php`: `it_redirects_to_the_screen_with_the_catalogue_read_again` and `it_redirects_with_the_catalogue_could_not_be_read` (`assertRedirect(route('admin.billing.index'))`, `assertSessionHas('status', ...)`)
- [X] T033 [P] [US3] Add `it_says_when_the_instance_last_read_the_catalogue` to `tests/Unit/ViewModels/Admin/BillingStatusViewModelTest.php`

### Implementation for User Story 3

- [X] T034 [US3] In `app/Helpers/StripeCatalogue.php` add `private const string READ_AT_CACHE_KEY = 'stripe_offers_read_at';`, put `now()->toImmutable()` under it next to the catalogue in `read()` with the same lifetime, forget it in `forget()`, and add `public static function readAt(): ?CarbonImmutable`
- [X] T035 [P] [US3] Add `case StripeCatalogueReloaded = 'stripe_catalogue_reloaded';` to `app/Enums/AdministratorActionEnum.php`, and its label wherever the other cases are worded for the Record screen (grep `SearchIndexRebuildStarted`)
- [X] T036 [US3] Create `app/Actions/ReloadStripeCatalogue.php` in the shape of `StartSearchIndexRebuild`: class docblock of one sentence; constructor `private readonly ?User $administrator = null`; `execute(): bool` calls `StripeCatalogue::forget()`, then `StripeCatalogue::isAvailable()`, then dispatches `LogAdministratorAction` on `low` with `AdministratorActionEnum::StripeCatalogueReloaded` and returns the availability
- [X] T037 [US3] Create `app/Http/Controllers/App/Admin/StripeCatalogueReloadController.php` with `create(Request $request): RedirectResponse` running the action and redirecting to `admin.billing.index` with `status` "The catalogue was read again." or "The catalogue could not be read."
- [X] T038 [US3] Add `Route::post('admin/billing/catalogue-reload', [StripeCatalogueReloadController::class, 'create'])->name('admin.billing.catalogueReload.create');` to the `administrator` group of `routes/administrator.php`, outside the `password.confirm` group (R7), and add the URL to the POST cases of `AdminAccessTest.php` if it holds any
- [X] T039 [US3] Extend `app/ViewModels/Admin/BillingStatusViewModel.php` with `catalogueReadAt(): ?CarbonImmutable` and add to the catalogue block of `resources/views/app/admin/billing/index.blade.php` the read time through `x-timestamp` and a form posting to `admin.billing.catalogueReload.create` with an `x-button` "Read the catalogue again", shown only when the connection is reachable
- [X] T040 [US3] Run T030 to T033

**Checkpoint**: Every story works.

---

## Phase 7: Polish and cross-cutting

- [X] T041 Run `php artisan monica:localize`, translate what it reports in every configured locale through subagents on `sonnet` as the translations skill requires, then `php artisan monica:localize --check`
- [X] T042 [P] Add one entry to `docs/FEATURES.md` per the feature-documentator skill
- [X] T043 Run the whole suite with `PAO_DISABLE=1 php artisan test --parallel`, then `vendor/bin/phpstan analyse --no-progress` and `vendor/bin/pint --dirty --format agent`
- [X] T044 [P] Run the `accessibility-reviewer` and the `rules-reviewer` agents over the change and fix what they confirm (scroll region extracted into `x-scroll-region`, `x-form`, `x-notice`, `StripeEvents` naming, failed reload flashed as `error`)
- [ ] T045 Follow the "By hand" steps of [quickstart.md](quickstart.md) against a Stripe test account, and record in the pull request whether the 403 shape for a restricted key and the endpoint fields matched [contracts/http.md](contracts/http.md)
- [ ] T046 In the pull request description, name the two deviations of the plan's Complexity Tracking: no API or MCP surface (Principle X), and FR-010 unbounded on a cold catalogue cache

---

## Dependencies and execution order

- **Phase 1** before everything. **Phase 2** blocks every story.
- **US1** (Phase 3) creates `CheckStripeConnection`, which US2 extends: US2 after US1.
- **US4** (Phase 5) touches only `BillingFigures` and its block: it can run right after Phase 2, in parallel with US1.
- **US3** (Phase 6) needs the catalogue block of US1.
- **Polish** after the stories you ship.

Within a story: tests first and failing, then the action or object, then the view model, then the view.

## Parallel examples

```text
After Phase 2:
  T010, T011, T012 (US1 tests)    alongside    T024, T025 (US4 tests)
  T013 CheckStripeConnection      alongside    T026 BillingFigures

User Story 2:
  T017 (action tests)  alongside  T018 (StripeConfirmations tests)  alongside  T020 (StripeConfirmations)

User Story 3:
  T030, T031, T032, T033 together, then T034 alongside T035
```

The view file and `BillingStatusViewModel.php` are shared by every story: tasks that edit them never run in parallel with each other.

## Implementation strategy

1. **MVP**: Phases 1, 2 and 3. The screen answers the question that costs money: can billing take a payment. Stop, check it by hand, ship if needed.
2. Add US2, the silent failure of a missing confirmation.
3. Add US4, the figures.
4. Add US3, the reload button.
5. Polish once, at the end of what ships.
