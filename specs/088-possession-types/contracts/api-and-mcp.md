# Contract: API Endpoints and MCP Tools

## API

`routes/api.php`, `v1` group behind `auth:sanctum`, next to `locations`. Prefix `api/v1/possession-types`,
written `…`. Parameters are uuids; controllers in `App\Http\Controllers\Api\PossessionTypes`, each
looking records up through `$user->account`, as `Api\LocationController`.

| Verb | URI | Name | Answer |
| --- | --- | --- | --- |
| GET | `…` | `api.v1.possessionTypes` | 200, `data` + `meta`, sorted by name |
| GET | `…/{type}` | `api.v1.possessionTypes.show` | 200, one type with groups and fields |
| POST | `…` | `api.v1.possessionTypes.create` | 201, body `name`, `colour` |
| PUT | `…/{type}` | `api.v1.possessionTypes.update` | 200 |
| DELETE | `…/{type}` | `api.v1.possessionTypes.destroy` | 204 |
| GET | `…/{type}/export` | `api.v1.possessionTypes.export` | 200, the file of research R9 |
| POST | `…/import` | `api.v1.possessionTypes.import` | 201, the new type; body `file` (string) |
| POST | `…/{type}/groups` | `api.v1.possessionFieldGroups.create` | 201, body `name` |
| PUT | `…/{type}/groups/{group}` | `api.v1.possessionFieldGroups.update` | 200 |
| DELETE | `…/{type}/groups/{group}` | `api.v1.possessionFieldGroups.destroy` | 204 |
| PUT | `…/{type}/groups/{group}/position` | `api.v1.possessionFieldGroups.position` | 200, body `position` |
| POST | `…/{type}/fields` | `api.v1.possessionFields.create` | 201, body `name`, `kind`, `options?`, `group_id?` |
| PUT | `…/{type}/fields/{field}` | `api.v1.possessionFields.update` | 200, body `name`, `kind`, `options?` |
| DELETE | `…/{type}/fields/{field}` | `api.v1.possessionFields.destroy` | 204 |
| PUT | `…/{type}/fields/{field}/position` | `api.v1.possessionFields.position` | 200, body `position` |

Over the API a name is required on creation (no default name). `PUT` replaces: absent `options`
clears them.

Answer of a type: `type: possession_type`, `id`, `attributes {name, colour, field_count, groups:
[{id, name, position, fields}], fields}`, each field `{id, name, kind, options, position, group_id}`,
`created_at`, `updated_at`; `links.self`, `links.web` (`settings.possessionTypes.edit`).

| Refusal | Answer |
| --- | --- |
| Type, group or field of another account, or a group or field of another type | 404 |
| Invalid input | 422 on the field, the screen's wording |
| Unreadable import | 422 on `file`, every problem listed |

## MCP

`app/Mcp/Tools`, registered in `MonicaServer`, each `MirrorsAnApiCapability` naming the route above
in the same order: `list-possession-types`, `get-possession-type`, `create-possession-type`,
`update-possession-type`, `delete-possession-type`, `export-possession-type`,
`import-possession-type`, `create-possession-field-group`, `update-possession-field-group`,
`delete-possession-field-group`, `set-possession-field-group-position`, `create-possession-field`,
`update-possession-field`, `delete-possession-field`, `set-possession-field-position`.

`list`, `get` and `export` are `#[IsReadOnly]`; the three `delete-` tools `#[IsDestructive]`. Update
tools keep what is left out and clear on null (087 R6). An unknown record answers "That possession
type does not exist.", "That group does not exist.", "That field does not exist."
