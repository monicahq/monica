# Implementation Plan: Security keys as a second factor

**Branch**: `2026-10-17-passkey-second-factor` | **Date**: 2026-10-18 | **Spec**: [spec.md](spec.md)

## Summary

Security keys get a table, a model and an enrolment of their own, built on the package's WebAuthn validators with resident key and user verification relaxed (research R1, R2). The two-factor challenge accepts a key or a passkey through application actions that dispatch no package event, on the challenge's own route and throttle (R3, R6), and opens the prompt as it loads (R7). `usesTwoFactorAuthentication()` counts keys, which carries FR-007 to every caller; the API sign-in is the one place reading the column directly (R4).

## Technical Context

**Language/Version**: PHP 8.5, Laravel 13

**Primary Dependencies**: `laravel/passkeys` v0.2.1 (its `Support\WebAuthn` validators and `GenerateRegistrationOptions`), `@laravel/passkeys` with route overrides, `pragmarx/google2fa-laravel` unchanged. No new package.

**Storage**: one new table, `security_keys` (data-model.md). `users` and `passkeys` unchanged.

**Testing**: PHPUnit. Action tests for enrolment, rename, removal and verification, including another person's key, a passkey at the challenge and the first and last factor; controller tests for the routes in contracts/routes.md; view model tests for the warning sentence and the list.

**Target Platform**: the web application, desktop to phone, over HTTPS; keys are absent on an insecure connection.

**Project Type**: web application (Laravel monolith).

**Performance Goals**: the challenge reads two short lists of the person's credentials; no other query is added to sign-in.

**Constraints**: WCAG 2.2 AA; every configured locale; SQLite, MySQL and PostgreSQL; the API surface is unchanged apart from R4's fix.

**Scale/Scope**: one migration, one model, six actions, two controllers, one rewritten section of the security screen, the challenge screen, two notifications, four journal entries, about twenty strings.

## Constitution Check

| Principle | Status |
| --- | --- |
| I. Actions hold the logic | `GenerateSecurityKeyOptions`, `CreateSecurityKey`, `UpdateSecurityKey`, `DestroySecurityKey`, `GenerateSecondFactorOptions`, `VerifySecondFactorKey`. Controllers validate and delegate. |
| II. Simplicity | One table beside `passkeys` instead of a filter on every package lookup (R1). One challenge POST instead of two copies of the sign-in completion (R6). |
| III. Tested | Every action, both controllers, `SecurityKey`, the two notifications and the touched view models. `RouteProtectionTest` lists `SecurityKey` with `Passkey` as scoped by its own binding. |
| IV. Laravel conventions | `foreignIdFor(User::class)->constrained()`, `up()` only, one named route per line, inline validation. |
| V. Localized, warned | Strings through `monica:localize`, a glossary row for "security key", `x-delete-action` before removal with the sentence FR-009 asks for. |
| VI. Nothing changes under anybody's feet | Adding a factor keeps the recovery codes somebody saved (R5). |
| VII, VIII | Not touched. Removing a key is the owner's explicit act, warned first. |
| IX. Accessible | A live region announcing the prompt opening and every outcome, a button to open it again, keyboard reachable list and rename (R7, R8). |
| X. API | Deliberately absent: no endpoint lists or manages keys, and the API sign-in takes a code or a recovery code (spec FR-019, Assumptions). |
| XI. Short record | No quickstart: SC-002's hardware matrix is the only manual check, and it is in the spec. |
| Self hosted equals hosted | Identical; no attestation list to maintain. |

Gate: passes before and after design.

## Project Structure

```text
database/migrations/2026_10_18_000000_create_security_keys_table.php     new
database/factories/SecurityKeyFactory.php                               new
app/Models/SecurityKey.php                                              new; binding scoped to Auth::id() like Passkey
app/Models/User.php                                                     securityKeys(), usesAuthenticatorApp(), usesTwoFactorAuthentication() counts keys
app/Actions/{GenerateSecurityKeyOptions,CreateSecurityKey,UpdateSecurityKey,DestroySecurityKey}.php        new
app/Actions/{GenerateSecondFactorOptions,VerifySecondFactorKey}.php                                    new
app/Actions/{ConfirmTwoFactorAuthentication,DisableTwoFactorAuthentication}.php                        codes follow first and last factor (R5)
app/Http/Controllers/App/Settings/Account/Security/{SecurityKeyController,SecurityKeyOptionsController}.php   new
app/Http/Controllers/App/Auth/TwoFactorKeyOptionsController.php         new
app/Http/Controllers/App/Auth/TwoFactorChallengeController.php          credential path, shared completion (R6)
app/Http/Controllers/App/Settings/Account/Security/TwoFactorController.php   keeps codes when a key exists
app/Http/Controllers/Api/Auth/LoginController.php                       passesSecondFactor() asks usesTwoFactorAuthentication()
app/ViewModels/Auth/TwoFactorViewModel.php                              which ways the person holds, prompt routes
app/ViewModels/Settings/Account/Security/SecurityViewModel.php          keys list, warning sentences
app/Notifications/{SecurityKeyAdded,SecurityKeyRemoved}.php             new
app/Enums/{UserActionEnum,EmailType}.php                                new cases
routes/web.php, routes/auth.php                                         contracts/routes.md
resources/js/passkey.js                                                 secondFactor(), auto-open, announcements (R7)
resources/views/app/auth/two-factor-challenge.blade.php                 key block above the code form
resources/views/app/settings/account/security/{index,_two-factor-on,_two-factor-off}.blade.php   three rows (R8)
resources/views/app/settings/account/security/{_security-keys,_security-key-row}.blade.php        new
specs/002-webauthn-auth/spec.md                                         FR-019 amended, FR-021 wording (spec FR-018)
spec/models/002-user-done.md                                            security keys and the meaning of two-factor on
.claude/rules/translations.md                                           glossary row "security key"
lang/*.json                                                             through monica:localize
tests/Unit/{Actions,Models,Notifications,ViewModels}/..., tests/Feature/Controllers/App/{Auth,Settings/Account/Security}/...   new and updated
```

## Order of work

1. Table, model, factory, `User` methods, and the API fix (R4), with their tests: nothing visible changes until a key exists.
2. Enrolment, rename, removal and their screen (story 2).
3. The challenge: options, verification, the controller path, the screen and the script (story 1).
4. Amendments to spec 002 and `spec/models`, the glossary row, `monica:localize`, Pint, PHPStan, the whole suite.

## Complexity Tracking

| Deviation | Why | Simpler alternative rejected |
| --- | --- | --- |
| A second credential table | FR-016 by construction (R1) | A `kind` column filtered on every package lookup |
| Reaching into the package's `Support\WebAuthn` | The validators are the WebAuthn implementation; writing one is worse | Asking the package for a second-factor mode it does not have |
| One POST answering two request shapes | The sign-in completion exists once (R6) | Two controllers each signing in |
