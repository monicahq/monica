# Feature Specification: Readable vault and contact addresses

**Feature Branch**: `2026-09-12-vault-contact-slugs`

**Created**: 2026-09-12

**Status**: Draft

**Input**: User description: "use vault and contact slug name in urls"

## Overview

A vault and a contact are reached today through a 36 character identifier nobody can read: `/vaults/9f3c…/contacts/1a7e…`. Each gains a readable name built from the name its owner gave it, so the same screen answers at `/vaults/famille/contacts/jim-halpert`, and that is the address the product puts in front of people unless the account asks otherwise. The unreadable identifier never stops working, so nothing anybody saved breaks, and the API takes either form.

## Clarifications

- Q: Does the API change too, or only the signed in screens? → A: Both, and the API accepts either form wherever it names a vault or a contact, so no client breaks.
- Q: A vault or a contact is renamed. What happens to its readable address? → A: It follows the name. The previous readable address stops answering, and the unreadable one still reaches the record.
- Q: What happens to the addresses that exist today? → A: All of them keep answering. The unreadable identifier stays a valid way in, permanently.

### Session 2026-09-12

- Q: Which parts of a contact's name build its address? → A: The whole name the screens show, prefix, suffix and maiden name included, in the order that contact reads in.
- Q: What is the address when a name leaves no latin letters behind? → A: The name's own characters. A Japanese name is addressed in Japanese and nothing is romanised.
- Q: May somebody turn readable addresses off? → A: Yes, through a preference on the account, on by default. It decides what the product builds, never what it accepts.
- Q: What does the address of a contact merged into another lead to? → A: A screen naming the contact that absorbed it, with a way through to it.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - A person reads the address (Priority: P1)

Somebody opens a vault and then a contact inside it. The address names both in words they recognise and in the script they wrote them in, so they can tell where they are, read a link before following it, and paste one into a note that still means something a month later.

**Why this priority**: it is the feature, and nothing else here has a purpose without it.

**Independent Test**: create a vault and a contact, open each, read the address, and open it again later to land on the same records.

**Acceptance Scenarios**:

1. **Given** a vault named "Famille" holding a contact named "Jim Halpert", **When** the user opens that contact, **Then** the address names the vault and the contact in readable words.
2. **Given** a vault named "家族" holding a contact named "田中太郎", **When** the user opens that contact, **Then** the address names both in Japanese.
3. **Given** either address, **When** another account opens it, or it names a different vault of the signer's own account, **Then** it is not found, exactly as the unreadable form is.
4. **Given** two contacts named "Jim Halpert" in one vault, **When** each is opened, **Then** each has an address of its own reaching the right person.

---

### User Story 2 - Nothing saved stops working (Priority: P1)

Links already exist: bookmarks, pasted addresses, links in mail the product has already sent, tabs left open, API clients in production. Every one of them still opens what it opened before.

**Why this priority**: it is a condition on shipping story 1 at all, not an improvement on it.

**Independent Test**: record the address of a vault and of a contact before the change, apply it, and confirm both still answer, on a screen and through an API method.

**Acceptance Scenarios**:

1. **Given** an address holding an unreadable identifier, **When** anybody opens it, **Then** it reaches the record it reached before.
2. **Given** an API client, **When** it passes either form to any method naming a vault or a contact, **Then** it reaches the records it reached before.

---

### User Story 3 - The address follows the name (Priority: P2)

Somebody corrects a contact's name, or renames a vault. The address changes with it, so what a reader sees in the address is what they see on the page.

**Why this priority**: an address naming somebody by a name they no longer have is worse than one naming nobody. Stories 1 and 2 ship and are useful before this exists.

**Independent Test**: rename a contact, confirm the new address answers, the previous readable one does not, and the unreadable identifier still reaches the record.

**Acceptance Scenarios**:

1. **Given** a contact reached at a readable address, **When** its name changes, **Then** the readable address changes with it and the reader is taken to the new one.
2. **Given** that rename, **When** somebody follows the previous readable address, **Then** they get not found, and never another person's record.
3. **Given** a vault rename, **When** somebody opens a contact inside it, **Then** the vault part of the address carries the new name.
4. **Given** a contact merged into another, **When** somebody follows its address, **Then** they are told which contact absorbed it and offered a way through.

---

### User Story 4 - Keeping names out of the address (Priority: P3)

Somebody who would rather their contacts were not named in a URL turns readable addresses off in their settings, and the product goes back to building unreadable ones.

**Why this priority**: the exposure is real and this answers it, but stories 1 to 3 are the feature and this only takes it away.

**Independent Test**: turn the preference off and read unreadable addresses, turn it on and read readable ones, and confirm both forms answer throughout.

**Acceptance Scenarios**:

1. **Given** the preference off, **When** the user opens any vault or contact, **Then** every address the product builds carries the unreadable identifier.
2. **Given** the preference off, **When** somebody follows a readable address saved earlier, **Then** it still reaches the record.
3. **Given** a new account, **When** its owner opens a contact, **Then** the address is readable without anybody changing a setting.

---

### Edge Cases

- A name made only of punctuation, emoji or spaces, and a name carrying characters that steer the direction text reads in.
- Two names looking identical on screen and written with different characters.
- A name reading exactly as a fixed part of an address the product already uses: `new` for a vault or a contact, `favourites` or `archived` for a contact.
- A partial contact known by a first name alone, and the Me contact, which every account has and nobody may delete.
- A name long enough to overflow what an address can hold, and one colliding only once it is shortened.
- A record deleted and restored, or a merge undone, after its readable name was released.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: A vault MUST carry a readable name built from the name its owner gave it, unique within its account, and a contact MUST carry one built from the whole name the screens show, prefix, suffix and maiden name included, in the order that contact reads in, unique within its vault.
- **FR-002**: The product MUST build addresses from the readable name on the signed in screens, in the mail it sends and in what the API reports, unless the account has turned readable addresses off.
- **FR-003**: A vault and a contact MUST answer to their readable name and to their unreadable identifier alike, whatever the preference says, for as long as the record exists.
- **FR-004**: The API MUST accept either form wherever it names a vault or a contact today, and MUST report the readable name alongside the identifier it reports already.
- **FR-005**: Renaming a vault or a contact MUST rebuild its readable name, the previous readable name MUST stop answering, and a reader looking at the old address MUST be taken to the new one.
- **FR-006**: A readable name a record has held MUST NOT later reach a different record. A contact merged into another is the one exception: its address MUST name the contact that absorbed it and offer a way through, rather than opening it.
- **FR-007**: A readable name MUST keep the characters of the name it is built from, whatever script they are written in, and the product MUST NOT romanise or transliterate a name into another alphabet.
- **FR-008**: A readable name MUST NOT be one the product already uses as a fixed part of an address, and the product MUST pick another when it would be.
- **FR-009**: Every vault and contact existing when the change is applied MUST be given a readable name, without anybody being asked to do anything.
- **FR-010**: A readable name MUST be built by the product alone. Nobody is asked for one and nobody edits one.
- **FR-011**: Reaching a record by either form MUST apply the checks reaching it applies today: the account must match, and the vault the address names must be the one holding the record.
- **FR-012**: A readable name MUST reach its record whether a client sends its characters as written or in the encoded form an address requires, and the product MUST settle every name to one form so two names looking alike on screen cannot both exist in one scope.
- **FR-013**: A name MUST NOT carry into an address any character steering the direction text reads in, or otherwise invisible, so an address cannot be made to read as something it is not.
- **FR-014**: Readable addresses MUST be governed by a preference on the account, on for a new account, and turning it off MUST change only the addresses the product builds.
- **FR-015**: Every screen this feature adds or changes MUST be operable with the keyboard alone and announced by a screen reader, and every string it adds MUST be translatable in every configured locale.

### Key Entities

- **Vault**: gains a readable name, unique within its account, rebuilt when the vault is renamed. Its unreadable identifier is unchanged and stays the permanent way in.
- **Contact**: gains a readable name, unique within its vault, rebuilt when any part of its name changes. A contact merged into another keeps its readable name, which leads to the explanation rather than to the survivor.
- **Account**: gains the preference deciding whether the product builds readable addresses. It governs what is built, never what is accepted.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Somebody shown an address can say which vault and which person it opens without opening it.
- **SC-002**: Every address that worked before the change opens the same record afterwards, proven by the suite for a screen and for an API method.
- **SC-003**: No address reaches a record outside the signer's account or outside the vault it names, and no address a record has released reaches a different record.
- **SC-004**: Every vault and contact carries an address of its own whatever script its name is written in, proven for a name in latin letters, for one in a script with none, and for duplicates within one vault.
- **SC-005**: With the preference off, no address the product builds names a vault or a contact, and every address saved while it was on still answers.

## Assumptions

- The name of a vault and of a contact now appears in the address, in browser history, in anything recording a path and in anything a person shares their screen with. That is the point of the change, and the preference of FR-014 is the whole of the answer to it.
- Guessing an address is not a way in and never was. An address is refused unless the record sits in the signer's account and in the vault the address names, which the product already enforces. A readable name makes an address easier to guess and no easier to use.
- Share links are untouched. A share carries its own unguessable token and names neither vault nor contact.
- Calendar and address book synchronisation is untouched. It addresses a contact by an identifier of its own.
- Readable names are built, not chosen, and no record other than a vault and a contact gains one. Templates, sections and entity types have theirs already.
- Moving a contact between vaults is not something the product does today, so nothing here decides what such a move would do to an address.
