# Contract: API v1 and MCP

Both endpoints take at most 500 people, address each one as every contact endpoint does (uuid or
slug), and answer 422 over that, or when a person or a category is outside the vault.

## `PUT /api/vaults/{vault}/contacts/categories` (`api.v1.contacts.categories.update`)

```json
{ "contacts": [ { "contact": "c1f0…", "categories": [12] }, { "contact": "a9e2…", "categories": [] } ] }
```

- `categories` lists items of the account's "Contact categories" list; empty removes every category.
- 200:

```json
{ "data": { "type": "contact_category_change", "attributes": { "changed": ["c1f0…"], "unchanged": [ { "contact": "a9e2…", "reason": "Their template has no category field." } ] } } }
```

## `DELETE /api/vaults/{vault}/contacts` (`api.v1.contacts.deletion.destroy`)

```json
{ "contacts": ["c1f0…", "a9e2…"] }
```

- 200: `{ "data": { "type": "contact_deletion", "attributes": { "deleted": ["c1f0…"], "kept": [ { "contact": "a9e2…", "reason": "<the contact policy's sentence>" } ] } } }`

## MCP

| Tool | Mirrors | Note |
| --- | --- | --- |
| `SetContactCategories` | `api.v1.contacts.categories.update` | Description says each person ends with exactly the listed categories |
| `DeleteContacts` | `api.v1.contacts.deletion.destroy` | Destructive annotation, as `DeleteContact` |
