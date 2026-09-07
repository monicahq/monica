# Data model

Both tables follow `spec/models/024` and `spec/models/025`. Only what the migrations need is here.

## relationship_categories

| Column | Type | Note |
| --- | --- | --- |
| `id` | big increments | |
| `uuid` | string 36, unique | set by the model's booted hook, as every other model does |
| `account_id` | big integer, indexed | foreign key declared at the bottom of the migration, cascade on delete |
| `key` | string 64, nullable | `family`, `romantic`, `friendship`, `professional`, `other`; null for a user-created one |
| `name` | string 255, nullable | what the user wrote, null while the supplied wording stands |
| `name_translation_key` | string 255, nullable | the supplied wording, read through `__()`, per [research.md](research.md) decision 8 |
| `is_system` | boolean, default false | |
| `is_active` | boolean, default true | no interface in this feature, per spec assumptions |
| `position` | integer | unique within the account, one-based |
| timestamps, `deleted_at` | | soft deletes |

## relationship_types

| Column | Type | Note |
| --- | --- | --- |
| `id` | big increments | |
| `uuid` | string 36, unique | |
| `account_id` | big integer, indexed | cascade on delete |
| `relationship_category_id` | big integer, indexed | cascade on delete |
| `key` | string 64, nullable | `parent_child`, `sibling`, …; null for a user-created one |
| `name` | string 255, nullable | unique per account once resolved, enforced in the actions rather than by an index, because a soft-deleted row keeps its name |
| `name_translation_key` | string 255, nullable | the supplied wording |
| `role_a` | string 255, nullable | |
| `role_a_translation_key` | string 255, nullable | |
| `role_b` | string 255, nullable | equal to `role_a` when `is_symmetric` |
| `role_b_translation_key` | string 255, nullable | |
| `is_symmetric` | boolean | |
| `system_behavior` | string 32, nullable | `RelationshipBehaviorEnum`: `genealogy_parentage`, `romantic` |
| `is_system` | boolean, default false | |
| `is_active` | boolean, default true | |
| `position` | integer | one-based within the category |
| timestamps, `deleted_at` | | soft deletes |

## What the actions enforce

- `name`, `role_a` and, when directed, `role_b` are required and trimmed; a value of only spaces is empty.
- `name` is refused when another live type of the account already carries it.
- A symmetric type writes `role_a` into `role_b` on every save.
- `is_symmetric` cannot change when `is_system` is true or the type is in use.
- `key`, `is_system` and `system_behavior` are never written after creation.
- A type is deleted only when `is_system` is false and it is not in use.
- A category is renamed or deleted only when `is_system` is false, and deleted only when it holds no live type.
- Every read is scoped to the account, and `resolveRouteBinding()` refuses anything else.

## Seed

Five categories in the order Family, Romantic, Friendship, Professional, Other. The ten types of `spec/spec.md` §8.2 go in the first four, in the order that section lists them. `genealogy_parentage` on Parent / Child, `romantic` on Partner, Fiancé and Spouse.
