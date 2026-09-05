# Feature Specification: Reminder endpoints

**Feature Branch**: `2026-09-04-reminders-api`

**Created**: 2026-09-04

**Status**: Draft

**Input**: User description: "i want the api to manage reminders (list and all the actions we've written on it). of course i want the docs oto."

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Read what is coming up (Priority: P1)

A reminder is the one thing in the product that reaches out on its own, so a caller has to be able to ask what is due, what it is about, and what has already been sent. That is what a native application's home screen shows, and what any tool that wants to put reminders somewhere else has to read first.

**Why this priority**: Every other story names a rule or a deadline, and only this one hands over an identifier. It also stands alone: a caller who has nothing but this can already report the whole reminder state of an account.

**Independent Test**: List the reminders of a vault and the deadlines they produce on an account holding several, and confirm each rule and each deadline comes back with its identifier, its wording, when it is due, what it is about, and whether it is silenced.

**Acceptance Scenarios**:

1. **Given** a caller holding a valid credential, **When** they list the rules of one of their vaults, **Then** each rule carries its identifier, its wording, its cadence, its interval, its moments, its time of day, its frozen zone, whether it is active, why it is not when it is not, the record and the field and the date it reads, and a link to itself.
2. **Given** a caller listing the deadlines of a vault, **When** the answer comes back, **Then** each deadline carries its identifier, the rule it came from, the instant it is due, the local day it is due on, its state, how many times it has been put off, when it was sent if it was, and a link to itself.
3. **Given** deadlines both ahead and behind, **When** the caller asks for the deadlines already sent, **Then** they get those and only those, matching the history the reminders screen shows.
4. **Given** a vault holding no reminder at all, **When** the caller asks, **Then** they get an empty list rather than an error.
5. **Given** more rules or deadlines than one page holds, **When** the caller asks, **Then** they get one page, how many there are in all, and the means to ask for the next, with a page size outside the allowed range brought back into it.
6. **Given** a rule or a deadline in another account, or in another vault of the same account, **When** the caller asks for it by its identifier, **Then** the answer is not found, and nothing about it is disclosed.
7. **Given** a rule silenced because the contact was archived or the field left the structure, **When** the caller reads it, **Then** it is there, marked inactive, and says which of those happened.

---

### User Story 2 - Deal with a deadline that has come due (Priority: P2)

When a reminder arrives, there are two things a person does with it: put it off until later, or say they are done with it. Both are single gestures on the reminders screen, and a caller who can read a deadline but never close it is left with a list that only grows.

**Why this priority**: It is the pair of gestures somebody performs every day, and the reason to read the list at all. It needs a deadline to act on, which the first story supplies.

**Independent Test**: Put one deadline off until a chosen instant and dismiss another, and confirm the first moved, its count went up and its original instant was kept, the second closed, and that neither touched the rule behind them or their sibling deadlines.

**Acceptance Scenarios**:

1. **Given** a deadline still open, **When** the caller puts it off until a later instant, **Then** it is due then, the instant it was first generated for is kept, the count of times it has been put off goes up, and the answer describes it as it now stands.
2. **Given** a caller who names one of the delays the product offers rather than an instant, **When** the request is answered, **Then** the delay is resolved against the rule's own frozen zone, not the caller's.
3. **Given** a deadline still open, **When** the caller dismisses it, **Then** it is closed, any delivery still queued for it is abandoned, and deliveries already sent are untouched.
4. **Given** a deadline the caller puts off, **When** it moves, **Then** the sibling deadline of the same cycle, every later cycle, the rule itself and the date on the record are all left exactly as they were.
5. **Given** a deadline already dismissed, already sent and closed, or cancelled, **When** the caller tries to put it off or dismiss it, **Then** the request is refused as invalid and says the deadline has already been dealt with.
6. **Given** a caller who asks to put a deadline off until an instant already past, **When** the request is answered, **Then** it is refused as invalid and the deadline is left where it was.
7. **Given** either gesture performed through the API, **When** somebody reads the account journal, **Then** it is written there exactly as the same gesture made in the browser is.

---

### User Story 3 - Ask to be reminded, change it, stop (Priority: P3)

A reminder hangs off a date somebody wrote on a record: a birthday, an anniversary, a renewal. Asking to be reminded of one, correcting when the reminder fires or what it says, and no longer wanting it are the three things the product lets a person do to a rule, and a caller who cannot do them has to open a browser for the whole setup.

**Why this priority**: It is what makes the API able to build reminders rather than only read them, and it is the last piece of the parity the product promises. It is below the first two because a rule created in the browser is already usable through them.

**Independent Test**: Create a rule on a date value, change its cadence and its wording, then remove it, and confirm the deadlines it produces appear, are rebuilt when the schedule moves, survive a rename, and go quiet when the rule is removed, with the date on the record untouched throughout.

**Acceptance Scenarios**:

1. **Given** a caller naming a date value on a record of theirs, a cadence, its moments and a time of day, **When** they create the rule, **Then** it exists, the deadlines it produces inside the rolling window are written at once, and the answer describes it.
2. **Given** a caller who supplies no wording, **When** the rule is created, **Then** it is given the wording built from the field's label and the record's name, rather than the request being refused.
3. **Given** a caller who supplies no zone, **When** the rule is created, **Then** it freezes the caller's own zone, and changing that preference afterwards never moves the rule.
4. **Given** a date value that already carries a live rule, **When** the caller creates a second, **Then** it is refused as invalid, because a date carries one rule.
5. **Given** a field whose kind holds no date, or a value holding no date, or moments that say nothing, or an interval outside what is allowed, or a time of day that is not one, or a zone the product does not know, **When** the request is answered, **Then** it is refused as invalid and says what is wrong.
6. **Given** a caller who changes a rule's cadence, interval, moments, time of day or zone, **When** the change is made, **Then** the deadlines still ahead are taken away as changed by the rule and the ones the new schedule defines are written, while the deadlines behind are left as the record of what was actually sent.
7. **Given** a caller who changes nothing but the wording, **When** the change is made, **Then** no deadline is taken away and none is rebuilt, so a deadline already put off survives the rename.
8. **Given** a caller who leaves the zone out of a change, **When** the change is made, **Then** the frozen zone stays exactly as it was.
9. **Given** a caller who removes a rule, **When** the request is answered, **Then** the rule is gone from the lists, the deadlines ahead of it go quiet saying the rule was switched off, the ones behind stay, and the date on the record is untouched.
10. **Given** a rule or a date value in another account or another vault, **When** the caller names it, **Then** the answer is not found and nothing is written.

---

### User Story 4 - Prove a reminder will actually arrive (Priority: P4)

Whether a reminder reaches somebody is only answerable by waiting for the date, unless they can ask for one now. The reminders screen offers exactly that, and it is the one reminder gesture with no effect on any data.

**Why this priority**: It is a convenience rather than a capability, and everything else works without it. It is also the cheapest to add, since it changes nothing.

**Independent Test**: Ask for a test of one deadline and confirm the message arrives with that deadline's own wording, and that the deadline's state, its due instant and the history of what was sent are all unchanged.

**Acceptance Scenarios**:

1. **Given** a deadline of the caller's, **When** they ask for a test, **Then** the message is sent to them at once, carrying that deadline's own wording, in their own language.
2. **Given** a test has been sent, **When** the caller reads the deadline and the history of what was sent, **Then** neither mentions it, and the real delivery still happens on the day.
3. **Given** the hours in which the product does not interrupt anybody, **When** the caller asks for a test inside them, **Then** it is sent anyway, because they asked for this one.
4. **Given** a deadline whose rule has been removed, or one in another account, **When** the caller asks for a test, **Then** the answer is not found and nothing is sent.

---

### Edge Cases

- A deadline is put off and dismissed by two requests arriving together. The second finds it already dealt with and is refused, rather than reporting a second closure.
- A caller puts a deadline off past the end of the rolling window the product generates within. The deadline stands where it was put, because a deadline somebody moved is not regenerated.
- A rule is silenced because its contact was archived. Its deadlines are quiet, none of them can be put off or dismissed, and the rule still says why.
- A wording arrives carrying markup or surrounding blank space. It is reduced to plain text before it is written, exactly as the browser path reduces it.
- A cadence that does not come back is given an interval anyway. The interval is stored as one, because it means nothing for a rule that fires once.
- A rule is removed and then named again by the identifier the caller still holds. It answers not found, indistinguishable from an identifier that never named anything.
- The zone frozen on a rule has since stopped existing in the product's catalogue. The rule is still readable and its deadlines still fire, because a preference changed later may not break a reminder already set.
- A caller pages through the deadlines while the nightly sweep writes more. The order is by the instant each is due, so a deadline is not skipped by a page boundary moving under it.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The API MUST offer a way to list the reminder rules of one of the caller's vaults, ordered so that the same request twice returns the same order.
- **FR-002**: Each rule the API describes MUST carry its identifier, its wording, its cadence, its interval, its moments, its time of day, its frozen zone, whether it is active, why it is not when it is not, the record it is about, the field and the date value it reads, when it was created and last changed, and a link to itself.
- **FR-003**: The API MUST offer a way to read one rule of the caller's by its identifier, described exactly as the list describes it.
- **FR-004**: The API MUST offer a way to list the deadlines one of the caller's vaults holds, ordered by the instant each is due, and MUST let a caller ask for the ones still ahead and the ones already sent separately.
- **FR-005**: Each deadline the API describes MUST carry its identifier, the rule it came from, the instant it is due, the local day it falls on, its state, the instant it was first generated for, how many times it has been put off, when it was sent if it was, and a link to itself.
- **FR-006**: The API MUST offer a way to read one deadline of the caller's by its identifier, described exactly as the list describes it.
- **FR-007**: Every list MUST be answered a page at a time, saying which page it is, how many one page holds and how many there are in all, using the same default and maximum page size as the rest of the API and bringing a size outside the range back into it.
- **FR-008**: The API MUST offer a way to put one deadline off until a later instant, accepting either an instant or one of the delays the product already offers, and MUST resolve a named delay against the rule's frozen zone rather than the caller's.
- **FR-009**: Putting a deadline off MUST keep the instant it was first generated for, MUST raise the count of times it has been put off, and MUST leave its siblings, later cycles, the rule and the date on the record untouched.
- **FR-010**: The API MUST offer a way to dismiss one deadline, which MUST close it, abandon any delivery still queued for it, leave deliveries already sent alone, and leave the rule producing.
- **FR-011**: Putting off or dismissing a deadline already dealt with MUST be refused as invalid and say so, and putting one off until an instant already past MUST be refused as invalid.
- **FR-012**: The API MUST offer a way to create a rule on a date value of the caller's, taking the value, a cadence, its moments and a time of day, and optionally a wording, an interval and a zone.
- **FR-013**: Creation MUST fill an absent wording from the field's label and the record's name, MUST freeze an absent zone from the caller's own, and MUST write at once the deadlines the rule produces inside the rolling window.
- **FR-014**: Creation MUST refuse a second live rule on a date value that already carries one, a field whose kind holds no date, a value holding no date, moments that say nothing, an interval outside what is allowed, a time of day that is not one, and a zone the product does not know.
- **FR-015**: The API MUST offer a way to change a rule's wording, cadence, interval, moments and time of day, and MUST NOT let it change which value, field or record the rule reads.
- **FR-016**: A change that moves anything a deadline is computed from MUST take the deadlines still ahead away as changed by the rule and write the ones the new schedule defines, MUST leave the deadlines behind untouched, and MUST NOT bring a taken-away deadline back.
- **FR-017**: A change that moves only the wording MUST take no deadline away and rebuild none.
- **FR-018**: A change that does not mention the zone MUST leave the frozen zone exactly as it was.
- **FR-019**: The API MUST offer a way to remove a rule, which MUST take the deadlines ahead of it quiet as switched off, leave the deadlines behind, leave the date on the record untouched, and keep the rule restorable rather than destroying it.
- **FR-020**: The API MUST offer a way to send the caller one deadline's reminder at once as a test, which MUST use that deadline's own wording, MUST NOT touch the deadline or the history of what was sent, and MUST ignore the hours in which the product does not otherwise interrupt anybody.
- **FR-021**: A rule, a deadline or a date value belonging to another account, or to another vault than the one named in the address, MUST answer not found on every endpoint, indistinguishable from an identifier that matches nothing.
- **FR-022**: A rule and a deadline MUST each be addressed by the unguessable identifier it already carries, never by a sequential number.
- **FR-023**: Every endpoint MUST refuse an unauthenticated caller and MUST be held to the same rate limit as the rest of the authenticated API.
- **FR-024**: An invalid request MUST be refused as invalid, MUST name every field that is wrong, and MUST leave the account exactly as it was.
- **FR-025**: Every write reaching the API MUST reuse the same operation the browser path calls, so the two surfaces cannot come to disagree, and MUST leave the same trace in the account journal.
- **FR-026**: Reading a rule or a deadline MUST NOT itself be written to the account journal.
- **FR-027**: The endpoints MUST appear in the public API reference as their own group, describing every field of a rule and of a deadline, every endpoint, the page parameters, the cadences, the moments, the delays on offer, the states a deadline can be in, the reasons a rule can be silent, the rate limit, the refusals and the not found behaviour, with an example response for each endpoint.
- **FR-028**: The reference MUST stay in step with the routes that exist, in the way the reference is already checked against the route table.
- **FR-029**: A caller MUST be able to discover the date value to hang a new rule on through the API alone, by [NEEDS CLARIFICATION: no contact, record or field value is reachable through the API today, so a date value's identifier has no source. Ship rule writing anyway and accept that it is only usable once records are exposed, add a read-only listing of a vault's reminder-eligible date values here, or leave rule writing out until the records API arrives?]
- **FR-030**: The lists MUST be scoped [NEEDS CLARIFICATION: the reminders screen is per vault, and a rule belongs to one vault. Should the API offer only per-vault lists, or also an account-wide list of what is coming up across every vault, which is what a phone's home screen and quick capture would read?]

### Key Entities

- **Reminder rule**: The standing request to be reminded of a date somebody wrote on a record. It belongs to one account and one vault, reads exactly one date value through exactly one field of exactly one record, carries a wording, a cadence, an interval, the moments in the cycle it fires at, a time of day and the zone frozen when it was made, and is either active or silent with a reason. At most one live rule per date value.
- **Deadline**: One instant a rule produced, which falls due, may be put off, may be dismissed, and is either still pending, sent, put off, dismissed or cancelled. It keeps the instant it was first generated for and how many times it has been moved. Its times are local rather than absolute, and the deadlines behind a rule are the record of what was actually sent.
- **Delivery**: One attempt to reach the person for one deadline. Written as mail leaves, never edited, and the history of them is what says a reminder arrived.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Everything the reminders screens of a vault show is readable through the API, field for field, including why a silent rule is silent.
- **SC-002**: Every gesture those screens offer, putting a deadline off, dismissing it and asking for a test, is performable through the API and leaves the account in the same state as the same gesture made in the browser.
- **SC-003**: A reminder can be set up, corrected and given up entirely through the API, without anybody opening a browser, and the date on the record is never altered by any of it.
- **SC-004**: Changing a rule's schedule through the API leaves exactly the deadlines the new schedule defines ahead of it and exactly the deadlines already sent behind it, judged the same way the browser path is judged.
- **SC-005**: No caller can read or act on a rule or a deadline belonging to another account or to another vault, and a probe cannot tell one that exists elsewhere from one that does not exist.
- **SC-006**: A rule's frozen zone never moves except when a caller moves it, whatever else changes about the account or the caller afterwards.
- **SC-007**: Somebody who has never seen this API can call every endpoint correctly using the reference alone, and every field they send or receive is described there.
- **SC-008**: The reference and the routes cannot drift apart without the test suite failing.

## Assumptions

- **Silencing and reviving a rule are out of scope as endpoints.** They are consequences the product draws when something else happens, archiving a contact, detaching a field, clearing a date, and never a gesture somebody performs. Exposing them would let a caller assert a cause that did not occur. The state and the reason are readable.
- **The generation, cancellation, restoration and delivery of deadlines are out of scope as endpoints.** They are the engine behind the rule, driven by the nightly sweep and by the operations above, and a caller who could invoke them could produce deadlines no rule defines.
- **A change to a rule replaces its schedule rather than patching it.** A caller states the cadence, the moments and the time of day every time, so a request that mentioned only the wording cannot silently reset the schedule. The zone is the exception, because leaving it out is what keeps it frozen.
- **Correcting the date is not a change to the rule.** The rule goes on reading the same value and simply reads a different day out of it, so moving a birthday is a write to the record and belongs to the records API.
- **The rolling window is not a parameter.** How far ahead deadlines are generated is a property of the product, read from configuration, and a caller neither sets it nor is told a different one.
- **Restoring a removed rule is out of scope.** The removal is soft and nothing is lost, but nothing in the product calls the restoration yet from any surface, so a caller should treat removal through the API as final.
- **The confirmation of a destructive gesture belongs to the interface.** An API call is itself the confirmation, so removing a rule takes no second step, and the reference says plainly what goes quiet with it.
- **A credential is not limited in what it may do.** Tokens whose reach can be narrowed do not exist yet, so any valid key can remove a rule as readily as read one. That gap is neither made better nor worse here, and the reference must not imply a read-only key is possible.
- **The page size default and maximum are the ones the rest of the API uses**, read from configuration rather than restated.
