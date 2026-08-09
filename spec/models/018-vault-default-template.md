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
template_version_id         int                             exact version a new record starts on
created_at                  datetime
updated_at                  datetime
```

## Relationships

- `belongsTo` [Account](001-account.md)
- `belongsTo` [Vault](007-vault.md)
- `belongsTo` [EntityType](011-entity-type.md)
- `belongsTo` [TemplateVersion](013-template-version.md)

## Rules

- One row per vault and per kind of record. For entities, one row per vault and per entity type.
- The row points at a version, not at a template. Otherwise a new record would silently follow the latest structure, which section 3.1 forbids.
- Changing the default affects records created afterwards and never touches records already created. Moving those is a separate, deliberate action ([TemplateVersion](013-template-version.md)).
- Every vault has a contact default, seeded at creation from the supplied contact template.
- An entity type with no default in a vault simply offers no shortcut there. The user picks a template when creating the record.

## Not done yet

- **Only the contact default is read by anything.** `CreateContact` starts a contact on it when the caller names no version. The entity default is stored and managed and nothing reads it, since [Entity](020-entity.md) does not exist.
- **No screen and no API.** Setting the default is an action, and the screen that offers the choice arrives with the vault settings.
- **A vault whose default template version is retired keeps pointing at it.** Retiring a version stops it being offered for anything new, and nothing walks the defaults to find the ones that just went stale.

## Decisions taken while building it

- **The kind of record is read off the template rather than asked for.** A template already knows whether it describes a contact or one of the kinds of record the account invented, and asking the caller as well only creates a way for the two answers to disagree.
- **Setting is one action, not a create and an update.** There is one row per vault and per kind of record, so `SetVaultDefaultTemplate` writes it or replaces what is there. What it points at is always a published version, for the same reason a record can only be put on one.
- **The contact default cannot be removed**, only replaced. `DestroyVaultDefaultTemplate` refuses it, since a vault where writing somebody down asks which template to use first has forgotten what it is for. An entity default is removed freely: the kind of record is then simply offered no shortcut there.
- **The uniqueness of the contact default is enforced by the action, not by the index.** The unique index covers vault, kind and entity type, and every database treats two null entity types as distinct rows, so it catches the entity case only. The action writes through `updateOrCreate`, which is what makes the contact case true.
- **The first vault of an account is given its default by the job that seeds the template.** The vault is created inside the sign-up transaction and the supplied template arrives a moment later on a queue, so the seeding cannot happen when the vault is made. Every vault opened afterwards gets its default from `CreateVault`.

## Open questions

- Whether a vault may forbid an entity type outright rather than merely not defaulting it. Useful for keeping a work vault clean, and not asked for.
