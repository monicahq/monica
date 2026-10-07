# 034. ContactMerge

**Status:** exists (`app/Models/ContactMerge.php`), with relationships, groups and life events waiting on the models that carry them
**Spec:** 8.3, 15, 3.3

## What it is

The record of two contacts being merged into one, kept so the merge can be explained and undone.

Section 15 puts duplicate detection and merging in the first version, with preview, conflict resolution, relationship transfer and full history preservation. Transferring relationships may place two identical explicit rows side by side, so the preview has to expose that collision rather than guess what the user meant.

That is too much to do without a trace. This model is the trace.

## Fields

```
id                          int                             primary key
uuid                        string (36)                     unguessable identifier used in URLs and by the API
account_id                  int                             account the merge happened in
vault_id                    int                             vault both contacts lived in
kept_contact_id             int                             contact that survived the merge, carrying no foreign key so the record outlives it
merged_contact_id           int                             contact that was absorbed, kept soft deleted rather than destroyed, no foreign key either
resolutions                 json                            what the user decided at every difference, by field key and repeater row, collisions included
ledger                      json                            what moved, row by row, so the undo can replay it backwards
snapshot_id                 int                             snapshot of the surviving contact taken before the merge, null once that snapshot is pruned
merged_by_user_id           int                             user who performed it, null when an import did
undone_at                   datetime                        when it was undone, null while it stands
undone_by_user_id           int                             user who undid it, null while it stands
created_at                  datetime
updated_at                  datetime
```

`resolutions` and `ledger` are two columns rather than one because a decision belongs to the user and a move is its consequence. Replaying the moves without the decisions would put the rows back and lose the reasoning. What the specification called `transferred` is `ledger`, and `relationship_resolutions` folds into `resolutions` once relationships exist, since a relationship collision is decided the same way a value conflict is.

## Relationships

- `belongsTo` [Account](001-account-in-progress.md), [Vault](007-vault-in-progress.md)
- `belongsTo` [Contact](019-contact-done.md) twice, kept and merged
- `belongsTo` [RecordSnapshot](023-record-snapshot-done.md), [User](002-user-done.md)

## Rules

- The absorbed contact is soft deleted, never destroyed (3.3). Every identifier that pointed at it keeps resolving, and the page redirects to the survivor.
- A snapshot of the survivor is taken before anything is written, so the whole operation can be undone as a unit.
- Everything moves: field values, repeater rows, files, the avatar, reminders, share links, and the relation fields other records pointed at the absorbed contact through. Relationships, group memberships and life event participations join that list when those models exist.
- Collisions are shown in the preview. The user chooses whether to keep both rows or remove an exact duplicate, and that choice is recorded in `resolutions`. Different types or dates are never combined automatically.
- `Moi` is never absorbed (4.3). It can absorb another contact, never the reverse.
- A merge is previewed before it runs. Conflicting values are shown side by side and the user chooses. Nothing is resolved silently.
- Two contacts shown under different names make the preview ask which name to show (`display_name`). Every way the absorbed contact writes its name comes over except an exact duplicate, which stays on the absorbed contact; an unanswered question keeps the survivor's name. Both are in the ledger (`name_representation_moved`, `display_name_replaced`), so the undo gives them back ([ContactNameRepresentation](057-contact-name-representation-done.md)).
- The merge writes to [Log](004-log-in-progress.md). It will emit a webhook (14.2) once webhook machinery exists; the log row is what that event is built from.
- A value the survivor gave up at a conflict is detached rather than deleted, and reaches the set-aside screen. So does a value whose field the surviving structure does not carry. Nothing a merge touches is destroyed.
- A merge is confirmed against the state it was previewed on. If either contact changed in between, the merge is refused and the preview is rebuilt, so nothing is applied to a state nobody looked at.
- An undo that cannot be completed is refused whole. It never applies in part.

## Settled

- **How long a merge stays revertible.** As long as the absorbed contact is retained. Nothing purges a soft deleted contact today, so in practice a merge stays undoable, and no clock of its own is introduced. If a retention policy for [RecordSnapshot](023-record-snapshot-done.md) and [File](008-file-in-progress.md) ever purges contacts, an undo past that point is refused with an explanation rather than half applied.
- **Whether entities can be merged too.** Not for now. The specification discusses contacts only, and that is the narrower reading. Duplicate companies are as likely as duplicate people, so this is a surface to add later rather than a decision to reverse: the model, the preview and the undo are all shaped around a record, not specifically a contact.

## Open questions

- Whether the merge should be offered on a partial contact automatically, since a partial contact being enriched is the commonest way a duplicate appears. Nothing suggests it today.
