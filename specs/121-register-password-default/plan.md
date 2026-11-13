# Implementation Plan: Password as the default registration method

**Branch**: `2026-11-12-register-password-default` | **Date**: 2026-11-13 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `specs/121-register-password-default/spec.md`

## Summary

Flip the default of one method in `RegisterViewModel` and rearrange the registration view around it:
the password parts become what is rendered without scripts, the passkey parts and the control offering
them become what Alpine reveals, only in a browser that can create a passkey. The warning shown over a
connection that is not secure is deleted. Reasons are in [research.md](research.md).

## Technical Context

**Language/Version**: PHP 8.5, Laravel (installed major), Blade, Tailwind CSS 4, Alpine.js

**Primary Dependencies and Storage**: none added, none changed

**Testing**: PHPUnit, in the two files below. The browser check and the screen with scripts off need a
browser, which the suite does not have, and are checked by hand in [quickstart.md](quickstart.md)

**Constraints**: WCAG 2.2 AA; right to left; the form works with scripts off; no class assertions in tests

**Scale/Scope**: one view, one view model, two test files, two translation keys dropped from every locale

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

| Principle | Check | Result |
| --- | --- | --- |
| I. Actions hold the business logic | `CreateAccount` and both controllers are untouched | Pass |
| II. Simplicity | One ternary flipped, one inline expression, no component and no helper added | Pass |
| III. Tested before merged | View model test rewritten, screen test gains the copy it has to show | Pass |
| IV. Laravel conventions | No new file under `app/` | Pass |
| V. Localized and safe | No new string. Two keys leave through `monica:localize`. Nothing destructive | Pass |
| VI. Nothing changes under anybody's feet | Existing accounts and their credentials are not touched | Pass |
| VII and VIII | Not touched | Pass |
| IX. Accessible to everybody | Hidden fields are `display: none`; the control is a button with a visible focus ring and a name stating what it does. Confirmed by the accessibility review in the tasks | Pass |
| X. Everything goes through the API | Registration through the API is password only and stays so | Pass |
| XI. The written record is short | Every artifact under its cap. `data-model.md` and `contracts/` omitted: no data, and the spec carries the screen's contract | Pass |
| Flags | A changed default on a shipped screen, not a capability being built. No flag | Pass |

Re-checked after Phase 1: unchanged.

## Project Structure

Documents of this feature: `plan.md`, `research.md`, `quickstart.md`, then `tasks.md` from `/speckit-tasks`.

```text
app/ViewModels/Auth/RegisterViewModel.php       # registrationMethod() flipped; passkeysUnavailableReason() deleted
resources/views/app/auth/register.blade.php     # see Design notes
lang/*.json                                     # via php artisan monica:localize, two keys dropped

tests/Unit/ViewModels/Auth/RegisterViewModelTest.php
    # opens on the password over a secure connection; keeps the passkey after a refused passkey attempt;
    # answers the password over a connection that is not secure, even when the attempt named the passkey
tests/Feature/Controllers/App/Auth/RegistrationControllerTest.php
    # over a secure connection the screen holds "Use a passkey instead"; otherwise none of the passkey copy
```

**Structure Decision**: everything stays where it is. Both registration controllers, the API's one,
`register-passkey.blade.php` and `resources/js/passkey.js` are read, not edited, and their tests stay green.

## Design notes

- **View model**: `registrationMethod()` answers `'passkey'` only when passkeys are available on the
  instance and the flashed `registration_method` is `'passkey'`. Everything else is `'password'`.
- **Alpine state**: the screen's `x-data` gains
  `supported: typeof globalThis.PublicKeyCredential === 'function'`, and sets `mode = 'password'`
  when it is false.
- **Password parts** (the two fields, their requirements line, the "Next step" label): no `x-cloak`,
  `required` in the markup, `x-bind:required` and `x-show` kept.
- **Passkey parts** (the explaining sentence, the "Create a passkey" label): `x-cloak` and `x-show`.
- **Control**: an `x-button.secondary type="button"`, full width, inside
  `@if ($viewModel->passkeysAreAvailable())`, with `x-cloak` and `x-show="supported"`. Its label is two
  spans, "Use a passkey instead" and "Set a password instead", one shown per mode. It keeps focus.
- **Refused passkey attempt**: the view reads `registrationMethod()` once and puts `x-cloak` on the
  password parts instead of the passkey parts, so the screen does not flash the wrong method.
- **Deleted**: the `@unless ($viewModel->passkeysAreAvailable())` notice and the "Other options" text.
- **Unchanged in the view**: the form's static `action`, the hidden `registration_method` input, the
  other fields, the terms checkbox, Turnstile and the footnote.

## Risks

- `x-input` has to render `required` and accept `x-bind:required` together. It already forwards the
  binding. If the pair misbehaves, fall back to `required` alone with `x-bind:disabled` when hidden.
- A browser can hold `PublicKeyCredential` and still have no authenticator. That case keeps reaching
  the step after the passkey method, which already handles it.

## Complexity Tracking

None.
