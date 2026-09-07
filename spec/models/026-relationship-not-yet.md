# 026. Relationship

**Status:** to create
**Spec:** 3.2, 8.1, 8.3

## What it is

A relationship explicitly entered between two contacts. One row holds both sides and is read from either end.

The first version deliberately keeps this object small: two people, one type, one temporal state, optional dates and notes. Categories organise the available types. Protected behaviours on supplied types allow a family tree and romantic history without guessing any additional relationship.

## Fields

```
id                          int                             primary key
uuid                        string (36)                     unguessable identifier used in URLs and by the API
account_id                  int                             account the relationship belongs to
vault_id                    int                             vault both contacts live in
relationship_type_id        int                             structure of the link
contact_a_id                int                             side carrying role A of a directed type
contact_b_id                int                             side carrying role B of a directed type
status                      enum (current, ended, unknown)  whether the relationship applies today
started_on                  date                            known start date, null when unknown or irrelevant
ended_on                    date                            known end date, null when current or unknown
notes                       text                            optional context written by the user
position                    int                             order it appears in the list on a contact page
created_at                  datetime
updated_at                  datetime
deleted_at                  datetime
```

## Relationships

- `belongsTo` [Account](001-account-in-progress.md), [Vault](007-vault-in-progress.md), [RelationshipType](025-relationship-type-done.md)
- `belongsTo` [Contact](019-contact-done.md) twice, as A and as B

## Rules

- One row, both directions. Nothing writes a mirror row, and no code path may create one, because two rows drift apart the moment one is edited.
- For a directed type, A and B are meaningful. Parent is A and child is B for Parent / Child. For a symmetric type, the application stores the lower contact id as A so the same link has one canonical ordering.
- Several relationships may exist between the same pair. Two siblings may also be colleagues, and two separate Partner rows may record two periods separated by a breakup. There is no unique constraint on the pair.
- A contact cannot be linked to itself.
- Both contacts and the relationship type belong to the same account, and both contacts belong to the relationship's vault. A relationship never crosses the compartment.
- `ended_on` cannot be before `started_on`. `status = current` requires `ended_on` to be null. Dates remain optional: the temporal state is never guessed from missing dates.
- A Parent / Child relationship must not create a cycle in the explicitly recorded parentage graph. This is validation of the entered link, not deduction of another relationship.
- Every relationship row comes from an explicit user or API action. The application creates no inferred relationship, suggestion or probable link in the first version.
- The family tree follows every non-deleted, explicitly recorded relationship whose type has `system_behavior = genealogy_parentage`. Its inclusion does not depend on `status`: death or estrangement does not erase parentage. Traversing several recorded edges may place an ancestor in the tree, but it does not create or claim a separate Grandparent relationship.
- Romantic history lists only explicitly recorded relationships whose type has `system_behavior = romantic`, ordered by their known dates. `ended` expresses an ex relationship; Ex-partner is not a separate supplied type.
- Renaming or moving a supplied type does not change either projection. A custom type named Parent or placed in Family has no effect on the family tree.
- Every creation, edit, status change, deletion and restoration writes to [Log](004-log-in-progress.md) and appears on the contact timeline beside activities.
- Deleting is soft, and it emits a webhook (14.2).

## Open questions

- Whether a relationship can survive a contact moving to another vault. It cannot as specified here, and the specification does not say what should happen instead.

## Deferred extensions

These may be added in a later version without changing the meaning of existing rows:

- reusable or per-side display labels;
- qualifiers such as biological, adoptive, step, foster or legal;
- imprecise dates and several periods on one relationship;
- certainty recorded by the user;
- deductions and suggestions computed from the explicit graph, with their evidence kept outside this table.
