# Tasks: Search engine status in the admin space

**Input**: [spec.md](spec.md), [plan.md](plan.md), [research.md](research.md), [contracts/http.md](contracts/http.md), [quickstart.md](quickstart.md)

**Tests**: required by constitution principle III, written in the layer `.claude/rules/testing.md` assigns. Data from The Office. Read the `actions`, `controllers`, `view-models`, `views`, `routes`, `tests` and `translations` skills before the matching task. Every engine call uses `Http::timeout(2)->connectTimeout(1)` and is tested with `Http::fake()`. The index kinds are `contacts`, `entities` and `life-events`.

## Phase 1: Setup

No setup: no table, no enum, no dependency.

## Phase 2: Foundational

- [X] T001 Create `CheckSearchEngine` with `execute()` returning the array shape of contracts/http.md, filled for every driver with `driver`, `prefix`, `isQueued` and one `indexes` row per `Contact`, `Entity`, `LifeEvent` holding `name` from `searchableAs()` and `recordCount` from `Model::query()->count()` (R6), `health` `unavailable` and every other figure null, in app/Actions/CheckSearchEngine.php
- [X] T002 Test T001: an unknown driver (`collection`) gives `unavailable`, the three rows with their record counts, and no HTTP call (R8), in tests/Unit/Actions/CheckSearchEngineTest.php

**Checkpoint**: the action answers for any driver; stories fill it in.

## Phase 3: User Story 1, an administrator sees whether search works (P1) MVP

**Independent test**: with the engine stopped the screen reads unreachable; started, it reads healthy.

- [X] T003 [US1] Resolve the engine through Scout's `EngineManager` in a `try` before any call; an exception gives `unreachable` with its message (R7), in app/Actions/CheckSearchEngine.php
- [X] T004 [US1] Database driver: `healthy`, no HTTP call, null `responseTimeInMilliseconds`, `address` and `version` (R5), in app/Actions/CheckSearchEngine.php
- [X] T005 [US1] Meilisearch: `GET {host}/health` then `GET /version` (`pkgVersion`), `address` the host only, response time measured on the health call, in app/Actions/CheckSearchEngine.php
- [X] T006 [US1] Typesense: `GET {node}/health` (`ok`) then `GET /debug` (`version`), node built from `scout.typesense.client-settings.nodes.0`, in app/Actions/CheckSearchEngine.php
- [X] T007 [US1] Algolia: `GET https://{id}.algolia.net/1/indexes` answering 200 is healthy, `address` `{id}.algolia.net`, no version (R4), in app/Actions/CheckSearchEngine.php
- [X] T008 [US1] A failed health call (connection error, timeout, non 2xx) gives `unreachable`, a `reason` that is the HTTP status or the error message with the key and secret replaced, and stops further calls (R3, R9), in app/Actions/CheckSearchEngine.php
- [X] T009 [US1] Test T003 to T008 with Scout's `EngineManager` replaced in the container by a stub (R7): one healthy test per engine, connection refused, a 401, a timeout, a stub that throws for the missing client, and for each engine that the key and secret appear nowhere in `json_encode()` of the result (SC-004), in tests/Unit/Actions/CheckSearchEngineTest.php
- [X] T010 [P] [US1] Create `SearchEngineViewModel` taking the action's array: `engineName()` ("Database", "Meilisearch", "Typesense", "Algolia", else the raw driver), `healthLabel()` ("Healthy", "Unreachable", "No status available"), `healthTone()`, `reason()`, `responseTime()`, `address()`, `version()`, `toleratesTypingMistakes()` false for the database, in app/ViewModels/Admin/SearchEngineViewModel.php
- [X] T011 [P] [US1] Test T010 wording for each health and for the database engine in tests/Unit/ViewModels/Admin/SearchEngineViewModelTest.php
- [X] T012 [US1] Add `Route::get('admin/search', [SearchEngineController::class, 'index'])->name('admin.search.index')` inside the `administrator` group of routes/administrator.php
- [X] T013 [US1] Create `SearchEngineController::index()` returning `app.admin.search.index` with `new SearchEngineViewModel(status: new CheckSearchEngine()->execute())` in app/Http/Controllers/App/Admin/SearchEngineController.php
- [X] T014 [US1] Create the view with `x-page-header` ("Search"), a "Check again" link to the same route, and the health block: engine, health in words through `x-badge`, reason, response time, address, version, the typing mistakes sentence, in resources/views/app/admin/search/index.blade.php
- [X] T015 [US1] Add `['label' => __('Search'), 'route' => 'admin.search.index', 'section' => 'admin.search.*', 'icon' => 'search']` after Configuration in resources/views/layouts/admin.blade.php
- [X] T016 [P] [US1] Test that the screen renders for an administrator with "Search" and the engine name, and that opening it pushes no `LogAdministratorAction` (FR-012), in tests/Feature/Controllers/App/Admin/SearchEngineControllerTest.php
- [X] T017 [P] [US1] Add `'the search engine' => ['admin/search']` to the provider of tests/Feature/Controllers/App/Admin/AdminAccessTest.php and `route('admin.search.index')` to the first list of tests/Feature/Controllers/App/Admin/NoVaultContentTest.php

**Checkpoint**: US1 is shippable alone.

## Phase 4: User Story 2, an administrator sees what each index holds (P2)

**Independent test**: 12 contacts in the instance and 9 in the index show 9, 12 and 3 missing.

- [X] T018 [US2] Database driver: `documentCount` from `whereNotNull('search_name')->count()` per model, `exists` true, `sizeInBytes` null (R5), in app/Actions/CheckSearchEngine.php
- [X] T019 [US2] Meilisearch: `GET /stats` in the same `Http::pool()` as `/version`; per index `numberOfDocuments`, `rawDocumentDbSize`; a name absent from `indexes` gives `exists` false and null counts, in app/Actions/CheckSearchEngine.php
- [X] T020 [US2] Typesense: `GET /collections` in the same pool as `/debug`; `num_documents` by `name`, `sizeInBytes` null, an absent name gives `exists` false, in app/Actions/CheckSearchEngine.php
- [X] T021 [US2] Algolia: read `items[].entries` and `dataSize` by `name` from the answer of T007, an absent name gives `exists` false, in app/Actions/CheckSearchEngine.php
- [X] T022 [US2] When health passed and the index call fails, keep `healthy`, set each row's `reason`, leave `documentCount` and `exists` null, in app/Actions/CheckSearchEngine.php
- [X] T023 [US2] Test T018 to T022: counts and sizes per engine, a missing index beside two present ones, the index call failing after a healthy answer, database rows with null `search_name` counted as not indexed, in tests/Unit/Actions/CheckSearchEngineTest.php
- [X] T024 [P] [US2] Add `indexes()` to the view model: per row a kind label ("Contacts", "Entities", "Life events"), both counts, `difference()` through `trans_choice()` (":count missing", ":count left over", "Complete" at zero), "This index does not exist yet", size through `App\Helpers\StorageSize` or "Not reported" (R10), in app/ViewModels/Admin/SearchEngineViewModel.php
- [X] T025 [P] [US2] Test T024: 9 of 12 reads "3 missing", 14 of 12 reads "2 left over", equal reads "Complete", null size reads "Not reported", a missing index, in tests/Unit/ViewModels/Admin/SearchEngineViewModelTest.php
- [X] T026 [US2] Add the index table (`<table>` with `<caption>` and `scope="col"` headers: Index, Documents indexed, Records, Difference, Size) under the health block in resources/views/app/admin/search/index.blade.php

**Checkpoint**: US1 and US2 work together.

## Phase 5: User Story 3, an administrator sees how the instance talks to the engine (P3)

**Independent test**: with queued changes and a prefix set, the block shows both.

- [X] T027 [US3] Fill `isIndexing`: Meilisearch true when any index of `/stats` has `isIndexing`, Algolia true when any of the three items has `pendingTask`, null for Typesense and the database, in app/Actions/CheckSearchEngine.php
- [X] T028 [US3] Test T027 per engine in tests/Unit/Actions/CheckSearchEngineTest.php
- [X] T029 [P] [US3] Add `prefix()` ("None" when empty), `queueLabel()` and `indexingLabel()` (null when the engine does not say) to app/ViewModels/Admin/SearchEngineViewModel.php, tested in tests/Unit/ViewModels/Admin/SearchEngineViewModelTest.php
- [X] T030 [US3] Add the settings block with `x-detail-list` (Engine, Index prefix, Changes sent through the queue, Still indexing) to resources/views/app/admin/search/index.blade.php

## Phase 6: User Story 4, an administrator rebuilds an index (P2)

**Independent test**: with 9 of 12 contacts indexed, rebuilding the contacts index brings the row to 12 of 12.

- [X] T031 [P] [US4] Add `SearchIndexRebuildStarted = 'search_index_rebuild_started'` to app/Enums/AdministratorActionEnum.php with its description naming the `index` parameter, its icon and its translation keys, like `FlagActivatedForEveryAccount`
- [X] T032 [P] [US4] Create the job `RebuildSearchIndex` (`ShouldQueue`, `ShouldBeUnique` with the model class as `uniqueId()`), taking a `class-string` of `Contact`, `Entity` or `LifeEvent`, whose `handle()` calls `$model::removeAllFromSearch()` then `$model::sendToSearch($model::query())` (R12, R13, R15), in app/Jobs/RebuildSearchIndex.php
- [X] T033 [P] [US4] Test T032 on the database engine: a contact with a stale `search_name` and one with a null `search_name` both hold the current name afterwards, in tests/Unit/Jobs/RebuildSearchIndexTest.php
- [X] T034 [US4] Create the action `StartSearchIndexRebuild` (`kind: string`, `administrator: ?User`): map `contacts`, `entities`, `life-events` to their model, throw `ModelNotFoundException` for anything else, dispatch `RebuildSearchIndex` on the `low` queue, dispatch `LogAdministratorAction` with `SearchIndexRebuildStarted` and `['index' => $index]` (R14), in app/Actions/StartSearchIndexRebuild.php
- [X] T035 [US4] Test T034 with `Queue::fake()`: the job is pushed on `low` for the right model, the log job carries the administrator and the index, an unknown index throws and pushes nothing, in tests/Unit/Actions/StartSearchIndexRebuildTest.php
- [X] T036 [US4] Add `Route::post('admin/search/{kind}/rebuild', [SearchIndexRebuildController::class, 'create'])->where('index', '[a-z-]+')->name('admin.search.rebuild.create')` (the action refuses an unknown index as not found, as the Flags route does) inside the `password.confirm` group of routes/administrator.php
- [X] T037 [US4] Create `SearchIndexRebuildController::create()` calling the action and redirecting to `admin.search.index` with `status` "The rebuild has started." and `status_description` "Search misses records of this kind until it has finished.", in app/Http/Controllers/App/Admin/SearchIndexRebuildController.php
- [X] T038 [US4] Add `canRebuild()` (true only when health is healthy, FR-015) and a `rebuildUrl` per row to app/ViewModels/Admin/SearchEngineViewModel.php, tested in tests/Unit/ViewModels/Admin/SearchEngineViewModelTest.php
- [X] T039 [US4] Add one `x-modal.confirm-password` and, per row when `canRebuild()`, an `x-modal.warning` with `confirms-password`, title "Rebuild this index?", confirm "Rebuild it", a trigger button "Rebuild this index" followed by the kind in `sr-only`, and the body "The index is emptied, then every record is sent again. Until that has finished, search misses records of this kind for every account." (FR-016), as resources/views/app/admin/flags/index.blade.php does, in resources/views/app/admin/search/index.blade.php
- [X] T040 [P] [US4] Test the POST with `Queue::fake()`: redirect and flash, not found for an unknown index and for a regular user, with the password confirmed the way FlagActivationControllerTest does, in tests/Feature/Controllers/App/Admin/SearchIndexRebuildControllerTest.php
- [X] T047 [US2] Add `totalSizeInBytes` (Meilisearch `databaseSize`, the sum of `dataSize` over every Algolia index, null elsewhere) to app/Actions/CheckSearchEngine.php, and `totalSize()` in megabytes with the reader's number format to app/ViewModels/Admin/SearchEngineViewModel.php, shown as "Total index size" in the engine panel, tested in both test files

## Phase 7: Polish

- [X] T041 Run `php artisan monica:localize` and translate every new string in every locale of lang/*.json, following `.claude/rules/translations.md`
- [X] T042 [P] Add the entry to docs/FEATURES.md with the `feature-documentator` skill
- [X] T043 Run the full suite, `vendor/bin/phpstan analyse --no-progress`, `vendor/bin/pint --dirty --format agent`, `php artisan monica:localize --check`
- [X] T044 Run the `accessibility-reviewer` and `rules-reviewer` agents on the change and fix what they find
- [ ] T045 Do the "By hand" steps of quickstart.md against a live Meilisearch and Typesense, and correct contracts/http.md and the action where a response field differs
- [ ] T046 State in the pull request that Principle X is not met for the status and the rebuild (plan.md, Complexity Tracking) and that counting is not bounded (R17)

## Dependencies

- T001 before everything. US1 (T003 to T017) before US2 and US3, which both extend its action, view model and view.
- US2 and US3 are independent of each other, but T019 and T027 touch the same Meilisearch answer: do T019 first.
- US4 needs the index rows of US2 (T024, T026). T031 to T033 can start any time.
- Tasks in app/Actions/CheckSearchEngine.php are sequential. [P] tasks touch other files.
- MVP: Phases 2 and 3.
