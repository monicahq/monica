# Feature Specification: Export a contact as a vCard

**Created**: 2026-09-17

**Status**: Draft

**Input**: User description: "export contact as vcard"

## Clarifications

### Session 2026-09-17

- Q: How does the product decide whether fields marked sensitive go into an exported file? → A: they always go in. The export is the account holder taking data they already see, so no choice is offered and no credential is asked. A program calling the API still receives only what the scopes of its key allow.
- Q: Does the API answer with the raw card or with JSON holding the card? → A: the raw card, as the response body, with the vCard content type and a download file name. It is the first endpoint in the API that does not answer in JSON, and the docs portal and the tests take a new pattern for it.
- Q: An exported file loaded onto a synchronising device comes back under a filing address the product has never seen. Does it update the contact or make a second one? → A: it updates the contact. An incoming card whose filing address is unknown is matched by the identifier it carries, confined to the vault it is filed into.
- Q: May somebody holding a read-only share link download the contact as a vCard? → A: no. The export belongs to the account holder and to the API. The shared page is unchanged, following the rule that a new kind of content stays outside shares until somebody decides otherwise.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Download one contact as a vCard file (Priority: P1)

Somebody reading a contact wants that person as a file they can keep, send to themselves, or open in the address book on their phone, without configuring synchronisation first.

**Why this priority**: It is the whole request, and the only slice that delivers value on its own.

**Independent Test**: Open a contact, ask for the vCard, and confirm the downloaded file opens in an address book application showing the name, photo and details the screen showed.

1. **Given** a contact carrying a name, several field values and an avatar, **When** the reader asks to export it, **Then** a vCard file downloads, named after the contact.
2. **Given** that file, **When** it is opened in an address book application, **Then** the structured name, the nickname, the photo and every field value, including the values of fields marked sensitive, are present and labelled.
3. **Given** a contact reached through the wrong vault, or through another account, **When** the export is requested, **Then** the answer is not found and no file is produced.

---

### User Story 2 - Fetch the same vCard through the API (Priority: P2)

A script or an MCP client holds an API key and wants the file the interface offers, without driving a browser.

**Why this priority**: The constitution requires every capability of the interface to exist in the API, but the interface alone is already useful.

**Independent Test**: Call the endpoint with an API key and compare the body with the file the interface downloads for the same contact.

1. **Given** an API key carrying the sensitive fields scope, **When** it requests the vCard of a contact in one of its vaults, **Then** the response body is the card itself, byte for byte what the interface downloads.
2. **Given** an API key without that scope, **When** it requests the same contact, **Then** the card comes back with the sensitive values left out.
3. **Given** an API key for another account, **When** it requests that contact, **Then** the answer is not found.

---

### User Story 3 - A card that comes back updates the contact it came from (Priority: P3)

Somebody exports a contact, loads the file onto their phone, and the phone synchronises. They expect one contact afterwards, not two.

**Why this priority**: The export is useful without it, but leaving it out hands a duplicate to anybody who does the obvious thing with the file.

**Independent Test**: Export a contact, present the file back to synchronisation under a filing address the product has never seen, and confirm the original contact is updated and the vault holds no second contact.

1. **Given** an exported card presented under an unknown filing address, **When** its identifier matches a contact in the vault it is filed into, **Then** that contact is updated and no second contact appears.
2. **Given** an exported card whose identifier belongs to a contact in another vault or another account, **When** it is presented, **Then** it is treated as new, and nothing in the other vault is read or changed.
3. **Given** a card carrying no identifier, or one matching nothing, **When** it is presented, **Then** a new contact is created, as it is today.

---

### Edge Cases

- A contact whose card arrived from a device: the export keeps the properties the product does not understand rather than dropping them.
- A contact holding no field values: the file still carries the structured name and opens cleanly.
- A name a file system refuses, being empty, very long, or holding reserved characters: the download still gets a usable name.
- A contact whose values are approximate or partial dates: the file records what is known and invents no precision.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: A reader MUST be able to ask for the vCard of a contact in one interaction from the contact screen, and receive it as a downloaded file.
- **FR-002**: The file MUST carry the structured name in all its components, the nickname, the avatar, the contact's field values labelled as the structure labels them, and an identifier that stays stable for that contact.
- **FR-003**: The file MUST be the same card the product already hands to a synchronising device for that contact, so the two never disagree.
- **FR-004**: The file MUST carry the values of fields marked sensitive, because the reader already sees them on the screen. No choice is offered and no credential is asked, which keeps the export outside the sensitive action list settled in `027-sensitive-action-verification`.
- **FR-005**: The export MUST answer not found for a contact in another account or reached through the wrong vault.
- **FR-006**: The same export MUST be available through the API, answering with the card as the response body rather than wrapped in JSON, and MUST honour the sensitive fields scope of the key presented, so a key without it receives a card carrying none.
- **FR-007**: Every export MUST be recorded in the account journal, naming the contact.
- **FR-008**: The file MUST be named after the contact and carry the vCard content type, so an address book application offers to open it.
- **FR-009**: Every string the feature shows MUST be translatable and present in every configured locale.
- **FR-010**: The control MUST be reachable with the keyboard alone and announced by a screen reader.
- **FR-011**: An incoming card whose filing address is unknown MUST be matched to an existing contact by the identifier it carries, and MUST update that contact instead of creating a second one.
- **FR-012**: That matching MUST be confined to the vault the card is filed into. An identifier held in another vault or another account MUST NOT match, and that record MUST NOT be read or changed.

### Key Entities *(include if feature involves data)*

- **Contact**: the person exported, carrying a structured name, an avatar, and field values held under the structure version it points at.
- **Field value**: one value on the contact, which the field it belongs to may mark sensitive.
- **Journal entry**: the record the account keeps of the export having happened.
- **API key scope**: what decides whether a program calling the API reaches sensitive fields.
- **Card identifier**: the stable identity the exported file carries, which is what a returning card is matched by.
- **Filing address**: where a synchronising device filed a card, which is how an incoming card is matched first.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: A reader reaches the downloaded file in one interaction from the contact screen.
- **SC-002**: A card exported and then presented back to synchronisation reproduces the structured name, nickname, photo and field values of the contact it came from.
- **SC-003**: The file opens without error in Apple Contacts, Google Contacts and Thunderbird, showing the name, the photo and the contact details.
- **SC-004**: A contact holding 100 field values exports in under 2 seconds.
- **SC-005**: Every export appears in the account journal.
- **SC-006**: A card fetched with an API key lacking the sensitive fields scope carries no sensitive value.
- **SC-007**: For one contact and one reach, the API response body and the file the interface downloads are identical byte for byte.
- **SC-008**: Exporting a contact and presenting the file back to synchronisation leaves the vault holding the same number of contacts.

## Assumptions

- One contact at a time. Exporting a vault, a group or a selection is out of scope.
- A share link does not offer the download, and the shared contact page is unchanged. Offering it there is a separate feature, and the most natural one to follow this.
- The interface export carries everything the reader can see. Narrowing what a caller receives is the API's job, done through scopes.
- The card built for synchronising devices is the right card to hand over, so the export reuses it rather than defining a second shape.
- The file is produced on request and never stored. No queue, no notification.
- Activities, relationships, reminders, notes and attached files stay outside the card, because vCard has no place for them.
