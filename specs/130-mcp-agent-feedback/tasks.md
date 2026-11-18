# Tasks: Agent feedback through MCP

Tests are included: constitution III makes them non-negotiable. Paths and field limits: [data-model.md](data-model.md), [contracts/api-and-mcp.md](contracts/api-and-mcp.md).

## Phase 1: Setup

- [X] T001 Read `.claude/rules/mcp.md`, `.claude/rules/testing.md`, `.claude/rules/components.md` and the skills `models`, `migrations`, `actions`, `controllers`, `view-models`, `views`, `api-writer`, `mcp-development`

## Phase 2: Foundational

- [X] T002 [P] Create `AgentFeedbackKindEnum` (`bug`, `missing_tool`, `unclear_description`, `improvement`) with `label()` and `values()` in app/Enums/AgentFeedbackKindEnum.php
- [X] T003 [P] Create `AgentFeedbackSeverityEnum` (`blocks_task`, `needs_workaround`, `minor`) with `label()`, `tone()` and `values()` in app/Enums/AgentFeedbackSeverityEnum.php
- [X] T004 [P] Create `AgentFeedbackStatusEnum` (`new`, `accepted`, `fixed`, `dismissed`) with `label()`, `tone()`, `values()` and `allowedTargets()` (new→accepted,dismissed; accepted→fixed,dismissed; fixed,dismissed→new) in app/Enums/AgentFeedbackStatusEnum.php
- [X] T005 [P] Test `allowedTargets()` for every status in tests/Unit/Enums/AgentFeedbackStatusEnumTest.php
- [X] T006 Write the migration for `agent_feedback_reports` exactly as data-model.md (account cascadeOnDelete, user nullOnDelete, `title` string(200), `tool` string(100) nullable, `steps` json nullable, indexes `(status, created_at)` and `tool`) in database/migrations/2026_11_18_000000_create_agent_feedback_reports_table.php
- [X] T007 Write the migration for `agent_feedback_notes` (report cascadeOnDelete, `author_user_id` nullOnDelete, `author_name`, `body`) in database/migrations/2026_11_18_000001_create_agent_feedback_notes_table.php
- [X] T008 [P] Create the `AgentFeedbackReport` model with uuid route key, enum and `steps` array casts, `account()`, `user()`, `notes()` in app/Models/AgentFeedbackReport.php
- [X] T009 [P] Create the `AgentFeedbackNote` model with `report()` and `author()` in app/Models/AgentFeedbackNote.php
- [X] T010 [P] Create factories with a state per status in database/factories/AgentFeedbackReportFactory.php and database/factories/AgentFeedbackNoteFactory.php
- [X] T011 Add `AgentFeedbackStatusChanged` and `AgentFeedbackNoteAdded` (with their labels) to app/Enums/AdministratorActionEnum.php

## Phase 3: User Story 1, an agent reports friction (P1)

Independent test: POST a complete report as a signed in user, and it is stored with status `new` and every field intact.

- [X] T012 [US1] Create the `SubmitAgentFeedback` action (account, user, validated fields, connection name; creates the report with status `new`) in app/Actions/SubmitAgentFeedback.php
- [X] T013 [US1] Test that it stores every field, the connection name and the user, and that the report goes when its account is deleted, in tests/Unit/Actions/SubmitAgentFeedbackTest.php
- [X] T014 [US1] Create `Api\AgentFeedbackController@create` with inline validation (limits from data-model.md, `steps` max 20, `outcome` in success,error, placeholder closure on `observed` per research R5) answering 201 `{id, status}` in app/Http/Controllers/Api/AgentFeedbackController.php
- [X] T015 [US1] Add `POST agent-feedback` named `agentFeedback.create` with `throttle:10,1` in routes/api.php
- [X] T016 [US1] Test 201 with shape, a 422 on a missing field, and a 422 on `observed: "TODO"` in tests/Feature/Controllers/Api/AgentFeedbackControllerTest.php
- [X] T017 [US1] Create the `SubmitFeedback` tool (`$mirrors = 'api.v1.agentFeedback.create'`, schema per the contract, description per the contract, including the paraphrase and placeholder rule) in app/Mcp/Tools/SubmitFeedback.php
- [X] T018 [US1] Register `SubmitFeedback` and add one line to the server instructions on when to call it in app/Mcp/Servers/MonicaServer.php

## Phase 4: User Story 2, administrators triage (P1)

Independent test: seed reports in every status, then filter, search and open one as an administrator.

- [X] T019 [US2] Create `AgentFeedbackQueueViewModel` (tab counts, status/kind/severity/query filters over title, tool, user first/last name and email, paged rows with the `#id`, age, model, user, connection, tool, note count, empty messages per tab and for filters) in app/ViewModels/Admin/AgentFeedbackQueueViewModel.php
- [X] T020 [US2] Create `AgentFeedbackReportViewModel` (badges, byline, steps with outcome, observed/expected, fix and code, person and account, session list, notes, "Same tool" newest first, allowed transitions, Markdown for copying) in app/ViewModels/Admin/AgentFeedbackReportViewModel.php
- [X] T021 [P] [US2] Test filtering, search, counts and empty states in tests/Unit/ViewModels/Admin/AgentFeedbackQueueViewModelTest.php
- [X] T022 [P] [US2] Test "Same tool", allowed transitions and the Markdown in tests/Unit/ViewModels/Admin/AgentFeedbackReportViewModelTest.php
- [X] T023 [US2] Create `App\Admin\AgentFeedbackController@index/show` in app/Http/Controllers/App/Admin/AgentFeedbackController.php
- [X] T024 [US2] Add `admin/agent-feedback` and `admin/agent-feedback/{agentFeedbackReport}` (whereUuid) in routes/administrator.php
- [X] T025 [US2] Build the list from the design (header, search, `aria-pressed` status tabs with counts, kind and severity selects, rows, empty state, Alpine Ajax swap) in resources/views/app/admin/agent-feedback/index.blade.php and _row.blade.php
- [X] T026 [US2] Build the detail from the design (breadcrumb, badges, "What the person asked" with the line "Paraphrased by the agent, without names or details from the vault.", steps, observed/expected, fix, notes, "On behalf of", "Session", "Same tool"), with all text printed through `{{ }}`, in resources/views/app/admin/agent-feedback/show.blade.php
- [X] T027 [US2] Add `newAgentFeedbackCount()` in app/ViewModels/Admin/AdminNavigationViewModel.php and the "Agent feedback" sidebar entry (icon `memory`, count, `trans_choice` count label) after Changelog in resources/views/layouts/admin.blade.php
- [X] T028 [US2] Create `Api\Admin\AgentFeedbackController@index/show` reusing the view model queries, paged `{data, meta}`, in app/Http/Controllers/Api/Admin/AgentFeedbackController.php
- [X] T029 [US2] Add the admin GET|QUERY routes under `administrator`, `administration` in routes/api.php
- [X] T030 [US2] Add the `agentFeedbackReport()` lookup (across accounts) in app/Mcp/Tools/MirrorsAnApiCapability.php
- [X] T031 [P] [US2] Create `ListAgentFeedback` and `GetAgentFeedback` tools, registered only for administrators, in app/Mcp/Tools/ListAgentFeedback.php and app/Mcp/Tools/GetAgentFeedback.php
- [X] T032 [US2] Test the web screens (the list renders, the detail renders, not found for a non-administrator) in tests/Feature/Controllers/App/Admin/AgentFeedbackControllerTest.php and add the URLs to the AdminAccessTest provider
- [X] T033 [US2] Test the admin API list and show, with their shapes, in tests/Feature/Controllers/Api/Admin/AgentFeedbackControllerTest.php

## Phase 5: User Story 3, administrators act (P2)

Independent test: move one report through every transition and add a note, then find each step in the administrator log.

- [X] T034 [P] [US3] Create the `ChangeAgentFeedbackStatus` action (refuses a target outside `allowedTargets()`, writes `AgentFeedbackStatusChanged` to the administrator log) in app/Actions/ChangeAgentFeedbackStatus.php
- [X] T035 [P] [US3] Create the `AddAgentFeedbackNote` action (author name kept, writes `AgentFeedbackNoteAdded`) in app/Actions/AddAgentFeedbackNote.php
- [X] T036 [P] [US3] Test both actions, including a refused transition, in tests/Unit/Actions/ChangeAgentFeedbackStatusTest.php and tests/Unit/Actions/AddAgentFeedbackNoteTest.php
- [X] T037 [US3] Create the web `AgentFeedbackStatusController@update` and `AgentFeedbackNoteController@create` (redirect with flash) in app/Http/Controllers/App/Admin/
- [X] T038 [US3] Create the API `AgentFeedbackStatusController@update` and `AgentFeedbackNoteController@create` (422 on a refused transition) in app/Http/Controllers/Api/Admin/
- [X] T039 [US3] Add the PUT status and POST notes routes in routes/administrator.php and routes/api.php
- [X] T040 [P] [US3] Create the `AddAgentFeedbackNote` and `ChangeAgentFeedbackStatus` tools in app/Mcp/Tools/AddAgentFeedbackNote.php and app/Mcp/Tools/ChangeAgentFeedbackStatus.php, and register them
- [X] T041 [US3] Add the transition forms, the note form and `x-copy-button` for the Markdown to resources/views/app/admin/agent-feedback/show.blade.php
- [X] T042 [US3] Test the web and API status and note routes (redirect or flash, 422 on a refused transition) in tests/Feature/Controllers/App/Admin/ and tests/Feature/Controllers/Api/Admin/

## Phase 6: User Story 4, documentation (P2)

- [X] T043 [US4] Add the families "Feedback" (`submit-feedback`) and "Agent feedback queue" (the four admin tools), each with its one sentence of purpose, in app/ViewModels/Docs/McpDocsViewModel.php
- [X] T044 [US4] Document the five endpoints and add the api-changelog entry, with the `api-docs-writer` skill, in resources/docs/api and resources/docs/api-changelog

## Phase 7: Polish

- [X] T045 Write the What's new entry with the `changelog` skill in changelog/
- [X] T046 Run `monica:localize`, then have Sonnet subagents translate every locale and pass `monica:localize --check` in lang/*.json
- [X] T047 Run the accessibility-reviewer and rules-reviewer agents on the change and fix their findings
- [X] T048 Run the `definition-of-done` skill: the full suite, PHPStan, `vendor/bin/pint --dirty --format agent`, and quickstart.md by hand

## Dependencies

Phase 2 blocks everything else. US1 and US2 are independent once Phase 2 is done. US3 needs US2's detail screen (T026) and lookup (T030). US4 needs the tool classes from US1 to US3. Polish comes last. The tasks marked [P] touch different files. MVP: Phases 1 to 3 (an agent can file a report) plus US2 (an administrator can read it).
