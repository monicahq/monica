# Feature Specification: Search engine status in the admin space

**Feature Branch**: `claude/search-engine-status-admin-87ad9f`  
**Created**: 2026-11-12  
**Status**: Draft

**Input**: User description: "Add admin page to check search engine status, depending on laravel scout settings. Health, number of elements, size, etc"

## Clarifications

### Session 2026-11-12
- Q: When an index is missing records, what should the screen offer the administrator? → A: A "Rebuild this index" button, with a confirmation, a password confirmation and an administrator log entry.
- Q: Should a rebuild empty the index before sending every record again? → A: Yes. It empties the index, then sends every record.
- Q: Should the rebuild also be reachable through the API and MCP? → A: No. The status and the rebuild are web only.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - An administrator sees whether search works (Priority: P1)

The admin space gains a Search screen. It names the search engine the instance is configured to use, says in words whether that engine answers, and how long it took to answer. When it does not answer, the screen says why, as far as the engine tells.

**Why this priority**: "Search finds nothing" is today diagnosed from a shell. This answer alone removes that.

**Independent Test**: With the engine stopped, an administrator opens the screen and reads that it is unreachable. With it started, they read that it is healthy.

**Acceptance Scenarios**:
1. **Given** an instance searching through an outside engine that answers, **When** an administrator opens Search, **Then** the screen names the engine, its address, its version when the engine reports one, and says it is healthy.
2. **Given** the engine does not answer or refuses the credentials, **When** the screen opens, **Then** it still renders within the time limit, says the engine is unreachable, and gives the reason.
3. **Given** an instance searching in its own database, **When** the screen opens, **Then** it says so, says that this engine does not tolerate typing mistakes, and reports it healthy without calling anything outside.
4. **Given** a visitor, a regular user or a deactivated administrator, **When** they ask for the screen, **Then** they get not found.

### User Story 2 - An administrator sees what each index holds (Priority: P2)

Under the health, one row per kind of record search covers (contacts, entities, life events): how many documents the engine holds, how many records the instance holds, the difference between the two, and the size of the index when the engine reports it.

**Why this priority**: A healthy engine holding half the records is the second most common failure, and only the two counts side by side show it.

**Independent Test**: With 12 contacts in the instance and 9 in the index, the contacts row shows 9, 12 and 3 missing.

**Acceptance Scenarios**:
1. **Given** an engine holding every record, **When** the screen opens, **Then** each row shows equal counts and says the index is complete.
2. **Given** an index holding fewer or more documents than the instance has records, **When** the screen opens, **Then** the row shows both counts and how many are missing or left over.
3. **Given** an index that does not exist yet, **When** the screen opens, **Then** its row says so and the other rows are still shown.
4. **Given** an engine that reports no size, or the database engine, **When** the screen opens, **Then** the size reads as not reported rather than as zero.

### User Story 3 - An administrator sees how the instance talks to the engine (Priority: P3)

A short block lists the settings that decide the behaviour: the engine, the index name prefix, whether changes are sent through the queue, and whether the engine is still working through changes it has received, when it reports that.

**Why this priority**: It explains a difference found in story 2 (changes still queued, a wrong prefix) without opening the configuration files.

**Independent Test**: With changes sent through the queue and a prefix set, the block shows both.

**Acceptance Scenarios**:
1. **Given** any supported engine, **When** the screen opens, **Then** the block shows the engine, the prefix or that there is none, and whether changes are queued.
2. **Given** an engine with a key or a secret, **When** the screen opens, **Then** neither is shown anywhere on it, in whole or in part.
3. **Given** an engine reporting work in progress, **When** the screen opens, **Then** the block says the engine is still indexing.

### User Story 4 - An administrator rebuilds an index (Priority: P2)

Each index row carries a "Rebuild this index" button. It asks for confirmation, empties the index, then sends every record of that kind to the engine again, through the same path a changed record takes. Until that has finished, search finds fewer records of that kind, or none, for every account.

**Why this priority**: A gap found in story 2 has no remedy on the screen without it.

**Independent Test**: With 9 of 12 contacts indexed, an administrator rebuilds the contacts index and the row then shows 12 of 12.

**Acceptance Scenarios**:
1. **Given** an index row, **When** an administrator presses the button, **Then** a confirmation warns that search will miss records of that kind for every account until the rebuild has finished, before anything is emptied.
2. **Given** the confirmation is accepted and the password or passkey was confirmed, **When** the rebuild starts, **Then** the screen says it has started and the administrator log records who rebuilt which index.
3. **Given** an index holding left over documents, **When** it is rebuilt and the queue has finished, **Then** the row shows equal counts.
4. **Given** an engine that is unreachable or has no status available, **When** the screen opens, **Then** no rebuild button is offered.

### Edge Cases

- The engine is slow: the check stops at the time limit and reports unreachable, it never holds the screen.
- The engine answers for health but fails on one index: health stays healthy, that row reports the failure.
- The configured engine is one the screen does not know how to question: it names the engine and says no status is available for it. Counts of the instance's own records are still shown.
- The client library of the configured engine is not installed: reported as unreachable, with that reason.
- A very large instance: counting its records must not make the screen slower than the time limit.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The admin space MUST offer a Search screen, reachable from the admin navigation, open to active administrators only and not found for everybody else.
- **FR-002**: The screen MUST name the search engine in use, read from the instance's search configuration at the moment it opens.
- **FR-003**: The screen MUST state the engine's health in words (healthy, unreachable, no status available) and never by colour alone.
- **FR-004**: The screen MUST show the response time of the check, the engine's version and address when it has them, and the reason of a failure.
- **FR-005**: The whole check MUST finish within 5 seconds, and a failure of any part MUST NOT prevent the rest of the screen from rendering.
- **FR-006**: For each kind of record search covers, the screen MUST show the number of documents in the index, the number of records the instance holds, and the difference.
- **FR-007**: The screen MUST show the size of each index, and the total size of the engine in megabytes, when the engine reports it, and say it is not reported otherwise.
- **FR-008**: The screen MUST show the index prefix, whether changes are sent through the queue, and whether the engine reports work in progress.
- **FR-009**: The screen MUST NOT show a key, a secret or a password, and MUST NOT show the content of any record.
- **FR-010**: Counts MUST be totals for the instance. Nothing on the screen is broken down by account or by vault.
- **FR-011**: The status MUST be read live each time the screen opens. A control MUST let the administrator check again.
- **FR-012**: Opening the screen MUST change nothing in the engine, in the instance or in the administrator log.
- **FR-014**: An administrator MUST be able to rebuild one index from its row: the index is emptied, then every record of that kind is sent again. The rebuild MUST be written to the administrator log.
- **FR-016**: A rebuild MUST start only after a confirmation that warns search will miss records of that kind for every account until it has finished, and after a password or passkey confirmation.
- **FR-015**: The rebuild button MUST be absent when the engine is unreachable or has no status available.
- **FR-013**: All copy MUST be translated in every configured locale, and the screen MUST meet WCAG 2.2 AA.

### Key Entities

- **Search engine status**: the engine's name, address, version, health, response time and failure reason. Read live, never stored.
- **Index status**: for one kind of record, the documents indexed, the records held, the difference, the size and whether the index exists. Read live, never stored.

## Success Criteria *(mandatory)*

- **SC-001**: An administrator learns whether search is working in one screen and under 10 seconds, without a shell.
- **SC-002**: With the engine down, the screen renders in under 6 seconds and says so.
- **SC-003**: A gap of one record between the instance and an index is visible as a number on the screen.
- **SC-004**: No credential appears in the screen's output for any supported engine.

## Assumptions

- Supported engines are the ones the instance documents: its own database (the default), Meilisearch, Typesense and Algolia. Any other value gets "no status available".
- Web only, for the status and for the rebuild, like the Flags, Configuration and Overview screens of the admin space. Only Support has administrator API methods today. This leaves Principle X uncovered, for an action as well as a screen, and the pull request must say so.
- Out of scope: changing the engine or its settings (they stay in the environment), a history of past checks, alerts, and a count on the admin navigation.
- "Records the instance holds" means the records that are meant to be searchable, counted the way the index is filled.
- Reading the status is not an administrator action and writes nothing to the administrator log. A rebuild is one.
