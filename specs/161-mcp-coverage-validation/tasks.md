# Tasks: MCP coverage and stricter section values

**Input**: [plan.md](plan.md), [spec.md](spec.md), [research.md](research.md), [contracts/api.md](contracts/api.md)

**Tests**: included, as constitution principle III requires. Each test sits in the layer that owns
the rule (`.claude/rules/testing.md`).

## Format: `[ID] [P?] [Story] Description`

## Phase 1: Setup

None. No dependency, no migration (R14).

## Phase 2: Foundational

None. The four stories share no prerequisite; each can ship alone.

## Phase 3: User Story 1, a wrong section payload is refused (P1) 🎯 MVP

**Goal**: FR-001, FR-002, FR-003, FR-013. **Independent test**: quickstart rows 1 to 5.

- [X] T001 [US1] In `app/Helpers/FieldValueRules.php` `for()`, emit `fields => ['nullable', 'array:<ids>']` over the ids of input fields without a parent, `fields.{id}.* => ['array:id,values,labels,label_wordings,unknown_year']` and `fields.{id}.*.values => ['nullable', 'array:<child input ids>']` (R1, R4)
- [X] T002 [US1] Add cases to `tests/Unit/Helpers/FieldValueRulesTest.php`: an unknown top level id, an unknown row key (child ids beside `values`), a row that is a string, an unknown child id are refused; a row without `values` and one with an empty `values` pass
- [X] T003 [US1] Add one test to `tests/Feature/Controllers/Api/ContactSectionControllerTest.php` sending a row with child ids beside `values`, asserting a 422 and that the section is unchanged
- [X] T004 [US1] In `app/Actions/SetFieldValues.php`, pass a row's `unknown_year[<child id>]` to the value writer of that child instead of the top level `yearIsUnknown` (R3)
- [X] T005 [US1] Add a test to `tests/Unit/Actions/SetFieldValuesTest.php`: a repeated date saved with `unknown_year` is stored with `year_is_unknown` true
- [X] T006 [P] [US1] In `app/Mcp/Tools/UpdateContactSection.php`, extend the `fields` description with the row shape of contracts/api.md, including `unknown_year`, and a one line example
- [X] T007 [P] [US1] In `resources/docs/api/06-contacts.php` (the section update entry), document the row shape and the 422 for an unknown key
- [X] T008 [US1] Run `composer test:parallel`; rewrite any section form test that relied on an unknown key being ignored

## Phase 4: User Story 2, delete a contact and set the list columns (P2)

**Goal**: FR-004, FR-005. **Independent test**: quickstart row 8 for these three tools.

- [X] T009 [P] [US2] Create `app/Mcp/Tools/DeleteContact.php` mirroring `api.v1.contacts.destroy`, `IsDestructive`, answering `__('The contact is deleted.')`, its description saying the assistant cannot bring it back (follow `DeleteGroup.php`)
- [X] T010 [P] [US2] Create `app/Mcp/Tools/GetContactListColumns.php` mirroring `api.v1.contactListColumns.show`, read only
- [X] T011 [P] [US2] Create `app/Mcp/Tools/SetContactListColumns.php` mirroring `api.v1.contactListColumns.update`, its schema describing `columns[]` with `source`, `section`, `field_key`, `heading`, `is_visible` as the controller validates them
- [X] T012 [US2] Register the three tools in `app/Mcp/Servers/MonicaServer.php` and add them to the `Contacts` group in `app/ViewModels/Docs/McpDocsViewModel.php`
- [X] T013 [US2] Dropped: no delete tool has a test of its sentence (`DeleteGroup`, `DeleteLocation`), so the code already settles it; covered by the walk over every registered tool

## Phase 5: User Story 3, value lists through the API and MCP (P2)

**Goal**: FR-006 to FR-009. **Independent test**: quickstart rows 5 to 8.

- [X] T014 [P] [US3] Create `app/Http/Resources/ValueListResource.php` and `ValueListItemResource.php` with the shapes of contracts/api.md (item: `label`, `country_code` only for the countries list, `is_active`, `position`) (R8)
- [X] T015 [US3] Switch `ValueListController::index` in `app/Http/Controllers/Api/ValueListController.php` to the resources, keeping its `{data, meta}` shape
- [X] T016 [US3] Add `create`, `update`, `destroy` to `app/Http/Controllers/Api/ValueListController.php`, calling `CreateValueList`, `UpdateValueList` (description and `allows_user_values` unchanged), `DestroyValueList`; lists looked up by id among the account's shared lists, else 404; `AuthorizationException` becomes 422 on `valueList`
- [X] T017 [P] [US3] Create `app/Http/Controllers/Api/ValueListItemController.php` with `create`, `update`, `destroy`, validating `label` (nullable, `required_without:country_code`, ≤255) and `country_code` (uppercased, size 2, `CountryCodes::known()`) as the settings controller does; the item looked up inside its list, else 404; refusals 422 on `valueList` or `valueListItem`
- [X] T018 [P] [US3] Create `app/Http/Controllers/Api/ValueListItemActivationController.php` (`active` required boolean, `EnableValueListItem` or `DisableValueListItem`) and `ValueListItemPositionController.php` (`position` required integer ≥1, `UpdateValueListItemPosition`)
- [X] T019 [US3] Add the eight routes of contracts/api.md to `routes/api.php` next to `value-lists`, one per line, ids constrained to `[0-9]+`
- [X] T020 [P] [US3] Create `tests/Feature/Controllers/Api/ValueListItemControllerTest.php`, `ValueListItemActivationControllerTest.php`, `ValueListItemPositionControllerTest.php` and extend `ValueListControllerTest.php`: each route answers with its shape, inline validation refuses, one policy refusal reaches a 422, an item of another list and a list that is not shared answer 404
- [X] T021 [US3] Add the two lookups `valueList(Request, string)` and `valueListItem(ValueList, string)` to `app/Mcp/Tools/MirrorsAnApiCapability.php`, shared lists only, with the translated "does not exist" sentence
- [X] T022 [P] [US3] Create the eight tools in `app/Mcp/Tools/`: `CreateValueList`, `UpdateValueList`, `DeleteValueList`, `CreateValueListItem`, `UpdateValueListItem`, `DeleteValueListItem`, `SetValueListItemActivation`, `SetValueListItemPosition`, each mirroring its route with the annotations of `docs/MCP_ANNOTATIONS.md`
- [X] T023 [US3] Register the eight tools in `app/Mcp/Servers/MonicaServer.php` and add them to the `Templates` group, after `list-value-lists`, in `app/ViewModels/Docs/McpDocsViewModel.php`
- [X] T024 [US3] Extend `tests/Unit/Mcp/MirrorsAnApiCapabilityTest.php` for the two lookups (found, another account, not shared)
- [X] T025 [P] [US3] Document the eight endpoints in `resources/docs/api/11-templates.php` beside `value-lists-index`, with the `api-docs-writer` skill

## Phase 6: User Story 4, durations up to a year (P3)

**Goal**: FR-010 to FR-012. **Independent test**: quickstart rows 9 and 10.

- [X] T026 [US4] Create `app/Helpers/Duration.php` with `LONGEST_IN_MINUTES = 525600` and `wording(int $minutes): string`, joining with a space the non-zero parts among `trans_choice(':count week|:count weeks')`, `':count day|:count days'`, `':count hour|:count hours'`, `':count minute|:count minutes'` (R11)
- [X] T027 [US4] Create `tests/Unit/Helpers/DurationTest.php`: 1 → "1 minute", 90 → "1 hour 30 minutes", 10080 → "1 week", 131400 → "13 weeks 6 hours", 525600 → "52 weeks 1 day"
- [X] T028 [US4] Replace `max:100000` with `'max:'.Duration::LONGEST_IN_MINUTES` in `app/Helpers/FieldValueRules.php`, `app/Http/Requests/SaveLifeEventRequest.php`, `app/Http/Controllers/Api/LifeEventController.php`, `app/Http/Controllers/Api/GroupLifeEventController.php`
- [X] T029 [US4] Replace `trans_choice(':count minute|:count minutes', …)` with `Duration::wording(…)` in `app/Helpers/FieldValueFormatter.php`, `app/ViewModels/Vaults/LifeEventDetailsViewModel.php`, `app/ViewModels/Vaults/ContactTimelineBlock.php`, `app/ViewModels/Vaults/ContactMarkdownViewModel.php`
- [X] T030 [P] [US4] Add `max` from the constant to the duration inputs in `resources/views/components/field-input.blade.php` and `resources/views/app/vaults/contacts/_life-event-panel.blade.php` (R12)
- [X] T031 [P] [US4] In `app/ViewModels/Settings/Account/Templates/FieldCatalogueViewModel.php`, add to the Duration description one sentence saying something that comes back, such as a visit every three months, is recorded with a reminder (R13)
- [X] T032 [P] [US4] Update the `duration_in_minutes` maximum in `resources/docs/api/22-life-events.php` and `resources/docs/api/29-group-activities.php`, and the `duration_in_minutes` description in `app/Mcp/Tools/CreateLifeEvent.php`, `UpdateLifeEvent.php` and `CreateGroupLifeEvent.php` if it states a maximum
- [X] T033 [US4] Fix the tests asserting "N minutes" for a duration over an hour, found with `grep -rn "minutes" tests/`

## Phase 7: Polish

- [X] T034 Run `php artisan monica:localize`, then translate the new strings into every locale with Sonnet subagents (`translations` skill), then `php artisan monica:localize --check`
- [X] T035 [P] Add an entry to `resources/docs/api-changelog/2026-11-25.php`: the eight endpoints, the stricter row rule, the duration cap, the item fields
- [X] T036 [P] Write a What's new entry with the `changelog` skill and a line in `docs/FEATURES.md`
- [X] T037 Run `vendor/bin/pint --dirty --format agent`, then the `definition-of-done` skill, then the `rules-reviewer` agent

## Dependencies

- US1, US2, US3, US4 are independent; US1 first as the MVP.
- T001 before T002, T003 and T008; T004 before T005.
- T014 before T015 to T018; T016 to T019 before T020; T021 before T022; T022 before T023.
- T026 before T027 to T029. T028 touches `FieldValueRules.php` after T001 when both stories run.
- Phase 7 after every story it covers.

## Parallel examples

- US1: T006 and T007 beside T001 to T005.
- US2: T009, T010, T011 together.
- US3: T017, T018 and T025 together once T014 is done; the eight tools of T022 together.
- US4: T030, T031, T032 beside T029.

## Strategy

Ship US1 alone if needed: it stops the data loss. US2 is an hour. US3 is the bulk. US4 last.
