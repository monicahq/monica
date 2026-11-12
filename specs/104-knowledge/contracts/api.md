# Contract: API and assistant

Prefix `v1/vaults/{vault}/knowledge`. Bearer token; another vault's or account's folder, note, list,
column or item answers 404, so does a folder, column or item of another list or vault named in a
body. Validation errors and policy refusals answer 422.

| Method | Path | Answer |
| --- | --- | --- |
| GET | `folders` | 200, every folder by position, each with its notes and lists (uuid, kind, title) |
| POST, PUT | `folders`, `folders/{folder}` | 201, 200; `name`, and `position` on PUT |
| DELETE | `folders/{folder}` | 204; `move_to` a folder uuid, else the files are deleted; the last folder 422 |
| GET, POST | `notes` | 200 (`folder` filter, titles only), 201; `folder_id` optional on POST (R2) |
| GET, PUT, DELETE | `notes/{note}` | 200 with `body` and `headings`, 200, 204 |
| GET, POST | `lists` | 200 (`folder` filter), 201 with the two default columns |
| GET, PUT, DELETE | `lists/{list}` | 200 with columns and items, 200, 204 |
| POST, PUT, DELETE | `lists/{list}/columns[/{column}]` | 201, 200 (`name`, `position`), 204; the last column 422 |
| POST, PUT, DELETE | `lists/{list}/items[/{item}]` | 201, 200, 204; `cells` keyed by column uuid |
| GET | `search?query=` | 200, matching notes and lists, at least two letters or digits (R8) |

PUT leaves out what is not sent. A note's text travels raw as `body`, never rendered.

## List

```json
{ "data": { "type": "knowledge_list", "id": "uuid",
  "attributes": { "title": "Favourite restaurants", "description": "Places worth going back to.",
    "folder": { "id": "uuid", "name": "Entertainment" },
    "columns": [ { "id": "uuid", "name": "Name", "position": 1 }, { "id": "uuid", "name": "City", "position": 2 } ],
    "items": [ { "id": "uuid", "position": 1, "cells": { "<column uuid>": "Cooper's Seafood", "<column uuid>": "Scranton" } } ],
    "created_at": 1730000000, "updated_at": null },
  "links": { "self": "...", "web": "..." } } }
```

A note reads `type: knowledge_note` with `title`, `body`, `headings` (text, level, anchor), `folder`
and the timestamps; a folder `type: knowledge_folder` with `name`, `position`, `note_count`, `list_count`.

## Assistant

Tools, each calling the endpoint's action: `list-knowledge`, `search-knowledge`;
`create-`, `update-`, `delete-knowledge-folder`; `get-`, `create-`, `update-`, `delete-knowledge-note`;
`get-`, `create-`, `update-`, `delete-knowledge-list`; `create-`, `update-`, `delete-knowledge-list-column`;
`create-`, `update-`, `delete-knowledge-list-item`. Registered on `MonicaServer`.
