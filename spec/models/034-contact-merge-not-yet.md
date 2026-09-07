# 034. ContactMerge

**Status:** to create
**Spec:** 8.3, 15, 3.3

## What it is

The record of two contacts being merged into one, kept so the merge can be explained and undone.

Section 15 puts duplicate detection and merging in the first version, with preview, conflict resolution, relationship transfer and full history preservation. Transferring relationships may place two identical explicit rows side by side, so the preview has to expose that collision rather than guess what the user meant.

That is too much to do without a trace. This model is the trace.

## Fields

```
id                          int                             primary key
account_id                  int                             account the merge happened in
vault_id                    int                             vault both contacts lived in
kept_contact_id             int                             contact that survived the merge
merged_contact_id           int                             contact that was absorbed, kept soft deleted rather than destroyed
resolution                  json                            which value was kept for every field where the two disagreed
transferred                 json                            what moved across, the relationships, activities, groups, files and reminders
relationship_resolutions    json                            user decisions for relationship collisions created by the merge
snapshot_id                 int                             snapshot of the surviving contact taken before the merge
merged_by_user_id           int                             user who performed it, null when an import did
reverted_at                 datetime                        when it was undone, null while it stands
created_at                  datetime
updated_at                  datetime
```

## Relationships

- `belongsTo` [Account](001-account-in-progress.md), [Vault](007-vault-in-progress.md)
- `belongsTo` [Contact](019-contact-done.md) twice, kept and merged
- `belongsTo` [RecordSnapshot](023-record-snapshot-done.md), [User](002-user-done.md)

## Rules

- The absorbed contact is soft deleted, never destroyed (3.3). Every identifier that pointed at it keeps resolving, and the page redirects to the survivor.
- A snapshot of the survivor is taken before anything is written, so the whole operation can be undone as a unit.
- Everything moves: relationships, group memberships, activity participations, field values, files, reminders, share links.
- Relationship collisions are shown in the preview. The user chooses whether to keep both rows or remove an exact duplicate, and that choice is recorded in `relationship_resolutions`. Different types or dates are never combined automatically.
- `Moi` is never absorbed (4.3). It can absorb another contact, never the reverse.
- A merge is previewed before it runs. Conflicting values are shown side by side and the user chooses. Nothing is resolved silently.
- The merge writes to [Log](004-log-in-progress.md) and emits a webhook (14.2).

## Open questions

- How long a merge stays revertible. Reverting requires the absorbed contact to still be soft deleted and the files it held to still exist, which ties it to the same retention decisions as [RecordSnapshot](023-record-snapshot-done.md) and [File](008-file-in-progress.md).
- Whether entities can be merged too. The specification only discusses contacts, and duplicate companies are exactly as likely as duplicate people.
