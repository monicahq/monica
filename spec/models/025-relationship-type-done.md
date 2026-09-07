# 025. RelationshipType

**Status:** done
**Spec:** 8.2, 8.3

## What it is

The user-selectable nature of a link between two people: Parent / Child, Sibling, Partner, Friend or Colleague.

A type belongs to a display category and defines how the relationship reads from each side. It may be symmetric, as Friend, or directed, as Parent / Child.

Some supplied types carry a small protected behaviour used by product features. Parent / Child feeds the family tree. Partner, Fiancé and Spouse feed romantic history. This behaviour is attached to the stable type, not to its name or category.

## Fields

```
id                          int                             primary key
uuid                        string (36)                     unguessable identifier used in URLs and by the API
account_id                  int                             account the type belongs to
relationship_category_id    int                             display category containing the type
key                         string (64)                     stable identifier of a supplied type, null for a user-created one
name                        string (255), nullable          name the user gave it, null while the supplied wording stands
name_translation_key        string (255), nullable          the supplied wording of the name
role_a                      string (255), nullable          how A relates to B, such as Parent
role_a_translation_key      string (255), nullable          the supplied wording of the first role
role_b                      string (255), nullable          how B relates to A, such as Child
role_b_translation_key      string (255), nullable          the supplied wording of the opposite role
is_symmetric                boolean                         whether both directions use the same role
system_behavior             enum (genealogy_parentage, romantic), nullable  protected product behaviour, null for ordinary types
is_system                   boolean                         whether the product supplied it with the account
is_active                   boolean                         whether it can be chosen for a new relationship
position                    int                             order inside its category
created_at                  datetime
updated_at                  datetime
deleted_at                  datetime
```

## Relationships

- `belongsTo` [Account](001-account-in-progress.md)
- `belongsTo` [RelationshipCategory](024-relationship-category-done.md)
- `hasMany` [Relationship](026-relationship-not-yet.md)

## Supplied types

| Category | Type | Roles | Protected behaviour |
|---|---|---|---|
| Family | Parent / Child | Parent → Child | `genealogy_parentage` |
| Family | Sibling | Sibling ↔ Sibling | none |
| Family | Grandparent / Grandchild | Grandparent → Grandchild | none |
| Family | Aunt or Uncle / Niece or Nephew | Aunt or Uncle → Niece or Nephew | none |
| Family | Cousin | Cousin ↔ Cousin | none |
| Romantic | Partner | Partner ↔ Partner | `romantic` |
| Romantic | Fiancé | Fiancé ↔ Fiancé | `romantic` |
| Romantic | Spouse | Spouse ↔ Spouse | `romantic` |
| Friendship | Friend | Friend ↔ Friend | none |
| Professional | Colleague | Colleague ↔ Colleague | none |

## Rules

- The user may create, rename, reorder, move and deactivate types.
- A supplied type cannot be deleted. Its `key`, direction and `system_behavior` never change.
- A user-created type has no `system_behavior`. Its name or category never activates product behaviour.
- A user-created type may be deleted only when no relationship uses it. Otherwise it must be deactivated.
- Deactivating a type prevents new use and leaves existing relationships readable.
- For a symmetric type, `role_a` and `role_b` are the same. For a directed type, A always carries `role_a` and B always carries `role_b`.
- A supplied name or role is stored as a translation key rather than a word, so it reads in the
  language of whoever is looking. The moment the user rewrites it, their words are stored and the
  key is ignored. This is the same arrangement [ValueList](009-value-list-in-progress.md) uses.
- The initial system has no deduction rules. `system_behavior` is only a stable hook for the family-tree and romantic-history projections and for optional future inference features.

