# Feature Specification: Sent emails endpoints

**Feature Branch**: `2026-08-29-sent-emails-api`

**Created**: 2026-08-29

**Status**: Draft

**Input**: User description: "i want you to add api methods to get sent emails, as shown in http://127.0.0.1:8000/settings/account/logs. add proper api documentation too."

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Read the record of emails we sent, from a script (Priority: P1)

Somebody who never received the sign-in link we say we sent wants to find out what happened without opening the browser. The screen under the account logs already answers this: it lists every email that left, newest first, and says of each one whether it arrived, is still on its way, or came back. That answer is only reachable by a person clicking, so a script that watches for a bounce, or a support tool that answers "did it leave our hands", cannot get at it.

**Why this priority**: It is the whole of the feature. Everything the screen shows is a list, so the list is the minimum that delivers the value, and a caller who has only this can already tell that an email bounced.

**Independent Test**: Call the endpoint that lists sent emails with a valid credential on an account that has some, and confirm the answer carries every email, newest first, each with its subject, its recipient address and its delivery state.

1. **Given** a caller holding a valid credential and an account with more emails than one page holds, **When** they ask for the list of sent emails, **Then** they get one page of emails, newest sent first, together with how many there are in all and the means to ask for the next page.
2. **Given** an account with an email that has been delivered, one still on its way, and one that came back, **When** the caller lists the emails, **Then** each one says which of the three it is, and carries the moments it was sent, delivered and bounced, empty where it has not happened.
3. **Given** a caller whose account has never had an email leave, **When** they ask for the list, **Then** they get an empty list rather than an error.
4. **Given** a caller who asks for a page size outside what is allowed, **When** the request is answered, **Then** the size is brought back into the allowed range rather than the request being refused.
5. **Given** a caller with no credential, or one whose credential has been revoked, **When** they ask for the list, **Then** the request is refused as unauthenticated and no email is disclosed.

---

### User Story 2 - Read one email, including the copy that left (Priority: P2)

The screen lets somebody open a row to read what the email actually said, rather than only that it was sent. Somebody debugging a template, or answering "what exactly did we tell them", needs that same copy from a script.

**Why this priority**: It depends on the list to be worth anything, since the list is where an identifier comes from, and the list already answers the more common question. It is the second half of what the screen offers, so leaving it out would leave the API behind the browser.

**Independent Test**: Take an identifier from the list, ask for that one email, and confirm the answer carries the copy of the email as well as everything the list row carried.

1. **Given** a caller holding a valid credential, **When** they ask for one of their sent emails by its identifier, **Then** they get its type, subject, recipient address, delivery state, timestamps, and the copy that was sent.
2. **Given** an email belonging to another account, **When** the caller asks for it by its identifier, **Then** the answer is not found, and nothing about that email is disclosed.
3. **Given** an identifier that matches no email at all, **When** the caller asks for it, **Then** the answer is not found, told apart from an email that exists elsewhere by nothing at all.
4. **Given** a caller who tries to create, change or remove a sent email, **When** they attempt it, **Then** no such operation exists, because the record is written by the application as mail leaves and is never edited.

---

### Edge Cases

- An email left before we knew whether it arrived. It is neither delivered nor bounced, and reads as still on its way rather than as an error or an empty state.
- An email both bounced and had been marked delivered, which a provider can report out of order. Bounced wins, exactly as the screen decides it, so a program and a person reading the same record reach the same conclusion.
- An email carries no subject, or no copy, because the record predates our keeping them. The field is empty rather than the email being left out of the list.
- An email was sent to an address that belongs to nobody with an account. It is still on the account and still listed, since the account is what it was sent on behalf of.
- The list is asked for a page size of zero, a negative number, or a number far above the maximum. The size is clamped, matching how the journal already behaves.
- The copy of an email is large. It is left out of the list rows and carried only when one email is asked for, so that listing a page never turns into a download.
- An account has emails that were sent months apart. Ordering is by when the email was sent, so an email whose record was written late does not jump the queue.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The API MUST offer a way to list the emails sent on behalf of the caller's account, most recently sent first.
- **FR-002**: Each listed email MUST carry its identifier, its type, its subject, the address it went to, its delivery state, the moments it was sent, delivered and bounced, and a link to itself.
- **FR-003**: The delivery state MUST be a single value naming one of three states, bounced, delivered, or still on its way, decided the same way the account logs screen decides it, with bounced taking precedence over delivered.
- **FR-004**: The list MUST be answered a page at a time, and MUST say which page it is, how many one page holds, and how many emails there are in all.
- **FR-005**: The list MUST accept a requested page size, MUST bring a size outside the allowed range back into it rather than refusing the request, and MUST use the same default and maximum as the rest of the API.
- **FR-006**: The API MUST offer a way to read one sent email of the caller's account by its identifier.
- **FR-007**: The single email MUST carry everything a listed email carries, and additionally the copy that was sent.
- **FR-008**: The copy of an email MUST NOT be carried in the list rows.
- **FR-009**: An email belonging to another account MUST answer not found, and MUST be indistinguishable from an identifier that matches nothing.
- **FR-010**: Both endpoints MUST refuse an unauthenticated caller, and MUST be held to the same rate limit as the rest of the authenticated API.
- **FR-011**: The API MUST offer no way to create, change or delete a sent email, because the record is written as mail leaves and a record that can be rewritten is worth nothing.
- **FR-012**: The endpoints MUST appear in the public API reference, as their own group, describing every field, both endpoints, the page parameters, the three delivery states, the rate limit and the not found behaviour, with an example response for each endpoint.
- **FR-013**: The reference MUST stay in step with the routes that exist, in the way the reference is already checked against the route table.
- **FR-014**: Reading a sent email MUST NOT itself be written to the account journal, matching how reading the journal is not written to the journal.

### Key Entities

- **Sent email**: One email that left the system on behalf of an account. It carries which account it was sent on behalf of, the person it was sent to when they have an account, its type, the address it went to, its subject, the copy that was sent, and the moments it was sent, delivered and bounced. It is written when mail leaves and updated only by delivery reports.
- **Delivery state**: The one word a reader wants out of the three timestamps. Not stored, derived, and derived identically for the screen and for the API.

## Success Criteria *(mandatory)*

- **SC-001**: Everything the sent emails screen shows a person is reachable through the API, field for field, so nobody has to open a browser to answer a question about an email that left.
- **SC-002**: A caller can tell whether a given email was delivered, bounced, or is still on its way, from one value, without interpreting timestamps themselves.
- **SC-003**: A caller can page through an account's entire history of sent emails, in a stable order, without any email appearing twice or being skipped.
- **SC-004**: No caller can read an email belonging to another account, and a probe cannot tell an email that exists elsewhere from one that does not exist.
- **SC-005**: Listing a page of emails returns an answer whose size does not grow with the length of the copy of the emails on it.
- **SC-006**: Somebody who has never seen this API can call both endpoints correctly using the reference alone, and every field they receive is described there.
- **SC-007**: The reference and the routes cannot drift apart without the test suite failing.

## Assumptions

- **Scope is the account, not the person.** The screen lists the emails sent to the person signed in; the API scopes to their account, which is how the journal endpoints already behave. An account holds exactly one person, so the two sets differ only by emails sent on behalf of the account to an address belonging to nobody, which are the caller's own either way.
- **The copy is safe to hand out.** The screen already shows it to the same person, and the API answers only with a credential that person minted, so exposing it grants nobody anything they did not have.
- **Ordering is by when the email was sent**, matching the screen, rather than by the identifier the journal endpoints order by.
- **Paging is the shape the rest of the API uses**, offset based with a page number and a total, rather than the cursor the screen uses. The screen's list grows in place and only ever needs "the next page"; a caller wants to know how many there are.
- **The default and maximum page size are the ones already configured for the API**, rather than the ten the screen shows.
- **Filtering is out of scope.** Neither by type, nor by delivery state, nor by date. The screen offers none, and a caller can filter a page themselves. Adding it later takes nothing away from anybody.
- **Timestamps are given the way the rest of the API gives them**, so a caller parses one kind of date across every endpoint.
- **This is the version one API**, added beside the endpoints already there, under the same authentication, the same locale handling and the same rate limit.

## Out of Scope

- Any way to write, edit, resend or delete a sent email.
- Filtering, searching or sorting the list by anything.
- Exposing the emails of one account to another, including to an instance administrator, whose own space is a separate surface with its own rules.
- Changing the existing screen under `/settings/account/logs`, which keeps its cursor paging and its ten rows.
- Webhooks or any push notification when an email bounces.
