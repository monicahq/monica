# Feature Specification: Vault Sidebar Sections

**Feature Branch**: `2026-10-25-location-management`
**Created**: 2026-10-26 · **Status**: Draft
**Input**: "implement new vault sidebar based on design 1a. add a new “All possessions” link, even
though we do not represent the possessions for now. also, make sure animals only appear when the type
is actually configured in the account (and all other types as well)." Design: claude.ai/design project
`85e4275a`, file `Barre latérale - sections.dc.html`, variant 1a.

The column beside every vault screen today is one flat list under “This vault”: Dashboard, People, the
custom types, Reminders, Vault settings. Variant 1a splits it into labelled sections, so the people
and animals somebody keeps track of read apart from the things they own.

## Clarifications

- Q: A type no longer configured in a vault that still holds its records: listed? → A: No, it disappears.
- Q: What does All possessions open? → A: A screen of its own in the vault, saying possessions are coming.
- Q: An account with no possession type: is “Your possessions” shown? → A: No, the section is absent.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - The column reads as sections (Priority: P1)

Somebody opens a vault. The column shows three labelled sections, each label set in the same small
capitals as “This vault” today, and Vault settings pinned to the foot of the list.

| Section label | Entries, in order |
| --- | --- |
| This vault | Dashboard, Reminders (with the count awaiting an answer) |
| Your relationships | People (with the number of people), then every custom type shown in this vault, in the account’s order |
| Your possessions | All possessions (with a count) |
| *(no label, at the foot)* | Vault settings |

**Why this priority**: it is the whole of the design.

**Independent Test**: open any vault screen and read the column.

**Acceptance Scenarios**:

1. **Given** a vault with 25 people and 1 reminder due, **When** its dashboard opens, **Then** the
   column shows the sections and entries above, with 25 beside People and 1 beside Reminders.
2. **Given** any vault screen, **When** it opens, **Then** the entry for that screen is marked as the
   current page, as today.
3. **Given** a screen reader, **When** it lists the headings of the column, **Then** it announces the
   three section labels as headings, and the labels are not links.

---

### User Story 2 - A custom type appears only where it is configured (Priority: P1)

Animals, and every other custom type, is listed under “Your relationships” only in a vault where the
type is actually in use, never merely because the account has it.

**Why this priority**: an entry leading to a type nobody set up for this vault is noise, and the user
names it explicitly.

**Independent Test**: create a type, open a vault without configuring it there, read the column.

**Acceptance Scenarios**:

1. **Given** Animals is active and has a published default template in vault A only, **When** vault B
   opens, **Then** Animals is absent from its column.
2. **Given** Animals is deactivated in the account settings, **When** any vault opens, **Then** Animals
   is absent from the column.
3. **Given** Animals is deleted, **When** any vault opens, **Then** Animals is absent.
4. **Given** Animals is no longer configured in a vault that still holds animal records, **When** that
   vault opens, **Then** Animals is absent from the column; the records themselves are untouched.

---

### User Story 3 - All possessions is offered before possessions exist (Priority: P2)

The link is in place now so the column does not change shape when possessions arrive.

**Why this priority**: it reserves the place; nothing lies behind it yet.

**Independent Test**: open a vault and act on All possessions.

**Acceptance Scenarios**:

1. **Given** any vault, **When** somebody acts on All possessions, **Then** a screen of the vault opens,
   marked as the current page in the column, saying possessions are coming.
2. **Given** an account with no possession type, **When** a vault opens, **Then** the “Your
   possessions” section is absent, label and entry both.

### Edge Cases

- No custom type is shown in the vault: “Your relationships” holds People alone, never an empty label.
- People count zero, or no reminder due: the count is absent, as today.
- A custom type name longer than the column is truncated on one line, as today.
- On a narrow screen the column still slides in from the side and closes the same way.
- The count beside All possessions is absent while no possession can be recorded.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The vault column MUST group its entries under the three labels and in the order of User Story 1.
- **FR-002**: Section labels MUST be headings and MUST NOT be links.
- **FR-003**: Vault settings MUST sit at the foot of the list, apart from the sections.
- **FR-004**: A custom type MUST be listed only when it is active, not deleted, and has a published default template in this vault; holding records in the vault MUST NOT list it on its own.
- **FR-005**: The column MUST list “All possessions” under “Your possessions”, opening a screen of the vault that says possessions are coming.
- **FR-006**: The “Your possessions” section MUST be absent when the account has no possession type.
- **FR-007**: The current page, the counts, the search box, the theme switch and the account menu at the foot MUST behave as they do today.
- **FR-008**: Every new label MUST be translated in every configured locale, with “possession” following the glossary.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: On every vault screen, the column shows exactly the sections and entries of User Story 1, and nothing else.
- **SC-002**: A custom type not configured in a vault appears in its column zero times.
- **SC-003**: Every entry in the column is reachable and operable by keyboard alone, and the section labels are announced as headings.
- **SC-004**: The column renders in every configured locale with no untranslated label.

## Assumptions

- The section is labelled “Your possessions” as in variant 1a; the design’s caption says “My possessions”, and the drawing wins.
- The icon beside All possessions is the key glyph the design draws. Variant 1b is not built.
- Today a type is also listed when the vault holds its records; that clause is dropped. Nothing is deleted, and the records stay reachable through search and their own address (principle VIII is about data, not about a menu entry). Planning confirms the type’s list screen still opens there.
