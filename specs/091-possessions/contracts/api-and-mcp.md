# Contract: API Endpoints and MCP Tools

## API

`routes/api.php`, `v1` group, next to `contacts`. Prefix `api/v1/vaults/{vault}/possessions`, written
`…`; `{possession}` constrained to a uuid. Controllers in `App\Http\Controllers\Api\Possessions`.

| Verb | URI | Name | Answer |
| --- | --- | --- | --- |
| GET | `…` | `api.v1.possessions` | 200, `data` + `meta`; `type`, `status`, `featured`, `sort`, `per_page` |
| GET | `…/{possession}` | `api.v1.possessions.show` | 200 |
| POST | `…` | `api.v1.possessions.create` | 201; `name`, `type_id`, `description?`, `location_id?`, `identifier?`, `status?`, `quantity?`, `is_featured?` |
| PUT | `…/{possession}` | `api.v1.possessions.update` | 200; the same without `type_id`, replacing |
| DELETE | `…/{possession}` | `api.v1.possessions.destroy` | 204 |
| PUT | `…/{possession}/position` | `api.v1.possessions.position` | 200; `position` |
| PUT | `…/{possession}/values` | `api.v1.possessions.values` | 200; `values: {field_id: value\|null}` |

Answer: `type: possession`, `id`, `attributes {name, description, identifier, status, quantity,
is_featured, position, possession_type {id, name, colour}, location {id, name}|null, values:
[{field_id, name, kind, group, value}]}` in the type's order, `value` typed by kind (number, boolean,
integer rating, `YYYY-MM-DD`, string), `created_at`, `updated_at`; `links.self`.

| Refusal | Answer |
| --- | --- |
| Vault or possession outside the account | 404 |
| Type, location or field of another account, or a field of another type | 422 on that attribute |
| A value that does not fit its field | 422 on `values.{field_id}` |
| `position` on a possession not featured | 422 on `position` |

## MCP

`app/Mcp/Tools`, registered in `MonicaServer`, each `MirrorsAnApiCapability`, in this order:
`list-possessions`, `get-possession`, `create-possession`, `update-possession`, `delete-possession`,
`set-possession-position`, `set-possession-values`. `list` and `get` are `#[IsReadOnly]`, `delete`
`#[IsDestructive]`. Update keeps what is left out and clears on null (087 R6). Unknown record: "That
possession does not exist." `delete-possession-type` now answers with how many possessions went.
