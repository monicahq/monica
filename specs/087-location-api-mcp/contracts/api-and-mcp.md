# Contract: API Endpoints and MCP Tools

## API

In `routes/api.php`, inside the `v1` group behind `auth:sanctum`, next to `relationship-types`.
`{location}` is a uuid (`where('location', '[0-9a-fA-F-]{36}')`). Controller
`App\Http\Controllers\Api\LocationController`.

| Verb | URI | Action | Name | Answer |
| --- | --- | --- | --- | --- |
| GET | `api/v1/locations` | `index` | `api.v1.locations` | 200, `data` + `meta {page, per_page, total}` |
| GET | `api/v1/locations/{location}` | `show` | `api.v1.locations.show` | 200, one location |
| POST | `api/v1/locations` | `create` | `api.v1.locations.create` | 201, the new location |
| PUT | `api/v1/locations/{location}` | `update` | `api.v1.locations.update` | 200, the location |
| DELETE | `api/v1/locations/{location}` | `destroy` | `api.v1.locations.destroy` | 204 |

### Body of `POST` and `PUT`

| Field | Rule | Absent on `PUT` |
| --- | --- | --- |
| `name` | `required`, string, max 255 | refused |
| `parent_id` | nullable string, a uuid of the key's account | top level |
| `emoji` | nullable string, max 32, one emoji | no emoji |
| `address` | nullable string, max 2000 | no address |

### Refusals

| Case | Answer |
| --- | --- |
| Location of another account, or unknown | 404 |
| Validation, or a name of nothing but markup | 422, error on the field |
| Parent is the location or inside it | 422 on `parent_id`, "A location cannot sit inside itself, or inside a location it holds." |
| Delete while holding others | 422 on `location`, ":count location is inside it. Move it first." (counted) |
| No key, or a browser session | 401, as every endpoint |

## MCP

In `app/Mcp/Tools`, registered in `MonicaServer`. Each mirrors the route named.

| Tool | Mirrors | Annotation | Input | Answer |
| --- | --- | --- | --- | --- |
| `list-locations` | `api.v1.locations` | read only | none | every location, tree order |
| `get-location` | `api.v1.locations.show` | read only | `location` | one location |
| `create-location` | `api.v1.locations.create` | | `name`, `parent_id?`, `emoji?`, `address?` | the new location |
| `update-location` | `api.v1.locations.update` | | `location`, then any of `name`, `parent_id`, `emoji`, `address`; left out keeps its value, null clears it | the location |
| `delete-location` | `api.v1.locations.destroy` | destructive | `location` | "The location is deleted." |

A refusal is a tool error carrying the API's wording. An unknown location answers "That location does
not exist."
