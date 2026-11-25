# API and MCP contract

Prefix `/api/vaults/{vault}/record-kinds/{recordKind}`, where `recordKind` is `contacts` or an entity type uuid (research R1). Another account's vault, kind or template answers 404. Refusals answer 422 with the same sentence as the screens.

| Method | Path | Answers | MCP tool |
| --- | --- | --- | --- |
| GET | `statistics` | 200 overview and templates | `get-record-statistics` (read only) |
| GET | `statistics/fields?template={uuid}&version={n}&order=lowest\|form` | 200 field completion | `get-field-completion` (read only) |
| GET | `template-change-preview?from={uuid}&version={n}&to={uuid}` | 200 preview | `preview-template-change` (read only) |
| POST | `template-changes` `{from, version?, to}` | 202 the change, `running` | `change-template-of-records` |
| GET | `/api/vaults/{vault}/template-changes/{uuid}` | 200 the change | `get-template-change` (read only) |

`to` names a template; the batch goes to its published version. For "Update to vN", `to` equals `from`.

Every answer is `{data: {type, id?, attributes, links?}}`, as the rest of the API. Only `attributes` is shown below.

## `statistics`

```json
{ "kind": {"id": "uuid or contacts", "singular": "animal", "plural": "animals"},
  "overview": {"records": 18, "templates_in_use": 2, "on_older_versions": 3, "archived": 2, "partial": null},
  "templates": [{"id": "uuid", "name": "Pet", "is_default": true, "is_active": true,
    "records": 14, "share": 78, "published_version": 3,
    "versions": [{"number": 3, "records": 11, "is_published": true}, {"number": 2, "records": 3, "is_published": false}]}] }
```

`partial` is a number for People and null otherwise.

## `statistics/fields`

```json
{ "template": "uuid", "version": null, "records": 11,
  "sections": [{"name": "Identification", "fields": [{"key": "microchip", "label": "Microchip number",
    "filled": 5, "offered_to": 11, "percentage": 45, "available_from": 3}]}],
  "hidden_count": 0 }
```

`percentage` is null when `offered_to` is 0. `available_from` is null when every chosen version offers the field.

## Preview

```json
{ "from": "uuid", "to": "uuid", "to_version": 3,
  "counts": {"ready": 4, "unchanged": 2, "set_aside": 2, "restored": 0, "reminders_paused": 1, "reminders_resumed": 0},
  "set_aside": [{"label": "Stable name", "section": "Stable", "records": 2}],
  "restored": [], "reminders_paused": [{"name": "Farrier visit", "records": 1}], "reminders_resumed": [],
  "blocked": ["uuid"], "hidden_count": 0 }
```

`blocked` lists the records another running change holds, which is the only reason a record is left out of a batch before it starts.

## Change

```json
{ "status": "running", "record_kind": "uuid or contacts", "from": "uuid", "to": "uuid", "to_version": 3,
  "counts": {"total": 4, "done": 4, "moved": 3, "skipped": 1, "failed": 0},
  "not_moved": [{"id": "uuid", "name": "Bijou", "outcome": "skipped", "reason": "This animal’s template changed after the preview."}],
  "created_at": 1764072000, "ended_at": null }
```

`status` is `running` while `ended_at` is null and `finished` after; it is read from `ended_at`, not stored. `moved`, `skipped` and `failed` are written when the change ends and read 0 before; `done` counts progress. `not_moved` lists the first 100.

The tool description of `change-template-of-records` tells the assistant to call `preview-template-change` and show its answer to the person first.
