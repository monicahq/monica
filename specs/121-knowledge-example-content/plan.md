# Implementation Plan: Knowledge example content

**Branch**: `2026-11-12-knowledge-dummy-data` | **Date**: 2026-11-12 | **Spec**: [spec.md](spec.md)

## Summary

Add a data class `database/seeders/ExampleKnowledge.php` holding the eight folders of FR-002 with their
notes and lists, and a `createKnowledge()` step in `ExampleAccountSeeder` that writes them through the
existing Knowledge actions. Decisions are in [research.md](research.md).

## Technical Context

**Language/Version**: PHP 8.5, Laravel (current major)
**Primary Dependencies**: none new
**Storage**: existing Knowledge tables, written only through actions
**Testing**: PHPUnit, `tests/Unit/Seeders/ExampleAccountSeederTest.php`
**Project Type**: web application, seeder change only
**Scale/Scope**: 8 folders, 17 notes, 13 lists, about 50 list items

## Constitution Check

| Principle | Result |
| --- | --- |
| I. Actions hold the logic | Pass: the seeder calls `CreateKnowledgeFolder`, `CreateKnowledgeNote`, `CreateKnowledgeList`, `UpdateKnowledgeListColumn`, `CreateKnowledgeListColumn`, `CreateKnowledgeListItem` |
| II. Simplicity | Pass: one data class, one seeder method, the pattern of `ExampleGroups` and `ExamplePossessions` |
| III. Tested | Pass: the existing seeder test gains one test |
| V. Localized | Pass: example content is user data, not interface copy (research R3); no new `__()` string |
| VI to X | Not touched: no structure, no screen, no API change |
| XI. Short record | Pass: no data-model, contracts or quickstart, this plan carries them |

Re-checked after design: unchanged.

## Design

`ExampleKnowledge::all()` returns:

```php
/** @return list<array{name: string, notes: list<array{title: string, body: string}>, lists: list<array{title: string, description: string, columns: list<string>, items: list<list<?string>>}>}> */
```

`ExampleAccountSeeder::createKnowledge()`, called after `createPossessions()`:

1. Create the folder with `CreateKnowledgeFolder`, before anything is put in it (no "General").
2. Create each note with `CreateKnowledgeNote`, passing the folder.
3. Create each list with `CreateKnowledgeList`, then rename its two starting columns to `columns[0]` and
   `columns[1]` with `UpdateKnowledgeListColumn`, and add the rest with `CreateKnowledgeListColumn`.
4. Create each item with `CreateKnowledgeListItem`, its cells keyed by the uuid of the column at the same
   index; `null` is a blank cell.

Every list declares at least two columns, so no starting column is ever deleted.

Content coverage (FR-005, FR-006): headings and bullets in "About me", numbered steps in "Grandma's
lasagna", links in "Places to visit", tasks in "Home maintenance" and "Things to learn", fenced code in
"Useful commands" and "Home server setup". "Appliances" has five columns and a blank warranty cell.

## Project Structure

```text
database/seeders/ExampleKnowledge.php           new
database/seeders/ExampleAccountSeeder.php       one call, one method
tests/Unit/Seeders/ExampleAccountSeederTest.php one test
```

## Validation

`php artisan test --compact tests/Unit/Seeders/ExampleAccountSeederTest.php`, then PHPStan, Pint and
`php artisan monica:localize --check` (expected unchanged). Manual check: `php artisan migrate:fresh
--seed`, sign in as the example account, open Knowledge.
