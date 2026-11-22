# Contract: countries in the API and the MCP server

## `GET /api/v1/countries` (route `api.v1.countries`, also `QUERY`)

Every country of the standard, in the language of the request (`SetApiLocale`), in the order the
interface offers them: the account's active favourites carrying a code first, in their position, then
every country sorted by that language. A favourite also appears in the second part (FR-006), so a
client deduplicates on `code` if it wants one entry per country.

```json
{
  "data": [
    { "code": "US", "name": "United States", "favourite_id": 12 },
    { "code": "CA", "name": "Canada", "favourite_id": 13 },
    { "code": "AF", "name": "Afghanistan", "favourite_id": null },
    { "code": "US", "name": "United States", "favourite_id": 12 }
  ]
}
```

Not paged: 256 entries plus the favourites. A favourite typed as free text has no code and is not
listed here; it is still listed by `GET /api/v1/value-lists`.

Declared next to `value-lists`, inside the `auth:sanctum` group, with no scope: `ApiKeyScopeEnum`
guards only sensitive fields and administration (checked on 2026-11-21). Answers 401 without a key.

## Saving an address

Unchanged endpoint, `PUT /api/v1/vaults/{vault}/contacts/{contact}/sections/{section}`
(`api.v1.contacts.sections.update`). The `country` part of an address takes:

| Value | Meaning |
| --- | --- |
| `""` or null | no country |
| `"keep"` | keep the stored country, text and code |
| an integer | the id of an active favourite, as today |
| two letters | a country of the standard; saved as the favourite carrying it when there is one |

Anything else answers 422 on `fields.{id}.country` with the translated `validation.exists` message.

An address may also carry `country_name`, at most 255 characters. When it is not empty it wins over
`country`: it is recorded as written, without a country code (FR-014).

`ContactResource` returns no field value but the birthday, so nothing in the contact answer changes.
The readers that do show an address (the contact markdown endpoint and its MCP tool, the shared page)
go through `FieldValueFormatter`, so a country recorded with its code reads in the request's locale
("Japon" in French), as `LocaleResolution::forApi()` resolves it.

## MCP

- `list-countries` mirrors `api.v1.countries` through `answerThroughApi()`. No parameter. Read only.
- `update-contact-section` is new: no tool mirrored `api.v1.contacts.sections.update` before
  (searched `app/Mcp` on 2026-11-21), so an assistant could not save an address at all. Parameters
  `vault`, `contact`, `section` and `fields` as the endpoint takes them. Not read only.
