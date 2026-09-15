# 035. ActivityType

**Status:** the table, the model and its creation action exist. Its screens and its API follow separately
**Spec:** 9.3, 9.5, 10.2, 7

## What it is

The nature of an activity: lunch, phone call, concert, medical appointment, argument, walk.

The type belongs to an account and one activity category. It defines:

- its name and optional presentation;
- the optional custom fields an activity of this type asks for.

The activity remains a fixed product object. Its type is the configurable part. A type may be shared on the marketplace with its field structure.

## Fields

```
id                          int                             primary key
uuid                        string (36)                     unguessable identifier used by the API
account_id                  int                             account the type belongs to
activity_category_id        int                             category the type is filed under
key                         string (64), nullable           stable identifier of a supplied type, null for a user made one
name                        string (255), nullable          label of the type, null when it falls back to its translation key
name_translation_key        string (255), nullable          translation key of a supplied label
description                 string (255), nullable          what it covers, shown when picking a type
icon                        string (64), nullable           icon shown on its activities, with a neutral fallback
colour                      string (7), nullable            colour used the same way, with a neutral fallback
is_system                   boolean                         whether the product supplied it
is_active                   boolean                         whether it can still be chosen for a new activity
position                    int                             order it appears in the picker
created_at                  datetime
updated_at                  datetime
deleted_at                  datetime
```

## Relationships

- `belongsTo` [Account](001-account-in-progress.md), [ActivityCategory](028-activity-category-done.md)
- `morphOne` [Structure](056-structure-done.md), which carries the versions of the custom fields it asks for
- `hasMany` [Activity](036-activity-not-yet.md)
- `hasOne` [MarketplaceListing](045-marketplace-listing-done.md) when published

## Supplied types

| Category | Type |
|---|---|
| Relationships and family | Call |
| Relationships and family | Message |
| Relationships and family | Meal |
| Relationships and family | Visit |
| Relationships and family | Time together |
| Relationships and family | Argument |
| Relationships and family | Marriage |
| Relationships and family | Birth |
| Relationships and family | Separation |
| Work and education | Meeting |
| Work and education | Interview |
| Work and education | New job |
| Work and education | Class |
| Work and education | Training |
| Work and education | Graduation |
| Health and wellbeing | Medical appointment |
| Health and wellbeing | Therapy |
| Health and wellbeing | Illness |
| Health and wellbeing | Hospital stay |
| Health and wellbeing | Exercise |
| Home and daily life | Move |
| Home and daily life | Home improvement |
| Home and daily life | Major purchase |
| Home and daily life | Household task |
| Leisure and culture | Restaurant |
| Leisure and culture | Concert |
| Leisure and culture | Cinema |
| Leisure and culture | Reading |
| Leisure and culture | Game |
| Leisure and culture | Hobby |
| Travel and experiences | Trip |
| Travel and experiences | Holiday |
| Travel and experiences | Hike |
| Travel and experiences | Excursion |
| Personal | Reflection |
| Personal | Decision |
| Personal | Achievement |
| Personal | Memory |
| Personal | Spiritual event |
| Other | Other activity |

## Management screens

- **Settings, Activity types** lists categories in their account order and types in their category order. It includes inactive rows, identifies supplied rows and searches names across every category.
- Type rows have no interaction setting or badge. Whether a contact history changes is determined only by the activity's participants.
- The settings screen creates, renames, reorders, moves, activates and deactivates a type. It links to category management and warns before deletion.
- The type form requires a name and an active category. Description, icon and colour are optional.
- A new type starts with no custom fields, which is a published structure version holding one empty page rather than the absence of one. Saving the type offers the field builder, which is the same engine the template builder uses.
- Editing a published field structure creates a draft. Publishing the draft changes the version offered to new activities and never moves an existing activity to it.
- The screens say "custom fields" throughout. The words template, structure and version never reach the user here: neither is what they came to manage.
- The activity form groups active types under active categories. It offers search and recent types before the complete grouped list.
- The activity form may create a type inline. This short form asks only for name and category. The saved type belongs to the account and is immediately selected.
- Supplied and used types offer deactivation instead of deletion. A user created type that no activity uses may be deleted after confirmation.

## API contract

The API exposes the same account level operations as the settings screen:

| Method and path | Result |
|---|---|
| `GET /activity-types` | Paginated types ordered by category position, then type position. Inactive types are included. |
| `GET /activity-categories/{activityCategory}/types` | Paginated types in one category. |
| `GET /activity-types/{activityType}` | One type and the exact version its structure currently publishes. |
| `POST /activity-types` | Create a user type at the end of an active category. |
| `PUT /activity-types/{activityType}` | Change its name, category, description, icon or colour. |
| `PUT /activity-types/{activityType}/activation` | Set `is_active` explicitly. |
| `PUT /activity-types/{activityType}/position` | Move it inside its category. Moving it to another category belongs to the ordinary update. |
| `DELETE /activity-types/{activityType}` | Soft delete a user type that no activity uses. |

- Public identifiers are UUIDs. A type from another account, a deleted type and an unknown type each answer 404.
- Creation requires `activity_category_id` and `name`. The optional inputs are `description`, `icon` and `colour`.
- The ordinary update never changes `key`, `is_system`, position, activation or the published field structure. Each of those either stays protected or has its own operation.
- A supplied type or a type used by an activity answers 422 to deletion and remains available for deactivation.
- Field structure changes go through the engine on the type's own path, never through the template API, which would put a word in front of the user that this model exists to keep away. Type endpoints name the version the structure currently publishes and do not accept a nested field schema. That path is designed with the screens.
- All writes use the same actions as the web interface. Creating a type inline is the ordinary create operation, not a contact scoped endpoint.

## Rules

- The type owns a [Structure](056-structure-done.md) and does not name a version. The structure says which version is published, which is what lets a type change its fields without every activity following. Deleting a type is soft and the structure deliberately stays, because a restored type has to come back with the fields it asked for. The structure names its owner through a morph, which carries no foreign key, so nothing that force deletes a single type can leave the structure behind.
- A type has exactly one structure, for the whole account. An [EntityType](011-entity-type-done.md) may have several templates with a vault picking between them (5.3); an activity type deliberately may not. Recording a restaurant visit differently in two vaults is not a need anybody has stated, and one structure per type keeps the journal aggregating across vaults without asking which structure a figure came from.
- Every type belongs to one account owned category. The category organises settings, pickers, journal filters and aggregate statistics, but none of the type's behaviour.
- A type may be created from settings or inline while an activity is being recorded. The inline form asks for its name and category, saves it for the whole account and selects it for the activity in progress. Its remaining settings can be completed later.
- Icon, colour and description are optional. Their absence uses a neutral presentation rather than blocking creation.
- The type uses the same page, section and field hierarchy as a contact or an entity, through the same engine and the same actions. Every activity keeps the exact version used when it was created, so changing the type's fields never changes an old activity.
- An activity type never decides whether contact occurred. Participation does. Every contact added as a participant contributes the activity to that contact's history and latest interaction.
- Disabling a type never breaks the activities already using it (9.3). They keep their type, their icon and their fields.
- The account is seeded with a usable set at creation. The supplied catalogue ships with the settings screens rather than with the engine, so no account holds a supplied type yet. Supplied types may be renamed, reordered or disabled, but not deleted, which the delete action already refuses.
- A user created type may be deleted only when no activity uses it. Otherwise it must be disabled.
- One activity has one type. Overlap belongs in custom fields or later tags, not in several type links.
