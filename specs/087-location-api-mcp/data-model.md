# Data Model: Location API and MCP Tools

No table, column or model changes. `locations` and `Location` stay as specification 086 left them
(see `specs/086-location-management/data-model.md`).

## `App\Helpers\LocationTree` (new, research R2)

Built from one query of an account's locations.

| Method | Returns |
| --- | --- |
| `forAccount(int $accountId): self` | the tree of that account |
| `ordered(): Collection<int, Location>` | every location, each parent before what it holds, sorted by `LocaleCollation` at each level |
| `childrenOf(?Location): Collection<int, Location>` | the locations directly inside one, or at the top level, sorted |
| `path(Location): string` | names from the top level down, joined by " › " |
| `addressHolder(Location): ?Location` | the location itself if it has an address, else its nearest ancestor with one |
| `childCount(Location): int` | locations directly inside |
| `descendantIds(Location): list<int>` | every location inside, at any depth |

`LocationsViewModel` keeps its public methods and reads from it.

## `App\Http\Resources\LocationResource` (new, research R4)

Takes a `Location` and the `LocationTree` it belongs to.

```json
{
  "type": "location",
  "id": "uuid",
  "attributes": {
    "parent_id": "uuid|null",
    "name": "Shelf 3",
    "emoji": "📦|null",
    "address": "string|null",
    "path": "Dunder Mifflin Scranton › Warehouse › Shelf 3",
    "effective_address": {
      "address": "1725 Slough Avenue\nScranton, PA",
      "location_id": "uuid",
      "location_name": "Dunder Mifflin Scranton"
    },
    "location_count": 0,
    "created_at": 1761350400,
    "updated_at": null
  },
  "links": {
    "self": "https://…/api/v1/locations/{uuid}",
    "web": "https://…/settings/account/locations/{uuid}/edit"
  }
}
```

`effective_address` is null when neither the location nor any ancestor has an address.

## Rules

Unchanged and held by the actions of 086: parent in the same account, never itself or one it holds,
a non empty name after sanitising, one emoji or none, and no deletion while it holds another.
