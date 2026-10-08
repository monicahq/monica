# Tasks: Closing Registration on an Instance

**Input**: [plan.md](./plan.md), [spec.md](./spec.md), [research.md](./research.md), [contracts/api.md](./contracts/api.md), [quickstart.md](./quickstart.md)

**Tests**: included, because Principle III makes them a delivery condition. Test rules: `.claude/rules/testing.md`.

## Phase 1: Foundational

- [X] T001 Add `registration_enabled` as `(bool) env('MONICA_REGISTRATION_ENABLED', true)` with a block comment, after the link by email settings in `config/monica.php`, and `MONICA_REGISTRATION_ENABLED=true` after `MONICA_REMEMBER_DURATION_DAYS` in `.env.example`
- [X] T002 Create `app/Http/Middleware/EnsureRegistrationIsEnabled.php` copying `EnsureMagicLinkIsEnabled` (abort 404 when `config('monica.registration_enabled')` is false) and alias it `registration` in `bootstrap/app.php`

## Phase 2: User Story 1, the operator closes registration (P1, MVP)

- [X] T003 [P] [US1] In `tests/Feature/Controllers/App/Auth/RegistrationControllerTest.php`, prove `auth.register.new` and `auth.register.create` answer 404 and create no user while `monica.registration_enabled` is false
- [X] T004 [P] [US1] In `tests/Feature/Controllers/App/Auth/RegistrationPasskeyControllerTest.php`, prove `auth.registerPasskey.create` answers 404 and creates no user while disabled
- [X] T005 [P] [US1] In `tests/Feature/Controllers/Api/Auth/RegistrationControllerTest.php`, prove `POST /api/v1/register` answers 404 with a valid body and creates no user while disabled
- [X] T006 [P] [US1] In `tests/Unit/Actions/SignInWithProviderTest.php`, prove an unknown provider identity is refused with "This instance does not accept new accounts." and no user, and a connected one still signs in, while disabled
- [X] T007 [US1] Add `->middleware('registration')` to `auth.register.new`, `auth.register.create` and `auth.registerPasskey.create` in `routes/auth.php` (not `auth.registerPasskey.new`), and to `api.v1.register` in `routes/api.php`
- [X] T008 [US1] Add `refuseWhenRegistrationIsDisabled()` to `app/Actions/SignInWithProvider.php`, called in `resolve()` after the connected account lookup and before `refuseWithoutEmail()`, throwing `ValidationException::withMessages(['provider' => __('This instance does not accept new accounts.')])`

## Phase 3: User Story 2, the screens stop offering an account (P2)

- [X] T009 [P] [US2] Add `registrationIsEnabled()` to `app/ViewModels/Auth/LoginViewModel.php` beside `magicLinkIsEnabled()`, test it in `tests/Unit/ViewModels/Auth/LoginViewModelTest.php`, and wrap the "Create an account" footnote in `resources/views/app/auth/login.blade.php`
- [X] T010 [P] [US2] Add `registrationIsEnabled()` to `app/ViewModels/Marketplace/MarketplaceViewModel.php`, test it in `tests/Unit/ViewModels/Marketplace/MarketplaceViewModelTest.php`, and render the visitor's "Create an account" button in `resources/views/app/marketplace/index.blade.php` only when it is true
- [X] T011 [P] [US2] Add `registrationIsEnabled()` to `app/View/Components/MarketplaceLayout.php`, use it around the header button in `resources/views/layouts/marketplace.blade.php`, and create `tests/Feature/Views/Layouts/MarketplaceLayoutTest.php` proving the link to `auth.register.new` is present when enabled and absent when disabled
- [X] T012 [P] [US2] Add `registrationIsEnabled()` to `app/ViewModels/Docs/DocsViewModel.php`, test it in `tests/Unit/ViewModels/Docs/DocsViewModelTest.php`, and point "Get started with Monica" in `resources/views/app/docs/index.blade.php` at `auth.login.new` when it is false

## Phase 4: User Story 3, an administrator changes it without redeploying (P2)

- [X] T013 [US3] Add `case RegistrationEnabled = 'registration_enabled'` to `app/Enums/InstanceSettingEnum.php`: group `SigningIn`, kind `Boolean`, description "Whether somebody may create an account on this instance.", warning "Anybody who reaches this instance can create an account until it is switched off again.", `warnsWhen()` `switchedOn`; cover all four in `tests/Unit/Enums/InstanceSettingEnumTest.php`
- [X] T014 [US3] Teach `takesSomethingAway()` in `resources/views/app/admin/configuration/index.blade.php` the `switchedOn` mode (current `'0'`, next `'1'`) without changing the three existing modes, and prove in `tests/Feature/Views/App/Admin/ConfigurationTest.php` that the `registration_enabled` form carries that mode

## Phase 5: Polish

- [X] T015 [P] Say in the `register-store` body of `resources/docs/api/02-authentication.php` that the endpoint answers 404 while registration is disabled
- [X] T016 Run `php artisan monica:localize`, translate the three new strings in every configured locale following `.claude/rules/translations.md`, and confirm `php artisan monica:localize --check` passes
- [X] T017 [P] Replace "registration cannot be closed (no switch exists)" and its acceptance line under `hosting.sign-in-options` in `docs/portal/ROADMAP.md` with the switch and `MONICA_REGISTRATION_ENABLED`
- [X] T018 [P] Add the feature to `docs/FEATURES.md` through the `feature-documentator` skill
- [ ] T019 Run the whole suite, PHPStan and Pint as listed in `quickstart.md`, then walk its manual steps

## Dependencies

- T001 before T002, T009 to T014. T002 before T007.
- US1: T003 to T006 before T007 and T008. US2 and US3 depend only on Phase 1 and on nothing in each other.
- T013 before T014; T008 and T013 before T016; everything before T019.
- MVP: Phases 1 and 2 close every route; Phase 3 removes the dead ends.
