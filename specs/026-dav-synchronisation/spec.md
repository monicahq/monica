# Feature Specification: Address book and calendar synchronisation

**Feature Branch**: `2026-09-09-dav-synchronisation` | **Created**: 2026-09-09 | **Status**: Draft

**Input**: "add monicahq/laravel-sabre integration"

## Clarifications

### Session 2026-09-09

- Q: When a field is marked sensitive, does its value reach the device through the address book, or stay in Erica as it stays out of a private link? → A: Only when the device key was minted with that choice, which is off by default.
- Q: When somebody deletes a card on their phone, what happens to the contact in Erica? → A: It is removed the way the API removes one, recoverable in the data and journalled, rather than archived or refused.
- Q: What does the calendar a device subscribes to hold for each vault? → A: Both a calendar of the recurring dates on records, as yearly all day entries, and a task list of the upcoming deadlines the reminders screen lists, each per vault and both read only.
- Q: What happens when a card saved from a device carries information the structure has no field for? → A: Revised the same day. The card is kept whole as the device sent it; what has a field is written to the record, the rest stays on the card, and the card reads back as that original with every value the record holds merged over it, so nothing a device wrote is lost and nothing Monica holds is stale.
- Q: Which contacts and calendar applications must the first release be verified against? → A: Apple Contacts and Calendar on iOS and macOS, Android through DAVx5, Thunderbird, GNOME Contacts and Evolution, and KDE Kontact.

### Session 2026-09-10

- Q: When a device saves a card carrying more values of one kind than the record has room for, three phone numbers where the structure holds one phone field, what happens to the values that do not fit? → A: They stay on the card and reach the person's other devices, the record's field taking the value the card lists first, and none of them is written to the record.
- Q: Should giving a record room for several emails and phone numbers happen inside this feature, or as its own piece of work? → A: Its own feature. This spec keeps the rule above and names the capability as owed; when it lands the sync writes every value with no change to its design.
- Q: When a second device saves a card that no longer carries what the first device had put on it, should the kept card become exactly what the second device sent? → A: Yes. One card is kept per contact and it is the one the last device sent, so removing a property on a device removes it everywhere.
- Q: Should a deadline reach the device as a moment in time, or as a clock time that reads the same in every timezone? → A: A moment in time, the one the product will act on, which a device shows in the timezone it is set to.
- Q: When somebody drags a card from one vault's address book into another on their device, should the product refuse the move? → A: No. The standard's fallback stands: the card is created in the target vault and the source contact is removed, so the person arrives as a new record without their history, and the journal carries both halves.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - See the people of a vault on a device (Priority: P1)

The people somebody documents in Erica are the people they call, text and visit, and the phone is where those gestures start. Adding the account to the contacts application of a phone or a computer, over CardDAV, the standard those applications already speak, puts every vault on the device as an address book of its own, with the name, the nickname, the picture and the ways of reaching each person that the record holds. Nothing of ours is installed on the device.

**Why this priority**: It is the story every other one connects through, and read only is already most of the value, since a name and a number where the call is made is what most people want from an address book. **Independent test**: mint a key for a device, connect a stock contacts application with the address the security screen shows, and confirm each vault appears as an address book whose cards match the records field for field.

**Acceptance Scenarios**:

1. **Given** a person holding a key made for a device, **When** they set the account up in a standard contacts application with the address the product shows them, their email and that key, **Then** the application finds one address book per vault of the account, named after the vault.
2. **Given** a vault holding favourites, archived people, a partial one and the person's own contact, **When** the device reads the address book, **Then** it holds exactly the people the everyone grouping of the contacts screen shows, so the archived and the partial are absent and the person's own contact is present.
3. **Given** a contact carrying a name, a nickname, a picture, and fields holding emails, phone numbers, postal addresses, websites and a birthday, wherever those fields sit in the structure, **When** the device reads the card, **Then** every part of the name is in its own slot, the nickname and the picture are there, every one of those values is there with the label of the field it came from, while a field of any other kind stays home, and so does a field marked sensitive unless the key was minted to reach them.
4. **Given** a birthday whose year is unknown, **When** the card is read, **Then** it carries the day and the month and no year, rather than a year the product invented.
5. **Given** a contact renamed in the browser and another removed since the device last looked, **When** the device refreshes, **Then** it is handed only the card that changed and told which one is gone, rather than every card again.
6. **Given** a key of another account, **When** the device asks for a vault of this one, **Then** the answer is not found, indistinguishable from a vault that does not exist.
7. **Given** a device presenting no credential, a wrong one, a revoked key, or the account password, **When** it connects, **Then** it is refused, and neither the second factor nor a passkey is ever asked for, because a key is not a sign in.

---

### User Story 2 - Change somebody from the device (Priority: P2)

A number typed into the phone right after a call, an acquaintance added on the spot, a spelling corrected on the train. Each of these lands in the record without anybody opening a browser, and reads back on every other device the person has.

**Why this priority**: It turns the device from a mirror into a way in, which is what quick capture on a phone means before a native application exists. It comes second because a mirror that cannot write is already useful and a write that goes wrong touches data. **Independent test**: on the device, correct a name, add a phone number, create a contact and delete one, then confirm the browser shows each change and the account journal names the device's key as its source.

**Acceptance Scenarios**:

1. **Given** a card edited on the device, **When** the change is saved, **Then** the contact's name parts, nickname and every value the card carries for a field the record has replace what was there, a sensitive field the key does not reach is left as it was, the contact sorts where the new name puts them, and the journal records the change with the device's key as its source.
2. **Given** a new card saved into an address book, **When** it is saved, **Then** a contact is created in that vault on the vault's default structure, holding the name and whichever parts of the card have a field to go in, and a card with no name part at all is refused.
3. **Given** a card carrying something the contact's structure has no field for, an organisation, a note or a property the product has never heard of, **When** it is saved, **Then** everything that has a field is written to the record, the whole card is kept as sent, and the device's next refresh reads that original card with the record's current values merged into it, so nothing it wrote is lost.
4. **Given** a card deleted on the device, **When** the deletion is saved, **Then** the contact is removed exactly as the API removes one, recoverable, leaving everything they took part in, taking the reminders about them quiet with the reason, and written to the journal.
5. **Given** the card of the person's own contact, **When** the device deletes it, **Then** the deletion is refused and the card stays.
6. **Given** a picture changed or removed on the device, **When** it is saved, **Then** it is treated as an upload is, same kinds, same maximum size, same account storage, and a removed picture returns the contact to its initials on their unchanged background.
7. **Given** a contact changed in the browser since the device last read it, **When** the device saves its own version of the card, **Then** the save is refused as a conflict so the device reads the card again before writing, and the browser's change is not overwritten.

---

### User Story 3 - See the dates and the deadlines ahead in a calendar (Priority: P3)

The dates on a record that come round every year, a birthday, a wedding anniversary, the day two people met, belong in the calendar somebody already looks at every morning, and so does what the [reminders screen](../015-vault-reminders-list/spec.md) lists as falling due over the coming weeks. Over CalDAV, each vault offers the first as a calendar and the second as a task list, both read only.

**Why this priority**: It reuses the connection the first story sets up and adds two things to look at, and it is last because the deadlines already reach the person as notifications and a birthday already reaches them on the card. **Independent test**: connect a stock calendar application and confirm the vault's calendar shows every recurring date on its records every year, and its task list the same deadlines as the reminders screen at the same moments, with a postponed one at its new time and a dismissed one absent.

**Acceptance Scenarios**:

1. **Given** records carrying recurring dates, one with an unknown year and one inside a repetition, **When** the device reads the vault's calendar, **Then** it holds one all day entry per date, every year, named after the person and the label of the field, the one with an unknown year included.
2. **Given** a vault with reminders whose deadlines fall inside the horizon the reminders screen shows, one postponed, one dismissed and one cancelled because its contact was archived, **When** the device reads the vault's task list, **Then** it holds one entry per deadline at the moment the product will act on it, named after the reminder and the person, with the postponed one at its new time, the other two absent, and nothing beyond the horizon.
3. **Given** a device that tries to add, move, edit, complete or delete an entry in either, **When** the change is saved, **Then** it is refused and the entry stays, because postponing and dismissing are gestures of the product with their own effects and a date is corrected on the record.

---

### Edge Cases

- A card is dragged from one vault's address book into another. The standard turns a move it cannot perform into a copy and a removal, and that is what happens: the person is created in the target vault on its default structure and removed from the source the way any removal from a device works. Their reminders, snapshots, relationships, activities and files stay with the removed record and do not follow, so what arrives is a new person carrying the card and nothing of their past. The journal records the creation and the removal, which is what makes the move visible afterwards.
- A second device saves a card without the properties the first device had put on it, because it drops what it does not understand. One card is kept per contact and it is the one the last device sent, so those properties go for every device, exactly as they would had somebody removed them by hand. This is what makes a removal on a device a removal.
- A device removes the account with the option to delete its contacts from the server, and every card in every address book is deleted at once. Each is the same recoverable removal and each is journalled, but no screen offers a restore yet, so the way back exists in the data and not in the interface until that screen is built.
- A vault is renamed, deleted, or shares its name with another. The address book takes the new name at the next refresh, disappears with the vault and its people, or sits beside its namesake told apart by identity and not by label.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The product MUST offer address book synchronisation over the standard the contacts applications of phones and computers already speak, so that connecting needs an address, an email and a credential and no software of ours.
- **FR-002**: Every vault of the account MUST appear as one address book named after the vault, holding the people the everyone grouping of the contacts screen shows, the person's own contact included, the archived and the partial excluded.
- **FR-003**: A card MUST carry every part of the name in its own slot, the nickname, the picture, and every value of every email, phone, website, postal address and recurring date field on the record, wherever the field sits in the structure including inside a repetition, each labelled with the label of its field, MUST carry a value of a field marked sensitive only when the key was minted to reach sensitive fields, and MUST carry nothing else from the record.
- **FR-004**: A date whose year is unknown MUST reach the device without a year.
- **FR-005**: A device MUST be able to ask for what changed since its last visit and receive only the cards that changed and the identities of the cards that are gone.
- **FR-006**: A device MUST authenticate with the account's email and a key minted for the purpose, MUST be refused when it presents the account password, and MUST never be asked for a second factor or a passkey; a revoked key fails closed.
- **FR-007**: The security screen MUST show the address a device is given, MUST let the person mint a key for a device, shown once and never again, with the choice to let it reach sensitive fields, off by default, and MUST list and revoke it beside the API keys, since it is one, saying which keys reach sensitive fields.
- **FR-008**: Every write a device makes MUST reuse the operation the browser and the API call for the same gesture, and MUST be written to the account journal with the key as its source.
- **FR-009**: A card saved into an address book MUST create a contact in that vault on the vault's default structure. Which cards are accepted, repaired or refused is decided by [074](../074-carddav-accept-any-vcard/spec.md).
- **FR-010**: The card a device saves MUST be kept whole, a part of it the structure has no field for MUST stay on the card without failing the save, a value of a kind the structure maps but has no room for MUST stay on the card and reach the person's other devices without being written to the record, the record's fields taking the values the card lists first, a sensitive field the key does not reach MUST be left as it was, and the card read back MUST be the kept card with every value the record holds merged over it.
- **FR-011**: A card deleted on a device MUST remove the contact the way the API removes one, recoverable and journalled, and MUST refuse to remove the person's own contact.
- **FR-012**: A picture saved from a device MUST go through the same upload path, kinds, maximum size and account storage as the browser, and removing it MUST return the contact to its initials.
- **FR-013**: A save over a card that changed since the device last read it MUST be refused as a conflict rather than overwrite the change.
- **FR-014**: A vault of another account MUST answer not found on every request, indistinguishable from a vault that does not exist, and a key confined to a vault, once keys can be, MUST reach that vault alone.
- **FR-015**: Reading a card or an address book MUST NOT be written to the account journal and MUST NOT count as somebody having consulted the record.
- **FR-016**: Every request MUST be held to the same rate limit as the rest of the authenticated API.
- **FR-017**: Every vault MUST appear as one read only calendar holding every recurring date on the records of the people its address book holds, as a yearly all day entry named after the person and the field, and as one read only task list holding the deadlines the reminders screen lists, each at the moment the product will act on it, named after the reminder and the person, postponed ones at their new time, dismissed and cancelled ones absent, with every write to either refused.
- **FR-018**: The public reference MUST gain a page saying how to connect each of the verified clients, the address, the credential, what a card carries, how a saved card is kept and merged, and what the calendar and the task list hold.

### Key Entities

- **Address book**: One vault as a device sees it, named after the vault, holding a card per person the everyone grouping shows.
- **Card**: One contact as a device sees it, the name in its parts, the nickname, the picture and the values of the fields a device understands, each labelled with its field. It keeps one identity across every device of the person, and when a device wrote it, the card it sent is kept whole and the record's values are merged over it on every read.
- **Device key**: An API key minted for a device, presented with the account's email, carrying whether it reaches sensitive fields, off unless chosen at minting, listed and revoked with the other keys, named as the source of every write it makes.
- **Calendar and task list**: One vault's recurring dates as yearly all day entries and its upcoming deadlines as tasks, both read only, both over the people the address book holds.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: A person can connect Apple Contacts and Calendar on iOS and macOS, Android through DAVx5, Thunderbird, GNOME Contacts and Evolution, and KDE Kontact using only what the security screen shows them, in under five minutes and with nothing of ours installed.
- **SC-002**: Every name part, nickname, picture, email, phone number, postal address, website and birthday a record holds is on the device, and a change made in the browser is on the device at its next refresh.
- **SC-003**: A correction, a creation and a removal made on the device leave the account in the same state as the same gesture made in the browser, each journalled with the device as its source.
- **SC-004**: No device reads or writes a vault of another account, and every such request answers as though the vault did not exist.
- **SC-005**: Every recurring date and every upcoming deadline a vault holds appears in every verified client that shows a calendar, the dates every year and the deadlines at the moments the reminders screen counts down to.

## Assumptions

- **The credential is an API key and never the password.** Devices only know how to send a name and a password, and the account password must not travel that way: it would bypass the second factor, and a person who signs in with a passkey has none. A key is already shown once, revoked in one place and named in the journal, which is everything a device credential needs, so a device key is an API key minted from the same screen. Whether it reaches sensitive fields is chosen at minting and off by default, because an address book on a phone is readable by the other applications on it, and that choice is the first use of the scopes the token specification asks for. Confining it to one vault and marking what kind of caller it is follow the [token specification](../../spec/models/006-personal-access-token-in-progress.md) and are not decided here.
- **One address book per vault, holding what the everyone grouping holds.** A vault is the compartment, the archived are archived to stop being seen, and a partial contact is somebody the person has not decided to document. The person's own contact is a real contact and stays.
- **A card carries what a device can show.** Emails, phone numbers, postal addresses, websites, recurring dates, the name, the nickname and the picture. Everything else a structure asks about somebody has no place on a card, and inventing one would put it where the device cannot render it. Birthdays reach the device on the card as well as in the calendar, and a phone that builds its own birthday calendar shows them twice until the person hides one; the calendar is kept whole because an anniversary or the day two people met reaches a device no other way.
- **A record holds one value per field, and holding several is owed elsewhere.** The supplied structure gives one email, one phone, one website and one address, so a device handing over three numbers writes the first and carries the rest on its card, where the person's other devices still read them. Giving a record room for several values of a kind is its own feature, because the record screen, the API, the importer and every existing account's structure read that shape; when it lands, this sync writes them all with nothing here changing.
- **The card a device sends is kept whole and merged, never rebuilt from the record alone.** Devices put things on a card the product has no field for, and a card rebuilt from the record would strip them at the next refresh without a word. So the original is kept as sent and the record's values are merged over it on every read. What has no field lives on the card only, which the product never reads, searches or shows, so it is not a second way to attach data. Refusing such a save was rejected: contacts applications retry a refused save indefinitely. Files are not synchronised either way; nothing here exposes one beyond a contact's picture.
