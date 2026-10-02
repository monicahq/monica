# Tasks: Full text search

**Input**: [spec.md](spec.md), [plan.md](plan.md), [research.md](research.md), [data-model.md](data-model.md), [contracts/search.md](contracts/search.md), [quickstart.md](quickstart.md)

**Tests**: required by constitution principle III and `.claude/rules/testing.md`. Each rule is tested once, in the layer that owns it: the document in its model, matching, tolerance, ranking and scoping in `SearchRecords` against the reference engine, what is resent in the action that resends it (with a recording engine), the screen in its view model, HTTP in the controllers.

## Phase 1: Setup

- [X] T001 Read `.claude/skills/{actions,models,config,controllers,routes,view-models,views,components,tests,translations,api-writer,api-docs-writer}/SKILL.md` and `.claude/rules/{code-style,testing,components,translations,policies}.md`
- [X] T002 Run `composer require laravel/scout`, publish `config/scout.php` with `php artisan vendor:publish --provider="Laravel\Scout\ScoutServiceProvider"`, add the Algolia, Meilisearch and Typesense clients to `suggest` in `composer.json`, and the Meilisearch client with `http-interop/http-factory-guzzle` to `require-dev` (research R7)
- [X] T003 Set `config/scout.php`: `driver` from `SCOUT_DRIVER` defaulting to `null`, `prefix` from `SCOUT_PREFIX`, `queue` true on queue `high`, `after_commit` true, `soft_delete` false; for `Contact`, `Entity` and `LifeEvent`, the Algolia `index-settings`, the Meilisearch `index-settings` and the Typesense `model-settings` of the requirements table of data-model.md; add `SCOUT_DRIVER` and each engine's credentials, commented, to `.env.example`
- [X] T004 Run the reference engine for the suite (research R7): a pinned `getmeili/meilisearch` service with a master key on port 7700 in `.github/workflows/tests.yml`, its host and key in the job's environment; the pinned binary downloaded in `.devcontainer/postCreate.sh` and started in the background in `.devcontainer/postStart.sh`
- [X] T005 Register `Scout::makeSearchableUsing(MakeSearchableUniquely::class)` and `Scout::removeFromSearchUsing(RemoveFromSearchUniquely::class)` in `app/Providers/AppServiceProvider.php`

## Phase 2: Foundational

- [X] T006 Create `tests/Fixtures/Engines/RecordingEngine.php`, a Scout engine recording the models passed to `update()` and `delete()`, and add to `tests/TestCase.php` a `useRecordingSearchEngine()` that registers it as the driver, and a `useSearchEngine()` that sets a prefix unique to the test, syncs the index settings, and flushes the indexes afterwards
- [X] T007 Keep every other test without an engine: `SCOUT_DRIVER` `null` in `phpunit.xml`, so saving in a test sends nothing unless the test asks for one

## Phase 3: User Story 1, find a record by anything written on it (P1) MVP

**Independent test**: Dwight's "Employer" reads "Schrute Farms"; searching "farms dwight" or "Scrute" lists him under People with "Employer: Schrute Farms".

- [X] T008 [P] [US1] Test the documents in `tests/Unit/Models/ContactTest.php` and `tests/Unit/Models/EntityTest.php`: `body` holds Text, Textarea, Number, Url, Email, Phone, Address, Date (a French account reads `14 mars 1966`), the Select and Multiselect labels, a File name and a relation value's target name; nothing from Boolean, Avatar, layout fields or detached values; sensitive values only in `sensitive_body`; the account's locale used when built in a queued job; `entity_type_id` set for an entity, null for a contact
- [X] T009 [US1] Add `Searchable`, `toSearchableArray()` and `makeSearchableUsing()` to `app/Models/Contact.php` and `app/Models/Entity.php` per the document table of data-model.md, values written by `FieldValueFormatter` for the account's user inside the account's locale
- [X] T010 [US1] Test `SearchRecords` against the reference engine (`useSearchEngine()`) in `tests/Unit/Actions/SearchRecordsTest.php`: every word in any order; "Scrute" and "Dwigth" find Dwight and "elodie" finds Élodie; a name match before a value match and an exact match before a tolerant one; a choice found by its label; a field created a moment ago; the current vault by default; the account scope naming each vault; another account never; sensitive values matched with `withSensitive` and not without; a soft deleted record gone and back once restored; an archived contact marked; a term under two letters or digits searches nothing; an unreachable engine answers unavailable instead of throwing
- [X] T011 [US1] Create `app/Actions/SearchRecords.php`: constructor with promoted `readonly` `User $user`, `Vault $vault`, `string $query`, `string $scope`, `?string $group`, `bool $withSensitive`, `int $page`; the account check first; `execute()` returns the groups People then one per entity type (`name_plural`), each `{key, label, paginator}` paged by 20, searched with each engine's per search options of data-model.md, built in one private method keyed by the driver; it catches the failure to reach the engine, and a `null` driver, and returns an unavailable result
- [X] T012 [P] [US1] Test `matchedField()` in `tests/Unit/Models/Concerns/SearchesRecordsTest.php` without an engine: the first value holding a word, exactly or within one edit from 5 letters and two from 9, with its label and text; the label alone for a sensitive value; null when the name holds it
- [X] T013 [US1] Create `app/Models/Concerns/SearchesRecords.php` with `matchedField(array $words, bool $withSensitive): ?array{label: string, excerpt: ?string}` per research R6, comparing words folded with `LocaleCollation::foldForSearch()`, and use it on Contact and Entity
- [X] T014 [US1] Test with the recording engine that saving a field value sends its record once (SC-002), and that `UpdateValueListItem`, `UpdatePreferences` (locale or date format), `MoveRecordToStructureVersion`, `DestroyFieldValueRow` and `UndoContactMerge` send the records of research R4 and `DestroyVault` removes its vault's records, each in that action's test under `tests/Unit/Actions/`
- [X] T015 [US1] Add those `searchable()` and `unsearchable()` calls to `app/Actions/UpdateValueListItem.php`, `UpdatePreferences.php`, `MoveRecordToStructureVersion.php`, `DestroyFieldValueRow.php`, `UndoContactMerge.php` and `DestroyVault.php`
- [X] T016 [US1] Test `app/ViewModels/Vaults/SearchViewModel.php` in `tests/Unit/ViewModels/Vaults/SearchViewModelTest.php`: groups and their counts, row name, url, vault name when widened, archived label, excerpt with the words in `<mark>`, the empty, short term and unavailable lines
- [X] T017 [US1] Create `app/ViewModels/Vaults/SearchViewModel.php` from `SearchRecords`, the reader able to read sensitive values
- [X] T018 [US1] Add `Route::get('vaults/{vault}/search', [SearchController::class, 'index'])->name('search.index')` to the vault group of `routes/web.php` and create `app/Http/Controllers/App/Vaults/SearchController.php` validating inline `query` (string, max 255), `scope` (`vault` or `account`), `group` (string), `page` (integer)
- [X] T019 [US1] Create `resources/views/app/vaults/search/index.blade.php`: `x-shell` with the vault sidebar and breadcrumb, `x-page-header`, a `role="search"` form with a labelled input named `query` and a "This vault / Every vault" radio group, the result count in an `aria-live="polite"` status, one `x-list-panel` per group with its "Load more", the empty state, and an `x-notice` when search is unavailable
- [X] T020 [US1] Test `tests/Feature/Controllers/App/Vaults/SearchControllerTest.php`: the page renders with its heading, a short term shows its line, `scope=account` widens, `group` pages one group, the unavailable notice, a vault of the same account reached wrongly is not found
- [X] T021 [US1] Resolve the route's `Vault` in `app/View/Components/Shell.php` and draw the search form before `$actions` in `resources/views/layouts/shell.blade.php` (input from `md` up, a link to the page below); test its presence and label on a vault screen and its absence elsewhere in `tests/Feature/Views/Layouts/ShellTest.php`
- [ ] T022 [US1] Measure SC-001, SC-002 and SC-005 per quickstart.md section 3; on a miss, stop and report before any other phase

## Phase 4: User Story 2, find by what happened and who was there (P2)

**Independent test**: searching "jan" lists Jan before Michael ("Partner of Jan Levinson") among People, and the Chili's dinner ("With Jan Levinson") among Life events.

- [X] T023 [P] [US2] Test in `tests/Unit/Models/LifeEventTest.php` the life event document (name, values, narrative and location in `body`, participants in `linked_names`) and in `tests/Unit/Models/ContactTest.php` a contact's `linked_names` from relationships on either side, a deleted relationship left out
- [X] T024 [US2] Add `Searchable`, `toSearchableArray()`, `makeSearchableUsing()` and `SearchesRecords` to `app/Models/LifeEvent.php`, and the related people's names to the contact document in `app/Models/Contact.php`
- [X] T025 [US2] Resend, when a contact's or entity's `search_key` changed on save, the life events it takes part in, its related contacts and the records whose relation value points at it, from `saved` in `app/Models/Contact.php` and `app/Models/Entity.php`; resend a renamed type's untitled life events from `app/Actions/UpdateLifeEventType.php`; test both with the recording engine
- [X] T026 [US2] Extend `tests/Unit/Actions/SearchRecordsTest.php` (reference engine): a life event by title, narrative, location, values and a participant's name; a contact by a related person's name; linked name matches ranked last; groups ordered People, entity types, Life events; then add the Life events group to `app/Actions/SearchRecords.php`
- [X] T027 [US2] Add the life event date and the "Partner of :name" / "With :name" excerpts to `app/ViewModels/Vaults/SearchViewModel.php`, tested in `tests/Unit/ViewModels/Vaults/SearchViewModelTest.php`

## Phase 5: User Story 3, search from the API and MCP (P2)

**Independent test**: `GET api/v1/vaults/{vault}/search?query=farms+dwight` returns the People group holding Dwight, 404 for another account's vault.

- [X] T028 [US3] Test `tests/Feature/Controllers/Api/SearchControllerTest.php` with a shared `$jsonStructure` of contracts/search.md: the structure, 404 for another account's vault, 422 for a missing or one character `query`, 503 while the engine is unreachable, a key without `sensitive-fields` not matching a sensitive value
- [X] T029 [US3] Add `Route::get('vaults/{vault}/search', [SearchController::class, 'index'])->name('search')` to the v1 group of `routes/api.php`; create `app/Http/Controllers/Api/SearchController.php` resolving the vault like `ContactController::vault()`, `withSensitive` read like `ContactVCardController`, `per_page` clamped like `ContactController::index`; create `app/Http/Resources/SearchResultResource.php`
- [X] T030 [P] [US3] Write `resources/docs/api/21-search.php` following `.claude/skills/api-docs-writer/SKILL.md`, the 503 included
- [X] T031 [US3] Create `app/Mcp/Tools/SearchRecords.php` (`#[IsReadOnly]`, `MirrorsAnApiCapability`, `$mirrors = 'api.v1.search'`, schema `address`, `query`, `scope`, `group`), register it in `app/Mcp/Servers/EricaServer.php`, describe it in `resources/views/app/docs/mcp.blade.php`, test it in `tests/Unit/Mcp/Tools/SearchRecordsTest.php`

## Phase 6: User Story 4, an operator runs the search engine (P3)

**Independent test**: with the engine stopped, Dwight saves and the page says search is unavailable; restarted and rebuilt, he is found.

- [X] T032 [US4] Create `app/Console/Commands/RebuildSearchIndexCommand.php`, `monica:rebuild-search-index {--account=}`, per contracts/search.md, and test its per vault lines and final count with the recording engine in `tests/Unit/Console/Commands/RebuildSearchIndexCommandTest.php`
- [ ] T033 [US4] Walk quickstart.md section 2 by hand: engine stopped, save, notice, 503, restart, caught up; no engine configured

## Phase 7: Polish

- [X] T034 Run `php artisan monica:localize`, translate every reported string in every locale per `.claude/rules/translations.md`, then `php artisan monica:localize --check`
- [X] T035 Point the `SearchIndexEntry` mentions of `spec/models/017-field-done.md`, `019-contact-done.md` and `022-field-value-done.md` at this design
- [X] T036 Run `php artisan test --compact`, `vendor/bin/phpstan analyse --no-progress` and `vendor/bin/pint --dirty --format agent`
- [ ] T037 Walk quickstart.md section 1 by hand, keyboard and screen reader included, then section 4 on Algolia and Typesense

## Dependencies

- Phase 1 → Phase 2 → every story. T004 before any engine test (T010, T026).
- US1 before US2 (it extends the documents, `SearchRecords` and the view model) and before US3 (the API wraps `SearchRecords`).
- US4 depends only on Phase 2.
- T022 gates everything after it.
- Within a story, a test task precedes the implementation it proves.

## Parallel opportunities

- T008 and T012 beside T005 to T007.
- T023 beside T026's test half.
- T030 beside T028, T029 and T031.
- US4 beside US2 and US3.

## Implementation strategy

MVP is Phases 1 to 3: contacts and entities found by any value, typing mistakes tolerated, from the top bar. The T022 measurement then confirms the targets. Then US2, US3, US4, polish.
