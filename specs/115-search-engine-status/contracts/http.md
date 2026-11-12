# Contracts: Search engine status

## The routes

| Method | URL | Name | Middleware | Answer |
| --- | --- | --- | --- | --- |
| GET | `admin/search` | `admin.search.index` | `administrator` | 200 with the screen. 404 for anybody else |
| POST | `admin/search/{kind}/rebuild` | `admin.search.rebuild.create` | `administrator`, `password.confirm` | Redirect to the screen with `status` "The rebuild has started." 404 for an index other than `contacts`, `entities`, `life-events` |

## What `CheckSearchEngine::execute()` returns

```text
array{
  driver: string,                       config('scout.driver')
  health: 'healthy'|'unreachable'|'unavailable',
  reason: ?string,                      set when unreachable
  responseTimeInMilliseconds: ?int,     null for the database engine and for unavailable
  address: ?string,                     host only
  version: ?string,
  totalSizeInBytes: ?int,               Meilisearch databaseSize, the sum of every Algolia index, null otherwise
  isIndexing: ?bool,                    null when the engine does not say
  prefix: string,
  isQueued: bool,
  indexes: list<array{
    name: string,                       searchableAs()
    kind: 'contacts'|'entities'|'life-events',   also the {kind} of the rebuild route
    recordCount: int,
    documentCount: ?int,                null when the index is missing or the engine did not answer
    sizeInBytes: ?int,                  null when not reported
    exists: ?bool                       null when the engine did not answer
  }>
}
```

## What each engine is asked

Every call: `timeout(2)`, `connectTimeout(1)`, credentials in a header.

| Engine | Health | Version | Documents, size, indexing |
| --- | --- | --- | --- |
| database | none, healthy | none | `whereNotNull('search_name')->count()` per model. No size |
| meilisearch | `GET {host}/health`, `status` is `available` | `GET /version`, `pkgVersion` | `GET /stats`: `databaseSize`, `indexes.{name}.numberOfDocuments`, `rawDocumentDbSize`, `isIndexing`. An absent name is a missing index |
| typesense | `GET {node}/health`, `ok` is true | `GET /debug`, `version` | `GET /collections`: `num_documents` by `name`. No size, no indexing state |
| algolia | `GET https://{id}.algolia.net/1/indexes` answers 200 | none | The same answer: `items[].entries`, `dataSize`, `pendingTask` by `name` |

Headers: `Authorization: Bearer {key}` for Meilisearch, `X-TYPESENSE-API-KEY` for Typesense,
`X-Algolia-Application-Id` and `X-Algolia-API-Key` for Algolia.
