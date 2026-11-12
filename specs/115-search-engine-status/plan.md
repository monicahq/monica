# Implementation Plan: Search engine status in the admin space

**Branch**: `claude/search-engine-status-admin-87ad9f` | **Date**: 2026-11-12 | **Spec**: [spec.md](spec.md)

## Summary

A Search screen in the admin space. One action questions the configured engine over HTTP and counts
the instance's own records, one view model words the answer, one Blade view shows it. Each index row
can be rebuilt: a second action starts a queued job that empties the index and sends every record
again. No table, no migration, no new dependency. Decisions are in [research.md](research.md), the
routes and the engine endpoints in [contracts/http.md](contracts/http.md).

## Technical Context

| | |
| --- | --- |
| Language | PHP 8.4, Laravel 13, Blade, Tailwind 4, Alpine.js |
| Packages | `laravel/scout` 11, Laravel's HTTP client. No new dependency |
| Storage | None. The rebuild writes to the engine, or to the `search_*` columns for the database engine |
| Testing | PHPUnit, `RefreshDatabase`, `Http::fake()`, `Queue::fake()` |
| Constraints | Whole check under 5 seconds (FR-005): every engine call has a 2 second timeout (R3) |
| Scale | Three indexes. A rebuild walks every record of one kind, so it runs on the queue (R12) |

## Constitution Check

| Principle | How the plan meets it |
| --- | --- |
| I. Actions | `CheckSearchEngine` holds the check, `StartSearchIndexRebuild` the rebuild. The view model only words the result |
| II. Simplicity | One check action with one private method per engine, no driver interface, no value object (R2). The rebuild reuses `removeAllFromSearch()` and `sendToSearch()` |
| III. Tested | Both actions, the job, the view model wording, one test per controller, `AdminAccessTest` and `NoVaultContentTest` entries |
| IV. Conventions | One named route per line, thin controllers, array shape docblock, job named after what it does |
| V. Localized and safe | All copy through `__()` and `trans_choice()`. The rebuild asks first through `x-modal.warning` and says what search loses meanwhile (FR-016) |
| VI, VII | Not touched |
| VIII. Nothing disappears silently | Only the index is emptied, never a record, and the administrator log says who did it (R14) |
| IX. Accessible | Health in words beside its tone, a real table with headers, each rebuild button names its index for a screen reader, reviewed by the `accessibility-reviewer` agent |
| X. API | **Not met**, for the status and for the rebuild, decided in the spec's clarifications. Tracked below |
| XI. Short record | All artifacts within their caps. `data-model.md` omitted: nothing is stored |
| No vault content in admin | Counts only, instance wide (FR-009, FR-010). The screen joins `NoVaultContentTest` |

Gate: passes with one tracked violation. Re-checked after Phase 1: unchanged.

## Project Structure

```text
app/Actions/CheckSearchEngine.php                              the check (R1 to R9)
app/Actions/StartSearchIndexRebuild.php                        validates the index, dispatches the job, logs (R12 to R14)
app/Jobs/RebuildSearchIndex.php                                empties one index, then sends every record (R12, R13)
app/Enums/AdministratorActionEnum.php                          new case SearchIndexRebuildStarted
app/ViewModels/Admin/SearchEngineViewModel.php                 labels, differences, sizes, whether a rebuild is offered
app/Http/Controllers/App/Admin/SearchEngineController.php      index
app/Http/Controllers/App/Admin/SearchIndexRebuildController.php create
routes/administrator.php                                       GET admin/search, POST admin/search/{kind}/rebuild
resources/views/app/admin/search/index.blade.php               health, index table with rebuild, settings, Check again link
resources/views/layouts/admin.blade.php                        Search entry, icon `search`
lang/*.json                                                    via monica:localize
docs/FEATURES.md                                               one entry
tests/Unit/Actions/{CheckSearchEngine,StartSearchIndexRebuild}Test.php
tests/Unit/Jobs/RebuildSearchIndexTest.php
tests/Unit/ViewModels/Admin/SearchEngineViewModelTest.php
tests/Feature/Controllers/App/Admin/{SearchEngine,SearchIndexRebuild}ControllerTest.php
tests/Feature/Controllers/App/Admin/{AdminAccess,NoVaultContent}Test.php   one more URL each
```

**Structure Decision**: the existing Laravel layout, beside the other admin screens.

## Implementation order

1. `CheckSearchEngine` and its test: database engine first, then Meilisearch, Typesense, Algolia, then the failures.
2. `SearchEngineViewModel` and its test.
3. Route, controller, view, navigation entry, controller test, `AdminAccessTest`, `NoVaultContentTest`.
4. The rebuild: enum case, job, action, route behind `password.confirm`, controller, the confirmation in the view, their tests.
5. Translations, `docs/FEATURES.md`.
6. Full suite, PHPStan, Pint, `monica:localize --check`, accessibility and rules reviews.
7. Manual check against a live Meilisearch and Typesense ([quickstart.md](quickstart.md)): the response shapes of R4 are from the engines' documentation and no test here can prove them.

## Complexity Tracking

| Violation | Why Needed | Simpler Alternative Rejected Because |
| --- | --- | --- |
| Principle X: no API method and no MCP tool for the status or the rebuild | Decided in the spec (Clarifications, 2026-11-12). Flags, Configuration and Overview are web only too | An API surface would need the `administration` scope, a resource, two MCP tools and their docs. The pull request must name this gap, which now covers an action |
