# 013. TemplateVersion

**Status:** exists (`app/Models/TemplateVersion.php`), built as far as the models it depends on allow
**Spec:** 6, 3.1, 7

## What it is

One frozen state of a template. This model is the whole answer to the question section 6 opens: if everybody can change structures, how do records stay stable?

Two rules govern it. A published version never changes again, ever, and any edit produces a new version beside it with its own change note. A record points at a precise version and follows it until somebody deliberately moves it, which takes a snapshot first.

A version stays freely editable while it is a draft. Freezing at every keystroke during design would produce hundreds of versions nobody asked for, so freezing happens at publication.

## Fields

```
id                          int                             primary key
account_id                  int                             account the version belongs to
template_id                 int                             template this is a version of
version_number              int                             incrementing number within the template, starting at 1
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
- `belongsTo` [Template](012-template-in-progress.md)
- `belongsTo` self as the previous version
- `hasMany` [TemplateVersionPage](055-template-version-page-done.md)
- `hasMany` [TemplateVersionSection](016-template-version-section-done.md)
- referenced by [Contact](019-contact-done.md), [Entity](020-entity-done.md), [ActivityType](035-activity-type-not-yet.md) and [Activity](036-activity-not-yet.md)

## Rules

- A published version is immutable. Not "should not be edited": the application refuses the write, and every code path that could reach it goes through a check. This is the load bearing rule of section 6 and everything else in the personalisation engine leans on it.
- Immutability cascades. A published version pins its pages, their order, the placement of sections on them and the exact section versions they contain. Those section versions pin their fields. Renaming a value list item elsewhere cannot change what a section published yesterday does. Where a resolved copy is needed to make that true, [SectionVersion](015-section-version-in-progress.md) says so.
- Every version contains at least one [TemplateVersionPage](055-template-version-page-done.md). It may contain any number above one. A template version with no page cannot be published.
- Editing a published version creates a draft copied from it, with `previous_version_id` set. Publishing that draft is a separate act, and it requires a change note.
- A record never follows "latest". Moving a record to another version is deliberate, per record or in bulk, and it always writes a [RecordSnapshot](023-record-snapshot-done.md) first so it can be undone (6).
- Retiring a version stops it being applied to anything new and leaves every record already on it exactly as it is.
- A version is never deleted while `record_count` is above zero, and a published version is never deleted at all. A draft can be deleted freely.

## Not done yet

- **Records move between versions one at a time, and only a contact does.** `MoveRecordToTemplateVersion` writes the [RecordSnapshot](023-record-snapshot-done.md) first, moves the record and hands `record_count` from one version to the other. A contact reaches it two ways: the banner offering the version the template publishes now, and the line of the version history that goes back to an earlier published one. [Entity](020-entity-done.md) has no screen to reach it from, and [ActivityType](035-activity-type-not-yet.md) is still ahead.
- **Nothing moves a whole version's worth of records at once.** Publishing moves nobody, deliberately, and there is no way to say "and bring everybody with it" for an account that wants exactly that.
- **No API.** The builder starts a draft, publishes a version and throws a draft away. None of it is exposed over the API yet.
- **Retiring a version has no screen.** The action exists and nothing calls it: a version is retired when a template stops being offered, and that decision is taken on the template rather than the version. The version history already says a retired version cannot be gone back to, so the sentence exists before the thing that would produce one.

## Decisions taken while building it

- **The freeze is enforced on the model, not in the actions.** A `updating` guard refuses any change to what the version contains once its status is published, and a `deleting` guard refuses the deletion of anything that is not a draft. The specification says the application refuses the write and that every code path goes through a check; one guard below the actions is the only way to make that true, rather than trusting twenty-eight actions and every future one to remember.
- **What publication freezes is listed explicitly.** The version number, the change note, the publication date, the lineage and the account are frozen. The status, the retirement date and `record_count` are not: retiring a version and counting the records on it describe where the version stands rather than what it renders, and both have to keep working after publication.
- **Publishing refuses while the assembly still carries a draft section version.** Freezing a template version that points at a section somebody is still editing would freeze nothing. It also refuses a version with no page or a section placement whose page belongs to another version. The refusal is explicit rather than quietly repairing or publishing structures on the user's behalf, which section 3.1 rules out.
- **Drafting copies the page structure as well as the sections.** Every copied page gets a new UUID but keeps its stable key, label and position. Section placements are then copied onto the corresponding new pages. This preserves what the record renders without making the draft share mutable page rows with the published version.
- **A change note is required from the second version on.** The first version has nothing to have changed from, so asking for a note there would only teach people to type something meaningless.
- **Retiring the version a template offers clears `published_version_id`.** A retired version cannot go on being what a new record is handed. The template is then left offering nothing until something else is published, which is deliberate and visible.
- **`record_count` is true rather than merely written.** Every path that gives a record to a version or takes one away moves it: writing a record down, deleting one, bringing one back, moving one to another version, and deleting or restoring a whole vault. That is what lets the guard on deletion ask a column instead of counting rows across two record tables on every attempt.

## Open questions

- Whether moving a record to a newer version can be automated for the safe cases (a version that only adds fields cannot lose data). Section 3.1 says nothing changes without acceptance, which suggests offering it rather than doing it. A "this update only adds fields" hint on the accept screen would make the choice easy without taking it.
