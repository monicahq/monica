# Implementation Plan: Prove a credential before a sensitive action

**Branch**: `2026-09-11-sensitive-action-verification` | **Date**: 2026-09-11 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `/specs/027-sensitive-action-verification/spec.md`

## Summary

Put Laravel's `password.confirm` middleware in front of the twelve sensitive routes, pointed at a screen of our own that offers whichever credential the account holds. The password path is a controller and an action; the passkey path is the passkey package's confirmation endpoints, which already write the same session fact. Shorten the window to fifteen minutes, fill in the `management_middleware` the project deliberately left empty, and put a modal in front of each sensitive control so the proof is given before the form is sent rather than after it is lost. Separately, the recovery codes stop being printed on the security screen: they are shown once, on a screen of their own that follows their creation. Decisions are in [research.md](research.md).

## Technical Context

**Language/Version**: PHP 8.4, Laravel 13.

**Primary Dependencies**: none added. `laravel/passkeys` supplies `passkeys/confirm`, and `@laravel/passkeys` supplies the browser side through `Passkeys.verify({ routes })`. `@alpinejs/focus` is installed, so the modal traps focus the way the two existing ones do.

**Storage**: no migration, no column. The proof is `auth.password_confirmed_at` in the session and nothing else.

**Testing**: PHPUnit. One new feature test for the screen, one unit test for the action and one for the view model, one view test for the modal, and the five existing security controller tests plus `ProfileControllerTest` gain the refusal. `HandlePasskeyLoginTest` gains the branch.

**Target Platform**: the signed in web application. FR-010 leaves the JSON API alone.

**Constraints**: `Redirector::guest()` sets the intended URL to the previous page for anything that is not a GET, so the middleware's redirect cannot carry a payload back. That is why the modal exists. The profile form saves the names and the address together, and FR-002 makes only the address sensitive, so the address moves to a route of its own rather than the gate spreading to a change of name. `PasskeyVerified` fires for a sign in and for a confirmation alike, so `HandlePasskeyLogin` has to tell them apart or a confirmation emails "new login detected".

**Scale/Scope**: twelve routes gated, two routes added, one modal, one Alpine component, two enum cases, one config value changed, one config array filled.

## Constitution Check

*GATE: passes before Phase 0 and after Phase 1.*

| Principle | How this feature satisfies it |
| --- | --- |
| I Actions hold the business logic | `ConfirmPassword` checks the credential and writes the log. The controller marks the session and redirects, which is transport. No existing action changes. |
| II Simplicity over abstraction | No middleware class, no session key, no table and no service. The gate is the framework's, the passkey ceremony is the package's, and the modal is a third copy of a pattern that already has two. |
| III Tested before merged | Every gated route refuses without a proof and accepts with one, a wrong credential is refused and rate limited, a passwordless account is offered the passkey alone, an account with neither is told what to do, the window expires, and signing out clears it. FR-009 is the one exception: resuming the action lives in the browser, the suite holds no browser test, and it is checked by hand against quickstart step 1. The pull request says so. |
| IV Laravel conventions | `password.confirm` is the framework's own alias. Route names stay camelCase, `env()` stays in `config/`, and the two new routes sit in `routes/auth.php` beside the other credential screens. |
| V Localized and safe | The screen and the modal are new copy, so `php artisan monica:localize` runs and every configured locale is translated. |
| VI Nothing changes under anybody's feet | Sessions open when this ships hold no proof, so the first sensitive action asks, which is the safe direction. FR-013 does take the codes off a screen somebody may have been reading them from, so that screen says how many are left and offers new ones, and nothing becomes unrecoverable. |
| VIII Nothing disappears silently | An account that cannot prove anything is told so and pointed at the password reset email, rather than meeting a refusal with no way past it. |
| IX Accessible to everybody | The modal copies `modal/warning`: `role="dialog"`, `aria-modal`, `x-trap.noscroll.inert`, escape to close, focus returned. The refusal is announced through `aria-live`, and no state is carried by colour. |
| X Everything goes through the API | FR-010 keeps the API exactly as it is. The step is a property of the interactive session, and no API request is refused for want of a proof. |
| XI The written record is short | `data-model.md` omitted: no table, no column, no model. The session fact and the routes are one contract file. |
| Product invariants | Monica is mono-user, and a proof is held against one session of one account. Nothing here reaches another account. |

## Project Structure

### Documentation (this feature)

```text
specs/027-sensitive-action-verification/
├── plan.md
├── research.md
├── contracts/confirm-password.md
├── quickstart.md
└── checklists/requirements.md
```

`data-model.md` is omitted: nothing is persisted, and the spec's Key Entities already name the three things involved.

### Source Code (repository root)

```text
config/auth.php, config/passkeys.php                 # password_timeout 900, management_middleware filled
routes/auth.php, routes/web.php                      # the two new routes, the gate on the twelve, recoveryCodes.show
app/Http/Controllers/App/Auth/ConfirmPasswordController.php # new: the screen and the password path
app/Actions/ConfirmPassword.php                      # new: checks the password, writes the log
app/ViewModels/Auth/ConfirmPasswordViewModel.php     # new: which credentials the account can offer
app/Enums/UserActionEnum.php                         # three cases, one per outcome
app/Listeners/HandlePasskeyLogin.php                 # a confirmation is not a sign in
app/Providers/AppServiceProvider.php                 # the named limiter
app/Http/Responses/PasskeyDeletedResponse.php, .../Security/PasswordController.php  # forget the proof
app/Http/Controllers/App/Settings/Account/Profile/EmailController.php       # new: the address on its own route
app/Http/Controllers/App/Settings/Account/Security/RecoveryCodeController.php, TwoFactorController.php  # flash the codes once
app/ViewModels/.../Security/RecoveryCodesViewModel.php                     # new: the codes held in the flash
app/ViewModels/.../Security/SecurityViewModel.php, .../Profile/ProfileViewModel.php  # passwordIsConfirmed(), recoveryCodesRemaining()
resources/views/app/auth/confirm-password.blade.php  # new: the screen the gate redirects to
resources/views/components/modal/confirm-password.blade.php                # new: the prompt in place
resources/views/app/settings/account/security/recovery-codes.blade.php     # new: the codes, once
resources/views/app/settings/account/security/*.blade.php                  # the modal on each control, the codes gone from _two-factor-on
resources/views/app/settings/account/profile/index.blade.php               # the address split out, account deletion
resources/js/confirm-password.js, resources/js/app.js  # new component, registered
lang/*.json                                          # written by monica:localize
tests/Feature/Controllers/App/Auth/ConfirmPasswordControllerTest.php, tests/Unit/Actions/ConfirmPasswordTest.php  # new
tests/Unit/ViewModels/Auth/ConfirmPasswordViewModelTest.php, tests/Feature/Views/Components/Modal/ConfirmPasswordTest.php  # new
tests/Feature/Controllers/App/Settings/Account/Security/*.php              # the five gain the refusal, two gain the codes
tests/Feature/Controllers/App/Settings/Account/Profile/ProfileControllerTest.php
tests/Unit/Listeners/HandlePasskeyLoginTest.php      # the branch
```

**Structure Decision**: nothing outside a folder that exists. The screen goes with the other credential screens under `resources/views/app/auth`, and the modal joins the two already in `resources/views/components/modal`.

## Complexity Tracking

| Violation | Why Needed | Simpler Alternative Rejected Because |
|-----------|------------|-------------------------------------|
| A modal in front of every sensitive control, rather than the middleware alone | FR-009: the action has to be carried out without a second press, and the framework's redirect loses a non-GET payload | Redirecting with `withInput()` asks for the second press. Stashing and replaying the request holds an arbitrary payload in the session |
| A route name carrying a hyphen, `auth.confirmPassword.new`, where code-style.md asks for camelCase and all 186 existing route names comply | Asked for directly, and it reads the way the framework's own `password.confirm` does | `auth.confirmPassword.new` keeps the rule, and was not what was asked |
| Reversing a decision `config/passkeys.php` states in a comment | That comment is this feature's reason to exist: it was emptied because a passwordless account cannot confirm a password | Leaving passkey registration ungated is the hole the feature closes, since a hijacked session would add its own passkey |
