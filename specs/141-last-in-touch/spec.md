# Feature Specification: Last in touch

**Feature Branch**: `2026-11-20-last-in-touch` | **Created**: 2026-11-20 | **Status**: Draft

**Input**: A user reports that on Basic contact, "Last in touch" in the contact header stays "never"
whatever life events they add. "Last in touch" should come from a conversation or an event shared
with the person (a meeting, an encounter), and later drive a reminder to get back in touch.

## Clarifications

### Session 2026-11-20

- Q: Which life events set "Last in touch"? → A: Only those whose type says so. Shared moments such
  as Call, Meal and Meeting do by default, news such as moving does not, and the user can change it
  type by type.
- Q: Is the setting a field or a template option? → A: Neither. It belongs to the life event type and
  is worded as "this life event updates Last in touch".
- Q: Does the stay in touch reminder ship with the fix? → A: No, it is a later feature of its own.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - The header says when I was last in touch (Priority: P1)

Michael records a lunch with Jim on 3 November. Jim's header then reads **Last in touch** "2 weeks
ago", the **Last spoke** column shows the same date, and sorting by it puts Jim among the people
Michael saw recently.

**Why this priority**: it is the reported defect, and the column and the sort are useless without it.

**Independent Test**: record a qualifying life event with a contact, then read the header, the list
column and the API.

**Acceptance Scenarios**:

1. **Given** a contact with no qualifying life event, **Then** the header reads "never".
2. **Given** a contact, **When** a life event of a type that updates **Last in touch** is recorded
   with them, **Then** the header, the list column and the API give its date.
3. **Given** a contact last in touch on 3 November, **When** an older qualifying life event is
   recorded, **Then** the date stays 3 November.
4. **Given** the latest qualifying life event is deleted, moved to another date or loses the contact,
   **Then** the date falls back to the latest one remaining, or "never". Restoring it brings it back.
5. **Given** a life event of a type that does not update **Last in touch**, **Then** recording it
   leaves the date as it was.
6. **Given** contacts holding qualifying life events before release, **Then** their date is filled
   when the feature ships.

---

### User Story 2 - I choose which life events update Last in touch (Priority: P2)

Michael records "Jim moved to Philadelphia". It is news about Jim, not a moment they shared, and must
not change **Last in touch**. Each life event type's settings screen says whether its life events
update **Last in touch** on the people they are recorded with, and Michael can change it.

**Why this priority**: the defaults make story 1 right for most people; changing them is a refinement.

**Independent Test**: turn the setting off on a type, record a life event of it, and confirm the date
does not move; turn it on and confirm the date is recomputed.

**Acceptance Scenarios**:

1. **Given** the supplied types, **Then** each ships with a default chosen by the product.
2. **Given** a type, **When** its setting changes, **Then** every contact with a life event of that
   type gets its date recomputed.
3. **Given** a type the user creates, **Then** it updates **Last in touch** unless changed.
4. **Given** the setting on screen, **Then** its label names **Last in touch** as the header writes
   it, and a line under it says what happens to the people the life event is recorded with.

### Edge Cases

- A life event dated in the future counts from its day onward, not before.
- A life event with an approximate date counts from the first day it can stand for.
- A life event counts for each of its participants; one recorded on the Me contact alone moves nobody.
- Merging contacts keeps the later date, and undoing the merge restores both; rolling back an import
  recomputes the contacts it touched.
- A type no longer offered keeps its setting, and its life events keep counting.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The product MUST record, for each contact, the date of the latest past life event the
  contact took part in whose type updates **Last in touch**.
- **FR-002**: The date MUST be recomputed whenever a life event is recorded, edited, deleted,
  restored, merged or rolled back, when a type's setting changes, and when a future life event's day
  arrives.
- **FR-003**: The header, the **Last spoke** column, its sort and the API MUST read this one date.
- **FR-004**: Contacts existing at release MUST get their date from the life events they hold.
- **FR-005**: Each life event type MUST carry a setting saying whether its life events update **Last
  in touch**. It belongs to the type, beside whether the type is offered, and is not a field or a
  template option.
- **FR-006**: The setting's label MUST reuse **Last in touch** as the header writes it, and a line
  under it MUST say what happens, so a reader understands it without a guide.
- **FR-007**: The supplied types MUST ship with a product default; a type the user creates, or imports
  from the marketplace, MUST update **Last in touch** unless changed.
- **FR-008**: The setting MUST be readable and changeable through the API and the MCP server.
- **FR-009**: The header MUST read "never" only when no qualifying life event exists.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Every contact with a qualifying past life event shows a date, not "never", on release.
- **SC-002**: The header, the list column and the API agree for 100% of contacts.
- **SC-003**: A life event change shows in the date the next time the contact is opened.
- **SC-004**: A reader shown the type setting can say, unaided, which date it changes and on whose page.

## Assumptions

- Being in touch is recorded only through life events; a conversation is a life event.
- The original definition (a life event shared with the Me contact) is dropped: users rarely add
  themselves to a life event, so most dates would stay "never".
- The separate "last life event" date, counting every life event, stays as it is in the API.
- The list and the API keep their names for the date (**Last spoke**, `last_interaction_at`).
- Out of scope for a later feature: a stay in touch rhythm and its reminder, turning the Monica 4
  stay in touch frequency into that rhythm, and suggesting whom to contact.
