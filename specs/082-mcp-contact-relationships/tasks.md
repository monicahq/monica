# Tasks: Contact relationships over the API and MCP

**Input**: [plan.md](plan.md), [spec.md](spec.md), [research.md](research.md), [contracts/api.md](contracts/api.md)

**Tests**: required by principle III; scope in plan.md "Test scope". Follow `.claude/rules/testing.md`.

**Every story ships its docs with its code**: `BuildOpenApiDocumentTest` fails on an undocumented API
route, and `McpDocsViewModelTest` and `McpCatalogueTest` fail on a tool missing from the docs or the
server. Copy `ContactReminderController`, the `*ContactReminder` tools and `resources/docs/api/20-reminders.php`.

## Phase 1: Foundational

- [X] T001 Create `RelationshipResource` with the shape in contracts/api.md, `self` through `contact_a`, in app/Http/Resources/RelationshipResource.php
- [X] T002 Create `Api\ContactRelationshipController` with private `vault()`, `contact()` and `relationship(Contact, string)` (`Relationship::query()->forContact($contact)->where('uuid', ...)`, eager loading type and both contacts, `firstOrFail()`) in app/Http/Controllers/Api/ContactRelationshipController.php
- [X] T003 [P] Add `relationship(Contact, string)` to the trait, answering "That relationship does not exist." through `doesNotExist()`, in app/Mcp/Tools/MirrorsAnApiCapability.php
- [X] T004 [P] Create the API reference page with the sample resource and the `vault`/`contact`/`relationship` identifiers, no endpoint yet, in resources/docs/api/23-relationships.php

## Phase 2: User Story 1, link two existing contacts (P1) MVP

- [X] T005 [US1] Add `create()`: validate `relationship_type` (required UUID of the account), `other_contact` (nullable string, looked up in the URL's vault, 404 otherwise), `holds_role_a` (boolean, default true, passed as `swapped: ! holds_role_a`); call `CreateRelationship`; map refusals per research R8; answer 201 in app/Http/Controllers/Api/ContactRelationshipController.php
- [X] T006 [US1] Add `POST vaults/{vault}/contacts/{contact}/relationships` named `contacts.relationships.create` after the reminder block in routes/api.php
- [X] T007 [US1] Document the create endpoint, its fields and its 422 refusals in resources/docs/api/23-relationships.php
- [X] T008 [P] [US1] Create `CreateContactRelationship` mirroring `api.v1.contacts.relationships.create`, its schema explaining `role_a`/`role_b` and `holds_role_a` (FR-007), refusals through `Response::error()`, in app/Mcp/Tools/CreateContactRelationship.php
- [X] T009 [US1] Register the tool in app/Mcp/Servers/MonicaServer.php and add a "Relationships, changing" group with it in app/ViewModels/Docs/McpDocsViewModel.php
- [X] T010 [P] [US1] Test create: 201 and shape, symmetric and directed roles, `holds_role_a` false, one action refusal as 422, unknown `relationship_type`, `other_contact` of another vault 404, in tests/Feature/Controllers/Api/ContactRelationshipControllerTest.php
- [X] T011 [P] [US1] Test the tool: links two contacts, returns the shape, relays one refusal, not found wording, in tests/Unit/Mcp/Tools/CreateContactRelationshipTest.php

## Phase 3: User Story 2, read a contact's relationships (P1)

- [X] T012 [US2] Add `index()` (paginated like reminders, `forContact`, ordered by `id`) and `show()` in app/Http/Controllers/Api/ContactRelationshipController.php
- [X] T013 [US2] Add the `GET` list route `contacts.relationships` and the `GET` show route `contacts.relationships.show` with the UUID `where` in routes/api.php
- [X] T014 [US2] Document both endpoints in resources/docs/api/23-relationships.php
- [X] T015 [P] [US2] Create `ListContactRelationships` and `GetContactRelationship`, both `#[IsReadOnly]`, in app/Mcp/Tools/ListContactRelationships.php and app/Mcp/Tools/GetContactRelationship.php
- [X] T016 [US2] Register both and add a "Relationships, reading" group in app/Mcp/Servers/MonicaServer.php and app/ViewModels/Docs/McpDocsViewModel.php
- [X] T017 [P] [US2] Test index (both sides listed, other contacts' relationships left out, empty list) and show (404 through a non-involved contact and through the wrong vault) in tests/Feature/Controllers/Api/ContactRelationshipControllerTest.php
- [X] T018 [P] [US2] Test both tools in tests/Unit/Mcp/Tools/ListContactRelationshipsTest.php and tests/Unit/Mcp/Tools/GetContactRelationshipTest.php

## Phase 4: User Story 3, correct or end a relationship (P2)

- [X] T019 [US3] Add `update()`: every field optional, absent keeps the value, `null` clears a date (research R6); `relationship_type`, `status` in `RelationshipStatusEnum::values()`, `started_on`/`ended_on` as `date_format:Y-m-d`, `holds_role_a` defaulting to the current orientation; call `UpdateRelationship`; map refusals per R8, in app/Http/Controllers/Api/ContactRelationshipController.php
- [X] T020 [US3] Add the `PUT` route `contacts.relationships.update` in routes/api.php and document it in resources/docs/api/23-relationships.php
- [X] T021 [P] [US3] Create `UpdateContactRelationship` in app/Mcp/Tools/UpdateContactRelationship.php, register it, list it under "Relationships, changing"
- [X] T022 [P] [US3] Test update: keeps absent fields, `null` clears a date, invalid `status` 422, dates out of order 422 on `ended_on`, in tests/Feature/Controllers/Api/ContactRelationshipControllerTest.php
- [X] T023 [P] [US3] Test the tool in tests/Unit/Mcp/Tools/UpdateContactRelationshipTest.php

## Phase 5: User Story 4, link somebody not written down yet (P2)

- [X] T024 [US4] Accept `first_name` and `last_name` (nullable, max 255) and `creates_record` (boolean, default false) on create, `other_contact` `prohibits` both names, in app/Http/Controllers/Api/ContactRelationshipController.php
- [X] T025 [US4] Accept the same three on update, only for a placeholder other side, else 422 on `first_name` "Only somebody without a record of their own can be named here." (research R7), in the same controller
- [X] T026 [US4] Add the fields to both tool schemas and to the docs, in app/Mcp/Tools/CreateContactRelationship.php, app/Mcp/Tools/UpdateContactRelationship.php and resources/docs/api/23-relationships.php
- [X] T027 [P] [US4] Test named placeholder, full contact, unnamed placeholder, `other_contact` with a name 422, renaming a non-placeholder 422, in tests/Feature/Controllers/Api/ContactRelationshipControllerTest.php

## Phase 6: User Story 5, remove a relationship and document it all (P3)

- [X] T028 [US5] Add `destroy()` calling `DestroyRelationship`, answering 204, and the `DELETE` route `contacts.relationships.destroy`, in the controller and routes/api.php
- [X] T029 [P] [US5] Create `DeleteContactRelationship`, `#[IsDestructive]`, answering "The relationship is removed.", in app/Mcp/Tools/DeleteContactRelationship.php; register it and list it saying it deletes
- [X] T030 [US5] Document the delete endpoint in resources/docs/api/23-relationships.php and add one paragraph on relationships to the `#[Instructions]` of app/Mcp/Servers/MonicaServer.php
- [X] T031 [P] [US5] Test destroy (204, both contacts kept) in the controller test and the tool in tests/Unit/Mcp/Tools/DeleteContactRelationshipTest.php

## Phase 7: Polish

- [X] T032 Set `LAST_UPDATED` to the merge date in app/ViewModels/Docs/McpDocsViewModel.php
- [X] T033 Run `php artisan monica:localize`, translate what it reports in every locale, then `php artisan monica:localize --check`
- [X] T034 Run `vendor/bin/pint --dirty --format agent`, `vendor/bin/phpstan analyse --no-progress` and the full suite
- [ ] T035 Walk through quickstart.md by hand

## Dependencies

- Phase 1 blocks everything. US1 and US2 are independent once it is done.
- US3 needs US2's `show` only for its own tests. US4 extends the files of US1 and US3, so it comes after both.
- US5 is independent of US3 and US4. Polish comes last.
- Tasks touching the controller, routes/api.php, MonicaServer or the docs page run one after another; tool files and test files marked [P] run in parallel.
