# Tasks: Possession Types

**Input**: [plan.md](plan.md), [spec.md](spec.md), [research.md](research.md), [data-model.md](data-model.md), [contracts/web-routes.md](contracts/web-routes.md), [contracts/api-and-mcp.md](contracts/api-and-mcp.md)
**Tests**: required by constitution principle III, written with each piece, per `.claude/rules/testing.md` (The Office data). No test asserts markup or classes.

## Phase 1: Setup

- [X] T001 Amend principle VII in `.specify/memory/constitution.md`: Possession type joins the fixed concepts with its own groups, fields and options; Sync Impact Report; 2.6.0 → 2.7.0 (research R1)
- [X] T002 [P] Name Possession type beside Location in `spec/spec.md`; write `spec/models/059-possession-type-in-progress.md`, `060-possession-field-group-in-progress.md`, `061-possession-field-in-progress.md` in the shape of `058-location-in-progress.md`
- [X] T003 [P] Create `app/Enums/PossessionFieldKindEnum.php`: `Text`, `Number`, `Date`, `Boolean`, `Select`, `Rating`, `implements Translatable` with `label()`, constant `MAX_RATING = 5` (research R4)
- [X] T004 [P] Add to `app/Enums/UserActionEnum.php`: `PossessionTypeCreation`, `PossessionTypeUpdate`, `PossessionTypeDeletion`, `PossessionTypeImport`, `PossessionFieldGroupCreation`, `PossessionFieldGroupUpdate`, `PossessionFieldGroupDeletion`, `PossessionFieldCreation`, `PossessionFieldUpdate`, `PossessionFieldDeletion`, each description naming `:name`

## Phase 2: Foundational

- [X] T005 Create `database/migrations/2026_10_26_000000_create_possession_types_table.php` with the three tables of data-model.md: `uuid` unique, `foreignIdFor(Account::class)->constrained()->cascadeOnDelete()`, `name` string(255), `colour` string(7), `position` unsigned int, `kind` string, `options` json nullable, `possession_field_group_id` `nullable()->constrained()->nullOnDelete()`, indexes on `(possession_type_id, position)`, a comment on every column, `up()` only
- [X] T006 Create `app/Models/PossessionType.php`, `PossessionFieldGroup.php`, `PossessionField.php` with `#[RouteKey('uuid')]`, `#[Fillable]`, `HasUuids`, `ResolvesModelInAccount`, `HasFactory`, casts (`kind` to the enum, `options` to array), relations of data-model.md ordered by position, full model docblocks; add `possessionTypes()` to `app/Models/Account.php`
- [X] T007 [P] Create `database/factories/PossessionTypeFactory.php`, `PossessionFieldGroupFactory.php`, `PossessionFieldFactory.php` (account consistent with the type; field text kind, no group)

## Phase 3: User Story 1, create, list, name, colour and delete types (P1) MVP

Independent test: spec US1.

- [X] T008 [P] [US1] Create `app/Actions/CreatePossessionType.php` (name `__('Untitled type')`, colour `VaultColourEnum::Blue`), `UpdatePossessionType.php` (sanitised name refused when empty, colour one of `VaultColourEnum`), `DestroyPossessionType.php`; each refuses another account with `ModelNotFoundException` and logs on the `low` queue
- [X] T009 [US1] Write `tests/Unit/Actions/CreatePossessionTypeTest.php`, `UpdatePossessionTypeTest.php`, `DestroyPossessionTypeTest.php`: default name in French for a French user, empty name refused, unknown colour refused, delete takes groups and fields, another account refused, log dispatched
- [X] T010 [US1] Create `app/ViewModels/Settings/Account/PossessionTypes/PossessionTypesViewModel.php` (types sorted with `LocaleCollation`, colour, counts through `trans_choice()`) and `PossessionTypeViewModel.php` (name, colours as in `LifeEventTypeFormViewModel::colours()`, breadcrumb); test both in `tests/Unit/ViewModels/Settings/Account/PossessionTypes/`
- [X] T011 [US1] Create `app/Http/Controllers/App/Settings/Account/PossessionTypes/PossessionTypeController.php` (`index`, `create`, `edit`, `update`, `destroy`) with the validation of contracts/web-routes.md, and its five routes in `routes/web.php` after the locations
- [X] T012 [US1] Create `resources/views/app/settings/account/possession-types/index.blade.php` (list, "New type", "Import JSON", `x-empty-state`) and `edit.blade.php` (name and colour edited in place, an export link in the header, deletion in an `x-delete-section` with `x-delete-action`)
- [X] T013 [US1] Add the "Possession types" entry to the Possessions group in `resources/views/components/settings/sidebar.blade.php` and a row with a count to `app/ViewModels/Settings/SettingsViewModel.php`; update `tests/Feature/Views/Components/SidebarTest.php` and `tests/Unit/ViewModels/Settings/SettingsViewModelTest.php`
- [X] T014 [US1] Write `tests/Feature/Controllers/App/Settings/Account/PossessionTypes/PossessionTypeControllerTest.php`: index renders, create redirects to edit, update saves, empty name refused on the form, delete redirects with status

## Phase 4: User Story 2, define the fields (P1)

Independent test: spec US2. Depends on US1.

- [X] T015 [P] [US2] Create `app/Actions/CreatePossessionField.php` (`__('Untitled field')`, `Text`, last position in its set, optional group of the same type), `UpdatePossessionField.php` (name, kind, options distinct and non-empty, cleared off `Select`, at most 200 of 255 characters), `UpdatePossessionFieldPosition.php` (as `UpdateValueListItemPosition`, siblings in the same group or standalone), `DestroyPossessionField.php`
- [X] T016 [US2] Write the four action tests in `tests/Unit/Actions/`: positions renumbered from one within the set only, options dropped when leaving select, duplicate option refused, group of another type refused, another account refused, logs dispatched
- [X] T017 [US2] Create `PossessionFieldController.php` (`create`, `update`, `destroy`) and `PossessionFieldPositionController.php` (`update`) with `abort_unless` on the type, answering the redrawn row for `x-target` or redirecting to edit; add the four routes
- [X] T018 [US2] Create `resources/views/app/settings/account/possession-types/_field.blade.php`: name, kind `x-select`, options list with add and confirmed remove, up and down buttons sending position ± 1 with accessible names, confirmed delete, form submitted on `change`, polite live region saying "Saved" (research R8)
- [X] T019 [US2] Write `PossessionFieldControllerTest.php` and `PossessionFieldPositionControllerTest.php`: each route answers, validation refuses an unknown kind, a field of another type in the URL gives 404

## Phase 5: User Story 3, group fields (P2)

Independent test: spec US3. Depends on US2.

- [X] T020 [P] [US3] Create `app/Actions/CreatePossessionFieldGroup.php` (`__('Untitled group')`, last position), `UpdatePossessionFieldGroup.php`, `UpdatePossessionFieldGroupPosition.php`, `DestroyPossessionFieldGroup.php` (fields to standalone, appended after the existing ones, one transaction)
- [X] T021 [US3] Write the four action tests in `tests/Unit/Actions/`: removal keeps fields in order after the standalone ones, positions renumbered, another account refused, logs dispatched
- [X] T022 [US3] Create `PossessionFieldGroupController.php` and `PossessionFieldGroupPositionController.php`, their four routes, and `_group.blade.php` (name in place, "+ Field", moves, confirmed removal saying the fields are kept); group the fields on `edit.blade.php` with empty states for no group and no standalone field
- [X] T023 [US3] Extend `PossessionTypeViewModel.php` with groups and standalone fields in order, test it, and write `PossessionFieldGroupControllerTest.php` and `PossessionFieldGroupPositionControllerTest.php`

## Phase 6: User Story 4, export and import (P2)

Independent test: spec US4. Depends on US3.

- [X] T024 [P] [US4] Create `app/Helpers/PossessionTypeFile.php` (format of research R9, `encode()`, file name) and `app/Exceptions/PossessionTypeFileIsNotReadable.php` holding a list of problems
- [X] T025 [US4] Create `app/Helpers/PossessionTypeFileReader.php`: 100 KB, 50 groups, 300 fields, 200 options, 255 characters, known kinds, a select field accepted without options (spec US4.4), colour defaulting to `Slate`, every problem collected and naming the group or field; test it in `tests/Unit/Helpers/PossessionTypeFileReaderTest.php`
- [X] T026 [US4] Create `app/Actions/ExportPossessionType.php` and `ImportPossessionType.php` (one transaction, logs `PossessionTypeImport`); test that an export imported back is identical in structure
- [X] T027 [US4] Create `PossessionTypeExportController.php` (`index`, `show` streaming the file), `PossessionTypeImportController.php` (`new`, `create`), `PossessionTypeImportCheckController.php` (`create`), their routes (import before `{possessionType}`, `throttle:20,1`), view models, and `export.blade.php` with `x-copy-button`, `import.blade.php` listing problems, modelled on the template export and import
- [X] T028 [US4] Write the three controller tests: export answers and downloads JSON, check lists problems, import redirects to the new type, an unreadable file comes back with errors on `file`

## Phase 7: User Story 5, API, MCP and documentation (P2)

Independent test: spec US5. Depends on US1 to US4.

- [X] T029 [P] [US5] Create `app/Http/Resources/PossessionTypeResource.php` with the shape of contracts/api-and-mcp.md
- [X] T030 [US5] Create the API controllers in `app/Http/Controllers/Api/PossessionTypes/` and the fifteen routes in `routes/api.php`, calling the actions above, refusals turned into 422 on the field
- [X] T031 [US5] Write `tests/Feature/Controllers/Api/PossessionTypes/` tests with a shared `$jsonStructure`: every route, another account's records left out of the list and 404 when read, one 422 per controller, import 422 on `file`
- [X] T032 [US5] Add `possessionType()`, `possessionFieldGroup()`, `possessionField()` lookups to `app/Mcp/Tools/MirrorsAnApiCapability.php` and create the fifteen tools of contracts/api-and-mcp.md in `app/Mcp/Tools/`, annotated read only or destructive
- [X] T033 [US5] Register the tools in `app/Mcp/Servers/MonicaServer.php` with one paragraph of instructions, and add "Possession types" and "Possession types, changing" groups to `app/ViewModels/Docs/McpDocsViewModel.php`
- [X] T034 [US5] Write one test per tool in `tests/Unit/Mcp/Tools/` and one possession type case in `tests/Feature/Mcp/McpChangelogTest.php`
- [X] T035 [P] [US5] Write `resources/docs/api/26-possession-types.php` with the `api-docs-writer` skill
- [X] T036 [P] [US5] Write `docs/portal/en/9-templates-and-customisation/12-possession-types.md` with the `writer` skill

## Phase 8: Polish

- [X] T037 Run `php artisan monica:localize`, translate what it reports in every locale, then `php artisan monica:localize --check`
- [X] T038 Add the feature to `docs/FEATURES.md` with the `feature-documentator` skill
- [X] T039 Run `vendor/bin/pint --dirty --format agent`, `vendor/bin/phpstan analyse --no-progress`, then the whole suite
- [ ] T040 Walk through `quickstart.md` by hand on `https://erica.test`

## Dependencies

Setup → Foundational → US1 → US2 → US3 → US4 → US5 → Polish. Within a phase, `[P]` tasks touch different
files. US2 to US4 extend the edit screen of US1, so they run in order; T035 and T036 run beside T032 to T034.

## Strategy

MVP is US1 and US2: types with ordered fields, which already answers the brief. US3 and US4 add
groups and files; US5 closes principle X and must land before the pull request is opened.
