# API and MCP contract

## `GET|QUERY vaults/{vault}/life-events` (`api.lifeEvents`, existing)

New parameters, combined with the existing `contact`, `page` and `per_page`:

| Parameter | Type | Effect | Refusal |
| --- | --- | --- | --- |
| `slice` | `upcoming` or a year (`2024`) | Only that slice, in its order (data-model, Slice). Without it, every life event, newest first, as today | 422 on any other value |
| `life_event_type` | uuid | Only life events of that type | 404 when it is not a type of the account |

The answer is unchanged: `{data: [LifeEventResource], meta: {page, per_page, total}}`.

## `GET|QUERY vaults/{vault}/life-event-slices` (`api.lifeEventSlices`, new)

`LifeEventSliceController@index`. Parameters: `contact`, `life_event_type`, as above.

```json
{
  "data": [
    {"slice": "upcoming", "count": 2, "total": 5},
    {"slice": "2025", "count": 3, "total": 18},
    {"slice": "2023", "count": 0, "total": 4}
  ],
  "meta": {"total": 27, "today": "2026-11-22"}
}
```

`count` is under the filters, `total` without them; `meta.total` is every life event of the vault;
`meta.today` is the token owner's today, which decides what is upcoming. Counted in one read (FR-015).

## MCP

- `ListLifeEvents` gains `slice` and `life_event_type`, described as the endpoint describes them.
- `ListLifeEventSlices`, new, mirrors `api.lifeEventSlices` through `answerThroughApi()`, read only.
  It adds nothing beyond the lookup, so it gets no test file of its own (`.claude/rules/mcp.md`).
