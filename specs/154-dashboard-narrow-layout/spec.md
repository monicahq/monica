# Feature Specification: A one column dashboard for narrow screens

**Feature Branch**: `2026-11-24-dashboard-narrow-layout` | **Created**: 2026-11-24 | **Status**: Draft

**Input**: User description: "nouvelle branche. sur les ecrans "petits" le dashboard ne s'affice pas bien. change le pour ce design la afin de respecter les bons breakpoints: [claude.ai design project 0a258ebd] Implement: `Dashboard narrow.dc.html`"

**Design**: `Dashboard narrow.dc.html` (claude.ai design project `0a258ebd`), approach "une colonne", drawn at two widths: frame 1a at 1180px with the sidebar visible, frame 1b at 820px with the sidebar in its drawer. The design's sample data (Fletcher Russell, the counts, the plan figures, the "Earlier years" option) is illustrative only. Its four decisions are adopted: the year column becomes a "Period" filter, the two people panels move above the timeline, every entry gets a fixed grid, and under 1024px the sidebar is a drawer as it already is.

The dashboard this changes is the one built in `specs/149-dashboard-timeline`. Its behaviour (slices, filters, counts, folding, details, adding a life event) is kept; only the arrangement changes.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Read the dashboard on a laptop screen (Priority: P1)

Michael Scott opens the "Dunder Mifflin" vault on a 13 inch laptop, the browser at about 1180px with the sidebar showing. Today the page splits in two: the timeline on the left with its own column of years, the favourites and recently viewed people on the right. The timeline is left with so little room that an entry's title, its date and "in 2 weeks" wrap over each other.

After the change the page is one column, capped at a reading width. From the top: the vault's name, its figures and "Add somebody"; then "Favourite people" and "Recently viewed" side by side as two compact panels; then the switch between "Timeline" and "Needs your attention" with "Add a life event"; then the filters; then the timeline itself, each entry with its date on the left.

**Why this priority**: this is the width the user reported as broken, and the most common width a laptop opens at.

**Independent Test**: open the dashboard of a vault holding favourites, recently viewed people and life events at 1180px, and read it from top to bottom without horizontal scrolling and without any entry's text overlapping.

**Acceptance Scenarios**:

1. **Given** a window 1180px wide with the sidebar showing, **When** Michael opens the dashboard, **Then** the content is a single column, centred, no wider than the reading width the other non wide screens use, and nothing scrolls sideways.
2. **Given** that window, **When** he looks above the timeline, **Then** "Favourite people" and "Recently viewed" stand side by side, each with its icon, its title and its content or its empty sentence, before the switch between "Timeline" and "Needs your attention".
3. **Given** that window, **When** he reads the timeline, **Then** no column of years stands beside it.
4. **Given** a timeline entry, **When** it is shown, **Then** its date (day and month) and its relative date ("in 2 weeks", "3 days ago") sit one above the other in a fixed column on the left, the type marker next to it, then the title on its own line and "With …" under it.
5. **Given** a long title, **When** it is shown, **Then** it wraps within its own column and never pushes the date or the marker out of place.

---

### User Story 2 - Choose a period from the filters (Priority: P1)

The years that used to stand in their own column are now one choice, "Period", at the start of the row of filters, next to "With anyone" and "All event types". It offers "Upcoming" first, then one option per year holding a life event, most recent first, each with its count. Michael picks "2024", and the timeline shows 2024 exactly as picking the year in the old column did.

**Why this priority**: removing the column without this choice would remove the only way to reach past years other than paging with "Older".

**Independent Test**: give the vault life events after today, in 2025 and in 2023, filter on a person, open the Period choice, read its options and counts, pick 2023.

**Acceptance Scenarios**:

1. **Given** the row of filters, **When** Michael opens "Period", **Then** it offers "Upcoming" followed by every year holding a life event of the vault, most recent first, each option naming its count of entries, and the selected slice is the one shown.
2. **Given** the Period choice, **When** he picks a year, **Then** the timeline switches to that slice in place, the address changes so that reloading or going back shows the same slice, and the person and type filters are kept.
3. **Given** a person or a type chosen, **When** he opens "Period", **Then** the counts in its options count only the life events matching the filters, as the old column did, and a year matching nothing stays listed with a count of zero.
4. **Given** "Clear filters", **When** he presses it, **Then** the person and type go back to their defaults and the selected period is kept.
5. **Given** the browser runs no script, **When** he changes the period and submits the filters, **Then** the chosen slice is shown.
6. **Given** a screen reader, **When** he reaches the Period choice, **Then** it is announced as "Period" with its selected option and the count of each option.
7. **Given** a vault with no life event, **When** the dashboard is shown, **Then** no Period choice and no filter appear, and the timeline's empty state is shown as today.

---

### User Story 3 - Use the dashboard on a tablet or a phone (Priority: P2)

Pam opens the dashboard on a tablet in portrait, about 820px wide. The sidebar is in its drawer behind the menu button, as it already is under 1024px. The page keeps the same single column as on the laptop: "Favourite people" and "Recently viewed" stack one above the other when two of them no longer fit side by side, and the three filters wrap onto as many lines as they need. On a phone the same holds down to the narrowest screens the application supports.

**Why this priority**: the layout is one column at every width once Story 1 is built, so this story is mostly about the panels and the filters reflowing, not about a separate design.

**Independent Test**: open the dashboard at 820px and at 375px and check that nothing scrolls sideways, the two panels stack, the filters wrap, and every control can still be reached.

**Acceptance Scenarios**:

1. **Given** a window narrower than 1024px, **When** the dashboard opens, **Then** the sidebar is in its drawer and the rest of the page has the same order as at 1180px.
2. **Given** a width where the two panels no longer fit side by side, **When** the dashboard is shown, **Then** "Favourite people" stands above "Recently viewed", each at the full width.
3. **Given** a width where the three filters no longer fit on one line, **When** the dashboard is shown, **Then** they wrap onto further lines, each keeping a usable width, and "Clear filters" stays reachable.
4. **Given** a phone at 375px, **When** Michael reads a timeline entry, **Then** the date column, the marker and the title still stand side by side, and the title wraps rather than overflowing.
5. **Given** the switch between "Timeline" and "Needs your attention" and the "Add a life event" button, **When** they no longer fit on one line, **Then** the button moves under the switch rather than overflowing.

---

### Edge Cases

- A wide monitor: the column stays capped at the reading width and is centred; the dashboard no longer stretches across a wide screen.
- No favourite or nobody recently viewed: the panel still shows its empty sentence; more favourites than shown keeps "All favourites" at its foot.
- The "Needs your attention" view: it takes the same single column; the Period choice and the timeline filters belong to the timeline view and are not shown with it.
- An entry opened to show its details: the details stand under the title, in the column of the title, and do not slide under the date column.
- A very long translated period or filter label: the control wraps or truncates within its box, never overflowing the row.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The dashboard MUST lay its content out as a single column at every screen width, capped at the reading width used by the application's non wide screens, and centred.
- **FR-002**: The sidebar MUST keep its current behaviour: shown beside the content from 1024px, in a drawer opened by the menu button below.
- **FR-003**: The order of the dashboard MUST be: the header (vault tile, name, description, figures, "Add somebody"); the paywall prompt when the ceiling is reached; "Favourite people" and "Recently viewed"; the switch between "Timeline" and "Needs your attention" with its action; the filters; the timeline or the attention list.
- **FR-004**: "Favourite people" and "Recently viewed" MUST stand side by side when each can keep a comfortable minimum width, and stack at full width otherwise. Each keeps its title, icon, list, empty sentence and, for favourites, the "All favourites" link.
- **FR-005**: The column of years beside the timeline MUST be removed.
- **FR-006**: The row of filters MUST start with a "Period" choice offering "Upcoming" and every year the old column offered, in the same order, each option naming its count under the current filters, with the shown slice selected.
- **FR-007**: Choosing a period MUST show that slice in place, change the address so a reload or the back button shows it again, and keep the person and type filters. Without script, the period MUST be submitted with the other filters.
- **FR-008**: The filters ("Period", "With anyone", "All event types") MUST share the row in equal shares and wrap onto further lines when each would fall below a usable width.
- **FR-009**: Every timeline entry MUST be laid out in three fixed columns: a date column holding the date and, under it, the relative date; the type marker; then the title, with "With …" and the entry's actions under or beside it. The date and the relative date MUST no longer appear in the title's line.
- **FR-010**: The entry's details, when opened, MUST stand in the column of the title, and across the whole entry on a phone.
- **FR-011**: The month headings, their folding, the counts, "Newer" and "Older", the "Nothing matches here" state, the empty state and the add life event panel MUST behave as they do today.
- **FR-012**: Nothing on the dashboard MUST scroll horizontally at any width from 320px upward.
- **FR-013**: The layout MUST mirror in a right to left locale.
- **FR-014**: The Period choice MUST have an accessible name, announce each option's count, and be reachable and operable by keyboard; removing the year column MUST NOT remove any way of reaching a slice.

### Key Entities

No data changes. The slices (Upcoming and the years), their counts and the filters are the ones `specs/149-dashboard-timeline` defined.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: At 1180px with the sidebar shown, at 820px and at 375px, the dashboard shows no horizontal scroll and no timeline entry whose title, date or relative date overlap or share a wrapped line.
- **SC-002**: At 1180px with the sidebar shown, the timeline's entries are at least 200px wider than before the change.
- **SC-003**: Every slice reachable from the old year column is reachable from the Period choice, with the same counts under the same filters.
- **SC-004**: Every action available on the dashboard before the change (add somebody, add a life event, switch views, filter, fold a month, open an entry's details, page newer and older, follow a favourite or a recently viewed person) is available at each of the three widths.
- **SC-005**: The dashboard passes the accessibility review at WCAG 2.2 AA at each of the three widths.

## Assumptions

- "Small screens" means every width where the two column layout is cramped; 1180px and 820px (the design's frames) and 375px are the reference widths.
- The single column applies at every width, including wide monitors, as the design draws it with a cap of 960px; the dashboard stops using the wide shell.
- The design's "Earlier years" option is not built: the Period choice lists one option per year, as the old column did. Grouping old years is a separate change.
- The plan strip at the top of the design (free plan, usage, "Upgrade your account") is the existing full access offer of the shell and is not changed by this feature.
- The type marker keeps the tint and the icon it has today. As in the design, entries are separated by a hairline and the vertical line joining the markers goes.
- The entry's actions (opening its details, editing) keep their current behaviour and visibility rules; the design does not draw them.
- Two strings are new: the "Period" label, and the line under "Nothing matches here", which today points at "the counts beside the years".
