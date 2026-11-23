# Contracts: making a contact partial or full

## Web

`PUT vaults/{vault}/contacts/{contact}/partial`, named `contacts.partial.update`, handled by
`App\Http\Controllers\App\Vaults\ContactPartialController::update`. Replaces `contacts.promotion.create`.

| Field | Rule |
| --- | --- |
| `partial` | required, boolean |

| Case | Answer |
| --- | --- |
| Made partial | Redirect to `contacts.show`, flash `status` ":name is a partial contact." |
| Made full | Redirect to `contacts.show`, flash `status` ":name is a full contact." |
| Policy or state refusal | `back()` with the error under `partial` |
| Contact reached through another vault of the account | 404 |
| `partial` missing or not a boolean | validation error under `partial` |

## API

`PUT /api/v1/vaults/{vault}/contacts/{contact}/partial`, named `api.v1.contacts.partial.update`,
handled by `App\Http\Controllers\Api\ContactPartialController::update`.

Request:

```json
{ "partial": true }
```

| Case | Answer |
| --- | --- |
| Done (either direction) | 200, the `ContactResource` of the contact, `is_partial` reporting the new state |
| Refusal (account holder, already in that state, archived, plan ceiling) | 422, `message` carrying the refusal's wording |
| Vault or contact not found in the account | 404 |
| `partial` missing or not a boolean | 422 with a validation error on `partial` |

Documented in `resources/docs/api/06-contacts.php`, with an entry in `resources/docs/api-changelog/`.

The merge endpoint (`api.v1.contacts.merge.create`) gains one refusal: 422 with the plan's wording
when merging two partial contacts would pass the ceiling.

## MCP

`App\Mcp\Tools\SetContactPartial`, mirroring `api.v1.contacts.partial.update`, registered in
`MonicaServer` beside `SetContactFavourite`.

| Parameter | Schema |
| --- | --- |
| `vault` | string, required |
| `contact` | string, required |
| `partial` | boolean, required: "True to make them a partial contact, false to make them a full one." |

Annotations as `SetContactFavourite`: not read only, destructive, not open world.
