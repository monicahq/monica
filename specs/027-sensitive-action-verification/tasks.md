---
description: "Task list for 027-sensitive-action-verification"
---

# Tasks: Prove a credential before a sensitive action

**Input**: Design documents from `/specs/027-sensitive-action-verification/`

**Prerequisites**: [plan.md](plan.md), [spec.md](spec.md), [research.md](research.md), [contracts/confirm-password.md](contracts/confirm-password.md), [quickstart.md](quickstart.md)

**Tests**: included. Principle III makes a test a condition of merge, so the test sits in the story that needs it rather than in a phase of its own.

**Organization**: by user story. The whole feature is one pull request, and User Story 1 ships on its own for an account that has a password.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: different files, no dependency on an unfinished task
- Several tasks edit one test file each. Where two tasks share a file they run in order, and the dependency list says so

---

## Phase 1: Setup

- [X] T001 Create the branch `2026-09-11-sensitive-action-verification` from an up to date `main`
- [X] T002 Run `composer install` and `bun install` at the repository root, since this worktree has neither `vendor/` nor `node_modules/`

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: the screen that asks, and the one credential path that needs writing. Nothing requires it yet.

- [X] T003 Add `PasswordConfirmed = 'password_confirmed'`, `PasskeyConfirmed = 'passkey_confirmed'` and `PasswordConfirmationRefused = 'password_confirmation_refused'` beside `PasskeyLogin` in `app/Enums/UserActionEnum.php:37`, with a sentence each in `description()` at line 184
- [X] T004 [P] Create `app/Actions/ConfirmPassword.php` taking the promoted readonly `User $user` and `string $password`, returning `bool` from `Hash::check()`, dispatching `LogUserAction` with `PasswordConfirmed` or `PasswordConfirmationRefused` on the `low` queue, shaped like `app/Actions/ConfirmTwoFactorAuthentication.php`
- [X] T005 [P] Create `app/ViewModels/Auth/ConfirmPasswordViewModel.php` answering `offersPassword()`, `offersPasskey()` and `offersNothing()` from `$user->password`, `$user->hasPasskeys()` and `passkeysAreAvailable()`, the last read the way `SecurityViewModel` reads it
- [X] T006 Create `app/Http/Controllers/App/Auth/ConfirmPasswordController.php` with `new()` returning the view and `create()` validating `['password' => ['required', 'string']]`, calling `ConfirmPassword`, calling `$request->session()->passwordConfirmed()` and redirecting to `intended(route('home.index'))`, throwing `ValidationException` on a wrong password
- [X] T007 Create `resources/views/app/auth/confirm-password.blade.php` on the guest layout used by `app/auth/login.blade.php`, with the opening `{{-- --}}` line, the `@var` block, the password form when the account has one, and the FR-005 explanation naming the password reset email when it has neither credential
- [X] T008 Add `GET confirm-password` as `auth.confirmPassword.new` and `POST confirm-password` as `auth.confirmPassword.create` inside the `auth` group of `routes/auth.php:60`, the POST carrying `->middleware('throttle:confirm-password')`
- [X] T009 [P] Add `RateLimiter::for('confirm-password', ...)` at `self::MAX_LOGIN_ATTEMPTS` a minute, keyed on `$request->user()?->id ?: $request->ip()`, beside the `passkey` limiter in `app/Providers/AppServiceProvider.php:82`
- [X] T010 [P] Create `tests/Unit/Actions/ConfirmPasswordTest.php`: the right password returns true and logs `PasswordConfirmed`, a wrong one returns false and logs `PasswordConfirmationRefused`, an account with no password returns false
- [X] T011 [P] Create `tests/Unit/ViewModels/Auth/ConfirmPasswordViewModelTest.php`: the four states, password only, passkey only, both, neither
- [X] T012 Create `tests/Feature/Controllers/App/Auth/ConfirmPasswordControllerTest.php`: the screen answers 200 signed in and redirects to sign in as a guest, the right password marks `auth.password_confirmed_at` and lands on the intended URL, a wrong one does not and returns the error, and a sixth attempt in a minute answers 429, `MAX_LOGIN_ATTEMPTS` being 5

**Checkpoint**: `php artisan route:list --name=confirm-password` shows both routes, and the screen renders signed in.

---

## Phase 3: User Story 1 - An action that changes how somebody signs in asks first (Priority: P1) 🎯 MVP

**Goal**: the twelve sensitive routes refuse a session with no live proof, and the control asks for the password in place rather than sending the person away and losing what they typed.

**Independent Test**: signed in with a password, press "Remove my password". The modal asks, nothing changes until the password is given, and giving it carries the removal out. `curl -X DELETE` at the same URL answers 302 to `confirm-password` and removes nothing.

- [X] T013 [US1] Create `app/Http/Controllers/App/Settings/Account/Profile/EmailController.php` with `update()` validating the address the way `ProfileController::update()` does, then calling `UpdateUserProfile` with the user's current `first_name` and `last_name`
- [X] T014 [US1] Add `PUT settings/account/profile/email` as `settings.email.update` beside the profile routes in `routes/web.php:257`, and drop `email` from the rules and the `UpdateUserProfile` call in `ProfileController::update()`, which now passes `$request->user()->email`
- [X] T015 [US1] Split the address out of the details form into its own `x-form` pointed at `settings.email.update` in `resources/views/app/settings/account/profile/index.blade.php`
- [X] T016 [US1] Wrap the ten security routes at `routes/web.php:359-372` in `Route::middleware(['password.confirm:auth.confirmPassword.new'])->group(...)`, leaving `settings.security.index` outside it, with a comment naming what the group is for
- [X] T017 [US1] Put `settings.email.update` and `settings.account.destroy` under the same middleware in `routes/web.php`, leaving `settings.profile.update` outside it
- [X] T018 [P] [US1] Add `passwordIsConfirmed(): bool` to `app/ViewModels/Settings/Account/Security/SecurityViewModel.php` and to `app/ViewModels/Settings/Account/Profile/ProfileViewModel.php`, reading `auth.password_confirmed_at` against `config('auth.password_timeout')`
- [X] T019 [P] [US1] Create `resources/views/components/modal/confirm-password.blade.php` copying the dialog of `resources/views/components/modal/warning.blade.php`, `role="dialog"`, `aria-modal`, `x-trap.noscroll.inert`, escape to close, with the password field and an `aria-live` line for the refusal
- [X] T020 [US1] Create `resources/js/confirm-password.js` holding the form that opened the modal, posting the password to `auth.confirmPassword.create`, calling `form.requestSubmit()` on success and announcing the refusal otherwise, and register it with `Alpine.data` in `resources/js/app.js:19`
- [X] T021 [US1] Put the modal in front of the submit in `_change-password.blade.php`, `_remove-password.blade.php`, `_two-factor-on.blade.php` for both of its forms, `_passkey-row.blade.php` for the rename, and `_api-keys.blade.php` for both of its forms, each skipped when `$viewModel->passwordIsConfirmed()` is true. `_two-factor-off.blade.php` is a link, so the middleware's redirect already resumes it
- [X] T022 [US1] Put the modal in front of the address form and the account deletion in `resources/views/app/settings/account/profile/index.blade.php`
- [X] T023 [P] [US1] Add `it_refuses_without_a_confirmed_password` and `it_accepts_with_one` to each of `PasswordControllerTest.php`, `TwoFactorControllerTest.php`, `RecoveryCodeControllerTest.php` and `ApiKeyControllerTest.php` under `tests/Feature/Controllers/App/Settings/Account/Security/`
- [X] T024 [P] [US1] Add the same pair for `settings.account.destroy` in `tests/Feature/Controllers/App/Settings/Account/AccountControllerTest.php`, and assert the account survives the refusal
- [X] T025 [US1] Add `it_refuses_an_address_change_without_a_confirmed_password` and `it_still_saves_the_names_without_one` to `tests/Feature/Controllers/App/Settings/Account/Profile/ProfileControllerTest.php`, and create `EmailControllerTest.php` beside it for the new route
- [X] T026 [P] [US1] Create `tests/Feature/Views/Components/Modal/ConfirmPasswordTest.php` asserting the dialog role, the label and the field, the way `tests/Feature/Views/Components/` tests the others. This is as far as the suite reaches: FR-009's resumption is browser behaviour and is checked by hand at T050

**Checkpoint**: `php artisan route:list --path=settings/account` shows `password.confirm` on twelve routes and on neither `settings.security.index` nor `settings.profile.update`, and the suite is green.

---

## Phase 4: User Story 2 - A passkey holder proves it with the passkey (Priority: P1)

**Goal**: an account with no password proves itself with a passkey, on the screen and in the modal, and passkey management joins the gated routes now that it can be satisfied.

**Independent Test**: remove the password, sign out, sign in with the passkey, then rename a passkey. The prompt offers the passkey and no password field, and satisfying it carries the rename out.

- [X] T027 [US2] Add the passkey button to `resources/views/app/auth/confirm-password.blade.php`, driven by a `Passkeys.verify({ routes: { options: '/passkeys/confirm/options', submit: '/passkeys/confirm' } })` call, shaped like the block at `resources/views/app/auth/login.blade.php:64-93`
- [X] T028 [US2] Add the same button and the same route override to `resources/views/components/modal/confirm-password.blade.php` and `resources/js/confirm-password.js`, submitting the held form on success
- [X] T029 [US2] Branch `app/Listeners/HandlePasskeyLogin.php:16` on `Auth::guard('web')->check()`: when somebody is already signed in, log `PasskeyConfirmed` and return, leaving `last_login_at`, `CheckLastLogin` and `NewLoginDetected` to the sign in path alone
- [X] T030 [US2] Set `'management_middleware' => ['password.confirm:auth.confirmPassword.new']` at `config/passkeys.php:100` and rewrite the comment above it, which today gives the opposite instruction and the reason it no longer holds
- [X] T031 [P] [US2] Add `it_records_a_confirmation_rather_than_a_sign_in` to `tests/Unit/Listeners/HandlePasskeyLoginTest.php`: signed in, the event logs `PasskeyConfirmed`, leaves `last_login_at` alone and sends nothing
- [X] T032 [P] [US2] Add `it_refuses_passkey_management_without_a_confirmed_password` and `it_accepts_with_one` to `tests/Feature/Controllers/App/Settings/Account/Security/SecurityControllerTest.php`, covering `passkey.store` and `passkey.destroy`
- [X] T033 [US2] Add `it_offers_the_passkey_alone_to_a_passwordless_account` and `it_explains_when_the_account_holds_neither_credential` to `tests/Feature/Controllers/App/Auth/ConfirmPasswordControllerTest.php`

**Checkpoint**: a passwordless account reaches every sensitive action, and `php artisan route:list --path=passkeys` shows the gate on the three management routes.

---

## Phase 5: User Story 3 - One proof covers a short run of changes (Priority: P2)

**Goal**: fifteen minutes, one prompt, and the proof ends with the session or with the credential it was given for.

**Independent Test**: prove once, create an API key and delete it with no second prompt, then travel past fifteen minutes and watch the next one ask.

- [X] T034 [US3] Change `password_timeout` from 10800 to 900 in `config/auth.php:112`, and say in the comment above it why fifteen minutes rather than the framework's three hours
- [X] T035 [P] [US3] Call `$request->session()->forget('auth.password_confirmed_at')` after the removal and after a change in `app/Http/Controllers/App/Settings/Account/Security/PasswordController.php`. FR-006 clears on any credential changing, not on the one the proof was given with, so no task records which was used
- [X] T036 [P] [US3] Do the same in `app/Http/Responses/PasskeyDeletedResponse.php`, so deleting a passkey ends a proof given with one
- [X] T037 [US3] Add `it_covers_a_second_action_inside_the_window`, `it_asks_again_once_the_window_has_passed` using `$this->travel(16)->minutes()`, and `it_forgets_the_proof_on_sign_out` to `tests/Feature/Controllers/App/Auth/ConfirmPasswordControllerTest.php`
- [X] T038 [P] [US3] Add `it_forgets_the_proof_when_the_password_goes` to `PasswordControllerTest.php` and `it_forgets_the_proof_when_a_passkey_goes` to `SecurityControllerTest.php`

**Checkpoint**: `php artisan config:show auth.password_timeout` prints 900, and the three window tests pass.

---

## Phase 6: The recovery codes are shown once (FR-013)

**Purpose**: close the read the gate does not cover. The codes are printed on the security screen today, so a taken over session copies them without giving anything, and each one signs in once.

**Independent Test**: turn two factor authentication on, read the codes on the screen that follows, go back to the security screen and find a count and no code. Ask for new ones and land on the same screen with the new set.

- [X] T039 Add `GET settings/account/security/recovery-codes` as `settings.recoveryCodes.show` in `routes/web.php` beside `settings.recoveryCodes.create`, outside the gate, with a comment saying the flashed codes are what make it worth reaching
- [X] T040 [P] Create `app/ViewModels/Settings/Account/Security/RecoveryCodesViewModel.php` reading the codes out of the flashed session data and answering whether there are any
- [X] T041 Create `resources/views/app/settings/account/security/recovery-codes.blade.php` holding the list and the copy block moved verbatim from `_two-factor-on.blade.php:49-89`, the warning that this is the only time they are shown, and the way back to the security screen
- [X] T042 Add `show()` to `app/Http/Controllers/App/Settings/Account/Security/RecoveryCodeController.php` redirecting to `settings.security.index` when nothing is flashed, and make `create()` redirect to it with the codes flashed instead of to the security index
- [X] T043 Make `create()` in `app/Http/Controllers/App/Settings/Account/Security/TwoFactorController.php:33` redirect to `settings.recoveryCodes.show` with the codes flashed, keeping its status message
- [X] T044 Replace `recoveryCodes()` with `recoveryCodesRemaining(): int` in `app/ViewModels/Settings/Account/Security/SecurityViewModel.php:37`, and cut the code list and the copy block from `resources/views/app/settings/account/security/_two-factor-on.blade.php:49-89`, leaving a row that says how many are left with `trans_choice()` and keeps the spent warning
- [X] T045 [P] Replace `it_shows_the_recovery_codes_when_two_factor_authentication_is_on`, `it_offers_to_copy_the_recovery_codes` and `it_does_not_offer_to_copy_recovery_codes_that_are_all_spent` in `tests/Feature/Controllers/App/Settings/Account/Security/SecurityControllerTest.php:112-155` with `it_shows_no_recovery_code` and `it_says_how_many_recovery_codes_are_left`
- [X] T046 Add `it_shows_the_codes_once_after_regenerating` and `it_shows_nothing_on_a_second_visit` to `RecoveryCodeControllerTest.php`, and `it_shows_the_codes_once_after_enrolment` to `TwoFactorControllerTest.php`, both under `tests/Feature/Controllers/App/Settings/Account/Security/`

**Checkpoint**: the codes appear in no response to `GET settings/account/security`, and appear once in the response that follows their creation.

---

## Phase 7: Polish

- [X] T047 Run `php artisan monica:localize` and translate everything it reports, in every configured locale, following `.claude/rules/translations.md` for the typography of each
- [X] T048 Run `vendor/bin/pint --dirty --format agent` and `vendor/bin/phpstan analyse`
- [X] T049 Run `bun run build`, then `php artisan test --compact` in full, and let CI run it on SQLite, MySQL and PostgreSQL
- [X] T050 Walk [quickstart.md](quickstart.md) by hand, the accessibility section included: keyboard alone, screen reader, 200% zoom
- [X] T051 Say in the pull request why confirming a password has no API counterpart, why `config/passkeys.php` now says the opposite of what it said

---

## Dependencies

- Phase 1 comes first. Phase 2 blocks every story phase.
- Phase 3 and Phase 4 are both P1 and ship together, but Phase 3 stands alone for an account with a password.
- Phase 4 depends on Phase 3 for the modal it adds the passkey button to, and T030 depends on T027 and T028: gating passkey management before a passkey can prove would shut a passwordless account out of adding one.
- Phase 5 depends on Phase 3. Phase 6 needs only Phase 1: it closes a read rather than an action, and touches nothing the gate touches. Phase 7 depends on everything.
- T003 blocks T004 and T029. T005 blocks T007. T006 blocks T008 and T012. T004, T006 and T008 block T013 onwards.
- T013 blocks T014, which blocks T015 and T025. T018 blocks T021 and T022. T019 blocks T020, which blocks T021, T022 and T028.
- T012, T033 and T037 all edit `ConfirmPasswordControllerTest.php`, and T023 and T038 both edit `PasswordControllerTest.php`. Each group runs in order.

## Parallel

- T004 with T005, then T010 with T011.
- T018, T019 and T026 together.
- T023, T024 and T026 together once T021 and T022 are done.
- T031 with T032. T035 with T036. T040 with T045.

## MVP

Phase 1, Phase 2, Phase 3. Every sensitive action then costs a password, which is the whole of User Story 1 and closes the takeover for every account that has one. A passwordless account is not yet served, so Phase 4 goes in the same pull request.
