# 028. RelationshipQualifier

**Status:** to create
**Spec:** 8.2

## What it is

A nuance that can be added to a relationship: biological, adoptive, step, foster, legal, social.

Section 8.2 chooses composition over enumeration here, and the reason is arithmetic. If every nuance were its own relationship type, the picker would need one entry per combination, and a relationship that is both legal and adoptive would need a type of its own. Keeping the type as "parent towards child" and stacking qualifiers on top keeps the list of types short and makes combinations free.

## Fields

```
id                          int                             primary key
account_id                  int                             account the qualifier belongs to
key                         string (64)                     stable identifier of a supplied qualifier, null for a user made one
label                       string (255)                    the word itself, null when it falls back to its translation key
label_translation_key       string (255)                    translation key of a supplied label
description                 string (255)                    what it means, shown when the word alone is ambiguous
applies_to_type_ids         json                            relationship types it can be added to, null when it applies to all of them
is_system                   boolean                         whether the product supplied it
is_active                   boolean                         whether it can still be added
position                    int                             order it appears in the picker
created_at                  datetime
updated_at                  datetime
deleted_at                  datetime
```

## Relationships

- `belongsTo` [Account](001-account-in-progress.md)
- `belongsToMany` [Relationship](026-relationship-not-yet.md) through [RelationshipQualifierAssignment](029-relationship-qualifier-assignment-not-yet.md)

## Rules

- Qualifiers stack. A relationship may carry several, and the specification gives legal plus adoptive as the example.
- `applies_to_type_ids` keeps the picker sensible. Adoptive belongs on a parent link and not on a colleague link.
- A qualifier changes how a relationship is described and never what it structurally is. Deduction (8.3) runs on the type, not on the qualifiers, so a stepparent link does not produce blood grandparents.
- The supplied set is seeded and editable, like every other supplied list.
- Deactivating one leaves existing assignments in place.

## Open questions

- Whether a qualifier can carry its own period, so a foster relationship that later becomes legal reads correctly over time. Currently the qualifier is a flat attribute of the relationship. Periods per qualifier would be more accurate and materially more complex, and the specification does not ask for it.
