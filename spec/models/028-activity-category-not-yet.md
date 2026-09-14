# 028. ActivityCategory

**Status:** to create
**Spec:** 9.3

## What it is

An account owned group for activity types. Examples are Relationships and family, Work and education, Health and wellbeing and Travel and experiences.

A category organises the activity vocabulary in settings and creation pickers. It can also filter the journal and group aggregate statistics. It never gives an activity product behaviour. Moving Call into Personal does not stop it from counting as an interaction, and moving Thought into Relationships and family does not make it count as one.

## Fields

```
id                          int                             primary key
uuid                        string (36)                     unguessable identifier used in URLs and by the API
account_id                  int                             account the category belongs to
key                         string (64)                     stable identifier of a supplied category, null for a user created one
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
- `hasMany` [ActivityType](035-activity-type-not-yet.md)

## Rules

- A new account starts with Relationships and family, Work and education, Health and wellbeing, Home and daily life, Leisure and culture, Travel and experiences, Personal and Other.
- The user may create, rename, reorder and deactivate categories.
- A supplied category cannot be deleted. Its `key` and `is_system` values never change.
- A user created category may be deleted only when it contains no activity type. Otherwise the types must first be moved or the category deactivated.
- Deactivating a category removes it and its types from activity creation. Existing activities remain readable and keep their types.
- Every activity type belongs to exactly one category. Other is proposed when no more precise category was chosen.
- Category names and positions have no semantic effect. Only the activity type decides whether an activity counts as an interaction or carries custom fields.
- Journal filters and aggregate statistics read the category through the activity type. Moving a type therefore reclassifies its past activities in those views. The activity does not store a category snapshot.
- A type created inline while recording an activity is saved at account level and requires a category. It is not local to the contact or activity from which it was created.
- Installing an activity type from the marketplace requires an active local category and proposes Other. A [MarketplaceCategory](044-marketplace-category-done.md) is editorial catalogue organisation and never becomes an ActivityCategory.
