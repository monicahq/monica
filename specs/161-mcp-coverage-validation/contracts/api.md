# Contracts

## Value list endpoints (new), under `api/v1`

| Verb and path | Route name | Body | Answer | Refusal |
| --- | --- | --- | --- | --- |
| `POST value-lists` | `valueLists.create` | `name` (required, ≤255) | 201, list | 422 validation |
| `PUT value-lists/{valueList}` | `valueLists.update` | `name` (required, ≤255) | 200, list | 404 not shared or other account |
| `DELETE value-lists/{valueList}` | `valueLists.destroy` | | 204 | 422 `valueList`: "This list stays." |
| `POST value-lists/{valueList}/items` | `valueLists.items.create` | `label` or `country_code` (one required) | 201, item | 422 `valueList`: "This value list does not take choices of its own." |
| `PUT value-lists/{valueList}/items/{valueListItem}` | `valueLists.items.update` | `label` or `country_code` | 200, item | 422 validation |
| `DELETE value-lists/{valueList}/items/{valueListItem}` | `valueLists.items.destroy` | | 204 | 422 `valueListItem`: "The value is in use and stays." |
| `PUT value-lists/{valueList}/items/{valueListItem}/activation` | `valueLists.items.activation.update` | `is_active` (required, boolean) | 200, item | |
| `PUT value-lists/{valueList}/items/{valueListItem}/position` | `valueLists.items.position.update` | `position` (required, integer ≥1) | 200, item | |

`{valueList}` and `{valueListItem}` are integer ids, as `GET value-lists` gives them. An item of
another list, a list that is not shared, and a record of another account all answer 404.
`country_code` is accepted only by the countries list and is two letters from `CountryCodes::known()`,
as on the settings screen.

**List**: `{"type": "value_list", "id": 4, "attributes": {"name": "Paper suppliers", "items": [item…]}}`

**Item**: `{"type": "value_list_item", "id": 31, "attributes": {"label": "Staples", "country_code": null, "is_active": true, "position": 1}}`

`GET value-lists` keeps its shape; its items gain `is_active` and `position`.

## MCP tools (new)

| Tool | Mirrors |
| --- | --- |
| `delete-contact` | `api.v1.contacts.destroy` |
| `get-contact-list-columns` | `api.v1.contactListColumns.show` |
| `set-contact-list-columns` | `api.v1.contactListColumns.update` |
| `create-value-list`, `update-value-list`, `delete-value-list` | the three list routes |
| `create-value-list-item`, `update-value-list-item`, `delete-value-list-item` | the three item routes |
| `set-value-list-item-activation`, `set-value-list-item-position` | the two item state routes |

Delete tools answer with their own sentence. The `delete-contact` description says the assistant
cannot bring the contact back.

## A repeating row in `fields` (changed)

```json
{"fields": {"12": [{"id": 88, "values": {"13": "+1 570 555 0100"}, "labels": {"13": 4}}]}}
```

Allowed row keys: `id` (an existing row; left out for a new one), `values`, `labels`,
`label_wordings`, `unknown_year` (child id → true for a date whose year is not known). Any other key, a key of `values` that is not a child of the field,
and a top level key of `fields` that is not a field of the section are refused with a 422.
