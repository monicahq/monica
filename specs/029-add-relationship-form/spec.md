# Feature Specification: Add a relationship

**Feature Branch**: `2026-09-13-spec-relations` | **Created**: 2026-09-13 | **Status**: Draft

**Input**: "implement the form to add a relationship … read the related spec to make it happen … just add this form, we'll do the rest after (including whatever happens after you click save and where the user ends up)"

## Overview

The form that records a relationship between the contact on screen and somebody else. It reveals itself progressively: choose a type, and only then is the other side asked for. Three ways to answer, a contact written down when the person is new, and the sentence read back before saving.

Scope is the form and the record it writes. The relationship list, the Immediate family block, the family tree, romantic history, editing, ending, deleting, the API surface and where the user lands after saving are all out of scope. Rules come from [spec/models/026](../../spec/models/026-relationship-not-yet.md) and [spec.md §8](../../spec/spec.md).

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Link to somebody already written down (Priority: P1)

Camille's record is open. The user records that Camille is Théo's parent, picking Théo from a search of the vault.

**Why this priority**: the smallest path that proves the whole form, using nothing but data the vault already holds. Without it there is no feature.

**Independent Test**: open the form, choose a type, search and select another contact, save, confirm one row with the two contacts on the correct sides.

**Acceptance Scenarios**:

1. **Given** no type is chosen, **When** the user looks at the form, **Then** only the type picker shows, saving is unavailable, and the form says a type is needed.
2. **Given** a directed type is chosen, **Then** Camille is labelled with the first role, the other side with the second, and the three ways of naming the other side appear.
3. **Given** the user searches the vault, **Then** each match shows a name and a distinguishing line, and selecting one marks it chosen.
4. **Given** a type and a person are settled, **Then** the form states the relationship as a sentence from each side and saving becomes available; saving writes exactly one row and no mirror.

### User Story 2 - Write somebody new down while recording (Priority: P1)

The user records that Camille has a son, Théo, whom the vault does not know. They type the name, decide whether Théo joins the main people list, and save.

**Why this priority**: the product spec makes this the defining behaviour. A mentioned person becomes a real contact, not a free-text name, and this form is where that happens.

**Independent Test**: choose a type, say the name is known, type a first name, save, confirm both a contact and a relationship pointing at it.

**Acceptance Scenarios**:

1. **Given** the user says the name is known, **Then** a first name and an optional last name are asked for, and creating a contact record is offered.
2. **Given** the offer is left unchecked, **When** saved, **Then** a contact is created but kept out of the main people lists, still with a page of its own and able to take further relationships.
3. **Given** the offer is checked, **When** saved, **Then** a regular contact is created and appears in the main people lists.
4. **Given** either write fails, **Then** neither the contact nor the relationship exists afterwards.

### User Story 3 - Record somebody whose name is unknown (Priority: P2)

The user knows Camille has a child but not the child's name. Saving twice produces two distinct people, not one.

**Why this priority**: the case that distinguishes this product from a contact list, called out by name in the product spec. Cheap once Story 2 exists.

**Independent Test**: save twice with no name, confirm two distinct contacts and two distinct relationships.

**Acceptance Scenarios**:

1. **Given** the user says the name is unknown, **Then** no name fields are asked for, creating a contact record is still offered, and saving is available.
2. **Given** two saves with no name, **Then** two distinct contacts and two distinct relationships exist, separately identifiable though they read the same.
3. **Given** a nameless contact, **Then** the sentence names the other side by role and no placeholder name is stored.

### User Story 4 - Put the two people on the right sides (Priority: P2)

The form cast Camille as the parent and it is the other way round. The user swaps the roles.

**Why this priority**: a directed type recorded backwards is silently wrong data that reads correctly on the screen that wrote it. The swap is the only protection.

**Independent Test**: choose a directed type, swap, save, confirm the viewed contact is stored on the opposite side.

**Acceptance Scenarios**:

1. **Given** a directed type, **When** the user swaps, **Then** both labels exchange, the two blocks exchange positions, and the sentence is rewritten.
2. **Given** a symmetric type, **Then** the swap is unavailable, because both sides carry the same word.
3. **Given** the user swaps then changes type, **Then** the swap is forgotten and the new type's default sides apply.

### Edge Cases

| Situation | Behaviour |
|---|---|
| Search matches nobody | Say so, quote what was searched for, point at typing the name instead |
| The viewed contact appears in its own search | Excluded from results; a self-link is refused if it arrives anyway |
| The chosen contact is in another vault | Refused; search never reaches outside the vault |
| A parentage link would close a loop | Refused, with a reason on the form |
| The pair is already linked | Allowed, with no warning. Several types, and several periods of one type, may link two people |
| The type is deactivated between opening and saving | Refused rather than recorded |
| "I know their name" with no first name typed | Saving stays unavailable; the unknown-name path records the same thing honestly |
| Only a first name is known | Valid. The last name is never required |
| Search text edited after a person was chosen | The choice is cleared |
| The account has no active types | The form says so rather than showing an empty picker |
| Reduced motion is asked for | Each reveal is instant, not faster |

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: Users MUST reach this form from the record of a contact they can see, and it MUST name that contact.
- **FR-002**: The form MUST ask for the type first and show no other question until one is chosen.
- **FR-003**: The picker MUST offer only active types in active categories of that account, each shown by the wording read from both sides.
- **FR-004**: The form MUST always say what is still needed or that the record is ready, and keep saving unavailable until it is complete.
- **FR-005**: The form MUST state that both directions are recorded at once.
- **FR-006**: Once a type is chosen, the form MUST label the viewed contact with one role and the other side with the other, taking both words from the type.
- **FR-007**: Users MUST be able to swap which side of a directed type each person holds, re-labelling both sides and rewriting the sentence.
- **FR-008**: The swap MUST be unavailable for a symmetric type.
- **FR-009**: Choosing a different type MUST discard any swap.
- **FR-010**: Users MUST say who the other side is in exactly three ways: name unknown, name known and typed, or already written down in this vault.
- **FR-011**: When the name is known, the form MUST ask a first name and an optional last name, never requiring the last.
- **FR-012**: When the person is already written down, users MUST be able to search that vault's contacts, see enough to tell two people of one name apart, and see which is chosen.
- **FR-013**: Search MUST exclude the viewed contact and never reach outside its vault.
- **FR-014**: When search matches nobody, the form MUST say so, quote the search, and point at typing the name instead.
- **FR-015**: Changing the search text MUST clear any person already chosen.
- **FR-016**: For a new person the form MUST offer to create a contact record, off by default, and MUST NOT offer it for a contact already written down.
- **FR-017**: Saving MUST create a real contact for a new person either way. The offer MUST control only whether that contact appears in the main people lists, and nothing else: not its page, fields, further relationships or place in the family tree.
- **FR-018**: A new person MUST be creatable with no name at all, and no placeholder name may be stored.
- **FR-019**: Creating the new contact and the relationship MUST be one indivisible operation.
- **FR-020**: Once both sides are settled, the form MUST state the relationship as a plain sentence from the viewed contact's side and restate it from the other side.
- **FR-021**: The sentence MUST use the type's current wording, including after a swap, and refer to a nameless person by role.
- **FR-022**: Saving MUST record exactly one relationship holding both sides, and never a second row for the opposite direction.
- **FR-023**: A saved relationship MUST carry the account and vault of the contact the form was opened on, and both people MUST belong to that vault.
- **FR-024**: A relationship recorded here MUST start as current with no dates and no notes. This form MUST NOT ask for status, dates or notes.
- **FR-025**: For a symmetric type the two people MUST be stored in one canonical order.
- **FR-026**: Saving MUST be refused when the two sides are the same person, when they are not in one vault, when the type is not currently choosable, or when a parentage link would close a loop. A refusal MUST explain itself and leave nothing written.
- **FR-027**: Creating a relationship MUST be recorded in the account's history.
- **FR-028**: Users MUST be able to leave the form without recording anything.
- **FR-029**: Opening this form on, or linking to, a contact outside the user's account or reached through the wrong vault MUST fail as though the contact did not exist.
- **FR-030**: Every word MUST be translatable and read in the viewer's language, including the supplied wording of types and roles.
- **FR-031**: The form MUST be operable by keyboard alone and MUST announce each revealed part, its two role groups and its three-way choice to assistive technology.

### Key Entities

- **Relationship**: the record written here. Both people, the type, the shared account and vault, a current-or-ended state and optional dates and notes. New.
- **RelationshipType**, **RelationshipCategory**: the nature of the link and the group it is offered in. Both already built.
- **Contact**: both sides of every relationship without exception, including a person written down here. Already built, with its partial marking and its nameless display fallback.

## Success Criteria *(mandatory)*

- **SC-001**: Linking to somebody already in the vault takes under 30 seconds and 4 interactions: type, choose existing, select, save.
- **SC-002**: Recording a relationship to a new person takes under 45 seconds without leaving the form to create the contact first.
- **SC-003**: 100% of relationships written here carry two real people; none carries a free-text name or an absent side.
- **SC-004**: Every saved relationship reads back on the confirmation sentence exactly as stored, from both sides, for every supplied type.
- **SC-005**: No relationship written here links somebody to themselves, crosses two vaults, closes a parentage loop, or uses a type the account may not choose.
- **SC-006**: Two people recorded with no name produce two distinct records, 100% of the time.
- **SC-007**: No part of the form is shown before the answer that makes it relevant.
- **SC-008**: The form is completable using only the keyboard, and every revealed part is announced by a screen reader.

## Assumptions

- **A page, not a dialog.** The mockup frames it as a dialog; every comparable creation form here is a page in the shell with a breadcrumb. Its card layout, copy and progression carry over unchanged. Reversing this changes layout only.
- **Where the user lands after saving is out of scope**, as the request states. The form saves and returns to the contact; the confirmation and the list it should feed come later.
- **The type list comes from the account**, not from the mockup's seven hardcoded types, one of which ("date") the product does not supply. Grouped by active category, in recorded order, each showing both roles.
- **The offer is worded as the product spec words it**: "Create a contact record for this person", not the mockup's "contact entry".
- **Relationship, its table, its factory and the action that writes it are new.** The type, the category, their supplied data, the contact, its partial marking and the action creating a contact all exist and are reused.
- **Search reaches the whole vault**, archived and partial people included, since all are legitimate ends of a relationship.
- **The loop check** walks only types carrying the supplied parentage behaviour, never a type merely named Parent.
