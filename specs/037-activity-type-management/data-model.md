# Data model

No migration. Every column these screens write already exists, and this is the finding rather than a
formality: 034 wrote `activity_categories` and 035 wrote `activity_types` against the same model
specifications the screens are built from.

## What the tables already carry

| Record | Columns the screens read and write |
| --- | --- |
| `activity_categories` | `name`, `name_translation_key`, `is_system`, `is_active`, `position` |
| `activity_types` | `activity_category_id`, `name`, `name_translation_key`, `description`, `icon`, `colour`, `is_system`, `is_active`, `position` |
| `structures` and their versions | created with the type by `CreateActivityType`, one published version holding one page |

`activity_types.colour` is 7 characters, which is exactly a `VaultColourEnum` value, so the mark
picker sections already use stores into it unchanged (research D6). `icon` is 64 characters and
holds a name from `Section::ICONS`, not markup.

## The one behaviour that changes

`ActivityCategory::activityTypeCount()` returns a literal `0` today, with a comment naming this
feature as the one that fixes it. It becomes a count of the types filed under the category,
undeleted. Two readers depend on it and must agree: the number the rail shows beside a category, and
the rule refusing to delete a category that still holds something. The category API resource already
publishes it, so the endpoints shipped in 034 start answering truthfully with no change of their own.

## Validation

| Input | Rule |
| --- | --- |
| Category name | required, string, at most 255, sanitised to plain text |
| Type name | required, string, at most 255, sanitised to plain text |
| Type category | required, exists in the signed in account |
| Type description | optional, at most 255, sanitised to plain text |
| Type icon | optional, one of `Section::ICONS` |
| Type colour | optional, one of `VaultColourEnum` |
| Position | required integer, clamped inside the list it moves within |

## State

A category and a type are each either offered or not, and the switch is reversible from the same
control. Deletion is soft on both, which is what lets a restored type come back with the fields it
asked for. Neither state transition touches anything recorded.
