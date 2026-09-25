# API contract: entities

Both routes sit in the authenticated API group, beside the entity type routes. `{vault}` is
addressed the way the contact routes address it; `{entityType}` is the type's UUID.

## `GET vaults/{vault}/entity-types/{entityType}/entities` (`entities`)

- Query: `page`, `per_page`, capped by `monica.api_maximum_items_per_page`.
- 200: `{ data: Entity[], meta: { page, per_page, total } }`, ordered by `sort_name`, archived
  records included, nothing from another vault.
- 404: vault or type of another account, or a type hidden in this vault.

## `POST vaults/{vault}/entity-types/{entityType}/entities` (`entities.create`)

| Key | Rule |
| --- | --- |
| `name` | required, string, max 255 |
| `fields` | object keyed by field id, each value validated by `FieldValueRules` |
| `labels`, `label_wordings` | objects keyed by field id |
| `year_is_unknown`, `month_is_unknown` | objects keyed by field id, booleans |

- Files are sent as multipart under `fields[<id>]`.
- 201: the created `Entity`.
- 422: validation, a refusal of `SetFieldValues` (on `fields`), or `addEntity` denied (on `name`).
- 404: as for the list.

## Entity

```json
{
  "type": "entity",
  "id": "uuid",
  "attributes": {
    "name": "Mose’s goat",
    "entity_type_id": "uuid",
    "initials": "MG",
    "avatar_colour": "#22c55e",
    "avatar": null,
    "is_archived": false,
    "structure_version_id": 12,
    "template_name": "Basic animal",
    "created_at": 1790366400,
    "updated_at": 1790366400
  }
}
```

The shape follows `ContactResource`. There is no `links.self`, since no show endpoint exists yet.
