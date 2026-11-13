# Feature Specification: Knowledge example content

**Feature Branch**: `2026-11-12-knowledge-dummy-data`
**Created**: 2026-11-12
**Status**: Draft
**Input**: User description: "provide default values in the setup dummy account process for the knowledge section", with the table of folders, notes and tables reproduced in FR-002.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - The example account opens on a filled Knowledge section (Priority: P1)

A developer or reviewer sets up the application locally, which creates the example account (Monica
Geller). They open the vault's Knowledge section and find eight folders, each holding notes and lists
with real content, instead of an empty screen.

**Why this priority**: Knowledge is the only major example section left empty, so none of its screens
can be reviewed without typing content by hand first.

**Independent Test**: Run the example account setup on an empty database, sign in as the example
account, open Knowledge, and count the folders, notes and lists against the table in FR-002.

**Acceptance Scenarios**:

1. **Given** an empty database, **When** the example account is set up, **Then** its vault's Knowledge
   holds the eight folders of FR-002, in that order.
2. **Given** the example account is set up, **When** the Travel folder is opened, **Then** it shows the
   notes "Japan trip memories" and "Places to visit" and the lists "Countries visited" and
   "Travel wishlist".
3. **Given** the example account is set up, **When** any note is opened, **Then** it shows a body of
   formatted text, not an empty page.
4. **Given** the example account is set up, **When** any list is opened, **Then** it shows columns that
   fit its subject and at least three items.


### User Story 2 - The content exercises what Knowledge can display (Priority: P2)

A reviewer checking a change to the Knowledge screens finds, in the example content, every kind of
text those screens render: headings, bullet lists, links, emphasis, code and ticked or unticked tasks
in notes; lists with more columns than the two a new list starts with; cells left blank.

**Why this priority**: One paragraph everywhere hides rendering defects; existing content matters more.

**Independent Test**: Open the notes named in FR-005 and check each formatting kind is visible; tick a
task in "Home maintenance" and see it saved.

**Acceptance Scenarios**:

1. **Given** the example account, **When** "Home maintenance" is opened, **Then** it shows tasks, some
   ticked and some not, and ticking one works.
2. **Given** the example account, **When** "Useful commands" is opened, **Then** it shows code set in a
   fixed-width face.
3. **Given** the example account, **When** "Appliances" is opened, **Then** it shows more than two
   columns, and at least one item has a blank cell.


### Edge Cases

- The example account already exists: setup writes nothing, as it does today.
- The vault starts with no folder: no "General" folder may appear as a side effect.
- The example account's language is not English: the folder, note and list names stay in English, like
  the example possessions and contacts.
- The input's "&" is written "and": the text sanitizer stores "&" as "&amp;", which then displays
  literally (a separate defect). "Grandma's lasagna" displays exactly as written.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: Setting up the example account MUST fill the Knowledge section of its vault, as one more
  step of the existing setup, using the same operations a person uses in the application, so that
  every journal entry and search entry a person's content would produce is produced.
- **FR-002**: Knowledge MUST hold exactly these folders, notes and lists, in this order:

  | Folder | Notes | Lists |
  | --- | --- | --- |
  | Personal | About me, Life goals, Favorite things | Personal preferences |
  | Travel | Japan trip memories, Places to visit | Countries visited, Travel wishlist |
  | Home | Wi-Fi information, Home maintenance | Appliances, Maintenance schedule |
  | Health and Fitness | Workout routine, Health goals | Workout history |
  | Books and Movies | Favorite books, Movie recommendations | Reading list, Movies watched |
  | Food and Recipes | Grandma's lasagna, Favorite cocktails | Favorite restaurants, Recipes |
  | Technology | Home server setup, Useful commands | Software subscriptions, Devices |
  | Ideas and Projects | App ideas, Things to learn | Side projects |

  That is 8 folders, 17 notes and 13 lists.
- **FR-003**: Every note MUST have a body of formatted text written for its title.
- **FR-004**: Every list MUST have a short description, columns named for its subject, and at least
  three items. A list MAY keep or rename the two columns a new list starts with.
- **FR-005**: Across the notes, the content MUST include at least one heading, bullet list, numbered
  list, link, emphasis, block of code and set of tasks. "Home maintenance" and "Things to learn" MUST
  hold tasks with both states; "Useful commands" MUST hold code.
- **FR-006**: At least one list MUST have four columns or more, and at least one item MUST leave a cell
  blank.
- **FR-007**: The content MUST be in the voice of the example account (Monica Geller, a chef in New
  York) where it tells something personal, and MUST contain no real password, real network key, real
  address of a private person or real personal data. The Wi-Fi note uses an obviously invented network
  name and key.
- **FR-008**: The existing test of the example account setup MUST prove the Knowledge content is there
  (the folder count, and one note and one list by name with their content).

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: After setup on an empty database, the example vault's Knowledge shows 8 folders, 17 notes
  and 13 lists, matching FR-002 name for name.
- **SC-002**: 0 notes open on an empty body and 0 lists open with fewer than three items.
- **SC-003**: A reviewer can see every formatting kind listed in FR-005 by opening at most three notes.
- **SC-004**: Running setup twice leaves the counts of SC-001 unchanged.

## Assumptions

- "The setup dummy account process" is the example account seeder that local setup runs outside
  production; nothing changes for real accounts or for a new vault.
- "Tables" are Knowledge lists, the glossary's word for them.
- The titles given are used verbatim, in English. The bodies and items are new invented content; the
  input supplies only titles.
- Example content is data, not interface copy, so it is not translated, as with the existing example
  possessions, contacts and life events.
