---
description: "Task list for adopting laravel/passkeys"
---

# Tasks: Adopt laravel/passkeys

**Input**: Design documents in `/specs/021-adopt-laravel-passkeys/`

**Tests**: Not optional. Constitution Principle III is non-negotiable, and [spec.md](spec.md) SC-001
keeps every existing assertion except the two FR-009 repeals.

**On story independence**: the three stories share one engine, so they are not independently
deployable here. Phase 2 swaps the package and nothing signs in until it is done. What each story
phase gives you is an independently *testable* slice, and the checkpoints are where you stop and
prove one before starting the next.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: touches its own files, no dependency on an unfinished task
- **[Story]**: US1, US2, US3 from [spec.md](spec.md)

---

## Phase 1: Setup

**Purpose**: get both halves of the package in place.

- [X] T001 Require `laravel/passkeys ^0.2.1` and remove `asbiin/laravel-webauthn` in `composer.json`
- [X] T002 Publish `config/passkeys.php` and the passkeys migration into `database/migrations/` with `vendor:publish --tag=passkeys-config --tag=passkeys-migrations`, leaving the migration exactly as it ships
- [X] T003 [P] Add `@laravel/passkeys ^0.4.0` and drop the direct `@simplewebauthn/browser` entry in `package.json`

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: replace the engine. Nothing signs in until this phase is finished.

- [X] T004 [P] Delete `database/migrations/2026_08_25_163611_add_webauthn.php`
- [X] T005 [P] Delete `config/webauthn.php`
- [X] T006 [P] Return the `users` provider driver to `eloquent` in `config/auth.php`
- [X] T007 Set `management_middleware` to `[]` and `throttle` to `throttle:passkey` in `config/passkeys.php`
- [X] T008 [P] Create `app/Models/Passkey.php` per [data-model.md](data-model.md): extends the package model, scoped `resolveRouteBinding()`, retyped `user()`, `hasBeenUsed()`
- [X] T009 [P] Create `database/factories/PasskeyFactory.php` building a `credential` JSON payload instead of seven columns
- [X] T010 Swap `app/Models/User.php` onto `PasskeyAuthenticatable` and `PasskeyUser`, delete its own `passkeys()` so the trait supplies it, keep `hasPasskeys()`, override `getPasskeyDisplayName()` to the full name
- [X] T011 Delete `app/Models/WebauthnKey.php` and `database/factories/WebauthnKeyFactory.php`
- [X] T012 Create `app/Actions/VerifyPasskey.php` extending the package action, running `RefusePasskeyLogin` between `getPasskey()` and `validate()` per [research.md](research.md) D1
- [X] T013 [P] Delete `app/Providers/Webauthn/PasskeyCredentialRepository.php` and `SecurityKeyCredentialRepository.php` (FR-012)
- [X] T014 [P] Delete `app/Http/Middleware/EnsurePasskeyLoginIsAllowed.php`, whose pipeline no longer exists
- [X] T015 Rewrite the passkey wiring in `app/Providers/AppServiceProvider.php`: `Passkeys::usePasskeyModel()`, bind `VerifyPasskey`, drop `authenticateThrough`, the two view responses and the RS1 workaround, and rekey the limiter as `passkey`
- [X] T016 Repoint `shouldRenderJsonWhen` in `bootstrap/app.php` from `config('webauthn.prefix')` to `passkeys/*` and `user/passkeys*`
- [X] T017 [P] Drop the `config('webauthn.enable')` branch and the `NOT_ENABLED` reason from `app/Helpers/PasskeyAvailability.php`
- [X] T018 Rewrite `resources/js/passkey.js` onto `Passkeys.isSupported/register/verify/cancel`, mapping `UserCancelledError`, `PasskeyExistsError`, `InvalidDomainError` and `NotSupportedError` to `announce()`
- [X] T019 [P] Delete `app/Http/Responses/PasskeyLoginViewResponse.php` and `PasskeyRegisterViewResponse.php`

**Checkpoint**: `php artisan route:list --path=passkey` shows the package's seven routes and the app boots.

---

## Phase 3: User Story 1 - Sign in with a passkey (Priority: P1) 🎯 MVP

**Goal**: a passkey signs somebody in, a refused account gets nothing written down, FR-009's two warnings are gone.

**Independent Test**: quickstart scenarios 1 and 2.

- [X] T020 [US1] Add `app/Http/Responses/PasskeyLoginResponse.php` redirecting to `intended(route('home.index'))` and bind the contract in `AppServiceProvider` (research D7)
- [X] T021 [US1] Rewrite `app/Listeners/HandlePasskeyLogin.php` onto `PasskeyVerified`, naming the passkey from the event instead of re-reading the most recently used one
- [X] T022 [P] [US1] Delete `app/Listeners/RecordPasskeyUse.php`; the package stamps `last_used_at` itself
- [X] T023 [P] [US1] Delete `app/Listeners/NotifyOfPasskeyAnomaly.php`, `app/Listeners/ExplainAPasskeyBoundElsewhere.php` and `app/Notifications/PasskeyCounterAnomaly.php` (FR-009)
- [X] T024 [P] [US1] Remove the `PasskeyCounterAnomaly` case from `app/Enums/EmailType.php`
- [X] T025 [US1] Point `resources/views/app/auth/login.blade.php` at the package's sign-in URLs
- [X] T026 [P] [US1] Update `app/ViewModels/Auth/LoginViewModel.php` for the new URLs and availability reasons
- [X] T027 [P] [US1] Delete `tests/Unit/Listeners/{NotifyOfPasskeyAnomaly,ExplainAPasskeyBoundElsewhere,RecordPasskeyUse}Test.php` and `tests/Unit/Notifications/PasskeyCounterAnomalyTest.php`
- [X] T028 [P] [US1] Rewrite `tests/Unit/Listeners/HandlePasskeyLoginTest.php` against `PasskeyVerified`
- [X] T029 [P] [US1] Add `tests/Unit/Actions/VerifyPasskeyTest.php` proving a deactivated or SSO user is refused with `last_used_at` unmoved and no journal entry or email
- [X] T030 [P] [US1] Move `tests/Feature/Auth/PasskeyChallengeTest.php` and `PasskeyRefusalTest.php` onto the package URLs
- [X] T031 [P] [US1] Update `tests/Unit/ViewModels/Auth/LoginViewModelTest.php`
- [X] T032 [P] [US1] Update `tests/Feature/Auth/ProviderRegressionTest.php` for the `eloquent` provider

**Checkpoint**: sign-in works end to end and quickstart scenario 2's three absences hold.

---

## Phase 4: User Story 2 - Manage passkeys (Priority: P2)

**Goal**: add, rename and remove, with the journal, the emails and the "what still signs you in" message intact.

**Independent Test**: quickstart scenario 3.

- [X] T033 [US2] Add `app/Http/Responses/PasskeyDeletedResponse.php` carrying the remaining-credentials message and bind the contract
- [X] T034 [P] [US2] Rewrite `app/Listeners/HandlePasskeyRegistration.php` onto `PasskeyRegistered`
- [X] T035 [US2] Add `app/Listeners/HandlePasskeyRemoval.php` on `PasskeyDeleted`, writing the journal entry and sending `PasskeyRemoved`
- [X] T036 [US2] Delete `app/Actions/DestroyPasskey.php` and its `destroy` method from `app/Http/Controllers/App/Settings/Account/Security/PasskeyController.php`, keeping `update`
- [X] T037 [US2] Drop `settings.passkeys.destroy` from `routes/web.php`, keeping `settings.passkeys.update` (research D2)
- [X] T038 [P] [US2] Point `resources/views/app/settings/account/security/{index,_passkeys,_passkey-row,_passkeys-unavailable}.blade.php` at the new URLs, keeping the removal warning
- [X] T039 [P] [US2] Update `PasskeyListViewModel.php` and `SecurityViewModel.php` in `app/ViewModels/Settings/Account/Security/` for `last_used_at`
- [X] T040 [P] [US2] Retype `app/Actions/UpdatePasskey.php` and `app/Actions/DestroyUserPassword.php` onto `App\Models\Passkey`
- [X] T041 [P] [US2] Delete `tests/Unit/Actions/DestroyPasskeyTest.php` and add `tests/Unit/Listeners/HandlePasskeyRemovalTest.php`
- [X] T042 [P] [US2] Rewrite `tests/Unit/Listeners/HandlePasskeyRegistrationTest.php` against `PasskeyRegistered`
- [X] T043 [P] [US2] Replace `tests/Unit/Models/WebauthnKeyTest.php` with `tests/Unit/Models/PasskeyTest.php`, covering the scoped route binding returning 404
- [X] T044 [P] [US2] Move `tests/Feature/Auth/PasskeyManagementTest.php` onto the package URLs, keeping the warning and remaining-credentials assertions
- [X] T045 [P] [US2] Update `tests/Unit/ViewModels/Settings/Account/Security/PasskeyListViewModelTest.php`

**Checkpoint**: US1 and US2 both pass.

---

## Phase 5: User Story 3 - Create an account with a passkey (Priority: P3)

**Goal**: registering with a passkey still works and the second passkey needs no password confirmation.

**Independent Test**: quickstart scenario 4.

- [X] T046 [US3] Point `app/Http/Controllers/App/Auth/RegistrationPasskeyController.php` at the package's registration URLs, keeping its own two routes
- [X] T047 [P] [US3] Update `resources/views/app/auth/register-passkey.blade.php` and `register.blade.php`
- [X] T048 [P] [US3] Update `app/ViewModels/Auth/RegisterPasskeyViewModel.php`
- [X] T049 [P] [US3] Update `tests/Feature/Controllers/App/Auth/RegistrationPasskeyControllerTest.php` and `tests/Feature/Auth/RegistrationScreenTest.php`
- [X] T050 [P] [US3] Update `tests/Unit/Actions/CreateAccountTest.php` and `tests/Unit/Models/UserTest.php`

**Checkpoint**: all three stories pass.

---

## Phase 6: Polish & Cross-Cutting

- [X] T051 Amend `specs/002-webauthn-auth/spec.md` to repeal its FR-012 and FR-041, as FR-009 requires
- [X] T052 [P] Update `tests/Feature/Auth/{PasskeyAvailability,PasskeyAccessibility,PasskeyAccountDeletion,PasskeyScreens}Test.php`
- [X] T053 [P] Update `tests/Unit/Enums/UserActionEnumTest.php` and `TranslatableEnumsTest.php` for the removed `EmailType` case
- [X] T054 Run `php artisan monica:localize` and correct all nine `lang/*.json`, removing only FR-009's strings (FR-014)
- [X] T055 Run `vendor/bin/pint --dirty --format agent` and Larastan, fixing what they report
- [ ] T056 Run the full suite and walk scenarios 1 to 5 of `quickstart.md`, including the cancelled-prompt announcement — **suite done (4132 passing), the five browser scenarios still need a real authenticator over https**

---

## Dependencies & Execution Order

- **Phase 1** → **Phase 2** → **Phase 3** → **Phase 4** → **Phase 5** → **Phase 6**. Phase 2 blocks
  everything: the old package is gone and the new one is not wired until it ends.
- Within Phase 2: T008 and T009 before T010; T010 and T012 before T015; T004 to T007 are free.
- T020 depends on T015 (the container bindings live there). T033 likewise.
- T036 and T037 must land together; a route pointing at a deleted method is a broken build.
- T054 must follow every copy change, so it cannot move earlier than Phase 6.
- T051 has no code dependency and may be done at any point.

### Parallel opportunities

- Phase 2: T004, T005, T006, T008, T009, T013, T014, T017, T019 are nine independent files.
- Phase 3: T022, T023, T024, T026 to T032 once T021 is in.
- Phase 4: T034, T038 to T045. Phase 5: T047 to T050.

## Implementation Strategy

Phase 2 is the risk and cannot be split; do it in one sitting and get the app booting again before
touching a screen. From there each story phase ends at a checkpoint that proves one journey, so a
failure is attributable to the phase that caused it rather than to the swap as a whole.

---

## What actually happened

Every task above landed except the browser half of T056, which needs a device that
can make a passkey over https and cannot be done from a terminal.

Three things were found during implementation and are worth carrying forward:

- **The limiter had to be rekeyed.** The credential now arrives at `credential.id` rather than at
  the top level, so the limiter from FR-006 would have silently collapsed to one allowance per
  address. `PasskeyRefusalTest` covers both directions.
- **Refusals had to be made uniform by hand.** The package answers an unknown credential with its own
  message under its own key, where a refused user gets ours. Left alone, the difference tells an
  attacker which credentials exist here, which spec 002 FR-013 forbids. `VerifyPasskey::getPasskey()`
  now routes both through `RefusePasskeyLogin::refuse()`.
- **`shouldRenderJsonWhen` could not match on a prefix.** Removing a passkey lives under
  `user/passkeys/{id}` and is an ordinary form post, so matching `user/passkeys*` handed guests a 401
  instead of the sign-in screen. The four ceremony paths are named one by one instead.

Two scope notes, both reported rather than absorbed quietly:

- **Nine translated strings went, not the five FR-009 implies.** Five are FR-009's. Three are the
  master switch, which `laravel/passkeys` has no equivalent for and which no deployment could reach
  anyway, since `webauthn.enable` was a hardcoded `true`. One is `a passkey`, the fallback name
  `HandlePasskeyLogin` no longer needs now the event carries the passkey.
- **Several tests were quietly coupled to `APP_URL`.** They asserted the insecure branch while
  inheriting whatever scheme the developer's `.env` carried, and passed only because that scheme
  happened to be http. They now force the scheme they are about. This was pre-existing: the branch
  removed by FR-009's neighbour was a no-op, and the same tests fail on `main` under
  `APP_URL=https://…`.
