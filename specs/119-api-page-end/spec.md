# Feature Specification: End of an API reference page

**Feature Branch**: `2026-11-12-docs-portal-design` | **Created**: 2026-11-12 | **Status**: Draft

**Input**: "implement design 1a pour le portail de doc [...] pour la page de changelog, ne fais rien pour le moment". Source: variant 1a of `API page end.dc.html`, design project `3b75d0b1-ce1f-4f4a-909e-611fff762f23`.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Move to the previous or next page of the reference (Priority: P1)

A developer reading one page of the API reference reaches the end of the centre column and wants to
continue reading in order, without going back to the sidebar. Two cards stand at the end of the page:
one naming the page before, one naming the page after, in the order the sidebar lists them.

**Why this priority**: The reference is read in order by somebody learning the API (getting started,
authentication, then the resources). The cards are the larger part of the design and the only part
that changes navigation.

**Independent Test**: Open any page of the reference that is neither the first nor the last, and
follow each card; each one opens the neighbouring page the sidebar lists.

**Acceptance Scenarios**:

1. **Given** the page "Authentication" stands between "Getting started" and "Profile" in the sidebar,
   **When** a visitor reaches the end of it, **Then** a card labelled "Previous" names "Getting
   started" and links to it, and a card labelled "Next" names "Profile" and links to it.
2. **Given** the first page of the reference, **When** a visitor reaches its end, **Then** only the
   "Next" card is shown, and it keeps its place on the right.
3. **Given** the last page of the reference, **When** a visitor reaches its end, **Then** only the
   "Previous" card is shown, on the left.
4. **Given** a page hidden from the reference because its flag is off or support is switched off,
   **When** its neighbours are shown, **Then** the cards skip it, exactly as the sidebar does.

---

### User Story 2 - Find where to go when the reference is not enough (Priority: P2)

Below the cards, separated by a line, a short list gives one sentence per destination: write to
support when stuck, and use `llms.txt` when reading the reference through a language model. Under the
list, a muted line says when the reference was last generated.

**Why this priority**: It answers "where do I go now" at the exact place a reader gives up, but it
does not change how the reference itself is read.

**Independent Test**: Open any page of the reference and follow each sentence's link; read the date
line.

**Acceptance Scenarios**:

1. **Given** support is switched on for the instance, **When** a visitor reaches the end of a page,
   **Then** the sentence "Stuck on something? Write to support." links to the place where a support
   request is written.
2. **Given** support is switched off for the instance, **When** a visitor reaches the end of a page,
   **Then** the support sentence is absent and the others remain.
3. **Given** any page of the reference, **When** a visitor reaches its end, **Then** the sentence
   "Reading this with an LLM? Use llms.txt." links to the instance's `llms.txt`.
4. **Given** any page of the reference, **When** a visitor reaches its end, **Then** the line "Last
   generated on" is followed by a date, written machine readable as well as for the reader.

---

### Edge Cases

- A reference holding a single page shows no card at all, and the footer stands alone.
- A visitor who is not signed in follows the support sentence: they are asked to sign in first, then
  reach the place where a request is written.
- On a narrow screen the two cards stay side by side at half width each, and their titles wrap rather
  than overflow.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: Every page of the API reference MUST end, after its last block of content, with the
  previous and next cards followed by the footer.
- **FR-002**: The previous and next pages MUST follow the order of the reference's sidebar, guides
  first and then resources, and MUST skip every page the reference does not offer to the visitor.
- **FR-003**: A card MUST carry its direction ("Previous" or "Next") and the title of the page it
  opens, and the whole card MUST be the link.
- **FR-004**: The cards MUST be grouped as one navigation landmark with an accessible name, so a screen
  reader can tell it from the sidebar.
- **FR-005**: The footer MUST list the support sentence only while support is available on the
  instance.
- **FR-006**: The footer MUST list the `llms.txt` sentence on every page.
- **FR-007**: The footer MUST NOT list a changelog sentence until the changelog page exists. The
  changelog page and its sentence are a later feature.
- **FR-008**: The footer MUST end with the date the reference was last generated, readable by a person
  and marked up as a date for a machine.
- **FR-009**: The cards and the footer MUST meet WCAG 2.2 AA: visible focus on every link, icons hidden
  from assistive technology, and contrast at the level the rest of the portal meets.
- **FR-010**: The cards and the footer MUST NOT appear in the Markdown version of a page, which
  already carries its own links.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: On every page of the reference, a reader can open the next page in one click from the
  end of the centre column.
- **SC-002**: 100% of reference pages show neighbours matching the sidebar's order, with no page
  hidden from the sidebar ever named by a card.
- **SC-003**: The end of a page matches variant 1a of the design when compared side by side, apart
  from the changelog sentence, which is deliberately absent.

## Assumptions

- Variants 1b and 1c are alternatives to 1a, not additions.
- "Pour la page de changelog, ne fais rien pour le moment" is read as: no changelog page and no
  changelog sentence in this feature. The footer is built so the sentence can be added later without
  reworking it.
- The scope is the API reference (`/docs/api/...`). The MCP, CardDAV and help centre pages keep their
  current end, since the design names the API page only.
- The support sentence points at the existing place a signed in person writes a support request, which
  asks a visitor to sign in first.
- "Last generated" is the date the reference's content last changed, as it is shipped with the
  release, in the same way the other portal pages state their "Last updated" date. The plan settles
  where that date is kept.
- The copy is in English, as the rest of the API reference is.
