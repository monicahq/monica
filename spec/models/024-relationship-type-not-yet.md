# 024. RelationshipType

**Status:** to create
**Spec:** 8.1, 8.2, 8.3

## What it is

The structure of a link between two people, stripped of how it is written. "Parent towards child". "Sibling". "Partner". "Colleague".

Section 8.2 separates the structure from the wording deliberately, and this is the structure half. The product knows there is a parent to child link. Whether it reads as father, mother, parent, dad, mum, stepfather or guardian is an independent layer ([RelationshipLabel](025-relationship-label-not-yet.md)). That separation is what lets gender, language and family nicknames be handled without multiplying relationship types.

It also carries what the deduction engine needs (8.3). Grandparents, nephews and cousins are computed from parent and sibling links, so a type has to declare whether it is transitive and what composing it with another produces.

## Fields

```
id                          int                             primary key
account_id                  int                             account the type belongs to
key                         string (64)                     stable identifier of a supplied type, such as parent_child, null for a user made one
name                        string (255)                    name of the type, null when it falls back to its translation key
name_translation_key        string (255)                    translation key of a supplied name
category                    enum (family, romantic, professional, social, other)  family it belongs to, used to group the picker
is_symmetric                boolean                         whether both sides mean the same thing, as for sibling, unlike parent and child
is_hierarchical             boolean                         whether one side is above the other, which is what deduction walks
allows_multiple             boolean                         whether a contact may hold several of these at once
supports_periods            boolean                         whether it makes sense to record when it started and ended
is_deducible                boolean                        whether the product may infer it rather than requiring it to be entered
deduction_rule              json                            how it is inferred, as the composition of other types it is equivalent to
is_system                   boolean                         whether the product supplied it
is_active                   boolean                         whether it can still be chosen for a new relationship
position                    int                             order it appears in the picker
created_at                  datetime
updated_at                  datetime
deleted_at                  datetime
```

## Relationships

- `belongsTo` [Account](001-account-in-progress.md)
- `hasMany` [RelationshipLabel](025-relationship-label-not-yet.md)
- `hasMany` [Relationship](026-relationship-not-yet.md)

## Rules

- Relationship types apply to contacts only (8.1). Entities are not part of this system.
- The type never carries wording. It carries structure. Every string a user reads about a relationship comes from a label.
- `is_symmetric` decides whether the two sides are interchangeable. Sibling is symmetric, parent to child is not, and the two sides of a non symmetric type read differently by construction.
- `deduction_rule` is what section 8.3 runs on: parent of a parent is a grandparent, sibling of a parent is an aunt or uncle. A deduced relationship is never stored as if it had been entered ([RelationshipSuggestion](030-relationship-suggestion-not-yet.md)), though the user may confirm one explicitly when the intermediate links are missing.
- The supplied set is seeded at account creation and is fully editable, like every other supplied list.
- Deactivating a type leaves existing relationships intact and readable.

## Open questions

- How far deduction walks. Second cousins twice removed are computable and nobody asked for them. A depth limit is needed and the specification gives none.
- Whether a user can create a genuinely new type with its own deduction rule, or only new labels on the supplied types. Creating types is more in the spirit of the product and makes the deduction engine take user authored rules, which is a materially harder thing to get right.
