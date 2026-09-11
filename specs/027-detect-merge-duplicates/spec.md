# Feature Specification: Detect and merge duplicate contacts

**Feature Branch**: `2026-09-10-detect-merge-duplicates`

**Created**: 2026-09-10

**Status**: Draft

**Input**: User description: "Detect and merge duplicates"

## Overview

A vault accumulates the same person twice. Somebody enters "Monica's son, Jack" and gets a partial contact, then later creates Jack properly. Somebody types a name slightly differently and gets a second record. Today the product notices neither, and offers no way to join them: a contact can only be deleted, which discards everything attached to it. This adds three things. A vault tells its owner which of its contacts look like the same person. Two contacts can be merged into one, previewed before it runs, with the user deciding every conflict. A merge is recorded so it can be explained and undone. The absorbed contact is retained rather than destroyed, so a link somebody saved keeps working and lands on the survivor.

## Clarifications

### Session 2026-09-10

- Q: Which signals make two contacts a candidate duplicate? → A: Name similarity, plus an exact match on the value of a field the user has marked as identifying. The mark belongs to the field, so a vault of companies can key on a registration number while a vault of people keys on an email address. The supplied templates carry the mark on email and phone, so a new vault detects duplicates before anybody configures anything.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Merge two contacts I have chosen (Priority: P1)

The user opens a contact, asks to merge it with another they name, and is shown what the result would be before anything happens. Where the two disagree, both values are shown side by side and the user picks. On confirming, everything attached to the absorbed contact belongs to the survivor, and the absorbed contact is retained but no longer appears in the vault's lists.

**Why this priority**: it is the whole point. Detection without a merge produces a list nobody can act on, while a merge without detection is still fully useful to somebody who has already spotted a duplicate.

**Independent Test**: create two contacts with values, files, reminders and share links, merge one into the other, and confirm the survivor holds everything, the vault lists one person, and the absorbed contact's address lands on the survivor.

**Acceptance Scenarios**:

1. **Given** two contacts in one vault, **When** the user asks to merge them, **Then** a preview names the survivor, lists what moves, and shows every conflict with both values offered.
2. **Given** a preview whose conflicts have all been decided, **When** the user confirms, **Then** the survivor holds the chosen values plus everything the absorbed contact held that it did not, and the absorbed contact leaves every list.
3. **Given** a merge has run, **When** somebody opens the absorbed contact's address, **Then** they arrive at the survivor rather than at a missing page.
4. **Given** the contact representing the user, **When** a merge is proposed, **Then** it may absorb another contact and may never be the one absorbed.
5. **Given** a merge is about to run, **When** the user reaches the confirmation, **Then** they are warned a contact will be absorbed and told it can be undone.

---

### User Story 2 - Find the duplicates in a vault (Priority: P2)

The vault offers a screen listing the pairs of contacts that look like the same person, strongest resemblance first, each pair showing what makes them look alike. From a pair the user goes straight into the merge preview, or dismisses it as two different people, and a dismissed pair does not come back.

**Why this priority**: it turns a capability somebody has to think of into one the product raises. It is second because a user who already knows about a duplicate is served by story 1 alone.

**Independent Test**: create several near identical contacts and several plainly different ones, open the screen, and confirm the near identical ones are listed, the different ones are not, and dismissing a pair removes it for good.

**Acceptance Scenarios**:

1. **Given** two contacts sharing an email address in a field marked as identifying, **When** the user opens the duplicates screen, **Then** the pair is listed and the shared value is named as the resemblance.
2. **Given** a listed pair, **When** the user dismisses it, **Then** it disappears and stays gone when the screen is reopened.
3. **Given** a vault with no resembling contacts, **When** the user opens the screen, **Then** it says so rather than showing an empty table.

---

### User Story 3 - Undo a merge (Priority: P2)

Having merged the wrong pair, the user reverses it. Both contacts come back as they were, and everything that moved returns to the contact it came from.

**Why this priority**: it is what makes the merge safe to offer at all. A merge moves a lot of data at once, and the product must not require the user to be certain.

**Independent Test**: merge two contacts, undo it, and confirm both contacts and all their content are as they were before the merge.

**Acceptance Scenarios**:

1. **Given** a merge that has run, **When** the user undoes it, **Then** both contacts are present again and each holds what it held beforehand.
2. **Given** a merge whose absorbed contact has since been removed for good, **When** an undo is attempted, **Then** it is refused with an explanation rather than partially applied.

### Edge Cases

- A contact proposed as a duplicate of itself is never offered, and a pair reaching the screen from both sides is shown once. A merge proposed between two vaults, or two overlapping merges confirmed together, is refused.
- A merge confirmed after one of the two contacts changed underneath the preview: refused, and the preview is rebuilt. A conflict with a value on one side and nothing on the other is not a conflict, and the value is kept without asking.
- The two contacts point at different template versions, or at versions marking different fields as identifying: the survivor keeps its own version, and values with nowhere to land are set aside rather than dropped. Two reminders falling on the same day are both kept, since two reminders are not a duplicate, and a share link onto the absorbed contact shows the survivor, never more than it was scoped to see.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The product MUST identify pairs of contacts within one vault likely to be the same person, from names resembling each other or from an exact shared value in a field marked as identifying, and MUST NOT compare across vaults or accounts.
- **FR-002**: Candidate pairs MUST be presented ordered by how strongly they resemble each other, each stating what the resemblance is.
- **FR-003**: A field MAY be marked as identifying. The mark belongs to the field's definition, so changing it on a published structure MUST create a new version like any other change and MUST NOT alter what a record already shows.
- **FR-004**: The templates the product supplies MUST mark their email and phone fields as identifying, so a vault detects duplicates without anybody configuring it.
- **FR-005**: A user MUST be able to dismiss a candidate pair permanently, and a dismissed pair MUST NOT be offered again.
- **FR-006**: Detection MUST run away from the act of saving a contact, so creating or editing one is never slowed by it.
- **FR-007**: A user MUST be able to merge any two contacts of one vault, whether or not the product proposed the pair.
- **FR-008**: A merge MUST be previewed. Every conflicting value MUST be shown with both sides and MUST be decided by the user. Nothing may be resolved silently.
- **FR-009**: A merge MUST move everything attached to the absorbed contact onto the survivor: field values, files, the avatar, reminders and share links, and, when those concepts exist, relationships, group memberships and activity participations. A partial contact taking part in a merge MUST stop being partial.
- **FR-010**: Where moving something would place two identical rows side by side, the preview MUST expose the collision and the user MUST choose whether to keep both or one. Rows that differ MUST NOT be combined.
- **FR-011**: The absorbed contact MUST be retained rather than destroyed, MUST leave every list and count, and its identifiers MUST keep resolving onto the survivor.
- **FR-012**: The state of the survivor MUST be captured before anything is written, so the merge can be undone as a single unit.
- **FR-013**: The contact representing the user MUST NOT be absorbed. It MAY absorb another contact.
- **FR-014**: The product MUST warn the user before running a merge and MUST say that it can be undone.
- **FR-015**: A merge MUST be recorded with who ran it, when, which contact survived, which was absorbed and how each conflict was decided, and MUST leave the changelog of both contacts intact, rewriting and discarding nothing.
- **FR-016**: A user MUST be able to undo a merge and return both contacts to their state beforehand. An undo that cannot be completed MUST be refused whole rather than applied in part.
- **FR-017**: Every capability here MUST be reachable through the API, and a request from outside the account or vault holding the contacts MUST fail as though they did not exist.
- **FR-018**: Every screen here MUST be operable with the keyboard alone and with a screen reader, and MUST NOT carry the resemblance or a conflict by colour alone.

### Key Entities

- **Candidate pair**: two contacts of one vault the product believes are the same person, with the strength and the reason for that belief, and whether somebody has dismissed it. The reason names the identifying field when that is what matched.
- **Merge record**: what one merge did. The vault, the survivor, the absorbed contact, the decisions taken on each conflict, the captured state of the survivor, who ran it and when.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: A user who knows two contacts are the same person can join them in under one minute, without leaving the contact they started from.
- **SC-002**: No merge loses content. For every merge, everything held by either contact beforehand is either on the survivor or set aside and reachable, and nothing is silently discarded.
- **SC-003**: Every merge is reversible while its absorbed contact is retained, and an undo returns both contacts to their previous state in one step.
- **SC-004**: The duplicates screen of a vault holding 10,000 contacts opens as quickly as that vault's contact list, and saving a contact takes no longer than it does today.
- **SC-005**: Of the pairs the product proposes, at least four in five are judged real duplicates, measured as merges against dismissals.
- **SC-006**: Each of the five operations, listing candidates, dismissing a pair, previewing, merging and undoing, is available through the API and covered by a test proving another account cannot reach it.

## Assumptions

- **Contacts only.** Custom entities are left out, the narrower reading of the product specification, where only contacts are discussed. Whether entities can be merged stays an open question there, and answering it later adds a surface rather than changing this one.
- **How long an undo stays available.** A merge is undoable while the absorbed contact is retained. The product purges nothing today, so in practice a merge stays undoable. No retention policy is introduced here, and the product specification's open question on file and snapshot retention stays open.
- **Detection is proposed, never applied.** The product never joins two contacts on its own, however sure it looks, following the rule that no deduction becomes a fact without an explicit user action. Bulk merging, and detecting duplicates across an incoming file, belong to the import that does not exist yet.
- **Webhooks are not emitted.** The product specification says a merge emits one, and no webhook machinery exists. The merge is recorded in the changelog, which is what an event would be built from when webhooks arrive.
