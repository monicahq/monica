---
description: "Task list for Authorization Policies and Gates"
---

# Tasks: Authorization Policies and Gates

**Input**: Design documents from `/specs/028-authorization-policies/`

**Prerequisites**: [plan.md](plan.md), [spec.md](spec.md), [research.md](research.md), [quickstart.md](quickstart.md)

**Tests**: required. Constitution III is non-negotiable and FR-009 asks for a permitted and a refused case per ability.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: can run in parallel (different files, no dependency on an unfinished task)
- **[Story]**: US1, US2 or US3, matching the user stories in spec.md

---

## Phase 1: Setup

**Purpose**: clear the governance blocker. Done, so work starts at Phase 2.

- [x] T001 Amend `.specify/memory/constitution.md` to admit `Policies` into the fixed `app/` tree, version 2.2.0 to 2.3.0, with a Sync Impact Report saying what changed and why (merged as #179)

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: create the one file two stories both write to, so they do not collide.

- [x] T002 Create `app/Policies/ContactPolicy.php` holding the class declaration and no methods yet

**Checkpoint**: US1 and US2 can now each add their own method to it.

---

## Phase 3: User Story 1 - The two free offer ceilings (Priority: P1) 🎯 MVP

**Goal**: the vault ceiling and the contact ceiling each have one home, asked by the action and by every screen that offers the control.

**Independent Test**: the "Proving SC-001" procedure in [quickstart.md](quickstart.md). Change the message in `VaultPolicy::create()` and confirm both the form and the refusal follow without a second edit.

### Tests for User Story 1

- [x] T003 [P] [US1] Write `tests/Unit/Policies/VaultPolicyTest.php` covering create permitted below the ceiling, refused at it, permitted when the account has no ceiling at all, refused when nobody is signed in, and the refusal message carrying the counted wording
- [x] T004 [P] [US1] Write `tests/Unit/Policies/ContactPolicyTest.php` covering create permitted below the ceiling, refused at it, permitted when the account has no ceiling at all, and refused when nobody is signed in

### Implementation for User Story 1

- [x] T005 [P] [US1] Add `create(User $user)` to `app/Policies/VaultPolicy.php` returning `Response::allow()` or `Response::deny()` with the ceiling wording
- [x] T006 [US1] Add `create(User $user)` to `app/Policies/ContactPolicy.php` (depends on T002)
- [x] T007 [P] [US1] Rewrite `refuseAboveTheCeiling()` in `app/Actions/CreateVault.php` to ask `$this->user->authorize('create', Vault::class)`, which refuses with the wording the policy holds
- [x] T008 [P] [US1] Do the same in `app/Actions/CreateContact.php`, keeping the existing `isMe` and `isPartial` early returns ahead of the policy call
- [x] T009 [P] [US1] Rewrite `ceilingMessage()` in `app/ViewModels/Vaults/VaultFormViewModel.php` to return the policy's denial message, deleting the duplicated limit logic
- [x] T010 [P] [US1] Do the same in `app/ViewModels/Vaults/ContactFormViewModel.php`
- [x] T011 [P] [US1] Add a method to `app/ViewModels/Vaults/VaultsViewModel.php` reporting whether a vault may be created and the reason when it may not
- [x] T012 [P] [US1] Add the equivalent to `app/ViewModels/Vaults/ContactsViewModel.php` beside `newContactUrl()`
- [x] T013 [P] [US1] Add the equivalent to `app/ViewModels/Vaults/VaultDashboardViewModel.php` beside `newContactUrl()`
- [x] T014 [US1] In `resources/views/app/vaults/index.blade.php` lines 12 and 32, render both "New vault" controls without `href` and with `type="button" aria-disabled="true"` when refused, pointing `aria-describedby` at the reason
- [x] T015 [US1] Do the same for both contact controls in `resources/views/app/vaults/show.blade.php` lines 27 and 54
- [x] T016 [US1] Do the same for all three contact controls in `resources/views/app/vaults/contacts/index.blade.php` lines 18, 93 and 120
- [x] T017 [US1] Add `aria-disabled="true"` to the submit in `resources/views/app/vaults/form.blade.php` at the ceiling, with `aria-describedby` pointing at the paywall prompt as the reason
- [x] T018 [US1] Do the same for the submit in `resources/views/app/vaults/contacts/form.blade.php`
- [x] T019 [P] [US1] Update `tests/Unit/Actions/CreateVaultTest.php` for the unified wording
- [x] T020 [P] [US1] Update `tests/Unit/Actions/CreateContactTest.php` for the unified wording
- [x] T021 [P] [US1] Update `tests/Unit/ViewModels/Vaults/VaultFormViewModelTest.php` and `tests/Unit/ViewModels/Vaults/ContactFormViewModelTest.php`
- [x] T022 [US1] Assert every control above renders unavailable at the ceiling and offered below it, in `tests/Feature/Controllers/App/Vaults/VaultControllerTest.php` and `tests/Feature/Controllers/App/Vaults/ContactControllerTest.php`

**Checkpoint**: both ceilings have one home, and no screen offers a control that will refuse.

---

## Phase 4: User Story 2 - The contact representing you (Priority: P2)

**Goal**: the `is_me` rule stops being written in two places.

**Independent Test**: the "Proving the Me contact refusal" procedure in [quickstart.md](quickstart.md).

### Tests for User Story 2

- [x] T023 [US2] Add delete cases to `tests/Unit/Policies/ContactPolicyTest.php`, refused for the Me contact, permitted for any other, and refused when nobody is signed in (same file as T004)

### Implementation for User Story 2

- [x] T024 [US2] Add `delete(User $user, Contact $contact)` to `app/Policies/ContactPolicy.php` returning `Response::deny()` for the Me contact (same file as T006)
- [x] T025 [US2] Rewrite the `is_me` branch of `validate()` in `app/Actions/DestroyContact.php` to ask the policy, leaving the `account_id` check exactly where it is per FR-007
- [x] T026 [US2] Rewrite `mayBeDeleted()` and `deleteDescription()` in `app/ViewModels/Vaults/ContactEditViewModel.php` to ask the policy
- [x] T027 [US2] In `resources/views/app/vaults/contacts/edit.blade.php` around line 170, stop hiding the delete control behind `@if ($viewModel->mayBeDeleted())` and render it without `href` and with `type="button" aria-disabled="true"` when refused, pointing `aria-describedby` at `deleteDescription()` as the reason
- [x] T028 [US2] Update `tests/Unit/Actions/DestroyContactTest.php`
- [x] T029 [US2] Assert the Me contact edit screen shows the delete control marked unavailable and carrying its reason, and that any other contact shows it active, in `tests/Feature/Controllers/App/Vaults/ContactControllerTest.php`

**Checkpoint**: the Me contact rule has one home.

---

## Phase 5: User Story 3 - The same refusal through the API (Priority: P3)

**Goal**: prove the API refuses identically. No implementation beyond the exception each controller catches: `Api\VaultController` and `Api\ContactController` catch the `AuthorizationException` that T007, T008 and T025 now raise, and answer it exactly as before.

**Independent Test**: the "Proving the API refuses identically" procedure in [quickstart.md](quickstart.md).

- [x] T030 [P] [US3] Assert the unified ceiling wording in `tests/Feature/Controllers/Api/VaultControllerTest.php`
- [x] T031 [P] [US3] Assert the unified ceiling wording and the Me contact refusal in `tests/Feature/Controllers/Api/ContactControllerTest.php`

**Checkpoint**: every surface refuses with one wording.

---

## Phase 6: Polish and Cross-Cutting Concerns

- [x] T032 Assert in `tests/Unit/Policies/PolicyCoverageTest.php` that every public method on every class in `app/Policies` has both a permitted and a refused case somewhere under `tests/Unit/Policies`, so a policy added without tests fails the suite (FR-010)
- [x] T033 Run `php artisan monica:localize` and translate the changed ceiling strings in all 31 locales, never editing `lang/*.json` keys by hand
- [x] T034 Run `php artisan monica:localize --check` and fix what it reports, including the counted phrase and typography rules
- [ ] T035 Check `resources/views/app/vaults/index.blade.php`, `show.blade.php`, `contacts/index.blade.php`, `form.blade.php` and `contacts/form.blade.php` for keyboard operation, screen reader announcement of the unavailable state, visible focus and 200% zoom, per Principle IX
- [x] T036 Run `vendor/bin/pint --dirty --format agent` and PHPStan at level 5
- [ ] T037 Run the full suite on SQLite, MySQL and PostgreSQL
- [x] T038 Walk [quickstart.md](quickstart.md) end to end

---

## Dependencies and Execution Order

- **T001 is done**, merged as #179, so `app/Policies` is admitted and the work starts at T002.
- **T002 blocks T006 and T024**, which both write `app/Policies/ContactPolicy.php`.
- **US1 (Phase 3)** depends only on Phase 2. It is the MVP and delivers value alone.
- **US2 (Phase 4)** depends only on Phase 2. It shares two files with US1 (`ContactPolicy.php` and `ContactPolicyTest.php`), so run it after US1 or coordinate those two files.
- **US3 (Phase 5)** depends on T007, T008 and T025 for the wording it asserts. It adds no production code.
- **T032** depends on every policy method that is being shipped (T005, T006 and T024) and on their tests (T003, T004 and T023), since it asserts over both.
- **T029 follows T022**, both writing `tests/Feature/Controllers/App/Vaults/ContactControllerTest.php`. It is the one file US1 and US2 share beyond the policy, so the two stories are independent everywhere else but not there.
- **FR-005, FR-011 and FR-012 carry no task by design.** They forbid rather than require, so they are proven by the existing suite passing unchanged at T036 rather than by new work.
- **Phase 6** depends on every story that is being shipped. T033 and T034 must follow all copy changes.
- Within a story: tests, then the policy, then the action and the view models, then the templates, then the test updates.

### Parallel opportunities

- T003 and T004 together.
- T005, T007, T008, T009, T010, T011, T012 and T013 together, all different files.
- T019, T020 and T021 together.
- T030 and T031 together.
- T014 through T018 are separate files and could run together, but each depends on the view model method from T011, T012 or T013.
