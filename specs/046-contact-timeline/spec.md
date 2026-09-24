# Feature Specification: Contact timeline and adding a life event

**Feature Branch**: `2026-09-23-contact-timeline` | **Created**: 2026-09-23 | **Status**: Draft

**Input**: "Add the timeline of life events, plus the add life event widget on the contact page, based
on `Contact profile.dc.html`." It replaces an earlier request for design 1a of `Contact timeline.dc.html`.

## Context

The design puts a **Timeline** (a rail, newest first, grouped by year) in the main column of the
Overview tab, and above it an **Add a life event** panel in two steps: choose the type, then the
date, the people and the details. It still tells life events from activities. The application does
not: commit `a681d13e` renamed activity to life event, and `spec/models/036` says a life event covers
calls and meals as much as marriages and moves. So there is one concept, the button reads **Add a
life event**, and every life event is drawn alike. Life event types and categories exist; the life
event record and its participants (`spec/models/036`, `037`) do not, and this feature creates them.

## Clarifications

### Session 2026-09-23

- Q: Is Me a participant by default? → A: No. Forget "Last in touch" here; the panel lists the
  contact only, as designed.
- Q: Where do relationship entries come from? → A: Nowhere. Relationships stay off the timeline.
- Q: Should some life events stand out? → A: No. Every one is the quieter line, people inline. The
  card is for later. With one kind of entry left, the filter chips go too.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Record a life event from somebody’s page (Priority: P1)

On Jim’s page Michael presses **Add a life event**, picks Home and daily life, then Move, sets 3 July
2026, adds Pam, writes a summary and presses **Add to the timeline**. The entry tops Jim’s timeline
marked "Just added", and a note reads "Move, 3 July 2026, is on the timeline of Jim and Pam."

**Acceptance Scenarios**:

1. **Given** the Overview, **When** the button is pressed, **Then** the panel opens at step 1,
   "Choose what happened", and the button reports itself expanded and is disabled while it is open.
2. **Given** step 1, **Then** the account’s active categories are listed in order with their count of
   active types, the first current, and beside them that category’s types with their count of custom
   fields ("No extra fields", ":count field", ":count fields").
3. **Given** text in "Search every type", **Then** every active type whose name or category contains
   it is listed with its category under a counted heading, or "No type matches “…”" with a pointer to
   the settings; the panel foot always links to the life event types settings.
4. **Given** a type is picked, **Then** step 2 shows the type and its category, **Change type**, "When
   did it happen" set to today, "Who was there" and "Details".
5. **Given** step 2, **Then** the page’s contact is a chip that cannot be removed; **Add someone**
   searches the other contacts of the vault, adds removable chips, never offers one already listed,
   and says "Nobody else matches." when nothing does.
6. **Given** step 2, **Then** "Details" offers "+" chips for the title, narrative, place and duration
   every life event carries, then the type’s custom fields in order; a chip opens its field, and
   **Remove** closes it and forgets its value.
7. **Given** a type with no custom field, **Then** a line reads ":type asks for nothing beyond the
   date and the people." with a link to that type’s custom fields.
8. **Given** no date, **Then** Add to the timeline is disabled.
9. **Given** it is pressed, **Then** one life event is recorded with the date, the type, the structure
   version the type publishes then, what was written and every listed person; the panel resets and
   closes; the dismissible note names the type, the full date and every participant’s first name.
10. **Given** it is recorded, **Then** each participant’s life event count rises by one and their last
    life event date moves to its date unless a later one exists.
11. **Given** a value is refused, **Then** nothing is recorded and step 2 stays open with what was
    written and the error on its field.
12. **Given** Cancel or close, **Then** the panel closes and nothing is kept.

### User Story 2 - Change the type without losing the rest (Priority: P2)

1. **Given** step 2, **When** Change type is pressed, **Then** step 1 marks the type "Current" and
   says "The date and the people stay as they are.", naming any custom field holding a value that the
   new type may not ask for.
2. **Given** a new type is picked, **Then** the date, people, title, narrative, place and duration
   are kept, and custom values the new type does not ask for are dropped.

### User Story 3 - Read what happened with somebody (Priority: P1)

1. **Given** life events in 2025 and 2026, **Then** 2026 sits above 2025, each heading showing the
   year and ":count entry" / ":count entries".
2. **Given** one year, **Then** later dates come first, and within a day the latest recorded.
3. **Given** a life event, **Then** it is one line: the type’s icon in an outlined mark, its title (or
   the type’s name) and day and month; under it the narrative if any and "With Pam, Dwight" for the
   other participants. No card, no avatar.
4. **Given** a life event Jim does not participate in, even one naming him, **Then** it is not shown.
5. **Given** a type since deactivated, **Then** its entries keep their icon and name.
6. **Given** more entries than a page holds, **Then** the latest are shown with a way to ask for
   more, and year headings still count the whole year.

### User Story 4 - A new contact’s timeline (Priority: P3)

1. **Given** no life event, **Then** the timeline shows "You added Toby Flenderson · 23 September 2026"
   and a dashed card, "Nothing else yet", whose **Add the first life event** opens the panel.
2. **Given** life events, **Then** the creation entry is the oldest and no year heading counts it.

### Edge Cases

Archived and partial contacts have a timeline and can participate. A contact of another vault is
never offered and is refused if posted. With no active type, step 1 points to the settings. A type
deactivated while the panel is open is refused on save. A future date is accepted. A soft deleted
participant drops the entry from their own timeline only.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: A life event MUST be recorded as `spec/models/036` and `037` define; columns this
  feature does not fill keep their defaults.
- **FR-002**: A participant MUST be a contact of the same vault, at most once per life event.
- **FR-003**: Recording MUST update each participant’s life event count and last life event date,
  never replacing a later date; "Last in touch" is not changed by this feature.
- **FR-004**: Me MUST NOT be added unless the reader adds them.
- **FR-005**: Recording MUST write a creation entry to the account journal.
- **FR-006**: Recording MUST refuse a type of another account, a deactivated type, a contact of
  another vault and a value its field refuses.
- **FR-007**: The panel MUST be operable by keyboard alone, every list reachable and announced.
- **FR-008**: Every field type the engine offers MUST be fillable in the panel as on the contact page.
- **FR-009**: The panel MUST NOT create types; it links to the settings.
- **FR-010**: The timeline MUST list only life events the contact participates in, plus the creation
  entry, with no filter chips; "activity" MUST NOT appear in the section.
- **FR-011**: Grouping, ordering, counting and paging MUST happen in the database, scoped to the
  account, the vault and the contact.
- **FR-012**: Every count MUST use a counted phrase interpolating `:count` in every wording.
- **FR-013**: The constitution’s principle VII MUST name "Life event" in place of "Activity".

## Success Criteria *(mandatory)*

- **SC-001**: A call with one contact is recorded in under 20 seconds without leaving their page.
- **SC-002**: A type whose name the reader knows is found in one search.
- **SC-003**: A life event with three participants shows on all three timelines at once.
- **SC-004**: A contact with 500 entries opens as fast as one with none, as far as a reader notices,
  and keyboard and screen reader users can record a life event and read it back.

## Assumptions

Only the timeline and the panel change, and the unbuilt "Write a note" panel is removed from the Overview. Dates are to the day (no imprecise date, period or time).
Recording is the only write; editing, deleting and a screen per life event come next. Only contacts
participate (no entity, role or note), and types are not created inline. API, MCP, webhooks, search,
share links and the journal by day do not change.
