# Tasks: Skip the list of vaults

**Input**: [plan.md](plan.md), [spec.md](spec.md), [research.md](research.md), [contracts/api.md](contracts/api.md), [quickstart.md](quickstart.md)

**Tests**: included, since principle III makes them a delivery condition. Each test sits in the one layer that owns the rule (`.claude/rules/testing.md`).

## Phase 1: Foundational (the preference and its switch)

**Blocks**: every story, since both stories read `shows_vault_list`.

- [X] T001 Write migration `database/migrations/2026_11_18_000003_add_shows_vault_list_to_users_table.php` adding `shows_vault_list` boolean, default `true`, `up()` only (research R1)
- [X] T002 Add `@property bool $shows_vault_list`, the fillable entry and the `boolean` cast in `app/Models/User.php`, and `shows_vault_list => true` in `database/factories/UserFactory.php` if the factory lists the other preferences
- [X] T003 [P] Add case `VaultListVisibilityUpdate = 'vault_list_visibility_updated'` labelled `Changed whether the list of vaults is shown` in `app/Enums/UserActionEnum.php`, following `UserTimezoneUpdate` in every match (research R7)
- [X] T004 Write action `app/Actions/UpdateVaultListVisibility.php` (`User $user`, `bool $showsVaultList`), saving the column and dispatching `LogUserAction` with `VaultListVisibilityUpdate`, modelled on `app/Actions/UpdateTimezone.php` (research R2)
- [X] T005 [P] Test T004 in `tests/Unit/Actions/UpdateVaultListVisibilityTest.php`: saves both values, dispatches the log job
- [X] T006 Add `Route::put('settings/account/preferences/vault-list', [VaultListController::class, 'update'])->name('settings.vaultList.update')` beside the timezone route in `routes/web.php`
- [X] T007 Write `app/Http/Controllers/App/Settings/Account/Preferences/VaultListController.php`, validating `shows_vault_list` as `['required', 'boolean']` and redirecting to `settings.preferences.index` with a `status` flash, modelled on `TimezoneController`
- [X] T008 [P] Test T007 in `tests/Feature/Controllers/App/Settings/Account/Preferences/VaultListControllerTest.php`: update answers with the redirect and flash, refuses a missing value
- [X] T009 Add `showsVaultList(): bool` to `app/ViewModels/Settings/Account/Preferences/PreferencesViewModel.php`
- [X] T010 Write `resources/views/app/settings/account/preferences/_vault-list.blade.php`: an `x-row` titled `Show the list of vaults`, described `When it is off, you go straight to your default vault.`, holding an `x-form` with a hidden `0` and an `x-switch` submitting on change, as `app/settings/account/notifications/_channel.blade.php` does, plus an `x-help` pointing at `account.preferences#the-list-of-vaults` (research R8)
- [X] T011 Add an `x-page-section` titled `Vaults` including `_vault-list` with every variable named in `resources/views/app/settings/account/preferences/index.blade.php`, after `Language and formats`
- [X] T012 [P] Expose `shows_vault_list` in `app/Http/Resources/UserResource.php` attributes (contracts/api.md)
- [X] T013 Add `Route::put('me/vault-list', [MeVaultListController::class, 'update'])->name('me.vaultList.update')` beside `me/timezone` in `routes/api.php`, and write `app/Http/Controllers/Api/MeVaultListController.php` calling T004 and returning `UserResource`
- [X] T014 [P] Test T013 in `tests/Feature/Controllers/Api/MeVaultListControllerTest.php`: 200 with the new value under `data.attributes.shows_vault_list`, 422 on a missing or non boolean value

**Checkpoint**: the switch saves on screen and through the API, and changes nothing else yet.

## Phase 2: User Story 1, go straight to the default vault (P1) MVP

**Independent test**: switch off, open `/vaults`, land on the default vault; with no vault, see the empty list.

- [X] T015 [US1] In `index()` of `app/Http/Controllers/App/Vaults/VaultController.php`, when `! $request->user()->shows_vault_list`, look up the account's vault with `is_default` true; if found, `$request->session()->reflash()` and redirect to `vaults.show` (research R3)
- [X] T016 [US1] Test T015 in `tests/Feature/Controllers/App/Vaults/VaultControllerTest.php`: redirects to the default vault when off, shows the list when on, shows the list when off with no vault, keeps the `status` flash across the redirect

## Phase 3: User Story 2, the default vault cannot be deleted while the list is skipped (P1)

**Independent test**: switch off, the default vault's delete button is refused on screen, and `DELETE api/v1/vaults/{vault}` answers 422.

- [X] T017 [US2] Add `delete(User $user, Vault $vault): Response` to `app/Policies/VaultPolicy.php`, denying with `You cannot delete your default vault while the list of vaults is turned off. Turn it back on in Preferences first.` when `! $user->shows_vault_list && $vault->is_default` (research R4)
- [X] T018 [P] [US2] Test `delete` in `tests/Unit/Policies/VaultPolicyTest.php`: permitted when on, refused for the default vault when off, permitted for another vault when off, and a test named with `nobody_is_signed_in`
- [X] T019 [US2] Call `$this->user->authorize('delete', $this->vault)` in `validate()` after the account check in `app/Actions/DestroyVault.php`
- [X] T020 [P] [US2] Test in `tests/Unit/Actions/DestroyVaultTest.php` that the refused deletion throws `AuthorizationException` and the vault still exists
- [X] T021 [US2] Catch `AuthorizationException` in `destroy()` of `app/Http/Controllers/App/Vaults/VaultController.php` and return `back()->withErrors(['vault' => $e->getMessage()])`
- [X] T022 [US2] Catch `AuthorizationException` in `destroy()` of `app/Http/Controllers/Api/VaultController.php` and throw `ValidationException::withMessages(['vault' => $e->getMessage()])`, as `AccountExportController` does
- [X] T023 [P] [US2] Test the refusal once per controller: the web one in `tests/Feature/Controllers/App/Vaults/VaultControllerTest.php` (error on the session), the API one in `tests/Feature/Controllers/Api/VaultControllerTest.php` (422, `errors.vault`)
- [X] T024 [US2] Add `deletionRefusal(): ?string` to `app/ViewModels/Vaults/VaultSettingsViewModel.php` returning `$this->user->inspect('delete', $this->vault)->message()` when denied, else null
- [X] T025 [P] [US2] Test T024 in `tests/Unit/ViewModels/Vaults/VaultSettingsViewModelTest.php`: null when on, the sentence for the default vault when off
- [X] T026 [US2] In `resources/views/app/vaults/settings.blade.php`, when `deletionRefusal()` is not null, replace the modal trigger with `<x-button.danger type="button" aria-disabled="true" aria-describedby="vault-delete-refusal">` and a `<p id="vault-delete-refusal">` holding the sentence and a link to `settings.preferences.index`, as the export refusal on the same screen does (research R5)

## Phase 4: User Story 3, read how it works (P2)

- [X] T027 [P] [US3] Add a section `## The list of vaults` to `docs/portal/en/10-your-account-and-security/15-preferences.md` and bump `last_updated`, following the `writer` skill, no dashes as punctuation
- [X] T028 [P] [US3] Add one paragraph to `docs/portal/en/6-vaults/4-delete-a-vault.md` saying the default vault cannot be deleted while the list is off, linking with `@doc(account.preferences)`, and bump `last_updated`
- [X] T029 [P] [US3] Document `PUT me/vault-list`, the new attribute on `GET me`, and the 422 on `DELETE vaults/{vault}` in the API reference, following the `api-docs-writer` skill

## Phase 5: Polish

- [X] T030 Run `php artisan monica:localize` and have every reported string translated by Sonnet subagents, per the `translations` skill, then `php artisan monica:localize --check`
- [X] T031 [P] Write the What's new entry, following the `changelog` skill
- [X] T032 Run `vendor/bin/pint --dirty --format agent`, PHPStan, and the full suite (`php artisan test --compact`), since `VaultController` and a shared policy changed
- [X] T033 Run the `accessibility-reviewer` and `rules-reviewer` agents on the diff and check their evidence before acting on it
- [ ] T034 Walk [quickstart.md](quickstart.md) by hand at https://monica.test, then run the `definition-of-done` skill, stating in the report why there is no MCP tool (research R6)

## Dependencies

- Phase 1 blocks Phases 2 and 3. Phases 2, 3 and 4 are independent of each other. Phase 5 comes last.
- Inside Phase 1: T001 → T002 → T004 → (T007, T013); T009 → T010 → T011.
- Inside Phase 3: T017 → (T019, T024) → (T021, T022, T026).
- Tasks marked [P] touch a file no unfinished task in the same phase touches. T016 and T023 both edit the web `VaultControllerTest`, so they run one after the other.

## Strategy

MVP is Phase 1 and Phase 2: the switch and the redirect. Phase 3 must ship in the same pull request, since a skipped list with a deletable default vault leaves nowhere to land.
