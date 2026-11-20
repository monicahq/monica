# Contract: contact resource in the API

Applies to every response built from `ContactResource`: `GET /api/v1/vaults/{vault}/contacts`,
`GET /api/v1/vaults/{vault}/contacts/{contact}`, and the create and update answers.

Two attributes are added under `attributes`, after `is_me`:

```json
{
  "age": 47,
  "next_birthday_on": "2026-11-26"
}
```

| Attribute | Type | Value |
| --- | --- | --- |
| `age` | integer or null | Whole years on the token owner’s current day, in their time zone |
| `next_birthday_on` | string `Y-m-d` or null | Next anniversary on or after that day; today’s date on the birthday itself |

Both are null when the contact has no birthday. `age` is null when the year or the month is unknown;
`next_birthday_on` is null when the month is unknown. Both are null when the birthday field is
sensitive and the token lacks the `sensitive-fields` ability.

Additive: no existing attribute changes, so the API version does not move. The docs portal page for
contacts documents both, through the `api-docs-writer` skill. The MCP tools reading a contact pass
them on unchanged.
