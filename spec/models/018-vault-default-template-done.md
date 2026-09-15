# 018. VaultDefaultTemplate

**Status:** exists (`app/Models/VaultDefaultTemplate.php`), built as far as the models it depends on allow
**Spec:** 5.3, 4.2

## What it is

Which structure a new record gets, in this vault, for this kind of record.

Section 5.3 requires it explicitly: a single entity type may have several templates, and the one applied by default can differ from one vault to another. The family vault and the professional breeding vault both handle Animals, with completely different levels of detail. The same holds for contacts: a work contact and a family contact do not start from the same page.

It is a small table, and it is a table rather than a column on the vault because there is one default per kind of record, not one per vault.

## Fields

```
id                          int                             primary key
account_id                  int                             account the default belongs to
vault_id                    int                             vault the default applies in
applies_to                  enum (contact, entity)          what kind of record the default is for
entity_type_id              int                             type the default is for when applies_to is entity, null otherwise
structure_version_id        int                             exact version a new record starts on
created_at                  datetime
updated_at                  datetime
```

## Relationships

- `belongsTo` [Account](001-account-in-progress.md)
- `belongsTo` [Vault](007-vault-in-progress.md)
- `belongsTo` [EntityType](011-entity-type-done.md)
- `belongsTo` [StructureVersion](013-structure-version-done.md)

## Rules

- One row per vault and per kind of record. For entities, one row per vault and per entity type.
- The row points at a version, not at a template. Otherwise a new record would silently follow the latest structure, which section 3.1 forbids.
- Changing the default affects records created afterwards and never touches records already created. Moving those is a separate, deliberate action ([StructureVersion](013-structure-version-done.md)).
- Every vault has a contact default, seeded at creation from the supplied contact template.
- An entity type with no default in a vault simply offers no shortcut there. The user picks a template when creating the record.

## Not done yet

- **Only the contact default is written, and only it is read.** `CreateContact` starts a record on the default of its kind when the caller names no version. Nothing writes the entity default: the seeding of an account sets the contact one on every vault, and `SetVaultDefaultTemplate` is reached only from the vault screens, which offer contacts alone. Nothing reads it either, the action that wrote an entity down having been removed, so the row can exist and no code consults it.
- **No API.** The choice is offered twice: on the form that opens a vault, and on the settings of one, where every published version of every contact template the account has is listed under the template it belongs to. It is a version rather than a template on both, since that is what the column holds. Nothing is exposed over the API yet.
- **A vault whose default structure version is retired keeps pointing at it.** Retiring a version stops it being offered for anything new, and nothing walks the defaults to find the ones that just went stale.

## Decisions taken while building it

- **The kind of record is read off the template rather than asked for.** A template already knows whether it describes a contact or one of the kinds of record the account invented, and asking the caller as well only creates a way for the two answers to disagree.
- **Setting is one action, not a create and an update.** There is one row per vault and per kind of record, so `SetVaultDefaultTemplate` writes it or replaces what is there. What it points at is always a published version, for the same reason a record can only be put on one.
- **The contact default cannot be removed**, only replaced, since a vault where writing somebody down asks which template to use first has forgotten what it is for. The action that removed a default enforced exactly that and refused the contact one, and it had no caller, so it has been removed. Nothing now deletes one of these rows at all: a default is replaced through `SetVaultDefaultTemplate`, or it goes with the vault.
- **The uniqueness of the contact default is enforced by the action, not by the index.** The unique index covers vault, kind and entity type, and every database treats two null entity types as distinct rows, so it catches the entity case only. The action writes through `updateOrCreate`, which is what makes the contact case true.
- **The first vault of an account is given its default by the job that seeds the template.** The vault is created inside the sign-up transaction and the supplied template arrives a moment later on a queue, so the seeding cannot happen when the vault is made. Every vault opened afterwards gets its default from `CreateVault`.

## Open questions

- Whether a vault may forbid an entity type outright rather than merely not defaulting it. Useful for keeping a work vault clean, and not asked for.
