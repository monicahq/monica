# 023. RecordSnapshot

**Status:** to create
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
payload                     json                            full state of the record, its fields, its values and the files they pointed at
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
- The payload is self contained. Restoring must work even if the structure it came from was retired since, which means the payload carries the field keys and labels, not only the values.
- `file_ids` keeps the orphan sweep honest. Section 11 says the grace period exists precisely so that restoring an old version can recover a recently detached file. A file listed in a live snapshot is not swept, whatever its reference count.
- Restoring is itself an action that writes a snapshot first, so a restore can be undone.
- A snapshot is immutable.
- Restoring is refused when the structure it points at no longer exists and cannot be reconstructed. It says so rather than restoring something partial.

## Open questions

- Retention. Keeping every snapshot forever grows without bound on a record edited often. Keeping the last N per record plus every structure change snapshot is probably the right shape, and it interacts with the file grace period, so the two need deciding together.
- Whether a snapshot of a contact includes its relationships and activities, or only its own fields. Only its own fields is the narrower promise and the one section 6 actually makes. Relationships have their own history ([Relationship](026-relationship.md)).
