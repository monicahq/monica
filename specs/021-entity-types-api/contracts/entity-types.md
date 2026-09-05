# Contract: entity type endpoints

Base `/api/v1`, inside the `auth:sanctum` group, so every route inherits `throttle:60,1`. The identifier is a
uuid and the router refuses any other shape before a query runs. A type is account scoped, so no vault
appears in the address.

## Endpoints

| Method | Path | Route name | Answers |
| --- | --- | --- | --- |
| GET | `entity-types` | `entityTypes` | 200, `data` and `meta` |
| GET | `entity-types/{entityType}` | `entityTypes.show` | 200 |
| POST | `entity-types` | `entityTypes.create` | 201 |
| PUT | `entity-types/{entityType}` | `entityTypes.update` | 200 |
| DELETE | `entity-types/{entityType}` | `entityTypes.destroy` | 204 |
| PUT | `entity-types/{entityType}/activation` | `entityTypes.activation.update` | 200 |
| PUT | `entity-types/{entityType}/position` | `entityTypes.position.update` | 200 |

A type of another account, and one already deleted, answer 404 everywhere, never 403. An invalid body
answers 422 naming every field, and an unauthenticated caller answers 401.

## The entity type object

```json
{ "type": "entity_type", "id": "<uuid>",
  "attributes": {
    "slug": "animal", "name_singular": "Animal", "name_plural": "Animals",
    "description": "Every creature the family keeps.", "icon": "profile", "colour": "#4f8a49",
    "position": 1, "is_active": true, "entity_count": 12,
    "created_at": 1756252800, "updated_at": 1756339200 },
  "links": { "self": "<base>/entity-types/<uuid>" } }
```

Withheld: the primary key, `account_id` (always the caller's) and `deleted_at`. `slug` is what the browser
addresses the same type by, so a caller can build a link into the application from it. It is settled when
the type is declared and never rebuilt.

## Query parameters on the list

`page` and `per_page` only, `per_page` 1 to 100, clamped rather than refused, defaulting to 1 and 25. There
is no `filter`, no `query` and no `sort`: the answer is always in `position` order, then `id`, inactive
types among the rest.

## Bodies

- **Declare**: `name_singular` and `name_plural` required, up to 255 characters each. `description`
  (up to 65535), `icon`, `colour`, `is_active` and `slug` optional. Left out, the icon is `profile`, the
  colour `#3f5fd0`, `is_active` true, and the slug is derived from the singular name and made unique.
- **Correct**: `name_singular`, `name_plural`, `icon` and `colour` required, `description` `present` and
  nullable, because this replaces rather than patches. `slug`, `position`, `is_active` and `entity_count`
  are not accepted here and are left untouched.
- **Activation**: `is_active` required boolean, the state wanted rather than a toggle.
- **Position**: `position` required integer, at least 1. A number past the end puts the type last, and every
  type of the account is renumbered from one.

`icon`: `profile`, `logs`, `security`, `preferences`, `key`, `device`, `info`, `activity`, `mail`, `sun`,
`moon`, `check`. `colour`: `#3f5fd0`, `#4f8a49`, `#9a6a24`, `#9a3f2b`, `#5d5f96`, `#1d1d20`, lower case.
`slug`: lowercase letters, numbers and single dashes, up to 64 characters, free in the account counting the
slugs of deleted types. Deleting a type that holds records answers 422 with the count, from
`CannotDeleteAnEntityTypeInUse`.
