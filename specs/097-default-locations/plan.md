# Implementation Plan: Default Locations

**Branch**: `2026-10-25-location-management` | **Date**: 2026-10-26 | **Spec**: [spec.md](spec.md)

## Summary

The spec's six locations become one `Translatable` enum. One action writes them into an account in
the user's language, one queued job at the end of the account setup chain calls it, and one migration
runs the action for accounts that existed before. No schema, screen, API or MCP change. Decisions in
[research.md](research.md), checks in [quickstart.md](quickstart.md). The catalogue is in the spec, so
there is no `data-model.md`; nothing external changes, so there is no `contracts/`.

## Technical Context

| | |
| --- | --- |
| Language | PHP 8.5, Laravel (current major), queues |
| Storage | The `locations` table of 086; one data migration, no schema change; SQLite, MySQL and PostgreSQL |
| Testing | PHPUnit: the action, the job, the migration, the enum, `CreateAccount` |
| Scale | Six rows per account, in one transaction |
| Constraints | Six names in every locale; nothing written to an account that holds a location |

## Constitution Check

| Principle | Status |
| --- | --- |
| I Actions | Pass. The job and the migration both reach `CreateDefaultLocations` (R2, R7) |
| II Simplicity | Pass. The possession conditions trio is the pattern; no helper, no flag, no general parent method (R1) |
| III Tested | Pass. Action, job, migration, enum, chain (quickstart) |
| IV Laravel | Pass. Queued job; a data migration with `up()` only |
| V Localized | Pass. Six names through `monica:localize`; emojis are not text to translate |
| VI Nothing changes | Pass. Locations are unreleased; an account holding one is left alone |
| VII Stable core | Pass. Seeded rows of the existing concept; nothing new |
| VIII Nothing disappears | Not touched: nothing is removed |
| IX Accessible | Not touched: no screen changes |
| X API | Pass. No new capability; seeded locations are reached by the 087 endpoints and tools |
| XI Short record | Pass. Spec at its 120 line cap; no `data-model.md` or `contracts/` |

Re-checked after design: unchanged.

## Project Structure

```text
app/Enums/DefaultLocationEnum.php                the catalogue (R1)
app/Enums/UserActionEnum.php                     LocationsSeeded (R5)
app/Actions/CreateDefaultLocations.php           (R2, R3, R4)
app/Actions/CreateAccount.php                    chain gains SeedLocations (R6)
app/Jobs/SeedLocations.php
database/migrations/2026_10_26_000007_backfill_locations.php   (R7)
docs/portal/en/9-templates-and-customisation/13-locations.md   the starting locations (FR-010)
docs/FEATURES.md                                 one line
specs/086-location-management/spec.md            assumption points at 097 (R9)
lang/*.json                                      via `php artisan monica:localize`
tests/Unit/Actions/CreateDefaultLocationsTest.php, CreateAccountTest.php
tests/Unit/Jobs/SeedLocationsTest.php
tests/Unit/Migrations/BackfillLocationsTest.php
tests/Unit/Enums/DefaultLocationEnumTest.php
```

## Order of work

1. Enum, journal case, enum test.
2. Action and its tests.
3. Job, chain in `CreateAccount`, their tests.
4. Migration and its test.
5. Guide page, FEATURES.md, the 086 assumption, translations, PHPStan, Pint, whole suite.

Translations are seven new strings (six names and the journal wording) in each of 30 locales. French
first, checked against the glossary ("location" is "emplacement"); the other locales go to a Sonnet
subagent in one batch, checked against the same glossary row.

## Complexity Tracking

No departure.
