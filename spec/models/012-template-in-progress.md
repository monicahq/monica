# 012. Template

**Status:** exists (`app/Models/Template.php`), built as far as the models it depends on allow
**Spec:** 5.2, 5.3, 6, 7, 9.3

## What it is

The definition of a record: what a contact page shows, what an Animal holds, what a Restaurant life event asks for. It is what gets applied to a record, and what decides what appears on it.

The template itself is a container with an identity. It holds no pages, sections or fields directly, and since the engine was made generic it holds no versions either. What it holds is a [Structure](056-structure-done.md), and the structure holds the versions ([StructureVersion](013-structure-version-done.md)). A version contains ordered pages ([StructureVersionPage](055-structure-version-page-done.md)), and those pages contain ordered section versions through [StructureVersionSection](016-structure-version-section-done.md). The split exists because of section 6: a published version is frozen forever, and a record points at a precise version rather than at "the latest". The stable product identity has to live somewhere, and that somewhere is this model; what the engine needs lives on the structure.

Three things can be templated in the first version: a contact, an entity of a given type, and a life event of a given nature (9.3). The life event case is what lets somebody design a good mountain hike log or a good medical appointment log and publish it as is.

## Fields

```
id                          int                             primary key
uuid                        string (36)                     unguessable identifier used by the API, alongside the readable slug
account_id                  int                             account the template belongs to
name                        string (255)                    name the user gave it, null when it falls back to its translation key
name_translation_key        string (255)                    translation key of a supplied name
description                 text                            what the template is for, shown in the picker and on the marketplace
slug                        string (64)                     stable identifier used by the API, unique within the account
is_system                   boolean                         whether the product supplied it at account creation
is_active                   boolean                         whether it can still be applied to a new record
locale                      string (5)                      language the labels inside it are written in
installed_from_listing_version_id  int                       marketplace version it was copied from, null when it was built here
position                    int                             order the templates appear in the picker
created_at                  datetime
updated_at                  datetime
deleted_at                  datetime
```

## Relationships

- `belongsTo` [Account](001-account-in-progress.md)
- `morphOne` [Structure](056-structure-done.md), which carries its versions, its published version, its `applies_to` and its entity type
- `hasOne` [MarketplaceListing](045-marketplace-listing-done.md) when it is published
- `belongsTo` [MarketplaceListingVersion](046-marketplace-listing-version-done.md) as its origin when it was installed

## Rules

- A template does not point at a life event type and never did. The two are siblings now: each owns a [Structure](056-structure-done.md) and neither knows the other exists.
- A template owns exactly one structure. Deleting a template is soft, and the structure stays: a soft deleted template is restorable and has to come back with its fields. What must never be left behind is a structure whose owner is gone for good, and the only path to that today is the account being deleted, which the database handles through `structures.account_id`. A force delete of a single template would need to take the structure with it, and nothing does one.
- `applies_to` and `entity_type_id` are read through the structure. They moved there so that a version can say which kind of record it describes without anybody asking who owns it, which is what moving a record to another version and resolving a vault default both need.
- `locale` is recorded because a template written by one person is written in one language (3.7). The marketplace shows it and filters on it, and an installer may rename everything freely because what they installed is a copy.
- A template is private by default (7). Publishing is a separate, deliberate act.
- Deleting a template is refused while any record points at one of its versions. The template is deactivated instead, which stops it being applied to anything new (3.3).
- Every account is seeded with one contact template, so a contact page is usable before anybody opens the builder (2.1).

## Not done yet

- **The life event case left this model.** `applies_to` now lives on the structure and offers `life event` as well, which is what an [LifeEventType](035-life-event-type-in-progress.md) structure carries. A template is never the thing that applies to a life event.
- **Everything the marketplace needs.** `installed_from_listing_version_id` and the `hasOne` [MarketplaceListing](045-marketplace-listing-done.md) are not in the schema. Both point at tables that do not exist, and adding a nullable column with no foreign key would only look like support for something absent.
- **No API.** The screens exist: the list that groups templates into what somebody built and what the product supplied, the form that names one, and the builder. Nothing is exposed over the API yet.
- **No record preview.** The builder shows the structure being edited, and not what a record made from it would look like. The record renderer exists and is not reachable from here, so somebody building a template still finds out what they built by opening somebody.
- **Nothing tells a template who is on it.** `record_count` moves as records are written down, deleted, brought back and moved between versions, and `DestroyTemplate` refuses against the sum of it across the versions. What no screen offers is the list behind the number: a version says how many records it holds and never which, so there is no way from a template to the people it describes.

## Decisions taken while building it

- **A template is created with its first draft version and first page.** A template with no version at all is a name nothing can be done with, and a version with no page cannot be published. `CreateTemplate` writes all three in one transaction. The version contains an empty "General" page and is unpublished, which is exactly the state a template somebody has just named should be in.
- **The slug follows the rules already settled for [EntityType](011-entity-type-done.md).** Choosable, cleaned the same way whether given or derived, counted up for uniqueness within the account, never rebuilt on a rename, and not released by a soft delete. The three places that now need this share one helper rather than three copies of the loop.
- **`applies_to` and `entity_type_id`, now on the structure, cannot be changed after creation.** `UpdateTemplate` touches the name, the description and the locale only. Pointing a template at a different kind of record would leave every record already on it rendering something never meant for it, which is a new template rather than an edit.
- **Duplicating copies one version and always produces a draft.** The copy includes new page rows with the same keys, labels, order and section placement. It is an ordinary template of the user's from the first moment, never marked as supplied. Nothing is published on their behalf: publishing is the moment to notice what needs changing, and skipping it would defeat the point of copying.
- **The template an account starts with is published, and asks for nothing.** A draft is a template no record can be made from, so an account whose only template has to be published first is an account that starts with homework. Nothing on it is required either: a contact met once is worth writing down with only a name, and a form that refuses a birthday nobody knows is a form people stop using. It is seeded with three pages, General, Ways to connect and Information, carrying six ordinary sections and fourteen ordinary fields between them, marked as supplied and editable from the first moment like anything else. Two of those fields are repeaters, since a contact carries as many documents and as many photos as they carry: each repetition is a file and a line saying what it is. A page is seeded only when there is something to put on it: relationships, pets, groups, life events, goals, reminders, loans, tasks, calls and journal entries are each their own model with their own screens, and a tab named after one of them would open on nothing.
- **Seeding it is a job, not part of creating the account.** Eleven rows nobody is looking at yet have no business holding the sign-up request open. `CreateAccount` dispatches `CreateDefaultTemplate` once its own transaction has committed, so a worker reaching for the user cannot arrive before the user exists. The value lists stay synchronous, since the template reads two of them.
- **A copy carries the same section versions, not copies of them.** Its page rows are new because they belong to one structure version, but the frozen section versions placed on those pages are reused. Duplicating a template stays cheap, and changing a shared section still means drafting a new version of that section.

## Open questions

- Whether a template can carry several translations of its labels, or stays frozen in its author's language. Section 16 asks it. The columns here assume one language per template, which is the simpler answer and the one section 3.7 currently describes. Supporting several would move labels out of [Field](017-field-done.md) into a translation table, and that change is contained enough to defer.
