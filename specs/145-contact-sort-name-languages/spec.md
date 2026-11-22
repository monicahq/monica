# Feature Specification: Contact sort names follow each language

**Feature Branch**: `claude/contact-sort-name-languages-3b809f` | **Created**: 2026-11-21 | **Status**: Draft

**Input**: User description: "Adapt contact sort name to work with every language specifications"

## Clarifications

### Session 2026-11-21

- Q: What chooses the filing rule of a name? → A: The reader's interface language alone (FR-003).
- Q: May a recorded pronunciation decide a position? → A: Only where the language orders by reading (FR-005).
- Q: What does a name opening with a particle file under? → A: The word after, by language rule (FR-006).

## User Scenarios & Testing *(mandatory)*

### User Story 1: A name files under the part its reader looks for (Priority: P1)

Today every contact files under its family name, then its given name, in every language. An Icelandic
reader looks for Björk Guðmundsdóttir under B, because the second part is a patronymic, and a
Vietnamese reader looks for Nguyễn Văn An under A.
**Why this priority**: it is the request, and constitution principle V forbids hardcoding this order.

**Acceptance Scenarios**:

1. **Given** an Icelandic interface and Jón Stefánsson, Björk Guðmundsdóttir and Anna Jónsdóttir,
   **When** the list is ordered by name, **Then** it reads Anna, Björk, Jón.
2. **Given** a Vietnamese interface and Trần Thị Bình, Nguyễn Văn An and Nguyễn Thị Chi, **Then** it
   reads An, Bình, Chi.
3. **Given** an English interface and Dwight Schrute, Jim Halpert and Pam Beesly, **Then** it reads
   Beesly, Halpert, Schrute, as it does today.

### User Story 2: A capital letter files with its small letter (Priority: P1)

A Turkish reader has two letters where English has one: I with ı, and İ with i. Today Irmak is filed
among the dotted İ names, after İpek, because its capital is read the English way.
**Why this priority**: it is a wrong order shipped today, in a language the product ships.

**Acceptance Scenarios**:

1. **Given** a Turkish interface and the family names İpek, ırmak and Irmak, **When** the list is
   ordered by name, **Then** Irmak and ırmak sit together, before İpek.

### User Story 3: Readings and particles follow the reader's language (Priority: P2)

A Japanese reader looks a name up by how it is read, not by its characters, and a Dutch reader looks
for Vincent van Gogh under G. Neither holds today.
**Why this priority**: each concerns one language, where the first two stories concern every list.

**Acceptance Scenarios**:

1. **Given** a Japanese interface, 山田 太郎 read やまだ たろう and 鈴木 一郎 read すずき いちろう,
   **Then** 鈴木 comes first. Without readings, or for an English reader, today's order stays.
2. **Given** the family names van Gogh and van der Berg, **Then** they file under G and B for a Dutch
   reader, and both under V for an English one.

### User Story 4: Nothing is filed on a mixture of two rules (Priority: P2)

The rule changes with this release, and again whenever the reader changes their interface language.
Either way the reader sees one order, entirely old or entirely new.
**Why this priority**: the product is in production, and a list half refiled is worse than either rule.

**Acceptance Scenarios**:

1. **Given** contacts recorded before this release, **When** the upgrade completes, **Then** each one
   files under the new rule and no name has been rewritten.
2. **When** the reader changes English to Icelandic, **Then** every contact is refiled, or none is.

### Edge Cases

- A name with one part, or only a full name, files under what it has.
- The order chosen for displaying a name (given first, family first) does not decide its filing.
- Names in several scripts keep the order [003](../003-locale-aware-sorting/spec.md) gives them.
- A pronunciation in another script than the one the reader's language orders by (romaji, IPA) never
  moves a name. Of several that qualify, the first on the contact decides.
- A particle is skipped as a separate word, or as an elided one (d’Aubigné files under A). Vandenberg files under V.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: A contact MUST file under the part its filing rule puts first, then under its other parts.
- **FR-002**: Every shipped language MUST have a filing rule. A language added later files family name
  first until it has one.
- **FR-003**: The reader's interface language alone MUST choose the filing rule. No setting is added.
- **FR-004**: The capital and small forms of one letter of the reader's language MUST file together.
- **FR-005**: Where the reader's language orders names by their reading, a contact holding a
  pronunciation in the script that language orders by MUST file under it, and no other contact does.
- **FR-006**: A family name opening with particles its filing rule skips MUST file under the first
  word that is not one of them.
- **FR-007**: Every list of people ordered by name, on screen or in the API, MUST file a contact alike.
- **FR-008**: Every search that finds a contact before this change MUST still find it afterwards.
- **FR-009**: Changing the interface language MUST refile every contact, all or nothing (003 FR-011).
- **FR-010**: The upgrade MUST refile every contact in one step, by new migrations, rewriting no name.

### Key Entities

- **Filing rule**: what a language says about ordering the names of people: which part comes first,
  whether a reading decides, and which particles are skipped.
- **Sort name**: the text a contact files under. Derived, never shown and never typed.

## Success Criteria *(mandatory)*

- **SC-001**: In every shipped language, five names characteristic of it read in the order its filing
  rule gives, each rule being one row of the plan's research.
- **SC-002**: A count of shipped languages filing the two forms of one letter apart is zero.
- **SC-003**: After the upgrade no name has changed, every search term that found a contact still finds
  it, and no contact files against its rule.
- **SC-004**: A language change on an account of a thousand people takes one step, and a count of
  readings mixing two rules is zero, including after a change that failed.

## Assumptions

- This feature decides which text a name files under. Where a letter sits in the alphabet stays with
  003, and which name is displayed stays with [070](../070-multilingual-contact-names/spec.md).
- FR-005 amends 070 FR-006 for the sort position only. Address and initials follow the displayed name.
- Out of scope: a sort name typed for one contact, acting on vCard `SORT-AS` (kept and copied back as
  070 has it), the sort names of entities, ordering by nickname and how duplicates are grouped.
- A filing rule comes from the environment where it supplies one, and from a table in the product
  otherwise, sourced row by row in the plan's research. This specification decides only five rows.
