# Tasks: Knowledge example content

**Input**: [plan.md](plan.md), [spec.md](spec.md), [research.md](research.md)

## Phase 1: User Story 1, the example account opens on a filled Knowledge section (P1)

- [x] T001 [US1] Write the failing test `it_fills_the_example_account_with_its_knowledge` (folder count and order, note and list counts, no "General" folder) in tests/Unit/Seeders/ExampleAccountSeederTest.php
- [x] T002 [US1] Create the data class with the 8 folders, 17 note titles and bodies, 13 lists with descriptions, columns and items in database/seeders/ExampleKnowledge.php
- [x] T003 [US1] Add `createKnowledge()` after `createPossessions()`, writing folders, notes, lists, renamed and added columns and items through the actions, in database/seeders/ExampleAccountSeeder.php
- [x] T004 [US1] Run the seeder test until it passes

## Phase 2: User Story 2, the content exercises what Knowledge displays (P2)

- [x] T005 [US2] Extend the test to assert "Home maintenance" holds ticked and unticked tasks, "Useful commands" holds a code block, and "Appliances" has five columns with a blank cell, in tests/Unit/Seeders/ExampleAccountSeederTest.php
- [x] T006 [US2] Check the content in database/seeders/ExampleKnowledge.php covers every formatting kind of FR-005 and FR-006, adjusting where it does not

## Phase 3: Polish

- [x] T007 Run `vendor/bin/pint --dirty --format agent`, `vendor/bin/phpstan analyse --no-progress` and `php artisan monica:localize --check`
- [X] T008 Run `php artisan migrate:fresh --seed` on the local database and confirm the counts of SC-001 and SC-004 (second `db:seed` writes nothing)

## Dependencies

T001 → T002 → T003 → T004 → T005 → T006 → T007 → T008. Nothing runs in parallel: every task touches one of three files in sequence.
