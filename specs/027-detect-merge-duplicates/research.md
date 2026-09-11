# Research: Detect and merge duplicate contacts

Schema in [data-model.md](data-model.md), surfaces in [contracts/duplicates-and-merges.md](contracts/duplicates-and-merges.md).

## 1. Where the identifying mark lives

**Decision**: a `is_identifying` boolean on `fields`.
**Rationale**: `fields.section_version_id` already ties a field to one section version, and a published
version is frozen, so the mark is versioned by the mechanism that exists and changing it goes through
`CreateSectionVersionDraft` and `PublishSectionVersion` like any other field change.
**Alternatives**: reuse `fields.is_unique`, rejected because refusing a shared value is wrong where
proposing a pair is right, a household legitimately sharing one phone number. A per-vault setting,
rejected because which field identifies a record is a property of the structure.

## 2. Name similarity across three database drivers

**Decision**: a `match_key` column on `contacts`, holding the folded last name, or the only name token
when there is one. Detection groups a vault by it and compares full folded names inside a group in PHP.
**Rationale**: the suite runs on SQLite, MySQL and PostgreSQL and the constitution forbids unguarded
driver-specific SQL. Blocking in SQL and comparing in PHP needs no guard. The column is written in the
`saving` hook that already computes `sort_key` and `search_key`.
**Alternatives**: `pg_trgm`, `SOUNDEX` or `difference()`, rejected as driver-specific. A `LIKE` sweep
of the vault per contact, rejected as unindexable and quadratic against SC-004.
**Known limit**: the given names are what get compared, the block having already fixed the family
name, so a nickname that is no spelling of the name is missed, "Jim" beside "James", as is a pair
whose family names differ. The identifying value catches both, which is why there are two signals.

## 3. Matching an identifying value

**Decision**: group non-detached `field_values` by vault, field and `value_string`, keeping groups of
two or more. Only the types stored in `value_string` take part, which is where email and phone land.
**Rationale**: portable SQL on an index that exists, `field_values_filter_string_index`.
**Alternatives**: comparing every value column, rejected since a shared birthday is not evidence.

## 4. When detection runs

**Decision**: one job, `DetectDuplicateContacts`, constructed with a `Contact` or a `Vault`. The
contact form is dispatched by `CreateContact` and `UpdateContactName`, the vault form scheduled
nightly on the `low` queue beside the two sweeps already there.
**Rationale**: FR-006 keeps detection off the save. The contact form costs one block and one value
lookup. The nightly form is what notices a vault whose template gained an identifying field, since no
contact is written at that moment.
**Alternatives**: computing candidates when the screen opens, rejected against SC-004. Two jobs,
rejected under Principle II, the union constructor being the shape `MoveRecordToTemplateVersion` uses.

## 5. Two rows that need no column of their own

**Decision**: a dismissal is recorded on the candidate row, and the sweep never deletes a dismissed
row. The absorbed contact's address is resolved by looking the trashed contact up in `contact_merges`.
**Rationale**: one table and one `where` clause for the first, and for the second one indexed lookup
on a path that would otherwise be a 404.
**Alternatives**: a second table for dismissals, and a `merged_into_contact_id` column on `contacts`.
Both rejected under Principle II, the column also as a second source of truth an undo must maintain.

## 6. How an undo works

**Decision**: the merge writes a ledger of what it moved and the undo replays it backwards, then
restores the absorbed contact. The survivor's snapshot is taken first and kept as the verifiable state
before the merge, not as the mechanism of the undo.
**Rationale**: a snapshot describes one record, so replaying the survivor's would rebuild the survivor
and leave the absorbed contact's rows on it. Nothing restores a snapshot today and writing that is a
larger feature than this one.
**Alternatives**: `RestoreRecordSnapshot`, rejected for that reason. It stays worth building, and the
snapshot taken here is what it would read.

## 7. Where the preview is computed

**Decision**: an action, `PreviewContactMerge`, returning a documented array shape, read by the view
model for Blade and by the controller for JSON.
**Rationale**: Principle I forbids a view model being consumed anywhere but Blade, Principle X forbids
either surface holding a rule the other lacks, and both need the same answer about what conflicts.
**Alternatives**: computing it in the view model, rejected because the API cannot read it. A value
object, rejected because the repository layout is fixed and holds no folder for one.

## 8. What this leaves for `spec/`

**Decision**: update `spec/models/034-contact-merge-done.md` in this pull request.
**Rationale**: Governance requires an open question answered in `spec/` before code depends on it.
This answers two, entities are out and an undo lasts while the absorbed contact is retained, and
replaces that model's `relationship_resolutions` with one `resolutions` column, relationships being absent.
