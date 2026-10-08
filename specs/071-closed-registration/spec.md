# Feature Specification: Closing Registration on an Instance

**Feature Branch**: `071-closed-registration` (git branch `2026-10-07-closed-registration`)

**Created**: 2026-10-07

**Status**: Draft

**Input**: User description: "Config to prevent register"

## Clarifications

### Session 2026-10-07

- Q: When registration is closed, how does an account get onto the instance, the first one or a relative later? → A: The operator opens registration briefly, the person signs up, and the operator closes it again. No other way is added.
- Q: Should the Configuration screen warn an administrator before they open registration? → A: Yes, when opening only. Closing asks nothing, since it takes nothing from an existing account.
- Q: Should the API status endpoint report whether registration is open? → A: No. The status endpoint is left unchanged; a client learns from the 404 of the register endpoint.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - The operator closes registration (Priority: P1)

Somebody running an instance for their family does not want strangers to create accounts on it. They
close registration, and from then on nobody can create an account by any route, while everybody who
already has one carries on as before.

**Why this priority**: it is the whole request. Every other story only refines it.

**Independent Test**: close registration, try each of the four ways an account is created today, and
confirm none of them creates one, then sign in as an existing person.

**Acceptance Scenarios**:

1. **Given** registration is closed, **When** a visitor opens the sign up screen, **Then** it answers not found.
2. **Given** registration is closed, **When** the sign up form is submitted, with a password or with a passkey, **Then** it answers not found and no account exists.
3. **Given** registration is closed, **When** an API client calls the registration endpoint, **Then** it answers not found and no account exists.
4. **Given** registration is closed, **When** somebody the instance has never seen finishes signing in through an outside provider, **Then** they are sent back to the sign in screen with a sentence saying this instance does not accept new accounts, and no account exists.
5. **Given** registration is closed, **When** an existing person signs in with a password, a passkey, a link by email or a provider they connected earlier, **Then** they are signed in as before.

### User Story 2 - The screens stop offering to create an account (Priority: P2)

A visitor on a closed instance is never invited to do something that will answer not found.

**Why this priority**: without it the feature works but the screens lead people into a dead end.

**Independent Test**: close registration and look at every screen that offers an account today.

**Acceptance Scenarios**:

1. **Given** registration is closed, **When** a visitor opens the sign in screen, the marketplace or the API documentation, **Then** no screen offers to create an account.
2. **Given** registration is open, **When** the same screens are opened, **Then** they offer it exactly as today.

### User Story 3 - An administrator changes it without redeploying (Priority: P2)

An administrator opens or closes registration from the Configuration screen of the admin space, with
the other signing in settings, and can put it back to what the deployment supplies.

**Why this priority**: an operator who wants to let one relative in should not have to edit a file and restart.

**Independent Test**: as an administrator, close registration on the Configuration screen, then try to sign up.

**Acceptance Scenarios**:

1. **Given** the deployment leaves registration open, **When** an administrator closes it on the Configuration screen, **Then** the next sign up attempt answers not found and the change is written in the administrator log.
2. **Given** an administrator closed registration, **When** they revert the setting, **Then** the deployment value governs again.
3. **Given** registration is closed, **When** an administrator opens it on the Configuration screen, **Then** they are warned that anybody who reaches the instance can create an account, and it opens only once they save again.

### Edge Cases

- A sign up form left open in a tab while registration closes is refused on submission, as any other attempt.
- An account created just before registration closed keeps working: email verification and adding its first passkey are not sign ups.
- A signed in person connecting a provider from their security settings is unaffected, since no account is created.
- An instance with no account at all and registration closed accepts nobody until the operator opens registration. While it is open, anybody who reaches the instance can sign up, which the operator accepts by opening it.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The instance MUST carry one setting saying whether registration is open, and it MUST default to open, so an instance upgraded without touching it behaves as today.
- **FR-002**: The deployment MUST supply the setting, and an administrator MUST be able to override and revert it from the Configuration screen, in the signing in group, with each change written in the administrator log, and opening it there MUST warn first while closing it MUST NOT.
- **FR-003**: A change to the setting MUST govern from the next request, without a redeploy.
- **FR-004**: While registration is closed, the sign up screen and both sign up submissions, with a password and with a passkey, MUST answer not found.
- **FR-005**: While registration is closed, the API registration endpoint MUST answer not found.
- **FR-006**: While registration is closed, signing in through an outside provider MUST refuse somebody without an account, with a sentence saying the instance does not accept new accounts, and MUST still sign in somebody whose account is connected to that provider.
- **FR-007**: Closing registration MUST NOT change anything for an existing account: signing in by every method, resetting a password, verifying an email address, adding a passkey and connecting a provider.
- **FR-008**: While registration is closed, no screen MUST offer to create an account: not the sign in screen, the marketplace header, the marketplace page or the API documentation page.
- **FR-009**: The product MUST NOT add another way to create an account while registration is closed; letting somebody in, the first account included, means opening registration and closing it again.
- **FR-010**: The setting MUST behave identically on hosted and self hosted instances.

### Key Entities

- **Registration setting**: one instance wide answer, open or closed, supplied by the deployment and optionally overridden by an administrator. It belongs to the instance, never to an account.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: With registration closed, none of the four routes that create an account today (password form, passkey form, API, outside provider) creates one.
- **SC-002**: With registration closed, every sign in method an existing person uses still signs them in.
- **SC-003**: An administrator closes registration in under one minute from the Configuration screen, with no redeploy.
- **SC-004**: An instance that sets nothing shows and accepts sign up exactly as before the change.
- **SC-005**: With registration closed, none of the four screens listed in FR-008 offers to create an account.

## Assumptions

- Not found, rather than a page explaining that sign up is closed, follows the link by email setting and the flags: a capability that is off does not exist.
- The public marketing site lives outside this application and is out of scope; a link from it to sign up answers not found.
- Out of scope: invitations (forbidden by the product invariants), allow lists of email domains, a ceiling on the number of accounts and waiting lists.
