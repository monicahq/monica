# Contract: the timeline through the API and MCP

`GET /api/v1/vaults/{vault}/possessions/{possession}/timeline`

- Query: `section` (optional, one of the seven keys). The list is whole rather than paged, as every possession section list is.
- Answer: `{data}` in the timeline's order. Each entry:

```json
{
  "type": "possession_timeline_entry",
  "attributes": {
    "section": "loans",
    "kind": "loan_returned",
    "date": "2026-11-02",
    "date_precision": "day",
    "is_current": false,
    "is_acquisition": false,
    "record_id": "9b1e6c42-7a35-4f08-b2d9-5c8e41a7f031"
  },
  "links": { "record": "https://…/api/v1/vaults/…/possessions/…/loans/9b1e…" }
}
```

- A possession of another vault or account answers 404; an unknown `section` answers 422.
- MCP tool `list-possession-timeline`, read only, mirrors `api.v1.possessions.timeline` with `vault`, `possession`, `section`.
