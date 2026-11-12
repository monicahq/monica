# 076. PossessionLocationStay

**Status:** in progress (`app/Models/PossessionLocationStay.php`)
**Spec:** 3.2, specs/117-possession-locations

## What it is

One period one [Possession](062-possession-in-progress.md) spent at one [Location](058-location-in-progress.md) of the account. The stay with no day it left is the open one: where the possession is now. Together the stays say where it has been since it was recorded.

## Fields

```
id                          int                             primary key
uuid                        uuid                            route key, and identifier for the API
account_id                  int                             account of the possession
vault_id                    int                             vault of the possession
possession_id               int                             possession it belongs to
location_id                 int                             a location of the same account
arrived_on                  date                            optional; null when the day is unknown
left_on                     date                            null on the open stay; not before arrived_on
reason                      string (255)                    why it went there, optional
note                        text                            optional
```

## Rules

- A possession has at most one open stay, and its `current_location_id` is always that stay's location, or null without one.
- Moving closes the open stay on the day given and opens the next on the same day. It refuses the location of the open stay and a day before the open stay began.
- Correcting a stay changes that stay only. A past stay keeps a day it left; the open stay never gets one by correction.
- Stays are ordered by arrival, those with an unknown arrival first; on the same day, the earliest recorded comes first.
- Adding a possession with a location opens its first stay that day; changing the location on its edit form is a move dated that day, and clearing it closes the open stay.
- Stays are reached from the possession page, through the API (`/api/v1/vaults/{vault}/possessions/{possession}/location-stays`) and through the MCP server. Moving, correcting and deleting are written to the changelog.
- A location any stay refers to cannot be deleted. Deleting the possession deletes its stays.
