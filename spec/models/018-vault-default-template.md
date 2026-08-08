# 018. VaultDefaultTemplate

**Status:** to create
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

## Open questions

- Whether a vault may forbid an entity type outright rather than merely not defaulting it. Useful for keeping a work vault clean, and not asked for.
