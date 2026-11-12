# Feature Specification: Possessions

**Feature Branch**: `2026-10-25-location-management`
**Created**: 2026-10-26 · **Status**: Draft
**Input**: "i want to manage the possession model, along with crud actions + mcp/api + docs." A table of
attributes: name, description, type, current location, identifier, condition, status, quantity,
featured, position. "do not do UI for now."

Specification 088 added possession types and their fields, 086 and 087 the locations, and 090 a
placeholder All possessions screen. This adds the possessions themselves: what somebody owns, in one
vault, of one type, optionally at one location, with a value for each field its type carries. It is
reached through the API and the assistant only; the screens come later.

## Clarifications

- Q: Where does the condition live? → A: Nowhere yet. Condition is left out of this feature entirely.
- Q: A type or a location is deleted while possessions use it? → A: Cascade. The type's possessions are deleted with it; a deleted location is cleared from the possessions it held.
- Q: Do possessions record values for their type's fields now? → A: Yes.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Record a possession (Priority: P1)

Through the API or the assistant, somebody records “Michael’s Chrysler Sebring” in the Dunder Mifflin
vault: type Car, at the location Scranton parking lot, identifier `1C3EL55R`, status owned, quantity 1.

**Why this priority**: without it nothing else exists.

**Independent Test**: create one, read it back, list the vault.

**Acceptance Scenarios**:

1. **Given** a vault and a type of the account, **When** a possession is created with a name and that type, **Then** it is stored with status owned, quantity 1, not featured, and is returned with every attribute.
2. **Given** a missing name, a type or a location of another account, a quantity below 1, or an unknown status, **When** created, **Then** nothing is stored and each problem is named.
3. **Given** a vault holding possessions, **When** listed, **Then** they come back by name, can be narrowed by type, status and featured, and nothing of another vault or account appears.
4. **Given** a possession, **When** its name, description, location, identifier, status or quantity changes, **Then** the change is stored and the rest is left as it was.
5. **Given** a possession, **When** deleted, **Then** it no longer appears anywhere and its field values go with it.

---

### User Story 2 - Record the details its type asks for (Priority: P1)

The Car type asks for Plate, Fuel and First registration. Somebody gives the Sebring `PA-1234`, Petrol
and 2003-05-01.

**Why this priority**: a type's fields are the point of having types.

**Independent Test**: write values of every kind on one possession and read them back.

**Acceptance Scenarios**:

1. **Given** a possession, **When** values are written for fields of its type, **Then** each is stored and returned with its field’s name, group and kind, in the type’s order.
2. **Given** a value that does not fit its field (text in a number, a date that is not one, a choice not offered, a rating outside 1 to 5), **When** written, **Then** nothing is stored and the field is named.
3. **Given** a field of another type, **When** written, **Then** it is refused.
4. **Given** an empty value, **When** written, **Then** the field’s value is removed.
5. **Given** a field whose kind changes, or which is removed, **When** that happens, **Then** its values are deleted with it.

---

### User Story 3 - Feature a few possessions in order (Priority: P2)

Somebody marks the Sebring and the World’s Best Boss mug as featured and puts the mug first.

**Why this priority**: it orders what the future screens show first; nothing depends on it.

**Independent Test**: feature three possessions, move one, list the featured ones.

**Acceptance Scenarios**:

1. **Given** a possession marked featured, **Then** it is placed last among the vault’s featured possessions.
2. **Given** a featured possession moved to a position, **Then** the others shift and the order holds no gap.
3. **Given** a featured possession no longer featured, **Then** it leaves the order and the others close the gap.

---

### User Story 4 - Learn what the API and the assistant offer (Priority: P3)

A developer reads the API reference; somebody asks their assistant what it can do with possessions.

**Acceptance Scenarios**:

1. **Given** the API reference, **Then** every possession endpoint is described with its fields, statuses, refusals and an example.
2. **Given** an assistant connected to the account, **Then** it can list, read, create, change, delete and order possessions and write their values, within the account the key belongs to.

### Edge Cases

- A select field’s option is renamed or removed: values already written keep the old wording.
- A possession is deleted while featured: the featured order closes the gap.
- A type with no field: the possession holds no value, and that is valid.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: A possession MUST belong to one vault and one possession type of the same account, and MAY have one current location of that account.
- **FR-002**: A possession MUST have a name of 1 to 255 characters, and MAY have a description of up to 2,000 characters and an identifier of up to 255.
- **FR-003**: Status MUST be one of: owned, ordered, lent, sold, given away, lost, stolen, thrown away; owned by default.
- **FR-004**: Quantity MUST be a whole number from 1 to 1,000,000, 1 by default.
- **FR-005**: The type of a possession MUST NOT change once created.
- **FR-006**: A value MUST fit its field: text up to 2,000 characters, a number, a calendar date, yes or no, one of the field’s current choices, or a rating from 1 to 5.
- **FR-007**: Featured possessions MUST have a gapless position within their vault; others MUST have none.
- **FR-008**: Deleting a possession MUST remove it from every list and delete its values.
- **FR-009**: Deleting a possession type MUST delete its possessions and their values, with the count in the account’s log and in the assistant’s answer; deleting a location MUST clear it from its possessions and leave the rest as it was.
- **FR-010**: Every operation MUST be available through the API and through the assistant, scoped to the account of the key.
- **FR-011**: Creating, changing and deleting a possession MUST be written in the account’s log.
- **FR-012**: The API reference and the assistant’s tool descriptions MUST describe every operation.

## Success Criteria *(mandatory)*

- **SC-001**: A possession with values for six fields, one of each kind, is recorded and read back identical through the API and through the assistant.
- **SC-002**: No request reaches a possession, type, location or field of another account.
- **SC-003**: Every endpoint and tool appears in the API reference and the tool list on the day it ships.

## Assumptions

- No screen: the All possessions placeholder of 090 stays as it is.
- Condition is absent from the record, the API and the assistant.
- Cascade on type deletion departs from principle VIII (nothing disappears silently), at the user’s request; the response saying how many possessions went is what keeps it from being silent.
- A deleted possession is deleted for good, with its values. Specs 098, 099, 102 and 103 settled this: the card says it is final, and its transactions, documents, insurance and maintenance records go with it.
- The status list above extends the input’s “owned, ordered, sold, given away, lost…”.
