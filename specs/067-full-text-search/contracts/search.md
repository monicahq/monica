# Contract: search

One action, `App\Actions\SearchRecords`, answers all three surfaces.

## Web

`GET vaults/{vault}/search` → `[SearchController::class, 'index']`, name `search.index`
(`App\Http\Controllers\App\Vaults`), in the signed in vault group.

| Query key | Rule | Meaning |
| --- | --- | --- |
| `query` | string, max 255 | the term; under two letters or digits, the page says so and searches nothing |
| `scope` | `vault` (default) or `account` | FR-004 |
| `group` | a `group_key` | page one group only, for "Load more" |
| `page` | integer | page of that group |

## API

`GET api/v1/vaults/{vault}/search`, name `api.v1.search`. `{vault}` is a string resolved through
`$request->user()->account->vaults()->addressedBy()`, so another account's vault is 404.

Same keys, with `query` required and at least two characters (422 otherwise), and `per_page`
clamped like `ContactController::index`. A key without the `sensitive-fields` scope never matches
on a sensitive field. While the engine is unreachable the answer is 503; the page shows a notice.

```json
{
  "data": [
    {
      "group": "people",
      "label": "People",
      "total": 2,
      "next_page": null,
      "results": [
        {
          "type": "contact", "id": "9f1c…", "name": "Dwight Schrute",
          "vault": {"id": "…", "name": "Work"},
          "archived": false, "date": null,
          "matched": {"label": "Employer", "excerpt": "Schrute Farms"}
        }
      ]
    }
  ],
  "meta": {"query": "farms dwight", "scope": "vault"}
}
```

`type` is `contact`, `entity` or `life_event`; `id` is the identifier each record's own endpoint
takes. `matched` is null for a name match; `excerpt` is null for a sensitive field.

## MCP

Tool `SearchRecords`, `#[IsReadOnly]`, `use MirrorsAnApiCapability`, `$mirrors = 'api.v1.search'`,
schema `address`, `query`, `scope`, `group`. It calls the action and returns the JSON above.

## Command

`monica:rebuild-search-index {--account=}`: syncs the index settings, then per account and vault
sends every contact, entity and life event to the configured engine; prints `Vault {id}: {n} records`, and ends
with `Indexed {n} records in {m} vaults.`
