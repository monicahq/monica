# Feature Specification: Preview list and admission while registration is closed

**Feature Branch**: `claude/preview-register-landing-0d2a56` | **Created**: 2026-11-14 | **Status**: Draft

**Input**: "Add a "Preview register" page as default landing page. People can enter their email address to enter the preview list. Admins can admit them to register even if it is not available for everyone."

**Supersedes**: in `specs/071-closed-registration/spec.md`, FR-009 and the waiting list item of its
out of scope assumption. An admission becomes the second way to create an account while registration
is closed. Everything else in 071 stands.

## Clarifications

### Session 2026-11-14

- Q: Does the preview list ship on or off, and who switches it? → A: Off by default. The deployment switches it on with an environment variable, and an administrator can override that on the Configuration screen, as for every instance setting. Admitting a waiting entry happens in the admin space and nowhere else.
- Q: Is an admission the invitation the product invariants forbid? → A: No. That invariant is about what happens inside one account. An admission adds a new account to the instance.
- Q: What does the preview page show? → A: A simple card in the layout of the sign in screen. No landing page with sections.
- Q: Can an admitted person sign up through an outside provider? → A: No. With a link, the sign up screen offers the password and the passkey only.
- Q: Is the email address of an account created through a link already verified? → A: Yes. Following the link proves the mailbox, and no verification email is sent.
- Q: Does an administrator admit entries one at a time or several at once? → A: Both. Each waiting entry is admitted on its own, and a number admits that many of the oldest waiting entries.
- Q: Is the preview list reachable over the JSON API? → A: No. Joining it and managing it are screens only. Signing up with a link is the one part the API takes.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - A visitor joins the preview list (Priority: P1)

A visitor who reaches an instance that takes no new account sees a page presenting the product
instead of the sign in screen. They leave their email address and are told they are on the list.

**Why this priority**: without the list there is nobody to admit.

**Independent Test**: set the preview list on with registration closed, open the root address
signed out, submit an address, and find it in the list.

**Acceptance Scenarios**:

1. **Given** the preview list is on and registration is closed, **When** a signed out visitor opens the root address, **Then** they are sent to the preview page at `/preview`, with one field for their email address and a link to sign in.
2. **Given** the preview page, **When** the visitor submits a valid email address, **Then** the address is on the list as waiting and the page confirms it.
3. **Given** an address already on the list, or already holding an account, **When** it is submitted, **Then** the page confirms in the same words and nothing is added.
4. **Given** the preview list is off, or registration is open, **When** a signed out visitor opens the root address, **Then** they are sent to the sign in screen as today, `/preview` answers not found, and sign up is open whenever registration is.

### User Story 2 - An administrator admits somebody (Priority: P1)

An administrator opens the preview list in the admin space and admits one entry. That person
receives an email with a personal link, and the link lets them create an account while registration
stays closed for everybody else.

**Why this priority**: it is the other half of the request. A list nobody can be admitted from is a dead end.

**Independent Test**: with registration closed, admit an entry, follow the link from the email, and
create an account. Then open the sign up screen without the link and get not found.

**Acceptance Scenarios**:

1. **Given** a waiting entry, **When** an administrator admits it, **Then** the entry is admitted, the person is sent an email holding a personal link, and the admission is written in the administrator log.
2. **Given** registration is closed, **When** an admitted person follows their link, **Then** the sign up screen opens with their address filled in, and submitting it, with a password or with a passkey, creates their account and marks the entry registered.
3. **Given** a link that was used, has expired, was replaced, or belonged to a removed entry, **When** it is followed, **Then** it answers not found.
4. **Given** registration is closed, **When** somebody opens or submits sign up without a valid link, **Then** it answers not found as today.
5. **Given** an admitted entry, **When** an administrator admits it again, **Then** a new link is sent and the previous one stops working.
6. **Given** waiting entries, **When** an administrator gives a number to admit, **Then** that many waiting entries, the oldest first, or all of them when fewer wait, are admitted and each person is sent their link.

### User Story 3 - An administrator manages the list (Priority: P2)

**Why this priority**: the list works without it, but an operator has to be able to switch it on and clean it.

**Independent Test**: as an administrator, switch the preview list on, read the list, remove one entry.

**Acceptance Scenarios**:

1. **Given** the Configuration screen, **When** an administrator switches the preview list on or off, **Then** the root address follows from the next request and the change is written in the administrator log.
2. **Given** entries on the list, **When** an administrator opens it, **Then** each entry shows its address, the day it joined and its state, with waiting entries first, oldest first.
3. **Given** an entry, **When** an administrator removes it, **Then** they are warned first, and once they confirm the entry is gone, its link stops working and the removal is written in the administrator log.

### Edge Cases

- Registration opens for everybody while entries wait: the root address goes back to the sign in screen, the entries are kept, and nobody is emailed.
- The preview list is switched off while entries exist: the page goes away, and the entries and the links already sent keep working.
- An administrator admits an address that already holds an account: it is refused with a sentence saying so.
- Somebody submits the form repeatedly, or submits other people's addresses: the form is rate limited and sends no email.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The instance MUST carry one setting saying whether the preview list is on. It MUST be off by default, supplied by the deployment through an environment variable, and overridable by an administrator on the Configuration screen, governing from the next request.
- **FR-002**: While the preview list is on and registration is closed, the root address MUST send a signed out visitor to the preview page at `/preview`. In every other case the root address MUST behave as today and `/preview` MUST answer not found.
- **FR-003**: The preview page MUST use the layout of the sign in screen, and hold one or two sentences saying what the product is, one field for an email address, and the way to sign in.
- **FR-004**: Submitting a valid email address MUST add one waiting entry, recording the language the visitor read the page in.
- **FR-005**: The confirmation MUST read the same whether the address is new, already on the list or already holds an account.
- **FR-006**: Joining the list MUST NOT send any email, and repeated submissions from one visitor MUST be rate limited.
- **FR-007**: The admin space MUST list the entries with their address, the day they joined, their state (waiting, admitted, registered) and the day they were admitted.
- **FR-008**: An administrator MUST be able to admit a waiting or an admitted entry, in the admin space and nowhere else, which sends the person an email in the language of their entry, holding a personal link that works once and for 14 days. Admitting again MUST replace the link.
- **FR-009**: A valid link MUST open sign up while registration is closed, with the email address fixed to the admitted one, for the password form, the passkey form and the API registration endpoint. The screen it opens MUST NOT offer an outside provider.
- **FR-010**: Creating the account MUST mark the entry registered, end its link, and hold the email address as verified, so that no verification email is sent.
- **FR-011**: Without a valid link, everything 071 refuses while registration is closed MUST stay refused, signing up through an outside provider included.
- **FR-012**: An administrator MUST be able to remove an entry, after a warning. Removing MUST end its link and MUST NOT touch any account.
- **FR-013**: Admitting an entry, removing an entry and changing the setting MUST each be written in the administrator log.
- **FR-014**: An account created through an admission MUST be an ordinary account of its own, with no tie to the administrator who admitted it.
- **FR-015**: An administrator MUST be able to admit a given number of waiting entries at once, the oldest first, each admitted as FR-008 and FR-013 say.

### Key Entities

- **Preview list entry**: one email address waiting for, or given, the right to sign up. It carries a language, a state, the days it joined, was admitted and registered, and at most one working link. It belongs to the instance, never to an account.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: A visitor joins the list in under 30 seconds, filling one field.
- **SC-002**: With registration closed, an admitted person creates an account, and nobody else does by any of the routes 071 lists.
- **SC-003**: An administrator admits somebody in under one minute, and the email reaches that person within five minutes.
- **SC-004**: An instance that sets nothing shows and refuses exactly what it does before the change.
- **SC-005**: No answer of the preview page tells a visitor whether an address is known to the instance.
- **SC-006**: The preview page and the list in the admin space are fully usable with the keyboard alone and with a screen reader.

## Assumptions

- Entries stay until an administrator removes them. Nothing expires them.
- Out of scope: telling the list when registration opens for everybody, a confirmation email on joining, and the list over the API.
