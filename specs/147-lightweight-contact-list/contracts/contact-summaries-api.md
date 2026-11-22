# Contract: contact summaries in the API and the MCP server

## `GET /api/v1/vaults/{vault}/contact-summaries` (route `api.v1.contactSummaries`, also `QUERY`)

| Parameter | Values | Default |
| --- | --- | --- |
| `filter` | `partial`, `full` | both |
| `archived` | `excluded`, `included` | `excluded` |
| `page` | integer, 1 or more | 1 |
| `per_page` | integer, 1 to 500 | 100 |

Ordered by name, as the contact list of the vault orders it by default (family name first for a western name). Deleted contacts are never
listed.

```json
{
  "data": [
    { "id": "9d1e…", "name": "Pam Beesly", "is_partial": false, "is_archived": false },
    { "id": "9d1d…", "name": "Jim Halpert", "is_partial": false, "is_archived": false },
    { "id": "9d1c…", "name": "Astrid Levinson", "is_partial": true, "is_archived": false }
  ],
  "meta": { "page": 1, "per_page": 100, "total": 3 }
}
```

`id` is the contact's uuid, the id every other contact endpoint and tool accepts. `name` is the
contact's `name` accessor, "Unnamed contact" (translated) when it has none.

| Request | Answer |
| --- | --- |
| No key | 401 |
| A vault of another account, or no such vault | 404 |
| `filter` or `archived` outside its values | 422 on that parameter, naming the accepted values |
| `per_page` above 500 | 500 entries, as `per_page` above the maximum does on the contact list |

## MCP tool `list-contact-summaries`

Mirrors `api.v1.contactSummaries` through `answerThroughApi()`. Schema: `vault` (required), `filter`,
`archived`, `page`, `per_page`, each described with the values above. Read only, not destructive,
not open world, like `list-contacts`.
