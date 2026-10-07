# Feature Specification: Multilingual contact names

**Feature Branch**: `2026-10-06-multilingual-contact-names` | **Created**: 2026-10-06 | **Status**: Draft

**Input**: User description: "A contact can carry several structured representations of the same current name: the local script, a Latin transliteration, a phonetic reading. One is explicitly chosen as the display name and governs lists, titles, notifications, readable addresses and initials. Every representation is searchable and synchronised. Former names, several legal names and structured aliases are out of scope; nickname and maiden name stay. Covers the contact screens, search, API, MCP, vCard and CardDAV. The create and edit screens follow `Edit Contact.dc.html` from the design project, starting with one name and revealing more only through Add another way to write the name."

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Record several ways to write one name (Priority: P1)

The name panel of the create and edit screens follows `Edit Contact.dc.html`. With one representation it shows the usual name parts and **Add another way to write this name**. With two or more it shows a list in which one row is selected as the display name, each row offering Edit and Remove. A row's editor asks for the type (written form, transliteration, pronunciation), an optional language and writing system, a pronunciation system for a pronunciation, a full name, and the name parts behind a disclosure. Nothing is written until **Save the name**; Cancel discards every change.

**Why this priority**: without storage and display choice, nothing else in this feature exists.

**Independent Test**: give Kim Min-ji three representations, select another display name, save, and read the contact list, the contact page and the initials.

**Acceptance Scenarios**:

1. **Given** either screen with one representation or none, **When** it opens, **Then** it shows the name parts and no list until Add another way to write this name is chosen, which opens an editor set to transliteration.
2. **Given** a contact with no representation, **When** the first one is saved, **Then** it becomes the display name.
3. **Given** 김민지 (Korean, Hangul) displayed and Kim Min-ji (Korean, Latin alphabet) recorded, **When** the owner selects Kim Min-ji and saves, **Then** lists, the page title, notifications and initials read Kim Min-ji, and 김민지 is unchanged.
4. **Given** a secondary representation, **When** it is removed and the form saved, **Then** the display name does not change.
5. **Given** the displayed representation and others, **When** the owner removes it, **Then** the row asks which name to show instead, offering Remove and switch or Keep it.
6. **Given** one representation, **When** the owner empties every name part and saves, **Then** a warning is confirmed first and the contact reads Unnamed contact.
7. **Given** an editor with neither a full name nor a name part, or a pronunciation with no system, **When** it is shown, **Then** its Add or Done button is disabled and the editor says what is missing.
8. **Given** a row being edited or a removal awaiting its answer, **When** the owner looks at Save the name, **Then** it is disabled.

### User Story 2 - Find the contact by any of its names (Priority: P1)

**Why this priority**: a name the owner wrote but cannot search on is a name they cannot use.

**Independent Test**: search 김민지, Kim Min-ji and 金敏智 in the people list and in vault search.

**Acceptance Scenarios**:

1. **Given** the three representations above, **When** any one is searched, **Then** the same single contact is found, shown under its display name.
2. **Given** the people list and vault search, **When** the same text is searched, **Then** both find the same contacts, folding accents and case as they do today.
3. **Given** only 김민지 recorded, **When** Kim Min-ji is searched, **Then** nothing is found.
4. **Given** a relationship or life event naming the contact, **When** a representation changes, **Then** the new text finds that record too.

### User Story 3 - Import, export and synchronise every representation (Priority: P2)

**Why this priority**: a device that drops names silently is data loss (principle VIII).

**Independent Test**: import a vCard 4 with three `N`/`FN` pairs linked by `ALTID`, export it, import the export, and compare.

**Acceptance Scenarios**:

1. **Given** a card with several representations, **When** it is imported, **Then** one contact is created holding all of them, with `FN;PREF=1` displayed, or failing that the first non phonetic one.
2. **Given** an exported card, **When** it is imported again, **Then** values, languages, scripts, kinds and the display choice are identical.
3. **Given** a contact with three representations, **When** a device sends a card with a single ordinary `N`, **Then** only the display name is updated and the other two remain.
4. **Given** a device sending several representations linked by `ALTID`, or carrying the identifiers Monica exported, **When** it omits one, **Then** that one is removed.
5. **Given** a card with properties Monica does not manage, **When** it is synchronised, **Then** they survive in the kept card.

### User Story 4 - Manage representations through the API and MCP (Priority: P2)

**Independent Test**: list, add, edit, delete and choose the display representation over the API and through MCP tools, for a contact of the token's account.

**Acceptance Scenarios**:

1. **Given** a contact, **When** it is read, **Then** the response carries `name` (the display name) and every representation with its kind, language, script, components and whether it is displayed.
2. **Given** a contact of another account or reached through another vault, **When** any of these operations is asked, **Then** the answer is not found.

### User Story 5 - Merge, snapshot and deletion keep every name (Priority: P3)

**Acceptance Scenarios**:

1. **Given** two contacts being merged, **When** the preview opens, **Then** it asks which name is displayed, and the representations of both contacts are kept, exact duplicates once.
2. **Given** a merge, **When** it is undone, **Then** each contact gets back its representations and its display choice.
3. **Given** a contact, **When** a snapshot is taken or the contact is deleted, **Then** the snapshot records every representation and the display choice, and deletion keeps them for an undone merge to bring back.

### Edge Cases

- Two contacts sharing only a phonetic reading are suggested as duplicates, never merged automatically.
- Changing a secondary representation leaves the readable address and the sort position alone, and reruns search indexing and duplicate detection.
- The Me contact can hold several representations like any other.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: A contact MUST hold zero or more name representations, each of kind written, transliteration or phonetic.
- **FR-002**: A representation MUST hold a formatted name or at least one of prefix, given name, additional name, family name, suffix, and MAY hold a language tag (BCP 47), a script code (ISO 15924) and a name order.
- **FR-003**: A phonetic representation MUST name its phonetic system, MUST hold its name in parts with no formatted name, and MUST NOT be the display representation, since vCard carries a pronunciation only on `N`.
- **FR-004**: A contact with at least one representation MUST have exactly one display representation, belonging to that contact; a contact with none has none and reads Unnamed contact.
- **FR-005**: The display name MUST be the formatted name of the display representation, else its components in its name order, else Unnamed contact.
- **FR-006**: Only the display representation MUST drive the readable address, the sort position and the initials.
- **FR-007**: The display choice MUST change only by an explicit act of the owner or by the import rules of FR-012; the interface language MUST NOT change it.
- **FR-008**: Monica MUST NOT generate any transliteration or detect any language or script.
- **FR-009**: Every representation MUST be searchable from the people list and from vault search, and from the relationships and life events that index the contact's name.
- **FR-010**: Duplicate detection MUST compare written and transliteration representations, and a phonetic match alone MUST only produce a suggestion.
- **FR-011**: Export MUST write one `N` per representation and one `FN` per non phonetic one, all under a shared `ALTID`, `PREF=1` on the displayed `FN`, `LANGUAGE` and script when known, `PHONETIC` only on phonetic ones, and `NICKNAME` separately.
- **FR-012**: Import MUST read every `N` and `FN` with `ALTID`, `LANGUAGE`, `PREF`, `SCRIPT`, `PHONETIC`, `PROP-ID` and `SORT-AS`, and apply the reconciliation of User Story 3.
- **FR-013**: The API and MCP MUST expose listing, adding, editing, deleting and choosing the display representation, scoped by account then vault.
- **FR-014**: Snapshots, merges, their undoing and contact deletion MUST carry every representation and the display choice.
- **FR-015**: Nickname and maiden name MUST stay separate from representations; templates MUST NOT define representations.
- **FR-016**: The first, middle and last name the API returns today MAY mirror the display representation, and MUST NOT be stored as a second source of truth.
- **FR-017**: The product is in production, so every schema change and the conversion of existing data MUST ship as new migrations, and no migration already shipped may be edited; that conversion gives every contact with a name one written representation holding it, set as its display name, with no visible change.

### Key Entities

- **Name representation**: one way to write or pronounce the contact's current name. Kind, language, script, phonetic system, formatted name, the five components, name order, its vCard grouping and property identifiers, and a position on screen.
- **Contact**: gains a pointer to its display representation. Keeps nickname and maiden name.

## Success Criteria *(mandatory)*

- **SC-001**: An import, export, import cycle in vCard 4 loses no representation, language, script, kind or display choice.
- **SC-002**: Every saved representation finds its contact in 100% of searches for its exact text.
- **SC-003**: Changing the display name updates every surface listed in User Story 1 with no other representation changed.
- **SC-004**: No device sending a single name can remove a secondary representation.
- **SC-005**: A list of 10,000 contacts resolves display names with no query per contact.
- **SC-006**: Every operation refuses another account's or another vault's contact, proven by tests.

## Assumptions

- The nameless contact rule of spec/models/019 is implemented here, since deleting the last representation needs it.
- The interface offers the languages, writing systems and pronunciation systems the design lists; the API and vCard accept any valid code, and a stored code outside the list stays selectable when edited.
- A single representation carrying a full name, a language, a writing system or a non written type is shown as a list of one, so the name parts form never hides a value it cannot edit.
- vCard 3 clients receive the display name only; lossless variants there, display by reader language and former or legal names are out of scope.
- The feature ships without a flag unless the plan finds a reason for one.
