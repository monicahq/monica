# Feature Specification: Make a full contact partial again

**Feature Branch**: `2026-11-23-partial-contact-promotion`

**Created**: 2026-11-23

**Status**: Draft

**Input**: User description: "same branch. now i want to let complete contacts be changed to partial contacts. based on the design 2a and 2b. analyze what will be required to make it happen, and all the problems we could have with it - including state machines."

**Design**: `Contact partiel.dc.html` (claude.ai design project `33f0feac`), variant 2a, "Panneau dans la page d'édition", and variant 2b, "Confirmation". Variant 2a places a panel on the contact edit page, just above the existing delete section, drawn as a quiet panel rather than a red one because the change destroys nothing. Variant 2b is the confirmation dialog. The design's sample name (Theo) is illustrative. Its copy is a draft: two of its claims do not hold in the product today (see Edge Cases), and the copy that ships says only what is true.

**Related work on this branch**: the opposite move, making a partial contact full from a band on their page (design 1a), is already built on this branch. This feature completes the pair.

**Technical analysis**: [analysis.md](analysis.md) lists every place in the application that reads the partial state, the state machine of a contact, and the defects found while preparing this specification.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Make a full contact partial from the edit page (Priority: P1)

Somebody keeps a full contact for a person they only know through somebody else: a colleague's partner, a friend's child. That person sits in the list of people and takes a place in the free plan. On the contact's edit page, a panel above the delete section explains what being partial means and offers "Make a partial contact". A confirmation names the consequences. Once confirmed, the person leaves the list of people, stops counting towards the plan, and keeps everything written about them.

**Why this priority**: it is the whole feature. Without it, a contact created full by mistake can only be deleted or kept.

**Independent Test**: open the edit page of a full contact, confirm, and check that the contact is no longer in the list of people, is in the partial grouping, no longer counts towards the plan, and still holds every value, life event, relationship, reminder and note it held before.

**Acceptance Scenarios**:

1. **Given** a full contact, **When** the person opens the edit page, **Then** a panel above the delete section states what changes and offers "Make a partial contact".
2. **Given** that panel, **When** the person presses the button, **Then** a confirmation asks "Make :name a partial contact?" and states the consequences before anything changes.
3. **Given** the confirmation, **When** the person confirms, **Then** the contact is partial, the person lands on the contact's page, which shows the partial band built on this branch, and a message confirms the change.
4. **Given** the confirmation, **When** the person cancels, **Then** nothing changes.
5. **Given** a free account at its contact ceiling, **When** a full contact is made partial, **Then** the account is one place below the ceiling.

---

### User Story 2 - Undo it at any time (Priority: P1)

The person changes their mind. The partial contact's page offers "Make a full contact" (already built on this branch), and the contact comes back exactly as it was, within the plan's ceiling.

**Why this priority**: the panel promises "You can make them a full contact again at any time". The promise is only true if the way back always exists.

**Independent Test**: make a contact partial, then full again, and compare everything the contact held before and after: nothing differs except the dates of the two changes.

**Acceptance Scenarios**:

1. **Given** a contact made partial, **When** it is made full again, **Then** every value, relationship, life event, reminder, group membership, share link and favourite mark is as it was.
2. **Given** a free account that filled its last place after a contact was made partial, **When** the person tries to make that contact full again, **Then** the product refuses with the plan's own wording, and the contact stays partial with nothing lost.

---

### User Story 3 - The same through the API and an assistant (Priority: P2)

A client of the API, or an assistant connected through the MCP server, can make a full contact partial and a partial contact full, with the same rules and the same refusals as the screen.

**Why this priority**: the constitution requires every action of the interface to exist in the API. It is second because nobody is blocked on the screen without it.

**Independent Test**: call the endpoint for both directions on a contact and check the answers, the refusals, and that the contact's resource reports the new state.

**Acceptance Scenarios**:

1. **Given** a full contact, **When** the API is asked to make it partial, **Then** it answers with the contact, now partial.
2. **Given** the contact representing the account holder, **When** the API is asked to make it partial, **Then** it refuses with the same wording as the screen.
3. **Given** a free account at its ceiling, **When** the API is asked to make a partial contact full, **Then** it refuses with the plan's wording.

---

### User Story 4 - The journal says what happened (Priority: P3)

The account's journal records each change of state, so the person can see when a contact was made partial or full again.

**Why this priority**: nothing is lost by the change, so the record is a convenience, but every action the person takes is journalled today.

**Independent Test**: make a contact partial, then full, and find both lines in the journal.

**Acceptance Scenarios**:

1. **Given** a contact made partial, **When** the person reads the journal, **Then** it says ":name was made a partial contact" (wording final in the plan).

### Edge Cases

- **The contact representing the account holder.** It can never be partial. The panel is drawn but refused, with its reason, as the delete section already does for that contact.
- **A contact already partial.** The panel is absent from the edit page, since the page's band already offers the way back. A request to make it partial again is refused without changing anything.
- **A contact that no relationship reaches.** Today a partial contact is only reached through the "Names only" grouping of the list of people, through search, or through its address: the relationship blocks, the family tree and the timeline show a partial contact's name without a link (see analysis, A3). A contact with no relationship, made partial, is reachable only through that grouping and search. Decided: the change is allowed, and the confirmation warns that the contact will then be found only in the "Names only" grouping and in search.
- **Phones and calendars.** A partial contact is removed from the address books synchronised to phones, and its birthday leaves the synchronised birthday calendar. Making a contact partial therefore removes it from the person's phone. The constitution forbids anything disappearing silently, so the confirmation MUST say so.
- **Reminders.** A partial contact's reminders keep firing today, and nothing in this feature changes that. The copy MUST NOT claim otherwise, as design 1c did ("No reminders, even for the birthday").
- **Search.** Design 2b says the contact "will no longer appear in People or in search". Today a partial contact does appear in search. Decided: it stays in search, which remains a way to reach its page and make it full again, and the copy drops the claim.
- **The plan's ceiling as a way around itself.** A partial contact keeps a full page: values, notes, life events, reminders, files. It does not count towards the free plan. Once full contacts can be made partial at will, a free account can hold any number of fully used records by making them partial, and it reaches them through the "Names only" grouping. Decided: this is accepted. The same is already possible by adding people through relationships, and making a partial contact full again stays under the ceiling. The decision is recorded so that it is not mistaken for an oversight.
- **A merged contact.** Undoing a merge puts back the partial state the kept contact had before the merge, whatever happened since (see analysis, S2). If the person made the kept contact partial or full between the merge and its undo, the undo overrides that choice. This is accepted and recorded as a known limit. The undo's screen does not say it.
- **A favourite.** The favourite mark is kept but has no effect while the contact is partial, since the favourites grouping holds only full contacts. It applies again when the contact is made full.
- **Concurrent requests.** Two requests changing the same contact's state, or one making a contact full while another fills the last place of the plan, MUST NOT leave the account above its ceiling.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The edit page of a full contact MUST show a panel, placed just above the delete section, that names what making the contact partial changes and offers "Make a partial contact". The panel is not drawn in the danger tint.
- **FR-002**: Pressing "Make a partial contact" MUST open a confirmation before anything changes. The confirmation MUST name every consequence the person would not otherwise notice: leaving the list of people, no longer counting towards the plan, leaving synchronised phones and the birthday calendar, and that everything written stays.
- **FR-003**: Confirming MUST make the contact partial and nothing else: no value, relationship, life event, reminder, group membership, share link, file, favourite mark or note is changed or removed.
- **FR-004**: The contact representing the account holder MUST NOT be made partial. The panel MUST stay on the page, marked as refused, with its reason.
- **FR-005**: A contact already partial MUST NOT be offered the panel, and a request to make it partial MUST be refused without changing anything.
- **FR-006**: A partial contact MUST be made full again through the band already built on this branch, under the plan's ceiling, with the plan's own refusal wording.
- **FR-007**: The change MUST be recorded in the account's journal, in both directions.
- **FR-008**: The API MUST offer both directions, with the same rules and refusals as the screen, and the MCP server MUST offer a tool mirroring each endpoint.
- **FR-009**: The copy MUST state only what the product does. In particular it MUST NOT promise that reminders stop, or that the contact leaves search.
- **FR-010**: A partial contact MUST stay in search results, as it does today.
- **FR-011**: A contact that no relationship reaches MUST be allowed to become partial, and the confirmation MUST then add that it will be found only in the "Names only" grouping of the list of people and in search.
- **FR-012**: Partial contacts MUST keep counting for nothing towards the plan's ceiling, and making a contact partial MUST be possible whatever the account's count.
- **FR-013**: Every path that turns a partial contact full MUST respect the plan's ceiling. This includes merging two partial contacts, which today produces a full contact without asking the plan (see analysis, D1).
- **FR-014**: A change of state MUST be safe against concurrent requests on the same account: the account MUST NOT end above its ceiling.
- **FR-015**: Every new string MUST be translated in every shipped locale, and the What's new changelog MUST carry an entry.

### Key Entities

- **Contact**: a person in a vault. Relevant states: full (listed, counted towards the plan), partial (off the main lists, not counted), the contact representing the account holder (always full), archived, deleted, absorbed by a merge.
- **Journal entry**: a line of the account's journal recording that a contact was made partial or full.
- **Merge**: records what a merge changed on the kept contact, including whether it cleared the partial state, so that the merge can be undone.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: A person can make a full contact partial in at most three actions from the contact's page (open the edit page, press the button, confirm).
- **SC-002**: Making a contact partial and then full again leaves every piece of information it held identical, in 100% of cases.
- **SC-003**: After making a contact partial, a free account's count of contacts goes down by exactly one, and after making it full again, up by exactly one.
- **SC-004**: No statement in the panel, the confirmation or the messages is contradicted by what the product does afterwards (reminders, search, phones, calendars, plan).
- **SC-005**: No sequence of making partial, making full and merging can leave a free account above its contact ceiling. Undoing a merge is the one exception: it puts back the absorbed contact without asking the plan, since refusing an undo would trap data (research R4).

## Clarifications

### Session 2026-11-23

- Q: A partial contact keeps every capability and does not count towards the free plan; may making contacts partial be used to stay under the ceiling? → A: Accepted. Partial contacts stay outside the ceiling; making one full again stays under it.
- Q: May a contact that no relationship reaches be made partial? → A: Yes, and the confirmation warns where it will be found afterwards.
- Q: Does a partial contact leave search, as design 2b says? → A: No. It stays in search, and the copy drops the claim.

## Assumptions

- The edit page meant by design 2a is the existing contact edit page, the one ending with "Save the name" and the delete section.
- The confirmation reuses the confirmation dialog the application already draws, not a new one.
- Reminders of a partial contact keep firing, as they do today. Changing that is a separate decision.
- The vault's own count of contacts keeps including partial contacts, as it does today, so making a contact partial does not change it.
- The partial band, its promotion action and its web route built earlier on this branch are kept and form half of this feature. Its API endpoint is still to build (FR-008).
- A partial contact keeps its address, its slug and its share links. Somebody holding a share link still reaches it. Not confirmed in the code: nothing found that filters share links on the partial state.
- An archived contact is not offered the change, since no screen reaches it.
