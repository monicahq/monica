# Feature Specification: Favourite people and Recently viewed as dashboard tabs

**Feature Branch**: `2026-11-24-dashboard-favourites-recent` | **Created**: 2026-11-24 | **Status**: Draft

**Input**: User description: "nouvelle branche. modifie le dashboard. suis ce design: [claude.ai design project d4c00f28] Implement: `Favourites and Recent.dc.html`. aussi, par defaut, c'est Past entries qui doit etre selectionné dans le dropdown par defaut"

**Design**: `Favourites and Recent.dc.html` (claude.ai design project `d4c00f28`). Frame **2a**, "Tabs for the three lists", is implemented. Frames 1a, 1b and 1c are the side by side alternatives it replaces, and none is built. The sample data (Philippe Rincon, the counts) is illustrative.

The dashboard is the one from `specs/149-dashboard-timeline` and `specs/154-dashboard-narrow-layout`. Timeline, filters, details, adding a life event and the attention list keep their behaviour. Only two things change: where the two lists of people stand, and which period the timeline opens on.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Reach favourite and recently viewed people from the tab bar (Priority: P1)

Michael Scott opens the "Dunder Mifflin" vault. Today "Favourite people" and "Recently viewed" stand side by side above the timeline. With no favourites, one panel is a sentence tall and the other is five rows tall. After the change, the vault's header is followed by one bar of four tabs: "Timeline", "Needs your attention", "Favourite people" and "Recently viewed". Each tab has an icon and a count. Selecting a people tab shows its list at the full width of the column.

**Why this priority**: it is the change the design asks for.

**Independent Test**: open a vault holding three favourites, five recently viewed people and some life events, then select each tab in turn.

**Acceptance Scenarios**:

1. **Given** the dashboard, **When** Michael opens it, **Then** no people panel stands above the tab bar, and Timeline is selected.
2. **Given** the bar, **When** he reads it, **Then** it holds the four tabs in the order above, with the icons timeline, reminder, star and clock, each with a count.
3. **Given** twelve favourites, **When** he reads the "Favourite people" tab, **Then** its count is 12. The list shows the first ten and offers "All favourites".
4. **Given** the "Recently viewed" tab, **When** he reads its count, **Then** it is the number of people the tab lists.
5. **Given** "Favourite people" is selected, **When** it is shown, **Then** the sentence "People you marked as a favourite on their page, one step away." is followed by one panel. Each row has an avatar, a name and a filled star, and opens the person's page.
6. **Given** "Recently viewed" is selected, **When** it is shown, **Then** the sentence "The last people whose page you opened in this vault." is followed by one panel. Each row has an avatar, a name and the day the page was opened, latest first, and opens the person's page.
7. **Given** a tab other than Timeline is selected, **When** the bar is shown, **Then** "Add a life event" is not offered. "All reminders" stays with Needs your attention.
8. **Given** nobody to list in a people tab, **When** it is selected, **Then** its count is 0 and an empty state says what would appear there and, for favourites, how to mark somebody as a favourite.

---

### User Story 2 - The timeline opens on past entries (Priority: P1)

Today the timeline opens on "Upcoming" whenever anything is coming up, and the Period choice lists "Upcoming" and then one option per year. After the change, the choice reads "Upcoming", "Past", and then the years under "Years". "Past" holds every life event before today, most recent first, and is what the dashboard opens on.

**Why this priority**: the user asked for it, and it decides what everybody sees first in a vault.

**Independent Test**: give a vault one life event after today and seven before it, spread over two years. Open the dashboard with no period in the address.

**Acceptance Scenarios**:

1. **Given** life events before and after today, **When** the dashboard opens with no period in the address, **Then** "Past" is selected and the timeline lists the past life events, most recent first, grouped by month with the year in each heading.
2. **Given** only upcoming life events, **When** the dashboard opens with no period in the address, **Then** it opens on "Upcoming". A period holding nothing is never opened by default.
3. **Given** a period in the address (a reload, Back, a pick, or returning after adding a life event), **When** the dashboard is shown, **Then** that period stays selected.
4. **Given** a person or a type filter, **When** the dashboard opens with no period in the address, **Then** "Past" is chosen if anything before today matches. Otherwise the first period holding something is chosen.
5. **Given** the Period choice, **When** Michael opens it, **Then** "Past" shows its count, which follows the filters, and every year is still offered.
6. **Given** "Past" is shown, **When** Michael looks for Newer and Older, **Then** Newer leads to "Upcoming" and Older is not offered, because "Past" already holds every year.
7. **Given** an API client or an assistant, **When** it lists the slices or lists life events with the slice `past`, **Then** it gets the same counts and entries as the dashboard.

---

### User Story 3 - Use the four tabs with a keyboard and a screen reader (Priority: P2)

Dwight uses only a keyboard and a screen reader. He tabs to the bar, hears "Timeline, tab, selected, 1 of 4", and moves to "Recently viewed" with the arrow keys.

**Why this priority**: WCAG 2.2 AA is a delivery condition, and the bar grows from two tabs to four.

**Independent Test**: walk the four tabs with the arrows, Home and End, using a screen reader.

**Acceptance Scenarios**:

1. **Given** focus on a tab, **When** Dwight presses the right or left arrow, **Then** the next or previous tab is selected and focused, wrapping at the ends. Home and End reach the first and last tab.
2. **Given** the bar, **When** he tabs into it, **Then** only the selected tab is in the tab order.
3. **Given** a tab, **When** it is announced, **Then** its name, its count, its selected state and the panel it controls are exposed, and each panel is labelled by its tab.
4. **Given** a screen 375px wide, **When** the dashboard is shown, **Then** the tabs wrap or stay reachable without the page scrolling sideways, and every label can be read.

---

### Edge Cases

- A vault without a Me contact shows the same four tabs, without "Add a life event". Today it shows no bar at all.
- A vault with no life event keeps its Timeline tab with a count of 0 and the "Nothing on the timeline yet" empty state, and shows no Period choice.
- A favourite who is not listed (archived) is left out of both the list and the count.
- Returning to the dashboard with Back opens on Timeline. The selected tab is not kept in the address.
- A period in the address that holds nothing any more falls back to the default.
- An undated "Sometime in 2024" group in "Past" stands after the dated months of 2024 and before those of 2023.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The dashboard MUST NOT show the "Favourite people" and "Recently viewed" panels above the timeline.
- **FR-002**: The dashboard MUST show one bar of four tabs, in this order: Timeline, Needs your attention, Favourite people, Recently viewed. Each tab has its icon and its count, and Timeline is selected on load.
- **FR-003**: The Favourite people count MUST be every listed favourite in the vault. The Recently viewed count MUST be the number of people its list shows.
- **FR-004**: Each people tab MUST show its introductory sentence, its rows as described in User Story 1, and an empty state when it lists nobody. Favourites keeps "All favourites" when more exist than are shown.
- **FR-005**: "Add a life event" MUST be offered only on the Timeline tab and only when the vault has a Me contact. "All reminders" MUST be offered only on Needs your attention.
- **FR-006**: The tab bar MUST be shown for every vault, and MUST follow the tabs pattern described in User Story 3.
- **FR-007**: The timeline periods MUST be "Upcoming", "Past" (every life event before today, most recent first), then one option per year. Each has a count that follows the filters.
- **FR-008**: With no period in the address, the timeline MUST open on "Past" when it holds something, else on the first period holding something, else on "Upcoming".
- **FR-009**: The API's slice list and life event list MUST accept and report `past` with the same counts and order as the dashboard. The API documentation and the MCP tools MUST say so.
- **FR-010**: Every new sentence MUST be translated into every shipped locale.

### Key Entities

- **Timeline period**: one slice of a vault's life events. It is "Upcoming", "Past", or one year before today.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Nothing stands between the vault's header and the tab bar.
- **SC-002**: Every person the two panels showed before is reached in two actions from opening the dashboard: select the tab, then select the person.
- **SC-003**: A vault with both past and upcoming life events opens on "Past" every time no period is in the address.
- **SC-004**: All four tabs and their panels can be reached and read with a keyboard alone, and are announced with name, count and selected state.
- **SC-005**: At 375px, 820px and 1180px wide, the dashboard never scrolls sideways.

## Assumptions

- The design's section title says "Three tabs", but its bar draws four. The bar is followed.
- Clarified 2026-11-24: "Past" is added beside the per year options, not in their place.
- The attention tab keeps its current empty state. The design's "Nothing needs you right now" is illustrative.
- The caps stay at ten favourites and five recently viewed people.
