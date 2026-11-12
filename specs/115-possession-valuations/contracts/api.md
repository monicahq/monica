# Contract: API and assistant

Prefix `v1/vaults/{vault}/possessions/{possession}`. Bearer token; another vault, account or
possession answers 404. Validation errors answer 422. There is no PUT: a valuation is never edited (R6).

| Method | Path | Answer |
| --- | --- | --- |
| GET | `valuations` | 200, `data` list newest first, not paginated |
| POST | `valuations` | 201, one valuation |
| GET | `valuations/{valuation}` | 200 |
| DELETE | `valuations/{valuation}` | 204; its documents are detached |
| POST | `valuations/{valuation}/documents` | 201, multipart `file`; behind `flag:file-upload` |
| DELETE | `valuations/{valuation}/documents/{file}` | 204 |

## Valuation

```json
{ "data": {
  "type": "possession_valuation", "id": "uuid",
  "attributes": {
    "type": "appraisal", "valued_on": "2026-03-14", "confidence": "certain",
    "amount": "650.00", "currency": "usd", "is_current": true,
    "valuer": "Scranton Jewelers", "method": "Inspection and comparable sales",
    "reference_number": "APP-2026-0212", "source_url": "https://example.com/appraisal",
    "note": null, "created_at": 1773446400,
    "documents": [{ "id": "uuid", "name": "Appraisal.pdf", "mime_type": "application/pdf", "size_in_bytes": 245760 }] },
  "links": { "self": "...", "web": "..." } } }
```

`is_current` is derived (R4). `type`, `valued_on`, `amount` and `currency` are required on create;
`confidence` defaults to `unknown`. `documents` follows the shape the transaction resource uses.

## Assistant

New tools `list-possession-valuations`, `get-possession-valuation`, `create-possession-valuation` and
`delete-possession-valuation`, each calling the endpoint's action, as the transaction tools do. No
tool uploads a file, as for transactions.
