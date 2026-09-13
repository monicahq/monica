# 026. Relationship

**Status:** to create
**Spec:** 3.2, 8.1, 8.2, 8.3

## What it is

A relationship explicitly entered between two contacts. One row holds both sides and is read from either contact's point of view.

The first version stays small: two people, one type, a current or ended state, optional dates and notes. It supports three day-one uses: filtering a contact's relationships, displaying their immediate family and drawing a family tree from recorded Parent / Child links.

A relationship never contains a free-text person and never has an absent endpoint. Both sides are always [Contact](019-contact-done.md) rows, including when the user does not want a full contact record or does not know the person's name.

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

## Creating a relationship

- Each side is either an existing contact selected by the user or a new contact created as part of saving the relationship. Creating the new contact and the relationship is one transaction: both succeed or neither exists.
- For a new person, the relationship editor offers **Create a contact record for this person**. Checked creates a regular contact (`is_partial = false`) visible in the main contact lists. Unchecked still creates a Contact, but marks it partial (`is_partial = true`) so it stays out of those lists.
- The checkbox changes only the initial value of `is_partial`. It does not change the model used, the relationship endpoint, access to the contact page, custom fields, future relationships, merge behaviour or family-tree membership. Editing the contact later does not silently promote it; promotion to the main contact lists is an explicit action.
- Name and partial state are independent. Either kind of contact may have no name. A nameless contact uses the product fallback described by [Contact](019-contact-done.md); Erica never stores the fallback as a name.
- Saving two unnamed children creates two distinct Contact rows and two distinct Parent / Child relationships. They remain separately identifiable by their ids even though they have the same fallback display.

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

## Immediate family on a contact record

Every contact record, including Me, has an **Immediate family** block (**Famille proche** in French) with two lists: **Children** and **Current partner**. These are projections of explicitly recorded relationships, using the same rules in the interface and authenticated API.

| List | Selection |
|---|---|
| Children | Relationships with `system_behavior = genealogy_parentage` and `contact_a_id` equal to the viewed contact. Display contact B. Include both current and ended rows, as in the family tree: ending contact with a child does not erase parentage. |
| Current partner | Relationships with `system_behavior = romantic` and `status = current`, where the viewed contact is either A or B. Display the other contact. This includes the supplied Partner, Fiancé and Spouse types. |

- Both queries are scoped to the account and vault and exclude deleted relationships and deleted contacts. Partial and archived contacts remain eligible. Deactivating or renaming a type or category does not hide existing family links.
- Dates are optional. Current partnership is determined by `status`, not by a null end date, the latest start date or the most recently edited row. An ended row with no end date is excluded from Current partner.
- Show each person once per list and count distinct contacts. Several periods or romantic types linking the same pair do not produce duplicate cards. An ended Partner row and a current Spouse row for the same person produce one current partner; a former relationship does not cancel a separate current relationship.
- If several distinct people have explicit current romantic links, show all of them under **Current partners**. Do not select one arbitrarily or require a single partner. Display the wording of the qualifying roles beside each name and link the name to its contact record.
- The block shows a short preview of each list, with its distinct contact count and a **View all** link when more entries exist. The full list uses the same selection, distinct contacts, stable name ordering and database pagination. Neither the preview nor its counts load all of the contact's relationships.
- A child whose name is unknown is still a distinct partial [Contact](019-contact-done.md) and a real endpoint of the Parent / Child relationship. The interface displays the contact fallback rather than inventing a name. Learning the name updates that same contact and leaves the relationship untouched.
- Empty lists say **No children recorded** and **No current partner recorded**. These describe the stored information, not a claim that the person has no children or partner.
- A partner's children are not included unless the viewed contact has their own explicit Parent / Child links to them. Parents, siblings and other relatives do not enter this block through graph traversal. Custom type names and display categories never establish membership.
- Saving, ending, deleting or restoring a qualifying relationship, or merging its contacts, refreshes the block from the committed records. Ending a partnership removes that partner when no other current romantic link to them remains; it does not remove the contact's children or change their parentage rows.
- This adds no model, membership table, certainty state or inference engine. Both relationship endpoints are real contacts, whether or not their names are known. The block is available on the owner's contact page in v1. It grants no additional access through private share links.

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
- Given Alice is Bob's parent, Alice's Immediate family block lists Bob under Children; Bob's block does not list Alice as a child.
- Given Alice has an ended Parent / Child row to Bob, Bob remains in Children while both contacts and the relationship are not deleted.
- Given an explicit current Partner, Fiancé or Spouse row with no dates, the other contact appears under Current partner.
- Given an ended romantic row whose end date is unknown, that row does not contribute a current partner.
- Given an ended Partner row and a current Spouse row to the same person, the block shows one current partner and counts one person.
- Given two distinct current partners, the block shows both under Current partners; it does not choose the newest or most recently edited relationship.
- Given a partner has a child but the viewed contact has no Parent / Child link to that child, the child is not added to the viewed contact's Immediate family.
- Given a qualifying contact is partial or archived, the block includes them. Deleting the contact or its only qualifying relationship removes the entry; a valid restoration makes it eligible again.
- Given a qualifying type is renamed, moved or deactivated, existing Immediate family membership is unchanged. A custom type named Spouse does not qualify.
- Given repeated parentage rows and 10,000 other relationships, Children counts each child once and loads only the requested preview or page through database queries.
- Given Alice records that she has two children without knowing their names, Erica creates two distinct nameless partial contacts and two Parent / Child relationships. Alice's Children list and family tree show two nodes. Naming either child updates that same contact without replacing its relationship.
- Given a user enters Jack while creating a relationship and unchecks **Create a contact record for this person**, Erica creates Jack as a partial Contact and creates the relationship to his id. Jack is absent from the main contact lists but has a contact page and can participate in other relationships.
- Given the same checkbox is checked, Erica creates a regular Contact instead. Leaving every name component empty is valid in either case.
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
