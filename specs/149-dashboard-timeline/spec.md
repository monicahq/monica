# Feature Specification: A year index for the dashboard timeline

**Feature Branch**: `2026-11-22-dashboard-timeline`

**Created**: 2026-11-22

**Status**: Draft

**Input**: User description: "Design a new dashboard for the timeline part. Filter by year, by person, by activity type. Have an obsession over performance and number of queries on this new timeline on the dashboard of a vault. It should be extremely performant. Use design 1c."

**Design**: `Dashboard Timeline.dc.html` (claude.ai design project `bf41e9de`), variant 1c, "A year index", with the entry drawn by `TimelineEvent.dc.html`. Variants 1a and 1b are not built. The design's sample data (its event types, its people, today set to 22 November 2026) is illustrative only. The user's answer overrides one point of the design: the timeline covers every life event of the vault, not only the signed in person's, so its switch label reads "Timeline" rather than "Your timeline".

## Clarifications

### Session 2026-11-22

- Q: Whose life events does the dashboard timeline show, the Me contact's or every life event of the vault? → A: Every life event of the vault.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - See what is coming up, then walk back through the years (Priority: P1)

Michael Scott opens the "Dunder Mifflin" vault. Under the switch between "Timeline" and "Needs your attention", the timeline is split in two columns. On the left, a short index: "Upcoming" first, then a divider, then one line per year that holds a life event of the vault, most recent first, each with its count. On the right, the chosen slice only, grouped by month. The dashboard opens on "Upcoming": the Christmas party on 18 December and Pam's art show on 3 January sit under "December 2026" and "January 2027", nearest first, each saying "in 26 days" or "in 6 weeks". He picks "2024" in the index and sees only 2024, most recent month first. At the foot of the slice, "Newer: 2025" and "Older: 2023" take him one step either way.

**Why this priority**: it is the feature. Today the dashboard shows the ten latest life events of the Me contact and five of the vault, and sends Michael elsewhere for anything older; what is planned is mixed in with what happened.

**Independent Test**: give the vault two life events after today, three in 2025 and two in 2023, among several people, open the dashboard, and read the index and the slice; then pick 2023.

**Acceptance Scenarios**:

1. **Given** life events dated today or later, **When** Michael opens the dashboard, **Then** "Upcoming" is selected and lists them nearest first, grouped by month with the month and year as each heading.
2. **Given** no life event dated today or later and some in 2025, **When** he opens the dashboard, **Then** the most recent year holding one is selected instead.
3. **Given** the index, **When** he picks a year, **Then** only that year's life events before today are listed, most recent first, grouped by month with the month alone as each heading, and the slice's title and count name that year.
4. **Given** a selected year, **When** he follows "Older: 2023" or "Newer: 2025", **Then** that slice is selected; "Newer" from the most recent year leads to "Upcoming", "Upcoming" offers no "Newer", and the oldest year offers no "Older".
5. **Given** a month heading, **When** he activates it, **Then** the month's entries fold away and the heading says it is collapsed; activating it again unfolds them.
6. **Given** a year chosen in the index, **When** he reloads the page or comes back with the browser's back button, **Then** the same slice is selected.
7. **Given** a vault with no life event at all, **When** he opens the dashboard, **Then** the index is not drawn and the timeline shows an empty state saying life events written about anybody in the vault land here.

---

### User Story 2 - Narrow it to one person or one kind of event (Priority: P1)

Above the slice, two choices: "With anyone" and "All event types". Michael types "Dw" in the person choice, picks Dwight Schrute, and the slice keeps only the life events Dwight took part in; the counts in the index change with it, so he sees at once that 2019 holds four and 2021 none. He adds "Promotion" as the type and both conditions apply. "Clear filters" appears as soon as one is set and puts both back.

**Why this priority**: the request names the three filters, and without the counts following them the index would send him to years that turn out empty.

**Independent Test**: give the vault life events with Dwight, with Jim, and of two types across three years, filter on Dwight then on one type, and read the slice and the counts.

**Acceptance Scenarios**:

1. **Given** the person choice, **When** Michael types part of a name, **Then** it offers the people of the vault who take part in at least one life event and whose name matches, at most ten at a time, and "With anyone" to remove the filter.
2. **Given** the type choice, **When** he opens it, **Then** it offers "All event types" followed by every life event type used by at least one life event of the vault, grouped under their categories.
3. **Given** a person chosen, **When** the slice is shown, **Then** it holds only life events that person took part in, and every count of the index, "Upcoming" included, counts only those.
4. **Given** a person and a type chosen, **When** the slice is shown, **Then** it holds only life events matching both.
5. **Given** filters under which a year holds nothing, **When** the index is shown, **Then** that year stays in the index with a count of zero, and selecting it shows "Nothing matches here" with a line pointing at the counts.
6. **Given** a filter set, **When** he presses "Clear filters", **Then** both choices go back to "With anyone" and "All event types", the selected slice is kept, and the link disappears.
7. **Given** a filter set, **When** he picks another slice, reloads or goes back, **Then** the filter is kept and the chosen person is named in the person choice.

---

### User Story 3 - It stays fast however large the vault gets (Priority: P1)

The vault holds fifteen years of the office: twenty thousand life events, two thousand people. The dashboard opens as quickly as it did when it held ten, and choosing a year or a person changes the timeline in place without reloading the rest of the dashboard.

**Why this priority**: the request makes performance the first quality of this screen. The dashboard is the page somebody lands on after signing in, every time, and a vault-wide timeline grows with everybody in it.

**Independent Test**: render the dashboard against a vault of ten life events and against one of several thousand over many years and people, and compare the number of reads of stored data and the time taken.

**Acceptance Scenarios**:

1. **Given** any vault, **When** the dashboard is shown, **Then** the number of reads of stored data made for the timeline does not depend on how many life events, years, people, types or months it holds.
2. **Given** a slice is chosen or a filter changes, **When** the timeline is updated, **Then** only the timeline area is fetched and replaced, and the rest of the dashboard is neither recomputed nor redrawn.
3. **Given** a year with more than 100 life events under the current filters, **When** it is selected, **Then** the 100 most recent are listed with a way to show the next 100, and the count still gives the year's full total.
4. **Given** the person choice, **When** a name is typed, **Then** the matches arrive without loading every person of the vault.

---

### User Story 4 - The same slices and filters through the API and the assistant (Priority: P2)

A tool Michael connected through the API, or his assistant through MCP, asks for the life events of the vault that Dwight took part in, of the type "Promotion", in 2019, and for the count of each year under the same filters. It gets the same entries and the same counts the dashboard shows.

**Why this priority**: the constitution makes a capability incomplete until the API offers it; it follows the screen rather than leads it.

**Independent Test**: call the list with a year, a type and a person, and the counts with the same filters, and compare both with the dashboard over the same data.

**Acceptance Scenarios**:

1. **Given** the API's list of life events of a vault, **When** it is asked for one year, or for upcoming life events, **Then** it lists only that slice, in the dashboard's order.
2. **Given** the same list, **When** it is asked for a life event type together with its existing contact filter, **Then** it lists only life events matching both.
3. **Given** the same filters, **When** the counts per slice are asked for, **Then** they are returned in a single answer and equal the dashboard's index.
4. **Given** a type or a contact of another vault or account, **When** it is passed, **Then** the API answers as it does for any record it cannot reach.
5. **Given** the MCP tool that mirrors the list, **When** the assistant passes the same filters, **Then** it answers exactly as the endpoint does.

### Edge Cases

- A life event dated today is upcoming, and reads "today". One dated yesterday is in the current year's slice and reads "yesterday".
- The current year appears in the index when it holds a life event before today; its life events after today are in "Upcoming", never in both.
- A life event spanning several days is placed by its first day.
- A life event known only to the month sits under its month and shows the month without a day; one known only to the year sits under a last group of its year, "Sometime in 2019", and neither gets a relative label.
- The relative label ("in 3 days", "2 weeks ago") is shown within eight weeks of today and left out beyond.
- A participant who is a partial contact is named without a link, as on the contact page.
- A life event whose participants have all been deleted is still listed and counted, with no "With" line, and leads nowhere to be edited.
- A person who takes part only in deleted life events is not offered in the person choice. A type that is deactivated still appears in the type choice while a life event uses it.
- A person or type in the address that no longer exists, or belongs to another vault or account, is ignored as though no filter were set; a year with no life event selects the default slice.
- Group joins and departures, which the contact page interleaves, are not part of the dashboard timeline: they have no type to filter on.
- A vault whose Me contact is missing shows the timeline but no "Add a life event", as today.
- "Today" is the signed in person's today, in their time zone.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The dashboard's timeline view, labelled "Timeline" in the switch with the vault's count of life events, MUST show an index of slices beside the timeline: "Upcoming" first, then every year holding at least one life event of the vault before today, most recent first, each labelled with its count under the current filters.
- **FR-002**: The timeline MUST list one slice at a time. "Upcoming" holds life events dated today or later, nearest first; a year holds that year's life events dated before today, most recent first.
- **FR-003**: Entries MUST be grouped by month, each group headed by its month (with the year under "Upcoming") and its count, and each group MUST fold and unfold, open by default.
- **FR-004**: The timeline MUST open on "Upcoming" when it holds a life event under the current filters, and otherwise on the most recent year holding one.
- **FR-005**: The foot of a slice MUST offer the neighbouring slices as "Newer: …" and "Older: …", where they exist.
- **FR-006**: The timeline MUST offer a person filter that searches, by name, the people of the vault taking part in at least one life event and offers at most ten matches at a time, and a type filter listing every life event type used by a life event of the vault, grouped by category.
- **FR-007**: The filters MUST combine, MUST apply to the slice and to every count of the index, and MUST be cleared together with "Clear filters", shown only while one is set.
- **FR-008**: A slice holding nothing under the filters MUST say "Nothing matches here" and point at the counts of the index.
- **FR-009**: The selected slice and filters MUST be kept in the page's address, so a reload, a shared link and the browser's back and forward buttons show the same timeline.
- **FR-010**: Choosing a slice, a filter, the next page of a slice, or clearing the filters MUST update only the timeline area, without reloading the dashboard. The slices, the pages and "Clear filters" MUST be ordinary links to the address of FR-009, so they open in a new tab and work as a page load.
- **FR-011**: Each entry MUST show its type's icon in its type's colour, its title, its date without the year, a relative label within eight weeks of today, and every contact who took part, each linked to their page unless partial.
- **FR-012**: Entries MUST stay read only on the dashboard and MUST each lead to their edit panel on the page of their first participant who is not partial.
- **FR-013**: A slice MUST list at most 100 entries at a time, with a way to show the next 100; the counts MUST give the full totals.
- **FR-014**: The number of reads of stored data made to draw the timeline area (index, counts, filter options, slice, entries with their types and people) MUST be fixed, MUST NOT grow with the number of life events, years, months, people or types, and MUST NOT exceed 6. A search of the person choice MUST take one read.
- **FR-015**: The counts of the index MUST be obtained together, in a single read, rather than one read per year.
- **FR-016**: Drawing the timeline area of a vault holding 20,000 life events over 30 years and 2,000 people MUST take no more than 100 ms of server time on the reference test machine.
- **FR-017**: The index, the filters, the month headings and the slice navigation MUST be operable with the keyboard alone; the index MUST mark the selected slice as current; a month heading MUST announce whether it is expanded; the person choice MUST announce its matches; and an update of the slice MUST be announced to a screen reader.
- **FR-018**: The layout MUST stack the index above the slice on a narrow screen, and MUST work in a right to left language.
- **FR-019**: The switch between the timeline and "Needs your attention", the "Add a life event" panel and the confirmation after saving MUST keep working as today; a life event just added MUST be shown, announced as new, in the slice that holds it.
- **FR-020**: Only life events of the vault and account in the address MUST ever be listed, counted or offered as a filter, and only contacts of that vault offered as a person.
- **FR-021**: Every new string MUST be translated in every configured locale, the counts through the counted phrase rules.
- **FR-022**: The API's list of life events MUST accept a slice (a year, or upcoming) and a life event type, combined with its existing contact filter, and MUST return the counts per slice under the same filters, counted in a single read as FR-015 asks; its documentation and its MCP tool MUST follow.

### Key Entities

- **Timeline**: every life event of the vault, excluding deleted ones.
- **Slice**: "Upcoming", or one calendar year before today. The unit the timeline loads and shows.
- **Life event type**: the kind of a life event, with its icon, its colour and its category. What the request calls an activity type.
- **Person filter**: a contact of the vault taking part in at least one life event of the timeline.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: The timeline area is drawn with the same number of reads of stored data, at most six, whether the vault holds ten life events or twenty thousand, proven by a test that counts them on both.
- **SC-002**: With 20,000 life events over 30 years and 2,000 people, the dashboard's timeline area is ready in no more than 100 ms of server time, and changing a slice or a filter replaces it in under half a second as perceived by the reader.
- **SC-003**: Any life event of any year is reachable in at most two choices from the dashboard (a year, then a filter or a month), without leaving it.
- **SC-004**: Every count shown in the index equals the number of entries the slice lists, across pages, under the same filters, proven by a test.
- **SC-005**: The whole screen, from choosing a slice to clearing the filters, is done with the keyboard alone.

## Assumptions

- The timeline covers the whole vault (clarified 2026-11-22). On the dashboard the Me contact stays only the default subject of "Add a life event"; its own timeline stays on its contact page.
- The sidebar's "Recent life events", the five latest of the vault, duplicates the first entries of the new timeline and is removed.
- "Activity type" in the request is the life event type, the only kind a life event has. The type choice groups types by their category; filtering by a whole category is out of scope.
- The person filter offers one person at a time. Several people at once is out of scope.
- The entry follows the design's compact form: the details, the narrative and the group line the contact page shows are left out of the dashboard, which leads to a contact page for them.
- Group joins and departures leave the dashboard timeline; they stay on the contact page.
- The "View full timeline" link of specification 074 is no longer needed, since every year is reachable here.
- The fold state of month groups is not remembered between visits.
- The API's list of life events is already vault wide and filters by one contact. Principle X makes the slice, the type and the counts a capability the API must offer too, which is user story 4.
- The reference test machine is the one the test suite runs on; the performance target is checked there and not in production.
