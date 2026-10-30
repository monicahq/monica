# API contract: contact categories

Every route sits in the existing authenticated `api` group, answers 404 for a vault or a contact
outside the account, and is documented in `/docs/api` by the `api-docs-writer` skill.

## Save a section of a contact

`PUT api/vaults/{vault}/contacts/{contact}/sections/{section}`, the section by its id.

The body replaces the whole section, as the web form does: a field left out is emptied, except a
sensitive field the key cannot reach. It takes `fields`, `labels`, `label_wordings`,
`year_is_unknown`, `month_is_unknown` (no file), plus:

| Key | Type | Meaning |
| --- | --- | --- |
| `new_choices.{fieldId}` | array of strings, each up to 255 | Wordings to add to that multiselect field (R1, R2) |

- 200 with `ContactResource`.
- 422 on `fields` when a wording matches a stopped category, or when the list refuses new values,
  as every other refusal of a field. Nothing is written in either case. A wording over 255
  characters answers 422 on `new_choices.{fieldId}.{n}`.
- 404 when the section does not belong to the contact's structure version.

## List the contacts of a vault

`GET api/vaults/{vault}/contacts`: the existing parameters, plus `categories[]`, the ids of value list
items of the account's "Contact categories" list. A contact appears when it holds any of them. An id
outside that list answers 422.

## List the categories offered as filters

`GET api/vaults/{vault}/contact-categories`, in the existing `filter` grouping when one is given.

```json
{ "data": [ { "type": "contact_category", "id": 12, "attributes": { "label": "Colleague", "is_active": true, "contacts_count": 4 } } ] }
```

Exactly the categories held by at least one contact of the vault, ordered by list position
(FR-006, US2.4).

## A contact's categories

`ContactResource` gains `categories`: `[{ "id": 12, "label": "Colleague" }]`, the union of every
Category field of the contact, ordered by list position.

## MCP

`ListContacts` (`app/Mcp/Tools/ListContacts.php`) takes `categories`, an array of ids, with the same
meaning and refusal as the API.
