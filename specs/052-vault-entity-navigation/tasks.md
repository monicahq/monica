---
description: "Task list for entity types in the vault navigation"
---

# Tasks: Entity types in the vault navigation

**Input**: Design documents from `specs/052-vault-entity-navigation/`

**Prerequisites**: plan.md, spec.md, research.md

**Tests**: Required (constitution III). Follow `.claude/rules/testing.md`: strict types, `#[Test]`, snake_case `it_` names, named arguments, The Office data, no comments, no class string assertion. The visibility rule is tested once, on the scope.

**Organization**: the scope is the foundation. US1 builds the entries and the list, US2 proves the visibility cases through the scope, US3 the notice, US4 the empty state. US3 and US4 edit the list view after US1.

## Format: `[ID] [P?] [Story] Description`

## Phase 1: Setup

No setup: no dependency, configuration or data change.

## Phase 2: Foundational

- [X] T001 In `app/Models/EntityType.php`, add `vaultDefaultTemplates(): HasMany` (docblock `@return HasMany<VaultDefaultTemplate, $this>`) and `scopeVisibleInVault(Builder $query, Vault $vault): void` (research R1): `where(fn)` of either `is_active = true` and `whereHas('vaultDefaultTemplates', vault_id = $vault->id, applies_to = Entity, whereHas('structureVersion', status = Published, whereHas('structure', whereHasMorph('owner', Template::class, is_active = true))))`, or `whereHas('entities', vault_id = $vault->id)` (soft deleted rows are already left out; archived ones count); document the scope in the model docblock

## Phase 3: User Story 1 - Reach a type from the vault (Priority: P1)

**Goal**: an entry per visible type under People, opening the type's list in the vault.

**Independent Test**: spec US1.

- [X] T002 [US1] In `app/View/Components/Vault/Sidebar.php`, pass `'entityTypes'` to the view: `EntityType::query()->where('account_id', $this->vault->account_id)->visibleInVault($this->vault)->orderBy('position')->get()` mapped to `{slug, name (name_plural), icon, colour, url (route('entities.index', [$vault, slug]))}`
- [X] T003 [US1] In `resources/views/components/vault/sidebar.blade.php`, add `@var` for `$entityTypes`; right after the `contacts` entry, render one `<a>` per type with the `$item` classes, `x-entity-icon :name :size="16"` inside a `<span aria-hidden="true" style="color: {{ colour }}">`, the plural name in a `truncate` span, `data-turbo="true"`, and `aria-current="page"` plus the active classes when `$current === "entity-type-{slug}"`; keep the other entries unchanged (build the list in the `@php` block by splicing after index 1)
- [X] T004 [US1] In `routes/web.php`, next to the contact routes, add `Route::get('vaults/{vault}/entities/{entityType}', [EntityController::class, 'index'])->name('entities.index');` and the `use`
- [X] T005 [US1] Create `app/Http/Controllers/App/Vaults/EntityController.php` with `index(Request $request, Vault $vault, EntityType $entityType): View`: `abort_unless(EntityType::query()->whereKey($entityType->id)->visibleInVault($vault)->exists(), 404)`, then `view('app.vaults.entities.index', ['viewModel' => new EntitiesViewModel(user: $request->user(), vault: $vault, entityType: $entityType)])`
- [X] T006 [US1] Create `app/ViewModels/Vaults/EntitiesViewModel.php`: `user()`, `vault()`, `title()` (plural name), `entityType(): array{name, icon, colour, isActive}`, `entities(): list<array{id, name, initials, avatarUrl, isArchived}>` from `Entity::query()->where('vault_id', vault)->where('entity_type_id', type)->orderBy('sort_name')`, `emptyTitle()` (T012); follow `ContactsViewModel` for the avatar URL
- [X] T007 [US1] Create `resources/views/app/vaults/entities/index.blade.php` in the shell of `resources/views/app/vaults/contacts/index.blade.php` (read it first: layout, breadcrumb, `x-vault.sidebar :user :vault current="entity-type-{{ slug }}"`), with `x-page-header :title`, and an `x-list-panel` of rows: avatar or `x-avatar-initials`, the name, `x-badge` "Archived" when archived; rows are not links
- [X] T008 [P] [US1] In `tests/Feature/Views/Components/SidebarTest.php`, add `it_lists_the_types_of_the_vault_under_people_in_their_order` (`assertSeeInOrder(['People', 'Animals', 'Companies', 'Tasks'])` with positions 2 and 1 swapped to prove order), `it_marks_the_type_being_read_as_the_current_page` (`aria-current="page"` beside the type's `entities.index` address)
- [X] T009 [P] [US1] Create `tests/Unit/ViewModels/Vaults/EntitiesViewModelTest.php`: `it_lists_the_records_of_this_type_in_this_vault_by_name`, `it_leaves_out_the_records_of_another_vault_and_another_type`, `it_marks_an_archived_record`
- [X] T010 [US1] Create `tests/Feature/Controllers/App/Vaults/EntityControllerTest.php`: `it_shows_the_records_of_a_type` (renders the plural name and a record's name), `it_answers_not_found_for_a_type_hidden_in_this_vault`

## Phase 4: User Story 2 - An entry appears only when it leads somewhere (Priority: P1)

- [X] T011 [US2] In `tests/Unit/Models/EntityTypeTest.php`, add: `it_shows_an_active_type_with_a_published_default_in_the_vault`, `it_keeps_a_type_turned_off_while_the_vault_holds_one_of_its_records`, `it_keeps_a_type_whose_only_record_is_archived`, `it_hides_a_turned_off_type_once_its_last_record_is_deleted`, `it_hides_a_type_whose_default_is_retired_or_whose_template_is_turned_off`, `it_decides_each_vault_on_its_own`

## Phase 5: User Story 3 - A turned-off type says so (Priority: P2)

- [X] T012 [US3] In `resources/views/app/vaults/entities/index.blade.php`, when `! $viewModel->entityType()['isActive']`, render `x-notice` (read it first) with `__('This type is turned off. You can view and edit the existing profiles, but you cannot add new ones.')` above the list
- [X] T013 [US3] In `EntityControllerTest.php`, add `it_says_so_when_the_type_is_turned_off`

## Phase 6: User Story 4 - An empty list (Priority: P3)

- [X] T014 [US4] In `EntitiesViewModel.php`, `emptyTitle()` returns `__('No :name in this vault yet', ['name' => plural])`; in the view, the `x-list-panel` empty slot shows `x-empty-state :title` with no link
- [X] T015 [US4] In `EntityControllerTest.php`, add `it_says_when_the_vault_holds_none_of_them`

## Phase 7: Polish

- [X] T016 Run `php artisan monica:localize` and translate the new strings in every locale (French notice exactly as the brief gives it: "Ce type est désactivé. Vous pouvez consulter et modifier les fiches existantes, mais vous ne pouvez pas en ajouter."), then `php artisan monica:localize --check`; never `lang:update`
- [X] T017 Run `php artisan test --compact --parallel` (the three `another_alphabet` tests fail on `main` already), `vendor/bin/phpstan analyse --no-progress`, `vendor/bin/pint --dirty --format agent`

## Dependencies

- T001 blocks everything. US1: T002 → T003; T004 → T005 → T006 → T007; T008 after T003; T009 after T006; T010 after T007.
- US2 (T011) needs only T001. US3 and US4 after T007 (same view). Polish last.

## Parallel Opportunities

T008 and T009; T011 beside all of US1 after T001.

## Implementation Strategy

MVP is Phase 2 and US1: types reachable from the vault. Then the visibility tests, the notice, the empty state. Commit after each phase.
