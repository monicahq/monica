# Feature Specification: Possessions Listing

**Feature Branch**: `2026-10-25-location-management` | **Created**: 2026-10-26 | **Status**: Draft

**Input**: "same branch. implement the listing of possessions based on this design."
Design: `All possessions.dc.html` (claude.ai/design project 85e4275a), direction 1c, "Browse by
location".

All possessions shows the 095 empty state for an empty vault and "Possessions are coming" otherwise.
This feature replaces the placeholder with the listing: the account's locations as a tree, and what
the chosen location holds, featured possessions first.

## Clarifications

### Session 2026-10-26
- Q: Which of the three design directions? → A: 1c, browse by location.
- Q: How are sold, given away, lost, stolen and thrown away possessions treated? → A: Listed and
  counted like any other. Only Lent carries a badge, as drawn.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - See every possession of the vault (Priority: P1)

Michael Scott opens All possessions in a vault of fifteen things: a summary, the three he featured,
and the twelve others with their type, place and value.

**Independent Test**: A vault with featured and other possessions shows the summary and both lists,
and nothing from another vault.

1. **Given** possessions, **When** the screen opens, **Then** a summary counts the possessions and
   the locations and gives the declared value, and "Add a possession" is offered.
2. **Given** featured possessions, **When** shown, **Then** they appear under "Featured here" in their
   order, each with type icon and colour, name, place, value, description and a star.
3. **Given** other possessions, **When** shown, **Then** they are listed by name under "Everything
   else" with type icon and colour, name, type, place and value.
4. **Given** a Lent possession, **When** listed, **Then** it carries a "Lent" badge; no other status does.
5. **Given** no declared value, **When** listed, **Then** the value is a dash and adds to no total.
6. **Given** another vault of the account, **When** this screen shows, **Then** its possessions are
   neither listed nor counted.

### User Story 2 - Browse by location (Priority: P2)

Dwight Schrute selects Garage in the tree. The panel describes Garage: its path and inherited address,
its count and value, a bar split by its sub-locations, its featured possessions and the rest under
"Kept at Garage".

**Independent Test**: Selecting a location narrows counts, bar and lists to it and what it contains.

1. **Given** locations, **When** shown, **Then** the tree lists "All locations", then every location
   under its parent, each counting this vault's possessions in it and in what it contains.
2. **Given** a selection, **When** shown, **Then** the tree marks it current and the panel, counts and
   lists cover it and everything inside it.
3. **Given** a top level location, **When** selected, **Then** its address shows; a nested one shows
   its path and "at the address of" its holder; "All locations" counts the addresses.
4. **Given** possessions spread over two or more parts, **When** shown, **Then** a bar and legend give
   each part and its count, with "No specific spot" for those at the selection itself.
5. **Given** a selection holding only featured possessions, **When** shown, **Then** the list reads
   "Nothing else is kept here. Featured possessions are listed above."
6. **Given** a selection, **When** the page is reloaded or its address shared, **Then** it stays selected.
7. **Given** a location of another account, **When** opened under this vault, **Then** not found.

### User Story 3 - Around the listing (Priority: P3)

Pam Beesly follows "Manage" beside the tree to the locations settings, and reads the vault's
possession count beside "All possessions" in the sidebar.

1. **Given** the listing, **When** "Manage" is selected, **Then** the locations settings open.
2. **Given** possessions, **When** the sidebar shows, **Then** "All possessions" carries their count.

### Edge Cases

- A possession without a location counts under "All locations" only, reads "No location", and gets a
  "No location" part in that bar.
- A location holding none of this vault's possessions stays in the tree with zero.
- Without locations, the tree holds "All locations" and "Manage".
- Values in several currencies are never added together: a total gives one amount per currency.
- An empty vault keeps the 095 empty state.
- Every count reads correctly at zero and one in every shipped language.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The listing MUST replace "Possessions are coming" when the vault holds a possession.
- **FR-002**: The header MUST give the summary of the vault's possessions and offer "Add a possession".
- **FR-003**: A tree MUST list "All locations" then the account's locations nested, by name at each
  level, each with its count including nested locations, coloured per top level location.
- **FR-004**: Each node MUST have its own address; "All locations" is the default.
- **FR-005**: The selection panel MUST give name, address or inherited address, count and value.
- **FR-006**: With two or more parts, a proportional bar with a legend and a text alternative MUST show them.
- **FR-007**: Featured possessions of the selection MUST show under "Featured here", in position order.
- **FR-008**: The others MUST be listed by name, titled "Everything else" or "Kept at" the location.
- **FR-009**: A Lent possession MUST carry a "Lent" badge; every other status shows none.
- **FR-010**: Totals MUST be per currency; a missing value shows a dash and adds nothing.
- **FR-011**: Only this vault's possessions and the account's locations MUST be read.
- **FR-012**: "Manage" MUST open the locations settings.
- **FR-013**: The sidebar MUST show the vault's possession count when above zero.
- **FR-014**: The tree MUST be a navigation landmark marking its current node, usable by keyboard.
- **FR-015**: The API and the assistant MUST list a vault's possessions by location, nested ones included.
- **FR-016**: Every string MUST be translated in every configured locale.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: A vault holding possessions never shows "Possessions are coming".
- **SC-002**: Any possession is found in two selections or fewer.
- **SC-003**: Every count and total equals this vault's possessions in the selection.
- **SC-004**: A vault of 500 possessions across 50 locations opens without a visible wait.

## Assumptions

- A possession has no page yet, so rows are not links.
- The note under a featured possession is its description.
- The declared value covers the whole record, whatever its quantity.
- Who a possession is lent to is not recorded, so the badge reads "Lent".
- No paging: a household vault holds hundreds of possessions at most.
- Directions 1a and 1b, type chips and the figure strip are out of scope.
