# Contract: contact relationships

All under `auth:sanctum`, prefix `/api/v1`, names prefixed `api.v1.`. `{vault}` and `{contact}` take a
slug or an id; `{relationship}` is a UUID. Anything outside the account or the vault answers 404. The
list takes `page` and `per_page` like reminders, ordered by `id`.

| Method | Path | Name | Answer |
| --- | --- | --- | --- |
| GET | `vaults/{vault}/contacts/{contact}/relationships` | `contacts.relationships` | 200, `data` + `meta` |
| POST | same | `contacts.relationships.create` | 201, resource |
| GET | `.../relationships/{relationship}` | `contacts.relationships.show` | 200, resource |
| PUT | same | `contacts.relationships.update` | 200, resource |
| DELETE | same | `contacts.relationships.destroy` | 204 |

## Create

| Field | Rule |
| --- | --- |
| `relationship_type` | required, UUID of a type of the account |
| `other_contact` | nullable string, slug or id inside the vault; prohibits `first_name`, `last_name` |
| `first_name`, `last_name` | nullable, max 255 |
| `creates_record` | boolean, default false: the named person is a placeholder |
| `holds_role_a` | boolean, default true; ignored when the type reads the same both ways |

## Update

Every field optional; absent keeps, `null` clears a date. `relationship_type`, `status`
(`current`\|`ended`), `started_on` and `ended_on` (`Y-m-d`), `holds_role_a`, and `first_name`,
`last_name`, `creates_record` for a placeholder other side only.

## Resource

```json
{
  "type": "relationship",
  "id": "uuid",
  "attributes": {
    "relationship_type": { "id": "uuid", "name": "Partner", "is_symmetric": true },
    "contact_a": { "id": "uuid", "slug": "jim-halpert", "name": "Jim Halpert", "is_partial": false, "role": "partner" },
    "contact_b": { "id": "uuid", "slug": "pam-beesly", "name": "Pam Beesly", "is_partial": false, "role": "partner" },
    "status": "current", "started_on": "2007-05-10", "ended_on": null,
    "created_at": 1790380800, "updated_at": null
  },
  "links": { "self": "/api/v1/vaults/{vault}/contacts/{contact}/relationships/{uuid}" }
}
```

`self` goes through `contact_a`; a contact's `id` or `slug` (as `ContactResource`) addresses it.

## MCP tools

Each takes `vault`, `contact`, `relationship` where the route has one, and the body fields.

| Tool | Mirrors | Annotation |
| --- | --- | --- |
| `list-contact-relationships` | `api.v1.contacts.relationships` | read only |
| `get-contact-relationship` | `api.v1.contacts.relationships.show` | read only |
| `create-contact-relationship` | `api.v1.contacts.relationships.create` | |
| `update-contact-relationship` | `api.v1.contacts.relationships.update` | |
| `delete-contact-relationship` | `api.v1.contacts.relationships.destroy` | destructive |
