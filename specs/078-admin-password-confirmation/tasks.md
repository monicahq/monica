# Tasks: Password confirmation on the admin screens

**Input**: [plan.md](plan.md), [admin-password-confirmation.spec.md](admin-password-confirmation.spec.md), [research.md](research.md)

Tests come first in every phase and must fail before the implementation wave starts. US3 has no phase of its own: the card already renders the "holds neither" case (`ConfirmPasswordTest::it_says_so_when_the_account_holds_neither_credential`), and the one thing US3 adds, that each admin view model reads the signed in administrator's credentials, is asserted in T006 and T013.

## Phase 1: Setup

- [x] **T001** Create branch `2026-10-13-admin-password-confirmation` from an up to date `main` (the git config lock blocks `checkout -b` here; build it with plumbing) · git

## Phase 2: Foundational

Files: `resources/views/components/modal/confirm-password.blade.php`, `resources/views/components/modal/warning.blade.php`, `tests/Feature/Views/Components/Modal/ConfirmPasswordTest.php`, `tests/Feature/Views/Components/Modal/WarningTest.php`

**Wave 1 — independent (different files):**

- [x] **T002** [P] Add tests: without `description` the card shows today's sign in sentence; with `description` it shows the given sentence instead and the title stays "Confirm it is you" · tests/Feature/Views/Components/Modal/ConfirmPasswordTest.php
- [x] **T003** [P] Add tests: with `confirmsPassword` the danger button closes the warning and dispatches `confirm-password` with `{ form: $el.form }`; without it the button renders exactly as today (compare two renderings) · tests/Feature/Views/Components/Modal/WarningTest.php

**⟶ Wait for Wave 1 to finish, then:**

**Wave 2 — independent (different files):**

- [x] **T004** [P] Add the `description` prop (`?string`, default `null` falls back to today's sentence) (R3) · resources/views/components/modal/confirm-password.blade.php
- [x] **T005** [P] Add the `confirmsPassword` prop (`bool`, default `false`); when true the danger button gets `@click.prevent="open = false; $dispatch('confirm-password', { form: $el.form })"` (R2). Check that `confirms-password` forwarded through `x-delete-action`'s `$attributes` arrives as `true` · resources/views/components/modal/warning.blade.php

## Phase 3: User Story 1 - Confirm in place when changing the instance configuration (P1, MVP)

**Goal**: all six configuration actions open the card over the configuration screen, after any existing warning.

**Independent Test**: as an administrator with a password and no recent confirmation, change one setting: the card opens, the right password saves the typed value.

Files: `app/ViewModels/Admin/ConfigurationViewModel.php`, `app/Http/Controllers/App/Admin/InstanceSettingController.php`, `resources/views/app/admin/configuration/index.blade.php`, `resources/views/app/admin/configuration/_telegram.blade.php`

### Tests

Files: `tests/Unit/ViewModels/Admin/ConfigurationViewModelTest.php`, `tests/Feature/Views/App/Admin/ConfigurationTest.php`, `tests/Feature/Controllers/App/Admin/InstanceSettingControllerTest.php`

**Wave 1 — independent (different files):**

- [x] **T006** [P] [US1] Build the view model with `user:`; test `credentials()` offers the password to an administrator who has one, and offers neither to an administrator with no password and no passkey (US3) · tests/Unit/ViewModels/Admin/ConfigurationViewModelTest.php
- [x] **T007** [P] [US1] Assert the card is on the screen with the admin `description`; Save dispatches `confirm-password` after the warning check, which no longer sits on `@submit` (R1); a setting that cannot be changed keeps `aria-disabled` and dispatches nothing (R6); Clear, Put back and orphan removal pass `confirms-password`. Rewrite the exact `takesSomethingAway($refs.value.value)` count to assert behaviour · tests/Feature/Views/App/Admin/ConfigurationTest.php
- [x] **T008** [P] [US1] Assert an unconfirmed destroy and an unconfirmed orphan removal are refused and redirected to the confirmation screen, with nothing changed (FR-008) · tests/Feature/Controllers/App/Admin/InstanceSettingControllerTest.php

### Implementation

**⟶ Wait for the Tests wave to finish, then:**

**Wave 2 — independent (different files):**

- [x] **T009** [P] [US1] Take `User $user` in the constructor; add `credentials(): ConfirmPasswordViewModel`, as `SecurityViewModel` does (R4) · app/ViewModels/Admin/ConfigurationViewModel.php
- [x] **T010** [P] [US1] Generate a secret: warning first, then dispatch. Register: dispatch. Check stays ungated. An unavailable button keeps `aria-disabled` with no Alpine handler, through a Blade `@if` around two renderings (R6) · resources/views/app/admin/configuration/_telegram.blade.php

**⟶ Wait for Wave 2 to finish, then:**

**Wave 3 — independent (different files):**

- [x] **T011** [P] [US1] Pass `user: $request->user()` to the view model · app/Http/Controllers/App/Admin/InstanceSettingController.php
- [x] **T012** [P] [US1] Place `x-modal.confirm-password` once at the top with `$viewModel->credentials()` and the admin `description`. Move the warning check from the form's `@submit` to Save's `@click.prevent`, dispatching `confirm-password` once the warning was shown, on `x-button` and never on `x-dynamic-component` (R1). Clear, Put back and orphan removal pass `confirms-password` · resources/views/app/admin/configuration/index.blade.php

**Checkpoint**: the configuration screen confirms in place, with the existing warnings first.

## Phase 4: User Story 2 - Confirm in place when switching a flag for every account (P2)

**Goal**: activating or deactivating a flag for every account opens the card after the existing warning.

**Independent Test**: as an administrator with no recent confirmation, activate a flag for every account: the warning, then the card, and the flag is on once the password is accepted.

Files: `app/ViewModels/Admin/FlagsViewModel.php`, `app/Http/Controllers/App/Admin/FlagController.php`, `resources/views/app/admin/flags/index.blade.php`

### Tests

Files: `tests/Unit/ViewModels/Admin/FlagsViewModelTest.php`, `tests/Feature/Views/App/Admin/FlagsTest.php` (new), `tests/Feature/Controllers/App/Admin/FlagActivationControllerTest.php`

**Wave 1 — independent (different files):**

- [x] **T013** [P] [US2] Build the view model with `user:`; test `credentials()` for an administrator with a password and for one with neither credential (US3) · tests/Unit/ViewModels/Admin/FlagsViewModelTest.php
- [x] **T014** [P] [US2] New test (`php artisan make:test --phpunit FlagsTest`, moved under `Views/App/Admin`): the screen renders the card with the admin `description`, and both every-account warnings carry the `confirm-password` dispatch · tests/Feature/Views/App/Admin/FlagsTest.php
- [x] **T015** [P] [US2] Assert an unconfirmed deactivation for every account is refused and the flag is unchanged (FR-008) · tests/Feature/Controllers/App/Admin/FlagActivationControllerTest.php

### Implementation

**⟶ Wait for the Tests wave to finish, then:**

**Wave 2:**

- [x] **T016** [US2] Take `User $user` in the constructor; add `credentials(): ConfirmPasswordViewModel` (R4) · app/ViewModels/Admin/FlagsViewModel.php

**⟶ Wait for Wave 2 to finish, then:**

**Wave 3 — independent (different files):**

- [x] **T017** [P] [US2] Pass `user: $request->user()` to the view model · app/Http/Controllers/App/Admin/FlagController.php
- [x] **T018** [P] [US2] Place `x-modal.confirm-password` once at the top with the admin `description`; both every-account `x-modal.warning` pass `confirms-password` · resources/views/app/admin/flags/index.blade.php

**Checkpoint**: switching a flag for every account confirms in place, after its warning.

## Phase 5: Polish

Files: `lang/*.json`

**Wave 1:**

- [x] **T019** Run `php artisan monica:localize` and translate the new sentences in every configured locale, following `.claude/rules/translations.md` · lang/*.json

**⟶ Wait for Wave 1 to finish, then:**

**Wave 2 — independent:**

- [x] **T020** [P] Validate against SC-003 and FR-009: run the whole suite (`x-modal.warning` is used in 27 files), `vendor/bin/phpstan analyse --no-progress`, `vendor/bin/pint --dirty --format agent` and `php artisan monica:localize --check` · whole repository
- [ ] **T021** [P] Validate SC-001, SC-002 and SC-004 in a browser: walk the seven gated actions as an administrator without a recent confirmation, confirm each stays on its screen, the typed value is saved, cancel and Escape keep the value, and a second change inside the window opens no card · no file

## Dependencies & Execution Order

- Setup (T001) → Foundational (T002 to T005) → US1 and US2 in parallel (disjoint files) → Polish.
- Foundational: Wave 1 (T002, T003) blocks Wave 2 (T004, T005).
- US1: Tests (T006, T007, T008) block Wave 2 (T009, T010), which blocks Wave 3 (T011, T012).
- US2: Tests (T013, T014, T015) block T016, which blocks Wave 3 (T017, T018).
- Polish: T019 needs every view written (T010, T012, T018), then T020 and T021 run in either order.
