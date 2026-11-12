# Implementation Plan: Default Possession Types

**Branch**: `2026-10-25-location-management` | **Date**: 2026-10-26 | **Spec**: [spec.md](spec.md)

## Summary

The spec's catalogue becomes one `Translatable` enum. One action writes it into an account in the
user's language, one queued job at the end of the account setup chain calls it, and one migration
runs the action for accounts that existed before. No schema, screen, API or MCP change.
Decisions in [research.md](research.md), rows and colours in [data-model.md](data-model.md), checks in
[quickstart.md](quickstart.md).

## Technical Context

| | |
| --- | --- |
| Language | PHP 8.5, Laravel (current major), queues |
| Storage | The three 088 tables; one data migration, no schema change; SQLite, MySQL and PostgreSQL |
| Testing | PHPUnit: the action, the job, the migration, the enum, `CreateAccount` |
| Scale | 25 types, 58 groups, 190 fields per account, in one transaction |
| Constraints | Every name in every locale; nothing written to an account that holds a type |

## Constitution Check

| Principle | Status |
| --- | --- |
| I Actions | Pass. The job and the migration both reach `CreateDefaultPossessionTypes` (R4, R7) |
| II Simplicity | Pass. The relationship types pair is the pattern; no helper, no flag |
| III Tested | Pass. Action, job, migration, enum, chain (quickstart) |
| IV Laravel | Pass. Queued job; a data migration with `up()` only, like `backfill_notification_preferences` |
| V Localized | Pass. Every name through `monica:localize`; codes deliberately untranslated (R2) |
| VI Nothing changes | Pass. Existing accounts hold no type and have never seen the screen; an account holding one is left alone |
| VII Stable core | Pass. Seeded rows of the existing concept; nothing new |
| VIII Nothing disappears | Not touched: nothing is removed |
| IX Accessible | Not touched: no screen changes |
| X API | Pass. No new capability; seeded types are reached by the 088 endpoints and tools (R8) |
| XI Short record | **Exceeded**: `spec.md` holds the catalogue (R9) |

Re-checked after design: unchanged.

## Project Structure

```text
app/Enums/PossessionTypeEnum.php                 the catalogue (R1, R2, R6)
app/Enums/UserActionEnum.php                     PossessionTypesSeeded (R5)
app/Actions/CreateDefaultPossessionTypes.php
app/Actions/CreateAccount.php                    chain gains SeedPossessionTypes
app/Jobs/SeedPossessionTypes.php
database/migrations/2026_10_26_000001_backfill_possession_types.php
docs/portal/en/9-templates-and-customisation/12-possession-types.md   the starting types (FR-011)
docs/FEATURES.md                                 one line
specs/088-possession-types/spec.md               Assumptions point at 089 (done)
lang/*.json                                      via `php artisan monica:localize`
tests/Unit/Actions/CreateDefaultPossessionTypesTest.php, CreateAccountTest.php
tests/Unit/Jobs/SeedPossessionTypesTest.php
tests/Unit/Migrations/BackfillPossessionTypesTest.php
tests/Unit/Enums/PossessionTypeEnumTest.php
```

## Order of work

1. Enum, journal case, enum test.
2. Action and its tests, including the export comparison for every case.
3. Job, chain in `CreateAccount`, their tests.
4. Migration and its test.
5. Guide page, FEATURES.md, translations, PHPStan, Pint, whole suite, quickstart by hand.

Translations are a few hundred new strings (exact count from `monica:localize`) in each of 30 locales. Step 5 writes the French file first as
the reference, then hands the other locales to Sonnet subagents in batches, checked against the
glossary of `.claude/rules/translations.md` ("group", "field", "possession type").

## Complexity Tracking

| Departure | Why | Simpler alternative rejected because |
| --- | --- | --- |
| `spec.md` over its 120 line cap | The catalogue is the requirement | Splitting it from the spec leaves two statements of what ships |
| Codes listed apart in the enum | They must not reach translators | A flag per option repeats itself 18 times for one coin field |
