# 056. Structure

**Status:** exists (`app/Models/Structure.php`)
**Spec:** 5.2, 6, 7, 9.3

## Where it sorts

This model is depended on by models 012 to 018, which are numbered before it. That breaks the
dependency order [the README](README.md) sets, and it does so for the reason model 055 gives: the
numbers are referenced throughout the repository and renumbering an established engine costs more
than an out of order file explained in one paragraph. Structure takes the next unused number.

Read it before 012. Its table is created before every table that points at it.

## What it is

The stable container for one field structure. It holds no fields and no pages of its own. It says
which kind of record it describes, which of its versions is currently published, and who owns it.

It exists because the engine had been named after one of its callers. A contact carried a
`template_version_id`, a page belonged to a template version, a section placement was a template
version section. That was accurate while a template was the only thing with fields. An
[ActivityType](035-activity-type-in-progress.md) needs the same engine for its custom fields (9.3), and
naming the engine after a template left two bad options: an activity type borrows a
[Template](012-template-in-progress.md) it is not, or a second field engine grows beside the first,
which [the constitution](../../.specify/memory/constitution.md) principle VII forbids outright.

So the product concept and the technical one are separated. Template stays what a user applies to a
contact or an entity, and it is the word every screen keeps. Structure is what the engine is, and no
user sees it.

## Fields

```
id                          int                             primary key
uuid                        string (36)                     unguessable identifier used by the API
account_id                  int                             account the structure belongs to
owner_type                  string (255)                    class of the thing that owns it, a template or an activity type
owner_id                    int                             id of that owner
applies_to                  enum (contact, entity, activity)  kind of record it describes
entity_type_id              int, nullable                   type it applies to when applies_to is entity, null otherwise
published_version_id        int, nullable                   version offered to a new record, null while only drafts exist
created_at                  datetime
updated_at                  datetime
```

`owner_type` and `owner_id` are unique together.

## Relationships

- `belongsTo` [Account](001-account-in-progress.md), [EntityType](011-entity-type-done.md)
- `morphTo` its owner, a [Template](012-template-in-progress.md) or an
  [ActivityType](035-activity-type-in-progress.md)
- `belongsTo` [StructureVersion](013-structure-version-done.md) as the published one
- `hasMany` [StructureVersion](013-structure-version-done.md)

## Rules

- A structure has exactly one owner and an owner has exactly one structure. The first half is carried
  by the two columns being required, the second by the unique index on them.
- The owner is a morph, and a morph takes no foreign key, so two rules the database would otherwise
  enforce live elsewhere.
- The first is that a structure never outlives its owner for good. Deleting a template or an activity
  type is soft, and the structure deliberately stays, because a restored owner has to come back with
  its fields. The only deletion that is final is the account, and the database handles that through
  `account_id`. Anything that force deletes a single owner has to take the structure with it, and
  nothing does today. Orphaning it silently is what principle VIII calls the worst kind of loss.
- The second is that the owner reads as null once it is soft deleted, because the column can name a
  row the owner's own scope hides. The deleting actions keep that state out of reach: a template
  refuses to go while a record points at one of its versions, and takes the vault defaults naming one
  with it.
- `applies_to` lives here rather than on the owner so that a version can say which kind of record it
  describes without anybody asking who owns it. Moving a record to another version and resolving a
  vault default both need that answer, and neither should have to know that templates and activity
  types exist.
- `published_version_id` is the only place "which version is current" is written. Nothing else caches
  it and nothing derives it by taking the highest number.
- Publishing changes this column and nothing else. Every record already created keeps the version it
  was given (6).
- A structure is never shared between two owners. The alternative was considered and refused: two
  owners sharing a published version makes "publishing affects new records only" ambiguous, since it
  would be unclear whose new records and whose change note.

## Open questions

- Nothing here is settled against a marketplace listing that installs a structure without its owner.
  [MarketplaceListing](045-marketplace-listing-done.md) publishes the owner, so the question does not
  arise today, and it would arise the moment somebody wants to install a bare structure into an
  activity type that already has one.
