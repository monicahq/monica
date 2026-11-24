# Tasks: Hide the main sections of a vault

**Input**: [plan.md](plan.md), [spec.md](spec.md), [research.md](research.md), [data-model.md](data-model.md), [contracts/interfaces.md](contracts/interfaces.md)
**Tests**: required by constitution III, written in the layer that owns each rule (`.claude/rules/testing.md`).

## Phase 1: Setup

- [X] T001 Create migration `database/migrations/2026_11_24_000000_add_section_visibility_to_vaults_table.php`, `up()` only, adding `shows_people`, `shows_things`, `shows_knowledge` as "boolean, not null, default `true`", each with a `->comment()` like `shows_vault_list`

## Phase 2: Foundational

- [X] T002 Add the three columns to `#[Fillable]`, cast each to `boolean`, and document them in the model docblock in `app/Models/Vault.php`
- [X] T003 [P] Add `VaultSectionVisibilityUpdate = 'vault_section_visibility_updated'` with label "Changed which sections the vault :name shows" in `app/Enums/UserActionEnum.php`
- [X] T004 Create `app/Actions/UpdateVaultSectionVisibility.php` (user, vault, showsPeople, showsThings, showsKnowledge): account check throwing `ModelNotFoundException`, save, dispatch `LogUserAction` with `loggable: $vault` and `changes` holding only changed columns as booleans
- [X] T005 Test the action in `tests/Unit/Actions/UpdateVaultSectionVisibilityTest.php`: saves the three values, logs only the changed ones, refuses a vault of another account

## Phase 3: User Story 1, hide a section from the screen (P1) MVP

**Independent test**: switch Knowledge off in Vault settings, save, the Knowledge group leaves that vault's sidebar only.

- [X] T006 [US1] Create `app/Http/Controllers/App/Vaults/VaultSectionVisibilityController.php` with `update()` reading each value with `$request->boolean()`, calling the action, redirecting to `vaults.settings.index` with status "The vault is saved."
- [X] T007 [US1] Add `Route::put('vaults/{vault}/settings/sections', [VaultSectionVisibilityController::class, 'update'])->name('vaults.sectionVisibility.update');` beside the default template route in `routes/web.php`
- [X] T008 [US1] Add `sections()` (key, name, description, icon, field name, shown) and `sectionVisibilityAction()` to `app/ViewModels/Vaults/VaultSettingsViewModel.php`, and extend `description()` to mention which sections the vault shows
- [X] T009 [US1] Add the **What this vault shows** panel between the default template panel and the export panel in `resources/views/app/vaults/settings.blade.php`: `x-panel`, own `x-form` with id, one `<label>` row per section holding icon, name, description, an `aria-hidden` Shown/Hidden word bound with Alpine, and `x-switch`; quiet `x-notice` "Hidden here, still available elsewhere"; footer **Save changes**
- [X] T010 [US1] Leave out every hidden group in `resources/views/components/vault/sidebar.blade.php` (People group holds contacts, entity types and Groups) and skip the possession and knowledge counts of hidden groups in `app/View/Components/Vault/Sidebar.php`
- [X] T011 [US1] Add `bool $respectsHiddenSections = false` to `app/Actions/SearchRecords.php`; when true, the `people` and `entity-type-*` groups search only vaults in scope whose `shows_people` is true; pass `true` from `app/ViewModels/Vaults/SearchViewModel.php`
- [X] T012 [P] [US1] Test the controller in `tests/Feature/Controllers/App/Vaults/VaultSectionVisibilityControllerTest.php`: saves, absent field means hidden, redirect and flash, other fields of the vault untouched
- [X] T013 [P] [US1] Test `sections()` and the description in `tests/Unit/ViewModels/Vaults/VaultSettingsViewModelTest.php`
- [X] T014 [P] [US1] Test in `tests/Feature/Views/Components/SidebarTest.php` that each hidden group is absent, the vault entries stay, and another vault is unaffected
- [X] T015 [P] [US1] Test in `tests/Unit/Actions/SearchRecordsTest.php` that hidden People is ignored by default, respected when asked, and that across the account only vaults hiding People are skipped
- [X] T016 [P] [US1] Test in `tests/Feature/Controllers/App/Vaults/VaultSettingsControllerTest.php` that the panel renders with its form action and the three switch names

## Phase 4: User Story 2, the API (P2)

**Independent test**: `PUT /api/vaults/{vault}` with `{"shows_things": false}` changes that value only; `GET` shows it.

- [X] T017 [US2] Add `shows_people`, `shows_things`, `shows_knowledge` to `attributes` in `app/Http/Resources/VaultResource.php`
- [X] T018 [US2] In `update()` of `app/Http/Controllers/Api/VaultController.php`, validate each as `['sometimes', 'boolean']` and call `UpdateVaultSectionVisibility` only when one is present, taking unsent values from the vault
- [X] T019 [P] [US2] Test in `tests/Feature/Controllers/Api/VaultControllerTest.php`: the three fields in the JSON structure, a partial update keeps the rest, a non boolean answers 422 naming the field

## Phase 5: User Story 3, the MCP tool (P3)

**Independent test**: `update-vault` with `shows_people: true` gives the API update's answer.

- [X] T020 [US3] Create `app/Mcp/Tools/UpdateVault.php` mirroring `api.v1.vaults.update`: looks the vault up with `vault()`, then `answerThroughApi()`; schema per contracts/interfaces.md; annotations per `docs/MCP_ANNOTATIONS.md`
- [X] T021 [US3] Register `UpdateVault::class` beside `ListVaults::class` in `app/Mcp/Servers/MonicaServer.php`, and add it to any tool list the server instructions enumerate

## Phase 6: User Story 4, documentation (P3)

- [X] T022 [P] [US4] Add a "Choose what the vault shows" section to `docs/portal/en/6-vaults/3-vault-settings.md`, update its title, description, screenshot label and `last_updated`
- [X] T023 [P] [US4] Document the three fields and the update parameters in `resources/docs/api/05-vaults.php` (api-docs-writer skill)
- [X] T024 [P] [US4] Create `resources/docs/api-changelog/2026-11-24.php` with an `Added` entry for the three fields and the update endpoint
- [X] T025 [P] [US4] Add a What's new entry `changelog/2026-11-24-<ulid>-vault-sections.md` (changelog skill)
- [X] T026 [P] [US4] Mention `update-vault` in `docs/portal/en/10-your-account-and-security/12-connected-assistants.md` only if that page lists tools by name

## Phase 7: Polish

- [X] T027 Run `php artisan monica:localize`, translate every missing string with Sonnet subagents, then `php artisan monica:localize --check`
- [X] T028 Run `vendor/bin/pint --dirty --format agent`, PHPStan and `composer test:parallel`
- [X] T029 Run the `definition-of-done` skill, then the `accessibility-reviewer` and `rules-reviewer` agents on the change

## Dependencies

- T001 → T002 → T004 → every story. T003 before T004.
- US1, US2 and US4 depend only on Phase 2. US3 depends on US2 (T017, T018).
- T027 after every task that adds copy; T028 and T029 last.
- Parallel: T012 to T016 together; T019 with US1 tests; T022 to T026 together.
