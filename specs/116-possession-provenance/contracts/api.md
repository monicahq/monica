# Contract: API and assistant

Prefix `v1/vaults/{vault}/possessions/{possession}`. Bearer token; another vault, account or
possession answers 404. Validation errors answer 422.

| Method | Path | Answer |
| --- | --- | --- |
| GET | `provenance` | 200, `data` list in chain order (R5), not paginated |
| POST | `provenance` | 201, one event |
| GET | `provenance/{event}` | 200 |
| PUT | `provenance/{event}` | 200, one event; what is left out keeps its value, as the transaction PUT |
| DELETE | `provenance/{event}` | 204; the linked transaction stays |

## Event

```json
{ "data": {
  "type": "possession_provenance_event", "id": "uuid",
  "attributes": {
    "type": "acquisition", "group": "ownership", "title": "Bought at Scranton Jewelers",
    "date_precision": "day", "occurred_on": "2005-03-24",
    "from": "Scranton Jewelers", "to": "Michael Scott", "location": "Scranton",
    "description": null, "reference_number": "Receipt 0347", "source_url": null,
    "is_verified": true, "transaction_id": "uuid", "created_at": 1773446400 },
  "links": { "self": "...", "web": "..." } } }
```

`type` and `date_precision` are required. `occurred_on` is written and read in the shape its
precision gives (R3): `2005-03-24`, `2005-03`, `2005`, or null for `unknown`. `title` is null when
none was written. `transaction_id` is the uuid of a transaction of the same possession, or null.

## Assistant

New tools `list-possession-provenance-events`, `get-possession-provenance-event`,
`create-possession-provenance-event`, `update-possession-provenance-event` and
`delete-possession-provenance-event`, each calling the endpoint's action, as the transaction tools do.
