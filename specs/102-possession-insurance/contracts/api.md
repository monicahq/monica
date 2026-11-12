# Contract: API and assistant

Prefix `v1/vaults/{vault}/possessions/{possession}`. Bearer token; another vault, account or
possession answers 404. Validation errors answer 422.

| Method | Path | Answer |
| --- | --- | --- |
| GET | `insurance-records` | 200, `data` list in the screen's order, not paginated |
| POST | `insurance-records` | 201, one record |
| GET | `insurance-records/{record}` | 200 |
| PUT | `insurance-records/{record}` | 200; fields not sent are left as they are |
| DELETE | `insurance-records/{record}` | 204 |

## Insurance record

```json
{ "data": {
  "type": "possession_insurance_record", "id": "uuid",
  "attributes": {
    "insurer": "Sabre Insurance", "status": "active", "is_current": true,
    "policy_number": "SAB-2026-0042", "coverage": "Theft, fire and accidental damage",
    "insured_value": "500.00", "excess": "50.00", "currency": "usd",
    "starts_on": "2026-01-01", "ends_on": "2027-01-01", "is_listed_individually": true,
    "contact_name": "Gabe Lewis", "contact_email": "gabe@sabre.com", "contact_phone": null,
    "note": null, "created_at": 1730000000, "updated_at": null },
  "links": { "self": "...", "web": "..." } } }
```

`is_current` is derived (R4). Writes take every other attribute; `insurer`, `status`,
`insured_value` and `currency` are required on create.

## Assistant

New tools `list-possession-insurance-records`, `get-possession-insurance-record`,
`create-possession-insurance-record`, `update-possession-insurance-record` and
`delete-possession-insurance-record`, each calling the endpoint's action, as the transaction tools do.
