# Feature Specification: MCP coverage and stricter section values

**Feature Branch**: `2026-11-25-mcp-coverage-validation` · **Created**: 2026-11-25 · **Status**: Draft

**Input**: Feedback from somebody using the MCP server, on four gaps listed below.

## Context

| Gap | Today |
| --- | --- |
| Repeating fields | The row format is undocumented. A row without `values`, an unknown key in a row, an unknown child field and an unknown top level field are all accepted without an error, through the interface, the API and MCP alike. |
| Delete a contact, contact list columns | The API endpoints exist. Only the MCP tools are missing. |
| Value lists | The interface creates, renames and deletes a list, and adds, renames, deletes, stops offering and reorders a value. The API only lists them, so neither the API nor MCP can change one, against constitution principle X. |
| Duration | Capped at 100,000 minutes for a Duration field and for the duration of a life event, and always shown in minutes, so "131400 minutes" would be the reading even with a higher cap. |

## User Scenarios & Testing *(mandatory)*

### User Story 1 - A wrong section payload is refused and the right one is documented (Priority: P1)

An assistant fills Michael's phone numbers, a repeating field, sending each row as `{"<child id>": "…"}`
instead of `{"values": {"<child id>": "…"}}`. Today the call succeeds and nothing is written. After
this change the call is refused with an error naming the row and what is wrong, and the tool's
description shows the row format, so the next call is right.

**Why this priority**: silent data loss is a defect. The others are missing capabilities.

**Independent Test**: send each malformed shape to the section endpoint and check each is refused
and nothing is written; send the documented shape and check it is written.

**Acceptance Scenarios**:

1. **Given** a section with a repeating field, **When** a row carries a key other than `id`, `values`, `labels`, `label_wordings` and `unknown_year` (child values sent beside `values` instead of in it), **Then** the request is refused and names that row.
2. **Given** the same, **When** a row is not an object, **Then** the request is refused.
3. **Given** the same, **When** `values` names a field that is not a child of the repeating field, **Then** the request is refused.
4. **Given** any section, **When** `fields` names a field that is not in the section, **Then** the request is refused.
5. **Given** a refused request, **Then** no value of the section is changed.
6. **Given** a repeated date, **When** its row says the year is unknown (`unknown_year`), **Then** the date is stored without its year, as a date outside a repeating field is.

### User Story 2 - An assistant deletes a contact and sets the contact list columns (Priority: P2)

Somebody asks their assistant to delete the duplicate "Dwight Schrute (old)" and to show the phone
number and the birthday as columns of the contact list. The assistant does both through MCP.

**Independent Test**: call each new tool and compare its answer with the endpoint it mirrors.

**Acceptance Scenarios**:

1. **Given** a contact, **When** the delete tool is called, **Then** the contact is deleted as the endpoint deletes it and the answer says so.
2. **Given** a vault, **When** the columns are read, **Then** the answer is the endpoint's answer.
3. **Given** a vault, **When** the columns are set, **Then** the contact list shows them, and an invalid column is refused with the endpoint's wording.

### User Story 3 - Value lists through the API and MCP (Priority: P2)

Somebody keeps a list of "Paper suppliers" for a field on a template. Through the API, or by asking
their assistant, they create it, add Staples and Office Depot, rename one, stop offering another,
reorder the values, and delete the list when it is no longer needed.

**Independent Test**: perform each operation through the API, then through its MCP tool, and check the
settings screen shows the same result as when the operation is done there.

**Acceptance Scenarios**:

1. **Given** an account, **When** a list is created, renamed or deleted, **Then** the result matches the interface and is written to the account log.
2. **Given** a list, **When** a value is added, renamed, deleted, stopped, offered again or moved, **Then** the result matches the interface.
3. **Given** a list the application itself reads, **When** its deletion is asked, **Then** it is refused with the interface's wording.
4. **Given** a value in use, **When** its deletion is asked, **Then** it is refused with the interface's wording.
5. **Given** a list that takes no choices of its own, **When** a value is added, **Then** it is refused with the interface's wording.
6. **Given** a list or a value of another account, **Then** it does not exist.

### User Story 4 - A duration of up to a year, shown in readable units (Priority: P3)

Somebody records on Jim that a work trip lasted three weeks, and that the usual gap between two
visits is about three months. Both are accepted, and both read in weeks, days and hours rather than as
a count of minutes.

**Why this priority**: the cap is a real limit, but a rhythm is better served by a reminder, which
already exists; the description of the Duration field says so.

**Independent Test**: write durations of one minute, 100,001 minutes and one year, and read them back.

**Acceptance Scenarios**:

1. **Given** a Duration field or a life event, **When** a duration up to 525,600 minutes (one year) is written, **Then** it is accepted.
2. **Given** the same, **When** 525,601 minutes is written, **Then** it is refused.
3. **Given** a stored duration, **When** it is shown, **Then** it reads in weeks, days, hours and minutes, leaving out the parts that are zero (10,080 minutes reads "1 week"; 90 minutes reads "1 hour 30 minutes").

### Edge Cases

- A row without `values`, or with an empty one: allowed, as a row with nothing filled in; an HTML form cannot send an empty list, so the interface sends exactly that.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: Writing the values of a section MUST refuse each malformed shape listed in User Story 1, through the interface, the API and MCP alike, and write nothing when it refuses.
- **FR-002**: The error MUST name the field and, for a repeating field, the row at fault.
- **FR-003**: The description of `update-contact-section` MUST give the format of a repeating row with an example, and the API documentation of the endpoint MUST give the same.
- **FR-004**: MCP MUST offer a tool to delete a contact, mirroring the existing endpoint, whose description says the deletion cannot be undone by the assistant.
- **FR-005**: MCP MUST offer tools to read and to set the contact list columns of a vault, mirroring the existing endpoints.
- **FR-006**: The API MUST offer every value list operation the interface offers: create, rename and delete a list; add, rename, delete, stop or resume offering, and reposition a value.
- **FR-007**: Each refusal of FR-006 MUST carry the wording the interface shows, and every change MUST be written to the account log as the interface writes it.
- **FR-008**: MCP MUST offer one tool per endpoint of FR-006.
- **FR-009**: The API documentation MUST describe every new endpoint, and the MCP tools page every new tool.
- **FR-010**: A Duration field and the duration of a life event MUST accept 1 to 525,600 minutes, in the interface, the API and MCP.
- **FR-011**: Every screen, API answer and MCP answer that shows a duration as words MUST use the unit breakdown of User Story 4; the API keeps returning the number of minutes as it does today.
- **FR-012**: The description of the Duration field type MUST say that a recurring rhythm is recorded with a reminder.
- **FR-013**: A repeated date MUST honour the row's `unknown_year`, through the interface, the API and MCP.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Every malformed shape of User Story 1 returns an error; none returns success.
- **SC-002**: A duration of three months is accepted and reads in weeks and days, not in minutes.

## Assumptions

- Contacts stay soft deleted, and no restore is added: the interface offers none either.
- The duration input in the interface stays in minutes; only the cap and the reading change.
- The new endpoints reach the lists the settings screen shows, which are the shared lists the existing API list returns.
