# Contract: API, MCP and the web form

## Contact object

`attributes` gains, beside the existing fields (kept, now read from the projection):

```json
"formatted_name": "김민지",
"name_representations": [
  { "id": "uuid", "kind": "written", "language": "ko", "script": "Hang", "phonetic_system": null,
    "formatted_name": "김민지", "prefix": null, "given_name": "민지", "additional_name": null,
    "family_name": "김", "suffix": null, "name_order": "family_first", "position": 1, "is_display": true }
]
```

Loaded on show and on index (one query per page). Ordered by `position`.

## `GET api/v1/vaults/{vault}/contacts/{contact}/name-representations`

Name `api.v1.contacts.nameRepresentations`. Returns `{ "data": [representation, …] }`.

## `PUT api/v1/vaults/{vault}/contacts/{contact}/name-representations`

Name `api.v1.contacts.nameRepresentations.update`. Replaces the set; a row not sent is deleted.

| Body | Rule |
| --- | --- |
| `name_representations` | `present`, `array`, max 20 |
| `.*.id` | nullable uuid of a representation of this contact, distinct; absent creates |
| `.*.kind` | required, `ContactNameKindEnum` |
| `.*.language` | nullable, BCP 47 pattern (research R8) |
| `.*.script` | nullable, ISO 15924 pattern |
| `.*.phonetic_system` | required when kind is phonetic, prohibited otherwise, max 32 |
| `.*.formatted_name`, `.*.prefix`, `.*.given_name`, `.*.additional_name`, `.*.family_name`, `.*.suffix` | nullable, max 255 (prefix and suffix max 50); at least one per row; `formatted_name` prohibited when kind is phonetic |
| `.*.name_order` | nullable, `NameOrderEnum` |
| `display` | required when the list is not empty: the index in the list of the displayed row, never a phonetic one |
| `nickname`, `maiden_name` | `present`, nullable, max 255, as on `PUT …/name` |

Returns the contact object. 404 for another account's contact or the wrong vault; 422 for an `id`
that is not one of this contact's representations. Logged as `contact_name_updated`.

`PUT …/contacts/{contact}/name` keeps its body and now rewrites the display representation's parts,
nickname and maiden name, creating the representation when there is none.

## MCP

| Tool | Mirrors |
| --- | --- |
| `ListContactNameRepresentations` | `api.v1.contacts.nameRepresentations` |
| `UpdateContactNameRepresentations` | `api.v1.contacts.nameRepresentations.update` |

`GetContact` returns the new attributes; both tools use `MirrorsAnApiCapability` and the action.

## Web

`PUT contacts.name.update` (edit) and `POST contacts.create` (new) post `names[i][…]` with the
API row keys, `display`, `nickname`, `maiden_name`. Validation is inline and identical to the API
table. A refusal returns to the form with the error on `names.{i}.{key}`.

Docs: `resources/docs/api/06-contacts.php` gains the two endpoints and the new attributes.
