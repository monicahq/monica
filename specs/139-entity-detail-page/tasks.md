# Tasks: Record of an invented type, read and edited

**Input**: `specs/139-entity-detail-page/` (plan.md, research.md, contracts/api.md, quickstart.md)

**Tests**: required by the constitution (principle III), each rule at its own layer per `.claude/rules/testing.md`.

## Phase 1: Setup

None. No dependency, migration or config.

## Phase 2: Foundational

- [X] T001 Add `case EntityUpdate = 'entity_updated';` and its description wherever `EntityCreation` is described, in `app/Enums/UserActionEnum.php`
- [X] T002 Create `app/Actions/UpdateEntity.php` per research R4: sanitise and require the name (`EntityNeedsAName`), refuse another account or vault (`ModelNotFoundException`), run `SetFieldValues` for every section of `$entity->structureVersion->assembly` with `keptFieldIds`, update `name`, dispatch `LogUserAction` with `EntityUpdate`
- [X] T003 Test `UpdateEntity` in `tests/Unit/Actions/UpdateEntityTest.php`: writes name and values, keeps the record's version when a newer default exists, keeps a field listed in `keptFieldIds`, refuses an empty name, refuses another account, logs the action

## Phase 3: User Story 1, read a record (P1)

**Independent test**: Biscuit's page shows every section and field of its first page; another page has its own address.

- [X] T004 [US1] Add `entities.show` (`vaults/{vault}/entities/{entityType}/{entity}/{page?}`) after the `edit` route in `routes/web.php`
- [X] T005 [US1] Create `app/ViewModels/Vaults/EntityViewModel.php`: title, type caption, icon, colour, `pages()` (label, field count, url, isSelected; first page by default), `sections()` of the selected page (name, fields with label, field, rawValue, formatted value, rows, isEmpty), template name, version number and url, edit url, list url, archived flag; values loaded once with `field`, `valueListItem`, `file.vault`, `relatedContact`, `relatedEntity`, rows grouped by field
- [X] T006 [US1] Add `show()` to `app/Http/Controllers/App/Vaults/EntityController.php`: refuse a hidden type, `abort_unless($entity->entity_type_id === $entityType->id, 404)`, 404 for an unknown page key
- [X] T007 [US1] Create `resources/views/app/vaults/entities/show.blade.php` per design 1a: header (mark with `x-entity-icon` on the type tint, caption, name, Edit button), `nav` labelled "Pages" with `aria-current`, field counts and the template line, then one `x-panel` per section holding `x-detail-list` rows with `x-field-value`, "Not recorded" for an empty field, an empty page notice linking to the template; the column stacks above the sections below `lg`
- [X] T008 [US1] Make each row of `resources/views/app/vaults/entities/index.blade.php` a link to `entities.show`, with the url built in `app/ViewModels/Vaults/EntitiesViewModel.php::entities()`
- [X] T009 [P] [US1] Test `EntityViewModel` in `tests/Unit/ViewModels/Vaults/EntityViewModelTest.php`: pages in order with counts, first page by default, sections and fields in template order, empty field flagged, repeated field rows, fields of the record's own version when the template moved on
- [X] T010 [P] [US1] Controller tests in `tests/Feature/Controllers/App/Vaults/EntityControllerTest.php`: page renders name and a value, second page by its address, 404 for the wrong vault, the wrong type, an unknown page; list row links to the record

## Phase 4: User Story 2, correct a record (P1)

**Independent test**: Edit, change the name and a field, save; the page shows both.

- [X] T011 [US2] Add `entities.edit` (`.../{entity}/edit`) and `entities.update` (PUT `.../{entity}`) before `entities.show` in `routes/web.php`
- [X] T012 [US2] Extend `app/ViewModels/Vaults/EntityFormViewModel.php` with an optional `?Entity $entity`: values prefilled from the record (old input first), `action()` and a new `method()` switching to `entities.update` and `put`, title `Edit :name`, back url to the record
- [X] T013 [US2] Make `resources/views/app/vaults/entities/form.blade.php` use `method()` and the back url, and omit `structure_version_id` when editing
- [X] T014 [US2] Add `edit()` and `update()` to `app/Http/Controllers/App/Vaults/EntityController.php`: validation as `create()` over the record's own version, call `UpdateEntity`, redirect to `entities.show` with `:name is saved.`, back with input and errors on refusal
- [X] T015 [US2] Controller tests in `tests/Feature/Controllers/App/Vaults/EntityControllerTest.php`: edit form renders prefilled, update saves and redirects, an empty name comes back with an error, 404 through the wrong vault
- [X] T016 [P] [US2] Test the prefill and `method()` in `tests/Unit/ViewModels/Vaults/EntityFormViewModelTest.php`

## Phase 5: User Story 3, reach a record from outside (P2)

**Independent test**: API GET returns `fields`; PUT with only a name keeps every value; the MCP tools answer the same.

- [X] T017 [US3] Add `fields` (LifeEventResource format, sensitive values hidden without the scope) and `links` to `app/Http/Resources/EntityResource.php`; eager load what it reads in `Api\EntityController::index`
- [X] T018 [US3] Add `show()` and `update()` to `app/Http/Controllers/Api/EntityController.php` per `contracts/api.md`, update keeping fields left out, and their routes in `routes/api.php`
- [X] T019 [US3] API tests in `tests/Feature/Controllers/Api/EntityControllerTest.php`: show structure and values, update by name only keeps values, null clears a field, 422 on an empty name, 404 for another account's record
- [X] T020 [US3] Add `entityType()` and `entity()` lookups to `app/Mcp/Tools/MirrorsAnApiCapability.php` and their tests beside the trait's existing ones
- [X] T021 [P] [US3] Create `app/Mcp/Tools/ListEntities.php`, `CreateEntity.php`, `GetEntity.php`, `UpdateEntity.php` mirroring the four routes, and register them where the other tools are registered
- [X] T022 [US3] Document the four endpoints in `resources/docs/api/42-entities.php` and record them in `resources/docs/api-changelog/2026-11-19.php`, per the `api-docs-writer` skill

## Phase 6: Polish

- [X] T023 Update `docs/portal/en/3-people/6-profiles.md` (open, read, edit a profile) and remove the "no page of its own yet" sentence from `docs/portal/en/9-templates-and-customisation/9-profile-types.md`, per the `writer` skill
- [X] T024 Changelog entry `changelog/2026-11-19-<ulid>-profile-page.md`
- [X] T025 `php artisan monica:localize`, translate with Sonnet subagents, `--check`
- [X] T026 Pint, PHPStan, `composer test:parallel`, screenshots (light, dark, 320px) and the `definition-of-done` checks

## Dependencies

- T001, T002 before T014 and T018. T004 to T007 before T008 and T010. T011 to T014 before T015.
- US2 needs US1's page to land on. US3 needs T002. Polish last.
- Parallel: T009 with T010; T016 with T015; T021 across its four files after T020.

## Implementation strategy

US1 alone ships a readable record. US2 and US3 complete the capability the constitution requires; ship all three together.
