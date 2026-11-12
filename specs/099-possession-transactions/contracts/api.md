# Contract: API and assistant

Prefix `v1/vaults/{vault}/possessions/{possession}`. Bearer token; another vault, account or
possession answers 404. Validation errors answer 422.

| Method | Path | Answer |
| --- | --- | --- |
| GET | `transactions` | 200, `data` list, oldest first, undated first, not paginated |
| POST | `transactions` | 201, one transaction |
| GET | `transactions/{transaction}` | 200 |
| PUT | `transactions/{transaction}` | 200; fields not sent are left as they are |
| DELETE | `transactions/{transaction}` | 204; its documents are detached |
| POST | `transactions/{transaction}/documents` | 201, multipart `file`; behind `flag:file-upload` |
| DELETE | `transactions/{transaction}/documents/{file}` | 204 |

## Transaction

```json
{ "data": {
  "type": "possession_transaction", "id": "uuid",
  "attributes": {
    "type": "purchase", "changes_ownership": true, "is_acquisition": true,
    "occurred_on": "2005-03-14", "counterparty": "Dundies Inc.",
    "amount": "40.00", "tax": "3.20", "fees": null, "shipping": "6.00",
    "entered_total": null, "total": "49.20", "total_source": "sum", "currency": "usd",
    "reference": "Invoice 4021", "source_url": null, "note": null,
    "documents": [{ "id": "uuid", "name": "Invoice 4021.pdf", "mime_type": "application/pdf", "size_in_bytes": 88000 }],
    "created_at": 1730000000, "updated_at": null },
  "links": { "self": "...", "web": "..." } } }
```

`total` is derived and `total_source` is `entered`, `sum` or `unknown`. Writes take `type`,
`occurred_on`, `counterparty`, `amount`, `tax`, `fees`, `shipping`, `entered_total`, `currency`,
`reference`, `source_url` and `note`.

## Possession (changed)

`acquisition` becomes read only and derived: `{ way, acquired_on, acquired_from: { name } | null,
transaction_id }`; `declared_value` is unchanged. Create and update no longer take `acquisition`,
`acquired_from_id` or `acquired_on`, and answer 422 when they are sent, so a client relying on them
learns at once (R10).

## Assistant

New tools `list-possession-transactions`, `create-possession-transaction`,
`update-possession-transaction` and `delete-possession-transaction`, each calling the endpoint's
action. `create-possession` and `update-possession` lose the three acquisition fields. Documents are
attached through the API only, since the assistant cannot send a file.
