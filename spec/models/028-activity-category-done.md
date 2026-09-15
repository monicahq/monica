# 028. ActivityCategory

**Status:** exists (`app/Models/ActivityCategory.php`), model and API done; settings screen waits for activity types
**Spec:** 9.3

## What it is

An account owned group for activity types. Examples are Relationships and family, Work and education, Health and wellbeing and Travel and experiences.

A category organises the activity vocabulary in settings and creation pickers. It can also filter the journal and group aggregate statistics. It never gives an activity product behaviour. Moving Call into Personal changes only where it is organised and aggregated.

## Fields

```
id                          int                             primary key
uuid                        string (36)                     unguessable identifier used in URLs and by the API
account_id                  int                             account the category belongs to
key                         string (64), nullable           stable identifier of a supplied category, null for a user created one
name                        string (255), nullable          name the user gave it, null while the supplied wording stands
name_translation_key        string (255), nullable          the supplied wording, read through the translator
is_system                   boolean                         whether the product supplied it with the account
is_active                   boolean                         whether it and its types appear when creating an activity
position                    int                             order it appears in settings, pickers and filters
created_at                  datetime
updated_at                  datetime
deleted_at                  datetime
```

## Relationships

- `belongsTo` [Account](001-account-in-progress.md)
- `hasMany` [ActivityType](035-activity-type-in-progress.md)

## Rules

- A new account starts with Relationships and family, Work and education, Health and wellbeing, Home and daily life, Leisure and culture, Travel and experiences, Personal and Other.
- The user may create, rename, reorder and deactivate categories.
- A supplied category cannot be deleted. Its `key` and `is_system` values never change.
- A user created category may be deleted only when it contains no activity type. Otherwise the types must first be moved or the category deactivated.
- Deactivating a category removes it and its types from activity creation. Existing activities remain readable and keep their types.
- Every activity type belongs to exactly one category. Other is proposed when no more precise category was chosen.
- Category names and positions have no semantic effect. The activity type decides which custom fields an activity carries. Participation decides which contact histories the activity updates.
- Journal filters and aggregate statistics read the category through the activity type. Moving a type therefore reclassifies its past activities in those views. The activity does not store a category snapshot.
- A type created inline while recording an activity is saved at account level and requires a category. It is not local to the contact or activity from which it was created.
- Installing an activity type from the marketplace requires an active local category and proposes Other. A [MarketplaceCategory](044-marketplace-category-done.md) is editorial catalogue organisation and never becomes an ActivityCategory.

## Management screens

- Category management is part of **Settings, Activity types**, not a separate destination.
- The screen lists supplied and user created categories in their chosen order, including inactive categories and their type counts.
- A category may be created, renamed, reordered, activated or deactivated without leaving the list.
- Deactivation asks for confirmation because every type under the category leaves activity creation with it. The confirmation says how many types are affected and that existing activities stay unchanged.
- Deleting a user created category asks for confirmation and is offered only when the category contains no type. Supplied categories offer deactivation instead.

## API contract

| Method and path | Result |
|---|---|
| `GET /activity-categories` | Paginated categories in account order, including inactive categories and type counts. |
| `GET /activity-categories/{activityCategory}` | One category. |
| `GET /activity-categories/{activityCategory}/types` | Paginated types filed under it. |
| `POST /activity-categories` | Create an active user category at the end of the list. |
| `PUT /activity-categories/{activityCategory}` | Rename a category. |
| `PUT /activity-categories/{activityCategory}/activation` | Set `is_active` explicitly. |
| `PUT /activity-categories/{activityCategory}/position` | Move it and renumber the account list. |
| `DELETE /activity-categories/{activityCategory}` | Soft delete an empty user category. |

- Public identifiers are UUIDs. A category from another account, a deleted category and an unknown category each answer 404.
- Creation and renaming require a nonempty plain text `name` of at most 255 characters.
- Activation and position are separate idempotent operations. Moving past the end puts the category last.
- A supplied category or a category containing types answers 422 to deletion and names the reason.
- All writes use the same actions as the settings screen.

## What is built

The table, model, factory, supplied catalogue, actions, API endpoints and API reference exist. Listing, reading, creating, renaming, deleting, activating, deactivating and reordering are covered by tests.

The settings screen is not built. Listing the types of one category and reporting a nonzero `activity_type_count` wait for the [ActivityType](035-activity-type-in-progress.md) screens. The type's table, model and creation action exist since the structure engine became generic.
