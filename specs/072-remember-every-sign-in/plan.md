# Implementation Plan: Remembering Every Sign In

**Branch**: `072-remember-every-sign-in` (git branch to create: `2026-10-08-remember-every-sign-in`) | **Date**: 2026-10-08 | **Spec**: [spec.md](./spec.md)

**Input**: Feature specification from `/specs/072-remember-every-sign-in/spec.md`

## Summary

Every way in signs somebody in with `remember: true`, the password form excepted, whose box is now
ticked by default. Three framework gaps are closed on the way: an account without a password cannot
be recalled at all (research D1), the duration is set on one path only (D2), and a password change
leaves other open sessions running (D5). The decisions and the code they rest on are in
[research.md](./research.md).

## Technical Context

**Language/Version**: PHP 8.4, Laravel 13.34
**Primary Dependencies**: `laravel/passkeys` (server and `@laravel/passkeys` script), Socialite, Alpine.js
**Storage**: existing `users.remember_token` and `sessions`; no migration
**Testing**: PHPUnit, feature and unit, per `.claude/rules/testing.md`
**Target Platform**: Linux server, hosted and self hosted alike, any session driver
**Project Type**: web application with a JSON API (the API is untouched)
**Performance Goals**: none beyond today; `AuthenticateSession` adds one hash comparison per request
**Constraints**: no new setting, no new copy, no new class
**Scale/Scope**: about 12 application files and their tests

## Constitution Check

| Principle | Verdict |
| --- | --- |
| I. Actions hold the business logic | Pass. `login()` stays in the controllers, where every sign in has it today; no action gains guard logic (D3) |
| II. Simplicity | Pass. A named argument per way in, one model override, one config line, one middleware |
| III. Tested before merged | Pass. Each way in, the override, the duration and the revocation get a test (below) |
| IV. Laravel conventions | Pass. `AuthenticateSession` and the guard's `remember` key are the documented mechanisms. The `getAuthPassword()` override is a deviation, justified in D1 |
| V. Localized and safe | Pass. No new string; the box keeps its wording |
| VI. Nothing changes under anybody's feet | Pass. A cookie already issued keeps its end date |
| VIII. Nothing disappears silently | Pass. Nothing is deleted |
| IX. Accessible | Pass. The one touched control is an existing checkbox changing only its initial state |
| X. Everything through the API | Pass. Token sign in has no cookie to remember (FR-013) |
| XI. Short record | Pass. `data-model.md` and `contracts/` omitted: no new data, no interface change |

Re-checked after Phase 1: unchanged.

## Project Structure

### Documentation (this feature)

```text
specs/072-remember-every-sign-in/
├── plan.md
├── research.md
├── quickstart.md
└── tasks.md            # /speckit-tasks
```

### Source Code (repository root)

```text
app/Models/User.php                                          # getAuthPassword() override (D1)
app/Providers/AppServiceProvider.php                         # auth.guards.web.remember (D2)
app/Actions/AttemptLogin.php                                 # setRememberDuration() removed (D2)
bootstrap/app.php                                            # AuthenticateSession on web (D5)
app/Http/Controllers/App/Auth/MagicLinkController.php        # remember: true (D3)
app/Http/Controllers/App/Auth/SsoController.php              # remember: true (D3)
app/Http/Controllers/App/Auth/RegistrationController.php     # remember: true (D3)
app/Http/Controllers/App/Auth/RegistrationPasskeyController.php
app/Http/Controllers/App/Settings/Account/Security/PasswordController.php  # re-issue cookie (D6)
resources/js/passkey.js                                      # verify({ remember: true }) (D3)
resources/views/app/auth/login.blade.php                     # box ticked by default (D4)
```

Tests, one rule each in the layer that owns it:

- `tests/Unit/Models/UserTest.php`: `getAuthPassword()` returns `''` without a password, the hash with one.
- `tests/Unit/Providers/AppServiceProviderTest.php`: the guard's duration follows the stored instance setting.
- `tests/Unit/Actions/AttemptLoginTest.php`: unchanged; still proves the token is written only when asked.
- `tests/Feature/Controllers/App/Auth/`: `MagicLinkControllerTest`, `SsoControllerTest`,
  `RegistrationControllerTest`, `RegistrationPasskeyControllerTest` each assert the recaller cookie, its
  expiry and `twoFactor.remember` where it applies; `TwoFactorChallengeControllerTest` asserts the
  cookie lasts the configured duration; `LoginControllerTest` asserts the box is ticked on arrival and unticked
  after a failed submit without it, and that a passwordless account with a recaller cookie is signed in.
- `tests/Feature/Controllers/App/Settings/Account/Security/PasswordControllerTest.php`: a session
  holding the old fingerprint is sent to sign in after a change; the changing request gets a fresh
  recaller cookie when it had one.
- The passkey request is client side and has no PHPUnit layer: [quickstart.md](./quickstart.md) step 2.

**Structure Decision**: the existing Laravel layout; no new directory and no new class.

## Complexity Tracking

| Violation | Why Needed | Simpler Alternative Rejected Because |
| --- | --- | --- |
| `User::getAuthPassword()` overrides the framework's answer for a null password | Without it no account made through a passkey or a provider can ever be recalled, which is most of FR-001 to FR-005 | A custom guard replaces a framework class; a secret column adds a migration and a second credential (D1) |
