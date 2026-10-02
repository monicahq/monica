# Feature Specification: Full text search

**Feature Branch**: `2026-09-29-full-text-search`

**Created**: 2026-09-29

**Status**: Draft

**Input**: User description: "Add a full text search engine"

Product rules: `spec/spec.md` section 13. [SearchIndexEntry](../../spec/models/054-search-index-entry-not-yet.md) is not built: each record is a document in the search engine (research.md). This feature is the free text half of section 13; structured filtering on field values is a later feature.

## Clarifications

### Session 2026-09-29

- Q: How is the conflict between engine dependent typo tolerance and tolerant search on every deployment resolved? → A: Tolerance is kept everywhere (revised later on 2026-09-29), then revised on 2026-10-02: the database engine is supported too, searching the record's document written to its own columns (full text index on MySQL, MariaDB and PostgreSQL, LIKE on SQLite), finding every word or its start and tolerating no typing mistake.
- Q: What does a result show when the term matches a sensitive field? → A: The field label only, never the value; the value is revealed on the record, as today.
- Q: Which records match a term of several words? → A: Those holding every word, in any order, anywhere in the name or the fields.
- Q: How do results appear from the search box? → A: On a results page opened by pressing Enter, with no suggestions while typing.
- Q: Is a record found by the names of the people linked to it? → A: Yes: a life event by its participants, a contact by the people it has a relationship with.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Find a record by anything written on it (Priority: P1)

Somebody remembers a detail and not a name: "the one who sells paper in Scranton", "vegetarian", "Schrute Farms". They type it into one search box and get back every contact and entity whose name or field values hold it.

**Why this priority**: today only the contact list searches, and only on the name and the nickname. Everything written in a field is unreachable by search, which is the promise section 13 makes.

**Independent Test**: fill a custom field "Employer" on Dwight Schrute with "Schrute Farms", search "farms dwight", and find Dwight.

**Acceptance Scenarios**:

1. **Given** Dwight's "Employer" field reads "Schrute Farms", **When** the reader searches "farms", **Then** Dwight is a result, and the result names the field that matched ("Employer: Schrute Farms").
2. **Given** a field created a minute ago on a template, **When** a value is saved in it, **Then** that value is found without the reader configuring anything.
3. **Given** a choice field where Angela's diet is "Vegetarian", **When** the reader searches "vegetarian", **Then** Angela is found by the label, never by an identifier.
4. **Given** matches in a name and in a field, **Then** records whose name matches are listed before records matched only by a field.
5. **Given** no match, **Then** the page says nothing was found for that term and offers to clear it.
6. **Given** the reader is in the Work vault, **Then** only Work records are searched; **When** they widen the search to every vault, **Then** results of the other vaults of the account appear, each naming its vault.
7. **Given** Dwight Schrute and Élodie, **When** the reader searches "Scrute", "Dwigth" or "elodie", **Then** each is found despite the typing mistake or the missing accent, after any exact match.

### User Story 2 - Find by what happened and who was there (Priority: P2)

The reader searches "Chili's" and finds the life event "Dinner at Chili's with Michael and Jan", as a life event, linked to where it is read.

**Independent Test**: record a life event whose description holds "Dundie awards", search "dundie", find the life event.

**Acceptance Scenarios**:

1. **Given** a life event whose description or fields hold the term, **Then** it is a result, marked as a life event, with its date.
2. **Given** Michael is Jan's partner and took part in the Chili's dinner, **When** the reader searches "jan", **Then** among people Jan is listed before Michael ("Partner of Jan Levinson"), and among life events the dinner reads "With Jan Levinson".
3. **Given** results of several kinds, **Then** they are grouped by kind (people, each entity type, life events), each group showing its count.

### User Story 3 - Search from the API and MCP (Priority: P2)

A script or an assistant connected through MCP asks the same question and gets the same results.

**Acceptance Scenarios**:

1. **Given** an API key, **When** it searches, **Then** it receives the results the screen would show, in the same order, paginated.
2. **Given** a vault of another account, **Then** the search answers as not found.

### User Story 4 - An operator runs the search engine (Priority: P3)

The operator of an instance runs a typo tolerant search engine of their choice beside it, fills it once, and readers search; when the engine stops, nobody loses a save.

**Why this priority**: search depends on a service the instance did not need before, and its absence must not break anything else.

**Independent Test**: stop the engine, save Dwight, open the search page; start it again and find Dwight.

**Acceptance Scenarios**:

1. **Given** the engine configured and the rebuild command run, **Then** every scenario of User Story 1 holds.
2. **Given** the engine unreachable, **When** the reader saves a record, **Then** it saves; **When** they search, **Then** the page says search is unavailable, and the record is found once the engine is back.

### Edge Cases

- A renamed field, a renamed choice label or a structure version change: the affected records are reindexed: results show the new wording, and a choice is found by its new label only.
- A deleted or soft deleted record leaves the results at once; restored, it comes back.
- An archived contact is found and marked as archived.
- A sensitive field is found by its owner; the result names the field and never shows its value; an API key without the sensitive fields scope and a share link never reach it.
- Layout fields, yes or no fields, the contents of files and the changelog contribute nothing.
- A term of one character, or only spaces or punctuation, searches nothing and says so.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: Search MUST cover the name and every non layout field value of contacts, entities and life events, the names of a life event's participants and of a contact's related people, with no configuration by the user.
- **FR-002**: Each field type MUST decide its own searchable text; a choice contributes its label, a date its written form.
- **FR-003**: A record MUST match when it holds every word of the term, in any order, across everything FR-001 covers, ignoring case and accents, and, on an engine that tolerates typing mistakes, tolerating at least one in a word of five letters or more.
- **FR-004**: Search MUST cover the vault the reader is in by default, let the reader widen it to every vault of the account, name the vault of each widened result, and never return another account's record.
- **FR-005**: Saving a record MUST NOT wait for search: the engine is fed in the background right behind the save.
- **FR-006**: Every change that alters what a record displays (value, field label, choice label, structure version, a linked person's name, a participant or relationship added or removed, deletion, restoration) MUST be reflected in search for every record it touches.
- **FR-007**: Each result MUST show the record's name, its kind, and the field and excerpt that matched (the label alone for a sensitive field), with the term marked in a way that is not colour alone.
- **FR-008**: Within each kind, results MUST be ranked name matches first, then field matches, then linked names, an exact match before a tolerant one at each level, and paginated by 20.
- **FR-009**: A search box in the top bar of every vault screen MUST open, on Enter, a results page whose address carries the term and the scope, and whose result count a screen reader announces.
- **FR-010**: The same search MUST exist in the JSON API and as an MCP tool, returning the same results as the screen.
- **FR-011**: The operator MUST have a command that rebuilds the whole index, reporting each vault and a final count.
- **FR-012**: Every instance MUST run a search engine chosen by the operator among those the product supports: an outside engine that tolerates typing mistakes, or the database, which does not; while it is unreachable or not yet configured, saving MUST work as usual and search MUST say it is unavailable rather than fail.
- **FR-013**: Every new wording MUST be translated in every configured locale.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: In a vault of 10,000 contacts with 20 fields each, 95% of searches answer in under 500 ms.
- **SC-002**: A saved change is findable within 10 seconds under normal queue load, and saving a record adds at most one queued job and no query per field value.
- **SC-003**: Zero results ever belong to a vault or account outside the scope of FR-004, proven by tests on every surface.
- **SC-004**: The search box and results are fully operable with the keyboard and a screen reader.
- **SC-005**: A search with one typing mistake finds the intended record in the first five results in 90% of cases on a seeded test set.

## Assumptions

- Decided 2026-09-29: search goes through Laravel Scout with an engine the operator chooses, on hosted and self hosted instances alike. Revised 2026-10-02: Algolia, Meilisearch and Typesense tolerate typing mistakes; the database engine, supported since, does not.
- Groups are indexed when they exist; they do not exist yet (model 032).
- Structured filtering ("city is Paris and diet is vegetarian") and marketplace search are out of scope, as section 13 and model 054 separate them.
- The contact list keeps its existing name search; this feature adds a separate search screen.
- The engine's documents are derived from the records: the rebuild command sends them again.
