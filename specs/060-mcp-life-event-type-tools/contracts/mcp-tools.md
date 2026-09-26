# Contract: MCP tools for life event types

Every tool is registered on `EricaServer`, uses `MirrorsAnApiCapability`, and returns the resource the
API returns under `data`: `LifeEventTypeResource`, `LifeEventCategoryResource`, or for fields a list
of `StructureVersionSectionResource`. An identifier outside the key's account answers the validation
error `That life event type does not exist.` or `That life event category does not exist.` Every
change is journalled with the MCP source. Refusals are described in
[research R2](../research.md#r2-refusals).

`life_event_type` is "the identifier of the life event type, as list-life-event-types gives it".
`life_event_category` and `life_event_category_id` are "the identifier of the category, as
list-life-event-categories gives it".

## Life event types

### list-life-event-types

Mirrors `api.v1.lifeEventTypes`. `#[IsReadOnly]`. No input. Every type of the account, by category
position, then type position, then id.

### get-life-event-type

Mirrors `api.v1.lifeEventTypes.show`. `#[IsReadOnly]`.

| Input | Type | Rules |
| --- | --- | --- |
| `life_event_type` | string | required |

### create-life-event-type

Mirrors `api.v1.lifeEventTypes.create`.

| Input | Type | Rules |
| --- | --- | --- |
| `life_event_category_id` | string | required, a category of the account |
| `name` | string | required, max 255 |
| `description` | string | nullable, max 255 |
| `icon` | string | nullable, in `Section::ICONS` (listed in the description) |
| `colour` | string | nullable, in `VaultColourEnum::values()` (listed in the description) |

### update-life-event-type

Mirrors `api.v1.lifeEventTypes.update`. Anything left out keeps its value; `null` clears
`description`, `icon` or `colour`. Whether it is offered is never changed here.

| Input | Type | Rules |
| --- | --- | --- |
| `life_event_type` | string | required |
| `life_event_category_id` | string | optional, a category of the account |
| `name` | string | optional, not empty when given, max 255 |
| `description` | string | nullable, max 255 |
| `icon` | string | nullable, in `Section::ICONS` |
| `colour` | string | nullable, in `VaultColourEnum::values()` |

### delete-life-event-type

Mirrors `api.v1.lifeEventTypes.destroy`. `#[IsDestructive]`.

| Input | Type | Rules |
| --- | --- | --- |
| `life_event_type` | string | required |

Answers text `The life event type is deleted.` A type that came with the account, or that life events
were recorded with, answers an error with the policy's sentence, and nothing changes.

### set-life-event-type-activation

Mirrors `api.v1.lifeEventTypes.activation.update`.

| Input | Type | Rules |
| --- | --- | --- |
| `life_event_type` | string | required |
| `is_active` | boolean | required |

Asking for the state it already has changes and journals nothing.

### set-life-event-type-position

Mirrors `api.v1.lifeEventTypes.position.update`.

| Input | Type | Rules |
| --- | --- | --- |
| `life_event_type` | string | required |
| `position` | integer | required, min 1. The place within its category, counting from 1 |

### get-life-event-type-fields

Mirrors `api.v1.lifeEventTypes.fields`. `#[IsReadOnly]`. Returns the groups of the published version,
each with its fields, or an empty list.

| Input | Type | Rules |
| --- | --- | --- |
| `life_event_type` | string | required |

### replace-life-event-type-fields

Mirrors `api.v1.lifeEventTypes.fields.update`. `#[IsDestructive]`. Returns what
`get-life-event-type-fields` returns, after the change. Rules are `LifeEventTypeFieldLayout::rules()`,
shared with the API.

| Input | Type | Rules |
| --- | --- | --- |
| `life_event_type` | string | required |
| `groups` | array | nullable. Each: `placement_id` (integer, nullable, keeps an existing group), `name` (required, max 255), `fields` (array of fields) |
| `fields` | array | nullable. Fields nobody grouped, shown first |

A field: `key` (nullable, max 64, keeps an existing field's identity), `label` (required, max 255),
`type` (required, a `FieldTypeEnum` value, listed in the description), `is_required` (boolean),
`help_text` (nullable, max 255), `settings` (object of strings; `max_length`, `min`, `max`,
`decimals`, `max_choices`, `repeat_min`, `repeat_max` are whole numbers), `choices` (list of strings).

The set sent replaces the whole set; a field left out stops being asked for. Life events already
recorded keep the version they were written with.

## Life event categories

### get-life-event-category

Mirrors `api.v1.lifeEventCategories.show`. `#[IsReadOnly]`.

| Input | Type | Rules |
| --- | --- | --- |
| `life_event_category` | string | required |

### list-life-event-category-types

Mirrors `api.v1.lifeEventCategories.types`. `#[IsReadOnly]`. Every type of the category, by position
then id.

| Input | Type | Rules |
| --- | --- | --- |
| `life_event_category` | string | required |

### create-life-event-category

Mirrors `api.v1.lifeEventCategories.create`.

| Input | Type | Rules |
| --- | --- | --- |
| `name` | string | required, max 255 |

### update-life-event-category

Mirrors `api.v1.lifeEventCategories.update`.

| Input | Type | Rules |
| --- | --- | --- |
| `life_event_category` | string | required |
| `name` | string | required, max 255 |

### delete-life-event-category

Mirrors `api.v1.lifeEventCategories.destroy`. `#[IsDestructive]`.

| Input | Type | Rules |
| --- | --- | --- |
| `life_event_category` | string | required |

Answers text `The life event category is deleted.` One that came with the account, or that still
holds types, answers an error with the policy's sentence, and nothing changes.

### set-life-event-category-activation

Mirrors `api.v1.lifeEventCategories.activation.update`.

| Input | Type | Rules |
| --- | --- | --- |
| `life_event_category` | string | required |
| `is_active` | boolean | required |

### set-life-event-category-position

Mirrors `api.v1.lifeEventCategories.position.update`.

| Input | Type | Rules |
| --- | --- | --- |
| `life_event_category` | string | required |
| `position` | integer | required, min 1 |

## Documentation contract

`/docs/mcp` and `/docs/mcp.md`:

- "Life event vocabulary" (was "Life event categories"): `list-life-event-categories`,
  `get-life-event-category`, `list-life-event-category-types`, `list-life-event-types`,
  `get-life-event-type`, `get-life-event-type-fields`.
- "Life event vocabulary, changing" (changes data): the five changing type tools, the replace fields
  tool, the four changing category tools. Deleting and replacing tools say what they remove.
- Two paragraphs: a life event type is a kind of thing that happens to a contact, filed under a
  category, and can ask for fields of its own; replacing them never changes a life event already
  recorded; types and categories that came with the account, or that are in use, cannot be deleted,
  only switched off.
