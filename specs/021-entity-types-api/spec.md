# Feature Specification: Entity type endpoints

**Feature Branch**: `2026-09-05-api-entity-types` | **Created**: 2026-09-05 | **Status**: Draft

**Input**: "in a new branch, i want to manage entity types through the api + docs"

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Read the kinds of object an account invented (Priority: P1)

A type is the noun an account added when nothing the product ships with fits: Animal, Company, Recipe, Wine bottle. A caller has to be able to ask which types an account holds, in the order they appear in the navigation, and read one of them by its identifier.

**Why this priority**: It is the story that hands over an identifier, so every other one depends on it, and it stands alone: a caller holding nothing else can already report what an account keeps track of. **Independent test**: list the types of an account holding an active type, an inactive one and one that already holds records, and confirm the order, the counts and that one type reads back by identifier.

**Acceptance Scenarios**:

1. **Given** a caller holding a valid credential, **When** they list the types of their account, **Then** each type carries its identifier, its slug, both its names, its description, its icon, its colour, its place in the order, whether records may still be filed under it, how many records it holds, when it was created and last changed, and a link to itself.
2. **Given** an account holding types in a chosen order, **When** the caller lists them, **Then** they come back in that order, with inactive types among them rather than left out, because the settings screen shows them all.
3. **Given** more types than one page holds, **When** the caller asks, **Then** they get one page, how many there are in all, and the means to ask for the next, and an account with no type of its own answers an empty list rather than an error.
4. **Given** a type belonging to another account, or one that was deleted, **When** the caller asks for it by identifier, **Then** the answer is not found and nothing about it is disclosed.

---

### User Story 2 - Declare a type, correct it, remove it (Priority: P2)

Declaring the noun is the gesture that opens a whole area of the product, and today it can only be made in a browser. A caller setting an account up from a script, or shipping a starter set of types, has to be able to make it, reword it later, and take back one that was a mistake.

**Why this priority**: It turns the API from a reporting surface into one that can build an account, which is what an import and a migration from another tool need. **Independent test**: declare a type with names alone, correct its wording, then delete it, and confirm it appears in the list, keeps the slug it was given, and refuses to go once something is filed under it.

**Acceptance Scenarios**:

1. **Given** a caller supplying a singular name and a plural name, **When** they declare the type, **Then** it exists, is placed last in the order, holds no records, may be filed under, is given the default icon and colour, and the answer describes it.
2. **Given** a caller supplying a description, an icon and a colour the product offers, **When** the type is declared, **Then** each is kept, and one supplying an icon or a colour the product does not offer is refused as invalid.
3. **Given** a caller who chooses the slug, **When** the type is declared, **Then** that slug is used once cleaned, and one already taken in the account, including by a deleted type, is refused as invalid.
4. **Given** a caller who chooses no slug, **When** the type is declared, **Then** one is made from the singular name, made unique within the account, and the answer says what it is.
5. **Given** a caller correcting a type, **When** the change is made, **Then** the names, the description, the icon and the colour become what was sent, and the slug, the order, the counts and everything filed under the type are untouched, so links that already went out keep working.
6. **Given** a caller who deletes a type holding no record, **When** the request is answered, **Then** the type is gone from the lists, the deletion is recoverable, and the slug stays taken so a stale link cannot land on a different type later.
7. **Given** a caller who deletes a type that still holds records, **When** the request is answered, **Then** it is refused, the answer says the type is still in use, and nothing is deleted.

---

### User Story 3 - Decide where a type sits and whether it is still offered (Priority: P3)

Two decisions about a type are not corrections to its wording: whether somebody may still file records under it, and where it sits in the navigation. Each is a single gesture on the settings screen, and neither may be made by accident while renaming.

**Why this priority**: They are what make a type managed through the API indistinguishable from one managed in the browser, and they come last because a type without either is already usable. **Independent test**: stop a type being offered and offer it again, then move it up the order, and confirm what is filed under it is untouched and the order is renumbered from one.

**Acceptance Scenarios**:

1. **Given** a caller who sends the state they want rather than asking for a toggle, **When** the request is answered, **Then** the type ends in that state, and sending the same state twice confirms it rather than inverting it.
2. **Given** a type that is no longer offered, **When** the caller reads what is filed under it, **Then** every record is still there and still readable, because stopping a type is about the button that adds another and nothing else.
3. **Given** a type that is no longer offered, **When** it is offered again, **Then** it returns to the place it always had rather than to the end.
4. **Given** a caller who moves a type to a place in the order, **When** the request is answered, **Then** the type sits there, every type of the account is numbered from one again, and a place past the end puts the type last.
5. **Given** a caller who names a type of another account in any of these requests, **When** the answer comes back, **Then** it is not found and nothing is changed.

---

### Edge Cases

- A type is declared with a name made only of characters a slug cannot carry. A slug is still produced, because a type with no address could not be reached at all.
- A slug is chosen that a deleted type once used. It is refused, since the deleted type never released it.
- A type is renamed from Animal to Pet. It is the same type: the slug, the records and the order stay, and only the wording moves.
- A type is deleted between a caller reading the list and pressing delete. The second deletion answers not found rather than reporting success twice.
- The last type of an account is deleted. The account is left with none, which is a valid state: types are what an account invents, not what it must have.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The API MUST offer a way to list the types of the caller's account, in the order they appear in the navigation, with inactive types included.
- **FR-002**: Each type the API describes MUST carry its identifier, its slug, its singular and plural names, its description, its icon, its colour, its place in the order, whether records may still be filed under it, how many records it holds, when it was created, when it was last changed, and a link to itself.
- **FR-003**: The API MUST offer a way to read one type of the caller's by its identifier, described exactly as the list describes it.
- **FR-004**: The API MUST offer a way to declare a type from a singular name and a plural name, with the description, the icon, the colour, the slug and whether it is offered all optional.
- **FR-005**: The API MUST refuse an icon or a colour outside the sets the product offers, and MUST refuse a slug that is malformed, too long, or already taken in the account by any type including a deleted one.
- **FR-006**: The API MUST settle a slug when none is supplied, from the singular name, unique within the account, and MUST NOT rebuild it afterwards for any reason.
- **FR-007**: The API MUST offer a way to correct a type's names, description, icon and colour, and MUST leave its slug, its order, its counts and its records untouched.
- **FR-008**: The API MUST offer a way to delete a type that holds no record, recoverably, and MUST refuse to delete one that holds records with an answer naming that reason.
- **FR-009**: The API MUST offer a way to say whether records may still be filed under a type, as a state that is sent rather than a toggle, at its own address, and this MUST NOT touch anything already filed under the type.
- **FR-010**: The API MUST offer a way to move a type in the order, at its own address, renumbering every type of the account from one, and placing a type asked for a place past the end last.
- **FR-011**: Every one of these requests MUST answer not found for a type outside the caller's account, and MUST disclose nothing about it.
- **FR-012**: Every one of these requests MUST be recorded in the account's journal the way the same gesture made in a browser is recorded.
- **FR-013**: The public API reference MUST document every one of these endpoints, in the order list, read, declare, correct, delete, then the two decisions, with the same worked request and answer the rest of the reference gives.

### Key Entities

- **Entity type**: a kind of object an account invented. Belongs to the account, is addressed by an identifier and carries a slug, two names, a description, an icon, a colour, a place in an order, whether it is still offered, and how many records exist of it.
- **Entity**: a record of that kind, living in a vault. Counted by its type, never touched by any request here.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Every gesture the entity types settings screen offers can be made through the API, with no gesture left browser-only.
- **SC-002**: A caller can set up an account's full set of types, ordered and worded as they want them, without opening a browser.
- **SC-003**: A type declared through the API and one declared in the browser are indistinguishable once created, including how their slugs are settled.
- **SC-004**: Every endpoint appears in the public API reference, and the reference and the routes agree in both directions, so neither can drift from the other unnoticed.
- **SC-005**: A request naming a type of another account discloses nothing, on every endpoint, and each of those refusals is covered by a test.

## Assumptions

- A type is addressed through the API by its uuid, not by its slug, matching how vaults and contacts are addressed and what the model already says the uuid is for. The slug is carried in the answer so a caller can build a browser link.
- The list is paginated the way the other collection endpoints are paginated, with the same page size bounds, and is not filtered, searched or sorted: an account holds a handful of types and the navigation order is the only order that means anything.
- Whether records may be filed under a type is sent at declaration and afterwards changed only at its own address, mirroring how the browser routes that checkbox to its own actions.
- Which template a type renders from in a given vault is a separate capability with its own screen, and stays out of scope here.
- The reference is written as a new group in the existing docs portal, and needs no new page, route or controller.
