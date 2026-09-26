# Implementation Plan: Relationships and life events in the example account

**Branch**: `2026-09-26-seeded-relationships-life-events` | **Date**: 2026-09-26 | **Spec**: [spec.md](spec.md)

## Summary

Extend `ExampleAccountSeeder` so that the Friends account links its 25 people as the show does, and
gives each of them at least twenty life events. Seven people join the cast. Everything is written
through the existing actions (`CreateRelationship`, `UpdateRelationship`, `CreateRelationshipType`,
`CreateLifeEvent`), so the seeded data obeys the same rules as data a person enters. The content
itself is fixed in [data-model.md](data-model.md). The decisions are in [research.md](research.md).

## Technical Context

**Language/Version**: PHP 8.4, current Laravel
**Primary Dependencies**: none new
**Storage**: existing tables. No migration.
**Testing**: PHPUnit, `tests/Unit/Seeders/ExampleAccountSeederTest.php`
**Target Platform**: local development and CI databases (SQLite, MySQL, PostgreSQL)
**Project Type**: web application, seeder only
**Performance Goals**: one seed in under 10 s (SC-004). The baseline is about 1 s (research.md R9)
**Constraints**: deterministic content with no Faker (FR-012). No event after a participant's death
or before their birth (FR-011)
**Scale/Scope**: 25 contacts, about 60 relationships, about 200 life events

## Constitution Check

| Principle | Status |
| --- | --- |
| I. Actions hold the logic | Pass. The seeder calls actions only and writes no rows directly |
| II. Simplicity | Pass. The data sits in plain arrays, and one write loop covers each kind |
| III. Tested | Pass. The seeder test file gains the assertions listed below |
| IV. Laravel conventions | Pass. The files stay under `database/seeders`, one class per file |
| V. Localized | Not affected. Seeded values are user content. The one custom type name is content too, and `__()` is not used |
| VI to VIII | Not affected. No structure, version or deactivation is involved |
| IX. Accessible | Not affected. No screen changes |
| X. API | Not affected. No new capability |
| XI. Short record | Pass. The spec is at 115 lines, and contracts are omitted because nothing external exists |

The check was repeated after design and found no change.

## Project Structure

```text
database/seeders/
├── ExampleAccountSeeder.php     # 7 people added to cast(); new createRelationships(), createLifeEvents()
├── ExampleRelationships.php     # new: list of [type key, person A, person B, status, started, ended]
└── ExampleLifeEvents.php        # new: list of [type key, date, title, narrative, location, participants]
tests/Unit/Seeders/
└── ExampleAccountSeederTest.php # new tests below
```

**Structure Decision**: data classes sit beside the seeder, with no new folder (research.md R1).

## Order inside `run()`

1. `createAccount()`, then `readTheFields()`. Both are unchanged.
2. `createContacts()`, which now covers 25 people. Contacts are kept in a map keyed by full name.
3. `createRelationships()`. It creates Neighbour under Other first. Then, for each row, it calls
   `CreateRelationship`. When a row carries a status or dates, `UpdateRelationship` follows.
4. `createLifeEvents()`. For each row, it calls `CreateLifeEvent` from the first participant's side,
   with the others as `participants`.

## Tests to add (one layer: the seeder)

| Test | Proves |
| --- | --- |
| `it_links_every_person_to_somebody_as_the_show_does` | FR-001, FR-002, and a handful of named links (Ross and Ben, Monica and Chandler) |
| `it_marks_the_relationships_the_show_ended_as_ended` | FR-003 (Ross and Carol, Joey and Rachel), and that Monica and Chandler stay current |
| `it_adds_neighbour_as_the_one_custom_relationship_type` | FR-006 |
| `it_gives_every_person_at_least_twenty_life_events` | FR-007, read through `life_event_count` |
| `it_uses_every_life_event_category_and_twenty_types` | FR-008 |
| `it_dates_no_event_after_mr_heckles_died` | FR-011 at its one sharp edge |

The existing idempotence test already covers FR-012. The existing reminder test covers the twins'
birthdays (research.md R10).

## Complexity Tracking

None.
