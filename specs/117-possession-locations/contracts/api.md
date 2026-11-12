# Contract: API and assistant

Prefix `v1/vaults/{vault}/possessions/{possession}`. Bearer token; another vault, account or
possession answers 404, as does a location of another account. Validation errors answer 422.

| Method | Path | Answer |
| --- | --- | --- |
| GET | `location-stays` | 200, `data` list oldest first (R6), not paginated |
| POST | `location-stays` | 201, the new open stay; a move (R3) |
| GET | `location-stays/{stay}` | 200 |
| PUT | `location-stays/{stay}` | 200; what is left out keeps its value, as the transaction PUT |
| DELETE | `location-stays/{stay}` | 204; deleting the open stay clears the current location |

POST takes `location_id` (uuid, required), `moved_on` (`Y-m-d`, required), `reason`, `note`.
PUT takes `location_id`, `arrived_on`, `left_on` (refused on the open stay), `reason`, `note`.

## Stay

```json
{ "data": {
  "type": "possession_location_stay", "id": "uuid",
  "attributes": {
    "location_id": "uuid", "location_name": "Loft",
    "arrived_on": "2019-11-08", "left_on": null, "is_current": true,
    "reason": "After the harvest", "note": null, "created_at": 1773446400 },
  "links": { "self": "...", "web": "..." } } }
```

`location_name` is the location's own name; its path comes from `GET v1/locations`, which keeps one
stay from loading every location of the account.

## Locations

`DELETE v1/locations/{location}` and `delete-location` answer 422 with the policy's wording when a
stay is there (R7). Nothing else about them changes.

## Assistant

New tools `list-possession-location-stays`, `get-possession-location-stay`, `move-possession`,
`update-possession-location-stay` and `delete-possession-location-stay`, each calling the endpoint's
action, as the provenance tools do.
