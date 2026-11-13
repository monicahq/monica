# Tasks: Relatives without a record and possessions through a connected assistant

**Input**: [plan.md](plan.md), [spec.md](spec.md), [research.md](research.md), [contracts/mcp-guidance.md](contracts/mcp-guidance.md)

**Tests**: required by the constitution (III); placement in research R5.

## Phase 1: Setup

None. No dependency, migration or configuration.

## Phase 2: Foundational

None. Each story touches its own text.

## Phase 3: User Story 1, relatives without a record (P1)

- [X] T001 [P] [US1] Add `it_keeps_a_person_named_in_the_call_without_a_record` to tests/Unit/Mcp/Tools/CreateContactRelationshipTest.php: call with `first_name` only and no `creates_record`, assert the other side's contact row has `is_partial` true
- [X] T002 [P] [US1] Replace the first sentence of the `#[Description]` in app/Mcp/Tools/CreateContactRelationship.php with the wording in contracts/mcp-guidance.md, keeping the rest
- [X] T003 [US1] Replace the relationship paragraph of the `#[Instructions]` in app/Mcp/Servers/MonicaServer.php with the paragraph in contracts/mcp-guidance.md

## Phase 4: User Story 2, possessions (P1)

- [X] T004 [P] [US2] Replace the first sentence of the `#[Description]` in app/Mcp/Tools/CreatePossession.php with the wording in contracts/mcp-guidance.md, keeping the rest
- [X] T005 [US2] Add the possession paragraph from contracts/mcp-guidance.md to the `#[Instructions]` in app/Mcp/Servers/MonicaServer.php, right after the possession type paragraph

## Phase 5: User Story 3, whole catalogue in one listing (P2)

- [X] T006 [US3] Add `it_lists_every_capability_on_one_page` to tests/Feature/Mcp/McpCatalogueTest.php: post `tools/list` without a cursor with a valid key, assert `result.tools` counts every registered tool and `result.nextCursor` is absent (reuse the request helper McpAuthenticationTest uses)
- [X] T007 [US3] Override `public int $defaultPaginationLength = 500;` and `public int $maxPaginationLength = 500;` in app/Mcp/Servers/MonicaServer.php

## Phase 6: Documentation

- [X] T008 [P] [US1] In app/ViewModels/Docs/McpDocsViewModel.php, set the `create-contact-relationship` description to "Adds a partner, a family member or any other relative to a contact, with or without a record of their own."
- [X] T009 [US2] In app/ViewModels/Docs/McpDocsViewModel.php, set the `create-possession` description to "Records an object somebody owns in a vault, of one possession type."
- [X] T010 [P] [US1] Add a "From the API or an assistant" section at the end of docs/portal/en/4-relationships-and-life-events/2-link-two-people.md, before "Next steps", in the wording the possession pages use, saying an assistant can add a relative with or without a record and with or without a name; no dash as punctuation
- [X] T011 Record the change with the feature-documentator skill in docs/FEATURES.md

## Phase 7: Polish

- [X] T012 Run `php artisan test --compact tests/Feature/Mcp tests/Unit/Mcp tests/Unit/ViewModels/Docs tests/Feature/Controllers/App/Docs` and fix what fails
- [X] T013 Run `vendor/bin/pint --dirty --format agent`, `vendor/bin/phpstan analyse --no-progress` and `php artisan monica:localize --check`

## Dependencies

- T003, T005 and T007 edit the same file, app/Mcp/Servers/MonicaServer.php: run them in sequence.
- T008 and T009 edit the same file: run them in sequence.
- T001, T002, T004, T006 and T010 touch separate files and can run in parallel.
- T011 to T013 follow every story. The API changelog gets no entry: the API does not change. Stories are otherwise independent; MVP is US1, though US3 is the
  likeliest fix for both reports (research R1) and costs two lines, so ship all three together.
