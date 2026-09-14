# 035. ActivityType

**Status:** to create
**Spec:** 9.3, 9.5, 10.2, 7

## What it is

The nature of an activity: lunch, phone call, concert, medical appointment, argument, walk.

The type belongs to an account and one activity category. It defines:

- its name and optional presentation;
- whether it updates the last interaction with its participants;
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
counts_as_interaction       boolean                         whether it counts as real contact with the participants, which drives inactivity reminders
template_version_id         int, nullable                   structure of the extra fields it asks for, null when it asks for none
is_system                   boolean                         whether the product supplied it
is_active                   boolean                         whether it can still be chosen for a new activity
position                    int                             order it appears in the picker
created_at                  datetime
updated_at                  datetime
deleted_at                  datetime
```

## Relationships

- `belongsTo` [Account](001-account-in-progress.md), [ActivityCategory](028-activity-category-not-yet.md)
- `belongsTo` [TemplateVersion](013-template-version-done.md)
- `hasMany` [Activity](036-activity-not-yet.md)
- `hasOne` [MarketplaceListing](045-marketplace-listing-done.md) when published

## Rules

- The type points at its template version, not the reverse ([Template](012-template-in-progress.md)). This is the direction that avoids a cycle and matches how the page is read.
- Every type belongs to one account owned category. The category organises settings, pickers, journal filters and aggregate statistics, but none of the type's behaviour.
- A type may be created from settings or inline while an activity is being recorded. The inline form asks for its name, category and `counts_as_interaction`, saves it for the whole account and selects it for the activity in progress. Its remaining settings can be completed later.
- Icon, colour and description are optional. Their absence uses a neutral presentation rather than blocking creation.
- When the type has a template version, it uses the same page, section and field hierarchy as a contact or entity. Every activity keeps the exact version used when it was created, so changing the type's fields never changes an old activity.
- `counts_as_interaction` is the only thing feeding inactivity, and nothing else may. Section 9.3 states it as an exclusive rule, and honouring it exactly is what keeps the inactivity reminder trustworthy.
- Changing `counts_as_interaction` recomputes `last_activity_at` on every contact touched by activities of that type, in the background. The change is not retroactive to the user's intent, but the derived value has to stay true.
- Disabling a type never breaks the activities already using it (9.3). They keep their type, their icon and their fields.
- The account is seeded with a usable set at creation. Supplied types may be renamed, reordered or disabled, but not deleted.
- A user created type may be deleted only when no activity uses it. Otherwise it must be disabled.
- One activity has one type. Overlap belongs in custom fields or later tags, not in several type links.
