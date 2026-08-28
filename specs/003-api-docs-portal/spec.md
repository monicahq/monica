# Feature Specification: API foundations and the API docs portal

**Feature Branch**: `2026-08-27-api-doc-portal`

**Created**: 2026-08-27

**Status**: Draft

**Input**: User description: "add api methods to manage items in this image (sidebar of an API reference: Getting started with Introduction, Authentication, Rate limits, Pagination, Errors; Resources with Health, Authentication, Your profile). Mimic the beaver codebase for actions, routes and methods. Then implement the API doc itself matching these methods. The portal must be reachable at /docs/api, built from the `API portal.dc.html` design."

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Reach the account from outside the browser (Priority: P1)

Somebody who already has a Monica account wants to talk to it from a script, a phone app or another
service. They check that the service is up, sign in with their email and password, get a token back,
read their profile with that token, correct their name, and revoke the token when they are done.
Somebody without an account can create one the same way and get a token in the same response.

**Why this priority**: Nothing else in this feature has any value without it. The reference documents
these calls, so they have to exist and behave exactly as documented.

**Independent Test**: Can be tested entirely through the six calls, with no interface involved: check
status, register, log in, get profile, update profile, log out. It delivers programmatic access to an
account on its own.

**Acceptance Scenarios**:

1. **Given** the service is running, **When** anyone checks its status without a token, **Then** they
   are told it is up, along with the state of each service it depends on.
2. **Given** a valid email and password, **When** someone logs in, **Then** they receive a token that
   authenticates their later requests.
3. **Given** no account exists for an email address, **When** someone registers with it, **Then** an
   account is created exactly as it would be through the sign-up screen, and a token comes back.
4. **Given** a valid token, **When** the holder asks for their profile, **Then** they get their own
   user and nobody else's.
5. **Given** a valid token, **When** the holder submits a corrected name and email, **Then** the
   change is saved and the updated profile comes back.
6. **Given** a valid token, **When** the holder logs out, **Then** that token stops working while
   their other tokens keep working.
7. **Given** two-factor authentication is switched on for a user, **When** they log in without a
   valid code, **Then** no token is issued.

---

### User Story 2 - Find out what the API can do (Priority: P2)

A developer lands on the reference at `/docs/api`, sees every endpoint listed in a sidebar under
Getting started and Resources, reads what each one takes and returns, copies a ready made request in
the language they work in, and gets a key without leaving the page.

**Why this priority**: The API is unusable by anyone who did not write it until this exists, but the
API works without it.

**Independent Test**: Can be tested by opening `/docs/api` as a signed out visitor and checking that
every endpoint of User Story 1 appears with its verb, path, parameters, sample request and sample
response.

**Acceptance Scenarios**:

1. **Given** a visitor who is not signed in, **When** they open `/docs/api`, **Then** they land on
   the page the reference opens with, and every entry has a page of its own under it.
2. **Given** the reference is open, **When** the visitor looks at the sidebar, **Then** Getting
   started lists Introduction, Authentication, Rate limits, Pagination and Errors, and Resources
   lists Health, Authentication and Your profile with a verb next to each entry.
3. **Given** the visitor picks an entry in the sidebar, **When** they select it, **Then** that
   endpoint opens at an address of its own, which they can send to somebody else.
4. **Given** an endpoint is on screen, **When** the visitor switches the sample language, **Then**
   the same request is shown in the language they picked, and one action copies it.
5. **Given** the visitor types into the search box, **When** the text matches an endpoint, **Then**
   only matching entries stay listed.
6. **Given** an endpoint is documented, **When** the visitor compares it to the real API, **Then**
   the verb, path, parameters and response shape match exactly.

---

### User Story 3 - Hand the reference to a machine (Priority: P3)

A developer working with an assistant wants the reference as plain text rather than a web page, either
one endpoint at a time or the whole thing at once.

**Why this priority**: A convenience on top of a reference that already reads well in a browser.

**Independent Test**: Can be tested by requesting the plain text version of the whole reference and of
a single endpoint, and checking both describe the same endpoints as the page.

**Acceptance Scenarios**:

1. **Given** the reference exists, **When** someone requests it as plain text, **Then** every
   documented endpoint is present in a form an assistant can read.
2. **Given** an endpoint is on screen, **When** the visitor copies it for an assistant, **Then** that
   endpoint alone is copied as plain text.

---

### Edge Cases

- Logging in with a wrong password, a deactivated account, an account that signs in through an
  external provider, or an address nobody holds: all refused the same way, telling the caller nothing
  about which it was.
- Registering with an address already in use, or a throwaway address: refused with the reason named
  per field.
- Calling a protected endpoint with no token, an expired one, or one that was revoked: refused as
  unauthenticated.
- Exceeding the request limit: refused, with how long to wait before trying again.
- Updating a profile to an email address already held by somebody else: refused.
- Changing the email address: the address stops counting as confirmed until it is confirmed again.
- The reference and the API drifting apart: an endpoint that exists but is undocumented, or is
  documented but does not exist, must be caught before release rather than by a reader.
- Sending the request body in the wrong shape: refused, naming every field at fault at once.

## Requirements *(mandatory)*

### Functional Requirements

**API surface**

- **FR-001**: The system MUST expose a status check that needs no credentials and reports whether the
  service and its database are reachable.
- **FR-002**: The system MUST let anyone create an account from outside the browser, producing exactly
  the same account, first vault and starting data as the sign-up screen, and returning a token.
- **FR-003**: The system MUST let a user exchange their email and password for a token, and MUST
  refuse when the user is deactivated or signs in through an external provider.
- **FR-004**: The system MUST require a valid second factor before issuing a token to a user who has
  two-factor authentication switched on.
- **FR-005**: The system MUST let a token holder revoke the token they are using, leaving their other
  tokens untouched.
- **FR-006**: The system MUST let a token holder read the profile of the user the token belongs to.
- **FR-007**: The system MUST let a token holder update that user's name and email address, applying
  the same rules and the same consequences as the profile screen does.
- **FR-008**: The system MUST scope every authenticated call to the account of the token holder, and
  MUST answer as though a record does not exist when it belongs to another account.
- **FR-009**: The system MUST limit how often each endpoint can be called, with a tighter limit on
  registration and login than on the rest, and MUST tell a caller over the limit how long to wait.
- **FR-010**: The system MUST answer every API call with a machine readable body, including failures,
  and MUST name the field at fault for each validation failure.
- **FR-011**: The system MUST record account creation, sign-in and profile changes made through the
  API in the same activity log as the equivalent screens.

**Reference portal**

- **FR-012**: Visitors MUST be able to read the reference under `/docs/api` without signing in, with
  every entry at an address of its own, one endpoint to a page, so that a link opens what it names and
  the browser's own history walks between endpoints.
- **FR-013**: The reference MUST present a sidebar with a Getting started group covering
  Introduction, Authentication, Rate limits, Pagination and Errors, and a Resources group covering
  Health, Authentication and Your profile, each resource entry showing its verb.
- **FR-014**: Each documented endpoint MUST state its verb, path, whether it needs a token, every
  parameter with type and whether it is required, what it returns, and an example response.
- **FR-015**: Each documented endpoint MUST offer a ready to run sample request in more than one
  language, switchable in place and copyable in one action.
- **FR-016**: Visitors MUST be able to filter the sidebar by typing, and MUST be able to reach any
  endpoint from it, from any page of the reference.
- **FR-017**: Visitors MUST be able to get a key from the reference, being sent to where keys are
  issued.
- **FR-018**: The reference MUST be available as plain text, both as a whole and one endpoint at a
  time.
- **FR-019**: The reference MUST match the API exactly in both directions, and a mismatch MUST fail
  the build rather than reach a reader.
- **FR-020**: The reference MUST follow the supplied design, and MUST read correctly on a phone as
  well as on a desktop.

### Key Entities

- **User**: the person the token belongs to, carrying the name, email address and preferences already
  held today. Exactly one user per account.
- **Account**: everything the user owns. Every API call reads and writes within one account.
- **Token**: a credential that acts as one user, named after the device it was issued to, revocable,
  and shown in full only once when it is created.
- **Documentation group**: one sidebar heading, holding an ordered list of documented endpoints or
  guide pages.
- **Documented endpoint**: one entry in the reference, describing a real API call and its example
  request and response.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: A developer who has never seen the API can go from opening `/docs/api` to a successful
  authenticated call in under 10 minutes, using only what the page gives them.
- **SC-002**: All six calls of User Story 1 succeed against a fresh account without any manual step in
  the interface, other than obtaining credentials.
- **SC-003**: Every endpoint reachable in the API appears in the reference, and every endpoint in the
  reference exists in the API, verified automatically on every change.
- **SC-004**: Every documented example response matches the shape the API actually returns, verified
  automatically.
- **SC-005**: The reference loads and is readable on a 375 pixel wide screen with no sideways
  scrolling of the page.
- **SC-007**: Every entry in the sidebar resolves to a page of its own, and an address that names no
  entry is refused rather than showing something arbitrary.
- **SC-006**: Credentials sent more than the allowed number of times in a minute are refused, and the
  refusal states when to retry.

## Assumptions

- The endpoints in scope are exactly the eight sidebar entries in the supplied image: five guide pages
  and the Health, Authentication and Your profile resources. Everything else the product holds
  (vaults, contacts, templates, value lists) is out of scope for this feature, and the reference is
  built so those groups can be added later without rework.
- Updating a profile covers the fields the profile screen covers today: first name, last name and
  email address. Preferences (locale, timezone, clock, date format, name order) are updated by a
  different part of the product and are out of scope here.
- Registration asks for agreement with the terms, exactly as the sign-up screen does. A second way in
  that skipped them would be a way around them rather than a convenience. The bot challenge the screen
  carries is a browser mechanism with no meaning over the API, and the rate limit stands in its place.
- The Pagination guide page describes the convention list endpoints will follow, using an illustrative
  example. No list endpoint ships in this feature, and guide pages are not held to the endpoint
  matching rule of FR-019.
- Registration through the API is open to the same people it is open to through the sign-up screen,
  under the same instance settings. No separate switch is introduced.
- Tokens do not expire and are revoked rather than refreshed, matching how keys already work in the
  product.
- The reference is English only in this feature, like the rest of the public pages, even though the
  product is translated.
- Behaviour and vocabulary are mirrored from the beaver codebase, which already ships this exact
  surface, so that the two products stay recognisably the same to a developer who has used either.

## Dependencies

- The supplied Claude Design project, file `API portal.dc.html`, together with the Monica design
  system it imports, is the visual source for the portal.
- The beaver codebase, available in this session, is the behavioural reference for the endpoints, the
  reference builder and its tests.
- Personal access tokens already exist in the product and are reused. Nothing accepts them today, and
  this feature is what makes them useful.
