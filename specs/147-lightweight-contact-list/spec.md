# Feature Specification: A lightweight list of contacts

**Feature Branch**: `2026-11-22-lightweight-contact-list`

**Created**: 2026-11-22

**Status**: Draft

**Input**: User description: "Add a lightweight list of contacts through MCP and the API, so an agent can quickly give me the list of my contacts. I need the id of each contact, its name, and whether it is partial. I also want a filter on partial or not partial. By default, the list leaves out archived contacts."

## Context

The contact list of the API and of the assistant carries every column a vault shows, and it leaves
out partial contacts unless asked for them alone. Asking an assistant "who is in my vault?" therefore
costs several heavy pages, and partial contacts and full contacts never come back together, so an
assistant cannot see that a person it is about to create already exists as a partial contact.

## Clarifications

### Session 2026-11-22

- Q: One vault per request, or every vault of the account? → A: One vault, like every other contact list.
- Q: Can the caller ask for archived contacts? → A: Yes, as an opt in; each entry then says whether it is archived.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Ask an assistant for every contact (Priority: P1)

Michael asks his assistant "list everybody in my Dunder Mifflin vault". The assistant reads the
lightweight list and answers with every name, saying which ones are only partial (Jan's children,
known by first name). Each line carries the id the other tools accept, so Michael can follow up with
"open Pam's page" without a second search.

**Why this priority**: This is the request. Everything else narrows it.

**Independent Test**: In a vault holding full, partial and archived contacts, ask for the list with
no filter and check that every full and partial contact comes back once, with its id, its name and
whether it is partial, and no archived contact.

**Acceptance Scenarios**:

1. **Given** a vault with Pam and Jim (full), Astrid (partial) and Roy (archived), **When** the list
   is asked for with no filter, **Then** it holds Astrid, Jim and Pam, each with its id, its name and
   whether it is partial, and not Roy.
2. **Given** an entry of the list, **When** its id is passed to any other contact tool or endpoint,
   **Then** it addresses that contact.
3. **Given** a vault with more contacts than one page holds, **When** the list is asked for, **Then**
   it comes back a page at a time and says how to ask for the next page.
4. **Given** a vault of another account, **When** the list is asked for, **Then** it is refused as not
   existing.

---

### User Story 2 - Only partial, or only full, contacts (Priority: P2)

Before naming a new person, an assistant checks whether a partial contact of that name already
exists, and asks for the partial contacts only. Michael, tidying up, asks for the full contacts only.

**Why this priority**: The filter is what lets an assistant avoid creating the same person twice.

**Independent Test**: In the same vault, ask for the partial contacts, then for the full contacts,
and check each answer holds exactly one kind.

**Acceptance Scenarios**:

1. **Given** the vault above, **When** the list is asked for partial contacts, **Then** it holds
   Astrid only.
2. **Given** the vault above, **When** the list is asked for full contacts, **Then** it holds Jim and
   Pam only.
3. **Given** a filter value that is neither, **When** the list is asked for, **Then** the request is
   refused with a message naming the accepted values.
4. **Given** the vault above, **When** the list is asked for with archived contacts included,
   **Then** it holds Astrid, Jim, Pam and Roy, and only Roy is marked archived.

---

### Edge Cases

- An empty vault, or a filter matching nobody: the list is empty, not an error.
- The contact of the account holder ("me"): listed like any other contact.
- A partial contact with no name: listed with whatever name the product shows for it elsewhere.
- Two contacts with the same name: both are listed, told apart by their ids.
- A contact deleted and waiting in the bin: never listed.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The API MUST offer a list of the contacts of one vault, giving for each contact only its id, its name, whether it is partial and whether it is archived.
- **FR-002**: The id MUST be the one every other contact endpoint and tool accepts.
- **FR-003**: The name MUST be the name the product shows for that contact in the reader's account.
- **FR-004**: With no filter, the list MUST hold every contact that is not archived and not deleted, partial or not.
- **FR-005**: A filter MUST narrow the list to partial contacts only, or to full contacts only.
- **FR-006**: Archived contacts MUST be left out by default, and a parameter MUST let the caller include them, combined with the partial filter.
- **FR-007**: The list MUST be ordered the way the contact list of the vault orders names.
- **FR-008**: The list MUST be paged like every other list of the API, and a page MAY hold more entries than the full contact list allows, since each entry is small.
- **FR-009**: The MCP server MUST offer the same list through the API, adding no rule of its own.
- **FR-010**: The API reference MUST describe the list and its filter.

### Key Entities

- **Contact entry**: one line of the list: the contact's id, its name, whether it is partial and whether it is archived.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: An assistant can name every contact of a vault of 500 people in at most 2 requests.
- **SC-002**: One entry of the list is at most a tenth of the size of the same contact in the full contact list.
- **SC-003**: A list asked for with no filter holds every non archived contact of the vault exactly once.
- **SC-004**: An assistant asked to add a person who already exists as a partial contact finds them in one request.

## Assumptions

- No search parameter: the existing contact list already searches, and this list exists to be complete.
- "Partial" means what the product already calls it: somebody known through a relationship, without a record of their own.
- The list reads data the account already exposes through the API; it needs no new permission.
- Nothing changes in the interface.
