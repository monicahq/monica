# 012. Template

**Status:** to create
**Spec:** 5.2, 5.3, 6, 7, 9.3

## What it is

The definition of a record: what a contact page shows, what an Animal holds, what a Restaurant activity asks for. It is what gets applied to a record, and what decides what appears on it.

The template itself is a container with an identity. It holds no fields. What it holds is versions ([TemplateVersion](013-template-version.md)), and a version is an ordered assembly of section versions ([TemplateVersionSection](016-template-version-section.md)). The split exists because of section 6: a published version is frozen forever, and a record points at a precise version rather than at "the latest". The stable identity has to live somewhere, and that somewhere is this model.

Three things can be templated in the first version: a contact, an entity of a given type, and an activity of a given nature (9.3). The activity case is what lets somebody design a good mountain hike log or a good medical appointment log and publish it as is.

## Fields

```
id                          int                             primary key
account_id                  int                             account the template belongs to
applies_to                  enum (contact, entity, activity)  what kind of record the template describes
entity_type_id              int                             type it applies to when applies_to is entity, null otherwise
name                        string (255)                    name the user gave it, null when it falls back to its translation key
name_translation_key        string (255)                    translation key of a supplied name
description                 text                            what the template is for, shown in the picker and on the marketplace
slug                        string (64)                     stable identifier used by the API, unique within the account
is_system                   boolean                         whether the product supplied it at account creation
is_active                   boolean                         whether it can still be applied to a new record
published_version_id        int                             the version offered by default when applying it, null while only drafts exist
locale                      string (5)                      language the labels inside it are written in
installed_from_listing_version_id  int                       marketplace version it was copied from, null when it was built here
position                    int                             order the templates appear in the picker
created_at                  datetime
updated_at                  datetime
deleted_at                  datetime
```

## Relationships

- `belongsTo` [Account](001-account.md)
- `belongsTo` [EntityType](011-entity-type.md)
- `hasMany` [TemplateVersion](013-template-version.md)
- `belongsTo` [TemplateVersion](013-template-version.md) as the published one
- `hasOne` [MarketplaceListing](045-marketplace-listing.md) when it is published
- `belongsTo` [MarketplaceListingVersion](046-marketplace-listing-version.md) as its origin when it was installed

## Rules

- A template does not point at an activity type. The activity type points at its template version instead ([ActivityType](035-activity-type.md)). Pointing both ways would be a cycle, and this direction is the one the reading path needs.
- `locale` is recorded because a template written by one person is written in one language (3.7). The marketplace shows it and filters on it, and an installer may rename everything freely because what they installed is a copy.
- A template is private by default (7). Publishing is a separate, deliberate act.
- Deleting a template is refused while any record points at one of its versions. The template is deactivated instead, which stops it being applied to anything new (3.3).
- Every account is seeded with one contact template, so a contact page is usable before anybody opens the builder (2.1).

## Open questions

- Whether a template can carry several translations of its labels, or stays frozen in its author's language. Section 16 asks it. The columns here assume one language per template, which is the simpler answer and the one section 3.7 currently describes. Supporting several would move labels out of [Field](017-field.md) into a translation table, and that change is contained enough to defer.
