---

description: "Task list for the password as the default registration method"
---

# Tasks: Password as the default registration method

**Input**: Design documents from `specs/121-register-password-default/`

**Prerequisites**: [plan.md](plan.md), [spec.md](spec.md), [research.md](research.md), [quickstart.md](quickstart.md)

**Tests**: Included. The constitution (III, non negotiable) requires it, and `.claude/rules/testing.md` decides the layer: the view model test owns the method the screen opens on, the controller test owns the copy the screen holds. No test asserts a class string.

**Organization**: Grouped by user story. The three stories edit the same view and the same view model, so they run in order, not side by side.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies on incomplete tasks)
- **[Story]**: The user story from spec.md

---

## Phase 1: Setup

- [X] T001 Read `resources/views/app/auth/register.blade.php`, `app/ViewModels/Auth/RegisterViewModel.php`, `tests/Unit/ViewModels/Auth/RegisterViewModelTest.php`, `tests/Feature/Controllers/App/Auth/RegistrationControllerTest.php` and `resources/views/components/input.blade.php` (how it renders `required` next to a forwarded `x-bind:required`), then the `views`, `view-models` and `tests` skills, before writing anything

---

## Phase 2: User Story 1 - Create an account with a password, with nothing to choose (Priority: P1) 🎯 MVP

**Independent Test**: the screen opens with the password fields shown and required, and submitting it creates an account with a password.

- [X] T002 [P] [US1] In `tests/Unit/ViewModels/Auth/RegisterViewModelTest.php`, replace `it_offers_a_passkey_first_over_a_secure_connection` with `it_opens_on_the_password_over_a_secure_connection`: `HTTPS` on, no flashed input, `registrationMethod()` is `'password'`
- [X] T003 [P] [US1] In `tests/Feature/Controllers/App/Auth/RegistrationControllerTest.php`, make `it_shows_the_registration_page` also assert the copy "Create your account" and "Next step: validate your email address"
- [X] T004 [US1] In `app/ViewModels/Auth/RegisterViewModel.php`, change the last line of `registrationMethod()` to `return old('registration_method') === 'passkey' ? 'passkey' : 'password';`, keeping the guard on `passkeysAreAvailable()` above it
- [X] T005 [US1] In `resources/views/app/auth/register.blade.php`, make the password the resting state: remove `x-cloak` from the password block and from the "Next step: validate your email address" label, add `required` to the `password` and `password_confirmation` inputs (keeping their `x-bind:required`), and add `x-cloak` to the sentence explaining a passkey and to the "Create a passkey" label
- [X] T006 [US1] Run `php artisan test --compact tests/Unit/ViewModels/Auth/RegisterViewModelTest.php tests/Feature/Controllers/App/Auth`

**Checkpoint**: the default is flipped and the form works with scripts off. The control under the button still reads "Other options" in passkey mode.

---

## Phase 3: User Story 2 - Choose a passkey instead (Priority: P2)

**Independent Test**: "Use a passkey instead" swaps the fields, reads "Set a password instead", and a refused passkey attempt comes back on the passkey.

- [X] T007 [P] [US2] In `tests/Unit/ViewModels/Auth/RegisterViewModelTest.php`, replace `it_keeps_the_password_method_after_a_refused_attempt` with `it_keeps_the_passkey_method_after_a_refused_attempt`: `HTTPS` on, flashed `registration_method` of `'passkey'`, `registrationMethod()` is `'passkey'`
- [X] T008 [P] [US2] In `tests/Feature/Controllers/App/Auth/RegistrationControllerTest.php`, add `it_offers_a_passkey_over_a_secure_connection`: the screen requested over https holds "Use a passkey instead" and "Set a password instead", and does not hold "Other options"
- [X] T009 [US2] In `resources/views/app/auth/register.blade.php`, change the control's `x-text` to "Set a password instead" in passkey mode and "Use a passkey instead" otherwise, both through `__()` and `Js::from`, and add `focus-ring` to its classes
- [X] T010 [US2] In `resources/views/app/auth/register.blade.php`, read `$viewModel->registrationMethod()` once in the existing `@php` block (comments there use `//` only) and, when it is `'passkey'`, put `x-cloak` on the password parts of T005 instead of the passkey parts, so a refused passkey attempt does not flash the password fields
- [X] T011 [US2] Run `php artisan test --compact tests/Unit/ViewModels/Auth/RegisterViewModelTest.php tests/Feature/Controllers/App/Auth`, which includes `RegistrationPasskeyControllerTest`, not edited and still green

---

## Phase 4: User Story 3 - No passkey offered where one cannot be made (Priority: P3)

**Independent Test**: over a connection that is not secure the screen holds none of the passkey copy, and in a browser without `PublicKeyCredential` the control does not show.

- [X] T012 [P] [US3] In `tests/Unit/ViewModels/Auth/RegisterViewModelTest.php`, add `it_refuses_the_passkey_method_over_a_connection_that_is_not_secure`: `HTTPS` off, flashed `registration_method` of `'passkey'`, `registrationMethod()` is `'password'`
- [X] T013 [P] [US3] In `tests/Feature/Controllers/App/Auth/RegistrationControllerTest.php`, add `it_says_nothing_about_passkeys_over_a_connection_that_is_not_secure`: the screen requested over http does not hold "Use a passkey instead", "You will sign in with a passkey" or "Passkeys need a secure connection". Assert these sentences, never the bare word, since the form's script names the passkey route
- [X] T014 [US3] In `resources/views/app/auth/register.blade.php`, delete the `@unless ($viewModel->passkeysAreAvailable())` notice
- [X] T015 [US3] In `resources/views/app/auth/register.blade.php`, add `supported: typeof globalThis.PublicKeyCredential === 'function'` to the screen's `x-data`, add `x-init="supported || (mode = 'password')"` beside it, and put `x-cloak x-show="supported"` on the paragraph holding the control
- [X] T016 [US3] In `app/ViewModels/Auth/RegisterViewModel.php`, delete `passkeysUnavailableReason()` after `grep -rn "passkeysUnavailableReason" resources/views/app/auth/register.blade.php tests/Unit/ViewModels/Auth/RegisterViewModelTest.php` returns nothing
- [X] T017 [US3] Run `php artisan test --compact tests/Unit/ViewModels/Auth/RegisterViewModelTest.php tests/Feature/Controllers/App/Auth`

---

## Phase 5: Polish

- [X] T018 Run `php artisan monica:localize` and confirm it reports two keys dropped ("Other options" and the registration warning) and none added. Never edit `lang/*.json` keys by hand
- [X] T019 Build the assets (`bun run build`) and walk the ten steps of [quickstart.md](quickstart.md) in a browser, at 375 pixels and wide, light and dark. Steps 8, 9 and 10 have no automated equivalent
- [X] T020 Run the `accessibility-reviewer` and `rules-reviewer` agents on the diff and check their evidence before acting on it
- [X] T021 Run the whole suite: `PAO_DISABLE=true php artisan test --parallel --compact`
- [X] T022 `php artisan monica:localize --check`, `vendor/bin/phpstan analyse --no-progress` and `vendor/bin/pint --dirty --format agent` all report nothing
- [X] T023 Record the change in the feature list with the `feature-documentator` skill

---

## Dependencies & Execution Order

- **Setup (T001)** → **US1** → **US2** → **US3** → **Polish**. There is no foundational phase: nothing is shared beyond the two files every story edits.
- Inside a story, the tests come first and fail, then the edits, then the run.
- T009, T010, T014 and T015 edit the same view as T005, so they wait for it and for each other.
- T018 runs after T009 and T014, which remove the two sentences.

## Parallel Example

```text
Together, at the start of each story (two different test files):
  US1  T002 RegisterViewModelTest   T003 RegistrationControllerTest
  US2  T007 RegisterViewModelTest   T008 RegistrationControllerTest
  US3  T012 RegisterViewModelTest   T013 RegistrationControllerTest
```

## Implementation Strategy

- **MVP**: Phase 2 alone answers the request: the screen opens on the password and the passkey stays reachable through the existing control.
- **One pull request** for the three stories, scope `auth`, for example `feat(auth): password as default sign up`.
