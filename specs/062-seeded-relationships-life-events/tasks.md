# Tasks: Relationships and life events in the example account

**Input**: [plan.md](plan.md), [spec.md](spec.md), [research.md](research.md), [data-model.md](data-model.md)
**Tests**: included, since constitution principle III requires them. Every test goes in `tests/Unit/Seeders/ExampleAccountSeederTest.php`.

## Phase 1: Foundational (blocks every story)

- [X] T001 Add the seven people from data-model.md to `cast()` in `database/seeders/ExampleAccountSeeder.php`, with category, gender and note exactly as the table gives them. None is a favourite and none is archived. Only the twins have a birthday, `2004-05-06`, and each twin gets a yearly reminder with its own name and its own moments (research.md R10)
- [X] T002 Keep every created contact in a `private array $contacts` keyed by full name (`"Jack Geller"`, `"Jack Bing"`, `"Gunther"`) inside `createContacts()` in `database/seeders/ExampleAccountSeeder.php` (research.md R6)
- [X] T003 Run `php artisan test --compact tests/Unit/Seeders/ExampleAccountSeederTest.php` and confirm the 11 existing tests still pass with 25 people

## Phase 2: User Story 1, the cast is connected as in the show (P1)

**Independent test**: after a fresh seed, open Chandler and see spouse Monica (current), Janice (ended), his parents and his children.

- [X] T004 [P] [US1] Create `database/seeders/ExampleRelationships.php` with one public method `all(): array` returning a `list<array{type: string, a: string, b: string, status: RelationshipStatusEnum, started: ?string, ended: ?string}>` that holds every pair in the data-model.md table. `type` is a `RelationshipTypeEnum` value, or `'neighbour'` for the custom type, and person A holds role A (research.md R7)
- [X] T005 [US1] In `database/seeders/ExampleAccountSeeder.php`, add `createRelationships()`. It creates Neighbour through `CreateRelationshipType` under the account's Other category (`name: 'Neighbour'`, `roleA: 'Neighbour'`, `roleB: 'Neighbour'`, `isSymmetric: true`), then calls `CreateRelationship` once per row with `contact` set to A and `otherContact` set to B
- [X] T006 [US1] In the same loop, follow `CreateRelationship` with `UpdateRelationship` (status, `startedOn`, `endedOn` as `CarbonImmutable`) whenever the row is ended or carries a date (research.md R5). Call `createRelationships()` from `run()` after `createContacts()`
- [X] T007 [US1] Add `it_links_every_person_to_somebody_as_the_show_does`: every contact appears on at least one relationship, Ross is the parent of Ben, and Monica and Chandler are spouses
- [X] T008 [US1] Add `it_marks_the_relationships_the_show_ended_as_ended`: Ross and Carol are ended, Joey and Rachel are ended, and Monica and Chandler are current
- [X] T009 [US1] Add `it_adds_neighbour_as_the_one_custom_relationship_type`: exactly one relationship type has `is_system` false, it is named Neighbour, and it sits under Other

## Phase 3: User Story 2, every person has a full timeline (P1)

**Independent test**: after a fresh seed, every contact's `life_event_count` is 20 or more.

- [X] T010 [P] [US2] Create `database/seeders/ExampleLifeEvents.php` with one public method `all(): array` returning a `list<array{type: string, date: string, title: string, narrative: string, location: ?string, participants: list<string>}>`. `type` is a `LifeEventTypeEnum` value, and participants are full names, the first being whose page the event is created from
- [X] T011 [US2] Add the events for the six friends from 1994 to 1999 to `database/seeders/ExampleLifeEvents.php`: the pilot, Thanksgivings, Rachel and Ross, Joey's Days of Our Lives role, the triplets, London, Las Vegas
- [X] T012 [US2] Add the events for the six friends from 2000 to 2004 to `database/seeders/ExampleLifeEvents.php`: the proposal, Monica and Chandler's wedding, Emma's birth, Barbados, Phoebe and Mike's wedding, the finale and the adoption
- [X] T013 [US2] Add the Geller family events to `database/seeders/ExampleLifeEvents.php` (Jack, Judy, Ben, Emma, Carol, Susan), with none before Ben's 1995-05-11 birth or Emma's 2002-05-16 birth
- [X] T014 [US2] Add the events for the minor people to `database/seeders/ExampleLifeEvents.php` (Mike, Gunther, Janice, Richard, Emily, Barry, Ursula, Frank Jr., Nora, Helena), so that each reaches 20 events. Nora and Helena's divorce is dated 1978-11-23
- [X] T015 [US2] Add Mr. Heckles's events to `database/seeders/ExampleLifeEvents.php`, all dated in or before 1995, ending with his death
- [X] T016 [US2] Add the twins' events from 2004-05-06 to 2010 to `database/seeders/ExampleLifeEvents.php` (research.md R2). No other person takes part in an event after 2004-05-06 unless the twins are there
- [X] T017 [US2] In `database/seeders/ExampleAccountSeeder.php`, add `createLifeEvents()`. It looks each type up by key among the account's life event types and calls `CreateLifeEvent` with the first participant as `contact` and the rest as `participants`, passing no `values` (research.md R4). Call it from `run()` after `createRelationships()`
- [X] T018 [US2] Add `it_gives_every_person_at_least_twenty_life_events`, asserting that every contact has `life_event_count` of 20 or more
- [X] T019 [US2] Add `it_uses_every_life_event_category_and_twenty_types`: every life event category has at least one event, and at least 20 distinct `life_event_type_id` values are used
- [X] T020 [US2] Add `it_dates_no_event_after_mr_heckles_died`: none of Mr. Heckles's events is later than his death event

## Phase 4: User Story 3, reseeding stays predictable (P2)

**Independent test**: seeding twice writes nothing the second time.

- [X] T021 [US3] Confirm that `it_can_be_run_again_without_building_a_second_account` and `it_seeds_with_nothing_working_the_queue` still pass, and that `ExampleRelationships` and `ExampleLifeEvents` use no Faker and no relative dates (grep both files for `fake(` and `now(`)

## Phase 5: Polish

- [X] T022 Time one seed with `php artisan migrate:fresh --seed --quiet` and confirm it is under 10 s (SC-004)
- [X] T023 Run `vendor/bin/pint --dirty --format agent`, `vendor/bin/phpstan analyse --no-progress` and `php artisan monica:localize --check`, and fix whatever they report
- [X] T024 Run the whole suite with `php artisan test --compact`, because other tests call `DatabaseSeeder`
- [X] T025 Run the manual checks in [quickstart.md](quickstart.md)
- [X] T026 Record the feature with the `feature-documentator` skill

## Dependencies

- T001 → T002 → T003 → Phases 2 and 3. US1 and US2 are independent of each other; only the `run()` call order ties them.
- T004 and T010 are in different files and can run in parallel. T011 to T016 all write the same file, one after another.
- T017 depends on T010 to T016. T018 to T020 depend on T017.
- Phase 5 comes after every story.

## Strategy

MVP is Phase 1 plus US1 (T001 to T009), which ships the relationships alone. US2 is the bulk of the work (about 200 events). US3 is one check.
