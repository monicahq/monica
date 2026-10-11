# Feature Specification: Life events on the vault dashboard

**Feature Branch**: `2026-10-11-dashboard-life-events`

**Created**: 2026-10-11

**Status**: Draft

**Input**: User description: "Add a life event from the dashboard of a vault. A life event added there is linked to the signed in user, through their Me contact, by default. Below the box, show the life events of the signed in user, like the timeline of the contact page. Design: `Dashboard Life Event.dc.html`, variant 1b."

**Design**: `Dashboard Life Event.dc.html` (claude.ai design project `f2eed17e`): variant 2a for the dashboard, a segmented control switching between "Your timeline" and "Needs your attention", with the action of the selected view beside it; variant 1b for the panel, the contact page's picker opened inline. The user's request overrides one point of the design: the "Choose who" step is not the first step, because the Me contact is already chosen. It stays reachable through "Change".

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Write down what happened to me without leaving the dashboard (Priority: P1)

Michael Scott opens the "Dunder Mifflin" vault. The top of the main column switches between "Your timeline" and "Needs your attention"; on the timeline, "Add a life event" sits beside the switch. He presses it, the panel opens on the type picker, already "For Michael Scott", he picks "Promotion", fills the date and the details, and saves. He stays on the dashboard.

**Why this priority**: it is the feature. Today a life event about yourself needs a detour through your own contact page.

**Independent Test**: as Michael, add a life event from the dashboard and find it on the timeline of his Me contact.

**Acceptance Scenarios**:

1. **Given** the dashboard of a vault, **When** Michael presses "Add a life event", **Then** the panel opens on the type step, its subject already set to his Me contact.
2. **Given** the panel, **When** he picks a type, fills the details and saves, **Then** the life event is recorded with his Me contact as its first participant, he is back on the dashboard, and a confirmation names the type, the date and the people.
3. **Given** the details step, **When** he adds Dwight Schrute as another participant, **Then** the life event is on the timelines of both.
4. **Given** the panel at any step, **When** he closes it or presses Escape, **Then** nothing is recorded and the quiet row is back.
5. **Given** a refused submission (a missing date, a required field left empty, a type deactivated meanwhile), **When** he saves, **Then** the panel stays open on the details step with his input kept and the error shown.

---

### User Story 2 - My own timeline under the box (Priority: P1)

Below the box, Michael sees the life events of his Me contact, presented as the contact page presents them: grouped by year, most recent first, with the same entries and empty state. It shows the ten most recent, and a "View full timeline" link at the bottom opens his contact page.

**Why this priority**: the box without it gives no proof the entry landed, and the request names both.

**Independent Test**: give Michael's Me contact three life events and one that only involves Jim Halpert, open the dashboard, and read the block.

**Acceptance Scenarios**:

1. **Given** life events involving Michael's Me contact, **When** he opens the dashboard, **Then** they are listed under the box as on his contact page, and the one involving only Jim is not.
2. **Given** a life event just added from the dashboard, **When** the dashboard is shown again, **Then** that entry is at its place on the timeline and announced as new, as on the contact page.
3. **Given** a Me contact with no life events, **When** Michael opens the dashboard, **Then** the block shows the contact page's empty state.
4. **Given** an entry of the block, **When** Michael wants to change it, **Then** the entry offers no edit, delete or restore on the dashboard, and links to that life event's edit panel on his Me contact's page.
5. **Given** twelve life events on Michael's Me contact, **When** he opens the dashboard, **Then** the ten most recent are listed and "View full timeline" leads to his contact page.

---

### User Story 3 - It happened to somebody else (Priority: P2)

Pam Beesly's wedding is not Michael's life event. From the type step, Michael presses "Change" next to "For Michael Scott", lands on "Choose who", searches the people of the vault, and picks Pam.

**Why this priority**: the default covers most entries; the way out keeps the box from being wrong for the rest.

**Independent Test**: change the subject to Pam, save, and check the life event is on Pam's timeline and not on Michael's.

**Acceptance Scenarios**:

1. **Given** the type step, **When** Michael presses "Change", **Then** the panel shows the people of the vault, searchable, with a link to add somebody who is not there yet.
2. **Given** Pam is chosen, **When** the life event is saved, **Then** Pam is its first participant and Michael's Me contact takes no part unless he adds himself.
3. **Given** Pam is chosen, **When** the dashboard is shown again, **Then** Michael's timeline block is unchanged and the vault's "Recent life events" lists the new entry.

### Edge Cases

- A vault whose Me contact is missing (data older than specification 069) shows neither the entry point nor the timeline block.
- A vault with no active life event type: the type step shows the contact page's empty state with its link to the settings.
- "Choose who" offers the same people as the contact page's participant search, partial contacts included.
- The same person chosen as subject and as participant is recorded once.
- The free plan ceiling on contacts does not apply: no contact is created.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The top of the dashboard's main column MUST switch between the Me contact's timeline and "Needs your attention", opening on the timeline, each labelled with its count; "Add a life event" stands beside it on the timeline and "All reminders" on the other.
- **FR-002**: Opening it MUST show the contact page's life event panel inline, with the same types, categories, search, fields and participants.
- **FR-003**: The subject MUST default to the Me contact of the vault, and the panel MUST open on the type step.
- **FR-004**: The subject MUST be changeable to any contact of the vault before saving.
- **FR-005**: Saving MUST record the life event exactly as the contact page does, with the subject as first participant, and MUST record that it was written from the dashboard.
- **FR-006**: After saving, the person MUST return to the dashboard with the contact page's confirmation.
- **FR-007**: A refusal MUST keep the panel open with the input and the error, as on the contact page.
- **FR-008**: The timeline view MUST show the timeline of the Me contact as the contact page shows it, the panel above it when open.
- **FR-009**: The timeline block MUST list only life events the Me contact takes part in, the ten most recent, followed by a "View full timeline" link to the Me contact's page.
- **FR-010**: The timeline block MUST be read only; each entry MUST link to its edit panel on the Me contact's page.
- **FR-011**: The panel MUST be operable with the keyboard alone, announce its current step, close on Escape and return focus to the button that opened it.
- **FR-012**: Every new string MUST be translated in every configured locale.
- **FR-013**: The vault and contact checks of the contact page MUST apply: a subject or participant from another vault or account is refused as not found.

### Key Entities

- **Life event**: unchanged. Gains one more value for where it was written from: the dashboard.
- **Me contact**: the contact of the vault that stands for the signed in user. Default subject and owner of the timeline block.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: A life event about yourself takes one screen instead of two: no navigation away from the dashboard.
- **SC-002**: A life event saved from the dashboard and one saved from the contact page with the same input are indistinguishable except for where they were written from, proven by a test.
- **SC-003**: The timeline block shows the first ten entries of the contact page's timeline, in the same order, proven by a test.
- **SC-004**: The whole flow, from the quiet row to the confirmation, is done with the keyboard alone.

## Assumptions

- "Linked to the current logged user (and contact)" means the Me contact of the vault, since an account holds one user and life events take contacts as participants.
- Life events have no API or MCP tool yet. This feature adds no capability the contact page lacks and leaves that gap to its own specification (principle X).
- The sidebar's "Recent life events", covering the whole vault, stays; its empty line stops saying life events are written "on their page".
- Editing, deleting and restoring a life event stay on the contact page (clarified 2026-10-11).
- The design's simplified details step (When, Where, Notes) stands for the contact page's full form, which is what is built.
