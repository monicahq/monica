# 035. ActivityType

**Status:** to create
**Spec:** 9.3, 9.5, 10.2, 7

## What it is

The nature of an activity: lunch, phone call, concert, medical appointment, argument, walk.

Section 3.2 freezes the concept of an activity and explicitly leaves its nature open, and section 9.3 is where the personalisation comes back. A type carries three things, and the middle one is the load bearing one.

It carries a label, an icon and a colour, so the journal reads at a glance. It carries whether it counts as a real interaction with the participants: lunch counts, a phone call counts, "I thought about them" and "I saw a photo of her" do not. That flag alone drives the inactivity reminder (10.2), which section 10.2 calls the most important reminder in a personal CRM and the one most absent from existing tools. And it carries its own structure of fields, built with the same engine as contact pages, which is what lets a Restaurant type ask for the venue, the dish and a rating while a Call type asks only for a duration.

Because it is built on the same engine, an activity type is publishable on the marketplace with the same versioning and installation rules as anything else (7).

## Fields

```
id                          int                             primary key
uuid                        string (36)                     unguessable identifier used by the API
account_id                  int                             account the type belongs to
key                         string (64)                     stable identifier of a supplied type, null for a user made one
name                        string (255)                    label of the type, null when it falls back to its translation key
name_translation_key        string (255)                    translation key of a supplied label
description                 string (255)                    what it covers, shown when picking a type
icon                        string (64)                     icon shown on every activity of this type in the journal
colour                      string (7)                      hex colour used the same way
counts_as_interaction       boolean                         whether it counts as real contact with the participants, which drives inactivity reminders
template_version_id         int                             structure of the extra fields it asks for, null when it asks for none
requires_participants       boolean                         whether at least one participant must be named
default_duration_in_minutes int                             duration proposed when creating one, null when there is no sensible default
is_system                   boolean                         whether the product supplied it
is_active                   boolean                         whether it can still be chosen for a new activity
position                    int                             order it appears in the picker
usage_count                 int                             how many activities use it, so the cost of disabling it is visible
created_at                  datetime
updated_at                  datetime
deleted_at                  datetime
```

## Relationships

- `belongsTo` [Account](001-account-in-progress.md)
- `belongsTo` [TemplateVersion](013-template-version-done.md)
- `hasMany` [Activity](036-activity-not-yet.md)
- `hasOne` [MarketplaceListing](045-marketplace-listing-done.md) when published

## Rules

- The type points at its template version, not the reverse ([Template](012-template-in-progress.md)). This is the direction that avoids a cycle and matches how the page is read.
- When the type has a template version, it uses the same page, section and field hierarchy as a contact or entity. A simple activity normally needs one page and therefore no visible tabs. A richer activity with several pages uses the same accessible navigation contract (5.2).
- `counts_as_interaction` is the only thing feeding inactivity, and nothing else may. Section 9.3 states it as an exclusive rule, and honouring it exactly is what keeps the inactivity reminder trustworthy.
- Changing `counts_as_interaction` recomputes `last_activity_at` on every contact touched by activities of that type, in the background. The change is not retroactive to the user's intent, but the derived value has to stay true.
- Disabling a type never breaks the activities already using it (9.3). They keep their type, their icon and their fields.
- The account is seeded with a usable set at creation, and every part of it is editable.
- Deleting a type is refused while `usage_count` is above zero.

## Open questions

- Whether an activity can carry several types (a lunch that is also a business meeting). One type per activity is what the specification describes and it keeps the journal legible. Tags would cover the overlap case if it turns out to matter.
