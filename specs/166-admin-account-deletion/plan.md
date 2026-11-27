# Implementation Plan: Account deletion from the admin space

**Branch**: `2026-11-27-admin-account-deletion` | **Date**: 2026-11-27 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `specs/166-admin-account-deletion/spec.md`

## Summary

A red panel under the deactivation panel of `/admin/accounts/{account}` opens a modal that asks for
the person's address (or the account number), then deletes the account through
`DestroyAccountAsAdministrator`. That action calls the existing `DestroyAccount`, which this change
also completes: it now erases the vault files on disk and the sessions on other devices, and copes
with an account whose person is deleted or missing. Decisions are in [research.md](research.md). There
is no feature flag, no table and no API.

## Technical Context

**Language/Version**: PHP 8.5, Laravel 13
**Primary Dependencies**: Cashier (subscription stop, unchanged), Blade, Alpine.js; no new dependency
**Storage**: no migration; one enum case; files removed from every disk the account used (R2)
**Testing**: PHPUnit (`tests/Unit/Actions`, `tests/Unit/ViewModels/Admin`, `tests/Feature/Controllers/App/Admin`)
**Target Platform**: web application, admin space
**Project Type**: web application (Laravel monolith)
**Performance Goals**: synchronous deletion, as the settings deletion already is
**Constraints**: no vault content shown in the admin space (`NoVaultContentTest`); the panel names counts, never vaults
**Scale/Scope**: 1 new action, 1 changed action, 1 controller method, 1 route, 1 view model addition, 1 partial

## Constitution Check

| Principle | Status |
| --- | --- |
| I. Actions hold the business logic | Pass. `DestroyAccountAsAdministrator` and `DestroyAccount`; the controller validates the confirmation and delegates |
| II. Simplicity | Pass. One erasure for both callers (R1); the existing exception is reused (R3) |
| III. Tested before merged | Pass. Tests below |
| IV. Laravel conventions | Pass. `destroy` on the existing `AccountController`, inline validation, named route |
| V. Localized and safe | Pass. Typed confirmation in a modal; strings through `monica:localize` |
| VI. Nothing changes under anybody's feet | Pass. The settings deletion keeps its screen and wording; it now removes more, which its warning already promised |
| VII. Stable core | Pass. Nothing about records or templates |
| VIII. Nothing disappears silently | Pass. The deletion is asked for, confirmed, said in a flash message and written in the administrator log |
| IX. Accessible | Pass. `x-delete-section` and `x-modal.form`; the refused button follows the deactivation panel |
| X. Everything through the API | Deviation, justified in the spec: account administration has no API today, and an irreversible deletion over a token was not asked for |
| XI. Short record | Pass. No data model, contract or quickstart: nothing new to store, one route, and the tests are the walkthrough |

Re-checked after Phase 1: unchanged.

## Project Structure

### Documentation (this feature)

```text
specs/166-admin-account-deletion/
├── spec.md
├── plan.md
├── research.md
└── tasks.md             # /speckit-tasks
```

### Source Code (repository root)

```text
app/Actions/DestroyAccount.php                          # takes the account only; account directory on every disk, sessions (R1, R2)
app/Actions/DestroyAccountAsAdministrator.php           # new: refuses one's own account, erases, logs (R1, R3, R4)
app/Enums/AdministratorActionEnum.php                   # AccountDestroyed: description, icon, namesTheAccountHolder false (R4)
app/Http/Controllers/App/Admin/AccountController.php    # destroy: confirmation, both refusals as form errors, redirect to the list (R5)
app/Http/Controllers/App/Settings/Account/AccountController.php  # new DestroyAccount signature
app/ViewModels/Admin/AccountViewModel.php               # destroyUrl(), deletionConfirmation(): address or account number
routes/administrator.php                                # DELETE admin/accounts/{account} → admin.accounts.destroy
resources/views/app/admin/accounts/show.blade.php       # include the partial below the deactivation panel, outside isActionable()
resources/views/app/admin/accounts/_delete.blade.php    # new: x-delete-section, x-modal.form, refused button for oneself
changelog/2026-11-27-<ulid>-account-deletion-files.md   # self-service deletion now removes files and sessions (R6)
lang/*.json                                             # through monica:localize, Sonnet subagents
```

**Tests**:

| File | Proves |
| --- | --- |
| `tests/Unit/Actions/DestroyAccountTest.php` | vault files gone from every disk and another account's kept; session rows gone; an account with a deleted person or none |
| `tests/Unit/Actions/DestroyAccountAsAdministratorTest.php` (new) | erases and pushes the log entry with the account number; refuses one's own account; Stripe unreachable deletes nothing |
| `tests/Unit/ViewModels/Admin/AccountViewModelTest.php` | confirmation is the address, or the number without a person |
| `tests/Feature/Controllers/App/Admin/AccountControllerTest.php` | redirect and flash; wrong confirmation refused; one action refusal reaches the form |
| `AdminAccessTest`, `tests/Feature/Auth/PasskeyAccountDeletionTest.php` | new URL in the provider; the new signature |

## Complexity Tracking

| Addition | Why | Simpler alternative rejected |
| --- | --- | --- |
| A second action around `DestroyAccount` | The self check and the administrator log belong to the admin path only | One action with an optional administrator (R1) |
