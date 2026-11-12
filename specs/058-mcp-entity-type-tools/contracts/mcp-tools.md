# Contract: MCP tools for entity types

Every tool is registered on `MonicaServer`, uses `MirrorsAnApiCapability`, and returns
`EntityTypeResource` resolved, the same shape the API returns under `data`. An identifier that is
not an entity type of the key's account answers the validation error `That entity type does not
exist.` Every change is journalled with the MCP source.

`entity_type` is always "the identifier of the entity type, as list-entity-types gives it" (the
resource's `id`).

## get-entity-type

Mirrors `api.v1.entityTypes.show`. `#[IsReadOnly]`.

| Input | Type | Rules |
| --- | --- | --- |
| `entity_type` | string | required |

## create-entity-type

Mirrors `api.v1.entityTypes.create`.

| Input | Type | Rules |
| --- | --- | --- |
| `name_singular` | string | required, max 255 |
| `name_plural` | string | required, max 255 |
| `description` | string | nullable, max 65535 |
| `icon` | string | nullable, in `EntityType::ICONS` (listed in the description) |
| `colour` | string | nullable, in `EntityType::COLOURS` (listed in the description) |
| `is_active` | boolean | nullable, defaults to true |
| `slug` | string | nullable, max `EntityType::SLUG_LENGTH`, `^[a-z0-9]+(?:-[a-z0-9]+)*$`, unique in the account |

Left out, `icon` and `colour` take the action's defaults and `slug` is derived from `name_singular`.

## update-entity-type

Mirrors `api.v1.entityTypes.update`. Anything left out keeps its value; `description: null` clears it.
The slug cannot be changed.

| Input | Type | Rules |
| --- | --- | --- |
| `entity_type` | string | required |
| `name_singular` | string | optional, not empty when given, max 255 |
| `name_plural` | string | optional, not empty when given, max 255 |
| `description` | string | nullable, max 65535 |
| `icon` | string | optional, not empty when given, in `EntityType::ICONS` |
| `colour` | string | optional, not empty when given, in `EntityType::COLOURS` |

## delete-entity-type

Mirrors `api.v1.entityTypes.destroy`. `#[IsDestructive]`.

| Input | Type | Rules |
| --- | --- | --- |
| `entity_type` | string | required |

Answers text `The entity type is deleted.` A type holding records answers an error carrying the
policy's sentence, `The type still holds records and stays.`, and nothing changes.

## set-entity-type-activation

Mirrors `api.v1.entityTypes.activation.update`.

| Input | Type | Rules |
| --- | --- | --- |
| `entity_type` | string | required |
| `is_active` | boolean | required |

Asking for the state it already has changes and journals nothing.

## set-entity-type-position

Mirrors `api.v1.entityTypes.position.update`.

| Input | Type | Rules |
| --- | --- | --- |
| `entity_type` | string | required |
| `position` | integer | required, min 1 |

## Documentation contract

`/docs/mcp` and `/docs/mcp.md`:

- "Entity types": `list-entity-types`, `get-entity-type`.
- "Entity types, changing" (changes data): `create-entity-type`, `update-entity-type`,
  `set-entity-type-activation`, `set-entity-type-position`, `delete-entity-type` (says it deletes a
  type holding no record).
- One paragraph: an entity type is a kind of record besides contacts; its records follow a template
  made with `create-template` and `applies_to` set to entity; a type holding records cannot be
  deleted, only switched off.
