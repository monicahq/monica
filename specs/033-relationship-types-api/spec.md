# Feature Specification: Relationship category and type endpoints

**Feature Branch**: `2026-09-13-api` | **Created**: 2026-09-13 | **Status**: Draft

**Input**: "add api and docs to manage relationship category types and relationship types"

## Overview

An account holds the vocabulary it puts between two people: Parent / Child, Sibling, Partner, Friend, Colleague. Each is a relationship type, grouped under a category that organises the settings screen and the picker and means nothing more. The settings screens ship (spec 024) and deferred their API coverage; this feature is that coverage, plus its page in the public reference. Relationships themselves now exist (specs 029 and 031), so every rule that counts how many relationships use a type bites from the first day.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Read the vocabulary an account uses (Priority: P1)

A caller has to be able to ask which categories an account holds, which types sit under each, in the order the picker offers them, and to read one of either by its identifier.

**Why this priority**: It is the story that hands over an identifier, so every other one depends on it, and it stands alone: a caller holding nothing else can already report the words an account uses about the people in it. **Independent test**: list the categories and the types of a freshly created account and confirm the supplied set comes back grouped, ordered, worded in the caller's language, and that one of each reads back by identifier.

**Acceptance Scenarios**:

1. **Given** a caller holding a valid credential, **When** they list the categories of their account, **Then** each carries its identifier, its name, its place in the order, whether the product supplied it, whether it is still offered, how many types it holds, when it was created and last changed, and a link to itself.
2. **Given** a caller listing the types of their account, **When** the answer comes back, **Then** each type carries its identifier, the category it sits in, its name, the role each side reads with, whether both sides read the same word, the product behaviour attached to it or none, its place inside its category, whether the product supplied it, whether it may still be chosen, how many relationships use it, and a link to itself.
3. **Given** a supplied type nobody has reworded, **When** the caller reads it, **Then** its name and its roles come back in the caller's language, and a type the user has reworded comes back in the words they wrote, in every language.
4. **Given** a caller who asks for the types of one category, **When** the answer comes back, **Then** it holds that category's types in their recorded order, and categories and types that are no longer offered are included rather than left out.
5. **Given** a category or a type belonging to another account, or one that was deleted, **When** the caller asks for it by identifier, **Then** the answer is not found and nothing about it is disclosed.

---

### User Story 2 - Add a relationship type, reword it, remove it (Priority: P2)

A caller setting an account up from a script, or carrying a vocabulary over from another tool, has to be able to add a type the product did not supply, reword any type including a supplied one, and take back one that was a mistake.

**Why this priority**: It is the reason the list is editable at all, and it is what an import needs. **Independent test**: add a directed type, reword it, then delete it, and confirm it is refused once a relationship uses it.

**Acceptance Scenarios**:

1. **Given** a caller supplying a name, a category, a direction and a role for each side, **When** they add the type, **Then** it exists, sits last in its category, may be chosen, carries no product behaviour, and the answer describes it.
2. **Given** a caller declaring the type symmetric and sending one role, **When** it is added, **Then** both sides read that word, and a caller declaring it directed without a role for each side is refused as invalid.
3. **Given** a caller sending a name another type of the account already carries, or a name or a role made only of spaces, **When** the request is answered, **Then** it is refused as invalid and nothing is added.
4. **Given** a caller rewording a type, **When** the change is made, **Then** its name, its roles and its category become what was sent, and its identifier, its direction, its product behaviour, its place in the order and every relationship using it are untouched.
5. **Given** a caller rewording the two roles of a directed type, **When** the change is made, **Then** the two people are not swapped: the first role stays on the first person of every relationship already recorded.
6. **Given** a caller changing the direction of a type the product supplied, or of one that relationships already use, **When** the request is answered, **Then** it is refused, the answer names that reason, and a user-created type nothing uses accepts the change.
7. **Given** a caller deleting a user-created type no relationship uses, **When** the request is answered, **Then** the type is gone from the lists and the deletion is recoverable.
8. **Given** a caller deleting a type the product supplied, or one that relationships use, **When** the request is answered, **Then** it is refused, the answer names that reason and points at stopping the type being offered instead, and nothing is deleted.

---

### User Story 3 - Organise the categories (Priority: P3)

A caller adds a grouping of their own, renames it, and removes it once it holds nothing. The supplied categories may be moved but never renamed or deleted.

**Why this priority**: A category only groups the list, so the vocabulary is usable without ever adding one. **Independent test**: add a category, rename it, then delete it while empty, and confirm the deletion is refused while it still holds a type.

**Acceptance Scenarios**:

1. **Given** a caller supplying a name, **When** they add the category, **Then** it exists, sits last in the list, holds no type, may be chosen, and the answer describes it.
2. **Given** a caller renaming or deleting a category the product supplied, **When** the request is answered, **Then** it is refused and the answer names that reason.
3. **Given** a caller deleting a user-created category holding no type, **When** the request is answered, **Then** it is gone and the deletion is recoverable.
4. **Given** a caller deleting a user-created category that still holds types, **When** the request is answered, **Then** it is refused, the answer says how many types it holds and asks for them to be moved first, and nothing is deleted.

---

### User Story 4 - Decide what is offered and in which order (Priority: P4)

Two decisions are not corrections to wording: whether a category or a type may still be chosen for a new relationship, and where it sits. Each is its own gesture and neither may be made by accident while rewording.

**Why this priority**: They are what make a vocabulary managed through the API indistinguishable from one managed in the browser, and neither is needed to use it. **Independent test**: stop a type being offered and offer it again, then move it inside its category, and confirm the relationships using it are untouched and the order is renumbered from one.

**Acceptance Scenarios**:

1. **Given** a caller who sends the state they want rather than asking for a toggle, **When** the request is answered, **Then** the category or the type ends in that state, and sending the same state twice confirms it rather than inverting it.
2. **Given** a category or a type that is no longer offered, **When** the caller reads the relationships using it, **Then** every one is still there and still reads with its roles, because stopping it is about what may be chosen next and nothing else.
3. **Given** a caller who moves a category in the list, or a type inside its category, **When** the request is answered, **Then** it sits there, its level is numbered from one again, and a place past the end puts it last.
4. **Given** a caller who names a category or a type of another account in any of these requests, **When** the answer comes back, **Then** it is not found and nothing is changed.

---

### Edge Cases

- A type is moved into a category that is then deleted. The deletion is refused while the type is there.
- A type is moved between categories. It sits last in the one it arrives in, and what the product does with it is unchanged.
- Every type of an account is stopped. The lists still read, and nothing is offered for a new relationship.
- A type is deleted between a caller reading the list and asking for the deletion. The second request answers not found rather than reporting success twice.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The API MUST offer a way to list the categories of the caller's account in their recorded order, and the types of the account in their recorded order inside each category, with those no longer offered included, and MUST offer the types of one named category on their own.
- **FR-002**: Each category the API describes MUST carry its identifier, its name, its place in the order, whether the product supplied it, whether it may still be chosen, how many types it holds, when it was created, when it was last changed, and a link to itself.
- **FR-003**: Each type the API describes MUST carry its identifier, its category, its name, the role each side reads with, whether both sides read the same word, the product behaviour attached to it or none, its place inside its category, whether the product supplied it, whether it may still be chosen, how many relationships use it, when it was created, when it was last changed, and a link to itself.
- **FR-004**: A supplied name or role the user has not rewritten MUST read in the language the caller asks for; one the user has rewritten MUST read in their words whatever language is asked for.
- **FR-005**: The API MUST offer a way to read one category and one type of the caller's by identifier, described exactly as the lists describe them.
- **FR-006**: The API MUST offer a way to add a type from a name, a category, a direction and a role for each side, placing it last in its category, offered, carrying no product behaviour.
- **FR-007**: The API MUST require a role for each side of a directed type, MUST record the single role of a symmetric type on both sides, and MUST refuse a name or a role that is empty or only spaces, or a name another type of the account already carries.
- **FR-008**: The API MUST offer a way to reword a type's name, roles and category, supplied or not, leaving its identifier, its product behaviour, its order, whether it may be chosen and the relationships using it untouched, and MUST NOT swap the two people when the roles of a directed type are reworded. Its direction MUST stay as it is unless a direction is sent.
- **FR-009**: The API MUST refuse to change the direction of a type the product supplied, or of one that relationships already use, naming that reason.
- **FR-010**: The API MUST offer a way to delete a user-created type no relationship uses, recoverably, and MUST refuse to delete a supplied type or one that relationships use, naming that reason and pointing at stopping it being offered instead.
- **FR-011**: The API MUST offer a way to add a category from a name, placed last and offered, and to rename a user-created one.
- **FR-012**: The API MUST refuse to rename or delete a category the product supplied, and MUST refuse to delete a user-created category that still holds types, saying how many it holds.
- **FR-013**: The API MUST offer a way to delete a user-created category holding no type, recoverably.
- **FR-014**: The API MUST offer a way to say whether a category or a type may still be chosen for a new relationship, as a state that is sent rather than a toggle, at its own address, and this MUST NOT touch any relationship already recorded.
- **FR-015**: The API MUST offer a way to move a category in the list and a type inside its category, each at its own address, renumbering that level from one and placing a request for a place past the end last.
- **FR-016**: A type added or reworded through the API MUST NOT acquire product behaviour from its name or its category.
- **FR-017**: Every request here MUST answer not found for a category or a type outside the caller's account, MUST disclose nothing about it, and MUST be recorded in the account's journal the way the same gesture made in a browser is recorded.
- **FR-018**: The public API reference MUST document every one of these endpoints, categories then types, in the order list, read, add, reword, delete, then the two decisions, with the same worked request and answer the rest of the reference gives.

### Key Entities

- **Relationship category**: an account-owned grouping for relationship types, carrying a name, a place in the list, whether the product supplied it and whether it is still offered. It groups and nothing more.
- **Relationship type**: an account-owned kind of link between two people, carrying a name, its category, a role for each side, whether both sides read the same word, whether the product supplied it, the product behaviour attached to it, its place inside its category and whether it may still be chosen.
- **Relationship**: a recorded link between two contacts. Counted by its type, and never touched by any request here.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Every gesture the Relationship types settings screens offer can be made through the API, with none left browser-only, which discharges the API coverage spec 024 deferred.
- **SC-002**: A caller can carry a full vocabulary over from another tool, grouped, worded and ordered as they want it, without opening a browser.
- **SC-003**: A type added through the API and one added in the browser are indistinguishable once created.
- **SC-004**: No request through the API can destroy a recorded relationship. Every path that would is refused, names the alternative, and is covered by a test.
- **SC-005**: A request naming a category or a type of another account discloses nothing, on every endpoint, and each of those refusals is covered by a test.
- **SC-006**: Every endpoint appears in the public API reference, and the reference and the routes agree in both directions, so neither can drift from the other unnoticed.

## Assumptions

- Categories and types are two collections rather than one nested answer, each addressed by uuid as vaults, contacts and entity types are. The grouped reading the settings screen shows is built by a caller from the two, or by asking for the types of one category.
- Lists are paginated the way the other collection endpoints are, with the same page size bounds, and are not searched or sorted: an account holds a handful of each and the picker order is the only order that means anything.
- Whether something may be chosen, and where it sits, are each sent at their own address and never as part of a rewording, mirroring how the browser routes those gestures to their own actions.
- Stopping a category being chosen is the one gesture here the browser does not offer. The picker already leaves out an inactive category and its types, and the model note lists deactivation among what a user may do, so the API makes a live capability reachable rather than inventing one. The settings screen may grow the control later without changing this endpoint.
- Rewording a supplied name or role replaces it in every language, and the supplied wording does not come back. That is what the settings screen already does, and restoring it would be a change to the vocabulary record rather than to its API coverage.
- Deleting is soft, as everywhere else in the application, so an accident is recoverable.
- The relationship record itself, and the family tree and romantic history that read it, are separate capabilities with their own API coverage and stay out of scope here.
- The reference is written as a new group in the existing docs portal, and needs no new page, route or controller.
