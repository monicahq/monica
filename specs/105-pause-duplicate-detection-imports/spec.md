# Feature Specification: Pause the duplicate search while an import writes into a vault

**Feature Branch**: `claude/pause-detect-duplicates-imports-491569`
**Created**: 2026-11-02
**Status**: Draft
**Input**: User description: "Pause DetectDuplicateContacts during imports"

## Context

The duplicate search proposes pairs of contacts in a vault that look like the same person, and a person decides. It runs in the background in three ways: around one contact each time a contact is created or renamed, across a whole vault when somebody asks for it from the duplicates screen, and across every vault each night.

An import writes its contacts one at a time through the same creation path, so an import of 2,000 contacts queues 2,000 searches around one contact each. Each search reads a vault that is only partly written. It proposes pairs against what has arrived so far, and the next search rewrites them. The nightly pass and a search asked for by hand can land in the middle of the same import. When the import is cancelled or fails, the rollback removes the contacts it wrote, and every search still queued for one of them fails when the queue reaches it.

One search across the whole vault, run once the import is over, finds every pair the per contact searches would have found, because it compares every contact with every other.

**Out of scope**: synchronising contacts from a device (see Assumptions); how the search scores a pair; the duplicates screen beyond the message below; the import screens.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - An import ends with one search of the vault (Priority: P1)

Michael imports his Monica 4 file of 2,000 contacts into a new vault called Dunder Mifflin. While the import writes, no duplicate search runs for that vault. When it completes, one search runs across Dunder Mifflin, and the duplicates screen shows that Dwight Schrute and Dwight K. Schrute look like the same person.

**Why this priority**: this is the whole cost the feature removes, and the result the person sees has to stay the same.

**Independent Test**: import a file holding two contacts that match each other, check that no search runs while the import writes, that one search of the vault runs when it completes, and that the pair is proposed.

**Acceptance Scenarios**:

1. **Given** an import writing into a vault, **When** it writes a contact, **Then** no search runs around that contact.
2. **Given** an import that completes, **When** it ends, **Then** one search runs across each vault it wrote into, and the screen proposes every pair a search of that vault would propose.
3. **Given** an import writing into Scranton, **When** Jim edits a contact in Stamford, **Then** the search around that contact runs as it does today.

---

### User Story 2 - An import that is undone leaves no search behind (Priority: P1)

Pam starts an import into Scranton, which already holds her contacts, and cancels it halfway. The rollback removes what the import wrote. No search runs for a contact that no longer exists, and nothing fails in the background. Once the rollback has finished, one search runs across Scranton, so the pairs among her own contacts stay current.

**Why this priority**: today every cancelled or failed import leaves failed background work behind, one entry per contact it wrote.

**Independent Test**: start an import into an existing vault, cancel it after some contacts are written, check that no search is queued for a removed contact and that one search of the vault runs after the rollback.

**Acceptance Scenarios**:

1. **Given** an import into an existing vault that is rolled back, **When** the rollback finishes, **Then** one search runs across that vault.
2. **Given** an import into a vault it created that is rolled back, **When** the rollback removes the vault, **Then** no search runs for it.
3. **Given** an import whose rollback could not finish, **When** it stops, **Then** one search runs across each vault it wrote into that still exists.

---

### User Story 3 - Searches asked for during an import wait for it (Priority: P2)

While Michael's import writes, Dwight opens the duplicates screen of Dunder Mifflin and asks for a search. He is told the search will run once the import has finished, and the screen keeps what it showed before. The nightly pass leaves that vault alone the same way and searches every other vault.

**Why this priority**: a search of a half written vault proposes pairs that the next minute of the import changes, and it is wasted work for the end of import pass.

**Independent Test**: start an import, ask for a search of its vault, check the message and that no search runs; run the nightly pass and check that it searches every vault but that one.

**Acceptance Scenarios**:

1. **Given** an import writing into a vault, **When** somebody asks for a search of that vault, **Then** they are told it will run once the import has finished, and no separate search is queued.
2. **Given** an import writing into a vault, **When** the nightly pass runs, **Then** it searches every other vault and skips that one.
3. **Given** an import writing into a vault, **When** somebody renames a contact that was already there, **Then** no search runs around it, and the end of import search covers the change.

### Edge Cases

- The worker writing the import dies and the import stops moving: the pause lasts until the existing clean up hands the import to the rollback, and the rollback's end runs the search as in User Story 2.
- An import that holds several vaults pauses each of them and searches each that still exists when it ends.
- An import that was read but never started writes nothing, and pauses nothing.
- A search that was already running when the import started finishes. The end of import search corrects what it saw.
- A search queued before the import started is skipped while the import writes, and in no case fails.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: A vault MUST count as being imported into while an import is writing into it or rolling back what it wrote.
- **FR-002**: The system MUST NOT start a search around one contact, or across a vault, while that vault is being imported into.
- **FR-003**: The nightly pass MUST skip every vault being imported into, and search every other vault as it does today.
- **FR-004**: When an import ends, whether completed, rolled back or stopped after a failed rollback, the system MUST run one search across each vault it wrote into that still exists.
- **FR-005**: A search asked for from the duplicates screen of a vault being imported into MUST tell the person it will run once the import has finished, and MUST NOT queue a search of its own.
- **FR-006**: A search whose contact or vault no longer exists when it runs MUST end without doing anything and without being recorded as failed.
- **FR-007**: Searches for vaults that are not being imported into MUST run exactly as they do today.
- **FR-008**: The new message MUST be translated into every configured locale.

### Key Entities

- **Import**: an account import, with its state and the vaults it writes into, among them the vaults it created.
- **Duplicate candidate**: a pair of contacts in one vault that look alike, with its score and reasons, and the person's dismissal if any.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: An import of any number of contacts into one vault leads to exactly one duplicate search of that vault, run after the import ends, instead of one per contact.
- **SC-002**: After an import completes, the duplicates screen proposes exactly the pairs it would propose if somebody asked for a search of the vault by hand.
- **SC-003**: A cancelled or failed import leaves no failed duplicate search behind.
- **SC-004**: A contact created or renamed in a vault that no import is writing into is queued for a search as soon as it is saved, as today.

## Assumptions

- Only account imports pause the search, from a Monica or a Monica 4 file. A device synchronising its address book writes one card at a time, at a pace the person sets, and keeps today's per contact search.
- A dismissed pair stays dismissed after the end of import search, as it does after every search today.
- The pause is read from the import's own state, so a new setting, a new screen or a new state of the import is not needed.
- A search asked for by hand during an import is covered by the end of import search, so it does not need to be remembered.
