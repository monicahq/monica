# 026. Relationship

**Status:** to create
**Spec:** 3.2, 8.1, 8.2, 8.3, 8.4

## What it is

A structured human link between two contacts, read from both ends. It is in the immutable core (3.2).

Section 8.1 explains why this is a system of its own and not a custom field type, and the reasoning is worth restating because it justifies the six models around it. A real human relationship carries direction (parent and child are not interchangeable), periods (colleagues between 2012 and 2015), uncertainty (probably his father), nuance (biological, adoptive, step, legal), blended families, and deduction (parent of a parent is a grandparent). A generic field carries none of that.

One row holds both sides. The relationship is entered once and read from either end (8.2).

## Fields

```
id                          int                             primary key
account_id                  int                             account the relationship belongs to
vault_id                    int                             vault both contacts live in
relationship_type_id        int                             structure of the link
contact_a_id                int                             one end, the higher one for a hierarchical type
contact_b_id                int                             the other end, the lower one for a hierarchical type
label_a_id                  int                             wording A uses for B, null to use the default for the type and gender
label_b_id                  int                             wording B uses for A, null the same way
custom_label_a              string (255)                    wording A uses when no label in the list fits
custom_label_b              string (255)                    wording B uses, the same way
notes_a                     text                            what A wants noted about the link, private to that side
notes_b                     text                            what B wants noted about the link
certainty                   enum (certain, probable, uncertain)  how sure the user is, so probable never reads as fact
is_deduced                  boolean                         whether the product inferred it rather than the user entering it
deduced_from                json                            the chain of relationships it was inferred from, so the page can explain itself
confirmed_at                datetime                        when the user confirmed it, null while it is only inferred or only entered
is_active                   boolean                         whether the link holds today, computed from its periods
position                    int                             order it appears in the list on a contact page
created_at                  datetime
updated_at                  datetime
deleted_at                  datetime
```

## Relationships

- `belongsTo` [Account](001-account.md), [Vault](007-vault.md), [RelationshipType](024-relationship-type.md)
- `belongsTo` [Contact](019-contact.md) twice, as A and as B
- `belongsTo` [RelationshipLabel](025-relationship-label.md) twice
- `hasMany` [RelationshipPeriod](027-relationship-period.md)
- `belongsToMany` [RelationshipQualifier](028-relationship-qualifier.md) through [RelationshipQualifierAssignment](029-relationship-qualifier-assignment.md)

## Rules

- One row, both directions. Nothing writes a mirror row, and no code path may create one, because two rows drift apart the moment one is edited.
- For a hierarchical type, A is the higher end. Parent is A, child is B, always, so a query never has to check both columns.
- Several relationships may exist between the same pair (8.2). Two sisters may also be flatmates and business partners. There is no unique constraint on the pair.
- Each side keeps its own label and its own notes.
- Blocked outright, because they are impossible (8.3): a contact linked to itself, a genealogical loop, a period ending before it starts.
- Warned but allowed, because they are unusual and perfectly possible (8.3): a parent younger than their child, several simultaneous partners, overlapping couple periods. The product flags, it does not judge. This distinction is a product decision, not an implementation detail, and tests should cover both halves.
- Both contacts are in the same vault. A relationship never crosses the compartment.
- A deduced relationship is not stored here until it is confirmed. Until then it lives in [RelationshipSuggestion](030-relationship-suggestion.md). Confirming one writes a row with `is_deduced` true and `deduced_from` filled, so the page can still say where it came from.
- Every change to a relationship, its creation, confirmation, label change, qualifier addition, period start and end, merge, deletion and restoration, writes to [Log](004-log.md) and appears on the contact timeline beside the activities (8.4, 9.5). This is a visible feature, not a technical trace.
- Deleting is soft, and it emits a webhook (14.2).

## Open questions

- How far the loop check walks before it accepts an entry. A full graph walk on every save is expensive on a large family tree, and a shallow one misses real loops.
- Whether a relationship can survive a contact moving to another vault. It cannot as specified here, and the specification does not say what should happen instead.
