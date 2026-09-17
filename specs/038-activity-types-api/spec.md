# Feature Specification: Activity type and category endpoints

**Feature Branch**: `2026-09-17-activity-types-api` | **Created**: 2026-09-17 | **Status**: Draft

**Input**: "add api methods and docs to activity types and categories. also make sure openAPI specs are there too"

## Overview

An account describes what an activity was: lunch, a phone call, a medical appointment, a concert. Each of those is an activity type, filed under a category that organises the settings screen, the picker and the journal filter. A type also decides what an activity of that nature asks for, which the screens call its custom fields.

The categories are already reachable through the API and already documented. The types are not: they ship with settings screens and no API coverage at all, so an account cannot be set up, carried over or read back from a script. This feature is that coverage.

Two smaller gaps travel with it. A caller reading a category cannot ask which types are filed under it, which is how the settings screen presents them and how every other grouped vocabulary in the API is read. And the reference says of a category's type count that activity natures are not built yet, which stopped being true when the settings screens shipped, so a caller is told the number is always zero when it is not. Whatever the API answers must also reach the machine-readable document the reference generates, so a caller writing a client and a caller reading prose are told the same thing.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Read what an account records activities as (Priority: P1)

A caller has to be able to ask which types an account holds, in the order the picker offers them, filed under the category each belongs to, and to read one by its identifier. **Why this priority**: It is the story that hands over an identifier, so every other one depends on it, and it stands alone: a caller holding nothing else can already report the vocabulary an account uses about what its people did together. **Independent test**: list the types of a freshly created account, list the types of one category, and read one back by identifier, confirming the supplied set comes back grouped, ordered and worded in the caller's language.

**Acceptance Scenarios**:

1. **Given** a caller holding a valid credential, **When** they list the types of their account, **Then** each carries its identifier, the category it is filed under, its name, what it covers, how it is drawn, its place inside its category, whether the product supplied it, whether it may still be chosen, when it was created, when it was last changed, and a link to itself.
2. **Given** a caller who asks for the types of one category, **When** the answer comes back, **Then** it holds that category's types in their recorded order, and types that are no longer offered are included rather than left out.
3. **Given** a supplied type nobody has renamed, **When** the caller reads it, **Then** its name comes back in the language the caller asks for, and a type somebody has renamed comes back in the words they wrote, in every language.
4. **Given** a caller reading a category, **When** the answer comes back, **Then** the number of types filed under it is the true number, retired ones counted, rather than always zero.
5. **Given** a type or a category belonging to another account, or one that was deleted, **When** the caller asks for it by identifier, **Then** the answer is not found and nothing about it is disclosed.

### User Story 2 - Add a type, reword it, remove it (Priority: P2)

A caller setting an account up from a script, or carrying a vocabulary over from another tool, has to be able to add a type the product did not supply, reword any type including a supplied one, move it to another category, and take back one that was a mistake. **Why this priority**: It is the reason the list is editable at all, and it is what an import needs. **Independent test**: add a type under a chosen category, reword it, move it to another category, then delete it, and confirm a supplied type refuses deletion.

**Acceptance Scenarios**:

1. **Given** a caller supplying a name and a category, **When** they add the type, **Then** it exists, sits last in that category, may be chosen, carries no custom fields, and the answer describes it.
2. **Given** a caller supplying what the type covers, how it is drawn and what colour it carries, **When** the type is added, **Then** those are recorded, and a caller who supplies none of them gets a type that reads by its name alone.
3. **Given** a caller sending a name that is empty or only spaces, a name longer than the field allows, or a category of another account, **When** the request is answered, **Then** it is refused as invalid or not found, and nothing is added.
4. **Given** a caller rewording a type, **When** the change is made, **Then** its name, what it covers and how it is drawn become what was sent, and its identifier, whether it may be chosen and every activity already recorded with it are untouched.
5. **Given** a caller moving a type to another category, **When** the change is made, **Then** it sits last in the category it arrives in, and what the activities recorded with it say is unchanged.
6. **Given** a caller deleting a type the product did not supply, **When** the request is answered, **Then** the type is gone from the lists, the deletion is recoverable, and the activities already recorded with it are not destroyed.
7. **Given** a caller deleting a type the product supplied, **When** the request is answered, **Then** it is refused, the answer names that reason and points at stopping the type being offered instead, and nothing is deleted.

### User Story 3 - Decide what is offered and in which order (Priority: P3)

Two decisions are not corrections to wording: whether a type may still be chosen for a new activity, and where it sits inside its category. Each is its own gesture and neither may be made by accident while rewording. **Why this priority**: They are what make a vocabulary managed through the API indistinguishable from one managed in the browser, and neither is needed to read or build the list. **Independent test**: stop a type being offered and offer it again, then move it inside its category, and confirm the activities recorded with it are untouched and the category is renumbered from one.

**Acceptance Scenarios**:

1. **Given** a caller who sends the state they want rather than asking for a toggle, **When** the request is answered, **Then** the type ends in that state, and sending the same state twice confirms it rather than inverting it.
2. **Given** a type that is no longer offered, **When** the caller reads the activities recorded with it, **Then** every one is still there and still reads with its nature, its drawing and its custom fields.
3. **Given** a caller who moves a type inside its category, **When** the request is answered, **Then** it sits there, that category is numbered from one again with the retired types counted among them, and a place past the end puts it last.
4. **Given** a caller who names a type of another account in either request, **When** the answer comes back, **Then** it is not found and nothing is changed.

### User Story 4 - Read and rewrite what an activity of one nature asks for (Priority: P4)

A type decides which custom fields an activity of that nature carries, in which groups and in which order. A caller has to be able to read that layout and to write a new one. **Why this priority**: A type is usable without ever touching its fields, since a new type starts with none and still records activities, so this is the last slice rather than the first. It is also the slice a caller carrying a vocabulary over from another tool needs last. **Independent test**: read the fields of a supplied type, send a new layout for a type of the caller's own, read it back, and confirm an activity recorded before the change still reads as it did.

**Acceptance Scenarios**:

1. **Given** a caller reading a type's fields, **When** the answer comes back, **Then** it holds the groups in their order, the fields in each group in their order, and for each field what it is called, what kind of field it is, whether it is required and what it points at when it points at something.
2. **Given** a type nobody has given fields to, **When** the caller reads its fields, **Then** the answer is an empty layout rather than not found.
3. **Given** a caller sending a whole new layout, **When** the request is answered, **Then** the type asks for exactly that from then on, and a field the caller kept is recognised as the same field rather than replaced.
4. **Given** an activity recorded before the layout changed, **When** it is read afterwards, **Then** it still carries the fields and values it was written with, and does not follow the change.
5. **Given** a caller sending a layout naming a kind of field the product does not offer, or a group with no name, **When** the request is answered, **Then** it is refused as invalid and the layout already there is left exactly as it was.

### Edge Cases

- A category is deleted while it still holds types. The deletion is refused, as it already is, and the count the refusal names is now a real number rather than zero.
- Every type of an account is retired. The lists still read, and nothing is offered when an activity is recorded.
- A type is deleted between a caller reading the list and asking for the deletion. The second request answers not found rather than reporting success twice.
- A caller asks for the types of a category belonging to another account. The answer is not found rather than an empty list, so the existence of the category is not disclosed.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The API MUST offer a way to list the activity types of the caller's account in their recorded order inside each category, with those no longer offered included, and MUST offer the types of one named category on their own.
- **FR-002**: Each type the API describes MUST carry its identifier, the category it is filed under, its name, what it covers, how it is drawn, its place inside its category, whether the product supplied it, whether it may still be chosen, when it was created, when it was last changed, and a link to itself.
- **FR-003**: A supplied name nobody has rewritten MUST read in the language the caller asks for; one somebody has rewritten MUST read in their words whatever language is asked for.
- **FR-004**: The API MUST offer a way to read one type of the caller's by identifier, described exactly as the lists describe it.
- **FR-005**: The API MUST offer a way to add a type from a name and a category, optionally with what it covers, how it is drawn and what colour it carries, placing it last in that category, offered, and carrying no custom fields.
- **FR-006**: The API MUST refuse a name that is empty or only spaces or longer than the field allows, MUST reduce a name and a description to plain text so no markup survives, and MUST answer not found for a category outside the caller's account.
- **FR-007**: The API MUST offer a way to reword a type's name, what it covers, how it is drawn and which category it is filed under, supplied or not, leaving its identifier, whether it may be chosen and every activity recorded with it untouched, and MUST place a type arriving in another category last there.
- **FR-008**: The API MUST offer a way to delete a type the product did not supply, recoverably, and MUST refuse to delete a supplied type, naming that reason and pointing at stopping it being offered instead.
- **FR-009**: The API MUST offer a way to say whether a type may still be chosen for a new activity, as a state that is sent rather than a toggle, at its own address, and this MUST NOT touch any activity already recorded.
- **FR-010**: The API MUST offer a way to move a type inside its category, at its own address, renumbering that category from one with the retired types counted among them, and placing a request for a place past the end last.
- **FR-011**: The API MUST offer a way to read the custom fields a type asks for, as groups holding fields in their recorded order, answering an empty layout for a type nobody has given fields to.
- **FR-012**: The API MUST offer a way to write a type's custom fields as one whole layout, matching each field to the one already there so a field the caller kept is not replaced, and MUST leave every activity already recorded reading as it was written.
- **FR-013**: The API MUST refuse a layout naming a kind of field the product does not offer, or a group without a name, leaving the layout already there untouched.
- **FR-014**: Every request here MUST answer not found for a type or a category outside the caller's account, MUST disclose nothing about it, and MUST be recorded in the account's journal the way the same gesture made in a browser is recorded.
- **FR-015**: The number of types a category reports MUST be the true number, retired ones counted, and the reference MUST stop saying that activity natures are not built yet.
- **FR-016**: The public API reference MUST document every one of these endpoints, in the order list, read, add, reword, delete, then the two decisions, then the custom fields, with the same worked request and answer the rest of the reference gives, and the machine-readable description generated from it MUST therefore describe every endpoint added here and none the API does not answer.

### Key Entities

- **Activity category**: an account-owned grouping for activity types, carrying a name, a place in the list, whether the product supplied it and whether it is still offered. It organises the settings, the picker and the journal filter, and gives an activity no behaviour.
- **Activity type**: an account-owned nature an activity can be recorded as, carrying a name, the category it is filed under, what it covers, how it is drawn, its place inside its category, whether the product supplied it and whether it may still be chosen.
- **Custom field layout**: what an activity of one nature asks for, as groups of fields in a recorded order. It belongs to the type, and an activity keeps the layout it was written on rather than following later changes.
- **Activity**: a recorded thing that happened. It names its type rather than copying it, and is never destroyed by any request here.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Every gesture the Activity types settings screens offer can be made through the API, with none left browser-only.
- **SC-002**: A caller can build a full activity vocabulary, grouped, worded, drawn, ordered and given the fields they want, without opening a browser.
- **SC-003**: A type added through the API and one added in the browser are indistinguishable once created.
- **SC-004**: No request through the API can destroy a recorded activity or change what one already recorded says. Every path that would is refused, names the alternative, and is covered by a test.
- **SC-005**: A request naming a type or a category of another account discloses nothing, on every endpoint, and each of those refusals is covered by a test.
- **SC-006**: Every endpoint appears in the public reference and in the machine-readable description, and both agree with the routes in each direction, so none of the three can drift from the others unnoticed.

## Assumptions

- Deleting is soft, as everywhere else in the application, so an accident is recoverable.
- Reading a type does not carry its custom fields. They are read at their own address, so a caller listing a vocabulary is not made to carry every layout it holds.
- The custom fields of a type are written as one whole layout rather than field by field, because that is the one gesture the product has for them. The field by field editing templates offer belongs to templates.
- Carrying a type in or out of an account as a file, which the product can already do internally, is a marketplace capability with its own surface and stays out of scope here.
- The activity record itself, the journal that reads it, and the MCP surface, which exposes a chosen subset of the API rather than mirroring it, all stay out of scope here.
- The reference is written as a new group in the existing docs portal, and needs no new page, route or controller. The machine-readable description is generated from that reference, so documenting an endpoint is what puts it there.
- How these endpoints are addressed, paged and shaped is decided in the planning artifacts beside this file rather than restated here.
