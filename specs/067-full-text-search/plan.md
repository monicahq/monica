# Implementation Plan: Full text search

**Branch**: `2026-09-29-full-text-search` | **Date**: 2026-09-29 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `/specs/067-full-text-search/spec.md`

## Summary

Add Laravel Scout with an engine every instance runs, the operator's choice: Algolia, Meilisearch or
Typesense, which tolerate typing mistakes, or the database, which does not (research R1). Contact, Entity and LifeEvent become searchable, one index each; each record's
document is built from its values, as the owner reads them, its linked names and its scope when
Scout syncs it, queued behind the save. No table, no column, no migration. One action answers a
results page, an API endpoint and an MCP tool, and says search is unavailable while the engine is.
Decisions: [research.md](research.md); documents and index settings: [data-model.md](data-model.md);
surfaces: [contracts/search.md](contracts/search.md).

## Technical Context

**Language/Version**: PHP 8.4, Laravel 13
**Primary Dependencies**: `laravel/scout` (new); engine clients suggested; Blade, Tailwind CSS 4, Alpine Ajax
**Storage**: three indexes in the operator's engine; no database change
**Testing**: PHPUnit on SQLite, MySQL and PostgreSQL; engine behaviour against a reference engine (Meilisearch) in CI and the devcontainer
**Target Platform**: web, JSON API, MCP
**Project Type**: web application (single Laravel project)
**Performance Goals**: SC-001 (500 ms at p95 for 10,000 contacts), SC-002 (findable within 10 s)
**Constraints**: saving never waits for the engine; WCAG 2.2 AA; every locale
**Scale/Scope**: 3 searchable models, 1 trait, 1 action, 1 command, 2 controllers, 1 tool, 1 screen

## Constitution Check

| Principle or constraint | Status |
| --- | --- |
| I. Actions hold the logic | Pass. `SearchRecords` serves screen, API and MCP |
| II. Simplicity | Pass. Scout as documented; no engine subclass, no copy of the data in the database |
| III. Tested | Pass. Tests per layer below, against a real engine where the engine decides |
| IV. Laravel conventions | Pass. Scout's documented engine configuration, `MakeSearchableUniquely` |
| V. Localized | Pass. Values written in the account's locale; new labels through `monica:localize` |
| VII. One personalisation mechanism | Pass. A new field reaches the document through its type |
| VIII. Nothing disappears silently | Pass. Soft deleted records leave the results and return on restore; an unreachable engine is said, not hidden |
| IX. Accessible | Pass. `role="search"`, labelled input, count in a polite live region, `<mark>` plus weight |
| X. Through the API | Pass. `api.v1.search`, its docs page, the `SearchRecords` MCP tool |
| XI. Short record | Pass. Every artifact within its cap |
| Search constraint | Pass. Tolerant free search on every deployment; indexing queued behind the save |
| Same product everywhere | Pass. Hosted and self hosted both run a typo tolerant engine |
| Package reaching an outside service | Pass. Engine clients suggested, never required; the reference client in `require-dev` (R7) |

Re-checked after design: unchanged.

## Project Structure

Artifacts: this plan, research.md, data-model.md, contracts/search.md, quickstart.md, tasks.md.

### Source Code (repository root)

| File | Change |
| --- | --- |
| `composer.json`, `config/scout.php`, `.env.example` | Require Scout, suggest the clients; `driver` from `SCOUT_DRIVER` (default `database`), `queue` true on `high`, `after_commit` true; each engine's settings of data-model.md |
| `app/Providers/AppServiceProvider.php` | `MakeSearchableUniquely`, `RemoveFromSearchUniquely` |
| `app/Models/Contact.php`, `Entity.php`, `LifeEvent.php` | `Searchable`, `toSearchableArray()`, `makeSearchableUsing()`; resend linked records on a rename |
| `app/Models/Concerns/SearchesRecords.php` | `matchedField()`, found in PHP whatever the engine (research R6) |
| `app/Actions/SearchRecords.php` | Scope, groups, pages; each engine's per search options (data-model.md); unavailable engine |
| `UpdateValueListItem`, `UpdateLifeEventType`, `UpdatePreferences`, `MoveRecordToStructureVersion`, `DestroyFieldValueRow`, `UndoContactMerge`, `DestroyVault` | `searchable()` or `unsearchable()` per research R4 |
| `app/Console/Commands/RebuildSearchIndexCommand.php` | `monica:rebuild-search-index` |
| `app/Http/Controllers/App/Vaults/SearchController.php`, `routes/web.php` | Results page |
| `app/ViewModels/Vaults/SearchViewModel.php`, `resources/views/app/vaults/search/index.blade.php` | Results page |
| `app/View/Components/Shell.php`, `resources/views/layouts/shell.blade.php` | Search box in the top bar |
| `app/Http/Controllers/Api/SearchController.php`, `app/Http/Resources/SearchResultResource.php`, `routes/api.php` | API |
| `resources/docs/api/21-search.php`, `resources/views/app/docs/mcp.blade.php` | Docs |
| `app/Mcp/Tools/SearchRecords.php`, `app/Mcp/Servers/MonicaServer.php` | MCP tool |
| `.github/workflows/tests.yml`, `.devcontainer/postCreate.sh`, `.devcontainer/postStart.sh` | The reference engine for the suite |
| `spec/models/017`, `019`, `022` | Their pointers to `SearchIndexEntry` now point at this design |
| `lang/*.json` | Through `php artisan monica:localize` |

Tests, one layer per rule (`.claude/rules/testing.md`):

| Test | Proves |
| --- | --- |
| `tests/Unit/Models/ContactTest.php`, `EntityTest.php`, `LifeEventTest.php` | The document per type, locale, sensitive split, linked names, what gives nothing |
| `tests/Unit/Actions/SearchRecordsTest.php` (reference engine) | Every word, typo tolerance, ranking, scopes, other account, sensitive with and without scope, soft deleted, unreachable engine |
| `tests/Unit/Actions/*Test.php` of the actions of R4 | The records resent, with a recording engine |
| `tests/Unit/Models/Concerns/SearchesRecordsTest.php` | `matchedField()`, sensitive label only |
| `tests/Feature/Controllers/App/Vaults/SearchControllerTest.php` | Renders, short term, widen scope, one group page, unavailable notice |
| `tests/Feature/Controllers/Api/SearchControllerTest.php` | Structure, 404, 422, 503 |
| `tests/Unit/Mcp/Tools/SearchRecordsTest.php`, `tests/Unit/Console/Commands/RebuildSearchIndexCommandTest.php` | Tool answer; per vault output and count |
| `tests/Unit/ViewModels/Vaults/SearchViewModelTest.php` | Groups, labels, excerpt, empty state |
| `tests/Feature/Views/Layouts/ShellTest.php` | The search box on vault screens only |

**Structure Decision**: existing layout; no new folder, no database change.

## Complexity Tracking

| Addition | Why needed | Simpler alternative rejected because |
| --- | --- | --- |
| A search engine per instance, the database one without tolerance | The constitution asks for tolerant search on every deployment | The database cannot tolerate a typo portably, so an instance on it searches without (research R1) |
| Per engine settings and options | FR-003 and FR-008 must hold on each supported engine | Engine defaults drop words or rank by typo first |
