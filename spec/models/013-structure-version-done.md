# 013. StructureVersion

**Status:** exists (`app/Models/StructureVersion.php`)
**Spec:** 6, 3.1, 7

## What it is

One frozen state of a [Structure](056-structure-done.md). This model is the whole answer to the question section 6 opens: if everybody can change structures, how do records stay stable?

Two rules govern it. A published version never changes again, ever, and any edit produces a new version beside it with its own change note. A record points at a precise version and follows it until somebody deliberately moves it, which takes a snapshot first.

A version stays freely editable while it is a draft. Freezing at every keystroke during design would produce hundreds of versions nobody asked for, so freezing happens at publication.

It belongs to a structure and not to a template. That is the change: the version used to name the template it was part of, which made it impossible for anything but a template to have fields. Its owner is now whatever owns the structure, and this model does not know or care which. It depends on model 056, which is numbered after it, for [the reason that file gives](056-structure-done.md#where-it-sorts).

## Fields

```
id                          int                             primary key
account_id                  int                             account the version belongs to
structure_id                int                             structure this is a version of
version_number              int                             incrementing number within the structure, starting at 1
status                      enum (draft, published, retired)  draft is editable, published is frozen, retired is no longer offered
change_note                 text                            what changed since the previous version, shown to anybody deciding whether to move
published_at                datetime                        when it was frozen, null while it is a draft
retired_at                  datetime                        when it stopped being offered, null while it is still offered
previous_version_id         int                             version it was created from, null for the first one
record_count                int                             how many records currently point at it, so the cost of retiring it is visible
created_at                  datetime
updated_at                  datetime
```

## Relationships

- `belongsTo` [Account](001-account-in-progress.md)
- `belongsTo` [Structure](056-structure-done.md)
- `belongsTo` self as the previous version
- `hasMany` [StructureVersionPage](055-structure-version-page-done.md)
- `hasMany` [StructureVersionSection](016-structure-version-section-done.md)
- referenced by [Contact](019-contact-done.md), [Entity](020-entity-done.md) and [Activity](036-activity-not-yet.md)

An [ActivityType](035-activity-type-in-progress.md) does not reference a version. It owns a structure, and the structure says which version is published. That is the direction that lets a type change its fields without every activity following.

## Rules

- A published version is immutable. Not "should not be edited": the application refuses the write, and every code path that could reach it goes through a check. This is the load bearing rule of section 6 and everything else in the personalisation engine leans on it.
- Immutability cascades. A published version pins its pages, their order, the placement of sections on them and the exact section versions they contain. Those section versions pin their fields. Renaming a value list item elsewhere cannot change what a section published yesterday does. Where a resolved copy is needed to make that true, [SectionVersion](015-section-version-in-progress.md) says so.
- Every version contains at least one [StructureVersionPage](055-structure-version-page-done.md). It may contain any number above one. A version with no page cannot be published.
- A page may hold no sections, and a version of one empty page is publishable. That is what an activity type starts with before anybody opens its field builder, and it renders a record carrying no custom fields rather than failing to render.
- Editing a published version creates a draft copied from it, with `previous_version_id` set. Publishing that draft is a separate act, and it requires a change note.
- A record never follows "latest". Moving a record to another version is deliberate, per record or in bulk, and it always writes a [RecordSnapshot](023-record-snapshot-done.md) first so it can be undone (6).
- A record only ever moves between versions of one structure. `applies_to` is a property of the structure, so a contact cannot land on a version describing an entity, and the check needs no knowledge of who owns what.
- Retiring a version stops it being applied to anything new and leaves every record already on it exactly as it is.
- A version is never deleted while `record_count` is above zero, and a published version is never deleted at all. A draft can be deleted freely.

## Not done yet

- **Records move between versions one at a time, and only a contact does.** `MoveRecordToStructureVersion` writes the [RecordSnapshot](023-record-snapshot-done.md) first, moves the record and hands `record_count` from one version to the other. A contact reaches it two ways: the banner offering the version its template publishes now, and the line of the version history that goes back to an earlier published one. [Entity](020-entity-done.md) has no screen to reach it from, and [Activity](036-activity-not-yet.md) is still ahead.
- **Nothing moves a whole version's worth of records at once.** Publishing moves nobody, deliberately, and there is no way to say "and bring everybody with it" for an account that wants exactly that.
- **The API reaches versions through their template only.** `/templates/{template}/versions/{version}` is the whole surface. A structure owned by an activity type has no path yet, and it gets one with the activity type screens rather than here.
- **Retiring a version has no screen.** The action exists and nothing calls it: a version is retired when a template stops being offered, and that decision is taken on the template rather than the version. The version history already says a retired version cannot be gone back to, so the sentence exists before the thing that would produce one.

## Decisions taken while building it

- **The freeze is enforced on the model, not in the actions.** A `updating` guard refuses any change to what the version contains once its status is published, and a `deleting` guard refuses the deletion of anything that is not a draft. The specification says the application refuses the write and that every code path goes through a check; one guard below the actions is the only way to make that true, rather than trusting twenty-eight actions and every future one to remember.
- **What publication freezes is listed explicitly.** The version number, the change note, the publication date, the lineage and the account are frozen. The status, the retirement date and `record_count` are not: retiring a version and counting the records on it describe where the version stands rather than what it renders, and both have to keep working after publication.
- **Version numbers stay per structure.** A template owns exactly one structure, so a template's version numbers are its structure's version numbers and the version history screen and the API paths keep their meaning unchanged.
