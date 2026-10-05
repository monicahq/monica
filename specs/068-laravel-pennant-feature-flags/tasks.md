# Tasks: Feature flags

**Input**: [spec.md](spec.md), [plan.md](plan.md), [research.md](research.md), [contracts/flags.md](contracts/flags.md)

**Tests**: included, because constitution principle III makes them a merge condition. Follow
`.claude/rules/testing.md`; call `Feature::flushCache()` between acting and asserting (R8). A test that
needs the test flag discovers it with `Feature::discover('Tests\Fixtures\Features', base_path('tests/Fixtures/Features'))`.

## Phase 1: Setup

- [X] T001 Require `laravel/pennant:^1.26` in composer.json and composer.lock, and check `barryvdh/laravel-debugbar` and `sentry/sentry-laravel` still resolve (R1)
- [X] T002 Publish Pennant's config and migration with `php artisan vendor:publish --provider="Laravel\Pennant\PennantServiceProvider"`, keeping config/pennant.php on the `database` store and database/migrations/*_create_features_table.php exactly as shipped
- [X] T003 [P] Create app/Features/.gitkeep so `Feature::discover()` finds the folder

## Phase 2: Foundational

- [X] T004 Add Pennant's `HasFeatures` trait to app/Models/Account.php, on its own `use` line (R2)
- [X] T005 Add `configureFlags()` calling `Feature::discover()` to app/Providers/AppServiceProvider.php and call it from `boot()` (R4)
- [X] T006 [P] Create tests/Fixtures/Features/ExampleCapability.php: `#[Name('example-capability')]`, `resolve(Account $account): bool` returning `Feature::for(null)->active(self::class)` (R3)
- [X] T007 [P] Test in tests/Unit/Actions/DestroyAccountTest.php that destroying an account leaves no `features` row for its scope, then add `Feature::for($this->account)->forget(Feature::stored())` before `destroy()` in app/Actions/DestroyAccount.php (R7)

## Phase 3: User Story 1, ship a capability switched off (P1)

**Goal**: a capability behind a flag is unreachable until the flag is on. **Independent test**: T008 passes.

- [X] T008 [US1] Write tests/Feature/EnsureFlagIsOnTest.php with a test route behind `auth` and `flag:example-capability`: 404 for Dwight with nothing stored, 200 for Jim once his account has it on, 200 when `Feature::define(ExampleCapability::class, true)` forces it, and the same 404 and 200 on a test route behind `auth:sanctum` (FR-001, FR-002, FR-003, FR-006)
- [X] T009 [US1] Create app/Http/Middleware/EnsureFlagIsOn.php: `handle(Request $request, Closure $next, string $flag)` aborting 404 unless `$request->user()->account->features()->active($flag)`
- [X] T010 [US1] Alias the middleware as `flag` in bootstrap/app.php, beside `administrator`

## Phase 4: User Story 2, the administrator switches a flag (P2)

**Goal**: switch one account or every account, from the screen or the command, logged each time.
**Independent test**: T011 to T030 pass, then the quickstart table.

- [X] T011 [P] [US2] Add `FlagActivated`, `FlagDeactivated`, `FlagActivatedForEveryAccount`, `FlagDeactivatedForEveryAccount` with the descriptions in contracts/flags.md to app/Enums/AdministratorActionEnum.php, and cover them in tests/Unit/Enums/AdministratorActionEnumTest.php
- [X] T012 [P] [US2] Write tests/Unit/Actions/ActivateFlagTest.php: on for Pam and not Jim, same answer with and without Pam signed in (FR-002), logs `FlagActivated` with the account and the administrator, logs as the console with no administrator, an undeclared flag throws `ModelNotFoundException`
- [X] T013 [US2] Create app/Actions/ActivateFlag.php: `__construct(private readonly Account $account, private readonly string $flag, private readonly ?User $administrator = null)`, `execute(): void` refusing a name outside `Feature::defined()`, activating for the account and dispatching `LogAdministratorAction` on the `low` queue with `['flag' => ..., 'name' => ...]`
- [X] T014 [P] [US2] Write tests/Unit/Actions/DeactivateFlagTest.php mirroring T012, plus a contact Pam created beforehand that still exists once the flag is off and on again (FR-004, SC-003)
- [X] T015 [US2] Create app/Actions/DeactivateFlag.php mirroring T013
- [X] T016 [P] [US2] Write tests/Unit/Actions/ActivateFlagForEveryAccountTest.php: an account already resolved off turns on, an account created afterwards is on, a later `DeactivateFlag` for Pam overrides it, logs `FlagActivatedForEveryAccount` with no account
- [X] T017 [US2] Create app/Actions/ActivateFlagForEveryAccount.php: refuse an undeclared name, `Feature::for(null)->activate()` then `Feature::activateForEveryone()`, log (R3)
- [X] T018 [P] [US2] Write tests/Unit/Actions/DeactivateFlagForEveryAccountTest.php mirroring T016, plus contacts created beforehand that still exist once the flag is off and on again (FR-004, SC-003)
- [X] T019 [US2] Create app/Actions/DeactivateFlagForEveryAccount.php mirroring T017
- [X] T020 [P] [US2] Write tests/Unit/Console/Commands/ListFlagsCommandTest.php: says nothing is declared and exits 0; with the test flag, prints its name and its state for every account
- [X] T021 [US2] Create app/Console/Commands/ListFlagsCommand.php with `#[Signature('monica:list-flags')]` per contracts/flags.md
- [X] T022 [P] [US2] Write tests/Unit/Console/Commands/SwitchFlagCommandTest.php: each success path, `--email` ignoring case, and exit 1 for each refusal listed in contracts/flags.md
- [X] T023 [US2] Create app/Console/Commands/SwitchFlagCommand.php with the signature in contracts/flags.md, delegating to the four actions with no administrator
- [X] T024 [US2] Add the five routes in contracts/flags.md to routes/administrator.php, the two "every account" ones inside the `password.confirm` group
- [X] T025 [P] [US2] Write tests/Unit/ViewModels/Admin/FlagsViewModelTest.php, then create app/ViewModels/Admin/FlagsViewModel.php listing each declared flag, its state for every account, and its two URLs
- [X] T026 [P] [US2] Add `flags()` to app/ViewModels/Admin/AccountViewModel.php (each declared flag, its state for this account, its two URLs) and cover it in tests/Unit/ViewModels/Admin/AccountViewModelTest.php
- [X] T027 [US2] Create app/Http/Controllers/App/Admin/FlagController.php (`index`), FlagActivationController.php and AccountFlagActivationController.php (`create`, `destroy`), redirecting with a `status` flash like UserActivationController
- [X] T028 [US2] Create resources/views/app/admin/flags/index.blade.php (empty state when nothing is declared, `x-modal.warning` before each "every account" switch; a switch for one account submits directly, since it deletes nothing and its reverse sits beside it) and add a flags panel to resources/views/app/admin/accounts/show.blade.php, drawn only when a flag is declared; check resources/views/components first
- [X] T029 [US2] Add `Flags` between `Configuration` and `Administrators` in resources/views/layouts/admin.blade.php, and update tests/Feature/Views/Layouts/AdminLayoutTest.php if it names the entries
- [X] T030 [US2] Write tests/Feature/Controllers/App/Admin/FlagControllerTest.php, FlagActivationControllerTest.php (password confirmation required) and AccountFlagActivationControllerTest.php (redirect and flash, 404 for an undeclared flag), and add `'the flags' => ['admin/flags']` to AdminAccessTest.php

## Phase 5: User Story 3, retire a flag (P3)

**Goal**: a removed flag leaves no row, and the next developer knows how. **Independent test**: T031 passes.

- [X] T031 [US3] Test in tests/Unit/Providers/AppServiceProviderTest.php that `pennant:purge --except-registered` deletes the rows of an undeclared flag and keeps those of the discovered test flag (FR-011)
- [X] T032 [US3] Write .claude/rules/flags.md for `app/Features/**` and list it in .claude/rules/index.md: one class per flag with `#[Name]`, ask through the account and never the default scope (R2), never read the plan or the deployment (FR-007), and delete the class and run `pennant:purge` in the pull request that releases the capability (FR-008)

## Phase 6: Polish

- [X] T033 Run `php artisan monica:localize` and translate every reported string in all lang/*.json locales, following .claude/rules/translations.md
- [X] T034 Run `php artisan test --compact`, `vendor/bin/phpstan analyse --no-progress` and `vendor/bin/pint --dirty --format agent`, all clean
- [ ] T035 Check resources/views/app/admin/flags/index.blade.php and the flags panel in resources/views/app/admin/accounts/show.blade.php against the constitution's screen gate: keyboard alone, screen reader, nothing carried by colour alone, focus always visible, readable at 200% zoom, desktop, tablet and phone
- [ ] T036 Walk specs/068-laravel-pennant-feature-flags/quickstart.md, then take the admin screenshots with the take-screenshot skill for the pull request

## Dependencies

- Phase 1, then Phase 2, then any story. US1, US2 and US3 depend on nothing but Phase 2.
- Inside US2: T011 before T013 to T019; actions before T023 and T027; T024 before T025 to T030.
- Each test task is written and seen failing before the task that makes it pass.
- Parallel: T003, T006, T007; in US2, T011, T012, T014, T016, T018, T020, T022, T025 and T026.

## Phase 7: The marketplace flag (FR-012, added 2026-10-05)

- [X] T037 Create app/Features/Marketplace.php (`#[Name('marketplace')]`) and remove app/Features/.gitkeep
- [X] T038 Create app/Helpers/FlagState.php (account answer, or every account for a visitor) with tests/Unit/Helpers/FlagStateTest.php, and make app/Http/Middleware/EnsureFlagIsOn.php ask it
- [X] T039 Put `marketplace.index` behind `flag:marketplace` in routes/web.php, and prove it in tests/Feature/Controllers/App/Marketplace/MarketplaceControllerTest.php
- [X] T040 Hide the top bar link (resources/views/components/bar-links.blade.php) and the publish panels of the template and section share screens, and name the builder's way out after the export, through `marketplaceIsOn()` on their view models
- [X] T041 Translate the one new share sentence in all lang/*.json locales

## Strategy

US1 alone is the MVP: a flag can gate a route and be forced in tests. US2 makes it operable without
the database, US3 proves cleanup. One pull request carries all three with the marketplace as the
first flag.
