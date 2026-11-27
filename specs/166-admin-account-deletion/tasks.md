# Tasks: Account deletion from the admin space

**Input**: `specs/166-admin-account-deletion/` (spec.md, plan.md, research.md)

**Tests**: Required by constitution principle III, one layer each (`.claude/rules/testing.md`). Data
comes from The Office. The existing `DestroyAccountTest`, settings deletion and passkey deletion tests
must keep passing.

## Phase 1: Setup

- [X] T001 Read the skills (`actions`, `controllers`, `routes`, `view-models`, `views`, `components`, `tests`, `changelog`, `translations`) and `.claude/rules/` (`code-style.md`, `testing.md`, `components.md`, `policies.md`) before writing code

## Phase 2: Foundational (blocks every story)

- [X] T002 In `app/Actions/DestroyAccount.php` (research R1, R2):
  - the constructor takes only `Account $account`; the person is found with `User::withTrashed()` on the account, and may be null (no photo, no keys, no sessions then);
  - `accounts/{id}` (`File::directoryFor($id, null)`) is deleted on every disk named by the account's `files`, `account_exports` and `account_imports`, replacing the transfers-only deletion;
  - when `config('session.driver')` is `database`, the person's rows in the sessions table are deleted;
  - the class docblock still says what it does.
- [X] T003 Update the callers to the new signature: `app/Http/Controllers/App/Settings/Account/AccountController.php` and `tests/Feature/Auth/PasskeyAccountDeletionTest.php` (depends on T002)
- [X] T004 Extend `tests/Unit/Actions/DestroyAccountTest.php` (depends on T002):
  - a vault file and a contact avatar of Dwight's account are gone from a faked `s3` disk, and Jim's file on the same disk is kept;
  - Dwight's session rows are gone and Jim's are kept;
  - an account whose person is soft deleted is erased, the person row included;
  - an account with no person is erased;
  - drop the test of the person belonging to another account, which no longer applies.
- [X] T005 [P] Add `AccountDestroyed = 'account_destroyed'` to `app/Enums/AdministratorActionEnum.php` (research R4): description `Deleted account :account`, an existing destructive icon, `namesTheAccountHolder()` false, and every other `match` in the enum extended

## Phase 3: User Story 1, an administrator deletes an account (P1) 🎯 MVP

**Goal**: The red panel deletes the account and everything in it. **Independent test**: delete an account holding vaults, contacts, files and an API key from the admin screen; nothing of it remains.

- [X] T006 [US1] Create `app/Actions/DestroyAccountAsAdministrator.php` with `Account $account` and `User $administrator` (research R1, R4): call `DestroyAccount`, then dispatch `LogAdministratorAction` on `low` with `AccountDestroyed`, `account: null`, `parameters: ['account' => $id]` and the account as subject (depends on T002, T005)
- [X] T007 [US1] Create `tests/Unit/Actions/DestroyAccountAsAdministratorTest.php` (depends on T006):
  - Michael deletes Dwight's account: the account is missing and the log job is pushed on `low` with the account number and no name;
  - Stripe unreachable: `ProviderUnreachable` is thrown, nothing is deleted, nothing is logged.
- [X] T008 [US1] In `app/ViewModels/Admin/AccountViewModel.php`, add `destroyUrl()` and `deletionConfirmation()`: the person's address, a soft-deleted person's included, or the account number when there is nobody. Extend `tests/Unit/ViewModels/Admin/AccountViewModelTest.php` for the three cases
- [X] T009 [US1] Add `Route::delete('admin/accounts/{account}', [AccountController::class, 'destroy'])->whereNumber('account')->name('admin.accounts.destroy')` to `routes/administrator.php`, next to `show`
- [X] T010 [US1] Add `destroy(Request $request, Account $account)` to `app/Http/Controllers/App/Admin/AccountController.php` (research R5):
  - lowercase and trim `confirmation`, then validate it inline against the address or the account number;
  - catch `ProviderUnreachable` and `AdministratorCannotLockThemselvesOut` into `back()->withErrors(['confirmation' => ...])`;
  - redirect to `admin.accounts.index` with a `status` and a `status_description` saying everything is gone and the log keeps the number.
- [X] T011 [US1] Create `resources/views/app/admin/accounts/_delete.blade.php` with `x-delete-section`:
  - what goes with the account, as counts only (`NoVaultContentTest`), that it is final, and that a subscription stops without a refund;
  - an `x-modal.form` with `method="delete"`, a `confirmation` `x-input` whose help names the value to type, reopened by `$errors->has('confirmation')`.

  Include it in `show.blade.php` after the deactivation panel, outside `isActionable()`.
- [X] T012 [US1] Extend `tests/Feature/Controllers/App/Admin/AccountControllerTest.php` (depends on T010):
  - the right address deletes and redirects to the list with the flash;
  - a wrong address is refused with an error on `confirmation` and deletes nothing;
  - the account number is accepted for an account with no person;
  - the show screen renders the panel's title.
- [X] T013 [US1] Add the new URL with the `DELETE` method to the provider of `tests/Feature/Controllers/App/Admin/AdminAccessTest.php`. Not applicable: the provider lists screens opened with GET only, no mutation route is in it, and `RouteProtectionTest` covers the new route

## Phase 4: User Story 2, no deleting one's own account here (P2)

**Goal**: The administrator's own account cannot be deleted from the admin space. **Independent test**: open one's own account and try.

- [X] T014 [US2] In `app/Actions/DestroyAccountAsAdministrator.php`, refuse with `AdministratorCannotLockThemselvesOut` and its own message when `$administrator->account_id` is the account's id, before anything is deleted (research R3)
- [X] T015 [US2] Extend `tests/Unit/Actions/DestroyAccountAsAdministratorTest.php`: Michael deleting his own account is refused, his account stays, nothing is logged
- [X] T016 [US2] In `_delete.blade.php`, when `isTheViewer()`, render a refusal paragraph with an id and the trigger button with `aria-disabled="true"` and `aria-describedby` pointing at it, as the deactivation panel does
- [X] T017 [US2] Extend `AccountControllerTest.php`: the controller turns the self refusal into an error on `confirmation`, and the account stays

## Phase 5: Polish

- [X] T018 [P] Write `changelog/2026-11-27-<ulid>-account-deletion-files.md` (`changelog` skill): deleting your account now also removes your uploaded files from storage and signs out your other devices (research R6)
- [X] T019 Run `php artisan monica:localize`, then translate every new string in every locale through Sonnet subagents (`translations` skill)
- [X] T020 Run the `accessibility-reviewer` and `rules-reviewer` agents on the diff; fix what they confirm
- [X] T021 Run the `definition-of-done` skill, the full suite through `composer test:parallel`

## Dependencies

- T002 blocks T003, T004 and T006. T005 blocks T006.
- US2 extends the action and partial of US1, so it comes after it.
- T019 comes after every wording exists; T021 comes last.

## Parallel examples

- In Phase 2: T005 alongside T002.
- In Phase 3: T008 and T009 while T006 is written.
- In Phase 5: T018 alongside T019.

## Strategy

The MVP is Phase 2 plus US1: the panel deletes an account completely. US2 then closes the one way an
administrator could remove themselves from the instance.
