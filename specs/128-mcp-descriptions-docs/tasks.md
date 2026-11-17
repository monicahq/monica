# Tasks: Clearer MCP tool descriptions and a redesigned MCP documentation page

**Input**: [spec.md](spec.md), [plan.md](plan.md), [research.md](research.md), [contracts/new-endpoints.md](contracts/new-endpoints.md), [quickstart.md](quickstart.md)

**Tests**: required (constitution III). Controller tests live in `tests/Feature/Controllers/Api/`, tool tests in `tests/Unit/Mcp/Tools/<Tool>Test.php`. "Migrate a tool" below always means: `handle()` validates only the addressing parameters, looks the record up through `MirrorsAnApiCapability`, returns `answerThroughApi()`; the schema lists the endpoint's fields under their API names (R2); descriptions use the R3 vocabulary; its file in `tests/Unit/Mcp/Tools/` is deleted unless the tool adds something beyond the lookup (see "In the tests" in `.claude/rules/mcp.md`).

## Phase 1: Setup

- [X] T001 Replace "An update replaces" with "An update keeps what is left out and clears what is sent as null" and drop "Most tools are still copies" in .claude/rules/mcp.md (its "In the tests" section was already rewritten on 2026-11-17)
- [X] T001a Test `answerThroughApi()` and every lookup of the trait (body passed on, `data` removed, refusal relayed, empty answer, another account's record does not exist) in tests/Unit/Mcp/Tools/MirrorsAnApiCapabilityTest.php
- [X] T001b Walk every registered tool, send another account's id in each id parameter of its schema and expect "does not exist" in tests/Feature/Mcp/McpCompartmentTest.php

## Phase 2: Foundational (the update rule, R1; blocks every tool migration)

Each task: fields become `sometimes`, the controller passes `array_key_exists($key, $validated) ? $validated[$key] : <stored value>`, a name stays non-null, a boolean left out keeps its value; add the tests "a field left out keeps its value" and "null clears it" to the matching `<Controller>Test.php`.

- [X] T002 [P] Keep what is left out in MeController@update and MeContactListViewController@update (app/Http/Controllers/Api/MeController.php, MeContactListViewController.php)
- [X] T003 [P] Keep what is left out in NotificationChannelController@update and NotificationPreferenceController@update (app/Http/Controllers/Api/)
- [X] T004 [P] Keep is_default, structure_version_id and description when left out in app/Http/Controllers/Api/VaultController.php
- [X] T005 [P] Keep what is left out in app/Http/Controllers/Api/EntityTypeController.php and app/Http/Controllers/Api/TemplateController.php
- [X] T006 [P] Keep category, description, icon, colour when left out in app/Http/Controllers/Api/LifeEventTypeController.php
- [X] T007 [P] Keep groups and fields when left out in app/Http/Controllers/Api/LifeEventTypeFieldController.php
- [X] T008 [P] Keep roles, category, name when left out (role_b no longer falls back to role_a) in app/Http/Controllers/Api/RelationshipTypeController.php
- [X] T009 [P] Make name `sometimes` in LifeEventCategoryController, RelationshipCategoryController, GroupNoteController (body), StructureVersionPageController (label) under app/Http/Controllers/Api/
- [X] T010 [P] Keep parent_id, emoji, address when left out in app/Http/Controllers/Api/LocationController.php
- [X] T011 [P] Keep what is left out in PossessionTypeController, PossessionFieldGroupController, PossessionFieldController under app/Http/Controllers/Api/PossessionTypes/
- [X] T012 [P] Keep what is left out in app/Http/Controllers/Api/PossessionTags/PossessionTagController.php and app/Http/Controllers/Api/PossessionConditions/PossessionConditionController.php
- [X] T013 [P] Keep status, quantity, is_featured, location, tags and every other field when left out in app/Http/Controllers/Api/Possessions/PossessionController.php
- [X] T014 [P] Keep help_text, placeholder, the five booleans, value list, defaults, options, validation, repeat limits when left out in app/Http/Controllers/Api/FieldController.php
- [X] T015 [P] Keep title_override and is_collapsed_by_default when left out in app/Http/Controllers/Api/StructureVersionSectionController.php
- [X] T016 [P] Keep columns when left out in app/Http/Controllers/Api/ContactListColumnController.php
- [X] T017 [P] Keep each name part when left out in app/Http/Controllers/Api/ContactNameController.php and the list, nickname, maiden_name in ContactNameRepresentationController.php
- [X] T018 [P] Keep each field of the section left out in app/Http/Controllers/Api/ContactSectionController.php
- [X] T019 [P] Keep type, date, participants, title, narrative, location, duration when left out in app/Http/Controllers/Api/LifeEventController.php
- [X] T020 [P] Keep name, cadence, time when left out in app/Http/Controllers/Api/ContactReminderController.php and GroupReminderController.php
- [X] T021 State the update rule once in resources/docs/api/01-getting-started.php and remove every per field "cleared when left out" in resources/docs/api/*.php
- [X] T022 Add a release listing the 30 endpoints of T002 to T020 and "send null to clear" in resources/docs/api-changelog/2026-11-17.php

**Checkpoint**: `php artisan test --compact tests/Feature/Controllers/Api` passes.

## Phase 3: User Story 1 - An assistant does not misread an empty answer (P1) MVP

**Independent test**: quickstart "The reported bug".

- [X] T023 [US1] Add `GET vaults/{vault}/reminders` (`api.v1.reminders`, optional `about`: contact or group, group reminders only with the groups flag) in routes/api.php and app/Http/Controllers/Api/VaultReminderController.php, with tests in tests/Feature/Controllers/Api/VaultReminderControllerTest.php
- [X] T024 [US1] Document it in resources/docs/api/20-reminders.php and in the T022 release
- [X] T025 [US1] Create app/Mcp/Tools/ListVaultReminders.php mirroring `api.v1.reminders` and register it in app/Mcp/Servers/MonicaServer.php
- [X] T026 [US1] State the 7 day window and that an empty list covers only that window in the description of app/Mcp/Tools/ListVaultReminderOccurrences.php, and name list-vault-reminders as the way to list every reminder
- [X] T027 [US1] Migrate the Reminders family: ListContactReminders, GetContactReminder, ListContactReminderOccurrences, ListGroupReminders, GetGroupReminder, ListGroupReminderOccurrences, ListVaultReminderOccurrences, CreateContactReminder, UpdateContactReminder, DeleteContactReminder, AnswerReminderOccurrence, CreateGroupReminder, UpdateGroupReminder, DeleteGroupReminder, AnswerGroupReminderOccurrence in app/Mcp/Tools/
- [X] T028 [US1] Describe `interval` as "the number of cadence units between two dates, from 1 to 99: weekly with 2 is every two weeks" and "told" as "notified" in the four reminder writing tools of app/Mcp/Tools/

## Phase 4: User Story 2 - Every term means one thing (P2)

**Independent test**: `grep -niE "the address of|identifier of|account journal|activit" app/Mcp` returns only postal or email addresses.

- [X] T029 [P] [US2] Migrate Vaults and Search: ListVaults, SearchRecords (`group` described as a result group) in app/Mcp/Tools/
- [X] T030 [P] [US2] Migrate Contacts: ListContacts, GetContact, GetContactMarkdown, GetMeContact, ListContactNameRepresentations, CreateContact, UpdateContactName, UpdateContactNameRepresentations, SetContactFavourite in app/Mcp/Tools/
- [X] T031 [P] [US2] Migrate Relationships: ListContactRelationships, GetContactRelationship, CreateContactRelationship, UpdateContactRelationship, DeleteContactRelationship in app/Mcp/Tools/
- [X] T032 [P] [US2] Migrate Life events: ListLifeEvents, GetLifeEvent, CreateLifeEvent, UpdateLifeEvent, DeleteLifeEvent, RestoreLifeEvent in app/Mcp/Tools/
- [X] T033 [P] [US2] Migrate Groups, saying "life event" not "activity": ListGroups, GetGroup, ListGroupMembers, ListContactGroups, ListGroupFiles, ListGroupNotes, GetGroupNote, ListGroupLifeEvents, CreateGroup, UpdateGroup, DeleteGroup, RestoreGroup, AddGroupMember, UpdateGroupMember, RemoveGroupMember, RemoveGroupFile, CreateGroupNote, UpdateGroupNote, DeleteGroupNote, CreateGroupLifeEvent in app/Mcp/Tools/
- [X] T034 [P] [US2] Migrate Templates: ListTemplates, GetTemplate, ListTemplateVersions, GetTemplateVersion, CreateTemplate, UpdateTemplate, DuplicateTemplate, SetTemplateActivation, SetTemplatePosition, DeleteTemplate, CreateTemplateDraft, PublishTemplateVersion, DeleteTemplateDraft, AddTemplateSection, UpdateTemplateSection, RemoveTemplateSection, SetTemplateSectionPosition, CreateTemplateField, UpdateTemplateField, DeleteTemplateField, SetTemplateFieldPosition in app/Mcp/Tools/
- [X] T035 [P] [US2] Migrate Entity types: ListEntityTypes, GetEntityType, CreateEntityType, UpdateEntityType, SetEntityTypeActivation, SetEntityTypePosition, DeleteEntityType in app/Mcp/Tools/
- [X] T036 [P] [US2] Migrate Relationship vocabulary, saying "category" not "group": ListRelationshipTypes, GetRelationshipType, ListRelationshipCategories, GetRelationshipCategory, ListRelationshipCategoryTypes, CreateRelationshipType, UpdateRelationshipType, SetRelationshipTypeActivation, SetRelationshipTypePosition, DeleteRelationshipType, CreateRelationshipCategory, UpdateRelationshipCategory, SetRelationshipCategoryActivation, SetRelationshipCategoryPosition, DeleteRelationshipCategory in app/Mcp/Tools/
- [X] T037 [P] [US2] Migrate Life event vocabulary, saying "category" not "group": ListLifeEventCategories, GetLifeEventCategory, ListLifeEventCategoryTypes, ListLifeEventTypes, GetLifeEventType, GetLifeEventTypeFields, CreateLifeEventType, UpdateLifeEventType, SetLifeEventTypeActivation, SetLifeEventTypePosition, ReplaceLifeEventTypeFields, DeleteLifeEventType, CreateLifeEventCategory, UpdateLifeEventCategory, SetLifeEventCategoryActivation, SetLifeEventCategoryPosition, DeleteLifeEventCategory in app/Mcp/Tools/
- [X] T038 [P] [US2] Bring Locations to the R3 wording and the keep rule: ListLocations, GetLocation, CreateLocation, UpdateLocation, DeleteLocation in app/Mcp/Tools/
- [X] T039 [P] [US2] Migrate Possession types: ListPossessionTypes, GetPossessionType, ExportPossessionType, CreatePossessionType, UpdatePossessionType, DeletePossessionType, ImportPossessionType, CreatePossessionFieldGroup, UpdatePossessionFieldGroup, DeletePossessionFieldGroup, SetPossessionFieldGroupPosition, CreatePossessionField, UpdatePossessionField, DeletePossessionField, SetPossessionFieldPosition in app/Mcp/Tools/
- [X] T040 [P] [US2] Migrate Possessions, part one: ListPossessions, GetPossession, CreatePossession, UpdatePossession, DeletePossession, SetPossessionPosition, SetPossessionValues, MovePossession, ListPossessionLocationStays, GetPossessionLocationStay, UpdatePossessionLocationStay, DeletePossessionLocationStay in app/Mcp/Tools/, rewriting "counts one", "leaves every list" and the stays ordering sentence
- [X] T041 [P] [US2] Migrate Possessions, part two: ListPossessionTransactions, GetPossessionTransaction, CreatePossessionTransaction, UpdatePossessionTransaction, DeletePossessionTransaction, ListPossessionInsuranceRecords, GetPossessionInsuranceRecord, CreatePossessionInsuranceRecord, UpdatePossessionInsuranceRecord, DeletePossessionInsuranceRecord, ListPossessionMaintenanceRecords, GetPossessionMaintenanceRecord, CreatePossessionMaintenanceRecord, UpdatePossessionMaintenanceRecord, DeletePossessionMaintenanceRecord in app/Mcp/Tools/
- [X] T042 [P] [US2] Migrate Possessions, part three: ListPossessionValuations, GetPossessionValuation, CreatePossessionValuation, DeletePossessionValuation, ListPossessionProvenanceEvents, GetPossessionProvenanceEvent, CreatePossessionProvenanceEvent, UpdatePossessionProvenanceEvent, DeletePossessionProvenanceEvent, ListPossessionLoans, GetPossessionLoan, CreatePossessionLoan, UpdatePossessionLoan, DeletePossessionLoan in app/Mcp/Tools/
- [X] T043 [P] [US2] Migrate Possession tags and conditions: ListPossessionTags, GetPossessionTag, CreatePossessionTag, UpdatePossessionTag, DeletePossessionTag, ListPossessionConditions, GetPossessionCondition, CreatePossessionCondition, UpdatePossessionCondition, SetPossessionConditionPosition, DeletePossessionCondition in app/Mcp/Tools/
- [X] T044 [P] [US2] Migrate Knowledge: ListKnowledge, SearchKnowledge, GetKnowledgeNote, GetKnowledgeList, CreateKnowledgeFolder, UpdateKnowledgeFolder, DeleteKnowledgeFolder, CreateKnowledgeNote, UpdateKnowledgeNote, DeleteKnowledgeNote, CreateKnowledgeList, UpdateKnowledgeList, DeleteKnowledgeList, CreateKnowledgeListColumn, UpdateKnowledgeListColumn, DeleteKnowledgeListColumn, CreateKnowledgeListItem, UpdateKnowledgeListItem, DeleteKnowledgeListItem in app/Mcp/Tools/
- [X] T045 [P] [US2] Migrate Account logs and Support: ListLogs, GetLog, ListEmailsSent, GetEmailSent, ListSupportRequests, GetSupportRequest, CreateSupportRequest, AddSupportMessage, SolveSupportRequest, ListSupportQueue, GetSupportQueueRequest, SendSupportReply, AddSupportNote, ChangeSupportRequestStatus in app/Mcp/Tools/
- [X] T046 [US2] Reword the server instructions with the R3 vocabulary and the update rule in app/Mcp/Servers/MonicaServer.php
- [X] T047 [US2] Describe each mirrored endpoint's fields with the R3 vocabulary in the matching resources/docs/api/*.php

## Phase 5: User Story 3 - Every parameter can be filled in (P2)

**Independent test**: every string parameter whose endpoint validates `Rule::in`/`Rule::enum` declares `->enum()`; every id parameter names a tool returning it.

- [X] T048 [US3] Declare `->enum()` on name_order (UpdateContactName), icon (UpdatePossessionType, UpdatePossessionCondition) and deposit_currency (CreatePossessionLoan, UpdatePossessionLoan) in app/Mcp/Tools/ (after T030, T039, T042, T043)
- [X] T049 [US3] Add a test in tests/Feature/Mcp/McpCatalogueTest.php failing when a tool parameter names an id without naming the tool that gives it
- [X] T050 [P] [US3] Create app/Mcp/Tools/ListContactCategories.php mirroring `api.v1.contactCategories`, register it, and point `categories` of ListContacts at it
- [X] T051 [P] [US3] Add `GET value-lists` (`api.v1.valueLists`) in routes/api.php and app/Http/Controllers/Api/ValueListController.php per contracts/new-endpoints.md, with tests and a section in resources/docs/api/11-templates.php
- [X] T052 [US3] Create app/Mcp/Tools/ListValueLists.php mirroring `api.v1.valueLists`, register it, and point `value_list_id` of CreateTemplateField and UpdateTemplateField at it

## Phase 6: User Story 4 - A redesigned MCP documentation page (P3)

**Independent test**: quickstart "The documentation page".

- [X] T053 [US4] Build the catalogue from MonicaServer's tools through `Tool::toArray()` (name, description, parameters with type and required, read only mark) grouped by the 20 design families, and delete `toolGroups()` in app/ViewModels/Docs/McpDocsViewModel.php
- [X] T054 [US4] Test that every registered tool belongs to one family and that the catalogue carries each tool's server description in tests/Unit/ViewModels/Docs/McpDocsViewModelTest.php
- [X] T055 [US4] Add `docs/mcp/tools/{family}` (`docs.mcp.tools.show`) in routes/web.php and app/Http/Controllers/App/Docs/McpDocsController.php, and list it as public in tests/Feature/RouteProtectionTest.php
- [X] T056 [US4] Rebuild resources/views/app/docs/mcp.blade.php as the Getting started page of `MCP Docs.dc.html`: server address with x-copy-button, the two setup steps, client tabs (`role="tablist"`), connection check, access notice, template example, "On this page"
- [X] T057 [US4] Create resources/views/app/docs/mcp-tools.blade.php for a family: tool table of contents with the changes data mark, rows with `aria-expanded` showing parameters, Expand all, previous and next family
- [X] T058 [US4] Create the shared shell (top bar, breadcrumb, tool count, Alpine search over server rendered links, sidebar of families, View as Markdown and Get an API key buttons) in resources/views/app/docs/_mcp-shell.blade.php, used by both pages
- [X] T059 [US4] Render docs/mcp.md from the same catalogue in McpDocsViewModel::markdown() and its test in tests/Feature/Controllers/App/Docs/McpDocsMarkdownControllerTest.php
- [X] T060 [US4] Update tests/Feature/Controllers/App/Docs/McpDocsControllerTest.php: both pages answer, a family that does not exist answers not found, the copy a reader recognises is present
- [X] T061 [US4] Run the accessibility-reviewer agent on resources/views/app/docs/mcp*.blade.php and fix what it reports

## Phase 7: Polish

- [X] T062 Run `php artisan monica:localize`, translate what it reports through Sonnet subagents, and pass `--check`
- [X] T063 Run the whole suite, `vendor/bin/phpstan analyse --no-progress` and `vendor/bin/pint --dirty --format agent`
- [X] T064 Run the rules-reviewer agent on the branch diff and fix what it reports
- [ ] T065 Walk through quickstart.md by hand

## Dependencies

- T001, T001a and T001b first: no tool test is deleted before the wiring is proven once. Phase 2 blocks every tool migration (T027, T029 to T045), since a migrated tool inherits its endpoint's update rule.
- T022 before T024. T023 before T025. T051 before T052. T048 after the migrations it edits.
- Phase 6 last: the catalogue shows what Phases 3 to 5 wrote.
- US1, US2 and US3 are otherwise independent of each other.

## Parallel execution

- Phase 2: T002 to T020 touch different controllers and tests.
- US2: T029 to T045 touch disjoint tool files, one family each.
- US3: T050 and T051.

## Implementation strategy

MVP is Phase 1, the reminder endpoints of Phase 2 (T020) and Phase 3: it closes monicahq/monica#7990. Then Phase 2 whole, US2, US3, the page. One pull request per phase keeps each one reviewable.
