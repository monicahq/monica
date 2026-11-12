# Contract: API and assistant

Prefix `v1/vaults/{vault}/possessions/{possession}`. Bearer token; another vault, account or
possession answers 404, and so does a condition of another account. Validation errors answer 422.

| Method | Path | Answer |
| --- | --- | --- |
| GET | `maintenance-records` | 200, `data` list in the screen's order, not paginated |
| POST | `maintenance-records` | 201, one record |
| GET | `maintenance-records/{record}` | 200 |
| PUT | `maintenance-records/{record}` | 200; fields not sent are left as they are |
| DELETE | `maintenance-records/{record}` | 204 |

## Maintenance record

```json
{ "data": {
  "type": "possession_maintenance_record", "id": "uuid",
  "attributes": {
    "maintenance_type": "restoration", "title": "Re-plating",
    "performed_by": "Scranton Trophy & Engraving", "performed_on": "2026-03-12",
    "cost": "40.00", "currency": "usd",
    "condition_before": { "id": "uuid", "name": "Worn" },
    "condition_after": { "id": "uuid", "name": "Like new" },
    "next_due_on": "2027-03-12", "is_overdue": false, "description": null,
    "created_at": 1730000000, "updated_at": null },
  "links": { "self": "...", "web": "..." } } }
```

`is_overdue` is derived (R5). Writes take `maintenance_type`, `title`, `performed_by`,
`performed_on`, `cost`, `currency`, `condition_before_id`, `condition_after_id`, `next_due_on` and
`description`; `maintenance_type`, `title` and `performed_on` are required on create. `maintenance_type` avoids colliding with the resource's own `type`.

## Assistant

New tools `list-possession-maintenance-records`, `get-possession-maintenance-record`,
`create-possession-maintenance-record`, `update-possession-maintenance-record` and
`delete-possession-maintenance-record`, each calling the endpoint's action, as the insurance tools do.
