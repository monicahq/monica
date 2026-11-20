# API contract: entities

Base `/api/v1`. Bearer token. `{vault}` is the vault uuid or slug, `{entity}` the entity uuid.

| Method | Path | Answer |
| --- | --- | --- |
| GET, QUERY | `vaults/{vault}/entity-types/{entityType}/entities` | 200 `{data: [entity], meta}` (exists, gains `fields`) |
| POST | `vaults/{vault}/entity-types/{entityType}/entities` | 201 entity (exists) |
| GET, QUERY | `vaults/{vault}/entities/{entity}` | 200 entity; 404 other account or vault |
| PUT | `vaults/{vault}/entities/{entity}` | 200 entity; 422 on validation; 404 as above |

## Entity

```json
{
  "type": "entity",
  "id": "uuid",
  "attributes": {
    "name": "Biscuit",
    "entity_type_id": "uuid",
    "...": "existing attributes unchanged",
    "fields": [
      {"field_id": "12", "key": "breed", "label": "Breed", "type": "text", "row": null, "value": "Beagle", "is_hidden": false}
    ]
  },
  "links": {"self": "…/api/v1/vaults/{vault}/entities/{entity}", "web": "…/vaults/{vault}/entities/{type}/{entity}"}
}
```

`value` is the formatted value; `null` with `is_hidden: true` for a sensitive field without the
sensitive-fields scope.

## PUT body

| Key | Rule |
| --- | --- |
| `name` | optional; when sent, required string, max 255 |
| `fields` | optional object keyed by field id; a field left out is kept, `null` clears it |
| `labels`, `label_wordings`, `year_is_unknown`, `month_is_unknown` | optional, as on create |

Validation per field is `FieldValueRules::for()` over the record's own version.

## MCP tools

`list-entities`, `create-entity`, `get-entity`, `update-entity`: one per row above, answering through it.
