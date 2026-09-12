---
description: "Task list for sign in with an outside provider"
---

# Tasks: Sign in with an outside provider

**Input**: Design documents from `/specs/027-socialite-login/`

**Tests**: required, not optional. Principle III is non-negotiable and `.claude/rules/testing.md` governs every test file below.

**Organization**: grouped by user story. All three are P1.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: different file, no dependency on an unfinished task
- Paths are repository relative

---

## Phase 1: Setup

- [X] T001 Add `laravel/socialite` to `require` in `composer.json` and run `composer update laravel/socialite`
- [X] T002 Add `socialiteproviders/microsoft` to `require-dev` and a matching `suggest` entry in `composer.json`
- [X] T003 Register the Microsoft driver's event listener in `app/Providers/AppServiceProvider.php` only when `monica.sso_installed` names it, so an absent package is never referenced
- [X] T004 [P] Add `github`, `microsoft` and `facebook` entries, each with `client_id`, `client_secret` and `redirect`, to `config/services.php`
- [X] T005 [P] Add the nine matching keys, commented, to `.env.example`

---

## Phase 2: Foundational (blocks every story)

- [X] T006 Add `sso_id` with a comment and a unique index across `sso_provider` and `sso_id` to `database/migrations/0001_01_01_000000_create_users_table.php`
- [X] T007 [P] Create `app/Enums/SsoProviderEnum.php` with `GitHub`, `Google`, `Microsoft` and `Facebook`, backed by the Socialite driver name in lower case, each carrying its label and whether it needs a package
- [X] T008 Add `sso_installed`, built from a `class_exists` per packaged provider, to `config/monica.php`. Credentials stay in `config/services.php` and the helper reads them there
- [X] T009 Create `app/Helpers/SsoProviderAvailability.php` answering which providers are available, beside `PasskeyAvailability`
- [X] T010 [P] Add `signsInWith()` to `app/Models/User.php`, add `sso_id` to the property docblock and to `$fillable`, and cast `sso_provider` to `SsoProviderEnum` in `casts()`
- [X] T011 [P] Add `sso_id` to `UserFactory::singleSignOn()` in `database/factories/UserFactory.php`. Its `sso_provider` stays `google`, which the enum now names
- [X] T012 Compare against the enum rather than the string at `tests/Unit/Actions/CreateUserTest.php` line 65, the one assertion the cast breaks. Every other `'google'` in the suite is a written value the cast accepts
- [X] T013 [P] Create `tests/Unit/Enums/SsoProviderEnumTest.php` covering the driver name, the label and the package flag of every case
- [X] T014 Create `tests/Unit/Helpers/SsoProviderAvailabilityTest.php` covering both package states and both credential states
- [X] T015 [P] Add the `signsInWith()` cases to `tests/Unit/Models/UserTest.php`

**Checkpoint**: the enum, the column and the availability answer exist.

---

## Phase 3: User Story 1 - Sign in with a provider (P1) 🎯 MVP

**Goal**: somebody chooses a provider, confirms there, and lands signed in, with an account created when they have none.

**Independent Test**: configure GitHub, choose it on the sign in screen, confirm, arrive signed in with a full account. Repeat and reach the same account.

- [X] T016 [P] [US1] Create `tests/Unit/Actions/SignInWithProviderTest.php`: the pair matches, the address is unknown, the address belongs to a user who signs in another way, the address is unverified, the user is deactivated, the address changed at the provider since last time
- [X] T017 [P] [US1] Create `tests/Unit/Actions/CreateAccountFromProviderTest.php`: the account, the user, the first vault, the default value lists, the default relationship vocabulary, the address already verified, no verification email, no password
- [X] T018 [US1] Create `tests/Feature/Controllers/App/Auth/SsoControllerTest.php` covering every row of the callback table in `contracts/sign-in-with-a-provider.md`, with Socialite faked
- [X] T019 [US1] Add a promoted `ssoId` parameter beside the existing `ssoProvider` in `app/Actions/CreateUser.php`
- [X] T020 [US1] Add promoted `ssoProvider` and `ssoId` parameters to `app/Actions/CreateAccount.php`, passing them to `CreateUser` and defaulting to null so registration is unchanged
- [X] T021 [US1] Add the new parameters to `tests/Unit/Actions/CreateUserTest.php` and `tests/Unit/Actions/CreateAccountTest.php`
- [X] T022 [US1] Create `app/Actions/CreateAccountFromProvider.php` delegating to `CreateAccount`, marking `email_verified_at`, and suppressing the verification email
- [X] T023 [US1] Create `app/Actions/SignInWithProvider.php`: match on the pair, refuse the cases above, stamp `last_login_at`, notify `NewLoginDetected`, log `UserActionEnum::UserLogin`
- [X] T024 [US1] Create `app/Http/Controllers/App/Auth/SsoController.php` with `redirect` and `callback`, applying the two factor branch `MagicLinkController` already uses
- [X] T025 [US1] Add `auth.sso.redirect` and `auth.sso.callback` to `routes/auth.php`, in the `guest` group, rate limited at 6 a minute
- [X] T026 [US1] Add `providersToOffer()` to `app/ViewModels/Auth/LoginViewModel.php`
- [X] T027 [US1] Add the provider loop to `resources/views/app/auth/login.blade.php`, beside the passkey block, each control reading `Continue with :provider`
- [X] T028 [US1] Add the `providersToOffer()` cases to `tests/Unit/ViewModels/Auth/LoginViewModelTest.php`

**Checkpoint**: a person signs in and signs up through a provider. The gate is not on yet.

---

## Phase 4: User Story 2 - The operator decides what is offered (P1)

**Goal**: only a provider that is installed and configured exists on an instance. Nothing else does.

**Independent Test**: with nothing configured, `/login` is unchanged and every route answers not found. Configure GitHub and confirm exactly GitHub is offered.

- [X] T029 [P] [US2] Create `app/Http/Middleware/EnsureSsoProviderIsAvailable.php`, answering not found unless the provider in the route is available
- [X] T030 [US2] Alias it `sso` in `bootstrap/app.php`, beside `magic.link`
- [X] T031 [US2] Constrain `{provider}` to the enum's values and group the routes behind `sso` in `routes/auth.php`
- [X] T032 [US2] Add the not found cases to `tests/Feature/Controllers/App/Auth/SsoControllerTest.php`: no provider configured, a configured provider whose package is absent, and an unknown provider name
- [X] T033 [US2] Add the empty case to `tests/Unit/ViewModels/Auth/LoginViewModelTest.php` and assert `/login` renders unchanged with nothing configured
- [X] T034 [US2] Assert in `tests/Feature/Controllers/App/Auth/SsoControllerTest.php` that no request reads a credential belonging to the person, and that the token Socialite returns is not persisted
- [X] T035 [US2] Add a CI job to `.github/workflows/tests.yml` that removes `socialiteproviders/microsoft` and runs the suite, which is the only thing that proves SC-004

**Checkpoint**: US1 is now safe to ship. An instance that configures nothing carries nothing.

---

## Phase 5: User Story 3 - A user connects and disconnects a provider (P1)

**Goal**: an account that already exists reaches a provider, keeps whatever signed it in before, and can leave the provider again.

**Independent Test**: sign in with a password, connect GitHub, sign out, sign in through GitHub, sign out, sign in with the password, then disconnect GitHub and sign in with the password again.

### Making a provider additive

> Four existing actions treat a connected provider as the only way in. The rule settled on 2026-09-11 reverses that. These tasks come first because every connect test below is meaningless until they land.

- [X] T036 [US3] Remove the `usesSingleSignOn()` refusal at `app/Actions/AttemptLogin.php` line 75, refusing on a null password instead
- [X] T037 [US3] Remove the `usesSingleSignOn()` refusal at `app/Actions/UpdateUserPassword.php` line 64, so a connected user may set and change a password
- [X] T038 [US3] Rework the guards in `app/Actions/DestroyUserPassword.php` so a connected provider counts as another way in, and the refusal fires only when nothing else is left
- [X] T039 [US3] Remove the `usesSingleSignOn()` refusal in `app/Actions/RefusePasskeyLogin.php`, so a user may hold a passkey and a provider
- [X] T040 [US3] Refuse on a null password rather than on a connected provider in `app/Http/Controllers/Api/Auth/LoginController.php`
- [X] T041 [US3] Stop hiding the password block from a connected user in `app/ViewModels/Settings/Account/Security/SecurityViewModel.php` and `resources/views/app/settings/account/security/_change-password.blade.php`
- [X] T042 [US3] Rewrite the assertions that pinned the old exclusivity in `tests/Unit/Actions/AttemptLoginTest.php`, `UpdateUserPasswordTest.php`, `DestroyUserPasswordTest.php`, `RefusePasskeyLoginTest.php`, `tests/Feature/Auth/ProviderRegressionTest.php`, `PasskeyRefusalTest.php`, `tests/Feature/Controllers/Api/Auth/LoginControllerTest.php`, `tests/Feature/Controllers/App/Settings/Account/Security/PasswordControllerTest.php` and `SecurityControllerTest.php`
- [X] T043 [US3] Add both directions of SC-003 to `tests/Unit/Actions/AttemptLoginTest.php`: a password holder who connects a provider signs in with either, and an account created through a provider has no password to sign in with

### Connecting and disconnecting

- [X] T044 [P] [US3] Create `tests/Unit/Actions/ConnectProviderToUserTest.php`: connected, the password survives, the pair already belongs to another user, the user already holds a provider
- [X] T045 [P] [US3] Create `tests/Unit/Actions/DisconnectProviderFromUserTest.php`: disconnected with a password left, disconnected with a passkey left, refused with nothing left
- [X] T046 [P] [US3] Create `tests/Unit/Notifications/SignInProviderDisconnectedTest.php` asserting the subject, the panel, the outro lines and `emailType()`
- [X] T047 [P] [US3] Add `UserSsoConnected` and `UserSsoDisconnected` to `app/Enums/UserActionEnum.php` and their descriptions
- [X] T048 [US3] Create `tests/Feature/Controllers/App/Settings/Account/Security/SsoControllerTest.php` covering every row of the connect and disconnect table in the contract
- [X] T049 [US3] Create `app/Actions/ConnectProviderToUser.php`, touching nothing but the pair, logging `UserSsoConnected`
- [X] T050 [US3] Create `app/Actions/DisconnectProviderFromUser.php`, refusing when nothing else is left, logging `UserSsoDisconnected`
- [X] T051 [US3] Create `app/Notifications/SignInProviderDisconnected.php`, beside `PasswordRemoved`
- [X] T052 [US3] Create `app/Http/Controllers/App/Settings/Account/Security/SsoController.php` with `redirect`, `callback` and `destroy`
- [X] T053 [US3] Add `settings.sso.redirect`, `settings.sso.callback` and `settings.sso.destroy` to `routes/web.php`, behind `sso` and rate limited at 6 a minute
- [X] T054 [US3] Add the connected provider and what may be offered to `app/ViewModels/Settings/Account/Security/SecurityViewModel.php`
- [X] T055 [US3] Create `resources/views/app/settings/account/security/_sso.blade.php` and `_sso-unavailable.blade.php`, and include them from `index.blade.php`
- [X] T056 [US3] Add the destructive warning before disconnecting, the way the screen already warns before removing a password
- [X] T057 [US3] Add the new cases to `tests/Unit/ViewModels/Settings/Account/Security/SecurityViewModelTest.php`, including a user connected to a provider that has since become unavailable

**Checkpoint**: all three stories work independently.

---

## Phase 6: Polish

- [X] T058 Run `php artisan monica:localize` and translate everything it reports, in every configured locale
- [X] T059 Run `php artisan monica:localize --check` and correct what it refuses
- [X] T060 [P] Correct the middleware count in `.claude/CLAUDE.md` line 71 and in `.claude/skills/middlewares/SKILL.md` line 8, now six classes and four aliased
- [X] T061 [P] Change `signs in` to `signs up` on lines 22 and 23 of `spec/models/002-user-done.md`. Line 24 is right as it stands
- [ ] T062 [P] Check both screens with the keyboard alone, with a screen reader, and zoomed to 200%
- [X] T063 Run `vendor/bin/pint --dirty --format agent`
- [X] T064 Run Larastan at level 5 over `app/`, `routes/` and `database/` and fix what is new
- [ ] T065 Run `quickstart.md` end to end against a real GitHub application
- [X] T066 Run the suite on SQLite, MySQL and PostgreSQL

---

## Dependencies & Execution Order

- **Phase 1** has no dependency. T001 before T002 and T003.
- **Phase 2** depends on Phase 1 and blocks every story. T006 before T011. T007 before T010 before T012. T008 before T009.
- **Phase 3 (US1)** depends on Phase 2. T019 before T020 before T022. T023 before T024 before T025. T026 before T027.
- **Phase 4 (US2)** depends on Phase 3, because it gates routes US1 creates. T029 before T030 before T031.
- **Phase 5 (US3)** depends on Phase 2 only. T036 to T043 before T044 to T057, and T047 before T049 and T050.
- **Phase 6** depends on every story that is going to ship.

**Shipping order**: US1 is the MVP but MUST NOT ship without US2, whose whole purpose is that an unconfigured instance carries nothing. FR-010 asks for the provider to be named on the security screen, which is US3, so US1 and US2 alone leave FR-010 half met and the pull request has to say so.

### Parallel opportunities

- Phase 1: T004 and T005 together.
- Phase 2: T007, T011, T013 and T015 together, once T006 lands. T010 follows T007, since it casts to the enum T007 creates.
- US1: T016 and T017 together, before their actions exist.
- US3: T044, T045, T046 and T047 together, once T036 to T043 land.
- US1 and US3 may be built by two people at once. They share only `SsoProviderEnum`, which Phase 2 already finished.

---

## Notes

- Every test file starts with `declare(strict_types=1);`, uses `#[Test]`, names its methods in snake_case starting with `it_`, and carries no comments.
- Socialite is faked in every test. No test reaches a provider.
- T036 to T042 change behaviour the suite currently asserts. Expect those nine files to go red before they go green.
- Commit after each task or logical group.
