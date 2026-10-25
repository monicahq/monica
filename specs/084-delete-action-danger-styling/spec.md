# Feature Specification: Delete actions drawn as danger

**Feature Branch**: `claude/delete-action-danger-styling-3656a2` | **Created**: 2026-10-25 | **Status**: Draft

**Input**: User description: "change "delete" action design to appear as red/danger (red box, red button)"

## Context

Today the same act, destroying something for good, appears in several looks:

| Screen | Box around it | Button that opens the warning |
| --- | --- | --- |
| Vault, life event type | Red box | Solid red button |
| Contact edit, "Delete contact" | Plain box | Plain button, red text |
| Entity type, relationship type, template | None, at the foot of the form | Plain button, red text |
| Part of a screen (template field or draft, contact photo, notification setup) | A section that does other things | Red text, red on hover only, or no red |
| Rows of a list (a value, a relationship, a setting) | None | Quiet button, red text or red on hover |

The warning card that opens afterwards is already consistent: its confirming button is solid red.

## Clarifications

### Session 2026-10-25

- Q: Is a delete button in a list row solid red, or quiet with red text? → A: Quiet with red text (FR-007).
- Q: Do solid red buttons that do not delete (withdraw, deactivate, revoke) keep their red? → A: Yes (FR-009).
- Q: A delete button outside a section and a row? → A: The template's becomes a section; others follow rows (FR-003, FR-008).

## User Scenarios & Testing *(mandatory)*

### User Story 1 - A deletion section reads as dangerous (Priority: P1)

The section that deletes what a screen shows stands apart in red with a solid red button.

**Why this priority**: these delete the largest things in the product, and their looks disagree today.

**Independent Test**: open each of the six screens and compare the section and its button.

**Acceptance Scenarios**:

1. **Given** the vault settings, **When** the person reaches "Delete this vault", **Then** it is a red
   box with a solid red button, as today.
2. **Given** any other of the six screens (FR-003), **When** the person reaches its deletion section,
   **Then** it is the same red box and button, the template's standing apart from the form.
3. **Given** any of these buttons, **When** it is activated, **Then** the existing warning card opens
   and nothing is deleted before the person confirms.

### User Story 2 - A refused deletion still reads as a deletion (Priority: P2)

A type with records shows the same red section, its button unavailable and its reason beside it.

**Why this priority**: a refused control stays on screen and must not tell a second story.

**Independent Test**: open a type that cannot be deleted.

**Acceptance Scenarios**:

1. **Given** an entity type with records, **When** the person reaches its section, **Then** it is a red
   box and the button is red, dimmed, and does nothing when activated.
2. **Given** that button, **When** a screen reader reaches it, **Then** it is announced as unavailable
   with its reason.

### User Story 3 - Delete buttons inside lists (Priority: P3)

A list whose rows each carry a delete button stays readable, with the red kept quiet.

**Why this priority**: frequent, smaller deletions; a column of solid red would drown the big ones.

**Independent Test**: open a list with at least five deletable rows.

**Acceptance Scenarios**:

1. **Given** a list of deletable rows, **When** the person looks at it, **Then** every delete button
   is a quiet button with red text at rest, the same in every list.
2. **Given** any of them, **When** it is activated, **Then** the existing warning card opens.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: A section whose purpose is to delete the record the screen is about MUST be a danger box:
  red edge, red tinted heading band, heading in red.
- **FR-002**: The button in such a section that opens the warning MUST be solid red.
- **FR-003**: FR-001 and FR-002 MUST look identical on the vault, contact, template, entity type,
  relationship type and life event type screens.
- **FR-004**: A refused deletion button MUST keep the danger look while unavailable, stay on screen,
  be impossible to activate, and point at its reason for assistive technology.
- **FR-005**: Every delete button MUST open the existing warning card before anything is destroyed,
  then the password prompt where one is asked for today.
- **FR-006**: The warning card's confirming button MUST stay solid red.
- **FR-007**: A delete button in a list row MUST be a quiet button with red text at rest, the same in
  every list, never solid red and never in a red box.
- **FR-008**: A button that destroys a part of the screen rather than its record, under "Delete" or
  "Remove", MUST follow FR-007; one already solid red (removing the password) keeps it.
- **FR-009**: The feature MUST NOT give the danger look to an action that destroys no data and lacks it
  today; the solid red buttons that already mark one (withdraw, deactivate, revoke, disconnect, turn off
  two factor authentication, replace recovery codes, refuse a version) MUST keep their red.
- **FR-010**: Red MUST NOT be the only signal: every delete button names the act, and every danger box
  has a heading saying what is deleted.
- **FR-011**: Red text, red buttons and their focus indicator MUST meet WCAG 2.2 AA contrast in the
  light and the dark theme.
- **FR-012**: No wording, warning copy or confirmation step changes.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: All six deletion sections are a red box with a solid red button; none stays plain.
- **SC-002**: Every delete button in a list row or a part of a screen is red text at rest, including
  those red only on hover or not red today, and none is newly solid red.
- **SC-003**: Every red text and red button passes WCAG 2.2 AA in both themes, checked with a tool.
- **SC-004**: Shown any screen, a person points at the part that deletes within five seconds without
  reading the labels.

## Assumptions

- "Red box" is the danger box "Delete this vault" already uses; "red button" is the solid red button
  of the warning card. No new colour; the existing red, tint and edge exist in both themes.
- Delete lines in an unfolding actions menu are out of scope: not buttons, and their warning is red.
- The admin space is in scope under the list row rule; the API and the MCP server have no visual surface.
