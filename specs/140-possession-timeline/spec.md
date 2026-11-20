# Feature Specification: Possession timeline

**Feature Branch**: `2026-11-19-possession-timeline`

**Created**: 2026-11-19

**Status**: Draft

**Input**: User description: "Add the Timeline feature to the management of a possession, based on the design `Possession Timeline.dc.html` of the Claude Design project “Timeline de possession Monica”. A new pull request on top of the current stack."

## The design, in words

The Timeline section of a possession's page, which is listed today but marked as coming in a later version, becomes available. It shows, in one column, everything that happened to the possession, read from the sections where it was recorded:

- A heading **Timeline** with a help bubble titled “How the timeline works”: each entry is read from the section it was recorded in, nothing is stored on the timeline itself, and to correct an entry you open it in its section. The design's last sentence, about milestones, is left out.
- A line under it: “Everything that happened to this possession, gathered from its other sections and kept in order.”
- A row of filter chips, one **All** and one per section that holds at least one entry, each with its count. Choosing a chip shows only that section's entries.
- The entries, newest first, grouped under the year they happened in, each group with its count (“1 entry”, “4 entries”).
- Each entry shows the icon and colour of its section on a vertical rail, then: the name of what it is (Transaction, Valuation, Loan…), its date, an optional badge, a title, an optional amount with a short note under it, and a line of detail.
- When nothing matches, one dashed line: “Nothing recorded in any section yet.” or “Nothing recorded in transactions yet.”

The section's entry in the list of sections on the left shows how many entries the timeline holds, as the other sections do.

## What each section contributes

| Section | Entry | Date | Badge | Amount |
| --- | --- | --- | --- | --- |
| Transactions | One per transaction, titled by its type and counterparty (“Purchase from Party City”) | When it occurred | **Acquisition** for the transaction that brought the possession in (purchase, gift received, inheritance, exchange) | Its amount, “Total paid” or “Total received” |
| Insurance | One per record, titled by its cover (“Covered by the home contents policy”) | When the cover starts | none | Insured value, “Insured for” |
| Valuations | One per valuation, titled by its type (“Own estimate”) | When it was valued | **Current value** on the latest | Its amount, with the change from the previous one (“+$25 from the previous”) |
| Provenance | One per event, titled by its type and who (“Previously owned by Jan Levinson”) | Its date at the precision recorded (“Around 2024”, “March 2024”) | none | none |
| Maintenance | One per record, titled by its title (“Cleaning”) | When it was performed | none | Its cost, “Paid” |
| Loans | One when it started (“Lent to Dwight Schrute”, “Borrowed from …”), and one when it came back (“Returned by Dwight Schrute”) | Start, then return | none | none |
| Locations | One per stay, titled “Moved to Storage space”, or “Kept in Hall closet” for the first | When it arrived | **Here now** on the current stay | none |

Documents are not built yet and contribute nothing.

## Clarifications

### Session 2026-11-19

- Q: Where does the design come from? → A: The Claude Design project “Timeline de possession Monica”, file `Possession Timeline.dc.html`, read through the design connection. Its sample data (the Holiday Armadillo costume) is illustration only.
- Q: The design keeps a “milestones only” state, and its empty line says “Untick Milestones only”, but it draws no control for it. Is there one? → A: No. Milestones are left out entirely: no switch, no marking, and no sentence about them in the help bubble.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Read what happened to a possession, in order (Priority: P1)

Somebody opens the Timeline of their Holiday Armadillo costume. They see, newest first and grouped by year, that it moved to the storage space, came back from Dwight, was valued, was lent, was cleaned, was insured, was repaired, was kept in the hall closet, was bought from Party City, and was owned before by Jan.

**Why this priority**: This is the feature. Every section of a possession holds part of its story; nothing puts it back together today.

**Independent Test**: Give a possession one record in each section, open its timeline, and check every entry, its order, its group and its wording against the table above.

**Acceptance Scenarios**:

1. **Given** a possession with records in several sections, **When** its owner opens the Timeline, **Then** every record appears as the entries of the table above, newest first, under the year it happened in, each year with its count.
2. **Given** a loan that came back, **When** the timeline is read, **Then** it shows two entries, the loan and the return, each on its own date.
3. **Given** two valuations, **When** the timeline is read, **Then** the latest carries **Current value** and the change from the one before.
4. **Given** the stay the possession is in now, **When** the timeline is read, **Then** that entry carries **Here now**.
5. **Given** a provenance event known only as “around 2024”, **When** the timeline is read, **Then** it reads “Around 2024” and sits under 2024.
6. **Given** a possession with nothing recorded, **When** its owner opens the Timeline, **Then** it says “Nothing recorded in any section yet.”
7. **Given** the list of sections on the left, **When** the possession has entries, **Then** Timeline is a working link showing how many entries it holds, on every section's page.

---

### User Story 2 - Narrow the timeline to one section (Priority: P2)

Somebody wants only the money spent on maintenance. They choose the **Maintenance** chip and see the cleaning and the repair, with their costs.

**Why this priority**: A long timeline is hard to scan; the chips make it useful once a possession has a history.

**Independent Test**: With entries in several sections, choose each chip and check what is shown and the counts on the chips.

**Acceptance Scenarios**:

1. **Given** a timeline, **When** it opens, **Then** **All** is chosen and each section holding entries has a chip with its count, and a section with none has no chip.
2. **Given** a timeline, **When** somebody chooses a section's chip, **Then** only that section's entries show, still grouped by year.
3. **Given** a chosen chip, **When** the page is reloaded or the link shared, **Then** the same choice is shown.

---

### User Story 3 - Go from an entry to where it is kept (Priority: P2)

Somebody sees a wrong amount on the cleaning entry. They follow the entry to the Maintenance section and correct it there.

**Why this priority**: The help bubble promises it, and nothing on the timeline can be changed in place.

**Independent Test**: Follow an entry of each section and check it reaches that section with the record in view.

**Acceptance Scenarios**:

1. **Given** an entry, **When** somebody follows it, **Then** they reach the section it was read from, where the record can be corrected.
2. **Given** a record corrected or deleted in its section, **When** the timeline is opened again, **Then** it reflects the change, since it stores nothing of its own.

---

### Edge Cases

- A record without a date (a transaction, an insurance record or a stay whose date nobody knows, a provenance event of unknown date) goes in a last group, **Date unknown**, after every year.
- Two entries on the same day keep the order in which they were recorded, newest first.
- A loan still out has one entry; a loan planned but not started has none until it starts, since nothing happened yet. A loan marked returned without a return date shows its return under **Date unknown**.
- A possession archived or moved to another vault keeps its timeline; another vault's possession is never reachable.
- Amounts are written in the possession's currency, the way each section already writes them.
- A section the account cannot see (a flag off) contributes nothing and has no chip.
- A very long timeline (hundreds of entries) stays readable: the year groups still show, and the page does not slow down noticeably.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The Timeline section of a possession MUST become available, replacing the “comes in a later version” placeholder, at its own address under the possession.
- **FR-002**: The timeline MUST show one entry per record as the table “What each section contributes” says, and two for a loan that came back.
- **FR-003**: The timeline MUST store nothing of its own: every entry is read from its section at the moment it is shown, so a change in a section shows at once.
- **FR-004**: Entries MUST be ordered newest first and grouped by the year they happened in, each group naming its year and counting its entries; undated entries MUST form a last group, **Date unknown**.
- **FR-005**: Each entry MUST show its section's icon and colour, the name of what it is, its date in the reader's date format, its title, its detail line, and its badge and amount where the table gives one.
- **FR-006**: The page MUST offer an **All** chip and one chip per section holding entries, each with its count; choosing one MUST show that section's entries only, and the choice MUST survive a reload and a shared link.
- **FR-007**: Each entry MUST lead to the section it was read from, where the record can be corrected.
- **FR-008**: The Timeline entry in the list of a possession's sections MUST show the number of entries, on every section's page.
- **FR-009**: The page MUST carry the help bubble and the line under the heading given in the design, translated.
- **FR-010**: Every word on the page MUST be translated in every shipped language.
- **FR-011**: The timeline MUST be readable through the API and through an MCP tool mirroring it, giving the same entries, order, grouping and filter as the screen (principle X).
- **FR-012**: The page MUST meet WCAG 2.2 AA: the entries form an ordered list, each entry is named by its title, the chips announce which is chosen, the rail and icons are decorative.
- **FR-013**: The timeline MUST NOT distinguish milestones from other entries, in any way.

### Key Entities

- **Timeline entry**: a moment read from one record of a possession's section, with a section, a date (possibly unknown or approximate), a title, a detail, an optional badge, an optional amount and the way back to its record. It is computed, never stored.
- **Possession sections**: Transactions, Insurance, Valuations, Provenance, Maintenance, Loans and Locations, which already exist and are the only sources.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: 100% of the records of the seven sections appear on the timeline, as many entries as the table says, and 0 entries appear without a record behind them.
- **SC-002**: Somebody can find when a possession was last moved, lent or valued in under 10 seconds from its page.
- **SC-003**: A change made in a section shows on the timeline the next time it is opened, in 100% of cases.
- **SC-004**: A possession with 300 records opens its timeline as quickly as its overview, to the reader's eye.
- **SC-005**: The screen, the API and the MCP tool give the same entries in the same order for the same possession.

## Assumptions

- The design is followed for structure, wording and behaviour; its visual tokens are already the application's own. Where the design draws something the screens already have (the header of a possession, the list of sections, the chips, the help bubble, the badges), the existing parts are reused.
- Grouping by year is on, as the design's default; no switch to turn it off is offered.
- The entry's way back to its section is the whole title as a link. The design names it “Open in transactions” without drawing it.
- The Overview of a possession does not change.
- Documents, not built yet, stay out until they are.
- Dates are written in the reader's date format, as the sections write them; “Around 2024” follows the provenance date precision already recorded.
- Which transactions bring a possession in or send it out is decided by their type: purchase, gift received, inheritance and exchange bring it in; sale and gift given send it out.
