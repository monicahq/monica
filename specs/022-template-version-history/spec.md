# Feature Specification: Template version history

**Feature Branch**: `2026-09-05-template-version-history`

**Created**: 2026-09-05

**Status**: Draft

**Input**: User description: "show past versions of a template from the builder screen, reached by a 'Version history' link under the title next to 'Saved 1 day ago Edit', shown when relevant. Design: `Template versions.dc.html` in Claude Design project 41b6aed6."

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Read what each version contains (Priority: P1)

Somebody who has published a template a few times opens its builder, follows a **Version history** link under the title, and lands on a screen listing every version of that template newest first. Each entry names the version, its status, when it was published or last saved, which version it was based on, its change note, and how much it holds. Opening an entry reveals that version's pages in order, the sections on each page with the exact section version placed, and, section by section, the fields inside.

**Why this priority**: this is the whole ask. A published version is frozen and records stay on it until they are moved, so the only way to answer "what does the version my contacts are on actually contain?" is to read it. Today there is no way at all.

**Independent Test**: publish two versions of a template, open the history screen, and confirm both are listed with their pages, sections and fields, without changing anything.

**Acceptance Scenarios**:

1. **Given** a template with a published v1, a published v2 and a v3 draft, **When** the user opens the version history, **Then** all three appear newest first, each with its status and the version it was based on.
2. **Given** a version entry, **When** the user opens it, **Then** its pages appear in their order, each with its sections in order, each section showing its own version number and field count.
3. **Given** an open section, **When** the user opens it, **Then** its fields appear in order with their type.
4. **Given** a published version whose change note is set, **When** its entry is shown, **Then** the note is shown; a draft with no note says one is written at publication instead.
5. **Given** a version, **When** its entry is shown, **Then** a count of its pages, sections and fields is shown, and, unless it is the first version, a summary of what it added over the version it was based on.
6. **Given** a section placed at a different section version than in the previous template version, **When** its row is shown, **Then** it is marked as new in this version.
7. **Given** a template belonging to another account, **When** its version history URL is requested, **Then** the response is not found.

---

### User Story 2 - Find the history from the builder (Priority: P1)

The builder header carries the link, beside the "Saved …" line and the existing **Edit** link, so somebody editing a template can step back and see what came before.

**Why this priority**: without the entry point the screen is unreachable. It is separated from story 1 only because it is a distinct, separately testable slice.

**Independent Test**: open the builder for a template with two versions and confirm the link is present and reaches the history screen; open it for a template with one version and confirm the link is absent.

**Acceptance Scenarios**:

1. **Given** a template with more than one version, **When** the builder is opened, **Then** a **Version history** link appears next to the "Saved …" and **Edit** line and leads to the history screen.
2. **Given** a template with a single version, **When** the builder is opened, **Then** no such link appears.
3. **Given** the history screen, **When** the user follows the back control, **Then** they return to that template's builder.

### Edge Cases

- A template with no version at all: the link is absent, and the history URL renders an empty state rather than failing.
- A version with a page carrying no section, or a section carrying no field: shown as empty rather than omitted.
- A retired version: listed with its own status, and read like any other.
- A section version shared with another template: shown as it stands in this version, unaffected by the other template.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The system MUST show every version of a template on one screen, newest first, each with its number, status, the date it was published or last saved, the version it was based on, and its change note.
- **FR-002**: The system MUST show, per version, a count of its pages, sections and fields, and, where it has a predecessor, a summary of what it added.
- **FR-003**: Users MUST be able to open a version to read its pages in order, the sections on each page in order with the section version placed and its field count, and each section's fields with their type.
- **FR-004**: The system MUST mark a section placed at a section version other than the one the predecessor version placed.
- **FR-005**: The system MUST read frozen content only: opening the screen MUST NOT change any version, section or field.
- **FR-006**: The system MUST show a **Version history** link in the builder header, beside the saved line and the **Edit** link, only when the template has more than one version.
- **FR-007**: The system MUST return not found for a template outside the requesting user's account.
- **FR-008**: Every screen and control MUST be operable by keyboard and announced correctly, per the constitution's Principle IX.

### Key Entities

- **Template version**: one frozen state of a template. Carries a number, a status, a change note, publication and retirement dates, the version it came from, and how many records point at it.
- **Template version page**: a tab within one version, ordered.
- **Template version section**: the placement of one exact section version on one page of one version, ordered, optionally retitled.
- **Section version**: a frozen section, carrying its fields.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: From the builder of a template with several versions, a user reaches the full contents of any past version in two clicks and one expansion.
- **SC-002**: Every version of the template appears on the screen; none is omitted whatever its status.
- **SC-003**: What the screen shows for a published version never changes, however the template is edited afterwards.
- **SC-004**: The screen is fully usable with the keyboard alone and at 200% zoom.

## Assumptions

- "When it's relevant" means the template has more than one version, as confirmed by the author. With a single version there is no history to read.
- The history is its own screen under the template, reached from the builder and returning to it, as the design draws it, rather than a panel inside the builder.
- Every version is listed regardless of status, including retired ones, since a record may still sit on one.
- The comparison that produces the "added" summary and the "new in this version" mark is against the version a version was based on, not against the newest one.
- The existing versioning data already records everything the screen shows; no new stored data is needed.
- The screen is read only. The design's "Start a new draft from vN" button is deliberately out of scope, as confirmed by the author: creating a version stays on the builder and still starts from the published version. Publishing, discarding and retiring stay where they are too.
