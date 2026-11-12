# Tasks: Vault Sidebar Sections

**Input**: [plan.md](plan.md), [spec.md](spec.md), [research.md](research.md), [quickstart.md](quickstart.md)
**Tests**: required by constitution principle III, written with each piece, per `.claude/rules/testing.md`. No test asserts markup or classes.

## Phase 1: Setup

None: no dependency, no migration, no configuration.

## Phase 2: Foundational

- [X] T001 In `app/Models/EntityType.php`, add `scopeConfiguredInVault(Builder $query, Vault $vault): void` holding the first branch of today's `scopeVisibleInVault()` (`is_active` true and a `vaultDefaultTemplates` row for the vault, `applies_to` Entity, published version, active template owner), and rewrite `scopeVisibleInVault()` as `configuredInVault($vault)` or `whereHas('entities', vault_id)`; update the model docblock in one sentence (research R1)
- [X] T002 In `tests/Unit/Models/EntityTypeTest.php`, add tests for `configuredInVault`: configured in Scranton is true there and false in Stamford; deactivated is false; a draft only version is false; a vault holding records of the type but no default template is false while `visibleInVault` stays true; keep every existing `visibleInVault` test green

## Phase 3: User Story 1, the column reads as sections (P1) MVP

Independent test: spec US1.

- [X] T003 [US1] In `app/View/Components/Vault/Sidebar.php`, pass `hasPossessionTypes` to the view: `PossessionType::query()->where('account_id', $this->vault->account_id)->exists()` (research R4)
- [X] T004 [US1] Rewrite `resources/views/components/vault/sidebar.blade.php`: replace `$sections` with `$groups` of `['title' => ?string, 'entries' => [...]]` in the order of spec US1 (This vault: Dashboard, Reminders; Your relationships: People then each `$entityTypes` entry with its coloured `x-entity-icon`; Your possessions: All possessions, icon `key`, url `route('possessions.index', $vault)`, key `possessions`, only when `$hasPossessionTypes`; untitled last group: Vault settings); render titles with the `h2` caption markup of `resources/views/components/settings/sidebar.blade.php`; make the `nav` `flex flex-col` and push the last group to the foot with `mt-auto`; drop the `$section['key'] === 'contacts'` special case and the unused `aria-disabled` branch only if no entry still lacks a url; update the `@var` block (research R4)
- [X] T005 [US1] Add the route `Route::get('vaults/{vault}/possessions', [PossessionController::class, 'index'])->name('possessions.index');` beside `reminders.index` in `routes/web.php`, with its `use`
- [X] T006 [P] [US1] Create `app/Http/Controllers/App/Vaults/PossessionController.php` with `index(Request $request, Vault $vault): View` returning `view('app.vaults.possessions.index', ['user' => $request->user(), 'vault' => $vault])` (research R2)
- [X] T007 [P] [US1] Create `resources/views/app/vaults/possessions/index.blade.php` in the shape of `resources/views/app/vaults/search/index.blade.php`: first line comment, `@var` lines for `$user` and `$vault`, `x-shell` titled `__('Possessions')`, `x-breadcrumb.vault` with one item `__('Possessions')`, `x-vault.sidebar` with `current="possessions"`, `x-page-header`, and `x-empty-state` with the icon `key`, the title `__('Possessions are coming')` and one sentence saying the place is ready for what the account owns (research R5)
- [X] T008 [US1] In `tests/Feature/Views/Components/SidebarTest.php`: rewrite `the_vault_column_offers_the_destinations_of_that_vault` to assert the three section labels and Vault settings with `assertSeeInOrder(['This vault', 'Dashboard', 'Reminders', 'Your relationships', 'People', 'Your possessions', 'All possessions', 'Vault settings'])` for an account holding a possession type; change `the_vault_column_lists_the_types_of_the_vault_under_people_in_their_order` to expect `['Your relationships', 'People', 'Companies', 'Animals', 'Your possessions']`
- [X] T009 [US1] Create `tests/Feature/Controllers/App/Vaults/PossessionControllerTest.php`: the screen answers 200 with the copy `Possessions are coming` (another account's vault is covered by `RouteProtectionTest`)

## Phase 4: User Story 2, a custom type appears only where it is configured (P1)

Independent test: spec US2. Depends on T001.

- [X] T010 [US2] In `app/View/Components/Vault/Sidebar.php::entityTypes()`, replace `visibleInVault` with `configuredInVault`
- [X] T011 [US2] In `tests/Feature/Views/Components/SidebarTest.php`, add: a type with records in the vault but no default template there is absent; a deactivated type is absent; a deleted type is absent (spec US2.2 to US2.4)

## Phase 5: User Story 3, All possessions offered before possessions exist (P2)

Independent test: spec US3. Depends on T003 to T007.

- [X] T012 [US3] In `tests/Feature/Views/Components/SidebarTest.php`, add: with a possession type, All possessions links to `route('possessions.index', $vault)` and is marked current with `current="possessions"`; without one, neither `Your possessions` nor `All possessions` is rendered (spec US3.2)

## Phase 6: Polish

- [X] T013 Run `php artisan monica:localize`; write `lang/fr_FR.json` first under the French typography and glossary of `.claude/rules/translations.md`, then the other locales through subagents with the French file as reference; `php artisan monica:localize --check` passes
- [X] T014 [P] In `docs/portal/en/3-people/6-profiles.md`, say a profile kind sits under **Your relationships**, and add to its troubleshooting that a kind not configured in the vault is not in the sidebar, with the way to configure it (research R6)
- [X] T015 [P] Add one line to `docs/FEATURES.md` per the `feature-documentator` skill
- [X] T016 Skipped at the user’s request: retake `3-reminders.index.webp` with the `take-screenshot` skill, after `bun run build`
- [X] T017 Run `vendor/bin/pint --dirty --format agent`, `vendor/bin/phpstan analyse --no-progress` and the whole suite, `php artisan test --compact`; fix what a literal of the old column breaks
- [ ] T018 Walk the "By hand" table of `quickstart.md`

## Dependencies

- T001 before T002 and T010. T003 to T007 before T008, T009 and T012. T010 before T011.
- US1 and US2 touch the same two sidebar files, so they run in sequence, US1 first.
- T013 after every string exists (T004, T007). T016 after T004.

## Parallel

- T014 and T015 alongside T013.
- T006 and T007 alongside each other once T005 names the route.

## Strategy

MVP is US1 (the regrouped column with the placeholder screen). US2 is one line and its tests; US3 is
already built by US1 and only proven by T012.
