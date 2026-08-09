# 012. Template

**Status:** exists (`app/Models/Template.php`), built as far as the models it depends on allow
**Spec:** 5.2, 5.3, 6, 7, 9.3

## What it is

The definition of a record: what a contact page shows, what an Animal holds, what a Restaurant activity asks for. It is what gets applied to a record, and what decides what appears on it.

The template itself is a container with an identity. It holds no fields. What it holds is versions ([TemplateVersion](013-template-version.md)), and a version is an ordered assembly of section versions ([TemplateVersionSection](016-template-version-section.md)). The split exists because of section 6: a published version is frozen forever, and a record points at a precise version rather than at "the latest". The stable identity has to live somewhere, and that somewhere is this model.

Three things can be templated in the first version: a contact, an entity of a given type, and an activity of a given nature (9.3). The activity case is what lets somebody design a good mountain hike log or a good medical appointment log and publish it as is.

## Fields

```
id                          int                             primary key
uuid                        string (36)                     unguessable identifier used by the API, alongside the readable slug
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

## Not done yet

- **The activity case.** `applies_to` offers `contact` and `entity` and nothing else. An activity template is reached from [ActivityType](035-activity-type.md), which does not exist, so a template applying to one would be a template nothing could ever read. The enum case arrives with the model that reads it.
- **Everything the marketplace needs.** `installed_from_listing_version_id` and the `hasOne` [MarketplaceListing](045-marketplace-listing.md) are not in the schema. Both point at tables that do not exist, and adding a nullable column with no foreign key would only look like support for something absent.
- **Nothing reads the seeded contact template yet.** `CreateAccount` seeds it and the builder renders it, but [Contact](019-contact.md), [FieldValue](022-field-value.md) and the renderer are all still ahead, so no record has been made with it. The vault does not point at it either: `VaultDefaultTemplate` does not exist, and until it does nothing says which published version a new contact lands on.
- **Nothing moves `record_count` on any version.** As with `entity_count` on [EntityType](011-entity-type.md), the column is written once as zero. `DestroyTemplate` refuses against the sum of it across the versions, which is the only thing there is to ask until records exist.
- **No API.** The screens exist: the list that groups templates into what somebody built and what the product supplied, the form that names one, and the builder. Nothing is exposed over the API yet.
- **No record preview.** The builder cannot show what a record made with the template would look like, because the renderer and [Contact](019-contact.md) are both still ahead.

## Decisions taken while building it

- **A template is created with its first draft version.** A template with no version at all is a name nothing can be done with, so `CreateTemplate` writes both in one transaction. The version is empty and unpublished, which is exactly the state a template somebody has just named should be in.
- **The slug follows the rules already settled for [EntityType](011-entity-type.md).** Choosable, cleaned the same way whether given or derived, counted up for uniqueness within the account, never rebuilt on a rename, and not released by a soft delete. The three places that now need this share one helper rather than three copies of the loop.
- **`applies_to` and `entity_type_id` cannot be changed after creation.** `UpdateTemplate` touches the name, the description and the locale only. Pointing a template at a different kind of record would leave every record already on it rendering something never meant for it, which is a new template rather than an edit.
- **Duplicating copies one version and always produces a draft.** The copy is an ordinary template of the user's from the first moment, never marked as supplied. Nothing is published on their behalf: publishing is the moment to notice what needs changing, and skipping it would defeat the point of copying.
- **The template an account starts with is published, and asks for nothing.** A draft is a template no record can be made from, so an account whose only template has to be published first is an account that starts with homework. Nothing on it is required either: a contact met once is worth writing down with only a name, and a form that refuses a birthday nobody knows is a form people stop using. It is seeded as three ordinary sections carrying seven ordinary fields, marked as supplied and editable from the first moment like anything else.
- **Seeding it is a job, not part of creating the account.** Eleven rows nobody is looking at yet have no business holding the sign-up request open. `CreateAccount` dispatches `CreateDefaultTemplate` once its own transaction has committed, so a worker reaching for the user cannot arrive before the user exists. The value lists stay synchronous, since the template reads two of them.
- **A copy carries the same section versions, not copies of them.** A section version is frozen and can sit in any number of template versions, so duplicating a template is cheap and changing a shared section still means drafting a new version of that section.

## Open questions

- Whether a template can carry several translations of its labels, or stays frozen in its author's language. Section 16 asks it. The columns here assume one language per template, which is the simpler answer and the one section 3.7 currently describes. Supporting several would move labels out of [Field](017-field.md) into a translation table, and that change is contained enough to defer.
