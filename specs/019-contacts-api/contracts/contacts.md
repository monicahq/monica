# Contract: contact endpoints

Base `/api/v1`, inside the `auth:sanctum` group, so every route inherits `throttle:60,1` and `set.locale`.
Both identifiers are uuids and the router refuses any other shape before a query runs.

## Endpoints

| Method | Path | Route name | Answers |
| --- | --- | --- | --- |
| GET | `vaults/{vault}/contacts` | `contacts` | 200, `data` and `meta` |
| GET | `vaults/{vault}/contacts/{contact}` | `contacts.show` | 200 |
| POST | `vaults/{vault}/contacts` | `contacts.create` | 201 |
| DELETE | `vaults/{vault}/contacts/{contact}` | `contacts.destroy` | 204 |
| PUT | `vaults/{vault}/contacts/{contact}/name` | `contacts.name.update` | 200 |
| PUT | `vaults/{vault}/contacts/{contact}/favourite` | `contacts.favourite.update` | 200 |
| POST | `vaults/{vault}/contacts/{contact}/avatar` | `contacts.avatar.update` | 200 |
| DELETE | `vaults/{vault}/contacts/{contact}/avatar` | `contacts.avatar.destroy` | 200 |
| PUT | `vaults/{vault}/contacts/{contact}/template-version` | `contacts.templateVersion.update` | 200 |

Anything of another account, or of another vault than the address names, answers 404 everywhere. An
invalid body answers 422 naming every field, and an unauthenticated caller answers 401.

## The contact object

```json
{ "type": "contact", "id": "<uuid>",
  "attributes": {
    "name": "Pam Beesly", "prefix": null, "first_name": "Pam", "middle_name": null,
    "last_name": "Beesly", "suffix": null, "maiden_name": null, "nickname": "Pammy",
    "name_order": "given_first", "initials": "PB", "avatar_colour": "#0ea5e9",
    "avatar": { "id": "<file uuid>", "url": "<app url, needs the session>" }, "is_me": false,
    "is_favourite": true, "is_archived": false, "is_partial": false,
    "template_version_id": 4, "template_name": "Default contact", "last_activity_at": null,
    "created_at": 1756252800, "updated_at": 1756339200 },
  "links": { "self": "<base>/vaults/<uuid>/contacts/<uuid>" } }
```

Withheld: the primary key, `account_id`, `vault_id` (it is in the address), `sort_name`, `sort_key` and
`search_key` (internal), `activity_count`, `last_consulted_at` (a person clicking, which says nothing to a
program), `deleted_at` and `deleted_with_vault`.

## Query parameters on the list

| Name | Values | Default |
| --- | --- | --- |
| `filter` | `all`, `favourites`, `archived` | `all` |
| `query` | free text, folded for the account's language | none |
| `sort`, `direction` | `name`, `nickname`, `last-spoke`, `added`; `asc` or `desc` | `name`, `asc` |
| `page`, `per_page` | `per_page` 1 to 100, clamped rather than refused | 1, 25 |

## Bodies

- **Create**: `first_name` required; `prefix`, `middle_name`, `last_name`, `suffix`, `maiden_name`,
  `nickname`, `name_order`, `is_partial`, `is_favourite` and `template_version_id` optional. The
  structure must be a published, active contact template of the caller's account.
- **Name**: all eight parts `present` and nullable. This replaces rather than patches, so a part left
  out is an erasure the caller did not ask for.
- **Favourite**: `favourite` required boolean, the state wanted rather than a toggle.
- **Avatar**: `multipart/form-data`, field `avatar`, jpeg, png or webp, at most 5 MB.
- **Template version**: `template_version_id` optional; absent means whatever that structure publishes now, and anything the move does not offer answers 404 rather than 422.
