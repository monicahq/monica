# Quickstart: validating the search engine status screen

## Automated

```bash
php artisan test --compact tests/Unit/Actions/CheckSearchEngineTest.php tests/Unit/ViewModels/Admin/SearchEngineViewModelTest.php tests/Feature/Controllers/App/Admin
```

The suite must prove, each in its own layer (`.claude/rules/testing.md`):

| Scenario | Spec | Layer |
| --- | --- | --- |
| Database engine: healthy, no outside call, documents counted from the `search_*` columns | US1.3, R5 | `CheckSearchEngineTest` |
| Meilisearch, Typesense, Algolia: healthy, version, counts, size where reported | US1.1, US2 | `CheckSearchEngineTest`, one test per engine, `Http::fake()` |
| Connection refused, a 401 and a timeout each give unreachable with a reason, and no further call | US1.2, FR-005 | `CheckSearchEngineTest` |
| Health answers and the index call fails: healthy, the rows carry the reason | Edge case | `CheckSearchEngineTest` |
| An index absent from the engine's answer is reported missing, the others are kept | US2.3 | `CheckSearchEngineTest` |
| An unknown driver gives no status available and still counts records | R8 | `CheckSearchEngineTest` |
| The key and the secret are nowhere in the result, for each engine and for a failure quoting them | FR-009, SC-004 | `CheckSearchEngineTest` |
| 9 documents for 12 records reads "3 missing", equal counts read "Complete", a null size reads "Not reported" | US2, SC-003 | `SearchEngineViewModelTest` |
| The screen renders for an administrator with its heading, and opening it pushes no log job | FR-001, FR-012 | `SearchEngineControllerTest` |
| Starting a rebuild pushes the job for that model and the log entry, and refuses an unknown index | US4, FR-014 | `StartSearchIndexRebuildTest` |
| The job empties then refills: a stale `search_name` is replaced, a null one is filled | US4.3 | `RebuildSearchIndexTest`, database engine |
| The rebuild is offered only when health is healthy | FR-015 | `SearchEngineViewModelTest` |
| The POST redirects with the flash, and answers not found for an unknown index and for a regular user | FR-016 | `SearchIndexRebuildControllerTest` |
| A visitor, a regular user and a deactivated administrator get not found | US1.4 | `AdminAccessTest` provider |

## By hand

1. Default configuration (`SCOUT_DRIVER=database`): open `/admin/search` as an administrator. It reads healthy, and each row shows equal counts after `php artisan scout:import` for the three models.
2. `SCOUT_DRIVER=meilisearch` with a local Meilisearch: the version and the three sizes are shown. Delete one document in Meilisearch and reload: that row shows 1 missing.
3. Stop Meilisearch and reload: unreachable, with a reason, in under 6 seconds (SC-002).
4. The same three steps with Typesense. Size reads "Not reported".
5. View the page source in steps 2 and 4: the key is absent.
6. With one document deleted and one stray document added in Meilisearch, press "Rebuild this index" on that row: the warning shows, the password is asked, and after the queue has run the row reads "Complete". The Record screen names the administrator and the index.
