# Tasks: The Me contact in every new vault

**Input**: [plan.md](plan.md), [spec.md](spec.md), [research.md](research.md)

**Tests**: requested by the feature description. Each story writes its tests first.

## Phase 1: Foundational

- [X] T001 Add `->afterCommit()` to the `LogUserAction` dispatch in `log()` and to the `DetectDuplicateContacts` dispatch in `lookForDuplicates()` in app/Actions/CreateContact.php (research R3)
- [X] T002 Run tests/Unit/Actions/CreateContactTest.php and confirm it still passes after T001

## Phase 2: User Story 1, a new vault already holds me (P1)

Independent test: create a vault for Michael Scott and read its contacts.

- [X] T003 [US1] In tests/Unit/Actions/CreateVaultTest.php, give each test's factory user its contact template first with `new CreateDefaultTemplate(user: $user)->handle()`, and update `it_creates_a_vault` to expect `contact_count` 1
- [X] T004 [US1] Add `it_writes_down_the_contact_standing_for_the_owner_of_the_vault` to tests/Unit/Actions/CreateVaultTest.php: one contact with `is_me` true, `first_name` Michael, `last_name` Scott, on the system contact template's published version, and a `ContactCreation` `LogUserAction` pushed on `low`
- [X] T005 [US1] Add `it_creates_a_vault_for_an_account_at_the_contact_ceiling` to tests/Unit/Actions/CreateVaultTest.php: free plan, `monica.free_contact_limit` reached, vault and Me contact created, `Account::contactCount()` unchanged
- [X] T006 [US1] In app/Actions/CreateVault.php, wrap `create()`, `seedTheContactDefault()`, `seedTheAnimalDefault()`, a new `createTheMeContact()` and `log()` in one `DB::transaction`, keeping `sanitize()` and `refuseAboveTheCeiling()` before it; `createTheMeContact()` calls `new CreateContact(user:, vault:, firstName: $this->user->first_name, lastName: $this->user->last_name, isMe: true)->execute()`; add `->afterCommit()` to the log; the class docblock says the vault is created holding the contact standing for its owner
- [X] T007 [US1] Add `it_leaves_the_contact_standing_for_the_owner_alone_when_the_user_is_renamed` to tests/Unit/Actions/UpdateUserProfileTest.php: create a vault, rename the user with `UpdateUserProfile`, assert the Me contact keeps the old first and last name
- [X] T008 [P] [US1] In tests/Unit/Actions/DestroyVaultTest.php, tests/Feature/Controllers/App/Vaults/VaultControllerTest.php, tests/Feature/Controllers/Api/VaultControllerTest.php, tests/Feature/Controllers/App/Docs/ApiDocsReturnFieldsTest.php and tests/Feature/RouteProtectionTest.php, give every factory user that creates a vault its contact template first with `new CreateDefaultTemplate(user: $user)->handle()`, and update any expected `contact_count` from 0 to 1
- [X] T009 [US1] Run the test files touched in T003 to T008 and confirm they pass

## Phase 3: User Story 2, sign up gives the first vault its Me contact (P1)

Independent test: sign up as Pam Beesly and read the contacts of the first vault.

- [X] T010 [US2] Add `it_gives_the_first_vault_the_contact_standing_for_the_new_user` to tests/Unit/Actions/CreateAccountTest.php: one contact with `is_me` true named Pam Beesly in the first vault, on the published version of the `BasicContact` system template, with `Queue::fake()` active
- [X] T011 [US2] Update `it_asks_for_the_rest_of_the_seeding_in_the_order_it_has_to_happen_in` in tests/Unit/Actions/CreateAccountTest.php: the chain is `SeedNotificationPreference`, `SeedLifeEventCategories`, `CreateDefaultTemplate` for `BasicAnimal`, `CreateDefaultLifeEventTypes`, `SeedRelationshipTypes`; remove the `SeedValueLists` import if unused; adjust the value list and template tests that relied on the chain to expect the rows synchronously
- [X] T012 [US2] In app/Actions/CreateAccount.php, add `createTheContactTemplate()` called inside the transaction between `createUser()` and `createFirstVault()`: `new CreateDefaultValueLists(user: $this->user)->execute()` then `new CreateDefaultTemplate(user: $this->user)->handle()` (research R2, not `dispatchSync`); remove `SeedValueLists` and the `BasicContact` `CreateDefaultTemplate` from `seedTheAccount()` and the unused import; update the class docblock
- [X] T013 [P] [US2] Update the class docblock of app/Jobs/CreateDefaultTemplate.php: the animal template is queued, the contact template runs inline during sign up because the Me contact is written on it
- [X] T014 [P] [US2] In database/seeders/ExampleAccountSeeder.php `createContacts()`, use `$this->vault->contacts()->where('is_me', true)->firstOrFail()` for the cast row with `is_me` true instead of `CreateContact`, and drop the `isMe:` argument from the remaining call
- [X] T015 [US2] Run tests/Unit/Actions/CreateAccountTest.php, tests/Unit/Seeders/ExampleAccountSeederTest.php and tests/Unit/Jobs/CreateDefaultTemplateTest.php and confirm they pass

## Phase 4: User Story 3, the Me contact stays (P2)

Independent test: every route that deletes, merges or moves the Me contact refuses, and deleting the vault takes it.

- [X] T016 [US3] Add `it_takes_the_contact_standing_for_the_owner_with_the_vault` to tests/Unit/Actions/DestroyVaultTest.php: after `DestroyVault`, the Me contact is soft deleted with `deleted_with_vault` true
- [X] T017 [US3] Run tests/Unit/Policies/ContactPolicyTest.php, tests/Unit/Actions/PreviewContactMergeTest.php, tests/Unit/Actions/MergeContactsTest.php, tests/Feature/Dav/AddressBookTest.php and tests/Unit/Actions/DestroyVaultTest.php and confirm the existing delete, merge and move refusals still pass; add no duplicate test

## Phase 5: User Story 4, nothing half made (P2)

Independent test: a failed creation leaves no vault, no default, no contact and runs no job.

- [X] T018 [US4] Add `it_creates_nothing_when_the_account_has_no_contact_template` to tests/Unit/Actions/CreateVaultTest.php: expect `VaultHasNoContactDefault`, then assert zero `vaults`, `vault_default_templates` and `contacts` rows for the account
- [X] T019 [US4] Add `it_runs_no_queued_work_when_the_creation_is_rolled_back` to tests/Unit/Actions/CreateVaultTest.php: no `Queue::fake()`, listen for `Illuminate\Queue\Events\JobProcessing`, run `CreateVault` inside a `DB::transaction` that then throws, assert no job processed and no vault row
- [X] T020 [P] [US4] Add `it_refuses_a_vault_when_the_account_has_no_contact_template` to tests/Feature/Controllers/App/Vaults/VaultControllerTest.php (redirect back with an error on `name`) and to tests/Feature/Controllers/Api/VaultControllerTest.php (`assertUnprocessable()`, `assertJsonValidationErrors('name')`)
- [X] T021 [US4] In app/Http/Controllers/App/Vaults/VaultController.php `create()`, catch `VaultHasNoContactDefault` and return `back()->withErrors(['name' => __('This account has no template for contacts to start from, so the vault cannot be created.')])->withInput()`
- [X] T022 [US4] In app/Http/Controllers/Api/VaultController.php `create()`, catch `VaultHasNoContactDefault` and throw `ValidationException::withMessages(['name' => __(...)])` with the same sentence as T021
- [X] T023 [US4] Run tests/Unit/Actions/CreateVaultTest.php and both VaultControllerTest files and confirm they pass

## Phase 6: Polish

- [X] T024 [P] Amend the Me contact Product Invariant in .specify/memory/constitution.md to one Me contact per vault, add a Sync Impact Report for 2.4.3, bump the version and Last Amended date (research R7)
- [X] T025 [P] Update section 4.3 of spec/spec.md: Me is unique within each vault and is created with it
- [X] T026 [P] Update spec/models/007-vault-in-progress.md (move the Me contact from Not done yet to a rule), spec/models/018-vault-default-template-done.md, spec/models/012-template-in-progress.md, spec/models/001-account-in-progress.md and spec/models/019-contact-done.md as research R7 says
- [X] T027 Run `php artisan monica:localize`, translate the T021 sentence in every configured locale following .claude/rules/translations.md, then `php artisan monica:localize --check`
- [X] T028 Run `php artisan test --compact` (full suite) and fix every failure caused by the sign up or vault creation change
- [X] T029 Run `vendor/bin/pint --dirty --format agent` and `vendor/bin/phpstan analyse --no-progress`, and fix what they report

## Dependencies

- T001 before T006. T006 before T008, T016, T018 to T023. T010 and T011 pass only once T012 is done. T024 to T026 independent of code. T027 after T021. T028 and T029 last.
- US1 and US2 depend on Phase 1. US3 and US4 depend on US1.
- Parallel: T008, T013, T014, T020, T024 to T026.

## Implementation strategy

MVP is Phase 1 plus US1. US2 makes sign up match. US3 is proof only. US4 adds the refusal and the rollback proof.
