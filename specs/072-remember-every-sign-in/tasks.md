# Tasks: Remembering Every Sign In

**Input**: [plan.md](./plan.md), [spec.md](./spec.md), [research.md](./research.md), [quickstart.md](./quickstart.md)

**Tests**: included, because Principle III makes them a delivery condition. Test rules: `.claude/rules/testing.md`. Assert the cookie with `assertCookie(Auth::guard('web')->getRecallerName())` / `assertCookieMissing(...)`, as `TwoFactorChallengeControllerTest` does, and its expiry as within a minute of now plus `monica.remember_duration_days`.

## Phase 1: Foundational

- [X] T001 [P] In `tests/Unit/Models/UserTest.php`, prove `getAuthPassword()` returns `''` for a user whose `password` is null and the stored hash for one who has a password
- [X] T002 Override `getAuthPassword(): string` in `app/Models/User.php` with `#[Override]`, returning `$this->password ?? ''`, and a docblock saying the framework refuses to recall an account whose password is not a string (research D1)
- [X] T003 [P] In `tests/Unit/Providers/AppServiceProviderTest.php`, prove `config('auth.guards.web.remember')` and `Auth::guard('web')->getRememberDuration()` equal `monica.remember_duration_days` × 1440, and that the guard rebuilt after `$this->app['auth']->forgetGuards()` still does
- [X] T004 In `app/Providers/AppServiceProvider.php` `applyInstanceSettings()`, after `InstanceSettings::apply()`, set `auth.guards.web.remember` to `(int) config('monica.remember_duration_days') * 24 * 60`; delete the `setRememberDuration()` block and the `SessionGuard` import from `app/Actions/AttemptLogin.php` (research D2)

## Phase 2: User Story 1, every way in keeps somebody signed in (P1, MVP)

- [X] T005 [P] [US1] In `tests/Feature/Controllers/App/Auth/MagicLinkControllerTest.php`, prove opening a valid link sets the recaller cookie with the configured expiry, and for a user with two factor authentication puts `twoFactor.remember` to `true`
- [X] T006 [P] [US1] In `tests/Feature/Controllers/App/Auth/SsoControllerTest.php`, prove the callback sets the recaller cookie with the configured expiry for a connected user and for an account it creates, and puts `twoFactor.remember` to `true` for a user with two factor authentication
- [X] T007 [P] [US1] In `tests/Feature/Controllers/App/Auth/RegistrationControllerTest.php` and `RegistrationPasskeyControllerTest.php`, prove `auth.register.create` and `auth.registerPasskey.create` set the recaller cookie with the configured expiry
- [X] T008 [P] [US1] In `tests/Feature/Controllers/App/Auth/TwoFactorChallengeControllerTest.php`, prove a challenge started by a link by email ends with the recaller cookie with the configured expiry
- [X] T009 [P] [US1] Create `tests/Feature/Auth/PasswordlessRecallTest.php`: a user with a null `password` and a `remember_token`, holding only a cookie `"{$user->id}|{$token}|".Auth::guard('web')->hashPasswordForCookie('')` under the recaller name, reaches `home.index` signed in; a cookie with a wrong token does not
- [X] T010 [US1] In `app/Http/Controllers/App/Auth/MagicLinkController.php` `show()`, put `twoFactor.remember` to `true` and call `Auth::guard('web')->login($user, remember: true)`
- [X] T011 [US1] In `app/Http/Controllers/App/Auth/SsoController.php` `signIn()`, put `twoFactor.remember` to `true` and call `Auth::guard('web')->login($user, remember: true)`
- [X] T012 [US1] Call `Auth::login($user, remember: true)` in `app/Http/Controllers/App/Auth/RegistrationController.php` and `app/Http/Controllers/App/Auth/RegistrationPasskeyController.php`
- [X] T013 [US1] In `resources/js/passkey.js` `signIn()`, call `Passkeys.verify({ remember: true })`; leave the confirmation call with `config.routes` unchanged

## Phase 3: User Story 2, the password form ticks the box by default (P1)

- [X] T014 [P] [US2] In `tests/Feature/Controllers/App/Auth/LoginControllerTest.php`, prove `auth.login.new` renders the `remember` checkbox checked, and that after a failed `auth.login.create` sent without `remember` the screen renders it unchecked
- [X] T015 [US2] In `tests/Feature/Controllers/App/Auth/TwoFactorChallengeControllerTest.php`, prove a password sign in with `remember` that passes the challenge gets the recaller cookie with the configured expiry, not 400 days (FR-008)
- [X] T016 [US2] In `resources/views/app/auth/login.blade.php`, set the checkbox to `:checked="session()->hasOldInput() ? (bool) old('remember') : true"` (research D4)

## Phase 4: User Story 3, a password change signs out the other devices (P2)

- [X] T017 [P] [US3] Create `tests/Feature/Auth/OtherDeviceSignOutTest.php`: a signed in session holding the fingerprint of the old password is redirected to `auth.login.new` once the password is changed through `UpdateUserPassword`, and once it is reset through `ResetPassword`; a session holding the current fingerprint carries on
- [X] T018 [P] [US3] In `tests/Feature/Controllers/App/Settings/Account/Security/PasswordControllerTest.php`, prove `update`, `create` and `destroy` answer with a fresh recaller cookie with the configured expiry when the request carried one, and with none when it did not
- [X] T019 [US3] Append `Illuminate\Session\Middleware\AuthenticateSession::class` to the `web` group in `bootstrap/app.php`, after `SubstituteBindings` (research D5)
- [X] T020 [US3] In `app/Http/Controllers/App/Settings/Account/Security/PasswordController.php` `update()`, `create()` and `destroy()`, after the action, call `Auth::guard('web')->login($request->user(), remember: true)` when `$request->hasCookie(Auth::guard('web')->getRecallerName())` (research D6)

## Phase 5: Polish

- [X] T021 [P] In `docs/portal/ROADMAP.md`, update the `signin.password` entry (Remember me ticked by default) and the passkey, link by email and provider sign in entries (always remembered for the instance's duration)
- [X] T022 [P] Add the feature to `docs/FEATURES.md` through the `feature-documentator` skill
- [X] T023 Run `php artisan monica:localize --check` and confirm it passes with no new string
- [X] T024 Run the whole suite (the middleware sits on every web route), PHPStan and Pint as listed in `quickstart.md`, then walk its manual steps

## Dependencies

- T001 before T002, T003 before T004. Phase 1 before every story: T002 for T009 and the passwordless cases, T004 for every expiry assertion.
- Inside each story the tests come first: T005 to T009 before T010 to T013, T014 and T015 before T016, T017 and T018 before T019 and T020.
- US1, US2 and US3 touch different application files and depend on nothing in each other. T008 and T015 both edit `TwoFactorChallengeControllerTest.php`: run them one after the other.
- Everything before T024. MVP: Phases 1 and 2.
