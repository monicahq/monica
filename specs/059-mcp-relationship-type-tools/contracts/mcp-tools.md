# Contract: MCP tools for relationship types

Every tool is registered on `MonicaServer`, uses `MirrorsAnApiCapability`, and returns
`RelationshipTypeResource` or `RelationshipCategoryResource` resolved, the same shape the API returns
under `data`. An identifier outside the key's account answers the validation error `That relationship
type does not exist.` or `That relationship category does not exist.` Every change is journalled with
the MCP source. Refusals carry the sentences in [research R2](../research.md#r2-refusals-carry-the-web-screens-translated-sentence).

`relationship_type` is "the identifier of the relationship type, as list-relationship-types gives
it". `relationship_category` and `relationship_category_id` are "the identifier of the category, as
list-relationship-categories gives it".

## Relationship types

### get-relationship-type

Mirrors `api.v1.relationshipTypes.show`. `#[IsReadOnly]`.

| Input | Type | Rules |
| --- | --- | --- |
| `relationship_type` | string | required |

### create-relationship-type

Mirrors `api.v1.relationshipTypes.create`.

| Input | Type | Rules |
| --- | --- | --- |
| `relationship_category_id` | string | required, a category of the account |
| `name` | string | required, max 255 |
| `role_a` | string | required, max 255. Described as the role of the first contact (parent) |
| `role_b` | string | required when `is_symmetric` is false, nullable, max 255. The role of the other contact (child) |
| `is_symmetric` | boolean | nullable, defaults to true. Described as "both contacts hold the same role, as with siblings" |
| `is_active` | boolean | nullable, defaults to true |

Left out, `role_b` takes `role_a`. A name another type of the account uses is refused.

### update-relationship-type

Mirrors `api.v1.relationshipTypes.update`. Anything left out keeps its value; whether it is active is
never changed here.

| Input | Type | Rules |
| --- | --- | --- |
| `relationship_type` | string | required |
| `relationship_category_id` | string | optional, a category of the account |
| `name` | string | optional, not empty when given, max 255 |
| `role_a` | string | optional, not empty when given, max 255 |
| `role_b` | string | nullable, max 255; required when the type turns asymmetric from symmetric |
| `is_symmetric` | boolean | optional |

Changing `is_symmetric` on a type that came with the account or that relationships use is refused.

### delete-relationship-type

Mirrors `api.v1.relationshipTypes.destroy`. `#[IsDestructive]`.

| Input | Type | Rules |
| --- | --- | --- |
| `relationship_type` | string | required |

Answers text `The relationship type is deleted.` A type that came with the account or that
relationships use answers an error with the policy's sentence, and nothing changes.

### set-relationship-type-activation

Mirrors `api.v1.relationshipTypes.activation.update`.

| Input | Type | Rules |
| --- | --- | --- |
| `relationship_type` | string | required |
| `is_active` | boolean | required |

Asking for the state it already has changes and journals nothing.

### set-relationship-type-position

Mirrors `api.v1.relationshipTypes.position.update`.

| Input | Type | Rules |
| --- | --- | --- |
| `relationship_type` | string | required |
| `position` | integer | required, min 1. Described as the place within its category, counting from 1 |

## Relationship categories

### get-relationship-category

Mirrors `api.v1.relationshipCategories.show`. `#[IsReadOnly]`.

| Input | Type | Rules |
| --- | --- | --- |
| `relationship_category` | string | required |

### list-relationship-category-types

Mirrors `api.v1.relationshipCategories.types`. `#[IsReadOnly]`. Returns a list of
`RelationshipTypeResource`, every type of the category, by position.

| Input | Type | Rules |
| --- | --- | --- |
| `relationship_category` | string | required |

### create-relationship-category

Mirrors `api.v1.relationshipCategories.create`.

| Input | Type | Rules |
| --- | --- | --- |
| `name` | string | required, max 255 |

### update-relationship-category

Mirrors `api.v1.relationshipCategories.update`. A category that came with the account is refused.

| Input | Type | Rules |
| --- | --- | --- |
| `relationship_category` | string | required |
| `name` | string | required, max 255 |

### delete-relationship-category

Mirrors `api.v1.relationshipCategories.destroy`. `#[IsDestructive]`.

| Input | Type | Rules |
| --- | --- | --- |
| `relationship_category` | string | required |

Answers text `The relationship category is deleted.` A category that came with the account, or that
still holds types, answers an error and nothing changes.

### set-relationship-category-activation

Mirrors `api.v1.relationshipCategories.activation.update`.

| Input | Type | Rules |
| --- | --- | --- |
| `relationship_category` | string | required |
| `is_active` | boolean | required |

### set-relationship-category-position

Mirrors `api.v1.relationshipCategories.position.update`.

| Input | Type | Rules |
| --- | --- | --- |
| `relationship_category` | string | required |
| `position` | integer | required, min 1 |

## Documentation contract

`/docs/mcp` and `/docs/mcp.md`:

- "Relationship vocabulary": `list-relationship-types`, `get-relationship-type`,
  `list-relationship-categories`, `get-relationship-category`, `list-relationship-category-types`.
- "Relationship vocabulary, changing" (changes data): `create-relationship-type`,
  `update-relationship-type`, `set-relationship-type-activation`, `set-relationship-type-position`,
  `delete-relationship-type`, `create-relationship-category`, `update-relationship-category`,
  `set-relationship-category-activation`, `set-relationship-category-position`,
  `delete-relationship-category`. Both delete tools say what they delete and what is refused.
- Two paragraphs: a relationship type names how two contacts are linked, with a role for each side
  (parent and child) or the same role for both (siblings), and is filed under a category; types and
  categories that came with the account, or that are in use, cannot be deleted, only switched off.
