# Tasks: Billing screen redesign

**Input**: [spec.md](spec.md), [plan.md](plan.md), [research.md](research.md), [quickstart.md](quickstart.md)

**Tests**: required by constitution principle III and `.claude/rules/testing.md`. The view model owns what the screen shows; `OfferListTest` owns the component's contract; the controller test proves each state renders.

## Phase 1: Setup

- [X] T001 Read `.claude/skills/translations/SKILL.md`, `.claude/skills/view-models/SKILL.md`, `.claude/skills/views/SKILL.md`, `.claude/skills/components/SKILL.md`, `.claude/rules/components.md`, `.claude/rules/testing.md` and `.claude/rules/translations.md` before touching `app/ViewModels/Settings/Account/Billing/BillingViewModel.php`
- [X] T002 Save every locale's current values with `git show main:lang/<locale>.json` into `$CLAUDE_JOB_DIR/tmp/lang-before/` so renamed keys can be retranslated from them (research R1)

## Phase 2: Foundational, "offer" becomes "plan" (pull request 1, blocks every story)

- [X] T003 Add a "plan" row to the glossary in `.claude/rules/translations.md`, one word per locale column, checked against each locale's existing "subscription" wording so the two stay distinct
- [X] T004 [P] Rewrite the pricing "offer" to "plan" in `app/Enums/InstanceSettingEnum.php:266-267` and `app/Enums/UserActionEnum.php:212`
- [X] T005 [P] Rewrite `app/Notifications/AccessEnded.php:36-51` ("back on the free plan", "See the plans")
- [X] T006 [P] Rewrite the `trans_choice()` phrases in `app/Policies/VaultPolicy.php:24` and `app/Policies/ContactPolicy.php:25` ("The free plan holds :count …")
- [X] T007 [P] Rewrite `app/Actions/UpgradeSubscription.php:61` and wrap `app/Actions/StartCheckout.php:60` in `__()` as "This account cannot take up that plan."
- [X] T008 [P] Rewrite `app/Http/Controllers/App/Settings/Account/Billing/CheckoutController.php:25` and `CheckoutReturnController.php:30`; keep the error bag key `offer`
- [X] T009 [P] Rewrite `app/ViewModels/Settings/SettingsViewModel.php:147`, `app/ViewModels/Settings/Account/Billing/BillingViewModel.php:103,167,170` and `app/ViewModels/Admin/AccountViewModel.php:266`
- [X] T010 [P] Rewrite `resources/views/app/settings/account/billing/index.blade.php:8,12,24,51,93,162`, `resources/views/components/offer-list.blade.php:20,27` (`Switch to :plan?`, `Switch to this plan`), `resources/views/components/paywall-prompt.blade.php:7,11` and `resources/views/app/admin/accounts/show.blade.php:153`
- [X] T011 [P] Rewrite the pricing "offer" in the 11 guide pages under `docs/portal/en/` (`2-start-here/3`, `3-people/2,6`, `4-relationships-and-life-events/2`, `6-vaults/1,2`, `12-troubleshooting/5`, `13-reference/2,4,5`) and the prose of `docs/STRIPE.md`, leaving `MONICA_OFFER_TAG` and the tag name
- [X] T012 [P] Amend `.specify/memory/constitution.md:267,269` to "free plan" and "no per plan features", and bump the version line to 2.4.2 with today's date
- [X] T013 Run `php artisan monica:localize`, then write every reported missing value in every locale from the saved values of T002, using the T003 glossary word and `.claude/rules/translations.md` typography
- [X] T014 Update the copy asserted in the 13 test files that `grep -rniE "free offer|the offers|this offer" tests` finds (e.g. `tests/Feature/Views/Components/PaywallPromptTest.php`, `tests/Unit/Notifications/AccessEndedTest.php`), keeping API field and error bag assertions
- [X] T015 Run the quickstart grep, `php artisan monica:localize --check`, the whole suite, PHPStan and Pint; open pull request 1

## Phase 3: User Story 1, a free account sees what full access costs and takes it (P1) MVP

**Independent test**: a free account sees three prices in the chosen currency, the yearly saving, and each button posts to `settings.checkout.store` for its price.

- [X] T016 [US1] Test `yearlySaving()` (12 × monthly − yearly, null when a price is missing or the saving is not positive) and the `amount` key of `offers()` in `tests/Unit/ViewModels/Settings/Account/Billing/BillingViewModelTest.php`
- [X] T017 [US1] Implement `yearlySaving(): ?string` and add `amount` (formatted amount alone) to the `offers()` shape and its PHPDoc in `app/ViewModels/Settings/Account/Billing/BillingViewModel.php`
- [X] T018 [US1] Rewrite `resources/views/components/offer-list.blade.php` into the subscription card (monthly and yearly side by side, "Save :amount" when a saving prop is given) and the `bg-primary text-on-primary` lifetime card, keeping the switch dialog for `upgrade`
- [X] T019 [US1] Rewrite `tests/Feature/Views/Components/OfferListTest.php` for the checkout form actions, the switch dialog, the saving line and an empty list, with no class assertions
- [X] T020 [US1] Rebuild `resources/views/app/settings/account/billing/index.blade.php`: page header, notices as today, plans headline (`h2`, `id="plans"`), currency toggle form of submit buttons with `aria-pressed` and EUR/USD flags (only with two or more currencies), `x-offer-list`, tax line and inline Stripe mark with `fill="currentColor"`
- [X] T021 [P] [US1] Create `resources/views/app/settings/account/billing/_benefits.blade.php` ("What changes" or "What you have"; storage from `monica.paid_storage_limit_in_bytes`) and include it unless lifetime or granted
- [X] T022 [P] [US1] Create `resources/views/app/settings/account/billing/_questions.blade.php` with the nine `<details>` from the design, first `open`, "+" `aria-hidden`, storage figure from config, and the closing "long run" section with the source code link
- [X] T023 [US1] Replace the render tests in `tests/Feature/Controllers/App/Settings/Account/Billing/BillingControllerTest.php` with one per state of FR-001 asserting the copy that identifies it

## Phase 4: User Story 2, a paying account sees where it stands (P1)

**Independent test**: a yearly subscriber, a lifetime holder and an account whose subscription ended each show their own badge, title, dated line and actions.

- [X] T024 [US2] Test `usage()` (contacts, vaults, storage; text, and percentage null without a ceiling) in `tests/Unit/ViewModels/Settings/Account/Billing/BillingViewModelTest.php`
- [X] T025 [US2] Implement `usage(): list<array{label: string, value: string, note: string, percent: ?int}>` in `app/ViewModels/Settings/Account/Billing/BillingViewModel.php`
- [X] T026 [US2] Create `resources/views/app/settings/account/billing/_summary.blade.php`: badge, title, dated line, "Cancel renewal" through `x-modal.warning`, "Keep the subscription", "See it on Stripe", "Choose a plan" linking `#plans`, and the usage row with `aria-hidden` bars; include it for every state but never-paid free

## Phase 5: User Story 3, the reader reaches what was paid (P2)

**Independent test**: an account with four payments shows the three newest and "View invoices" opens `settings.invoices.index`.

- [X] T027 [US3] Test `recentPayments()` returns the three newest with their label in `tests/Unit/ViewModels/Settings/Account/Billing/BillingViewModelTest.php`
- [X] T028 [US3] Implement `recentPayments()` reusing the labelling of `InvoicesViewModel::paymentLabel()` in `app/ViewModels/Settings/Account/Billing/BillingViewModel.php`
- [X] T029 [US3] Create `resources/views/app/settings/account/billing/_record.blade.php` ("On record", rows with `x-timestamp`, "View invoices") and include it when there is a payment
- [X] T030 [US3] Replace `@include('app.settings.account.billing._tabs')` in `resources/views/app/settings/account/billing/invoices.blade.php` with an `x-back-link` to `settings.billing.index`, and ask before deleting `_tabs.blade.php`

## Phase 6: Polish

- [X] T031 Run `php artisan monica:localize` and translate every new phrase in every locale, then `--check`
- [X] T032 Screenshot every state of quickstart in light and dark at 1280px and 375px with the `take-screenshot` skill, and run the accessibility pass
- [ ] T033 Run the billing tests, the whole suite, PHPStan and Pint; open pull request 2 based on pull request 1

## Dependencies

- T002 before T013. Phase 2 (T003 to T015) before every story: new copy says "plan".
- US1 before US2 and US3: they include partials into the rebuilt `index.blade.php` (T020).
- US2 and US3 are independent of each other. T031 to T033 come last.

## Parallel examples

- Phase 2: T004 to T012 touch different files and run together; T013 waits for all of them.
- US1: T021 and T022 run together once T020 exists.

## Implementation strategy

Ship pull request 1 alone. Then US1 as the MVP (the free screen is what most readers see), US2, US3, polish, as pull request 2.
