# 026. Relationship

**Status:** to create
**Spec:** 3.2, 8.1, 8.3

## What it is

A relationship explicitly entered between two contacts. One row holds both sides and is read from either contact's point of view.

The first version stays small: two people, one type, a current or ended state, optional dates and notes. It must nevertheless support two day-one uses well: filtering a contact's relationships and drawing a family tree from recorded Parent / Child links.

## Fields

```
id                          int                             primary key
uuid                        string (36)                     unguessable identifier used in URLs and by the API
account_id                  int                             account the relationship belongs to
vault_id                    int                             vault both contacts live in
relationship_type_id        int                             structure of the link
contact_a_id                int                             side carrying role A of a directed type
contact_b_id                int                             side carrying role B of a directed type
status                      enum (current, ended)           whether the relationship applies today
started_on                  date, nullable                  known start date
ended_on                    date, nullable                  known end date
notes                       text, nullable                  context written by the user
created_at                  datetime
updated_at                  datetime
deleted_at                  datetime, nullable
```

## Relationships

- `belongsTo` [Account](001-account-in-progress.md), [Vault](007-vault-in-progress.md) and [RelationshipType](025-relationship-type-done.md)
- `belongsTo` [Contact](019-contact-done.md) twice, as A and as B

## Reading relationships on a contact

- Every relationship is shown using the role read from that contact's side. For Parent / Child, the parent sees Child and the child sees Parent.
- The list can be filtered by category, type, status and role without loading all of the contact's relationships first.
- Parents and Children are first-class filters. They use `system_behavior = genealogy_parentage` and the A/B direction, never the displayed name of the type.
- Filtering, counts, sorting and pagination happen in the database. A contact with 10,000 relationships can request only their children.
- The default order follows category position, type position and then the other contact's display name. A relationship has no manual position of its own.
- A deactivated category or type remains readable and filterable on existing relationships.

The database has indexes for looking relationships up from either side, including by type:

```
(vault_id, contact_a_id, relationship_type_id, deleted_at)
(vault_id, contact_b_id, relationship_type_id, deleted_at)
```

## Family tree

- The family tree is available in the first version.
- Starting from any contact, it follows every non-deleted relationship whose type has `system_behavior = genealogy_parentage`.
- A is the parent and B is the child. Incoming links find a person's parents; outgoing links find their children.
- The tree follows those links recursively to show recorded ancestors and descendants. Branches are loaded as needed; the tree is not built by loading every relationship in the vault.
- Explicit romantic relationships may be displayed beside a person in the tree.
- Parentage remains in the tree regardless of relationship status: death or estrangement does not erase it.
- Traversal may place a grandparent, grandchild or sibling in the tree, but it creates no additional relationship and does not claim an inferred relationship type.
- Renaming or moving the supplied Parent / Child type does not change the tree. A custom type named Parent or placed in Family has no effect on it.

## Rules

- One row represents both directions. The application never writes a mirror row.
- For a directed type, A carries `role_a` and B carries `role_b`. For a symmetric type, the lower contact id is stored as A to give the pair one canonical order.
- Several relationship types may link the same pair. Separate rows of the same type may also record separate periods, such as a couple that separated and later reunited. There is no unique constraint on the pair.
- A contact cannot be linked to itself.
- Both contacts and the relationship type belong to the relationship's account. Both contacts belong to its vault. A relationship never crosses a vault.
- Moving one contact is refused when it would make a non-deleted relationship cross a vault. Nothing silently moves or deletes the relationship.
- `status = current` requires `ended_on` to be null. `status = ended` may omit `ended_on` when the date is unknown. When both dates exist, `ended_on` cannot precede `started_on`.
- A Parent / Child relationship is refused when it would create a cycle in the explicitly recorded parentage graph.
- Every row comes from an explicit user or API action. The application creates no inferred relationship, suggestion or probable link.
- Romantic history contains only explicit relationships whose type has `system_behavior = romantic`. An ended row represents an ex relationship; Ex-partner is not a supplied type.
- Creation, editing, ending, deletion and restoration write to [Log](004-log-in-progress.md) and may appear on both contacts' timelines.
- Deletion is soft and emits a webhook. Restoration revalidates the account, vault and parentage-cycle rules.

## Acceptance scenarios

- Given Alice is A and Bob is B on Parent / Child, Alice's record reads Bob as her child and Bob's record reads Alice as his parent.
- Given a contact has 10,000 relationships, filtering by Children returns only their explicit children through a paginated database query.
- Given Alice is Bob's parent and Bob is Chloé's parent, a tree rooted at Alice can display Chloé as a descendant without creating a Grandparent / Grandchild row.
- Given Alice and Bob share a recorded parent, both can appear in the same tree, but Erica does not create or assert a Sibling relationship.
- Given a custom type named Parent, relationships using it never appear as parentage in the family tree or in the Parents and Children filters.
- Given Alice is already an ancestor of Bob, Erica refuses to record Bob as Alice's parent.
- Given two contacts are in different vaults, Erica refuses to create or restore a relationship between them.

## Deferred extensions

- reusable or per-side display labels;
- qualifiers such as biological, adoptive, step, foster or legal;
- imprecise dates and several periods on one relationship;
- certainty recorded by the user;
- deductions and suggestions computed from the explicit graph, with their evidence stored outside this table.
