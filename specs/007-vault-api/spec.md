# Feature Specification: Vault endpoints

**Feature Branch**: `2026-09-01-vault-api`

**Created**: 2026-09-01

**Status**: Draft

**Input**: User description: "add the vault management in the api + doc. that means, add, update and destroy a vault."

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Open a compartment from a script (Priority: P1)

A vault is the compartment everything else lives in. Nothing can be recorded until one exists, so a caller who cannot create one through the API cannot use the API for anything that holds data. Somebody setting up an account from a script, restoring from a backup they wrote themselves, or preparing a fresh account for somebody else has to open the browser once, at the very first step, before a program can take over.

**Why this priority**: It is the gate in front of every other capability. It is also the one endpoint that stands entirely on its own: the answer carries the new vault's identifier, so a caller who has only this can already create a vault and then keep working with it.

**Independent Test**: Call the endpoint that creates a vault with a valid credential and a name, and confirm a vault exists in the caller's account afterwards, that the answer describes it, and that the answer carries the identifier the other endpoints take.

**Acceptance Scenarios**:

1. **Given** a caller holding a valid credential, **When** they create a vault with a name, **Then** the vault exists in their account, the answer says it was created, and it carries the vault's identifier, name, colour and place in the list.
2. **Given** a caller who supplies a name, a description and a colour, **When** the vault is created, **Then** all three are what comes back, and the colour is one of the colours the product offers.
3. **Given** a caller who supplies no colour, **When** the vault is created, **Then** it is given the colour a vault gets when nobody picks one, rather than the request being refused.
4. **Given** a caller whose account holds no vault yet, **When** they create their first one, **Then** it is the vault records land in when nobody says which, because exactly one vault has to be that and there is nothing else to be it.
5. **Given** a caller who supplies no name, a name longer than the limit, or a colour the product does not offer, **When** the request is answered, **Then** it is refused as invalid and says which field is wrong.
6. **Given** a caller with no credential, or one whose credential has been revoked, **When** they try to create a vault, **Then** the request is refused as unauthenticated and no vault is created.
7. **Given** a vault has been created through the API, **When** somebody reads the account journal, **Then** the creation is written there exactly as a creation made in the browser is.

---

### User Story 2 - Read the compartments of the account (Priority: P2)

Every other endpoint here needs a vault's identifier, and only the vault a caller just created hands one over. Somebody writing against an account they did not set up from a script, or a tool that reports on what an account holds, has to be able to ask which vaults exist and what each one is.

**Why this priority**: It is what makes the three write endpoints usable on an account whose vaults already exist, and it is worth having on its own, since a caller who has only this can already report the compartments of an account and how much each one holds.

**Independent Test**: Call the endpoint that lists vaults on an account holding several, and confirm every vault comes back, in the order the account arranged them, each with its identifier and its wording, and that asking for one of them by that identifier answers with the same vault.

**Acceptance Scenarios**:

1. **Given** a caller holding a valid credential and an account holding several vaults, **When** they ask for the list, **Then** they get the vaults in the order the account arranged them, each carrying its identifier, name, description, colour, place in the list, whether it is the default one, how many contacts it holds, and a link to itself.
2. **Given** an account holding more vaults than one page holds, **When** the caller asks for the list, **Then** they get one page, together with how many vaults there are in all and the means to ask for the next page.
3. **Given** a caller who asks for a page size outside what is allowed, **When** the request is answered, **Then** the size is brought back into the allowed range rather than the request being refused.
4. **Given** an account holding no vault at all, which is the state every account starts in, **When** the caller asks for the list, **Then** they get an empty list rather than an error.
5. **Given** a caller holding a valid credential, **When** they ask for one of their vaults by its identifier, **Then** they get that vault, described exactly as the list describes it.
6. **Given** a vault belonging to another account, **When** the caller asks for it by its identifier, **Then** the answer is not found, and nothing about that vault is disclosed.
7. **Given** an identifier that matches no vault at all, or one that matches a vault already deleted, **When** the caller asks for it, **Then** the answer is not found, told apart from a vault that exists elsewhere by nothing at all.

---

### User Story 3 - Change what a vault says about itself (Priority: P3)

A vault carries a name, what it is for and the colour it is told apart by. Renaming one, or correcting what it is for, is something the settings screen already does, and a caller who can create a vault but never correct it is left with a mistake they have to open the browser to fix.

**Why this priority**: It is the least destructive of the three writes and the least urgent, since a caller can work around a wrong name. It cannot be tested without a vault to change, which the first story or the second one supplies.

**Independent Test**: Change the name, the description and the colour of one of the caller's vaults, and confirm all three changed, that nothing the vault holds moved, and that the change is written to the account journal.

**Acceptance Scenarios**:

1. **Given** a caller holding a valid credential and a vault of their own, **When** they change its name, description and colour, **Then** the answer carries the vault as it now reads, and the three values are what they sent.
2. **Given** a vault holding contacts, **When** its wording is changed, **Then** what it holds is untouched, and so are its place in the list and whether it is the default one.
3. **Given** a caller who supplies no name, no colour, no statement of what the vault is for, or a colour the product does not offer, **When** the request is answered, **Then** it is refused as invalid, says which field is wrong, and the vault is left as it was.
4. **Given** a vault belonging to another account, **When** the caller tries to change it, **Then** the answer is not found, and the vault is left as it was.
5. **Given** a change that alters nothing, because every value sent is what was already there, **When** the request is answered, **Then** it succeeds, and the journal records the update without claiming a value changed.
6. **Given** a vault whose wording has been changed through the API, **When** somebody reads the account journal, **Then** the change is there, naming the values before and after, exactly as a change made in the browser is.

---

### User Story 4 - Delete a vault and everything it separates (Priority: P4)

Deleting a vault is the most destructive thing somebody can do short of deleting the account, and it is the one operation the settings screen guards with a warning that says how many contacts go with it. A caller who cannot do it through the API has to open the browser for the one operation a script most wants to be able to undo an experiment with.

**Why this priority**: It is last because it is the most dangerous and because it is worthless without something to delete. It is also where the parity the product promises matters most, since a capability that exists only behind a warning screen is a capability no automated tool can reach.

**Independent Test**: Delete one of the caller's vaults and confirm the vault is gone, that the contacts and invented records it held went with it, that private links into it no longer open, and that the account is left in a coherent state.

**Acceptance Scenarios**:

1. **Given** a caller holding a valid credential and a vault of their own, **When** they delete it, **Then** the answer says it succeeded and carries nothing, and the vault no longer appears in the list.
2. **Given** a vault holding contacts and records of the kinds the account invented, **When** it is deleted, **Then** they go with it, marked as having left with the vault, so that bringing the vault back later brings back these and not somebody deleted on their own beforehand.
3. **Given** private links onto the vault or onto the contacts it holds, **When** the vault is deleted, **Then** those links stop opening, permanently, and whoever brings the vault back decides again who may read it.
4. **Given** the vault being deleted is the one records land in by default, **When** it is deleted, **Then** the first vault left takes that title, so exactly one vault holds it whenever the account holds a vault at all.
5. **Given** the vault being deleted is the last one the account holds, **When** it is deleted, **Then** it goes like any other, and the account is left holding no vault and no default, which is the state every account starts in.
6. **Given** a vault belonging to another account, or one already deleted, **When** the caller tries to delete it, **Then** the answer is not found, and nothing is deleted.
7. **Given** a vault has been deleted through the API, **When** somebody reads the account journal, **Then** the deletion is there, naming the vault, exactly as a deletion made in the browser is.

---

### User Story 5 - Say where records land and what they start from (Priority: P5)

The screen that creates a vault asks two things that are decisions about the account rather than wording of the vault: whether this is the vault records land in when nobody says which, and which published contact structure a new contact in it starts from. A caller who can create a vault but not answer those two has created a vault that behaves differently from one created in the browser.

**Why this priority**: It closes the gap between the API and the screen, and it is last because the first four stories are already usable without it: a vault created without either answer gets the account's sensible default for both.

**Independent Test**: Create a vault asking for it to be the default and naming a published contact structure, and confirm the account's default moved to it and that a new contact in it starts from the structure named.

**Acceptance Scenarios**:

1. **Given** a caller creating a vault who asks for it to be the vault records land in by default, **When** it is created, **Then** it holds that title and the vault that held it before does not, because exactly one vault per account is it.
2. **Given** a caller creating a vault who names a published contact structure, **When** it is created, **Then** a new contact in that vault starts from that structure rather than from the account's shipped one.
3. **Given** a caller changing a vault who asks for it to be the default, or who names a published contact structure, **When** the request is answered, **Then** the same two decisions are settled the same way as on creation.
4. **Given** a caller who names a structure belonging to another account, one that describes something other than a contact, or one that is still a draft, **When** the request is answered, **Then** it is refused as invalid and neither decision is applied.
5. **Given** a caller who says the vault is not the default, or who says nothing about it, **When** the request is answered, **Then** whichever vault is the default stays the default, because the title is never left unheld while the account holds a vault.
6. **Given** either decision has been settled through the API, **When** somebody reads the account journal, **Then** it is written there as its own entry, since each is its own act rather than part of writing the vault down.

---

### Edge Cases

- A name arrives carrying markup, or surrounding blank space. It is reduced to plain text before it is written down, exactly as the browser path reduces it, so the same name typed in either place is stored the same way.
- A colour arrives in a different case from the palette. It is brought to one case rather than refused, so a caller who writes it in capitals is not made to care.
- A description arrives absent, empty, or explicitly empty. The vault has no description, and the answer says so with an empty value rather than leaving the field out.
- A caller asks for the same vault to be the default when it already is. Nothing changes and the request still succeeds.
- The account holds one vault and the caller says that vault is not the default. It stays the default, because an account holding a vault always has one, and there is nothing else to hand the title to.
- A vault is created on an account so new that the structures a contact starts from have not been seeded yet. The vault is created without one and picks the structure up when the seeding arrives, which is already how the browser path behaves.
- The list is asked for a page size of zero, a negative number, or a number far above the maximum. The size is clamped, matching every other list in the API.
- A caller pages through the list while another vault is created. The order is the one the account arranged rather than one that shifts as vaults appear, so a vault is not skipped by a page boundary moving under it.
- A vault is deleted and then asked for by the identifier the caller still holds. It answers not found, indistinguishable from an identifier that never named anything.
- Deleting a vault leaves the account with none, and the caller immediately creates another. That one is the default, because there was nothing else to be it.
- Two requests try to delete the same vault at once. The second finds nothing to delete and answers not found, rather than reporting a second deletion.
- The identifier in the address is a sequential number rather than the unguessable one. It names no vault, so guessing the next number after somebody else's vault leads nowhere.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The API MUST offer a way to create a vault in the caller's account, taking a name, and optionally what the vault is for and the colour it is told apart by.
- **FR-002**: Creation MUST require a name, MUST accept a description or none, and MUST accept a colour or none, giving a vault with no colour named the colour a vault gets when nobody picks one.
- **FR-003**: A colour MUST be one the product offers, and a colour outside that set MUST be refused as invalid rather than stored.
- **FR-004**: A created vault MUST go at the end of the account's list, and MUST become the vault records land in by default when the account held none before it.
- **FR-005**: The API MUST offer a way to list the vaults of the caller's account, in the order the account arranged them.
- **FR-006**: Each vault the API describes MUST carry its identifier, its name, what it is for, its colour, its place in the list, whether it is the default one, how many contacts it holds, when it was created and last changed, and a link to itself.
- **FR-007**: The list MUST be answered a page at a time, MUST say which page it is, how many one page holds and how many vaults there are in all, and MUST use the same default and maximum page size as the rest of the API, bringing a size outside the range back into it rather than refusing the request.
- **FR-008**: The API MUST offer a way to read one vault of the caller's account by its identifier, describing it exactly as the list describes it.
- **FR-009**: The API MUST offer a way to change a vault's name, what it is for, and its colour, and that change MUST NOT touch what the vault holds, its place in the list, or whether it is the default one.
- **FR-009a**: A change MUST replace all three, so a caller MUST state each one: the name and the colour are required, and what the vault is for MUST be stated even when the answer is nothing. A change MUST NOT quietly reset a value the caller did not mention, because nothing may disappear without being said.
- **FR-010**: The API MUST offer a way to delete a vault of the caller's account, and the deletion MUST behave exactly as the deletion the settings screen performs: soft, taking the contacts and the invented records it holds with it and marking them as having left with it, revoking every private link into the compartment permanently, and handing the default title to the first vault left when the deleted vault held it.
- **FR-011**: Deleting the last vault of an account MUST be allowed, and MUST leave the account holding no vault and no default, which is the state every account starts in.
- **FR-012**: Creation and change MUST both accept an instruction that this vault is the one records land in when nobody says which, and applying it MUST take the title off whichever vault held it, so that exactly one vault holds it whenever the account holds a vault.
- **FR-013**: An absent instruction, or one saying the vault is not the default, MUST leave the current default alone, because the title is never left unheld.
- **FR-014**: Creation and change MUST both accept a published contact structure that new contacts in the vault start from, and MUST refuse one belonging to another account, one describing something other than a contact, or one that is still a draft.
- **FR-015**: A vault belonging to another account MUST answer not found on every endpoint that names one, and MUST be indistinguishable from an identifier that matches nothing. The same MUST hold for a vault already deleted.
- **FR-016**: A vault MUST be addressed by the unguessable identifier it already carries for this purpose, never by a sequential number, so that guessing the identifier next to somebody else's vault leads nowhere.
- **FR-017**: Every endpoint MUST refuse an unauthenticated caller, and MUST be held to the same rate limit as the rest of the authenticated API.
- **FR-018**: An invalid request MUST be refused as invalid, MUST name every field that is wrong, and MUST leave the account exactly as it was.
- **FR-019**: Creating, changing and deleting a vault through the API MUST be written to the account journal exactly as the same operation performed in the browser is, and MUST reuse the same behaviour rather than restating it, so that the two surfaces cannot come to disagree.
- **FR-020**: Reading a vault or the list of them MUST NOT itself be written to the account journal, matching how reading the journal is not written to the journal.
- **FR-021**: The endpoints MUST appear in the public API reference, as their own group, describing every field of a vault, every endpoint, the page parameters, the colours on offer, what each field accepts, the rate limit, the refusals and the not found behaviour, with an example response for each endpoint.
- **FR-022**: The reference MUST stay in step with the routes that exist, in the way the reference is already checked against the route table.

### Key Entities

- **Vault**: A watertight compartment of data. It belongs to one account, carries a name, what it is for, a colour, its place in the account's list, whether records land in it when nobody says which, a running count of the contacts it holds, and an unguessable identifier by which it is addressed. Contacts, invented records, files and private links belong to exactly one, and nothing reaches across.
- **The default vault**: The one vault per account records land in when nobody names one. It is a title exactly one vault holds while the account holds a vault at all, so granting it always takes it from somebody.
- **What a new contact starts from**: The published contact structure a new contact in a given vault begins on. One per vault, replaced rather than accumulated, and it never moves contacts already created.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: An account can be taken from holding no vault to holding a named, described, coloured vault that records land in by default, entirely through the API, without anybody opening a browser.
- **SC-002**: Everything the vault settings screen lets a person change about a vault is changeable through the API, field for field, and everything the screen shows about a vault is readable through it.
- **SC-003**: Deleting a vault through the API leaves the account in the same state as deleting the same vault in the browser, judged on what the vault held, which private links still open, and which vault records land in afterwards.
- **SC-004**: No caller can read, change or delete a vault belonging to another account, and a probe cannot tell a vault that exists elsewhere from one that does not exist.
- **SC-005**: An account always has exactly one default vault while it holds a vault, and never has one while it holds none, whichever order the endpoints are called in.
- **SC-006**: Every write reaching the API leaves the same trace in the account journal as the same write made in the browser, so a reader of the journal cannot tell which surface was used except where the journal says so.
- **SC-007**: Somebody who has never seen this API can call all five endpoints correctly using the reference alone, and every field they send or receive is described there.
- **SC-008**: The reference and the routes cannot drift apart without the test suite failing.

## Assumptions

- **Reading is in scope, and was confirmed as such.** The description named creation, change and deletion. Listing and reading one vault are included because the other three take an identifier that has to come from somewhere, and because every object the API already hands out carries a link to itself, which has to lead to a real endpoint.
- **The two account level decisions on the creation form are in scope, and were confirmed as such.** Whether the vault is the default, and which published contact structure new contacts start from, are both accepted on creation and on change, matching the browser form field for field. Each remains its own act, with its own journal entry.
- **A change replaces the wording rather than patching it.** Creating a vault may omit the colour and what the vault is for, since there is nothing yet to lose. Changing one may not: a caller who sent only a new name would reset the colour and erase the description, which is exactly the silent loss the product forbids, so the change endpoint makes the caller state all three. Partial change, if it is ever wanted, belongs in the operation itself so that both surfaces gain it at once.
- **On creation, the form requires a colour and the API does not.** The form always sends one because it offers a picker with a preselection. A caller has no picker, and the product already knows the colour a vault gets when nobody picks one, so requiring it at that moment would be a refusal with no purpose.
- **Ordering is the order the account arranged, not newest first.** A vault carries the place the user put it in, which the other lists in the API have no equivalent of. Answering in that order is what makes a listing match the switcher a person sees.
- **Reordering vaults is out of scope.** Moving a vault up or down the list is its own operation, and it gets its own endpoint in its own change.
- **Bringing a deleted vault back is out of scope.** The behaviour exists and the deletion is soft, so nothing is lost, but nothing in the product calls it yet, from any surface. Until it has an endpoint, a caller should treat deletion through the API as final.
- **The warning belongs to the interface, not to the API.** The product requires a destructive action to be confirmed before it is performed, and that obligation is the screen's. An API call is itself the confirmation, so deletion takes no second step, and the reference says plainly what goes with the vault.
- **A credential is not limited in what it may do.** Tokens whose reach can be narrowed do not exist yet, so any valid key can delete a vault as readily as read one. That is a known gap the product is carrying, it is not made worse or better here, and the reference should not imply a read only key is possible.
- **The logic already exists and is reused unchanged.** Creating, changing and deleting a vault, promoting a default and setting what contacts start from are each already a single named operation the browser path calls. The API adds transport and nothing else, which is what keeps the two surfaces in agreement.
- **The page size default and maximum are the ones the rest of the API uses**, read from configuration rather than restated, so all lists in the API move together.
