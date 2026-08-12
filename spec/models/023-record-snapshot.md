# 023. RecordSnapshot

**Status:** exists (`app/Models/RecordSnapshot.php`), built as far as the models it depends on allow
**Spec:** 12, 6, 11

## What it is

The complete state of a contact or an entity at a moment, kept so it can be restored. Section 12 defines it against the log: a snapshot exists to go back, a log exists to explain who did what. They are complementary and neither replaces the other.

The moment that matters most is a structure change. Section 6 requires that moving a record to another template version automatically saves the previous state, restorable. That guarantee is what makes changing structures safe enough to be offered casually, and it is the reason this model is not optional.

## Fields

```
id                          int                             primary key
account_id                  int                             account the snapshot belongs to
vault_id                    int                             vault the record lives in
snapshotable_type           string (255)                    class of the record captured, a contact or an entity
snapshotable_id             int                             id of that record
template_version_id         int                             structure the record was on at the time
reason                      enum (template_change, merge, manual, import, restore, api)  why the snapshot was taken
payload                     json                            full state of the record, its pages, section placements, fields, values and the files they pointed at
file_ids                    json                            files the snapshot references, so the orphan sweep leaves them alone
created_by_user_id          int                             user whose action triggered it, null when the system took it
restored_at                 datetime                        when this snapshot was used to restore, null while it never was
expires_at                  datetime                        when it may be pruned, null when it is kept indefinitely
created_at                  datetime
updated_at                  datetime
```

## Relationships

- `belongsTo` [Account](001-account.md), [Vault](007-vault.md)
- `morphTo` snapshotable
- `belongsTo` [TemplateVersion](013-template-version.md), [User](002-user.md)

## Rules

- A snapshot is taken automatically before a structure change, before a merge, and before an import overwrites anything. It can also be taken by hand.
- The payload is self contained. Restoring must work even if the structure it came from was retired since, which means the payload carries page keys and labels, section placement, and field keys and labels, not only the values.
- `file_ids` keeps the orphan sweep honest. Section 11 says the grace period exists precisely so that restoring an old version can recover a recently detached file. A file listed in a live snapshot is not swept, whatever its reference count.
- Restoring is itself an action that writes a snapshot first, so a restore can be undone.
- A snapshot is immutable.
- Restoring is refused when the structure it points at no longer exists and cannot be reconstructed. It says so rather than restoring something partial.

## Not done yet

- **One of the six reasons is written by anything.** `template_change` is, every time a record moves. `restore` is written by `RestoreRecordSnapshot`, which no screen calls, and `manual` is the default nothing passes; `merge` waits for [ContactMerge](034-contact-merge.md), `import` for an import that does not exist, and `api` for an API that has not been written.
- **Nothing prunes a snapshot.** Retention is an open question below, so `expires_at` is stored, honoured by nobody, and the model refuses deletion the way a [Log](004-log.md) entry does. Growth is unbounded until the question is answered, which is acceptable while nothing writes snapshots automatically except a structure change.
- **The orphan sweep asks the whole table.** `RecordSnapshot::fileIdsInUse()` reads every row's `file_ids` before the sweep takes anything. That is honest and it does not scale; it is the same decision as keeping the ids in a json column rather than in a pivot, and both should be revisited with retention rather than separately.
- **Restoring puts a record back and nothing else.** A contact's relationships, groups and activities are not in the payload. That is the narrower promise and the one section 6 actually makes; relationships carry their own history.
- **A snapshot is read as history and never as a way back.** The version history of a contact reads the snapshots to say which structures the record has been through and when each was applied, which is what makes them visible at all. What it offers is a move to another published version, not a restore: `RestoreRecordSnapshot` is written, tested and called by nothing, so what was written down at a given moment cannot be put back from any screen. No API either.

## Decisions taken while building it

- **The payload carries identifiers as well as labels.** The file asks for keys and labels so that a payload is readable without the structure it came from, and restoring also has to write values back into real fields. A published version is frozen and its fields cannot be deleted, so the identifiers are stable and are what puts the values back; the keys and labels are what makes the payload mean something on its own.
- **Restoring refuses rather than restores something partial**, checking that the version and every field the payload names are still there. Half a record put back is worse than a record left alone, because nobody can tell which half.
- **Restoring writes a snapshot first**, which is the file's own rule, and it means the machinery that undoes a restore is the machinery that performs one.
- **Immutable means the captured state, not the row.** `restored_at` moves, because it says something about the snapshot rather than about the state in it. Everything else is refused, and so is deletion, for the reason a log entry refuses both.
- **Moving a record to another version is its own action,** `MoveRecordToTemplateVersion`, and it takes the snapshot unconditionally rather than working out whether the change looks harmless. The whole point of the guarantee is that nobody has to decide in advance whether they will regret it. This is the rule [Contact](019-contact.md) and [Entity](020-entity.md) both list and neither could keep until now.
- **A value survives a move when the new structure still carries the field it points at.** Otherwise it is set aside, and a value set aside by an earlier move comes back when its field is carried again. Matching a value to a field of the same key on a differently versioned section is a separate question, and it belongs to the builder rather than here.

## Open questions

- Retention. Keeping every snapshot forever grows without bound on a record edited often. Keeping the last N per record plus every structure change snapshot is probably the right shape, and it interacts with the file grace period, so the two need deciding together.
- Whether a snapshot of a contact includes its relationships and activities, or only its own fields. Only its own fields is the narrower promise and the one section 6 actually makes. Relationships have their own history ([Relationship](026-relationship.md)).
