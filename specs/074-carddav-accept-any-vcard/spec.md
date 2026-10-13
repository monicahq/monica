# Feature Specification: CardDAV accepts any incoming vCard

**Feature Branch**: `2026-10-11-carddav-accept-any-vcard`

**Created**: 2026-10-11

**Status**: Draft

**Input**: User description: "Fix carddav to accept any incoming vcard"

This narrows [026](../026-dav-synchronisation/spec.md) FR-009, which refuses every card with no name part: such a card now borrows a name when it carries something to borrow from. Spec 026 also observed that a contacts application retries a refused save indefinitely, so a refusal never gets to the person as a message they can act on. It shows up as a sync error that never clears.

## Clarifications

### Session 2026-10-11

- Q: When a device saves a card that has no name at all, what name should the contact get? → A: Borrow the first of organisation, nickname, email, phone; refuse the card when it carries none of them.
- Q: When a card holds a line that cannot be read, what should happen to that line? → A: Keep it verbatim on the stored card and send it back unchanged to devices.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - A card the device considers valid is saved (Priority: P1)

Somebody adds or edits a contact on their phone, tablet or desktop contacts application. Whatever that application wrote, the save succeeds and the person appears in the vault.

**Why this priority**: today a card that is slightly off is refused, the device keeps retrying, and the person never reaches the vault. This is the defect.

**Independent Test**: save each card of a collection gathered from the common contacts applications into an address book; every one is accepted and becomes a contact.

**Acceptance Scenarios**:

1. **Given** a card with a malformed line (a bad fold, a stray blank line, a property name with a character the standard does not allow), **When** it is saved, **Then** it is accepted, the readable parts are written to the contact, and the unreadable line is kept on the card exactly as sent and comes back unchanged on the next read.
2. **Given** a card in vCard 2.1, **When** it is saved, **Then** it is accepted and read like a 3.0 or 4.0 card, including the character sets and encodings 2.1 allows.
3. **Given** a card with no identifier, an unknown or missing version, or more than one formatted name, **When** it is saved, **Then** it is accepted and repaired rather than refused.
4. **Given** a card holding a value that does not fit the field it maps to, **When** it is saved, **Then** the rest of the card is written, and the value that did not fit stays on the card and is not written to the field.

---

### User Story 2 - Every email address, phone number and address reaches the contact (Priority: P1)

A device saves a card holding one or several email addresses, phone numbers and postal addresses. Each of them appears on the contact in the vault, in the order the card lists them, with its label.

**Why this priority**: on the default template a card holding even one email address or phone number fails today with a server error, so nearly every real card is lost. A second postal address is never written to the contact.

**Independent Test**: on the default template, save a card holding two email addresses, two phone numbers and two postal addresses; the contact shows all six.

**Acceptance Scenarios**:

1. **Given** the default template, **When** a card with one email address and one phone number is saved, **Then** the contact shows both.
2. **Given** the default template, **When** a card with several email addresses, phone numbers and postal addresses is saved, **Then** the contact shows each one once, in the card's order, with the label the card gives it.
3. **Given** a contact holding three phone numbers, **When** the device saves the card with two of them, **Then** the contact holds exactly those two.
4. **Given** a field that allows fewer values than the card holds, **When** the card is saved, **Then** the values that fit are written and the rest stay on the card, as FR-004 says.

---

### User Story 3 - A card with no name still becomes a contact (Priority: P2)

A device saves a card for a company, an email address alone, or a number typed quickly with nothing else. The save succeeds and the contact is recognisable in the vault.

**Why this priority**: these are common on phones and refused today, but they are rarer than the malformed cards of story 1.

**Independent Test**: save cards that carry only an organisation, only a nickname, only an email address, or only a phone number; each is accepted and named as FR-005 says. A card carrying none of them is refused.

**Acceptance Scenarios**:

1. **Given** a card with no name but an organisation, **When** it is saved, **Then** the contact is named after the organisation.
2. **Given** a card with no name, no organisation and no nickname but an email address and a phone number, **When** it is saved, **Then** the contact is named after the email address.
3. **Given** a card whose name is present but empty, **When** it is saved, **Then** it is treated as a card with no name.
4. **Given** a card with no name, organisation, nickname, email address or phone number, **When** it is saved, **Then** it is refused and no contact is created.
5. **Given** a contact named by borrowing, **When** the device reads the card back, **Then** the card does not carry name parts the device did not send.

---

### Edge Cases

- A body that is not a vCard at all (plain text, an iCalendar event) is still refused: "any vCard" does not mean "any body".
- A card in jCard (the JSON form) is accepted, as it is today.
- A client that asks for strict handling (`Prefer: handling=strict`) is still refused on a card that would need repair, because it asked to be refused.
- A card naming a contact in another vault through its identifier is not matched to that contact (unchanged from 026).
- A card with more than twenty names keeps the first twenty, as today.
- A device that reads cards in another vCard version than the one sent still receives an unreadable line unchanged.
- A card that grouping applications use for a group (Apple's `X-ADDRESSBOOKSERVER-KIND:group`) is out of scope: it is accepted today and stays as it is.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: Saving a card into an address book MUST succeed for any body that can be read as a vCard, in versions 2.1, 3.0 or 4.0, or as a jCard, except where FR-007 refuses it.
- **FR-002**: A line that cannot be read MUST NOT refuse the save. It MUST be kept on the stored card exactly as sent, MUST be returned unchanged on every read, and MUST NOT be written to any field.
- **FR-003**: A card missing an identifier, a version or a formatted name, or carrying several formatted names, MUST be repaired rather than refused.
- **FR-004**: A value that cannot be written to the field it maps to MUST stay on the card, and the save MUST go on with the rest of the card.
- **FR-005**: A card with no usable name MUST create a contact named after the first of its organisation, nickname, email address and phone number that it carries.
- **FR-006**: The card read back after a save MUST NOT gain name parts the device did not send.
- **FR-007**: The save MUST still be refused for a body that cannot be read as a vCard or jCard, for a card carrying no name, organisation, nickname, email address or phone number, and for a card that needs repair when the client asked for strict handling.
- **FR-008**: A save MUST never fail with a server error because of what the card contains.
- **FR-009**: Every email address, phone number and postal address on a card MUST be written to the contact, one value each, in the card's order, wherever the structure allows several values of that kind.
- **FR-010**: The default template MUST allow several postal addresses, as it already allows several email addresses and phone numbers.

### Key Entities

- **Card**: what a device sends. It is kept whole as sent, and the record's values are merged over it on every read (026).
- **Contact**: the record created or updated from a card. It always holds at least one name, which is a core concept of the product.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Every card in a collection of at least 30 real cards exported from Apple Contacts, iOS, Android, DAVx⁵, Thunderbird, Evolution, Outlook and Google Contacts, including vCard 2.1 exports, is accepted on the first save.
- **SC-002**: No save of a readable card carrying a name or something to borrow one from ends in a refusal or a server error, and a device therefore shows no recurring sync error caused by such a card.
- **SC-003**: Each card in that collection that carries a name shows the same name in the vault as on the device.
- **SC-004**: The existing synchronisation behaviour of 026 (conflicts, deletions, the person's own contact, moves between vaults) is unchanged.
- **SC-005**: On the default template, a card holding any number of email addresses, phone numbers and postal addresses (up to the limit the template sets) shows every one of them on the contact.

## Assumptions

- "Any incoming vCard" means any body a contacts application sends as a contact card. It does not mean any body at all.
- Honouring a client that asks for strict handling follows the HTTP standard, and no common contacts application sends that request.
- An unreadable line is kept rather than dropped because the device sent it and principle VIII forbids losing it silently. A device that wrote it is expected to tolerate reading it back.
- Group cards, photos fetched from a remote address, and calendars are out of scope.
