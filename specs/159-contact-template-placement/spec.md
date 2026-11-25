# Feature Specification: Contact Template Placement

**Feature Branch**: `2026-11-25-contact-template-placement`

**Created**: 2026-11-25

**Status**: Draft

**Input**: User description: "Show the template a contact uses on their page, following design 1a of `Contact Template Placement.dc.html` (claude.ai/design project d4c00f28)."

## User Scenarios & Testing *(mandatory)*

### User Story 1 - See which template shapes a contact's page (Priority: P1)

Somebody opening a contact's page sees, in the row of facts under the name, which template the page
is built from, and can follow it to that template. Today the template is only named in the empty
page state, on the set aside screen and in the update banner, so a reader who wonders why the page
asks what it asks has no way to find out.

**Why this priority**: it is the whole feature.

**Independent Test**: open the page of a contact using the "Basic contact" template and find
"Template Basic contact" in the summary row, linking to that template.

**Acceptance Scenarios**:

1. **Given** Michael Scott uses the "Basic contact" template, **When** his page opens, **Then** the
   summary row ends with a fact labelled "Template" whose value is "Basic contact".
2. **Given** that fact, **When** it is activated, **Then** the template's builder opens.
3. **Given** the template is renamed to "Dunder Mifflin staff", **When** the page is opened again,
   **Then** the fact reads "Dunder Mifflin staff".
4. **Given** the page shows the existing facts (highlighted fields, Last in touch, Reminders),
   **When** it renders, **Then** the template fact comes after all of them.

### Edge Cases

- A newer version of the template is published but not applied: the fact still names the template
  only, without a version; the update banner keeps telling the reader a new version waits.
- The template has been deactivated: the contact still renders from it, so the fact still names it
  and still links to it.
- The template cannot be deleted while a contact uses it, so the fact always has a name to show.
- A long template name wraps with the row rather than overflowing it, at every width.
- The page refreshes its facts after an edit: the template fact stays in place.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The contact page MUST show the contact's template as the last fact of the summary row under the name.
- **FR-002**: The fact MUST read as a label "Template" followed by the template's current name, in the same shape as the other facts.
- **FR-003**: The fact MUST carry the `layers` icon, as design 1a draws it.
- **FR-004**: The fact MUST link to the builder of that template, the destination the page's other template links already use.
- **FR-005**: The fact MUST name the template, never the version the contact renders from.
- **FR-006**: The page somebody opens through a private share link MUST NOT show the fact.
- **FR-007**: The label MUST be translated in every shipped locale, using the glossary word for "template".

### Key Entities

- **Template**: the named shape a contact's page is built from. The contact points at one version of
  it; the fact names the template that version belongs to.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: On every contact page, the template is named and reachable in one activation from the page.
- **SC-002**: The page shows the template without an additional visible delay compared to today.
- **SC-003**: Somebody using a screen reader hears the label and the template name together as one link.

## Assumptions

- Design 1a is the chosen placement; 1b, 1c and 1d are not built.
- Every account member may open the template builder, since an account carries no role, so the link is never refused.
- The contact API already returns `template_name`, so the API, its documentation and the MCP tools need no change.
- The shared contact page keeps its own facts and is unchanged.
