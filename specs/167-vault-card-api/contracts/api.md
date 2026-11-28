# Contract: vault card values

## Vault resource, new `attributes`

On `GET /vaults`, `GET /vaults/{vault}`, `POST /vaults` and `PUT /vaults/{vault}`.

```json
"last_visited_at": 1764240000,
"faces": [
  {
    "id": "9b1e6c1e-6f0a-4a43-9a52-0c6f1e3b7d21",
    "name": "Pam Beesly",
    "avatar_url": "https://example.com/api/v1/vaults/dunder-mifflin/contacts/9b1e6c1e-6f0a-4a43-9a52-0c6f1e3b7d21/avatar"
  },
  { "id": "1f0c…", "name": "Dwight Schrute", "avatar_url": null }
],
"upcoming_reminder_count": 2
```

| Field | Type | Null | Rule |
| --- | --- | --- | --- |
| `last_visited_at` | integer | yes | Seconds since 1970; null when never opened |
| `faces` | array | no | 0 to 4 listed contacts, newest first; `[]` when none |
| `faces[].id` | uuid | no | The contact's identifier |
| `faces[].name` | string | no | The displayed name |
| `faces[].avatar_url` | string | yes | The avatar address below; null without an avatar |
| `upcoming_reminder_count` | integer | no | Awaiting an answer until the reader's month ends, overdue included |

## `GET /vaults` query

| Parameter | Values | Default |
| --- | --- | --- |
| `sort` | `recent` | Position. `recent`: last visit first, never opened last, then position |

Any other value answers 422 with an error on `sort`.

## `GET /vaults/{vault}/contacts/{contact}/avatar`

| Case | Answer |
| --- | --- |
| Contact with an avatar | 200, the thumbnail bytes, its `Content-Type`, `Cache-Control: private, max-age=3600` (the address outlives a replaced picture) |
| No avatar, file missing, other vault, other account | 404 |
| No token | 401 |

Also answers `QUERY`, as every read route does.
