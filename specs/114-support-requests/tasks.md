# Tasks: Support requests inside the application

**Input**: [spec.md](spec.md), [plan.md](plan.md), [research.md](research.md), [data-model.md](data-model.md), [contracts/http.md](contracts/http.md), [contracts/mcp.md](contracts/mcp.md), [quickstart.md](quickstart.md)

**Tests**: required by constitution principle III, written in the layer `.claude/rules/testing.md` assigns. Data from The Office. Read the `actions`, `models`, `migrations`, `controllers`, `view-models`, `views`, `routes`, `api-writer`, `mcp-development`, `tests` skills before the matching phase.

## Phase 1: Setup

- [X] T001 [P] Create `SupportCategoryEnum` (feature, billing, email, question, technical) implementing `Translatable`, holding label, short label, icon, heading, quote, paragraphs, subject hint (R10), in app/Enums/SupportCategoryEnum.php
- [X] T002 [P] Create `SupportRequestStatusEnum` (`to_handle`, `waiting_on_user`, `solved`) with `administratorLabel()`, `userLabel()` ("Waiting for support", "Reply from support", "Solved"), tone and icon, in app/Enums/SupportRequestStatusEnum.php
- [X] T003 [P] Create `SupportMessageKindEnum` (`message`, `reply`, `note`) in app/Enums/SupportMessageKindEnum.php
- [X] T004 [P] Add `Administration = 'administration'` with label and description to app/Enums/ApiKeyScopeEnum.php (R9)
- [X] T005 [P] Add cases `SupportRequestOpened`, `SupportMessageAdded`, `SupportRequestSolved` to app/Enums/UserActionEnum.php and `SupportReplySent`, `SupportRequestStatusChanged` to app/Enums/AdministratorActionEnum.php, with their wordings
- [X] T006 [P] Add `SupportRequestReceived` and `SupportReplySent` cases to app/Enums/EmailType.php
- [X] T007 [P] Test the three new enums (labels, every case translatable) in tests/Unit/Enums/

## Phase 2: Foundational

- [X] T008 Migration `support_requests` per data-model.md, including "`user_id` nullable, nullOnDelete", "`subject` string(255)", indexes `(status, created_at)` and `(account_id, last_activity_at)`, in database/migrations/2026_11_11_000000_create_support_requests_table.php
- [X] T009 Migration `support_messages` ("`author_user_id` nullable, nullOnDelete", "`author_name` string", "`kind` string", "`body` text") in database/migrations/2026_11_11_000001_create_support_messages_table.php
- [X] T010 Migration `support_attachments` ("`size_in_bytes` unsigned integer") in database/migrations/2026_11_11_000002_create_support_attachments_table.php
- [X] T011 Migration `support_status_changes` ("`from_status` nullable") in database/migrations/2026_11_11_000003_create_support_status_changes_table.php
- [X] T012 [P] Model `SupportRequest` (`ResolvesModelInAccount`, `#[RouteKey('uuid')]`, enum casts, `reference` accessor `MON-{number}`, `messages()`, `visibleMessages()` excluding notes, `statusChanges()`) in app/Models/SupportRequest.php
- [X] T013 [P] Model `SupportMessage` with `attachments()` and `isFromSupportTeam()` in app/Models/SupportMessage.php
- [X] T014 [P] Model `SupportAttachment` with `directoryFor(int $accountId)` returning `support/{account_id}` in app/Models/SupportAttachment.php
- [X] T015 [P] Model `SupportStatusChange` (written once) in app/Models/SupportStatusChange.php
- [X] T016 Add `supportRequests()` to app/Models/Account.php and app/Models/User.php
- [X] T017 [P] Factories with states `toHandle`, `waitingOnUser`, `solved`, `reply`, `note` in database/factories/Support{Request,Message,Attachment,StatusChange}Factory.php
- [X] T018 [P] Model tests: `number`, `visibleMessages()` leaves notes out, `isFromSupportTeam()`, in tests/Unit/Models/SupportRequestTest.php and SupportMessageTest.php
- [X] T019 Delete `support/{account_id}` from the disk in app/Actions/DestroyAccount.php, with a test in tests/Unit/Actions/DestroyAccountTest.php
- [X] T020 Seed the five requests of `Admin Support.dc.html` with Office characters in database/seeders/ExampleSupportRequests.php, called from database/seeders/ExampleAccountSeeder.php

**Checkpoint**: tables, models and factories exist; stories can start.

## Phase 3: User Story 1, a user opens a request (P1) MVP

**Independent test**: Michael Scott sends a billing request with one PDF and sees `MON-{n}` on the confirmation.

- [X] T021 [US1] Action `OpenSupportRequest` (user, category, subject, body, files): refuses a body "at least 10 characters", more than "5 per message", a file over "10 MB" or outside images, pdf, txt; stores files under `SupportAttachment::directoryFor()` without touching `storage_used_in_bytes`; records `application_version`, `locale`, status `to_handle`, the history row, `LogUserAction`, sends `SupportRequestReceived`, in app/Actions/OpenSupportRequest.php
- [X] T022 [P] [US1] Notification `SupportRequestReceived` (implements `HasEmailType`, links the request page, says to answer there, FR-014) in app/Notifications/SupportRequestReceived.php
- [X] T023 [P] [US1] Tests for T021 and T022 in tests/Unit/Actions/OpenSupportRequestTest.php and tests/Unit/Notifications/SupportRequestReceivedTest.php
- [X] T024 [US1] Shell component `x-support.page` (sidebar: Your requests with open count, New request, Documentation, API docs; forwards every `x-shell` slot) in resources/views/components/support/page.blade.php
- [X] T025 [US1] View model `SupportRequestFormViewModel` (categories with copy, the instance's active administrators for the intro card, `isOffered()` false without one) in app/ViewModels/Support/SupportRequestFormViewModel.php, tested in tests/Unit/ViewModels/Support/
- [X] T026 [US1] View model `SupportConfirmationViewModel` in app/ViewModels/Support/SupportConfirmationViewModel.php, tested in tests/Unit/ViewModels/Support/
- [X] T027 [US1] Controllers `SupportRequestController@new`, `@create` and `SupportRequestConfirmationController@show` in app/Http/Controllers/App/Support/, 404 when `isOffered()` is false
- [X] T028 [US1] Routes `support.requests.new`, `.create`, `.confirmation.show` in routes/web.php
- [X] T029 [US1] Views new.blade.php (radio group of categories, guidance swapped by Alpine, subject, message, files) and confirmation.blade.php (number with `x-copy-button`, what happens next) in resources/views/app/support/
- [X] T030 [US1] "Help and support" entry, shown only when support is offered, in resources/views/components/account-menu.blade.php
- [X] T031 [US1] Controller tests (answers, inline validation, redirect, one refusal from the action) in tests/Feature/Controllers/App/Support/

## Phase 4: User Story 2, an administrator answers from the queue (P1)

**Independent test**: with the seeded requests, answer Jonas Weber's and move it to Waiting on the user.

- [X] T032 [US2] Action `SendSupportReply` (administrator only, else `ModelNotFoundException`; status after reply, default `waiting_on_user`; history row when the status changes; `LogAdministratorAction`; sends `SupportReplySent`) in app/Actions/SendSupportReply.php
- [X] T033 [P] [US2] Action `AddSupportNote` (administrator only; no status change, no email) in app/Actions/AddSupportNote.php
- [X] T034 [P] [US2] Action `ChangeSupportRequestStatus` (administrator only; same status writes nothing; sets or clears `solved_at`) in app/Actions/ChangeSupportRequestStatus.php
- [X] T035 [P] [US2] Notification `SupportReplySent` (reply body, never a note, link to the request page, answer there) in app/Notifications/SupportReplySent.php
- [X] T036 [P] [US2] Tests for T032 to T035 in tests/Unit/Actions/ and tests/Unit/Notifications/
- [X] T037 [US2] View model `SupportQueueViewModel` (views with counts, filters `q` on subject, bodies, name, email, number; `category`; `user` listing only users with requests; `age` day, week, old; oldest first; empty copy) in app/ViewModels/Admin/SupportQueueViewModel.php
- [X] T038 [P] [US2] Block `SupportRequestContext` (account number, subscription wording, language, version, vault and contact counts, sign up date, link to `admin.accounts.show`; nothing from inside vaults) in app/ViewModels/Admin/SupportRequestContext.php
- [X] T039 [P] [US2] Tests for T037 and T038 in tests/Unit/ViewModels/Admin/
- [X] T040 [US2] Controllers `SupportController@index/@show`, `SupportReplyController@create`, `SupportNoteController@create`, `SupportStatusController@update`, `SupportAttachmentController@show` in app/Http/Controllers/App/Admin/
- [X] T041 [US2] Routes per contracts/http.md in routes/administrator.php
- [X] T042 [US2] Views from `Admin Support.dc.html` (tabs, filters, rows with `aria-current`, thread with notes, composer with reply and note modes, context, status history) in resources/views/app/admin/support/
- [X] T043 [US2] Support entry with the To handle count, after Moderation, in resources/views/layouts/admin.blade.php
- [X] T044 [US2] Controller tests, plus the admin URLs added to the data provider of `AdminAccessTest`, in tests/Feature/Controllers/App/Admin/

## Phase 5: User Story 3, the user follows up (P2)

**Independent test**: reply to a request with a support answer, mark it solved, reply again.

- [X] T045 [US3] Action `AddSupportMessage` (owner only; same file rules as T021; moves to `to_handle`, reopening a solved one; `last_activity_at`) in app/Actions/AddSupportMessage.php
- [X] T046 [P] [US3] Action `SolveSupportRequest` (owner only; refuses an already solved request) in app/Actions/SolveSupportRequest.php
- [X] T047 [P] [US3] Tests for T045 and T046 in tests/Unit/Actions/
- [X] T048 [US3] View models `SupportRequestsViewModel` (Open and Solved, latest activity first) and `SupportRequestViewModel` (visible messages only, attachments, can solve) in app/ViewModels/Support/, tested with a note present and absent from the output
- [X] T049 [US3] Controllers `SupportRequestController@index/@show`, `SupportMessageController@create`, `SupportSolutionController@create`, `SupportAttachmentController@show` (answers 404 for an attachment of another account) in app/Http/Controllers/App/Support/
- [X] T050 [US3] Routes `support.requests.index`, `.show`, `.messages.create`, `.solution.create`, `support.attachments.show` in routes/web.php
- [X] T051 [US3] Views index.blade.php (tabs, rows with status in words) and show.blade.php (thread, solved notice, reply with files, Mark as solved) in resources/views/app/support/
- [X] T052 [US3] Controller tests in tests/Feature/Controllers/App/Support/

## Phase 6: User Story 4, the API and MCP (P2)

**Independent test**: with a key, open, list, read, reply, solve; with an administrator's key holding `administration`, list the queue and reply.

- [X] T053 [P] [US4] Resources `SupportRequestResource`, `SupportMessageResource` (never `kind: note`) and `Admin\SupportRequestResource` (notes, `context`, `status_changes`) in app/Http/Resources/
- [X] T054 [US4] User API controllers per contracts/http.md in app/Http/Controllers/Api/Support*.php and routes in routes/api.php
- [X] T055 [US4] Admin API controllers in app/Http/Controllers/Api/Admin/, routes under `v1/admin` with `administrator`, refusing a key without `administration` as 404
- [X] T056 [P] [US4] User API tests (shape, 404 for another account, no note) in tests/Feature/Controllers/Api/
- [X] T057 [P] [US4] Admin API tests (non administrator 404, key without scope 404, counts in `meta`) in tests/Feature/Controllers/Api/Admin/
- [X] T058 [US4] Five user MCP tools per contracts/mcp.md in app/Mcp/Tools/, registered in app/Mcp/Servers/MonicaServer.php
- [X] T059 [US4] Five admin MCP tools whose `shouldRegister()` needs an administrator and the `administration` ability, in app/Mcp/Tools/, registered in app/Mcp/Servers/MonicaServer.php
- [X] T060 [P] [US4] MCP tool tests (no note, another account not found, admin tools absent without rights) in tests/Unit/Mcp/Tools/
- [X] T061 [US4] Offer the `administration` scope only to administrators, and refuse it to anybody else, in app/ViewModels/Settings/Account/Security/SecurityViewModel.php, app/Http/Controllers/App/Settings/Account/Security/ApiKeyController.php and app/Http/Controllers/Api/ApiKeyController.php, with tests

## Phase 7: Polish

- [X] T062 [P] API docs in resources/docs/api/32-support-requests.php and resources/docs/api/33-support-administration.php (use the `api-docs-writer` skill)
- [X] T063 [P] MCP docs: check the ten tools appear in /docs/mcp, extend app/ViewModels/Docs/McpDocsViewModel.php if a list is written by hand
- [X] T064 [P] Portal guide "Contact support" in docs/portal/en/10-your-account-and-security/ (use the `writer` skill), entry in docs/FEATURES.md (use the `feature-documentator` skill)
- [X] T065 Run `php artisan monica:localize` and translate every missing string in every locale, following .claude/rules/translations.md and its glossary
- [X] T066 `vendor/bin/pint --dirty --format agent`, `vendor/bin/phpstan analyse --no-progress`, `php artisan monica:localize --check`
- [X] T067 `PAO_DISABLE=true php artisan test --parallel --compact`, including tests/Feature/RouteProtectionTest.php
- [X] T068 Run the `accessibility-reviewer` and `rules-reviewer` agents on the diff and fix what they confirm
- [ ] T069 Walk quickstart.md by hand at monica.test

## Dependencies

Phase 1 → Phase 2 → US1 → US2 → US3 → US4 → Phase 7. US2 needs a request to exist (US1's action). US3 needs replies (US2). US4 wraps the actions of all three. Within a phase, [P] tasks touch different files and can run together: T001 to T007; T012 to T015 and T017, T018; T022 with T023; T033 to T036; T038, T039; T046, T047; T053, T056, T057, T060; T062 to T064.

## Strategy

MVP is Phases 1 to 3 plus US2: a request that nobody can answer is no MVP, so US1 and US2 ship together. US3 and US4 follow in the same pull request, since principle X makes API coverage part of done.
