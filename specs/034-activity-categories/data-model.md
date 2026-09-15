# Data model: activity categories

One new table. Nothing existing changes shape; `accounts` gains a relation and no column.
The record's rules are [spec/models/028](../../spec/models/028-activity-category-done.md).

## `activity_categories`

| Column | Type | Why |
| --- | --- | --- |
| `id` | `id` | primary key |
| `uuid` | `uuid`, unique | how the API addresses it |
| `account_id` | `unsignedBigInteger` | the account it belongs to, foreign key, cascade on delete |
| `key` | `string(64)`, nullable | stable identity of a supplied category, null for one somebody added |
| `name` | `string(255)`, nullable | the words somebody wrote, null while the supplied wording stands |
| `name_translation_key` | `string(255)`, nullable | the supplied wording, read through the translator |
| `is_system` | `boolean`, default false | whether the product supplied it, which governs deletion only |
| `is_active` | `boolean`, default true | whether it and its natures appear when an activity is recorded |
| `position` | `unsignedInteger` | order in settings, pickers and filters |
| `created_at`, `updated_at`, `deleted_at` | timestamps, soft delete | |

`unique(['account_id', 'key'])`, so an account holds each supplied category once. The foreign key is
declared at the bottom of the migration, never with `constrained()`.

## The model

- `belongsTo` Account. `hasMany` ActivityType is **not** declared: that model does not exist.
- `HasUuids`, `ResolvesModelInAccount`, `SoftDeletes`, `#[Fillable]`.
- `name` is an accessor returning the user's words when there are any and `__($this->name_translation_key)` otherwise, exactly as `RelationshipCategory` does.
- `activityTypeCount(): int` returns `0` today. It is the single place the count lives, read by the deletion guard and by the resource, and the only thing that changes when activity natures land.

## The eight supplied categories

`ActivityCategoryEnum`, backed by a string, implementing `Translatable` so `monica:localize` finds
the names. `key` holds the case value, `name_translation_key` holds the label, `name` stays null.

| Position | Case | `key` | Label |
| --- | --- | --- | --- |
| 1 | RelationshipsAndFamily | `relationships_and_family` | Relationships and family |
| 2 | WorkAndEducation | `work_and_education` | Work and education |
| 3 | HealthAndWellbeing | `health_and_wellbeing` | Health and wellbeing |
| 4 | HomeAndDailyLife | `home_and_daily_life` | Home and daily life |
| 5 | LeisureAndCulture | `leisure_and_culture` | Leisure and culture |
| 6 | TravelAndExperiences | `travel_and_experiences` | Travel and experiences |
| 7 | Personal | `personal` | Personal |
| 8 | Other | `other` | Other |

## State and transitions

- **Offered or retired.** `is_active` is sent as the state wanted, never toggled. A supplied category retires like any other.
- **Named.** `name` is written on any rename, supplied or not, and `name_translation_key` is then ignored. The supplied wording does not come back.
- **Deleted.** Soft, and only when `is_system` is false and `activityTypeCount()` is zero.
- **Positioned.** Every category of the account is renumbered from 1 on each move, retired ones counted.
