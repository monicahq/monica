# Tasks: WebAuthn Authentication

**Input**: Design documents from `/specs/002-webauthn-auth/`

**Prerequisites**: [plan.md](plan.md), [spec.md](spec.md), [research.md](research.md),
[data-model.md](data-model.md), [contracts/](contracts/), [quickstart.md](quickstart.md)

**Tests**: mandatory, not optional. The constitution requires a test for every model, controller,
action, helper, job, mailable and view model, and says no change ships without one. Test tasks are
therefore listed first inside each phase and are not marked as a choice.

**Organization**: grouped by user story so each can be implemented, tested and demonstrated on its
own.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: can run in parallel (different files, no dependency on an unfinished task)
- **[Story]**: which user story the task serves (US1 to US5)
- Every task names the file or files it touches

## Path Conventions

Single Laravel project, paths from the repository root. Actions in `app/Actions`, listeners in
`app/Listeners`, view responses in `app/Http/Responses`, view models in `app/ViewModels`, screens in
`resources/views/app`, unit tests mirroring the app namespace under `tests/Unit`, feature tests
under `tests/Feature`.

---

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: install the package and put the configuration in the state every later phase assumes.

- [X] T001 Require the package with `composer require asbiin/laravel-webauthn:^6.0` and commit the resulting `composer.json` and `composer.lock`
- [X] T002 Install the browser library with `bun add @simplewebauthn/browser` at ^13.1.0, placing it in `dependencies` rather than `devDependencies`, and commit `package.json` and `bun.lock`
- [X] T003 Publish the package config with `php artisan vendor:publish --provider="LaravelWebauthn\WebauthnServiceProvider"`, producing `config/webauthn.php`
- [X] T004 Set the deviations in `config/webauthn.php`: `prefix` to `passkeys`, `userless` true, `user_verification` and `resident_key` to `required`, `model` to `App\Models\WebauthnKey::class`, `limiters.login` to `'login'`, `timeout` explicit, `attachment_mode` null, `attestation_conveyance` `none`
- [X] T005 Add `'fallback' => true` to `config/webauthn.php` with a comment saying it is what keeps password sign in working, because the key is absent from the published file and defaults silently
- [X] T006 Switch the `users` provider in `config/auth.php` from `driver: eloquent` to `driver: webauthn`, leaving the `web` guard on `session`
- [X] T007 [P] Add `WEBAUTHN_USERLESS=true` to `.env.example`, with a comment that `CACHE_STORE` must not be `array` or `null` in production
- [X] T008 [P] Force the https scheme outside local environments in `app/Providers/AppServiceProvider.php`, since WebAuthn requires a secure context
- [X] T009 Register the `login` rate limiter in `app/Providers/AppServiceProvider.php` at five attempts a minute, keyed on the credential id and the IP rather than an email
- [X] T010 Run `php artisan test --compact` on SQLite, MySQL and PostgreSQL and confirm the suite is green before any feature code exists

**Checkpoint**: the package is installed and configured, and the application still behaves exactly
as it did.

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: the schema, the model, the shared enum cases, and the proof that switching the auth
provider broke nothing.

**CRITICAL**: no user story work begins until this phase is complete. T018 in particular is the task
that catches a `webauthn.fallback` mistake, and it has to exist before there is any passkey code to
blame.

- [X] T011 Write `tests/Feature/Auth/ProviderRegressionTest.php` covering, through `EloquentWebAuthnProvider`: password sign in, a wrong password, a deactivated user, an SSO user, magic link sign in, magic link sign in for a user with two factor authentication on, and the remember cookie
- [X] T012 [P] Write `tests/Unit/Models/WebauthnKeyTest.php` asserting the `user()` relation exists, `used_at` casts to a date, `used_at` is null on a fresh row, and `hasBeenUsed()` reflects both states
- [X] T013 [P] Extend `tests/Unit/Models/UserTest.php` for `passkeys()`, `hasPasskeys()` and `usesPasswordlessSignIn()`, including that an SSO user is not passwordless
- [X] T014 [P] Extend `tests/Unit/Enums/UserActionEnumTest.php` for the six new cases and their labels
- [X] T015 Write the migration `database/migrations/2026_08_25_000000_add_used_at_to_webauthn_keys_table.php` adding a nullable `used_at` timestamp, with `up()` only and no `->after()`
- [X] T016 [P] Create `app/Models/WebauthnKey.php` extending `LaravelWebauthn\Models\WebauthnKey`, whose constructor calls `mergeFillable`, `setVisible(array_merge(...))` and `mergeCasts` for `used_at`, plus a `user()` BelongsTo, `hasBeenUsed()` and the property docblock
- [X] T017 Add `passkeys()`, `hasPasskeys()` and `usesPasswordlessSignIn()` to `app/Models/User.php`, and widen the `password` docblock to say null now means SSO or a passwordless account
- [X] T018 Run `php artisan test --compact tests/Feature/Auth/ProviderRegressionTest.php` and confirm it passes, then flip `webauthn.fallback` to false by hand, confirm it fails, and flip it back
- [X] T019 [P] Add the six cases `PasskeyAdded`, `PasskeyRenamed`, `PasskeyRemoved`, `PasskeyLogin`, `UserPasswordRemoved` and `UserPasswordSet` to `app/Enums/UserActionEnum.php` with their translated labels
- [X] T020 [P] Add the five cases `PasskeyAdded`, `PasskeyRemoved`, `PasswordRemoved`, `PasswordSet` and `PasskeyCounterAnomaly` to `app/Enums/EmailType.php`
- [X] T021 Settle research open item 1: prove whether `CACHE_STORE=array` in `phpunit.xml` carries the challenge between the options request and the verification request inside one test, and switch it to `database` if it does not
- [X] T022 Settle research open item 2: determine whether the FR-014 throttle key can be shaped inside `EnsureLoginIsNotThrottled` through `config('webauthn.limiters.login')`, or whether it has to move into the pipeline stage added in T035, and record which in `research.md`
- [X] T023 Settle research open item 3: determine whether the package's `LoginViaRemember` listener does anything unwanted now that every sign in goes through the driver, and record the decision in `research.md`

**Checkpoint**: schema, model and shared enums are in place, and authentication that has nothing to
do with passkeys is proven intact.

---

## Phase 3: User Story 1 - Add a passkey and sign in with it (Priority: P1) 🎯 MVP

**Goal**: somebody signed in adds a passkey from the security screen, signs out, and signs back in
with it, typing nothing.

**Independent Test**: on a device with a platform authenticator, add a passkey from the security
screen, sign out, sign back in using only the passkey, and confirm the session carries the same
rights as one opened with a password.

### Tests for User Story 1

- [X] T026 [P] [US1] Write `tests/Feature/Auth/PasskeyRefusalTest.php` asserting that an unregistered credential, a credential for a user who is not `is_active`, one for a soft deleted user and one for an SSO user all return the identical message `These credentials do not match our records.`
- [X] T027 [P] [US1] Write `tests/Unit/Actions/RefusePasskeyLoginTest.php` for each refusal branch and for the allowed case
- [X] T028 [P] [US1] Write `tests/Unit/Listeners/RecordPasskeyUseTest.php` asserting `used_at` is stamped on the right row for the credential in the event
- [X] T029 [P] [US1] Write `tests/Unit/Listeners/HandlePasskeyLoginTest.php` asserting `last_login_at` is stamped and that `CheckLastLogin`, `LogUserAction` and `SendEmail` are pushed on their queues
- [X] T030 [P] [US1] Write `tests/Unit/Listeners/HandlePasskeyRegistrationTest.php` asserting the activity entry names the passkey and that `PasskeyAdded` is sent
- [X] T031 [P] [US1] Write `tests/Unit/Mail/PasskeyAddedTest.php` asserting the envelope subject and that `render()` carries the passkey name, with the fixture at `tests/Fixtures/Mail/PasskeyAdded.php`
- [X] T032 [P] [US1] Write `tests/Unit/ViewModels/Settings/Account/Security/PasskeyListViewModelTest.php` for ordering, the never used state and the counted header phrase
- [X] T033 [P] [US1] Write `tests/Unit/ViewModels/Auth/LoginViewModelTest.php` additions for whether passkeys are usable on this instance

### Implementation for User Story 1

- [X] T034 [US1] Create `app/Actions/RefusePasskeyLogin.php` holding the decision: no such credential, not `is_active`, soft deleted, or an SSO user, each reported with the message `AttemptLogin` already uses
- [X] T035 [US1] Create `app/Listeners/EnsurePasskeyLoginIsAllowed.php` as the pipeline stage that resolves the candidate through the provider and hands it to `RefusePasskeyLogin`
- [X] T036 [US1] Wire `Webauthn::authenticateThrough()` in `app/Providers/AppServiceProvider.php` so `EnsurePasskeyLoginIsAllowed` runs after `EnsureLoginIsNotThrottled` and before `AttemptToAuthenticate`
- [X] T037 [P] [US1] Create `app/Listeners/RecordPasskeyUse.php` on `Webauthn\Event\AuthenticatorAssertionResponseValidationSucceededEvent`, writing `used_at`
- [X] T038 [P] [US1] Create `app/Listeners/HandlePasskeyLogin.php` on `LaravelWebauthn\Events\WebauthnLogin`, stamping `last_login_at` and dispatching `CheckLastLogin`, `LogUserAction` with `PasskeyLogin`, and `NewLoginDetected`
- [X] T039 [P] [US1] Create `app/Listeners/HandlePasskeyRegistration.php` on `LaravelWebauthn\Events\WebauthnRegister`, dispatching `LogUserAction` with `PasskeyAdded` and the passkey as the loggable, and sending the `PasskeyAdded` mailable
- [X] T040 [P] [US1] Create `app/Mail/PasskeyAdded.php` and its Blade view under `resources/views/mail`
- [X] T041 [US1] Create `app/Http/Responses/PasskeyLoginViewResponse.php` extending the package base, answering JSON with the options and a browser with the sign in screen
- [X] T042 [US1] Create `app/Http/Responses/PasskeyRegisterViewResponse.php` extending the package base, answering JSON with the options and a browser with the security screen
- [X] T043 [US1] Register both response classes with `Webauthn::loginViewResponseUsing()` and `Webauthn::registerViewResponseUsing()` in `app/Providers/AppServiceProvider.php`
- [X] T044 [US1] Settle research open item 4: prove that the options `PasskeyRegisterViewResponse` and `PasskeyLoginViewResponse` emit are field for field the JSON form `@simplewebauthn/browser` 13 expects, with a test asserting the shape, since a mismatch is silent
- [X] T045 [US1] Create `resources/js/passkey.js` as an Alpine component wrapping `@simplewebauthn/browser`, calling `startRegistration({ optionsJSON })` and `startAuthentication({ optionsJSON, useBrowserAutofill: false })` and posting the result
- [X] T046 [US1] Register the component in `resources/js/app.js`
- [X] T047 [US1] Create `app/ViewModels/Settings/Account/Security/PasskeyListViewModel.php` returning the list most recently used first, each with name, added date and last use
- [X] T048 [US1] Add the passkey list and the availability question to `app/ViewModels/Settings/Account/Security/SecurityViewModel.php`, and extend `tests/Unit/ViewModels/Settings/Account/Security/SecurityViewModelTest.php`
- [X] T049 [US1] Create `resources/views/app/settings/account/security/_passkeys.blade.php` and `_passkey-row.blade.php` showing the list, with the never used state stated rather than blank
- [X] T050 [US1] Add the passkeys section to `resources/views/app/settings/account/security/index.blade.php`, above the two factor section, with its explanatory copy
- [X] T051 [US1] Add the availability question to `app/ViewModels/Auth/LoginViewModel.php`
- [X] T052 [US1] Add the sign in with a passkey control to `resources/views/app/auth/login.blade.php`
- [X] T053 [US1] Walk the User Story 1 scenarios in `quickstart.md` by hand on a real authenticator, including cancelling the device prompt and confirming nothing is recorded

**Checkpoint**: User Story 1 is complete. A passkey can be added and can sign somebody in with
nothing typed, and the activity and the notification both land.

---

## Phase 4: User Story 2 - See and manage the passkeys on the account (Priority: P2)

**Goal**: every passkey is listed and distinguishable, can be renamed, and can be removed after a
warning.

**Independent Test**: register three passkeys, confirm all three are listed and distinguishable,
rename one, remove one after being warned, and confirm the removed one no longer signs anybody in
while the other two still do.

### Tests for User Story 2

- [X] T054 [P] [US2] Write `tests/Feature/Auth/PasskeyManagementTest.php` covering rename through `webauthn.update`, a blank and an over long name refused with the old name standing, and removal through `webauthn.destroy`
- [X] T055 [P] [US2] Extend `tests/Feature/Auth/PasskeyManagementTest.php` to assert that a passkey belonging to another account is not found rather than refused, for both `webauthn.update` and `webauthn.destroy`
- [X] T056 [P] [US2] Write `tests/Unit/Actions/DestroyPasskeyTest.php` asserting the row is gone, that the activity entry survives it, and that `PasskeyRemoved` is sent
- [X] T057 [P] [US2] Write `tests/Unit/Mail/PasskeyRemovedTest.php` with the fixture at `tests/Fixtures/Mail/PasskeyRemoved.php`
- [X] T058 [P] [US2] Write `tests/Feature/Auth/PasskeyAccountDeletionTest.php` asserting `DestroyAccount` leaves no `webauthn_keys` rows, and run it on all three drivers

### Implementation for User Story 2

- [X] T059 [US2] Create `app/Actions/DestroyPasskey.php`, dispatching `LogUserAction` with the passkey as the loggable before the row is deleted
- [X] T060 [P] [US2] Create `app/Mail/PasskeyRemoved.php` and its Blade view under `resources/views/mail`
- [X] T061 [US2] Add the rename control to `resources/views/app/settings/account/security/_passkey-row.blade.php`, posting to `webauthn.update`
- [X] T062 [US2] Add the remove control with its destructive confirmation to `_passkey-row.blade.php`, warning that the device holding it will no longer sign them in
- [X] T063 [US2] Add the status message naming what still signs them in after a removal, in `app/Http/Controllers/App/Settings/Account/Security/PasskeyController.php` or the response that handles `webauthn.destroy`
- [X] T064 [US2] Add the empty state to `resources/views/app/settings/account/security/_passkeys.blade.php`, explaining what a passkey is and offering to add one
- [X] T065 [US2] Add the counted header phrase through `trans_choice()` with `:count` in every segment, in `app/ViewModels/Settings/Account/Security/PasskeyListViewModel.php`
- [X] T066 [US2] Confirm the duplicate registration path in `tests/Feature/Auth/PasskeyRegistrationTest.php`: `excludeCredentials` refuses at the device level and the server refuses a posted duplicate with an explanation and no second row

**Checkpoint**: User Stories 1 and 2 both work. Passkeys accumulate, stay distinguishable, and can
be cut off.

---

## Phase 5: User Story 3 - Keep a way in when a passkey cannot be used (Priority: P2)

**Goal**: nobody is stranded, and no screen offers a control that cannot work.

**Independent Test**: with a passkey registered, confirm password and magic link sign in both still
work, then open the sign in screen where passkeys are unavailable and confirm the reason is stated
and another way in is offered.

### Tests for User Story 3

- [X] T067 [P] [US3] Write `tests/Feature/Auth/PasskeyAvailabilityTest.php` asserting that an insecure non localhost host renders the unavailable explanation on both the sign in screen and the security screen, and offers no passkey control
- [X] T068 [P] [US3] Extend `tests/Feature/Auth/PasskeyLoginTest.php` for the throttle: past the limit, attempts are refused for a period with the reason stated
- [X] T069 [P] [US3] Write `tests/Unit/Listeners/HandlePasskeyRegistrationFailureTest.php` asserting the counter anomaly notification is sent only when the counter is the reason
- [X] T070 [P] [US3] Write `tests/Unit/Mail/PasskeyCounterAnomalyTest.php` with the fixture at `tests/Fixtures/Mail/PasskeyCounterAnomaly.php`
- [X] T071 [P] [US3] Extend `tests/Feature/Auth/PasskeyRefusalTest.php` for a credential bound to a former instance address, asserting it gets its own actionable message rather than the identical refusal

### Implementation for User Story 3

- [X] T072 [US3] Add the server side availability check to `app/ViewModels/Auth/LoginViewModel.php` and `app/ViewModels/Settings/Account/Security/SecurityViewModel.php`, reading `config('webauthn.enable')` and whether the request is secure or on localhost
- [X] T073 [US3] Create `resources/views/app/settings/account/security/_passkeys-unavailable.blade.php` with the two translated reasons
- [X] T074 [US3] Gate the control in `resources/js/passkey.js` on `browserSupportsWebAuthn()`, and derive the reason from `window.isSecureContext` and the hostname to pick between the two translated sentences
- [X] T075 [US3] Add the unavailable explanation to `resources/views/app/auth/login.blade.php`, in place of the control rather than beside it
- [X] T076 [P] [US3] Create `app/Listeners/HandlePasskeyRegistrationFailure.php` on `LaravelWebauthn\Events\WebauthnRegisterFailed`, sending `PasskeyCounterAnomaly` where the counter is the reason
- [X] T077 [P] [US3] Create `app/Mail/PasskeyCounterAnomaly.php` and its Blade view under `resources/views/mail`
- [X] T078 [US3] Add the former address explanation in `app/Actions/RefusePasskeyLogin.php`, so a credential created under a previous host says the passkey no longer applies here and points at another way in
- [X] T079 [US3] Wire the helper's `notify` callback in `resources/js/passkey.js` to a live region, so a cancelled prompt is announced and not only rendered
- [X] T080 [US3] Confirm `resources/views/app/auth/login.blade.php` still offers the password form and the magic link alongside the passkey control, unchanged

**Checkpoint**: User Story 3 is complete. Every refusal names a next step and every unavailable
state explains itself.

---

## Phase 6: User Story 4 - Sign in on a device that holds no passkey (Priority: P3)

**Goal**: the platform's own cross device flow works and Monica does not get in the way.

**Independent Test**: with a passkey registered on a phone, sign in on a separate computer that
holds no passkey, using the platform's cross device flow, and confirm the session is a normal one.

This story is verification rather than construction: `mediation: "optional"` and the empty
`allowCredentials` from Phase 3 are what make it work, so nothing new is built here.

- [X] T081 [P] [US4] Extend `tests/Feature/Auth/PasskeyLoginTest.php` to assert that an abandoned assertion leaves no session and returns the screen to its starting state
- [ ] T082 [US4] Verify the cross device flow by hand per `quickstart.md`, on a phone and a separate computer
- [ ] T083 [US4] Verify per `quickstart.md` that a passkey registered on a security key rather than the machine in front of you is listed and behaves identically to any other

**Checkpoint**: User Story 4 is verified.

---

## Phase 7: User Story 5 - Give up the password entirely (Priority: P3)

**Goal**: somebody holding a passkey may remove their password, is warned first, and can set one
again.

**Independent Test**: with a passkey registered, remove the password, confirm the passkey still
signs you in and that submitting a password is refused with the ordinary message, then set a
password again and confirm it works.

### Tests for User Story 5

- [X] T084 [P] [US5] Write `tests/Feature/Controllers/App/Settings/Account/Security/PasswordControllerTest.php` additions for `destroy`: removed with a passkey, refused with none, refused where passkeys are unavailable, refused for an SSO user
- [X] T085 [P] [US5] Extend `tests/Feature/Controllers/App/Settings/Account/Security/PasswordControllerTest.php` for `create`: sets a password on an account with none, refuses when one already exists, and requires confirmation and a minimum length
- [X] T086 [P] [US5] Write `tests/Unit/Actions/DestroyUserPasswordTest.php` asserting `password` and `password_changed_at` are nulled, the activity entry is written, and `PasswordRemoved` is sent
- [X] T087 [P] [US5] Extend `tests/Unit/Actions/AttemptLoginTest.php` asserting a user with a null password and no `sso_provider` is refused with the identical message and never reaches the hash check
- [X] T088 [P] [US5] Extend `tests/Unit/Actions/UpdateUserPasswordTest.php` asserting `UserPasswordSet` and the `PasswordSet` email when there was no password, and `UserPasswordUpdate` and no email when there was
- [X] T089 [P] [US5] Write `tests/Unit/Mail/PasswordRemovedTest.php` and `tests/Unit/Mail/PasswordSetTest.php` with their fixtures under `tests/Fixtures/Mail`
- [X] T090 [P] [US5] Extend `tests/Feature/Controllers/App/Auth/NewPasswordControllerTest.php` asserting the existing password reset flow sets a password on an account that has none, rather than failing

### Implementation for User Story 5

- [X] T091 [US5] Create `app/Actions/DestroyUserPassword.php`, refusing when the account holds no passkey, when passkeys are unavailable on this instance, and for an SSO user
- [X] T092 [US5] Add the null password guard to the existing early return in `app/Actions/AttemptLogin.php`
- [X] T093 [US5] Split `app/Actions/UpdateUserPassword.php` between setting a first password and changing one, choosing the enum case from whether a password was there and sending `PasswordSet` only in the first case
- [X] T094 [P] [US5] Create `app/Mail/PasswordRemoved.php` and `app/Mail/PasswordSet.php` with their Blade views under `resources/views/mail`
- [X] T095 [US5] Add `create` and `destroy` to `app/Http/Controllers/App/Settings/Account/Security/PasswordController.php`, with no `current_password` rule on `create`
- [X] T096 [US5] Add the two routes `settings.password.create` and `settings.password.destroy` to `routes/web.php`, beside the other security routes
- [X] T097 [US5] Create `resources/views/app/settings/account/security/_remove-password.blade.php` with the warning, what still signs them in, and the explicit confirmation
- [X] T098 [US5] Add the set a password form for an account that has none, to the same partial or to `_change-password.blade.php`
- [X] T099 [US5] Add the FR-033 warning to `_passkey-row.blade.php`: a passwordless account removing its last passkey is told the emailed link becomes its only way in, and offered a password instead, without the removal being refused
- [X] T100 [US5] Audit every signed in screen for a current password confirmation that a passwordless account could not satisfy, and record what was found in `research.md`

**Checkpoint**: all five user stories are complete.

---

## Phase 8: Polish & Cross-Cutting Concerns

- [X] T101 Run `php artisan monica:localize` and translate every reported string in all nine locales in `lang/*.json`, never editing keys by hand
- [X] T102 Run `php artisan monica:localize --check` and fix any counted phrase that does not interpolate `:count` in every segment
- [X] T103 [P] Run `vendor/bin/pint --dirty --format agent` and commit the formatting
- [X] T104 [P] Run `vendor/bin/phpstan analyse` and fix every new finding rather than ignoring it
- [ ] T105 Accessibility pass on `resources/views/app/auth/login.blade.php` and `resources/views/app/settings/account/security/index.blade.php`: keyboard only, screen reader over the whole passkey section, visible focus, no meaning carried by colour alone, readable at 200%, usable at phone width
- [X] T106 Confirm the live region in `resources/js/passkey.js` announces the cancelled prompt outcome to a screen reader, since it changes nothing visible on the page
- [ ] T107 Run `php artisan test --compact` on SQLite, MySQL and PostgreSQL
- [ ] T108 Walk `quickstart.md` end to end, including the "nothing else broke" section and the account deletion cascade
- [X] T109 Update `.env.example` and the install documentation with the real cache store requirement and the https requirement

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: no dependencies, starts immediately
- **Foundational (Phase 2)**: needs Phase 1, and blocks every user story
- **User Story 1 (Phase 3)**: needs Phase 2
- **User Story 2 (Phase 4)**: needs Phase 3, because there is nothing to list until a passkey can be
  created
- **User Story 3 (Phase 5)**: needs Phase 3. Independent of Phase 4
- **User Story 4 (Phase 6)**: needs Phase 3. Independent of Phases 4 and 5
- **User Story 5 (Phase 7)**: needs Phase 3 for a passkey to exist, and reads better after Phase 4,
  since removing a password and removing your last passkey share a warning
- **Polish (Phase 8)**: needs every story that is going to ship

### Story Dependencies

This feature is less parallel than most, and the reason is structural: US1 is the pair of
registering and signing in, and nothing else means anything until it exists. Once it does, US2, US3
and US4 are genuinely independent of each other. US5 depends on a passkey existing, which is US1,
and on the removal warning, which is US2.

### Within Each Story

Tests are written before the implementation they cover, and are expected to fail first. Then: the
action or listener, then the view model, then the screen. The pipeline stage before the listeners
that react to a successful sign in, because a refused sign in must never reach them.

### Parallel Opportunities

- T007 and T008 in Phase 1
- T012, T013, T014 in Phase 2, and T019 with T020, and T016 with T017 once T015 has run
- Every test task in Phase 3, T024 to T033, is a different file
- T037, T038, T039, T040 in Phase 3, four different listeners and one mailable
- T054 to T058 in Phase 4
- T067 to T071 in Phase 5, and T076 with T077
- T084 to T090 in Phase 7, and T094 alongside
- T103 with T104

---

## Parallel Example: User Story 1

```bash
# The test files, all different, all expected to fail first:
Task: "tests/Feature/Auth/PasskeyRegistrationTest.php"
Task: "tests/Feature/Auth/PasskeyLoginTest.php"
Task: "tests/Feature/Auth/PasskeyRefusalTest.php"
Task: "tests/Unit/Actions/RefusePasskeyLoginTest.php"
Task: "tests/Unit/Listeners/RecordPasskeyUseTest.php"
Task: "tests/Unit/Listeners/HandlePasskeyLoginTest.php"
Task: "tests/Unit/Listeners/HandlePasskeyRegistrationTest.php"

# The listeners, once the tests are red:
Task: "app/Listeners/RecordPasskeyUse.php"
Task: "app/Listeners/HandlePasskeyLogin.php"
Task: "app/Listeners/HandlePasskeyRegistration.php"
Task: "app/Mail/PasskeyAdded.php"
```

---

## Implementation Strategy

### MVP First

1. Phase 1, Setup
2. Phase 2, Foundational. Do not skip T011 and T018: they are the only thing standing between a
   `fallback` mistake and an instance nobody can sign in to
3. Phase 3, User Story 1
4. **Stop and validate**: add a passkey, sign out, sign in with it, typing nothing
5. Demonstrable at this point

### Incremental Delivery

1. Setup and Foundational, and the application behaves exactly as before
2. US1, and passkeys work. MVP
3. US2, and they are manageable. This is the first point the feature is shippable, because a passkey
   nobody can revoke is not something to ship
4. US3, and nobody can be stranded
5. US4, verified rather than built
6. US5, and the password can go

### Notes

- Every task is one commit or one logical group, and the suite stays green between them
- The three research open items are tasks, T021 to T023, not assumptions
- Two settings, `user_verification` and `resident_key`, cannot be tightened after credentials exist.
  They are set in T004 and revisiting them later is a migration of people's devices, not a config
  change
- Tests never carry comments; the method name carries the intent
- Every string added anywhere in these phases goes through `__()` or `trans_choice()`, and T101
  translates all of them

---

## Phase 9: User Story 6 - Create an account with a passkey and no password (Priority: P2)

**Goal**: somebody new signs up with a passkey and never invents a password.

**Independent Test**: on a device with a platform authenticator, create an account from the
registration screen without typing a password, then sign out and sign back in with the passkey
alone.

**Why this phase exists at all**: it was out of scope in the first specification, and became
incoherent once User Story 5 let an account hold no password. Forcing every new account to invent a
password it may delete a minute later is the product arguing with itself.

**The ordering constraint that shapes everything here**: a passkey is created against an account
that already exists, because `Webauthn::prepareAttestation()` takes a user. So the account is made
first, with no password, and the passkey immediately after. A prompt abandoned between the two
leaves an account with neither credential, reachable only by the emailed link, which is why T118
exists and is not optional.

### Tests for User Story 6

- [X] T110 [P] [US6] Extend `tests/Unit/Actions/CreateAccountTest.php` for a null password: the account and the user are created, `password` and `password_changed_at` are null, and `usesPasswordlessSignIn()` is true
- [X] T111 [P] [US6] Write `tests/Feature/Controllers/App/Auth/RegistrationPasskeyControllerTest.php` covering an account created with no password, the person signed in afterwards, the verification email sent, and the activity recorded
- [X] T112 [P] [US6] Extend `tests/Feature/Controllers/App/Auth/RegistrationControllerTest.php` asserting the password route still works unchanged, including its validation, its terms acceptance and its bot check
- [X] T113 [P] [US6] Write `tests/Feature/Auth/RegistrationScreenTest.php` asserting that over a secure connection the passkey is the primary action and the password is offered as an alternative, and that over an insecure one the reason is stated and only the password route appears
- [X] T114 [P] [US6] Extend `tests/Feature/Auth/RegistrationScreenTest.php` asserting that an account holding neither a passkey nor a password is told, on the next screen it sees, that the emailed link is what reaches it
- [X] T115 [P] [US6] Extend `tests/Feature/Auth/PasskeyScreensTest.php` asserting that a passkey created at registration is listed on the security screen like any other

### Implementation for User Story 6

- [X] T116 [US6] Make `password` nullable in `app/Actions/CreateAccount.php`, setting `password_changed_at` only when one was given
- [X] T117 [US6] Create `app/Http/Controllers/App/Auth/RegistrationPasskeyController.php` with `create`, which validates everything the password route validates except the password, creates the account with none, signs them in, and sends them to the passkey step
- [X] T118 [US6] Add the two routes to `routes/auth.php`: `POST register/passkey` as `auth.registerPasskey.create`, and `GET register/passkey` as `auth.registerPasskey.new` for the step that prompts for the credential
- [X] T119 [US6] Create `resources/views/app/auth/register-passkey.blade.php`, the step that opens the device prompt, confirms the passkey was created before moving on (FR-049), and on a cancelled prompt offers both a passkey and a password again (FR-050)
- [X] T120 [US6] Rework `resources/views/app/auth/register.blade.php` so creating a passkey is the primary action and setting a password is an alternative behind one link, with the plain sentence FR-047 asks for above it
- [X] T121 [US6] Add the availability question to `app/ViewModels/Auth/RegisterViewModel.php`, and the unavailable explanation to the registration screen, so no control appears that would fail (FR-051)
- [X] T122 [US6] Add the FR-052 notice: an account holding neither a passkey nor a password is told what still reaches it and is offered both, on the next screen it sees
- [X] T123 [US6] Confirm the email confirmation and the account activity are identical for both routes (FR-053), and that `CreateAccount`'s own log entry is unchanged
- [X] T124 [US6] Run `php artisan monica:localize` and translate every string this phase adds in all nine locales

**Checkpoint**: an account can be created with a passkey and no password, and nobody can leave
registration without being told what signs them in.
