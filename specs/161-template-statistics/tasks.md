# Tasks: Statistics per kind of record, and changing the template of many records

**Input**: `specs/161-template-statistics/` (spec.md, plan.md, research.md, data-model.md, contracts/api.md, quickstart.md)

**Tests**: required by constitution principle III; one layer each (`.claude/rules/testing.md`), no test of how anything looks. Data: the Animals example of the spec, people from The Office.

**Design**: `Animals Statistics Wireframes.dc.html`, claude.ai/design project `df580fc3-3c55-4f7f-a1bb-3bac3655e20f`. Read it again with `DesignSync get_file` before building a screen; its flow wins over the brief (spec Assumptions).

## Phase 1: Setup

- [X] T001 Read `.claude/skills/{actions,controllers,routes,view-models,views,components,migrations,models,help-bubbles,api-writer,api-docs-writer,mcp-development,translations,changelog}/SKILL.md` and `.claude/rules/{components,mcp,testing,code-style}.md` before writing code

## Phase 2: Foundational (blocks every story)

- [X] T002 Migration `database/migrations/2026_11_25_000000_add_statistics_indexes_to_records.php`: index `(vault_id, structure_version_id, is_archived)` on `contacts` and `(vault_id, entity_type_id, structure_version_id, is_archived)` on `entities`, `up()` only (research R2)
- [X] T003 Create `app/Helpers/RecordKind.php` (research R1): `forContacts(Vault)` and `forEntityType(Vault, EntityType)`; `fromApiSegment(Vault, string)` resolving `contacts` or an entity type uuid of the vault's account (`ModelNotFoundException` otherwise); `singular()`, `plural()` from the type's stored names or "contact"/"people" via `__()`; `recordQuery(): Builder` scoped to the vault (and type); `templates()` of the kind (offered ones, plus turned off ones still holding records in the vault); `defaultVersion()`; `listRoute(array $filters)`; `morphClass()`
- [X] T004 [P] `tests/Unit/Helpers/RecordKindTest.php`: both constructors, the uuid of another account refused, `recordQuery()` leaves another vault and another type out

## Phase 3: User Story 1, the Statistics page (P1) MVP, first delivery

**Independent Test**: quickstart checks 1, 2, 3 and 9.

- [X] T005 [US1] Create `app/Helpers/RecordStatistics.php` (research R2): one grouped query over `RecordKind::recordQuery()` by `structure_version_id`, `is_archived` (and `is_partial` for contacts); `toArray()` returning the `statistics` shape of `contracts/api.md` (records, templates in use, on older versions, archived, partial for People only; per template its count, share rounded, published version, Default and active flags, versions in use with counts, templates with no record at 0)
- [X] T006 [P] [US1] `tests/Unit/Helpers/RecordStatisticsTest.php`: the Animals figures of spec User Story 1; archived counted only in its own figure; partial contacts in the total and in `partial`; an inactive template with records listed with `is_active` false; an empty type gives zeros and every offered template
- [X] T007 [US1] Create `app/Helpers/FieldCompletion.php` (research R3): template, optional version, order `lowest` or `form`; per field of the chosen versions, `COUNT(DISTINCT valuable_id)` from `field_values` with `is_detached = false` (and `field_value_rows` for a repeater field) joined to non-archived, non-partial records on those versions; denominator = records on versions carrying the field; grouped by section; `percentage` null at denominator 0; sensitive field counted in `hidden_count` when `namesSensitiveFields` is false
- [X] T008 [P] [US1] `tests/Unit/Helpers/FieldCompletionTest.php`: Microchip number 5 of 11 (field only in v3); a record with two values counted once; a set aside value not counted; 0 of N for a field never filled; both orders; a sensitive field hidden
- [X] T009 [US1] Create `app/ViewModels/Vaults/RecordStatisticsViewModel.php` reading T005 and T007: title "Animals statistics" (`:plural statistics`), scope line "Family vault · Non-archived animals", trail, list links with `template`/`version` filters, "View :count animals" through `trans_choice()`, row menu entries (`Change template…` only when another active template has a published version; `Update to v:number…` only when records sit on an older version), empty state wording
- [X] T010 [P] [US1] `tests/Unit/ViewModels/Vaults/RecordStatisticsViewModelTest.php`: title and scope for Animals and People; menu entries present and absent as in spec Edge Cases; "On older versions" left out at 0
- [X] T011 [US1] Create `app/Http/Controllers/App/Vaults/RecordStatisticsController.php` (`index` for contacts and for an entity type, refusing a hidden type or a vault hiding People like `EntityController::refuseAHiddenType()`) and `FieldCompletionController.php` (`index`, validating `template` uuid, `version` integer, `order` in `lowest,form`); routes `contacts.statistics.index`, `contacts.statistics.fields.index`, `entities.statistics.index`, `entities.statistics.fields.index` in `routes/web.php`, the contact ones declared before `contacts.index` so `{filter?}` does not catch them
- [X] T012 [P] [US1] Create components `resources/views/components/meter.blade.php` (`role="meter"`, `aria-valuenow/min/max`, `aria-label`) and `tests/Feature/Views/Components/MeterTest.php` asserting the ARIA only
- [X] T013 [US1] Create `resources/views/app/vaults/statistics/index.blade.php`, `_overview.blade.php`, `_templates.blade.php`, `_field-completion.blade.php` following the design (desktop table, stacked blocks under `sm`, field completion form reloading `_field-completion` with `x-target`, working without JavaScript), using `x-page-header`, `x-page-section`, `x-actions-menu`, `x-badge`, `x-empty-state`, `x-meter`; first-line Blade comment and `@var` per `.claude/rules/code-style.md`
- [X] T014 [US1] Add Statistics beside the add action, and in the header menu with its label on narrow screens, in `resources/views/app/vaults/entities/index.blade.php` and `resources/views/app/vaults/contacts/index.blade.php`
- [X] T015 [US1] Add `template` (uuid) and `version` (integer) filters to `app/ViewModels/Vaults/EntitiesViewModel.php` and `ContactsViewModel.php` and their controllers, kept in pagination links, shown as removable `x-chip`s with "Clear filters"; the entity list gains a Template column "Pet · v3"
- [X] T016 [P] [US1] Tests: `tests/Unit/ViewModels/Vaults/EntitiesViewModelTest.php` and `ContactsViewModelTest.php` (filters narrow and combine), `tests/Feature/Controllers/App/Vaults/RecordStatisticsControllerTest.php` (renders "Animals statistics", wrong vault 404, hidden type 404), `FieldCompletionControllerTest.php` (validation)

## Phase 4: User Story 2, the bulk change (P1), second delivery

**Independent Test**: quickstart checks 4 to 8.

- [X] T017 [US2] Migration `database/migrations/2026_11_25_000001_create_template_changes_table.php` and `_000002_create_template_change_items_table.php` exactly as `data-model.md`; models `app/Models/TemplateChange.php` (`#[RouteKey('uuid')]`, `ResolvesModelInVault`) and `TemplateChangeItem.php` with full docblocks; factories; enums `TemplateChangeOutcomeEnum.php` (`Moved`, `Skipped`, `Failed`) and `TemplateChangeReasonEnum.php`; no status column, a change runs while `ended_at` is null
- [X] T018 [US2] In `app/Actions/MoveRecordToStructureVersion.php` add the optional last parameter `?FieldCorrespondence $correspondence = null`, used instead of building one (research R6); existing tests pass unchanged
- [X] T019 [US2] Create `app/Helpers/BulkTemplateChangePreview.php` (research R4) returning the preview shape of `contracts/api.md`: the batch (non-archived records of the source template on the chosen versions), one read of distinct `(valuable_id, field_id, is_detached)` for values and rows, grouped by signature, `FieldCorrespondence::counterparts()` once per signature, reminders from one joined query, records held by a running `TemplateChange` as blocked, sensitive fields counted in `hidden_count`
- [X] T020 [P] [US2] `tests/Unit/Helpers/BulkTemplateChangePreviewTest.php`: the six counts 4, 2, 2, 0, 1, 0 and "Stable name · 2"; for each record the result equals `TemplateChangePreview` for that record (SC-003); a record of a running change blocked; nothing written
- [X] T021 [US2] Create `app/Actions/CreateTemplateChange.php`: user, `RecordKind`, source `Template`, optional version numbers, target `Template`; refuses another account (`ModelNotFoundException`), a target of another kind, turned off or unpublished (reuse the wording of `ChangeContactTemplate::refusal()`), a source equal to target with no older version selected, and an empty batch; creates the `TemplateChange` and its items in chunks of 1,000, skipping blocked records; dispatches `ChangeTemplateOfRecords` once per 200 items
- [X] T022 [P] [US2] `tests/Unit/Actions/CreateTemplateChangeTest.php`: each refusal; items equal the batch; "Update to v3" takes only v2 records; jobs pushed (`Queue::fake()`)
- [X] T023 [US2] Create `app/Jobs/ChangeTemplateOfRecords.php` (research R5, R6): takes up to 200 items without outcome, timeout under the queue's `retry_after`, `failed()` marking what is left as failed, one shared `FieldCorrespondence`, `withoutSyncingToSearch()` then one `searchable()`; per item: skipped with `This :singular’s template changed after the preview.` when not on `from_version_id`, archived or deleted; otherwise `MoveRecordToStructureVersion(writesToLog: false)`, `Throwable` marks it failed with a reason and goes on; the run that finds no item left sets counts and `ended_at`; the one `LogUserAction` is written by the action at confirmation (research R7)
- [X] T024 [US2] Add `UserActionEnum::TemplateChangeStarted`, written by the action at confirmation, in `app/Enums/UserActionEnum.php`, worded with `trans_choice()` per `.claude/rules/code-style.md` (research R7)
- [X] T025 [P] [US2] `tests/Unit/Jobs/ChangeTemplateOfRecordsTest.php`: records moved and snapshotted, values set aside not deleted (SC-004), a record changed after the preview skipped, a failure on one leaves the others moved, a second run moves nothing more, one log entry, counts right
- [X] T026 [US2] Create component `resources/views/components/steps.blade.php` (`aria-current="step"`) and `tests/Feature/Views/Components/StepsTest.php`
- [X] T027 [US2] Create `app/ViewModels/Vaults/TemplateChangeViewModel.php` (select, preview and result data, "Move :count animals" and "3 animals moved · 1 skipped" through `trans_choice()`) and its test `tests/Unit/ViewModels/Vaults/TemplateChangeViewModelTest.php`
- [X] T028 [US2] Create `app/Http/Controllers/App/Vaults/TemplateChangeController.php` (`new`, `preview`, `create`, `show`) and routes `contacts.templateChanges.*` and `entities.templateChanges.*` in `routes/web.php`; refusals return to step 1 with `withErrors()`
- [X] T029 [US2] Create `resources/views/app/vaults/template-changes/new.blade.php`, `preview.blade.php`, `show.blade.php`, `_result.blade.php` following the design (4a to 4d): version filter updating the count, destinations with their version, `x-help` linking the guide, warning `x-notice` for blocked records, effects disclosure (`aria-expanded`), result block polling itself every two seconds while `Running` then announced with `aria-live`
- [X] T030 [P] [US2] `tests/Feature/Controllers/App/Vaults/TemplateChangeControllerTest.php`: each step renders, one refusal shown on the form, `create` redirects to `show`, wrong vault 404

## Phase 5: User Story 3, API and MCP (P2)

**Independent Test**: spec User Story 3 against the seeded example.

- [X] T031 [US3] Create `app/Http/Controllers/Api/RecordStatisticsController.php`, `FieldCompletionController.php`, `TemplateChangePreviewController.php`, `TemplateChangeController.php` and resources in `app/Http/Resources/`, routes in `routes/api.php` exactly as `contracts/api.md`; sensitive fields named only with the `sensitive-fields` scope
- [X] T032 [P] [US3] One test file per controller in `tests/Feature/Controllers/Api/`: `$jsonStructure`, validation 422, another account 404, POST answers 202
- [X] T033 [US3] Create the five tools of `contracts/api.md` in `app/Mcp/Tools/` through `MirrorsAnApiCapability::answerThroughApi()`, read-only ones annotated, register them, add them to `McpDocsViewModel::FAMILIES` (`.claude/rules/mcp.md`; no test file per tool)

## Phase 6: Polish

- [X] T034 [P] Help centre page `docs/portal/en/9-templates-and-customisation/24-statistics-and-template-changes.md` with the `writer` skill; link it from `4-change-a-template.md` and `docs/portal/en/3-people/7-change-the-template.md`
- [X] T035 [P] API reference `resources/docs/api/42-record-kinds.php` and an entry in `resources/docs/api-changelog/2026-11-25.php`, with the `api-docs-writer` skill
- [X] T036 Translate every new string with `php artisan monica:localize` and the `translations` skill (subagents on sonnet); `php artisan monica:localize --check` passes
- [X] T037 What's new entry with the `changelog` skill
- [X] T038 Time the 10,000 record seed of `quickstart.md` against SC-001 and SC-002 and record the figures in the pull request
- [X] T039 Run the `definition-of-done` skill, the `accessibility-reviewer` and `rules-reviewer` agents, the whole suite, PHPStan and `vendor/bin/pint --dirty --format agent`

## Dependencies

- T002, T003 block everything. US1 (T005 to T016) is the MVP and ships alone if needed.
- US2 needs T003 and T012 (meter); T017 before T019, T021, T023; T018 before T023; T019 before T027 to T029.
- US3 needs T005, T007, T019, T021, T023. Polish last.

## Parallel examples

- US1: T006, T008, T010, T012 together once their subject exists.
- US2: T020, T022, T025, T026 together; T029 after T027 and T028.
- Polish: T034, T035 alongside T036.
