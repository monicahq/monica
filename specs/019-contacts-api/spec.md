# Feature Specification: Contact endpoints

**Feature Branch**: `2026-09-04-api-contact` | **Created**: 2026-09-04 | **Status**: Draft

**Input**: "i want to manage contacts through the api with the features we do support in the ui"

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Read the people a vault holds (Priority: P1)

A contact is the thing the whole product orbits, and today the API can create the compartment but sees nobody in it. A caller has to be able to ask who is in a vault, narrow that list the way the screen narrows it, search it, sort it, and then read one person by their identifier.

**Why this priority**: It is the only story that hands over an identifier, so every other story depends on it, and it stands alone: a caller holding nothing else can already report who an account knows. **Independent test**: list the contacts of a vault holding favourites, archived people and a partial one, and confirm each grouping, each sort and the search box answer as the screen does, and that one contact reads back by identifier.

**Acceptance Scenarios**:

1. **Given** a caller holding a valid credential, **When** they list the contacts of one of their vaults, **Then** each person carries their identifier, their name as the product assembles it, the parts it was assembled from, their nickname, whether they are a favourite, whether they are archived, whether they represent the caller, the structure they render from, their picture, when something was last written about them, when they were added, and a link to themselves.
2. **Given** a vault holding favourites and archived people, **When** the caller asks for one of the groupings the screen offers, **Then** they get exactly the people that chip shows, with archived people out of every grouping but their own.
3. **Given** a vault holding a partial contact, somebody reduced to a first name by a relationship, **When** the caller lists contacts without asking for them, **Then** that person is absent, exactly as they are absent from the screen.
4. **Given** a caller who supplies a search term, **When** the answer comes back, **Then** it holds the people the same term finds on the screen, folded for the account's language the same way, and matching a nickname as typed.
5. **Given** a caller who asks for one of the orders the screen offers, by name, by nickname, by when somebody was last spoken to, or by when they were added, in either direction, **Then** the answer comes back in that order, with the people the sort knows nothing about at the end rather than mixed in.
6. **Given** more contacts than one page holds, **When** the caller asks, **Then** they get one page, how many there are in all, and the means to ask for the next, with a page size outside the allowed range brought back into it, and a vault holding nobody answers an empty list rather than an error.
7. **Given** a contact in another account, or in another vault of the same account, **When** the caller asks for them by identifier, **Then** the answer is not found, and nothing about them is disclosed.

---

### User Story 2 - Write somebody down, correct them, remove them (Priority: P2)

Somebody met once is worth writing down with nothing but a first name, and the name is the one thing about a contact the product imposes rather than the structure. A caller who can read contacts but never add one has to open a browser to do the thing the product is for.

**Why this priority**: It makes the API able to build an account rather than only report on one, which is what an import and a phone's quick capture need. **Independent test**: write a contact down with a first name alone, correct it to a full name, then remove them, and confirm they appear in the list, sort where the new name puts them, and leave without taking anything they took part in.

**Acceptance Scenarios**:

1. **Given** a caller naming a vault of theirs and a first name, **When** they write the contact down, **Then** the contact exists, renders from the vault's default structure, and the answer describes them.
2. **Given** a caller supplying a prefix, a middle name, a last name, a suffix, a maiden name, a nickname and the order the name is written in, **When** the contact is created, **Then** every part is kept and the assembled name follows the order that was asked for.
3. **Given** a caller who names a published structure of their own account that describes contacts, **When** the contact is created, **Then** they render from it rather than from the vault's default.
4. **Given** a caller who names a structure that is a draft, describes something other than a contact, or belongs to another account, **When** the request is answered, **Then** it is refused as invalid and nobody is written down.
5. **Given** a caller who writes down somebody as partial, **When** they list contacts afterwards, **Then** that person is off the list until they are filled in, exactly as the screen keeps them off.
6. **Given** a caller correcting a contact's name, **When** the change is made, **Then** every part of the name is replaced by what was sent, the assembled name changes with it, and where the contact sorts changes with it too.
7. **Given** a caller who removes a contact, **When** the request is answered, **Then** the contact is gone from the lists, everything they took part in stays, the reminders about them go quiet with the reason rather than disappearing, and the removal is recoverable rather than destroying anything.
8. **Given** the contact that represents the caller themselves, **When** the caller tries to remove them, **Then** the request is refused and says why, because a vault that lost it would be talking about nobody.

---

### User Story 3 - The marks that are not fields (Priority: P3)

Three things the product asks about somebody whatever structure they are on: whether they sit at the top of the list, what they are drawn as, and which version of the structure they render from. Each is a single gesture on the contact screen and none of them is a field.

**Why this priority**: They are what makes a contact built through the API indistinguishable from one built in the browser, and they come last because a contact without any of them is already usable. **Independent test**: mark a contact a favourite and unmark them, put a picture on and take it off, move them between published versions, and confirm each shows on the record while nothing written down about them moves.

**Acceptance Scenarios**:

1. **Given** a caller who sends the mark they want rather than asking for a toggle, **When** the request is answered, **Then** the contact ends in the state that was asked for, and sending the same state twice confirms it rather than inverting it.
2. **Given** a contact marked a favourite, **When** the caller asks for the favourites grouping, **Then** that contact is in it.
3. **Given** a caller sending a picture of an accepted kind and within the accepted size, **When** the request is answered, **Then** the picture becomes a file of the vault, the contact is drawn as it from then on, and the answer says where it can be read.
4. **Given** a caller sending something that is not a picture, or a picture over the accepted size, or one that would take the account past the storage it has left, **When** the request is answered, **Then** it is refused and says which, and the contact keeps whatever it had.
5. **Given** a contact with a picture, **When** the caller takes it off, **Then** the contact is drawn as their initials again, on the background it was given when they were created, and that background does not change.
6. **Given** a caller naming a published version of the structure the contact already renders from, or naming no version at all, **When** they move the contact, **Then** it renders from that version, or from whatever that structure publishes now, and nothing written down about them is touched.
7. **Given** a caller naming a draft version, a version of another structure, or one of another account, **When** the request is answered, **Then** the answer is not found and the contact is left where it was.

---

### Edge Cases

- A contact is renamed to nothing at all. Every part of a name is optional on a correction, and a contact reduced to no name still has to sort, search and draw initials, so the product supplies what it always supplies rather than refusing.
- A caller searches for a name written with accents in a language that ignores them. The fold is the account's, not the request's, so a queued import and a live search find the same people.
- A picture is replaced. The file behind the old one is let go rather than deleted, because a vault's files outlive what pointed at them.
- A caller sorts by when somebody was last spoken to on a vault where nothing has been written about anybody. Everyone is unknown to that sort, so everyone is at the end, in an order that is stable rather than arbitrary, because the order always ends on the identifier. That is also what keeps a person from being skipped or repeated by a page boundary moving under them.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The API MUST offer a way to list the contacts of one of the caller's vaults, ordered so that the same request twice returns the same order.
- **FR-002**: Each contact the API describes MUST carry its identifier, the assembled name, every part the name is assembled from, the order the name is written in, the nickname, whether it is a favourite, whether it is archived, whether it is partial, whether it represents the caller, the structure and the version it renders from, the picture if it has one and the background of its initials if it has not, when something was last written about it, when it was added, when it was last changed, and a link to itself.
- **FR-003**: The API MUST offer a way to read one contact of the caller's by its identifier, described exactly as the list describes it.
- **FR-004**: The list MUST offer the same groupings the contacts screen offers, everyone, favourites and archived, and MUST keep archived people out of every grouping but their own and partial people out of all of them.
- **FR-005**: The list MUST accept a search term and MUST match it the way the screen matches it, folded for the account's language against the assembled name and matched as typed against the nickname.
- **FR-006**: The list MUST accept each of the orders the screen offers, by name, by nickname, by when somebody was last spoken to and by when they were added, in either direction, and MUST place the people an order knows nothing about at the end of it.
- **FR-007**: Every list MUST be answered a page at a time, saying which page it is, how many one page holds and how many there are in all, using the same default and maximum page size as the rest of the API and bringing a size outside the range back into it.
- **FR-008**: The API MUST offer a way to write a contact down in one of the caller's vaults, requiring nothing but a first name, and accepting a prefix, a middle name, a last name, a suffix, a maiden name, a nickname, the order the name is written in, whether the person is partial and whether they are a favourite.
- **FR-009**: Creation MUST let a caller name a published structure of their own account that describes contacts, MUST fall back to the vault's default structure when none is named, and MUST refuse a draft, a structure describing something else, and one of another account.
- **FR-010**: The API MUST offer a way to correct every part of a contact's name at once, and MUST rebuild what the lists sort and search on from the corrected name.
- **FR-011**: The API MUST offer a way to remove a contact, which MUST leave everything they took part in, MUST take the reminders about them quiet with the reason rather than deleting them, MUST keep the removal recoverable, and MUST refuse to remove the contact representing the caller.
- **FR-012**: The API MUST offer a way to say whether a contact is a favourite, carrying the state wanted rather than asking for a toggle, so that the same request sent twice confirms the state rather than inverting it.
- **FR-013**: The API MUST offer a way to put a picture on a contact and a way to take it off, accepting the same kinds and the same maximum size the browser path accepts, writing the upload as a file of the vault, letting the replaced file go rather than deleting it, and returning to the initials on their unchanged background when the picture is taken off.
- **FR-014**: The API MUST offer a way to move a contact onto another published version of the structure it already renders from, MUST take the version that structure publishes now when none is named, MUST refuse anything else as not found, and MUST leave everything written on the record untouched.
- **FR-015**: A contact, a vault or a structure version belonging to another account, or to another vault than the one named in the address, MUST answer not found on every endpoint, indistinguishable from an identifier that matches nothing.
- **FR-016**: A contact MUST be addressed by the unguessable identifier it already carries, never by a sequential number, and MUST be reachable only through the vault it lives in.
- **FR-017**: Every endpoint MUST refuse an unauthenticated caller and MUST be held to the same rate limit as the rest of the authenticated API.
- **FR-018**: An invalid request MUST be refused as invalid, MUST name every field that is wrong, and MUST leave the account exactly as it was.
- **FR-019**: Every write reaching the API MUST reuse the same operation the browser path calls, so the two surfaces cannot come to disagree, and MUST leave the same trace in the account journal.
- **FR-020**: Reading a contact or a list of them MUST NOT itself be written to the account journal, and MUST NOT count as somebody having consulted the record.
- **FR-021**: The endpoints MUST appear in the public API reference as their own group, describing every field of a contact, every endpoint, the page parameters, the groupings, the orders, the name orders, the picture rules, the rate limit, the refusals and the not found behaviour, with an example response for each, and MUST stay in step with the routes that exist in the way the reference is already checked against the route table.

### Key Entities

- **Contact**: A person being documented, living in exactly one vault of exactly one account. The name is the only thing the product imposes, because sorting, searching, headers and initials are all built from it; everything else on the page comes from the structure the contact renders from. Carries whether it is a favourite, whether it is archived, whether it is partial, and whether it represents the person using the product, of which there is one per vault.
- **Name**: The parts a contact's assembled name is built from, a prefix, a first name, a middle name, a last name, a suffix, a maiden name and a nickname, plus the order they are written in. Every part is optional, including the assembled result.
- **Picture**: What a contact is drawn as. An uploaded one becomes a file of the vault and counts against the account's storage; without one the contact is drawn as its initials on a background chosen when it was created and never changed afterwards.
- **Structure version**: The published version of a template describing contacts, which says what pages a contact has, what blocks are on them and what fields are in each. A contact points at exactly one, and moving between versions writes nothing.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Everything the contacts list of a vault shows about a person is readable through the API, field for field, in every grouping, every order and under the same search.
- **SC-002**: Every gesture the contact screens offer over the contact itself, writing one down, correcting the name, marking a favourite, putting a picture on, taking it off, moving to another version of the structure and removing the contact, is performable through the API and leaves the account in the same state as the same gesture made in the browser.
- **SC-003**: A caller holding nothing but a credential can build an account from empty to a vault holding named contacts with pictures, using the API alone, and a contact built that way is indistinguishable from one built in the browser, in the record itself and in what the account journal says happened.
- **SC-005**: No endpoint discloses the existence of a contact in another account or another vault, and every such request answers exactly as a request for an identifier that matches nothing.

## Assumptions

- **Two surfaces are deliberately left out and get their own specifications.** What is written on a contact, the template driven pages, blocks, fields and answers, because that machinery is larger than everything here put together; a contact still reports which structure version it renders from, which is the identifier that surface will need. And the private links onto a contact's page, because a token and a revocation rule is a security surface rather than a record one.
- **The API mirrors what the interface supports today, not what the operations underneath could do.** Archiving is the clear case: the operations exist and the list has a grouping for archived people, but no screen offers the gesture, so the API reads the state and does not write it. Restoring a removed contact is left out for the same reason. If a screen gains either gesture, the endpoint follows it.
- **A contact is reached through its vault, in the address, the way every contact screen does.** Neither half is trusted alone: the account has to match and the person has to be in the vault that was named.
- **The list answers in the screen's order rather than newest first**, so a caller and a person see the same list, with the identifier breaking a tie so the order is total. Page size follows the rest of the API, its configured default and maximum, rather than the fifty the screen shows.
- **The picture is sent as an upload, the way the browser sends it**, subject to the same kinds, the same maximum size and the same account storage.
- **Which contact represents the person using the product is reported and not writable.** Exactly one per vault holds it and nothing in the interface hands it over. Reminders on a contact's dates are specified separately; this specification only says that removing a contact takes them quiet.
